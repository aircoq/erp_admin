<?php
/**
 * Created by PhpStorm.
 * User: rondaful_user
 * Date: 2018/10/10
 * Time: 19:49
 */

namespace app\publish\task;


use app\common\cache\Cache;
use app\common\model\ChannelUserAccountMap;
use app\common\model\ebay\EbayListing;
use app\common\model\ebay\EbayListingVariation;
use app\common\service\ChannelAccountConst;
use app\index\service\AbsTasker;
use app\publish\helper\ebay\EbayPublish;
use think\Exception;

class EbayPublishSPUStatistics extends AbsTasker
{
    public function getName()
    {
        return 'eBay刊登SPU上下架统计';
    }

    public function getDesc()
    {
        return 'eBay刊登SPU上下架统计';
    }

    public function getCreator()
    {
        return 'wlw2533';
    }

    public function getParamRule()
    {
        return [];
    }
    
    public function execute()
    {
        try {
            //按天统计
            $wh = [
                'draft' => 0,
                'item_id' => ['<>',0],
                'listing_status' => ['in',EbayPublish::OL_PUBLISH_STATUS],
                'goods_id' => ['<>',0],
                'account_id' => ['<>',0],
            ];
            $startTime = Cache::handler()->get('ebay:publish:spu:statistics:starttime');
            if (!$startTime) {
                $startTime = EbayListing::where($wh)->order('start_date asc')->value('start_date');
                $startTime = strtotime(date('Y-m-d',$startTime));
            }
            $endTime = $startTime+86400-1;

            $wh['start_date'] = ['between',[$startTime,$endTime]];

            $field = 'goods_id,account_id,realname,count(goods_id) as spu_count,start_date';
            $group = 'goods_id,account_id';
            //统计上架的
            $listings = EbayListing::field($field)->where($wh)->group($group)->select();
            if (!$listings) {
                Cache::handler()->set('ebay:publish:spu:statistics:starttime',$endTime+1);
                return;
            }

            foreach ($listings as $listing) {
                if (!$listing['realname']) {
                    $tmpWh = [
                        'channel_id' => ChannelAccountConst::channel_ebay,
                        'account_id' => $listing['account_id'],
                    ];
                    $listing['realname'] = ChannelUserAccountMap::where($tmpWh)->value('seller_id');
                }
                \app\report\service\StatisticShelf::addReportShelf(1,
                                                                    $listing['account_id'],
                                                                    $listing['realname'],
                                                                    $listing['goods_id'],
                                                                    $listing['spu_count'],
                                                                        0,
                                                                    $listing['start_date']
                                                                     );
            }
            if ($endTime+1 > time()) {
                return;
            }
            Cache::handler()->set('ebay:publish:spu:statistics:starttime',$endTime+1);

//           //统计下架的
//            unset($wh['start_date']);
//            $wh['listing_status'] = ['in',[9,11]];
//            $wh['manual_end_time'] = ['gt', $todayTime];
//            $whOr['end_date'] = ['between', [$todayTime,$todayTime+86400]];
//            $listings = EbayListing::field($field)->where($wh)->whereOr($whOr)->group($group)->select();
//            //调用接口
//            foreach ($listings as $listing) {
//                if (empty($listing['realname']) || empty($listing['account_id'])
//                    || empty($listing['goods_id']) || empty($listing['spu_count'])) {
//                    continue;
//                }
//                \app\report\service\StatisticPicking::addReportPicking(1,
//                    $listing['account_id'],
//                    $listing['realname'],
//                    $listing['goods_id'],
//                    $listing['spu_count']);
//            }
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

}