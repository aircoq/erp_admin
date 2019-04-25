<?php

namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\service\UniqueQueuer;
use app\order\queue\AmazonOrderUploadQueue;

/** 同步发货记录
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/6/25
 * Time: 16:41
 */
class Synchronous extends Cache
{

    /** 设置zoodmall平台订单同步发货记录
     * @param $time
     * @param $order_id
     */
    public function setZoodmallSynchronousOrder($time, $order_id)
    {
        $service = new UniqueQueuer(\app\order\queue\ZoodmallSynchronousQueue::class);
        if ($time > time()) {
            $service->push($order_id ."|", $time - time());
        } else {
            $service->push($order_id . "|");
        }

        return true;
    }

    /** 检查是否已存在
     * @param $orderId
     * @return bool
     */
    public function isHasZoodmallSynchronousOrder($orderId)
    {
        $service = new UniqueQueuer(\app\order\queue\ZoodmallSynchronousQueue::class);
        return $service->exist($orderId . "|");
    }

    /** 读取zoodmall平台订单同步发货记录
     * @param int $start
     * @param int $end
     * @return array
     */
    public function getZoodmallSynchronousOrder($start = 0, $end = 50)
    {
        $zoodmallSynchronousOrder = [];
        if ($this->persistRedis->exists('zset:zoodmall_synchronous_order')) {
            $zoodmallSynchronousOrder = $this->persistRedis->zRangeByScore('zset:zoodmall_synchronous_order', '-inf', time(),
                ['withscores' => true, 'limit' => [$start, $end]]);
        }
        return $zoodmallSynchronousOrder;
    }


    /** 设置记录zoodmall某个订单同步的次数
     * @param $order_id
     */
    public function setZoodmallSynchronousOrderCount($order_id)
    {
        $count = 1;
        if ($this->persistRedis->hExists('hash:zoodmall_synchronous_order_count', $order_id)) {
            $count += $this->getWishSynchronousOrderCount($order_id);
        }
        $this->persistRedis->hset('hash:zoodmall_synchronous_order_count', $order_id, $count);
    }

    /** 获取zoodmall某个订单同步的次数
     * @param $order_id
     * @return int|string
     */
    public function getZoodmallSynchronousOrderCount($order_id)
    {
        if ($this->persistRedis->hExists('hash:zoodmall_synchronous_order_count', $order_id)) {
            return $this->persistRedis->hGet('hash:zoodmall_synchronous_order_count', $order_id);
        }
        return 0;
    }

    /** 删除特定zoodmall订单同步发货记录
     * @param $order_id
     */
    public function delZoodmallSynchronousOrder($order_id)
    {
        if ($this->persistRedis->exists('zset:zoodmall_synchronous_order')) {
            $this->persistRedis->zRem('zset:zoodmall_synchronous_order', $order_id);
            $this->delZoodmallSynchronousOrderCount($order_id);
        }
    }

    /** 删除joom某个订单同步的次数记录
     * @param $order_id
     */
    public function delZoodmallSynchronousOrderCount($order_id)
    {
        if ($this->persistRedis->hExists('hash:zoodmall_synchronous_order_count', $order_id)) {
            $this->persistRedis->hDel('hash:zoodmall_synchronous_order_count', $order_id);
        }
    }


    /** 设置yandex平台订单同步发货记录
     * @param $time
     * @param $order_id
     */
    public function setYandexSynchronousOrder($time, $order_id)
    {
        $service = new UniqueQueuer(\app\order\queue\YandexSynchronousQueue::class);
        if ($time > time()) {
            $service->push($order_id ."|", $time - time());
        } else {
            $service->push($order_id . "|");
        }

        return true;
    }

    /** 检查是否已存在
     * @param $orderId
     * @return bool
     */
    public function isHasYandexSynchronousOrder($orderId)
    {
        $service = new UniqueQueuer(\app\order\queue\YandexSynchronousQueue::class);
        return $service->exist($orderId . "|");
    }

    /** 读取yandex平台订单同步发货记录
     * @param int $start
     * @param int $end
     * @return array
     */
    public function getYandexSynchronousOrder($start = 0, $end = 50)
    {
        $yandexSynchronousOrder = [];
        if ($this->persistRedis->exists('zset:yandex_synchronous_order')) {
            $yandexSynchronousOrder = $this->persistRedis->zRangeByScore('zset:yandex_synchronous_order', '-inf', time(),
                ['withscores' => true, 'limit' => [$start, $end]]);
        }
        return $yandexSynchronousOrder;
    }


    /** 设置记录yandex某个订单同步的次数
     * @param $order_id
     */
    public function setYandexSynchronousOrderCount($order_id)
    {
        $count = 1;
        if ($this->persistRedis->hExists('hash:yandex_synchronous_order_count', $order_id)) {
            $count += $this->getWishSynchronousOrderCount($order_id);
        }
        $this->persistRedis->hset('hash:yandex_synchronous_order_count', $order_id, $count);
    }

    /** 获取yandex某个订单同步的次数
     * @param $order_id
     * @return int|string
     */
    public function getYandexSynchronousOrderCount($order_id)
    {
        if ($this->persistRedis->hExists('hash:yandex_synchronous_order_count', $order_id)) {
            return $this->persistRedis->hGet('hash:yandex_synchronous_order_count', $order_id);
        }
        return 0;
    }

    /** 删除特定yandex订单同步发货记录
     * @param $order_id
     */
    public function delYandexSynchronousOrder($order_id)
    {
        if ($this->persistRedis->exists('zset:yandex_synchronous_order')) {
            $this->persistRedis->zRem('zset:yandex_synchronous_order', $order_id);
            $this->delYandexSynchronousOrderCount($order_id);
        }
    }

