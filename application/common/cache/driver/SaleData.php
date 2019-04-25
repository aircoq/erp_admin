<?php

namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\service\Report as reportService;
use app\report\service\StatisticGoods;
use app\common\model\FbaOrderDetail;
use app\common\service\ChannelAccountConst;
use app\warehouse\service\Warehouse;

/**
 * Created by PhpStorm.
 * User: Rondaful
 * Date: 2017/05/04
 * Time: 18:52
 */
class SaleData extends Cache
{

    private $fifteen_time;
    private $seven_time;
    private $five_time;
    private $three_time;
    private $one_months_time;
    private $two_months_time;
    private $one_months_name;
    private $two_months_name;

    public function __construct()
    {
        $this->fifteen_time = strtotime(date('Y-m-d', strtotime('-15 day')));
        $this->seven_time = strtotime(date('Y-m-d', strtotime('-7 day')));
        $this->five_time = strtotime(date('Y-m-d', strtotime('-5 day')));
        $this->three_time = strtotime(date('Y-m-d', strtotime('-3 day')));
        $timeRange = $this->getTimeRange(1);
        $this->one_months_name = $timeRange[0];
        $this->one_months_time = $timeRange[1];
        $timeRange = $this->getTimeRange(2);
        $this->two_months_name = $timeRange[0];
        $this->two_months_time = $timeRange[1];
        parent::__construct();
    }

    /**
     * 数据
     * @param int $sku_id
     * @param int $warehouse_id
     * @return array
     */
    private function statisticData($sku_id, $warehouse_id = 0, $channel_id = 1)
    {
        $reportList = reportService::statisticGoods($sku_id, $warehouse_id, $channel_id);
        $data = $this->getDayData();
        $allDate = array_keys($data);
        $current = end($allDate);
        array_pop($data);//去掉当天
        $list['x_axis'] = [];
        $list['data'] = [];
        $list['thirdty_qty'] = 0; // 30天销量
        $list['fifteen_qty'] = 0; // 15天销量
        $list['seven_qty'] = 0; // 7天销量
        $list['five_qty'] = 0; // 5天销量
        $list['three_qty'] = 0; // 3天销量
        $list['one_months_qty'] = reportService::getSaleSum($sku_id, $this->one_months_time, $warehouse_id, $channel_id);//上个月销量
        $list['one_months_name'] = $this->one_months_name;
        $list['two_months_qty'] = reportService::getSaleSum($sku_id, $this->two_months_time, $warehouse_id, $channel_id);//上上个月销量
        $list['two_months_name'] = $this->two_months_name;

        foreach ($reportList as $report) {
            $day = date('n/j', $report['dateline']);
            if($current == $day){
                continue;//跳过当天
            }
            // 3天的销量
            if ($this->three_time <= $report['dateline']) {
                $list['three_qty'] += $report['order_quantity'];
            }
            //5天的销量
            if ($this->five_time <= $report['dateline']) {
                $list['five_qty'] += $report['order_quantity'];
            }
            //7天的销量
            if ($this->seven_time <= $report['dateline']) {
                $list['seven_qty'] += $report['order_quantity'];
            }
            //15天的销量
            if ($this->fifteen_time <= $report['dateline']) {
                $list['fifteen_qty'] += $report['order_quantity'];
            }
            //30天的销量
            $list['thirdty_qty'] += $report['order_quantity'];
            // 单日的销量

            $data[$day] += $report['order_quantity'];
        }
        //$data[date('n/j')]= $this->getCurrentSales($sku_id, $warehouse_id, $channel_id);

        $list['data'] = array_values($data);
        $list['x_axis'] = array_keys($data);
        $list['expire'] = strtotime(date('Y-m-d', strtotime(' 1 day')));
        $list['channel_id'] = $channel_id;

        return $list;
    }

