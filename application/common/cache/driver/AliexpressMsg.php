<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\aliexpress\AliexpressMsgDetail as AliexpressMsgDetailModel;
use app\common\model\aliexpress\AliexpressMsgRelation as AliexpressMsgRelationModel;

class AliexpressMsg extends Cache
{

    private $taskPrefix_msg = 'task:aliexpress:last_msg:';
    /**
     * @title 把最后一条消息ID存到缓存中
     * @param $channel_id 通道ID
     * @param $last_msg_id 最后一条消息ID
     */
    public  function setLastMessageId($account_id, $channel_id, $last_msg_id)
    {
        $key = $this->taskPrefix_msg.$account_id;
        $this->redis->hset($key, $channel_id, $last_msg_id);
    }

    /**
     * @title 获取最后一条消息ID
     * @param $channel_id 消息通道ID
     * @return bool|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public  function getLastMessageId($account_id, $channel_id)
    {
        /*$key = $this->taskPrefix_msg.$account_id;
        if ($this->redis->hexists($key, $channel_id)){//先去缓存拿
            $last_msg_id=$this->redis->hget($key, $channel_id);
        }
        else //如果缓存没有去数据库拿
        {
            $result = AliexpressMsgRelationModel::where(['channel_id' => $channel_id])->find();
            $last_msg_id = $result ? $result['last_msg_id'] : 0;
            if($result){//如果数据库有，缓存没有，把数据库数据存缓存
                $this->redis->hset($key, $channel_id, $last_msg_id);
            }
        }*/
        $result = AliexpressMsgDetailModel::where(['channel_id' => $channel_id])->field('msg_id')->order('msg_id desc')->find();
        $last_msg_id = isset($result['msg_id']) ? $result['msg_id'] : 0;
        return $last_msg_id;
    }

}
