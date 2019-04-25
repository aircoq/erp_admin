<?php

namespace app\report\task;

use app\common\cache\Cache;
use app\common\model\report\ReportStatisticByDeeps;
use app\common\service\ChannelAccountConst;
use app\index\service\AbsTasker;
use think\Exception;
use think\Db;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2018/5/11
 * Time: 17:00
 */
class DeepsStatisticReport extends AbsTasker
{
    /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return '销售业绩统计信息写入数据库';
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
        try {
            $this->writeInDeeps();
        } catch (Exception $e) {

        }
    }

    /**
     * 销售业绩统计信息写入数据库
     * @return bool
     * @throws Exception
     */
    private function writeInDeeps()
    {
        $currencyData = Cache::store('currency')->getCurrency('USD');
        $system_rate = $currencyData['system_rate'];   //转换为人民币了
        $cache = Cache::store('report');
        $tableData = $cache->getSaleTableByDeeps();
        foreach ($tableData as $k => $key) {
            $deepsData = $cache->getSaleByDeeps($key);
            $reportDeepsModel = new ReportStatisticByDeeps();
            $temp = $deepsData;
            try {
                if (!isset($temp['dateline']) || $temp['dateline'] < 0) {
                    //删除缓存
                    Cache::store('report')->delSaleByDeeps($key);
                    continue;
                }
                //查看是否存在该记录了
                $reportDeepsInfo = $reportDeepsModel->field(true)->where([
                    'dateline' => $temp['dateline'],
                    'channel_id' => $temp['channel_id'],
                    'warehouse_id' => $temp['warehouse_id'],
                    'account_id' => $temp['account_id']
                ])->find();
                if (!empty($reportDeepsInfo)) {
                    $new['shipping_fee'] = $reportDeepsInfo['shipping_fee'] + ($temp['shipping_fee'] ?? 0);
                    $new['paypal_fee'] = $reportDeepsInfo['paypal_fee'] + ($temp['paypal_fee'] ?? 0);
                    $new['channel_cost'] = $reportDeepsInfo['channel_cost'] + ($temp['channel_cost'] ?? 0);
                    $new['sale_amount'] = $reportDeepsInfo['sale_amount'] + ($temp['sale_amount'] ?? 0);
                    $new['package_fee'] = $reportDeepsInfo['package_fee'] + ($temp['package_fee'] ?? 0);
                    $new['first_fee'] = $reportDeepsInfo['first_fee'] + ($temp['first_fee'] ?? 0);
                    $new['tariff'] = $reportDeepsInfo['tariff'] + ($temp['tariff'] ?? 0);
                    $new['refund_amount'] = $reportDeepsInfo['refund_amount'] + ($temp['refund_amount'] ?? 0);
                    $new['profits'] = $reportDeepsInfo['profits'] + ($temp['profits'] ?? 0);
                    $new['delivery_quantity'] = $reportDeepsInfo['delivery_quantity'] + ($temp['delivery_quantity'] ?? 0);
                    $new['refund_amount'] = sprintf("%.4f", $new['refund_amount']);
                    $new['cost'] = $reportDeepsInfo['cost'] + ($temp['cost'] ?? 0);
                    $new['profits'] = sprintf("%.4f", $new['profits']);
                    $new = $this->checkData($new);
                    if (!in_array($temp['warehouse_id'], [2, 6])) {
                        Cache::handler()->hSet('hash:statistic:deeps:save:' . date('Y-m-d', time()), date('Ymd H:i:s'),
                            json_encode(['sale_amount' => $temp['sale_amount'], 'warehouse_id' => $temp['warehouse_id'], 'new_sale_amount' => $new['sale_amount'], 'seller_id' => $temp['user_id']], JSON_UNESCAPED_UNICODE));
                    }
                    $reportDeepsModel->where([
                        'dateline' => $temp['dateline'],
                        'channel_id' => $temp['channel_id'],
                        'warehouse_id' => $temp['warehouse_id'],
                        'account_id' => $temp['account_id']
                    ])->update($new);
                } else {
                    if (!Cache::store('partition')->getPartition('ReportStatisticByDeeps', time())) {
                        Cache::store('partition')->setPartition('ReportStatisticByDeeps', time(), null, []);
                    }
                    $temp['rate'] = $system_rate;
                    $temp = $this->checkData($temp);
                    if (!in_array($temp['warehouse_id'], [2, 6])) {
                        Cache::handler()->hSet('hash:statistic:deeps:save:' . date('Y-m-d', time()), date('Ymd H:i:s'),
                            json_encode(['sale_amount' => $temp['sale_amount'], 'warehouse_id' => $temp['warehouse_id'], 'seller_id' => $temp['user_id']], JSON_UNESCAPED_UNICODE));
                    }
                    $reportDeepsModel->allowField(true)->isUpdate(false)->save($temp);
                }
                //删除缓存
                Cache::store('report')->delSaleByDeeps($key);
            } catch (Exception $e) {
				Cache::store('report')->delSaleByDeeps($key);
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


    /**
     * 检查数据
     * @param array $data
     * @return array
     */
    private function checkRollbackData(array $data)
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
                $newData[$k] = $v ?? 0;
            }
        }
        return $newData;
    }

    /**
     * 销售业绩统计信息写入数据库
     * @return bool
     * @throws Exception
     */
    public function rollbackDeeps()
    {
        set_time_limit(0);
        $reportDeepsModel = new ReportStatisticByDeeps();
        $lists = $reportDeepsModel->field('*')->where('dateline > 1533744000 and dateline<1541433600')->where('shipping_fee', 'eq', 0)->select();
        foreach ($lists as $item) {
            if (!$item['dateline']) {
                continue;
            }
            /*$where['p.channel_account_id'] = $item['account_id'];
            $where['p.channel_id'] = $item['channel_id'];
            $where['p.warehouse_id'] = $item['warehouse_id'];
            $where['p.shipping_time'] = [['>=', $item['dateline']],['<', $item['dateline']+86400]];*/

            $end_dateline = $item['dateline'] + 86400;
            $fields = 'sum(d.sku_price*d.sku_quantity) as sale_amount, sum(p.shipping_fee) as shipping_fee, sum(p.package_fee) as package_fee';
            $fields .= ', sum(if(o.delivery_type=1,o.cost,0)) as cost, sum(o.first_fee) as first_fee, sum(o.tariff) as tariff, sum(o.pay_fee*o.rate) as pay_fee';
            $fields .= ', sum(if(o.delivery_type=1,o.delivery_type,0)) as delivery_quantity, sum(o.paypal_fee*o.rate) as paypal_fee, sum(channel_cost) as channel_cost';
            $sq = "select {$fields}  from order_detail d
            left join order_package p on p.id = d.package_id
            left join `order`o on d.order_id = o.id
            where p.channel_account_id = {$item['account_id']} AND p.channel_id = {$item['channel_id']} AND p.warehouse_id = {$item['warehouse_id']} AND ( p.shipping_time >= {$item['dateline']} AND p.shipping_time < {$end_dateline})";
            $result = Db::query($sq);
            if (empty($result) && empty($result[0])) {
                continue;
            }
            $data = $result[0];
            $payPal_fee = 0;
            $channel_cost = 0;
            if (!empty(floatval($data['pay_fee']))) {
                $payPal_fee = ($data['sale_amount'] / $data['pay_fee']) * $data['paypal_fee'];
                $channel_cost = ($data['sale_amount'] / $data['pay_fee']) * $data['channel_cost'];
            }
            $data['paypal_fee'] = $payPal_fee;
            $data['channel_cost'] = $channel_cost;
            $data['p_fee'] = 0;
            if ($item['channel_id'] == \app\common\service\ChannelAccountConst::channel_amazon) {
                $data['p_fee'] = $p_fee = sprintf("%.4f", ($data['sale_amount'] - $channel_cost) * 0.006);
            }
            $data['profits'] = $data['sale_amount'] - $data['shipping_fee'] - $data['package_fee'] - $data['first_fee'] - $data['tariff'] - $payPal_fee - $channel_cost;

            $data = $this->checkRollbackData($data);
            $update_where['dateline'] = $item['dateline'];
            $update_where['channel_id'] = $item['channel_id'];
            $update_where['warehouse_id'] = $item['warehouse_id'];
            $update_where['account_id'] = $item['account_id'];
            (new ReportStatisticByDeeps())->allowField(true)->save($data, $update_where);
        }
    }

}