    /**
     * @desc 获取fba日均销量(查表)
     * @param int $warehouse_id
     * @param array|int $sku_id
     * @return array
     */
    private function getFbaSaleData($warehouse_id, $sku_id)
    {
        $data = $this->getDayData();
        $allDate = array_keys($data);
        $current = end($allDate);
        array_pop($data);//去掉当天
        //初始化
        $list['x_axis'] = [];
        $list['data'] = [];
        $list['thirdty_qty'] = 0; // 30天销量
        $list['fifteen_qty'] = 0; // 15天销量
        $list['seven_qty'] = 0; // 7天销量
        $list['five_qty'] = 0; // 5天销量
        $list['three_qty'] = 0; // 3天销量
        $timeRange = $this->getTimeRange(1);
        $timeRange2 = $this->getTimeRange(2);
        $month_where = [
            'order_time' => ['between',  $timeRange[1]],
            'sku_id' => ['=', $sku_id],
            'warehouse_id' =>  ['=', $warehouse_id]
        ];
        //上个月销量
        $list['one_months_qty'] = (new FbaOrderDetail())->alias('d')
            ->join('fba_order o', 'd.fba_order_id = o.id')
            ->where($month_where)
            ->value('sum(sku_quantity)');
        $list['one_months_qty'] =  $list['one_months_qty'] ?? 0;
        $list['one_months_name'] = $timeRange[0];
        //上上个月销量
        $month_where['order_time'] =  ['between',  $timeRange2[1]];
        $list['two_months_qty'] = (new FbaOrderDetail())->alias('d')
            ->join('fba_order o', 'd.fba_order_id = o.id')
            ->where($month_where)
            ->value('sum(sku_quantity)');
        $list['two_months_name'] = $timeRange2[0];
        $list['two_months_qty'] =  $list['two_months_qty'] ?? 0;
        $where = [
            'o.warehouse_id' => $warehouse_id,
            'd.sku_id' => ['in', $sku_id],
            'o.order_time' => ['>', strtotime('-31 day')]
        ];
        $lists = (new FbaOrderDetail())->alias('d')
            ->join('fba_order o', 'd.fba_order_id = o.id')
            ->where($where)
            ->group('d.sku_id')
            ->column('sku_quantity, order_time', 'sku_id');
        $this->fifteen_time = strtotime(date("Y-m-d", strtotime('-15 day')));
        $this->seven_time = strtotime(date("Y-m-d", strtotime('-7 day')));
        $this->five_time = strtotime(date("Y-m-d", strtotime('-5 day')));
        $this->three_time = strtotime(date("Y-m-d", strtotime('-3 day')));
        foreach ($lists as &$item) {
            $day = date('n/j', $item['order_time']);
            if($current == $day){
                continue;//跳过当天
            }
            // 3天的销量
            if ($this->three_time <= $item['order_time']) {
                $list['three_qty'] += $item['sku_quantity'];
            }
            //5天的销量
            if ($this->five_time <= $item['order_time']) {
                $list['five_qty'] += $item['sku_quantity'];
            }
            //7天的销量
            if ($this->seven_time <= $item['order_time']) {
                $list['seven_qty'] += $item['sku_quantity'];
            }
            //15天的销量
            if ($this->fifteen_time <= $item['order_time']) {
                $list['fifteen_qty'] += $item['sku_quantity'];
            }
            //30天的销量
            $list['thirdty_qty'] += $item['sku_quantity'];
            if (isset($data[$day])) {
                $data[$day] += $item['sku_quantity'];
            } else {
                $data[$day] = $item['sku_quantity'];
            }
        }
        $list['data'] = array_values($data);
        $list['x_axis'] = array_keys($data);
        $list['expire'] = strtotime(date('Y-m-d', strtotime(' 1 day')));
        $list['channel_id'] = ChannelAccountConst::channel_amazon;
        return $list;
    }

    /**
     * 获取指定条件SKU的销量
     * @param int $sku_id sku ID
     * @param int $warehouse_id 仓库ID
     * @param int  $channel_id 渠道ID
     * @return int $num 指定SKU当天的销售数量
     * @author Jimmy
     * @date 2017-10-10 10:34:11
     */
    private function getCurrentSales($sku_id, $warehouse_id, $channel_id)
    {
        $statisticGoods = new StatisticGoods();
        $res = $statisticGoods->getCurrentSales($sku_id, $warehouse_id, $channel_id);
        $num = 0;
        foreach ($res as $val) {
            $num += $val['sale_quantity'];
        }
        return $num;
    }

    /**
     * 获取30天日期
     * @return array
     */
    private function getDayData()
    {
        if($this->redis->exists('cache:30days')) {
            return json_decode($this->redis->get('cache:30days'), true);
        }
        $list = [];
        for($i = 30; $i >= 0 ; $i--) {
            $day = date('n/j', strtotime((-$i).' day'));
            $list[$day] = 0;
        }
        $this->redis->set('cache:30days', json_encode($list));
        $time = strtotime(date('Y-m-d', strtotime(' 1 day')));
        if (!$this->redis->expireAt('cache:30days', $time)) {
            $this->redis->del('cache:30days');
        }
        return $list;
    }

