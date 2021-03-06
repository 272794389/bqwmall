<?php
/**
 *
 * @author: xaboy<365615158@qq.com>
 * @day: 2017/12/20
 */

namespace app\models\store;

use app\admin\model\system\ShippingTemplatesFree;
use app\admin\model\system\ShippingTemplatesRegion;
use app\admin\model\ump\GoodsCoupon as GoodsCouponModel;
use app\admin\model\ump\GoodsCouponUser as CouponUserModel;


use crmeb\basic\BaseModel;
use think\facade\Cache;
use crmeb\traits\ModelTrait;
use think\facade\Log;
use app\models\system\SystemStore;
use app\models\routine\RoutineTemplate;
use app\models\user\StorePayLog;
use app\admin\model\system\DataConfig;
use think\facade\Db;
use app\models\user\{
    User, UserAddress, UserBill, WechatUser
};
use crmeb\services\{
    SystemConfigService, WechatTemplateService, workerman\ChannelService
};
use crmeb\repositories\{
    GoodsRepository, PaymentRepositories, OrderRepository, ShortLetterRepositories, UserRepository
};
use app\admin\model\system\ShippingTemplates;
use think\facade\Route as Url;

/**
 * TODO 订单Model
 * Class StoreOrder
 * @package app\models\store
 */
