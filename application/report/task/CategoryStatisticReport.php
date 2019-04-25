<?php
namespace app\report\task;
use app\common\cache\Cache;
use app\common\model\report\ReportStatisticByCategory;
use app\index\service\AbsTasker;
use think\Exception;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2018/5/11
 * Time: 16:59
 */
class CategoryStatisticReport extends AbsTasker
{
    /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return '分类统计信息写入数据库';
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
        $this->writeInCategory();
    }

    /**
     * 分类统计信息写入数据库
     * @return bool
     * @throws Exception
     */
    private function writeInCategory()
    {
        $categoryData = Cache::store('report')->getSaleByCategory();
        foreach ($categoryData as $k => $v) {
            $reportCategoryModel = new ReportStatisticByCategory();
            try {
                $temp = $v;
                if ($temp['dateline'] < 0) {
                    //删除缓存
                    Cache::store('report')->delSaleByCategory($k);
                    continue;
                }
                //查看是否存在该记录了
                $reportCategoryInfo = $reportCategoryModel->where([
                    'dateline' => $temp['dateline'],
                    'channel_id' => $temp['channel_id'],
                    'category_id' => $temp['category_id']
                ])->find();
                if (!empty($reportCategoryInfo)) {
                    $temp['void_quantity'] = $reportCategoryInfo['void_quantity'] + (isset($temp['void_quantity']) ? $temp['void_quantity'] : 0);
                    $temp['order_quantity'] = $reportCategoryInfo['order_quantity'] + (isset($temp['order_quantity']) ? $temp['order_quantity'] : 0);
                    $temp['order_turnover'] = $reportCategoryInfo['order_turnover'] + (isset($temp['order_turnover']) ? $temp['order_turnover'] : 0);
                    $temp['sale_quantity'] = $reportCategoryInfo['sale_quantity'] + (isset($temp['sale_quantity']) ? $temp['sale_quantity'] : 0);
                    $temp['repeat_quantity'] = $reportCategoryInfo['repeat_quantity'] + (isset($temp['repeat_quantity']) ? $temp['repeat_quantity'] : 0);
                    $temp['repair_quantity'] = $reportCategoryInfo['repair_quantity'] + (isset($temp['repair_quantity']) ? $temp['repair_quantity'] : 0);
                    $temp['total_quantity'] = $reportCategoryInfo['sale_quantity'] + (isset($temp['sale_quantity']) ? $temp['sale_quantity'] : 0);
                    $temp['total_quantity'] = $temp['total_quantity'] + $reportCategoryInfo['repeat_quantity'] + (isset($temp['repeat_quantity']) ? $temp['repeat_quantity'] : 0);
                    $temp['refund_quantity'] = $reportCategoryInfo['refund_quantity'] + (isset($temp['refund_quantity']) ? $temp['refund_quantity'] : 0);
                    $temp['sale_amount'] = $reportCategoryInfo['sale_amount'] + (isset($temp['sale_amount']) ? $temp['sale_amount'] : 0);
                    $temp['repeat_amount'] = $reportCategoryInfo['repeat_amount'] + (isset($temp['repeat_amount']) ? $temp['repeat_amount'] : 0);
                    $temp['refund_amount'] = $reportCategoryInfo['refund_amount'] + (isset($temp['refund_amount']) ? $temp['refund_amount'] : 0);
                    $temp['sale_amount'] = sprintf("%.4f", $temp['sale_amount']);
                    $temp['repeat_amount'] = sprintf("%.4f", $temp['repeat_amount']);
                    $temp['refund_amount'] = sprintf("%.4f", $temp['refund_amount']);
                    $temp = $this->checkData($temp);
                    $reportCategoryModel->allowField(true)->isUpdate(true)->save($temp, [
                        'dateline' => $temp['dateline'],
                        'channel_id' => $temp['channel_id'],
                        'category_id' => $temp['category_id']
                    ]);
                } else {
                    if (!Cache::store('partition')->getPartition('ReportStatisticByCategory', time())) {
                        Cache::store('partition')->setPartition('ReportStatisticByCategory', time(), null, []);
                    }
                    $temp = $this->checkData($temp);
                    $reportCategoryModel->allowField(true)->isUpdate(false)->save($temp);
                }
                //删除缓存
                Cache::store('report')->delSaleByCategory($k);
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