    /** 删除joom某个订单同步的次数记录
     * @param $order_id
     */
    public function delYandexSynchronousOrderCount($order_id)
    {
        if ($this->persistRedis->hExists('hash:yandex_synchronous_order_count', $order_id)) {
            $this->persistRedis->hDel('hash:yandex_synchronous_order_count', $order_id);
        }
    }


    /** 设置vova平台订单同步发货记录
     * @param $time
     * @param $order_id
     */
    public function setVovaSynchronousOrder($time, $order_id)
    {
        $service = new UniqueQueuer(\app\order\queue\VovaSynchronousQueue::class);
        if ($time > time()) {
            $service->push($order_id ."|", $time - time());
        } else {
            $service->push($order_id . "|");
        }

        return true;
    }

    /** 检查是否已存在
     * @param $orderId
     * @return bool
     */
    public function isHasVovaSynchronousOrder($orderId)
    {
        $service = new UniqueQueuer(\app\order\queue\VovaSynchronousQueue::class);
        return $service->exist($orderId . "|");
    }

    /** 读取vova平台订单同步发货记录
     * @param int $start
     * @param int $end
     * @return array
     */
    public function getVovaSynchronousOrder($start = 0, $end = 50)
    {
        $cdSynchronousOrder = [];
        if ($this->persistRedis->exists('zset:vova_synchronous_order')) {
            $cdSynchronousOrder = $this->persistRedis->zRangeByScore('zset:vova_synchronous_order', '-inf', time(),
                ['withscores' => true, 'limit' => [$start, $end]]);
        }
        return $cdSynchronousOrder;
    }


    /** 设置记录vova某个订单同步的次数
     * @param $order_id
     */
    public function setVovaSynchronousOrderCount($order_id)
    {
        $count = 1;
        if ($this->persistRedis->hExists('hash:vova_synchronous_order_count', $order_id)) {
            $count += $this->getVovaSynchronousOrderCount($order_id);
        }
        $this->persistRedis->hset('hash:vova_synchronous_order_count', $order_id, $count);
    }

    /** 获取vova某个订单同步的次数
     * @param $order_id
     * @return int|string
     */
    public function getVovaSynchronousOrderCount($order_id)
    {
        if ($this->persistRedis->hExists('hash:vova_synchronous_order_count', $order_id)) {
            return $this->persistRedis->hGet('hash:vova_synchronous_order_count', $order_id);
        }
        return 0;
    }

    /** 删除特定vova订单同步发货记录
     * @param $order_id
     */
    public function delVovaSynchronousOrder($order_id)
    {
        if ($this->persistRedis->exists('zset:vova_synchronous_order')) {
            $this->persistRedis->zRem('zset:vova_synchronous_order', $order_id);
            $this->delVovaSynchronousOrderCount($order_id);
        }
    }
    /** 删除vova某个订单同步的次数记录
     * @param $order_id
     */
    public function delVovaSynchronousOrderCount($order_id)
    {
        if ($this->persistRedis->hExists('hash:vova_synchronous_order_count', $order_id)) {
            $this->persistRedis->hDel('hash:vova_synchronous_order_count', $order_id);
        }
    }



    /** 设置pdd平台订单同步发货记录
     * @param $time
     * @param $order_id
     */
    public function setPddSynchronousOrder($time, $order_id)
    {
        $service = new UniqueQueuer(\app\order\queue\PddSynchronousQueue::class);
        if ($time > time()) {
            $service->push($order_id ."|", $time - time());
        } else {
            $service->push($order_id . "|");
        }

        return true;
    }

    /** 检查是否已存在
     * @param $orderId
     * @return bool
     */
    public function isHasPddSynchronousOrder($orderId)
    {
        $service = new UniqueQueuer(\app\order\queue\PddSynchronousQueue::class);
        return $service->exist($orderId . "|");
    }

    /** 读取Pdd平台订单同步发货记录
     * @param int $start
     * @param int $end
     * @return array
     */
    public function getPddSynchronousOrder($start = 0, $end = 50)
    {
        $pddSynchronousOrder = [];
        if ($this->persistRedis->exists('zset:pdd_synchronous_order')) {
            $pddSynchronousOrder = $this->persistRedis->zRangeByScore('zset:pdd_synchronous_order', '-inf', time(),
                ['withscores' => true, 'limit' => [$start, $end]]);
        }
        return $pddSynchronousOrder;
    }


    /** 设置记录Pdd某个订单同步的次数
     * @param $order_id
     */
    public function setPddSynchronousOrderCount($order_id)
    {
        $count = 1;
        if ($this->persistRedis->hExists('hash:pdd_synchronous_order_count', $order_id)) {
            $count += $this->getPddSynchronousOrderCount($order_id);
        }
        $this->persistRedis->hset('hash:pdd_synchronous_order_count', $order_id, $count);
    }

    /** 获取Pdd某个订单同步的次数
     * @param $order_id
     * @return int|string
     */
    public function getPddSynchronousOrderCount($order_id)
    {
        if ($this->persistRedis->hExists('hash:pdd_synchronous_order_count', $order_id)) {
            return $this->persistRedis->hGet('hash:pdd_synchronous_order_count', $order_id);
        }
        return 0;
    }

    /** 删除特定Pdd订单同步发货记录
     * @param $order_id
     */
    public function delPddSynchronousOrder($order_id)
    {
        if ($this->persistRedis->exists('zset:pdd_synchronous_order')) {
            $this->persistRedis->zRem('zset:pdd_synchronous_order', $order_id);
            $this->delPddSynchronousOrderCount($order_id);
        }
    }
    /** 删除Pdd某个订单同步的次数记录
     * @param $order_id
     */
    public function delPddSynchronousOrderCount($order_id)
    {
        if ($this->persistRedis->hExists('hash:pdd_synchronous_order_count', $order_id)) {
            $this->persistRedis->hDel('hash:pdd_synchronous_order_count', $order_id);
        }
    }


