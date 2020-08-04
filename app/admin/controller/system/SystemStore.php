<?php

namespace app\admin\controller\system;

use app\admin\controller\AuthController;
use app\admin\model\system\SystemStore as SystemStoreModel;
use app\admin\model\store\StoreCategory as StroreCateModel;
use crmeb\services\{
    JsonService, UtilService, JsonService as Json, FormBuilder as Form
};

/**
 * 门店管理控制器
 * Class SystemAttachment
 * @package app\admin\controller\system
 *
 */
class SystemStore extends AuthController
{

    /**
     * 门店列表
     */
    public function list()
    {
        $where = UtilService::getMore([
            ['page', 1],
            ['limit', 20],
            ['name', ''],
            ['excel', 0],
            ['type', $this->request->param('type')]
        ]);
        return JsonService::successlayui(SystemStoreModel::getStoreList($where));
    }
    
    
    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function upload($id = 0)
    {
        $this->assign('id', (int)$id);
        return $this->fetch();
    }
    

    /**
     * 门店设置
     * @return string
     */
    public function index()
    {
        $type = $this->request->param('type');
        $show = SystemStoreModel::where('status', 1)->where('is_del', 0)->count();//显示中的门店
        $hide = SystemStoreModel::where('status', 0)->count();//隐藏的门店
        $recycle = SystemStoreModel::where('is_del', 1)->count();//删除的门店
        if ($type == null) $type = 1;
        
        
        $this->assign(compact('type', 'show', 'hide', 'recycle'));
        return $this->fetch();
    }

    /**
     * 门店添加
     * @param int $id
     * @return string
     */
    public function add($id = 0)
    {
        $catList = StroreCateModel::getAllCatList()['data'];
        $store = SystemStoreModel::getStoreDispose($id);
        //echo $id;exit;
        $this->assign(compact('store', 'catList'));
        return $this->fetch();
    }

    /**
     * 删除恢复门店
     * @param $id
     */
    public function delete($id)
    {
        if (!$id) return $this->failed('数据不存在');
        if (!SystemStoreModel::be(['id' => $id])) return $this->failed('产品数据不存在');
        if (SystemStoreModel::be(['id' => $id, 'is_del' => 1])) {
            $data['is_del'] = 0;
            if (!SystemStoreModel::edit($data, $id))
                return Json::fail(SystemStoreModel::getErrorInfo('恢复失败,请稍候再试!'));
            else
                return Json::successful('恢复门店成功!');
        } else {
            $data['is_del'] = 1;
            if (!SystemStoreModel::edit($data, $id))
                return Json::fail(SystemStoreModel::getErrorInfo('删除失败,请稍候再试!'));
            else
                return Json::successful('删除门店成功!');
        }
    }

    /**
     * 设置单个门店是否显示
     * @param string $is_show
     * @param string $id
     * @return json
     */
    public function set_show($is_show = '', $id = '')
    {
        ($is_show == '' || $id == '') && JsonService::fail('缺少参数');
        $res = SystemStoreModel::where(['id' => $id])->update(['is_show' => (int)$is_show]);
        if ($res) {
            return JsonService::successful($is_show == 1 ? '设置显示成功' : '设置隐藏成功');
        } else {
            return JsonService::fail($is_show == 1 ? '设置显示失败' : '设置隐藏失败');
        }
    }
    
    /**
     * 设置单个门店是否审核
     * @param string $is_show
     * @param string $id
     * @return json
     */
    public function set_status($status = '', $id = '')
    {
        ($status == '' || $id == '') && JsonService::fail('缺少参数');
        $res = SystemStoreModel::where(['id' => $id])->update(['status' => (int)$status]);
        if ($res) {
            return JsonService::successful($status == 1 ? '审核成功' : '恢复待审核成功');
        } else {
            return JsonService::fail($status == 1 ? '审核失败' : '设置待审核失败');
        }
    }

    /**
     * 位置选择
     * @return string|void
     */
    public function select_address()
    {
        $key = sys_config('tengxun_map_key');
        if (!$key) return $this->failed('请前往设置->物流设置->物流配置 配置腾讯地图KEY', '#');
        $this->assign(compact('key'));
        return $this->fetch();
    }
    
