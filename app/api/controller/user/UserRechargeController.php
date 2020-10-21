<?php

namespace app\api\controller\user;

use app\models\user\UserRecharge;
use app\models\store\StorePayOrder;
use app\models\store\GoodsCouponUser;
use app\models\user\User;
use app\models\user\WechatUser;
use app\models\user\StorePayLog;
use crmeb\basic\BaseModel;
use app\Request;
use crmeb\services\GroupDataService;
use crmeb\services\SystemConfigService;
use crmeb\services\UtilService;
use crmeb\services\{
    ExpressService,
    JsonService,
    MiniProgramService,
    WechatService,
    FormBuilder as Form,
    WechatTemplateService,
    CacheService,
    UtilService as Util,
    JsonService as Json
};
use think\facade\Route as Url;

/**
 * 充值类
 * Class UserRechargeController
 * @package app\api\controller\user
 */
class UserRechargeController
{

    /**
     * 小程序充值
     *
     * @param Request $request
     * @return mixed
     */
    public function routine(Request $request)
    {
        list($price, $paid_price, $type) = UtilService::postMore([['price', 0], ['paid_price', 0], ['type', 0]], $request, true);
        if (!$price || $price <= 0) return app('json')->fail('参数错误');
        $storeMinRecharge = sys_config('store_user_min_recharge');
        if ($price < $storeMinRecharge) return app('json')->fail('充值金额不能低于' . $storeMinRecharge);
        switch ((int)$type) {
            case 0: //支付充值余额
                $rechargeOrder = UserRecharge::addRecharge($request->uid(), $price, 'routine', $paid_price);
                if (!$rechargeOrder) return app('json')->fail('充值订单生成失败!');
                try {
                    return app('json')->successful(UserRecharge::jsPay($rechargeOrder));
                } catch (\Exception $e) {
                    return app('json')->fail($e->getMessage());
                }
                break;
            case 1: //货款转入余额
                if (UserRecharge::importNowMoney($request->uid(), $price))
                    return app('json')->successful('转入余额成功');
                else
                    return app('json')->fail(UserRecharge::getErrorInfo());
                break;
            default:
                return app('json')->fail('缺少参数');
                break;
        }
    }

    /**
     * 公众号充值
     *
     * @param Request $request
     * @return mixed
     */
    public function wechat(Request $request)
    {
        list($price, $paid_price, $from, $type) = UtilService::postMore([['price', 0], ['paid_price', 0], ['from', 'weixin'], ['type', 0]], $request, true);
        if (!$price || $price <= 0) return app('json')->fail('参数错误');
        $storeMinRecharge = sys_config('store_user_min_recharge');
        if ($price < $storeMinRecharge) return app('json')->fail('充值金额不能低于' . $storeMinRecharge);
        switch ((int)$type) {
            case 0: //支付充值余额
                $rechargeOrder = UserRecharge::addRecharge($request->uid(), $price, 'weixin', $paid_price);
                if (!$rechargeOrder) return app('json')->fail('充值订单生成失败!');
                try {
                    if ($from == 'weixinh5') {
                        $recharge = UserRecharge::wxH5Pay($rechargeOrder);
                    } else {
                        $recharge = UserRecharge::wxPay($rechargeOrder);
                    }
                } catch (\Exception $e) {
                    return app('json')->fail($e->getMessage());
                }
                return app('json')->successful(['type' => $from, 'data' => $recharge]);
                break;
            case 1: //货款转入余额
                if (UserRecharge::importNowMoney($request->uid(), $price))
                    return app('json')->successful('转入余额成功');
                else
                    return app('json')->fail(UserRecharge::getErrorInfo());
                break;
            default:
                return app('json')->fail('缺少参数');
                break;
        }
    }
    
