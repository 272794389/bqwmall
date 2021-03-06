<?php
/**
 *
 * @author: xaboy<365615158@qq.com>
 * @day: 2017/12/12
 */

namespace app\models\store;

use app\admin\model\store\StoreProductAttrValue as StoreProductAttrValueModel;
use app\models\system\SystemUserLevel;
use app\models\system\SystemStore;

use app\models\user\UserLevel;
use crmeb\basic\BaseModel;
use crmeb\services\GroupDataService;
use crmeb\services\workerman\ChannelService;
use crmeb\traits\ModelTrait;
use app\models\store\{
    StoreBargain, StoreCombination, StoreSeckill
};

/**
 * TODO 产品Model
 * Class StoreProduct
 * @package app\models\store
 */
class StoreProduct extends BaseModel
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
    protected $name = 'store_product';

    use  ModelTrait;

    protected function getSliderImageAttr($value)
    {
        $sliderImage = json_decode($value, true) ?: [];
        foreach ($sliderImage as &$item) {
            $item = str_replace('\\', '/', $item);
        }
        return $sliderImage;
    }

    protected function getImageAttr($value)
    {
        return str_replace('\\', '/', $value);
    }

    public function getDescriptionAttr($value)
    {
        return htmlspecialchars_decode($value);
    }

    public static function getValidProduct($productId, $field = 'add_time,browse,cate_id,code_path,cost,ficti,give_integral,id,image,is_sub,is_bargain,is_benefit,is_best,is_del,is_hot,is_new,is_postage,is_seckill,is_show,store_id,keyword,mer_id,mer_use,ot_price,postage,price,sales,slider_image,sort,stock,store_info,store_name,unit_name,vip_price,IFNULL(sales,0) + IFNULL(ficti,0) as fsales,video_link,belong_t,give_point,pay_point,pay_amount,pay_paypoint,pay_repeatpoint,give_rate,hex_t,coupon_price')
    {
        $Product = self::where('is_del', 0)->where('is_show', 1)->where('id', $productId)->field($field)->find();
        if ($Product) return $Product->toArray();
        else return false;
    }
    public static function getGoodList($limit = 18, $field = '*')
    {
        $list = self::validWhere()->where('is_good', 1)->order('sort desc,id desc')->limit($limit)->field($field)->select();
        $list = count($list) ? $list->toArray() : [];
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                $list[$k]['activity'] = self::activity($v['id']);
            }
        }
        return $list;
    }
    
    public static function getStoreGoodList($store_id,$limit = 18, $field = '*')
    {
        $list = self::validWhere()->where('store_id', $store_id)->order('sort desc,id desc')->limit($limit)->field($field)->select();
        $list = count($list) ? $list->toArray() : [];
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                $list[$k]['activity'] = self::activity($v['id']);
            }
        }
        return $list;
    }
    
    public static function getListByBelong($belong_t,$cat_id,$limit = 18, $field = '*'){
        
        $list = self::validWhere()->where('belong_t', $belong_t)->where('cate_id',$cat_id)->order('sort desc')->limit($limit)->field($field)->select();
        $list = count($list) ? $list->toArray() : [];
       
        return $list;
    }
    
    public static function getTuiList($limit = 18, $field = '*'){
        $list = self::validWhere()->where('is_best', 1)->order('sort desc')->limit($limit)->field($field)->select();
        $list = count($list) ? $list->toArray() : [];
        return $list;  
    }
    
    
    
    // 批量获取商品
    public static function getListByIds($ids = []){
        $rs =self::where('id','in',$ids)->select();
        return empty($rs) ?[]:$rs->toArray();
    
    }

    public static function validWhere()
    {
        return self::where('is_del', 0)->where('is_show', 1)->where('mer_id', 0);
    }
    
    
    /**
     * 商品统计数据
     * @param $page
     * @param $limit
     * @return array
     */
    public static function getProductData($store_id)
    {
        $model = new self;
        $model = $model->field('count(id) as pcount');
        $model = $model->where('is_show', 1);
        $model = $model->where('store_id', $store_id);
        $proinfo = $model->find();
         $model = new self;
         $model = $model->field('count(id) as count');
         $model = $model->where('is_show', 0);
         $model = $model->where('store_id', $store_id);
         $xproinfo = $model->find();
         $proinfo['xcount'] = $xproinfo['count'];
        return ['proinfo'=>$proinfo];
    }
    
    /**
     * 上下架商品
     * @param string product_id 商品id
     * @param $uid
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function cancelProduct($order_id, $uid)
    {
        $product = self::where('id', $order_id)->find();
        if (!$product) return self::setErrorInfo('没有查到此商品');
        $store_id = $product['store_id'];
        $is_show = $product['is_show'];
        if($is_show>0){
            $is_show=0;
        }else{
            $is_show=1;
        }
        $storeinfo = SystemStore::where('id', $store_id)->where('user_id', $uid)->find();
        if (!$storeinfo) return self::setErrorInfo('无权限操作');
        $product->is_show = $is_show;
        if($product->save()){
            return true;
        }else{
            return false;
        }
    }
    
    
    /**
     * 前台订单管理订单列表获取
     * @param $where
     * @return mixed
     */
    public static function productList($uid,$where)
    {
        $model = new self;
        $merList =   StoreService::getAdminMerList($uid);
        $model = $model->where('store_id', 'in', $merList);
        $model = $model->where('is_show', $where['type']);
        $model = $model->order('id desc');
        $data = ($data = $model->page((int)$where['page'], (int)$where['limit'])->select()) && count($data) ? $data->toArray() : [];
        return $data;
    }

    public static function getProductList($data, $uid)
    {
        $sId = $data['sid'];
        $cId = $data['cid'];
        $keyword = $data['keyword'];
        $priceOrder = $data['priceOrder'];
        $salesOrder = $data['salesOrder'];
        $news = $data['news'];
        $belong_t = $data['belong_t'];
        $condition=$data['condition'];
        $page = $data['page'];
        $limit = $data['limit'];
        $type = $data['type']; // 某些模板需要购物车数量 1 = 需要查询，0 = 不需要
        $model = self::validWhere();
        if ($cId) {
            $model->whereIn('id', function ($query) use ($cId) {
                $query->name('store_product_cate')->where('cate_id', $cId)->field('product_id')->select();
            });
        } elseif ($sId) {
            $model->whereIn('id', function ($query) use ($sId) {
                $query->name('store_product_cate')->whereIn('cate_id', function ($q) use ($sId) {
                    $q->name('store_category')->where('pid', $sId)->field('id')->select();
                })->field('product_id')->select();
            });
        }
        if (!empty($keyword)) $model->where('keyword|store_name', 'LIKE', htmlspecialchars("%$keyword%"));
        if ($news != 0) $model->where('is_new', 1);
        if($belong_t>0){
            $model->where('belong_t', $belong_t);
        }
        /*
        if($condition==1){//周边
            $model->where('belong_t', 2);
        }else if($condition==2){//消费积分兑换
            $model->where('pay_paypoint', '>',0);
        }else if($condition==3){//重消积分兑换
            $model->where('pay_repeatpoint', '>',0);
        }else if($condition==4){//网店商品
            $model->where('belong_t', 2);
        }*/
        $baseOrder = '';
        if ($priceOrder) $baseOrder = $priceOrder == 'desc' ? 'price DESC' : 'price ASC';
//        if($salesOrder) $baseOrder = $salesOrder == 'desc' ? 'sales DESC' : 'sales ASC';//真实销量
        if ($salesOrder) $baseOrder = $salesOrder == 'desc' ? 'sales DESC' : 'sales ASC';//虚拟销量
        if ($baseOrder) $baseOrder .= ', ';
        $model->order($baseOrder . 'sort DESC, add_time DESC');
        $list = $model->page((int)$page, (int)$limit)->field('id,store_name,cate_id,image,IFNULL(sales,0) + IFNULL(ficti,0) as sales,price,stock,pay_amount,pay_paypoint,pay_repeatpoint,give_rate,ot_price,give_point,pay_point,belong_t')->select()->each(function ($item) use ($uid, $type) {
            if ($type) {
                $item['is_att'] = StoreProductAttrValueModel::where('product_id', $item['id'])->count() ? true : false;
                if ($uid) $item['cart_num'] = StoreCart::where('is_pay', 0)->where('is_del', 0)->where('is_new', 0)->where('type', 'product')->where('product_id', $item['id'])->where('uid', $uid)->value('cart_num');
                else $item['cart_num'] = 0;
                if (is_null($item['cart_num'])) $item['cart_num'] = 0;
            }
        });
        $list = count($list) ? $list->toArray() : [];
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                $list[$k]['activity'] = self::activity($v['id']);
            }
        }
        return self::setLevelPrice($list, $uid);
    }
    
    
    public static function getGoodsProductList($data, $uid)
    {
        $sId = $data['sid'];
        $cId = $data['cid'];
        $keyword = $data['keyword'];
        $priceOrder = $data['priceOrder'];
        $salesOrder = $data['salesOrder'];
        $news = $data['news'];
        $belong_t = $data['belong_t'];
        $condition=$data['condition'];
        $page = $data['page'];
        $limit = $data['limit'];
        $type = $data['type']; // 某些模板需要购物车数量 1 = 需要查询，0 = 不需要
        $model = self::validWhere();
        
        if ($cId) {
            $model->whereIn('id', function ($query) use ($cId) {
                $query->name('store_product_cate')->where('cate_id', $cId)->field('product_id')->select();
            });
        } elseif ($sId) {
            $model->whereIn('id', function ($query) use ($sId) {
                $query->name('store_product_cate')->whereIn('cate_id', function ($q) use ($sId) {
                    $q->name('store_category')->where('pid', $sId)->field('id')->select();
                })->field('product_id')->select();
            });
        }
        if (!empty($keyword)) $model->where('keyword|store_name', 'LIKE', htmlspecialchars("%$keyword%"));
        if ($news != 0) $model->where('is_new', 1);
        $model->where('belong_t', $belong_t);
        /*
         if($condition==1){//周边
         $model->where('belong_t', 2);
         }else if($condition==2){//消费积分兑换
         $model->where('pay_paypoint', '>',0);
         }else if($condition==3){//重消积分兑换
         $model->where('pay_repeatpoint', '>',0);
         }else if($condition==4){//网店商品
         $model->where('belong_t', 2);
         }*/
        $baseOrder = '';
        if ($priceOrder) $baseOrder = $priceOrder == 'desc' ? 'price DESC' : 'price ASC';
        //        if($salesOrder) $baseOrder = $salesOrder == 'desc' ? 'sales DESC' : 'sales ASC';//真实销量
        if ($salesOrder) $baseOrder = $salesOrder == 'desc' ? 'sales DESC' : 'sales ASC';//虚拟销量
        if ($baseOrder) $baseOrder .= ', ';
        $model->order($baseOrder . 'sort DESC, add_time DESC');
        $list = $model->page((int)$page, (int)$limit)->field('id,store_name,cate_id,image,IFNULL(sales,0) + IFNULL(ficti,0) as sales,ot_price,price,stock,pay_amount,pay_paypoint,pay_repeatpoint,give_rate,give_point,pay_point,belong_t,coupon_price,unit_name')->select()->each(function ($item) use ($uid, $type) {
            if ($type) {
                $item['is_att'] = StoreProductAttrValueModel::where('product_id', $item['id'])->count() ? true : false;
                if ($uid) $item['cart_num'] = StoreCart::where('is_pay', 0)->where('is_del', 0)->where('is_new', 0)->where('type', 'product')->where('product_id', $item['id'])->where('uid', $uid)->value('cart_num');
                else $item['cart_num'] = 0;
                if (is_null($item['cart_num'])) $item['cart_num'] = 0;
            }
        });
            $list = count($list) ? $list->toArray() : [];
            if (!empty($list)) {
                foreach ($list as $k => $v) {
                    $list[$k]['activity'] = self::activity($v['id']);
                }
            }
            return self::setLevelPrice($list, $uid);
    }
    
    public static function getNetGoodsProductList($data, $uid)
    {
        $sId = $data['sid'];
        $cId = $data['cid'];
        $keyword = $data['keyword'];
        $priceOrder = $data['priceOrder'];
        $salesOrder = $data['salesOrder'];
        $news = $data['news'];
        $belong_t = $data['belong_t'];
        $condition=$data['condition'];
        $page = $data['page'];
        $limit = $data['limit'];
        $type = $data['type']; // 某些模板需要购物车数量 1 = 需要查询，0 = 不需要
        $model = self::validWhere();
    
        if ($cId) {
            $model->whereIn('id', function ($query) use ($cId) {
                $query->name('store_product_cate')->where('cate_id', $cId)->field('product_id')->select();
            });
        } elseif ($sId) {
            $model->whereIn('id', function ($query) use ($sId) {
                $query->name('store_product_cate')->whereIn('cate_id', function ($q) use ($sId) {
                    $q->name('store_category')->where('pid', $sId)->field('id')->select();
                })->field('product_id')->select();
            });
        }
        if (!empty($keyword)) $model->where('keyword|store_name', 'LIKE', htmlspecialchars("%$keyword%"));
        if ($news != 0) $model->where('is_new', 1);
        $model->where('belong_t','<', 2);
        /*
         if($condition==1){//周边
         $model->where('belong_t', 2);
         }else if($condition==2){//消费积分兑换
         $model->where('pay_paypoint', '>',0);
         }else if($condition==3){//重消积分兑换
         $model->where('pay_repeatpoint', '>',0);
         }else if($condition==4){//网店商品
         $model->where('belong_t', 2);
         }*/
        $baseOrder = '';
        if ($priceOrder) $baseOrder = $priceOrder == 'desc' ? 'price DESC' : 'price ASC';
        //        if($salesOrder) $baseOrder = $salesOrder == 'desc' ? 'sales DESC' : 'sales ASC';//真实销量
        if ($salesOrder) $baseOrder = $salesOrder == 'desc' ? 'sales DESC' : 'sales ASC';//虚拟销量
        if ($baseOrder) $baseOrder .= ', ';
        $model->order($baseOrder . 'sort DESC, add_time DESC');
        $list = $model->page((int)$page, (int)$limit)->field('id,store_name,cate_id,image,IFNULL(sales,0) + IFNULL(ficti,0) as sales,ot_price,price,stock,pay_amount,pay_paypoint,pay_repeatpoint,give_rate,give_point,pay_point,belong_t,coupon_price,unit_name')->select()->each(function ($item) use ($uid, $type) {
            if ($type) {
                $item['is_att'] = StoreProductAttrValueModel::where('product_id', $item['id'])->count() ? true : false;
                if ($uid) $item['cart_num'] = StoreCart::where('is_pay', 0)->where('is_del', 0)->where('is_new', 0)->where('type', 'product')->where('product_id', $item['id'])->where('uid', $uid)->value('cart_num');
                else $item['cart_num'] = 0;
                if (is_null($item['cart_num'])) $item['cart_num'] = 0;
            }
        });
            $list = count($list) ? $list->toArray() : [];
            if (!empty($list)) {
                foreach ($list as $k => $v) {
                    $list[$k]['activity'] = self::activity($v['id']);
                }
            }
            return self::setLevelPrice($list, $uid);
    }
    

    /*
     * 分类搜索
     * @param string $value
     * @return array
     * */
    public static function getSearchStorePage($keyword, $page, $limit, $uid, $cutApart = [' ', ',', '-'])
    {
        $model = self::validWhere();
        $keyword = trim($keyword);
        if (strlen($keyword)) {
            $cut = false;
            foreach ($cutApart as $val) {
                if (strstr($keyword, $val) !== false) {
                    $cut = $val;
                    break;
                }
            }
            if ($cut !== false) {
                $keywordArray = explode($cut, $keyword);
                $sql = [];
                foreach ($keywordArray as $item) {
                    $sql[] = '(`store_name` LIKE "%' . $item . '%"  OR `keyword` LIKE "%' . $item . '%")';
                }
                $model = $model->where(implode(' OR ', $sql));
            } else {
                $model = $model->where('store_name|keyword', 'LIKE', "%$keyword%");
            }
        }
        $list = $model->field('id,store_name,cate_id,image,ficti as sales,price,stock')->page($page, $limit)->select();
        $list = count($list) ? $list->toArray() : [];
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                $list[$k]['activity'] = self::activity($v['id']);
            }
        }
        return self::setLevelPrice($list, $uid);
    }

    /**
     * 新品产品
     * @param string $field
     * @param int $limit
     * @return false|\PDOStatement|string|\think\Collection
     */
    public static function getNewProduct($field = '*', $limit = 0, $uid = 0, bool $bool = true)
    {
        if (!$limit && !$bool) return [];
        $model = self::where('is_new', 1)->where('is_del', 0)->where('mer_id', 0)
            ->where('stock', '>', 0)->where('is_show', 1)->field($field)
            ->order('sort DESC, id DESC');
        if ($limit) $model->limit($limit);
        $list = $model->select();
        $list = count($list) ? $list->toArray() : [];
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                $list[$k]['activity'] = self::activity($v['id']);
            }
        }
        return self::setLevelPrice($list, $uid);
    }

    /**
     * 热卖产品
     * @param string $field
     * @param int $limit
     * @return false|\PDOStatement|string|\think\Collection
     */
    public static function getHotProduct($field = '*', $limit = 0, $uid = 0)
    {
        $model = self::where('is_hot', 1)->where('is_del', 0)->where('mer_id', 0)
            ->where('stock', '>', 0)->where('is_show', 1)->field($field)
            ->order('sort DESC, id DESC');
        if ($limit) $model->limit($limit);
        $list = $model->select();
        $list = count($list) ? $list->toArray() : [];
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                $list[$k]['activity'] = self::activity($v['id']);
            }
        }
        return self::setLevelPrice($list, $uid);
    }
    

    /**
     * 热卖产品
     * @param string $field
     * @param int $page
     * @param int $limit
     * @return array|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getHotProductLoading($field = '*', $page = 0, $limit = 0)
    {
        if (!$limit) return [];
        $model = self::where('sales','>', 0)->where('is_del', 0)->where('mer_id', 0)
            ->where('stock', '>', 0)->where('is_show', 1)->field($field)
            ->order('sales DESC');
        if ($page) $model->page($page, $limit);
        $list = $model->select();
        if (is_object($list)) return $list->toArray();
        return $list;
    }

    
    public static function getProductListByBelong($limit = 0, $uid = 0,$belong_t=0, bool $bool = true)
    {
        if (!$limit && !$bool) return [];
        $model = self::where('is_del', 0)
            ->where('stock', '>', 0)->where('is_show', 1)->where('belong_t',$belong_t)->field("id,image,store_name,cate_id,price,ot_price,IFNULL(sales,0) + IFNULL(ficti,0) as sales,unit_name,pay_amount,pay_paypoint,pay_repeatpoint,give_rate,give_point,pay_point")
            ->order('sales DESC, id DESC');
        if ($limit) $model->limit($limit);
        $list = $model->select();
        $list = count($list) ? $list->toArray() : [];
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                $list[$k]['activity'] = self::activity($v['id']);
            }
        }
        return self::setLevelPrice($list, $uid);
    }
    
    public static function getProductIndexListByBelong($limit = 0, $uid = 0,$belong_t=0, bool $bool = true)
    {
    	if (!$limit && !$bool) return [];
    	$model = self::where('is_del', 0)
    	->where('stock', '>', 0)->where('is_best', 1)->where('is_show', 1)->where('belong_t',$belong_t)->field("id,image,store_name,cate_id,price,ot_price,IFNULL(sales,0) + IFNULL(ficti,0) as sales,unit_name,pay_amount,pay_paypoint,pay_repeatpoint,give_rate,give_point,pay_point")
    	->order('sales DESC, id DESC');
    	if ($limit) $model->limit($limit);
    	$list = $model->select();
    	$list = count($list) ? $list->toArray() : [];
    	if (!empty($list)) {
    		foreach ($list as $k => $v) {
    			$list[$k]['activity'] = self::activity($v['id']);
    		}
    	}
    	return self::setLevelPrice($list, $uid);
    }
    
    public static function getNetList($limit = 0,$uid = 0)
    {
        if (!$limit && !$bool) return [];
        $model = self::where('is_del', 0)
        ->where('stock', '>', 0)->where('is_show', 1)->where('belong_t',1)->field("id,image,store_name,cate_id,price,ot_price,IFNULL(sales,0) + IFNULL(ficti,0) as sales,unit_name,pay_amount,pay_paypoint,pay_repeatpoint,give_rate,give_point,pay_point")
        ->order('sort DESC');
        if ($limit) $model->limit($limit);
        $list = $model->select();
        $list = count($list) ? $list->toArray() : [];
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                $list[$k]['activity'] = self::activity($v['id']);
            }
        }
        return self::setLevelPrice($list, $uid);
    }
    
    public static function getNetIndexList($limit = 0,$uid = 0)
    {
    	if (!$limit && !$bool) return [];
    	$model = self::where('is_del', 0)
    	->where('stock', '>', 0)->where('is_hot','<>', 2)->where('is_show', 1)->where('belong_t',1)->field("id,image,store_name,cate_id,price,ot_price,IFNULL(sales,0) + IFNULL(ficti,0) as sales,unit_name,pay_amount,pay_paypoint,pay_repeatpoint,give_rate,give_point,pay_point")
    	->order('sort DESC');
    	if ($limit) $model->limit($limit);
    	$list = $model->select();
    	$list = count($list) ? $list->toArray() : [];
    	if (!empty($list)) {
    		foreach ($list as $k => $v) {
    			$list[$k]['activity'] = self::activity($v['id']);
    		}
    	}
    	return self::setLevelPrice($list, $uid);
    }
    
    /**
     * 同城商品列表
     * @return mixed
     */
    public static function lst($latitude, $longitude,$mapkay, $page, $limit,$sid,$cid,$keyword,$salesOrder,$priceOrder)
    {
        if($latitude){
            $crr = self::getCity($longitude, $latitude,$mapkay);
            $city = $crr['result']['address_component']['city'];
            $district = $crr['result']['address_component']['district'];
        }else{
            $city='';
            $district ='';
        }
      
        $baseOrder = '';
        if ($salesOrder) $baseOrder = $salesOrder == 'desc' ? 'sales DESC' : 'sales ASC'; 
        
        if ($priceOrder) $baseOrder = $priceOrder == 'desc' ? 'price DESC' : 'price ASC';
        
        $baseOrder = 'sort desc,id desc';
        $model = self::getProductWhere($city,$district,$sid,$cid,$keyword, self::alias('a')->join('system_store r', 'r.id=a.store_id', 'LEFT'), 'a.', 'r')->field('a.*')
        ->order($baseOrder);
       
        if ($limit) $model->page($page, $limit);
        $list = $model->select();
        return $list;
    }
    
    
    /**
     * 同城商品列表
     * @return mixed
     */
    public static function xlst($latitude, $longitude,$mapkay, $page, $limit,$sid,$cid,$keyword,$salesOrder,$priceOrder)
    {
    	if($latitude){
    		$crr = self::getCity($longitude, $latitude,$mapkay);
    		$city = $crr['result']['address_component']['city'];
    		$district = $crr['result']['address_component']['district'];
    	}else{
    		$city='';
    		$district ='';
    	}
    
    	$baseOrder = '';
    	if ($salesOrder) $baseOrder = $salesOrder == 'desc' ? 'sales DESC' : 'sales ASC';
    
    	if ($priceOrder) $baseOrder = $priceOrder == 'desc' ? 'price DESC' : 'price ASC';
    
    	$baseOrder = 'sort desc,id desc';
    	$model = self::getXProductWhere($city,$district,$sid,$cid,$keyword, self::alias('a')->join('system_store r', 'r.id=a.store_id', 'LEFT'), 'a.', 'r')->field('a.*')
    	->order($baseOrder);
    	 
    	if ($limit) $model->page($page, $limit);
    	$list = $model->select();
    	return $list;
    }
    
    
    /**
     * 同城商品推荐列表
     * @return mixed
     */
    public static function hlst($latitude, $longitude,$mapkay, $page, $limit,$sid,$cid,$keyword,$salesOrder,$priceOrder)
    {
    	if($latitude){
    		$crr = self::getCity($longitude, $latitude,$mapkay);
    		$city = $crr['result']['address_component']['city'];
    		$district = $crr['result']['address_component']['district'];
    	}else{
    		$city='';
    		$district ='';
    	}
    
    	$baseOrder = '';
    	if ($salesOrder) $baseOrder = $salesOrder == 'desc' ? 'sales DESC' : 'sales ASC';
    
    	if ($priceOrder) $baseOrder = $priceOrder == 'desc' ? 'price DESC' : 'price ASC';
    
    	$baseOrder = 'sort desc,id desc';
    	$model = self::getProductHWhere($city,$district,$sid,$cid,$keyword, self::alias('a')->join('system_store r', 'r.id=a.store_id', 'LEFT'), 'a.', 'r')->field('a.*')
    	->order($baseOrder);
    	 
    	if ($limit) $model->page($page, $limit);
    	$list = $model->select();
    	return $list;
    }
    
    /**
     * 按地区筛选商品列表
     * @return mixed
     */
    public static function getNearList($city, $district,$mapkay, $page, $limit,$sid,$cid,$keyword,$salesOrder,$priceOrder)
    {
        if($city=='全部省份'){
            $city='';
            $district ='';
        }
        $baseOrder = '';
        if ($salesOrder) $baseOrder = $salesOrder == 'desc' ? 'sales DESC' : 'sales ASC';
    
        if ($priceOrder) $baseOrder = $priceOrder == 'desc' ? 'price DESC' : 'price ASC';
    
        $baseOrder = 'sort desc,id desc';
        $model = self::getProductWhere($city,$district,$sid,$cid,$keyword, self::alias('a')->join('system_store r', 'r.id=a.store_id', 'LEFT'), 'a.', 'r')->field('a.*')
        ->order($baseOrder);
         
        if ($limit) $model->page($page, $limit);
        $list = $model->select();
        $list = count($list) ? $list->toArray() : [];
        return $list;
    }
    
    public static function getProductWhere($city,$district,$sId,$cid,$keyword,$model, $aler = '', $join = '')
    {
        $model = $model->where($aler.'is_del', 0)->where($aler.'stock', '>', 0)->where($aler.'is_show', 1)->where($aler.'belong_t',2);
        if($city){
            $model = $model->where($join . '.city', 'LIKE', "%$city%");
            $model = $model->where($join . '.district', 'LIKE', "%$district%");
        }
        if ($cid) {
            $model->whereIn($aler.'id', function ($query) use ($cid) {
                $query->name('store_product_cate')->where('cate_id', $cid)->field('product_id')->select();
            });
        } elseif ($sId) {
            $model->whereIn($aler.'id', function ($query) use ($sId) {
                $query->name('store_product_cate')->whereIn('cate_id', function ($q) use ($sId) {
                    $q->name('store_category')->where('pid', $sId)->field('id')->select();
                })->field('product_id')->select();
            });
        }
        if (!empty($keyword)) $model->where($aler.'store_name', 'LIKE', htmlspecialchars("%$keyword%"));
        return $model;
    }
    
    public static function getXProductWhere($city,$district,$sId,$cid,$keyword,$model, $aler = '', $join = '')
    {
    	$model = $model->where($aler.'is_del', 0)->where($aler.'is_hot', 1)->where($aler.'stock', '>', 0)->where($aler.'is_show', 1)->where($aler.'belong_t',2);
    	if($city){
    		$model = $model->where($join . '.city', 'LIKE', "%$city%");
    		$model = $model->where($join . '.district', 'LIKE', "%$district%");
    	}
    	if ($cid) {
    		$model->whereIn($aler.'id', function ($query) use ($cid) {
    			$query->name('store_product_cate')->where('cate_id', $cid)->field('product_id')->select();
    		});
    	} elseif ($sId) {
    		$model->whereIn($aler.'id', function ($query) use ($sId) {
    			$query->name('store_product_cate')->whereIn('cate_id', function ($q) use ($sId) {
    				$q->name('store_category')->where('pid', $sId)->field('id')->select();
    			})->field('product_id')->select();
    		});
    	}
    	if (!empty($keyword)) $model->where($aler.'store_name', 'LIKE', htmlspecialchars("%$keyword%"));
    	return $model;
    }
    
    public static function getProductHWhere($city,$district,$sId,$cid,$keyword,$model, $aler = '', $join = '')
    {
    	$model = $model->where($aler.'is_del', 0)->where($aler.'is_best', 1)->where($aler.'stock', '>', 0)->where($aler.'is_show', 1)->where($aler.'belong_t',2);
    	if($city){
    		$model = $model->where($join . '.city', 'LIKE', "%$city%");
    		$model = $model->where($join . '.district', 'LIKE', "%$district%");
    	}
    	if ($cid) {
    		$model->whereIn($aler.'id', function ($query) use ($cid) {
    			$query->name('store_product_cate')->where('cate_id', $cid)->field('product_id')->select();
    		});
    	} elseif ($sId) {
    		$model->whereIn($aler.'id', function ($query) use ($sId) {
    			$query->name('store_product_cate')->whereIn('cate_id', function ($q) use ($sId) {
    				$q->name('store_category')->where('pid', $sId)->field('id')->select();
    			})->field('product_id')->select();
    		});
    	}
    	if (!empty($keyword)) $model->where($aler.'store_name', 'LIKE', htmlspecialchars("%$keyword%"));
    	return $model;
    }
    
    
    
    
    
    
    
    
    
    
    //根据经纬度查询所在城市
    public static function getCity($longitude, $latitude,$mapkay) {
        //调取腾讯接口,其中ak为key,注意location纬度在前，经度在后
        $api = "https://apis.map.qq.com/ws/geocoder/v1/?location=" . $latitude . "," . $longitude . "&output=json&pois=1&key=".$mapkay;
        $content = file_get_contents($api);
        $arr = json_decode($content, true);
        if ($arr['status'] == 0) {
            return $arr;
        } else {
            return 'error';
        }
    }
    
    /**
     * 精品产品
     * @param string $field
     * @param int $limit
     * @return false|\PDOStatement|string|\think\Collection
     */
    public static function getBestProduct($field = '*', $limit = 0, $uid = 0, bool $bool = true)
    {
        if (!$limit && !$bool) return [];
        $model = self::where('is_hot', 1)->where('is_del', 0)->where('mer_id', 0)
        ->where('stock', '>', 0)->where('is_show', 1)->field($field)
        ->order('sort DESC, id DESC');
        if ($limit) $model->limit($limit);
        $list = $model->select();
        $list = count($list) ? $list->toArray() : [];
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                $list[$k]['activity'] = self::activity($v['id']);
            }
        }
        return self::setLevelPrice($list, $uid);
    }

    /**
     * 设置会员价格
     * @param object | array $list 产品列表
     * @param int $uid 用户uid
     * @return array
     * */
    public static function setLevelPrice($list, $uid, $isSingle = false)
    {
        if (is_object($list)) $list = count($list) ? $list->toArray() : [];
        if (!sys_config('vip_open')) {
            if (is_array($list)) return $list;
            return $isSingle ? $list : 0;
        }
        $levelId = UserLevel::getUserLevel($uid);
        if ($levelId) {
            $discount = UserLevel::getUserLevelInfo($levelId, 'discount');
            $discount = bcsub(1, bcdiv($discount, 100, 2), 2);
        } else {
            $discount = SystemUserLevel::getLevelDiscount();
            $discount = bcsub(1, bcdiv($discount, 100, 2), 2);
        }
        //如果不是数组直接执行减去会员优惠金额
        if (!is_array($list))
            //不是会员原价返回
            if ($levelId)
                //如果$isSingle==true 返回优惠后的总金额，否则返回优惠的金额
                return $isSingle ? bcsub($list, bcmul($discount, $list, 2), 2) : bcmul($discount, $list, 2);
            else
                return $isSingle ? $list : 0;
        //当$list为数组时$isSingle==true为一维数组 ，否则为二维
        if ($isSingle)
            $list['vip_price'] = isset($list['price']) ? bcsub($list['price'], bcmul($discount, $list['price'], 2), 2) : 0;
        else
            foreach ($list as &$item) {
                $item['vip_price'] = isset($item['price']) ? bcsub($item['price'], bcmul($discount, $item['price'], 2), 2) : 0;
            }
        return $list;
    }


    /**
     * 优惠产品
     * @param string $field
     * @param int $limit
     * @return false|\PDOStatement|string|\think\Collection
     */
    public static function getBenefitProduct($field = '*', $limit = 0)
    {
        $model = self::where('is_benefit', 1)
            ->where('is_del', 0)->where('mer_id', 0)->where('stock', '>', 0)
            ->where('is_show', 1)->field($field)
            ->order('sort DESC, id DESC');
        if ($limit) $model->limit($limit);
        $data = $model->select();
        if (count($data) > 0) {
            foreach ($data as $k => $v) {
                $data[$k]['activity'] = self::activity($v['id']);
            }
        }
        return $data;
    }

    public static function cateIdBySimilarityProduct($cateId, $field = '*', $limit = 0)
    {
        $pid = StoreCategory::cateIdByPid($cateId) ?: $cateId;
        $cateList = StoreCategory::pidByCategory($pid, 'id') ?: [];
        $cid = [$pid];
        foreach ($cateList as $cate) {
            $cid[] = $cate['id'];
        }
        $model = self::where('cate_id', 'IN', $cid)->where('is_show', 1)->where('is_del', 0)
            ->field($field)->order('sort DESC,id DESC');
        if ($limit) $model->limit($limit);
        return $model->select();
    }

    public static function isValidProduct($productId)
    {
        return self::be(['id' => $productId, 'is_del' => 0, 'is_show' => 1]) > 0;
    }

    public static function getProductStock($productId, $uniqueId = '')
    {
        return $uniqueId == '' ?
            self::where('id', $productId)->value('stock') ?: 0
            : StoreProductAttr::uniqueByStock($uniqueId);
    }

    /**
     * 加销量减销量
     * @param $num
     * @param $productId
     * @param string $unique
     * @return bool
     */
    public static function decProductStock($num, $productId, $unique = '')
    {
        if ($unique) {
            $res = false !== StoreProductAttrValueModel::decProductAttrStock($productId, $unique, $num, 0);
            $res = $res && self::where('id', $productId)->dec('stock', $num)->inc('sales', $num)->update();
        } else {
            $res = false !== self::where('id', $productId)->dec('stock', $num)->inc('sales', $num)->update();
        }
        if ($res) {
            $stock = self::where('id', $productId)->value('stock');
            $replenishment_num = sys_config('store_stock') ?? 0;//库存预警界限
            if ($replenishment_num >= $stock) {
                try {
                    ChannelService::instance()->send('STORE_STOCK', ['id' => $productId]);
                } catch (\Exception $e) {
                }
            }
        }
        return $res;
    }

    /**
     * 减少销量,增加库存
     * @param int $num 增加库存数量
     * @param int $productId 产品id
     * @param string $unique 属性唯一值
     * @return boolean
     */
    public static function incProductStock($num, $productId, $unique = '')
    {
        $product = self::where('id', $productId)->field(['sales', 'stock'])->find();
        if (!$product) return true;
        if ($product->sales > 0) $product->sales = bcsub($product->sales, $num, 0);
        if ($product->sales < 0) $product->sales = 0;
        if ($unique) {
            $res = false !== StoreProductAttrValueModel::incProductAttrStock($productId, $unique, $num);
            //没有修改销量则直接返回
            if ($product->sales == 0) return true;
            $res = $res && $product->save();
        } else {
            $product->stock = bcadd($product->stock, $num, 0);
            $res = false !== $product->save();
        }
        return $res;
    }

    /**
     * 获取产品分销佣金最低和最高
     * @param $storeInfo
     * @param $productValue
     * @return int|string
     */
    public static function getPacketPrice($storeInfo, $productValue)
    {
        $store_brokerage_ratio = sys_config('store_brokerage_ratio');
        $store_brokerage_ratio = bcdiv($store_brokerage_ratio, 100, 2);
        if (isset($storeInfo['is_sub']) && $storeInfo['is_sub'] == 1) {
            $Maxkey = self::getArrayMax($productValue, 'brokerage');
            $Minkey = self::getArrayMin($productValue, 'brokerage');
            $maxPrice = bcadd(isset($productValue[$Maxkey]) ? $productValue[$Maxkey]['brokerage'] : 0, 0, 0);
            $minPrice = bcadd(isset($productValue[$Minkey]) ? $productValue[$Minkey]['brokerage'] : 0, 0, 0);
        } else {
            $Maxkey = self::getArrayMax($productValue, 'price');
            $Minkey = self::getArrayMin($productValue, 'price');
            $maxPrice = bcmul($store_brokerage_ratio, bcadd(isset($productValue[$Maxkey]) ? $productValue[$Maxkey]['price'] : 0, 0, 0), 0);
            $minPrice = bcmul($store_brokerage_ratio, bcadd(isset($productValue[$Minkey]) ? $productValue[$Minkey]['price'] : 0, 0, 0), 0);
        }
        if ($minPrice == 0 && $maxPrice == 0)
            return 0;
        else if ($minPrice == 0 && $maxPrice)
            return $maxPrice;
        else if ($maxPrice == 0 && $minPrice)
            return $minPrice;
        else if ($maxPrice == $minPrice && $minPrice)
            return $maxPrice;
        else
            return $minPrice . '~' . $maxPrice;
    }

    /**
     * 获取二维数组中最大的值
     * @param $arr
     * @param $field
     * @return int|string
     */
    public static function getArrayMax($arr, $field)
    {
        $temp = [];
        foreach ($arr as $k => $v) {
            $temp[] = $v[$field];
        }
        if (!count($temp)) return 0;
        $maxNumber = max($temp);
        foreach ($arr as $k => $v) {
            if ($maxNumber == $v[$field]) return $k;
        }
        return 0;
    }

    /**
     * 获取二维数组中最小的值
     * @param $arr
     * @param $field
     * @return int|string
     */
    public static function getArrayMin($arr, $field)
    {
        $temp = [];
        foreach ($arr as $k => $v) {
            $temp[] = $v[$field];
        }
        if (!count($temp)) return 0;
        $minNumber = min($temp);
        foreach ($arr as $k => $v) {
            if ($minNumber == $v[$field]) return $k;
        }
        return 0;
    }

    /**
     * 产品名称 图片
     * @param array $productIds
     * @return array
     */
    public static function getProductStoreNameOrImage(array $productIds)
    {
        return self::whereIn('id', $productIds)->column('store_name,image', 'id');
    }

    /**
     * TODO 获取某个字段值
     * @param $id
     * @param string $field
     * @return mixed
     */
    public static function getProductField($id, $field = 'store_name')
    {
        if (is_array($id))
            return self::where('id', 'in', $id)->field($field)->select();
        else
            return self::where('id', $id)->value($field);
    }

    /**
     * 获取产品返佣金额
     * @param array $cartId
     * @param bool $type true = 一级返佣, fasle = 二级返佣
     * @return int|string
     */
    public static function getProductBrokerage(array $cartId, bool $type = true)
    {
        $cartInfo = StoreOrderCartInfo::whereIn('cart_id', $cartId)->column('cart_info');
        $oneBrokerage = 0;//一级返佣金额
        $twoBrokerage = 0;//二级返佣金额
        $sumProductPrice = 0;//非指定返佣商品总金额
        foreach ($cartInfo as $value) {
            $product = json_decode($value, true);
            if (isset($product['productInfo'])) {
                $productInfo = $product['productInfo'];
                //指定返佣金额
                if (isset($productInfo['is_sub']) && $productInfo['is_sub'] == 1) {
                    $oneBrokerage = bcadd($oneBrokerage, $productInfo['attrInfo']['brokerage'] ?? 0, 2);
                    $twoBrokerage = bcadd($twoBrokerage, $productInfo['attrInfo']['brokerage_two'] ?? 0, 2);
                } else {
                    //比例返佣
                    if (isset($productInfo['attrInfo'])) {
                        $sumProductPrice = bcadd($sumProductPrice, $productInfo['attrInfo']['price'] ?? 0, 2);
                    } else {
                        $sumProductPrice = bcadd($sumProductPrice, $productInfo['price'] ?? 0, 2);
                    }
                }
            }
        }
        if ($type) {
            //获取后台一级返佣比例
            $storeBrokerageRatio = sys_config('store_brokerage_ratio');
            //一级返佣比例 小于等于零时直接返回 不返佣
            if ($storeBrokerageRatio <= 0) {
                return $oneBrokerage;
            }
            //计算获取一级返佣比例
            $brokerageRatio = bcdiv($storeBrokerageRatio, 100, 2);
            $brokeragePrice = bcmul($sumProductPrice, $brokerageRatio, 2);
            //固定返佣 + 比例返佣 = 一级总返佣金额
            return bcadd($oneBrokerage, $brokeragePrice, 2);
        } else {
            //获取二级返佣比例
            $storeBrokerageTwo = sys_config('store_brokerage_two');
            //二级返佣比例小于等于0 直接返回
            if ($storeBrokerageTwo <= 0) {
                return $twoBrokerage;
            }
            //计算获取二级返佣比例
            $brokerageRatio = bcdiv($storeBrokerageTwo, 100, 2);
            $brokeragePrice = bcmul($sumProductPrice, $brokerageRatio, 2);
            //固定返佣 + 比例返佣 = 二级总返佣金额
            return bcadd($twoBrokerage, $brokeragePrice, 2);
        }

    }

    /**
     * 获取商品在此时段活动优先类型
     */
    public static function activity($id, $status = true)
    {
        $activity = self::where('id', $id)->value('activity');
        if (!$activity) $activity = '1,2,3';//如果老商品没有活动顺序，默认活动顺序，秒杀-砍价-拼团
        $activity = explode(',', $activity);
        $activityId = [];
        $time = 0;
        $seckillId = StoreSeckill::where('is_del', 0)->where('status', 1)->where('start_time', '<=', time())->where('stop_time', '>=', time())->where('product_id', $id)->field('id,time_id')->select();
        if ($seckillId) {
            foreach ($seckillId as $v) {
                $timeInfo = GroupDataService::getDataNumber((int)$v['time_id']);
                if ($timeInfo && isset($timeInfo['time']) && isset($timeInfo['continued'])) {
                    if (date('H') >= $timeInfo['time'] && date('H') < ($timeInfo['time'] + $timeInfo['continued'])) {
                        $activityId[1] = $v['id'];
                        $time = strtotime(date("Y-m-d"), time()) + 3600 * ($timeInfo['time'] + $timeInfo['continued']);
                    }
                }
            }
        }
        $bargainId = StoreBargain::where('is_del', 0)->where('status', 1)->where('start_time', '<=', time())->where('stop_time', '>=', time())->where('product_id', $id)->value('id');
        if ($bargainId) $activityId[2] = $bargainId;
        $combinationId = StoreCombination::where('is_del', 0)->where('start_time', '<=', time())->where('stop_time', '>=', time())->where('product_id', $id)->value('id');
        if ($combinationId) $activityId[3] = $combinationId;
        $data = [];
        foreach ($activity as $k => $v) {
            if (array_key_exists($v, $activityId)) {
                if ($status) {
                    $data['type'] = $v;
                    $data['id'] = $activityId[$v];
                    if ($v == 1) $data['time'] = $time;
                    break;
                } else {
                    $arr['type'] = $v;
                    $arr['id'] = $activityId[$v];
                    if ($v == 1) $arr['time'] = $time;
                    $data[] = $arr;
                }
            }
        }
        return $data;
    }
}