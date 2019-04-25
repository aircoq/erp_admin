<?php
namespace app\report\task;

use app\common\cache\Cache;
use app\common\model\report\ReportStatisticByOrder;
use app\index\service\AbsTasker;
use app\report\service\StatisticOrder;
use think\Exception;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2018/5/11
 * Time: 17:01
 */
class OrderStatisticReport extends AbsTasker
{
    /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return '订单统计信息凌晨写入数据库';
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
            $this->writeInOrder();
//            $data['begin_time'] = date('Y-m-d', strtotime('-1 day'));
//            (new StatisticOrder())->resetReport(0, $data['begin_time'], $data['begin_time']);
//            Cache::store('report')->delSaleByOrder();
        }catch (Exception $e){

        }
    }

    /**
     * 订单统计信息写入数据库
     * @return bool
     * @throws Exception
     */
    public function writeInOrder()
    {
        $currencyData = Cache::store('currency')->getCurrency('USD');
        $system_rate = $currencyData['system_rate'];   //转换为人民币了
        $cache = Cache::store('report');
        $tableData = $cache->getSaleTableByOrder();
        foreach ($tableData as $k => $key) {
            $orderData = $cache->getSaleByOrder($key);
            $reportOrderModel = new ReportStatisticByOrder();
            try {
                $temp = $orderData;
                if (!isset($temp['dateline']) || $temp['dateline'] < 0) {
                    //删除缓存
                    Cache::store('report')->delSaleByOrder($key);
                    continue;
                }
                //查看是否存在该记录了
                $reportOrderInfo = $reportOrderModel->where([
                    'dateline' => $temp['dateline'],
                    'channel_id' => $temp['channel_id'],
                    'account_id' => $temp['account_id']
                ])->find();
                if (!empty($reportOrderInfo)) {
                    if(isset($temp['order_quantity'])){
                        $new['order_quantity'] = ['exp', 'order_quantity+' . $temp['order_quantity']];
                    }
                    if(isset($temp['pay_amount'])){
                        $new['pay_amount'] = ['exp', 'pay_amount+' . $temp['pay_amount']];
                    }
                    if(isset($temp['order_unpaid_quantity'])){
                        $new['order_unpaid_quantity'] = ['exp', 'order_unpaid_quantity+' . $temp['order_unpaid_quantity']];
                    }
                   // $new = $this->checkData($new);
                    $reportOrderModel->where([
                        'dateline' => $temp['dateline'],
                        'channel_id' => $temp['channel_id'],
                        'account_id' => $temp['account_id']
                    ])->update($new);
                    //删除缓存
                    Cache::store('report')->delSaleByOrder($key);
                } else {
                    $temp['rate'] = $system_rate;
                    $temp = $this->checkData($temp);
                    if (!Cache::store('partition')->getPartition('ReportStatisticByOrder', time())) {
                        Cache::store('partition')->setPartition('ReportStatisticByOrder', time(), null, []);
                    }
                    (new ReportStatisticByOrder())->allowField(true)->isUpdate(false)->save($temp);
                    Cache::store('report')->delSaleByOrder($key);
                }
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