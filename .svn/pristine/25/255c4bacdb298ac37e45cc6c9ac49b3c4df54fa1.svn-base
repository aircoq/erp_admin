<?php

namespace app\report\service;

use app\common\model\User;
use app\purchase\service\PurchaseOrder;
use app\purchase\service\SupplierOfferService;
use app\report\queue\InvoicingQueue;
use app\warehouse\service\WarehouseGoods;
use think\Db;
use think\Exception;
use think\Loader;
use app\common\model\WarehouseLog;
use app\common\cache\Cache;
use app\warehouse\service\StockIn as StockInService;
use app\warehouse\service\StockOut as StockOutService;
use app\common\model\StockInDetail as StockInDetail;
use app\common\model\StockOutDetail as StockOutDetail;
use app\goods\service\GoodsSkuAlias as GoodsSkuAliasService;
use \app\goods\service\GoodsHelp as GoodsHelp;
use app\common\model\GoodsSku;
use app\common\model\Goods;
use app\common\service\Common;
use app\report\model\ReportExportFiles;
use app\common\service\CommonQueuer;
use app\common\traits\Export;

Loader::import('phpExcel.PHPExcel', VENDOR_PATH);

/**
 * Created by PhpStorm.
 * User: laiyongfeng
 * Date: 2019/03/20
 * Time: 19:17
 */
class Invoicing
{
    use Export;

    protected $stockInDetailModel = null;
    protected $stockOutDetailModel = null;
    protected $warehouseLogModel = null;
    protected $start_time = 0;//开始时间
    protected $end_time = 0;//时间时间
    protected $inout_where = [];
    protected $where = [];
    protected $warehouse_id = 0;
    protected $in_out_type = [];
    protected $type = 'summary';
    protected $colMap = [
        'summary' =>[
            'SKU' => 'string',
            '产品名称' => 'string',
            '商品分类' =>'string',
            '仓库' => 'string',
            '期初库存-数量' => 'string',
            '期初库存-单价' => 'string',
            '期初库存-金额' => 'string',
            '本期入库-数量' => 'string',
            '本期入库-单价' =>'string',
            '本期入库-金额' => 'string',
            '本期出库-数量' => 'string',
            '本期出库-单价' => 'string',
            '本期出库-金额' => 'string',
            '期末库存-数量' => 'string',
            '期末库存-单价' => 'string',
            '期末库存-金额' => 'string',
            '<30天' => 'string',
            '30天<>60天' => 'string',
            '60天<>90天' => 'string',
            '90天以上' => 'string',
            '盘点数量' => 'string',
            '最近采购单价' =>'string',
            '最新采购报价' => 'string'
        ],
        'detail' =>[
            '日期' => 'string',
            '出入库类型' => 'string',
            '仓库' =>'string',
            'sku' => 'string',
            '产品名称' => 'string',
            '数量' => 'string',
            '单价' => 'string',
            '金额' => 'string',
            '制单人' =>'string',
            '单据号' => 'string'
        ],
    ];

    public function __construct()
    {
        if (is_null($this->stockInDetailModel)) {
            $this->stockInDetailModel = new StockInDetail();
        }
        if (is_null($this->stockOutDetailModel)) {
            $this->stockOutDetailModel = new StockOutDetail();
        }
        if (is_null($this->warehouseLogModel)) {
            $this->warehouseLogModel = new WarehouseLog();
        }
        $in_type_arr = (new StockInService())->getTypes();
        $out_type_arr = (new StockOutService())->getOutType();
        $this->in_type = array_diff(array_column($in_type_arr, 'value'), array(0));
        $this->out_type = array_keys($out_type_arr);
        $this->in_out_type = array_merge($this->in_type, $this->out_type);
    }


    /*
     * @desc 分类查询
     * @param int $category_id
     */
    public function category($category_id)
    {
        $category_list = Cache::store('category')->getCategoryTree();
        if (isset($category_list[$category_id])) {
            $child = $category_list[$category_id]['child_ids'];
            if ($child) {
                $child = implode(',', $child);
                $category_ids = $child;
            } else {
                $category_ids = [$category_id];
            }
        } else {
            $category_ids = [$category_id];
        }
        $goods = (new Goods())->where('category_id', 'in', $category_ids)->field('id')->select();
        $goods_ids = array_map(function ($good) {
            return $good->id;
        }, $goods);
        if ($this->type == 'summary') {
            $goods_ids = $goods_ids ? $goods_ids : [0];
            $this->where .= ' and goods_id in(' . implode(',', $goods_ids) . ')';
        } else {
            $this->where['goods_id'] = ['in', implode(',', $goods_ids)];
        }
    }

