<?php
/**
 * Created by PhpStorm.
 * User: zhangdongdong
 * Date: 2018/1/30
 * Time: 09:42
 */

namespace app\index\service;

use app\common\cache\Cache;
use think\Exception;
use app\common\model\ebay\EbayAccountNotification;

class EbaySetNotificationHelper
{

    public function __construct() {

    }

    public $events = [
        [
            'title' => 'listing通知',
            'events' => ['ItemAddedToWatchList', 'ItemClosed', 'ItemListed', 'ItemLost', 'ItemRemovedFromWatchList', 'ItemRevised', 'ItemSold', 'ItemUnsold', 'ItemWon'],
            //'url' => 'http://www.zrzsoft.com:8081/api/ebay/notification',
        ],
        [
            'title' => 'message通知',
            'events' => ['AskSellerQuestion', 'MyMessagesM2MMessage', 'MyMessageseBayMessage'],
            //'url' => 'http://www.zrzsoft.com:8081/api/ebay/notification',
        ],
        [
            'title' => 'order通知',
            'events' => ['AuctionCheckoutComplete', 'CheckoutBuyerRequestsTotal', 'EndOfAuction', 'FixedPriceTransaction'],
            //'url' => 'http://www.zrzsoft.com:8081/api/ebay/notification',
        ],
        [
            'title' => 'return通知',
            'events' => ['ReturnCreated', 'ReturnWaitingForSellerInfo', 'ReturnSellerInfoOverdue', 'ReturnShipped', 'ReturnDelivered', 'ReturnRefundOverdue', 'ReturnClosed', 'ReturnEscalated'],
            //'url' => 'http://www.zrzsoft.com:8081/api/ebay/notification',
        ],
        [
            'title' => 'INR通知',
            'events' => ['INRBuyerOpenedDispute', 'INRBuyerRespondedToDispute', 'INRBuyerClosedDispute', 'INRSellerRespondedToDispute'],
            //'url' => 'http://www.zrzsoft.com:8081/api/ebay/notification',
        ],
        [
            'title' => 'dispute通知',
            'events' => ['BuyerResponseDispute', 'SellerClosedDispute', 'SellerOpenedDispute', 'SellerRespondedToDispute'],
            //'url' => 'http://www.zrzsoft.com:8081/api/ebay/notification',
        ],
    ];

    /**
     * 获取通知项目；
     * @param $account_id 帐号ID
     * @param int $sync 0不同步，直接取本地数据；1与Ebay网同步数据，再反回；
     * @return array
     */
    public function getNotificationField($account_id, $sync = 0) {
        //同步后反回；
        if($sync) {
            return $this->syncNotification($account_id);
        }

        //查本地数据反回；
        $enModel = new EbayAccountNotification();
        $list = $enModel->where(['ebay_account_id' => $account_id])->column('enable', 'event');
        //当前数据库没有数据时，会去同步一次；
        if(empty($list)) {
            return $this->syncNotification($account_id);
        }
        $result = $this->makeResult();
        foreach($result as $key=>$val) {
            foreach($val['events'] as $key2=>$tmp) {
                $result[$key]['events'][$key2]['enable'] = $list[$tmp['event']] ?? 0;
            }
        }
        return $result;
    }

    private function makeResult(){
        $result = [];
        foreach($this->events as $val){
            $data = [];
            $data['title'] = $val['title'];
            foreach($val['events'] as $event) {
                $data['events'][] = ['event' => $event, 'enable' => 0];
            }
            $result[] = $data;
        }
        return $result;
    }

