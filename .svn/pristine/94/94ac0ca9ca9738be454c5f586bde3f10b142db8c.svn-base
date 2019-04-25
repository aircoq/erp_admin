<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\ChannelUserAccountMap;
use app\common\model\ebay\EbayMessageGroup;
use app\common\service\ChannelAccountConst;
use app\index\service\MemberShipService;

/**
 * Created by tanbin.
 * User: PHILL
 * Date: 2016/11/5
 * Time: 11:44
 */
class EbayMessage extends Cache
{
    /** 获取属性信息
     * @param string $message_id  
     * @param array $data
     * @return array|mixed
     */
    public function ebayMessageId($message_id, $data = [])
    {      
        //Cache::handler()->del('hash:EbayMessageId'); 
        $key = 'hash:EbayMessageId';
        if ($data) {
            $this->redis->hset($key, $message_id, json_encode($data));
            return true;
        }
        $result = json_decode($this->redis->hget($key, $message_id), true);
        return $result ? $result : [];
    }
    
    
    /**
     * 清除过期的message_id
     * @param int $time 删除距离现在一定时间站内信id
     * @return boolean
     */
    public function delExpireMessage($time = 5*24*3600)
    {
        $key = 'hash:EbayMessageId';
        $last_time = time() - $time;
        $messages = $this->redis->hGetAll($key);
        foreach($messages as $meassage_id => $data) {
            $info = json_decode($data, true);
            if(!param($info, 'down_time')){
                $this->redis->hDel($key, $meassage_id);
                continue;
            }
            $info['down_time'] <= $last_time ? $this->redis->hDel($key, $meassage_id) : '';
        }
    
        return true;
    }
    
    
    /**
     * 添加站内信回复日志
     * @param unknown $key
     * @param array $data
     */
    public function addReplyMessageLogs($key, $data = [])
    {
        $this->redis->hSet('hash:EbayReplyMessageLogs', $key, json_encode($data));
    }
    
    /**
     * 获取站内信回复日志
     * @param unknown $key
     * @param array $data
     */
    public function getReplyMessageLogs($key)
    {
        if ($this->redis->hExists('hash:EbayReplyMessageLogs', $key)) {
            return true;
        }
        return false;
    }
    
    
    /**
     * 添加站内信发送信息日志
     * @param unknown $key
     * @param array $data
     */
    public function addSendMessageLogs($key, $data = [])
    {
        $this->redis->hSet('hash:EbaySendMessageLogs', $key, json_encode($data));
    }
    
    /**
     * 获取站内信发送信息日志
     * @param unknown $key
     * @param array $data
     */
    public function getSendMessageLogs($key)
    {
        if ($this->redis->hExists('hash:EbaySendMessageLogs', $key)) {
            return true;
        }
        return false;
    }
    
    
    /**
     * 添加站内信发送图片日志
     * @param unknown $key
     * @param array $data
     */
    public function addUploadPicLogs($key, $data = [])
    {
        $this->redis->hSet('hash:EbayUploadPicLogs', $key, json_encode($data));
    }
    
    /**
     * 获取站内信发送图片日志
     * @param unknown $key
     * @param array $data
     */
    public function getUploadPicLogs($key)
    {
        if ($this->redis->hExists('hash:EbayUploadPicLogs', $key)) {
            return true;
        }
        return false;
    }

    public function getGroupCustomerKey()
    {
        $key = 'hash:ebay:customer-group-count:'. date('Y-m-d');
        return $key;
    }
    
    public function groupCustomerCount($customer_id = 0)
    {
        $key = $this->getGroupCustomerKey();
        $this->redis->del($key);
        $data = $this->redis->hGetAll($key);
        if (empty($data)) {
            $data = $this->setGroupCustomerCount($key);
            $this->redis->expireAt($key, strtotime(date('Y-m-d')) + 86400);
        }

        if (!empty($customer_id)) {
            return $data[$customer_id];
        }
        return $data;
    }

    public function getCustomerAccoun()
    {
        $key = 'hash:ebay:customer-account:'. date('Y-m-d');
        $this->redis->del($key);
        $data = $this->redis->get($key);
        if (!empty($data)) {
            $data = json_decode($data, true);
            return $data;
        } else {
            $data = ChannelUserAccountMap::where(['channel_id' => ChannelAccountConst::channel_ebay])
                ->field('customer_id,account_id')->select();
            $customer = [];
            foreach ($data as $val) {
                if ($val['customer_id'] == 0 || $val['account_id'] == 0) {
                    continue;
                }
                $customer[$val['customer_id']][] = $val['account_id'];
            }
            $this->redis->set($key, json_encode($customer));
            $this->redis->expireAt($key, strtotime(date('Y-m-d')) + 86400);
            return $customer;
        }
    }

    public function setGroupCustomerCount($key)
    {
        $customerAccount = $this->getCustomerAccoun();
        $group = EbayMessageGroup::where(['customer_id' => ['<>', 0]])
            ->field('count(id) total,customer_id')
            ->group('customer_id')->select();
        //拿到所有的客服ID；
        $customerIds = array_keys($customerAccount);
        //每个客服对应的数据；
        $groupCustomerCount = [];
        foreach ($customerIds as $id) {
            $groupCustomerCount[$id] = 0;
            foreach ($group as $val) {
                if ($id = $val['customer_id']) {
                    $groupCustomerCount[$id] = $val['total'];
                    break;
                }
            }
            $this->redis->hSet($key, $id,$groupCustomerCount[$id]);
        }

        return $groupCustomerCount;
    }
}