    /**
     * @desc 汇总列表
     * @param array $params
     */
    public function where($params)
    {
        if($this->type == 'summary') {
            $this->inout_where = [
                's.warehouse_id' => $this->warehouse_id,
                's.update_time' => [['>=', $this->start_time], ['<=', $this->end_time]],
                's.status' => ['=', 2]
            ];
            $this->where = 'l1.warehouse_id = ' . $this->warehouse_id;
            $this->where .= ' and l1.type in ('.implode(',', $this->in_out_type).')';
            $this->where .= " and l1.create_time >= {$this->start_time} and l1.create_time <= {$this->end_time}";
        } else {
            $this->where['warehouse_id'] = ['=', $this->warehouse_id];
            $this->where['create_time'] = [['>=', $this->start_time], ['<=', $this->end_time]];
        }
        if (($snType = param($params, 'snType')) && ($snValue = param($params, 'snText'))) {
            switch ($snType) {
                case 'sku':
                    $sku_arr = json_decode($snValue);
                    if (!$sku_arr) {
                        break;
                    }
                    $sku_id_arr = [];
                    foreach ($sku_arr as $value) {
                        $sku_id = GoodsSkuAliasService::getSkuIdByAlias($value);//别名
                        if (!$sku_id) {
                            $sku_id = GoodsHelp::sku2id($value);
                        }
                        array_push($sku_id_arr, $sku_id);
                    }
                    if ($this->type == 'summary') {
                        $sku_id_arr = $sku_id_arr ? $sku_id_arr : [0];
                        $this->where .= ' and l1.sku_id in (' . implode(',', $sku_id_arr) . ')';
                    } else {
                        $this->where['sku_id'] = ['in', $sku_id_arr];
                    }

                    break;
                case 'name':
                    $snValue = json_decode($snValue);
                    $sku_id_arr = (new GoodsSku())->where('spu_name', 'like', "%$snValue[0]%")->column('id');
                    $sku_id_arr = $sku_id_arr ? $sku_id_arr : [0];
                    if ($this->type == 'summary') {
                        $this->where .= ' and l1.sku_id in (' . implode(',', $sku_id_arr) . ')';
                    } else {
                        $this->where['sku_id'] = ['in', $sku_id_arr];
                    }
                    break;
                default:
                    break;
            }
        }
        if (param($params, 'category_id')) {
            $this->category($params['category_id']);
        }
        if (param($params, 'sku_id')) {

            $sku_arr = json_decode($params['sku_id']);
            $sku_id_arr = [];
            foreach ($sku_arr as $value) {
                $sku_id = GoodsSkuAliasService::getSkuIdByAlias($value);//别名
                if (!$sku_id) {
                    $sku_id = GoodsHelp::sku2id($value);
                }
                array_push($sku_id_arr, $sku_id);
            }
            if ($this->type == 'summary') {
                $sku_id_arr = $sku_id_arr ? $sku_id_arr : [0];
                $this->where .= ' and l1.sku_id in (' . implode(',', $sku_id_arr) . ')';
            } else {
                $this->where['sku_id'] = ['in', $sku_id_arr];
            }
        }

        if (param($params, 'ids')) {
            $ids = json_decode($params['ids']);

            $this->where['id'] = ['in', $ids];
        }
    }

    /**
     * @desc 设置开始结束时间
     * @param array $params
     * @throws Exception
     */
    public function setStartEndTime($params)
    {
        if (!param($params, 'date_from')) {
            throw new Exception('开始时间不能为空');
        }
        if (!param($params, 'date_to')) {
            throw new Exception('结束时间不能为空');
        }
        $this->start_time = strtotime($params['date_from']);
        $this->end_time = strtotime($params['date_to']) + (3600 * 24 - 1);
    }

    /**
     * @desc 设置仓库
     * @param array $params
     * @throws Exception
     */
    public function setWarehouseId($params)
    {
        if (!param($params, 'warehouse_id')) {
            throw new Exception('仓库不能为空');
        }
        $this->warehouse_id = $params['warehouse_id'];
    }

