<?php
/**
 * Created by PhpStorm.
 * User: wlw2533
 * Date: 2018/7/21
 * Time: 15:50
 */

namespace app\index\controller;


use app\common\controller\Base;
use app\common\service\Common;
use app\index\service\EbayAccountHealthService;
use think\Request;
use think\Exception;


/**
 * @title ebay账号监控
 * @module 账号监控
 * @author wlw2533
 * @package app\index\controller
 */

class EbayAccountHealth extends Base
{
    private $service;
    private $userId;

    public function __construct()
    {
        parent::__construct();
        $userInfo = Common::getUserInfo();
        $this->userId = empty($userInfo) ? 0 : $userInfo['user_id'];//用户ID
        $this->service = new EbayAccountHealthService($this->userId);

    }


    /**
     * @title 查看列表
     * @url /ebay-account-health
     * @method GET
     * @param Request $request
     * @apiFilter app\index\filter\EbayAccountHealthFilter
     * @apiFilter app\index\filter\DepartFilter
     * @return \think\response\Json
     */
    public function getLists(Request $request)
    {
        try {
            $params = $request->param();
            $res = $this->service->getLists($params);
            return json(['result'=>true, 'data'=>$res], 200);
        } catch (Exception $e) {
            return json(['result'=>false, 'message'=>$e->getFile().'|'.$e->getLine().'|'.$e->getMessage()], 500);
        }
    }

    /**
     * @title 获取指定设置
     * @url /ebay-account-health/setting/:account_id/:region
     * @method GET
     * @param Request $request
     * @throws Exception
     */
    public function getAccountHealthSetting($account_id, $region)
    {
        try {
            $res = $this->service->getAccountHealthSetting($account_id, $region);
            return json(['result'=>true, 'data'=>$res], 200);
        } catch (Exception $e) {
            return json(['result'=>false, 'message'=>$e->getFile().'|'.$e->getLine().'|'.$e->getMessage()], 500);
        }
    }

    /**
     * @title 设置监测阈值
     * @url /ebay-account-health/setting/batch
     * @method POST
     * @param Request $request
     * @throws Exception
     */
    public function setAccountHealthSetting(Request $request)
    {
        try {
            $params = $request->param();
            $this->service->setAccountHealthSetting($params);
            return json(['result'=>true, 'message'=>'操作成功'], 200);
        } catch (Exception $e) {
            return json(['result'=>false, 'message'=>$e->getFile().'|'.$e->getLine().'|'.$e->getMessage()], 500);
        }
    }

    /**
     * @title 立即执行一次抓取
     * @url /ebay-account-health/sync/batch
     * @method POST
     * @return \think\response\Json
     */
    public function syncImmediately(Request $request)
    {
        try {
            $accountIds = $request->param('account_ids');
            $accountIds = json_decode($accountIds, true);
            if (empty($accountIds)) {
                throw new Exception('必须传递账号id');
            }
            $res = $this->service->syncImmediately($accountIds);
            return json($res);
        } catch (Exception $e) {
            return json(['result'=>false, 'message'=>$e->getFile().'|'.$e->getLine().'|'.$e->getMessage()], 500);
        }
    }

    /**
     * @title 导出数据
     * @url /ebay-account-health/export
     * @method GET
     * @param Request $request
     * @return \think\response\Json
     */
    public function export(Request $request)
    {
        try {
            $params = $request->param();
            $res = $this->service->export($params);
            return json(['data'=>$res], 200);
        } catch (Exception $e) {
            return json(['result'=>false, 'message'=>$e->getFile().'|'.$e->getLine().'|'.$e->getMessage()], 500);
        }
    }

    /**
     * @title 获取有权限的账号
     * @url /ebay-account-health/accounts
     * @method GET
     * @return \think\response\Json
     */
    public function getEbayHealthAccount(Request $request)
    {
        try {
            $params = $request->param();
            $res = $this->service->getEbayHealthAccount($params);
            return json($res);
        } catch (\Exception $e) {
            return json(['message'=>$e->getMessage()], 500);
        }
    }






}