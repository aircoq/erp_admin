<?php

namespace app\index\service;

use app\common\cache\Cache;
use app\common\exception\JsonErrorException;
use app\common\filter\ChannelAccountsFilter;
use app\common\filter\ChannelsFilter;
use app\common\interfaces\DashboradPublish;
use app\common\model\FbaOrder;
use app\common\model\report\ReportStatisticByDeeps;
use app\common\model\report\ReportStatisticByOrder;
use app\common\service\Channel;
use app\common\service\ChannelAccountConst;
use app\common\service\Filter;
use app\common\service\OrderType;
use app\order\service\OrderPackage;
use app\report\service\StatisticOrder;
use app\warehouse\service\WarehouseGoods;
use Carbon\Carbon;
use erp\AbsServer;
use think\Db;

/**
 * Created by PhpStorm.
 * User: Phill
 * Date: 18-6-11
 * Time: 下去15:38
 */
class Dashboard extends AbsServer
{
    public function nearby15_old($channel_id, $beginDay = '',$day = 15)
    {
        if (!$beginDay) {
            $now = new \DateTime();
            $beginDay = $now->format('Y-m-d');
            $beginDay = strtotime("$beginDay 0:0:0");
            $now->sub(new \DateInterval('P15D'));
        }

        $channelServer = new Channel();
        $channels = $channelServer->getChannels();
        $days = $this->interval($beginDay, $day);
        $channelData = [];
        $channelNames = [];
        $reportStatisticByOrderModel = new ReportStatisticByOrder();
        $statisticOrderService = new StatisticOrder();
        $currencyData = Cache::store('currency')->getCurrency('USD');
        $system_rate = $currencyData['system_rate'];   //转换为人民币了

        //平台过滤器
        $filterChannels = [];
        $targetFillter = new Filter(ChannelsFilter::class, true);
        if ($targetFillter->filterIsEffective()) {
            $filterChannels = $targetFillter->getFilterContent();
        }

        //平台账号过滤器
        $filterAccounts = [];
        $targetFillter = new Filter(ChannelAccountsFilter::class, true);
        if ($targetFillter->filterIsEffective()) {
            $filterAccount = $targetFillter->getFilterContent();
            foreach ($filterAccount as $v) {
                $filterAccounts[] = $v % OrderType::ChannelVirtual;
            }
        }


        foreach ($channels as $channel) {

            if ($filterChannels && !in_array($channel->id, $filterChannels)) {
                continue;
            }
            if (!empty($channel_id) && $channel->id != $channel_id) {
                continue;
            }
            $quantities = [];
            $payAmount = [];
            $cacheQuantity = $statisticOrderService->getCacheOrder($channel->id, $filterAccounts);
            $rsoWhere = [
                'channel_id' => $channel->id,
            ];
            if ($filterAccounts) {
                $rsoWhere['account_id'] = ['in', $filterAccounts];
            }
            foreach ($days as $day) {
                $rsoWhere['dateline'] = $day;
                $data = $reportStatisticByOrderModel->field('sum(order_quantity) as order_quantity,rate,sum(pay_amount) as pay_amount')->where($rsoWhere)->find();
                $channelQuantity = isset($cacheQuantity[$day]['order_quantity']) ? $cacheQuantity[$day]['order_quantity'] : 0;
                $quantities[] = !empty($data['order_quantity']) && isset($data['order_quantity']) ? intval($data['order_quantity']) + $channelQuantity : 0 + $channelQuantity;
                $pay_amount = 0;
                if (isset($cacheQuantity[$day]['pay_amount'])) {
                    $pay_amount = floor($cacheQuantity[$day]['pay_amount'] / $system_rate);
                }
                $payAmount[] = !empty($data['pay_amount']) && !empty($data['rate']) && isset($data['pay_amount']) ? floor($data['pay_amount'] / $data['rate']) + $pay_amount : 0 + $pay_amount;
            }
            $channelNames[] = $channel->title;
            $channelData[] = [
                'quantities' => $quantities,
                'amount' => $payAmount,
                'channel' => $channel->title,
                'channel_id' => $channel->id
            ];
        }
        return ['days' => $days, 'channels' => $channelNames, 'data' => $channelData];
    }