    /**
     * @desc 设类型
     * @param int $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }


    /**
     * @desc 出库数据
     * @return array
     */
    private function getOutData()
    {
        $field = 'd.sku_id, s.warehouse_id, sum(d.quantity) as quantity,  sum((d.price+d.shipping_cost)*d.quantity) as amount';
        return $this->stockOutDetailModel->alias('d')
            ->join('stock_out s', 's.id = d.stock_out_id')
            ->where($this->inout_where)
            ->field($field)
            ->group('d.sku_id')
            ->column($field, 'd.sku_id');
    }

    /**
     * @desc 入库数据
     * @return array
     */
    private function getInData()
    {
        $field = 's.code, d.sku_id, s.warehouse_id, sum(d.quantity) as quantity,  sum((d.price+d.shipping_cost)*d.quantity) as amount';
        return $this->stockInDetailModel->alias('d')
            ->join('stock_in s', 's.id = d.stock_in_id')
            ->where($this->inout_where)
            ->field($field)
            ->group('d.sku_id')
            ->column($field, 'sku_id');
    }



    /**
     * @desc 期初期末数据
     * @param  int $page
     * @param  int $pageSize
     * @return array
     */
    public function getMaxMinIds($page, $pageSize)
    {
        $start = ($page - 1)*$pageSize;
        $sql = "SELECT max(id) as end_id, min(id) as init_id
            FROM warehouse_log l1 force index(idx_create_time)
            where {$this->where}
            group by sku_id limit {$start}, {$pageSize}";
        return  DB::query($sql);
    }

    private function getFields()
    {
        $fields = 'l1.sku_id, l1.stock_quantity, l1.type, l1.quantity';
        $fields .= ', l1.price, l1.per_cost, l1.average_price, l1.shipping_cost';
        return $fields;
    }

    /**
     * @desc 获取期末库存
     * @param  array $ids
     * @return array
     */
    private function geEndLogData($ids)
    {
        return (new WarehouseLog())
            ->alias('l1')
            ->where(['id' => ['in', $ids]])
            ->field($this->getFields())
            ->select();
    }

    /**
     * @desc 获取期初库存
     * @param  array $ids
     * @return array
     */
    private function getStartLogData($ids)
    {
        return (new WarehouseLog())
            ->alias('l1')
            ->where(['id' => ['in', $ids]])
            ->field($this->getFields())
            ->select();
    }

    /**
     * @desc 汇总列表
     * @return array
     */
    public function summaryCount()
    {
        $sql = "SELECT count(distinct l1.sku_id) as count
            FROM warehouse_log l1 force index(idx_create_time)
            where {$this->where}";
        $data = DB::query($sql);
        return $data[0]['count'];
    }

    /**
     * @desc 汇总列表
     * @param array $data
     * @return array
     */
    private function getData($data)
    {
        $result = [];
        $type = in_array($data['type'], $this->in_type) ? 1 : 2; //1-入库 2-出库
        $result['qty'] = $type == 1 ? $data['stock_quantity'] + $data['quantity'] : $data['stock_quantity'] - $data['quantity'];
        if ($data['average_price']>0) {
            $result['price'] = $data['average_price'] + $data['shipping_cost'];
        } else {
            //原来没有存平均单价
            if ($type == 1) {
                if ($result['qty']) {
                    $result['price'] = ($data['per_cost'] * $data['stock_quantity'] + $data['quantity'] * $data['price']) / $result['qty'];
                } else {
                    $result['price'] = 0;
                }
            } else {
                $result['price'] = $data['per_cost'];
            }
            $result['price'] = $data['per_cost'] + $data['shipping_cost'];
        }
        $result['price'] = sprintf('%.4f', $result['price']);
        $result['amount'] = $result['qty'] * $result['price'];
        return $result;
    }

    /**
     * @desc 反推运费
     * @param int $sku_id
     * @return float
     */
    private function getInitShippingCost($sku_id)
    {
        $where = [
            'sku_id' => $sku_id,
            'warehouse_id' => $this->warehouse_id,
            'create_time' =>['<',  $this->start_time]
        ];
        return (new WarehouseLog())->where($where)->order('id desc')->value('shipping_cost', 0);
    }

