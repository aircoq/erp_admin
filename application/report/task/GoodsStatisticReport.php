<?php
namespace app\report\task;

use app\common\cache\Cache;
use app\common\model\report\ReportStatisticByGoods;
use app\common\model\User;
use app\index\service\AbsTasker;
use app\report\service\StatisticGoods;
use think\Exception;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2018/5/11
 * Time: 17:00
 */
class GoodsStatisticReport extends AbsTasker
{
    /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return '产品统计信息写入数据库';
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
        try{
            $this->writeInGoods();
        }catch (Exception $e){
        }
    }

    /**
     * 产品统计信息写入数据库
     * @return bool
     * @throws Exception
     */
    public function writeInGoods()
    {
        $cache = Cache::store('report');
        $tableData = $cache->getSaleTableByGoods();
        foreach ($tableData as $k => $key) {
            $goodsData = $cache->getSaleByGoods($key);
            $reportGoodsModel = new ReportStatisticByGoods();
            try {
                $temp = $goodsData;
                if (!isset($temp['dateline']) || $temp['dateline'] < 0) {
                    //删除缓存
                    Cache::store('report')->delSaleByGoods($key);
                    continue;
                }
                //查看是否存在该记录了
                $reportGoodsInfo = $reportGoodsModel->where([
                    'dateline' => $temp['dateline'],
                    'channel_id' => $temp['channel_id'],
                    'warehouse_id' => $temp['warehouse_id'],
                    'sku_id' => $temp['sku_id']
                ])->find();
                if (!empty($reportGoodsInfo)) {
                    $temp['void_quantity'] = $reportGoodsInfo['void_quantity'] + (isset($temp['void_quantity']) ? $temp['void_quantity'] : 0);
                    $temp['order_quantity'] = $reportGoodsInfo['order_quantity'] + (isset($temp['order_quantity']) ? $temp['order_quantity'] : 0);
                    $temp['order_turnover'] = $reportGoodsInfo['order_turnover'] + (isset($temp['order_turnover']) ? $temp['order_turnover'] : 0);
                    $temp['sale_quantity'] = $reportGoodsInfo['sale_quantity'] + (isset($temp['sale_quantity']) ? $temp['sale_quantity'] : 0);
                    $temp['repeat_quantity'] = $reportGoodsInfo['repeat_quantity'] + (isset($temp['repeat_quantity']) ? $temp['repeat_quantity'] : 0);
                    $temp['repair_quantity'] = $reportGoodsInfo['repair_quantity'] + (isset($temp['repair_quantity']) ? $temp['repair_quantity'] : 0);
                    $temp['total_quantity'] = $reportGoodsInfo['sale_quantity'] + (isset($temp['sale_quantity']) ? $temp['sale_quantity'] : 0);
                    $temp['total_quantity'] = $temp['total_quantity'] + $reportGoodsInfo['repeat_quantity'] + (isset($temp['repeat_quantity']) ? $temp['repeat_quantity'] : 0);
                    $temp['refund_quantity'] = $reportGoodsInfo['refund_quantity'] + (isset($temp['refund_quantity']) ? $temp['refund_quantity'] : 0);
                    $temp['sale_amount'] = $reportGoodsInfo['sale_amount'] + (isset($temp['sale_amount']) ? $temp['sale_amount'] : 0);
                    $temp['repeat_amount'] = $reportGoodsInfo['repeat_amount'] + (isset($temp['repeat_amount']) ? $temp['repeat_amount'] : 0);
                    $temp['refund_amount'] = $reportGoodsInfo['refund_amount'] + (isset($temp['refund_amount']) ? $temp['refund_amount'] : 0);
                    $temp['buyer_quantity'] = $reportGoodsInfo['buyer_quantity'] + (isset($temp['buyer_quantity']) ? $temp['buyer_quantity'] : 0);
                    $temp['sale_amount'] = sprintf("%.4f", $temp['sale_amount']);
                    $temp['repeat_amount'] = sprintf("%.4f", $temp['repeat_amount']);
                    $temp['refund_amount'] = sprintf("%.4f", $temp['refund_amount']);
                    $temp = $this->checkData($temp);
                    $reportGoodsModel->allowField(true)->isUpdate(true)->save($temp, [
                        'dateline' => $reportGoodsInfo['dateline'],
                        'channel_id' => $reportGoodsInfo['channel_id'],
                        'warehouse_id' => $reportGoodsInfo['warehouse_id'],
                        'sku_id' => $reportGoodsInfo['sku_id']
                    ]);
                } else {
                    if (!Cache::store('partition')->getPartition('ReportStatisticByGoods', time())) {
                        Cache::store('partition')->setPartition('ReportStatisticByGoods', time(), null, []);
                    }
                    $temp = $this->checkData($temp);
                    $reportGoodsModel->allowField(true)->isUpdate(false)->save($temp);
                }
                //删除缓存
                 Cache::store('report')->delSaleByGoods($key);
            } catch (Exception $e) {
                continue;
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