<?php
namespace app\report\service;

use app\common\cache\Cache;
use app\common\exception\JsonErrorException;
use app\common\model\Category;
use app\common\service\Common;
use app\common\service\CommonQueuer;
use app\report\model\ReportExportFiles;
use app\report\queue\SaleStockExportQueue;
use think\Db;
use think\Exception;
use think\Loader;
use app\common\model\GoodsSku as GoodsSkuModel;
use app\goods\service\GoodsHelp;
use app\goods\service\GoodsSkuAlias;
use app\warehouse\service\WarehouseGoods;
use app\common\model\PurchaseOrderDetail as PurchaseOrderDetailModel;
use app\common\model\OrderDetail as OrderDetailModel;
use app\report\validate\FileExportValidate;
use app\report\service\GoodsAnalysisService;
use app\common\model\WarehouseGoods as WarehouseGoodsModel;
use app\common\model\LogExportDownloadFiles;
use phpzip\PHPZip;
use app\common\model\Warehouse;
use app\common\model\WarehouseCargoGoods;

Loader::import('phpExcel.PHPExcel', VENDOR_PATH);

/**
 * Created by PhpStorm.
 * User: laiyongfeng
 * Date: 2017/10/20
 * Time: 10:11
 */
class SaleStockService
{
    protected $colMap = [
        'title' => [
            'A' => ['title' => '编号', 'width' => 10],
            'B' => ['title' => '货位号', 'width' => 15],
            'C' => ['title' => 'SKU别名', 'width' => 15],
            'D' => ['title' => '中文配货名称', 'width' => 30],
            'E' => ['title' => '中文名称', 'width' => 30],
            'F' => ['title' => '产品状态', 'width' => 15],
            'G' => ['title' => '品类', 'width' => 20],
            'H' => ['title' => '开发员', 'width' => 15],
            'I' => ['title' => '采购员', 'width' => 15],
            'J' => ['title' => '导入系统时间', 'width' => 20],
            'K' => ['title' => '上次采购价', 'width' => 15],
            'L' => ['title' => '当前成本', 'width' => 15],
            'M' => ['title' => '价格差值', 'width' => 15],
            'N' => ['title' => '重量', 'width' => 15],
            'O' => ['title' => '7天销量', 'width' => 15],
            'P' => ['title' => '7天营业额', 'width' => 20],
            'Q' => ['title' => '7天销售成本', 'width' => 20],
            'R' => ['title' => '7天周转天数', 'width' => 20],
            'S' => ['title' => '30天销量	', 'width' => 15],
            'T' => ['title' => '30天营业额', 'width' => 20],
            'U' => ['title' => '30天销售成本', 'width' => 20],
            'V' => ['title' => '30天周转天数', 'width' => 20],
            'W' => ['title' => '90天销量', 'width' => 15],
            'X' => ['title' => '90天营业额', 'width' => 20],
            'Y' => ['title' => '90天销售成本	', 'width' => 20],
            'Z' => ['title' => '90天周转天数', 'width' => 20],
            'AA' => ['title' => '可用库存数量', 'width' => 20],
            'AB' => ['title' => '待发库存数量', 'width' => 20],
            'AC' => ['title' => '在途数量', 'width' => 15],
            'AD' => ['title' => '库存总金额（包括在途）', 'width' => 30],
        ],
        'data' => [
            'sku' =>                          ['col' => 'A', 'type' => 'str'],
            'warehouse_cargo_code' =>         ['col' => 'B', 'type' => 'str'],
            'sku_alias' =>                    ['col' => 'C', 'type' => 'str'],
            'packing_name' =>                 ['col' => 'D', 'type' => 'str'],
            'name' =>                         ['col' => 'E', 'type' => 'str'],
            'status' =>                       ['col' => 'F', 'type' => 'str'],
            'category' =>                     ['col' => 'G', 'type' => 'str'],
            'developer_name' =>               ['col' => 'H', 'type' => 'str'],
            'purchaser_name' =>               ['col' => 'I', 'type' => 'str'],
            'create_time' =>                  ['col' => 'J', 'type' => 'time'],
            'last_purchase_price' =>          ['col' => 'K', 'type' => 'str'],
            'cost_price' =>                   ['col' => 'L', 'type' => 'numeric'],
            'diff_price' =>                   ['col' => 'M', 'type' => 'numeric'],
            'weight' =>                       ['col' => 'N', 'type' => 'str'],
            'seven_quantity' =>               ['col' => 'O', 'type' => 'str'],
            'seven_amount' =>                 ['col' => 'P', 'type' => 'str'],
            'seven_cost' =>                   ['col' => 'Q', 'type' => 'str'],
            'seven_turn_days' =>              ['col' => 'R', 'type' => 'str'],
            'thirty_quantity' =>              ['col' => 'S', 'type' => 'str'],
            'thirty_amount' =>                ['col' => 'T', 'type' => 'str'],
            'thirty_cost' =>                  ['col' => 'U', 'type' => 'str'],
            'thirty_turn_days' =>             ['col' => 'V', 'type' => 'str'],
            'ninety_quantity' =>              ['col' => 'W', 'type' => 'str'],
            'ninety_amount' =>                ['col' => 'X', 'type' => 'str'],
            'ninety_cost' =>                  ['col' => 'Y', 'type' => 'str'],
            'ninety_turn_days' =>             ['col' => 'Z', 'type' => 'str'],
            'available_quantity' =>           ['col' => 'AA', 'type' => 'str'],
            'waiting_shipping_quantity' =>    ['col' => 'AB', 'type' => 'str'],
            'instransit_quantity' =>          ['col' => 'AC', 'type' => 'str'],
            'stock_amt' =>                    ['col' => 'AD', 'type' => 'str']
        ]
    ];