    /**
     * @desc 汇总列表
     * @param int  $page
     * @param int  $pageSize
     * @return array
     */
    public function summary($page=1, $pageSize=20, $export = false)
    {
        $stockInService = new StockInService;
        //$purchaseOrder = new PurchaseOrder();
        $supplierOfferService = new SupplierOfferService();
        $warehouse = cache::store('warehouse')->getWarehouse($this->warehouse_id);

        $start_end_ids = $this->getMaxMinIds($page, $pageSize);
        $init_ids = array_column($start_end_ids, 'init_id');
        $end_ids = array_column($start_end_ids, 'end_id');
        //期末数据
        $end_data = $this->geEndLogData($end_ids);
        $sku_id_arr = array_column($end_data, 'sku_id');
        //期初数据
        $start_data = $this->getStartLogData($init_ids);
        $start_sku_data = [];
        foreach ($start_data as $item) {
            $start_sku_data[$item['sku_id']] = $item;
        }
        //出入库
        $this->inout_where['d.sku_id'] = ['in', $sku_id_arr];
        $in_data = $this->getInData();  //入库数据
        $out_data = $this->getOutData(); //出库数据

        $data = [];
        $end_qty_arr = [];
        $goodsModel = new Goods();
        foreach ($end_data as $end) {
            $sku_info = Cache::store('goods')->getSkuInfo($end['sku_id']);
            $category = '';
            $goods = Cache::store('goods')->getGoodsInfo($sku_info['goods_id']);
            if ($goods) {
                $category = $goodsModel->getCategoryAttr('', $goods);
            }
            //期末数据
            $this_end = $this->getData($end);
            $end_qty = $this_end['qty'];//期末库存
            $end_price = $this_end['price'];//期末库存
            $end_amount = $this_end['amount']; //期末库存
            $end_qty_arr[$end['sku_id']] = $end_qty;

            //入库数据
            $in = $in_data[$end['sku_id']] ?? [];
            $in_qty = $in['quantity'] ?? 0;
            $in_amount = $in ? $in['amount'] : 0;
            $in_price = $in_qty ? sprintf('%.4f', $in_amount / $in_qty) : 0;

            //出库数据
            $out = $out_data[$end['sku_id']] ?? [];
            $out_qty = $out['quantity'] ?? 0;
            $out_amount = $out ? $out['amount'] : 0;
            $out_price = $out_qty ? sprintf('%.4f', $out_amount / $out_qty) : 0;

            //期初数据
            $init_qty = 0;//期初库存
            $init_price = 0;//期初单价
            $init_amount = 0; //期初数量
            $start = $start_sku_data[$end['sku_id']] ?? [];
            if ($start) {
                $type = in_array($start['type'], $this->in_type) ? 1 : 2; //1-入库 2-出库
                $init_qty = $start['stock_quantity'];
                if ($type ==  2) { //出库运费不变
                    $init_price = $start['per_cost']+$start['shipping_cost'];
                } else { //入库取不到原来的运费
                    $init_shipping = 0;
                    //这个时间日志加了本次运费
                    if ($start['create_time'] > 1555903800) {
                        if ($start['stock_quantity']) {
                            $init_shipping = (($start['stock_quantity']+$start['quantity'])*$start['shipping_cost']-$start['quantity']*$start['shipping_fee'])/$start['stock_quantity'];
                            $init_shipping = sprintf('%.4f', $init_shipping);
                        }
                    } else {
                        $init_shipping = $this->getInitShippingCost($end['sku_id']);
                        //单价反推（因为取不到运费, 中间会手动改价格--不适用）
                        //$init_price = sprintf('%.4f', ($end_amount+$out_amount-$in_amount)/$init_qty);
                    }
                    $init_price = $init_shipping + $start['per_cost'];
                }
                $init_amount =  $init_price*$start['stock_quantity'];
            }

            $diff_qty = $init_qty+$in_qty-$out_qty-$end_qty;
            //会存在期末库存有并发出库的情况
            if ($diff_qty <0 && !in_array($end['type'], $this->in_type)) {
                $end_qty = $init_qty+$in_qty-$out_qty;
                $end_amount = $end_qty*$end_price; //期末库存
            }
            $latest_purchase_price = $stockInService->getLastPurchasePrice($this->warehouse_id, $end['sku_id'], StockInService::TYPE_PURCHASE_IN, false); //最近采购单价
            //组装数据
            $data[$end['sku_id']] = [
                'sku' => $sku_info['sku'],
                'spu_name' => $sku_info['spu_name'],
                'category' => $category,
                'warehouse_name' => $warehouse['name'],
                'init_qty' => $init_qty,
                'inti_price' => sprintf('%.4f', $init_price),
                'init_amount' => sprintf('%.4f', $init_amount),
                'in_qty' =>$in_qty,
                'in_price' => sprintf('%.4f', $in_price),
                'in_amount' => sprintf('%.4f', $in_amount),
                'out_qty' => $out_qty,
                'out_price' => sprintf('%.4f', $out_price),
                'out_amount' =>sprintf('%.4f', $out_amount),
                'end_qty' => $end_qty,
                'end_price' =>sprintf('%.4f', $end_price),
                'end_amount' => sprintf('%.4f', $end_amount),
                'less_third' => 0,
                'third_sixty' => 0,
                'sixty_ninety' => 0,
                'more_ninety' => 0,
                'check_qty' => '-', //盘点数据
                'latest purchase_price' => $latest_purchase_price,
                'latest_supply_prcie' => $supplierOfferService->getGoodsOffer($end['sku_id'])
            ];
        }
        $stockInService->batchGetAgeDetail($this->warehouse_id, $end_qty_arr, $this->end_time, $data);
        return $data;
    }

