<?php
/**
 * Created by PhpStorm.
 * User: wlw2533
 * Date: 2018/7/13
 * Time: 15:36
 */

namespace app\publish\queue;


use app\common\cache\Cache;
use app\common\model\ebay\EbayActionLog;
use app\common\model\ebay\EbayListing;
use app\common\model\ebay\EbayAccount;
use app\common\model\ebay\EbayListingImage;
use app\common\model\ebay\EbayListingSetting;
use app\common\model\ebay\EbayModelStyle;
use app\common\service\SwooleQueueJob;

use app\common\service\UniqueQueuer;
use app\goods\service\GoodsImage;
use app\listing\queue\EbayEndItemQueue;
use app\publish\helper\ebay\EbayPublish;
use app\publish\service\EbayApiApply;
use app\publish\service\EbayDealApiInformation;
use app\publish\service\EbayPackApi;
use think\Db;
use think\Exception;

class EbayUpdateOnlineListing extends SwooleQueueJob
{
    protected $maxFailPushCount = 0;
    /**
     * @doc 每次task执行队列消费最大次数
     * @var int
     */
    protected static $maxRunnerCount = 1000;

    public function getName():string
    {
        return 'ebay listing在线更新';
    }
    public function getDesc():string
    {
        return 'ebay Listing在线更新';
    }
    public function getAuthor():string
    {
        return 'wlw2533';
    }

    public static function swooleTaskMaxNumber():int
    {
        return 30;
    }

    public function execute()
    {
        try {
            $id = $this->params;
            if (empty($id)) {
                throw new Exception("获取日志id失败");
            }
            $this->updateOnlineListing($id);

        } catch (\Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }catch (\Throwable $e){
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }


    public function updateOnlineListing(int $id)
    {
        $log = EbayActionLog::get($id);
        if (empty($log)) {
            return false;//没办法记录错误信息，直接返回
        }
        try {
            EbayActionLog::update(['run_time' => time()],['id'=>$id]);//更新执行时间
            $field = 'id,item_id,account_id,listing_type,application,site,picture_gallery,variation';
            $listingInfo = EbayListing::where('item_id',$log['item_id'])->field($field)->find();
            if (empty($listingInfo)) {
                EbayActionLog::update(['message' => 'listing信息获取失败', 'status' => 3],['id'=>$id]);
                EbayListing::update(['listing_status'=>EbayPublish::PUBLISH_STATUS['updateFail']],['item_id'=>$log['item_id']]);
                return false;
            }
            $listingInfo->save(['listing_status' => EbayPublish::PUBLISH_STATUS['updating']]);
            $accountInfo = EbayAccount::get($listingInfo['account_id']);
            if (empty($accountInfo)) {
                EbayActionLog::update(['message' => '获取账号信息失败', 'status' => 3],['id'=>$id]);
                EbayListing::update(['listing_status'=>EbayPublish::PUBLISH_STATUS['updateFail']],['item_id'=>$log['item_id']]);
                throw new Exception('获取账号信息失败');
            }
            $accountInfo = $accountInfo->toArray();

            $newVal = json_decode($log['new_val'], true);//待更新的数据

            if ($log['api_type'] == 1) {//更新一口价商品库存或价格
                $verb = 'ReviseInventoryStatus';
            } else if ($listingInfo['listing_type'] == 1) {//一口价商品
                $verb = 'ReviseFixedPriceItem';
            } else {//拍卖
                $verb = 'ReviseItem';
            }

            //处理图片
            if (isset($newVal['style']['imgs']) || isset($newVal['imgs'])) {
                $imgs = $newVal['style']['imgs'] ?? $newVal['imgs'];
                $accountInfo['site_id'] = $listingInfo['site'];
                EbayApiApply::UploadSiteHostedPictures($imgs,$accountInfo);
//                $res = (new EbayPackApi())->uploadImgsToEps($imgs,$accountInfo, $listingInfo['site']);
//                if ($res['result'] === false) {
//                    throw new Exception($res['message']);
//                }
                isset($newVal['style']['imgs']) && $newVal['style']['imgs'] = $imgs;
                isset($newVal['imgs']) && $newVal['imgs'] = $imgs;
            }
            $packApi = new EbayPackApi();
            $api = $packApi->createApi($accountInfo, $verb, $listingInfo['site']);
            $xmlInfo = [
                'newVal' => $newVal,
                'listing' => $listingInfo,
                'accountInfo' => $accountInfo
            ];
            $xml = $packApi->createXml($xmlInfo);
            $response = $api->createHeaders()->__set('requesBody', $xml)->sendHttpRequest2();
        } catch (\Exception $e) {
            EbayActionLog::update(['message' => $e->getMessage(), 'status' => 3],['id'=>$id]);
            EbayListing::update(['listing_status'=>EbayPublish::PUBLISH_STATUS['updateFail']],['item_id'=>$log['item_id']]);
//            if (isset($listingInfo['id'])) {
//                EbayPublish::setListingStatus($listingInfo['id'], 'updateFail');
//            }
        }
        $res = (new EbayDealApiInformation())->dealWithApiResponse($verb,$response, ['listing'=>$listingInfo, 'logId'=>$id]);

        if ($res['result'] === true) {//更新成功
            EbayActionLog::update(['status'=>2],['id'=>$id]);
            EbayPublish::setListingStatus($listingInfo['id'],'publishSuccess');
            if (isset($newVal['is_virtual_send'])) {
                EbayListing::update(['is_virtual_send'=>$newVal['is_virtual_send']],['id'=>$listingInfo['id']]);
            }
            if (isset($newVal['imgs'])) {//图片有更新
                $usedIds = [];
                foreach ($newVal['imgs'] as $img) {
                    if (!empty($img['id'])) {
                        $usedIds[] = $img['id'];
                    }
                }

                try {
                    Db::startTrans();
                    EbayListingImage::destroy(['id'=>['not in',$usedIds],'main_de'=>['<>',1],'listing_id'=>$listingInfo['id']]);
                    (new EbayListingImage())->saveAll($newVal['imgs']);
                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                }

            }
        } else {
            if ($log['api_type'] == 1 && strpos($res['message'],'SKU does not exist in Non-ManageBySKU item specified by ItemID') !== false) {
                //仅剩一个SKU有库存时，调0会返回错误，直接下架
                if ($log['remark'] == '停售调0') {
                    EbayActionLog::update(['api_type'=>4,'remark'=>'停售调0失败下架'],['id'=>$id]);
                } else {
                    EbayActionLog::update(['api_type'=>5,'remark'=>'本地SKU状态变化调0失败下架'],['id'=>$id]);
                }
                (new UniqueQueuer(EbayEndItemQueue::class))->push($id);
            } else {
                EbayActionLog::update(['status' => 3, 'message' => $res['message']], ['id' => $id]);
            }
        }
    }
}