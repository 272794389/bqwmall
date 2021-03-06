<?php
/**
 *
 * @author: xaboy<365615158@qq.com>
 * @day: 2018/01/05
 */

namespace app\models\store;

use crmeb\basic\BaseModel;
use crmeb\services\MiniProgramService;
use crmeb\services\WechatService;
use crmeb\repositories\ShortLetterRepositories;
use crmeb\traits\ModelTrait;
use app\models\user\User;
use app\models\system\SystemStore;
use app\models\user\StorePayLog;
use app\models\user\WechatUser;
use app\admin\model\system\DataConfig;
use think\facade\Db;
use crmeb\services\{ SystemConfigService, WechatTemplateService, workerman\ChannelService};
use crmeb\repositories\{ PaymentRepositories, OrderRepository};
use think\facade\Route as Url;

/**
 * TODO 用户到商家消费
 * Class UserRecharge
 * @package app\models\user
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

    protected $insert = ['add_time'];

    protected function setAddTimeAttr()
    {
        return time();
    }

    /**
     * 创建vip订单
     * @param $uid
     * @param $price
     * @param string $recharge_type
     * @param int $paid
     * @return UserRecharge|bool|\think\Model
     */
    public static function addOrder($uid,$store_id,$total_amount,$check_id)
    {
        $order_id = self::getNewOrderId($uid);
        if(!$order_id) return self::setErrorInfo('订单生成失败！');
        $add_time = time();
        $storeInfo = SystemStore::getStoreDispose($store_id);//获取商家信息
        $userInfo = User::getUserInfo($uid);
        //计算商家购物积分支付金额
        $give_amount = round( $storeInfo['give_rate']*$total_amount/100,2);
        $give_point = $userInfo['give_point'];
        $pay_points = $userInfo['pay_point'];
        $pay_give=0;//购物积分支付金额
        $pay_point=0;//消费积分支付金额
        if($give_point<$give_amount){
            $pay_give = $give_point;
        }else{
            $pay_give= $give_amount;
        }
        $pay_amount = $total_amount;
       // $pay_amount = bcsub($total_amount,$pay_give,2);
       
        //计算优惠券优惠金额
        //判断该商家是否支持抵扣券抵扣
        $couponList = StoreCoupon::where('status',1)->where('is_del',0)->where('use_min_price','<=',$total_amount)
        //->whereFindinSet('product_id', $store_id)
        ->where('belong',$storeInfo['belong'])
        ->field('*')
        ->order('coupon_price', 'DESC')
        ->select();
        $coupon_price=0;
        $coupon_amount=0;
        if(count($couponList)>0){
            $coupon = $couponList[0];
            $coupon_amount = $coupon['coupon_price'];
        }
        
        if($coupon_amount>0){//商家有抵扣活动，判断是否还有抵扣金额
            //计算消费积分抵扣
            if($pay_points>0){
                if($pay_points>$coupon_amount){
                    $pay_point = $coupon_amount;
                }else{
                    $pay_point = $pay_points;
                }
            }
            $couponMap = GoodsCouponUser::where('uid',$uid)->where('is_fail',0)->field('sum(coupon_price) as acoupon_price,sum(hamount) as hamount')->find();
            $mcouponAmount = 0;
            if($couponMap){
                $mcouponAmount = $couponMap['acoupon_price']-$couponMap['hamount'];
            }
            if($mcouponAmount<$coupon_amount){
                $coupon_amount = $mcouponAmount;
            }
        }
        $pay_pointer = 0;
        if($pay_amount==$total_amount){
            $pay_pointer =  round( $storeInfo['pay_rate']*$pay_amount/100,2);
        }
        return self::create(compact('order_id','uid','store_id','total_amount','pay_amount','coupon_amount','pay_give','pay_point','add_time','pay_pointer','check_id'));
    }
    
    //计算积分抵扣或抵扣券抵扣的金额
    public static function computerOrder($orderid,$useIntegral,$useCoupon,$usePayIntegral)
    {
        $orderinfo = self::get($orderid);
        $order_id = $orderid;
        
        $storeInfo = SystemStore::getStoreDispose($orderinfo['store_id']);//获取商家信息
        $userInfo = User::getUserInfo($orderinfo['uid']);
        $pay_flag=0;
        $pay_point=$orderinfo['pay_point'];
        $pay_pointer=0;
        $pay_give=$orderinfo['pay_give'];
        $coupon_amount=$orderinfo['coupon_amount'];
        $total_amount = $orderinfo['total_amount'];
        $pay_amount = $orderinfo['total_amount'];
        //计算优惠券优惠金额
        //判断该商家是否支持抵扣券抵扣
        $couponList = StoreCoupon::where('status',1)->where('is_del',0)->where('use_min_price','<=',$total_amount)
        //->whereFindinSet('product_id', $orderinfo['store_id'])
        ->where('belong',$storeInfo['belong'])
        ->field('*')
        ->order('coupon_price', 'DESC')
        ->select();
        
        if(count($couponList)>0){
            $coupon = $couponList[0];
            $coupon_amount = $coupon['coupon_price'];
        }
        if($useIntegral==1){
            //计算商家购物积分支付金额
            $give_amount = round( $storeInfo['give_rate']*$total_amount/100,2);
            $give_point = $userInfo['give_point'];
           //购物积分支付金额
            if($give_point<$give_amount){
                $pay_give = $give_point;
            }else{
                $pay_give= $give_amount;
            }
            $pay_amount = bcsub($total_amount,$pay_give,2);
            if($pay_give>0){
                $pay_flag=1;
            }
        }else if($useCoupon==1){//使用抵扣券
            if($coupon_amount>0){//商家有抵扣活动，判断是否还有抵扣金额
                $couponMap = GoodsCouponUser::where('uid',$orderinfo['uid'])->where('is_fail',0)->field('sum(coupon_price) as acoupon_price,sum(hamount) as hamount')->find();
                $mcouponAmount = 0;
                if($couponMap){
                    $mcouponAmount = $couponMap['acoupon_price']-$couponMap['hamount'];
                }
                if($mcouponAmount<$coupon_amount){
                    $coupon_amount = $mcouponAmount;
                }
                $pay_amount = bcsub($total_amount,$coupon_amount,2);
                if($coupon_amount>0){
                    $pay_flag=3;
                }
            }
          
        }else if($usePayIntegral==1){//使用消费积分抵扣券
            //计算商家消费积分支付金额
            if($coupon_amount>0){//商家有抵扣活动，判断是否还有抵扣金额
              $pay_points = $userInfo['pay_point'];
              if($pay_points>$coupon_amount){
                  $pay_point = $coupon_amount;
              }else{
                  $pay_point = $pay_points;
              }
              $pay_amount = bcsub($total_amount,$pay_point,2);
            }
            if($pay_point>0){
                $pay_flag=2;
            }
        }
        
        if($pay_amount==$total_amount){
            $pay_pointer =  round( $storeInfo['pay_rate']*$pay_amount/100,2);
            
        }
        return self::where('id',$orderid)->update(['pay_amount'=>$pay_amount,'pay_give'=>$pay_give,'coupon_amount'=>$coupon_amount,'pay_pointer'=>$pay_pointer,'pay_point'=>$pay_point,'pay_flag'=>$pay_flag]);
    }
    
    /*
     * 获取门店信息
     * @param int $id
     * */
    public static function getPayOrder($uid,$id = 0, $felid = '')
    {
       $orderInfo = self::where('id', $id)->where('paid',0)->where('uid',$uid)->find();
       return $orderInfo;
    }

    /**
     * 生成充值订单号
     * @param int $uid
     * @return bool|string
     */
    public static function getNewOrderId($uid = 0)
    {
        if(!$uid) return false;
        $count = (int)self::where('uid', $uid)->where('add_time', '>=', strtotime(date("Y-m-d")))->where('add_time', '<', strtotime(date("Y-m-d", strtotime('+1 day'))))->count();
        return 'wx' . date('YmdHis', time()) . (10000 + $count + $uid);
    }
    
    
    /**
     * 累计消费
     * @param $uid
     * @return float
     */
    public static function getOrderStatusSum($uid)
    {
        return self::where('uid', $uid)->where('paid', 1)->sum('pay_amount');
    }

    /**
     * 充值js支付
     * @param $orderInfo
     * @return array|string
     * @throws \Exception
     */
    public static function jsPay($orderInfo)
    {
        return MiniProgramService::jsPay(WechatUser::uidToOpenid($orderInfo['uid']),$orderInfo['order_id'],$orderInfo['pay_amount'],'user_pay','商家消费');
    }

    /**
     * 微信H5支付
     * @param $orderInfo
     * @return mixed
     */
    public static function h5Pay($orderInfo)
    {
        return WechatService::paymentPrepare(null,$orderInfo['order_id'],$orderInfo['pay_amount'],'user_pay','商家消费', '', 'MWEB');
    }
    /**
     * 公众号支付
     * @param $orderInfo
     * @return array|string
     * @throws \Exception
     */
    public static function wxPay($orderInfo)
    {
        return WechatService::jsPay(WechatUser::uidToOpenid($orderInfo['uid'], 'openid'),$orderInfo['order_id'],$orderInfo['pay_amount'],'user_pay','商家消费');
    }

    /**
     * //TODO用户下单成功后
     * @param $orderId
     */
    public static function paySuccess($orderId, $paytype = 'weixin')
    {
        $orderInfo = self::where('order_id',$orderId)->where('paid',0)->find();
        $uid = $orderInfo['uid'];
        if(!$orderInfo) return false;
        //$storeInfo = SystemStore::getStoreDispose($orderInfo['store_id']);
        $storeInfo = SystemStore::where('id',$orderInfo['store_id'])->find();
        $userInfo = User::getUserInfo($uid);
        $userInfo['add_time'] = User::where('uid',$uid)->value('add_time');
        //获取平台费率参数
        $feeRate = DataConfig::where('id', 1)->find();
        //短信发送开关
        $sms_open = $feeRate['sms_open'];
        self::beginTrans();
        $res = true;
        $dikou=0;
        $pay_pointer=0;
        $flag=0;$total_amount=0;$pay_amount=0;$huokuan=0;$pointer=0;$coupon_amount=0;$shopaward=0;$faward=0;$saward=0;$fagent=0;$sagent=0;$fprerent=0;$sprerent=0;$out_amount=0;$feet=0;$profit=0;
        $total_amount = $orderInfo['total_amount'];
        if($orderInfo['pay_give']>0&&$orderInfo['pay_flag']==1){//购物积分抵扣
            $res = false !== User::bcDec($uid, 'give_point', $orderInfo['pay_give'], 'uid');
            if($res){
                $res = StorePayLog::expend($uid, $orderInfo['id'], 0, 0, 0, -$orderInfo['pay_give'], 0,0,0, '商家消费' . floatval($orderInfo['total_amount']) . '元抵扣');
            }
            $dikou = $orderInfo['pay_give'];
            $pointer = $orderInfo['pay_give'];
        }else if($orderInfo['pay_point']>0&&$orderInfo['pay_flag']==2){//消费积分抵扣
            $res = false !== User::bcDec($uid, 'pay_point', $orderInfo['pay_point'], 'uid');
            if($res){
                $res = StorePayLog::expend($uid, $orderInfo['id'], 0, 0, 0, 0,-$orderInfo['pay_point'],0,0, '商家消费' . floatval($orderInfo['total_amount']) . '元抵扣');
            }
            $dikou = $orderInfo['pay_point'];
            $pointer = $orderInfo['pay_point'];
        }else if($orderInfo['coupon_amount']>0&&$orderInfo['pay_flag']==3){//有使用抵扣券
            $coupon_price = $orderInfo['coupon_amount'];
            $couponList = GoodsCouponUser::getAllCouponList($uid,0);
            foreach ($couponList as $coupon){
                $amount = bcsub($coupon['coupon_price'],$coupon['hamount'],2);
                if($amount>0&&$amount>$coupon_price&&$coupon_price>0){//该次抵扣券足够抵扣
                    //更改已使用金额
                    $pamount = bcadd($coupon['hamount'],$coupon_price,2);
                    GoodsCouponUser::where('id',$coupon['id'])->update(['hamount' => $pamount]);
                    //写入抵扣记录
                    $couponUse = [
                        'cid' => $coupon['id'],
                        'order_id' => $orderInfo['order_id'],
                        'coupon_price' => $coupon_price,
                        'add_time' => time(),
                    ];
                    $info = GoodsCouponUse::create($couponUse);
                    if (!$info) return self::setErrorInfo('抵扣券记录写入失败!', true);
                    $coupon_price=0;
                    break;
                }else if($amount>0&&($amount<$coupon_price||$amount==$coupon_price)){//该次抵扣券不足以抵扣
                    GoodsCouponUser::where('id',$coupon['id'])->update(['hamount' => $coupon['coupon_price']]);
                    //写入抵扣记录
                    $couponUse = [
                        'cid' => $coupon['id'],
                        'order_id' => $orderInfo['order_id'],
                        'coupon_price' => $amount,
                        'add_time' => time(),
                    ];
                    $info = GoodsCouponUse::create($couponUse);
                    if (!$info) return self::setErrorInfo('抵扣券记录写入失败!', true);
                    $coupon_price=bcsub($coupon_price,$amount,2);
                }
                if($coupon_price==0){
                    break;
                }
            }
            $dikou = $orderInfo['coupon_amount'];
            $coupon_amount= $orderInfo['coupon_amount'];
        }else if($orderInfo['pay_pointer']>0&&$orderInfo['pay_flag']==0){//判断是否有赠送积分
            $res = false !== User::bcInc($uid, 'pay_point',$orderInfo['pay_pointer'], 'uid');
            $pay_pointer = $orderInfo['pay_pointer'];
            if($res){
                $res = StorePayLog::expend($uid, $orderInfo['id'], 0, 0, 0, 0, $orderInfo['pay_pointer'],0,0, '商家消费赠送消费积分');
            }
        }
        
        //给商家结算货款
        $amount = $orderInfo['total_amount']*(100-$storeInfo['sett_rate'])/100;
        $huokuan = $amount;
        if($res){
            $res = false !== User::bcInc($storeInfo['user_id'], 'huokuan', $amount, 'uid');
            if($res){
                $res = StorePayLog::expend($storeInfo['user_id'], $orderInfo['id'], 0, 0, $amount, 0, 0,0,0, '货款结算');
            }
            if($amount>0){//给商家结算货款
                //给商家发送支付成功提醒
                WechatTemplateService::sendTemplate(WechatUser::where('uid', $storeInfo['user_id'])->value('openid'), WechatTemplateService::ORDERTIPS_SUCCESS, [
                    'first' => '尊敬的商家您好，您的店铺刚成交一笔消费订单',
                    'keyword1' => $orderInfo['order_id'],
                    'keyword2' => '客户扫码消费',
                    'keyword3' => $amount,
                    'keyword4' => $storeInfo['mer_name'],
                    'keyword5' => '客户扫码消费成功，消费金额'.$orderInfo['total_amount'].'元，结算货款'.$amount.'元，请注意查收！',
                    'remark' => '点击查看货款记录'
                ], Url::buildUrl('/user/huokuan')->suffix('')->domain(true)->build());
                if($sms_open>0){
                    $data['code'] = '1';
                    $content = "尊敬的商户您好，您刚完成一笔交易，货款结算：".$amount."元！";
                    ShortLetterRepositories::send(true, $storeInfo['link_phone'], $data,$content); 
                }
            }
            SystemStore::bcInc($orderInfo['store_id'], 'sales', 1, 'id');
        }
        
        //用于分配整体利润
        $runamount = ($orderInfo['total_amount'] - $amount - $dikou)*(100-$feeRate['plat_rate'])/100;
        $profit = $runamount;
        $out_amount = ($orderInfo['total_amount'] - $amount - $dikou)*$feeRate['plat_rate']/100;
        $pay_amount = $orderInfo['pay_amount'];
        $use_amount = 0;
        $fee=0;
        $repeat_point=0;
        //计算3代推荐奖励
        $spread_uid = $userInfo['spread_uid'];
        
        if($res&&$runamount>0&&$spread_uid>0){
            for ($i=0; $i < 3; $i++)
            {
                $uinfo = User::getUserInfo($spread_uid);
                $use_amount=0;
                if($spread_uid>0){//存在推荐人
                    if($i==0&&$feeRate['rec_f']>0){//第一代推荐人
                        $use_amount = $runamount*$feeRate['rec_f']/100;
                    }else if($i==1&&$feeRate['rec_s']>0){//第二代推荐人
                        $use_amount = $runamount*$feeRate['rec_s']/100;
                    }else if($i==2&&$feeRate['rec_t']>0){//第三代推荐人
                        $use_amount = $runamount*$feeRate['rec_t']/100;
                    }
                    if($use_amount>0.012){
                        $fee = $use_amount*$feeRate['fee_rate']/100;
                        $repeat_point = $use_amount*$feeRate['repeat_rate']/100;
                        $use_amount = $use_amount - $fee - $repeat_point;
                        if($res){
                            $res = false !== User::bcInc($spread_uid, 'now_money', $use_amount, 'uid');
                            if($i==0){//第一代奖励
                                $faward = $use_amount;
                                $feet += $fee;
                                $profit = $profit-$use_amount-$repeat_point;
                            }else if($i==1){//第二代推荐人
                                $saward = $use_amount;
                                $feet += $fee;
                                $profit = $profit-$use_amount-$repeat_point;
                            }
                        }
                        if($res){
                            $res = false !== User::bcInc($spread_uid, 'repeat_point', $repeat_point, 'uid');
                        }
                        if($res&&$use_amount>0){
                            $res = StorePayLog::expend($spread_uid, $orderInfo['id'], 0, $use_amount, 0, 0, 0,$repeat_point,$fee, '分销奖励');
                        }
                        if($uinfo['phone']&&$sms_open>0){//推荐奖励
                            $data['code'] = '1';
                            $content = "尊敬的客户您好，您的账户收到一笔分销奖励，奖励金额：".$use_amount."元！";
                            ShortLetterRepositories::send(true, $uinfo['phone'], $data,$content);
                        }
                        //给推荐人发送余额变动通知
                        $use_amount = bcadd($use_amount, 0, 2);
                        $fenamount = bcadd($uinfo['now_money'], $use_amount, 2);
                        WechatTemplateService::sendTemplate(WechatUser::where('uid', $spread_uid)->value('openid'), WechatTemplateService::MONEYCHANGE_SUCCESS, [
                            'first' => '尊敬的客户您好，您在佰仟万平台的账户余额产生了变动',
                            'keyword1' => '平台发放分销奖励',
                            'keyword2' => '+'.$use_amount,
                            'keyword3' => $fenamount,
                            'remark' => '佰仟万平台感谢您的支持'
                        ], Url::buildUrl('/user/account')->suffix('')->domain(true)->build());
                    }
                    
                    //判断是否是商家直接推荐
                    if($spread_uid==$storeInfo['user_id']&&$i==0){
                       //判断商家推荐人与商家扫码人是否一致
                       if($userInfo['add_time']>$storeInfo['add_time']){
                           $spread_uid = $storeInfo['parent_id'];
                       }else{
                           $spread_uid = $uinfo['spread_uid'];
                       }
                    }else{
                        $spread_uid = $uinfo['spread_uid'];
                    }
                }else{
                    break;
                }
            }
        }
        //计算商家推荐人奖励
        $uinfo = User::getUserInfo($storeInfo['parent_id']);
        if($storeInfo['parent_id']>0&&$feeRate['shop_rec']>0){
            $use_amount = $runamount*$feeRate['shop_rec']/100;
            $fee = $use_amount*$feeRate['fee_rate']/100;
            $repeat_point = $use_amount*$feeRate['repeat_rate']/100;
            $use_amount = $use_amount - $fee - $repeat_point;
            $feet +=$fee;
            if($res){
                $res = false !== User::bcInc($uinfo['uid'], 'now_money', $use_amount, 'uid');
                $shopaward = $use_amount;
                $profit = $profit-$use_amount-$repeat_point;
            }
            if($res){
                $res = false !== User::bcInc($uinfo['uid'], 'repeat_point', $repeat_point, 'uid');
            }
            if($res&&$use_amount>0.012){
                $res = StorePayLog::expend($uinfo['uid'], $orderInfo['id'], 0, $use_amount, 0, 0, 0,$repeat_point,$fee, '商家推荐奖励');
            }
            if($uinfo['phone']&&$sms_open>0){//推荐奖励
                $data['code'] = '1';
                $content = "尊敬的客户您好，您的账户收到一笔商家推荐奖励，奖励金额：".$use_amount."元！";
                ShortLetterRepositories::send(true, $uinfo['phone'], $data,$content);
            }
            
            //给推荐人发送余额变动通知
            $use_amount = bcadd($use_amount, 0, 2);
            $fenamount = bcadd($uinfo['now_money'], $use_amount, 2);
            WechatTemplateService::sendTemplate(WechatUser::where('uid', $uinfo['uid'])->value('openid'), WechatTemplateService::MONEYCHANGE_SUCCESS, [
                'first' => '尊敬的客户您好，您在佰仟万平台的账户余额产生了变动',
                'keyword1' => '平台发放商家推荐奖励',
                'keyword2' => '+'.$use_amount,
                'keyword3' => $fenamount,
                'remark' => '感谢您的支持'
            ], Url::buildUrl('/user/account')->suffix('')->domain(true)->build());
        }
        
        //计算省市区代理提成
        $address = $storeInfo['address'];
        $agent = explode(",",$address);
        
        $districtInfo = Db::name('system_city')->where('name', 'like', "%$agent[2]%")->find();//地区
        //查询区id
        $cityInfo = Db::name('system_city')->where('city_id', $districtInfo['parent_id'])->find();//城市
        $province = Db::name('system_city')->where('city_id', $cityInfo['parent_id'])->find();//省份
        //计算代理商总佣金
        $agentAmount = $feeRate['agent_pro']*$runamount/100;
        $districtAmount = 0;
        $cityAmount = 0;
        if($agentAmount>0){
            $use_amount=0;
            if($districtInfo['agent_uid']>0){//地区代理佣金
                $use_amount = $runamount*$feeRate['agent_district']/100;
                $fee = $use_amount*$feeRate['fee_rate']/100;
                $repeat_point = $use_amount*$feeRate['repeat_rate']/100;
                $districtAmount = $use_amount - $fee - $repeat_point;
                if($res&&$districtAmount>0.012){
                    $res = false !== User::bcInc($districtInfo['agent_uid'], 'now_money', $districtAmount, 'uid');
                    $uinfo = User::getUserInfo($districtInfo['agent_uid']);
                    $sagent = $districtAmount;
                    $profit = $profit-$districtAmount-$repeat_point;
                    $feet +=$fee;
                    if($uinfo['phone']&&$sms_open>0){//推荐奖励
                        $data['code'] = '1';
                        $content = "尊敬的代理商您好，您的账户收到一笔代理商奖励，奖励金额：".$districtAmount."元！";
                        ShortLetterRepositories::send(true, $uinfo['phone'], $data,$content);
                    }
                    //给推荐人发送余额变动通知
                    $districtAmount = bcadd($districtAmount, 0, 2);
                    $fenamount = bcadd($uinfo['now_money'], $districtAmount, 2);
                    WechatTemplateService::sendTemplate(WechatUser::where('uid', $uinfo['uid'])->value('openid'), WechatTemplateService::MONEYCHANGE_SUCCESS, [
                        'first' => '尊敬的客户您好，您在佰仟万平台的账户余额产生了变动',
                        'keyword1' => '平台发放代理商提成',
                        'keyword2' => '+'.$districtAmount,
                        'keyword3' => $fenamount,
                        'remark' => '感谢您的支持'
                    ], Url::buildUrl('/user/account')->suffix('')->domain(true)->build());
                }
                if($res&&$districtAmount>0.012){
                    $res = false !== User::bcInc($districtInfo['agent_uid'], 'repeat_point', $repeat_point, 'uid');
                }
                if($res&&$districtAmount>0.012){
                    $res = StorePayLog::expend($districtInfo['agent_uid'], $orderInfo['id'], 0, $districtAmount, 0, 0, 0,$repeat_point,$fee, '地区代理商奖励');
                }
            }
            if($cityInfo['agent_uid']>0){//城市代理佣金
                $use_amount = $runamount*$feeRate['agent_city']/100-$districtAmount;
                $fee = $use_amount*$feeRate['fee_rate']/100;
                $repeat_point = $use_amount*$feeRate['repeat_rate']/100;
                $cityAmount = $use_amount - $fee - $repeat_point;
                if($res&&$cityAmount>0.012){
                    $res = false !== User::bcInc($cityInfo['agent_uid'], 'now_money', $cityAmount, 'uid');
                    $uinfo = User::getUserInfo($cityInfo['agent_uid']);
                    $fagent = $cityAmount;
                    $profit = $profit-$cityAmount-$repeat_point;
                    $feet +=$fee;
                    if($uinfo['phone']&&$sms_open>0){//推荐奖励
                        $data['code'] = '1';
                        $content = "尊敬的代理商您好，您的账户收到一笔代理商奖励，奖励金额：".$cityAmount."元！";
                        ShortLetterRepositories::send(true, $uinfo['phone'], $data,$content);
                    }
                    //给推荐人发送余额变动通知
                    $cityAmount = bcadd($cityAmount, 0, 2);
                    $fenamount = bcadd($uinfo['now_money'], $cityAmount, 2);
                    WechatTemplateService::sendTemplate(WechatUser::where('uid', $uinfo['uid'])->value('openid'), WechatTemplateService::MONEYCHANGE_SUCCESS, [
                        'first' => '尊敬的客户您好，您在佰仟万平台的账户余额产生了变动',
                        'keyword1' => '平台发放代理商提成',
                        'keyword2' => '+'.$cityAmount,
                        'keyword3' => $fenamount,
                        'remark' => '感谢您的支持'
                    ], Url::buildUrl('/user/account')->suffix('')->domain(true)->build());
                }
                if($res&&$cityAmount>0.012){
                    $res = false !== User::bcInc($cityInfo['agent_uid'], 'repeat_point', $repeat_point, 'uid');
                }
                if($res&&$cityAmount>0.012){
                    $res = StorePayLog::expend($cityInfo['agent_uid'], $orderInfo['id'], 0, $cityAmount, 0, 0, 0,$repeat_point,$fee, '城市代理商奖励');
                }
            }
            if($province['agent_uid']>0){//省级代理佣金
                $use_amount = $runamount*$feeRate['agent_pro']/100-$districtAmount-$cityAmount;
                $fee = $use_amount*$feeRate['fee_rate']/100;
                $repeat_point = $use_amount*$feeRate['repeat_rate']/100;
                $agentAmount = $use_amount - $fee - $repeat_point;
                if($res&&$agentAmount>0){
                    $res = false !== User::bcInc($province['agent_uid'], 'now_money', $agentAmount, 'uid');
                    $uinfo = User::getUserInfo($province['agent_uid']);
                    if($uinfo['phone']&&$sms_open>0){//推荐奖励
                        $data['code'] = '1';
                        $content = "尊敬的代理商您好，您的账户收到一笔代理商奖励，奖励金额：".$agentAmount."元！";
                        ShortLetterRepositories::send(true, $uinfo['phone'], $data,$content);
                    }
                    //给推荐人发送余额变动通知
                    $agentAmount = bcadd($agentAmount, 0, 2);
                    $fenamount = bcadd($uinfo['now_money'], $agentAmount, 2);
                    WechatTemplateService::sendTemplate(WechatUser::where('uid', $uinfo['uid'])->value('openid'), WechatTemplateService::MONEYCHANGE_SUCCESS, [
                        'first' => '尊敬的客户您好，您在佰仟万平台的账户余额产生了变动',
                        'keyword1' => '平台发放代理商提成',
                        'keyword2' => '+'.$agentAmount,
                        'keyword3' => $fenamount,
                        'remark' => '感谢您的支持'
                    ], Url::buildUrl('/user/account')->suffix('')->domain(true)->build());
                }
                if($res&&$repeat_point>0){
                    $res = false !== User::bcInc($province['agent_uid'], 'repeat_point', $repeat_point, 'uid');
                }
                if($res&&$agentAmount>0){
                    $res = StorePayLog::expend($province['agent_uid'], $orderInfo['id'], 0, $agentAmount, 0, 0, 0,$repeat_point,$fee, '省级代理商奖励');
                }
            }
        }
        
        //计算总监佣金
        $agentAmount = $feeRate['inspect_pro']*$runamount/100;
        $districtAmount = 0;
        $cityAmount = 0;
        if($agentAmount>0){
            $use_amount=0;
            if($districtInfo['inspect_uid']>0){//地区总监佣金
                $use_amount = $runamount*$feeRate['inspect_district']/100;
                $fee = $use_amount*$feeRate['fee_rate']/100;
                $repeat_point = $use_amount*$feeRate['repeat_rate']/100;
                $districtAmount = $use_amount - $fee - $repeat_point;
                if($res&&$districtAmount>0){
                    $res = false !== User::bcInc($districtInfo['inspect_uid'], 'now_money', $districtAmount, 'uid');
                    $uinfo = User::getUserInfo($districtInfo['inspect_uid']);
                    $sprerent = $districtAmount;
                    $profit = $profit-$districtAmount-$repeat_point;
                    $feet +=$fee;
                    if($uinfo['phone']&&$sms_open>0){//推荐奖励
                        $data['code'] = '1';
                        $content = "尊敬的区域总监您好，您的账户收到一笔区域奖励，奖励金额：".$districtAmount."元！";
                        ShortLetterRepositories::send(true, $uinfo['phone'], $data,$content);
                    }
                    //给推荐人发送余额变动通知
                    $districtAmount = bcadd($districtAmount, 0, 2);
                    $fenamount = bcadd($uinfo['now_money'], $districtAmount, 2);
                    WechatTemplateService::sendTemplate(WechatUser::where('uid', $uinfo['uid'])->value('openid'), WechatTemplateService::MONEYCHANGE_SUCCESS, [
                        'first' => '尊敬的客户您好，您在佰仟万平台的账户余额产生了变动',
                        'keyword1' => '平台发放总监提成',
                        'keyword2' => '+'.$districtAmount,
                        'keyword3' => $fenamount,
                        'remark' => '感谢您的支持'
                    ], Url::buildUrl('/user/account')->suffix('')->domain(true)->build());
                }
                if($res&&$districtAmount>0){
                    $res = false !== User::bcInc($districtInfo['inspect_uid'], 'repeat_point', $repeat_point, 'uid');
                }
                if($res&&$districtAmount>0){
                    $res = StorePayLog::expend($districtInfo['inspect_uid'], $orderInfo['id'], 0, $districtAmount, 0, 0, 0,$repeat_point,$fee, '地区代理商奖励');
                }
            }
            if($cityInfo['inspect_uid']>0){//城市总监佣金
                $use_amount = $runamount*$feeRate['inspect_city']/100-$districtAmount;
                $fee = $use_amount*$feeRate['fee_rate']/100;
                $repeat_point = $use_amount*$feeRate['repeat_rate']/100;
                $cityAmount = $use_amount - $fee - $repeat_point;
                if($res&&$cityAmount){
                    $res = false !== User::bcInc($cityInfo['inspect_uid'], 'now_money', $cityAmount, 'uid');
                    $uinfo = User::getUserInfo($cityInfo['inspect_uid']);
                    $fprerent = $cityAmount;
                    $profit = $profit-$cityAmount-$repeat_point;
                    $feet +=$fee;
                    if($uinfo['phone']&&$sms_open>0){//推荐奖励
                        $data['code'] = '1';
                        $content = "尊敬的区域总监您好，您的账户收到一笔区域奖励，奖励金额：".$cityAmount."元！";
                        ShortLetterRepositories::send(true, $uinfo['phone'], $data,$content);
                    }
                    //给推荐人发送余额变动通知
                    $cityAmount = bcadd($cityAmount, 0, 2);
                    $fenamount = bcadd($uinfo['now_money'], $cityAmount, 2);
                    WechatTemplateService::sendTemplate(WechatUser::where('uid', $uinfo['uid'])->value('openid'), WechatTemplateService::MONEYCHANGE_SUCCESS, [
                        'first' => '尊敬的客户您好，您在佰仟万平台的账户余额产生了变动',
                        'keyword1' => '平台发放总监提成',
                        'keyword2' => '+'.$cityAmount,
                        'keyword3' => $fenamount,
                        'remark' => '感谢您的支持'
                    ], Url::buildUrl('/user/account')->suffix('')->domain(true)->build());
                }
                if($res&&$cityAmount){
                    $res = false !== User::bcInc($cityInfo['inspect_uid'], 'repeat_point', $repeat_point, 'uid');
                }
                if($res&&$cityAmount){
                    $res = StorePayLog::expend($cityInfo['inspect_uid'], $orderInfo['id'], 0, $cityAmount, 0, 0, 0,$repeat_point,$fee, '城市代理商奖励');
                }
            }
            if($province['inspect_uid']>0){//省级总监佣金
                $use_amount = $runamount*$feeRate['inspect_pro']/100-$districtAmount-$cityAmount;
                $fee = $use_amount*$feeRate['fee_rate']/100;
                $repeat_point = $use_amount*$feeRate['repeat_rate']/100;
                $agentAmount = $use_amount - $fee - $repeat_point;
                if($res&&$agentAmount>0){
                    $res = false !== User::bcInc($province['inspect_uid'], 'now_money', $agentAmount, 'uid');
                    $uinfo = User::getUserInfo($province['inspect_uid']);
                    if($uinfo['phone']&&$sms_open>0){//推荐奖励
                        $data['code'] = '1';
                        $content = "尊敬的区域总监您好，您的账户收到一笔区域奖励，奖励金额：".$agentAmount."元！";
                        ShortLetterRepositories::send(true, $uinfo['phone'], $data,$content);
                    }
                    //给推荐人发送余额变动通知
                    $agentAmount = bcadd($agentAmount, 0, 2);
                    $fenamount = bcadd($uinfo['now_money'], $agentAmount, 2);
                    WechatTemplateService::sendTemplate(WechatUser::where('uid', $uinfo['uid'])->value('openid'), WechatTemplateService::MONEYCHANGE_SUCCESS, [
                        'first' => '尊敬的客户您好，您在佰仟万平台的账户余额产生了变动',
                        'keyword1' => '平台发放总监提成',
                        'keyword2' => '+'.$agentAmount,
                        'keyword3' => $fenamount,
                        'remark' => '感谢您的支持'
                    ], Url::buildUrl('/user/account')->suffix('')->domain(true)->build());
                }
                if($res&&$repeat_point>0){
                    $res = false !== User::bcInc($province['inspect_uid'], 'repeat_point', $repeat_point, 'uid');
                }
                if($res&&$agentAmount>0){
                    $res = StorePayLog::expend($province['inspect_uid'], $orderInfo['id'], 0, $agentAmount, 0, 0, 0,$repeat_point,$fee, '省级代理商奖励');
                }
            }
        }
        
        $remark = '本次消费'.$orderInfo['total_amount'].'元,实际支付'.$orderInfo['pay_amount'].'元,';
        if($pay_pointer>0){
            $remark = $remark.'消费赠送'.$pay_pointer.'个消费积分,积分可抵'.$pay_pointer.'元现金使用,';
        }
        $remark = $remark.'感谢您的支持';
        //给客户发送消费通知
        WechatTemplateService::sendTemplate(WechatUser::where('uid', $orderInfo['uid'])->value('openid'), WechatTemplateService::PAYORDER_SUCCESS, [
            'first' => '尊敬的客户您好，您在佰仟万平台完成了一笔交易',
            'keyword1' => '到店扫码消费',
            'keyword2' => $orderInfo['order_id'],
            'keyword3' => date('Y-m-d H:i:s', time()),
            'keyword4' => $orderInfo['total_amount'],
            'remark' => $remark
        ], Url::buildUrl('/user/payorder')->suffix('')->domain(true)->build());
        
        //给核销员发送通知
        if($orderInfo['check_id']>0){
            WechatTemplateService::sendTemplate(WechatUser::where('uid', $orderInfo['check_id'])->value('openid'), WechatTemplateService::PAYORDER_SUCCESS, [
                'first' => '尊敬的管理员您好，您在佰仟万平台完成了一笔交易',
                'keyword1' => '客户到店扫码消费',
                'keyword2' => $orderInfo['order_id'],
                'keyword3' => date('Y-m-d H:i:s', time()),
                'keyword4' => $orderInfo['total_amount'],
                'remark' => '感谢您的支持'
            ], Url::buildUrl('/customer/myorder/'.$orderInfo['check_id'])->suffix('')->domain(true)->build());
        }
        $data=[
            'idno' => $orderInfo['order_id'],
            'flag' => 0,
            'total_amount' => $total_amount,
            'pay_amount' => $pay_amount,
            'huokuan' => $huokuan,
            'pointer' => $pointer,
            'coupon_amount' => $coupon_amount,
            'shopaward' => $shopaward,
            'faward' => $faward,
            'saward' => $saward,
            'fagent' => $fagent,
            'sagent' => $sagent,
            'fprerent' => $fprerent,
            'sprerent' => $sprerent,
            'out_amount' => $out_amount,
            'fee' => $feet,
            'profit' => $profit,
            'add_time' => time(),
        ];
        StoreProfitDetail::create($data);
        $res1 = self::where('order_id',$orderInfo['order_id'])->update(['paid'=>1,'pay_type'=>$paytype,'pay_time'=>time()]);
        $res2 = $res1 && $res;
        self::checkTrans($res2);
        return $res2;
    }
    
    /**
     * 余额支付
     * @param $order_id
     * @param $uid
     * @param string $formId
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function yuePay($order_id, $uid, $formId = '')
    {
        $orderInfo = self::where('uid', $uid)->where('order_id', $order_id)->find();
        if (!$orderInfo) return self::setErrorInfo('订单不存在!');
        if ($orderInfo['paid']) return self::setErrorInfo('该订单已支付!');
        $userInfo = User::getUserInfo($uid);
        if ($userInfo['now_money'] < $orderInfo['pay_amount'])
            return self::setErrorInfo(['status' => 'pay_deficiency', 'msg' => '余额不足' . floatval($orderInfo['pay_amount'])]);
            self::beginTrans();
            $res1 = false !== User::bcDec($uid, 'now_money', $orderInfo['pay_amount'], 'uid');
            $res2 = StorePayLog::expend($uid, $orderInfo['id'], 0, -$orderInfo['pay_amount'], 0, 0, 0,0,0, '余额消费');
            $res3 = self::paySuccess($order_id, 'yue', $formId);//余额支付成功
            try {
                PaymentRepositories::yuePayProduct($userInfo, $orderInfo);
            } catch (\Exception $e) {
                self::rollbackTrans();
                return self::setErrorInfo($e->getMessage());
            }
            $res = $res1 && $res2 && $res3;
            self::checkTrans($res);
            return $res;
    }
    
    /**
     * 微信支付 为 0元时
     * @param $order_id
     * @param $uid
     * @param string $formId
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function jsPayPrice($order_id, $uid, $formId = '')
    {
        $orderInfo = self::where('uid', $uid)->where('order_id', $order_id)->find();
        if (!$orderInfo) return self::setErrorInfo('订单不存在!');
        if ($orderInfo['paid']) return self::setErrorInfo('该订单已支付!');
        $res = self::paySuccess($order_id, 'weixin');//微信支付为0时
        return $res;
    }
    
    /**
     * 获取用户购买次数
     * @param int $uid
     * @return int|string
     */
    public static function getUserCountPay($uid = 0)
    {
        if (!$uid) return 0;
        return self::where('uid', $uid)->where('paid', 1)->count();
    }
    
    /**
     * 获取用户购买金额
     * @param int $uid
     * @return int|string
     */
    public static function getUserSumPay($uid = 0)
    {
        if (!$uid) return 0;
        return self::where('uid', $uid)->where('paid', 1)->field('sum(pay_amount) as pay_price')->find();
    }
    
    
}