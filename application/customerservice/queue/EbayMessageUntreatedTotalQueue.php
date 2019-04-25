<?php
// +----------------------------------------------------------------------
// | 
// +----------------------------------------------------------------------
// | File  : EbayMessageQueue.php
// +----------------------------------------------------------------------
// | Author: tanbin
// +----------------------------------------------------------------------
// | Date  : 2017-09-30
// +----------------------------------------------------------------------

namespace  app\customerservice\queue;

use app\common\cache\Cache;
use app\common\model\ebay\EbayMessage;
use app\common\model\ebay\EbayMessageGroup;
use app\common\service\SwooleQueueJob;
use think\Exception;


class EbayMessageUntreatedTotalQueue extends SwooleQueueJob
{
  
    public function getName(): string
    {
        return "ebay站内信未处理数据修正";
    }

    public function getDesc(): string
    {
        return "ebay站内信未处理数据修正";
    }

    public function getAuthor(): string
    {
        return "冬";
    }

    public static function swooleTaskMaxNumber():int
    {
        return 5;
    }

    public function execute()
    {
        try {
            if (empty($this->params)) {
                return;
            }
            $where['status'] = ['in', [0, 1]];

            $limit = 1;
            if (is_int($this->params)) {
                $where['id'] = $this->params;
            } else if (isset($this->params['id']) && isset($this->params['limit'])) {
                $where['id'] = ['>=', $this->params['id']];
                $limit = $this->params['limit'];
            } else {
                return;
            }

            $groupModel = new EbayMessageGroup();
            $msgModel = new EbayMessage();

            $groups = $groupModel->where($where)->limit($limit)->field('id,msg_count,untreated_count,last_message_id,status')->select();
            if (empty($groups)) {
                return;
            }

            //处理每一分组的数据；
            foreach ($groups as $group) {
                $messages = $msgModel->where(['group_id' => $group['id']])
                    ->field('id,message_id,replied,status,send_time,message_type')
                    ->order('send_time', 'asc')->select();
                if (empty($messages)) {
                    continue;
                }
                Cache::handler()->set('task:ebay:message:group_id', $group['id']);

                $untreated_count = 0;
                $msg_count = 0;
                $status = 0;
                $last_message = ['message_id' => '', 'send_time' => 0];
                foreach ($messages as $message) {
                    //已回复，则标为已处理，未回复，则标为0未处理；
                    if (($message['status'] == 2 || $message['status'] == 1) && $message['replied'] == 0) {
                        $message['replied'] = 1;
                        $msgModel->update(['replied' => 1], ['id' => $message['id']]);
                    }
                    if ($message['message_type'] == 1) {
                        $last_message = $message;
                        $msg_count++;
                        if ($message['replied'] == 1) {
                            $status = 1;
                        } else {
                            $untreated_count++;
                            $status = 0;
                        }
                    } else if ($message['message_type'] == 3 && $message['send_status'] == 1) {
                        $status = 1;
                    }
                }

                //以下数据，有一个不同，则更新；
                //if (
                //    $msg_count != $group['msg_count'] ||
                //    $untreated_count != $group['untreated_count'] ||
                //    $status != $group['status']
                //) {
                    $update = [];
                    $update['msg_count'] = $msg_count;
                    $update['untreated_count'] = $untreated_count;
                    $update['status'] = $status;

                    if (!empty($last_message) && $group['last_message_id'] != $last_message['message_id']) {
                        $update['last_message_id'] = $last_message['message_id'];
                        $update['last_receive_time'] = $last_message['send_time'];
                    }

                    $groupModel->update($update,['id'=>$group['id']]);
                //}
            }
        }catch (Exception $ex){
            throw new Exception($ex->getMessage());
        }
    }
}