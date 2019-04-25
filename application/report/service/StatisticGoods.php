<?php

namespace app\report\service;

use app\common\cache\Cache;
use app\common\model\AfterRedeliverDetail;
use app\common\model\AfterSaleService;
use app\common\model\AfterWastageGoods;
use app\common\model\Order;
use app\common\model\OrderDetail;
use app\common\model\OrderOos;
use app\common\model\OrderPackage;
use app\common\model\report\ReportStatisticByGoods;
use app\common\model\SinglePackageSku;
use app\common\model\User;
use app\common\model\Warehouse;
use app\common\service\AfterSaleType;
use app\common\service\ChannelAccountConst;
use app\common\service\OrderStatusConst;
use app\common\service\Report;
use app\common\service\UniqueQueuer;
use app\common\traits\Export;
use app\goods\service\GoodsSku;
use app\order\service\OrderRuleExecuteService;
use app\order\service\PackageService;
use app\report\queue\WriteBackOrderGoods;
use think\Db;
use think\Exception;
use think\Loader;

Loader::import('phpExcel.PHPExcel', VENDOR_PATH);

/** 商品统计
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/8/1
 * Time: 19:17
 */
class StatisticGoods
{
    use Export;
    protected $reportStatisticByDeepsModel = null;

    public function __construct()
    {
        if (is_null($this->reportStatisticByDeepsModel)) {
            $this->reportStatisticByDeepsModel = new ReportStatisticByGoods();
        }
    }

    /** 列表数据
     * @param $data
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function lists($data)
    {
        $where = [];
        $this->where($data, $where);
        $lists = $this->reportStatisticByDeepsModel->field(true)->where($where)->select();
        return $lists;
    }

    /** 搜索条件
     * @param $data
     * @param $where
     * @return \think\response\Json
     */
    private function where($data, &$where)
    {
        if (isset($data['channel_id']) && !empty($data['channel_id'])) {
            $where['channel_id'] = ['eq', $data['channel_id']];
        }
        if (isset($data['warehouse_id']) && !empty($data['warehouse_id'])) {
            $where['warehouse_id'] = ['eq', $data['warehouse_id']];
        }
        if (isset($data['warehouse_type']) && !empty($data['warehouse_type'])) {
            $where['warehouse_type'] = ['eq', $data['warehouse_type']];
        }
        if (isset($data['goods_id']) && !empty($data['goods_id'])) {
            $where['goods_id'] = ['eq', $data['goods_id']];
        }
        if (isset($data['sku_id']) && !empty($data['sku_id'])) {
            $where['sku_id'] = ['eq', $data['sku_id']];
        }
        if (isset($data['category_id']) && !empty($data['category_id'])) {
            $where['category_id'] = ['eq', $data['category_id']];
        }
        $data['date_b'] = isset($data['date_b']) ? $data['date_b'] : 0;
        $data['date_e'] = isset($data['date_e']) ? $data['date_e'] : 0;
        $condition = timeCondition($data['date_b'], $data['date_e']);
        if (!is_array($condition)) {
            return json(['message' => '日期格式错误'], 400);
        }
        if (!empty($condition)) {
            $where['dateline'] = $condition;
        }
    }

    /**
     * 获取当天的产品销售
     * @param $sku_id
     * @param $warehouse_id
     * @param int $channel_id
     * @return array
     */
    public function getCurrentSales($sku_id, $warehouse_id, $channel_id = 0)
    {
        $time = strtotime(date('Y-m-d', time()));
        $cache = Cache::handler(true);
        $currentSales = [];
        if (empty($channel_id)) {
            $channel_id = [
                ChannelAccountConst::channel_ebay,
                ChannelAccountConst::channel_amazon,
                ChannelAccountConst::channel_aliExpress,
                ChannelAccountConst::channel_wish
            ];
        } else {
            $channel_id = [$channel_id];
        }
        foreach ($channel_id as $key => $channel) {
            $key = $channel . '-' . $sku_id . '-' . $warehouse_id . '-' . $time;
            if ($cache->hExists('hash:saleByGoods', $key)) {
                $goodsData = $cache->hget('hash:saleByGoods', $key);
                $goodsData = json_decode($goodsData, true);
                array_push($currentSales, $goodsData);
            }
        }
        return $currentSales;
    }

