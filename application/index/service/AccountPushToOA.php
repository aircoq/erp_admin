<?php


namespace app\index\service;

use think\Exception;
use app\goods\service\GoodsHelp;
use org\Curl;
use app\common\traits\ConfigCommon;

class AccountPushToOA
{
    use ConfigCommon;

    public function getChannelCode($channelId)
    {
        $GoodsHelp = new GoodsHelp();
        $aChannel = $GoodsHelp->getChannel();
        foreach ($aChannel as $k => $v) {
            if ($v['id'] == $channelId) {
                return $k;
            }
        }
        return '';
    }

    public function push($accountInfo, $start_time, $end_time,$option = [])
    {
        $channel_id = $accountInfo['channel_id'];
        if (!$channel_id) {
            throw new Exception('该帐号对应平台为空');
        }
        $channelCode = $this->getChannelCode($channel_id);
        if ($channelCode) {
            $strFun = 'push' . ucfirst($channelCode);
            if (in_array($strFun, get_class_methods(self::class))) {
                return $this->$strFun($accountInfo, $start_time, $end_time,$option);
            }
        }
    }

    private function getApiIpCfg()
    {
        $this->setConfigIdentification('api_ip');
        return $this->getConfigData();
    }

    public function pushWish($accountInfo, $start_time, $end_time,$option = [])
    {
        // $accountInfo['server_name'] = '172.19.23.101';
        $url = "http://" . $accountInfo['server_name'] . ":10088/download/wish";

        //$accountInfo['account_name'] = 'cathyhui';
        //$accountInfo['account_code'] = '176wishlu';
        //$accountInfo['password'] = 'bevvller1011';
        $wishData = [
            [
                'account' => $accountInfo['account_name'],
                'abbreviation' => $accountInfo['account_code'],
                'password' => $accountInfo['password'],
                'startTime' => $start_time,
                'endTime' => $end_time]
        ];
        $ip = $this->getApiIpCfg();
        //$ip = "http://erp.com";
        $data = [
            'wish' => json_encode($wishData),
            'callbackurl' => $ip . '/api/post?url=download_order_callback&channel=wish'
        ];
        $xml = Curl::curlPost($url, http_build_query($data));
        $result = json_decode($xml, true);
        return $result;
    }

    public function pushAliExpress($accountInfo, $start_time, $end_time, $option = [])
    {
       // $accountInfo['server_name'] = '172.19.23.101';
        $url = "http://" . $accountInfo['server_name'] . ":10088/download/aliexpress";

        //$accountInfo['account_name'] = 'tangmon@outlook.com';
       // $accountInfo['account_code'] = 'TJHJIAJU';
       // $accountInfo['password'] = 'tre%F&F853';
        $wishData = [
            [
                'account' => $accountInfo['account_name'],
                'abbreviation' => $accountInfo['account_code'],
                'password' => $accountInfo['password'],
                'startTime' => $start_time,
                'endTime' => $end_time,
                'fundaccount'=>''
            ]
        ];
        if(isset($option['fundaccount'])){
            $wishData[0]['fundaccount'] = $option['fundaccount'];
        }
        $ip = $this->getApiIpCfg();
       // $ip = "http://erp.com";
        $data = [
            'aliexpress' => json_encode($wishData),
            'callbackurl' => $ip . '/api/post?url=download_order_callback&channel=aliexpress&account_code='.$accountInfo['account_code']
        ];
        $xml = Curl::curlPost($url, http_build_query($data));
        $result = json_decode($xml, true);
        return $result;
    }
}