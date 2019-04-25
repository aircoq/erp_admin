<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\ebay\EbayEmail as EbayEmailList;

/**
 * Created by tanbin.
 * User: PHILL
 * Date: 2016/11/5
 * Time: 11:44
 */
class EbayEmail extends Cache
{
    /** 获取属性信息
     * @param string $order_number ebay订单id
     * @param array $data
     * @return array|mixed
     */
    public function getMaxUid($email_account_id, $account_id = 0)
    {
        //Cache::handler()->del('hash:EbayOrderUpdateTime'); //删除

        //暂时关闭缓存
//        $key = 'hash:EbayEmailMaxUid';
//        $hashKey = $account_id. '-'. $email;
//        $result = $this->redis->hget($key, $hashKey);
//        if($result){
//            return $result;
//        }

        $data = EbayEmailList::where(['email_account_id' => $email_account_id])->field('id,email_uid')
            ->order('email_uid', 'desc')
            ->find();
        return empty($data)? 0 : $data->email_uid;
    }

    public function setMaxUid($email, $uid, $account_id = 0)
    {
        $key = 'hash:EbayEmailMaxUid';
        $hashKey = $account_id. '-'. $email;
        if($this->redis->hset($key, $hashKey, $uid)) {
            return true;
        }
        return false;
    }


}