    /**
     * 获取日均销量
     * @param $sku_id
     * @param $warehouse_id
     * @return float|int
     */
    public function getDailySale($sku_id, $warehouse_id)
    {
        $cache = 'cache:1daysSale'. $warehouse_id;
        $key = $sku_id;
        if ($this->redis->exists($cache) && $this->redis->hExists($cache,$key)) {
            return $this->redis->hGet($cache,$key);
        }
        $list = Cache::store('saleData')->getSaleData($sku_id, $warehouse_id, 0);
        //计算日均销量
        $daily_sale = $list['seven_qty'] / 7 * 0.6 + $list['fifteen_qty'] / 15 * 0.25 + $list['thirdty_qty'] / 30 * 0.15;
        $this->redis->hSet($cache,$key,$daily_sale);
        $time = strtotime(date('Y-m-d',strtotime('1 day')));
        if (!$this->redis->expireAt($cache, $time)){
            $this->redis->del($cache);
        }
        return $daily_sale;
    }

    /**
     * 获取销售数据
     * @param int $sku_id
     * @param int $warehouse_id
	 * @param int $channel_id 
     * @return array
     */
    public function getSaleData($sku_id, $warehouse_id = 0,$channel_id = 1)
    {	

      //  $key = $sku_id.':'.$warehouse_id.':'.$channel_id;
		//echo $sku_id, $warehouse_id,$channel_id;exit;
      //  do {
           /*
            if($this->redis->hexists('hash:OrderQuantityData', $key)) {
                $result = json_decode($this->redis->hget('hash:OrderQuantityData', $key), true);
                print_r($result);exit;
                // 检测过期时间
                if ($result['expire'] > time()) {//为过期
                    break;
                }
            }
           */
            //fba查表
            if ((new Warehouse())->isAppointType($warehouse_id, Warehouse::TYPE_FBA)) {
                if(is_array($sku_id)){
                    $result = $this->getFbaSaleDataManySku($warehouse_id, $sku_id);
                }else{
                    $result = $this->getFbaSaleData($warehouse_id, $sku_id);
                }
            } else {
                if(is_array($sku_id)){
                    $result = $this->statisticDataManySku($sku_id, $warehouse_id, $channel_id);
                }else{
                    $result = $this->statisticData($sku_id, $warehouse_id, $channel_id);
                }
            }

          /*
            if ($result) {
                $this->redis->hset('hash:OrderQuantityData', $key, json_encode($result));
            }
           */
			
      //  } while(false);

        return $result;
    }

    private function getTimeRange($offsetMonths)
    {
        $monthsArr = range(1,12);
        $year = date('Y');
        $key = array_search(date('m'), $monthsArr)-$offsetMonths;
        if($key < 0){
            $key = 12+$key;
            $year -= 1;
        }
        $month = $monthsArr[$key];
        $day = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $begin = mktime(0, 0, 0, $month, 1, $year);
        $end = mktime(23, 59, 59, $month, $day, $year);
        return [$month, [$begin, $end]];
    }

    private function statisticDataManySku(array $sku_id, int $warehouse_id = 0, int $channel_id = 1) :array
    {
        $reportList = reportService::statisticGoods($sku_id, $warehouse_id, $channel_id);
        $data = $this->getDayData();
        $allDate = array_keys($data);
        $current = end($allDate);
        array_pop($data);//去掉当天
        $oneMonthQty = reportService::getSaleSum($sku_id, $this->one_months_time, $warehouse_id, $channel_id);//上个月销量
        $twoMonthQty = reportService::getSaleSum($sku_id, $this->two_months_time, $warehouse_id, $channel_id);//上上个月销量
        $metadata = $this->getMetadata($data, $channel_id);
        $result = [];
        foreach($sku_id as $v){
            $metadata['one_months_qty'] = $oneMonthQty[$v] ?? 0;
            $metadata['two_months_qty'] = $twoMonthQty[$v] ?? 0;
            $result[$v] = $metadata;
        }
        foreach ($reportList as $report) {
            $day = date('n/j', $report['dateline']);
            if($current == $day){
                continue;//跳过当天
            }
            // 3天的销量
            if ($this->three_time <= $report['dateline']) {
                $result[$report['sku_id']]['three_qty'] += $report['order_quantity'];
            }
            //5天的销量
            if ($this->five_time <= $report['dateline']) {
                $result[$report['sku_id']]['five_qty'] += $report['order_quantity'];
            }
            //7天的销量
            if ($this->seven_time <= $report['dateline']) {
                $result[$report['sku_id']]['seven_qty'] += $report['order_quantity'];
            }
            //15天的销量
            if ($this->fifteen_time <= $report['dateline']) {
                $result[$report['sku_id']]['fifteen_qty'] += $report['order_quantity'];
            }
            //30天的销量
            $result[$report['sku_id']]['thirdty_qty'] += $report['order_quantity'];
            // 单日的销量
            $result[$report['sku_id']]['data'][$day] += $report['order_quantity'];
        }
        //$data[date('n/j')]= $this->getCurrentSales($sku_id, $warehouse_id, $channel_id);
        foreach($result as &$val){
            $val['data'] = array_values($val['data']);
        }
        return $result;
    }

