<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\ShippingAddress as Model;
use think\Db;

class ShippingAddress extends Cache
{
    const cachePrefix = 'table';
    private $tablePrefix = self::cachePrefix . ':shipping_address:';


    /**
     * 判断key是否存在
     * @param $key
     * @return bool
     */
    private function isExists($key)
    {
        if ($this->redis->exists($key)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 判断域是否存在
     * @param $key
     * @param $field
     * @return bool
     */
    private function isFieldExists($key, $field)
    {
        if ($this->redis->hExists($key, $field)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @desc 设置值
     * @param $key
     * @param $field
     * @param $value
     */
    private function setData($key, $field, $value)
    {
        if (!$this->isFieldExists($key, $field)) {
            $this->redis->hSet($key, $field, $value);
        }
    }


    /**
     * @desc 获取地址信息
     * @param $id
     * @return array|mixed
     */
    private function readShippingAddress($id)
    {
        $result = [];
        $shippingAddress = (new Model())->where(['id' => $id])->field(true)->find();
        if($shippingAddress) {
            $result = $shippingAddress->getData();
            $key = $this->tablePrefix . $id;
            foreach ($result as $k => $v) {
                $this->setData($key, $k, $v);
            }
        }
        return $result;
    }

    /**
     * @desc 获取地址信息
     * @param int $id
     * @return array|mixed
     */
    public function getAddress($id)
    {
        $key = $this->tablePrefix . $id;
        if ($this->isExists($key)) {
            $shippingInfo = $this->redis->hGetAll($key);
        } else {
            $shippingInfo = $this->readShippingAddress($id);
        }
        return $shippingInfo;
    }

    /**
     * @desc 删除缓存
     * @param int $id
     */
    public function delAddress($id)
    {
        $key = $this->tablePrefix . $id;
        $this->redis->del($key);
    }
}