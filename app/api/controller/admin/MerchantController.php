<?php


namespace app\api\controller\admin;


use app\models\store\StoreService;
use app\models\store\StoreProduct;
use app\models\system\SystemMerchant;
use app\models\system\SystemStore;
use app\models\user\User;
use app\Request;
use crmeb\services\UtilService;

class MerchantController
{

    /**
     * 申请成功商家
     * @param Request $request
     * @return mixed
     */
    public function apply(Request $request)
    {
        $merchant = $request->post()['merchant'];
        $uid = $request->uid();
        SystemMerchant::saveCreate($merchant, $uid);
        return app('json')->successful([]);
    }
    
    /**
     * 产品中心数据
     * @param Request $request
     * @return mixed
     */
    public function data(Request $request)
    {
        $uid = $request->uid();
        $storeinfo = SystemStore::getUserMer($uid);
        $data = StoreProduct::getProductData($storeinfo['id']);
        if ($data) return app('json')->successful($data);
        return app('json')->successful([]);
    }
    
    /**
     * 订单列表
     * @param Request $request
     * @return mixed
     */
    public function plist(Request $request)
    {
        $uid = $request->uid();
        $where = UtilService::getMore([
                ['status', ''],
                ['is_del', 0],
                ['data', ''],
                ['type', ''],
                ['order', ''],
                ['page', 0],
                ['limit', 0]
            ], $request);
            if (!$where['limit']) return app('json')->successful([]);
            return app('json')->successful(StoreProduct::productList($uid,$where));
    }

    /**
     * 商品上下架
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cancel(Request $request)
    {
        list($id) = UtilService::postMore([['id', 0]], $request, true);
        if (!$id) return app('json')->fail('参数错误');
        if (StoreProduct::cancelProduct($id, $request->uid()))
            return app('json')->successful('操作成功');
            return app('json')->fail(StoreProduct::getErrorInfo('操作失败'));
    }

    /**
     * 商家首页
     * @param Request $request
     * @return mixed
     */
    public function home(Request $request){
        $uid = $request->uid();
        $merList=  SystemStore::getUserMer($uid);
        $status =0;

        $mer = (object)[];
        if ($merList){
            $merList = $merList->toArray();
            if (isset($merList[0])){
                $mer = $merList[0];
            }else{
                $mer = $merList ;
            }
            if ($mer['status'] == 0){
                $status = 1;
            }else if($mer['status'] == 1){
                $status = 2;
            }
        }

        // status 0 未申请  1已申请未审核通过 2 已申请已审核通过
        return app('json')->successful([
            'status' =>$status,
            'mer' =>$mer,
        ]);
    }

    /**
     * 获取商家二维码
     * @param Request $request
     * @return mixed
     */
    
    public function maurl(Request $request){
        $uid = $request->uid();
        $merList=  SystemStore::getUserMer($uid);
       
        $store_id =0;
    
        $erma_url='';
        if ($merList){
            $merList = $merList->toArray();
            $store_id = $merList['id'];
            $erma_url= $merList['erma_url'];
        }
        
        if(!$store_id) return $this->failed('数据不存在');
       
        $ermaImg = '';
        if(!$erma_url){
            //$siteUrl = sysConfig('site_url');
            $siteUrl = "http://www.dshqfsc.com";
            $codeUrl = UtilService::setHttpType($siteUrl, 1)."/order/detail/".$store_id;//二维码链接
            $name = date("Y-m-d")."-order-sale-".time().".jpg";
            $imageInfo = UtilService::getQRCodePath($codeUrl, $name);
            if(!$imageInfo) return app('json')->fail('二维码生成失败');
            if (!$imageInfo) return app('json')->fail('二维码生成失败');
            $data =[];
            //计算二维码图片地址
            $arr = array();
            $arr = explode("//",$siteUrl);
            $farr = explode(".",$arr[1]);
            $ermaImg = 'img';
            if($farr[0]!='www'){
                $ermaImg = $farr[0]."-".$ermaImg;
            }
            // $orderImg = $orderImg.".".$farr[1].".".$farr[2]."/".$name;
            $data['erma_url']="img-bqw.dshqfsc.com/".$name;
            $ermaImg = $data['erma_url'];
            SystemStore::edit($data,$store_id);
        }
       
        // status 0 未申请  1已申请未审核通过 2 已申请已审核通过
        return app('json')->successful([
           
            'ermaImg' =>$ermaImg,
        ]);
    }
    
    /**
     * 客服列表
     * @param Request $request
     * @return mixed
     */
    public function serviceList(Request $request)
    {
        // TODO 验证权限
        $uid = $request->uid();
        $q = UtilService::getMore([
            ['store_id', ''],
        ], $request);

        $list = StoreService::getMerService($q['store_id']);
        return app('json')->successful(
            $list ?$list->toArray():[]
        );
    }

