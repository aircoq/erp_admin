<?php
namespace app\report\task;
use app\common\cache\Cache;
use app\common\model\report\ReportStatisticByCountry;
use app\index\service\AbsTasker;
use think\Exception;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2018/5/11
 * Time: 17:00
 */
class CountryStatisticReport extends AbsTasker
{
    /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return '按国家统计信息写入数据库';
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
        $this->writeInCountry();
    }

    /**
     * 按国家统计信息写入数据库
     * @return bool
     * @throws Exception
     */
    private function writeInCountry()
    {
        $cache = Cache::store('report');
        $tableData = $cache->getSaleTableByCountry();
        foreach ($tableData as $k => $key) {
            $countryData = $cache->getSaleByCountry($key);
            $reportCountryModel = new ReportStatisticByCountry();
            try {
                $temp = $countryData;
                if (!isset($temp['dateline']) || $temp['dateline'] < 0) {
                    //删除缓存
                    Cache::store('report')->delSaleByCountry($key);
                    continue;
                }
                //判断记录是否存在
                $reportCountryInfo = $reportCountryModel->where([
                    'dateline' => $temp['dateline'],
                    'country_code' => $temp['country_code'],
                    'sku_id' => $temp['sku_id']
                ])->find();
                if (!empty($reportCountryInfo)) {
                    $temp['order_turnover'] = $reportCountryInfo['order_turnover'] + (isset($temp['order_turnover']) ? $temp['order_turnover'] : 0);
                    $temp['sale_quantity'] = $reportCountryInfo['sale_quantity'] + (isset($temp['sale_quantity']) ? $temp['sale_quantity'] : 0);
                    $temp['refund_quantity'] = $reportCountryInfo['refund_quantity'] + (isset($temp['refund_quantity']) ? $temp['refund_quantity'] : 0);
                    $temp = $this->checkData($temp);
                    $reportCountryModel->allowField(true)->isUpdate(true)->save($temp, [
                        'dateline' => $reportCountryInfo['dateline'],
                        'country_code' => $reportCountryInfo['country_code'],
                        'sku_id' => $reportCountryInfo['sku_id']
                    ]);
                } else {
                    if (!Cache::store('partition')->getPartition('ReportStatisticByCountry', time())) {
                        Cache::store('partition')->setPartition('ReportStatisticByCountry', time(), null, []);
                    }
                    $temp = $this->checkData($temp);
                    $reportCountryModel->allowField(true)->isUpdate(false)->save($temp);
                }
                //删除缓存
                Cache::store('report')->delSaleByCountry($key);
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