<?php

namespace app\admin\controller\system;

use app\admin\controller\AuthController;
use crmeb\services\{
    JsonService, UtilService as Util, JsonService as Json, FormBuilder as Form
};
use crmeb\traits\CurdControllerTrait;
use app\admin\model\system\{
    SystemAttachment, ShippingTemplates,SystemStore,DataConfig as DataConfigModel
};


/**
 * 产品管理
 * Class StoreProduct
 * @package app\admin\controller\store
 */
class DataConfig extends AuthController
{

    use CurdControllerTrait;

    protected $bindModel = DataConfigModel::class;
    
    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function index()
    {
        $this->assign('id', 1);
        return $this->fetch();
    }
    
    /**
     * 获取产品详细信息
     * @param int $id
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function get_dateconfig_info($id = 0)
    {
       
        $data['dataInfo'] = [];
            $dataInfo = DataConfigModel::get(1);
            if (!$dataInfo) {
                return Json::fail('参数不存在');
            }
            $data['dataInfo'] = $dataInfo;
        return JsonService::successful($data);
    }
    
    /**
     * 保存新建的资源
     *
     *
     */
    public function save($id)
    {
        $data = Util::postMore([
            ['rec_f', 0],
            ['rec_s', 0],
            ['rec_t', 0],
            ['fee_rate', 0],
            ['repeat_rate', 100],
            ['shop_rec', 0],
            ['agent_pro', 0],
            ['agent_city', 0],
            ['agent_district', 0],
            ['inspect_pro', -1],
            ['inspect_city', 0],
            ['inspect_district', 0],
            ['withdraw_fee', 0]
        ]);
            DataConfigModel::edit($data, $id);
            return Json::success('修改成功!');
    }
}
