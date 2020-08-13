<?php
/**
 *
 * @author: xaboy<365615158@qq.com>
 * @day: 2017/12/20
 */

namespace app\models\store;


use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;

/**
 * TODO 优惠券发放Model
 * Class StoreCouponUser
 * @package app\models\store
 */
class GoodsCouponUser extends BaseModel
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
    protected $name = 'goods_coupon_user';

    protected $type = [
        'coupon_price' => 'float'
    ];

    protected $hidden = [
        'uid'
    ];

    use ModelTrait;

   
    /**
     * TODO 获取用户优惠券（全部）
     * @param $uid
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getUserCouponList($uid)
    {
        self::checkInvalidCoupon();
        $couponList = self::where('uid', $uid)->order('is_fail ASC,add_time DESC')->select()->toArray();
        
        /*
        if($status==1){//有效可使用
            $couponList = self::where('uid', $uid)->where('is_fail',0)->order('is_fail ASC,add_time DESC')->select()->toArray();
        }else{//失效已过期
            $couponList = self::where('uid', $uid)->where('is_fail',1)->order('is_fail ASC,add_time DESC')->select()->toArray();
        }*/
        return self::tidyCouponList($couponList);
    }
    
    
    // 批量获取抵扣券
    public static function getCouponList($uid,$is_flag){
        $rs =self::where('uid',$uid)->where('is_fail',0)->where('is_flag',$is_flag)->select();
        return empty($rs) ?[]:$rs->toArray();
    }
    
    // 批量获取抵扣券
    public static function getAllCouponList($uid){
        $rs =self::where('uid',$uid)->where('is_fail',0)->order('is_fail desc')->select();
        return empty($rs) ?[]:$rs->toArray();
    }


    public static function checkInvalidCoupon()
    {
        self::where('end_time', '<', time())->where('is_fail', 0)->update(['is_fail' => 1]);
    }
    
    
    public static function tidyCouponList($couponList)
    {
        $time = time();
        foreach ($couponList as $k => $coupon) {
            $coupon['_add_time'] = date('Y/m/d', $coupon['add_time']);
            $coupon['_end_time'] = date('Y/m/d', $coupon['end_time']);
            $coupon['coupon_price'] = number_format($coupon['coupon_price'], 2);
            $coupon['hamount'] = number_format($coupon['hamount'], 2);
            if ($coupon['is_fail']) {
                $coupon['_type'] = 0;
                $coupon['_msg'] = '已失效';
            } else if ($coupon['add_time'] > $time || $coupon['end_time'] < $time) {
                $coupon['_type'] = 0;
                $coupon['_msg'] = '已过期';
            } else if($coupon['coupon_price']==$coupon['hamount']){
                $coupon['_type'] = 0;
                $coupon['_msg'] = '已使用';
            }else {
                if ($coupon['add_time'] + 3600 * 24 > $time) {
                    $coupon['_type'] = 2;
                    $coupon['_msg'] = '可使用';
                } else {
                    $coupon['_type'] = 1;
                    $coupon['_msg'] = '可使用';
                }
            }
            $couponList[$k] = $coupon;
        }
        return $couponList;
    }

    
}