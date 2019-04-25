<?php
/**
 * Created by PhpStorm.
 * User: wlw2533
 * Date: 2018/9/10
 * Time: 15:43
 */

namespace app\publish\queue;


use app\common\model\ebay\EbayAccount;
use app\common\model\ebay\EbayListing;
use app\common\service\SwooleQueueJob;
use app\publish\helper\ebay\EbayPublish;
use think\Exception;

class EbayFixPBSE extends SwooleQueueJob
{
    public function getName(): string {
        return 'ebay更新已刊登的违反了PBSE政策的listing';
    }

    public function getDesc(): string {
        return 'ebay更新已刊登的违反了PBSE政策的listing';
    }

    public function getAuthor(): string {
        return 'wlw2533';
    }

    public static function swooleTaskMaxNumber():int
    {
        return 4;
    }

    public function execute()
    {
        try {
            $itemIds = $this->params;
            if (!is_array($itemIds)) {
                return false;
            }
            $wh['item_id'] = ['in', $itemIds];
            $lists = EbayListing::field('item_id,primary_category,site,account_id')->where($wh)->select();
            if (empty($lists)) {
                return false;
            }
            $accountId = $lists[0]['account_id'];
            $site = $lists[0]['site'];
            $oauthToken = EbayAccount::where(['id' => $accountId])->value('oauth_token');
            foreach ($lists as $list) {
                $epid = (new EbayPublish())->searchEbayCatalogProduct($list['primary_category'], $site, $oauthToken);//获取epid
                if (empty($epid)) {
                    continue;
                }
                $updateData['item_id'] = $list['item_id'];
                $updateData['listing_sku'] = 0;
                $updateData['account_id'] = $list['account_id'];
                $updateData['site'] = $list['site'];
                $updateData['cron_time'] = 0;
                $updateData['remark'] = '添加epid';
                $updateData['api_type'] = 2;
                $updateData['create_id'] = 0;
                $updateData['new_val']['epid'] = $epid;
                (new EbayPublish())->writeUpdateLog($updateData);
                (new EbayPublish())->pushQueue(EbayUpdateOnlineListing::class, $updateData);
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

    }

}