    /**
     * 数据重置
     * @param int $begin_time
     * @param int $end_time
     * @param int $sku_id
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function resetReport($begin_time = 0, $end_time = 0, $sku_id = 0)
    {
        date_default_timezone_set("PRC");
        $where['p.status'] = ['<>', OrderStatusConst::SaidInvalid];
        $where['o.create_time'] = ['between', [$begin_time, $end_time]];
        $where2['p.status'] = ['>', OrderStatusConst::ForDistribution];
        if ($sku_id) {
            $where['d.sku_id'] = ['eq', $sku_id];
        }
        if ($begin_time > 0) {
            if (!empty($sku_id)) {
                $deleteWhere['sku_id'] = ['eq', $sku_id];
            }
            $deleteWhere['dateline'] = ['between', [$begin_time, $end_time]];
            (new ReportStatisticByGoods())->where($deleteWhere)->update(['order_quantity' => 0]);
        }
        $packageList = Db::table('order_detail')->master()->alias('d')->field('d.sku_id,d.goods_id,sku_quantity,p.warehouse_id,o.channel_id,o.create_time')
            ->join('order_package p', 'd.package_id = p.id')
            ->join('order o', 'd.order_id = o.id')
            ->where($where)
            ->where($where2)
            ->select();
        $packageData = [];
        foreach ($packageList as $k => $v) {
            $time = strtotime(date('Y-m-d', $v['create_time']));
            $key = $v['channel_id'] . '-' . $v['warehouse_id'] . '-' . $v['sku_id'] . '-' . $v['goods_id'] . '-' . $time;
            if (isset($packageData[$key])) {
                $packageData[$key]['quantity'] += $v['sku_quantity'];
            } else {
                $packageData[$key]['quantity'] = $v['sku_quantity'];
            }
        }
        unset($packageList);
        //$userModel = new User();
        $reportGoodsModel = new ReportStatisticByGoods();
        $insertData = [];
        $updateData = [];
        foreach ($packageData as $key => $data) {
            list($channel_id, $warehouse_id, $sku_id, $goods_id, $create_time) = explode('-', $key);
            $reportGoodsInfo = $reportGoodsModel->where([
                'dateline' => $create_time,
                'channel_id' => $channel_id,
                'warehouse_id' => $warehouse_id,
                'sku_id' => $sku_id
            ])->find();
            if (!empty($reportGoodsInfo)) {
                $updateData[] = ['create_time' => $create_time, 'channel_id' => $channel_id, 'warehouse_id' => $warehouse_id, 'sku_id' => $sku_id, 'order_quantity' => $data['quantity']];
            } else {
                //根据商品  超找分类
                $goodsInfo = Cache::store('goods')->getGoodsInfo($goods_id);
                $goodsData = [];
                if (!empty($goodsInfo)) {
                    $goodsData['category_id'] = $goodsInfo['category_id'];
                    //查找开发者信息
                    $goodsData['developer_id'] = $goodsInfo['developer_id'];
                    //if (!empty($goodsData['developer_id']) && !is_numeric($goodsData['developer_id'])) {
                    //$userInfo = $userModel->field('id')->where(['realname' => $goodsData['developer_id']])->find();
                    //$userInfo = Cache::store('user')->getOneUser($goodsData['developer_id']);
                    // $goodsData['developer_id'] = $userInfo['id'] ?? 0;
                    //}
                    //查找采购员信息
                    $goodsData['purchaser_id'] = $goodsInfo['purchaser_id'];
                    //查看是否为新品
                    if ($goodsInfo['sales_status'] == 1 && ($goodsInfo['publish_time'] - time()) < 30 * 24 * 60 * 60) {
                        $goodsData['new_listing'] = 1;   //是新品
                    } else {
                        $goodsData['new_listing'] = 0;
                    }
                }
                //查出仓库类型
                // $warehouseModel = new Warehouse();
                // $warehouseInfo = $warehouseModel->where(['id' => $warehouse_id])->find();
                $warehouseInfo = Cache::store('warehouse')->getWarehouse($warehouse_id);
                if (!empty($warehouseInfo)) {
                    $goodsData['warehouse_type'] = $warehouseInfo['type'];
                } else {
                    $goodsData['warehouse_type'] = 5;   //仓库为零，默认为fba仓库
                }
                $goodsData['dateline'] = $create_time;
                $goodsData['order_quantity'] = $data['quantity'];
                $goodsData['order_turnover'] = 1;
                $goodsData['channel_id'] = $channel_id;
                $goodsData['warehouse_id'] = $warehouse_id;
                $goodsData['sku_id'] = $sku_id;
                $goodsData['goods_id'] = $goods_id;
                $insertData[] = $goodsData;
            }
        }
        unset($packageData);
        //Db::startTrans();
        try {
            foreach ($updateData as $u => $value) {
                $reportGoodsModel = new ReportStatisticByGoods();
                $reportGoodsModel->where([
                    'dateline' => $value['create_time'],
                    'channel_id' => $value['channel_id'],
                    'warehouse_id' => $value['warehouse_id'],
                    'sku_id' => $value['sku_id']
                ])->update(['order_quantity' => $value['order_quantity']]);
            }
            if (!Cache::store('partition')->getPartition('ReportStatisticByGoods', time())) {
                Cache::store('partition')->setPartition('ReportStatisticByGoods', time(), null, []);
            }
            foreach ($insertData as $k => $data) {
                (new ReportStatisticByGoods())->allowField(true)->isUpdate(false)->save($data);
            }
            //Db::commit();
        } catch (Exception $e) {
            //Db::rollback();
            var_dump($e->getMessage() . $e->getFile() . $e->getLine());
        }
    }


    /**
     * SKU    今日缺货数量     最近10天的销量， 最近20天的销量    供应商
     * @param int $timestamp
     * @return false|\PDOStatement|string|\think\Collection
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getSkuOosSalesSupplier($timestamp = 0)
    {
        if (!$timestamp) {
            $timestamp = time();
        }
        $reportGoodsModel = new ReportStatisticByGoods();
        $orderOosModel = new OrderOos();
        $goodsSkuModel = new GoodsSku();
        $timesS = strtotime(date('Y-m-d', $timestamp)); //时间
        $timesE = $timesS + 3600 * 24 - 1;
        $times10d = $timesS - 3600 * 24 * 10;
        $times20d = $timesS - 3600 * 24 * 20;

        //$whereOos['create_time'] = ['between', [$timesS,$timesE]];
        $whereOos = [];

        $skuIdList = [];
        $list = $orderOosModel->field('sku,sku_id,goods_id,(sum(requisition_qty) - sum(alloted_qty)) as qs')->where($whereOos)->where('`requisition_qty` > `alloted_qty`')->group('sku_id')->select(); //今日今日缺货数量

        foreach ($list as $v) {
            $skuIdList[] = $v['sku_id'];
        }

        $where['sku_id'] = ['in', $skuIdList];
        $where['dateline'] = ['between', [$times10d, $timesS]];
        $sales10d = $reportGoodsModel->where($where)->group('sku_id')->column('sum(order_quantity)', 'sku_id');
        $where['dateline'] = ['between', [$times20d, $timesS]];
        $sales20d = $reportGoodsModel->where($where)->group('sku_id')->column('sum(order_quantity)', 'sku_id');
        $writer = new \XLSXWriter();
        $title = [
            'sku' => 'string',
            'sku_id' => 'string',
            '今日缺货数量' => 'string',
            '最近10天的销量' => 'string',
            '最近20天的销量' => 'string',
            // '供应商' => 'string'
        ];
        $writer->writeSheetHeader('Sheet1', $title);
        foreach ($list as &$v) {
            $v = $v->toArray();
            $v['sales10d'] = $sales10d[$v['sku_id']] ?? 0;
            $v['sales20d'] = $sales20d[$v['sku_id']] ?? 0;
            $v['supplier'] = $goodsSkuModel->getDefaultSupplierId($v['sku_id']);
            unset($v['goods_id']);
        }

        foreach ($list as $value) {
            $writer->writeSheetRow('Sheet1', $value);
        }
        $downLoadDir = '/download/order_detail/';
        $saveDir = ROOT_PATH . 'public' . $downLoadDir;
        if (!is_dir($saveDir) && !mkdir($saveDir, 0777, true)) {
            throw new Exception('导出目录创建失败');
        }
        $fullName = $saveDir . '采购数据.xlsx';
        $writer->writeToFile($fullName);
        //return $list;
    }

    /**
     * 查询suk的平均重量（去掉最大和最小值）
     * @param array $skuList
     * @param int $priorTimes
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getSkuWeightData($where, $join, $page = 1, $pageSize = 500)
    {
        $data = [];
        $orderPackage = new OrderPackage();
        $field = 'od.sku,od.sku_id,sum(package_weight) as weightSum,count(op.id) as weightCount';
        $list = $orderPackage->alias('op')->join($join)->field($field)->where($where)->group('od.sku_id')->page($page, $pageSize)->select();
        foreach ($list as $item) {
            $one = [];
            $one['sku_id'] = $item['sku_id'];
            $one['sku'] = $item['sku'];
            $one['weightCount'] = $item['weightCount'];
            $oldSku = Cache::store('Goods')->getSkuInfo($item['sku_id']);
            $one['weightOld'] = $oldSku['weight'] ?? 0;
            if ($item['weightCount'] <= 20) {
                $one['weight'] = $item['weightSum'] / $item['weightCount'];
            } else if ($item['weightCount'] <= 100) {
                $one['weight'] = $this->getSkuAvgWeihtBySkuId($item['sku_id'], $item['weightSum'], $item['weightCount'], 0.05);
            } else {
                $one['weight'] = $this->getSkuAvgWeihtBySkuId($item['sku_id'], $item['weightSum'], $item['weightCount'], 0.1);
            }
            $one['weight'] = sprintf('%.2f', $one['weight']);
            $data[] = $one;
        }
        return $data;
    }

    public function getSkuAvgWeihtBySkuId($skuId, $sum = 100, $count = 21, $percent = 0.05)
    {
        $rmNum = intval($count * $percent);
        $rmNumMax = $count - $rmNum;
        $orderPackage = new OrderPackage();
        $where = $this->getPackageWhere();
        $where['od.sku_id'] = $skuId;
        $join = $this->getPackageJoin();
        $list = $orderPackage->alias('op')->join($join)->where($where)->order('package_weight')->column('package_weight');
        foreach ($list as $key => $value) {
            if ($key < $rmNum || $key >= $rmNumMax) {
                $sum -= $value;
            }
        }
        unset($list);
        return $sum / ($count - 2 * $rmNum);
    }

    private function getPackageJoin()
    {
        $join[] = ['order_detail od', 'op.id = od.package_id', 'left'];
        return $join;
    }

    private function getPackageWhere($skuList = [], $priorTimes = 0, $skuIds = '')
    {
        $warehouses = [2, 6];
        $where = [
            'op.warehouse_id' => ['in', $warehouses],
            'op.type' => 1,
            'op.shipping_time' => ['>', 0],
        ];
        if (!empty($skuIds)) {
            $where['od.sku_id'] = ['in', $skuIds];
        }
        if (!empty($skuList)) {
            $where['od.sku'] = ['in', $skuList];
        }
        if ($priorTimes > 0) {
            $where['op.create_time'] = ['>', $priorTimes];
        }
        return $where;
    }

    /**
     * 导出单品单件包裹，统计sku 平均重量（排除最大最小）
     * @param array $skuList
     * @param int $priorTimes
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getPackageSkuAverageWeightOld($skuList = [], $priorTimes = 0)
    {
        set_time_limit(0);
        $data = $this->getSkuWeightData($skuList, $priorTimes);
        var_dump($data);
        die;
        //导出
        $writer = new \XLSXWriter();
        $title = [
            'sku' => 'string',
            '原有重量' => 'string',
            '称重重量' => 'string',
        ];
        $writer->writeSheetHeader('Sheet1', $title);
        foreach ($data as $value) {
            $writer->writeSheetRow('Sheet1', $value);
        }
        $downLoadDir = '/download/order_detail/';
        $saveDir = ROOT_PATH . 'public' . $downLoadDir;
        if (!is_dir($saveDir) && !mkdir($saveDir, 0777, true)) {
            throw new Exception('导出目录创建失败');
        }
        $fullName = $saveDir . '单品单件包裹sku平均重量.xlsx';
        $writer->writeToFile($fullName);
    }

    protected $colMap = [
        'title' => [
            'A' => ['title' => 'sku', 'width' => 30],
            'B' => ['title' => '原有重量', 'width' => 30],
            'C' => ['title' => '称重重量', 'width' => 30],
            'D' => ['title' => '统计数量', 'width' => 30],
            'E' => ['title' => 'sku_id', 'width' => 30],
        ],
        'data' => [
            'sku' => ['col' => 'A', 'type' => 'str'],
            'weightOld' => ['col' => 'B', 'type' => 'str'],
            'weight' => ['col' => 'C', 'type' => 'str'],
            'weightCount' => ['col' => 'D', 'type' => 'str'],
            'sku_id' => ['col' => 'E', 'type' => 'str'],
        ]
    ];

    public function doCount($where, $join)
    {
        $orderPackage = new OrderPackage();

        $count = $orderPackage->alias('op')->join($join)->where($where)->group('od.sku_id')->count();
        return $count;
    }

    /**
     * 导出数据至excel文件
     * @param array $params
     * @return bool
     * @throws Exception
     */
    public function getPackageSkuAverageWeight($skuList = [], $priorTimes = 0)
    {
        set_time_limit(0);
        try {
            //ini_set('memory_limit', '4096M');

            $fileName = '单品单件包裹sku平均重量' . date('Y-m-d') . '.xlsx';
            $downLoadDir = '/download/order_detail/';
            $saveDir = ROOT_PATH . 'public' . $downLoadDir;
            if (!is_dir($saveDir) && !mkdir($saveDir, 0777, true)) {
                throw new Exception('导出目录创建失败');
            }
            $fullName = $saveDir . $fileName;
            //创建excel对象
            $excel = new \PHPExcel();
            $excel->setActiveSheetIndex(0);
            $sheet = $excel->getActiveSheet();
            $titleRowIndex = 1;
            $dataRowStartIndex = 2;
            $titleMap = $this->colMap['title'];
            $lastCol = 'D';
            $dataMap = $this->colMap['data'];
            //设置表头和表头样式
            foreach ($titleMap as $col => $set) {
                $sheet->getColumnDimension($col)->setWidth($set['width']);
                $sheet->getCell($col . $titleRowIndex)->setValue($set['title']);
                $sheet->getStyle($col . $titleRowIndex)
                    ->getFill()
                    ->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('FF9900');
                $sheet->getStyle($col . $titleRowIndex)
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
            }
            $sheet->setAutoFilter('A1:' . $lastCol . '1');

            //统计需要导出的数据行
            $where = $this->getPackageWhere($skuList, $priorTimes);
            $join = $this->getPackageJoin();
            $where = is_null($where) ? [] : $where;
            $count = $this->doCount($where, $join);
            $pageSize = 500;
            $loop = ceil($count / $pageSize);
            //分批导出
            for ($i = 0; $i < $loop; $i++) {
                $data = $this->getSkuWeightData($where, $join, $i + 1, $pageSize);
                foreach ($data as $r) {
                    foreach ($dataMap as $field => $set) {
                        $cell = $sheet->getCell($set['col'] . $dataRowStartIndex);
                        switch ($set['type']) {
                            case 'time_stamp':
                                if (empty($r[$field])) {
                                    $cell->setValue('');
                                } else {
                                    $cell->setValue(date('Y-m-d', $r[$field]));
                                }
                                break;
                            case 'numeric':
                                $cell->setDataType(\PHPExcel_Cell_DataType::TYPE_NUMERIC);
                                if (empty($r[$field])) {
                                    $cell->setValue(0);
                                } else {
                                    $cell->setValue($r[$field]);
                                }
                                break;
                            default:
                                if (is_null($r[$field])) {
                                    $r[$field] = '';
                                }
                                $cell->setValue($r[$field]);
                        }
                    }
                    $dataRowStartIndex++;
                }
            }
            $writer = \PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
            $writer->save($fullName);
            if (is_file($fullName)) {
                return true;
            } else {
                throw new Exception('文件写入失败');
            }
        } catch (\Exception $ex) {
            throw new Exception('文件写入失败' . $ex->getMessage());
        }
    }