    /** 设置umka平台订单同步发货记录
     * @param $time
     * @param $order_id
     */
    public function setUmkaSynchronousOrder($time, $order_id)
    {
        $service = new UniqueQueuer(\app\order\queue\UmkaSynchronousQueue::class);
        if ($time > time()) {
            $service->push($order_id ."|", $time - time());
        } else {
            $service->push($order_id . "|");
        }

        return true;
    }

    /** 检查是否已存在
     * @param $orderId
     * @return bool
     */
    public function isHasUmakSynchronousOrder($orderId)
    {
        $service = new UniqueQueuer(\app\order\queue\UmkaSynchronousQueue::class);
        return $service->exist($orderId . "|");
    }

    /** 读取umka平台订单同步发货记录
     * @param int $start
     * @param int $end
     * @return array
     */
    public function getUmkaSynchronousOrder($start = 0, $end = 50)
    {
        $umkaSynchronousOrder = [];
        if ($this->persistRedis->exists('zset:umka_synchronous_order')) {
            $umkaSynchronousOrder = $this->persistRedis->zRangeByScore('zset:umka_synchronous_order', '-inf', time(),
                ['withscores' => true, 'limit' => [$start, $end]]);
        }
        return $umkaSynchronousOrder;
    }


    /** 设置记录umka某个订单同步的次数
     * @param $order_id
     */
    public function setUmakSynchronousOrderCount($order_id)
    {
        $count = 1;
        if ($this->persistRedis->hExists('hash:umka_synchronous_order_count', $order_id)) {
            $count += $this->getUmkaSynchronousOrderCount($order_id);
        }
        $this->persistRedis->hset('hash:umka_synchronous_order_count', $order_id, $count);
    }

    /** 获取umka某个订单同步的次数
     * @param $order_id
     * @return int|string
     */
    public function getUmkaSynchronousOrderCount($order_id)
    {
        if ($this->persistRedis->hExists('hash:umka_synchronous_order_count', $order_id)) {
            return $this->persistRedis->hGet('hash:umka_synchronous_order_count', $order_id);
        }
        return 0;
    }

    /** 删除特定umka订单同步发货记录
     * @param $order_id
     */
    public function delUmkaSynchronousOrder($order_id)
    {
        if ($this->persistRedis->exists('zset:umka_synchronous_order')) {
            $this->persistRedis->zRem('zset:umka_synchronous_order', $order_id);
            $this->delUmkaSynchronousOrderCount($order_id);
        }
    }
    /** 删除umka某个订单同步的次数记录
     * @param $order_id
     */
    public function delUmkaSynchronousOrderCount($order_id)
    {
        if ($this->persistRedis->hExists('hash:umka_synchronous_order_count', $order_id)) {
            $this->persistRedis->hDel('hash:umka_synchronous_order_count', $order_id);
        }
    }

    /** 设置cd平台订单同步发货记录
     * @param $time
     * @param $order_id
     */
    public function setCdSynchronousOrder($time, $order_id)
    {
        $service = new UniqueQueuer(\app\order\queue\CdSynchronousQueue::class);
        if ($time > time()) {
            $service->push($order_id ."|", $time - time());
        } else {
            $service->push($order_id . "|");
        }

        return true;
    }

    /** 检查是否已存在
     * @param $orderId
     * @return bool
     */
    public function isHasCdSynchronousOrder($orderId)
    {
        $service = new UniqueQueuer(\app\order\queue\CdSynchronousQueue::class);
        return $service->exist($orderId . "|");
    }

    /** 读取cd平台订单同步发货记录
     * @param int $start
     * @param int $end
     * @return array
     */
    public function getCdSynchronousOrder($start = 0, $end = 50)
    {
        $cdSynchronousOrder = [];
        if ($this->persistRedis->exists('zset:cd_synchronous_order')) {
            $cdSynchronousOrder = $this->persistRedis->zRangeByScore('zset:cd_synchronous_order', '-inf', time(),
                ['withscores' => true, 'limit' => [$start, $end]]);
        }
        return $cdSynchronousOrder;
    }


    /** 设置记录cd某个订单同步的次数
     * @param $order_id
     */
    public function setCdSynchronousOrderCount($order_id)
    {
        $count = 1;
        if ($this->persistRedis->hExists('hash:cd_synchronous_order_count', $order_id)) {
            $count += $this->getWishSynchronousOrderCount($order_id);
        }
        $this->persistRedis->hset('hash:cd_synchronous_order_count', $order_id, $count);
    }

    /** 获取cd某个订单同步的次数
     * @param $order_id
     * @return int|string
     */
    public function getCdSynchronousOrderCount($order_id)
    {
        if ($this->persistRedis->hExists('hash:cd_synchronous_order_count', $order_id)) {
            return $this->persistRedis->hGet('hash:cd_synchronous_order_count', $order_id);
        }
        return 0;
    }

    /** 删除特定cd订单同步发货记录
     * @param $order_id
     */
    public function delCdSynchronousOrder($order_id)
    {
        if ($this->persistRedis->exists('zset:cd_synchronous_order')) {
            $this->persistRedis->zRem('zset:cd_synchronous_order', $order_id);
            $this->delCdSynchronousOrderCount($order_id);
        }
    }

    /** 删除joom某个订单同步的次数记录
     * @param $order_id
     */
    public function delCdSynchronousOrderCount($order_id)
    {
        if ($this->persistRedis->hExists('hash:cd_synchronous_order_count', $order_id)) {
            $this->persistRedis->hDel('hash:cd_synchronous_order_count', $order_id);
        }
    }


