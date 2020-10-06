<?php


namespace app\models\system;

use crmeb\traits\ModelTrait;
use crmeb\basic\BaseModel;
use app\models\store\StoreCategory;
use app\models\user\StorePayLog;
use app\models\user\User;

/**
 * 门店自提 model
 * Class SystemStore
 * @package app\model\system
 */
class SystemStore extends BaseModel
{
    const EARTH_RADIUS = 6371;

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
    
    protected function getSliderImageAttr($value)
    {
        $sliderImage = json_decode($value, true) ?: [];
        foreach ($sliderImage as &$item) {
            $item = str_replace('\\', '/', $item);
        }
        return $sliderImage;
    }


    public static function getLatlngAttr($value, $data)
    {
        return $data['latitude'] . ',' . $data['longitude'];
    }

    public static function verificWhere()
    {
        return self::where('is_show', 1)->where('is_del', 0);
    }

    /*
     * 获取门店信息
     * @param int $id
     * */
    public static function getStoreDispose($id = 0, $felid = '')
    {
        if ($id)
            $storeInfo = self::verificWhere()->where('id', $id)->find();
        else
            $storeInfo = self::verificWhere()->find();
        if ($storeInfo) {
            $storeInfo['latlng'] = self::getLatlngAttr(null, $storeInfo);
            $storeInfo['valid_time'] = $storeInfo['valid_time'] ? explode(' - ', $storeInfo['valid_time']) : [];
            $storeInfo['_valid_time'] = str_replace('-', '/', ($storeInfo['valid_time'][0] ?? '') . ' ~ ' . ($storeInfo['valid_time'][1] ?? ""));
            $storeInfo['day_time'] = $storeInfo['day_time'] ? str_replace(' - ', ' ~ ', $storeInfo['day_time']) : [];
            $storeInfo['_detailed_address'] = $storeInfo['detailed_address'];
            $storeInfo['address'] = $storeInfo['address'] ? explode(',', $storeInfo['address']) : [];
            if ($felid) return $storeInfo[$felid] ?? '';
        }
        return $storeInfo;
    }
    
    public static function getValidStore($id = 0, $field = '*')
    {
        $Store = self::where('id', $id)->field($field)->find();
        if ($Store) return $Store->toArray();
        else return false;
    }
    
    

    /**
     * 门店列表
     * @return mixed
     */
//    public static function lst()
//    {
//        $model = new self;
//        $model = $model->where('is_show', 1);
//        $model = $model->where('is_del', 0);
//        $model = $model->order('id DESC');
//        return $model->select();
//    }

    /**
     * 计算某个经纬度的周围某段距离的正方形的四个点
     *
     * @param lng float 经度
     * @param lat float 纬度
     * @param distance float 该点所在圆的半径，该圆与此正方形内切，默认值为2.5千米
     * @return array 正方形的四个点的经纬度坐标
     */
    public static function returnSquarePoint($lng, $lat, $distance = 200)
    {
        $dlng = 2 * asin(sin($distance / (2 * self::EARTH_RADIUS)) / cos(deg2rad($lat)));
        $dlng = rad2deg($dlng);
        $dlat = rad2deg($distance / self::EARTH_RADIUS);
        return [
            'left_top' => [
                'lat' => $lat + $dlat,
                'lng' => $lng - $dlng,
            ],
            'right_top' => [
                'lat' => $lat + $dlat,
                'lng' => $lng + $dlng
            ],
            'left_bottom' => [
                'lat' => $lat - $dlat,
                'lng' => $lng - $dlng
            ],
            'right_bottom' => [
                'lat' => $lat - $dlat,
                'lng' => $lng + $dlng
            ]
        ];
    }

    /*
     设置where条件
    */
    public static function nearbyWhere($model = null, $latitude = 0, $longitude = 0)
    {
        if (!is_object($model)) {
            $latitude = $model;
            $model = new self();
            $longitude = $latitude;
        }
        $field = "(round(6367000 * 2 * asin(sqrt(pow(sin(((latitude * pi()) / 180 - ({$latitude} * pi()) / 180) / 2), 2) + cos(({$latitude} * pi()) / 180) * cos((latitude * pi()) / 180) * pow(sin(((longitude * pi()) / 180 - ({$longitude} * pi()) / 180) / 2), 2))))) AS distance";
        $model->field($field);
        return $model;
    }

