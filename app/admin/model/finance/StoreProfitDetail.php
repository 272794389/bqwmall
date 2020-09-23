<?php
namespace app\admin\model\finance;

use crmeb\traits\ModelTrait;
use crmeb\basic\BaseModel;
use app\admin\model\user\User;
use crmeb\services\PHPExcelService;

/**
 * TODO 用户到商家消费
 * Class UserRecharge
 * @package app\models\user
 */
class StoreProfitDetail extends BaseModel
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
    protected $name = 'profit_detail';

    use ModelTrait;

    protected $insert = ['add_time'];

    protected function setAddTimeAttr()
    {
        return time();
    }
    
    
    public static function getList($where)
    {
        $data = ($data = self::setWhereList($where)->page((int)$where['page'], (int)$where['limit'])->select()) && count($data) ? $data->toArray() : [];
        $count = self::setWhereList($where)->count();
        return compact('data', 'count');
    }
    
    public static function SaveExport($where)
    {
        $data = ($data = self::setWhereList($where)->select()) && count($data) ? $data->toArray() : [];
        $export = [];
        foreach ($data as $value) {
            $belong_t = '';
            if($value['flag']==0){
                $belong_t = '消费订单';
            }else{
               $belong_t = '购物订单';
            }
            $export[] = [
                $value['idno'],
                $belong_t,
                $value['total_amount'],
                $value['pay_amount'],
                $value['huokuan'],
                $value['pointer'],
                $value['coupon_amount'],
                $value['shopaward'],
                $value['faward'],
                $value['saward'],
                $value['fagent'],
                $value['sagent'],
                $value['fprerent'],
                $value['sprerent'],
                $value['out_amount'],
                $value['fee'],
                $value['profit'],
                $value['add_time'],
            ];
        }
        PHPExcelService::setExcelHeader(['订单号', '订单类型', '交易额', '实际支付', '货款','积分抵扣','抵扣券抵扣','商家推荐奖励','一代推荐奖励','二代推荐奖励', '市级代理分红', '地区代理分红', '市级总监分红', '地区总监分红', '运营成本', '手续费', '平台利润', '创建时间'])
        ->setExcelTile('交易分配记录表', '交易分配记录表', date('Y-m-d H:i:s', time()))
        ->setExcelContent($export)
        ->ExcelSave();
    }
    
    
    public static function setWhereList($where)
    {
        $time['data'] = '';
        if ($where['start_time'] != '' && $where['end_time'] != '') {
            $time['data'] = $where['start_time'] . ' - ' . $where['end_time'];
        }
        $model = self::getModelTime($time, self::alias('A')->order('A.add_time desc'), 'A.add_time');
        if (trim($where['belong_t']) != '') {//记录类型
            $model = $model->where('A.flag', $where['belong_t']);
        }
        
        if ($where['idno'] != '') {
            $model = $model->where('A.idno', 'like', "%$where[idno]%");
        }
        return $model->field(['A.*', 'FROM_UNIXTIME(A.add_time,"%Y-%m-%d %H:%i:%s") as add_time']);
    }
    
}