    protected $header = [
            'A' => ['title' => '编号', 'width' => 10, 'key'=>'sku'],
            'B' => ['title' => '货位号', 'width' => 30, 'key'=>'warehouse_cargo_code', 'coding'=>true],
            'C' => ['title' => 'SKU别名', 'width' => 30, 'key'=>'sku_alias', 'coding'=>true],
            'D' => ['title' => '中文配货名称', 'width' => 30, 'key'=>'packing_name', 'coding'=>true],
            'E' => ['title' => '中文名称', 'width' => 30, 'key'=>'name', 'coding'=>true],
            'F' => ['title' => '产品状态', 'width' => 15, 'key'=>'status', 'coding'=>true],
            'G' => ['title' => '品类', 'width' => 20, 'key'=>'category', 'coding'=>true],
            'H' => ['title' => '开发员', 'width' => 15, 'key'=>'developer_name', 'coding'=>true],
            'I' => ['title' => '采购员', 'width' => 15, 'key'=>'purchaser_name', 'coding'=>true],
            'J' => ['title' => '导入系统时间', 'width' => 20, 'key'=>'create_time'],
            'K' => ['title' => '上次采购价', 'width' => 15, 'key'=>'last_purchase_price'],
            'L' => ['title' => '当前成本', 'width' => 15, 'key'=>'cost_price'],
            'M' => ['title' => '价格差值', 'width' => 15, 'key'=>'diff_price'],
            'N' => ['title' => '重量', 'width' => 15, 'key'=>'weight'],
            'O' => ['title' => '7天销量', 'width' => 15, 'key'=>'seven_quantity'],
            'P' => ['title' => '7天营业额', 'width' => 20, 'key'=>'seven_amount'],
            'Q' => ['title' => '7天销售成本', 'width' => 20, 'key'=>'seven_cost'],
            'R' => ['title' => '7天周转天数', 'width' => 20, 'key'=>'thirty_quantity'],
            'S' => ['title' => '30天销量	', 'width' => 15, 'key'=>'thirty_quantity'],
            'T' => ['title' => '30天营业额', 'width' => 20, 'key'=>'thirty_amount'],
            'U' => ['title' => '30天销售成本', 'width' => 20, 'key'=>'thirty_cost'],
            'V' => ['title' => '30天周转天数', 'width' => 20, 'key'=>'thirty_turn_days'],
            'W' => ['title' => '90天销量', 'width' => 15, 'key'=>'ninety_quantity'],
            'X' => ['title' => '90天营业额', 'width' => 20, 'key'=>'ninety_amount'],
            'Y' => ['title' => '90天销售成本	', 'width' => 20, 'key'=>'ninety_cost'],
            'Z' => ['title' => '90天周转天数', 'width' => 20, 'key'=>'ninety_turn_days'],
            'AA' => ['title' => '可用库存数量', 'width' => 20, 'key'=>'available_quantity'],
            'AB' => ['title' => '待发库存数量', 'width' => 20, 'key'=>'waiting_shipping_quantity'],
            'AC' => ['title' => '在途数量', 'width' => 15, 'key'=>'instransit_quantity'],
            'AD' => ['title' => '库存总金额（包括在途）', 'width' => 30, 'key'=>'stock_amt'],
    ];


