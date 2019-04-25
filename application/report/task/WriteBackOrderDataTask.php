<?php

namespace app\report\task;

use app\common\model\amazon\AmazonAccountHealth;
use app\common\model\amazon\AmazonAccountHealthList;
use app\common\model\report\ReportStatisticByDeeps;
use app\common\service\ChannelAccountConst;
use app\index\service\AbsTasker;
use app\common\model\ReportShortageByDate;
use app\report\service\AccountOperationAnalysisService;
use app\report\service\WarehousePackageService;
use think\Exception;
use app\common\exception\TaskException;

/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2019/4/24
 * Time: 9:36
 */
class WriteBackOrderDataTask extends AbsTasker
{
    public function getCreator()
    {
        return 'libaimin';
    }

    public function getDesc()
    {
        return '回写账号对应的销售额（USD）与 订单量';
    }

    public function getName()
    {
        return '回写账号对应的销售额（USD）与 订单量';
    }

    public function getParamRule()
    {
        return [
            'type|处理类型:' => 'require|select:销售额与订单量、账号订单缺陷率:0,销售额与订单量:1,账号订单缺陷率 :2,',
            'start_time|开始时间:' => ''
        ];
    }

    public function execute()
    {
        set_time_limit(0);

        $type = $this->getData('type');
        if (empty($type)) {
            $type = 0;
        }
        $startTime = $this->getData('start_time');
        if (empty($type)) {
            $startTime = strtotime(date('Y-m-d'));
        }
        try {
            switch ($type){
                case 0:
                    $this->writeBackOrderFee($startTime);
                    $this->writeBackOrderRate($startTime);
                    break;
                case 1: // 销售额与订单量
                    $this->writeBackOrderFee($startTime);
                    break;
                case 2: // 账号订单缺陷率
                    $this->writeBackOrderRate($startTime);
                    break;
            }
        } catch (Exception $ex) {
            throw new TaskException($ex->getMessage());
        }
        return true;
    }

    /**
     * 回写销售额与订单量
     * @param $startTime
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function writeBackOrderFee($startTime)
    {
        $where = [
            'dateline' => $startTime,
        ];
        $field = 'channel_id,account_id,sum(sale_amount / rate) as amount,sum(delivery_quantity) as qty';
        $list = (new ReportStatisticByDeeps())->where($where)->field($field)->group('channel_id,account_id')->select();
        foreach ($list as $v){
            $data = [
                'sale_amount' => $v['amount'],
                'order_quantity' => $v['qty'],
            ];
            (new AccountOperationAnalysisService())->writeSaleAmount($v['channel_id'], $v['account_id'], $data, $startTime);
        }
        return true;
    }


    /**
     * 账号订单缺陷率
     * @param $startTime
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function writeBackOrderRate($startTime)
    {
        //目前只有亚马逊的才有数据
        $where = [
            'create_time' => ['between' ,[$startTime - 86400,$startTime -1]],
        ];
        $field = 'account_id,order_defect_rate_buyer';
        $list = (new AmazonAccountHealth())->where($where)->field($field)->select();
        foreach ($list as $v){
            $data = [
                'odr' => $v['order_defect_rate_buyer'],
            ];
            (new AccountOperationAnalysisService())->writeSaleAmount(ChannelAccountConst::channel_amazon, $v['account_id'], $data, $startTime);
        }




        return true;
    }
}