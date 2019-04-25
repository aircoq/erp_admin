<?php

/**
 * Description of EbayRsyncListing
 * @datetime 2017-7-5  10:14:17
 * @author joy
 */

namespace app\listing\task;
use app\common\cache\Cache;
use app\common\model\ebay\EbayAccount;
use app\common\model\ebay\EbayListing;
use app\common\service\UniqueQueuer;
use app\index\service\AbsTasker;
use app\listing\queue\EbayRsyncListingQueue;
use app\listing\service\RedisListing;
use app\listing\service\EbayListingHelper;
use app\publish\helper\ebay\EbayPublish;
use app\publish\queue\EbayGetItemQueue;
use app\publish\service\EbayPackApi;
use think\Exception;

class EbayRsyncListing extends AbsTasker{
    /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return "ebay同步listing信息";
    }
    /**
     * 定义任务描述
     * @return string
     */
    public function getDesc()
    {
        return "ebay同步listing信息";
    }
    /**
     * 定义任务作者
     * @return string
     */
    public function getCreator()
    {
        return "wlw2533";
    }
    /**
     * 定义任务参数规则
     * @return array
     */
    public function getParamRule()
    {
        return [];
    }
    /**
     * 任务执行内容
     * @return void
     */
    public  function execute()
    {
        set_time_limit(0);
        try {
            //同步前先对本地数据库去重
            $wh = [
                'draft' => 0,
                'listing_status' => ['in',[3,5,6,7,8,9,10]],
            ];
            $field = 'id,item_id,count(item_id) as count';
            $listings = EbayListing::field($field)->where($wh)->group('item_id')->having('count > 1')->select();
            if ($listings) {
                $listings = collection($listings)->toArray();
                $itemIds = array_column($listings,'item_id');

                //查出所有重复的
                $field = 'id,item_id,application';
                $lists = EbayListing::field($field)->whereIn('item_id',$itemIds)->select();
                $lists = collection($lists)->toArray();

                $itemIds = [];
                $reservIds = [];
                foreach ($lists as $list) {
                    if (in_array($list['item_id'],$itemIds)) {//已经存在了一个
                        //判断是不是erp刊登，如果是erp刊登，替换掉保留的
                        if ($list['application']) {
                            $reservIds[$list['item_id']] = $list['id'];
                        }
                    } elseif (!$list['item_id']) {//没有统计到且不为空才做处理
                        $itemIds[] = $list['item_id'];//加入统计
                        $reservIds[$list['item_id']] = $list['id'];//第一次统计到的保留
                    }
                }
                $allIds = array_column($lists,'id');
                $delIds = array_values(array_diff($allIds,array_values($reservIds)));
                (new EbayPublish())->delListings($delIds);//删除重复的
            }


            //以账号为单位进行同步
            $accountWh = [
                'is_invalid' => 1,
                'account_status' => 1,
            ];
            $accountIds = EbayAccount::where($accountWh)->column('id');
            if (!$accountIds) {
                return;
            }
            foreach ($accountIds as $accountId) {
                //以加队列的方式处理
                $params = [
                    'userId' => 0,
                    'accountId' => $accountId,
                ];
                (new UniqueQueuer(EbayRsyncListingQueue::class))->push($params);
            }
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    
}
