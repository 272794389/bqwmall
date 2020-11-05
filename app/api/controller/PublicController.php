<?php

namespace app\api\controller;

use app\admin\model\system\SystemAttachment;
use app\models\store\StoreCategory;
use app\models\store\StoreCouponIssue;
use app\models\store\StoreProduct;
use app\models\store\StoreService;
use app\models\store\StoreCoupon;
use app\models\system\Express;
use app\models\system\SystemCity;
use app\models\system\SystemStore;
use app\models\system\SystemStoreStaff;
use app\models\user\UserBill;
use app\models\user\WechatUser;
use app\Request;
use crmeb\services\CacheService;
use crmeb\services\UtilService;
use crmeb\services\workerman\ChannelService;
use think\facade\Cache;
use app\models\article\Article;
use crmeb\services\upload\Upload;
use app\models\store\StorePayOrder;


/**
 * 公共类
 * Class PublicController
 * @package app\api\controller
 */
class PublicController
{
    /**
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        $banner = sys_data('routine_home_banner') ?: [];//TODO 首页banner图
        $hbanner = sys_data('routine_hui_home_banner') ?: [];//TODO 本地特惠首页banner图
        $sbanner = sys_data('routine_store_home_banner') ?: [];//TODO 周边的店首页banner图
        $nbanner = sys_data('routine_net_home_banner') ?: [];//TODO 网店首页banner图
        $pbanner = sys_data('routine_shop_home_banner') ?: [];//TODO 商品中心首页banner图
        $activity = sys_data('routine_home_activity', 3) ?: [];//TODO 首页活动区域图片
        $site_name = sys_config('site_name');
        $routine_index_page = sys_data('routine_index_page');
        $info['fastInfo'] = $routine_index_page[0]['fast_info'] ?? '';//sys_config('fast_info');//TODO 快速选择简介
        $info['bastInfo'] = $routine_index_page[0]['bast_info'] ?? '';//sys_config('bast_info');//TODO 精品推荐简介
        $info['firstInfo'] = $routine_index_page[0]['first_info'] ?? '';//sys_config('first_info');//TODO 首发新品简介
        $info['salesInfo'] = $routine_index_page[0]['sales_info'] ?? '';//sys_config('sales_info');//TODO 促销单品简介
        $logoUrl = sys_config('routine_index_logo');//TODO 促销单品简介
        if (strstr($logoUrl, 'http') === false && $logoUrl) $logoUrl = sys_config('site_url') . $logoUrl;
        $logoUrl = str_replace('\\', '/', $logoUrl);
        $fastNumber = sys_config('fast_number', 0);//TODO 快速选择分类个数
        $bastNumber = sys_config('bast_number', 0);//TODO 精品推荐个数
        $firstNumber = sys_config('first_number', 0);//TODO 首发新品个数
        $info['fastList'] = StoreCategory::getIndexList(0, 10,false);//TODO 分类个数
        $info['sfastList'] = StoreCategory::getIndexList(10, 10,false);//TODO 分类个数
        $info['tfastList'] = StoreCategory::getIndexList(20, 10,false);//TODO 分类个数
        $info['ffastList'] = StoreCategory::getIndexList(30, 10,false);//TODO 分类个数
        
        $info['bastList'] = StoreProduct::getProductIndexListByBelong(20, $request->uid(),0, false);//TODO 商品中心产品列表
        $info['netGoodList'] = StoreProduct::getProductIndexListByBelong(20, $request->uid(),1, false);//TODO 网店商品列表
        $info['nearGoodList'] = StoreProduct::getProductListByBelong((int)$firstNumber, $request->uid(),2, false);//TODO 吃喝玩乐商品列表
        $info['hostList'] = StoreProduct::getBestProduct('*',(int)$firstNumber, $request->uid(),false);
        
        
        $couponList = StoreCouponIssue::getIssueCouponList($request->uid(), 3);
        $subscribe = WechatUser::where('uid', $request->uid() ?? 0)->value('subscribe') ? true : false;
        $newGoodsBananr = sys_config('new_goods_bananr');
        $tengxun_map_key = sys_config('tengxun_map_key');
        $article = Article::getArticleNotice(1);
        return app('json')->successful(compact('banner','hbanner','sbanner','nbanner','pbanner', 'info', 'activity',  'logoUrl', 'couponList', 'site_name', 'subscribe', 'newGoodsBananr', 'tengxun_map_key','article'));
    }
    
    public function getNearStoreData(Request $request)
    {
        $storeList = SystemStore::netlst(1, 10,0,0,'','desc','','');
        $nearGoodList = StoreProduct::getNetIndexList(10,$request->uid());
        return app('json')->successful(compact('storeList','nearGoodList'));
    }
    

    /**
     * 获取分享配置
     * @return mixed
     */
    public function share()
    {
        $data['img'] = sys_config('wechat_share_img');
        if (strstr($data['img'], 'http') === false) $data['img'] = sys_config('site_url') . $data['img'];
        $data['img'] = str_replace('\\', '/', $data['img']);
        $data['title'] = sys_config('wechat_share_title');
        $data['synopsis'] = sys_config('wechat_share_synopsis');
        return app('json')->successful(compact('data'));
    }


