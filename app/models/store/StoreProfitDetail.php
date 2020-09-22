<?php
namespace app\models\store;
use crmeb\basic\BaseModel;
use crmeb\services\MiniProgramService;
use crmeb\services\WechatService;
use crmeb\repositories\ShortLetterRepositories;
use crmeb\traits\ModelTrait;
use app\models\user\User;
use app\models\system\SystemStore;
use app\models\user\StorePayLog;
use app\models\user\WechatUser;
use app\admin\model\system\DataConfig;
use think\facade\Db;
use crmeb\services\{ SystemConfigService, WechatTemplateService, workerman\ChannelService};
use crmeb\repositories\{ PaymentRepositories, OrderRepository};
use think\facade\Route as Url;

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