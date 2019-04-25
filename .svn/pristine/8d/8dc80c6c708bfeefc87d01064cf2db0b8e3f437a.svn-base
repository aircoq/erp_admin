<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\PurchaseProposal as PurchaseProposalModel;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2017/01/09
 * Time: 17:45
 */
class PurchaseProposal extends Cache
{
    private $key = 'cache:purchase_proposal';
    private $seconds = 600;//key 的过期时间为半天(12小时)

    /** 获取采购建议
     * @param $id
     * @return array
     */
    public function getPurchaseProposal($id = 0)
    {
        if (self::$redis->exists('cache:PurchaseProposal')) {
            if (!empty($id)) {
                $result = self::$redis->zrangebyscore('cache:PurchaseProposal', $id, $id);
                if (isset($result[0]) && !empty($result[0])) {
                    return json_decode($result[0], true);
                } else {
                    return [];
                }
            }
            $data = self::$redis->zrange('cache:PurchaseProposal', 0, -1);
            $result = [];
            foreach ($data as $k => $v) {
                array_push($result, json_decode($v, true));
            }
            return $result;
        }
        //查表
        $supplierModel = new PurchaseProposalModel();
        $result = $supplierModel::field('*')->select();
        foreach ($result as $k => $v) {
            self::$redis->zadd('cache:PurchaseProposal', $v['id'], json_encode($v));
        }
        if (!empty($id)) {
            $result = self::$redis->zrangebyscore('cache:PurchaseProposal', $id, $id);
            if (isset($result[0]) && !empty($result[0])) {
                return json_decode($result[0], true);
            } else {
                return [];
            }
        }
        return $result;
    }

    /**
     * @desc 检查需要生成采购建议的采购人员是否已经加入到队列
     * @param int $purchaseId 采购人员id
     * @return boolean ture|false 如果之前没有，return false. 如果已经存在了就return true
     * @author Jimmy <554511322@qq.com>
     * @date 2018-04-13 10:05:11
     */
    public function isExists($purchaseId)
    {
        $key = $this->key . ':' . $purchaseId;
        return $this->redis->get($key) ? true : false;
    }

    /**
     * @desc 设置已经添加到生成采购建议队列的key
     * @param int $purchaseId 采购员Id
     * /**
     * @desc 成功执行之后删除key
     * @param int $purchaseId 采购员ID
     * @author Jimmy <554511322@qq.com>
     * @date 2018-04-13 16:39:11
     */
    public function delKey($purchaseId)
    {
        $key = $this->key . ':' . $purchaseId;
        $this->redis->del($key);
    }

    /* @param int $userId 用户Id
     * @author Jimmy <554511322@qq.com>
     * @date 2018-04-13 10:57:11
     */
    public function setKey($purchaseId, $userId)
    {
        $key = $this->key . ':' . $purchaseId;
        $this->redis->set($key, $userId);
        //设置过期时间
        $this->redis->expire($key, $this->seconds);
    }
}