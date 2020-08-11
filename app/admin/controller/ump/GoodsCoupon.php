<?php

namespace app\admin\controller\ump;

use app\admin\controller\AuthController;
use think\facade\Route as Url;
use app\admin\model\ump\{GoodsCoupon as CouponModel};
use crmeb\services\{FormBuilder as Form, UtilService as Util, JsonService as Json};
/**
 * 优惠券控制器
 * Class StoreCategory
 * @package app\admin\controller\system
 */
class GoodsCoupon extends AuthController
{

    /**
     * @return mixed
     */
    public function index()
    {
        $where = Util::getMore([
            ['status', ''],
            ['title', ''],
            ['type','']
        ], $this->request);
        $this->assign('where', $where);
        $this->assign(CouponModel::systemPage($where));
        return $this->fetch();
    }
    /**
     * @return mixed
     */
    public function create()
    {
        $data = Util::getMore(['type',]);//接收参数
        $tab_id = !empty(request()->param('tab_id')) ? request()->param('tab_id') : 1;
        //前面通用字段
        $f = [];
        $f[] = Form::input('title', '优惠券名称');
        //不同类型不同字段
        $formbuider = [];
        //后面通用字段
        $formbuiderfoot = array();
        $formbuiderfoot[] = Form::number('coupon_price', '优惠券面值', 0)->min(0);
        $formbuiderfoot[] = Form::number('coupon_time', '有效期限')->min(0);
        $formbuiderfoot[] = Form::number('sort', '排序');
        $formbuiderfoot[] = Form::hidden('type', $data['type']);
        $formbuiderfoot[] = Form::radio('status', '状态', 0)->options([['label' => '开启', 'value' => 1], ['label' => '关闭', 'value' => 0]])->value(1);
        $formbuiders = array_merge($f, $formbuider, $formbuiderfoot);
        $form = Form::make_post_form('添加优惠券', $formbuiders, Url::buildUrl('save'));
        $this->assign(compact('form'));
        $this->assign('get', request()->param());
        return $this->fetch();
    }
    /**
     * 删除指定资源
     *
     * @param int $id
     * @return \think\Response
     */
    public function delete($id)
    {
        if (!$id) return Json::fail('数据不存在!');
        $data['is_del'] = 1;
        if (!CouponModel::edit($data, $id))
            return Json::fail(CouponModel::getErrorInfo('删除失败,请稍候再试!'));
            else
                return Json::successful('删除成功!');
    }
    
    /**
     * 修改优惠券状态
     * @param $id
     * @return \think\response\Json
     */
    public function status($id)
    {
        if (!$id) return Json::fail('数据不存在!');
        if (!CouponModel::editIsDel($id))
            return Json::fail(CouponModel::getErrorInfo('修改失败,请稍候再试!'));
            else
                return Json::successful('修改成功!');
    }
    
    /**
     * 保存
     */
    public function save()
    {
        $data = Util::postMore([
            'title',
            'coupon_price',
            'coupon_time',
            ['status', 0]
        ]);
        if (!$data['title']) return Json::fail('请输入优惠券名称');
        if (!$data['coupon_price']) return Json::fail('请输入优惠券面值');
        if (!$data['coupon_time']) return Json::fail('请输入优惠券有效期限');
        $data['add_time'] = time();
        CouponModel::create($data);
        return Json::successful('添加优惠券成功!');
    }
}
