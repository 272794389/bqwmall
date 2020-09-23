<?php
namespace app\models\store;
use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;
use app\models\user\User;
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
}