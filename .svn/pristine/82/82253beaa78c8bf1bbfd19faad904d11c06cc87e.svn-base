<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\aliexpress\AliexpressMsgDetail as AliexpressMsgDetailModel;
use app\common\model\aliexpress\AliexpressMsgRelation as AliexpressMsgRelationModel;

class AliexpressMsgDetail extends Cache
{
    /**
     * 检测消息明细是否存在
     * @param string $msgId     平台消息ID
     * @return boolean Description
     */
    public function isHas($msgId){
        if ($this->redis->hexists('cache:AliexpressMsgDetail', $msgId)) {
            return true;
        }
        $result = AliexpressMsgDetailModel::where(['msg_id' => $msgId])->find();
        if ($result) {
            $this->redis->hset('cache:AliexpressMsgDetail', $msgId, $msgId);
            return true;
        }
        return false;
    }
    
    /**
     * 检测会话是否需要更新
     * @param type $channelId
     * @param type $data  ['readStat','dealStat','msgTime']
     * @return boolean
     */
    public function isNewest($channelId,$data)
    {
        if ($this->redis->hexists('cache:AliexpressMsgRelation', $channelId)) {
            $value = $this->redis->hget('cache:AliexpressMsgRelation', $channelId);
            if($value==  implode(':', $data)){
                return true;
            }
        }
        $this->redis->hset('cache:AliexpressMsgRelation', $channelId, implode(':', $data));
        return false;
    }
    /**
     * @desc 获取指定账号站内信抓取过的最近的时间节点
     * @param string $clientId 指定账号的 client_id
     * @author Jimmy
     * @datetime 2017-09-26 20:08:11
     */
    public function getLastMessageTime($clientId)
    {
        $messageTimeLast=0;
        if ($this->redis->hexists('cache:AliexpressMsgTime', $clientId)) {
            $messageTimeLast = $this->redis->hget('cache:AliexpressMsgTime', $clientId);
        }
        return $messageTimeLast;
    }
    /**
     * @param string $clientId 指定账号的 client_id
     * @param int $messageTime 最近更新的时间节点
     * @author Jimmy
     * @datetime 2017-09-26 20:22:11
     */
    public function setLastMessageTime($clientId, $messageTime)
    {
        $this->redis->hset('cache:AliexpressMsgTime', $clientId, $messageTime);
    }

}
