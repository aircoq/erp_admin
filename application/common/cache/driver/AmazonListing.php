<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\amazon\AmazonListing as AmazonListingModel;

class AmazonListing extends Cache
{
    private $hashPrefix = 'task:amazon:listing:';
    private $set = 'task:amazon:listing:set';
    
    /**
     * @param string $accountId
     * @param $listing_id amazonListingId
     * @param array $data
     * @return array|mixed
     */
    public function listingUpdateTime($accountId, $listing_id, $data = [])
    {
        $key = $this->getListingKey($accountId);
        if ($data) {
            $this->redis->zAdd($this->set, $accountId);
            $this->redis->hSet($key, $listing_id, json_encode($data));
            return true;
        }
        $result = $this->redis->hGet($key, $listing_id);
        if (!$result) {
            $field = 'id, modify_time as last_update_time';
            $where = [
                'account_id'=>$accountId,
                'amazon_listing_id'=>$listing_id
            ];
            $result = (new AmazonListingModel)->field($field)->where($where)->find();
            if (!empty($result)) {
                $result = $result->toArray();
            } else {
                $result = [];
            }
        } else {
            $result = json_decode($result, true);
        }
        
        return $result;
    }
    
    private function getListingKey($accountId)
    {
        return $this->hashPrefix . $accountId;
    }
    
}
