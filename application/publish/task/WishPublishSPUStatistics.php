<?php
/**
 * Created by PhpStorm.
 * User: panguofu
 * Date: 2018/10/22
 * Time: 下午3:13
 */

namespace app\publish\task;

use app\common\model\wish\WishWaitUploadProduct;
use app\common\model\wish\WishWaitUploadProductVariant;
use app\common\model\report\ReportStatisticPublishByShelf;
use app\common\service\ChannelAccountConst;
use app\index\service\AbsTasker;
use think\Exception;


class WishPublishSPUStatistics extends AbsTasker
{
    public function getName()
    {
        return 'wish刊登SPU上下架统计';
    }

    public function getDesc()
    {
        return 'wish刊登SPU上下架统计';
    }

    public function getCreator()
    {
        return '潘多拉';
    }

    public function getParamRule()
    {
        return [];
    }

    public function execute()
    {
        try {
            $todayTime = strtotime(date('Y-m-d', time())) - 1;
            $yesterday = $todayTime + 1 - 86400;

//            $oldCount = ReportStatisticPublishByShelf::where(['channel_id' => ChannelAccountConst::channel_wish, 'dateline' => $yesterday])->count();
//            if ($oldCount > 0) {
//                return;
//            }


            $wh['goods_id'] = ['neq', 0];
            $wh['uid'] = ['neq', 0];
            $wh['date_uploaded'] = ['BETWEEN', [$yesterday, $todayTime]];

            $wh['publish_status'] = 1;
            $field = 'goods_id,accountid,uid,count(goods_id) as spu_count,date_uploaded';
            $group = 'goods_id,accountid';
            //统计上架
            $products = WishWaitUploadProduct::field($field)->where($wh)->group($group)->select();
//            echo WishWaitUploadProduct::getLastSql();
//            exit;


            //调用接口
            foreach ($products as &$product) {
               if (is_object($product)) $product=$product->toArray();
                \app\report\service\StatisticShelf::addReportShelf(ChannelAccountConst::channel_wish,
                    $product['accountid'],
                    $product['uid'],
                    $product['goods_id'],
                    $product['spu_count'],
                    0,
                    $product['date_uploaded']
                );
            }



            //统计下架的
            unset($wh['date_uploaded']);
            //$wh['publish_status'] = 2;
            $wh['manual_end_time'] = ['BETWEEN', [$yesterday, $todayTime]];
            $products = WishWaitUploadProduct::field($field)->where($wh)->group($group)->select();
//            echo WishWaitUploadProduct::getLastSql();
//            exit;
            foreach ($products as &$product) {
                if (is_object($product)) $product=$product->toArray();
                \app\report\service\StatisticPicking::addReportPicking(ChannelAccountConst::channel_wish,
                    $product['accountid'],
                    $product['uid'],
                    $product['goods_id'],
                    $product['spu_count'],
                    0,
                    $yesterday);
            }

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

    }
}