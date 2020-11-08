<?php
/**
 * Created by PhpStorm.
 * User: lianghuan
 * Date: 2018-03-03
 * Time: 16:47
 */

namespace app\models\store;


use app\models\routine\RoutineTemplate;
use app\models\user\StorePayLog;
use app\admin\model\order\StorePayOrder;
use app\admin\model\user\User;
use think\facade\Route as Url;
use crmeb\traits\ModelTrait;
use crmeb\basic\BaseModel;
use crmeb\services\WechatTemplateService;
use crmeb\services\PHPExcelService;

/**
 * 用户消费订单管理 model
 * Class User
 * @package app\admin\model\user
 */
class StoreMission extends BaseModel
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
    protected $name = 'store_mission';

    use ModelTrait;
    
    
    public static function getMyShopSpreadCountList($uid,$keyword = '', $page = 0, $limit = 20)
    {
    	$model = new self;
    	if (strlen(trim($keyword))) $model = $model->where('b.mer_name', 'like', "%$keyword%");
    	$list = $model->alias('a')
    	->join('system_store b', 'a.store_id=b.id')
    	->field('a.*,b.name,b.mer_name,b.image,b.parent_id,b.user_id')
    	->where('b.parent_id',$uid)
    	->page($page, $limit)
    	->select()
    	->each(function ($item) {
    		$datetime = strtotime($item['date'].'-01 00:00:00');
    		$dt=date('Y-m-d H:i:s',$datetime);
    		$endtime = strtotime("$dt+1month");
    		$item['rocnt'] = StorePayOrder::getOrderCounts($item['store_id'],$item['date'],$item['minAmount'],$datetime,$endtime);
    		$item['orderAmount'] = StorePayOrder::getOrderAmount($item['store_id'],$item['date'],$item['minAmount'],$datetime,$endtime);
    		if(!$item['rocnt']) $item['rocnt']=0;
    		$item['rucnt'] = User::getUserCounts($item['user_id'],$datetime,$endtime,1);
    		if(!$item['rucnt']) $item['rucnt']=0;
    		$item['aucnt'] = User::getUserCounts($item['user_id'],$datetime,$endtime,0);
    		if(!$item['aucnt']) $item['aucnt']=0;
    	});//->toArray();
    	if ($list) return $list->toArray();
        else return [];
    }
    
    public static function getMySpreadShopCount($uid,$keyword = ''){
    	$model = new self;
    	if (strlen(trim($keyword))) $model = $model->where('b.mer_name', 'like', "%$keyword%");
    	return $model->alias('a')
    	->join('system_store b', 'a.store_id=b.id')
    	->where('b.parent_id',$uid)
    	->count();	
    }
    
    
    /**
     * 异步获取当前用户 信息
     * @param $where
     * @return array
     */
    public static function getUserList($where)
    {
    	$model = self::setWherePage(self::setWhere($where), $where, [], []);
    	$list = $model->alias('a')
    	->join('system_store b', 'a.store_id=b.id')
    	->join('user c', 'c.uid=b.parent_id')
    	->field('a.*,b.name,b.parent_id,c.real_name,b.user_id')
    	->page((int)$where['page'], (int)$where['limit'])
    	->select()
    	->each(function ($item) {
    		$datetime = strtotime($item['date'].'-01 00:00:00');
    		$dt=date('Y-m-d H:i:s',$datetime);
    		$endtime = strtotime("$dt+1month");
    		$item['rocnt'] = StorePayOrder::getOrderCounts($item['store_id'],$item['date'],$item['minAmount'],$datetime,$endtime);
    		$item['orderAmount'] = StorePayOrder::getOrderAmount($item['store_id'],$item['date'],$item['minAmount'],$datetime,$endtime);
    		if(!$item['rocnt']) $item['rocnt']=0;
    		$item['rucnt'] = User::getUserCounts($item['user_id'],$datetime,$endtime,1);
    		if(!$item['rucnt']) $item['rucnt']=0;
    		$item['aucnt'] = User::getUserCounts($item['user_id'],$datetime,$endtime,0);
    		if(!$item['aucnt']) $item['aucnt']=0;
    	});//->toArray();
    	$count = self::setWherePage(self::setWhere($where), $where, [], [])->alias('a')->join('system_store b', 'a.store_id=b.id')
    	->join('user c', 'c.uid=b.parent_id')->count();
    	return ['count' => $count, 'data' => $list];
    }
    
    public static function setWhere($where)
    {
    	$model = self::order('a.id asc');
    	if ($where['date'] != '') {
    		$model = $model->where('a.date',$where['date']);
    	}
    	if ($where['parent_id'] != '') $model = $model->where('b.parent_id', $where['parent_id']);
    	if ($where['shopname'] != '') $model = $model->where('b.name', 'like', "%$where[shopname]%");
    	return $model;
    }
    
}