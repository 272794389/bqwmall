<?php


namespace app\admin\model\system;

use app\admin\model\user\User;
use crmeb\traits\ModelTrait;
use crmeb\basic\BaseModel;
use crmeb\services\PHPExcelService;

/**
 * 门店自提 model
 * Class SystemStore
 * @package app\admin\model\system
 */
class SystemStore extends BaseModel
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
    protected $name = 'system_store';


    public static function getLatlngAttr($value, $data)
    {
        return $data['latitude'] . ',' . $data['longitude'];
    }

    public static function verificWhere()
    {
        return self::where('is_del', 0);
    }

    /**
     * 获取门店信息
     * @param int $id
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getStoreDispose($id = 0)
    {
        if ($id)
            $storeInfo = self::verificWhere()->where('id', $id)->find();
        else
//            $storeInfo = self::verificWhere()->find();
            $storeInfo = [];
        if ($storeInfo) {
            $storeInfo['latlng'] = self::getLatlngAttr(null, $storeInfo);
            $storeInfo['valid_time'] = $storeInfo['valid_time'] ? explode(' - ', $storeInfo['valid_time']) : [];
            $storeInfo['day_time'] = $storeInfo['day_time'] ? explode(' - ', $storeInfo['day_time']) : [];
            $storeInfo['address'] = $storeInfo['address'] ? explode(',', $storeInfo['address']) : [];
        } else {
            $storeInfo['latlng'] = [];
            $storeInfo['valid_time'] = [];
            $storeInfo['valid_time'] = [];
            $storeInfo['day_time'] = [];
            $storeInfo['address'] = [];
            $storeInfo['id'] = 0;
        }
        return $storeInfo;
    }

    /**
     * 获取门店列表
     * @param $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getStoreList($where)
    {
        $model = new self();
        if (isset($where['name']) && $where['name'] != '') {
            $model = $model->where('id|name|introduction', 'like', '%' . $where['name'] . '%');
        }
        if (isset($where['type']) && $where['type'] != '' && ($data = self::setData($where['type']))) {
            $model = $model->where($data);
        }
        $count = $model->count();
        if ($where['excel'] == 0) $model = $model->page((int)$where['page'], (int)$where['limit']);
        $data = ($data = $model->order('id desc')->select()) && count($data) ? $data->toArray() : [];
        
       
        //$data = $model->page((int)$where['page'], (int)$where['limit'])->order('id desc')->select();
        if ($where['excel'] == 1) {
            foreach ($data as &$item) {
                if($item['belong_t']==0){
                    $item['belong_name'] = "商品中心";
                }else if($item['belong_t']==1){
                    $item['belong_name'] = "网店";
                }else if($item['belong_t']==2){
                    $item['belong_name'] = "周边的店";
                }else if($item['belong_t']==3){
                    $item['belong_name'] = "服务中心";
                }
                $shopuser = User::where('uid',$item['user_id'])->find();
                if($shopuser['spread_uid']>0){
                    $spreadInfo = User::where('uid',$shopuser['spread_uid'])->find();
                    $item['yewu']="业务员id:【".$shopuser['spread_uid']."】,姓名：【".$spreadInfo['real_name']."】,电话：【".$spreadInfo['phone']."】";
                }else{
                    $item['yewu']="无业务员";
                }
            }
            $export = [];
            foreach ($data as $index => $item) {
                $export[] = [
                    $item['name'],
                    $item['link_name'],
                    $item['link_phone'],
                    $item['address'] .= ' ' . $item['detailed_address'],
                    $item['city'],
                    $item['district'],
                    $item['sett_rate'].'%',
                    $item['give_rate'].'%',
                    $item['pay_rate'].'%',
                    $item['belong_name'],
                    $item['sales'],
                    $item['label'],
                    $item['termDate'].= ' ' . $item['day_time'],
                    date('Y-m-d',$item['add_time']),
                    $item['yewu']
                ];
            }
            PHPExcelService::setExcelHeader(['商户名称', '联系人', '联系电话', '详细地址', '所在城市', '所在地区', '分成比例', '购物积分支付比例'
                , '赠送消费积分比例', '商户类型', '已消费笔数', '标签', '营业时间', '上线时间', '业务员'])
                ->setExcelTile('佰仟万平台入驻商户台账', '商户信息' . time(), ' 生成台账时间：' . date('Y-m-d H:i:s', time()))
                ->setExcelContent($export)
                ->ExcelSave();
        }
        
        return compact('count', 'data');
    }

    /**
     * 获取连表查询条件
     * @param $type
     * @return array
     */
    public static function setData($type)
    {
        switch ((int)$type) {
            case 1:
                $data = ['status' => 1, 'is_del' => 0];
                break;
            case 2:
                $data = ['status' => 0, 'is_del' => 0];
                break;
            case 3:
                $data = ['is_del' => 1];
                break;
        };
        return isset($data) ? $data : [];
    }

    public static function dropList()
    {
        $model = new self();
        $model = $model->where('is_del', 0);
        $list = $model->select()
            ->toArray();
        return $list;
    }
}