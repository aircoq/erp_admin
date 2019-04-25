<?php
namespace app\publish\queue;


use app\common\model\ebay\EbayListingImage;
use app\common\service\SwooleQueueJob;
use app\common\cache\Cache;
use app\common\model\ebay\EbayListing;
use app\common\model\ebay\EbayListingSetting;
use app\publish\helper\ebay\EbayPublish;
use app\publish\service\EbayApiApply;
use app\publish\service\EbayPackApi;
use app\publish\service\EbayDealApiInformation;
use app\common\model\ebay\EbayAccount;
use app\publish\helper\ebay\EbayPublish as EbayPublishHelper;
use think\Exception;

class EbayPublishItemQueuer extends SwooleQueueJob
{
    protected $maxFailPushCount = 0;

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
        return 'ebay刊登listing队列';
    }

    public function getDesc():string
    {
        return 'ebay刊登listing队列';
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

        set_time_limit(0);
        $listingId = $this->params;
        EbayApiApply::addItem($listingId);
    }
}