    /**
     * 更新sku列表的数据
     * @param array $skuList
     * @param int $priorTimes
     * @param int $pageSize
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function updatePackageSkuAverageWeight($skuList = [], $priorTimes = 0, $skuIds = '', $pageSize = 100)
    {
        $where = $this->getPackageWhere($skuList, $priorTimes, $skuIds);
        $join = $this->getPackageJoin();
        $data = $this->getSkuWeightData($where, $join, 1, $pageSize);
        if ($data) {
            $updateData = [];
            foreach ($data as $v) {
                $updateData[$v['sku_id']] = $v['weight'];
            }
            $re = (new GoodsSku())->batchUpdateWeightByASkuId($updateData);
            return true;
        }
        return false;
    }

    /**
     * 更新sku 大小数据
     * @param string $skuIds
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function updatePackageSkuSize($skuIds = '')
    {
        $where = [
            'length' => ['>' , 0],
            'width' => ['>' , 0],
            'height' => ['>' , 0],
            'sku_id' => ['in' , $skuIds],
        ];
        $model = new SinglePackageSku();
        $field = 'sku_id,count(id) as count,avg(length) as length,avg(width) as width,avg(height) as height';
        $data = $model->field($field)->where($where)->group('sku_id')->select();
        foreach ($data as $v) {
            if($v['count'] >=  5){
                $re = (new GoodsSku())->updateSizeByASkuId($v['sku_id'],$v);
            }
        }
    }

    /**
     * 更新统计信息
     * @param $package_id
     * @throws Exception
     */
    public function updateReportByDelivery($package_id)
    {
        try {
            //包裹信息
            $packageInfo = (new OrderPackage())->field(true)->where(['id' => $package_id])->find();
            if (empty($packageInfo)) {
                return false;
            }
            //订单信息
            $orderInfo = (new Order())->field(true)->where(['id' => $packageInfo['order_id']])->find();
            if (empty($orderInfo)) {
                return false;
            }
            $detailCost = (new OrderDetail())->field('sum(sku_cost * sku_quantity) as cost')->where(['order_id' => $orderInfo['id']])->find();
            $totalCost = $detailCost['cost'] ?? 0;
            //详情
            $detailList = (new OrderDetail())->field(true)->where(['package_id' => $package_id])->select();
            foreach ($detailList as $key => $value) {
                //查询产品信息
                $totalAmount = ($value['sku_cost'] * $value['sku_quantity']) / $totalCost * $orderInfo['goods_amount'] * $orderInfo['rate'];
                //产品统计
                Report::saleByGoods($packageInfo['channel_id'], $value['goods_id'], $value['sku_id'],
                    $packageInfo['warehouse_id'], $orderInfo['create_time'], [
                        'sale' => $value['sku_quantity'],  //销售数
                        'sale_amount' => $totalAmount
                    ], false);
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage() . $e->getFile() . $e->getLine());
        }
    }

