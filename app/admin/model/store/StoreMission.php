<?php
/**
 * Created by PhpStorm.
 * User: lianghuan
 * Date: 2018-03-03
 * Time: 16:47
 */

namespace app\admin\model\store;

use app\admin\model\wechat\WechatUser;
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

    public static function payStatistics($date)
    {
        //任务单量
        $data['total_ocnt'] = self::where('date', $date)->sum('ocnt');
        //锁客任务量
        $data['total_ucnt'] = self::where('date', $date)->sum('ucnt');
        return compact('data');
    }

    /**
     * @param $where
     * @return array
     */
    public static function systemPage($where)
    {
        $model = new self;
        if ($where['date'] != '') {
            $model = $model->where('a.date',$where['date']);
        }
        if ($where['parent_id'] != '') $model = $model->where('b.parent_id', $where['parent_id']);
        if ($where['shopname'] != '') $model = $model->where('b.name', 'like', "%$where[shopname]%");
        
        $model = $model->alias('a');
        $model = $model->field('a.*,b.name,b.parent_id,c.real_name');
        $model = $model->join('system_store b', 'b.id=a.store_id', 'LEFT');
        $model = $model->join('user c', 'c.uid=b.parent_id', 'LEFT');
        $model = $model->order('a.id desc');
        $list = self::page($model, $where);
        echo $list;
        foreach ($list as &$item) {
        	echo "fuck";
        }
        return $list;
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
    
    
    public static function getMissionInfo($id = 0,$datetime){
    	if ($id){
    		$mInfo = self::where('store_id', $id)->where('date',$datetime)->find();
    	}else{
    		$mInfo = [];
    	}
    	return $mInfo;
    }
    
    /**
     * @param $where
     * @return array
     */
    public static function exportList($where)
    {
        $model = new self;
        if ($where['date'] != '') {
            list($startTime, $endTime) = explode(' - ', $where['date']);
            $model = $model->where('a.add_time', '>', strtotime($startTime));
            $model = $model->where('a.add_time', '<', (int)bcadd(strtotime($endTime), 86400, 0));
        }
        $model = $model->where('paid',1);
        if ($where['status'] != '') $model = $model->where('a.paid', $where['status']);
        if ($where['nireid'] != '') $model = $model->where('b.nickname|b.account|b.real_name|b.phone', 'like', "%$where[nireid]%");
        if ($where['shopname'] != '') $model = $model->where('c.name|c.mer_name', 'like', "%$where[shopname]%");
    
        $model = $model->alias('a');
        $model = $model->field('a.*,b.nickname,c.mer_name');
        $model = $model->join('user b', 'b.uid=a.uid', 'LEFT');
        $model = $model->join('system_store c', 'c.id=a.store_id', 'LEFT');
        $model = $model->order('a.id desc');
        
        $data = ($data = $model->select()) && count($data) ? $data->toArray() : [];
        $export = [];
        foreach ($data as $index => $item) {
            if($item['pay_type']=='yue'){
                $item['pay_type']='余额支付';
            }else{
                $item['pay_type']='微信';
            }
            
            if($item['pay_flag']==0){
                $item['pay_give'] = 0;
                $item['coupon_amount'] = 0;
                $item['pay_point'] = 0;
            }else if($item['pay_flag']==1){
                $item['coupon_amount'] = 0;
                $item['pay_point'] = 0;
            }else if($item['pay_flag']==2){
                $item['coupon_amount'] = 0;
                $item['pay_give'] = 0;
            }else if($item['pay_flag']==3){
                $item['pay_point'] = 0;
                $item['pay_give'] = 0;
            }
            $export[] = [
                $item['order_id'],
                ' 用户昵称:'.$item['nickname'] .=' /用户id:'. $item['uid'],
                $item['mer_name'],
                '￥'.$item['total_amount'],
                '￥'.$item['pay_amount'],
                $item['pay_give'],
                $item['pay_point'],
                $item['coupon_amount'],
                $item['pay_type'],
                date('Y-m-d',$item['add_time'])
            ];
        }
        PHPExcelService::setExcelHeader(['订单号', '用户信息', '商户名称', '消费总额', '实际支付', '购物积分抵扣','消费积分抵扣', '抵扣券抵扣', '支付方式'
            , '消费时间'])
            ->setExcelTile('佰仟万平台用户消费台账', '消费信息' . time(), ' 生成台账时间：' . date('Y-m-d H:i:s', time()))
            ->setExcelContent($export)
            ->ExcelSave();
        }
}