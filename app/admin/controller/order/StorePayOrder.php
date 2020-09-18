<?php
/**
 * Created by PhpStorm.
 * User: chenmingsheng
 * Date: 2020-07-28
 * Time: 16:37
 */

namespace app\admin\controller\order;

use app\admin\controller\AuthController;
use think\facade\Route as Url;
use crmeb\services\JsonService;
use app\admin\model\order\StorePayOrder as StorePayOrderModel;
use crmeb\services\{UtilService as Util, FormBuilder as Form};
use think\facade\Db;

/**
 * 用户提现管理
 * Class UserExtract
 * @package app\admin\controller\finance
 */
class StorePayOrder extends AuthController
{
    public function index()
    {
        $where = Util::getMore([
            ['status', ''],
            ['nickname', ''],
            ['shopname', ''],
            ['extract_type', ''],
            ['nireid', ''],
            ['date', ''],
            ['export', 0],
        ], $this->request);
        $limitTimeList = [
            'today' => implode(' - ', [date('Y/m/d'), date('Y/m/d', strtotime('+1 day'))]),
            'week' => implode(' - ', [
                date('Y/m/d', (time() - ((date('w') == 0 ? 7 : date('w')) - 1) * 24 * 3600)),
                date('Y-m-d', (time() + (7 - (date('w') == 0 ? 7 : date('w'))) * 24 * 3600))
            ]),
            'month' => implode(' - ', [date('Y/m') . '/01', date('Y/m') . '/' . date('t')]),
            'quarter' => implode(' - ', [
                date('Y') . '/' . (ceil((date('n')) / 3) * 3 - 3 + 1) . '/01',
                date('Y') . '/' . (ceil((date('n')) / 3) * 3) . '/' . date('t', mktime(0, 0, 0, (ceil((date('n')) / 3) * 3), 1, date('Y')))
            ]),
            'year' => implode(' - ', [
                date('Y') . '/01/01', date('Y/m/d', strtotime(date('Y') . '/01/01 + 1year -1 day'))
            ])
        ];
        
        $this->assign('where', $where);
        $this->assign('limitTimeList', $limitTimeList);
        $this->assign(StorePayOrderModel::payStatistics());
        $this->assign(StorePayOrderModel::systemPage($where));
        if($where['export']==1){
            StorePayOrderModel::exportList($where);
        }
        
        
        return $this->fetch();
    }
}