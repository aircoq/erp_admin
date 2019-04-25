<?php
namespace app\index\queue;

use app\common\model\AccountUserMap;
use app\common\model\ChannelUserAccountMap;
use app\common\service\SwooleQueueJob;
use app\common\service\UniqueQueuer;
use app\index\service\ManagerServer;
use think\Exception;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2018/5/23
 * Time: 12:03
 */
class ServerUserMapWriteBack extends SwooleQueueJob
{
    public function getName(): string
    {
        return "服务器成员信息回写";

    }

    public function getDesc(): string
    {
        return "服务器成员信息回写";
    }

    public function getAuthor(): string
    {
        return "phill";
    }

    public static function swooleTaskMaxNumber():int
    {
        return 1;
    }

    public function execute()
    {
        try {
//            $accountList = (new ChannelUserAccountMap())->field('channel_id,account_id,seller_id,customer_id')->select();
//            foreach($accountList as $a => $temp){
//                (new UniqueQueuer(AccountUserMapQueue::class))->push(
//                    [
//                        'channel_id' => $temp['channel_id'],
//                        'account_id' => $temp['account_id'],
//                        'customer_id' => $temp['customer_id'],
//                        'seller_id' => $temp['seller_id']
//                    ]
//                );
//            }
            $mapList = (new AccountUserMap())->alias('m')->field('server_id,channel_id,account_id,user_id')->join('account a','m.account_id = a.id')->select();
            foreach ($mapList as $k => $value){
                (new UniqueQueuer(AuthorizationQueue::class))->push(['server_id' => $value['server_id'],'user_id' => $value['user_id']]);
            }
        } catch (Exception $e) {
            var_dump($e->getMessage());
        }
    }
}