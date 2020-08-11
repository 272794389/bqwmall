<?php
/**
 * @author: xaboy<365615158@qq.com>
 * @day: 2017/11/11
 */

namespace app\admin\model\ump;

use crmeb\services\FormBuilder as Form;
use crmeb\traits\ModelTrait;
use crmeb\basic\BaseModel;
use think\facade\Route as Url;
use app\admin\model\store\StoreCategory as CategoryModel;


/**
 * Class StoreCategory
 * @package app\admin\model\store
 */
class GoodsCoupon extends BaseModel
{
    /**
     * 数据表主键
     * @var string
     */
    protected $pk = 'id';

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'goods_coupon';

    use ModelTrait;

    /**
     * @param $where
     * @return array
     */
    public static function systemPage($where)
    {
        $model = new self;
        if ($where['status'] != '') $model = $model->where('status', $where['status']);
        if ($where['title'] != '') $model = $model->where('title', 'LIKE', "%$where[title]%");
//        if($where['is_del'] != '')  $model = $model->where('is_del',$where['is_del']);
        $model = $model->where('is_del', 0);
        $model = $model->order('id desc');
        return self::page($model, $where);
    }

    public static function editIsDel($id)
    {
        $data['status'] = 0;
        self::beginTrans();
        $res1 = self::edit($data, $id);
        $res2 = false !== StoreCouponUser::where('cid', $id)->update(['is_fail' => 1]);
        $res = $res1 && $res2;
        self::checkTrans($res);
        return $res;

    }
}