    private $goodsSkuModel;
    private $goodsHelp;
    private $purchaseOrderDetailModel;
    private $orderDetailModel;
    private $warehouseGoods;

    public function __construct()
    {
        if (is_null($this->goodsSkuModel)) {
            $this->goodsSkuModel = new GoodsSkuModel();
        }
        if (is_null($this->goodsHelp)) {
            $this->goodsHelp = new GoodsHelp();
        }
        if (is_null($this-> purchaseOrderDetailModel)) {
            $this->purchaseOrderDetailModel = new PurchaseOrderDetailModel();
        }
        if (is_null($this-> orderDetailModel)) {
            $this->orderDetailModel = new OrderDetailModel();
        }
        if (is_null($this->warehouseGoods)) {
            $this->warehouseGoods = new WarehouseGoodsModel();
        }

    }

    /**
     * 查询条件
     * @param array $params
     * @param array $where
     * @return \think\response\Json
     */
    private function where($params, &$where)
    {
        $where['sku_id'] = ['neq', 0];
        //仓库筛选
        if (isset($params['warehouse_id']) && !empty($params['warehouse_id'])) {
            $where['warehouse_id'] = ['eq', $params['warehouse_id']];
        }
        //分类筛选
        if($categoryId = param($params, 'category_id')){
            $goods_ids =  (new GoodsHelp())->getGoodsIdByCategoryId($categoryId);
            $where['goods_id'] = ['in', $goods_ids];
        }
        //sku_id筛选
        if (isset($params['sku_ids']) && !empty($params['sku_ids']) && $params['sku_ids']!="[]") {
            if(is_array($params['sku_ids'])){
                $where['sku_id'] = ['in', $params['sku_ids']];
            }else{
                $where['sku_id'] = strpos($params['sku_ids'], ',') !== false ? ['in', explode(',', $params['sku_ids'])] : $params['sku_ids'];
            }
        }
    }


    /**
     * 根据天数获取销售数据
     * @param int $sku_id
     * @param int $time
     * @param int|string $warehouse_id
     * @return array
     */
    protected function getSaleInfoBySkuIds($sku_ids,  $time,  $warehouse_id = '')
    {
        $join[] = ['order o', 'o.id = d.order_id', 'left'];
        $where['o.shipping_time'] = ['>=', $time];
        $where['d.sku_id'] = ['in', $sku_ids];
        //币种选择
        if ($warehouse_id != '') {
            $where['p.warehouse_id'] = ['eq', $warehouse_id];
            $join[] = ['order_package p', 'p.id = d.package_id', 'left'];
        }

        $field =
            'd.sku_id, '.  //sku_id
            'o.shipping_time, '.  //发货时间
            'sum(d.sku_quantity) as sale_quantity, '.  //总的销量
            'sum(d.sku_price*d.sku_quantity) as sales_amount, '.    //总的销售额
            'sum(d.sku_cost*d.sku_quantity) as total_cost';//总的销售成本

        $lists = $this->orderDetailModel->alias('d')->join($join)->field($field)->where($where)->group('o.shipping_time, d.sku_id')->select();
        $results = [];
        foreach($lists as $item){
            if(!$item['sku_id']){
                continue;
            }
            $results[$item['sku_id']][$item['shipping_time']] = $item;
        }
        return $results;
    }


