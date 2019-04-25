<?php
/**
 * Created by PhpStorm.
 * User: wlw2533
 * Date: 18-5-28
 * Time: 下午6:00
 */

namespace app\publish\queue;


use app\common\model\shopee\ShopeeActionLog;
use app\common\service\SwooleQueueJob;
use app\publish\helper\shopee\ShopeeHelper;
use think\Exception;

class ShopeeListingUpdateQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return 'shopee更新listing队列';
    }

    public function getDesc(): string
    {
        return 'shopee更新listing队列';
    }

    public function getAuthor(): string
    {
        return 'wlw2533';
    }

    public function execute()
    {
        try {
            $logId = $this->params;
            if (empty($logId)) {
                throw new Exception('传递的日志id有误');
            }
            $log = ShopeeActionLog::get($logId);
            if (empty($log)) {
                throw new Exception('获取日志信息失败');
            }
            $log = $log->toArray();
            $helper = new ShopeeHelper();
            switch($log['type']) {
                case ShopeeHelper::UPDATE_TYPE['updateItem']:
                    $data = json_decode($log['new_data'], true);
                    if (empty($data)) {
                        throw new Exception('日志中存储的更新数据有误');
                    }
                    $res = $helper->updateItem($data);
                    if ($res !== true) {
                        throw new Exception($res);
                    }
                    break;
                case 11://同步listing
                    $helper->getItemDetail($log['product_id']);
                    break;
            }

        } catch (Exception $e) {

        }

    }


}