    /**
     * 新方法首页数据
     * @param $channel_id
     * @param string $beginDay
     * @param int $day
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function nearby15($channel_id, $beginDay = '',$day = 15)
    {
        if (!$beginDay) {
            $now = new \DateTime();
            $beginDay = $now->format('Y-m-d');
            $beginDay = strtotime("$beginDay 0:0:0");
            $now->sub(new \DateInterval('P15D'));
        }

        $channelServer = new Channel();
        $channels = $channelServer->getChannels();
        $days = $this->interval($beginDay, $day);
        $dayMax = $day - 1;
        $channelData = [];
        $channelNames = [];
        $reportStatisticByOrderModel = new ReportStatisticByOrder();
        $statisticOrderService = new StatisticOrder();
        $currencyData = Cache::store('currency')->getCurrency('USD');
        $system_rate = $currencyData['system_rate'];   //转换为人民币了

        //平台过滤器
        $filterChannels = [];
        $targetFillter = new Filter(ChannelsFilter::class, true);
        if ($targetFillter->filterIsEffective()) {
            $filterChannels = $targetFillter->getFilterContent();
        }

        //平台账号过滤器
        $filterAccounts = [];
        $targetFillter = new Filter(ChannelAccountsFilter::class, true);
        if ($targetFillter->filterIsEffective()) {
            $filterAccount = $targetFillter->getFilterContent();
            foreach ($filterAccount as $v) {
                $filterAccounts[] = $v % OrderType::ChannelVirtual;
            }
        }

        //1.初始化数据
        $quantities = [];
        $payAmount = [];
        $channelAll = [];
        foreach ($days as $v) {
            $quantities[$v] = 0;
            $payAmount[$v] = 0;
        }
        foreach ($channels as $channel) {
            if ($filterChannels && !in_array($channel->id, $filterChannels)) {
                continue;
            }
            if (!empty($channel_id) && $channel->id != $channel_id) {
                continue;
            }
            $channelAll[] = $channel->id;
            $channelNames[] = $channel->title;
            $channelData[$channel->id] = [
                'quantities' => $quantities,
                'amount' => $payAmount,
                'channel' => $channel->title,
                'channel_id' => $channel->id
            ];
        }
        //2.查询数据，并组装数据
        $rsoWhere = [
            'dateline' => ['between', [$days[0], ($days[$dayMax] + 86399)]],
            'channel_id' => ['in', $channelAll],
        ];
        if ($filterAccounts) {
            $rsoWhere['account_id'] = ['in', $filterAccounts];
        }
        $field = 'sum(order_quantity) as quantity,sum(pay_amount / rate) as amount,channel_id,dateline';
        $list = $reportStatisticByOrderModel->field($field)->where($rsoWhere)->group('channel_id,dateline')->select();
        foreach ($list as $v){
            $this->dayToZero($day);
            $channelData[$v['channel_id']]['quantities'][$day] += $v['quantity'];
            $channelData[$v['channel_id']]['amount'][$day] += $v['amount'];
        }
        unset($list);
        //2.1读缓存数据
        $cacheQuantity = $statisticOrderService->getCacheOrderByChannels($channelAll, $filterAccounts);
        foreach ($cacheQuantity as $channel_id => $v){
            foreach ($v as $day => $v1){
                $this->dayToZero($day);
                if($day < $days[0] || $day > $days[$dayMax]){
                    continue;
                }
                $channelData[$channel_id]['quantities'][$day] += $v1['order_quantity'];
                $channelData[$channel_id]['amount'][$day] += ($v1['pay_amount'] / $system_rate) ;
            }
        }
        unset($cacheQuantity);
        //3.格式化数据
        $reData = [];
        foreach ($channelData as $v){
            $quantities = [];
            $amounts = [];
            foreach ($v['quantities'] as $v1) {
                $quantities[] = $v1;
            }
            foreach ($v['amount'] as $v1) {
                $amounts[] = floor($v1);
            }
            $reData[] = [
                'quantities' => $quantities,
                'amount' => $amounts,
                'channel' => $v['channel'],
                'channel_id' => $v['channel_id']
            ];
        }
        return ['days' => $days, 'channels' => $channelNames, 'data' => $reData];
    }

    private function dayToZero(&$day)
    {
        $day = $day - date('H',$day) * 3600;
    }

    public function nearby2($channel_id)
    {
        return $this->nearby15($channel_id,'',2);
    }

    public function nearby16($channel_id)
    {
        $now = new \DateTime();
        $beginDay = $now->format('Y-m-d');
        $beginDay = strtotime("$beginDay 0:0:0") - 86400;
        return $this->nearby15($channel_id);
    }

    public function fbaNearby15($channel_id, $beginDay = '')
    {
        if (!$beginDay) {
            $now = new \DateTime();
            $beginDay = $now->format('Y-m-d');
            $beginDay = strtotime("$beginDay 0:0:0");
            $now->sub(new \DateInterval('P15D'));
        }

        $channelServer = new Channel();
        $channels = $channelServer->getChannels();
        $days = $this->interval($beginDay, 15);
        $channelData = [];
        $channelNames = [];
        $fbaOrderModel = new FbaOrder();


        $where = [
            'pay_time' => ['between', [$days[0], ($days[14] + 86399)]],
        ];


        //平台过滤器
        $filterChannels = [];
        $targetFillter = new Filter(ChannelsFilter::class, true);
        if ($targetFillter->filterIsEffective()) {
            $filterChannels = $targetFillter->getFilterContent();
            $where['channel_id'] = ['in', $filterChannels];
        }

        //平台账号过滤器
        $filterAccounts = [];
        $targetFillter = new Filter(ChannelAccountsFilter::class, true);
        if ($targetFillter->filterIsEffective()) {
            $filterAccounts = $targetFillter->getFilterContent();
            $where['channel_account'] = ['in', $filterAccounts];
        }

        //1.初始化数
        $quantities = [];
        $amounts = [];
        foreach ($days as $day) {
            $quantities[$day] = 0;
            $amounts[$day] = 0;
        }
        foreach ($channels as $channel) {
            if ($filterChannels && !in_array($channel->id, $filterChannels)) {
                continue;
            }
            if (!empty($channel_id) && $channel->id != $channel_id) {
                continue;
            }
            $channelNames[] = $channel->title;
            $channelData[$channel->id] = [
                'quantities' => $quantities,
                'amount' => $amounts,
                'channel' => $channel->title
            ];
        }

        //2.统计数据


        $datas = $fbaOrderModel->field('channel_id,pay_time,(rate*pay_fee) as pay_amount')->where($where)->select();
        foreach ($datas as $data) {
            $dayKey = strtotime(date('Y-m-d', $data['pay_time']));
            $channelData[$data['channel_id']]['quantities'][$dayKey] += 1;
            $channelData[$data['channel_id']]['amount'][$dayKey] += $data['pay_amount'];
        }
        //3.格式化数据
        $currencyData = Cache::store('currency')->getCurrency('USD');
        $system_rate = $currencyData['system_rate'];   //转换为人民币了
        $channelDatas = [];
        foreach ($channelData as &$v) {
            $quantities = [];
            $amounts = [];
            foreach ($v['quantities'] as $v1) {
                $quantities[] = $v1;
            }
            foreach ($v['amount'] as $v1) {
                $amounts[] = floor($v1 / $system_rate);
            }
            $channelDatas[] = [
                'quantities' => $quantities,
                'amount' => $amounts,
                'channel' => $v['channel'],
            ];
        }

        return ['days' => $days, 'channels' => $channelNames, 'data' => $channelDatas];
    }

    private function getFabNearby14($beginDay = '')
    {
        $cache = Cache::handler();
        $cacheKey = 'cache:getFabNearby14:' . date('Y_m_d');
        if ($cache->exists($cacheKey)) {
            $channelData = $cache->get($cacheKey);
            $channelData = json_decode($channelData, true);
            return $channelData;
        }
        if (!$beginDay) {
            $now = new \DateTime();
            $beginDay = $now->format('Y-m-d');
            $beginDay = strtotime("$beginDay 0:0:0");
            $now->sub(new \DateInterval('P15D'));
        }

        $channelServer = new Channel();
        $channels = $channelServer->getChannels();
        $days = $this->interval($beginDay, 15);
        unset($days[14]);
        $channelData = [];
        $channelNames = [];
        $fbaOrderModel = new FbaOrder();
        $currencyData = Cache::store('currency')->getCurrency('USD');
        $system_rate = $currencyData['system_rate'];   //转换为人民币了
        foreach ($channels as $channel) {
            if (!empty($channel_id) && $channel->id = $channel_id) {
                continue;
            }
            $quantities = [];
            $payAmount = [];
            foreach ($days as $id => $day) {
                $where = [
                    'pay_time' => ['between', [$day, ($day + 86399)]],
                    'channel_id' => $channel->id,
                ];
                $order_quantity = $fbaOrderModel->where($where)->count();
                if ($order_quantity) {
                    $data = $fbaOrderModel->field('sum(rate*pay_fee) as pay_amount')->where($where)->find();
                    $payAmount[] = !empty($data['pay_amount']) ? floor($data['pay_amount'] / $system_rate) : 0;
                    $quantities[] = $order_quantity;
                } else {
                    $payAmount[] = 0;
                    $quantities[] = 0;
                }
            }
            $channelNames[] = $channel->title;
            $channelData[$channel->id] = [
                'quantities' => $quantities,
                'amount' => $payAmount,
                'channel' => $channel->title
            ];
        }
        $cache->set(
            $cacheKey,
            json_encode($channelData),
            3600 * 2);
        return $channelData;
    }


    private function interval($secs, $day)
    {
        $result = [];
        for ($i = 0; $i < $day; $i++) {
            $result[] = $secs;
            $secs -= TIME_SECS_DAY;
        }
        return array_reverse($result);
    }

    public function orderInfo()
    {
        $orderPackage = new OrderPackage();
        return [
            'message_count' => $orderPackage->getMessageInformationCount(),
            'addr_wrong_count' => $orderPackage->getAddressWrongCount(),
            'commodity_is_unknown_count' => $orderPackage->getCommodityIsUnknownCount(),
            'cannot_allocate_warehouse' => $orderPackage->getCannotAllocateWarehouse(),
            'you_review_the' => $orderPackage->getYouReviewThe(),
            'maill_error' => $orderPackage->getMaillError(),
            'stock_out' => $orderPackage->getStockOut(),
            'overdue' => $orderPackage->getOverdueCount()
        ];
    }

    public function listingCount()
    {
        $channelServer = new Channel();
        $result = [];
        foreach ($channelServer->getChannels() as $channel) {
            $listData = [
                'id' => $channel->id,
                'name' => $channel->name,
            ];
            $listServer = $channelServer->getPublishServer($channel->id);
            if ($listServer instanceof DashboradPublish) {
                $listData['exceptionCount'] = $listServer->getExceptionListing();
                $listData['listing'] = $listServer->getListingIn();
                $listData['notyet'] = $listServer->getNotyetPublish();
                $listData['stopsell'] = $listServer->getStopSellWaitRelisting();
                $result[] = $listData;
            }
        }
        return $result;
    }

    public function warehouseInfo()
    {
        $server = new WarehouseGoods();
    }

    public function performance($channel, $account, $time)
    {
        switch ($time) {
            case 1://昨日
                $beginTime = Carbon::yesterday()->getTimestamp();
                $endTime = Carbon::today()->getTimestamp();
                break;
            case 2://上旬
                $today = Carbon::today();
                $today->day = 1;
                $beginTime = $today->getTimestamp();
                $today->addDay(10);
                $endTime = $today->getTimestamp();
                break;
            case 3://中旬
                $today = Carbon::today();
                $today->day = 11;
                $beginTime = $today->getTimestamp();
                $today->addDay(10);
                $endTime = $today->getTimestamp();
                break;
            case 4://下旬
                $today = Carbon::today();
                $today->day = 21;
                $beginTime = $today->getTimestamp();
                $today->month += 1;
                $today->day = 1;
                $endTime = $today->getTimestamp();
                break;
            default:
                throw new JsonErrorException("不支持的时间类型");
        }
        $model = new ReportStatisticByDeeps();
        $orderReportModel = new ReportStatisticByOrder();
        if ($account) {
            $model->scope('account', $account);
            $orderReportModel->scope('account', $account);
        }
        $join = [];
        if ($channel) {
            $channel = intval($channel);
            $model->scope('channel', $channel);
            $orderReportModel->scope('channel', $channel);
            switch ($channel) {
                case ChannelAccountConst::channel_ebay:
                    $join[] = ['ebay_account a', 'a.id = r.account_id'];
                    break;
                case ChannelAccountConst::channel_amazon:
                    $join[] = ['amazon_account a', 'a.id = r.account_id'];
                    break;
                case ChannelAccountConst::channel_wish:
                    $join[] = ['wish_account a', 'a.id = r.account_id'];
                    break;
                case ChannelAccountConst::channel_aliExpress:
                    $join[] = ['aliexpress_account a', 'a.id = r.account_id'];
                    break;
            }
        }
        //查询订单数
        $field = 'sum(order_quantity) as order_quantity,sum(order_unpaid_quantity) as order_unpaid_quantity,account_id,code';
        $orderData = $orderReportModel->scope('interval', ['begin_time' => $beginTime, 'end_time' => $endTime])->alias('r')->field($field)->join($join)->group('account_id')->select();
        $field = 'sum(delivery_quantity) as delivery_quantity,sum(sale_amount) as sale_amount,account_id,code';
        $deepsData = $model->scope('interval', ['begin_time' => $beginTime, 'end_time' => $endTime])->alias('r')->field($field)->join($join)->group('account_id')->select();
        $reportData = [];
        foreach ($deepsData as $d => $dd) {
            $reportData[$dd['account_id']]['delivery_quantity'] = intval($dd['delivery_quantity']);
            $reportData[$dd['account_id']]['sale_amount'] = intval($dd['sale_amount']);
            $reportData[$dd['account_id']]['account_name'] = $dd['code'];
            $reportData[$dd['account_id']]['order_quantity'] = 0;
            $reportData[$dd['account_id']]['order_unpaid_quantity'] = 0;
        }
        foreach ($orderData as $o => $oo) {
            $reportData[$oo['account_id']]['order_quantity'] = intval($oo['order_quantity']);
            $reportData[$oo['account_id']]['order_unpaid_quantity'] = intval($oo['order_unpaid_quantity']);
            if (!isset($reportData[$oo['account_id']]['delivery_quantity'])) {
                $reportData[$oo['account_id']]['delivery_quantity'] = 0;
            }
            if (!isset($reportData[$oo['account_id']]['sale_amount'])) {
                $reportData[$oo['account_id']]['sale_amount'] = 0;
            }
            $reportData[$oo['account_id']]['account_name'] = $oo['code'];
        }
        $reportData = array_values($reportData);
        return $reportData;
    }

    /**
     * @doc 统计某一天按平台订单数量
     * @param $day string
     */
    public function statisticsDayOrderCount($day)
    {
        $channelServer = new Channel();
        $channels = $channelServer->getChannels();
        $beginTime = strtotime("$day 00:00:00");
        $endTime = strtotime("$day 23:59:59");
        foreach ($channels as $channel) {
            $count = Db::table('order')->whereBetween('pay_time', [$beginTime, $endTime])->where('channel_id', $channel->id)->count();
            $orderCount = [
                'order_count' => $count,
                'day' => $day,
                'channel_id' => $channel->id
            ];
            if ($oldReport = ReportOrderCountByDay::get(['day' => $day, 'channel_id' => $channel->id])) {
                $oldReport->order_count = $count;
                $oldReport->save();
            } else {
                ReportOrderCountByDay::create($orderCount);
            }
        }
    }

