<?php


namespace app\models\system;

use crmeb\traits\ModelTrait;
use crmeb\basic\BaseModel;


class SystemMerchant extends BaseModel
{
    use ModelTrait;
    /**
     * 数据表主键
     * @var string
     */
    protected $pk = 'id';

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'system_merchant';

    public static function saveCreate($merchant, $uid)
    {
        return self::create([
            'add_time' => time(),
            'name' => $merchant['name'],
            'link_name' => $merchant['link_name'],
            'phone' => $merchant['phone'],
            'user_id' => $uid,
        ]);
    }

    /**
     * 获取当前用户下的企业
     * @param $uid
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getUserMer($uid){
       return self::where('user_id',$uid)->find();
    }
}