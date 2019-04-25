<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\customerservice\AmazonEmail as AmazonEmailList;

/**
 * Created by tanbin.
 * User: PHILL
 * Date: 2016/11/5
 * Time: 11:44
 */
class AmazonEmail extends Cache
{
    /** 获取属性信息
     * @param string $order_number ebay订单id
     * @param array $data
     * @return array|mixed
     */
    public function getMaxUid($email_account_id, $mail_box,$account_id = 0)
    {
        //Cache::handler()->del('hash:EbayOrderUpdateTime'); //删除

        //暂时关闭缓存
//        $key = 'hash:AmazonEmailMaxUid';
//        $hashKey = $account_id. '-'. $email;
//        $result = $this->redis->hget($key, $hashKey);
//        if($result){
//            return $result;
//        }

        if (strtolower($mail_box) == 'sent')
        {
            $where['email_account_id'] = $email_account_id;
            $where['type'] = 2;
        } else {
            $where['email_account_id'] = $email_account_id;
            $where['type'] = 1;
        }

        $data = AmazonEmailList::where($where)->field('id,email_uid')
            ->order('email_uid', 'desc')
            ->find();
        return empty($data)? 0 : $data->email_uid;
    }

    public function setMaxUid($email, $uid, $account_id = 0)
    {
        $key = 'hash:AmazonEmailMaxUid';
        $hashKey = $account_id. '-'. $email;
        if($this->redis->hset($key, $hashKey, $uid)) {
            return true;
        }
        return false;
    }

    /**
     *  获取分组数据；
     */
    public function getGroupData($groupWhere) {
        if (empty($groupWhere)) {
            return false;
        }
        $key = 'hash:AmazonEmailGroup';
        $hashKey = implode('-', $groupWhere);
        $result = $this->redis->hget($key, $hashKey);
        if(!empty($result)) {
            return json_decode($result, true);
        }
        return [];
    }

    /**
     *  设置分组数据；
     */
    public function setGroupData($groupWhere, $group) {
        if (empty($groupWhere)) {
            return false;
        }
        $key = 'hash:AmazonEmailGroup';
        $hashKey = implode('-', $groupWhere);
        if($this->redis->hset($key, $hashKey, json_encode($group))) {
            return true;
        }
        return false;
    }

}