    /**
     * 根据天数获取销售数据
     * @param int $sku_id
     * @param int $time
     * @param int|string $warehouse_id
     * @return array
     */
    protected function getSaleInfo($sku_id,  $time,  $warehouse_id = '')
    {
        $join[] = ['order o', 'o.id = d.order_id', 'left'];
        $where['o.shipping_time'] = ['>=', $time];
        $where['d.sku_id'] = ['eq', $sku_id];
        //币种选择
        /*if ($currency_code!='') {
            $where['o.currency_code'] = ['eq', $currency_code];
        }*/
        //仓库选择
        if ($warehouse_id != '') {
            $where['p.warehouse_id'] = ['eq', $warehouse_id];
            $join[] = ['order_package p', 'p.id = d.package_id', 'left'];
        }

        $field =
            'o.shipping_time, '.  //发货时间
            'sum(d.sku_quantity) as sale_quantity, '.  //总的销量
            'sum(d.sku_price*d.sku_quantity) as sales_amount, '.    //总的销售额
            'sum(d.sku_cost*d.sku_quantity) as total_cost';//总的销售成本

        $results = $this->orderDetailModel->alias('d')->join($join)->field($field)->where($where)->group('o.shipping_time')->select();
        return $results;
    }

    private function getSkuIds($data){
        $sku_ids = [];
        foreach($data as $item){
            $sku_ids[] = $item['sku_id'];
        }
        return $sku_ids;
    }
    /**
     * 搜索
     * @param $field
     * @param array $condition
     * @param int|string $warehouse_id
     * @param int $page
     * @param int $pageSize
     * @return false|\PDOStatement|string|\think\Collection
     */
    protected function doSearch($field, array $condition = [], $warehouse_id = '', $page = 1, $pageSize = 10)
    {
        $data = $this->warehouseGoods->where($condition)->field($field)->page($page, $pageSize)->group('sku_id')->select();
        $warehouseGoods = new WarehouseGoods();
        //获取总的仓库的信息
        $warehouses_lists = Cache::store('warehouse')->getWarehouse();
        $warehouses = [];
        foreach($warehouses_lists as $item){
            $warehouses[$item['id']] = $item;
        }
        $sku_ids = $this->getSkuIds($data);
        //销量信息
        $all_ninety_info =  $this->getSaleInfoBySkuIds($sku_ids, strtotime('-89 days'), $warehouse_id);
        $goodsHelp = new \app\goods\service\GoodsHelp();
        //货位信息
        $cargo_lists = (new WarehouseCargoGoods())->alias('g')->join('warehouse_cargo c','g.warehouse_cargo_id = c.id', 'left')->where(['g.sku_id'=>['in', $sku_ids]])
           ->where('g.warehouse_area_type', 11)->field('g.warehouse_cargo_id, g.warehouse_id, g.sku_id, c.code')->select();
        $sku_cargo = [];
        foreach($cargo_lists as $value) {
            $sku_cargo[$value['sku_id']][] = $warehouses[$value['warehouse_id']]['name'].'|'.$value['code'];
        }

        //组装数据
        foreach ($data as $key=>&$item) {
            $skuInfo = Cache::store('goods')->getSkuInfo($item['sku_id']);
            if(empty($skuInfo)){
                continue;
            }
            $goodsInfo = Cache::store('goods')->getGoodsInfo($skuInfo['goods_id']);
            $item['warehouse_cargo_code'] = isset($sku_cargo[$item['sku_id']]) ? implode('，', $sku_cargo[$item['sku_id']]) : '';
            $item['weight'] = $skuInfo['weight'];//重量
            $item['name'] = $goodsInfo['name']; //中文名称
            $item['packing_name'] = $goodsInfo['packing_name'];//中文配货名称
            $item['cost_price'] = $goodsInfo['cost_price'];//成本价

            $item['category'] = $this->goodsHelp->mapCategory($goodsInfo['category_id']); //分类

            $item['status'] = isset($goodsHelp->sku_status[$skuInfo['status']]) ? $goodsHelp->sku_status[$skuInfo['status']] : '';//sku状态
            $alias = GoodsSkuAlias::getAliasBySkuId($item['sku_id']);
            $item['sku_alias'] = !empty($alias) ? implode(',', $alias) : '';//sku别名

            $item['developer_name'] = param($goodsInfo, 'developer_id'); //开发者

            $purchaserInfo = Cache::store('user')->getOneUser($goodsInfo['purchaser_id']);
            $item['purchaser_name'] = !empty($purchaserInfo) ? $purchaserInfo['realname'] : '';  //采购员

            //上次采购价
            $item['last_purchase_price'] = $this->getLastPurchasePrice($item['sku_id']);

            $item['diff_price'] = 0;
            $item['diff_price'] =  $item['last_purchase_price'] != '' ? sprintf('%.2f',($item['cost_price'] - $item['last_purchase_price'])) : '';//价格差值

            $item['available_quantity'] = $item['quantity']-$item['waiting_shipping_quantity'];
            //(仓库筛选问题）（后期需调整 仓库有好几种类型）
            /*if ($warehouse_id) {
                $item['available_quantity'] = $warehouseGoods->available_quantity($warehouse_id, $item['sku_id']);
            } else {
                foreach($warehouses as $warehouse){
                    $item['available_quantity'] += $warehouseGoods->available_quantity($warehouse['id'], $item['sku_id']);
                }
            }*/

            $item['seven_quantity'] = 0;//七天销售额
            $item['seven_amount'] = 0;//七天营业额
            $item['seven_cost'] = 0;//七天销售成本


            $item['thirty_quantity'] = 0;//30天销售额
            $item['thirty_amount'] = 0;//30天营业额
            $item['thirty_cost'] = 0;//30天销售成本

            $item['ninety_quantity'] = 0;//90天销售额
            $item['ninety_amount'] = 0;//90天营业额
            $item['ninety_cost'] = 0;//90天销售成本

            $ninety_info = isset($all_ninety_info[$item['sku_id']]) ? $all_ninety_info[$item['sku_id']] : array();
            foreach($ninety_info as $value){
                $value['sales_amount'] = sprintf('%.4f',floatval($value['sales_amount']));
                $value['total_cost'] = sprintf('%.4f',floatval($value['total_cost']));
                if($value['shipping_time']>=strtotime('-6 days')){
                    $item['seven_quantity'] += $value['sale_quantity'];//七天销售额
                    $item['seven_amount'] += $value['sales_amount'];//七天营业额
                    $item['seven_cost'] += $value['total_cost'];//七天销售成本
                }

                if($value['shipping_time']>=strtotime('-29 days')){
                    $item['thirty_quantity'] += $value['sale_quantity'];//30天销售额
                    $item['thirty_amount'] += $value['sales_amount'];//30天营业额
                    $item['thirty_cost'] += $value['total_cost'];//30天销售成本
                }
                $item['ninety_quantity'] += $value['sale_quantity'];//90天销售额
                $item['ninety_amount'] += $value['sales_amount'];//90天营业额
                $item['ninety_cost'] +=  $value['total_cost'];//90天销售成本
            }


            //七天周转天数
            $item['seven_turn_days'] = $item['seven_quantity'] == 0 ? '' : sprintf("%.1f", $item['available_quantity'] / $item['seven_quantity'] * 7);//周转天数

            //30天周转天数
            $item['thirty_turn_days'] = $item['thirty_quantity'] == 0 ? '' : sprintf("%.1f", $item['available_quantity'] / $item['thirty_quantity'] * 30);//天周转天数

            //90天周转天数
            $item['ninety_turn_days'] = $item['ninety_quantity'] == 0 ? '' : sprintf("%.1f", $item['available_quantity'] / $item['ninety_quantity'] * 90);//90天周转天数

            //库存金额
            $item['stock_amt'] = sprintf('%.2f',($item['available_quantity'] + $item['waiting_shipping_quantity'] + $item['instransit_quantity']) *  $item['cost_price']);
            $item['create_time'] = (isset($goodsInfo['create_time']) && $goodsInfo['create_time']) ? date('Y-m-d H:i:s', $goodsInfo['create_time']) : '';//导入时间
        }
        return  array_values($data);
    }

