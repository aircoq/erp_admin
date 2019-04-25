<?php

namespace app\publish\queue;

/**
 * 曾绍辉
 * 17-8-5
 * ebay图片上传队列
*/
use app\common\model\ebay\EbayListingSetting;
use app\common\service\CommonQueueJob;
use app\common\exception\TaskException;
use app\publish\service\EbayListingCommonHelper;
use think\Db;
use app\common\service\SwooleQueueJob;
use app\common\cache\Cache;
use service\ebay\EbayApi;
use think\cache\driver;
use app\index\service\AbsTasker;
use app\common\service\UniqueQueuer;
use app\common\model\ebay\EbayListing;
use app\common\model\ebay\EbayListingImage;
use app\common\model\ebay\EbayAccount;
use app\publish\helper\ebay\EbayPublish;
use app\publish\service\EbayApiApply;
use think\Exception;

class EbayImgQueuer extends SwooleQueueJob
{
    public static function swooleTaskMaxNumber():int
    {
        return 4;
    }

    public function getName():string
    {
        return 'ebay图片上传至EPS';
    }

    public function getDesc():string
    {
        return 'ebay图片上传至EPS';
    }

    public function getAuthor():string
    {
        return 'wlw2533';
    }

    public  function execute()
    {
        $listingId = $this->params;

        $fieldImg = 'm.id,path,thumb,eps_path,ser_path,name,value,sort,de_sort,main,main_de,detail,message,l.account_id,l.site';
        $imgs = EbayListingImage::alias('m')->where('listing_id', $listingId)->field($fieldImg)->join('ebay_listing l','l.id=m.listing_id','left')->select();
        if (!$imgs) {
            return;
        }
        $imgs = collection($imgs)->toArray();

        $account = EbayAccount::where('id',$imgs[0]['account_id'])->field(EbayPublish::ACCOUNT_FIELD_TOKEN)->find();
        $config = $account->toArray();
        $config['site_id'] = $imgs[0]['site'];
        EbayApiApply::UploadSiteHostedPictures($imgs, $config);
        //保存图片
        (new EbayListingImage())->allowField(true)->saveAll($imgs);
    }

}
