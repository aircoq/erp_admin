<?php
/**
 * Created by PhpStorm.
 * User: wlw2533
 * Date: 2018/8/31
 * Time: 10:29
 */

namespace app\publish\queue;


use app\common\model\shopee\ShopeeAccount;
use app\common\model\shopee\ShopeeProduct;
use app\common\service\SwooleQueueJob;
use app\publish\helper\shopee\ShopeeHelper;
use think\Exception;

class ShopeeGetItemDetailQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return 'shopee获取Item详情';
    }

    public function getDesc(): string
    {
        return 'shopee获取Item详情';
    }

    public function getAuthor(): string
    {
        return 'wlw2533';
    }

    public function execute()
    {
        try {
            $params = $this->params;
            $res = (new ShopeeHelper())->getItemDetail($params['item_id'], $params['account_id']);
            if ($res !== true) {
                throw new Exception($res);
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}