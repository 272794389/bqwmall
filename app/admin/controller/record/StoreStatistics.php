<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/16 0016
 * Time: 10:39
 */

namespace app\admin\controller\record;

use app\admin\controller\AuthController;
use crmeb\services\UtilService as Util;
use app\admin\model\record\{StoreStatistics as StatisticsModel,ProfitStatics as ProfitStaticsModel};

/**
 * Class StoreStatistics
 * @package app\admin\controller\record
 */
class StoreStatistics extends AuthController
{
    /**
     * 显示列表
     */
    public function index()
    {
        $where = Util::getMore([
            ['date', ''],
            ['export', ''],
            ['data', '']
        ], $this->request);
        $where['date'] = $this->request->param('date');
        $where['data'] = $this->request->param('data');
        $where['export'] = $this->request->param('export');
        $trans = StatisticsModel::trans();//最近交易
        $seckill = StatisticsModel::getSeckill($where);//秒杀商品
        $ordinary = StatisticsModel::getOrdinary($where);//普通商品
        $pink = StatisticsModel::getPink($where);//拼团商品
        $recharge = StatisticsModel::getRecharge($where);//充值
        $extension = StatisticsModel::getExtension($where);//推广金
        $orderCount = [
            urlencode('微信支付') => StatisticsModel::getTimeWhere($where, StatisticsModel::statusByWhere('weixin'))->count(),
            urlencode('余额支付') => StatisticsModel::getTimeWhere($where, StatisticsModel::statusByWhere('yue'))->count(),
            urlencode('线下支付') => StatisticsModel::getTimeWhere($where, StatisticsModel::statusByWhere('offline'))->count(),
        ];
        $Statistic = [
            ['name' => '营业额', 'type' => 'line', 'data' => []],
            ['name' => '支出', 'type' => 'line', 'data' => []],
            ['name' => '盈利', 'type' => 'line', 'data' => []],
        ];
        $orderinfos = ProfitStaticsModel::getStaticsInfo($where);
        $orderinfo = $orderinfos['orderinfo'];
        $orderDays = [];
         if (empty($orderinfo)) {
            $orderDays[] = date('Y-m-d', time());
            $Statistic[0]['data'][] = 0;
            $Statistic[1]['data'][] = 0;
            $Statistic[2]['data'][] = 0;
        }
        foreach ($orderinfo as $info) {
            $orderDays[] = $info['add_time'];
            $Statistic[0]['data'][] = $info['total_amount'];
            $Statistic[1]['data'][] = ($info['huokuan'] + $info['pointer']+ $info['coupon_amount']+ $info['shopaward']+ $info['faward']+ $info['saward']+ $info['fagent']
            + $info['sagent']+ $info['fprerent']+ $info['sprerent']+ $info['out_amount']+$info['fee']);
            $Statistic[2]['data'][] = $info['profit'];
        }
        
        $header = [
            ['name' => '交易总额', 'class' => 'fa-line-chart', 'value' => '￥' . $orderinfos['total_amount'], 'color' => 'red'],
            ['name' => '实收总额', 'class' => 'fa-area-chart', 'value' => '￥' . $orderinfos['pay_amount'], 'color' => 'lazur'],
            ['name' => '支付货款', 'class' => 'fa-pie-chart', 'value' => '￥' . $orderinfos['huokuan'], 'color' => 'lazur'],
            ['name' => '积分抵扣', 'class' => 'fa-area-chart', 'value' => '￥' . $orderinfos['pointer'], 'color' => 'lazur'],
            ['name' => '抵扣券抵扣', 'class' => 'fa-line-chart', 'value' => '￥' . $orderinfos['coupon_amount'], 'color' => 'lazur'],
            ['name' => '支付商家推荐佣金', 'class' => 'fa-line-chart', 'value' => '￥' . $orderinfos['shopaward'], 'color' => 'lazur'],
            ['name' => '支付一代推荐佣金', 'class' => 'fa-line-chart', 'value' => '￥' . $orderinfos['faward'], 'color' => 'lazur'],
            ['name' => '支付二代推荐佣金', 'class' => 'fa-line-chart', 'value' => '￥' . $orderinfos['saward'], 'color' => 'lazur'],
            ['name' => '支付市级代理商佣金', 'class' => 'fa-line-chart', 'value' => '￥' . $orderinfos['fagent'], 'color' => 'lazur'],
            ['name' => '支付地区代理商佣金', 'class' => 'fa-line-chart', 'value' => '￥' . $orderinfos['sagent'], 'color' => 'lazur'],
            ['name' => '支付市级总监佣金', 'class' => 'fa-line-chart', 'value' => '￥' . $orderinfos['fprerent'], 'color' => 'lazur'],
            ['name' => '支付地区总监佣金', 'class' => 'fa-line-chart', 'value' => '￥' . $orderinfos['sprerent'], 'color' => 'lazur'],
            ['name' => '支付运营成本', 'class' => 'fa-line-chart', 'value' => '￥' . $orderinfos['out_amount'], 'color' => 'lazur'],
            ['name' => '收取手续费', 'class' => 'fa-line-chart', 'value' => '￥' . $orderinfos['fee'], 'color' => 'lazur'],
            ['name' => '总盈利', 'class' => 'fa-bar-chart', 'value' => '￥' . $orderinfos['profit'], 'color' => 'navy']
        ];
        $data = [
            ['value' => $orderinfos['huokuan'], 'name' => '支付货款'],
            ['value' => $orderinfos['coupon_amount'], 'name' => '优惠券抵扣'],
            ['value' => $orderinfos['pointer'], 'name' => '积分抵扣'],
            ['value' => $orderinfos['shopaward'], 'name' => '商家推荐佣金'],
            ['value' => $orderinfos['faward'], 'name' => '一代推荐佣金'],
            ['value' => $orderinfos['saward'], 'name' => '二代推荐佣金'],
            ['value' => $orderinfos['fagent'], 'name' => '市级代理商佣金'],
            ['value' => $orderinfos['sagent'], 'name' => '地区代理商佣金'],
            ['value' => $orderinfos['fprerent'], 'name' => '市级总监佣金'],
            ['value' => $orderinfos['sprerent'], 'name' => '地区总监佣金'],
            ['value' => $orderinfos['fee'], 'name' => '运营成本']
        ];

        $this->assign(StatisticsModel::systemTable($where));
        $this->assign(compact('where', 'trans', 'orderCount', 'orderDays', 'header', 'Statistic', 'ordinary', 'pink', 'recharge', 'data', 'seckill'));
        $this->assign('price', StatisticsModel::getOrderPrice($where));

        return $this->fetch();
    }
}