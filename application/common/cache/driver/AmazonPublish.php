<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use think\Db;
use think\Exception;

class AmazonPublish extends Cache
{
    const cachePrefix = 'table:';

    public function __construct() {
        parent::__construct();
    }

    public function getProduct($product_id)
    {
        $key = self::cachePrefix. 'amazon_publish:'. $product_id;
        $product = $this->redis->hGet($key, 'product');
        if (!empty($product)) {
            return json_decode($product, true);
        }
        $product = Db::name('amazon_publish_product')->where(['id' => $product_id])->find();
        if (empty($product)) {
            throw new Exception('Amazon刊登记录不存在，ID：'. $product_id);
        }

        $this->redis->hSet($key, 'product', json_encode($product, JSON_UNESCAPED_UNICODE));
        $this->redis->expire($key, 60 * 120);
        return $product;
    }

    /**
     * @param $product_id
     * @param $filed 字段字符串，或【'key' => 'value', 'key2' => 'value2']
     * @param string $value 值
     * @throws Exception
     */
    public function updateProduct($product_id, $filed, $value = '')
    {
        $product = $this->getProduct($product_id);
        if (empty($filed)) {
            throw new Exception('amazon_publish_product更新字段为空');
        }
        if (is_string($filed)) {
            $product[$filed] = $value;
        }
        if (is_array($filed)) {
            foreach ($filed as $key=>$val) {
                $product[$key] = $val;
            }
        }

        $key = self::cachePrefix. 'amazon_publish:'. $product_id;
        $this->redis->hSet($key, 'product', json_encode($product, JSON_UNESCAPED_UNICODE));
        $this->redis->expire($key, 60 * 120);
        return $product;
    }

    public function getDetail($product_id)
    {
        $key = self::cachePrefix. 'amazon_publish:'. $product_id;
        //$this->redis->del($key);
        $details = $this->redis->hGet($key, 'product_detail');
        if (!empty($details)) {
            return json_decode($details, true);
        }
        $details = Db::name('amazon_publish_product_detail')->where(['product_id' => $product_id])->select();
        if (empty($details)) {
            throw new Exception('Amazon刊登详情记录不存在，ID：'. $product_id);
        }

        foreach ($details as &$val) {
            $val['error_message'] = '[]';
            $val['warning_message'] = '[]';
        }

        $this->redis->hSet($key, 'product_detail', json_encode($details, JSON_UNESCAPED_UNICODE));
        $this->redis->expire($key, 60 * 120);
        return $details;
    }

    /**
     * @param $product_id
     * @param $datas ['publish_sku' => '', id => 1, 'data' => [ 'key' => 'value'...]    ]
     * @throws Exception
     */
    public function updateDetail($product_id, $datas)
    {
        $details = $this->getDetail($product_id);
        if (empty($datas)) {
            throw new Exception('amazon_publish_product_detail更新字段为空');
        }

        foreach ($details as &$detail) {
            if (
                (!empty($datas['publish_sku']) && $datas['publish_sku'] === 'ALL') ||
                (!empty($datas['publish_sku']) && $detail['publish_sku'] == $datas['publish_sku']) ||
                (!empty($datas['id']) && $detail['id'] == $datas['id'])
            ) {
                foreach ($datas['data'] as $key=>$val) {
                    $detail[$key] = $val;
                }
            }
        }
        unset($detail);

        $key = self::cachePrefix. 'amazon_publish:'. $product_id;
        $this->redis->hSet($key, 'product_detail', json_encode($details, JSON_UNESCAPED_UNICODE));
        $this->redis->expire($key, 60 * 120);
        return $details;
    }

    public function delCache($product_id) {
        $key = self::cachePrefix. 'amazon_publish:'. $product_id;
        $this->redis->del($key);
    }


    /**
     * 拿取刊登等级
     * @param int $type
     * @return int
     */
    public function getPublishLevel(int $type = 1)
    {
        $fieldArr = [1 => 'product', 'relation', 'quantity', 'image', 'price'];
        if (empty($fieldArr[$type])) {
            return 1;
        }
        $key = 'task:amazon:publish-level';
        $result = $this->redis->hGet($key, $fieldArr[$type]);
        return (int)$result;
    }


    /**
     * 设置刊登等级
     * @param int $type
     * @param int $level
     * @return bool
     */
    public function setPublishLevel(int $type, int $level)
    {
        $fieldArr = [1 => 'product', 'relation', 'quantity', 'image', 'price'];
        if (empty($fieldArr[$type])) {
            return false;
        }
        $key = 'task:amazon:publish-level';
        $this->redis->hSet($key, $fieldArr[$type], $level);
        return true;
    }


    /**
     * 重新刊登的队列
     * @param $id   刊登记录ID
     * @param $type 刊登的5个类型，5条队列的重刊队列；
     * @return bool
     * @throws Exception
     */
    public function rePublishList($id, $type)
    {
        if (!in_array($type, ['upload_product', 'upload_relation', 'upload_quantity', 'upload_image', 'upload_price'])) {
            throw new Exception('Amazon重刊登保存，未知队列类型参数:'. $type);
        }
        $key = 'task:amazon:RePublishList:'. $type;
        $this->redis->rPush($key, $id);
        return true;
    }

    /**
     * 提交了的submision类别无序集合
     * @param $id   刊登记录ID
     * @param $type 类别
     * @return bool
     * @throws Exception
     */
    public function submissionTypeCache($id, $type)
    {
        if (!in_array($type, ['upload_product', 'upload_relation', 'upload_quantity', 'upload_image', 'upload_price'])) {
            throw new Exception('Amazon刊登已提交的submissionId类别保存，未知类别类型参数:'. $type);
        }
        $key = 'task:amazon:submition-type:'. $id;
        $this->redis->sAdd($key, $type);
        $this->redis->expire($key, 86400);
        return true;
    }

    /**
     * 删除刊登的了submission类别；
     * @param $id
     * @param $type
     * @return bool
     * @throws Exception
     */
    public function delSubmissionType($id, $type)
    {
        if (!in_array($type, ['upload_product', 'upload_relation', 'upload_quantity', 'upload_image', 'upload_price'])) {
            throw new Exception('Amazon刊登已提交的submissionId类别删除，未知类别类型参数:'. $type);
        }
        $key = 'task:amazon:submition-type:'. $id;
        $this->redis->sRemove($key, $type);
        return true;
    }

    /**
     * 返回提交后的submission类别的个数；
     * @param $id
     * @return int
     */
    public function submissionTypeNum($id)
    {
        $key = 'task:amazon:submition-type:'. $id;
        $types = $this->redis->sMembers($key);
        if (empty($types)) {
            return 0;
        }
        return count($types);
    }



    /**
     * 重新亚马逊跟卖队列
     * @param $id   跟卖记录ID
     * @param $type  跟卖类型；
     * @return bool
     * @throws Exception
     */
    public function reAmazonHeelSaleList($id, $type)
    {
        if (!in_array($type, ['upload_product', 'upload_quantity', 'upload_price'])) {
            throw new Exception('Amazon重新跟卖保存，未知队列类型参数:'. $type);
        }
        $key = 'task:amazon:reAmazonHeelSaleList:'. $type;
        $this->redis->rPush($key, $id);
        return true;
    }
}