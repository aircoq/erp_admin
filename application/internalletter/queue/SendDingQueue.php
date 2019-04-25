<?php

namespace  app\internalletter\queue;

use app\common\model\InternalLetterText;
use app\common\service\SwooleQueueJob;
use app\internalletter\service\DingTalkService;
use Exception;
use app\common\model\InternalLetter;

class SendDingQueue extends SwooleQueueJob
{
  
    public function getName(): string
    {
        return "发送钉钉队列";
    }

    public function getDesc(): string
    {
        return "发送钉钉队列";
    }

    public function getAuthor(): string
    {
        return "denghaibo";
    }

    public static function swooleTaskMaxNumber():int
    {
        return 10;
    }

    public function execute()
    {
        $internalLetterModel = new InternalLetter();
        $internalLetterTextModel = new InternalLetterText();
        try {
            if(empty($this->params)) {
                return;
            }
            if(empty($this->params['count'])){
                $this->params['count'] = 0;
            }

            $access_token = DingTalkService::getAccessToken();

            $letter = $internalLetterModel->where('id', $this->params['id'])->field('letter_text_id, title, dingtalk_userid')->find();
            $content = $internalLetterTextModel->where('id', $letter['letter_text_id'])->value('content');

            $resp = DingTalkService::send_dingtalk_message($access_token, $letter['dingtalk_userid'],
                $content, $letter['title'], $this->params['send_to_all']);

            //发送成功
            if($resp['ask'] == 1){
                $data['task_id']=$resp['task_id'];
                $data['status']=1;
            }else{
                //发送失败
                $data['error_msg'] = $resp['message'];
                $data['status']=2;
            }

            $internalLetterModel->where('id', $this->params['id'])->update($data);
        }catch (\Exception $e){
            throw new Exception($e->getMessage() . $e->getFile() . $e->getLine());
        }
    }
}