    /** 设置jumia平台订单同步发货记录
     * @param $time
     * @param $order_id
     */
    public function setJumiaSynchronousOrder($time, $order_id)
    {
        $service = new UniqueQueuer(\app\order\queue\JumiaSynchronousQueue::class);
        if ($time > time()) {
            $service->push($order_id ."|", $time - time());
        } else {
            $service->push($order_id . "|");
        }

        return true;
    }

    /** 检查是否已存在
     * @param $orderId
     * @return bool
     */
    public function isHasJumiaSynchronousOrder($orderId)
    {
        $service = new UniqueQueuer(\app\order\queue\JumiaSynchronousQueue::class);
        return $service->exist($orderId . "|");
    }

    /** 读取jumia平台订单同步发货记录
     * @param int $start
     * @param int $end
     * @return array
     */
    public function getJumiaSynchronousOrder($start = 0, $end = 50)
    {
        $jumiaSynchronousOrder = [];
        if ($this->persistRedis->exists('zset:jumia_synchronous_order')) {
            $jumiaSynchronousOrder = $this->persistRedis->zRangeByScore('zset:jumia_synchronous_order', '-inf', time(),
                ['withscores' => true, 'limit' => [$start, $end]]);
        }
        return $jumiaSynchronousOrder;
    }


    /** 设置记录jumia某个订单同步的次数
     * @param $order_id
     */
    public function setJumiaSynchronousOrderCount($order_id)
    {
        $count = 1;
        if ($this->persistRedis->hExists('hash:jumia_synchronous_order_count', $order_id)) {
            $count += $this->getWishSynchronousOrderCount($order_id);
        }
        $this->persistRedis->hset('hash:jumia_synchronous_order_count', $order_id, $count);
    }

    /** 获取jumia某个订单同步的次数
     * @param $order_id
     * @return int|string
     */
    public function getJumiaSynchronousOrderCount($order_id)
    {
        if ($this->persistRedis->hExists('hash:jumia_synchronous_order_count', $order_id)) {
            return $this->persistRedis->hGet('hash:jumia_synchronous_order_count', $order_id);
        }
        return 0;
    }

    /** 删除特定jumia订单同步发货记录
     * @param $order_id
     */
    public function delJumiaSynchronousOrder($order_id)
    {
        if ($this->persistRedis->exists('zset:jumia_synchronous_order')) {
            $this->persistRedis->zRem('zset:jumia_synchronous_order', $order_id);
            $this->delJumiaSynchronousOrderCount($order_id);
        }
    }

    /** 删除joom某个订单同步的次数记录
     * @param $order_id
     */
    public function delJumiaSynchronousOrderCount($order_id)
    {
        if ($this->persistRedis->hExists('hash:jumia_synchronous_order_count', $order_id)) {
            $this->persistRedis->hDel('hash:jumia_synchronous_order_count', $order_id);
        }
    }


    /** 设置walmart平台订单同步发货记录
     * @param $time
     * @param $order_id
     */
    public function setWalmartSynchronousOrder($time, $order_id)
    {
        $service = new UniqueQueuer(\app\order\queue\WalmartSynchronousQueue::class);
        if ($time > time()) {
            $service->push($order_id ."|", $time - time());
        } else {
            $service->push($order_id . "|");
        }

        return true;
    }

    /** 检查是否已存在
     * @param $orderId
     * @return bool
     */
    public function isHasWalmartSynchronousOrder($orderId)
    {
        $service = new UniqueQueuer(\app\order\queue\WalmartSynchronousQueue::class);
        return $service->exist($orderId . "|");
    }

    /** 读取walmart平台订单同步发货记录
     * @param int $start
     * @param int $end
     * @return array
     */
    public function getWalmartSynchronousOrder($start = 0, $end = 50)
    {
        $walmartSynchronousOrder = [];
        if ($this->persistRedis->exists('zset:walmart_synchronous_order')) {
            $walmartSynchronousOrder = $this->persistRedis->zRangeByScore('zset:walmart_synchronous_order', '-inf', time(),
                ['withscores' => true, 'limit' => [$start, $end]]);
        }
        return $walmartSynchronousOrder;
    }


    /** 设置记录walmart某个订单同步的次数
     * @param $order_id
     */
    public function setWalmartSynchronousOrderCount($order_id)
    {
        $count = 1;
        if ($this->persistRedis->hExists('hash:walmart_synchronous_order_count', $order_id)) {
            $count += $this->getWishSynchronousOrderCount($order_id);
        }
        $this->persistRedis->hset('hash:walmart_synchronous_order_count', $order_id, $count);
    }

    /** 获取walmart某个订单同步的次数
     * @param $order_id
     * @return int|string
     */
    public function getWalmartSynchronousOrderCount($order_id)
    {
        if ($this->persistRedis->hExists('hash:walmart_synchronous_order_count', $order_id)) {
            return $this->persistRedis->hGet('hash:walmart_synchronous_order_count', $order_id);
        }
        return 0;
    }

    /** 删除特定walmart订单同步发货记录
     * @param $order_id
     */
    public function delWalmartSynchronousOrder($order_id)
    {
        if ($this->persistRedis->exists('zset:walmart_synchronous_order')) {
            $this->persistRedis->zRem('zset:walmart_synchronous_order', $order_id);
            $this->delWalmartSynchronousOrderCount($order_id);
        }
    }

    /** 删除joom某个订单同步的次数记录
     * @param $order_id
     */
    public function delWalmartSynchronousOrderCount($order_id)
    {
        if ($this->persistRedis->hExists('hash:walmart_synchronous_order_count', $order_id)) {
            $this->persistRedis->hDel('hash:walmart_synchronous_order_count', $order_id);
        }
    }