    /**
     * @desc 入库运费
     * @param int $stock_in_id
     * @param int $sku_id
     * @return int
     */
    private function getInPrice($stock_in_id, $sku_id)
    {
        $where = [
            'stock_in_id' => $stock_in_id,
            'sku_id' => $sku_id,
        ];
        return $this->stockInDetailModel->where($where)->value('sum(price+shipping_cost) as price', 0);
    }


    /**
     * @desc 出库运费
     * @param int $stock_out_id
     * @param int $sku_id
     * @return int
     */
    private function getOutPrice($stock_out_id, $sku_id)
    {
        $where = [
            'stock_out_id' => $stock_out_id,
            'sku_id' => $sku_id,
        ];
        return $this->stockOutDetailModel->where($where)->value('sum(price+shipping_cost) as price', 0);
    }

    /**
     * @desc 汇总列表
     * @return array
     */
    public function detailCount()
    {
        return (new WarehouseLog())->alias('l1')->where($this->where)->count();
    }

    /**
     * @desc 明细列表
     * @param int $page
     * @param int $pageSize
     * @param bool $export
     * @return array
     */
    public function detail($page = 1, $pageSize=20, $export = false)
    {
        $stockInService = new StockInService;
        $data = (new WarehouseLog())
            ->where($this->where)
            ->page($page, $pageSize)
            ->select();
        $in_types = array_column($stockInService->getTypes(), 'value');
        $in_types = array_diff($in_types, [0]);

        $warehouse = cache::store('warehouse')->getWarehouse($this->warehouse_id);
        $result = [];
        foreach ($data as $item) {
            $sku_info = Cache::store('goods')->getSkuInfo($item['sku_id']);
            if (in_array($item['type'], $in_types)) {
                $item['price'] = $this->getInPrice($item['stock_inout_id'], $item['sku_id']);
            } else {
                $item['price'] = $this->getOutPrice($item['stock_inout_id'], $item['sku_id']);
            }
            $temp =  [
                'create_time' => date('Y-m-d', $item['create_time']),
                'type' =>  StockInService::getType($item['type']),
                'warehouse_name' =>$warehouse['name'],
                'sku' => $item['sku'],
                'spu_name' => param($sku_info, 'spu_name'),
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'amount' => (($item['price']) * $item['quantity']),
                'creator' =>cache::store('user')->getOneUserRealname($item['create_id']),
                'stock_inout_code' => $item['stock_inout_code'],
            ];
            if (!$export) {
                $temp['id'] = $item['id'];
            }
            $result[] = $temp;
        }
        return $result;
    }

    /**
     * @desc 创建导出文件名
     * @param string $type
     * @return string
     */
    protected function createExportFileName($params)
    {
        $fileName = $this->type == 'summary' ? '进销存汇总报表' : '进销存明细报表';
        $lastID = (new ReportExportFiles())->order('id desc')->value('id');
        $fileName .= '_'.($lastID+1);
        $warehouse = cache::store('warehouse')->getWarehouse($this->warehouse_id);
        $fileName .= '_'.$warehouse['name'].'('.$params['date_from'].'_'.$params['date_to'].').xlsx';
        return $fileName;
    }

