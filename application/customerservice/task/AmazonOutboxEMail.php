<?php
namespace app\customerservice\task;

use app\common\service\ChannelAccountConst;
use app\index\service\AbsTasker;
use \Exception as Exception;
use app\common\exception\TaskException;
use app\common\service\UniqueQueuer;
use app\customerservice\queue\AmazonOutboxEmailReceiveQueue;
use think\Db;

class AmazonOutboxEMail extends AbsTasker
{
    /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return '亚马逊发件箱邮件同步';
    }

    /**
     * 定义任务描述
     * @return string
     */
    public function getDesc()
    {
        return '同步亚马逊发件箱邮件';
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
     * 同步邮件任务
     * @throws TaskException
     */
    public  function execute()
    {
        try{
            $queue = new UniqueQueuer(AmazonOutboxEmailReceiveQueue::class);

            $where['channel_id'] = ChannelAccountConst::channel_amazon;

            $accounts = Db::table('account')->where($where)->field('id')->group('email')->select();

            if(empty($accounts)){
                return false;
            }

            foreach ($accounts as $email) {
                $queue->push($email);
            }
        } catch (Exception $e){
            throw new Exception($e->getMessage() . $e->getFile() . $e->getLine());
        }
    }

}

