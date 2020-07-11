<?php
/**
 *
 * @author: xaboy<365615158@qq.com>
 * @day: 2017/12/23
 */

namespace app\models\store;


use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;

/**
 * TODO 客服Model
 * Class StoreService
 * @package app\models\store
 */
class StoreService extends BaseModel
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
    protected $name = 'store_service';

    use ModelTrait;

    /**
     * 获取客服列表
     * @param $page
     * @param $limit
     * @return array
     */
    public static function lst($page, $limit)
    {
//        if(!$page || !$limit) return [];
        $model = new self;
        $model = $model->where('status', 1);
//        $model = $model->page($page, $limit);
        return $model->select();
    }

    /**
     * 获取客服信息
     * @param $uid
     * @param string $field
     * @return array|null|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getServiceInfo($uid, $field = '*')
    {
        return self::where('uid', $uid)->where('status', 1)->field($field)->find();
    }

    /**
     * 判断是否客服
     * @param $uid
     * @return int
     */
    public static function orderServiceStatus($uid)
    {
        return self::where('uid', $uid)->where('status', 1)->where('customer', 1)->count();
    }

    /**
     * 获取接受通知的客服
     *
     * @return array
     */
    public static function getStoreServiceOrderNotice(){
        return self::where('status',1)->where('notify',1)->column('uid','uid');
    }
    
    // 获取管理的商家
    public static function getAdminMerList($uid){
        return self::where('is_admin',1)->where('uid',$uid)->column('store_id','store_id');
    }
    
    
    // 是否有核销的权限
    public static function isCanCheck($merId, $userId)
    {
        $rs1 = self::where('store_id', $merId)->where('uid', $userId)->where('is_check', 1)->find();
        $rs2 = self::where('store_id', $merId)->where('uid', $userId)->where('is_admin', 1)->find();
        return !empty($rs1) || !empty($rs2);
    }
    
    
    // 是否有核销的权限
    public static function isOnlyCanCheck($userId)
    {
        $rs1 = self::where('uid', $userId)->where('is_check', 1)->find();
        return !empty($rs1);
    }
    
    
    public static function isCanOrder($userId){
        $rs1 = self::where('uid', $userId)->where('is_admin', 1)->find();
        return !empty($rs1);
    }
    
    /**
     * 取得当商户的全部客服
     * @param $merId
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getMerService($merId){
        return self::where('store_id',  $merId)->select();
    }
    
    // 设置管理员
    public static function setAdmin($id, $status)
    {
        return self::where('id', $id)->update([
            'is_admin' => $status
        ]);
    
    }
    
    // 设置核销员
    public static function setCheck($id, $status)
    {
        return self::where('id', $id)->update([
            'is_check' => $status
        ]);
    }
    
    public static function isBind($merId,$userId){
        $rs1 = self::where('mer_id', $merId)->where('uid', $userId)->find();
        return !empty($rs1);
    }
}