    /**
     * 获取个人中心菜单
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function menu_user(Request $request)
    {
        $menusInfo = sys_data('routine_my_menus') ?? [];
        $user = $request->user();
        $vipOpen = sys_config('vip_open');
        $vipOpen = is_string($vipOpen) ? (int)$vipOpen : $vipOpen;
        foreach ($menusInfo as $key => &$value) {
            $value['pic'] = set_file_url($value['pic']);
            
            if ($value['id'] == 137 && !(intval(sys_config('store_brokerage_statu')) == 2 || $user->is_promoter == 1))
                unset($menusInfo[$key]);
            if ($value['id'] == 174 && !StoreService::orderServiceStatus($user->uid))
                unset($menusInfo[$key]);
            if (((!StoreService::orderServiceStatus($user->uid)) && (!SystemStoreStaff::verifyStatus($user->uid))) && $value['wap_url'] === '/order/order_cancellation')
                unset($menusInfo[$key]);
            if (((!StoreService::orderServiceStatus($user->uid)) && (!SystemStoreStaff::verifyStatus($user->uid))) && $value['wap_url'] === '/admin/order_cancellation/index')
                unset($menusInfo[$key]);
            if ((!StoreService::orderServiceStatus($user->uid)) && $value['wap_url'] === '/admin/order/index')
                unset($menusInfo[$key]);
            if ($value['wap_url'] == '/user/vip' && !$vipOpen)
                unset($menusInfo[$key]);
            if ($value['wap_url'] == '/customer/index' && !StoreService::orderServiceStatus($user->uid))
                unset($menusInfo[$key]);
        }
        return app('json')->successful(['routine_my_menus' => $menusInfo]);
    }

    /**
     * 热门搜索关键字获取
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function search()
    {
        $routineHotSearch = sys_data('routine_hot_search') ?? [];
        $searchKeyword = [];
        if (count($routineHotSearch)) {
            foreach ($routineHotSearch as $key => &$item) {
                array_push($searchKeyword, $item['title']);
            }
        }
        return app('json')->successful($searchKeyword);
    }


    /**
     * 图片上传
     * @param Request $request
     * @return mixed
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function upload_image(Request $request)
    {
        $data = UtilService::postMore([
            ['filename', 'file'],
        ], $request);
        if (!$data['filename']) return app('json')->fail('参数有误');
        if (Cache::has('start_uploads_' . $request->uid()) && Cache::get('start_uploads_' . $request->uid()) >= 100) return app('json')->fail('非法操作');
        $upload_type = sys_config('upload_type', 1);
        $upload = new Upload((int)$upload_type, [
            'accessKey' => sys_config('accessKey'),
            'secretKey' => sys_config('secretKey'),
            'uploadUrl' => sys_config('uploadUrl'),
            'storageName' => sys_config('storage_name'),
            'storageRegion' => sys_config('storage_region'),
        ]);
        $info = $upload->to('store/comment')->validate()->move($data['filename']);
        if ($info === false) {
            return app('json')->fail($upload->getError());
        }
        $res = $upload->getUploadInfo();
        SystemAttachment::attachmentAdd($res['name'], $res['size'], $res['type'], $res['dir'], $res['thumb_path'], 1, $upload_type, $res['time'], 2);
        if (Cache::has('start_uploads_' . $request->uid()))
            $start_uploads = (int)Cache::get('start_uploads_' . $request->uid());
        else
            $start_uploads = 0;
        $start_uploads++;
        Cache::set('start_uploads_' . $request->uid(), $start_uploads, 86400);
        $res['dir'] = path_to_url($res['dir']);
        if (strpos($res['dir'], 'http') === false) $res['dir'] = $request->domain() . $res['dir'];
        return app('json')->successful('图片上传成功!', ['name' => $res['name'], 'url' => $res['dir']]);
    }

    /**
     * 物流公司
     * @return mixed
     */
    public function logistics()
    {
        $expressList = Express::lst();
        if (!$expressList) return app('json')->successful([]);
        return app('json')->successful($expressList->hidden(['code', 'id', 'sort', 'is_show'])->toArray());
    }

