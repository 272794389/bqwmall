<?php
/**
 * Created by CRMEB.
 * Copyright (c) 2017~2019 http://www.crmeb.com All rights reserved.
 * Author: liaofei <136327134@qq.com>
 * Date: 2019/3/27 21:44
 */

namespace app\models\user;

use think\facade\Cache;
use crmeb\traits\ModelTrait;
use crmeb\basic\BaseModel;

/**
 * TODO 用户资金变动model
 * Class UserBill
 * @package app\models\user
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
    
    //写入记录
    public static function expend( $uid, $order_id, $belong_t, $use_money=0, $huokuan = 0, $give_point = 0, $pay_point = 0,$repeat_point = 0,$fee=0, $mark = '')
    {
        $add_time = time();
        return self::create(compact('uid', 'order_id', 'belong_t', 'use_money', 'huokuan', 'give_point', 'pay_point', 'repeat_point','fee', 'mark', 'add_time'));
    }
}