    /**
     * 退款/退货之后更新统计信息
     * @param $after_id
     * @return bool
     * @throws Exception
     */
    public function updateReportByRefund($after_id)
    {
        try {
            $afterSaleModel = new AfterSaleService();
            $afterWastageModel = new AfterWastageGoods();
            $afterRedeliverModel = new AfterRedeliverDetail();
            $where['id'] = ['=', $after_id];
            $where['approve_status'] = ['=', 4];
            $afterSaleInfo = $afterSaleModel->field(true)->where($where)->find();
            if (empty($afterSaleInfo)) {
                return false;
            }
            //订单信息
            $orderInfo = (new Order())->field(true)->where(['id' => $afterSaleInfo['order_id']])->find();
            //详情
            $detailList = (new OrderDetail())->field(true)->where(['order_id' => $afterSaleInfo['order_id']])->select();
            $totalAmount = 0;
            $detailArray = [];
            foreach ($detailList as $key => $value) {
                $totalAmount = $totalAmount + $value['sku_price'] * $value['sku_quantity'];
                $detailArray[$value['sku_id']] = [
                    'sku_price' => $value['sku_price'],
                    'sku_quantity' => $value['sku_quantity'],
                    'package_id' => $value['package_id'],
                ];
            }
            //补发货品/退货货品信息
            $afterRedeliverGoods = [];
            $afterRedeliverList = $afterRedeliverModel->field(true)->where(['after_sale_service_id' => $after_id])->select();
            foreach ($afterRedeliverList as $key => $value) {
                $afterRedeliverGoods[$value['sku_id']] = $value;
            }
            //退款单
            if (in_array($afterSaleInfo['type'], [AfterSaleType::Refund, AfterSaleType::RefundAndReplacementGoods, AfterSaleType::RefundAndReturnGoods, AfterSaleType::RefundAndReplacementGoodsAndReturnGoods])) {
                $orderRuleExecuteService = new OrderRuleExecuteService();
                //金额转换为人民币
                $after_refund_amount = $orderRuleExecuteService->convertCurrency($afterSaleInfo['refund_currency'], 'CNY', $afterSaleInfo['refund_amount']);
                //问题商品
                $afterWastageList = $afterWastageModel->field(true)->where(['after_sale_service_id' => $after_id])->select();
                foreach ($afterWastageList as $after => $wastage) {
                    if (isset($afterRedeliverGoods[$wastage['sku_id']]) && $afterRedeliverGoods[$wastage['sku_id']]['type'] == 2) {  //排除是补发货
                        continue;
                    }
                    if (empty($wastage['sku_id'])) {
                        continue;
                    }
                    //查询产品信息
                    $skuInfo = Cache::store('goods')->getSkuInfo($wastage['sku_id']);
                    $goodsInfo = Cache::store('goods')->getGoodsInfo($skuInfo['goods_id']);
                    if (isset($detailArray[$wastage['sku_id']]) && !empty($goodsInfo)) {
                        $refund_amount = 0;
                        //平摊费用
                        if (!empty($totalAmount)) {
                            $refund_amount = ($detailArray[$wastage['sku_id']]['sku_price'] * $detailArray[$wastage['sku_id']]['sku_quantity']) / $totalAmount * $after_refund_amount;
                        }
                        //查出包裹号
                        $packageInfo = (new OrderPackage())->field('warehouse_id')->where(['id' => $detailArray[$wastage['sku_id']]['package_id']])->find();
                        Report::saleByGoods($orderInfo['channel_id'], $wastage['goods_id'], $wastage['sku_id'],
                            $packageInfo['warehouse_id'], $orderInfo['create_time'], [
                                'refund_amount' => $refund_amount,
                                'refund' => $wastage['quantity']
                            ], false);
                    }
                }
            }
            foreach ($afterRedeliverGoods as $after => $redeliver) {
                //查询产品信息
                $skuInfo = Cache::store('goods')->getSkuInfo($redeliver['sku_id']);
                $goodsInfo = Cache::store('goods')->getGoodsInfo($skuInfo['goods_id']);
                if (isset($detailArray[$redeliver['sku_id']]) && !empty($goodsInfo)) {
                    $refund = $redeliver['quantity'];  //退货数量
                    $repeat = 0;
                    $repeat_amount = 0;
                    if ($redeliver['type'] == 2) {   //补发货
                        $repeat = $redeliver['quantity'];
                        $refund = 0;
                        $repeat_amount = $detailArray[$redeliver['sku_id']]['sku_price'] * $redeliver['quantity'];
                    }
                    //查出包裹号
                    $packageInfo = (new OrderPackage())->field('warehouse_id')->where(['id' => $detailArray[$redeliver['sku_id']]['package_id']])->find();
                    Report::saleByGoods($orderInfo['channel_id'], $redeliver['goods_id'], $redeliver['sku_id'],
                        $packageInfo['warehouse_id'], $orderInfo['create_time'], [
                            'repeat_amount' => $repeat_amount,
                            'refund' => $refund,
                            'repeat' => $repeat
                        ], false);
                }
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage() . $e->getFile() . $e->getLine());
        }
    }