    /**
     * 短信购买异步通知
     *
     * @param Request $request
     * @return mixed
     */
    public function sms_pay_notify(Request $request)
    {
        list($order_id, $price, $status, $num, $pay_time, $attach) = UtilService::postMore([
            ['order_id', ''],
            ['price', 0.00],
            ['status', 400],
            ['num', 0],
            ['pay_time', time()],
            ['attach', 0],
        ], $request, true);
        if ($status == 200) {
            ChannelService::instance()->send('PAY_SMS_SUCCESS', ['price' => $price, 'number' => $num], [$attach]);
            return app('json')->successful();
        }
        return app('json')->fail();
    }

    /**
     * 记录用户分享
     * @param Request $request
     * @return mixed
     */
    public function user_share(Request $request)
    {
        return app('json')->successful(UserBill::setUserShare($request->uid()));
    }

    /**
     * 获取图片base64
     * @param Request $request
     * @return mixed
     */
    public function get_image_base64(Request $request)
    {
        list($imageUrl, $codeUrl) = UtilService::postMore([
            ['image', ''],
            ['code', ''],
        ], $request, true);
        try {
            $codeTmp = $code = $codeUrl ? image_to_base64($codeUrl) : false;
            if (!$codeTmp) {
                $putCodeUrl = put_image($codeUrl);
                $code = $putCodeUrl ? image_to_base64($_SERVER['HTTP_HOST'] . '/' . $putCodeUrl) : false;
                $code ?? unlink($_SERVER["DOCUMENT_ROOT"] . '/' . $putCodeUrl);
            }

            $imageTmp = $image = $imageUrl ? image_to_base64($imageUrl) : false;
            if (!$imageTmp) {
                $putImageUrl = put_image($imageUrl);
                $image = $putImageUrl ? image_to_base64($_SERVER['HTTP_HOST'] . '/' . $putImageUrl) : false;
                $image ?? unlink($_SERVER["DOCUMENT_ROOT"] . '/' . $putImageUrl);
            }
            return app('json')->successful(compact('code', 'image'));
        } catch (\Exception $e) {
            return app('json')->fail($e->getMessage());
        }
    }