    /**
     * 设置通知项
     * @param $account_id 帐号ID
     * @param $params 【'event' => '通知名', enable => (0关1开)]
     * @param string $url 设置的通知Url参数；
     * @return array|bool 设置成功，返回数据，不成功返回false;
     * @throws Exception
     */
    public function setNotificationEvent($account_id, $params, $url = '') {
        $url = !empty($url)? $url : 'https://api.rondaful.com/api/ebay/notification';

        if(empty($params[0]['events'])) {
            throw new Exception('设置参数错误!');
        }
        //代码里的events,会保存至数据库;
        $kvEvents = [];
        foreach($this->events as $val) {
            foreach($val['events'] as $event) {
                $kvEvents[$event] = 0;
            }
        }
        //传过来的events，会提交至ebay;
        $pEvents = [];
        foreach($params as $val) {
            foreach($val['events'] as $event) {
                $pEvents[$event['event']] = $event['enable'];
            }
        }

        //同步$kvEvents
        foreach($kvEvents as $key=>$val) {
            $kvEvents[$key] = $pEvents[$key] ?? 0;
        }

        //转换需提交的数据；
        foreach($pEvents as $key=>$val) {
            $pEvents[$key] = ($val == 1) ? 'Enable' : 'Disable';
        }

        try{
            $result = $this->processingResults($account_id, $pEvents, $url);
            if(!isset($result['SetNotificationPreferencesResponse'])) {
                throw new Exception('Ebay系统访问出错，请重试！');
            }
            //保存成功，把数据保存到数组据；
            if($result['SetNotificationPreferencesResponse']['Ack'] == 'Success') {
                if($this->saveNotificationLists($account_id, $kvEvents, $url)) {
                    $result = $this->makeResult();
                    foreach($result as $key=>$val) {
                        foreach($val['events'] as $key2=>$tmp) {
                            $result[$key]['events'][$key2]['enable'] = $kvEvents[$tmp['event']] ?? 0;
                        }
                    }
                    return $result;
                }
                return false;
            } else {
                throw new Exception(json_encode($result['SetNotificationPreferencesResponse']['Errors']));
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 同步帐号数据
     * @param $account_id 帐号ID
     * @return array
     */
    public function syncNotification($account_id) {
        $acInfo = Cache::store('EbayAccount')->getAccountById($account_id);
        $tokenArr = json_decode($acInfo['token'], true);
        $token = trim($tokenArr[0]) ? $tokenArr[0] : $acInfo['token'];

        //先从Ebay同步Url
        $url = $this->getUrlRequest($acInfo, $token);
        //同步开启的项目；
        $getLists = $this->getEventRequest($acInfo, $token);

        //同步的数据为空，则返回空数组；
        if(empty($getLists)) {
            return $this->makeResult();
        }
        $synclist = [];
        foreach($getLists as $val) {
            $synclist[$val['EventType']] = ($val['EventEnable'] == 'Enable')? 1 : 0;
        }

        $events = [];
        $result = $this->makeResult();
        foreach($result as $key=>$val){
            foreach($val['events'] as $key2=>$tmp) {
                if(isset( $synclist[$tmp['event']])) {
                    $enable = $synclist[$tmp['event']];
                    //每保存一个，清除一下已存在代码设置里的
                    unset($synclist[$tmp['event']]);
                } else {
                    $enable = 0;
                }
                $result[$key]['events'][$key2]['enable'] = $enable;
                $events[$tmp['event']] = $enable;
            }
        }

        //最后检查有无不存在设置里面的加上去
        if(!empty($synclist)) {
            $data = ['title' => '不在配置中'];
            foreach($synclist as $key=>$val) {
                $data['events'][] = [
                    'event' => $key,
                    'enable' => $val
                ];
            }
            $result[] = $data;
        }
        unset($acInfo, $tokenArr, $token, $getLists, $synclist, $data);

        //保存数据；
        $this->saveNotificationLists($account_id, $events, $url);
        return $result;
    }

    /**
     * 取URl
     * @param $account
     * @param $token
     * @param string $level
     * @return string
     */
    private function getUrlRequest($account, $token, $level = 'Application')
    {
        $resText = $this->makeGetRequest($account, $token, $level);
        if(isset($resText['GetNotificationPreferencesResponse']['ApplicationDeliveryPreferences'])) {
            return $resText['GetNotificationPreferencesResponse']['ApplicationDeliveryPreferences']['ApplicationURL'];
        }
        return '';
    }

    /**
     * 取项目参数；
     * @param $account
     * @param $token
     * @param string $level
     * @return array
     */
    private function getEventRequest($account, $token, $level = 'User')
    {
        $resText = $this->makeGetRequest($account, $token);
        $getLists = $resText['GetNotificationPreferencesResponse']['UserDeliveryPreferenceArray']['NotificationEnable'] ?? [];
        if(isset($getLists['EventType'])) {
            $getLists = [$getLists];
        }
        return $getLists;
    }

    /**
     * 发出获取设置请求
     * @param $account
     * @param $token
     * @param string $level
     * @return mixed
     * @throws Exception
     */
    private function makeGetRequest($account, $token, $level = 'User')
    {
        $xml = <<<begin
<?xml version="1.0" encoding="utf-8"?>
<GetNotificationPreferencesRequest xmlns="urn:ebay:apis:eBLBaseComponents">
<RequesterCredentials>
<eBayAuthToken>{$token}</eBayAuthToken>
</RequesterCredentials>
<Version>957</Version>
<PreferenceLevel>{$level}</PreferenceLevel>
</GetNotificationPreferencesRequest>
begin;
        $ebayApi = $this->createApi($account, $token, 'GetNotificationPreferences'); // 创建API对象
        $resText = $ebayApi->createHeaders()->__set("requesBody", $xml)->sendHttpRequest2();
        if(!isset($resText['GetNotificationPreferencesResponse'])) {
            throw new Exception('Ebay系统访问出错，请重试！|'. json_encode($resText, JSON_UNESCAPED_UNICODE));
        }
        if($resText['GetNotificationPreferencesResponse']['Ack'] != 'Success') {
            throw new Exception(json_encode($resText['GetNotificationPreferencesResponse']['Errors']));
        }
        return $resText;
    }

    /**
     * 保存参数；
     * @param $account_id
     * @param $events
     * @param string $url
     * @return bool
     * @throws Exception
     */
    public function saveNotificationLists($account_id, $events, $url  = '')
    {
        try{
            $time = time();
            $exclude = [];
            $enModel = new EbayAccountNotification();
            $lists = $enModel->where(['ebay_account_id' => $account_id])->select();
            foreach($lists as $val) {
                //当数据已存在时，开启状太不一样才修改；
                $exclude[] = $val['event'];
                if(isset($events[$val['event']])) {
                    $eventVal = $events[$val['event']];
                    if($eventVal != $val['enable']) {
                        $enModel->update(['enable' => $eventVal, 'url' => $url, 'update_time' => $time], ['id' => $val['id']]);
                    }
                }
            }

            foreach($events as $key=>$val) {
                if(in_array($key, $exclude)) continue;
                $insertData = [
                    'ebay_account_id' => $account_id,
                    'event' => $key,
                    'enable' => $val,
                    'url' => $url,
                    'create_time' => $time,
                    'update_time' => $time,
                ];
                $enModel->insert($insertData);
            }
            return true;
        } catch(Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /*
     * title 创建获取在线Item信息的xml
     * @param token 账号秘钥
     * @return string
     */
    private function createXml($token, $events, $url)
    {
        $requesBody = '<?xml version="1.0" encoding="utf-8"?>';
        $requesBody .= '<SetNotificationPreferencesRequest xmlns="urn:ebay:apis:eBLBaseComponents">';
        $requesBody .= '<RequesterCredentials>';
        $requesBody .= '<eBayAuthToken>' . $token . '</eBayAuthToken>';
        $requesBody .= '</RequesterCredentials>';
        $requesBody .= '<ApplicationDeliveryPreferences>';
        $requesBody .= '<ApplicationEnable>Enable</ApplicationEnable>';
        $requesBody .= '<ApplicationURL>'. $url. '</ApplicationURL>';
        $requesBody .= '<DeviceType>Platform</DeviceType>';
        $requesBody .= '</ApplicationDeliveryPreferences>';
        $requesBody .= '<UserDeliveryPreferenceArray>';
        foreach($events as $event => $status) {
            $requesBody .= '<NotificationEnable>';
            $requesBody .= '<EventEnable>'. $status. '</EventEnable>';
            $requesBody .= '<EventType>'. $event . '</EventType>';
            $requesBody .= '</NotificationEnable>';
        }
        $requesBody .= '</UserDeliveryPreferenceArray>';
        $requesBody .= '</SetNotificationPreferencesRequest>';
        return $requesBody;
    }

    private function processingResults($account_id, $events, $notificationUrl)
    {
        $acInfo = Cache::store('EbayAccount')->getAccountById($account_id);
        $tokenArr = json_decode($acInfo['token'], true);
        $token = trim($tokenArr[0]) ? $tokenArr[0] : $acInfo['token'];
        $ebayApi = $this->createApi($acInfo, $token, 'SetNotificationPreferences'); // 创建API对象
        $xml = $this->createXml($token, $events, $notificationUrl);
        $resText = $ebayApi->createHeaders()->__set("requesBody", $xml)->sendHttpRequest2();
        return $resText;
    }

    /*
     * title 实例化API对象
     * @param list listing详细信息
     * @param account_id 销售账号ID
     * @return $ebayApi obj
     */
    private function createApi(&$acInfo, $token, $api)
    {
        $config['devID'] = $acInfo['dev_id'];
        $config['appID'] = $acInfo['app_id'];
        $config['certID'] = $acInfo['cert_id'];
        $config['userToken'] = $token;
        $config['compatLevel'] = 957;
        $config['siteID'] = 0;
        $config['verb'] = $api;
        $config['appMode'] = 0;
        $config['account_id'] = $acInfo['id'];
        return new \service\ebay\EbayApi($config);
    }
}