    /**
     * 重发之后更新统计信息
     * @param $package_id
     * @param $old_shipping_fee
     * @throws Exception
     */
    public function updateReportByRepeat($package_id)
    {
        try {
            //包裹信息
            $packageInfo = (new OrderPackage())->field(true)->where(['id' => $package_id])->find();
            if (empty($packageInfo)) {
                return false;
            }
            //订单信息
            $orderInfo = (new Order())->field(true)->where(['id' => $packageInfo['order_id']])->find();
            if (empty($orderInfo)) {
                return false;
            }
            $detailCost = (new OrderDetail())->field('sum(sku_cost * sku_quantity) as cost')->where(['order_id' => $orderInfo['id']])->find();
            $totalCost = $detailCost['cost'] ?? 0;
            //详情
            $detailList = (new OrderDetail())->field(true)->where(['package_id' => $package_id])->select();
            foreach ($detailList as $key => $value) {
                //查询产品信息
                $totalAmount = ($value['sku_cost'] * $value['sku_quantity']) / $totalCost * $orderInfo['goods_amount'] * $orderInfo['rate'];
                //产品统计
                Report::saleByGoods($packageInfo['channel_id'], $value['goods_id'], $value['sku_id'],
                    $packageInfo['warehouse_id'], $orderInfo['create_time'], [
                        'repeat' => $value['sku_quantity'],  //重发数
                        'repeat_amount' => $totalAmount  //重发金额
                    ]);
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage() . $e->getFile() . $e->getLine());
        }
    }