    /**
     * 门店列表
     * @return mixed
     */
    public function store_list(Request $request)
    {
        list($latitude, $longitude, $page, $limit,$sid,$cid,$keyword,$salesOrder,$condition,$city,$district) = UtilService::getMore([
            ['latitude', ''],
            ['longitude', ''],
            ['page', 1],
            ['limit', 10],
            ['sid', 0],
            ['cid', 0],
            ['keyword', ''],
            ['salesOrder', ''],
            ['condition', 1],
            ['city', ''],
            ['district', '']
        ], $request, true);
        if($condition==1){//同城
          $list = SystemStore::lst($latitude, $longitude, $page, $limit,$sid,$cid,$keyword,$salesOrder,$condition);
          if (!$list) $list = [];
          $data['list'] = $list;
        }else{//查询网店
            $list = SystemStore::netlst($page, $limit,$sid,$cid,$keyword,$salesOrder,$city,$district);
            if (!$list) $list = [];
            $data['list'] = $list;
        }
        $data['tengxun_map_key'] = sys_config('tengxun_map_key');
        return app('json')->successful($data);
    }
    
    
    /**
     * 门店列表
     * @return mixed
     */
    public function store_index_list(Request $request)
    {
    	list($latitude, $longitude, $page, $limit,$sid,$cid,$keyword,$salesOrder,$condition,$city,$district) = UtilService::getMore([
    			['latitude', ''],
    			['longitude', ''],
    			['page', 1],
    			['limit', 15],
    			['sid', 0],
    			['cid', 0],
    			['keyword', ''],
    			['salesOrder', ''],
    			['condition', 1],
    			['city', ''],
    			['district', '']
    	], $request, true);
    	$list = SystemStore::indexlst($latitude, $longitude, $page, $limit,$sid,$cid,$keyword,$salesOrder,$condition);
    	if (!$list) $list = [];
    	$data['list'] = $list;
    	$data['tengxun_map_key'] = sys_config('tengxun_map_key');
    	return app('json')->successful($data);
    }
    
    
    /**
     * 同城商品列表
     * @return mixed
     */
    public function tgoods_list(Request $request)
    {
        list($latitude, $longitude, $page, $limit,$sid,$cid,$keyword,$salesOrder,$priceOrder) = UtilService::getMore([
            ['latitude', ''],
            ['longitude', ''],
            ['page', 1],
            ['limit', 10],
            ['sid', 0],
            ['cid', 0],
            ['keyword', ''],
            ['salesOrder', ''],
            ['priceOrder', '']
        ], $request, true);
        $list = StoreProduct::lst($latitude, $longitude,sys_config('tengxun_map_key'), $page, $limit,$sid,$cid,$keyword,$salesOrder,$priceOrder);
        if (!$list) $list = [];
        $data['list'] = $list;
        $data['tengxun_map_key'] = sys_config('tengxun_map_key');
        return app('json')->successful($data);
    }
    
    /**
     * 首页显示
     * @return mixed
     */
    public function txgoods_list(Request $request)
    {
    	list($latitude, $longitude, $page, $limit,$sid,$cid,$keyword,$salesOrder,$priceOrder) = UtilService::getMore([
    			['latitude', ''],
    			['longitude', ''],
    			['page', 1],
    			['limit', 30],
    			['sid', 0],
    			['cid', 0],
    			['keyword', ''],
    			['salesOrder', ''],
    			['priceOrder', '']
    	], $request, true);
    	$list = StoreProduct::xlst($latitude, $longitude,sys_config('tengxun_map_key'), $page, $limit,$sid,$cid,$keyword,$salesOrder,$priceOrder);
    	if (!$list) $list = [];
    	$data['list'] = $list;
    	$data['tengxun_map_key'] = sys_config('tengxun_map_key');
    	return app('json')->successful($data);
    }
    
    
    
    
    /**
     * 同城商品推荐列表
     * @return mixed
     */
    public function thgoods_list(Request $request)
    {
    	list($latitude, $longitude, $page, $limit,$sid,$cid,$keyword,$salesOrder,$priceOrder) = UtilService::getMore([
    			['latitude', ''],
    			['longitude', ''],
    			['page', 1],
    			['limit', 20],
    			['sid', 0],
    			['cid', 0],
    			['keyword', ''],
    			['salesOrder', ''],
    			['priceOrder', '']
    	], $request, true);
    	$list = StoreProduct::hlst($latitude, $longitude,sys_config('tengxun_map_key'), $page, $limit,$sid,$cid,$keyword,$salesOrder,$priceOrder);
    	if (!$list) $list = [];
    	$data['list'] = $list;
    	$data['tengxun_map_key'] = sys_config('tengxun_map_key');
    	return app('json')->successful($data);
    }
    

