<?php
namespace app\customerservice\task;

use app\common\service\ChannelAccountConst;
use app\index\service\AbsTasker;
use \Exception as Exception;
use app\common\exception\TaskException;
use app\common\service\UniqueQueuer;
use app\customerservice\queue\AmazonEmailReceiveQueue;
use think\Db;

class AmazonEMail extends AbsTasker
{
    /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return '亚马逊客服邮件同步';
    }

    /**
     * 定义任务描述
     * @return string
     */
    public function getDesc()
    {
        return '同步亚马逊客服未读邮件';
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

            $queue = new UniqueQueuer(AmazonEmailReceiveQueue::class);

            $where['a.channel_id'] = ChannelAccountConst::channel_amazon;
            $where['e.status'] = 1;
            $where['e.is_receive'] = 1;

            $accounts = Db::table('account')->where($where)->field('a.id')->group('a.email')->alias('a')
                ->join('email e','e.id=a.email_id','LEFT')->select();

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