    /**
     * 获取排序sql
     * @param $latitude
     * @param $longitude
     * @return mixed
     */
    public static function distanceSql($latitude, $longitude)
    {
        $field = "(round(6367000 * 2 * asin(sqrt(pow(sin(((latitude * pi()) / 180 - ({$latitude} * pi()) / 180) / 2), 2) + cos(({$latitude} * pi()) / 180) * cos((latitude * pi()) / 180) * pow(sin(((longitude * pi()) / 180 - ({$longitude} * pi()) / 180) / 2), 2))))) AS distance";
        return $field;
    }
    
    public static function getDistance($latitude, $longitude,$distance){
        $field = "round(sqrt((pow((($latitude - latitude) * 111000), 2)) + (pow((($longitude - longitude) * 111000), 2)))/1000,1)<$distance ";
        return $field;
    }

    /**
     * 同城门店列表
     * @return mixed
     */
    public static function lst($latitude, $longitude, $page, $limit,$sid,$cid,$keyword,$salesOrder,$condition)
    {
        $model = new self();
        $model = $model->where('is_del', 0);
        $model = $model->where('is_show',1);
        $model = $model->where('status', 1);
        $model = $model->where('belong_t', 2);
        if($cid>0){
            $model = $model->where('cat_id',$cid);
        }
        if($sid>0){//大分类
            $model->whereIn('cat_id', function ($query) use ($sid) {
                $query->name('store_category')->where('pid', $sid)->field('id')->select();
            });
        }
        if (!empty($keyword)){
            $sql = [];
            $sql[] = '(`mer_name` LIKE "%' . $keyword . '%"  OR `introduction` LIKE "%' . $keyword . '%")';
            $model = $model->where(implode(' OR ', $sql));
            //$model->where('mer_name', 'LIKE', htmlspecialchars("%$keyword%"));
        }
        $baseOrder = '';
        if ($salesOrder) {
            $baseOrder = $salesOrder == 'desc' ? 'sales DESC' : 'sales ASC';
        }else{
            $baseOrder = $salesOrder == 'id DESC';
        }
       
        if ($latitude && $longitude) {
            if($condition==1){//默认50km
                $model = $model->where(self::getDistance($latitude, $longitude,50.1));
            }else if($condition==2){
                $model = $model->where(self::getDistance($latitude, $longitude,1.1));
            }else if($condition==3){
                $model = $model->where(self::getDistance($latitude, $longitude,5.1));
            }else if($condition==4){
                $model = $model->where(self::getDistance($latitude, $longitude,10.1));
            }else if($condition==5){
                $model = $model->where(self::getDistance($latitude, $longitude,20.1));
            }
           // $model = $model->where(self::getDistance($latitude, $longitude));
            $model = $model->field(['*', self::distanceSql($latitude, $longitude)])->order($baseOrder . 'distance asc');
        }
        $list = $model->page((int)$page, (int)$limit)
            ->select()
            ->hidden(['is_show', 'is_del'])
            ->toArray();
       
            foreach ($list as &$value) {
                if ($latitude && $longitude) {
                    //计算距离
                    $value['distance'] = sqrt((pow((($latitude - $value['latitude']) * 111000), 2)) + (pow((($longitude - $value['longitude']) * 111000), 2)));
                    //转换单位
                    $value['range'] = bcdiv($value['distance'], 1000, 1);
                }
                $value['cate_name'] = StoreCategory::where('id',$value['cat_id'])->value('cate_name'); 
            }
        return $list;
    }
    