    /**
     * 查找城市数据
     * @param Request $request
     * @return mixed
     */
    public function city_list(Request $request)
    {
        $list = CacheService::get('CITY_LIST', function () {
            $list = SystemCity::with('children')->field(['city_id', 'name', 'id', 'parent_id'])->where('parent_id', 0)->order('id asc')->select()->toArray();
            $data = [];
            foreach ($list as &$item) {
                $value = ['v' => $item['city_id'], 'n' => $item['name']];
                if ($item['children']) {
                    foreach ($item['children'] as $key => &$child) {
                        $value['c'][$key] = ['v' => $child['city_id'], 'n' => $child['name']];
                        unset($child['id'], $child['area_code'], $child['merger_name'], $child['is_show'], $child['level'], $child['lng'], $child['lat'], $child['lat']);
                        if (SystemCity::where('parent_id', $child['city_id'])->count()) {
                            $child['children'] = SystemCity::where('parent_id', $child['city_id'])->field(['city_id', 'name', 'id', 'parent_id'])->select()->toArray();
                            foreach ($child['children'] as $kk => $vv) {
                                $value['c'][$key]['c'][$kk] = ['v' => $vv['city_id'], 'n' => $vv['name']];
                            }
                        }
                    }
                }
                $data[] = $value;
            }
            return $data;
        }, 0);
        return app('json')->successful($list);
    }
    
    public function store_detail(Request $request, $id, $type = 0){
        if (!$id || !($storeInfo = SystemStore::getValidStore($id))) return app('json')->fail('商户不存在或已下架');
        $storeInfo['cate_name'] = StoreCategory::where('id',$storeInfo['cat_id'])->value('cate_name');
        if($storeInfo['label']){
            $label_list = explode(',',$storeInfo['label']);
            $data['label_list'] = $label_list;
        }
        $data['storeInfo'] = $storeInfo;
        $data['mapKey'] = sys_config('tengxun_map_key');
        $data['good_list'] = StoreProduct::getStoreGoodList($id,50, '*');
        $data['tgood_list'] = StoreProduct::getListByBelong($storeInfo['belong_t'],$storeInfo['cat_id'],30, '*');
        $data['ogood_list'] = StoreProduct::getTuiList(30, '*');
        
        //获取商家抵扣券
        $couponList = StoreCoupon::where('status',1)->where('is_del',0)
        //->whereFindinSet('product_id', $id)
        ->where('belong',$storeInfo['belong'])
        ->field('*')
        ->order('coupon_price', 'asc')
        ->limit(2)
        ->select();
        //获取商家抵扣券
        $acouponList = StoreCoupon::where('status',1)->where('is_del',0)
        //->whereFindinSet('product_id', $id)
        ->where('belong',$storeInfo['belong'])
        ->field('*')
        ->order('coupon_price', 'asc')
        ->select();
        $couponList = count($couponList) ? $couponList->toArray() : [];
        $acouponList = count($acouponList) ? $acouponList->toArray() : [];
        $data['coupon_list'] = $couponList;
        $data['acoupon_List'] = $acouponList;
        return app('json')->successful($data);
    }
    