    /** 设置paytm平台订单同步发货记录
     * @param $time
     * @param $order_id
     */
    public function setPaytmSynchronousOrder($time, $order_id)
    {
        $service = new UniqueQueuer(\app\order\queue\PaytmSynchronousQueue::class);
        if ($time > time()) {
            $service->push($order_id ."|", $time - time());
        } else {
            $service->push($order_id . "|");
        }

        return true;
    }

    /** 检查是否已存在
     * @param $orderId
     * @return bool
     */
    public function isHasPaytmSynchronousOrder($orderId)
    {
        $service = new UniqueQueuer(\app\order\queue\PaytmSynchronousQueue::class);
        return $service->exist($orderId . "|");
    }

    /** 读取paytm平台订单同步发货记录
     * @param int $start
     * @param int $end
     * @return array
     */
    public function getPaytmSynchronousOrder($start = 0, $end = 50)
    {
        $paytmSynchronousOrder = [];
        if ($this->persistRedis->exists('zset:paytm_synchronous_order')) {
            $paytmSynchronousOrder = $this->persistRedis->zRangeByScore('zset:paytm_synchronous_order', '-inf', time(),
                ['withscores' => true, 'limit' => [$start, $end]]);
        }
        return $paytmSynchronousOrder;
    }


    /** 设置记录paytm某个订单同步的次数
     * @param $order_id
     */
    public function setPaytmSynchronousOrderCount($order_id)
    {
        $count = 1;
        if ($this->persistRedis->hExists('hash:paytm_synchronous_order_count', $order_id)) {
            $count += $this->getWishSynchronousOrderCount($order_id);
        }
        $this->persistRedis->hset('hash:paytm_synchronous_order_count', $order_id, $count);
    }

    /** 获取paytm某个订单同步的次数
     * @param $order_id
     * @return int|string
     */
    public function getPaytmSynchronousOrderCount($order_id)
    {
        if ($this->persistRedis->hExists('hash:paytm_synchronous_order_count', $order_id)) {
            return $this->persistRedis->hGet('hash:paytm_synchronous_order_count', $order_id);
        }
        return 0;
    }

    /** 删除特定paytm订单同步发货记录
     * @param $order_id
     */
    public function delPaytmSynchronousOrder($order_id)
    {
        if ($this->persistRedis->exists('zset:paytm_synchronous_order')) {
            $this->persistRedis->zRem('zset:paytm_synchronous_order', $order_id);
            $this->delPaytmSynchronousOrderCount($order_id);
        }
    }

    /** 删除joom某个订单同步的次数记录
     * @param $order_id
     */
    public function delPaytmSynchronousOrderCount($order_id)
    {
        if ($this->persistRedis->hExists('hash:paytm_synchronous_order_count', $order_id)) {
            $this->persistRedis->hDel('hash:paytm_synchronous_order_count', $order_id);
        }
    }

    /** 设置pandao平台订单同步发货记录
     * @param $time
     * @param $order_id
     */
    public function setPandaoSynchronousOrder($time, $order_id)
    {
        $service = new UniqueQueuer(\app\order\queue\PandaoSynchronousQueue::class);
        if ($time > time()) {
            $service->push($order_id ."|", $time - time());
        } else {
            $service->push($order_id . "|");
        }

        return true;
    }

    /** 检查是否已存在
     * @param $orderId
     * @return bool
     */
    public function isHasPandaoSynchronousOrder($orderId)
    {
        $service = new UniqueQueuer(\app\order\queue\PandaoSynchronousQueue::class);
        return $service->exist($orderId . "|");
    }

    /** 读取pandao平台订单同步发货记录
     * @param int $start
     * @param int $end
     * @return array
     */
    public function getPandaoSynchronousOrder($start = 0, $end = 50)
    {
        $pandaoSynchronousOrder = [];
        if ($this->persistRedis->exists('')) {
            $pandaoSynchronousOrder = $this->persistRedis->zRangeByScore('zset:pandao_synchronous_order', '-inf', time(),
                ['withscores' => true, 'limit' => [$start, $end]]);
        }
        return $pandaoSynchronousOrder;
    }


    /** 设置记录pandao某个订单同步的次数
     * @param $order_id
     */
    public function setPandaoSynchronousOrderCount($order_id)
    {
        $count = 1;
        if ($this->persistRedis->hExists('hash:pandao_synchronous_order_count', $order_id)) {
            $count += $this->getWishSynchronousOrderCount($order_id);
        }
        $this->persistRedis->hset('hash:pandao_synchronous_order_count', $order_id, $count);
    }

    /** 获取pandao某个订单同步的次数
     * @param $order_id
     * @return int|string
     */
    public function getPandaoSynchronousOrderCount($order_id)
    {
        if ($this->persistRedis->hExists('hash:pandao_synchronous_order_count', $order_id)) {
            return $this->persistRedis->hGet('hash:pandao_synchronous_order_count', $order_id);
        }
        return 0;
    }

    /** 删除特定pandao订单同步发货记录
     * @param $order_id
     */
    public function delPandaoSynchronousOrder($order_id)
    {
        if ($this->persistRedis->exists('zset:pandao_synchronous_order')) {
            $this->persistRedis->zRem('zset:pandao_synchronous_order', $order_id);
            $this->delPandaoSynchronousOrderCount($order_id);
        }
    }

    /** 删除pandao某个订单同步的次数记录
     * @param $order_id
     */
    public function delPandaoSynchronousOrderCount($order_id)
    {
        if ($this->persistRedis->hExists('hash:pandao_synchronous_order_count', $order_id)) {
            $this->persistRedis->hDel('hash:pandao_synchronous_order_count', $order_id);
        }
    }

    /** 设置joom平台订单同步发货记录
     * @param $time
     * @param $order_id
     */
    public function setJoomSynchronousOrder($time, $order_id)
    {
        $service = new UniqueQueuer(\app\order\queue\JoomSynchronousQueue::class);
        if ($time > time()) {
            $service->push($order_id ."|", $time - time());
        } else {
            $service->push($order_id . "|");
        }

        return true;
    }