    /**
     * 获取参数
     * @param array $params
     * @param $key
     * @param $default
     * @return mixed
     */
    public function getParameter(array $params, $key, $default)
    {
        $v = $default;
        if (isset($params[$key]) && $params[$key]) {
            $v = $params[$key];
        }
        return $v;
    }


    /**
     * @desc 申请导出
     * @param $params
     * @return bool
     * @throws Exception
     * @throws \Exception
     */
    public function applyExport($params)
    {
        Db::startTrans();
        try {
            $userId = Common::getUserInfo()->toArray()['user_id'];
            $cache = Cache::handler();
            $lastApplyTime = $cache->hget('hash:export_apply', $userId);
           /* if ($lastApplyTime && time() - $lastApplyTime < 5) {
                throw new Exception('请求过于频繁', 400);
            } else {
                $cache->hset('hash:export_apply', $userId, time());
            }*/
            $this->setStartEndTime($params);
            $this->setWarehouseId($params);
            $model = new ReportExportFiles();
            $model->applicant_id = $userId;
            $model->apply_time = time();

            $model->export_file_name = $this->createExportFileName($params);
            $model->status = 0;
            if (!$model->save()) {
                throw new Exception('导出请求创建失败', 500);
            }
            $params['file_name'] = $model->export_file_name;
            $params['apply_id'] = $model->id;
            $params['type'] = $this->type;
            $queuer = new CommonQueuer(InvoicingQueue::class);
            $queuer->push($params);
            Db::commit();
            return true;
        } catch (\Exception $ex) {
            Db::rollback();
            if ($ex->getCode()) {
                throw $ex;
            } else {
                Cache::handler()->hset(
                    'hash:report_export_apply',
                    $params['apply_id'] . '_' . time(),
                    $ex->getMessage());
                throw new Exception($ex->getFile().$ex->getLine().$ex->getMessage(), 500);
            }
        }
    }


    /**
     * 导出数据至excel文件
     * @param $params
     * @return bool
     * @throws Exception
     */
    public function export($params)
    {
        set_time_limit(0);
        try {
//            ini_set('memory_limit','1024');
            $applyId = $this->getParameter($params, 'apply_id', '');
            if (!$applyId) {
                throw new Exception('导出申请id获取失败');
            }
            $fileName = $this->getParameter($params, 'file_name', '');
            if (!$fileName) {
                throw new Exception('导出文件名未设置');
            }

            $downLoadDir = '/download/invoicing/';
            $saveDir = ROOT_PATH . 'public' . $downLoadDir;
            if (!is_dir($saveDir) && !mkdir($saveDir, 0777, true)) {
                throw new Exception('导出目录创建失败');
            }
            $fullName = $saveDir . $fileName;
            //创建excel对象
            $writer = new \XLSXWriter();
            $this->setType($params['type']);
            $titleMap = $this->colMap[$this->type];
            $this->setStartEndTime($params);
            $this->setWarehouseId($params);
            $this->where($params);
            $func = $this->type;
            $countFunc = $func.'Count';
            $count = $this->$countFunc();
            $pageSize = 100;
            $loop = ceil($count / $pageSize);
            $writer->writeSheetHeader('Sheet1', $titleMap);
            //分批导出
            for ($i = 0; $i < $loop; $i++) {
                $page =  $i+1;
                $data = $this->$func($page, $pageSize, true);
                foreach ($data as $r) {
                    $writer->writeSheetRow('Sheet1', $r);
                }
                unset($data);
            }
            $writer->writeToFile($fullName);
            if (is_file($fullName)) {
                $applyRecord = ReportExportFiles::get($applyId);
                $applyRecord->exported_time = time();
                $applyRecord->download_url = $downLoadDir . $fileName;
                $applyRecord->status = 1;
                $applyRecord->isUpdate()->save();
            } else {
                throw new Exception('文件写入失败');
            }
        } catch (\Exception $ex) {
            $applyRecord = ReportExportFiles::get($applyId);
            $applyRecord->status = 2;
            $applyRecord->error_message = $ex->getMessage();
            $applyRecord->isUpdate()->save();
            Cache::handler()->hset(
                'hash:report_export',
                $applyId . '_' . time(),
                '申请id: ' . $applyId . ',导出失败:' . $ex->getMessage());

        }
        return true;
    }
}