    /**
     * 获取最新一次采购价
     * @param array $sku_id
     * @return float
     */
    public function getLastPurchasePrice($sku_id)
    {
        $where = [];
        $where['sku_id'] = ['eq', $sku_id];
        $results =  $this->purchaseOrderDetailModel->field('price')->where($where)->order('create_time desc')->find();
        return empty($results) ? "":sprintf("%.2f", $results['price']);
    }

    /**
     * 查询总数
     * @param array $condition
     * @param array $join
     * @return int|string
     */
    protected function doCount(array $condition = [])
    {
        $total =  $this->warehouseGoods->where($condition)->group('sku_id')->count();
        return $total;
    }


    /**
     * @desc 获取字段
     */
    private function getField()
    {
        $fields = 'id, sku_id, sku, sum(quantity) as quantity, sum(waiting_shipping_quantity) as waiting_shipping_quantity,sum(instransit_quantity) as instransit_quantity';
        return $fields;
    }

    /**
     * 列表详情
     * @param $page
     * @param $pageSize
     * @param $params
     * @return array
     */
    public function lists($page, $pageSize, $params)
    {
        $where = [];
        $this->where($params, $where);
        $count = $this->doCount($where);
        $field = $this->getField();
        $data =$this->doSearch($field, $where, $params['warehouse_id'], $page, $pageSize);
        $result = [
            'data' => $data,
            'count' => $count,
            'page' => $page,
            'pageSize' => $pageSize
        ];
        return $result;
    }

