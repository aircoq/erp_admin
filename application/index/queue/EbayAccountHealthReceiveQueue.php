<?php
/**
 * Created by PhpStorm.
 * User: wlw2533
 * Date: 2018/7/21
 * Time: 12:10
 */

namespace app\index\queue;


use app\common\service\SwooleQueueJob;
use app\index\service\EbayAccountHealthService;
use think\Exception;

class EbayAccountHealthReceiveQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "Ebay健康监控-接收数据";
    }

    public function getDesc(): string
    {
        return "Ebay健康监控数据接收";
    }

    public function getAuthor(): string
    {
        return "wlw2533";
    }

    public function execute()
    {
        try {
            $data = $this->params;//本身就是数组
            if (empty($data)) {
                return;
            }
            if (!isset($data['status']) || $data['status'] == 'Fail') {
                throw new Exception(isset($data['message']) ? $data['message'] : '未知错误');
            }
            //处理数据
            $serv = new EbayAccountHealthService();
            $serv->saveHealthData($data);
        } catch (Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }

}