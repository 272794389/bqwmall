<?php
/**
 * Created by PhpStorm.
 * User: chenmingsheng
 * Date: 2020-07-28
 * Time: 16:37
 */

namespace app\admin\controller\store;

use app\admin\controller\AuthController;
use think\facade\Route as Url;
use crmeb\services\JsonService;
use app\admin\model\store\StoreMission as StoreMissionModel;
use crmeb\services\{UtilService as Util, FormBuilder as Form, JsonService as Json};
use think\facade\Db;

/**
 * 用户提现管理
 * Class UserExtract
 * @package app\admin\controller\finance
 */
class StoreMission extends AuthController
{
    public function index()
    {
        return $this->fetch();
    }
    
    
    /**
     * 获取任务表
     *
     * @return json
     */
    public function get_shop_list()
    {
    	$where = Util::getMore([
    			['page', 1],
    			['limit', 20],
    			['parent_id', ''],
    			['shopname', ''],
    			['dtime', ''],
    	]);
    	$date = date('Y-m');
    	if($where['dtime']==2){
    		$date = date('Y-m', strtotime('-1 month'));
    	}else if($where['dtime']==1){
    		$date = date('Y-m');
    	}else{
    		$date = '';
    	}
    	$where['date'] = $date;
    	return Json::successlayui(StoreMissionModel::getUserList($where));
    }
}