    /**
     * 导出申请
     * @param $params
     * @return bool
     * @throws Exception
     * @throws \Exception
     */
    public function applyExport($params)
    {
        $userId = Common::getUserInfo()->toArray()['user_id'];
        $cache = Cache::handler();
        $lastApplyTime = $cache->hget('hash:export_stock_apply',$userId);
        if ($lastApplyTime && time() - $lastApplyTime < 5) {
            throw new JsonErrorException('请求过于频繁',400);
        } else {
            $cache->hset('hash:export_stock_apply',$userId,time());
        }
        $export_file_name  = $this->createExportFileName($params);
        try {
            $model = new ReportExportFiles();
            $data['applicant_id'] = $userId;
            $data['apply_time'] = time();
            $data['export_file_name'] = str_replace('.csv', '.zip', $export_file_name);
            $data['status'] = 0;
            $data['applicant_id'] = $userId;
            $model->allowField(true)->isUpdate(false)->save($data);
            $params['file_name'] = $export_file_name;
            $params['apply_id'] = $model->id;
            (new CommonQueuer(SaleStockExportQueue::class))->push($params);
            return true;
        } catch (\Exception $ex) {
            Db::rollback();
            throw new JsonErrorException('申请导出失败');
        }
    }


    /**
     * 创建导出文件名
     * @param int $user_id
     * @return string
     */
    protected function createExportFileName($params)
    {
        $warehouse_name = '';
        $category_name = '';
        $fileName = '销量及库存管理_';
        $goodsHelp = new GoodsHelp();
        if (isset($params['warehouse_id']) && intval($params['warehouse_id'])) {
            $warehouse_name = Cache::store('warehouse')->getWarehouseNameById($params['warehouse_id']);
            $fileName .='_'.$warehouse_name;
        }
        if (isset($params['category_id']) && $params['category_id']) {
            $category_name = $goodsHelp->mapCategory($params['category_id']);
            $fileName .= '_'.$category_name;
        }
        $fileName .= '_'.date("YmdHis").'.csv';
        return $fileName;
    }


