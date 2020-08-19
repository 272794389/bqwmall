<?php
/**
 *
 * @author: xaboy<365615158@qq.com>
 * @day: 2018/3/3
 */

namespace app\models\user;

use crmeb\basic\BaseModel;
use crmeb\services\workerman\ChannelService;
use crmeb\traits\ModelTrait;
use think\facade\Db;


/**
 * TODO 用户提现
 * Class UserExtract
 * @package app\models\user
 */
class UserExtract extends BaseModel
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
    protected $name = 'user_extract';

    use ModelTrait;

    //审核中
    const AUDIT_STATUS = 0;
    //未通过
    const FAIL_STATUS = -1;
    //已提现
    const SUCCESS_STATUS = 1;

    protected static $extractType = ['alipay','bank','weixin'];

    protected static $extractTypeMsg = ['alipay'=>'支付宝','bank'=>'银行卡','weixin'=>'微信'];

    protected static $status = array(
        -1=>'未通过',
        0 =>'审核中',
        1 =>'已提现'
    );

    /**
     * 用户自主提现记录提现记录,后台执行审核
     * @param array $userInfo 用户个人信息
     * @param array $data 提现详细信息
     * @return bool
     */
    public static function userExtract($userInfo,$data){
        if(!in_array($data['extract_type'],self::$extractType))
            return self::setErrorInfo('提现方式不存在');
        $userInfo = User::get($userInfo['uid']);
        $extractPrice = $userInfo['now_money'];
        if(!isset($data['bank_address'])){
            return self::setErrorInfo('请填写支行名称'.$data['money']);
        }
        if($extractPrice < 0) return self::setErrorInfo('提现金额不足'.$data['money']);
        if($data['money'] > $extractPrice) return self::setErrorInfo('提现金额不足'.$data['money']);
        if($data['money'] <= 0) return self::setErrorInfo('提现金额大于0');
        $balance = bcsub($userInfo['now_money'],$data['money'],2);
        if($balance < 0) $balance=0;
        //查询提现手续费率
        $withdraw_fee = Db::name('data_config')->where('id',1)->value('withdraw_fee');
        $fee = $withdraw_fee*$data['money']/100;
        $insertData = [
            'uid' => $userInfo['uid'],
            'extract_type' => $data['extract_type'],
            'extract_price' => $data['money'],
            'fee' => $fee,
            'belong_t' => 1,
            'add_time' => time(),
            'balance' => $balance,
            'status' => self::AUDIT_STATUS
        ];
        if(isset($data['name']) && strlen(trim($data['name']))) $insertData['real_name'] = $data['name'];
        else $insertData['real_name'] = $userInfo['nickname'];
        if(isset($data['cardnum'])) $insertData['bank_code'] = $data['cardnum'];
        else $insertData['bank_code'] = '';
        if(isset($data['bankname'])) {
            $insertData['bank_name']=$data['bankname'];
            $insertData['bank_address']=$data['bank_address'];
        }
        else $insertData['bank_address']='';
        if(isset($data['weixin'])) $insertData['wechat'] = $data['weixin'];
        else $insertData['wechat'] = $userInfo['nickname'];
        if($data['extract_type'] == 'alipay'){
            if(!$data['alipay_code']) return self::setErrorInfo('请输入支付宝账号');
            $insertData['alipay_code'] = $data['alipay_code'];
            $mark = '使用支付宝提现'.$insertData['extract_price'].'元';
        }else if($data['extract_type'] == 'bank'){
            if(!$data['cardnum']) return self::setErrorInfo('请输入银行卡账号');
            if(!$data['bankname']) return self::setErrorInfo('请输入开户行信息');
            $mark = '使用银联卡'.$insertData['bank_code'].'提现'.$insertData['extract_price'].'元';
        }else if($data['extract_type'] == 'weixin'){
            if(!$data['weixin']) return self::setErrorInfo('请输入微信账号');
            $mark = '使用微信提现'.$insertData['extract_price'].'元';
        }
       
        self::beginTrans();
        try{
            $res1 = self::create($insertData);
            if(!$res1) return self::setErrorInfo('提现失败');
            $res2 = User::edit(['now_money'=>$balance],$userInfo['uid'],'uid');
            $res3 = StorePayLog::expend($userInfo['uid'],$res1['id'], 2, -$data['money'], 0, 0, 0,0,$fee, '余额提现');
            $res = $res2 && $res3;
            if($res){
                self::commitTrans();
                try{
                    ChannelService::instance()->send('WITHDRAW', ['id'=>$res1->id]);
                }catch (\Exception $e){}
                event('AdminNewPush');
                //发送模板消息
                return true;
            }else return self::setErrorInfo('提现失败!');
        }catch (\Exception $e){
            self::rollbackTrans();
            return self::setErrorInfo('提现失败!');
        }
    }
    
    
    /**
     * 用户自主货款提现记录提现记录,后台执行审核
     * @param array $userInfo 用户个人信息
     * @param array $data 提现详细信息
     * @return bool
     */
    public static function userHuoExtract($userInfo,$data){
        if(!in_array($data['extract_type'],self::$extractType))
            return self::setErrorInfo('提现方式不存在');
            $userInfo = User::get($userInfo['uid']);
            $extractPrice = $userInfo['huokuan'];
            if(!isset($data['bank_address'])){
                return self::setErrorInfo('请填写支行名称'.$data['money']);
            }
            if($extractPrice < 0) return self::setErrorInfo('提现金额不足'.$data['money']);
            if($data['money'] > $extractPrice) return self::setErrorInfo('提现金额不足'.$data['money']);
            if($data['money'] <= 0) return self::setErrorInfo('提现金额大于0');
            $balance = bcsub($userInfo['huokuan'],$data['money'],2);
            if($balance < 0) $balance=0;
            //查询提现手续费率
            $insertData = [
                'uid' => $userInfo['uid'],
                'extract_type' => $data['extract_type'],
                'extract_price' => $data['money'],
                'belong_t' => 2,
                'add_time' => time(),
                'balance' => $balance,
                'status' => self::AUDIT_STATUS
            ];
            if(isset($data['name']) && strlen(trim($data['name']))) $insertData['real_name'] = $data['name'];
            else $insertData['real_name'] = $userInfo['nickname'];
            if(isset($data['cardnum'])) $insertData['bank_code'] = $data['cardnum'];
            else $insertData['bank_code'] = '';
            if(isset($data['bankname'])) {
                    $insertData['bank_name']=$data['bankname'];
                    $insertData['bank_address']=$data['bank_address'];
                }
            else $insertData['bank_address']='';
            if(isset($data['weixin'])) $insertData['wechat'] = $data['weixin'];
            else $insertData['wechat'] = $userInfo['nickname'];
            if($data['extract_type'] == 'alipay'){
                if(!$data['alipay_code']) return self::setErrorInfo('请输入支付宝账号');
                $insertData['alipay_code'] = $data['alipay_code'];
                $mark = '使用支付宝提现'.$insertData['extract_price'].'元';
            }else if($data['extract_type'] == 'bank'){
                if(!$data['cardnum']) return self::setErrorInfo('请输入银行卡账号');
                if(!$data['bankname']) return self::setErrorInfo('请输入开户行信息');
                $mark = '使用银联卡'.$insertData['bank_code'].'提现'.$insertData['extract_price'].'元';
            }else if($data['extract_type'] == 'weixin'){
                if(!$data['weixin']) return self::setErrorInfo('请输入微信账号');
                $mark = '使用微信提现'.$insertData['extract_price'].'元';
            }
             
            self::beginTrans();
            try{
                $res1 = self::create($insertData);
                if(!$res1) return self::setErrorInfo('提现失败');
                $res2 = User::edit(['huokuan'=>$balance],$userInfo['uid'],'uid');
                $res3 = StorePayLog::expend($userInfo['uid'],$res1['id'], 2, 0, -$data['money'], 0, 0,0,0, '货款提现');
                $res = $res2 && $res3;
                if($res){
                    self::commitTrans();
                    try{
                        ChannelService::instance()->send('WITHDRAW', ['id'=>$res1->id]);
                    }catch (\Exception $e){}
                    event('AdminNewPush');
                    //发送模板消息
                    return true;
                }else return self::setErrorInfo('提现失败!');
            }catch (\Exception $e){
                self::rollbackTrans();
                return self::setErrorInfo('提现失败!');
            }
    }
    

    /**
     * 获得用户最后一次提现信息
     * @param $openid
     * @return mixed
     */
    public static function userLastInfo($uid)
    {
        return self::where(compact('uid'))->order('add_time DESC')->find();
    }

    /**
     * 获得用户提现总金额
     * @param $uid
     * @return mixed
     */
    public static function userExtractTotalPrice($uid,$status=self::SUCCESS_STATUS)
    {
        return self::where('uid',$uid)->where('status',$status)->value('SUM(extract_price)')?:0;
    }

    /**
     * 用户提现记录列表
     * @param int $uid 用户uid
     * @param int $first 截取行数
     * @param int $limit 截取数
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function extractList($uid,$first = 0,$limit = 8)
    {
        $list=UserExtract::where('uid',$uid)->order('add_time desc')->limit($first,$limit)->select();
        foreach($list as &$v){
            $v['add_time']=date('Y/m/d',$v['add_time']);
        }
        return $list;
    }

    /**
     * 获取累计已提现佣金
     * @param $uid
     * @return float
     */
    public static function extractSum($uid)
    {
        return self::where('uid',$uid)->where('status',1)->sum('extract_price');
    }
    
    
    /*
     * 获取余额提现明细
     * @param int $uid 用户uid
     * @param int $page 页码
     * @param int $limit 展示多少条
     * @param int $type 展示类型
     * @return array
     * */
    public static function getUserWithdrawList($uid, $page, $limit,$type)
    {
        if (!$limit) return [];
        $model = self::where('uid', $uid)->where('belong_t',$type)->order('add_time desc')
        ->field('FROM_UNIXTIME(add_time,"%Y-%m") as time,group_concat(id SEPARATOR ",") ids')->group('time');
        if ($page) $model = $model->page((int)$page, (int)$limit);
        $list = ($list = $model->select()) ? $list->toArray() : [];
        $data = [];
        foreach ($list as $item) {
            $value['time'] = $item['time'];
            $value['list'] = self::where('id', 'in', $item['ids'])->field('FROM_UNIXTIME(add_time,"%Y-%m-%d %H:%i") as add_time,extract_price,status')->order('add_time DESC')->select();
            array_push($data, $value);
        }
        return $data;
    }
    
    /**
     * 获取除失败的提现金额
     * @param $uid
     * @return float
     */
    public static function getWithdrawSum($uid,$type)
    {
        return self::where('uid', $uid)->where('status','>', -1)->where('belong_t', $type)
        ->sum('extract_price');
    }

}