    /**
     * 网店列表
     * @return mixed
     */
    public static function netlst($page, $limit,$sid,$cid,$keyword,$salesOrder,$city,$district)
    {
        $model = new self();
        $model = $model->where('is_del', 0);
        $model = $model->where('is_show',1);
        $model = $model->where('status', 1);
        if($cid>0){
            $model = $model->where('cat_id',$cid);
        }
        if($sid>0){//大分类
            $model->whereIn('cat_id', function ($query) use ($sid) {
                $query->name('store_category')->where('pid', $sid)->field('id')->select();
            });
        }
        if (!empty($keyword)) $model->where('mer_name', 'LIKE', htmlspecialchars("%$keyword%"));
        $baseOrder = '';
        if ($salesOrder) $baseOrder = $salesOrder == 'desc' ? 'sales DESC' : 'sales ASC';
        
        if($city!='全部省份'){
            if (!empty($city)) $model->where('city', 'LIKE', htmlspecialchars("%$city%"));
            if (!empty($district)) $model->where('district', 'LIKE', htmlspecialchars("%$district%"));
        }
       // $model = $model->where('belong_t',1);
        
        $list = $model->page((int)$page, (int)$limit)
        ->select()
        ->hidden(['is_show', 'is_del'])
        ->toArray();
        foreach ($list as &$value) {
            $value['cate_name'] = StoreCategory::where('id',$value['cat_id'])->value('cate_name');
        }
        return $list;
    }
    
    
    /**
     * 获取商户信息
     * @param $uid
     * @param string $orderBy
     * @param string $keyword
     * @param int $page
     * @param int $limit
     * @return array
     */
    /*
    public static function getShopSpreadCountList($uid, $orderBy = '', $keyword = '', $page = 0, $limit = 20)
    {
        $model = new self;
        if ($orderBy === '') $orderBy = 'u.add_time desc';
        $model = $model->alias(' u');
        $sql = StorePayLog::where('o.order_id','>', 0)->where('o.huokuan', '>', 0)->where('o.belong_t', '<', 2)->group('o.uid')->field(['SUM(o.huokuan) as numberCount,count(1) as orderCount', 'o.uid','o.huokuan', 'o.order_id'])
        ->alias('o')->fetchSql(true)->select();
        $model = $model->join("(" . $sql . ") p", 'u.user_id = p.uid', 'LEFT');
        $model = $model->where('u.user_id', 'IN', $uid);
        $model = $model->field("u.user_id,u.mer_name,u.image,from_unixtime(u.add_time,'%Y/%m/%d') as time,p.numberCount,p.orderCount");
        if (strlen(trim($keyword))) $model = $model->where('u.mer_name', 'like', "%$keyword%");
        $model = $model->group('u.user_id');
        $model = $model->order($orderBy);
        $model = $model->page($page, $limit);
        $list = $model->select();
        if ($list) return $list->toArray();
        else return [];
    }*/
    
    
    public static function getShopSpreadCountList($uid, $orderBy = '', $keyword = '', $page = 0, $limit = 20)
    {
        $model = new self;
        if ($orderBy === '') $orderBy = 'u.add_time desc';
        $model = $model->alias(' u');
        $sql = StorePayLog::where('o.order_id','>', 0)->where('o.huokuan', '>', 0)->where('o.belong_t', '<', 2)->group('o.uid')->field(['SUM(o.huokuan) as numberCount,count(1) as orderCount', 'o.uid','o.huokuan', 'o.order_id'])
        ->alias('o')->fetchSql(true)->select();
        $model = $model->join("(" . $sql . ") p", 'u.user_id = p.uid', 'LEFT');
        $model = $model->where('u.parent_id',  $uid);
        $model = $model->field("u.user_id,u.mer_name,u.image,from_unixtime(u.add_time,'%Y/%m/%d') as time,p.numberCount,p.orderCount");
        if (strlen(trim($keyword))) $model = $model->where('u.mer_name', 'like', "%$keyword%");
        $model = $model->group('u.id');
        $model = $model->order($orderBy);
        $model = $model->page($page, $limit);
        $list = $model->select()->each(function ($item) {
            $item['childCount'] = User::getUserSpreadCount($item['user_id']);//累计推荐人数
        });
        if ($list) return $list->toArray();
        else return [];
    }
    
    
    
    
    
    public static function getStoreList($merId){
        return self::where('mer_id',  $merId)->select();
    }
    
    public static function setShow($id, $status)
    {
        return self::where('id', $id)->update([
            'is_show' => $status
        ]);
    }
    
    public static function setDel($id, $status)
    {
        return self::where('id', $id)->update([
            'is_del' => $status
        ]);
    }
    
    public static function getUserMer($uid){
        return self::where('user_id',$uid)->find();
    }
    

}