    /**
     * @desc 导出scv
     */
    public function export($params)
    {
        set_time_limit(0);
        try {
            ini_set('memory_limit', '1024M');
            //验证时申请id和文件名不能为空
            $validate = new FileExportValidate();
            if (!$validate->scene('export')->check($params)) {
                throw new Exception($validate->getError());
            }
            $header  = $this->header;
            $aHeader = [];
            foreach ($header as $v) {
                $aHeader[] = $v['title'];
            }
            $fileName =  $params['file_name'];
            $downLoadDir = DS . 'download' . DS . 'sale_stock';
            $file = ROOT_PATH . 'public' . $downLoadDir;
            $filePath = $file . DS . $fileName;
            //无文件夹，创建文件夹
            if (!is_dir($file) && !mkdir($file, 0777, true)) {
                $result['message'] = '创建文件夹失败。';
                @unlink($filePath);
                return $result;
            }
            $fp = fopen($filePath, 'w+');
            fwrite($fp, "\xEF\xBB\xBF");
            fputcsv($fp, $aHeader);
            fclose($fp);

            $where = [];
            $this->where($params, $where);
            $where = is_null($where) ? [] : $where;
            $fields =  $this->getField();
            $count = $this->doCount($where);
            $pageSize = 1000;
            $loop     = ceil($count/$pageSize);
            $fp = fopen($filePath, 'a');
            //分批导出
            for ($i = 0; $i<$loop; $i++) {
                $lists = $this->doSearch($fields, $where, param($params, 'warehouse_id', 0), $i + 1, $pageSize);
                foreach ($lists as $key => $row) {
                    $rowContent = [];
                    foreach ($header as $h) {
                        $field = $h['key'];
                        $value = isset($row[$field]) ? $row[$field] : '';
                        $content = $value;
                        $rowContent[] = $content;
                    }
                    fputcsv($fp, $rowContent);
                }
            }
            fclose($fp);
            if (is_file($filePath)) {
                $fileName = str_replace('.csv', '', $fileName);
                $zipPath = $file . DS . $fileName . ".zip";
                $PHPZip = new PHPZip();
                $zipData = [
                    [
                        'name' => $fileName,
                        'path' => $filePath
                    ]
                ];
                $PHPZip->saveZip($zipData, $zipPath);
                @unlink($filePath);
                $applyRecord = ReportExportFiles::get($params['apply_id']);
                $applyRecord['exported_time'] = time();
                $applyRecord['download_url'] = $downLoadDir .DS. $fileName. ".zip";
                $applyRecord['status'] = 1;
                $applyRecord->allowField(true)->isUpdate(true)->save();
            }else {
                throw new Exception('文件写入失败');
            }
        } catch (Exception $ex) {
            $applyRecord = ReportExportFiles::get($params['apply_id']);
            $applyRecord['status'] = 2;
            $applyRecord['error_message'] = $ex->getMessage();
            $applyRecord->isUpdate(true)->save();
            Cache::handler()->hset(
                'hash:report_export:sale_stock',
                $params['apply_id'].'_'.time(),
                '申请id: '.$params['apply_id'].',导出失败:'.$ex->getMessage());
        }
    }

    /**
     * 导出数据至excel文件
     * @param array $params
     * @return bool
     * @throws Exception
     */
    public function export1(array $params)
    {
        /*$params['apply_id'] = 859;
        $params['file_name'] = '销量及库存管理_1_2018_05_28_20_23_15.xlsx';*/
        set_time_limit(0);
        try {
            //ini_set('memory_limit', '4096M');
            //验证时申请id和文件名不能为空
            $validate = new FileExportValidate();
            if (!$validate->scene('export')->check($params)) {
                throw new Exception($validate->getError());
            }

            $fileName = $params['file_name'];
            $downLoadDir = '/download/sale_stock/';
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
            $titleMap  = $this->colMap['title'];
            $lastCol   = 'AD';
            $dataMap   = $this->colMap['data'];
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
            $sheet->setAutoFilter('A1:'.$lastCol.'1');

            //统计需要导出的数据行
            $where = [];
            $this->where($params, $where);
            $where = is_null($where) ? [] : $where;
            $fields =  $this->getField();
            $count = $this->doCount($where);
            $pageSize = 1000;
            $loop     = ceil($count/$pageSize);
            //分批导出
            for ($i = 0; $i<$loop; $i++) {
                $data = $this->doSearch($fields,$where, param($params, 'warehouse_id', 0), $i+1, $pageSize);
                foreach ($data as $r) {
                    foreach ($dataMap as $field => $set){
                        $cell = $sheet->getCell($set['col']. $dataRowStartIndex);
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
            $writer = \PHPExcel_IOFactory::createWriter($excel,'Excel2007');
            $writer->save($fullName);
            if (is_file($fullName)) {
                $applyRecord = ReportExportFiles::get($params['apply_id']);
                $applyRecord['exported_time'] = time();
                $applyRecord['download_url'] = $downLoadDir.$fileName;
                $applyRecord['status'] = 1;
                $applyRecord->allowField(true)->isUpdate(true)->save();
            } else {
                throw new Exception('文件写入失败');
            }
        } catch (\Exception $ex) {
            $applyRecord = ReportExportFiles::get($params['apply_id']);
            $applyRecord['status'] = 2;
            $applyRecord['error_message'] = $ex->getMessage();
            $applyRecord->isUpdate(true)->save();
            Cache::handler()->hset(
                'hash:report_export',
                $params['apply_id'].'_'.time(),
                '申请id: '.$params['apply_id'].',导出失败:'.$ex->getMessage());
        }
    }
}