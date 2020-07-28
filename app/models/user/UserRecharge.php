<?php
/**
 *
 * @author: xaboy<365615158@qq.com>
 * @day: 2018/01/05
 */

namespace app\models\user;

use crmeb\basic\BaseModel;
use crmeb\services\MiniProgramService;
use crmeb\services\WechatService;
use crmeb\traits\ModelTrait;

/**
 * TODO 用户充值
 * Class UserRecharge
 * @package app\models\user
 */
class UserRecharge extends BaseModel
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
    protected $name = 'user_recharge';

    use ModelTrait;

    protected $insert = ['add_time'];

    protected function setAddTimeAttr()
    {
        return time();
    }

    /**
     * 创建充值订单
     * @param $uid
     * @param $price
     * @param string $recharge_type
     * @param int $paid
     * @return UserRecharge|bool|\think\Model
     */
    public static function addRecharge($uid, $price, $recharge_type = 'weixin', $give_price = 0, $paid = 0)
    {
        $order_id = self::getNewOrderId($uid);
        if (!$order_id) return self::setErrorInfo('订单生成失败！');
        $add_time = time();
        return self::create(compact('order_id', 'uid', 'price', 'recharge_type', 'paid', 'add_time', 'give_price'));
    }

    /**
     * 生成充值订单号
     * @param int $uid
     * @return bool|string
     */
    public static function getNewOrderId($uid = 0)
    {
        if (!$uid) return false;
        $count = (int)self::where('uid', $uid)->where('add_time', '>=', strtotime(date("Y-m-d")))->where('add_time', '<', strtotime(date("Y-m-d", strtotime('+1 day'))))->count();
        return 'wx' . date('YmdHis', time()) . (10000 + $count + $uid);
    }

    /**
     * 充值js支付
     * @param $orderInfo
     * @return array|string
     * @throws \Exception
     */
    public static function jsPay($orderInfo)
    {
        return MiniProgramService::jsPay(WechatUser::uidToOpenid($orderInfo['uid']), $orderInfo['order_id'], $orderInfo['price'], 'user_recharge', '用户充值');
    }

    /**
     * 微信H5支付
     * @param $orderInfo
     * @return mixed
     */
    public static function wxH5Pay($orderInfo)
    {
        return WechatService::paymentPrepare(null, $orderInfo['order_id'], $orderInfo['price'], 'user_recharge', '用户充值', '', 'MWEB');
    }

    /**
     * 公众号支付
     * @param $orderInfo
     * @return array|string
     * @throws \Exception
     */
    public static function wxPay($orderInfo)
    {
        return WechatService::jsPay(WechatUser::uidToOpenid($orderInfo['uid'], 'openid'), $orderInfo['order_id'], $orderInfo['price'], 'user_recharge', '用户充值');
    }

    /**
     * //TODO用户充值成功后
     * @param $orderId
     */
    public static function rechargeSuccess($orderId)
    {
        $order = self::where('order_id', $orderId)->where('paid', 0)->find();
        if (!$order) return false;
        $user = User::getUserInfo($order['uid']);
        self::beginTrans();
        $price = bcadd($order['price'], 0, 2);
        $res1 = self::where('order_id', $order['order_id'])->update(['paid' => 1, 'pay_time' => time()]);
        $res2 = StorePayLog::expend($order['uid'],$order['id'], 4, $price,0, 0, 0,0,0, '余额充值' . floatval($price) . '元');
        $res3 = User::edit(['now_money' => bcadd($user['now_money'], $price, 2)], $order['uid'], 'uid');
        $res = $res1 && $res2 && $res3;
        self::checkTrans($res);
        event('RechargeSuccess', [$order]);
        return $res;
    }
    /**
     * 导入货款到余额
     * @param $uid 用户uid
     * @param $price 导入金额
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function importNowMoney($uid, $price)
    {
        $user = User::getUserInfo($uid);
        self::beginTrans();
        try {
            $huokuan = $user['huokuan'];
            $res=true;
            
            if ($price > $huokuan) return self::setErrorInfo('转入金额不能大于货款余额！');
            $res1 = User::bcInc($uid, 'now_money', $price, 'uid');
            $res3 = User::bcDec($uid, 'huokuan', $price, 'uid');
            $res2 = StorePayLog::expend($uid,-1, 3, $price, -$price, 0, 0,0,0, '货款转入余额' . floatval($price) . '元');
            $res = $res2 && $res1 && $res3;
            self::checkTrans($res);
            if ($res) {
                event('ImportNowMoney', [$uid, $price]);
            }
            return $res;
        } catch (\Exception $e) {
            self::rollbackTrans();
            return self::setErrorInfo($e->getMessage());
        }
    }
}