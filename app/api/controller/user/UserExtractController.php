<?php

namespace app\api\controller\user;

use app\admin\model\system\SystemConfig;
use app\models\store\StoreOrder;
use app\models\user\UserBill;
use app\models\user\UserBank;
use app\models\user\UserExtract;
use app\Request;
use crmeb\services\UtilService;
use think\facade\Db;

/**
 * 提现类
 * Class UserExtractController
 * @package app\api\controller\user
 */
class UserExtractController
{
    /**
     * 提现银行
     * @param Request $request
     * @return mixed
     */
    public function bank(Request $request)
    {
        $user = $request->user();
        $broken_time = intval(sys_config('extract_time'));
        $search_time = time() - 86400 * $broken_time;
        //可提现佣金
        //返佣 +
        $brokerage_commission = UserBill::where(['uid' => $user['uid'], 'category' => 'now_money', 'type' => 'brokerage'])
            ->where('add_time', '>', $search_time)
            ->where('pm', 1)
            ->sum('number');
        //退款退的佣金 -
        $refund_commission = UserBill::where(['uid' => $user['uid'], 'category' => 'now_money', 'type' => 'brokerage'])
            ->where('add_time', '>', $search_time)
            ->where('pm', 0)
            ->sum('number');
        $data['broken_commission'] = bcsub($brokerage_commission, $refund_commission, 2);
        if ($data['broken_commission'] < 0)
            $data['broken_commission'] = 0;
//        return $data;
        $data['brokerage_price'] = $user['brokerage_price'];
        //可提现佣金
        $data['commissionCount'] = $data['brokerage_price'] - $data['broken_commission'];
        
        //用户信息
        $data['use_money'] = $user['now_money'];
        //用户信息
        $data['huokuan'] = $user['huokuan'];
        //查询提现手续费率
        $withdraw_fee = Db::name('data_config')->where('id',1)->value('withdraw_fee');
        $data['withdraw_fee'] = $withdraw_fee;
        $bankinfo = UserBank::where('uid',$user['uid'])->find();
        if($bankinfo){
            $data['bankname'] = $bankinfo['bankname'];
            $data['bank_address'] = $bankinfo['bank_address'];
            $data['uname'] = $bankinfo['uname'];
            $data['cardnum'] = $bankinfo['cardnum'];
        }
        
        $extractBank = sys_config('user_extract_bank') ?? []; //提现银行
        $extractBank = str_replace("\r\n", "\n", $extractBank);//防止不兼容
        $data['extractBank'] = explode("\n", is_array($extractBank) ? (isset($extractBank[0]) ? $extractBank[0] : $extractBank) : $extractBank);
        $data['minPrice'] = sys_config('user_extract_min_price');//提现最低金额
        return app('json')->successful($data);
    }

    /**
     * 提现申请
     * @param Request $request
     * @return mixed
     */
    public function cash(Request $request)
    {
        $uid = $request->uid();
        $extractInfo = UtilService::postMore([
            ['alipay_code', ''],
            ['extract_type', ''],
            ['money', 0],
            ['name', ''],
            ['bankname', ''],
            ['bank_address', ''],
            ['cardnum', ''],
            ['weixin', ''],
        ], $request);
        if (!preg_match('/^[0-9]*$/',$extractInfo['money'])) return app('json')->fail('提现金额输入有误');
        if($extractInfo['bankname'] =='请选择银行'){
            return app('json')->fail('请选择提现银行');
        }
        if($extractInfo['bank_address'] ==''){
            return app('json')->fail('请填写开户支行');
        }
        if($extractInfo['name'] ==''){
            return app('json')->fail('请填写持卡人姓名');
        }
        if($extractInfo['cardnum'] ==''){
            return app('json')->fail('请填卡号');
        }
        if (!$extractInfo['cardnum'] =='')
            if (!preg_match('/^([1-9]{1})(\d{14}|\d{18})$/',$extractInfo['cardnum']))
                return app('json')->fail('银行卡号输入有误');
            
        $bank = UserBank::where('uid',$uid)->find();
        if(!$bank){
            UserBank::createBank($uid,$extractInfo['bankname'],$extractInfo['bank_address'],$extractInfo['name'],$extractInfo['cardnum']);
        }
                
        if (UserExtract::userExtract($request->user(), $extractInfo))
            return app('json')->successful('申请提现成功!');
        else
            return app('json')->fail(UserExtract::getErrorInfo('提现失败'));
    }
    
    /**
     * 货款提现申请
     * @param Request $request
     * @return mixed
     */
    public function huo_cash(Request $request)
    {
        $uid = $request->uid();
        $extractInfo = UtilService::postMore([
            ['alipay_code', ''],
            ['extract_type', ''],
            ['money', 0],
            ['name', ''],
            ['bankname', ''],
            ['bank_address', ''],
            ['cardnum', ''],
            ['weixin', ''],
        ], $request);
        if (!preg_match('/^[0-9]*$/',$extractInfo['money'])) return app('json')->fail('提现金额输入有误');
        
        if($extractInfo['bankname'] =='请选择银行'){
            return app('json')->fail('请选择提现银行');
        }
        if($extractInfo['bank_address'] ==''){
            return app('json')->fail('请填写开户支行');
        }
        if($extractInfo['name'] ==''){
            return app('json')->fail('请填写持卡人姓名');
        }
        if($extractInfo['cardnum'] ==''){
            return app('json')->fail('请填卡号');
        }
        if (!$extractInfo['cardnum'] =='')
            if (!preg_match('/^([1-9]{1})(\d{14}|\d{18})$/',$extractInfo['cardnum']))
                return app('json')->fail('银行卡号输入有误');
        $bank = UserBank::where('uid',$uid)->find();
        if(!$bank){
            UserBank::createBank($uid,$extractInfo['bankname'],$extractInfo['bank_address'],$extractInfo['name'],$extractInfo['cardnum']);
        }
        if (UserExtract::userHuoExtract($request->user(), $extractInfo))
            return app('json')->successful('申请提现成功!');
            else
                return app('json')->fail(UserExtract::getErrorInfo('提现失败'));
    }
    
    
    /**
     * 余额提现明细
     * @param Request $request
     * @param $type 1 余额提现，2 货款提现
     * @return mixed
     */
    public function withdraw(Request $request, $type)
    {
        list($page, $limit) = UtilService::getMore([
            ['page', 0],
            ['limit', 0],
        ], $request, true);
        return app('json')->successful(UserExtract::getUserWithdrawList($request->uid(), $page, $limit,$type));
    }
    
    public function withdrawStatic(Request $request,$type)
    {
        $uid = $request->uid();
        $withdrawAmount = UserExtract::getWithdrawSum($uid,$type);//除失败的提现金额
       
        $data['withdrawAmount'] = $withdrawAmount;
        return app('json')->successful($data);
    }
}