    /**
     * 更新订单状态时更新统计
     * @param $order_id
     */
    public function updateReportByStatus($order_id)
    {
        try {
            $sign = 1;
            //查询sku详情信息,订单信息
            $orderInfo = (new Order())->field('create_time,channel_id,pay_time,channel_account_id,goods_amount,rate')->where(['id' => $order_id])->find();
            $detailList = (new OrderDetail())->field('sku_cost,sku_quantity,sku_id,goods_id,package_id')->where(['order_id' => $order_id])->select();
            $sku_total_cost = 0;
            $package_ids = [];
            foreach ($detailList as $d => $detail){
                $sku_total_cost += $detail['sku_cost'] * $detail['sku_quantity'];
                $package_ids[$detail['package_id']] = $detail['package_id'];
            }
            $package_ids = array_values($package_ids);
            $packageInfo = (new OrderPackage())->field('id,shipping_time,channel_id,warehouse_id,picking_id,shipping_id')->where('id', 'in',
                $package_ids)->select();
            foreach ($packageInfo as $package => $info) {
                //查询详情
                foreach ($detailList as $key => $value) {
                    if($value['package_id'] == $info['id']){
                        $is_shipping = false;
                        if($info['shipping_time'] > 0){
                            $is_shipping = true;
                            $cost = $value['sku_cost'] * $value['sku_quantity'];
                            //成本比
                            $totalAmount = ($cost / $sku_total_cost) * $orderInfo['goods_amount'] * $orderInfo['rate'];
                        }
                        Report::saleByGoods($info['channel_id'], $value['goods_id'], $value['sku_id'],
                            $info['warehouse_id'], $orderInfo['create_time'], [
                                'order' => $value['sku_quantity'] * $sign,  //订单商品数
                                'buyer' => 1 * $sign,   //买家数
                                'turnover' => 1 * $sign,  //订单笔数
                                'sale' => $is_shipping ? $value['sku_quantity'] : 0,  //销售数
                                'sale_amount' => $is_shipping ? $totalAmount : 0
                            ],false);
                    }
                }
            }
        } catch (Exception $e) {

        }
    }

    /**
     * 统计会写
     * @param $begin_time
     * @param $end_time
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function writeBackOrder($begin_time, $end_time)
    {
        $where['create_time'] = ['between', [$begin_time, $end_time]];
        $where['status'] = [['>=',OrderStatusConst::ForDistribution],['<',OrderStatusConst::SaidInvalid],'and'];
        Db::table('order')->field('id')->where($where)->chunk(20000, function ($orderList) {
            foreach ($orderList as $orderInfo) {
                (new UniqueQueuer(WriteBackOrderGoods::class))->push($orderInfo['id']);
            }
        });
    }
}