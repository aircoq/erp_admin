<?php
namespace app\report\task;
use app\common\cache\Cache;
use app\common\model\report\ReportStatisticByPackage;
use app\index\service\AbsTasker;
use think\Exception;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2018/5/11
 * Time: 17:01
 */
class PackageStatisticReport extends AbsTasker
{
    /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return '包裹统计信息写入数据库';
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
        $this->write();
    }

    /**
     *  统计写入数据库数据
     */
    public function write()
    {
        $this->writeInPackage();
    }

    /**
     * 包裹统计信息写入数据库
     * @return bool
     * @throws Exception
     */
    private function writeInPackage()
    {
        $packageData = Cache::store('report')->getSaleByPackage();
        foreach ($packageData as $k => $v) {
            $reportPackageModel = new ReportStatisticByPackage();
            try {
                $temp = $v;
                if (!isset($temp['dateline']) || $temp['dateline'] < 0) {
                    //删除缓存
                    Cache::store('report')->delSaleByPackage($k);
                    continue;
                }
                //查看是否存在该记录了
                $reportPackageInfo = $reportPackageModel->where([
                    'dateline' => $temp['dateline'],
                    'channel_id' => $temp['channel_id'],
                    'warehouse_id' => $temp['warehouse_id'],
                    'shipping_id' => $temp['shipping_id'],
                    'country_code' => $temp['country_code']
                ])->find();
                if (!empty($reportPackageInfo)) {
                    $new['package_quantity'] = $reportPackageInfo['package_quantity'] + ($temp['package_quantity'] ?? 0);
                    $new['package_generated_quantity'] = $reportPackageInfo['package_generated_quantity'] + ($temp['package_generated_quantity'] ?? 0);
                    $new['shipping_fee'] = $reportPackageInfo['shipping_fee'] + ($temp['shipping_fee'] ?? 0);
                    $new['refund_quantity'] = $reportPackageInfo['refund_quantity'] + ($temp['refund_quantity'] ?? 0);
                    $new = $this->checkData($new);
                    $reportPackageModel->allowField(true)->isUpdate(true)->save($new, [
                        'dateline' => $temp['dateline'],
                        'channel_id' => $temp['channel_id'],
                        'warehouse_id' => $temp['warehouse_id'],
                        'shipping_id' => $temp['shipping_id'],
                        'country_code' => $temp['country_code']
                    ]);
                } else {
                    if (!Cache::store('partition')->getPartition('ReportStatisticByPackage', time())) {
                        Cache::store('partition')->setPartition('ReportStatisticByPackage', time(), null, []);
                    }
                    $temp = $this->checkData($temp);
                    $reportPackageModel->allowField(true)->isUpdate(false)->save($temp);
                }
                //删除缓存
                Cache::store('report')->delSaleByPackage($k);
            } catch (Exception $e) {
                throw new Exception($e->getMessage() . $e->getFile() . $e->getLine());
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