    /**
     * 账号销售量统计
     * @param $channel_id
     * @param $dateline
     * @param int $page
     * @param int $pageSize
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getStaticOrderInfo($channel_id, $dateline, $page = 1, $pageSize = 20)
    {

        $defaultReturn = [
            'data' => [],
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => 0,
        ];

        //平台过滤器
        $targetFillter = new Filter(ChannelsFilter::class, true);
        if ($targetFillter->filterIsEffective()) {
            $filterChannels = $targetFillter->getFilterContent();
            if ($filterChannels && !in_array($channel_id, $filterChannels)) {
                throw new JsonErrorException('您没有该平台的权限');
            }
        }


        //平台账号过滤器
        $filterAccounts = [];
        $targetFillter = new Filter(ChannelAccountsFilter::class, true);
        if ($targetFillter->filterIsEffective()) {
            $filterAccount = $targetFillter->getFilterContent();
            foreach ($filterAccount as $v) {
                $filterAccounts[] = $v % OrderType::ChannelVirtual;
            }
            $where['account_id'] = ['in', $filterAccounts];
        }

        $reportStatisticByOrderModel = new ReportStatisticByOrder();
        $statisticOrderService = new StatisticOrder();
        $currencyData = Cache::store('currency')->getCurrency('USD');
        $system_rate = $currencyData['system_rate'];   //转换为人民币了
        $where = [
            'dateline' => strtotime($dateline),
            'channel_id' => $channel_id,
        ];
        $count = $reportStatisticByOrderModel->where($where)->count();
        $result = Cache::store('account')->getAccountByChannel($channel_id);
        $cacheQuantity = $statisticOrderService->getCacheOrderByAccount($channel_id, $filterAccounts, $where['dateline']);
        $reData = [];
        if ($count) {
            $defaultReturn['count'] = $count;
            $field = 'account_id,order_quantity,pay_amount,rate';
            $list = $reportStatisticByOrderModel->field($field)->where($where)->page($page, $pageSize)->select();

            foreach ($list as $v) {
                $payAmount = 0;
                if ($v['pay_amount'] && $v['rate']) {
                    $payAmount = floor($v['pay_amount'] / $v['rate']);
                }
                $reData[] = [
                    'account_id' => $v['account_id'],
                    'code' => $result[$v['account_id']]['code'] ?? $v['account_id'],
                    'order_quantity' => ($cacheQuantity[$v['account_id']]['order_quantity'] ?? 0) + $v['order_quantity'],
                    'amount' => ($cacheQuantity[$v['account_id']]['pay_amount'] ?? 0) / $system_rate + $payAmount,
                ];
            }
        } elseif ($cacheQuantity) {
            $defaultReturn['count'] = count($cacheQuantity);
            foreach ($cacheQuantity as $k => $v) {
                $reData[] = [
                    'account_id' => $k,
                    'code' => $result[$k]['code'] ?? $k,
                    'order_quantity' => ($cacheQuantity[$k]['order_quantity'] ?? 0),
                    'amount' => ($cacheQuantity[$k]['pay_amount'] ?? 0) / $system_rate,
                ];
            }
        }
        $defaultReturn['data'] = $reData;
        return $defaultReturn;
    }
}