    /** 检查是否已存在
     * @param $orderId
     * @return bool
     */
    public function isHasJoomSynchronousOrder($orderId)
    {
        $service = new UniqueQueuer(\app\order\queue\JoomSynchronousQueue::class);
        return $service->exist($orderId . "|");
    }

    /** 读取joom平台订单同步发货记录
     * @param int $start
     * @param int $end
     * @return array
     */
    public function getJoomSynchronousOrder($start = 0, $end = 50)
    {
        $joomSynchronousOrder = [];
        if ($this->persistRedis->exists('zset:joom_synchronous_order')) {
            $joomSynchronousOrder = $this->persistRedis->zRangeByScore('zset:joom_synchronous_order', '-inf', time(),
                ['withscores' => true, 'limit' => [$start, $end]]);
        }
        return $joomSynchronousOrder;
    }


    /** 设置记录joom某个订单同步的次数
     * @param $order_id
     */
    public function setJoomSynchronousOrderCount($order_id)
    {
        $count = 1;
        if ($this->persistRedis->hExists('hash:joom_synchronous_order_count', $order_id)) {
            $count += $this->getWishSynchronousOrderCount($order_id);
        }
        $this->persistRedis->hset('hash:joom_synchronous_order_count', $order_id, $count);
    }

    /** 获取joom某个订单同步的次数
     * @param $order_id
     * @return int|string
     */
    public function getJoomSynchronousOrderCount($order_id)
    {
        if ($this->persistRedis->hExists('hash:joom_synchronous_order_count', $order_id)) {
            return $this->persistRedis->hGet('hash:joom_synchronous_order_count', $order_id);
        }
        return 0;
    }

    /** 删除特定joom订单同步发货记录
     * @param $order_id
     */
    public function delJoomSynchronousOrder($order_id)
    {
        if ($this->persistRedis->exists('zset:joom_synchronous_order')) {
            $this->persistRedis->zRem('zset:joom_synchronous_order', $order_id);
            $this->delJoomSynchronousOrderCount($order_id);
        }
    }

    /** 删除joom某个订单同步的次数记录
     * @param $order_id
     */
    public function delJoomSynchronousOrderCount($order_id)
    {
        if ($this->persistRedis->hExists('hash:joom_synchronous_order_count', $order_id)) {
            $this->persistRedis->hDel('hash:joom_synchronous_order_count', $order_id);
        }
    }

    /** 设置ebay平台订单同步发货记录
     * @param $time
     * @param $order_id
     */
    public function setEbaySynchronousOrder($time, $order_id)
    {
        $service = new UniqueQueuer(\app\order\queue\EbaySynchronousQueue::class);
        if ($time > time()) {
            $service->push($order_id ."|", $time - time());
        } else {
            $service->push($order_id . "|");
        }

        return true;
    }

    /** 检查是否已存在
     * @param $order_id
     * @return bool
     */
    public function isHasEbaySynchronousOrder($orderId)
    {
        $service = new UniqueQueuer(\app\order\queue\EbaySynchronousQueue::class);
        return $service->exist($orderId . "|");
    }

    /** 读取ebay平台订单同步发货记录
     * @param int $start
     * @param int $end
     * @return array
     */
    public function getEbaySynchronousOrder($start = 0, $end = 50)
    {
        $ebaySynchronousOrder = [];
        if ($this->persistRedis->exists('zset:ebay_synchronous_order')) {
            $ebaySynchronousOrder = $this->persistRedis->zRangeByScore('zset:ebay_synchronous_order', '-inf', time(),
                ['withscores' => true, 'limit' => [$start, $end]]);
        }
        return $ebaySynchronousOrder;
    }


    /** 设置记录ebay某个订单同步的次数
     * @param $order_id
     */
    public function setEbaySynchronousOrderCount($order_id)
    {
        $count = 1;
        if ($this->persistRedis->hExists('hash:ebay_synchronous_order_count', $order_id)) {
            $count += $this->getWishSynchronousOrderCount($order_id);
        }
        $this->persistRedis->hset('hash:ebay_synchronous_order_count', $order_id, $count);
    }

    /** 获取ebay某个订单同步的次数
     * @param $order_id
     * @return int|string
     */
    public function getEbaySynchronousOrderCount($order_id)
    {
        if ($this->persistRedis->hExists('hash:ebay_synchronous_order_count', $order_id)) {
            return $this->persistRedis->hGet('hash:ebay_synchronous_order_count', $order_id);
        }
        return 0;
    }

    /** 删除特定ebay订单同步发货记录
     * @param $order_id
     */
    public function delEbaySynchronousOrder($order_id)
    {
        if ($this->persistRedis->exists('zset:ebay_synchronous_order')) {
            $this->persistRedis->zRem('zset:ebay_synchronous_order', $order_id);
            $this->delEbaySynchronousOrderCount($order_id);
        }
    }

    /** 删除wish某个订单同步的次数记录
     * @param $order_id
     */
    public function delEbaySynchronousOrderCount($order_id)
    {
        if ($this->persistRedis->hExists('hash:ebay_synchronous_order_count', $order_id)) {
            $this->persistRedis->hDel('hash:ebay_synchronous_order_count', $order_id);
        }
    }


    /** 设置速卖通平台订单同步发货记录
     * @param $time
     * @param $order_id
     * @param $second 是否为第二次标记
     */
    public function setAliExpressSynchronousOrder($time, $orderId, $second = 0)
    {
        $className = 0 == $second ? \app\order\queue\AliExpressOrderUploadQueue::class : \app\order\queue\AliExpressOrderUploadSecQueue::class;
        $service = new UniqueQueuer($className);
        if (time() < $time) {
            $service->push((string)$orderId, $time - time());
        } else {
            $service->push((string)$orderId);
        }

        return true;
    }

