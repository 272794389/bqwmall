<?php

namespace app\api\controller\user;

use app\admin\model\system\SystemAttachment;
use app\models\routine\RoutineCode;
use app\models\routine\RoutineQrcode;
use app\models\store\StoreOrder;
use app\models\user\User;
use app\models\user\StorePayLog;
use app\models\user\UserExtract;
use app\Request;
use crmeb\services\GroupDataService;
use crmeb\services\SystemConfigService;
use crmeb\services\UtilService;
use crmeb\services\upload\Upload;

/**
 * 用户资金变动类
 * Class UserBillController
 * @package app\api\controller\user
 */
class StorePayLogController
{
    
    
    /**
     * 余额明细
     * @param Request $request
     * @param $type 0 全部  1 消费  2 收入
     * @return mixed
     */
    public function yu_record(Request $request, $type)
    {
        list($page, $limit) = UtilService::getMore([
            ['page', 0],
            ['limit', 0],
        ], $request, true);
        return app('json')->successful(StorePayLog::getUserRecordList($request->uid(), $page, $limit, $type));
    }
    
    /**
     * 货款明细
     * @param Request $request
     * @param $type 0 全部  1 消费  2 收入
     * @return mixed
     */
    public function huo_record(Request $request, $type)
    {
        list($page, $limit) = UtilService::getMore([
            ['page', 0],
            ['limit', 0],
        ], $request, true);
        return app('json')->successful(StorePayLog::getHuoRecordList($request->uid(), $page, $limit, $type));
    }
    
    /**
     * 购物积分明细
     * @param Request $request
     * @param $type 0 全部  1 消费  2 收入
     * @return mixed
     */
    public function give_record(Request $request, $type)
    {
        list($page, $limit) = UtilService::getMore([
            ['page', 0],
            ['limit', 0],
        ], $request, true);
        return app('json')->successful(StorePayLog::getGiveRecordList($request->uid(), $page, $limit, $type));
    }
    
    /**
     * 消费积分明细
     * @param Request $request
     * @param $type 0 全部  1 消费  2 收入
     * @return mixed
     */
    public function paypoint_record(Request $request, $type)
    {
        list($page, $limit) = UtilService::getMore([
            ['page', 0],
            ['limit', 0],
        ], $request, true);
        return app('json')->successful(StorePayLog::getPayRecordList($request->uid(), $page, $limit, $type));
    }
    
    /**
     * 重消明细
     * @param Request $request
     * @param $type 0 全部  1 消费  2 收入
     * @return mixed
     */
    public function repoint_record(Request $request, $type)
    {
        list($page, $limit) = UtilService::getMore([
            ['page', 0],
            ['limit', 0],
        ], $request, true);
        return app('json')->successful(StorePayLog::getReRecordList($request->uid(), $page, $limit, $type));
    }
    
    
    

}