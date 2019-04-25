<?php
/**
 * Created by PhpStorm.
 * User: wlw2533
 * Date: 2018/10/11
 * Time: 9:07
 */

namespace app\publish\task;

use app\common\model\amazon\AmazonActionLog;
use app\common\model\amazon\AmazonListing;
use app\common\model\amazon\AmazonPublishProduct;
use app\common\model\report\ReportStatisticPublishByPicking;
use app\common\model\report\ReportStatisticPublishByShelf;
use app\common\service\ChannelAccountConst;
use app\index\service\AbsTasker;
use app\publish\service\AmazonPublishConfig;
use think\Exception;

class AmazonPublishSPUStatistics extends AbsTasker
{
    public function getName()
    {
        return 'Amazon刊登SPU上下架统计';
    }

    public function getDesc()
    {
        return 'Amazon刊登SPU上下架统计';
    }

    public function getCreator()
    {
        return '冬';
    }

    public function getParamRule()
    {
        return [];
    }

    public function execute()
    {
        try {
            //找出今天零点前一秒；
            $todayTime = strtotime(date('Y-m-d', time())) - 1;
            //找出昨天零点；
            $yesterday = $todayTime + 1 - 86400;

            //一天执行一次排重，这数据因为是第二天早上9点后可能需要看，所以不能在第二天下午执行,而要在早上执行；
            $oldCount = ReportStatisticPublishByShelf::where(['channel_id' => ChannelAccountConst::channel_amazon, 'dateline' => $yesterday])->count();
            if (empty($oldCount)) {
                //1.刊登记录表上架；
                $wh['update_time'] = ['BETWEEN', [$yesterday, $todayTime]];
                $wh['publish_status'] = AmazonPublishConfig::PUBLISH_STATUS_FINISH;

                $field = 'goods_id,account_id,creator_id,count(goods_id) as spu_count';
                $group = 'goods_id,account_id,creator_id';
                //统计上架
                $products = AmazonPublishProduct::field($field)->where($wh)->group($group)->select();

                //调用接口
                if (!empty($products)) {
                    foreach ($products as $product) {
                        \app\report\service\StatisticShelf::addReportShelf(
                            ChannelAccountConst::channel_amazon,
                            $product['account_id'],
                            $product['creator_id'],
                            $product['goods_id'],
                            $product['spu_count'],
                            0,
                            $yesterday
                        );
                    }
                }

                //2.listing跟卖表上架
                //$wh2['create_time'] = ['BETWEEN', [$yesterday, $todayTime]];
                //$wh2['seller_type'] = 2;
                //$wh2['goods_id'] = ['>', 0];
                //
                //$field = 'goods_id,account_id,create_user_id,count(goods_id) as spu_count';
                //$group = 'goods_id,account_id,create_user_id';
                ////统计上架
                //$products = AmazonListing::field($field)->where($wh2)->group($group)->select();
                //
                ////调用接口
                //if (!empty($products)) {
                //    foreach ($products as $product) {
                //        \app\report\service\StatisticShelf::addReportShelf(
                //            ChannelAccountConst::channel_amazon,
                //            $product['account_id'],
                //            $product['create_user_id'],
                //            $product['goods_id'],
                //            $product['spu_count'],
                //            0,
                //            $yesterday
                //        );
                //    }
                //}

                //$where = [];
                //$where['log.run_time'] = ['BETWEEN', [$yesterday, $todayTime]];
                ////操作成功的数据；
                //$where['log.status'] = 1;
                //$where['log.new_value'] = ['!=', '{"quantity":0}'];
                ////类型3 是库存；
                //$where['log.type'] = 3;
                //$where['l.goods_id'] = ['>', 0];
                //$upProducts = AmazonActionLog::alias('log')
                //    ->join(['amazon_listing' => 'l'], 'l.id=log.amazon_listing_id')
                //    ->field('l.goods_id,log.account_id,log.create_id')
                //    ->where($where)
                //    ->select();
                //
                ////调用接口
                //if (!empty($upProducts)) {
                //    foreach ($upProducts as $product) {
                //        \app\report\service\StatisticShelf::addReportShelf(
                //            ChannelAccountConst::channel_amazon,
                //            $product['account_id'],
                //            $product['create_id'],
                //            $product['goods_id'],
                //            1,
                //            0,
                //            $yesterday
                //        );
                //    }
                //}
            }

            //统计下架的，因为没做下架的接品，所以
            $oldDownCount = ReportStatisticPublishByPicking::where(['channel_id' => ChannelAccountConst::channel_amazon, 'dateline' => $yesterday])->count();
            if (empty($oldDownCount)) {
                $where = [];
                $where['log.run_time'] = ['BETWEEN', [$yesterday, $todayTime]];
                //操作成功的数据；
                $where['log.status'] = 1;
                $where['log.new_value'] = '{"quantity":0}';
                //类型3 是库存；
                $where['log.type'] = 3;
                $where['l.goods_id'] = ['>', 0];
                $downProducts = AmazonActionLog::alias('log')
                    ->join(['amazon_listing' => 'l'], 'l.id=log.amazon_listing_id')
                    ->field('l.goods_id,log.account_id,log.create_id')
                    ->where($where)
                    ->select();

                //调用接口
                if (!empty($downProducts)) {
                    foreach ($downProducts as $product) {
                        \app\report\service\StatisticPicking::addReportPicking(
                            ChannelAccountConst::channel_amazon,
                            $product['account_id'],
                            $product['create_id'],
                            $product['goods_id'],
                            0,
                            0,
                            $yesterday
                        );
                    }
                }
            }

        } catch (Exception $e) {
            throw new Exception($e->getMessage(). '|'. $e->getLine(). '|'. $e->getFile());
        }

    }

}