    /** 检查是否已存在
     * @param $orderId
     * @return bool
     */
    public function isHasAliExpressSynchronousOrder($orderId)
    {
        $service = new UniqueQueuer(\app\order\queue\AliExpressOrderUploadQueue::class);
        return $service->exist($orderId);
    }

    /**
     * 设置亚马逊平台订单同步发货记录
     * @param int $time
     * @param string $orderId 订单Id
     * @return boolean
     */
    public function setAmazonSynchronousOrder($time, $orderId, $accountId = 0)
    {
        if (!$accountId || $time > time()) {
            return true;
        }
        $this->redis->hSet('task:amazon_synchronous_order:'. $accountId, $orderId, $time);
        return true;
    }

    /** 检查是否已存在
     * @param $orderId
     * @return bool
     */
    public function isHasAmazonSynchronousOrder($orderId, $accountId = 0)
    {
        if ($accountId == 0) {
            return true;
        }
        $flag = $this->redis->hExists('task:amazon_synchronous_order:'. $accountId, $orderId);
        return $flag;
    }

    /**
     * 获取amazon同步订单(按照score从小到大)
     * @param int $start
     * @param int $end
     * @return array
     */
    public function getAmazonSynchonousOrder($start = 0, $end = 50)
    {
        $orders = $this->persistRedis->zRange('zset:amazon_synchronous_order', $start, $end, true);
        return $orders ? $orders : [];
    }

    /** 设置wish平台订单同步发货记录
     * @param $time
     * @param $order_id
     * @param bool|false $count 是否计数
     */
    public function setWishSynchronousOrder($time, $order_id, $count = false)
    {
        $this->persistRedis->zAdd('zset:wish_synchronous_order', $time, $order_id);
        if ($count) {
            $this->setWishSynchronousOrderCount($order_id);
        }
    }

    /** 检查是否已存在
     * @param $order_id
     * @return bool
     */
    public function isHasWishSynchronousOrder($order_id)
    {
        $score = $this->persistRedis->zRANK('zset:wish_synchronous_order', $order_id);
        if ($score) {
            return true;  //存在
        }
        return false;
    }

    /** 读取wish平台订单同步发货记录
     * @param int $start
     * @param int $end
     * @return array
     */
    public function getWishSynchronousOrder($start = 0, $end = 50)
    {
        $wishSynchronousOrder = [];
        if ($this->persistRedis->exists('zset:wish_synchronous_order')) {
            $wishSynchronousOrder = $this->persistRedis->zRangeByScore('zset:wish_synchronous_order', '-inf', time(),
                ['withscores' => true, 'limit' => [$start, $end]]);
        }
        return $wishSynchronousOrder;
    }

    /** 删除特定wish订单同步发货记录
     * @param $order_id
     */
    public function delWishSynchronousOrder($order_id)
    {
        if ($this->persistRedis->exists('zset:wish_synchronous_order')) {
            $this->persistRedis->zRem('zset:wish_synchronous_order', $order_id);
            $this->delWishSynchronousOrderCount($order_id);
        }
    }

    /** 设置记录wish某个订单同步的次数
     * @param $order_id
     */
    public function setWishSynchronousOrderCount($order_id)
    {
        $count = 1;
        if ($this->persistRedis->hExists('hash:wish_synchronous_order_count', $order_id)) {
            $count += $this->getWishSynchronousOrderCount($order_id);
        }
        $this->persistRedis->hset('hash:wish_synchronous_order_count', $order_id, $count);
    }

    /** 获取wish某个订单同步的次数
     * @param $order_id
     * @return int|string
     */
    public function getWishSynchronousOrderCount($order_id)
    {
        if ($this->persistRedis->hExists('hash:wish_synchronous_order_count', $order_id)) {
            return $this->persistRedis->hGet('hash:wish_synchronous_order_count', $order_id);
        }
        return 0;
    }

    /** 删除wish某个订单同步的次数记录
     * @param $order_id
     */
    public function delWishSynchronousOrderCount($order_id)
    {
        if ($this->persistRedis->hExists('hash:wish_synchronous_order_count', $order_id)) {
            $this->persistRedis->hDel('hash:wish_synchronous_order_count', $order_id);
        }
    }

    /**
     * 设置记录wish某个订单同步的次数
     * @param $order_id
     * @return boolean
     */
    public function setAmazonSynchronousOrderCount($order_id)
    {
        $count = 1;
        if ($this->persistRedis->hExists('hash:amazon_synchronous_order_count', $order_id)) {
            $count += $this->getAmazonSynchronousOrderCount($order_id);
        }
        $this->persistRedis->hset('hash:amazon_synchronous_order_count', $order_id, $count);
    }

    /**
     * 获取amazon某个订单同步的次数
     * @param $order_id
     * @return int|string
     */
    public function getAmazonSynchronousOrderCount($order_id)
    {
        if ($this->persistRedis->hExists('hash:amazon_synchronous_order_count', $order_id)) {
            return $this->persistRedis->hGet('hash:amazon_synchronous_order_count', $order_id);
        }
        return 0;
    }

    /**
     * 删除amazon同步订单集合
     * @param string $order_id
     * @return boolean
     */
    public function delAmazonSynchronousOrder($order_id)
    {
        return $this->persistRedis->zRem('zset:amazon_synchronous_order', $order_id);
    }

    /**
     * 删除amazon hash中总数
     * @param string $order_id
     * @return boolean
     */
    public function delAmazonSynchronousOrderCount($order_id)
    {
        return $this->persistRedis->hDel('hash:amazon_synchronous_order_count', $order_id);
    }