    private function getFbaSaleDataManySku(int $warehouse_id, array $sku_id) :array
    {
        $data = $this->getDayData();
        $allDate = array_keys($data);
        $current = end($allDate);
        array_pop($data);//去掉当天
        $metadata = $this->getMetadata($data, ChannelAccountConst::channel_amazon);
        $month_where = [
            'order_time' => ['between',  $this->one_months_time],
            'sku_id' => ['in', $sku_id],
            'warehouse_id' =>  ['=', $warehouse_id]
        ];
        //上个月销量
        $oneMonthQty = (new FbaOrderDetail())->alias('d')
            ->join('fba_order o', 'd.fba_order_id = o.id')
            ->where($month_where)
            ->group('sku_id')->column('sku_id,sum(sku_quantity)');
        //上上个月销量
        $month_where['order_time'] =  ['between',  $this->two_months_time];
        $twoMonthQty = (new FbaOrderDetail())->alias('d')
            ->join('fba_order o', 'd.fba_order_id = o.id')
            ->where($month_where)
            ->group('sku_id')->column('sku_id,sum(sku_quantity)');
        $result = [];
        foreach($sku_id as $v){
            $metadata['one_months_qty'] = $oneMonthQty[$v] ?? 0;
            $metadata['two_months_qty'] = $twoMonthQty[$v] ?? 0;
            $result[$v] = $metadata;
        }
        $where = [
            'o.warehouse_id' => $warehouse_id,
            'd.sku_id' => ['in', $sku_id],
            'o.order_time' => ['>', strtotime('-31 day')]
        ];
        $lists = (new FbaOrderDetail())->alias('d')
            ->join('fba_order o', 'd.fba_order_id = o.id')
            ->where($where)
            ->group('d.sku_id')
            ->field('sku_quantity,order_time,sku_id')->select();
        foreach ($lists as $item) {
            $day = date('n/j', $item['order_time']);
            if($current == $day){
                continue;//跳过当天
            }
            // 3天的销量
            if ($this->three_time <= $item['order_time']) {
                $result[$item['sku_id']]['three_qty'] += $item['sku_quantity'];
            }
            //5天的销量
            if ($this->five_time <= $item['order_time']) {
                $result[$item['sku_id']]['five_qty'] += $item['sku_quantity'];
            }
            //7天的销量
            if ($this->seven_time <= $item['order_time']) {
                $result[$item['sku_id']]['seven_qty'] += $item['sku_quantity'];
            }
            //15天的销量
            if ($this->fifteen_time <= $item['order_time']) {
                $result[$item['sku_id']]['fifteen_qty'] += $item['sku_quantity'];
            }
            //30天的销量
            $result[$item['sku_id']]['thirdty_qty'] += $item['sku_quantity'];
            if (isset($data[$day])) {
                $result[$item['sku_id']]['data'][$day] += $item['sku_quantity'];
            } else {
                $result[$item['sku_id']]['data'][$day] = $item['sku_quantity'];
            }
        }
        foreach($result as &$val){
            $val['data'] = array_values($val['data']);
        }
        return $result;
    }

    private function getMetadata(array $dayData, $channel_id)
    {
        return [
            'x_axis' => array_keys($dayData),
            'data' => $dayData,
            'thirdty_qty' => 0,
            'fifteen_qty' => 0,
            'seven_qty' => 0,
            'five_qty' => 0,
            'three_qty' => 0,
            'one_months_qty' => 0,
            'one_months_name' => $this->one_months_name,
            'two_months_qty' => 0,
            'two_months_name' => $this->two_months_name,
            'expire' => strtotime(date('Y-m-d', strtotime(' 1 day'))),
            'channel_id' => $channel_id
        ];
    }
}
