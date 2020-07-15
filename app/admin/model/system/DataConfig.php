<?php
/**
 * @author: xaboy<365615158@qq.com>
 * @day: 2017/11/11
 */

namespace app\admin\model\system;

use crmeb\basic\BaseModel;
use crmeb\services\PHPExcelService;
use crmeb\traits\ModelTrait;

use app\admin\model\ump\{StoreBargain, StoreCombination, StoreSeckill};

/**
 * 产品管理 model
 * Class StoreProduct
 * @package app\admin\model\store
 */
class DataConfig extends BaseModel
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
    protected $name = 'data_config';

    use ModelTrait;
}