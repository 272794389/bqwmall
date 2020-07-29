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
 * TODO 用户消费明细 model
 * Class UserBill
 * @package app\models\user
 */
class UserPayOrder extends BaseModel
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


    /*
     * 获取用户账单明细
     * @param int $uid 用户uid
     * @param int $page 页码
     * @param int $limit 展示多少条
     * @param int $type 展示类型
     * @return array
     * */
    public static function getUserPayOrderList($uid, $page, $limit, $type)
    {
        if (!$limit) return [];
        $model = self::where('uid', $uid)->where('paid', 1)->order('add_time desc')
            ->field('FROM_UNIXTIME(add_time,"%Y-%m") as time,group_concat(id SEPARATOR ",") ids')->group('time');
        if ($page) $model = $model->page((int)$page, (int)$limit);
        $list = ($list = $model->select()) ? $list->toArray() : [];
        $data = [];
        foreach ($list as $item) {
            $value['time'] = $item['time'];
            $value['list'] = self::alias('a')->field('FROM_UNIXTIME(a.add_time,"%Y-%m-%d %H:%i") as add_time,a.pay_amount,b.mer_name')->join('system_store b', 'b.id=a.store_id', 'LEFT')->order('a.add_time DESC')->select();
            //$value['list'] = self::where('id', 'in', $item['ids'])->field('FROM_UNIXTIME(add_time,"%Y-%m-%d %H:%i") as add_time,pay_amount')->order('add_time DESC')->select();
            array_push($data, $value);
        }
        return $data;
    }
    
    /**
     * 获取总佣金
     * @param $uid
     * @return float
     */
    public static function getPayAmount($uid)
    {
        return self::where('uid', $uid)->where('paid', 1)->sum('pay_amount');
    }

   
}
