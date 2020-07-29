<?php

namespace app\api\controller\user;

use app\admin\model\system\SystemAttachment;
use app\models\routine\RoutineCode;
use app\models\routine\RoutineQrcode;
use app\models\store\StoreOrder;
use app\models\user\User;
use app\models\user\UserBill;
use app\models\user\UserPayOrder;
use app\models\user\UserExtract;
use app\Request;
use crmeb\services\GroupDataService;
use crmeb\services\SystemConfigService;
use crmeb\services\UtilService;
use crmeb\services\upload\Upload;

/**
 * 账单类
 * Class UserPayOrderController
 * @package app\api\controller\user
 */
class UserPayOrderController
{
    
    public function payinfo(Request $request)
    {
        $uid = $request->uid();
        $commissionCount = UserPayOrder::getPayAmount($uid);//总消费金额
        $data['commissionCount'] = $commissionCount;
        return app('json')->successful($data);
    }
    /**
     * 消费订单明细
     * @param Request $request
     * @param $type 0 全部  1 消费  2 充值  3 返佣  4 提现
     * @return mixed
     */
    public function payorderinfo(Request $request, $type)
    {
        list($page, $limit) = UtilService::getMore([
            ['page', 0],
            ['limit', 0],
        ], $request, true);
        return app('json')->successful(UserPayOrder::getUserPayOrderList($request->uid(), $page, $limit, $type));
    }

    /**
     * 推广 佣金/提现 总和
     * @param Request $request
     * @param $type 3 佣金  4 提现
     * @return mixed
     */
    public function spread_count(Request $request, $type)
    {
        $count = 0;
        if ($type == 3) $count = UserBill::getRecordCount($request->uid(), 'now_money', 'brokerage');
        else if ($type == 4) $count = UserExtract::userExtractTotalPrice($request->uid());//累计提现
        $count = $count ? $count : 0;
        return app('json')->successful(['count' => $count]);
    }


