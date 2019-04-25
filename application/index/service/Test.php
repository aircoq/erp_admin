<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-3-28
 * Time: 上午10:52
 */

namespace app\index\service;


use app\common\model\App;
use erp\AbsServer;
use app\order\access\Order;
use app\order\access\Warehouse;
use think\Log;
use think\Request;
use think\Db;
use app\common\model\Software;
use app\common\model\SoftwareVersion;

class Test extends AbsServer
{
    //\app\common\model\Test 
    protected $test;
    protected $model = \app\common\model\Test::class;
    protected $orderServer = \app\common\service\Order::class;

    /**
     * 获取最新版本
     * @return mixed
     */
    public function getLastVersion()
    {
        $query = "select a.* from app a inner join (select type,max(version) version from app GROUP BY type) b on a.type = b.type and a.version=b.version order by a.type";
        $result =  Db::query($query);
        return $result;
    }

    /**
     * 获取历史版本
     * @param $notId
     * @return mixed
     */
    public function historyVersion($notId)
    {
        $appModel = new App();
        $historyVersion = $appModel->field('*')->whereNotIn('id',$notId)->select();
        return $historyVersion;
    }

    /**
     * 移植最新版本
     * @param $lastedVersion
     */
    public function moveLasted($lastedVersion)
    {
        $software = new Software();
        foreach ($lastedVersion as $key => $lasted) {
            $lastedVersion[$key]['creator_id'] = $lasted['creator_id'] ?? '1';
            $lastedVersion[$key]['software_type'] = $lasted['type'];
            unset($lastedVersion[$key]['type']);
        }
        try {
            Log::info("开始移植数据入software table");
            $software->insertAll($lastedVersion);
            Log::info('移植完成');
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * 移植历史版本
     * @param $historyVersion
     */
    public function moveHistory($historyVersion)
    {
        $softwareVerson = new SoftwareVersion();
        $historyVersions = [];
        foreach ($historyVersion as $key => $history) {
            $historyVersions[$key]['software_id'] = $history['id'];
            $historyVersions[$key]['software_type'] = $history['type'];
            $historyVersions[$key]['version'] = $history['version'];
            $historyVersions[$key]['md5'] = $history['md5'];
            $historyVersions[$key]['remark'] = $history['remark'];
            $historyVersions[$key]['status'] = $history['status'];
            $historyVersions[$key]['upgrade_address'] = $history['upgrade_address'];
            $historyVersions[$key]['create_time'] = $history['create_time'];
            $historyVersions[$key]['creator_id'] = $history['creator_id'] ?? '1';
            unset($historyVersion);
        }

        // 批量添加数据入software_version
        try {
            Log::info("开始移植数据入 software_version");
            $softwareVerson->insertAll($historyVersions);
            Log::info("software_version 移植完成");
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     *
     */
    public function moveDana()
    {
        //获取最新版本
        $lastedVersion = $this->getLastVersion();
        $notId  = array_column($lastedVersion, 'id');

        // 获取历史版本
        $historyVersion = $this->historyVersion($notId);

        // 清空表数据
        Log::info("开始清空原数据");
        $cleanSoftwareVersion = "TRUNCATE table software_version";
        $cleanSoftware = "TRUNCATE table software";
        Db::query($cleanSoftwareVersion);
        Db::query($cleanSoftware);

        //移植数据
        $this->moveLasted($lastedVersion);
        $this->moveHistory($historyVersion);
    }
}