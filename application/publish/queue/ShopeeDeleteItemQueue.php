<?php
/**
 * Created by PhpStorm.
 * User: wlw2533
 * Date: 2018/8/31
 * Time: 15:37
 */

namespace app\publish\queue;


use app\common\service\SwooleQueueJob;
use app\publish\helper\shopee\ShopeeHelper;
use think\Exception;

class ShopeeDeleteItemQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return 'shopee删除Item';
    }

    public function getDesc(): string
    {
        return 'shopee删除Item';
    }

    public function getAuthor(): string
    {
        return 'wlw2533';
    }

    public function execute() {
        try {
            $params = $this->params;
            $accountId = $params['account_id'];
            $itemId = $params['item_id'];
            $tortId = $params['tort_id'] ?? 0;
            $res = (new ShopeeHelper())->delItem($accountId, $itemId,$tortId);
            if ($res !== true) {
                throw new Exception($res);
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}