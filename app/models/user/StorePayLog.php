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
    
    
    /*
     * 获取用户余额明细
     * @param int $uid 用户uid
     * @param int $page 页码
     * @param int $limit 展示多少条
     * @param int $type 展示类型
     * @return array
     * */
    public static function getUserRecordList($uid, $page, $limit, $type)
    {
        if (!$limit) return [];
        $model = self::where('uid', $uid)->where('use_money', '<>', 0)->order('add_time desc')
        ->field('FROM_UNIXTIME(add_time,"%Y-%m") as time,group_concat(id SEPARATOR ",") ids')->group('time');
        switch ((int)$type) {
            
            case 1://消费
                $model = $model->where('use_money', '<', 0);
                break;
            case 2://收入
                $model = $model->where('use_money', '>', 0);
                break;
        }
        if ($page) $model = $model->page((int)$page, (int)$limit);
        $list = ($list = $model->select()) ? $list->toArray() : [];
        $data = [];
        foreach ($list as $item) {
            $value['time'] = $item['time'];
            $value['list'] = self::where('id', 'in', $item['ids'])->field('FROM_UNIXTIME(add_time,"%Y-%m-%d %H:%i") as add_time,mark,use_money')->order('add_time DESC')->select();
            array_push($data, $value);
        }
        return $data;
    }
    
    
     /*
     * 获取用户货款明细
     * @param int $uid 用户uid
     * @param int $page 页码
     * @param int $limit 展示多少条
     * @param int $type 展示类型
     * @return array
     * */
    public static function getHuoRecordList($uid, $page, $limit, $type)
    {
        if (!$limit) return [];
        $model = self::where('uid', $uid)->where('huokuan', '<>', 0)->order('add_time desc')
        ->field('FROM_UNIXTIME(add_time,"%Y-%m") as time,group_concat(id SEPARATOR ",") ids')->group('time');
        switch ((int)$type) {
            
            case 1://消费
                $model = $model->where('huokuan', '<', 0);
                break;
            case 2://收入
                $model = $model->where('huokuan', '>', 0);
                break;
        }
        if ($page) $model = $model->page((int)$page, (int)$limit);
        $list = ($list = $model->select()) ? $list->toArray() : [];
        $data = [];
        foreach ($list as $item) {
            $value['time'] = $item['time'];
            $value['list'] = self::where('id', 'in', $item['ids'])->field('FROM_UNIXTIME(add_time,"%Y-%m-%d %H:%i") as add_time,mark,huokuan')->order('add_time DESC')->select();
            array_push($data, $value);
        }
        return $data;
    }
    
    /*
     * 获取用户购物积分明细
     * @param int $uid 用户uid
     * @param int $page 页码
     * @param int $limit 展示多少条
     * @param int $type 展示类型
     * @return array
     * */
    public static function getGiveRecordList($uid, $page, $limit, $type)
    {
        if (!$limit) return [];
        $model = self::where('uid', $uid)->where('give_point', '<>', 0)->order('add_time desc')
        ->field('FROM_UNIXTIME(add_time,"%Y-%m") as time,group_concat(id SEPARATOR ",") ids')->group('time');
        switch ((int)$type) {
    
            case 1://消费
                $model = $model->where('give_point', '<', 0);
                break;
            case 2://收入
                $model = $model->where('give_point', '>', 0);
                break;
        }
        if ($page) $model = $model->page((int)$page, (int)$limit);
        $list = ($list = $model->select()) ? $list->toArray() : [];
        $data = [];
        foreach ($list as $item) {
            $value['time'] = $item['time'];
            $value['list'] = self::where('id', 'in', $item['ids'])->field('FROM_UNIXTIME(add_time,"%Y-%m-%d %H:%i") as add_time,mark,give_point')->order('add_time DESC')->select();
            array_push($data, $value);
        }
        return $data;
    }
    
    /*
     * 获取用户消费积分明细
     * @param int $uid 用户uid
     * @param int $page 页码
     * @param int $limit 展示多少条
     * @param int $type 展示类型
     * @return array
     * */
    public static function getPayRecordList($uid, $page, $limit, $type)
    {
        if (!$limit) return [];
        $model = self::where('uid', $uid)->where('pay_point', '<>', 0)->order('add_time desc')
        ->field('FROM_UNIXTIME(add_time,"%Y-%m") as time,group_concat(id SEPARATOR ",") ids')->group('time');
        switch ((int)$type) {
    
            case 1://消费
                $model = $model->where('pay_point', '<', 0);
                break;
            case 2://收入
                $model = $model->where('pay_point', '>', 0);
                break;
        }
        if ($page) $model = $model->page((int)$page, (int)$limit);
        $list = ($list = $model->select()) ? $list->toArray() : [];
        $data = [];
        foreach ($list as $item) {
            $value['time'] = $item['time'];
            $value['list'] = self::where('id', 'in', $item['ids'])->field('FROM_UNIXTIME(add_time,"%Y-%m-%d %H:%i") as add_time,mark,pay_point')->order('add_time DESC')->select();
            array_push($data, $value);
        }
        return $data;
    }
    
    
    /*
     * 获取用户重消积分明细
     * @param int $uid 用户uid
     * @param int $page 页码
     * @param int $limit 展示多少条
     * @param int $type 展示类型
     * @return array
     * */
    public static function getReRecordList($uid, $page, $limit, $type)
    {
        if (!$limit) return [];
        $model = self::where('uid', $uid)->where('repeat_point', '<>', 0)->order('add_time desc')
        ->field('FROM_UNIXTIME(add_time,"%Y-%m") as time,group_concat(id SEPARATOR ",") ids')->group('time');
        switch ((int)$type) {
    
            case 1://消费
                $model = $model->where('repeat_point', '<', 0);
                break;
            case 2://收入
                $model = $model->where('repeat_point', '>', 0);
                break;
        }
        if ($page) $model = $model->page((int)$page, (int)$limit);
        $list = ($list = $model->select()) ? $list->toArray() : [];
        $data = [];
        foreach ($list as $item) {
            $value['time'] = $item['time'];
            $value['list'] = self::where('id', 'in', $item['ids'])->field('FROM_UNIXTIME(add_time,"%Y-%m-%d %H:%i") as add_time,mark,repeat_point')->order('add_time DESC')->select();
            array_push($data, $value);
        }
        return $data;
    }
    
    
    
    /**
     * 货款累计
     * @param $uid
     * @return float
     */
    public static function getHuokuanSum($uid,$flag)
    {
        if($flag==1){//支出
            return self::where('uid', $uid)->where('huokuan', '<',0)->sum('huokuan');
        }else{//收入
            return self::where('uid', $uid)->where('huokuan', '>',0)->sum('huokuan');
        }
        
    }
    
    /**
     * 购物积分累计
     * @param $uid
     * @return float
     */
    public static function getGiveSum($uid,$flag)
    {
        if($flag==1){//支出
            return self::where('uid', $uid)->where('give_point', '<',0)->sum('give_point');
        }else{//收入
            return self::where('uid', $uid)->where('give_point', '>',0)->sum('give_point');
        }
    
    }
    
    /**
     * 消费积分累计
     * @param $uid
     * @return float
     */
    public static function getPayPointSum($uid,$flag)
    {
        if($flag==1){//支出
            return self::where('uid', $uid)->where('pay_point', '<',0)->sum('pay_point');
        }else{//收入
            return self::where('uid', $uid)->where('pay_point', '>',0)->sum('pay_point');
        }
    
    }
    
    /**
     * 重消积分累计
     * @param $uid
     * @return float
     */
    public static function getRePointSum($uid,$flag)
    {
        if($flag==1){//支出
            return self::where('uid', $uid)->where('repeat_point', '<',0)->sum('repeat_point');
        }else{//收入
            return self::where('uid', $uid)->where('repeat_point', '>',0)->sum('repeat_point');
        }
    
    }
    
    
    
}