class StoreOrder extends BaseModel
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
    protected $name = 'store_order';

    use ModelTrait;

    protected $insert = ['add_time'];

    protected static $payType = ['weixin' => '微信支付', 'yue' => '余额支付', 'offline' => '线下支付'];

    protected static $deliveryType = ['send' => '商家配送', 'express' => '快递配送'];

    protected function setAddTimeAttr()
    {
        return time();
    }

    protected function setCartIdAttr($value)
    {
        return is_array($value) ? json_encode($value) : $value;
    }

    protected function getCartIdAttr($value)
    {
        return json_decode($value, true);
    }

    /**获取订单组信息
     * @param $cartInfo
     * @return array
     */
    public static function getOrderPriceGroup($cartInfo, $addr,$uid,$point_pay,$useCoupon)
    {
        $storeFreePostage = floatval(sys_config('store_free_postage')) ?: 0;//满额包邮
        $totalPrice = self::getOrderSumPrice($cartInfo, 'truePrice');//获取订单总金额
        $costPrice = self::getOrderSumPrice($cartInfo, 'costPrice');//获取订单成本价
        $vipPrice = self::getOrderSumPrice($cartInfo, 'vip_truePrice');//获取订单会员优惠金额
        
        //计算需支付的总现金、总购物积分、消费积分、重消积分，赠送总的消费积分、购物积分
        $priceGroup = self::getOrderSumAmount($cartInfo,$uid,$point_pay,$useCoupon);
        $give_point = $priceGroup['give_point'];//赠送购物积分
        $pay_point = $priceGroup['pay_point'];//赠送消费积分
        $pay_amount = $priceGroup['pay_amount'];//支付现金总额
        $pay_paypoint = $priceGroup['pay_paypoint'];//支付消费积分总额
        $pay_repeatpoint = $priceGroup['pay_repeatpoint'];//支付重消积分总额
        $give_rate = $priceGroup['give_rate'];//支付购物积分
        $coupon_price = $priceGroup['coupon_price'];//抵扣金额
        
        //如果满额包邮等于0
        if (!$storeFreePostage) {
            $storePostage = 0;
        } else {
            if ($addr) {
                //按照运费模板计算每个运费模板下商品的件数/重量/体积以及总金额 按照首重倒序排列
                $temp_num = [];
                foreach ($cartInfo as $cart) {
                    $temp = ShippingTemplates::get($cart['productInfo']['temp_id']);
                    if (!$temp) $temp = ShippingTemplates::get(1);
                    if ($temp->getData('type') == 1) {
                        $num = $cart['cart_num'];
                    } elseif ($temp->getData('type') == 2) {
                        $num = $cart['cart_num'] * $cart['productInfo']['attrInfo']['weight'];
                    } else {
                        $num = $cart['cart_num'] * $cart['productInfo']['attrInfo']['volume'];
                    }
                    $region = ShippingTemplatesRegion::where('temp_id', $cart['productInfo']['temp_id'])->where('city_id', $addr['city_id'])->find();
                    if (!$region) $region = ShippingTemplatesRegion::where('temp_id', $cart['productInfo']['temp_id'])->where('city_id', 0)->find();
                    if (!$region) $region = ShippingTemplatesRegion::where('temp_id', 1)->where('city_id', 0)->find();
                    if (!$region) {
                        return self::setErrorInfo('运费模板不存在');
                    }
                    if (!isset($temp_num[$cart['productInfo']['temp_id']])) {
                        $temp_num[$cart['productInfo']['temp_id']]['number'] = $num;
                        $temp_num[$cart['productInfo']['temp_id']]['price'] = bcmul($cart['cart_num'], $cart['truePrice'], 2);
                        $temp_num[$cart['productInfo']['temp_id']]['first'] = $region['first'];
                        $temp_num[$cart['productInfo']['temp_id']]['first_price'] = $region['first_price'];
                        $temp_num[$cart['productInfo']['temp_id']]['continue'] = $region['continue'];
                        $temp_num[$cart['productInfo']['temp_id']]['continue_price'] = $region['continue_price'];
                        $temp_num[$cart['productInfo']['temp_id']]['temp_id'] = $cart['productInfo']['temp_id'];
                        $temp_num[$cart['productInfo']['temp_id']]['city_id'] = $addr['city_id'];
                    } else {
                        $temp_num[$cart['productInfo']['temp_id']]['number'] += $num;
                        $temp_num[$cart['productInfo']['temp_id']]['price'] += bcmul($cart['cart_num'], $cart['truePrice'], 2);
                    }
                }
                array_multisort(array_column($temp_num, 'first_price'), SORT_DESC, $temp_num);
                $type = $storePostage = 0;
                foreach ($temp_num as $k => $v) {
                    if (ShippingTemplatesFree::where('temp_id', $v['temp_id'])->where('city_id', $v['city_id'])->where('number', '<=', $v['number'])->where('price', '<=', $v['price'])->find()) {
                        unset($temp_num[$k]);
                    }
                }
                foreach ($temp_num as $v) {
                    if ($type == 0) {
                        if ($v['number'] <= $v['first']) {
                            $storePostage = bcadd($storePostage, $v['first_price'], 2);
                        } else {
                            if ($v['continue'] <= 0) {
                                $storePostage = $storePostage;
                            } else {
                                $storePostage = bcadd(bcadd($storePostage, $v['first_price'], 2), bcmul(ceil(bcdiv(bcsub($v['number'], $v['first']), $v['continue'] ?? 0, 2)), $v['continue_price']), 2);
                            }
                        }
                        $type = 1;
                    } else {
                        if ($v['continue'] <= 0) {
                            $storePostage = $storePostage;
                        } else {
                            $storePostage = bcadd($storePostage, bcmul(ceil(bcdiv($v['number'], $v['continue'] ?? 0, 2)), $v['continue_price']), 2);
                        }
                    }
                }
            } else {
                $storePostage = 0;
            }
            if ($storeFreePostage <= $totalPrice) $storePostage = 0;//如果总价大于等于满额包邮 邮费等于0
        }
        return compact('storePostage', 'storeFreePostage', 'totalPrice', 'costPrice', 'vipPrice','give_point', 'pay_point', 'pay_amount', 'pay_paypoint', 'pay_repeatpoint', 'give_rate','coupon_price');
    }
    
    /**获取某个字段总金额
     * @param $cartInfo
     * @param $key 键名
     * @return int|string
     */
    public static function getOrderSumAmount($cartInfo,$uid,$point_pay,$useCoupon)
    {
        $userInfo = User::getUserInfo($uid);
        //客户账户余额情况
        $ugive_point = $userInfo['give_point'];
        $upay_point = $userInfo['pay_point'];
        $repeat_point = $userInfo['repeat_point'];
        //$couponMap = GoodsCouponUser::where('uid',$uid)->where('is_fail',0)->where('is_flag',0)->field('sum(coupon_price) as acoupon_price,sum(hamount) as hamount')->find();
        $couponMap = GoodsCouponUser::where('uid',$uid)->where('is_fail',0)->where(['is_flag'=>[0,2]])->field('sum(coupon_price) as acoupon_price,sum(hamount) as hamount')->find();
        $mcouponAmount = 0;
        if($couponMap){
            $mcouponAmount = $couponMap['acoupon_price']-$couponMap['hamount'];
        }
       // echo "mcouponAmount".$mcouponAmount;
        //统计支付及赠送
        $give_point = 0;//赠送购物积分
        $pay_point = 0;//赠送消费积分
        $pay_amount = 0;//支付现金总额
        $pay_paypoint = 0;//支付消费积分总额
        $pay_repeatpoint = 0;//支付重消积分总额
        $give_rate = 0;//支付购物积分
        $coupon_price = 0;//抵扣券抵扣金额
        foreach ($cartInfo as $cart) {
            $payAmount=0;//支付现金部分
            $huokuan = 0;
            $profit = 0;
            $sett_rate = 0;
            $dikouAmount=0;//抵扣券部分
            //判断商品类型
            if($cart['belong_t']==0){//商品中心商品结算
              if($point_pay==1){//积分支付
                if($cart['pay_repeatpoint']>0){//重复消费积分支付
                    if($repeat_point>0&&($repeat_point>$cart['pay_repeatpoint']||$repeat_point==$cart['pay_repeatpoint'])){
                        for($i=0;$i<$cart['cart_num'];$i++){
                            if(!$repeat_point<$cart['pay_repeatpoint']){//重复消费积分支付
                                $pay_repeatpoint = bcadd($pay_repeatpoint, $cart['pay_repeatpoint'], 2);
                                $repeat_point = bcsub($repeat_point,$cart['pay_repeatpoint'],2);
                                $pay_amount = bcadd($pay_amount, $cart['pay_amount'], 2);
                                $payAmount = bcadd($payAmount, $cart['pay_amount'], 2);
                            }else{//现金支付
                                if($mcouponAmount>0&&$cart['coupon_price']>0&&$useCoupon==1){//使用抵扣
                                    if($cart['coupon_price']<$mcouponAmount){
                                        $coupon_price = bcadd($coupon_price, $cart['coupon_price'], 2);
                                        $mcouponAmount = bcsub($mcouponAmount,$cart['coupon_price'],2);
                                        $dikouAmount = bcadd($dikouAmount, $cart['coupon_price'], 2);
                                        $pay_amount = bcadd($pay_amount, $cart['truePrice'], 2);
                                        $pay_amount = bcsub($pay_amount, $cart['coupon_price'], 2);
                                        $payAmount = bcadd($payAmount, $cart['truePrice'], 2);
                                        $payAmount = bcsub($payAmount, $cart['coupon_price'], 2);
                                    }else{
                                        $coupon_price = bcadd($coupon_price, $mcouponAmount, 2);
                                        $dikouAmount = bcadd($dikouAmount, $mcouponAmount, 2);
                                        $pay_amount = bcadd($pay_amount, $cart['truePrice'], 2);
                                        $pay_amount = bcsub($pay_amount, $mcouponAmount, 2);
                                        $payAmount = bcadd($payAmount, $cart['truePrice'], 2);
                                        $payAmount = bcsub($payAmount, $mcouponAmount, 2);
                                        $mcouponAmount = 0;
                                    }  
                                }else{//不使用抵扣
                                    $pay_amount = bcadd($pay_amount, $cart['truePrice'], 2);
                                    if($cart['give_point']>0){//判断是否赠送购物积分
                                        $give_point = bcadd($give_point, $cart['give_point'], 2);
                                    }
                                    $payAmount = bcadd($payAmount, $cart['truePrice'], 2);
                                }
                            } 
                        }
                    }else{//现金支付
                        for($i=0;$i<$cart['cart_num'];$i++){
                            if($mcouponAmount>0&&$cart['coupon_price']>0&&$useCoupon==1){//使用抵扣
                                if($cart['coupon_price']<$mcouponAmount){
                                    $coupon_price = bcadd($coupon_price, $cart['coupon_price'], 2);
                                    $mcouponAmount = bcsub($mcouponAmount,$cart['coupon_price'],2);
                                    $dikouAmount = bcadd($dikouAmount, $cart['coupon_price'], 2);
                                    $pay_amount = bcadd($pay_amount, $cart['truePrice'], 2);
                                    $pay_amount = bcsub($pay_amount, $cart['coupon_price'], 2);
                                    $payAmount = bcadd($payAmount, $cart['truePrice'], 2);
                                    $payAmount = bcsub($payAmount, $cart['coupon_price'], 2);
                                }else{
                                    $coupon_price = bcadd($coupon_price, $mcouponAmount, 2);
                                    $dikouAmount = bcadd($dikouAmount, $mcouponAmount, 2);
                                    $pay_amount = bcadd($pay_amount, $cart['truePrice'], 2);
                                    $pay_amount = bcsub($pay_amount, $mcouponAmount, 2);
                                    $payAmount = bcadd($payAmount, $cart['truePrice'], 2);
                                    $payAmount = bcsub($payAmount, $mcouponAmount, 2);
                                    $mcouponAmount = 0;
                                }
                            }else{
                                $pay_amount = bcadd($pay_amount, $cart['truePrice'], 2);
                                if($cart['give_point']>0){//判断是否赠送购物积分
                                    $give_point = bcadd($give_point, $cart['give_point'], 2);
                                }
                                $payAmount = bcadd($payAmount,$cart['truePrice'], 2);
                            }
                    }  
                  }
                }else if($cart['pay_paypoint']>0){//消费积分支付
                    if($upay_point>0&&($upay_point>$cart['pay_paypoint']||$upay_point==$cart['pay_paypoint'])){
                        for($i=0;$i<$cart['cart_num'];$i++){
                            if(!$upay_point<$cart['pay_paypoint']){//消费积分支付
                                $pay_paypoint = bcadd($pay_paypoint, $cart['pay_paypoint'], 2);
                                $upay_point = bcsub($upay_point,$cart['pay_paypoint'],2);
                                $pay_amount = bcadd($pay_amount, $cart['pay_amount'], 2);
                                $payAmount = bcadd($payAmount, $cart['pay_amount'], 2);
                            }else{//现金支付
                                if($mcouponAmount>0&&$cart['coupon_price']>0&&$useCoupon==1){//使用抵扣
                                    if($cart['coupon_price']<$mcouponAmount){
                                        $coupon_price = bcadd($coupon_price, $cart['coupon_price'], 2);
                                        $mcouponAmount = bcsub($mcouponAmount,$cart['coupon_price'],2);
                                        $dikouAmount = bcadd($dikouAmount, $cart['coupon_price'], 2);
                                        $pay_amount = bcadd($pay_amount, $cart['truePrice'], 2);
                                        $pay_amount = bcsub($pay_amount, $cart['coupon_price'], 2);
                                        $payAmount = bcadd($payAmount, $cart['truePrice'], 2);
                                        $payAmount = bcsub($payAmount, $cart['coupon_price'], 2);
                                    }else{
                                        $coupon_price = bcadd($coupon_price, $mcouponAmount, 2);
                                        $dikouAmount = bcadd($dikouAmount, $mcouponAmount, 2);
                                        $pay_amount = bcadd($pay_amount, $cart['truePrice'], 2);
                                        $pay_amount = bcsub($pay_amount, $mcouponAmount, 2);
                                        $payAmount = bcadd($payAmount, $cart['truePrice'], 2);
                                        $payAmount = bcsub($payAmount, $mcouponAmount, 2);
                                        $mcouponAmount = 0;
                                    }
                                }else{//不使用抵扣
                                    $pay_amount = bcadd($pay_amount, $cart['truePrice'], 2);
                                    if($cart['give_point']>0){//判断是否赠送购物积分
                                        $give_point = bcadd($give_point, $cart['give_point'], 2);
                                    }
                                    $payAmount = bcadd($payAmount, $cart['truePrice'], 2);
                                }
                            }
                        }
                    }else{//现金支付
                        for($i=0;$i<$cart['cart_num'];$i++){
                                if($mcouponAmount>0&&$cart['coupon_price']>0&&$useCoupon==1){//使用抵扣
                                    if($cart['coupon_price']<$mcouponAmount){
                                        $coupon_price = bcadd($coupon_price, $cart['coupon_price'], 2);
                                        $mcouponAmount = bcsub($mcouponAmount,$cart['coupon_price'],2);
                                        $dikouAmount = bcadd($dikouAmount, $cart['coupon_price'], 2);
                                        $pay_amount = bcadd($pay_amount, $cart['truePrice'], 2);
                                        $pay_amount = bcsub($pay_amount, $cart['coupon_price'], 2);
                                        $payAmount = bcadd($payAmount, $cart['truePrice'], 2);
                                        $payAmount = bcsub($payAmount, $cart['coupon_price'], 2);
                                    }else{
                                        $coupon_price = bcadd($coupon_price, $mcouponAmount, 2);
                                        $dikouAmount = bcadd($dikouAmount, $mcouponAmount, 2);
                                        $pay_amount = bcadd($pay_amount, $cart['truePrice'], 2);
                                        $pay_amount = bcsub($pay_amount, $mcouponAmount, 2);
                                        $payAmount = bcadd($payAmount, $cart['truePrice'], 2);
                                        $payAmount = bcsub($payAmount, $mcouponAmount, 2);
                                        $mcouponAmount = 0;
                                    }
                                }else{
                                    $pay_amount = bcadd($pay_amount, $cart['truePrice'], 2);
                                    if($cart['give_point']>0){//判断是否赠送购物积分
                                        $give_point = bcadd($give_point, $cart['give_point'], 2);
                                    }
                                    $payAmount = bcadd($payAmount,$cart['truePrice'], 2);
                                }
                        }  
                    } 
                }else{//纯现金支付
                       for($i=0;$i<$cart['cart_num'];$i++){
                                if($mcouponAmount>0&&$cart['coupon_price']>0&&$useCoupon==1){//使用抵扣
                                    if($cart['coupon_price']<$mcouponAmount){
                                        $coupon_price = bcadd($coupon_price, $cart['coupon_price'], 2);
                                        $mcouponAmount = bcsub($mcouponAmount,$cart['coupon_price'],2);
                                        $dikouAmount = bcadd($dikouAmount, $cart['coupon_price'], 2);
                                        $pay_amount = bcadd($pay_amount, $cart['truePrice'], 2);
                                        $pay_amount = bcsub($pay_amount, $cart['coupon_price'], 2);
                                        $payAmount = bcadd($payAmount, $cart['truePrice'], 2);
                                        $payAmount = bcsub($payAmount, $cart['coupon_price'], 2);
                                    }else{
                                        $coupon_price = bcadd($coupon_price, $mcouponAmount, 2);
                                        $dikouAmount = bcadd($dikouAmount, $mcouponAmount, 2);
                                        $pay_amount = bcadd($pay_amount, $cart['truePrice'], 2);
                                        $pay_amount = bcsub($pay_amount, $mcouponAmount, 2);
                                        $payAmount = bcadd($payAmount, $cart['truePrice'], 2);
                                        $payAmount = bcsub($payAmount, $mcouponAmount, 2);
                                        $mcouponAmount = 0;
                                    }
                                }else{
                                    $pay_amount = bcadd($pay_amount, $cart['truePrice'], 2);
                                    if($cart['give_point']>0){//判断是否赠送购物积分
                                        $give_point = bcadd($give_point, $cart['give_point'], 2);
                                    }
                                    $payAmount = bcadd($payAmount,$cart['truePrice'], 2);
                                }
                       }  
                }
              }else{
                       for($i=0;$i<$cart['cart_num'];$i++){
                                if($mcouponAmount>0&&$cart['coupon_price']>0&&$useCoupon==1){//使用抵扣
                                    if($cart['coupon_price']<$mcouponAmount){
                                        $coupon_price = bcadd($coupon_price, $cart['coupon_price'], 2);
                                        $mcouponAmount = bcsub($mcouponAmount,$cart['coupon_price'],2);
                                        $dikouAmount = bcadd($dikouAmount, $cart['coupon_price'], 2);
                                        $pay_amount = bcadd($pay_amount, $cart['truePrice'], 2);
                                        $pay_amount = bcsub($pay_amount, $cart['coupon_price'], 2);
                                        $payAmount = bcadd($payAmount, $cart['truePrice'], 2);
                                        $payAmount = bcsub($payAmount, $cart['coupon_price'], 2);
                                    }else{
                                        $coupon_price = bcadd($coupon_price, $mcouponAmount, 2);
                                        $dikouAmount = bcadd($dikouAmount, $mcouponAmount, 2);
                                        $pay_amount = bcadd($pay_amount, $cart['truePrice'], 2);
                                        $pay_amount = bcsub($pay_amount, $mcouponAmount, 2);
                                        $payAmount = bcadd($payAmount, $cart['truePrice'], 2);
                                        $payAmount = bcsub($payAmount, $mcouponAmount, 2);
                                        $mcouponAmount = 0;
                                    }
                                }else{
                                    $pay_amount = bcadd($pay_amount, $cart['truePrice'], 2);
                                    if($cart['give_point']>0){//判断是否赠送购物积分
                                        $give_point = bcadd($give_point, $cart['give_point'], 2);
                                    }
                                    $payAmount = bcadd($payAmount,$cart['truePrice'], 2);
                                }
                       }  
              }
            }else if($cart['belong_t']==1||$cart['belong_t']==2){//网店及周边的套餐商品结算
              if($point_pay==1){//积分支付
                if($cart['give_rate']>0){//购物积分支付
                    if($ugive_point>0&&($ugive_point>$cart['give_rate']||$ugive_point==$cart['give_rate'])){
                        for($i=0;$i<$cart['cart_num'];$i++){
                            if(!$ugive_point<$cart['give_rate']){//购物积分支付
                                $give_rate = bcadd($give_rate, $cart['give_rate'], 2);
                                $ugive_point = bcsub($ugive_point,$cart['give_rate'],2);
                                $pay_amount = bcadd($pay_amount, $cart['pay_amount'], 2);
                                $payAmount = bcadd($payAmount, $cart['pay_amount'], 2);
                            }else{//现金支付
                                if($mcouponAmount>0&&$cart['coupon_price']>0&&$useCoupon==1){//使用抵扣
                                    if($cart['coupon_price']<$mcouponAmount){
                                        $coupon_price = bcadd($coupon_price, $cart['coupon_price'], 2);
                                        $mcouponAmount = bcsub($mcouponAmount,$cart['coupon_price'],2);
                                        $dikouAmount = bcadd($dikouAmount, $cart['coupon_price'], 2);
                                        $pay_amount = bcadd($pay_amount, $cart['truePrice'], 2);
                                        $pay_amount = bcsub($pay_amount, $cart['coupon_price'], 2);
                                        $payAmount = bcadd($payAmount, $cart['truePrice'], 2);
                                        $payAmount = bcsub($payAmount, $cart['coupon_price'], 2);
                                    }else{
                                        $coupon_price = bcadd($coupon_price, $mcouponAmount, 2);
                                        $dikouAmount = bcadd($dikouAmount, $mcouponAmount, 2);
                                        $pay_amount = bcadd($pay_amount, $cart['truePrice'], 2);
                                        $pay_amount = bcsub($pay_amount, $mcouponAmount, 2);
                                        $payAmount = bcadd($payAmount, $cart['truePrice'], 2);
                                        $payAmount = bcsub($payAmount, $mcouponAmount, 2);
                                        $mcouponAmount = 0;
                                    }
                                }else{//不使用抵扣
                                    $pay_amount = bcadd($pay_amount, $cart['truePrice'], 2);
                                    if($cart['pay_point']>0){//判断是否赠送消费积分
                                        $pay_point = bcadd($pay_point, $cart['pay_point'], 2);
                                    }
                                    $payAmount = bcadd($payAmount, $cart['truePrice'], 2);
                                }
                            }
                        }
                    }else{//现金支付
                        for($i=0;$i<$cart['cart_num'];$i++){
                            if($mcouponAmount>0&&$cart['coupon_price']>0&&$useCoupon==1){//使用抵扣
                                if($cart['coupon_price']<$mcouponAmount){
                                    $coupon_price = bcadd($coupon_price, $cart['coupon_price'], 2);
                                    $mcouponAmount = bcsub($mcouponAmount,$cart['coupon_price'],2);
                                    $dikouAmount = bcadd($dikouAmount, $cart['coupon_price'], 2);
                                    $pay_amount = bcadd($pay_amount, $cart['truePrice'], 2);
                                    $pay_amount = bcsub($pay_amount, $cart['coupon_price'], 2);
                                    $payAmount = bcadd($payAmount, $cart['truePrice'], 2);
                                    $payAmount = bcsub($payAmount, $cart['coupon_price'], 2);
                                }else{
                                    $coupon_price = bcadd($coupon_price, $mcouponAmount, 2);
                                    $dikouAmount = bcadd($dikouAmount, $mcouponAmount, 2);
                                    $pay_amount = bcadd($pay_amount, $cart['truePrice'], 2);
                                    $pay_amount = bcsub($pay_amount, $mcouponAmount, 2);
                                    $payAmount = bcadd($payAmount, $cart['truePrice'], 2);
                                    $payAmount = bcsub($payAmount, $mcouponAmount, 2);
                                    $mcouponAmount = 0;
                                }
                            }else{
                                $pay_amount = bcadd($pay_amount, $cart['truePrice'], 2);
                                if($cart['pay_point']>0){//判断是否赠送购物积分
                                    $pay_point = bcadd($pay_point, $cart['pay_point'], 2);
                                }  
                                $payAmount = bcadd($payAmount, $cart['truePrice'], 2);
                            }
                        }  
                    }
                }else{//纯现金支付
                    for($i=0;$i<$cart['cart_num'];$i++){
                        if($mcouponAmount>0&&$cart['coupon_price']>0&&$useCoupon==1){//使用抵扣
                            if($cart['coupon_price']<$mcouponAmount){
                                $coupon_price = bcadd($coupon_price, $cart['coupon_price'], 2);
                                $mcouponAmount = bcsub($mcouponAmount,$cart['coupon_price'],2);
                                $dikouAmount = bcadd($dikouAmount, $cart['coupon_price'], 2);
                                $pay_amount = bcadd($pay_amount, $cart['truePrice'], 2);
                                $pay_amount = bcsub($pay_amount, $cart['coupon_price'], 2);
                                $payAmount = bcadd($payAmount, $cart['truePrice'], 2);
                                $payAmount = bcsub($payAmount, $cart['coupon_price'], 2);
                            }else{
                                $coupon_price = bcadd($coupon_price, $mcouponAmount, 2);
                                $dikouAmount = bcadd($dikouAmount, $mcouponAmount, 2);
                                $pay_amount = bcadd($pay_amount, $cart['truePrice'], 2);
                                $pay_amount = bcsub($pay_amount, $mcouponAmount, 2);
                                $payAmount = bcadd($payAmount, $cart['truePrice'], 2);
                                $payAmount = bcsub($payAmount, $mcouponAmount, 2);
                                $mcouponAmount = 0;
                            }
                        }else{
                            $pay_amount = bcadd($pay_amount, $cart['truePrice'], 2);
                            if($cart['pay_point']>0){//判断是否赠送购物积分
                                $pay_point = bcadd($pay_point, $cart['pay_point'], 2);
                            }  
                            $payAmount = bcadd($payAmount, $cart['truePrice'], 2);
                        }
                    }
                }
              }else{
                  for($i=0;$i<$cart['cart_num'];$i++){
                        if($mcouponAmount>0&&$cart['coupon_price']>0&&$useCoupon==1){//使用抵扣
                            if($cart['coupon_price']<$mcouponAmount){
                                $coupon_price = bcadd($coupon_price, $cart['coupon_price'], 2);
                                $mcouponAmount = bcsub($mcouponAmount,$cart['coupon_price'],2);
                                $dikouAmount = bcadd($dikouAmount, $cart['coupon_price'], 2);
                                $pay_amount = bcadd($pay_amount, $cart['truePrice'], 2);
                                $pay_amount = bcsub($pay_amount, $cart['coupon_price'], 2);
                                $payAmount = bcadd($payAmount, $cart['truePrice'], 2);
                                $payAmount = bcsub($payAmount, $cart['coupon_price'], 2);
                            }else{
                                $coupon_price = bcadd($coupon_price, $mcouponAmount, 2);
                                $dikouAmount = bcadd($dikouAmount, $mcouponAmount, 2);
                                $pay_amount = bcadd($pay_amount, $cart['truePrice'], 2);
                                $pay_amount = bcsub($pay_amount, $mcouponAmount, 2);
                                $payAmount = bcadd($payAmount, $cart['truePrice'], 2);
                                $payAmount = bcsub($payAmount, $mcouponAmount, 2);
                                $mcouponAmount = 0;
                            }
                        }else{
                            $pay_amount = bcadd($pay_amount, $cart['truePrice'], 2);
                            if($cart['pay_point']>0){//判断是否赠送购物积分
                                $pay_point = bcadd($pay_point, $cart['pay_point'], 2);
                            }  
                            $payAmount = bcadd($payAmount, $cart['truePrice'], 2);
                        }
                    }
              }
            }
            
            $huokuan = bcmul($cart['cart_num'], $cart['truePrice'], 2)*(100-$cart['sett_rate'])/100;
            if($payAmount>0){
                $profit = $payAmount*$cart['sett_rate']/100*(100-$cart['plat_rate'])/100;
            }
            
            //更改cart表的实际支付金额
            StoreCart::where('id',$cart['id'])->update(['payAmount'=>$payAmount,'huokuan'=>$huokuan,'profit'=>$profit,'coupon_price'=>$dikouAmount]);  
        }
        return compact('give_point', 'pay_point', 'pay_amount', 'pay_paypoint', 'pay_repeatpoint', 'give_rate','coupon_price');
    }


    /**获取某个字段总金额
     * @param $cartInfo
     * @param $key 键名
     * @return int|string
     */
    public static function getOrderSumPrice($cartInfo, $key = 'truePrice')
    {
        $SumPrice = 0;
        foreach ($cartInfo as $cart) {
            $SumPrice = bcadd($SumPrice, bcmul($cart['cart_num'], $cart[$key], 2), 2);
        }
        return $SumPrice;
    }


    /**
     * 拼团
     * @param $cartInfo
     * @return array
     */
    public static function getCombinationOrderPriceGroup($cartInfo)
    {
        $storePostage = floatval(sys_config('store_postage')) ?: 0;
        $storeFreePostage = floatval(sys_config('store_free_postage')) ?: 0;
        $totalPrice = self::getCombinationOrderTotalPrice($cartInfo);
        $costPrice = self::getCombinationOrderTotalPrice($cartInfo);
        if (!$storeFreePostage) {
            $storePostage = 0;
        } else {
            foreach ($cartInfo as $cart) {
                if (!StoreCombination::where('id', $cart['combination_id'])->value('is_postage'))
                    $storePostage = bcadd($storePostage, StoreCombination::where('id', $cart['combination_id'])->value('postage'), 2);
            }
            if ($storeFreePostage <= $totalPrice) $storePostage = 0;
        }
        return compact('storePostage', 'storeFreePostage', 'totalPrice', 'costPrice');
    }

    /**
     * 拼团价格
     * @param $cartInfo
     * @return float
     */
    public static function getCombinationOrderTotalPrice($cartInfo)
    {
        $totalPrice = 0;
        foreach ($cartInfo as $cart) {
            if ($cart['combination_id']) {
                $totalPrice = bcadd($totalPrice, bcmul($cart['cart_num'], StoreCombination::where('id', $cart['combination_id'])->value('price'), 2), 2);
            }
        }
        return (float)$totalPrice;
    }

    /**
     * 缓存订单信息
     * @param $uid
     * @param $cartInfo
     * @param $priceGroup
     * @param array $other
     * @param int $cacheTime
     * @return string
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public static function cacheOrderInfo($uid, $cartInfo, $priceGroup, $other = [], $cacheTime = 600)
    {
        $key = md5(time());
        Cache::set('user_order_' . $uid . $key, compact('cartInfo', 'priceGroup', 'other'), $cacheTime);
        return $key;
    }

    /**
     * 获取订单缓存信息
     * @param $uid
     * @param $key
     * @return mixed|null
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public static function getCacheOrderInfo($uid, $key)
    {
        $cacheName = 'user_order_' . $uid . $key;
        if (!Cache::has($cacheName)) return null;
        return Cache::get($cacheName);
    }

    /**
     * 删除订单缓存
     * @param $uid
     * @param $key
     */
    public static function clearCacheOrderInfo($uid, $key)
    {
        Cache::delete('user_order_' . $uid . $key);
    }

    /**
     * 生成订单
     * @param $uid
     * @param $key
     * @param $addressId
     * @param $payType
     * @param bool $useIntegral
     * @param int $couponId
     * @param string $mark
     * @param int $combinationId
     * @param int $pinkId
     * @param int $seckill_id
     * @param int $bargain_id
     * @param bool $test
     * @param int $isChannel
     * @param int $shipping_type
     * @param string $real_name
     * @param string $phone
     * @return StoreOrder|bool|\think\Model
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */

    public static function cacheKeyCreateOrder($uid, $key, $addressId, $payType, $useIntegral = false,$useCoupon = false, $couponId = 0, $mark = '', $combinationId = 0, $pinkId = 0, $seckill_id = 0, $bargain_id = 0, $test = false, $isChannel = 0, $shipping_type = 1, $real_name = '', $phone = '', $storeId = 0)
    {
        self::beginTrans();
        try {
            $shipping_type = (int)$shipping_type;
            $offlinePayStatus = (int)sys_config('offline_pay_status') ?? (int)2;
            if ($offlinePayStatus == 2) unset(self::$payType['offline']);
            if (!array_key_exists($payType, self::$payType)) return self::setErrorInfo('选择支付方式有误!', true);
            if (self::be(['unique' => $key, 'uid' => $uid])) return self::setErrorInfo('请勿重复提交订单', true);
            $userInfo = User::getUserInfo($uid);
            if (!$userInfo) return self::setErrorInfo('用户不存在!', true);
            $cartGroup = self::getCacheOrderInfo($uid, $key);
            if (!$cartGroup) return self::setErrorInfo('订单已过期,请刷新当前页面!', true);
            $cartInfo = $cartGroup['cartInfo'];
            $priceGroup = $cartGroup['priceGroup'];
            $other = $cartGroup['other'];
            $payPrice = (float)$priceGroup['totalPrice'];
            $addr = UserAddress::where('uid', $uid)->where('id', $addressId)->find();
            if ($payType == 'offline' && sys_config('offline_postage') == 1) {
                $payPostage = 0;
            } else {
                $payPostage = self::getOrderPriceGroup($cartInfo, $addr,$uid,$useIntegral,$useCoupon)['storePostage'];
            }
            if ($shipping_type === 1) {
                if (!$test && !$addressId) return self::setErrorInfo('请选择收货地址!', true);
                if (!$test && (!UserAddress::be(['uid' => $uid, 'id' => $addressId, 'is_del' => 0]) || !($addressInfo = UserAddress::find($addressId))))
                    return self::setErrorInfo('地址选择有误!', true);
            } else {
                if ((!$real_name || !$phone) && !$test) return self::setErrorInfo('请填写姓名和电话', true);
                $addressInfo['real_name'] = $real_name;
                $addressInfo['phone'] = $phone;
                $addressInfo['province'] = '';
                $addressInfo['city'] = '';
                $addressInfo['district'] = '';
                $addressInfo['detail'] = '';
            }
            $cartIds = [];
            $totalNum = 0;
            $gainIntegral = 0;
            foreach ($cartInfo as $cart) {
                $cartIds[] = $cart['id'];
                $totalNum += $cart['cart_num'];
                if (!$seckill_id) $seckill_id = $cart['seckill_id'];
                if (!$bargain_id) $bargain_id = $cart['bargain_id'];
                if (!$combinationId) $combinationId = $cart['combination_id'];
                $cartInfoGainIntegral = isset($cart['productInfo']['give_integral']) ? bcmul($cart['cart_num'], $cart['productInfo']['give_integral'], 2) : 0;
                $gainIntegral = bcadd($gainIntegral, $cartInfoGainIntegral, 2);
            }
            $deduction = $seckill_id || $bargain_id || $combinationId;
            if ($deduction) {
                $couponId = 0;
                $useIntegral = false;
                if (!$test) {
                    unset(self::$payType['offline']);
                    if (!array_key_exists($payType, self::$payType)) return self::setErrorInfo('营销产品不能使用线下支付!', true);
                }
            }
            //使用优惠劵
            $res1 = true;
            //print_r($couponId);
            if ($couponId) {
                $couponInfo = StoreCouponUser::validAddressWhere()->where('id', $couponId)->where('uid', $uid)->find();
                if (!$couponInfo) return self::setErrorInfo('选择的优惠劵无效!', true);
                $coupons = StoreCouponUser::getUsableCouponList($uid, ['valid' => $cartInfo], $payPrice);
                $flag = false;
                foreach ($coupons as $coupon) {
                    if ($coupon['id'] == $couponId) {
                        $flag = true;
                        continue;
                    }
                }
                if (!$flag)
                    return self::setErrorInfo('不满足优惠劵的使用条件!', true);
                $payPrice = (float)bcsub($payPrice, $couponInfo['coupon_price'], 2);
                $res1 = StoreCouponUser::useCoupon($couponId);
                $couponPrice = $couponInfo['coupon_price'];
            } else {
                $couponId = 0;
                $couponPrice = 0;
            }
            if (!$res1) return self::setErrorInfo('使用优惠劵失败!', true);

            //$shipping_type = 1 快递发货 $shipping_type = 2 门店自提
            $store_self_mention = sys_config('store_self_mention') ?? 0;
            if (!$store_self_mention) $shipping_type = 1;
            if ($shipping_type === 1) {
                //是否包邮
                if ((isset($other['offlinePostage']) && $other['offlinePostage'] && $payType == 'offline')) $payPostage = 0;
                $payPrice = (float)bcadd($payPrice, $payPostage, 2);
            } else if ($shipping_type === 2) {
                //门店自提没有邮费支付
                $priceGroup['storePostage'] = 0;
                $payPostage = 0;
                if (!$storeId && !$test) {
                    return self::setErrorInfo('请选择门店', true);
                }
            }

            //积分抵扣
            $res2 = true;
            $SurplusIntegral = 0;
            if ($useIntegral && $userInfo['integral'] > 0) {
                $deductionPrice = (float)bcmul($userInfo['integral'], $other['integralRatio'], 2);
                if ($deductionPrice < $payPrice) {
                    $payPrice = bcsub($payPrice, $deductionPrice, 2);
                    $usedIntegral = $userInfo['integral'];
                    $SurplusIntegral = 0;
                    $res2 = false !== User::edit(['integral' => 0], $userInfo['uid'], 'uid');
                } else {
                    $deductionPrice = $payPrice;
                    $usedIntegral = (float)bcdiv($payPrice, $other['integralRatio'], 2);
                    $SurplusIntegral = bcsub($userInfo['integral'], $usedIntegral, 2);
                    $res2 = false !== User::bcDec($userInfo['uid'], 'integral', $usedIntegral, 'uid');
                    $payPrice = 0;
                }
                $res2 = $res2 && false != UserBill::expend('积分抵扣', $uid, 'integral', 'deduction', $usedIntegral, $key, $userInfo['integral'], '购买商品使用' . floatval($usedIntegral) . '积分抵扣' . floatval($deductionPrice) . '元');
            } else {
                $deductionPrice = 0;
                $usedIntegral = 0;
            }
            if (!$res2) return self::setErrorInfo('使用积分抵扣失败!', true);
            if ($payPrice <= 0) $payPrice = 0;
            if ($test) {
                self::rollbackTrans();
                return [
                    'total_price' => $priceGroup['totalPrice'],
                    //'pay_price' => $payPrice,
                    'pay_price' => $priceGroup['pay_amount'],
                    'give_point' => $priceGroup['give_point'],
                    'pay_point' => $priceGroup['pay_point'],
                    'pay_paypoint' => $priceGroup['pay_paypoint'],
                    'pay_repeatpoint' => $priceGroup['pay_repeatpoint'],
                    'give_rate' => $priceGroup['give_rate'],
                    'pay_postage' => $payPostage,
                    'coupon_price' => $priceGroup['coupon_price'],
                    'deduction_price' => $deductionPrice,
                    'SurplusIntegral' => $SurplusIntegral,
                ];
            }
            $orderInfo = [
                'uid' => $uid,
                'order_id' => $test ? 0 : self::getNewOrderId(),
                'real_name' => $addressInfo['real_name'],
                'user_phone' => $addressInfo['phone'],
                'user_address' => $addressInfo['province'] . ' ' . $addressInfo['city'] . ' ' . $addressInfo['district'] . ' ' . $addressInfo['detail'],
                'cart_id' => $cartIds,
                'total_num' => $totalNum,
                'total_price' => $priceGroup['totalPrice'],
                'total_postage' => $priceGroup['storePostage'],
                'coupon_id' => $couponId,
                'coupon_price' => $priceGroup['coupon_price'],
                'pay_price' => $priceGroup['pay_amount']-$couponPrice,
                'give_point' => $priceGroup['give_point'],
                'pay_point' => $priceGroup['pay_point'],
                'pay_paypoint' => $priceGroup['pay_paypoint'],
                'pay_repeatpoint' => $priceGroup['pay_repeatpoint'],
                'give_rate' => $priceGroup['give_rate'],
                'pay_postage' => $payPostage,
                'deduction_price' => $deductionPrice,
                'paid' => 0,
                'addressId' => $addressId,
                'pay_type' => $payType,
                'use_integral' => $usedIntegral,
                'gain_integral' => $gainIntegral,
                'mark' => htmlspecialchars($mark),
                'combination_id' => $combinationId,
                'pink_id' => $pinkId,
                'seckill_id' => $seckill_id,
                'bargain_id' => $bargain_id,
                'cost' => $priceGroup['costPrice'],
                'is_channel' => $isChannel,
                'add_time' => time(),
                'unique' => $key,
                'point_pay' => $useIntegral,
                'coupon_pay' => $useCoupon,
                'shipping_type' => $shipping_type,
            ];
            if ($shipping_type === 2) {
                $orderInfo['verify_code'] = self::getStoreCode();
                $orderInfo['store_id'] = SystemStore::getStoreDispose($storeId, 'id');
                if (!$orderInfo['store_id']) return self::setErrorInfo('暂无门店无法选择门店自提！', true);
            }
            $order = self::create($orderInfo);
            if (!$order) return self::setErrorInfo('订单生成失败!', true);
            $res5 = true;
            foreach ($cartInfo as $cart) {
                //减库存加销量
                if ($combinationId) $res5 = $res5 && StoreCombination::decCombinationStock($cart['cart_num'], $combinationId, isset($cart['productInfo']['attrInfo']) ? $cart['productInfo']['attrInfo']['unique'] : '');
                else if ($seckill_id) $res5 = $res5 && StoreSeckill::decSeckillStock($cart['cart_num'], $seckill_id, isset($cart['productInfo']['attrInfo']) ? $cart['productInfo']['attrInfo']['unique'] : '');
                else if ($bargain_id) $res5 = $res5 && StoreBargain::decBargainStock($cart['cart_num'], $bargain_id, isset($cart['productInfo']['attrInfo']) ? $cart['productInfo']['attrInfo']['unique'] : '');
                else $res5 = $res5 && StoreProduct::decProductStock($cart['cart_num'], $cart['productInfo']['id'], isset($cart['productInfo']['attrInfo']) ? $cart['productInfo']['attrInfo']['unique'] : '');
            }
            //保存购物车商品信息
            $res4 = false !== StoreOrderCartInfo::setCartInfo($order['id'], $cartInfo);
            //购物车状态修改
            $res6 = false !== StoreCart::where('id', 'IN', $cartIds)->update(['is_pay' => 1]);
            if (!$res4 || !$res5 || !$res6) {
                return self::setErrorInfo('订单生成失败!', true);}
            //自动设置默认地址
            UserRepository::storeProductOrderCreateEbApi($order, compact('cartInfo', 'addressId'));
            self::clearCacheOrderInfo($uid, $key);
            self::commitTrans();
            StoreOrderStatus::status($order['id'], 'cache_key_create_order', '订单生成');
            return $order;
        } catch (\PDOException $e) {
            self::rollbackTrans();
            return self::setErrorInfo('生成订单时SQL执行错误错误原因：' . $e->getMessage());
        } catch (\Exception $e) {
            self::rollbackTrans();
            return self::setErrorInfo('生成订单时系统错误错误原因：' . $e->getMessage());
        }
    }


    /**
     * 回退积分
     * @param $order 订单信息
     * @return bool
     */
    public static function RegressionIntegral($order)
    {
        if ($order['paid'] || $order['status'] == -2 || $order['is_del']) return true;
        if ($order['use_integral'] <= 0) return true;
        if ((int)$order['status'] != -2 && (int)$order['refund_status'] != 2 && $order['back_integral'] >= $order['use_integral']) return true;
        $res = User::bcInc($order['uid'], 'integral', $order['use_integral']);
        if (!$res) return self::setErrorInfo('回退积分增加失败');
        UserBill::income('积分回退', $order['uid'], 'integral', 'deduction', $order['use_integral'], $order['unique'], User::where('uid', $order['uid'])->value('integral'), '购买商品失败,回退积分' . floatval($order['use_integral']));
        return false !== self::where('order_id', $order['order_id'])->update(['back_integral' => $order['use_integral']]);
    }


    /**
     * 回退库存和销量
     * @param $order 订单信息
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function RegressionStock($order)
    {
        if ($order['paid'] || $order['status'] == -2 || $order['is_del']) return true;
        $combinationId = $order['combination_id'];
        $seckill_id = $order['seckill_id'];
        $bargain_id = $order['bargain_id'];
        $res5 = true;
        $cartInfo = StoreOrderCartInfo::where('cart_id', 'in', $order['cart_id'])->select();
        foreach ($cartInfo as $cart) {
            //增库存减销量
            if ($combinationId) $res5 = $res5 && StoreCombination::incCombinationStock($cart['cart_info']['cart_num'], $combinationId);
            else if ($seckill_id) $res5 = $res5 && StoreSeckill::incSeckillStock($cart['cart_info']['cart_num'], $seckill_id);
            else if ($bargain_id) $res5 = $res5 && StoreBargain::incBargainStock($cart['cart_info']['cart_num'], $bargain_id);
            else $res5 = $res5 && StoreProduct::incProductStock($cart['cart_info']['cart_num'], $cart['cart_info']['productInfo']['id'], isset($cart['cart_info']['productInfo']['attrInfo']) ? $cart['cart_info']['productInfo']['attrInfo']['unique'] : '');
        }
        return $res5;
    }

    /**
     * 回退优惠卷
     * @param $order 订单信息
     * @return bool
     */
    public static function RegressionCoupon($order)
    {
        if ($order['paid'] || $order['status'] == -2 || $order['is_del']) return true;
        $res = true;
        if ($order['coupon_id'] && StoreCouponUser::be(['id' => $order['coupon_id'], 'uid' => $order['uid'], 'status' => 1])) {
            $res = $res && false !== StoreCouponUser::where('id', $order['coupon_id'])->where('uid', $order['uid'])->update(['status' => 0, 'use_time' => 0]);
        }
        return $res;
    }

    /**
     * 取消订单
     * @param string order_id 订单id
     * @param $uid
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function cancelOrder($order_id, $uid)
    {
        $order = self::where('order_id', $order_id)->where('uid', $uid)->find();
        if (!$order) return self::setErrorInfo('没有查到此订单');
        self::beginTrans();
        try {
            $res = self::RegressionIntegral($order) && self::RegressionStock($order) && self::RegressionCoupon($order);
            $order->is_del = 1;
            if ($res && $order->save()) {
                self::commitTrans();
                return true;
            } else
                return false;
        } catch (\Exception $e) {
            self::rollbackTrans();
            return self::setErrorInfo(['line' => $e->getLine(), 'message' => $e->getMessage()]);
        }
    }
    
    
    public static function updatePay($order_id, $pay_price, $pay_paypoint,$pay_repeatpoint,$give_rate,$give_point,$pay_point,$coupon_price)
    {
        $orderInfo = self::where('order_id', $order_id)->find();
        $orderInfo->pay_price = $pay_price;
        $orderInfo->pay_paypoint = $pay_paypoint;
        $orderInfo->pay_repeatpoint = $pay_repeatpoint;
        $orderInfo->give_rate = $give_rate;
        $orderInfo->give_point = $give_point;
        $orderInfo->pay_point = $pay_point;
        $orderInfo->coupon_price = $coupon_price;
        return $orderInfo->save();
    }

    /**
     * 生成订单唯一id
     * @param $uid 用户uid
     * @return string
     */
    public static function getNewOrderId()
    {
        list($msec, $sec) = explode(' ', microtime());
        $msectime = number_format((floatval($msec) + floatval($sec)) * 1000, 0, '', '');
        $orderId = 'wx' . $msectime . mt_rand(10000, 99999);
        if (self::be(['order_id' => $orderId])) $orderId = 'wx' . $msectime . mt_rand(10000, 99999);
        return $orderId;
    }

    /**
     * 修改订单号
     * @param $orderId
     * @return string
     */
    public static function changeOrderId($orderId)
    {
        $ymd = substr($orderId, 2, 8);
        $key = substr($orderId, 16);
        return 'wx' . $ymd . date('His') . $key;
    }

    /**
     * 查找购物车里的所有产品标题
     * @param $cartId 购物车id
     * @return bool|string
     */
    public static function getProductTitle($cartId)
    {
        $title = '';
        try {
            $orderCart = StoreOrderCartInfo::where('cart_id', 'in', $cartId)->field('cart_info')->select();
            foreach ($orderCart as $item) {
                if (isset($item['cart_info']['productInfo']['store_name'])) {
                    $title .= $item['cart_info']['productInfo']['store_name'] . '|';
                }
            }
            unset($item);
            if (!$title) {
                $productIds = StoreCart::where('id', 'in', $cartId)->column('product_id');
                $productlist = ($productlist = StoreProduct::getProductField($productIds, 'store_name')) ? $productlist->toArray() : [];
                foreach ($productlist as $item) {
                    if (isset($item['store_name'])) $title .= $item['store_name'] . '|';
                }
            }
            if ($title) $title = substr($title, 0, strlen($title) - 1);
            unset($item);
        } catch (\Exception $e) {
        }
        return $title;
    }

    /**
     * 获取门店自提唯一核销码
     * @return bool|string
     */
    public static function getStoreCode()
    {
        list($msec, $sec) = explode(' ', microtime());
        $num = bcadd(time(), mt_rand(10, 999999), 0) . '' . substr($msec, 2, 3);//生成随机数
        if (strlen($num) < 12)
            $num = str_pad((string)$num, 12, 0, STR_PAD_RIGHT);
        else
            $num = substr($num, 0, 12);
        if (self::be(['verify_code' => $num])) return self::getStoreCode();
        return $num;
    }

    
    /**
     * //TODO 支付成功后
     * @param $orderId
     * @param $paytype
     * @param $notify
     * @return bool
     */
    public static function paySuccess($orderId, $paytype = 'weixin', $formId = '')
    {
        $order = self::where('order_id', $orderId)->find();
        $uid = $order['uid'];
        $give_point = 0;
        $pay_point = 0;
        $repeat_point = 0;
        //判断支付消费积分
        $res1=true;
        if($res1&&$order['pay_paypoint']>0){
            $res1 = false !== User::bcDec($uid, 'pay_point', $order['pay_paypoint'], 'uid');
            $pay_point = -$order['pay_paypoint'];
        }
        //判断支付购物积分
        if($res1&&$order['give_rate']>0){
            $res1 = false !== User::bcDec($uid, 'give_point', $order['give_rate'], 'uid');
            $give_point = -$order['give_rate'];
        }
        //判断支付重消积分
        if($res1&&$order['pay_repeatpoint']>0){
            $res1 = false !== User::bcDec($uid, 'repeat_point', $order['pay_repeatpoint'], 'uid');
            $repeat_point = -$order['pay_repeatpoint'];
        }
        if($res1&&($give_point!=0||$pay_point!=0||$repeat_point!=0)){
            $res1 = StorePayLog::expend($uid, $order['id'], 1, 0, 0, $give_point, $pay_point,$repeat_point,0, '购买商品抵扣');
        }
        
        //赠送购物积分
        if($res1&&$order['give_point']>0){
            $res1 = false !== User::bcInc($uid, 'give_point', $order['give_point'], 'uid');
        }
        //赠送消费积分
        if($res1&&$order['pay_point']>0){
            $res1 = false !== User::bcInc($uid, 'pay_point', $order['pay_point'], 'uid');
        }
         
        if($res1&&($order['give_point']>0||$order['pay_point']>0)){
            $res1 = StorePayLog::expend($uid, $order['id'], 1,0, 0, $order['give_point'], $order['pay_point'],0,0, '购买商品赠送');
        }
      
        if($res1){//订单拆分及奖励提成核算
            $res1 = self::orderSplit($order['id']);
        }
        
        if($order['coupon_price']>0){//如果使用了抵扣券
            $coupon_price = $order['coupon_price'];
            $couponList = GoodsCouponUser::getCouponList($uid,0);
            foreach ($couponList as $coupon){
                $amount = bcsub($coupon['coupon_price'],$coupon['hamount'],2);
                if($amount>0&&$amount>$coupon_price&&$coupon_price>0){//该次抵扣券足够抵扣
                    //更改已使用金额
                    $pamount = bcadd($coupon['hamount'],$coupon_price,2);
                    GoodsCouponUser::where('id',$coupon['id'])->update(['hamount' => $pamount]);
                    //写入抵扣记录
                    $couponUse = [
                        'cid' => $coupon['id'],
                        'order_id' => $order['order_id'],
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
                        'order_id' => $order['order_id'],
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
        }
        $resPink = true;
        $res1 = self::where('order_id', $orderId)->update(['paid' => 1, 'pay_type' => $paytype, 'pay_time' => time()]);//订单改为支付
        //if ($order->combination_id && $res1 && !$order->refund_status) $resPink = StorePink::createPink($order);//创建拼团
        $oid = self::where('order_id', $orderId)->value('id');
        StoreOrderStatus::status($oid, 'pay_success', '用户付款成功');
        //支付成功后
        event('OrderPaySuccess', [$order, $formId]);
        $res = $res1 && $resPink;
        return false !== $res;
    }
    /**
     * 退消费积分
     * @param  $order
     * @return boolean
     */
    public static function BackPoint($order,$sid)
    {

        $give_point = $order['give_point'];
        $pay_point = $order['pay_point'];
        $pay_repeatpoint = $order['pay_repeatpoint'];
        //消费者UID
        $uid = $order['uid'];
        //返还消费积分
        $res1=true;
        if($res1&&$order['pay_paypoint']>0){
            $res1 = false !== User::bcInc($uid, 'pay_point', $order['pay_paypoint'], 'uid');
            $pay_point = -$order['pay_paypoint'];

        }
        //返还购物积分
        if($res1&&$order['give_rate']>0){
            $res1 = false !== User::bcInc($uid, 'give_point', $order['give_rate'], 'uid');
            $give_point = -$order['give_rate'];
        }
        //返还重消积分
        if($res1&&$order['pay_repeatpoint']>0){
            $res1 = false !== User::bcInc($uid, 'repeat_point', $order['pay_repeatpoint'], 'uid');
            $repeat_point = -$order['pay_repeatpoint'];
        }
       
        
        //购物积分系统收回
        if($res1&&$order['give_point']>0){
            $res1 = false !== User::bcDec($uid, 'give_point', $order['give_point'], 'uid');
        }
        //消费积分系统收回
        if($res1&&$order['pay_point']>0){
            $res1 = false !== User::bcDec($uid, 'pay_point', $order['pay_point'], 'uid');
        }

        //回退优惠券
        self::RegressionCoupon($order);
        //回退抵扣券
        if($order['coupon_price']>0)
        {
            $coupon_price = $order['coupon_price'];
            //查询抵扣券ID
            $coupon_info = GoodsCouponUse::where('order_id',$order['order_id'])->find();
            $cid = $coupon_info['cid'];
            $coupon_user_info = GoodsCouponUser::where('id','=',$cid)->find();
            $pamount = bcsub($coupon_user_info['hamount'],$coupon_price,2);
            GoodsCouponUser::where('id',$cid)->update(['hamount' => $pamount]);
        }


        //货款
        
        $store_id = $order['store_id'];
        $system_store_info = Db::name('system_store')->where('id','=',$store_id)->find();
        $user_id = $system_store_info['user_id'];
        User::bcDec($user_id, 'huokuan', $order['total_price']*0.8, 'uid');
        //Db::name('user')->where('uid','=',$user_id)->dec('huokuan',$order['total_price']*0.8)->update();
        //推荐人余额
        $config_info = Db::name('data_config')->where('id','=',1)->find();
        $first_rec = $config_info['rec_f'];
        $second_rec = $config_info['rec_s'];
        $shop_rec = $config_info['shop_rec'];
        $first_user = Db::name('user')->where('uid',$uid)->find();
        $first_uid = $first_user['spread_uid'];
        $second_user = Db::name('user')->where('uid',$first_uid)->find();
        $second_uid = $second_user['spread_uid'];
        
        $now_money = Db::name('user')->where('uid','=',$first_uid)->find();
        User::bcDec($first_uid, 'now_money', sprintf("%.2f",substr(sprintf("%.3f", $order['pay_price']*0.8*0.1*$first_rec/100), 0, -1)), 'uid');
        User::bcDec($second_uid, 'now_money', sprintf("%.2f",substr(sprintf("%.3f", $order['pay_price']*0.8*0.1*$second_rec/100), 0, -1)), 'uid');
        //推荐人重消积分
        User::bcDec($first_uid, 'repeat_point', sprintf("%.2f",substr(sprintf("%.3f", $order['pay_price']*0.1*0.1*$first_rec/100), 0, -1)), 'uid');
        User::bcDec($second_uid, 'repeat_point', sprintf("%.2f",substr(sprintf("%.3f", $order['pay_price']*0.1*0.1*$second_rec/100), 0, -1)), 'uid');
        //商家推荐人余额
        $shop_parent_id = $system_store_info['parent_id'];
        User::bcDec($shop_parent_id, 'now_money', sprintf("%.2f",substr(sprintf("%.3f", $order['pay_price']*0.8*0.1*$shop_rec/100), 0, -1)), 'uid');
        User::bcDec($shop_parent_id, 'repeat_point', sprintf("%.2f",substr(sprintf("%.3f", $order['pay_price']*0.1*0.1*$shop_rec/100), 0, -1)), 'uid');
        

        $huokuan = $order['total_price']*0.8;
        $use_money = $order['pay_price'];
        //消费者余额，消费积分记录
        if($order['pay_type']=='yue')
        {
            if($res1&&($order['give_point']>0||$order['pay_point']>0||$order['pay_point']>0)){
                $res1 = StorePayLog::expend($uid, $order['id'], 1,$use_money, 0, 0, 0,0,0, '商品退款');
            }
        }
        if($res1&&($order['give_point']>0||$order['pay_point']>0||$order['pay_point']>0)){
            $res1 = StorePayLog::expend($uid, $order['id'], 1,0, 0, '-'.$order['give_point'], '-'.$order['pay_point'],0,0, '商品退款');
        }
        //商家推荐人记录
        $res1 = StorePayLog::expend($shop_parent_id, $order['id'], 1,'-'.sprintf("%.2f",substr(sprintf("%.3f", $order['pay_price']*0.8*0.1*$shop_rec/100), 0, -1)), 0, 0, 0,'-'.sprintf("%.2f",substr(sprintf("%.3f", $order['pay_price']*0.1*0.1*$shop_rec/100), 0, -1)),0, '商品退款');
        //一代推荐人记录
        $res1 = StorePayLog::expend($first_uid, $order['id'], 1,'-'.sprintf("%.2f",substr(sprintf("%.3f", $order['pay_price']*0.8*0.1*$shop_rec/100), 0, -1)), 0, 0, 0,'-'.sprintf("%.2f",substr(sprintf("%.3f", $order['pay_price']*0.1*0.1*$first_rec/100), 0, -1)),0, '商品退款');
        //二代推荐人记录
        $res1 = StorePayLog::expend($second_uid, $order['id'], 1,'-'.sprintf("%.2f",substr(sprintf("%.3f", $order['pay_price']*0.8*0.1*$second_rec/100), 0, -1)), 0, 0, 0,'-'.sprintf("%.2f",substr(sprintf("%.3f", $order['pay_price']*0.1*0.1*$second_rec/100), 0, -1)),0, '商品退款');
        //商家退款
        $res1 = StorePayLog::expend($user_id, $order['id'], 1,0, '-'.$order['total_price']*0.8, 0, 0,0,0, '商品退款');
        
        
        return $res1;
        
        
        
        
    }

    
    // 订单拆分 支付成功以后订单拆分
    // 拆分规则  1 单个商品的不拆分  2 到店核销的商品每个一单 3 供应商不同订单不同
    
    public static function orderSplit($id){
        $group = [];
        $order =  self::where('id', $id)->find()->toArray();
        $cartIds =$order['cart_id'];
        $cartInfo = StoreOrderCartInfo::getProductListByOid($order['id']);
        $productIds = [];
        $productMap = [];
        foreach ($cartInfo as $value){
            $productIds[] = $value['product_id'];
            $value['cartInfo'] = $value['cart_info'];
            $productMap[$value['product_id']] = $value;
        }
        
        $productList = StoreProduct::getListByIds($productIds);
        //获取平台费率参数
        $feeRate = DataConfig::where('id', 1)->find();
        //短信发送开关
        $sms_open = $feeRate['sms_open'];
        //利润表数据
        $flag=0;$total_amount=0;$pay_amount=0;$huokuan=0;$pointer=0;$coupon_amount=0;$shopaward=0;$faward=0;$saward=0;$fagent=0;$sagent=0;$fprerent=0;$sprerent=0;$out_amount=0;$feet=0;$profit=0;
        $total_amount = $order['total_price'];
        $pay_amount = $order['pay_price'];
        // 单个商品订单不拆分
        self::beginTrans();
        if (count($cartIds) <= 1){
            $cart = $productMap[$productIds[0]];
            $product = $productList[0];
            $carinfo = StoreCart::where('id',$cart['cartInfo']['id'])->find();
            $storeInfo = SystemStore::where('id',$product['store_id'])->find();
            self::where('id', $id)->update(['store_id' => $product['store_id'],]);//订单改为支付
            //给商家结算货款
            $res = true;
            if($carinfo['huokuan']>0&&$storeInfo['user_id']>0){
                $res = false !== User::bcInc($storeInfo['user_id'], 'huokuan', $carinfo['huokuan'], 'uid');
                if($res){
                    $huokuan = $carinfo['huokuan'];
                    $res = StorePayLog::expend($storeInfo['user_id'], $order['id'], 1, 0, $carinfo['huokuan'], 0, 0,0,0, '商品订单结算');
                }
                //给商家发送支付成功提醒
                WechatTemplateService::sendTemplate(WechatUser::where('uid', $storeInfo['user_id'])->value('openid'), WechatTemplateService::ORDERTIPS_SUCCESS, [
                    'first' => '尊敬的商家您好，您的店铺刚收到一笔新订单',
                    'keyword1' => $order['order_id'],
                    'keyword2' => $product['store_name'],
                    'keyword3' => $carinfo['huokuan'],
                    'keyword4' => $storeInfo['mer_name'],
                    'keyword5' => '客户下单成功，若所售产品为邮寄商品，请及时发货！',
                    'remark' => '点击查看订单'
                ], Url::buildUrl('/merchant/home')->suffix('')->domain(true)->build());
                if($sms_open>0){
                    $data['code'] = '1';
                    $content = "尊敬的商户您好，您的店铺刚收到一笔新订单，订单金额：".$carinfo['huokuan']."元，若所售产品为邮寄商品，请及时发货！";
                    ShortLetterRepositories::send(true, $storeInfo['link_phone'], $data,$content);
                }
            }
            //计算商家推荐人提成
            $profit = $carinfo['profit'];
            $out_amount = $carinfo['profit'];
            $uinfo = User::where('uid',$storeInfo['parent_id'])->find();
            if($carinfo['profit']>0&&$storeInfo['parent_id']>0&&$feeRate['shop_rec']>0){
                $use_amount = $carinfo['profit']*$feeRate['shop_rec']/100;
                $fee = $use_amount*$feeRate['fee_rate']/100;
                $repeat_point = $use_amount*$feeRate['repeat_rate']/100;
                $use_amount = $use_amount - $fee - $repeat_point;
                if($res){
                    $res = false !== User::bcInc($uinfo['uid'], 'now_money', $use_amount, 'uid');
                    $shopaward = $use_amount;
                    $profit = $profit-$use_amount-$repeat_point;
                    $feet +=$fee;
                }
                if($res){
                    $res = false !== User::bcInc($uinfo['uid'], 'repeat_point', $repeat_point, 'uid');
                }
                if($res&&$use_amount>0){
                    $res = StorePayLog::expend($uinfo['uid'], $id, 1, $use_amount, 0, 0, 0,$repeat_point,$fee, '商家推荐奖励');
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
            }
           
            if($product['hex_t']==1){//
                $verify_code = self::getStoreCode();//生成订单核销码
                self::where('id', $id)->update(['verify_code' => $verify_code,]);//订单改为支付
            }
            //计算省市区代理提成
            $district='';
            if($order['shipping_type']==2){
                $address = $storeInfo['address'];
                $agent = explode(",",$address);
                $district = $agent[2];
            }else{
                $addressInfo = UserAddress::find($order['addressId']);
                $district = $addressInfo['district'];
            }
            $districtInfo = Db::name('system_city')->where('name', 'like', "%$district%")->find();//地区
            //查询区id
            $cityInfo = Db::name('system_city')->where('city_id', $districtInfo['parent_id'])->find();//城市
            $province = Db::name('system_city')->where('city_id', $cityInfo['parent_id'])->find();//省份
            //计算代理商总佣金
            $runamount = $carinfo['profit'];
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
                    if($res&&$districtAmount>0){
                        $sagent = $districtAmount;
                        $profit = $profit-$districtAmount-$repeat_point;
                        $feet +=$fee;
                        $res = false !== User::bcInc($districtInfo['agent_uid'], 'now_money', $districtAmount, 'uid');
                        $uinfo = User::getUserInfo($districtInfo['agent_uid']);
                        if($uinfo['phone']&&$sms_open>0){//代理商提成
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
                    if($res&&$districtAmount>0){
                        $res = false !== User::bcInc($districtInfo['agent_uid'], 'repeat_point', $repeat_point, 'uid');
                    }
                    if($res&&$districtAmount>0){
                        $res = StorePayLog::expend($districtInfo['agent_uid'], $order['id'], 0, $districtAmount, 0, 0, 0,$repeat_point,$fee, '地区代理商奖励');
                    }
                }
                if($cityInfo['agent_uid']>0){//城市代理佣金
                    $use_amount = $runamount*$feeRate['agent_city']/100-$districtAmount;
                    $fee = $use_amount*$feeRate['fee_rate']/100;
                    $repeat_point = $use_amount*$feeRate['repeat_rate']/100;
                    $cityAmount = $use_amount - $fee - $repeat_point;
                    if($res&&$cityAmount){
                        $fagent = $cityAmount;
                        $profit = $profit-$cityAmount-$repeat_point;
                        $feet +=$fee;
                        $res = false !== User::bcInc($cityInfo['agent_uid'], 'now_money', $cityAmount, 'uid');
                        $uinfo = User::getUserInfo($cityInfo['agent_uid']);
                        if($uinfo['phone']&&$sms_open>0){//代理商提成
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
                    if($res&&$cityAmount){
                        $res = false !== User::bcInc($cityInfo['agent_uid'], 'repeat_point', $repeat_point, 'uid');
                    }
                    if($res&&$cityAmount){
                        $res = StorePayLog::expend($cityInfo['agent_uid'], $order['id'], 0, $cityAmount, 0, 0, 0,$repeat_point,$fee, '城市代理商奖励');
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
                        if($uinfo['phone']&&$sms_open>0){//代理商提成
                            $data['code'] = '1';
                            $content = "尊敬的代理商您好，您的账户收到一笔代理商奖励，奖励金额：".$agentAmount."元！";
                            ShortLetterRepositories::send(true, $uinfo['phone'], $data,$content);
                        }
                        //给推荐人发送余额变动通知
                        $agentAmount = bcadd($cityAmount, 0, 2);
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
                        $res = StorePayLog::expend($province['agent_uid'], $order['id'], 0, $agentAmount, 0, 0, 0,$repeat_point,$fee, '省级代理商奖励');
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
                        $sprerent = $districtAmount;
                        $profit = $profit-$districtAmount-$repeat_point;
                        $feet +=$fee;
                        $res = false !== User::bcInc($districtInfo['inspect_uid'], 'now_money', $districtAmount, 'uid');
                        $uinfo = User::getUserInfo($districtInfo['inspect_uid']);
                        if($uinfo['phone']&&$sms_open>0){//总监提成
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
                        $res = StorePayLog::expend($districtInfo['inspect_uid'], $order['id'], 0, $districtAmount, 0, 0, 0,$repeat_point,$fee, '地区代理商奖励');
                    }
                }
                if($cityInfo['inspect_uid']>0){//城市总监佣金
                    $use_amount = $runamount*$feeRate['inspect_city']/100-$districtAmount;
                    $fee = $use_amount*$feeRate['fee_rate']/100;
                    $repeat_point = $use_amount*$feeRate['repeat_rate']/100;
                    $cityAmount = $use_amount - $fee - $repeat_point;
                    if($res&&$cityAmount){
                        $fprerent = $cityAmount;
                        $profit = $profit-$cityAmount-$repeat_point;
                        $feet +=$fee;
                        $res = false !== User::bcInc($cityInfo['inspect_uid'], 'now_money', $cityAmount, 'uid');
                        $uinfo = User::getUserInfo($cityInfo['inspect_uid']);
                        if($uinfo['phone']&&$sms_open>0){//总监提成
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
                        $res = StorePayLog::expend($cityInfo['inspect_uid'], $order['id'], 0, $cityAmount, 0, 0, 0,$repeat_point,$fee, '城市代理商奖励');
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
                        if($uinfo['phone']&&$sms_open>0){//总监提成
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
                        $res = StorePayLog::expend($province['inspect_uid'], $order['id'], 0, $agentAmount, 0, 0, 0,$repeat_point,$fee, '省级代理商奖励');
                    }
                }
            }
        }else{
            $order_index = 0;
            $addressId = $order['addressId'];
            if(!$addressId){
                $addressId = -1;
            }
            $addressInfo = UserAddress::find($addressId);
            $district = $addressInfo['district'];
            $runamount = 0;
           
            foreach ($productList as $product){
                $cart =  $productMap[$product['id']];
                $carinfo = StoreCart::where('id',$cart['cartInfo']['id'])->find();
                $cartOrderInfo = StoreOrderCartInfo::where('cart_id',$cart['cartInfo']['id'])->find();
                $runamount = bcadd($runamount, $carinfo['profit'], 2); 
                $verify_code='';
                $shipping_type=1;
                if ($product['hex_t'] == 1){
                    $verify_code = self::getStoreCode();
                    $shipping_type=2;
                }
                $order_index++;
                // 单独订单
                $total_price = $cart['cartInfo']['cart_num'] * $product['price'];
                $pay_price = $carinfo['payAmount'];
                //写入订单表
                $orderInfo = [
                    'order_id' => $order['order_id'].'_'.$order_index,
                    'cart_id' => '['.$cart['cart_id'].']',
                    'total_num' => $cart['cartInfo']['cart_num'],
                    'total_price' =>$total_price,
                    'pay_price' => $pay_price,
                    'pay_postage' => 0,
                    'freight_price' =>0,
                    'total_postage' => 0,
                    'mer_id' => $product['mer_id'],
                    'store_id' => (int)$product['store_id'],
                    'add_time' => time(),
                    'cost' => $cart['cartInfo']['cart_num']*$product['cost'],
                    'shipping_type' => $shipping_type,
                    'gain_integral' => 0,
                    'mark' => $order['mark'],
                    'unique'=> md5($order['unique'].'_'.$order_index),
                    'verify_code' => $verify_code,
            
            
                    // 公共复制部分
                    'pid' => $order['id'],
                    'uid' => $order['uid'],
                    'paid' =>1,
                    'pay_time' =>time(),
                    'addressId' =>$order['addressId'],
                    'pay_type' =>$order['pay_type'],
                    'real_name' =>$order['real_name'],
                    'user_phone' => $order['user_phone'],
                    'user_address' =>$order['user_address'],
                    'status' => $order['status'],
            
                ];
                $rs =  self::create($orderInfo);
                if ($rs['id'] <1){
                    self::rollback();
                    return ;
                }
               
                
                
                StoreOrderCartInfo::where('cart_id', $cart['cartInfo']['id'])->update(['oid' => $rs['id'], 'unique' => md5($cart['cartInfo']['id'].''.$rs['id'])]);
                //计算商家推荐人提成
                $storeInfo = SystemStore::where('id',$product['store_id'])->find();
            
                //给商家结算货款
                if($carinfo['huokuan']>0&&$storeInfo['user_id']>0){
                    $res = false !== User::bcInc($storeInfo['user_id'], 'huokuan', $carinfo['huokuan'], 'uid');
                    if($res){
                        $huokuan = $huokuan+$carinfo['huokuan'];
                        $res = StorePayLog::expend($storeInfo['user_id'], $rs['id'], 1, 0, $carinfo['huokuan'], 0, 0,0,0, '商品订单结算');
                    }
                    if($sms_open>0){
                        $data['code'] = '1';
                        $content = "尊敬的商户您好，您刚完成一笔交易，货款结算：".$carinfo['huokuan']."元！";
                        ShortLetterRepositories::send(true, $storeInfo['link_phone'], $data,$content);
                    }
                     //给商家发送支付成功提醒
                    WechatTemplateService::sendTemplate(WechatUser::where('uid', $storeInfo['user_id'])->value('openid'), WechatTemplateService::ORDERTIPS_SUCCESS, [
                        'first' => '尊敬的商家您好，您的店铺刚收到一笔新订单',
                        'keyword1' => $order['order_id'],
                        'keyword2' => $product['store_name'],
                        'keyword3' => $carinfo['huokuan'],
                        'keyword4' => $storeInfo['mer_name'],
                        'keyword5' => '客户下单成功，若所售产品为邮寄商品，请及时发货！',
                        'remark' => '点击查看订单'
                    ], Url::buildUrl('/merchant/home')->suffix('')->domain(true)->build());
                }
                //计算商家推荐人提成
                $uinfo = User::where('uid',$storeInfo['parent_id'])->find();
                if($carinfo['profit']>0&&$storeInfo['parent_id']>0&&$feeRate['shop_rec']>0){
                    $profit = $profit+$carinfo['profit'];
                    $out_amount = $out_amount+$carinfo['profit'];
                    $use_amount = $carinfo['profit']*$feeRate['shop_rec']/100;
                    $fee = $use_amount*$feeRate['fee_rate']/100;
                    $repeat_point = $use_amount*$feeRate['repeat_rate']/100;
                    $use_amount = $use_amount - $fee - $repeat_point;
                    if($res&&$use_amount){
                        $shopaward = $shopaward+$use_amount;
                        $profit = $profit-$use_amount-$repeat_point;
                        $feet +=$fee;
                        $res = false !== User::bcInc($uinfo['uid'], 'now_money', $use_amount, 'uid');
                    }
                    if($res){
                        $res = false !== User::bcInc($uinfo['uid'], 'repeat_point', $repeat_point, 'uid');
                    }
                    if($res){
                        $res = StorePayLog::expend($uinfo['uid'], $rs['id'], 1, $use_amount, 0, 0, 0,$repeat_point,$fee, '商家推荐奖励');
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
            }  
            
            //计算代理商提成
            $districtInfo = Db::name('system_city')->where('name', 'like', "%$district%")->find();//地区
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
                    if($res&&$districtAmount>0){
                        $sagent = $sagent+$districtAmount;
                        $profit = $profit-$districtAmount-$repeat_point;
                        $feet +=$fee;
                        $res = false !== User::bcInc($districtInfo['agent_uid'], 'now_money', $districtAmount, 'uid');
                        $uinfo = User::getUserInfo($districtInfo['agent_uid']);
                        if($uinfo['phone']&&$sms_open>0){//代理商提成
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
                    if($res&&$districtAmount>0){
                        $res = false !== User::bcInc($districtInfo['agent_uid'], 'repeat_point', $repeat_point, 'uid');
                    }
                    if($res&&$districtAmount>0){
                        $res = StorePayLog::expend($districtInfo['agent_uid'], $order['id'], 0, $districtAmount, 0, 0, 0,$repeat_point,$fee, '地区代理商奖励');
                    }
                }
                if($cityInfo['agent_uid']>0){//城市代理佣金
                    $use_amount = $runamount*$feeRate['agent_city']/100-$districtAmount;
                    $fee = $use_amount*$feeRate['fee_rate']/100;
                    $repeat_point = $use_amount*$feeRate['repeat_rate']/100;
                    $cityAmount = $use_amount - $fee - $repeat_point;
                    if($res&&$cityAmount){
                        $fagent = $fagent+$cityAmount;
                        $profit = $profit-$cityAmount-$repeat_point;
                        $feet +=$fee;
                        $res = false !== User::bcInc($cityInfo['agent_uid'], 'now_money', $cityAmount, 'uid');
                        $uinfo = User::getUserInfo($cityInfo['agent_uid']);
                        if($uinfo['phone']&&$sms_open>0){//代理商提成
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
                    if($res&&$cityAmount){
                        $res = false !== User::bcInc($cityInfo['agent_uid'], 'repeat_point', $repeat_point, 'uid');
                    }
                    if($res&&$cityAmount){
                        $res = StorePayLog::expend($cityInfo['agent_uid'], $order['id'], 0, $cityAmount, 0, 0, 0,$repeat_point,$fee, '城市代理商奖励');
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
                        if($uinfo['phone']&&$sms_open>0){//代理商提成
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
                        $res = StorePayLog::expend($province['agent_uid'], $order['id'], 0, $agentAmount, 0, 0, 0,$repeat_point,$fee, '省级代理商奖励');
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
                        $sprerent = $sprerent+$districtAmount;
                        $profit = $profit-$districtAmount-$repeat_point;
                        $feet +=$fee;
                        $res = false !== User::bcInc($districtInfo['inspect_uid'], 'now_money', $districtAmount, 'uid');
                        $uinfo = User::getUserInfo($districtInfo['inspect_uid']);
                        if($uinfo['phone']&&$sms_open>0){//总监奖励
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
                        $res = StorePayLog::expend($districtInfo['inspect_uid'], $order['id'], 0, $districtAmount, 0, 0, 0,$repeat_point,$fee, '地区代理商奖励');
                    }
                }
                if($cityInfo['inspect_uid']>0){//城市总监佣金
                    $use_amount = $runamount*$feeRate['inspect_city']/100-$districtAmount;
                    $fee = $use_amount*$feeRate['fee_rate']/100;
                    $repeat_point = $use_amount*$feeRate['repeat_rate']/100;
                    $cityAmount = $use_amount - $fee - $repeat_point;
                    if($res&&$cityAmount){
                        $fprerent = $fprerent+$cityAmount;
                        $profit = $profit-$cityAmount-$repeat_point;
                        $feet +=$fee;
                        $res = false !== User::bcInc($cityInfo['inspect_uid'], 'now_money', $cityAmount, 'uid');
                        $uinfo = User::getUserInfo($cityInfo['inspect_uid']);
                        if($uinfo['phone']&&$sms_open>0){//总监奖励
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
                        $res = StorePayLog::expend($cityInfo['inspect_uid'], $order['id'], 0, $cityAmount, 0, 0, 0,$repeat_point,$fee, '城市代理商奖励');
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
                        if($uinfo['phone']&&$sms_open>0){//总监奖励
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
                        $res = StorePayLog::expend($province['inspect_uid'], $order['id'], 0, $agentAmount, 0, 0, 0,$repeat_point,$fee, '省级代理商奖励');
                    }
                }
            }  
            // 标记父订单
            $res = self::where('id', $order['id'])
            ->update(['is_del' => 1, 'is_parent' => 1, 'is_system_del' => 1]);
        }
       
        //结算推荐人奖励
        $use_amount = 0;
        $fee=0;
        $repeat_point=0;
        //计算3代推荐奖励
        $userInfo = User::getUserInfo($order['uid']);
        $userInfo['add_time'] = User::where('uid',$order['uid'])->value('add_time');
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
                    if($use_amount>0){
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
                        if($res){
                            $res = StorePayLog::expend($spread_uid, $order['id'], 0, $use_amount, 0, 0, 0,$repeat_point,$fee, '分销奖励');
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
                            'remark' => '感谢您的支持'
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
        if($order['point_pay']>0){
           $pointer = $order['pay_paypoint']+$order['pay_repeatpoint']+$order['give_rate'];
        }
        
        if($order['coupon_pay']>0){
            $coupon_amount = $order['coupon_price'];
        }
        
        $data=[
            'idno' => $order['order_id'],
            'flag' => 1,
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
        
        //判断是否有赠送抵扣券
        foreach ($productList as $product){
            $cart =  $productMap[$product['id']];
            $carinfo = StoreCart::where('id',$cart['cartInfo']['id'])->find();
            if($product['coupon_id']>0){//赠送商品抵扣券
                $coupon = GoodsCouponModel::get($product['coupon_id'])->toArray();
                for($i=0;$i<$carinfo['cart_num'];$i++){
                   CouponUserModel::setGoodsCoupon($coupon, $order['uid']);
                } 
            }
            if($product['scoupon_id']>0){//赠送商品抵扣券
                $coupon = GoodsCouponModel::get($product['scoupon_id'])->toArray();
                for($i=0;$i<$carinfo['cart_num'];$i++){
                    CouponUserModel::setGoodsCoupon($coupon, $order['uid']);
                }
            }
        }
        
        $remark = '本次消费'.$order['total_price'].'元,实际支付'.$order['pay_price'].'元,';
        if($order['pay_point']>0){
            $remark = $remark.'消费赠送'.$order['pay_point'].'个消费积分,积分可抵'.$order['pay_point'].'元现金使用,';
        }
        $remark = $remark.'感谢您的支持';
       //给客户发送消费通知
        WechatTemplateService::sendTemplate(WechatUser::where('uid', $order['uid'])->value('openid'), WechatTemplateService::PAYORDER_SUCCESS, [
            'first' => '尊敬的客户您好，您在佰仟万平台完成了一笔交易',
            'keyword1' => '佰仟万平台购物',
            'keyword2' => $order['order_id'],
            'keyword3' => date('Y-m-d H:i:s', time()),
            'keyword4' => $order['pay_price'],
            'remark' => $remark
        ], Url::buildUrl('/order/list/1')->suffix('')->domain(true)->build());
        if (!$res) self::rollback();
        self::commit();
        return $res;
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
        
        $orderInfo = self::where('uid', $uid)->where('order_id', $order_id)->where('is_del', 0)->find();
        if (!$orderInfo) return self::setErrorInfo('订单不存在!');
        if ($orderInfo['paid']) return self::setErrorInfo('该订单已支付!');
//        if($orderInfo['pay_type'] != 'yue') return self::setErrorInfo('该订单不能使用余额支付!');
        $userInfo = User::getUserInfo($uid);
        if ($userInfo['now_money'] < $orderInfo['pay_price'])
            return self::setErrorInfo(['status' => 'pay_deficiency', 'msg' => '余额不足' . floatval($orderInfo['pay_price'])]);
        self::beginTrans();
        
        $use_money=0;
        $give_point = 0;
        $pay_point = 0;
        $repeat_point = 0;

        $res1 = true;
        if($orderInfo['pay_price']>0){
          $res1 = false !== User::bcDec($uid, 'now_money', $orderInfo['pay_price'], 'uid');
          $use_money = -$orderInfo['pay_price'];
        }
        if($res1&&$use_money!=0){
            $res1 = StorePayLog::expend($uid, $orderInfo['id'], 1,$use_money, 0, 0,0,0,0, '余额购买商品');
        }
        $res2 = self::paySuccess($order_id, 'yue', $formId);//余额支付成功
        try {
            PaymentRepositories::yuePayProduct($userInfo, $orderInfo);
        } catch (\Exception $e) {
            self::rollbackTrans();
            return self::setErrorInfo($e->getMessage());
        }
        $res = $res1 && $res2;
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
        $orderInfo = self::where('uid', $uid)->where('order_id', $order_id)->where('is_del', 0)->find();
        if (!$orderInfo) return self::setErrorInfo('订单不存在!');
        if ($orderInfo['paid']) return self::setErrorInfo('该订单已支付!');
        $userInfo = User::getUserInfo($uid);
        self::beginTrans();
        $res1 = UserBill::expend('购买商品', $uid, 'now_money', 'pay_product', $orderInfo['pay_price'], $orderInfo['id'], $userInfo['now_money'], '微信支付' . floatval($orderInfo['pay_price']) . '元购买商品');
        $res2 = self::paySuccess($order_id, 'weixin', $formId);//微信支付为0时
        $res = $res1 && $res2;
        self::checkTrans($res);
        return $res;
    }


    /**
     * 用户申请退款
     * @param $uni
     * @param $uid
     * @param string $refundReasonWap
     * @return bool
     */
    public static function orderApplyRefund($uni, $uid, $refundReasonWap = '', $refundReasonWapExplain = '', $refundReasonWapImg = [])
    {
        $order = self::getUserOrderDetail($uid, $uni);
        if (!$order) return self::setErrorInfo('支付订单不存在!');
        if ($order['refund_status'] == 2) return self::setErrorInfo('订单已退款!');
        if ($order['refund_status'] == 1) return self::setErrorInfo('正在申请退款中!');
        if ($order['status'] == 1) return self::setErrorInfo('订单当前无法退款!');
        self::beginTrans();
        $res1 = false !== StoreOrderStatus::status($order['id'], 'apply_refund', '用户申请退款，原因：' . $refundReasonWap);
        $res2 = false !== self::edit(['refund_status' => 1, 'refund_reason_time' => time(), 'refund_reason_wap' => $refundReasonWap, 'refund_reason_wap_explain' => $refundReasonWapExplain, 'refund_reason_wap_img' => json_encode($refundReasonWapImg)], $order['id'], 'id');
        $res = $res1 && $res2;
        self::checkTrans($res);
        if (!$res)
            return self::setErrorInfo('申请退款失败!');
        else {
            try {
                if (in_array($order['is_channel'], [0, 2])) {
                    //公众号发送模板消息通知客服
                    WechatTemplateService::sendAdminNoticeTemplate([
                        'first' => "亲,有个订单申请退款 \n订单号:{$order['order_id']}",
                        'keyword1' => '退款申请',
                        'keyword2' => '已支付',
                        'keyword3' => date('Y/m/d H:i', time()),
                        'remark' => '请及时处理'
                    ]);
                }
                if (in_array($order['is_channel'], [1, 2])) {
                    //小程序 发送模板消息
                    RoutineTemplate::sendOrderRefundStatus($order, $refundReasonWap);
                }
                //通知后台消息提醒
                ChannelService::instance()->send('NEW_REFUND_ORDER', ['order_id' => $order['order_id']]);
            } catch (\Exception $e) {
            }
            //发送短信
            event('ShortMssageSend', [$order['order_id'], 'AdminRefund']);
            return true;
        }
    }

    

    /*
     * 线下支付消息通知
     * 待完善
     *
     * */
    public static function createOrderTemplate($order)
    {

        //$goodsName = StoreOrderCartInfo::getProductNameList($order['id']);
//        RoutineTemplateService::sendTemplate(WechatUser::getOpenId($order['uid']),RoutineTemplateService::ORDER_CREATE, [
//            'first'=>'亲，您购买的商品已支付成功',
//            'keyword1'=>date('Y/m/d H:i',$order['add_time']),
//            'keyword2'=>implode(',',$goodsName),
//            'keyword3'=>$order['order_id'],
//            'remark'=>'点击查看订单详情'
//        ],Url::build('/wap/My/order',['uni'=>$order['order_id']],true,true));
//        RoutineTemplateService::sendAdminNoticeTemplate([
//            'first'=>"亲,您有一个新订单 \n订单号:{$order['order_id']}",
//            'keyword1'=>'新订单',
//            'keyword2'=>'线下支付',
//            'keyword3'=>date('Y/m/d H:i',time()),
//            'remark'=>'请及时处理'
//        ]);
    }

    /**
     * 获取订单详情
     * @param $uid
     * @param $key
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getUserOrderDetail($uid, $key)
    {
        return self::where('order_id|unique', $key)->where('uid', $uid)->find();
    }


    /**
     * TODO 订单发货
     * @param array $postageData 发货信息
     * @param string $oid orderID
     */
    public static function orderPostageAfter($postageData, $oid)
    {
        $order = self::where('id', $oid)->find();
        if ($postageData['delivery_type'] == 'send') {//送货
            RoutineTemplate::sendOrderPostage($order);
        } else if ($postageData['delivery_type'] == 'express') {//发货
            RoutineTemplate::sendOrderPostage($order, 1);
        }
    }

    /** 收货后发送模版消息
     * @param $order
     */
    public static function orderTakeAfter($order)
    {
        $title = self::getProductTitle($order['cart_id']);
        if ($order['is_channel'] == 1) {//小程序
            RoutineTemplate::sendOrderTakeOver($order, $title);
        } else {
            $openid = WechatUser::where('uid', $order['uid'])->value('openid');
            \crmeb\services\WechatTemplateService::sendTemplate($openid, \crmeb\services\WechatTemplateService::ORDER_TAKE_SUCCESS, [
                'first' => '亲，您的订单已收货',
                'keyword1' => $order['order_id'],
                'keyword2' => '已收货',
                'keyword3' => date('Y-m-d H:i:s', time()),
                'keyword4' => $title,
                'remark' => '感谢您的光临！'
            ]);
        }
    }

    /**
     * 删除订单
     * @param $uni
     * @param $uid
     * @return bool
     */
    public static function removeOrder($uni, $uid)
    {
        $order = self::getUserOrderDetail($uid, $uni);
        if (!$order) return self::setErrorInfo('订单不存在!');
        $order = self::tidyOrder($order);
        if ($order['_status']['_type'] != 0 && $order['_status']['_type'] != -2 && $order['_status']['_type'] != 4)
            return self::setErrorInfo('该订单无法删除!');
        if (false !== self::edit(['is_del' => 1], $order['id'], 'id') && false !== StoreOrderStatus::status($order['id'], 'remove_order', '删除订单')) {
            //未支付和已退款的状态下才可以退积分退库存退优惠券
            if ($order['_status']['_type'] == 0 || $order['_status']['_type'] == -2) {
                event('StoreOrderRegressionAllAfter', [$order]);
            }
            event('UserOrderRemoved', $uni);
            return true;
        } else
            return self::setErrorInfo('订单删除失败!');
    }


    /**
     * //TODO 用户确认收货
     * @param $uni
     * @param $uid
     */
    public static function takeOrder($uni, $uid)
    {
        $order = self::getUserOrderDetail($uid, $uni);
        if (!$order) return self::setErrorInfo('订单不存在!');
        $order = self::tidyOrder($order);
        if ($order['_status']['_type'] != 2) return self::setErrorInfo('订单状态错误!');
        self::beginTrans();
        if (false !== self::edit(['status' => 2], $order['id'], 'id') &&
            false !== StoreOrderStatus::status($order['id'], 'user_take_delivery', '用户已收货')) {
            try {
                OrderRepository::storeProductOrderUserTakeDelivery($order, $uid);
            } catch (\Exception $e) {
                self::rollbackTrans();
                return self::setErrorInfo($e->getMessage());
            }
            self::commitTrans();
            event('UserLevelAfter', [User::get($uni)]);
            event('UserOrderTake', $uni);
            //短信通知
            event('ShortMssageSend', [$order['order_id'], ['Receiving', 'AdminConfirmTakeOver']]);
            return true;
        } else {
            self::rollbackTrans();
            return false;
        }
    }

    /**
     * 获取订单状态购物车等信息
     * @param $order
     * @param bool $detail 是否获取订单购物车详情
     * @param bool $isPic 是否获取订单状态图片
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function tidyOrder($order, $detail = false, $isPic = false)
    {
        if ($detail == true && isset($order['id'])) {
            $cartInfo = StoreOrderCartInfo::where('oid', $order['id'])->column('cart_info', 'unique') ?: [];
            $info = [];
            foreach ($cartInfo as $k => $cart) {
                $cart = json_decode($cart, true);
                $cart['unique'] = $k;
                //新增是否评价字段
                $cart['is_reply'] = StoreProductReply::where('unique', $k)->count();
                array_push($info, $cart);
                unset($cart);
            }
            $order['cartInfo'] = $info;
        }
        $status = [];
        if (!$order['paid'] && $order['pay_type'] == 'offline' && !$order['status'] >= 2) {
            $status['_type'] = 9;
            $status['_title'] = '线下付款';
            $status['_msg'] = '商家处理中,请耐心等待';
            $status['_class'] = 'nobuy';
        } else if (!$order['paid']) {
            $status['_type'] = 0;
            $status['_title'] = '未支付';
            //系统预设取消订单时间段
            $keyValue = ['order_cancel_time', 'order_activity_time', 'order_bargain_time', 'order_seckill_time', 'order_pink_time'];
            //获取配置
            $systemValue = SystemConfigService::more($keyValue);
            //格式化数据
            $systemValue = self::setValeTime($keyValue, is_array($systemValue) ? $systemValue : []);
            if ($order['pink_id'] || $order['combination_id']) {
                $order_pink_time = $systemValue['order_pink_time'] ? $systemValue['order_pink_time'] : $systemValue['order_activity_time'];
                $time = bcadd($order['add_time'], $order_pink_time * 3600, 0);
                $status['_msg'] = '请在' . date('m-d H:i:s', $time) . '前完成支付!';
            } else if ($order['seckill_id']) {
                $order_seckill_time = $systemValue['order_seckill_time'] ? $systemValue['order_seckill_time'] : $systemValue['order_activity_time'];
                $time = bcadd($order['add_time'], $order_seckill_time * 3600, 0);
                $status['_msg'] = '请在' . date('m-d H:i:s', $time) . '前完成支付!';
            } else if ($order['bargain_id']) {
                $order_bargain_time = $systemValue['order_bargain_time'] ? $systemValue['order_bargain_time'] : $systemValue['order_activity_time'];
                $time = bcadd($order['add_time'], $order_bargain_time * 3600, 0);
                $status['_msg'] = '请在' . date('m-d H:i:s', $time) . '前完成支付!';
            } else {
                $time = bcadd($order['add_time'], $systemValue['order_cancel_time'] * 3600, 0);
                $status['_msg'] = '请在' . date('m-d H:i:s', $time) . '前完成支付!';
            }
            $status['_class'] = 'nobuy';
        } else if ($order['refund_status'] == 1) {
            $status['_type'] = -1;
            $status['_title'] = '申请退款中';
            $status['_msg'] = '商家审核中,请耐心等待';
            $status['_class'] = 'state-sqtk';
        } else if ($order['refund_status'] == 2) {
            $status['_type'] = -2;
            $status['_title'] = '已退款';
            $status['_msg'] = '已为您退款,感谢您的支持';
            $status['_class'] = 'state-sqtk';
        } else if (!$order['status']) {
            if ($order['pink_id']) {
                if (StorePink::where('id', $order['pink_id'])->where('status', 1)->count()) {
                    $status['_type'] = 1;
                    $status['_title'] = '拼团中';
                    $status['_msg'] = '等待其他人参加拼团';
                    $status['_class'] = 'state-nfh';
                } else {
                    $status['_type'] = 1;
                    $status['_title'] = '未发货';
                    $status['_msg'] = '商家未发货,请耐心等待';
                    $status['_class'] = 'state-nfh';
                }
            } else {
                if ($order['shipping_type'] === 1) {
                    $status['_type'] = 1;
                    $status['_title'] = '未发货';
                    $status['_msg'] = '商家未发货,请耐心等待';
                    $status['_class'] = 'state-nfh';
                } else {
                    $status['_type'] = 1;
                    $status['_title'] = '待核销';
                    $status['_msg'] = '待核销,请到核销点进行核销';
                    $status['_class'] = 'state-nfh';
                }
            }
        } else if ($order['status'] == 1) {
            if ($order['delivery_type'] == 'send') {//TODO 送货
                $status['_type'] = 2;
                $status['_title'] = '待收货';
                $status['_msg'] = date('m月d日H时i分', StoreOrderStatus::getTime($order['id'], 'delivery')) . '服务商已送货';
                $status['_class'] = 'state-ysh';
            } else {//TODO  发货
                $status['_type'] = 2;
                $status['_title'] = '待收货';
                if ($order['delivery_type'] == 'fictitious')
                    $_time = StoreOrderStatus::getTime($order['id'], 'delivery_fictitious');
                else
                    $_time = StoreOrderStatus::getTime($order['id'], 'delivery_goods');
                $status['_msg'] = date('m月d日H时i分', $_time) . '服务商已发货';
                $status['_class'] = 'state-ysh';
            }
        } else if ($order['status'] == 2) {
            $status['_type'] = 3;
            $status['_title'] = '待评价';
            $status['_msg'] = '已收货,快去评价一下吧';
            $status['_class'] = 'state-ypj';
        } else if ($order['status'] == 3) {
            $status['_type'] = 4;
            $status['_title'] = '交易完成';
            $status['_msg'] = '交易完成,感谢您的支持';
            $status['_class'] = 'state-ytk';
        }
        if (isset($order['pay_type']))
            $status['_payType'] = isset(self::$payType[$order['pay_type']]) ? self::$payType[$order['pay_type']] : '其他方式';
        if (isset($order['delivery_type']))
            $status['_deliveryType'] = isset(self::$deliveryType[$order['delivery_type']]) ? self::$deliveryType[$order['delivery_type']] : '其他方式';
        $order['_status'] = $status;
        $order['_pay_time'] = isset($order['pay_time']) && $order['pay_time'] != null ? date('Y-m-d H:i:s', $order['pay_time']) : date('Y-m-d H:i:s', $order['add_time']);
        $order['_add_time'] = isset($order['add_time']) ? (strstr($order['add_time'], '-') === false ? date('Y-m-d H:i:s', $order['add_time']) : $order['add_time']) : '';
        $order['status_pic'] = '';
        //获取产品状态图片
        if ($isPic) {
            $order_details_images = sys_data('order_details_images') ?: [];
            foreach ($order_details_images as $image) {
                if (isset($image['order_status']) && $image['order_status'] == $order['_status']['_type']) {
                    $order['status_pic'] = $image['pic'];
                    break;
                }
            }
        }
        $order['offlinePayStatus'] = (int)sys_config('offline_pay_status') ?? (int)2;
        return $order;
    }

    /**
     * 设置订单查询状态
     * @param $status
     * @param int $uid
     * @param null $model
     * @return StoreOrder|null
     */
    public static function statusByWhere($status, $uid = 0, $model = null)
    {
//        $orderId = StorePink::where('uid',$uid)->where('status',1)->column('order_id','id');//获取正在拼团的订单编号
        if ($model == null) $model = new self;
        if ('' === $status)
            return $model;
        else if ($status == 0)//未支付
            return $model->where('paid', 0)->where('status', 0)->where('refund_status', 0);
        else if ($status == 1)//待发货
            return $model->where('paid', 1)->where('status', 0)->where('refund_status', 0);
        else if ($status == 2)//待收货
            return $model->where('paid', 1)->where('status', 1)->where('refund_status', 0);
        else if ($status == 3)//待评价
            return $model->where('paid', 1)->where('status', 2)->where('refund_status', 0);
        else if ($status == 4)//已完成
            return $model->where('paid', 1)->where('status', 3)->where('refund_status', 0);
        else if ($status == -1)//退款中
            return $model->where('paid', 1)->where('refund_status', 1);
        else if ($status == -2)//已退款
            return $model->where('paid', 1)->where('refund_status', 2);
        else if ($status == -3)//退款
            return $model->where('paid', 1)->where('refund_status', 'IN', '1,2');
//        else if($status == 11){
//            return $model->where('order_id','IN',implode(',',$orderId));
//        }
        else
            return $model;
    }

    /**
     * 获取订单并分页
     * @param $uid
     * @param string $status
     * @param int $page
     * @param int $limit
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getUserOrderList($uid, $status = '', $page = 0, $limit = 8)
    {
        if ($page) $list = self::statusByWhere($status, $uid)->where('is_del', 0)->where('uid', $uid)
            ->field('add_time,seckill_id,bargain_id,combination_id,id,order_id,pay_price,total_num,total_price,pay_postage,total_postage,paid,status,refund_status,pay_type,coupon_price,deduction_price,pink_id,delivery_type,is_del,shipping_type')
            ->order('add_time DESC')->page((int)$page, (int)$limit)->select()->toArray();
        else  $list = self::statusByWhere($status, $uid)->where('is_del', 0)->where('uid', $uid)
            ->field('add_time,seckill_id,bargain_id,combination_id,id,order_id,pay_price,total_num,total_price,pay_postage,total_postage,paid,status,refund_status,pay_type,coupon_price,deduction_price,pink_id,delivery_type,is_del,shipping_type')
            ->order('add_time DESC')->page((int)$page, (int)$limit)->select()->toArray();
        foreach ($list as $k => $order) {
            $list[$k] = self::tidyOrder($order, true);
        }

        return $list;
    }

    /**
     * 获取推广人地下用户的订单金额
     * @param string $uid
     * @param string $status
     * @return array
     */
    public static function getUserOrderCount($uid = '', $status = '')
    {
        $res = self::statusByWhere($status, $uid)->where('uid', 'IN', $uid)->column('pay_price');
        return $res;
    }

    /**
     * 搜索某个订单详细信息
     * @param $uid
     * @param $order_id
     * @return bool|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function searchUserOrder($uid, $order_id)
    {
        $order = self::where('uid', $uid)->where('order_id', $order_id)->where('is_del', 0)->field('seckill_id,bargain_id,combination_id,id,order_id,pay_price,total_num,total_price,pay_postage,total_postage,paid,status,refund_status,pay_type,coupon_price,deduction_price,delivery_type,shipping_type')
            ->order('add_time DESC')->find();
        if (!$order)
            return false;
        else
            return self::tidyOrder($order->toArray(), true);

    }

    /**
     * 订单评价信息记录
     * @param $oid
     * @return StoreOrderStatus|\think\Model
     * @throws \Exception
     */
    public static function orderOver($oid)
    {
        $res = self::edit(['status' => '3'], $oid, 'id');
        if (!$res) exception('评价后置操作失败!');
        return StoreOrderStatus::status($oid, 'check_order_over', '用户评价');
    }

    /**
     * 设置订单产品评价完毕事件
     * @param $oid
     * @return StoreOrderStatus|\think\Model
     * @throws \Exception
     */
    public static function checkOrderOver($oid)
    {
        $uniqueList = StoreOrderCartInfo::where('oid', $oid)->column('unique', 'unique');
        //订单产品全部评价完成
        if (StoreProductReply::where('unique', 'IN', $uniqueList)->where('oid', $oid)->count() == count($uniqueList)) {
            event('StoreProductOrderOver', [$oid]);
            return self::orderOver($oid);
        }
    }


    public static function getOrderStatusNum($uid)
    {
        $noBuy = (int)self::where('uid', $uid)->where('paid', 0)->where('is_del', 0)->where('pay_type', '<>', 'offline')->count();
        $noPostageNoPink = (int)self::where('o.uid', $uid)->alias('o')->where('o.paid', 1)->where('o.pink_id', 0)->where('o.is_del', 0)->where('o.status', 0)->where('o.pay_type', '<>', 'offline')->count();
        $noPostageYesPink = (int)self::where('o.uid', $uid)->alias('o')->join('StorePink p', 'o.pink_id = p.id')->where('p.status', 2)->where('o.paid', 1)->where('o.is_del', 0)->where('o.status', 0)->where('o.pay_type', '<>', 'offline')->count();
        $noPostage = (int)bcadd($noPostageNoPink, $noPostageYesPink, 0);
        $noTake = (int)self::where('uid', $uid)->where('paid', 1)->where('is_del', 0)->where('status', 1)->where('pay_type', '<>', 'offline')->count();
        $noReply = (int)self::where('uid', $uid)->where('paid', 1)->where('is_del', 0)->where('status', 2)->count();
        $noPink = (int)self::where('o.uid', $uid)->alias('o')->join('StorePink p', 'o.pink_id = p.id')->where('p.status', 1)->where('o.paid', 1)->where('o.is_del', 0)->where('o.status', 0)->where('o.pay_type', '<>', 'offline')->count();
        $noRefund = (int)self::where('uid', $uid)->where('paid', 1)->where('is_del', 0)->where('refund_status', 'IN', '1,2')->count();
        return compact('noBuy', 'noPostage', 'noTake', 'noReply', 'noPink', 'noRefund');
    }

    /**
     * 购买商品赠送积分
     * @param $order
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function gainUserIntegral($order)
    {
        if ($order['gain_integral'] > 0) {
            $userInfo = User::getUserInfo($order['uid']);
            BaseModel::beginTrans();
            $res1 = false != User::where('uid', $userInfo['uid'])->update(['integral' => bcadd($userInfo['integral'], $order['gain_integral'], 2)]);
            $res2 = false != UserBill::income('购买商品赠送积分', $order['uid'], 'integral', 'gain', $order['gain_integral'], $order['id'], $userInfo['integral'], '购买商品赠送' . floatval($order['gain_integral']) . '积分');
            $res = $res1 && $res2;
            BaseModel::checkTrans($res);
            return $res;
        }
        return true;
    }

    /**
     * 获取当前订单中有没有拼团存在
     * @param $pid
     * @return int|string
     */
    public static function getIsOrderPink($pid = 0, $uid = 0)
    {
        return self::where('uid', $uid)->where('pink_id', $pid)->where('refund_status', 0)->where('is_del', 0)->count();
    }

    /**
     * 获取order_id
     * @param $pid
     * @return mixed
     */
    public static function getStoreIdPink($pid = 0, $uid = 0)
    {
        return self::where('uid', $uid)->where('pink_id', $pid)->where('is_del', 0)->value('order_id');
    }

    /**
     * 删除当前用户拼团未支付的订单
     */
    public static function delCombination()
    {
        self::where('combination', '>', 0)->where('paid', 0)->where('uid', User::getActiveUid())->delete();
    }

    public static function getUserPrice($uid = 0)
    {
        if (!$uid) return 0;
        $price = self::where('paid', 1)->where('uid', $uid)->where('status', 2)->where('refund_status', 0)->column('pay_price', 'id');
        $count = 0;
        if ($price) {
            foreach ($price as $v) {
                $count = bcadd($count, $v, 2);
            }
        }
        return $count;
    }


    /**
     * 个人中心获取个人订单列表和订单搜索
     * @param int $uid 用户uid
     * @param int | string 查找订单类型
     * @param int $first 分页
     * @param int 每页显示多少条
     * @param string $search 订单号
     * @return array
     * */
    public static function getUserOrderSearchList($uid, $type, $page, $limit, $search)
    {
        if ($search) {
            $order = self::searchUserOrder($uid, $search) ?: [];
            $list = $order == false ? [] : [$order];
        } else {
            $list = self::getUserOrderList($uid, $type, $page, $limit);
        }
        foreach ($list as $k => $order) {
            $list[$k] = self::tidyOrder($order, true);
            if ($list[$k]['_status']['_type'] == 3) {
                foreach ($order['cartInfo'] ?: [] as $key => $product) {
                    $list[$k]['cartInfo'][$key]['is_reply'] = StoreProductReply::isReply($product['unique'], 'product');
                    $list[$k]['cartInfo'][$key]['add_time'] = isset($product['add_time']) ? date('Y-m-d H:i', $product['add_time']) : '时间错误';
                }
            }
        }
        return $list;
    }

    /**
     * 获取用户下级的订单
     * @param int $xuid 下级用户用户uid
     * @param int $uid 用户uid
     * @param int $type 订单类型
     * @param int $first 截取行数
     * @param int $limit 展示条数
     * @return array
     * */
    public static function getSubordinateOrderlist($xUid, $uid, $type, $first, $limit)
    {
        $list = [];
        if (!$xUid) {
            $arr = User::getOneSpreadUid($uid);
            foreach ($arr as $v) $list = StoreOrder::getUserOrderList($v, $type, $first, $limit);
        } else $list = self::getUserOrderList($xUid, $type, $first, $limit);
        foreach ($list as $k => $order) {
            $list[$k] = self::tidyOrder($order, true);
            if ($list[$k]['_status']['_type'] == 3) {
                foreach ($order['cartInfo'] ?: [] as $key => $product) {
                    $list[$k]['cartInfo'][$key]['is_reply'] = StoreProductReply::isReply($product['unique'], 'product');
                }
            }
        }
        return $list;
    }

    /**
     * 获取 今日 昨日 本月 订单金额
     * @return mixed
     */
    public static function getOrderTimeData($uid)
    {
        $to_day = strtotime(date('Y-m-d'));//今日
        $pre_day = strtotime(date('Y-m-d', strtotime('-1 day')));//昨日
        $now_month = strtotime(date('Y-m'));//本月
        $merList =   StoreService::getAdminMerList($uid);
        //今日成交额
//        $data['todayPrice'] = (float)number_format(self::where('is_del', 0)->where('pay_time', '>=', $to_day)->where('paid', 1)->where('refund_status', 0)->value('sum(pay_price)'), 2) ?? 0;
        $data['todayPrice'] = number_format(self::where('is_del', 0)->where('pay_time', '>=', $to_day)->where('paid', 1) ->where('store_id','in', $merList)->where('refund_status', 0)->value('sum(pay_price)'), 2) ?? 0;
        //今日订单数
        $data['todayCount'] = self::where('is_del', 0)->where('pay_time', '>=', $to_day)->where('paid', 1) ->where('store_id','in', $merList)->where('refund_status', 0)->count();
        //昨日成交额
        $data['proPrice'] = number_format(self::where('is_del', 0)->where('pay_time', '<', $to_day)->where('pay_time', '>=', $pre_day)->where('paid', 1) ->where('store_id','in', $merList)->where('refund_status', 0)->value('sum(pay_price)'), 2) ?? 0;
        //昨日订单数
        $data['proCount'] = self::where('is_del', 0)->where('pay_time', '<', $to_day)->where('pay_time', '>=', $pre_day)->where('paid', 1) ->where('store_id','in', $merList)->where('refund_status', 0)->count();
        //本月成交额
        $data['monthPrice'] = number_format(self::where('is_del', 0)->where('pay_time', '>=', $now_month)->where('paid', 1) ->where('store_id','in', $merList)->where('refund_status', 0)->value('sum(pay_price)'), 2) ?? 0;
        //本月订单数
        $data['monthCount'] = self::where('is_del', 0)->where('pay_time', '>=', $now_month) ->where('store_id','in', $merList)->where('paid', 1)->where('refund_status', 0)->count();
        return $data;
    }
    
    
    /**
     * 获取 今日 昨日 本月 订单金额
     * @return mixed
     */
    public static function getMyOrderTimeData($uid,$store_id)
    {
        $to_day = strtotime(date('Y-m-d'));//今日
        $pre_day = strtotime(date('Y-m-d', strtotime('-1 day')));//昨日
        $now_month = strtotime(date('Y-m'));//本月
        //今日成交额
        $data['todayPrice'] = number_format(self::alias('a')->join('store_order_status s', 'a.id = s.oid')->where('a.pay_time', '>=', $to_day)->where('a.paid', 1)->where('a.store_id', $store_id)->where('a.refund_status', 0)->where('s.uid', $uid)->value('sum(total_price)'), 2) ?? 0;
        //今日订单数
        $data['todayCount'] = self::alias('a')->join('store_order_status s', 'a.id = s.oid')->where('a.pay_time', '>=', $to_day)->where('a.paid', 1)->where('a.store_id', $store_id)->where('a.refund_status', 0)->where('s.uid', $uid)->count();
        //昨日成交额
        $data['proPrice'] = number_format(self::alias('a')->join('store_order_status s', 'a.id = s.oid')->where('a.pay_time', '<', $to_day)->where('a.pay_time', '>=', $pre_day)->where('a.paid', 1)->where('a.store_id', $store_id)->where('a.refund_status', 0)->where('s.uid', $uid)->value('sum(total_price)'), 2) ?? 0;
        //昨日订单数
        $data['proCount'] = self::alias('a')->join('store_order_status s', 'a.id = s.oid')->where('a.pay_time', '<', $to_day)->where('a.pay_time', '>=', $pre_day)->where('a.paid', 1)->where('a.store_id', $store_id)->where('a.refund_status', 0)->where('s.uid', $uid)->count();
        //本月成交额
        $data['monthPrice'] = number_format(self::alias('a')->join('store_order_status s', 'a.id = s.oid')->where('a.pay_time', '>=', $now_month)->where('a.paid', 1)->where('a.store_id', $store_id)->where('a.refund_status', 0)->where('s.uid', $uid)->value('sum(total_price)'), 2) ?? 0;
        //本月订单数
        $data['monthCount'] = self::alias('a')->join('store_order_status s', 'a.id = s.oid')->where('a.pay_time', '>=', $now_month)->where('a.paid', 1)->where('a.store_id', $store_id)->where('a.refund_status', 0)->where('s.uid', $uid)->count();
       
        $data['todayPrice'] += number_format(StorePayOrder::where('check_id',$uid)->where('pay_time', '>=', $to_day)->where('paid', 1)->where('store_id', $store_id)->value('sum(total_amount)'), 2) ?? 0;
        $data['todayCount'] += StorePayOrder::where('check_id',$uid)->where('pay_time', '>=', $to_day)->where('paid', 1)->where('store_id', $store_id)->count();
        $data['proPrice'] += number_format(StorePayOrder::where('check_id',$uid)->where('pay_time', '<', $to_day)->where('pay_time', '>=', $pre_day)->where('paid', 1)->where('store_id', $store_id)->value('sum(total_amount)'), 2) ?? 0;
        $data['proCount'] += StorePayOrder::where('check_id',$uid)->where('pay_time', '<', $to_day)->where('pay_time', '>=', $pre_day)->where('paid', 1)->where('store_id', $store_id)->count();
        $data['monthPrice'] += number_format(StorePayOrder::where('check_id',$uid)->where('pay_time', '>=', $now_month)->where('paid', 1)->where('store_id', $store_id)->value('sum(total_amount)'), 2) ?? 0;
        $data['monthCount'] += StorePayOrder::where('check_id',$uid)->where('pay_time', '>=', $now_month)->where('paid', 1)->where('store_id', $store_id)->count();
        return $data;
    }
    
    
    /**
     * 获取 今日 昨日 本月 订单金额
     * @return mixed
     */
    public static function getPayOrderTimeData($uid)
    {
        $pre_day = strtotime(date('Y-m-d', strtotime('-1 day')));//昨日
        $data['total_Price'] = number_format(StorePayOrder::alias('a')->join('system_store s', 'a.store_id = s.id')->where('a.pay_time', '>', $pre_day)->where('s.user_id',$uid)->where('a.refund_status',0)->value('sum(pay_amount)'), 2) ?? 0;
        $data['total_count'] = StorePayOrder::alias('a')->join('system_store s', 'a.store_id = s.id')->where('a.pay_time', '>', $pre_day)->where('s.user_id',$uid)->where('a.refund_status',0)->count();
        $data['refund_Price'] = number_format(StorePayOrder::alias('a')->join('system_store s', 'a.store_id = s.id')->where('a.pay_time', '>', $pre_day)->where('s.user_id',$uid)->where('a.refund_status',1)->value('sum(refund_amount)'), 2) ?? 0;
        
        return $data;
    }
    
    public static function getPayOrderDetail($order_id){
        $data = StorePayOrder::where('id',$order_id)->where('refund_status',0)->find();
        $data = $data ? $data->toArray() : [];
        return $data;
    }
    
    

    /**
     * 获取某个用户的订单统计数据
     * @param $uid
     * @return mixed
     */
    public static function getOrderData($uid)
    {
        //订单支付没有退款 数量
        $data['order_count'] = self::where('is_del', 0)->where('paid', 1)->where('uid', $uid)->where('refund_status', 0)->count();
        //订单支付没有退款 支付总金额
        $data['sum_price'] = self::where('is_del', 0)->where('paid', 1)->where('uid', $uid)->where('refund_status', 0)->sum('pay_price');
        //订单待支付 数量
        $data['unpaid_count'] = self::statusByWhere(0, $uid)->where('is_del', 0)->where('uid', $uid)->count();
        //订单待发货 数量
        $data['unshipped_count'] = self::statusByWhere(1, $uid)->where('is_del', 0)->where('uid', $uid)->count();
        //订单待收货 数量
        $data['received_count'] = self::statusByWhere(2, $uid)->where('is_del', 0)->where('uid', $uid)->count();
        //订单待评价 数量
        $data['evaluated_count'] = self::statusByWhere(3, $uid)->where('is_del', 0)->where('uid', $uid)->count();
        //订单已完成 数量
        $data['complete_count'] = self::statusByWhere(4, $uid)->where('is_del', 0)->where('uid', $uid)->count();
        //订单退款
        $data['refund_count'] = self::statusByWhere(-1, $uid)->where('is_del', 0)->where('uid', $uid)->count();
        return $data;
    }


    /**
     * 获取订单统计数据
     * @param $uid
     * @return mixed
     */
    public static function getOrderDataAdmin($uid)
    {
        $merList =   StoreService::getAdminMerList($uid);
        //订单支付没有退款 数量
        $data['order_count'] = self::where('is_del', 0)->where('store_id','in', $merList)->where('paid', 1)->where('refund_status', 0)->count();
        //订单支付没有退款 支付总金额
        $data['sum_price'] = self::where('is_del', 0)->where('store_id','in', $merList)->where('paid', 1)->where('refund_status', 0)->sum('pay_price');
        //订单待支付 数量
        $data['unpaid_count'] = self::statusByWhere(0, 0)->where('is_del', 0)->where('store_id','in', $merList)->count();
        //订单待发货 数量
        $data['unshipped_count'] = self::statusByWhere(1, 0)->where('is_del', 0)->where('store_id','in', $merList)->count();
        //订单待收货 数量
        $data['received_count'] = self::statusByWhere(2, 0)->where('is_del', 0)->where('store_id','in', $merList)->count();
        //订单待评价 数量
        $data['evaluated_count'] = self::statusByWhere(3, 0)->where('is_del', 0)->where('store_id','in', $merList)->count();
        //订单已完成 数量
        $data['complete_count'] = self::statusByWhere(4, 0)->where('is_del', 0)->where('store_id','in', $merList)->count();
        //订单退款 数量
        $data['refund_count'] = self::statusByWhere(-3, 0)->where('is_del', 0)->where('store_id','in', $merList)->count();
        return $data;
    }


    /**
     * 累计消费
     * @param $uid
     * @return float
     */
    public static function getOrderStatusSum($uid)
    {
        return self::where('uid', $uid)->where('is_del', 0)->where('paid', 1)->sum('pay_price');
    }

    public static function getPinkOrderId($id)
    {
        return self::where('id', $id)->value('order_id');
    }

    /**
     * 未支付订单自动取消
     * @param int $limit 分页截取条数
     * @param string $prefid 缓存名称
     * @param int $expire 缓存时间
     * @return string|null
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public static function orderUnpaidCancel()
    {
        //系统预设取消订单时间段
        $keyValue = ['order_cancel_time', 'order_activity_time', 'order_bargain_time', 'order_seckill_time', 'order_pink_time'];

        //获取配置
        $systemValue = SystemConfigService::more($keyValue);
        //格式化数据
        $systemValue = self::setValeTime($keyValue, is_array($systemValue) ? $systemValue : []);
        //检查是否有未支付的订单   未支付查询条件
        $unPidCount = self::where('paid', 0)->where('pay_type', '<>', 'offline')->where('is_del', 0)->where('status', 0)->where('refund_status', 0)->count();
        if (!$unPidCount) return null;
        try {
            $res = true;
            // 未支付查询条件
            $orderList = self::where('paid', 0)->where('pay_type', '<>', 'offline')->where('is_del', 0)->where('status', 0)->where('refund_status', 0)->field('add_time,pink_id,order_id,seckill_id,bargain_id,combination_id,status,cart_id,use_integral,refund_status,uid,unique,back_integral,coupon_id,paid,is_del')->select();
            foreach ($orderList as $order) {
                if ($order['seckill_id']) {
                    //优先使用单独配置的过期时间
                    $order_seckill_time = $systemValue['order_seckill_time'] ? $systemValue['order_seckill_time'] : $systemValue['order_activity_time'];
                    $res = $res && self::RegressionAll($order_seckill_time, $order);
                    unset($order_seckill_time);
                } else if ($order['bargain_id']) {
                    $order_bargain_time = $systemValue['order_bargain_time'] ? $systemValue['order_bargain_time'] : $systemValue['order_activity_time'];
                    $res = $res && self::RegressionAll($order_bargain_time, $order);
                    unset($order_bargain_time);
                } else if ($order['pink_id'] || $order['combination_id']) {
                    $order_pink_time = $systemValue['order_pink_time'] ? $systemValue['order_pink_time'] : $systemValue['order_activity_time'];
                    $res = $res && self::RegressionAll($order_pink_time, $order);
                    unset($order_pink_time);
                } else {
                    $res = $res && self::RegressionAll($systemValue['order_cancel_time'], $order);
                }
            }
            if (!$res) throw new \Exception('更新错误');
            unset($orderList, $res, $pages);
            return null;
        } catch (PDOException $e) {
            Log::error('未支付自动取消时发生数据库查询错误，错误原因为：' . $e->getMessage());
            throw new \Exception($e->getMessage());
        } catch (\think\Exception $e) {
            Log::error('未支付自动取消时发生系统错误，错误原因为：' . $e->getMessage());
            throw new \Exception($e->getMessage());
        }

    }


    /**
     * 未支付订单超过预设时间回退所有,如果不设置未支付过期时间，将不取消订单
     * @param $time 预设时间
     * @param $order 订单详情
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected static function RegressionAll($time, $order)
    {
        if ($time == 0) return true;
        if (($order['add_time'] + bcmul($time, 3600, 0)) < time()) {
            $res1 = self::RegressionStock($order);
            $res2 = self::RegressionIntegral($order);
            $res3 = self::RegressionCoupon($order);
            $res = $res1 && $res2 && $res3;
            if ($res) $res = false !== self::where('order_id', $order['order_id'])->update(['is_del' => 1, 'mark' => '订单未支付已超过系统预设时间']);
            unset($res1, $res2, $res3);
            return $res;
        } else
            return true;
    }


    /**
     * 格式化数据
     * @param array $array 原本数据键
     * @param $value 需要格式化的数据
     * @param int $default 默认值
     * @return mixed
     */
    protected static function setValeTime(array $array, $value, $default = 0)
    {
        foreach ($array as $item) {
            if (!isset($value[$item]))
                $value[$item] = $default;
            else if (is_string($value[$item]))
                $value[$item] = (float)$value[$item];
        }
        return $value;
    }

    public static function getOrderTotalPrice($cartInfo)
    {
        $totalPrice = 0;
        foreach ($cartInfo as $cart) {
            $totalPrice = bcadd($totalPrice, bcmul($cart['cart_num'], $cart['truePrice'], 2), 2);
        }
        return $totalPrice;
    }

    public static function getOrderCostPrice($cartInfo)
    {
        $costPrice = 0;
        foreach ($cartInfo as $cart) {
            $costPrice = bcadd($costPrice, bcmul($cart['cart_num'], $cart['costPrice'], 2), 2);
        }
        return $costPrice;
    }

    public static function getCombinationOrderCostPrice($cartInfo)
    {
        $costPrice = 0;
        foreach ($cartInfo as $cart) {
            if ($cart['combination_id']) {
                $costPrice = bcadd($costPrice, bcmul($cart['cart_num'], StoreCombination::where('id', $cart['combination_id'])->value('price'), 2), 2);
            }
        }
        return (float)$costPrice;
    }

    public static function yueRefundAfter($order)
    {

    }

    /**
     * 获取余额支付的金额
     * @param $uid
     * @return float|int
     */
    public static function getOrderStatusYueSum($uid)
    {
        return self::where('uid', $uid)->where('is_del', 0)->where('is_del', 0)->where('pay_type', 'yue')->where('paid', 1)->sum('pay_price');
    }

    /**
     * 砍价支付成功订单数量
     * @param $bargain
     * @return int
     */
    public static function getBargainPayCount($bargain)
    {
        return self::where('bargain_id', $bargain)->where(['paid' => 1, 'refund_status' => 0])->count();
    }

    /**
     * 7天自动收货
     */
    public static function startTakeOrder()
    {
        //7天前时间戳
        $systemDeliveryTime = sys_config('system_delivery_time') ?? 0;
        //0为取消自动收货功能
        if ($systemDeliveryTime == 0) return true;
        $sevenDay = strtotime(date('Y-m-d H:i:s', strtotime('-' . $systemDeliveryTime . ' day')));
        $model = new self;
        $model = $model->alias('o');
        $model = $model->join('StoreOrderStatus s', 's.oid=o.id');
        $model = $model->where('o.paid', 1);
        $model = $model->where('s.change_type', 'delivery_goods');
        $model = $model->where('s.change_time', '<', $sevenDay);
        $model = $model->where('o.status', 1);
        $model = $model->where('o.refund_status', 0);
        $model = $model->where('o.is_del', 0);
        $orderInfo = $model->column('id', 'id');
        if (!count($orderInfo)) return true;
        foreach ($orderInfo as $key => &$item) {
            $order = self::get($item);
            if ($order['status'] == 2) continue;
            if ($order['paid'] == 1 && $order['status'] == 1) $data['status'] = 2;
            else if ($order['pay_type'] == 'offline') $data['status'] = 2;
            else continue;
            if (!self::edit($data, $item, 'id')) continue;
            try {
                OrderRepository::storeProductOrderTakeDeliveryTimer($order);
            } catch (\Exception $e) {
                continue;
            }
            StoreOrderStatus::status($item, 'take_delivery', '已收货[自动收货]');
        }
    }

    /**
     * 获取订单信息
     * @param $id
     * @param string $field
     * @return array|null|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getOrderInfo($id, $field = 'order_id')
    {
        return self::where('id', $id)->field($field)->find();
    }

    /**
     * 订单每月统计数据
     * @param $page
     * @param $limit
     * @return array
     */
    public static function getOrderDataPriceCount($uid,$page, $limit, $start, $stop)
    {
        $merList =   StoreService::getAdminMerList($uid);
        if (!$limit) return [];
        $model = new self;
        if ($start != '' && $stop != '') $model = $model->where('pay_time', '>', $start)->where('pay_time', '<=', $stop);
        $model = $model->field('sum(pay_price) as price,count(id) as count,FROM_UNIXTIME(pay_time, \'%m-%d\') as time');
        $model = $model->where('is_del', 0);
        $model = $model->where('paid', 1);
        $model = $model->where('store_id', "in",$merList );
        //$model = $model->whereOr([[['refunc_status','=',0]],[['refunc_status','=',1]]]);
        $model = $model->where('refund_status', 0);
        $model = $model->group("FROM_UNIXTIME(pay_time, '%Y-%m-%d')");
        $model = $model->order('pay_time DESC');
        if ($page) $model = $model->page($page, $limit);
        {
            return $model->select();
        }
    }
    
    /**
     * 订单每月统计数据
     * @param $page
     * @param $limit
     * @return array
     */
    public static function getMyOrderDataPriceCount($uid,$page, $limit, $start, $stop,$check_id,$store_id)
    {
        if (!$limit) return [];
        $model = self::alias('a')->join('store_order_status b', 'a.id = b.oid')->where('b.uid',$check_id);
        if ($start != '' && $stop != '') $model = $model->where('pay_time', '>', $start)->where('pay_time', '<=', $stop);
        $model = $model->field('sum(pay_price) as price,count(id) as count,FROM_UNIXTIME(pay_time, \'%m-%d\') as time');
        $model = $model->where('is_del', 0);
        $model = $model->where('paid', 1);
        $model = $model->where('store_id', $store_id);
        $model = $model->where('refund_status', 0);
        $model = $model->group("FROM_UNIXTIME(pay_time, '%Y-%m-%d')");
        $model = $model->order('pay_time DESC');
        if ($page) $model = $model->page($page, $limit);
        return $model->select();
    }
    
    public static function getMyPayOrderDataPriceCount($uid,$page, $limit,$check_id,$store_id)
    {
        if (!$limit) return [];
        $model = StorePayOrder::where('check_id',$check_id)->where('store_id',$store_id)->where('paid',1);
        $model = $model->field('sum(total_amount) as price,count(id) as count,FROM_UNIXTIME(pay_time, \'%m-%d\') as time');
        $model = $model->group("FROM_UNIXTIME(pay_time, '%Y-%m-%d')");
        $model = $model->order('pay_time DESC');
        if ($page) $model = $model->page($page, $limit);
        return $model->select();
    }
    
    public static function getMyPayOrderDataPriceList($uid,$page, $limit,$check,$store_id)
    {

        $pre_day = strtotime(date('Y-m-d', strtotime('-1 day')));//昨日
        if (!$limit) return [];
        $model = StorePayOrder::alias('a')->join('system_store b', 'a.store_id = b.id')->join('user c', 'b.user_id = c.uid')->where('a.store_id','=',$store_id)->where('a.pay_time','>',$pre_day);
        $model = $model->field('a.id,a.order_id,a.total_amount,a.pay_amount,a.refund_status,a.store_id,FROM_UNIXTIME(a.pay_time, \'%Y-%m-%d %h:%i:%s\') as time,c.nickname,c.phone');
        $model = $model->order('a.pay_time DESC');
        $data = $model->select();
        if ($page) $model = $model->page($page, $limit);
        return $model->select();
    }
    
    
    
    

    /**
     * 前台订单管理订单列表获取
     * @param $where
     * @return mixed
     */
    public static function orderList($where)
    {
        $model = self::getOrderWhere($where, self::alias('a')->join('user r', 'r.uid=a.uid', 'LEFT'), 'a.', 'r')->field('a.id,a.order_id,a.add_time,a.status,a.total_num,a.total_price,a.total_postage,a.pay_price,a.pay_postage,a.paid,a.refund_status,a.remark,a.pay_type');
        if ($where['order'] != '') {
            $model = $model->order(self::setOrder($where['order']));
        } else {
            $model = $model->order('a.id desc');
        }
        $model = $model->where('a.is_parent',0);
        $data = ($data = $model->page((int)$where['page'], (int)$where['limit'])->select()) && count($data) ? $data->toArray() : [];
        return self::tidyAdminOrder($data);
    }

    /**
     * 前台订单管理 订单信息设置
     * @param $data
     * @param bool $status
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function tidyAdminOrder($data, $status = false)
    {
        foreach ($data as &$item) {
            $_info = StoreOrderCartInfo::where('oid', $item['id'])->field('cart_info')->select()->toArray();
            foreach ($_info as $k => $v) {
                if (!is_array($v['cart_info']))
                    $_info[$k]['cart_info'] = json_decode($v['cart_info'], true);
            }
            foreach ($_info as $k => $v) {
                unset($_info[$k]['cart_info']['type'], $_info[$k]['cart_info']['product_id'], $_info[$k]['cart_info']['combination_id'], $_info[$k]['cart_info']['seckill_id'], $_info[$k]['cart_info']['bargain_id'], $_info[$k]['cart_info']['bargain_id'], $_info[$k]['cart_info']['truePrice'], $_info[$k]['cart_info']['vip_truePrice'], $_info[$k]['cart_info']['trueStock'], $_info[$k]['cart_info']['costPrice'], $_info[$k]['cart_info']['productInfo']['id'], $_info[$k]['cart_info']['productInfo']['vip_price'], $_info[$k]['cart_info']['productInfo']['postage'], $_info[$k]['cart_info']['productInfo']['give_integral'], $_info[$k]['cart_info']['productInfo']['sales'], $_info[$k]['cart_info']['productInfo']['stock'], $_info[$k]['cart_info']['productInfo']['unit_name'], $_info[$k]['cart_info']['productInfo']['is_postage'], $_info[$k]['cart_info']['productInfo']['slider_image'], $_info[$k]['cart_info']['productInfo']['cost'], $_info[$k]['cart_info']['productInfo']['mer_id'], $_info[$k]['cart_info']['productInfo']['cate_id'], $_info[$k]['cart_info']['productInfo']['is_show'], $_info[$k]['cart_info']['productInfo']['store_info'], $_info[$k]['cart_info']['productInfo']['is_del'], $_info[$k]['cart_info']['is_pay'], $_info[$k]['cart_info']['is_del'], $_info[$k]['cart_info']['is_new'], $_info[$k]['cart_info']['add_time'], $_info[$k]['cart_info']['id'], $_info[$k]['cart_info']['uid'], $_info[$k]['cart_info']['product_attr_unique']);
                $_info[$k]['cart_info']['productInfo']['suk'] = '';
                if (isset($v['cart_info']['productInfo']['attrInfo'])) {
                    $_info[$k]['cart_info']['productInfo']['image'] = $_info[$k]['cart_info']['productInfo']['attrInfo']['image'];
                    $_info[$k]['cart_info']['productInfo']['price'] = $_info[$k]['cart_info']['productInfo']['attrInfo']['price'];
                    $_info[$k]['cart_info']['productInfo']['suk'] = $_info[$k]['cart_info']['productInfo']['attrInfo']['suk'];
                    unset($_info[$k]['cart_info']['productInfo']['attrInfo']);
                }
                if (!isset($v['cart_info']['productInfo']['ot_price'])) {
                    $_info[$k]['cart_info']['productInfo']['ot_price'] = $v['cart_info']['productInfo']['price'];
                }
            }
            $item['_info'] = $_info;
            $item['add_time'] = date('Y-m-d H:i:s', $item['add_time']);


            if ($status) {
                $status = [];
                if (!$item['paid'] && $item['pay_type'] == 'offline' && !$item['status'] >= 2) {
                    $status['_type'] = 9;
                    $status['_title'] = '线下付款';
                    $status['_msg'] = '商家处理中,请耐心等待';
                    $status['_class'] = 'nobuy';
                } else if (!$item['paid']) {
                    $status['_type'] = 0;
                    $status['_title'] = '未支付';
                    //系统预设取消订单时间段
                    $keyValue = ['order_cancel_time', 'order_activity_time', 'order_bargain_time', 'order_seckill_time', 'order_pink_time'];
                    //获取配置
                    $systemValue = SystemConfigService::more($keyValue);
                    //格式化数据
                    $systemValue = self::setValeTime($keyValue, is_array($systemValue) ? $systemValue : []);
                    if ($item['pink_id'] || $item['combination_id']) {
                        $order_pink_time = $systemValue['order_pink_time'] ? $systemValue['order_pink_time'] : $systemValue['order_activity_time'];
                        $time = bcadd($item['add_time'], $order_pink_time * 3600, 0);
                        $status['_msg'] = '请在' . date('Y-m-d H:i:s', $time) . '前完成支付!';
                    } else if ($item['seckill_id']) {
                        $order_seckill_time = $systemValue['order_seckill_time'] ? $systemValue['order_seckill_time'] : $systemValue['order_activity_time'];
                        $time = bcadd($item['add_time'], $order_seckill_time * 3600, 0);
                        $status['_msg'] = '请在' . date('Y-m-d H:i:s', $time) . '前完成支付!';
                    } else if ($item['bargain_id']) {
                        $order_bargain_time = $systemValue['order_bargain_time'] ? $systemValue['order_bargain_time'] : $systemValue['order_activity_time'];
                        $time = bcadd($item['add_time'], $order_bargain_time * 3600, 0);
                        $status['_msg'] = '请在' . date('Y-m-d H:i:s', $time) . '前完成支付!';
                    } else {
                        $time = bcadd($item['add_time'], $systemValue['order_cancel_time'] * 3600, 0);
                        $status['_msg'] = '请在' . date('Y-m-d H:i:s', $time) . '前完成支付!';
                    }
                    $status['_class'] = 'nobuy';
                } else if ($item['refund_status'] == 1) {
                    $status['_type'] = -1;
                    $status['_title'] = '申请退款中';
                    $status['_msg'] = '商家审核中,请耐心等待';
                    $status['_class'] = 'state-sqtk';
                } else if ($item['refund_status'] == 2) {
                    $status['_type'] = -2;
                    $status['_title'] = '已退款';
                    $status['_msg'] = '已为您退款,感谢您的支持';
                    $status['_class'] = 'state-sqtk';
                } else if (!$item['status']) {
                    if ($item['pink_id']) {
                        if (StorePink::where('id', $item['pink_id'])->where('status', 1)->count()) {
                            $status['_type'] = 11;
                            $status['_title'] = '拼团中';
                            $status['_msg'] = '等待其他人参加拼团';
                            $status['_class'] = 'state-nfh';
                        } else {
                            $status['_type'] = 1;
                            $status['_title'] = '未发货';
                            $status['_msg'] = '商家未发货,请耐心等待';
                            $status['_class'] = 'state-nfh';
                        }
                    } else {
                        $status['_type'] = 1;
                        $status['_title'] = '未发货';
                        $status['_msg'] = '商家未发货,请耐心等待';
                        $status['_class'] = 'state-nfh';
                    }
                } else if ($item['status'] == 1) {
                    if ($item['delivery_type'] == 'send') {//TODO 送货
                        $status['_type'] = 2;
                        $status['_title'] = '待收货';
                        $status['_msg'] = date('m月d日H时i分', StoreOrderStatus::getTime($item['id'], 'delivery')) . '服务商已送货';
                        $status['_class'] = 'state-ysh';
                    } else {//TODO  发货
                        $status['_type'] = 2;
                        $status['_title'] = '待收货';
                        $status['_msg'] = date('m月d日H时i分', StoreOrderStatus::getTime($item['id'], 'delivery_goods')) . '服务商已发货';
                        $status['_class'] = 'state-ysh';
                    }
                } else if ($item['status'] == 2) {
                    $status['_type'] = 3;
                    $status['_title'] = '待评价';
                    $status['_msg'] = '已收货,快去评价一下吧';
                    $status['_class'] = 'state-ypj';
                } else if ($item['status'] == 3) {
                    $status['_type'] = 4;
                    $status['_title'] = '交易完成';
                    $status['_msg'] = '交易完成,感谢您的支持';
                    $status['_class'] = 'state-ytk';
                }
                if (isset($item['pay_type']))
                    $status['_payType'] = isset(self::$payType[$item['pay_type']]) ? self::$payType[$item['pay_type']] : '其他方式';
                if (isset($item['delivery_type']))
                    $status['_deliveryType'] = isset(self::$deliveryType[$item['delivery_type']]) ? self::$deliveryType[$item['delivery_type']] : '其他方式';
                $item['_status'] = $status;
            } else {
                if ($item['paid'] == 0 && $item['status'] == 0) {
                    $item['status_name'] = '未支付';
                } else if ($item['paid'] == 1 && $item['status'] == 0 && $item['refund_status'] == 0) {
                    $item['status_name'] = '未发货';
                } else if ($item['paid'] == 1 && $item['status'] == 1 && $item['refund_status'] == 0) {
                    $item['status_name'] = '待收货';
                } else if ($item['paid'] == 1 && $item['status'] == 2 && $item['refund_status'] == 0) {
                    $item['status_name'] = '待评价';
                } else if ($item['paid'] == 1 && $item['status'] == 3 && $item['refund_status'] == 0) {
                    $item['status_name'] = '已完成';
                }
            }

        }
        return $data;
    }

    /**
     * 处理where条件
     * @param $where
     * @param $model
     * @param string $aler
     * @param string $join
     * @return StoreOrder|null
     */
    public static function getOrderWhere($where, $model, $aler = '', $join = '')
    {
        if (isset($where['status']) && $where['status'] != '') $model = self::statusWhere($where['status'], $model, $aler);
        if (isset($where['is_del']) && $where['is_del'] != '' && $where['is_del'] != -1) $model = $model->where($aler . 'is_del', $where['is_del']);
        if (isset($where['combination_id'])) {
            if ($where['combination_id'] == '普通订单') {
                $model = $model->where($aler . 'combination_id', 0)->where($aler . 'seckill_id', 0)->where($aler . 'bargain_id', 0);
            }
            if ($where['combination_id'] == '拼团订单') {
                $model = $model->where($aler . 'combination_id', ">", 0)->where($aler . 'pink_id', ">", 0);
            }
            if ($where['combination_id'] == '秒杀订单') {
                $model = $model->where($aler . 'seckill_id', ">", 0);
            }
            if ($where['combination_id'] == '砍价订单') {
                $model = $model->where($aler . 'bargain_id', ">", 0);
            }
        }
        if (isset($where['type'])) {
            switch ($where['type']) {
                case 1:
                    $model = $model->where($aler . 'combination_id', 0)->where($aler . 'seckill_id', 0)->where($aler . 'bargain_id', 0);
                    break;
                case 2:
                    $model = $model->where($aler . 'combination_id', ">", 0);
                    break;
                case 3:
                    $model = $model->where($aler . 'seckill_id', ">", 0);
                    break;
                case 4:
                    $model = $model->where($aler . 'bargain_id', ">", 0);
                    break;
            }
        }
        
        if (isset($where['store_id']) && $where['store_id'] != '') $model = $model->where($aler . 'store_id','in', $where['store_id']);
        

        if (isset($where['real_name']) && $where['real_name'] != '')
            $model = $model->where($aler . 'order_id|' . $aler . 'real_name|' . $aler . 'user_phone' . ($join ? '|' . $join . '.nickname|' . $join . '.uid' : ''), 'LIKE', "%$where[real_name]%");
        if (isset($where['data']) && $where['data'] !== '')
            $model = self::getModelTime($where, $model, $aler . 'add_time');
        
        return $model;
    }

    /**
     * 设置where条件
     * @param $status
     * @param null $model
     * @param string $alert
     * @return StoreOrder|null
     */
    public static function statusWhere($status, $model = null, $alert = '')
    {
        if ($model == null) $model = new self;
        if ('' === $status)
            return $model;
        else if ($status == 0)//未支付
            return $model->where($alert . 'paid', 0)->where($alert . 'status', 0)->where($alert . 'refund_status', 0);
        else if ($status == 1)//已支付 未发货
            return $model->where($alert . 'paid', 1)->where($alert . 'status', 0)->where($alert . 'refund_status', 0);
        else if ($status == 2)//已支付  待收货
            return $model->where($alert . 'paid', 1)->where($alert . 'status', 1)->where($alert . 'refund_status', 0);
        else if ($status == 3)// 已支付  已收货  待评价
            return $model->where($alert . 'paid', 1)->where($alert . 'status', 2)->where($alert . 'refund_status', 0);
        else if ($status == 4)// 交易完成
            return $model->where($alert . 'paid', 1)->where($alert . 'status', 3)->where($alert . 'refund_status', 0);
        else if ($status == -1)//退款中
            return $model->where($alert . 'paid', 1)->where($alert . 'refund_status', 1);
        else if ($status == -2)//已退款
            return $model->where($alert . 'paid', 1)->where($alert . 'refund_status', 2);
        else if ($status == -3)//退款
            return $model->where($alert . 'paid', 1)->where($alert . 'refund_status', 'in', '1,2');
        else
            return $model;
    }

    /**
     * 订单详情 管理员
     * @param $orderId
     * @param string $field
     * @return array|null|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getAdminOrderDetail($orderId, $field = '*')
    {
        return self::where('order_id', $orderId)->field($field)->find();
    }

    /**
     * 获取指定时间区间的支付金额 管理员
     * @param $start
     * @param $stop
     * @return float
     */
    public static function getOrderTimeBusinessVolumePrice($start, $stop,$merList)
    {
        return self::where('is_del', 0)->where('paid', 1)->where('store_id','in', $merList)->where('refund_status', 0)->where('add_time', '>=', $start)->where('add_time', '<', $stop)->sum('pay_price');
    }

    /**
     * 获取指定时间区间的支付订单数量 管理员
     * @param $start
     * @param $stop
     * @return float
     */
    public static function getOrderTimeBusinessVolumeNumber($start, $stop,$merList)
    {
        return self::where('is_del', 0)->where('paid', 1)->where('store_id','in', $merList)->where('refund_status', 0)->where('add_time', '>=', $start)->where('add_time', '<', $stop)->count();
    }

    /**
     * 获取当前时间到指定时间的支付金额 管理员
     * @param $start 开始时间
     * @param $stop  结束时间
     * @return mixed
     */
    public static function chartTimePrice($start, $stop,$merList)
    {
        $model = new self;
        $model = $model->field('sum(pay_price) as num,FROM_UNIXTIME(add_time, \'%Y-%m-%d\') as time');
        $model = $model->where('is_del', 0);
        $model = $model->where('paid', 1);
        $model = $model->where('store_id','in', $merList);
        $model = $model->where('refund_status', 0);
        $model = $model->where('add_time', '>=', $start);
        $model = $model->where('add_time', '<', $stop);
        $model = $model->group("FROM_UNIXTIME(add_time, '%Y-%m-%d')");
        $model = $model->order('add_time ASC');
        return $model->select();
    }

    /**
     * 获取当前时间到指定时间的支付订单数 管理员
     * @param $start 开始时间
     * @param $stop  结束时间
     * @return mixed
     */
    public static function chartTimeNumber($start, $stop,$merList)
    {
        $model = new self;
        $model = $model->field('count(id) as num,FROM_UNIXTIME(add_time, \'%Y-%m-%d\') as time');
        $model = $model->where('is_del', 0);
        $model = $model->where('paid', 1);
        $model = $model->where('store_id','in', $merList);
        $model = $model->where('refund_status', 0);
        $model = $model->where('add_time', '>=', $start);
        $model = $model->where('add_time', '<', $stop);
        $model = $model->group("FROM_UNIXTIME(add_time, '%Y-%m-%d')");
        $model = $model->order('add_time ASC');
        return $model->select();
    }

    /**
     * 修改支付方式为线下支付
     * @param $orderId
     * @return bool
     */
    public static function setOrderTypePayOffline($orderId)
    {
        return self::edit(['pay_type' => 'offline'], $orderId, 'order_id');
    }

    /**
     * 线下付款
     * @param $id
     * @return $this
     */
    public static function updateOffline($id)
    {
        $count = self::where('id', $id)->count();
        if (!$count) return self::setErrorInfo('订单不存在');
        $count = self::where('id', $id)->where('paid', 0)->count();
        if (!$count) return self::setErrorInfo('订单已支付');
        $res = self::where('id', $id)->update(['paid' => 1, 'pay_time' => time()]);
        return $res;
    }

    /**
     * 向创建订单10分钟未付款的用户发送短信
     */
    public static function sendTen()
    {
        $list = self::where('paid', 0)->where('is_del', 0)->where('is_system_del', 0)->where('add_time', '>', time() - 900)->where('add_time', '<', time() - 600)->column('user_phone');
        foreach ($list as $phone) {
            ShortLetterRepositories::send(true, $phone, [], 'ORDER_PAY_FALSE');
        }
    }

    public function productInfo()
    {
        return $this->hasMany(StoreProductReply::class, 'oid', 'id');
    }

    public static function setOrderProductReplyWhere($where)
    {
        $model = self::where('status', 3)->order('add_time desc')->whereIn('id', function ($query) {
            $query->name('store_order')->alias('o')->join('store_product_reply a', 'a.oid = o.id')->group('o.id')->field('o.id')->select();
        })->with('productInfo', function ($query) use ($where) {
            $alias = '';
            if (isset($where['title']) && $where['title'] != '')
                $query->where("{$alias}comment", 'LIKE', "%$where[title]%");
            if (isset($where['is_reply']) && $where['is_reply'] != '') {
                if ($where['is_reply'] >= 0) {
                    $query->where("{$alias}is_reply", $where['is_reply']);
                } else {
                    $query->where("{$alias}is_reply", '>', 0);
                }
            }
            if (isset($where['producr_id']) && $where['producr_id'] != 0)
                $query->where($alias . 'product_id', $where['producr_id']);
            $query->where("{$alias}is_del", 0);
        });
        return $model;

    }
    
    //测试微信模板
    public static function sendmessage($uid,$order_id){
        $order = self::where('order_id', $order_id)->find();
        $storeInfo = SystemStore::where('id',$order['store_id'])->find();
        //给客户发送订单核销提醒  商品名称、数量
        $list = DB::name('store_order')->alias('o')->where('o.order_id',$order['order_id'])->join('store_order_cart_info p','o.id=p.oid')->join('store_product q','p.product_id=q.id')->field('q.store_name')->select()->toArray();
        if($list){
            foreach ($list as &$item) {
                //给客户发送支付成功提醒
                WechatTemplateService::sendTemplate(WechatUser::where('uid', $order['uid'])->value('openid'), WechatTemplateService::HEX_SUCCESS, [
                    'first' => '尊敬的客户您好，您有一笔订单已经到店核销',
                    'keyword1' => $item['store_name'],
                    'keyword2' => $order['total_num']."份",
                    'keyword3' => date('Y-m-d H:i:s', time()),
                    'remark' => '点击查看订单详情'
                ], Url::buildUrl('/order/detail/'.$order['order_id'])->suffix('')->domain(true)->build());
                //给商家发送支付成功提醒
                WechatTemplateService::sendTemplate(WechatUser::where('uid', $uid)->value('openid'), WechatTemplateService::SHEX_SUCCESS, [
                    'first' => '尊敬的商户您好，您刚完成一笔订单核销',
                    'keyword1' => $order['verify_code'],
                    'keyword2' => $item['store_name'],
                    'keyword3' => date('Y-m-d H:i:s', time()),
                    'keyword4' => $storeInfo['name'],
                    'remark' => '点击查看订单详情'
                ], Url::buildUrl('/order/detail/'.$order['order_id'])->suffix('')->domain(true)->build());
            } 
        }
    }
    
    

    public static function getOrderProductReplyList($where)
    {
        $list = self::setOrderProductReplyWhere($where)->page((int)$where['message_page'], (int)$where['limit'])->select();
        $list = count($list) ? $list->toArray() : [];
        foreach ($list as $key => $item) {
            if (isset($item['productInfo']) && is_array($item['productInfo']) && count($item['productInfo'])) {
                foreach ($item['productInfo'] as $k => $v) {
                    if (!$v['nickname'] && $v['uid']) {
                        $v['nickname'] = User::where('uid', $v['uid'])->value('nickname');
                        $v['avatar'] = User::where('uid', $v['uid'])->value('avatar');
                    }
                    $v['image'] = '';
                    $v['store_name'] = '';
                    if ($v['product_id']) {
                        $product = StoreProduct::where('id', $v['product_id'])->field(['image', 'store_name'])->find();
                        if ($product) {
                            $v['image'] = $product['image'];
                            $v['store_name'] = $product['store_name'];
                        }
                    }
                    $list[$key]['productInfo'][$k] = $v;
                }
            }
        }
        $count = self::setOrderProductReplyWhere($where)->count();
        return compact('list', 'count');
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
        return self::where('uid', $uid)->where('paid', 1)->field('sum(pay_price) as pay_price')->find();
    }

}