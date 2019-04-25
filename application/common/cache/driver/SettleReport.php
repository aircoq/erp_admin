<?php

namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\aliexpress\AliexpressSettlementImport;
use app\common\model\wish\WishSettlementImport;

/**
 * Class SettleReport
 * Created by linpeng
 * updateTime: time 2019/1/16 11:37
 * @package app\common\cache\driver
 */
class SettleReport extends Cache
{

    private $wish_download_key_prefix = 'table:wish_report:';
    private $aliexpress_download_key_prefix = 'table:aliexpress_report:';

    /**
     * 获取wish结算报告下载时间
     * @param $accountID
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getWishReportDownloadTime($accountID)
    {
        $key = $this->wish_download_key_prefix;

        if ($this->redis->hExists($key, $accountID)) {
            $res = $this->redis->hGet($key, $accountID);
            return $res ?? [];
        }
        $model = new WishSettlementImport();
        $res = $model->where('account_id', $accountID)->field('invoice_date')->order('invoice_date desc')->find();
        return $res['invoice_date'] ?? [];
    }

    /**
     * 设置wish结算报告下载时间
     * @param $accountID
     * @param $time
     */
    public function setWishReportDownloadTime($accountID, $time)
    {
        $key = $this->wish_download_key_prefix;
        if ($time) {
            $this->redis->hSet($key, $accountID, $time);
        }
    }

    /**
     * 记录运行log
     * @param $code
     * @param $data
     */
    public function setIsRunningEnviroment($code, $data)
    {
        $key = 'table:wish_report_runtime:' .$code.time();
        if ($data) {
            $this->redis->hSet($key, 'runtime', json_encode($data));
        }
    }


    /**
     * 记录aliexpress的下载时间
     * @param $accountID
     * @param $time
     */
    public function setAliReportDownloadTime($accountID, $time)
    {
        $key = $this->aliexpress_download_key_prefix;
        if ($time) {
            $this->redis->hSet($key, $accountID, $time);
        }
    }


    /**
     * 获取aliexpress的下载时间
     * @param $accountID
     * @return array|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAliReportDownloadTime($accountID)
    {
        $key = $this->aliexpress_download_key_prefix;

        if ($this->redis->hExists($key, $accountID)) {
            $res = $this->redis->hGet($key, $accountID);
            return $res ?? [];
        }
        $model = new AliexpressSettlementImport();
        $res = $model->where('account_id', $accountID)->field('end_date')->order('end_date desc')->find();
        return $res['end_date'] ?? [];
    }

}