    public function shoppay(Request $request){
        list($amount, $store_id,$check_id) = UtilService::postMore([
            ['amount', 0],
            ['store_id', 0],
            ['check_id', 0]
        ], $request, true);
        $uid = $request->uid();
        if ($amount<0.1)
            return app('json')->fail('消费金额不能小于0');
        if ($store_id<0)
            return app('json')->fail('请选择消费商家');
        if(!$uid){
            return app('json')->fail('请先登录');
        }
        $order = StorePayOrder::addOrder($uid,$store_id,$amount,$check_id);//创建消费订单
        if ($order)
            return app('json')->success('操作成功', ['order_id' => $order['id']]);
        else
        return app('json')->fail('订单创建失败'); 
    }
    
    public function get_order(Request $request, $id){
        if (!$id || !($orderinfo = StorePayOrder::getPayOrder($request->uid(),$id))) return app('json')->fail('订单不存在');
        $data['orderinfo'] = $orderinfo;
        return app('json')->successful($data);
    }
    
    public function computedOrder(Request $request){
        $uid = $request->uid();
        list($useIntegral,$useCoupon,$usePayIntegral,$orderid) = UtilService::postMore([
           ['useIntegral', 0],['useCoupon', 0],['usePayIntegral', 0],['orderid', 0],
        ], $request, true);
        if (!$orderid || !($orderinfo = StorePayOrder::getPayOrder($request->uid(),$orderid))) return app('json')->fail('订单不存在');
        StorePayOrder::computerOrder($orderid,$useIntegral,$useCoupon,$usePayIntegral);//创建消费订单
        $order = StorePayOrder::getPayOrder($request->uid(),$orderid);
        $data['orderinfo'] = $order;
        return app('json')->successful($data);
    }
    
    
    
    
    
    public function pay_order(Request $request){
        $uid = $request->uid();
        list($order_id, $payType, $formId,$from) = UtilService::postMore([['order_id', 0],'payType', ['formId', ''],['from', 'weixin'] ], $request, true);
        if (!$order_id || !($orderinfo = StorePayOrder::getPayOrder($uid,$order_id))) return app('json')->fail('订单不存在');
        $orderId = $orderinfo['order_id'];
        switch ($payType) {
            case "weixin":
                $orderInfo = $orderinfo->toArray();
                if ($orderInfo['paid']) return app('json')->fail('支付已支付!');
                //支付金额为0
                if (bcsub((float)$orderInfo['pay_amount'], 0, 2) <= 0) {
                    //创建订单jspay支付
                    $payPriceStatus = StorePayOrder::jsPayPrice($orderId, $uid, $formId);
                    if ($payPriceStatus)//0元支付成功
                        return app('json')->status('success', '微信支付成功', $orderinfo);
                        else
                            return app('json')->status('pay_error', StorePayOrder::getErrorInfo());
                } else {
                    try {
                        if ($from == 'routine') {
                            $jsConfig = StorePayOrder::jsPay($orderinfo); //创建订单jspay
                        } else if ($from == 'weixinh5') {
                            $jsConfig = StorePayOrder::h5Pay($orderinfo);
                        } else {
                            $jsConfig = StorePayOrder::wxPay($orderinfo);
                        }
                    } catch (\Exception $e) {
                        return app('json')->status('pay_error', $e->getMessage(), $orderinfo);
                    }
                    $info['jsConfig'] = $jsConfig;
                    if ($from == 'weixinh5') {
                        return app('json')->status('wechat_h5_pay', '订单创建成功', $info);
                    } else {
                        return app('json')->status('wechat_pay', '订单创建成功', $info);
                    }
                }
                break;
             case 'yue':
                    if (StorePayOrder::yuePay($orderId, $request->uid(), $formId))
                        return app('json')->status('success', '余额支付成功', "");
                        else {
                            $errorinfo = StorePayOrder::getErrorInfo();
                            if (is_array($errorinfo))
                                return app('json')->status($errorinfo['status'], $errorinfo['msg'], "");
                                else
                                    return app('json')->status('pay_error', $errorinfo);
                        }
                        break;
        }
        
    }

}