    /**
     * 公众号退款
     *
     * @param Request $request
     * @return mixed
     */
    public function refund(Request $request)
    {
        list($price, $order_id) = UtilService::postMore([['price', 0], ['order_id', 0]], $request, true);
        if (!$price || $price <= 0) return app('json')->fail('退款金额错误');
        
        $orderinfo = StorePayOrder::where('id',$order_id)->where('refund_status',0)->where('paid',1)->find();
        if(!$orderinfo){
            return app('json')->fail('订单不存在');
        }
        if($price>$orderinfo['total_amount']){
            return app('json')->fail('退款金额不能大于支付金额!!');
        }
        
        $data['refund_status']=1;
        $data['refund_amount']=$price;
        $data['refund_uid']= $request->uid();;
        
        $payamount = $orderinfo['total_amount']-$price;//本次消费金额
        //计算实际应退还金额
        $refund_price = $orderinfo['pay_amount']-$payamount;
        $refund_data['pay_price'] = $orderinfo['pay_amount'];
        $refund_data['refund_price'] = $refund_price;
        if ($orderinfo['pay_type'] == 'weixin') {
            try {
                WechatService::payOrderRefund($orderinfo['order_id'], $refund_data);
            } catch (\Exception $e) {
                return Json::fail($e->getMessage());
            }
        } else if ($orderinfo['pay_type'] == 'yue') {  
            BaseModel::beginTrans();
            $usermoney = User::where('uid', $orderinfo['uid'])->value('now_money');
            $res1 = User::bcInc($orderinfo['uid'], 'now_money', $refund_price, 'uid');
            $res2 = $res2 = StorePayLog::expend($orderinfo['uid'], $order_id, 0, $refund_price, 0, 0, 0,0,0, '消费退款');
            $res = $res1 && $res2;
            BaseModel::checkTrans($res);
            if (!$res) return Json::fail('余额退款失败!');
        }
        
        //处理积分、奖励、货款退回
        BaseModel::beginTrans();
        $loglist = StorePayLog::where('order_id',$order_id)->where('belong_t',0)->select();
        if($loglist){
            $loglist = $loglist->toArray();
            foreach ($loglist as $log){
                if($log['mark']!='余额消费'&&$log['mark']!='消费退款'){
                    User::bcDec($log['uid'], 'now_money', $log['use_money'], 'uid');
                    User::bcDec($log['uid'], 'huokuan', $log['huokuan'], 'uid');
                    User::bcDec($log['uid'], 'give_point', $log['give_point'], 'uid');
                    User::bcDec($log['uid'], 'pay_point', $log['pay_point'], 'uid');
                    User::bcDec($log['uid'], 'repeat_point', $log['repeat_point'], 'uid');
                    StorePayLog::expend($log['uid'], $order_id, 0, (-1)*$log['use_money'], (-1)*$log['huokuan'], (-1)*$log['give_point'], (-1)*$log['pay_point'],(-1)*$log['repeat_point'],0, '消费退款');
                } 
            }  
        }
        
        //修改订单状态
        $res = StorePayOrder::edit($data, $order_id, 'id');
        if($orderinfo['pay_flag']==3&&$orderinfo['coupon_amount']>0){
           $coupon = GoodsCouponUser::where('uid',$request->uid())->where('is_fail',0)->where('hamount','>',0)->find();
           $data1['hamount'] = $coupon['hamount']-$orderinfo['coupon_amount'];
           GoodsCouponUser::edit($data1, $coupon['id'], 'id');
        }
        BaseModel::checkTrans($res);
        //给商家发送支付成功提醒
        WechatTemplateService::sendTemplate(WechatUser::where('uid', $orderinfo['uid'])->value('openid'), WechatTemplateService::REFUND_SUCCESS, [
            'first' => '尊敬的商家您好，你支付的款项已原路退回，请查收',
            'keyword1' => $refund_price,
            'keyword2' => date('Y-m-d H:i:s', time()),
            'remark' => '款项将在1-7个工作日内到您的账户'
        ], Url::buildUrl('/user/account')->suffix('')->domain(true)->build());
        
        return Json::successful('退款成功!');
    }
    /**
     * 充值额度选择
     * @return mixed
     */
    public function index()
    {
        $rechargeQuota = sys_data('user_recharge_quota') ?? [];
        $data['recharge_quota'] = $rechargeQuota;
        $recharge_attention = sys_config('recharge_attention');
        $recharge_attention = explode("\n", $recharge_attention);
        $data['recharge_attention'] = $recharge_attention;
        return app('json')->successful($data);
    }
}