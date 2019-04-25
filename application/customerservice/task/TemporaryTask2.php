<?php
namespace app\customerservice\task;

use app\common\service\ChannelAccountConst;
use app\index\service\AbsTasker;
use Exception;
use app\common\exception\TaskException;
use app\common\service\UniqueQueuer;
use app\customerservice\queue\AmazonEmailReceiveQueue;
use think\Db;
use app\customerservice\service\EbayEmail;

class TemporaryTask2 extends AbsTasker
{
    /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return '临时任务-更新ebay_email receiver_id';
    }

    /**
     * 定义任务描述
     * @return string
     */
    public function getDesc()
    {
        return '临时任务-更新ebay_email receiver_id';
    }

    /**
     * 定义任务作者
     * @return string
     */
    public function getCreator()
    {
        return 'denghaibo';
    }

    /**
     * 定义任务参数规则
     * @return array
     */
    public function getParamRule()
    {
        return [];
    }

    /**
     * @throws TaskException
     */
    public  function execute()
    {
        try{
            $ebayEmail = new EbayEmail();
            $ebayEmail->set_ebay_email_receiver_id();
        } catch (Exception $e){
            throw new Exception($e->getMessage() . $e->getFile() . $e->getLine());
        }
    }

}

