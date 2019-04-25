<?php
/**
 * Created by PhpStorm.
 * User: wlw2533
 * Date: 2018/4/24
 * Time: 16:40
 */
namespace app\publish\task;

use app\index\service\AbsTasker;
use app\common\cache\Cache;
use app\publish\controller\EbayListing;
use service\ebay\EbayApi;
use think\Db;
use think\cache\driver;
use app\publish\queue\EbayQueuer;
use think\Exception;

class EbayComfirmPushPublishQueuer extends AbsTasker
{
    public function getName(){
        return 'ebay刊登队列确认入队';
    }

    public function getDesc()
    {
        return '扫描ebay刊登队列，确保每个都入队了，没有入队的重入队';
    }

    public function getCreator()
    {
        return 'wlw2533';
    }

    public function execute(){
        try {
            set_time_limit(0);
            $listingInfo = (new \app\common\model\ebay\EbayListing())->field('id,timing')->where(['listing_status'=>1,'timing' => ['gt', time()]])->select();
            foreach ($listingInfo as $listing) {
                $pubQueuer = new UniqueQueuer(EbayPublishItemQueuer::class);
                if (!$pubQueuer->exist($listing['id'])) {
                    $pubQueuer->push($listing['id'], $listing['timing']);
                }
            }
        }catch (Exception $e){
            return ['result'=>false,'message'=>$e->getFile()|$e->getLine()|$e->getMessage()];
        }

    }

    public function getParamRule(){
        return [];
    }
}