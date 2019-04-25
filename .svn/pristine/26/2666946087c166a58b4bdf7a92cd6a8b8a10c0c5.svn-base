<?php


namespace app\api\service;

use app\common\cache\Cache;
use think\exception;
use app\report\service\WishSettlementReportService;
use app\report\service\AliexpressSettlementReportService;

class Account extends Base
{
    /**
     * @title 下载订单回调
     * @author starzhan <397041849@qq.com>
     */
    public function downLoadCallback()
    {
        try {
            if(!isset($_GET['channel'])){
                throw new Exception('缺少必要参数channel');
            }
            $content = file_get_contents('php://input');
            $xml = json_decode($content,true);
            Cache::store('SettleReport')->setIsRunningEnviroment(40,$xml);
            $xml['WishInfo'] = base64_decode($xml['WishInfo']);
            $channel = strtolower($_GET['channel']);
            if(!is_array($xml)){
                throw new Exception('返回值有误');
            }
            switch ($channel){
                case 'wish':
                    $WishSettlementReportService = new WishSettlementReportService();
                    $res = $WishSettlementReportService->saveSettlementFile($xml['FileName'],$xml['WishInfo']);
                  //file_put_contents($xml['FileName'],$xml['WishInfo']);
                    Cache::store('SettleReport')->setIsRunningEnviroment(31,$xml['WishInfo']);
                    Cache::store('SettleReport')->setIsRunningEnviroment(32,$xml['FileName']);
                    Cache::store('SettleReport')->setIsRunningEnviroment(33, json_encode($res));
                    break;
                case 'aliexpress':
                    $account_code = $_GET['account_code']??'';
                    $AliexpressSettlementReportService = new AliexpressSettlementReportService();
                    $res = $AliexpressSettlementReportService->saveSettlementFile($xml['FileName'],$xml['WishInfo'],$account_code);
                    Cache::store('SettleReport')->setIsRunningEnviroment(41,json_encode($xml['WishInfo']));
                    Cache::store('SettleReport')->setIsRunningEnviroment(42,$xml['FileName']);
                    Cache::store('SettleReport')->setIsRunningEnviroment(43, json_encode($res));
                    break;
            }
            $this->retData['data'] = '成功';
            return $this->retData;

        } catch (Exception $exception) {
            $this->retData = [
                'postData' => [],
                'status' => 0,
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'message' => $exception->getMessage()
            ];
            $res = $this->retData;
            Cache::store('SettleReport')->setIsRunningEnviroment(90882, json_encode($res));
            return $this->retData;
        }
    }
}