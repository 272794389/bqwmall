<?php
/**
 * Created by PhpStorm.
 * User: lianghuan
 * Date: 2018-03-03
 * Time: 16:47
 */

namespace app\admin\model\order;

use app\admin\model\wechat\WechatUser;
use app\models\routine\RoutineTemplate;
use app\models\user\StorePayLog;
use think\facade\Route as Url;
use crmeb\traits\ModelTrait;
use crmeb\basic\BaseModel;
use crmeb\services\WechatTemplateService;
use crmeb\services\PHPExcelService;

/**
 * 用户消费订单管理 model
 * Class User
 * @package app\admin\model\user
 */
class StorePayOrder extends BaseModel
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
    protected $name = 'store_pay_order';

    use ModelTrait;

    public static function payStatistics()
    {
        //消费总金额
        $data['total_amount'] = floatval(self::where('paid', 1)->sum('total_amount'));
        //实际支付金额
        $data['pay_amount'] = floatval(self::where('paid', 1)->sum('pay_amount'));
        //购物积分支付金额
        $data['pay_give'] = floatval(self::where('paid', 1)->sum('pay_give'));
        //抵扣券抵扣金额
        $data['coupon_amount'] = floatval(self::where('paid', 1)->sum('coupon_amount'));
        
        return compact('data');
    }

    /**
     * @param $where
     * @return array
     */
    public static function systemPage($where)
    {
        $model = new self;
        if ($where['date'] != '') {
            list($startTime, $endTime) = explode(' - ', $where['date']);
            $model = $model->where('a.add_time', '>', strtotime($startTime));
            $model = $model->where('a.add_time', '<', (int)bcadd(strtotime($endTime), 86400, 0));
        }
        if ($where['status'] != '') $model = $model->where('a.paid', $where['status']);
        if ($where['nireid'] != '') $model = $model->where('b.nickname|b.account|b.real_name|b.phone', 'like', "%$where[nireid]%");
        if ($where['shopname'] != '') $model = $model->where('c.name|c.mer_name', 'like', "%$where[shopname]%");
        
        $model = $model->alias('a');
        $model = $model->field('a.*,b.nickname,c.mer_name');
        $model = $model->join('user b', 'b.uid=a.uid', 'LEFT');
        $model = $model->join('system_store c', 'c.id=a.store_id', 'LEFT');
        $model = $model->order('a.id desc');
        return self::page($model, $where);
    }
    
    /**
     * @param $where
     * @return array
     */
    public static function exportList($where)
    {
        $model = new self;
        if ($where['date'] != '') {
            list($startTime, $endTime) = explode(' - ', $where['date']);
            $model = $model->where('a.add_time', '>', strtotime($startTime));
            $model = $model->where('a.add_time', '<', (int)bcadd(strtotime($endTime), 86400, 0));
        }
        $model = $model->where('paid',1);
        if ($where['status'] != '') $model = $model->where('a.paid', $where['status']);
        if ($where['nireid'] != '') $model = $model->where('b.nickname|b.account|b.real_name|b.phone', 'like', "%$where[nireid]%");
        if ($where['shopname'] != '') $model = $model->where('c.name|c.mer_name', 'like', "%$where[shopname]%");
    
        $model = $model->alias('a');
        $model = $model->field('a.*,b.nickname,c.mer_name');
        $model = $model->join('user b', 'b.uid=a.uid', 'LEFT');
        $model = $model->join('system_store c', 'c.id=a.store_id', 'LEFT');
        $model = $model->order('a.id desc');
        
        $data = ($data = $model->select()) && count($data) ? $data->toArray() : [];
        $export = [];
        foreach ($data as $index => $item) {
            if($item['pay_type']=='yue'){
                $item['pay_type']='余额支付';
            }else{
                $item['pay_type']='微信';
            }
            $export[] = [
                $item['order_id'],
                ' 用户昵称:'.$item['nickname'] .=' /用户id:'. $item['uid'],
                $item['mer_name'],
                '￥'.$item['total_amount'],
                '￥'.$item['pay_amount'],
                $item['pay_give'],
                $item['coupon_amount'],
                $item['pay_type'],
                date('Y-m-d',$item['add_time'])
            ];
        }
        PHPExcelService::setExcelHeader(['订单号', '用户信息', '商户名称', '消费总额', '实际支付', '购物积分支付', '抵扣券抵扣', '支付方式'
            , '消费时间'])
            ->setExcelTile('佰仟万平台用户消费台账', '消费信息' . time(), ' 生成台账时间：' . date('Y-m-d H:i:s', time()))
            ->setExcelContent($export)
            ->ExcelSave();
        }
}