<?php
namespace app\customerservice\task;

use app\common\service\ChannelAccountConst;
use app\index\service\AbsTasker;
use app\common\model\EmailAccounts as EmailAccountsModel;
use app\common\service\UniqueQueuer;
use app\customerservice\queue\EbayEmailReceiveQueue;
use think\Db;

class EbayEMail extends AbsTasker
{
    /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return 'ebay客服邮件同步';
    }

    /**
     * 定义任务描述
     * @return string
     */
    public function getDesc()
    {
        return '同步ebay客服邮件';
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
     * @throws Exception
     */
    public  function execute()
    {
        $queue = new UniqueQueuer(EbayEmailReceiveQueue::class);

        $where['a.channel_id'] = ChannelAccountConst::channel_ebay;
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
    }

}

