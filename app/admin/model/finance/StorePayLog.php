<?php

namespace app\admin\model\finance;

use crmeb\traits\ModelTrait;
use crmeb\basic\BaseModel;
use app\models\user\UserBill;
use app\models\system\SystemStore;
use app\admin\model\user\User;
use crmeb\services\PHPExcelService;

/**
 * 数据统计处理
 * Class FinanceModel
 * @package app\admin\model\finance
 */
class StorePayLog extends BaseModel
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
    protected $name = 'store_pay_log';

    use ModelTrait;

   

    public static function getLogList($where)
    {
        $data = ($data = self::setWhereList($where)->page((int)$where['page'], (int)$where['limit'])->select()) && count($data) ? $data->toArray() : [];
        $count = self::setWhereList($where)->count();
        foreach ($data as &$item) {
            $storeinfo = SystemStore::where('user_id', $item['uid'])->field('mer_name')->find();
            if($storeinfo){
                $item['mer_name'] = $storeinfo['mer_name'];
            }else{
                $item['mer_name'] = '';
            }
            
            
        }
        return compact('data', 'count');
    }

    public static function SaveExport($where)
    {
        $data = ($data = self::setWhereList($where)->select()) && count($data) ? $data->toArray() : [];
        $export = [];
        foreach ($data as $value) {
            $belong_t = '';
            if($value['belong_t']==0){
                $belong_t = '消费订单';
            }else if($value['belong_t']==1){
                $belong_t = '购物订单';
            }else if($value['belong_t']==2){
                $belong_t = '提现';
            }else{
                $belong_t = '货款转余额';
            }
            $export[] = [
                $value['uid'],
                $value['nickname'],
                $belong_t,
                $value['use_money'],
                $value['huokuan'],
                $value['give_point'],
                $value['pay_point'],
                $value['repeat_point'],
                $value['fee'],
                $value['mark'],
                $value['add_time'],
            ];
        }
        PHPExcelService::setExcelHeader(['会员ID', '昵称', '记录类型', '余额', '货款','购物积分','消费积分','重消积分','手续费','备注', '创建时间'])
            ->setExcelTile('资金监控', '资金监控', date('Y-m-d H:i:s', time()))
            ->setExcelContent($export)
            ->ExcelSave();
    }

    public static function setWhereList($where)
    {
        $time['data'] = '';
        if ($where['start_time'] != '' && $where['end_time'] != '') {
            $time['data'] = $where['start_time'] . ' - ' . $where['end_time'];
        }
        $model = self::getModelTime($time, self::alias('A')
            ->join('user B', 'B.uid=A.uid')
            ->order('A.add_time desc'), 'A.add_time');
        if (trim($where['belong_t']) != '') {//记录类型
            $model = $model->where('A.belong_t', $where['belong_t']);
        }
        if (trim($where['pay_type']) != '') {//变动类型
            if($where['pay_type']==0){//余额
                $model = $model->where('A.use_money','<>', 0);
            }else if($where['pay_type']==1){//货款
                $model = $model->where('A.huokuan','<>', 0);
            }else if($where['pay_type']==2){//购物积分
                $model = $model->where('A.give_point','<>', 0);
            }else if($where['pay_type']==3){//消费积分
                $model = $model->where('A.pay_point','<>', 0);
            }else{//重消积分
                $model = $model->where('A.repeat_point','<>', 0);
            } 
        }
        if ($where['nickname'] != '') {
            $model = $model->where('B.nickname|B.uid|B.phone', 'like', "%$where[nickname]%");
        }
        return $model->field(['A.*', 'FROM_UNIXTIME(A.add_time,"%Y-%m-%d %H:%i:%s") as add_time', 'B.uid', 'B.nickname','B.phone']);
    }

    

    /**
     * 处理where条件
     */
    public static function statusByWhere($status, $model = null)
    {
        if ($model == null) $model = new self;
        if ('' === $status)
            return $model;
        else if ($status == 'weixin')//微信支付
            return $model->where('pay_type', 'weixin');
        else if ($status == 'yue')//余额支付
            return $model->where('pay_type', 'yue');
        else if ($status == 'offline')//线下支付
            return $model->where('pay_type', 'offline');
        else
            return $model;
    }
}