    /**
     * 获取Aliexpress订单同步发货记录
     * @param int $start
     * @param int $end
     * @return array
     */
    public function getAliExpressSynchronousOrder($start = 0, $end = 50)
    {
        $aliSynchronousOrder = [];
        if ($this->persistRedis->exists('zset:aliExpress_synchronous_order')) {
            $aliSynchronousOrder = $this->persistRedis->zRangeByScore('zset:aliExpress_synchronous_order', '-inf', time(),
                ['withscores' => true, 'limit' => [$start, $end]]);
        }
        return $aliSynchronousOrder;
    }

    /**
     * 设置记录Aliexpress某个订单同步的次数
     * @param $order_id
     */
    public function setAliExpressSynchronousOrderCount($order_id)
    {
        $count = 1;
        if ($this->persistRedis->hExists('hash:aliExpress_synchronous_order_count', $order_id)) {
            $count += $this->getAliExpressSynchronousOrderCount($order_id);
        }
        $this->persistRedis->hset('hash:aliExpress_synchronous_order_count', $order_id, $count);
    }

    /**
     * 获取Aliexpress某个订单同步的次数
     * @param $order_id
     * @return int|string
     */
    public function getAliExpressSynchronousOrderCount($order_id)
    {
        if ($this->persistRedis->hExists('hash:aliExpress_synchronous_order_count', $order_id)) {
            return $this->persistRedis->hGet('hash:aliExpress_synchronous_order_count', $order_id);
        }
        return 0;
    }

    /**
     * 删除特定Aliexpress订单同步发货记录
     * @param $order_id
     */
    public function delAliExpressSynchronousOrder($order_id)
    {
        if ($this->persistRedis->exists('zset:aliExpress_synchronous_order')) {
            $this->persistRedis->zRem('zset:aliExpress_synchronous_order', $order_id);
            $this->delAliExpressSynchronousOrderCount($order_id);
        }
    }

    /**
     * 删除Aliexpress某个订单同步的次数记录
     * @param $order_id
     */
    public function delAliExpressSynchronousOrderCount($order_id)
    {
        if ($this->persistRedis->hExists('hash:aliExpress_synchronous_order_count', $order_id)) {
            $this->persistRedis->hDel('hash:aliExpress_synchronous_order_count', $order_id);
        }
    }

    /** 设置daraz平台订单同步发货记录
     * @param $time
     * @param $order_id
     */
    public function setDarazSynchronousOrder($time, $order_id)
    {
        $service = new UniqueQueuer(\app\order\queue\DarazSynchronousQueue::class);
        if ($time > time()) {
            $service->push($order_id ."|", $time - time());
        } else {
            $service->push($order_id . "|");
        }

        return true;
    }

    /** 检查是否已存在
     * @param $orderId
     * @return bool
     */
    public function isHasDarazSynchronousOrder($orderId)
    {
        $service = new UniqueQueuer(\app\order\queue\DarazSynchronousQueue::class);
        return $service->exist($orderId . "|");
    }

    /** 读取daraz平台订单同步发货记录
 * @param int $start
 * @param int $end
 * @return array
 */
    public function getDarazSynchronousOrder($start = 0, $end = 50)
    {
        $darazSynchronousOrder = [];
        if ($this->persistRedis->exists('zset:daraz_synchronous_order')) {
            $darazSynchronousOrder = $this->persistRedis->zRangeByScore('zset:daraz_synchronous_order', '-inf', time(),
                ['withscores' => true, 'limit' => [$start, $end]]);
        }
        return $darazSynchronousOrder;
    }


    /** 设置记录daraz某个订单同步的次数
     * @param $order_id
     */
    public function setDarazSynchronousOrderCount($order_id)
    {
        $count = 1;
        if ($this->persistRedis->hExists('hash:daraz_synchronous_order_count', $order_id)) {
            $count += $this->getDarazSynchronousOrderCount($order_id);
        }
        $this->persistRedis->hset('hash:daraz_synchronous_order_count', $order_id, $count);
    }

    /** 获取daraz某个订单同步的次数
     * @param $order_id
     * @return int|string
     */
    public function getDarazSynchronousOrderCount($order_id)
    {
        if ($this->persistRedis->hExists('hash:daraz_synchronous_order_count', $order_id)) {
            return $this->persistRedis->hGet('hash:daraz_synchronous_order_count', $order_id);
        }
        return 0;
    }

    /** 删除特定daraz订单同步发货记录
     * @param $order_id
     */
    public function delDarazSynchronousOrder($order_id)
    {
        if ($this->persistRedis->exists('zset:daraz_synchronous_order')) {
            $this->persistRedis->zRem('zset:daraz_synchronous_order', $order_id);
            $this->delDarazSynchronousOrderCount($order_id);
        }
    }

    /** 删除joom某个订单同步的次数记录
     * @param $order_id
     */
    public function delDarazSynchronousOrderCount($order_id)
    {
        if ($this->persistRedis->hExists('hash:daraz_synchronous_order_count', $order_id)) {
            $this->persistRedis->hDel('hash:daraz_synchronous_order_count', $order_id);
        }
    }

    /** 设置oberlo平台订单同步发货记录
     * @param $time
     * @param $order_id
     */
    public function setOberloSynchronousOrder($time, $order_id)
    {
        $service = new UniqueQueuer(\app\order\queue\OberloSynchronousQueue::class);
        if ($time > time()) {
            $service->push($order_id ."|", $time - time());
        } else {
            $service->push($order_id . "|");
        }

        return true;
    }

    /** 检查是否已存在
     * @param $orderId
     * @return bool
     */
    public function isHasOberloSynchronousOrder($orderId)
    {
        $service = new UniqueQueuer(\app\order\queue\OberloSynchronousQueue::class);
        return $service->exist($orderId . "|");
    }


}