<?php
namespace app\customerservice\task;

use app\common\model\ebay\EbayMessage;
use app\common\service\UniqueQueuer;
use app\customerservice\queue\EbaySendMessageQueue;
use app\index\service\AbsTasker;

class EbaySendMessage extends AbsTasker{
    public function getName()
    {
        return "Ebay站内信发送中核查";
    }

    public function getDesc()
    {
        return "Ebay站内信发送中核查，找出卡在发送中，又没在发送队列的站内信重新发送";
    }

    public function getCreator()
    {
        return "冬";
    }

    public function getParamRule()
    {
        return [
            //'downTime|下载时间' => 'require|select:正常下载:0,1天前到现在:1,3天前到现在:3,5天前到现在:5,10天前到现在:10,20天前到现在:20,30天前到现在:30,40天前到现在:40,50天前到现在:50,60天前到现在:60'
        ];
    }

    public function execute()
    {
           $this->push_queue();
    }

    /**
     * 推送队列
     */
    function push_queue()
    {
        $msgQueue = new UniqueQueuer(EbaySendMessageQueue::class);
        $messageModel = new EbayMessage();

        $time = time();
        $start = 1;
        $limit = 100;
        $params = [];
        $where['message_type'] = 3;
        $where['send_status'] = 2;
        //创建时间在一天前到现在10分钟前没有发送出去的找出来，放进队列；
        $where['created_time'] = ['BETWEEN', [$time - 86400, $time - 600]];
         do {
            $data = $messageModel->where($where)->page($start++, $limit)->column('id');
            if (empty($data)) {
                break;
            }
            $params = array_merge($params, $data);
        } while($limit == count($data));

        foreach ($params as $param) {
            $msgQueue->push($param);
        }
    }

}