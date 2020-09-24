<?php

namespace app\admin\model\finance;

use crmeb\traits\ModelTrait;
use crmeb\basic\BaseModel;
use app\models\user\UserBill;
use app\models\system\SystemStore;
use app\admin\model\user\User;
use crmeb\services\PHPExcelService;
use think\facade\Db;

/**
 * 数据统计处理
 * Class FinanceModel
 * @package app\admin\model\finance
 */
class StorePayLog extends BaseModel
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
    protected $name = 'store_pay_log';

    use ModelTrait;

   

    public static function getLogList($where)
    {
        $data = ($data = self::setWhereList($where)->page((int)$where['page'], (int)$where['limit'])->select()) && count($data) ? $data->toArray() : [];
        $count = self::setWhereList($where)->count();
        foreach ($data as &$item) {
            $storeinfo = SystemStore::where('user_id', $item['uid'])->field('mer_name')->find();
            if($storeinfo){
                $item['mer_name'] = $storeinfo['mer_name'];
            }else{
                $item['mer_name'] = '';
            }
            
            
        }
        return compact('data', 'count');
    }

    public static function SaveExport($where)
    {
        $data = ($data = self::setWhereList($where)->select()) && count($data) ? $data->toArray() : [];
        $export = [];
        foreach ($data as $value) {
            $belong_t = '';
            if($value['belong_t']==0){
                $belong_t = '消费订单';
            }else if($value['belong_t']==1){
                $belong_t = '购物订单';
            }else if($value['belong_t']==2){
                $belong_t = '提现';
            }else{
                $belong_t = '货款转余额';
            }
            $export[] = [
                $value['uid'],
                $value['nickname'],
                $belong_t,
                $value['use_money'],
                $value['huokuan'],
                $value['give_point'],
                $value['pay_point'],
                $value['repeat_point'],
                $value['fee'],
                $value['mark'],
                $value['add_time'],
            ];
        }
        /*
        PHPExcelService::setExcelHeader(['会员ID', '昵称', '记录类型', '余额', '货款','购物积分','消费积分','重消积分','手续费','备注', '创建时间'])
            ->setExcelTile('资金监控', '资金监控', date('Y-m-d H:i:s', time()))
            ->setExcelContent($export)
            ->ExcelSave();*/
    }

    public static function setWhereList($where)
    {
        $time['data'] = '';
        if ($where['start_time'] != '' && $where['end_time'] != '') {
            $time['data'] = $where['start_time'] . ' - ' . $where['end_time'];
        }
        $model = self::getModelTime($time, self::alias('A')
            ->join('user B', 'B.uid=A.uid')
            ->order('A.add_time desc'), 'A.add_time');
        if (trim($where['belong_t']) != '') {//记录类型
            $model = $model->where('A.belong_t', $where['belong_t']);
        }
        if (trim($where['pay_type']) != '') {//变动类型
            if($where['pay_type']==0){//余额
                $model = $model->where('A.use_money','<>', 0);
            }else if($where['pay_type']==1){//货款
                $model = $model->where('A.huokuan','<>', 0);
            }else if($where['pay_type']==2){//购物积分
                $model = $model->where('A.give_point','<>', 0);
            }else if($where['pay_type']==3){//消费积分
                $model = $model->where('A.pay_point','<>', 0);
            }else{//重消积分
                $model = $model->where('A.repeat_point','<>', 0);
            } 
        }
        if ($where['nickname'] != '') {
            $model = $model->where('B.nickname|B.uid|B.phone', 'like', "%$where[nickname]%");
        }
        return $model->field(['A.*', 'FROM_UNIXTIME(A.add_time,"%Y-%m-%d %H:%i:%s") as add_time', 'B.uid', 'B.nickname','B.phone']);
    }

    

    /**
     * 处理where条件
     */
    public static function statusByWhere($status, $model = null)
    {
        if ($model == null) $model = new self;
        if ('' === $status)
            return $model;
        else if ($status == 'weixin')//微信支付
            return $model->where('pay_type', 'weixin');
        else if ($status == 'yue')//余额支付
            return $model->where('pay_type', 'yue');
        else if ($status == 'offline')//线下支付
            return $model->where('pay_type', 'offline');
        else
            return $model;
    }
    
    //获取消费积分统计头部信息
    public static function getScoreBadgeList($where){
        return [
            [
                'name'=>'累计发放消费积分',
                'field'=>'个',
                'count'=>self::getModelTime($where,new self())->where('pay_point','>',0)->sum('pay_point'),
                'background_color'=>'layui-bg-blue',
                'col'=>4,
            ],
            [
                'name'=>'已使用消费积分',
                'field'=>'个',
                'count'=>self::getModelTime($where,new self())->where('pay_point','<',0)->sum('pay_point')*-1,
                'background_color'=>'layui-bg-cyan',
                'col'=>4,
            ],
            [
                'name'=>'未使用消费积分',
                'field'=>'个',
                'count'=>self::getModelTime($where,Db::name('user'))->sum('pay_point'),
                'background_color'=>'layui-bg-cyan',
                'col'=>4,
            ],
        ];
    }
    
    //获取消费积分统计曲线图和柱状图
    public static function getScoreCurve($where){
        //发放积分趋势图
        $list=self::getModelTime($where,self::where('pay_point','>',0)
            ->field('FROM_UNIXTIME(add_time,"%Y-%m-%d") as _add_time,sum(pay_point) as sum_number')
            ->group('_add_time')->order('_add_time asc'))->select()->toArray();
            $date=[];
            $zoom='';
            $seriesdata=[];
            foreach ($list as $item){
                $date[]=$item['_add_time'];
                $seriesdata[]=$item['sum_number'];
            }
            unset($item);
            if(count($date)>$where['limit']){
                $zoom=$date[$where['limit']-5];
            }
            //使用积分趋势图
            $deductionlist=self::getModelTime($where,self::where('pay_point','<',0)
                ->field('FROM_UNIXTIME(add_time,"%Y-%m-%d") as _add_time,sum(pay_point) as sum_number')
                ->group('_add_time')->order('_add_time asc'))->select()->toArray();
                $deduction_date=[];
                $deduction_zoom='';
                $deduction_seriesdata=[];
                foreach ($deductionlist as $item){
                    $deduction_date[]=$item['_add_time'];
                    $deduction_seriesdata[]=$item['sum_number']*-1;
                }
                if(count($deductionlist)>$where['limit']){
                    $deduction_zoom=$deductionlist[$where['limit']-5];
                }
                return compact('date','seriesdata','zoom','deduction_date','deduction_zoom','deduction_seriesdata');
    }
    
    //获取购物积分统计头部信息
    public static function getGScoreBadgeList($where){
        return [
            [
                'name'=>'累计发放购物积分',
                'field'=>'个',
                'count'=>self::getModelTime($where,new self())->where('give_point','>',0)->sum('give_point'),
                'background_color'=>'layui-bg-blue',
                'col'=>4,
            ],
            [
                'name'=>'已使用购物积分',
                'field'=>'个',
                'count'=>self::getModelTime($where,new self())->where('give_point','<',0)->sum('give_point')*-1,
                'background_color'=>'layui-bg-cyan',
                'col'=>4,
            ],
            [
                'name'=>'未使用购物积分',
                'field'=>'个',
                'count'=>self::getModelTime($where,Db::name('user'))->sum('give_point'),
                'background_color'=>'layui-bg-cyan',
                'col'=>4,
            ],
        ];
    }
    
    //获取消费积分统计曲线图和柱状图
    public static function getGScoreCurve($where){
        //发放积分趋势图
        $list=self::getModelTime($where,self::where('give_point','>',0)
            ->field('FROM_UNIXTIME(add_time,"%Y-%m-%d") as _add_time,sum(give_point) as sum_number')
            ->group('_add_time')->order('_add_time asc'))->select()->toArray();
            $date=[];
            $zoom='';
            $seriesdata=[];
            foreach ($list as $item){
                $date[]=$item['_add_time'];
                $seriesdata[]=$item['sum_number'];
            }
            unset($item);
            if(count($date)>$where['limit']){
                $zoom=$date[$where['limit']-5];
            }
            //使用积分趋势图
            $deductionlist=self::getModelTime($where,self::where('give_point','<',0)
                ->field('FROM_UNIXTIME(add_time,"%Y-%m-%d") as _add_time,sum(give_point) as sum_number')
                ->group('_add_time')->order('_add_time asc'))->select()->toArray();
                $deduction_date=[];
                $deduction_zoom='';
                $deduction_seriesdata=[];
                foreach ($deductionlist as $item){
                    $deduction_date[]=$item['_add_time'];
                    $deduction_seriesdata[]=$item['sum_number']*-1;
                }
                if(count($deductionlist)>$where['limit']){
                    $deduction_zoom=$deductionlist[$where['limit']-5];
                }
                return compact('date','seriesdata','zoom','deduction_date','deduction_zoom','deduction_seriesdata');
    }
    
    //获取重消积分统计头部信息
    public static function getCScoreBadgeList($where){
        return [
            [
                'name'=>'累计发放重消积分',
                'field'=>'个',
                'count'=>self::getModelTime($where,new self())->where('repeat_point','>',0)->sum('repeat_point'),
                'background_color'=>'layui-bg-blue',
                'col'=>4,
            ],
            [
                'name'=>'已使用重消积分',
                'field'=>'个',
                'count'=>self::getModelTime($where,new self())->where('repeat_point','<',0)->sum('repeat_point')*-1,
                'background_color'=>'layui-bg-cyan',
                'col'=>4,
            ],
            [
                'name'=>'未使用重消积分',
                'field'=>'个',
                'count'=>self::getModelTime($where,Db::name('user'))->sum('repeat_point'),
                'background_color'=>'layui-bg-cyan',
                'col'=>4,
            ],
        ];
    }
    
    //获取消费积分统计曲线图和柱状图
    public static function getCScoreCurve($where){
        //发放积分趋势图
        $list=self::getModelTime($where,self::where('repeat_point','>',0)
            ->field('FROM_UNIXTIME(add_time,"%Y-%m-%d") as _add_time,sum(repeat_point) as sum_number')
            ->group('_add_time')->order('_add_time asc'))->select()->toArray();
            $date=[];
            $zoom='';
            $seriesdata=[];
            foreach ($list as $item){
                $date[]=$item['_add_time'];
                $seriesdata[]=$item['sum_number'];
            }
            unset($item);
            if(count($date)>$where['limit']){
                $zoom=$date[$where['limit']-5];
            }
            //使用积分趋势图
            $deductionlist=self::getModelTime($where,self::where('repeat_point','<',0)
                ->field('FROM_UNIXTIME(add_time,"%Y-%m-%d") as _add_time,sum(repeat_point) as sum_number')
                ->group('_add_time')->order('_add_time asc'))->select()->toArray();
                $deduction_date=[];
                $deduction_zoom='';
                $deduction_seriesdata=[];
                foreach ($deductionlist as $item){
                    $deduction_date[]=$item['_add_time'];
                    $deduction_seriesdata[]=$item['sum_number']*-1;
                }
                if(count($deductionlist)>$where['limit']){
                    $deduction_zoom=$deductionlist[$where['limit']-5];
                }
                return compact('date','seriesdata','zoom','deduction_date','deduction_zoom','deduction_seriesdata');
    }
    
    //获取头部信息
    public static function getRebateBadge($where){
        return [
            [
                'name'=>'返利数(笔)',
                'field'=>'个',
                'count'=>self::getModelTime($where,self::where('use_money','>',0)->where('fee','>',0))->count(),
                'content'=>'返利总笔数',
                'background_color'=>'layui-bg-blue',
                'class'=>'fa fa-bar-chart',
            ],
            [
                'name'=>'返利金额（元）',
                'field'=>'元',
                'count'=>self::getModelTime($where,self::where('use_money','>',0)->where('fee','>',0))->sum('use_money'),
                'content'=>'返利总金额',
                'background_color'=>'layui-bg-cyan',
                'class'=>'fa fa-line-chart',
            ],
        ];
    }
    
    //获取柱状图和饼状图数据
    public static function getUserBillChart($where,$zoom=15){
        $model=self::getModelTime($where,new self());
        $list=$model->field('FROM_UNIXTIME(add_time,"%Y-%c-%d") as un_time,sum(use_money) as sum_number')
        ->order('un_time asc')
        ->where('use_money','>',0)
        ->where('fee','>',0)
        ->group('un_time')
        ->select();
        if(count($list)) $list=$list->toArray();
        $legdata = [];
        $listdata = [];
        $dataZoom = '';
        foreach ($list as $item){
            $legdata[]=$item['un_time'];
            $listdata[]=$item['sum_number'];
        }
        if(count($legdata)>=$zoom) $dataZoom=$legdata[$zoom-1];
        //获取用户分布钱数
        $fenbulist=self::getModelTime($where,new self(),'a.add_time')
        ->alias('a')
        ->join('user r','a.uid=r.uid')
        ->field('a.uid,sum(a.use_money) as sum_number,r.nickname')
        ->where('a.use_money','>',0)
        ->where('a.fee', '>',0)
        ->order('sum_number desc')
        ->group('a.uid')
        ->limit(8)
        ->select();
        //获取用户当前时间段总钱数
        $sum_number=self::getModelTime($where,new self())
        ->alias('a')
        ->where('a.use_money','>',0)
        ->where('a.fee', '>',0)
        ->sum('use_money');
        if(count($fenbulist)) $fenbulist=$fenbulist->toArray();
        $fenbudate=[];
        $fenbu_legend=[];
        $color=['#ffcccc','#99cc00','#fd99cc','#669966','#66CDAA','#ADFF2F','#00BFFF','#00CED1','#66cccc','#ff9900','#ffcc00','#336699','#cccc00','#99ccff','#990066'];
        foreach ($fenbulist as $key=>$value){
            $fenbu_legend[]=$value['nickname'];
            $items['name']=$value['nickname'];
            $items['value']=bcdiv($value['sum_number'],$sum_number,2)*100;
            $items['itemStyle']['color']=$color[$key];
            $fenbudate[]=$items;
        }
        return compact('legdata','listdata','fenbudate','fenbu_legend','dataZoom');
    }
    
    //获取返佣用户信息列表
    public static function getFanList($where){
        $list=self::alias('a')->join('user r','a.uid=r.uid')
        ->where('a.use_money','>',0)
        ->where('a.fee', '>',0)
        ->order('a.use_money desc')
        ->field('FROM_UNIXTIME(a.add_time,"%Y-%c-%d") as add_time,a.uid,r.nickname,r.avatar,r.spread_uid,r.level,a.use_money')
        ->page((int)$where['page'],(int)$where['limit'])
        ->select();
        if(count($list)) $list=$list->toArray();
        return $list;
    }
}