    //保存商家轮播图片和商家介绍
    public function saveImg($id=0){
        
        $data = UtilService::postMore([
            ['slider_image', []],
            ['introduction', '']
            ]);
        $data['slider_image'] = json_encode($data['slider_image']);
        SystemStoreModel::edit($data, $id);
        return Json::success('修改成功!');
    }
    
    public function get_store_info($id = 0)
    {
        $storeInfo = SystemStoreModel::get($id);
        if (!$storeInfo) {
            return Json::fail('修改的商家不存在');
        }
        $storeInfo['introduction'] = htmlspecialchars_decode($storeInfo['introduction']);
        $storeInfo['slider_image'] = is_string($storeInfo['slider_image']) ? json_decode($storeInfo['slider_image'], true) : [];
        $data['storeInfo'] = $storeInfo;
        return JsonService::successful($data);
    }
    /**
     * 保存修改门店信息
     * @param int $id
     */
    public function save($id = 0)
    {
        $data = UtilService::postMore([
            ['mer_name', ''],
            ['name', ''],
            ['user_id', -1],
            ['cat_id', 0],
            ['introduction', ''],
            ['image', ''],
            ['license', ''],
            ['idCardz', ''],
            ['idCardf', ''],
            ['xukeImg', ''],
            ['phone', ''],
            ['link_name', ''],
            ['link_phone', ''],
            ['sett_rate',0],
            ['give_rate', 0],
            ['pay_rate', 0],
            ['belong_t', 2],
            ['address', ''],
            ['detailed_address', ''],
            ['latlng', ''],
            ['valid_time', []],
            ['day_time', []],
        ]);
        SystemStoreModel::beginTrans();
        try {
            $data['address'] = implode(',', $data['address']);
            $data['latlng'] = is_string($data['latlng']) ? explode(',', $data['latlng']) : $data['latlng'];
            if (!isset($data['latlng'][0]) || !isset($data['latlng'][1])) return JsonService::fail('请选择门店位置');
            $data['latitude'] = $data['latlng'][0];
            $data['longitude'] = $data['latlng'][1];
            
            //根据经纬度获取商家所在城市及地区
            $mapkey = sys_config('tengxun_map_key');
            $crr = self::getCity($data['longitude'], $data['latitude'],$mapkey);
            $data['city'] = $crr['result']['address_component']['city'];
            $data['district'] = $crr['result']['address_component']['district'];
            
            $data['valid_time'] = implode(' - ', $data['valid_time']);
            $data['day_time'] = implode(' - ', $data['day_time']);
            unset($data['latlng']);
            if ($data['image'] && strstr($data['image'], 'http') === false) {
                $site_url = sys_config('site_url');
                $data['image'] = $site_url . $data['image'];
            }
            if ($id) {
                if (SystemStoreModel::where('id', $id)->update($data)) {
                    SystemStoreModel::commitTrans();
                    return JsonService::success('修改成功');
                } else {
                    SystemStoreModel::rollbackTrans();
                    return JsonService::fail('修改失败或者您没有修改什么！');
                }
            } else {
                $data['add_time'] = time();
                $data['is_show'] = 1;
                if ($res = SystemStoreModel::create($data)) {
                    SystemStoreModel::commitTrans();
                    return JsonService::success('保存成功', ['id' => $res->id]);
                } else {
                    SystemStoreModel::rollbackTrans();
                    return JsonService::fail('保存失败！');
                }
            }
        } catch (\Exception $e) {
            SystemStoreModel::rollbackTrans();
            return JsonService::fail($e->getMessage());
        }
    }
    //根据经纬度查询所在城市
    public static function getCity($longitude, $latitude,$mapkey) {
        //调取腾讯接口,其中ak为key,注意location纬度在前，经度在后
        $api = "https://apis.map.qq.com/ws/geocoder/v1/?location=" . $latitude . "," . $longitude . "&output=json&pois=1&key=".$mapkey;
        $content = file_get_contents($api);
        $arr = json_decode($content, true);
        if ($arr['status'] == 0) {
            return $arr;
        } else {
            return 'error';
        }
    }
}