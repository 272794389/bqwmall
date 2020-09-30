<?php
namespace app\models\user;

use think\facade\Cache;
use crmeb\traits\ModelTrait;
use crmeb\basic\BaseModel;
class UserBank extends BaseModel
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
    protected $name = 'bank';

    use ModelTrait;
    
    
    public static function createBank($uid,$bankname,$bank_address,$name,$cardnum){
        $data = [
            'uid' => $uid,
            'bankname'=>$bankname,
            'bank_address'=>$bank_address,
            'uname'=>$name,
            'cardnum'=>$cardnum,
            ];
        self::create($data);
    }
    
    
}