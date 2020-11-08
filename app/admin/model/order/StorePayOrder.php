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
    
    public static function getOrderCounts($store_id,$date,$minAmount,$startTime,$endTime){
    	$model = new self;
    	$model = $model->where('store_id',$store_id);
    	$model = $model->where('total_amount','>',$minAmount);
    	$model = $model->where('pay_time','>',$startTime);
    	$model = $model->where('pay_time','<',$endTime);
    	return $model->count();
    }
    
    public static function getOrderAmount($store_id,$date,$minAmount,$startTime,$endTime){
    	$model = new self;
    	$model = $model->where('store_id',$store_id);
    	$model = $model->where('total_amount','>',$minAmount);
    	$model = $model->where('pay_time','>',$startTime);
    	$model = $model->where('pay_time','<',$endTime);
    	return $model->sum('total_amount');
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
            
            if($item['pay_flag']==0){
                $item['pay_give'] = 0;
                $item['coupon_amount'] = 0;
                $item['pay_point'] = 0;
            }else if($item['pay_flag']==1){
                $item['coupon_amount'] = 0;
                $item['pay_point'] = 0;
            }else if($item['pay_flag']==2){
                $item['coupon_amount'] = 0;
                $item['pay_give'] = 0;
            }else if($item['pay_flag']==3){
                $item['pay_point'] = 0;
                $item['pay_give'] = 0;
            }
            $export[] = [
                $item['order_id'],
                ' 用户昵称:'.$item['nickname'] .=' /用户id:'. $item['uid'],
                $item['mer_name'],
                '￥'.$item['total_amount'],
                '￥'.$item['pay_amount'],
                $item['pay_give'],
                $item['pay_point'],
                $item['coupon_amount'],
                $item['pay_type'],
                date('Y-m-d',$item['add_time'])
            ];
        }
        PHPExcelService::setExcelHeader(['订单号', '用户信息', '商户名称', '消费总额', '实际支付', '购物积分抵扣','消费积分抵扣', '抵扣券抵扣', '支付方式'
            , '消费时间'])
            ->setExcelTile('佰仟万平台用户消费台账', '消费信息' . time(), ' 生成台账时间：' . date('Y-m-d H:i:s', time()))
            ->setExcelContent($export)
            ->ExcelSave();
        }
        
        
        /*
         * 获取订单数据统计图
         * $where array
         * $limit int
         * return array
         */
        public static function getEchartsOrder($where, $limit = 20)
        {
            $orderlist = self::setEchatWhere($where)->field(
                'FROM_UNIXTIME(add_time,"%Y-%m-%d") as _add_time,count(*) count,sum(total_amount) total_price,sum(pay_amount) pay_amount'
                )->group('_add_time')->order('_add_time asc')->select();
                count($orderlist) && $orderlist = $orderlist->toArray();
                $legend = ['订单数量', '订单金额', '支付金额'];
                $seriesdata = [
                    [
                        'name' => $legend[0],
                        'type' => 'line',
                        'data' => []
                    ],
                    [
                        'name' => $legend[1],
                        'type' => 'line',
                        'data' => []
                    ],
                    [
                        'name' => $legend[2],
                        'type' => 'line',
                        'data' => []
                    ]
                ];
                $xdata = [];
                $zoom = '';
                foreach ($orderlist as $item) {
                    $xdata[] = $item['_add_time'];
                    $seriesdata[0]['data'][] = $item['count'];
                    $seriesdata[1]['data'][] = $item['total_price'];
                    $seriesdata[2]['data'][] = $item['pay_amount'];
                }
                count($xdata) > $limit && $zoom = $xdata[$limit - 5];
                $badge = self::getOrderBadge($where);
                $bingpaytype = self::setEchatWhere($where)->group('pay_type')->field('count(*) as count,pay_type')->select();
                count($bingpaytype) && $bingpaytype = $bingpaytype->toArray();
                $bing_xdata = ['微信支付', '余额支付', '其他支付'];
                $color = ['#ffcccc', '#99cc00', '#fd99cc', '#669966'];
                $bing_data = [];
                foreach ($bingpaytype as $key => $item) {
                    if ($item['pay_type'] == 'weixin') {
                        $value['name'] = $bing_xdata[0];
                    } else if ($item['pay_type'] == 'yue') {
                        $value['name'] = $bing_xdata[1];
                    } else {
                        $value['name'] = $bing_xdata[2];
                    }
                    $value['value'] = $item['count'];
                    $value['itemStyle']['color'] = isset($color[$key]) ? $color[$key] : $color[0];
                    $bing_data[] = $value;
                }
                return compact('zoom', 'xdata', 'seriesdata', 'badge', 'legend', 'bing_data', 'bing_xdata');
        } 
        
        public static function getOrderBadge($where)
        {
            return [
                [
                    'name' => '订单数量',
                    'field' => '个',
                    'count' => self::setEchatWhere($where)->count(),
                    'content' => '累计消费订单数量',
                    'background_color' => 'layui-bg-cyan',
                    'sum' => self::setEchatWhere($where, true)->count(),
                    'class' => 'fa fa-line-chart',
                    'col' => 2
                ],
                [
                    'name' => '使用抵扣卷金额',
                    'field' => '元',
                    'count' => self::setEchatWhere($where)->where('pay_flag',3)->sum('coupon_amount'),
                    'content' => '累计使用抵扣券抵扣金额',
                    'background_color' => 'layui-bg-cyan',
                    'sum' => self::setEchatWhere($where, null, true)->sum('coupon_amount'),
                    'class' => 'fa fa-line-chart',
                    'col' => 2
                ],
                [
                    'name' => '消费积分抵扣',
                    'field' => '个',
                    'count' => self::setEchatWhere($where)->where('pay_flag',2)->sum('pay_point'),
                    'content' => '累计使用积分抵扣数量',
                    'background_color' => 'layui-bg-cyan',
                    'sum' => self::setEchatWhere($where, true)->sum('pay_point'),
                    'class' => 'fa fa-line-chart',
                    'col' => 2
                ],
                [
                    'name' => '购物积分抵扣',
                    'field' => '个',
                    'count' => self::setEchatWhere($where)->where('pay_flag',1)->sum('pay_give'),
                    'content' => '累计使用积分抵扣数量',
                    'background_color' => 'layui-bg-cyan',
                    'sum' => self::setEchatWhere($where, true)->sum('pay_give'),
                    'class' => 'fa fa-line-chart',
                    'col' => 2
                ],
                [
                'name' => '交易金额',
                'field' => '元',
                'count' => self::setEchatWhere($where)->sum('total_amount'),
                'content' => '累计订单交易金额',
                'background_color' => 'layui-bg-cyan',
                'sum' => self::setEchatWhere($where,true)->sum('total_amount'),
                'class' => 'fa fa-balance-scale',
                'col' => 2
                ],
                [
                    'name' => '在线支付金额',
                    'field' => '元',
                    'count' => self::setEchatWhere($where)->where('pay_type', 'weixin')->sum('pay_amount'),
                    'content' => '在线支付总金额',
                    'background_color' => 'layui-bg-cyan',
                    'sum' => self::setEchatWhere($where,true)->where('pay_type', 'weixin')->sum('pay_amount'),
                    'class' => 'fa fa-weixin',
                    'col' => 2
                ],
                [
                    'name' => '余额支付金额',
                    'field' => '元',
                    'count' => self::setEchatWhere($where)->where('pay_type', 'yue')->sum('pay_amount'),
                    'content' => '余额支付总金额',
                    'background_color' => 'layui-bg-cyan',
                    'sum' => self::setEchatWhere($where, null, true)->where('pay_type', 'yue')->sum('pay_amount'),
                    'class' => 'fa  fa-balance-scale',
                    'col' => 2
                ],
                [
                    'name' => '赚取消费积分',
                    'field' => '个',
                    'count' => self::setEchatWhere($where)->sum('pay_pointer'),
                    'content' => '赚取总消费积分',
                    'background_color' => 'layui-bg-cyan',
                    'sum' => self::setEchatWhere($where,  true)->sum('pay_pointer'),
                    'class' => 'fa fa-gg-circle',
                    'col' => 2
                ]
            ];
        }
        
        
        
        
        /**
         * 设置订单统计图搜索
         * @param array $where 条件
         * @param null $status
         * @param null $time
         * @return array
         */
        public static function setEchatWhere($where,$time = null)
        {
            $model = self::where('paid', 1);
            if ($time === true) $where['data'] = '';
            return self::getModelTime($where, $model);
        }
}