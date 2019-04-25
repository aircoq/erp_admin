<?php

namespace app\publish\queue;

/**
 * 曾绍辉
 * 17-8-24
 * ebay重上队列
*/

use app\common\model\ebay\EbayAccount;
use app\common\model\ebay\EbayListing;
use app\common\model\ebay\EbayListingSetting;
use app\common\model\ebay\EbaySite;
use app\common\service\SwooleQueueJob;
use app\publish\helper\ebay\EbayPublish;
use app\publish\service\EbayPackApi;
use think\Exception;

class EbayRelistQueuer extends SwooleQueueJob
{
    protected $maxFailPushCount = 3;

    /**
     * @doc 队列优先级
     * @var int
     */
    protected static $priority = self::PRIORITY_HEIGHT;

    /**
     * @doc 获取优先级，越高越高！
     * @return int
     */
    public static function getPriority()
    {
        return static::$priority;
    }

    public function getName():string
    {
        return 'ebay重上队列';
    }

    public function getDesc():string
    {
        return 'ebay重上队列';
    }

    public function getAuthor():string
    {
        return 'wlw2533';
    }

    public static function swooleTaskMaxNumber():int
    {
        return 5;
    }
    
    public function execute()
    {
        try {
            $id = $this->params;
            $field = 'site,item_id,account_id';
            $list = EbayListing::where('id',$id)->field($field)->find();
            $account = EbayAccount::where('id',$list['account_id'])->find();
            $account = $account->toArray();
            $country = EbaySite::where('siteid',$list['site'])->value('country');
            $xml['item_id'] = $list['item_id'];
            $xml['country'] = $country;
            $verb = 'RelistItem';
            $response = (new EbayPackApi())->sendEbayApiCall($account,$xml,$verb,$list['site']);
            $message = '';
            if ($response) {
                $res = $response['RelistItemResponse'];
                if ($res['Ack'] == 'Failure') {
                    $errorMsg = EbayPublish::dealEbayApiError($res);
                    $message = json_encode($errorMsg,JSON_UNESCAPED_UNICODE);
                } else {
                    $update = [
                        'item_id' => $res['ItemID'],
                        'listing_status' => EbayPublish::PUBLISH_STATUS['publishSuccess'],
                        'start_date' => strtotime($res['StartTime']),
                        'end_date' => strtotime($res['EndTime']),
                    ];
                    EbayListing::update($update,['id'=>$id]);
                }
            } else {
                $message = '网络错误';
            }
            if ($message) {
                EbayPublish::updateListingStatusWithErrMsg('relistFail',$id,[],$message);
            }
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
	


}