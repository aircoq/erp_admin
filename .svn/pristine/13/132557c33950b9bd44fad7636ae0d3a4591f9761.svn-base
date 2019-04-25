<?php
namespace app\report\task;
use app\common\cache\Cache;
use app\common\model\report\ReportStatisticByBuyer;
use app\index\service\AbsTasker;
use think\Exception;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2018/5/11
 * Time: 17:01
 */
class BuyerStatisticReport extends AbsTasker
{
    /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return '买家统计信息写入数据库';
    }

    /**
     * 定义任务描述
     * @return string
     */
    public function getDesc()
    {
        return '';
    }

    /**
     * 定义任务作者
     * @return string
     */
    public function getCreator()
    {
        return '宇';
    }

    /**
     * 定义任务参数规则
     * @return array
     */
    public function getParamRule()
    {
        return [];
    }

    /**
     * 执行方法
     */
    public function execute()
    {
        $this->writeInBuyer();
    }

    /** 买家统计信息写入数据库
     * @return bool
     * @throws Exception
     */
    private function writeInBuyer()
    {
        $cache = Cache::store('report');
        $tableData = $cache->getSaleTableByBuyer();
        foreach ($tableData as $k => $key) {
            $orderData = $cache->getSaleByBuyer($key);
            $reportBuyerModel = new ReportStatisticByBuyer();
            try {
                $temp = $orderData;
                if (!isset($temp['dateline']) ||$temp['dateline'] < 0) {
                    //删除缓存
                    Cache::store('report')->delSaleByBuyer($key);
                    continue;
                }
                //查看是否存在该记录了
                $reportBuyerInfo = $reportBuyerModel->where([
                    'dateline' => $temp['dateline'],
                    'channel_id' => $temp['channel_id'],
                    'sku_id' => $temp['sku_id'],
                    'buyer' => $temp['buyer']
                ])->find();
                if (!empty($reportBuyerInfo)) {
                    continue;
                } else {
                    if (!Cache::store('partition')->getPartition('ReportStatisticByBuyer', time())) {
                        Cache::store('partition')->setPartition('ReportStatisticByBuyer', time(), null, []);
                    }
                    $temp = $this->checkData($temp);
                    $reportBuyerModel->allowField(true)->isUpdate(false)->save($temp);
                }
                //删除缓存
                Cache::store('report')->delSaleByBuyer($key);
            } catch (Exception $e) {
                //throw new Exception($e->getMessage() . $e->getFile() . $e->getLine());
            }
        }
        return true;
    }

    /**
     * 检查数据
     * @param array $data
     * @return array
     */
    private function checkData(array $data)
    {
        $newData = [];
        foreach ($data as $k => $v) {
            if (is_numeric($v)) {
                if ($v < 0) {
                    $newData[$k] = 0;
                } else {
                    $newData[$k] = $v;
                }
            } else {
                $newData[$k] = $v;
            }
        }
        return $newData;
    }
}