    /**
     * 新增客服
     * @param Request $request
     * @return mixed
     */
    public function serviceAdd(Request $request){
        $q = UtilService::getMore([
            ['id', ''],
            ['is_admin', 0],
            ['is_check', 0],
            ['real_name', ''],
            ['phone', ''],
        ], $request);

        $user = User::getByPhone($q['phone']);

        if (!$user || !$user->uid)  return app('json')->fail('用户不存在，请让用户先绑定手机');

        // 验证是否绑定
        if (StoreService::isBind($q['id'], $user->uid)) return app('json')->fail('用户已经是客服请直接修改');
        StoreService::create([
            'store_id' => $q['id'],
            'uid' => $user->uid,
            'avatar' =>$user->avatar,
            'nickname' =>$user->nickname,
            'add_time' =>time(),
            'real_name' =>$q['real_name'],
            'is_admin' =>$q['is_admin'],
            'is_check' =>$q['is_check'],
        ]);

        return app('json')->successful([]);
    }

    /**
     * 删除客服
     * @param Request $request
     * @return mixed
     */
    public function serviceDel(Request $request){
        $q = UtilService::getMore([
            ['id', ''],
            ['status', false],
        ], $request);

        StoreService::del($q['id']);

        return app('json')->successful([]);

    }

    public function serviceAdmin(Request $request){
        $q = UtilService::getMore([
            ['id', ''],
            ['status', false],
        ], $request);
        StoreService::setAdmin($q['id'], $q['status']);
        return app('json')->successful([]);
    }

    public function serviceCheck(Request $request){
        $q = UtilService::getMore([
            ['id', ''],
            ['status', false],
        ], $request);
        StoreService::setCheck($q['id'], $q['status']);
        return app('json')->successful([]);
    }

    /**
     * 更新客服
     * @param Request $request
     * @return mixed
     */
    public function serviceUpdate(Request $request){
        return app('json')->successful([]);

    }


    /**
     * 门店列表
     * @param Request $request
     * @return mixed
     */
    public function StoreList(Request $request){

        // TODO 验证权限
        $uid = $request->uid();
        $q = UtilService::getMore([
            ['mer_id', ''],
        ], $request);
        $list = SystemStore::getStoreList($q['mer_id']);

        return app('json')->successful($list ?$list->toArray():[]);
    }

    public function storeInfo(Request $request){
        $uid = $request->uid();
        $q = UtilService::getMore([
            ['id', ''],
        ], $request);

        $store = SystemStore::getStoreById($q['id']);
        return app('json')->successful($store->toArray());
    }

    /**
     * 新增门店
     * @param Request $request
     * @return mixed
     */
    public function storeAdd(Request $request){
        $uid = $request->uid();

        $q = UtilService::getMore([
            ['id', 0],
            ['name', ''],
            ['introduction', ''],
            ['phone', ''],
            ['address', ''],

            ['detailed_address', ''],
            ['image', ''],
            ['latitude', ''],
            ['longitude', ''],
            ['day_time', ''],
            ['valid_time', ''],
            ['is_show', 0],

            ['mer_name', ''],
            ['link_name', ''],
            ['link_phone', 0],

        ], $request);

        $data = [
            'name' => $q['name'],
            'introduction' => $q['introduction'],
            'phone' => $q['phone'],
            'address' => $q['address']['province'].','.$q['address']['city'].','.$q['address']['district'],
            'detailed_address' => $q['detailed_address'],
            'image' => $q['image'],
            'latitude' => $q['latitude'],
            'longitude' => $q['longitude'],
            'day_time' => $q['day_time'],
            'valid_time' => $q['valid_time'],
            'is_show' => $q['is_show'],
            'user_id' => $uid,
            'add_time' => time(),
            'mer_name' => $q['mer_name'],
            'link_name' => $q['link_name'],
            'link_phone' => $q['link_phone'],
        ];

        if (empty($q['id'])){
            SystemStore::create($data);
        }else{
            unset($data['add_time']);
            SystemStore::where('id',$q['id'])->update($data,['id' => $q['id']]);
        }

        // 验证是否绑定

        return app('json')->successful($data);
    }


    public function storeDel(Request $request){
        $q = UtilService::getMore([
            ['id', ''],
            ['status', false],
        ], $request);
        SystemStore::setDel($q['id'],1);
        return app('json')->successful([]);
    }

    public function storeShow(Request $request){
        $q = UtilService::getMore([
            ['id', ''],
            ['status', false],
        ], $request);

        SystemStore::setShow($q['id'],$q['status']);
        return app('json')->successful([]);
    }

    /**
     * 门店更新
     * @param Request $request
     * @return mixed
     */
    public function storeUpdate(Request $request){
        return app('json')->successful([]);
    }




}