    /**
     * 分销二维码海报生成
     * @param Request $request
     * @return mixed
     */
    public function spread_banner(Request $request)
    {
        list($type) = UtilService::getMore([
            ['type', 2],
        ], $request, true);
        $user = $request->user();
        $rootPath = app()->getRootPath();
        try {
            $resRoutine = true;//小程序
            $resWap = true;//公众号
            $siteUrl = sys_config('site_url');
            $routineSpreadBanner = sys_data('routine_spread_banner');
            if (!count($routineSpreadBanner)) return app('json')->fail('暂无海报');
            if ($type == 1) {
                //小程序
                $name = $user['uid'] . '_' . $user['is_promoter'] . '_user_routine.jpg';
                $imageInfo = SystemAttachment::getInfo($name, 'name');
                //检测远程文件是否存在
                if (isset($imageInfo['att_dir']) && strstr($imageInfo['att_dir'], 'http') !== false && UtilService::CurlFileExist($imageInfo['att_dir']) === false) {
                    $imageInfo = null;
                    SystemAttachment::where(['name' => $name])->delete();
                }
                if (!$imageInfo) {
                    $res = RoutineCode::getShareCode($user['uid'], 'spread', '', '');
                    if (!$res) return app('json')->fail('二维码生成失败');
                    $uploadType = (int)sys_config('upload_type', 1);
                    $upload = new Upload($uploadType, [
                        'accessKey' => sys_config('accessKey'),
                        'secretKey' => sys_config('secretKey'),
                        'uploadUrl' => sys_config('uploadUrl'),
                        'storageName' => sys_config('storage_name'),
                        'storageRegion' => sys_config('storage_region'),
                    ]);
                    $res = $upload->to('routine/spread/code')->validate()->stream($res['res'], $name);
                    if ($res === false) {
                        return app('json')->fail($upload->getError());
                    }
                    $imageInfo = $upload->getUploadInfo();
                    $imageInfo['image_type'] = $uploadType;
                    SystemAttachment::attachmentAdd($imageInfo['name'], $imageInfo['size'], $imageInfo['type'], $imageInfo['dir'], $imageInfo['thumb_path'], 1, $imageInfo['image_type'], $imageInfo['time'], 2);
                    RoutineQrcode::setRoutineQrcodeFind($res['id'], ['status' => 1, 'time' => time(), 'qrcode_url' => $imageInfo['dir']]);
                    $urlCode = $imageInfo['dir'];
                } else $urlCode = $imageInfo['att_dir'];
                if ($imageInfo['image_type'] == 1) $urlCode = $siteUrl . $urlCode;
                $siteUrlHttps = set_http_type($siteUrl, 0);
                $filelink = [
                    'Bold' => 'static' . DS . 'font' . DS . 'Alibaba-PuHuiTi-Regular.otf',
                    'Normal' => 'static' . DS . 'font' . DS . 'Alibaba-PuHuiTi-Regular.otf',
                ];
                if (!file_exists($filelink['Bold'])) return app('json')->fail('缺少字体文件Bold');
                if (!file_exists($filelink['Normal'])) return app('json')->fail('缺少字体文件Normal');
                foreach ($routineSpreadBanner as $key => &$item) {
                    $posterInfo = '海报生成失败:(';
                    $config = array(
                        'image' => array(
                            array(
                                'url' => $urlCode,     //二维码资源
                                'stream' => 0,
                                'left' => 114,
                                'top' => 790,
                                'right' => 0,
                                'bottom' => 0,
                                'width' => 120,
                                'height' => 120,
                                'opacity' => 100
                            )
                        ),
                        'text' => array(
                            array(
                                'text' => $user['nickname'],
                                'left' => 250,
                                'top' => 840,
                                'fontPath' => $rootPath . 'public' . DS . $filelink['Bold'],     //字体文件
                                'fontSize' => 16,             //字号
                                'fontColor' => '40,40,40',       //字体颜色
                                'angle' => 0,
                            ),
                            array(
                                'text' => '邀请您加入' . sys_config('site_name'),
                                'left' => 250,
                                'top' => 880,
                                'fontPath' => $rootPath . 'public' . DS . $filelink['Normal'],     //字体文件
                                'fontSize' => 16,             //字号
                                'fontColor' => '40,40,40',       //字体颜色
                                'angle' => 0,
                            )
                        ),
                        'background' => $item['pic']
                    );
                    $resRoutine = $resRoutine && $posterInfo = UtilService::setSharePoster($config, 'routine/spread/poster');
                    if (!is_array($posterInfo)) return app('json')->fail($posterInfo);
                    SystemAttachment::attachmentAdd($posterInfo['name'], $posterInfo['size'], $posterInfo['type'], $posterInfo['dir'], $posterInfo['thumb_path'], 1, $posterInfo['image_type'], $posterInfo['time'], 2);
                    if ($resRoutine) {
                        if ($posterInfo['image_type'] == 1)
                            $item['poster'] = $siteUrlHttps . $posterInfo['dir'];
                        else
                            $item['poster'] = set_http_type($posterInfo['dir'], 0);
                        $item['poster'] = str_replace('\\', '/', $item['poster']);
                    }
                }
            } else if ($type == 2) {
                //公众号
                $name = $user['uid'] . '_' . $user['is_promoter'] . '_user_wap.jpg';
                $imageInfo = SystemAttachment::getInfo($name, 'name');
                //检测远程文件是否存在
                if (isset($imageInfo['att_dir']) && strstr($imageInfo['att_dir'], 'http') !== false && curl_file_exist($imageInfo['att_dir']) === false) {
                    $imageInfo = null;
                    SystemAttachment::where(['name' => $name])->delete();
                }
                if (!$imageInfo) {
                    $codeUrl = set_http_type($siteUrl . '?spread=' . $user['uid'], 1);//二维码链接
                    $imageInfo = UtilService::getQRCodePath($codeUrl, $name);
                    if (is_string($imageInfo)) return app('json')->fail('二维码生成失败', ['error' => $imageInfo]);
                    SystemAttachment::attachmentAdd($imageInfo['name'], $imageInfo['size'], $imageInfo['type'], $imageInfo['dir'], $imageInfo['thumb_path'], 1, $imageInfo['image_type'], $imageInfo['time'], 2);
                    $urlCode = $imageInfo['dir'];
                } else $urlCode = $imageInfo['att_dir'];
                if ($imageInfo['image_type'] == 1) $urlCode = $siteUrl . $urlCode;
                $siteUrl = set_http_type($siteUrl, 1);
                $filelink = [
                    'Bold' => 'static' . DS . 'font' . DS . 'Alibaba-PuHuiTi-Regular.otf',
                    'Normal' => 'static' . DS . 'font' . DS . 'Alibaba-PuHuiTi-Regular.otf',
                ];
                if (!file_exists($filelink['Bold'])) return app('json')->fail('缺少字体文件Bold');
                if (!file_exists($filelink['Normal'])) return app('json')->fail('缺少字体文件Normal');
                foreach ($routineSpreadBanner as $key => &$item) {
                    $posterInfo = '海报生成失败:(';
                    $config = array(
                        'image' => array(
                            array(
                                'url' => $urlCode,     //二维码资源
                                'stream' => 0,
                                'left' => 114,
                                'top' => 790,
                                'right' => 0,
                                'bottom' => 0,
                                'width' => 120,
                                'height' => 120,
                                'opacity' => 100
                            )
                        ),
                        'text' => array(
                            array(
                                'text' => $user['nickname'],
                                'left' => 250,
                                'top' => 840,
                                'fontPath' => $rootPath . 'public' . DS . $filelink['Bold'],     //字体文件
                                'fontSize' => 16,             //字号
                                'fontColor' => '40,40,40',       //字体颜色
                                'angle' => 0,
                            ),
                            array(
                                'text' => '邀请您加入' . sys_config('site_name'),
                                'left' => 250,
                                'top' => 880,
                                'fontPath' => $rootPath . 'public' . DS . $filelink['Normal'],     //字体文件
                                'fontSize' => 16,             //字号
                                'fontColor' => '40,40,40',       //字体颜色
                                'angle' => 0,
                            )
                        ),
                        'background' => $item['pic']
                    );
                    $resWap = $resWap && $posterInfo = UtilService::setSharePoster($config, 'wap/spread/poster');
                    if (!is_array($posterInfo)) return app('json')->fail($posterInfo);
                    SystemAttachment::attachmentAdd($posterInfo['name'], $posterInfo['size'], $posterInfo['type'], $posterInfo['dir'], $posterInfo['thumb_path'], 1, $posterInfo['image_type'], $posterInfo['time'], 2);
                    if ($resWap) {
                        if ($posterInfo['image_type'] == 1)
                            $item['wap_poster'] = $siteUrl . $posterInfo['thumb_path'];
                        else
                            $item['wap_poster'] = UtilService::setHttpType($posterInfo['thumb_path'], 1);
                    }
                }
            }
            if ($resRoutine && $resWap) return app('json')->successful($routineSpreadBanner);
            else return app('json')->fail('生成图片失败');
        } catch (\Exception $e) {
            return app('json')->fail('生成图片时，系统错误', ['line' => $e->getLine(), 'message' => $e->getMessage(), 'file' => $e->getFile()]);
        }
    }


    /**
     * 积分记录
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function integral_list(Request $request)
    {
        list($page, $limit) = UtilService::getMore([
            ['page', 0], ['limit', 0]
        ], $request, true);
        return app('json')->successful(UserBill::userBillList($request->uid(), $page, $limit));

    }
}