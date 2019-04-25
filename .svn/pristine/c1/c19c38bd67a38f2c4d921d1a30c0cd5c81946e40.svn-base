<?php
namespace app\report\task;
use app\common\cache\Cache;
use app\common\model\report\ReportStatisticByDate;
use app\index\service\AbsTasker;
use think\Exception;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2018/5/11
 * Time: 17:00
 */
class DateStatisticReport extends AbsTasker
{
    /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return '按日期统计产品信息写入数据库';
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
        $this->writeInDate();
    }

    /**
     * 按日期统计产品信息写入数据库
     * @return bool
     * @throws Exception
     */
    private function writeInDate()
    {
        $cache = Cache::store('report');
        $tableData = Cache::store('report')->getSaleTableByDate();
        foreach ($tableData as $k => $key) {
            $dateData = $cache->getSaleByDate($key);
            $reportDateModel = new ReportStatisticByDate();
            try {
                $temp = $dateData;
                if(isset($tem['category_id']) && !is_numeric($temp['category_id'])){
                    //删除缓存
                    Cache::store('report')->delSaleByDate($key);
                    continue;
                }
                //判断记录是否存在
                $reportDateInfo = $reportDateModel->where([
                    'year' => $temp['year'],
                    'month' => $temp['month'],
                    'sku_id' => $temp['sku_id']
                ])->find();
                if (!empty($reportDateInfo)) {
                    $temp['order_turnover'] = $reportDateInfo['order_turnover'] + (isset($temp['order_turnover']) ? $temp['order_turnover'] : 0);
                    $temp['sale_quantity'] = $reportDateInfo['sale_quantity'] + (isset($temp['sale_quantity']) ? $temp['sale_quantity'] : 0);
                    $temp['refund_quantity'] = $reportDateInfo['refund_quantity'] + (isset($temp['refund_quantity']) ? $temp['refund_quantity'] : 0);
                    $temp = $this->checkData($temp);
                    $reportDateModel->allowField(true)->isUpdate(true)->save($temp, [
                        'year' => $temp['year'],
                        'month' => $temp['month'],
                        'sku_id' => $temp['sku_id']
                    ]);
                } else {
                    $temp = $this->checkData($temp);
                    $reportDateModel->allowField(true)->isUpdate(false)->save($temp);
                }
                //删除缓存
                Cache::store('report')->delSaleByDate($key);
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