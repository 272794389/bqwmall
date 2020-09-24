<?php
namespace app\admin\model\record;

use crmeb\traits\ModelTrait;
use crmeb\basic\BaseModel;
use app\models\user\UserBill;
use crmeb\services\PHPExcelService;
use think\facade\Db;

class ProfitStatics extends BaseModel
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
    protected $name = 'profit_detail';

    use ModelTrait;

    /**
     * 处理金额
     * @param $where
     * @return array
     */
    public static function getOrderPrice($where)
    {
        $model = new self;
        $price = array();
        $price['pay_price_wx'] = 0;//微信支付金额
        $price['pay_price_yue'] = 0;//余额支付金额
        $price['pay_price_offline'] = 0;//线下支付金额
        $list = self::getTimeWhere($where, $model)->field('pay_price,total_price,deduction_price,coupon_price,total_postage,pay_type,pay_time')->select()->toArray();
        if (empty($list)) {
            $price['pay_price_wx'] = 0;
            $price['pay_price_yue'] = 0;
            $price['pay_price_offline'] = 0;
        }
        foreach ($list as $v) {
            if ($v['pay_type'] == 'weixin') {
                $price['pay_price_wx'] = bcadd($price['pay_price_wx'], $v['pay_price'], 2);
            } elseif ($v['pay_type'] == 'yue') {
                $price['pay_price_yue'] = bcadd($price['pay_price_yue'], $v['pay_price'], 2);
            } elseif ($v['pay_type'] == 'offline') {
                $price['pay_price_offline'] = bcadd($price['pay_price_offline'], $v['pay_price'], 2);
            }
        }
        return $price;
    }

   
    
    /**
     * 获取营业数据
     */
    public static function getStaticsInfo($where)
    {
        $orderinfo = self::getTime($where)->field('sum(total_amount) total_amount,sum(pay_amount) pay_amount,sum(huokuan) huokuan,sum(pointer) pointer,sum(coupon_amount) coupon_amount,sum(shopaward) shopaward,
            sum(faward) faward,sum(saward) saward,sum(fagent) fagent,sum(sagent) sagent,sum(fprerent) fprerent,sum(sprerent) sprerent,sum(out_amount) out_amount,sum(fee) fee,sum(profit) profit, from_unixtime(add_time,\'%Y-%m-%d\') add_time')
        ->order('add_time')
        ->group('from_unixtime(add_time,\'%Y-%m-%d\')')->select()->toArray();
        $total_amount = 0;
        $pay_amount = 0;
        $huokuan = 0;
        $pointer = 0;
        $coupon_amount = 0;
        $shopaward = 0;
        $faward  = 0;
        $saward  = 0;
        $fagent  = 0;
        $sagent  = 0;
        $fprerent  = 0;
        $sprerent  = 0;
        $out_amount  = 0;
        $fee  = 0;
        $profit  = 0;
        foreach ($orderinfo as $info) {
            $total_amount = bcadd($total_amount, $info['total_amount'], 2);//成交额
            $pay_amount = bcadd($pay_amount, $info['pay_amount'], 2);//实际支付金额
            $huokuan = bcadd($huokuan, $info['huokuan'], 2);//货款
            $pointer = bcadd($pointer, $info['pointer'], 2);//积分抵扣
            $coupon_amount = bcadd($coupon_amount, $info['coupon_amount'], 2);//抵扣券抵扣
            
            $shopaward = bcadd($shopaward, $info['shopaward'], 2);//商家推荐奖励
            $faward = bcadd($faward, $info['faward'], 2);//一代推荐奖励
            $saward = bcadd($saward, $info['saward'], 2);//二代推荐奖励
            $fagent = bcadd($fagent, $info['fagent'], 2);//市级代理商
            $sagent = bcadd($sagent, $info['sagent'], 2);//地区代理商
            
            $fprerent = bcadd($fprerent, $info['fprerent'], 2);//市级总监
            $sprerent = bcadd($sprerent, $info['sprerent'], 2);//地区总监
            $out_amount = bcadd($out_amount, $info['out_amount'], 2);//运营成本
            $fee = bcadd($fee, $info['fee'], 2);//手续费
            $profit = bcadd($profit, $info['profit'], 2);//利润
        }
        return compact('orderinfo','total_amount', 'pay_amount', 'huokuan', 'pointer', 'coupon_amount', 'shopaward', 'faward', 'saward', 'fagent', 'sagent', 'fprerent', 'sprerent', 'out_amount', 'fee', 'profit');
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

    public static function getTimeWhere($where, $model = null)
    {
        return self::getTime($where)->where('paid', 1)->where('refund_status', 0);
    }
    /**
     * 获取时间区间
     */
    public static function getTime($where,$model=null,$prefix='add_time'){
        if ($model == null) $model = new self;
        if(!$where['date']) return $model;
        if ($where['data'] == '') {
            $limitTimeList = [
                'today'=>implode(' - ',[date('Y/m/d'),date('Y/m/d',strtotime('+1 day'))]),
                'week'=>implode(' - ',[
                    date('Y/m/d', (time() - ((date('w') == 0 ? 7 : date('w')) - 1) * 24 * 3600)),
                    date('Y-m-d', (time() + (7 - (date('w') == 0 ? 7 : date('w'))) * 24 * 3600))
                ]),
                'month'=>implode(' - ',[date('Y/m').'/01',date('Y/m').'/'.date('t')]),
                'quarter'=>implode(' - ',[
                    date('Y').'/'.(ceil((date('n'))/3)*3-3+1).'/01',
                    date('Y').'/'.(ceil((date('n'))/3)*3).'/'.date('t',mktime(0,0,0,(ceil((date('n'))/3)*3),1,date('Y')))
                ]),
                'year'=>implode(' - ',[
                    date('Y').'/01/01',date('Y/m/d',strtotime(date('Y').'/01/01 + 1year -1 day'))
                ])
            ];
            $where['data'] = $limitTimeList[$where['date']];
        }
        list($startTime, $endTime) = explode(' - ', $where['data']);
        $model = $model->where($prefix, '>', strtotime($startTime));
        $model = $model->where($prefix, '<', strtotime($endTime));
        return $model;
    }
    /**
     * 获取新增消费
     */
    public static function getConsumption($where)
    {
        $consumption=self::getTime($where,new UserBill,'b.add_time')->alias('a')->join('user b','a.uid = b.uid')
            ->field('sum(a.number) number')
        ->where('a.type','pay_product')->find()->toArray();
        return $consumption;
    }
    /**
     * 获取拼团商品
     */
    public static function getPink($where)
    {
        $pink = self::getTimeWhere($where)->where('pink_id', '<>', 0)->sum('pay_price');
        return $pink;
    }
    /**
     * 获取秒杀商品
     */
    public static function getSeckill($where){
        $seckill=self::getTimeWhere($where)->where('seckill_id', '<>', 0)->sum('pay_price');
        return $seckill;
    }
    /**
     * 获取普通商品数
     */
    public static function getOrdinary($where)
    {
        $ordinary = self::getTimeWhere($where)->where('pink_id',  0)->where('seckill_id','0')->sum('pay_price');
        return $ordinary;
    }

    /**
     * 获取用户充值
     */
    public static function getRecharge($where)
    {
            $Recharge = self::getTime($where,new UserBill)->where('type', 'system_add')->where('category','now_money')->sum('number');
            return $Recharge;
    }
    /**
     * 获取推广金
     */
    public static function getExtension($where)
    {
        $extension = self::getTime($where,new UserBill)->where('type', 'brokerage')->where('category','now_money')->sum('number');
        return $extension;
    }

    /**
     * 最近交易
     */
    public static function trans()
    {
        $trans = self::alias('a')
            ->join('user b', 'a.uid=b.uid','left')
            ->join('store_order_cart_info c', 'a.id=c.oid')
            ->join('store_product d', 'c.product_id=d.id')
            ->field('b.nickname,a.pay_price,d.store_name')
            ->order('a.add_time DESC')
            ->limit('6')
            ->select()->toArray();
        return $trans;
    }

    /**
     * 导出表格
     */
    public static function systemTable($where){
        $orderinfos=self::getOrderInfo($where);
        if($where['export'] == 1){
            $export = [];
            $orderinfo=$orderinfos['orderinfo'];
            foreach($orderinfo as $info){
                $time=$info['pay_time'];
                $price = $info['total_price']+$info['pay_postage'];
                $zhichu = $info['coupon_price']+$info['deduction_price']+$info['cost'];
                $profit = ($info['total_price']+$info['pay_postage'])-($info['coupon_price']+$info['deduction_price']+$info['cost']);
                $deduction=$info['deduction_price'];//积分抵扣
                $coupon=$info['coupon_price'];//优惠
                $cost=$info['cost'];//成本
                $export[] = [$time,$price,$zhichu,$cost,$coupon,$deduction,$profit];
            }
//            ExportService::exportCsv($export,'统计'.time(),['时间','营业额(元)','支出(元)','成本','优惠','积分抵扣','盈利(元)']);
            dump($export);
            PHPExcelService::setExcelHeader(['时间','营业额(元)','支出(元)','成本','优惠','积分抵扣','盈利(元)'])->setExcelTile('财务统计', '财务统计',date('Y-m-d H:i:s',time()))->setExcelContent($export)->ExcelSave();
        }
    }
}