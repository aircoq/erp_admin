<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/9
 * Time: 11:41
 */

namespace app\publish\queue;

use app\common\model\amazon\AmazonListing;
use app\common\model\amazon\AmazonListingProfit as profitModel;
use app\common\model\amazon\AmazonOrder;
use app\common\model\Order;
use app\common\model\OrderDetail;
use app\common\model\OrderSourceDetail;
use app\common\service\SwooleQueueJob;
use app\publish\service\AmazonListingService;
use think\Exception;

class AmazonListingProfitQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return 'AmazonListing记录利润率';
    }

    public function getDesc(): string
    {
        return 'AmazonListing记录利润率';
    }

    public function getAuthor(): string
    {
        return '冬';
    }

    public static function swooleTaskMaxNumber():int
    {
        return 2;
    }

    public function execute()
    {
        try{
            if (!is_numeric($this->params)) {
                return false;
            }
            $param = $this->params;

            $serv = new AmazonListingService();
            $omodel = new Order();
            $osdModel = new OrderSourceDetail();
            $details = $osdModel->where(['order_id' => $param])->field('channel_sku seller_sku')->select();
            if (empty($details)) {
                return false;
            }
            $order = $omodel->where(['id' => $param])
                ->field('order_number,channel_account_id account_id')
                ->find();
            if (empty($order)) {
                return false;
            }

            $time = time();
            $base['last_order_id'] = $param;
            $base['last_order_number'] = $order['order_number'];
            $base['update_time'] = $time;

            $listingModel = new AmazonListing();
            $amazonOrderModel = new AmazonOrder();
            $profitModel = new profitModel();

            //查找listing里面是否有这个seller_sku;
            foreach ($details as $val) {
                $where = [];
                $where['account_id'] = $order['account_id'];
                $where['seller_sku'] = $val['seller_sku'];
                $listing = $listingModel->where($where)->field('id,goods_id')->find();
                //找不到listing直接跳过；
                if (empty($listing)) {
                    continue;
                }

                $tmp = $base;
                //先找已售量；
                $soldData = $amazonOrderModel->alias('ao')
                    ->join(['amazon_order_detail' => 'ad'], 'ao.id=ad.amazon_order_id')
                    ->where(['account_id' => $where['account_id'], 'online_sku' => $where['seller_sku']])
                    ->field('SUM(ad.qty) total')
                    ->find();
                $tmp['sold_quantily'] = (int)$soldData['total'];
                $tmp['profit'] = $serv->getSingleOrderProfit($listing['goods_id'], $order['account_id']);

                if ($profitModel->where(['id' => $listing['id']])->count()) {
                    $profitModel->update($tmp, ['id' => $listing['id']]);
                } else {
                    $tmp['id'] = $listing['id'];
                    $tmp['create_time'] = $time;
                    $profitModel->insert($tmp);
                }
            }

            return true;
        }catch (\Exception $e){
            throw new Exception($e->getMessage());
        }
    }
}