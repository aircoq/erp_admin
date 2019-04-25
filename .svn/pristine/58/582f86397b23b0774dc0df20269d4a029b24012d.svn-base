<?php
namespace app\report\service;

use app\common\cache\Cache;
use app\common\exception\JsonErrorException;
use app\common\model\OrderDetail;
use app\common\service\Common;
use app\common\service\CommonQueuer;
use app\report\model\ReportExportFiles;
use app\report\queue\GoodsAnalysisExportQueue;
use think\Db;
use think\Exception;
use think\Loader;
use app\common\model\PurchaseOrderDetail as PurchaseOrderDetailModel;
use app\common\model\Packing as PackingModel;
use app\goods\service\GoodsSkuAlias;
use app\goods\service\GoodsHelp;
use app\report\validate\FileExportValidate;
use app\warehouse\service\WarehouseGoods;
use app\common\model\WarehouseGoods as WarehouseGoodsModel;
use app\warehouse\service\Warehouse;
use app\common\traits\Export;

Loader::import('phpExcel.PHPExcel', VENDOR_PATH);

/**
 * Created by PhpStorm.
 * User: laiyongfeng
 * Date: 2017/10/16
 * Time: 10:09
 */
class GoodsAnalysisService
{
    use Export;

    private $orderDetailModel;
    private $purchaseOrderDetailModel;
    private $goodsHelp;
    private $packingModel;
    protected $colMap = [
//            'title' => [
//                'A' => ['title' => '产品图片', 'width' => 20],
//                'B' => ['title' => 'SKU', 'width' => 10],
//                'C' => ['title' => 'SKU别名', 'width' => 10],
//                'D' => ['title' => '开发人员', 'width' => 10],
//                'E' => ['title' => '产品名称', 'width' => 10],
//                'F' => ['title' => '重量（g）', 'width' => 10],
//                'G' => ['title' => '品类', 'width' => 15],
//                'H' => ['title' => '上架时间', 'width' => 15],
//                'I' => ['title' => '订单数', 'width' => 10],
//                'J' => ['title' => '销量', 'width' => 10],
//                'K' => ['title' => '上期销量', 'width' => 10],
//                'L' => ['title' => '销量环比', 'width' => 10],
//                'M' => ['title' => '销量占比', 'width' => 10],
//                'N' => ['title' => '销售额', 'width' => 10],
//                'O' => ['title' => '上期销售额', 'width' => 15],
//                'P' => ['title' => '销售额环比', 'width' => 15],
//                'Q' => ['title' => '销售额占比', 'width' => 15],
//                'R' => ['title' => '平均成本', 'width' => 10],
//                'S' => ['title' => '最新采购单价', 'width' => 15],
//                'T' => ['title' => '平均运费', 'width' => 10],
//                'U' => ['title' => '平均综合成本', 'width' => 15],
//                'V' => ['title' => '利润', 'width' => 10],
//                'W' => ['title' => '平均利润', 'width' => 10],
//                'X' => ['title' => '利润环比', 'width' => 10],
//                'Y' => ['title' => '利润占比', 'width' => 10],
//                'Z' => ['title' => '利润率', 'width' => 10],
//                'AA' => ['title' => '可用库存', 'width' => 10],
//                'AB' => ['title' => '待发仓库', 'width' => 10],
//                'AC' => ['title' => '在途仓库', 'width' => 10],
//                'AD' => ['title' => '故障品保存', 'width' => 15],
//                'AE' => ['title' => '库存金额', 'width' => 10],
//                'AF' => ['title' => '周转天数', 'width' => 10],
//            ],
//            'data' => [
//                'sku_thumb' =>                  ['col' => 'A', 'type' => 'time'],
//                'sku' =>                        ['col' => 'B', 'type' => 'str'],
//                'sku_alias' =>                  ['col' => 'C', 'type' => 'str'],
//                'developer_name' =>             ['col' => 'D', 'type' => 'int'],
//                'goods_name' =>                 ['col' => 'E', 'type' => 'str'],
//                'weight' =>                     ['col' => 'F', 'type' => 'str'],
//                'category' =>                   ['col' => 'G', 'type' => 'str'],
//                'publish_time' =>               ['col' => 'H', 'type' => 'str'],
//                'order_num' =>                  ['col' => 'I', 'type' => 'str'],
//                'sale_quantity' =>              ['col' => 'J', 'type' => 'str'],
//                'last_sale_quantity' =>         ['col' => 'K', 'type' => 'str'],
//                'sale_ring_ratio' =>            ['col' => 'L', 'type' => 'str'],
//                'sale_ratio' =>                 ['col' => 'M', 'type' => 'str'],
//                'sales_amount' =>               ['col' => 'N', 'type' => 'str'],
//                'last_sales_amount' =>          ['col' => 'O', 'type' => 'str'],
//                'amount_ring_ratio' =>          ['col' => 'P', 'type' => 'str'],
//                'amount_ratio' =>               ['col' => 'Q', 'type' => 'str'],
//                'average_cost' =>               ['col' => 'R', 'type' => 'str'],
//                'last_purchase_price' =>        ['col' => 'S', 'type' => 'str'],
//                'average_shipping_fee' =>       ['col' => 'T', 'type' => 'str'],
//                'average_mult_cost' =>          ['col' => 'U', 'type' => 'str'],
//                'profit' =>                     ['col' => 'V', 'type' => 'str'],
//                'average_profit' =>             ['col' => 'W', 'type' => 'str'],
//                'profit_ring_ratio' =>          ['col' => 'X', 'type' => 'str'],
//                'profit_ratio' =>               ['col' => 'Y', 'type' => 'str'],
//                'profit_rate' =>                ['col' => 'Z', 'type' => 'str'],
//                'available_quantity' =>         ['col' => 'AA', 'type' => 'str'],
//                'waiting_shipping_quantity' =>  ['col' => 'AB', 'type' => 'str'],
//                'instransit_quantity' =>        ['col' => 'AC', 'type' => 'str'],
//                'defects_quantity' =>           ['col' => 'AD', 'type' => 'str'],
//                'stock_amt' =>                  ['col' => 'AE', 'type' => 'str'],
//                'turn_days' =>                  ['col' => 'AF', 'type' => 'str'],
//            ]
        'title' => [
            'A' => ['title' => '产品图片', 'width' => 20],
            'B' => ['title' => 'SKU', 'width' => 10],
            'C' => ['title' => 'SKU别名', 'width' => 10],
            'D' => ['title' => '开发人员', 'width' => 10],
            'E' => ['title' => '产品名称', 'width' => 10],
            'F' => ['title' => '重量（g）', 'width' => 10],
            'G' => ['title' => '上架时间', 'width' => 15],
            'H' => ['title' => '订单数', 'width' => 10],
            'I' => ['title' => '销量', 'width' => 10],
            'J' => ['title' => '上期销量', 'width' => 10],
            'K' => ['title' => '销售额', 'width' => 10],
            'L' => ['title' => '上期销售额', 'width' => 15],
            'M' => ['title' => '平均成本', 'width' => 10],
            'N' => ['title' => '最新采购单价', 'width' => 15],
            'O' => ['title' => '平均运费', 'width' => 10],
            'P' => ['title' => '平均综合成本', 'width' => 15],
            'Q' => ['title' => '利润', 'width' => 10],
            'R' => ['title' => '平均利润', 'width' => 10],
            'S' => ['title' => '利润率', 'width' => 10],
            'T' => ['title' => '可用库存', 'width' => 10],
            'U' => ['title' => '待发仓库', 'width' => 10],
            'V' => ['title' => '在途仓库', 'width' => 10],
            'W' => ['title' => '故障品保存', 'width' => 15],
            'X' => ['title' => '库存金额', 'width' => 10],
            'Y' => ['title' => '周转天数', 'width' => 10],
        ],
        'data' => [
            'sku_thumb' =>                  ['col' => 'A', 'type' => 'time'],
            'sku' =>                        ['col' => 'B', 'type' => 'str'],
            'sku_alias' =>                  ['col' => 'C', 'type' => 'str'],
            'developer_name' =>             ['col' => 'D', 'type' => 'int'],
            'goods_name' =>                 ['col' => 'E', 'type' => 'str'],
            'weight' =>                     ['col' => 'F', 'type' => 'str'],
            'publish_time' =>               ['col' => 'G', 'type' => 'str'],
            'order_num' =>                  ['col' => 'H', 'type' => 'str'],
            'sale_quantity' =>              ['col' => 'I', 'type' => 'str'],
            'last_sale_quantity' =>         ['col' => 'J', 'type' => 'str'],
            'sales_amount' =>               ['col' => 'K', 'type' => 'str'],
            'last_sales_amount' =>          ['col' => 'L', 'type' => 'str'],
            'average_cost' =>               ['col' => 'M', 'type' => 'str'],
            'last_purchase_price' =>        ['col' => 'N', 'type' => 'str'],
            'average_shipping_fee' =>       ['col' => 'O', 'type' => 'str'],
            'average_mult_cost' =>          ['col' => 'P', 'type' => 'str'],
            'profit' =>                     ['col' => 'Q', 'type' => 'str'],
            'average_profit' =>             ['col' => 'R', 'type' => 'str'],
            'profit_rate' =>                ['col' => 'S', 'type' => 'str'],
            'available_quantity' =>         ['col' => 'T', 'type' => 'str'],
            'waiting_shipping_quantity' =>  ['col' => 'U', 'type' => 'str'],
            'instransit_quantity' =>        ['col' => 'V', 'type' => 'str'],
            'defects_quantity' =>           ['col' => 'W', 'type' => 'str'],
            'stock_amt' =>                  ['col' => 'X', 'type' => 'str'],
            'turn_days' =>                  ['col' => 'Y', 'type' => 'str'],
        ]

    ];

    public function __construct()
    {
        if (is_null($this->orderDetailModel)) {
            $this->orderDetailModel = new OrderDetail();
        }
        if (is_null($this->purchaseOrderDetailModel)) {
            $this->purchaseOrderDetailModel = new PurchaseOrderDetailModel();
        }
        if (is_null($this->goodsHelp)) {
            $this->goodsHelp = new GoodsHelp();
        }
        if (is_null($this->packingModel)) {
            $this->packingModel = new PackingModel();
        }
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
        $lastApplyTime = $cache->hget('hash:export_goods_apply',$userId);
        if ($lastApplyTime && time() - $lastApplyTime < 5) {
            throw new JsonErrorException('请求过于频繁',400);
        } else {
            $cache->hset('hash:export_goods_apply', $userId,time());
        }
        try {
            $model = new ReportExportFiles();
            $data['applicant_id'] = $userId;
            $data['apply_time'] = time();
            $data['export_file_name'] = $this->createExportFileName($params,$params['date_b'], $params['date_e']);
            $data['status'] = 0;
            $data['applicant_id'] = $userId;
            $model->allowField(true)->isUpdate(false)->save($data);
            $params['file_name'] = $data['export_file_name'];
            $params['apply_id'] = $model->id;
            (new CommonQueuer(GoodsAnalysisExportQueue::class))->push($params);
            return true;
        } catch (\Exception $ex) {
            throw new JsonErrorException('申请导出失败');
        }
    }

    /**
     * 创建导出文件名
     *
     * @param array $params
     * @param [string] $date_b
     * @param [string] $date_e
     * @return string
     */
    protected function createExportFileName(array $params,$date_b, $date_e)
    {
        $lastID  = (new ReportExportFiles())->order('id desc')->value('id');
        $fileName = '商品销售分析报表'.($lastID+1).'(';
        $goodsHelp = new GoodsHelp();
        if (isset($params['channel_id']) && $params['channel_id']) {
            $channelName = Cache::store('channel')->getChannelName($params['channel_id']);
            $fileName .= $channelName;
        }
        if (isset($params['site_code']) && $params['site_code']) {
            $fileName .= '_' . $params['site_code'];
        }
        if (isset($params['warehouse_id']) && $params['warehouse_id']) {
            $warehouse_name = Cache::store('warehouse')->getWarehouseNameById($params['warehouse_id']);
            $fileName .= '_'.$warehouse_name;
        }
        if (isset($params['category_id']) && $params['category_id']) {
            $categoryName = $goodsHelp->mapCategory($params['category_id']);
            $fileName .= '_' . $categoryName;
        }
        if (isset($params['currency_code']) && $params['currency_code']) {
            $fileName .= '_' . '币种: ' . $params['currency_code'];
        }
        $fileName .= $date_b.'~'.$date_e.')'.'.xlsx';
        return $fileName;
    }

    /**
     * 导出数据至excel文件
     * @param array $params
     * @return bool
     * @throws Exception
     */
    public function export(array $params)
    {
        set_time_limit(0);
        try {
            ini_set('memory_limit', '1024M');
            $validate = new FileExportValidate();
            if (!$validate->scene('export')->check($params)) {
                throw new Exception($validate->getError());
            }
            $fileName = $params['file_name'];
            $downLoadDir = '/download/goods_analysis/';
            $saveDir = ROOT_PATH . 'public' . $downLoadDir;
            if (!is_dir($saveDir) && !mkdir($saveDir, 0777, true)) {
                throw new Exception('导出目录创建失败');
            }
            $fullName = $saveDir . $fileName;
            //创建excel对象
            $writer = new \XLSXWriter();
            $titleMap = $this->colMap['title'];
            $title = [];
            $titleData = $this->colMap['data'];
            foreach ($titleData as $k => $v) {
                array_push($title, $k);
            }
            $titleOrderData = [];
            foreach ($titleMap as $t => $tt){
                $titleOrderData[$tt['title']] = 'string';
            }
//            $excel = new \PHPExcel();
//            $excel->setActiveSheetIndex(0);
//            $sheet = $excel->getActiveSheet();
//            $titleRowIndex = 1;
//            $dataRowStartIndex = 2;
            $titleMap  = [];
//            $lastCol   = 'AE';
//            $dataMap   = $this->colMap['data'];
//            $titleMap   = $this->colMap['title'];
            //设置表头和表头样式
//            foreach ($titleMap as $col => $set) {
//                $sheet->getColumnDimension($col)->setWidth($set['width']);
//                $sheet->getCell($col . $titleRowIndex)->setValue($set['title']);
//                $sheet->getStyle($col . $titleRowIndex)
//                    ->getFill()
//                    ->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)
//                    ->getStartColor()->setRGB('33CCCC');
//                $sheet->getStyle($col . $titleRowIndex)
//                    ->getBorders()
//                    ->getAllBorders()
//                    ->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
//            }
//            $sheet->setAutoFilter('A1:'.$lastCol.'1');
            //统计需要导出的数据行
            $where = [];
            $join = $this->join();
            $this->where($params, $where);
            $count = $this->doCount($where, $join);
            $pageSize = 10000;
            $loop     = ceil($count/$pageSize);
            $writer->writeSheetHeader('Sheet1', $titleOrderData);
            //分批导出
            for ($i = 0;$i<$loop;$i++) {
                $data = $this->doSearch($where, $join, $i+1, $pageSize, $params, $title);
                foreach ($data as $r){
//                    foreach ($dataMap as $field => $set){
//                        $cell = $sheet->getCell($set['col']. $dataRowStartIndex);
//                        switch ($set['type']){
//                            case 'time_stamp':
//                                if (empty($r[$field])) {
//                                    $cell->setValue('');
//                                } else {
//                                    $cell->setValue(date('Y-m-d',$r[$field]));
//                                }
//                                break;
//                            case 'numeric':
//                                $cell->setDataType(\PHPExcel_Cell_DataType::TYPE_NUMERIC);
//                                if (empty($r[$field])) {
//                                    $cell->setValue(0);
//                                } else {
//                                    $cell->setValue($r[$field]);
//                                }
//                                break;
//                            default:
//                                if (is_null($r[$field])) {
//                                    $r[$field] = '';
//                                }
//                                $cell->setValue($r[$field]);
//                        }
//                    }
//                    $dataRowStartIndex++;
                    $writer->writeSheetRow('Sheet1', $r);
                }
                unset($data);
            }
            $writer->writeToFile($fullName);
//            $writer = \PHPExcel_IOFactory::createWriter($excel,'Excel2007');
//            $writer->save($fullName);
            if (is_file($fullName)) {
                $applyRecord = ReportExportFiles::get($params['apply_id']);
                $applyRecord['exported_time'] = time();
                $applyRecord['download_url'] = $downLoadDir.$fileName;
                $applyRecord['status'] = 1;
                $applyRecord->isUpdate()->save();
            } else {
                throw new Exception('文件写入失败');
            }
        } catch (\Exception $ex) {
            $applyRecord = ReportExportFiles::get($params['apply_id']);
            $applyRecord['status'] = 2;
            $applyRecord['error_message'] = $ex->getMessage();
            $applyRecord->isUpdate()->save();
            Cache::handler()->hset(
                'hash:report_export',
                $params['apply_id'].'_'.time(),
                '申请id: '.$params['apply_id'].',导出失败:'.$ex->getMessage());
        }
    }

    /**
     * 列表详情
     * @param int $page
     * @param int $pageSize
     * @param array $params
     * @return array
     */
    public function lists($page, $pageSize, $params)
    {
        $where = [];
        $this->where($params, $where);
        $join = $this->join();
        $count = $this->doCount($where, $join);
        $data = $this->doSearch($where, $join, $page, $pageSize, $params);
        $result = [
            'data' => $data,
            'count' => $count,
            'page' => $page,
            'pageSize' => $pageSize
        ];
        return $result;
    }

    /**
     * 查询条件
     * @param array $params
     * @param array $where
     * @return \think\response\Json
     */
    private function where($params, &$where)
    {

        if (isset($params['channel_id']) && !empty($params['channel_id'])) {
            $where['o.channel_id'] = ['eq', $params['channel_id']];
        }
        if (isset($params['site_code']) && !empty($params['site_code'])) {
            $where['o.site_code'] = ['eq', $params['site_code']];
        }
            if (isset($params['account_id']) && !empty($params['account_id'])) {
            $where['o.channel_account_id'] = ['eq', $params['account_id']];
        }
        if (isset($params['warehouse_id']) && !empty($params['warehouse_id'])) {
            $where['p.warehouse_id'] = ['eq', $params['warehouse_id']];
        }

        //分类筛选
        if (isset($params['category_id']) && $params['category_id']) {
            $goods_ids =  (new GoodsHelp())->getGoodsIdByCategoryId($params['category_id']);
            $where['d.goods_id'] = ['in', $goods_ids];
        }

        //开发员
       if (isset($params['developer_id']) && !empty($params['developer_id'])) {
           $where['g.developer_id'] = ['eq', $params['developer_id']];
       }

        //sku_id
        if (isset($params['sku_id']) && !empty($params['sku_id'])) {
            $where['d.sku_id'] = ['eq', $params['sku_id']];
        }
        //sku_id筛选
        if (isset($params['sku_ids']) && $params['sku_ids']) {
            $sku_ids = explode(',', $params['sku_ids']);
            $where['d.sku_id'] = ['in', $sku_ids];

        }

        if (isset($params['date_e']) && $params['date_e'] && isset($params['date_b']) && $params['date_b'] &&  isset($params['snDate']) && $params['snDate']) {
            $condition = timeCondition($params['date_b'], $params['date_e']);
            if (!is_array($condition)) {
                return json(['message' => '日期格式错误'], 400);
            }
            switch ($params['snDate']) {
                case 'shipping_time':
                    if (!empty($condition)) {
                        $where['p.shipping_time'] = $condition;
                    }
                    break;
                case 'pay_time':
                    if (!empty($condition)) {
                        $where['o.pay_time'] = $condition;
                    }
                    break;
                default:
                    return json(['message' => '日期格式错误'], 400);
            }
        } else {
            return json(['message' => '日期参数必填'], 400);
        }
        //币种筛选(后期完善)
        if (isset($params['currency_code']) && !empty($params['currency_code'])) {
            $where['o.currency_code'] = ['eq', $params['currency_code']];
        }
    }

    /**
     * 查询上期条件
     * @param array $params
     * @param array $where
     * @return boolean
     */
    private function lastWhere($params, &$where)
    {
        //获取上一个查询周期
        $diff = strtotime($params['date_e'])-strtotime($params['date_b']);
        $params['date_e'] = date("Y-m-d",(strtotime($params['date_e']) - $diff-86400));
        $params['date_b'] = date('Y-m-d', (strtotime($params['date_b']) - $diff-86400));
        $this->where($params, $where);

    }

    /**
     * 查询上期条件
     * @param array $params
     * @param array $where
     * @return boolean
     */
    private function totalWhere($params, &$where)
    {
        //获取上一个查询周期
        $diff = strtotime($params['date_e'])-strtotime($params['date_b']);
        $params['date_b'] = date('Y-m-d', (strtotime($params['date_b']) - $diff-86400));
        $this->where($params, $where);

    }

    /**
     * 获取间隔天数
     * @param $params
     * @return boolean|int
     */
    private function getIntervalDays($params)
    {
        //获取上一个查询周期
        if (isset($params['date_e']) && $params['date_e'] && isset($params['date_b']) && $params['date_b'] &&  isset($params['snDate']) && $params['snDate']) {
            $diff_days = (strtotime($params['date_e'])-strtotime($params['date_b']))/86400+1;
            return $diff_days;
        }
        return false;
    }

    private function getSkuIds($data){
        $sku_ids = [];
        foreach($data as $item){
            $sku_ids[] = $item['sku_id'];
        }
        return $sku_ids;
    }

    /**
     * @desc 获取库存信息
     * @param array $sku_ids
     * @param int $warehouse_id
     * @return array
     */
    private function batchGetStock($sku_ids, $warehouse_id)
    {
        $where['sku_id'] = ['in', $sku_ids];
        if($warehouse_id){
            $where['warehouse_id'] = ['=', $warehouse_id];
        }
        $fields = 'id, sku_id, thirdparty_goods_sku, sku,sum(quantity) as quantity, sum(waiting_shipping_quantity) as waiting_shipping_quantity,sum(instransit_quantity) as instransit_quantity,sum(defects_quantity) as defects_quantity';
        $data = (new WarehouseGoodsModel())->field($fields)->where($where)->group('sku_id')->select();
        $result = [];
        foreach($data as $item){
            $item['available_quantity'] = $item['quantity']-$item['waiting_shipping_quantity'];//可用库存
            $result[$item['sku_id']] = $item;
        }
        return $result;
    }

    /**
     * 获取排序字段名
     * @param $params
     * @return string
     */
    public function getOrder($params)
    {
        $sort_type = param($params,'sort_type');
        $sort_val = param($params,'sort_val');

        if(isset($sort_val) && $sort_val){
            $sort_val == 1 ? $sort_val = 'asc' : $sort_val = 'desc';
        }
        switch ($sort_type){
            case 'order_num': //订单数
                $order_by = 'order_num '.$sort_val;
                break;
            case 'sale_quantity': //销量
                $order_by = 'sale_quantity '.$sort_val;
                break;
            case 'sales_amount': //销售额
                $order_by = 'sales_amount ' .$sort_val;
                break;
            default:
                $order_by = 'd.sku_id asc';
        }
        return $order_by;

    }
    /**
     * 搜索
     * @param array $condition
     * @param array $join
     * @param int $page
     * @param int $pageSize
     * @param $title
     * @return array
     */
    protected function doSearch(array $condition = [], $join, $page = 1, $pageSize = 10, $params, $title = [])
    {
        $field = $this->field();
        $order = $this->getOrder($params);
        $results =  $this->orderDetailModel->alias('d')->field($field)->join($join)->where($condition)->group('d.sku_id')->order($order)->page($page, $pageSize)->select();
        $data = [];
        if(!empty($results)) {
            $total_goods = [];//包裹货品数(计算运费)
            $diff_days = $this->getIntervalDays($params);//查询间隔天数（为计算周转天数）

            $total_info = $this->getTotalInfo($condition, $join);//获取总的产品销量
            $sku_ids = $this->getSkuIds($results);
            $this_last_shipping = [];
            $total_shipping = $this->getTotalShippingFee($params, $join, $total_goods , $this_last_shipping);//获取本期总运费（计算总利润）
            $this_last_shipping = $this_last_shipping ? $this_last_shipping : $this->shippingInfo($params, $sku_ids);//上期和本期运费信息（后面需要组装）

            $last_where = [];
            $this->lastWhere($params, $last_where);
            $total_last_info = $this->getLastInfo($last_where, $sku_ids);//上期销售信息
            $last_purchase_price = $this->getLastPurchasePrice($sku_ids);//最新采购价
            //总利润(总销量-总的综合成本（总成本+总的头程费用+总的运费+总的平台费用+总的包装费用 ？）计算利润占比)
            $total_profit = $total_info['total_sales_amount'] - $total_info['total_sku_cost'] - $total_info['total_first_fee'] - $total_shipping['total_shipping_fee'] - $total_info['total_channel_cost']+$total_shipping['total_packing_fee'];

            $warehouse_id = param($params, 'warehouse_id', 0);
            //获取仓库库存信息
            $stock_info = $this->batchGetStock($sku_ids, $warehouse_id);

            $is_fba = 0;
            if($warehouse_id){
                $warehouses = Cache::store('warehouse')->getWarehouse($warehouse_id);
                $is_fba = (!empty($warehouses) && $warehouses['type'] == Warehouse::TYPE_FBA) ? 1 :0;
            }

            foreach ($results as $key => &$record) {
                $goods_info = Cache::store('goods')->getGoodsInfo($record['goods_id']);
                $record['developer_id'] = 0;
                $record['category_id'] = 0;
                $record['first_fee'] = 0;
                $record['publish_time'] = 0;
                if ($goods_info) {
                    $record['developer_id'] = $goods_info['developer_id'];
                    $record['category_id'] = $goods_info['category_id'];
                    $record['first_fee'] = $goods_info['first_fee'];
                    $record['publish_time'] = $goods_info['publish_time'];
                }

                $temp = [];
                $temp['publish_time'] = $record['publish_time'] ? date('Y-m-d', $record['publish_time']) : '';
                $temp['is_fba'] = $is_fba;
                $temp['sku_id'] = $record['sku_id'];
                $temp['sku'] = $record['sku'];
                $temp['site_code'] = $record['site_code'];
                $alias = GoodsSkuAlias::getAliasBySkuId($record['sku_id']);
                $temp['sku_alias'] = !empty($alias) ? implode(',', $alias) : '';//sku别名

                $sku_info = Cache::store('goods')->getSkuInfo($record['sku_id']);
                $temp['sku_thumb'] = (!empty($sku_info) && $sku_info['thumb']) ? $sku_info['thumb'] : ''; //sku图片
                $temp['goods_name'] = (!empty($sku_info) && $sku_info['spu_name']) ? $sku_info['spu_name'] : '';//商品名称
                $temp['weight'] = (!empty($sku_info) && $sku_info['weight']) ? $sku_info['weight'] : '';//重量

                $userInfo = Cache::store('user')->getOneUser($record['developer_id']);
                //$temp['developer_name'] = $record['developer_id']; //开发者
                $temp['developer_name'] = '';
                if (!empty($userInfo)) {
                    $temp['developer_name'] = $userInfo['realname']; //开发者
                }

                //$temp['category'] = $this->goodsHelp->mapCategory($record['category_id']); //分类

                $temp['order_num'] = $record['order_num'];//订单数量

                $temp['sales_amount'] = sprintf("%.2f",  $record['sales_amount']);//销量
                $temp['sale_quantity'] = $record['sale_quantity'];

                //上期销售信息
                $last_info = isset($total_last_info[$record['sku_id']]) ? $total_last_info[$record['sku_id']] : [];

                //获取上期销售数据
                $condition['sku_id'] = $record['sku_id'];
                $last_where['sku_id'] = $record['sku_id'];
                $last_shipping_data = isset($this_last_shipping['last'][$record['sku_id']]) ? $this_last_shipping['last'][$record['sku_id']] : [];
                $last_shipping_info = $this->getShippingfee($last_shipping_data, $temp['weight'], $total_goods); //上一期平均运费、平均包装费用组合
                //上期销售量
                $temp['last_sale_quantity'] = (isset($last_info['sku_quantity']) && $last_info['sku_quantity']) ? $last_info['sku_quantity'] : 0.00;
                //销售环比
                //$temp['sale_ring_ratio'] = $temp['last_sale_quantity'] != 0 ? sprintf("%.2f", ($record['sale_quantity'] - $temp['last_sale_quantity']) / $temp['last_sale_quantity']*100).'%' : '';
                //销售占比
                //$temp['sale_ratio'] = (!empty($total_info) && $total_info['total_sale_quantity'] != 0) ? sprintf("%.2f", $record['sale_quantity'] / $total_info['total_sale_quantity']*100).'%' : '';
                //上期销售额
                $temp['last_sales_amount'] = (isset($last_info['sales_amount']) && $last_info['sales_amount']) ? sprintf("%.2f", $last_info['sales_amount']) : 0.00;
                //销售额环比
                //$temp['amount_ring_ratio'] = $temp['last_sales_amount'] != 0 ? sprintf("%.2f", ($temp['sales_amount'] - $temp['last_sales_amount']) / $temp['last_sales_amount']*100).'%' : '';
                //销售额占比
                //$temp['amount_ratio'] = (!empty($total_info) && $total_info['total_sales_amount'] != 0) ? sprintf("%.2f", $temp['sales_amount'] / $total_info['total_sales_amount']*100).'%' : '';

                //平均成本(订单详情里的成本 按数量加权)
                $temp['average_cost'] = $record['sale_quantity'] != 0 ? sprintf("%.2f", $record['total_sku_cost'] / $record['sale_quantity']) : '';

                //最新一次采购价
                $temp['last_purchase_price'] = isset($last_purchase_price[$record['sku_id']]) ? $last_purchase_price[$record['sku_id']] : '';

//                $this_shipping_data = isset($total_shipping_info['this'][$record['sku_id']]) ? $total_shipping_info['this'][$record['sku_id']] : [];
                $this_shipping_data = [];

                $shipping_info = $this->getShippingfee($this_shipping_data, $temp['weight'], $total_goods);
                $temp['average_shipping_fee'] = $shipping_info['average_shipping_fee'];//sku平均运费

                //平均平台费用
                $average_channel_cost = $record['sale_quantity'] != 0 ? sprintf("%.2f", $record['total_sku_channel_cost'] / $record['sale_quantity']) : '';

                //平均综合成本(平均成本+头程费+平均运费+平均平台费+平均包装费用 ？)
                $temp['average_mult_cost'] = $temp['average_cost'] + $record['first_fee'] + $temp['average_shipping_fee'] + $average_channel_cost + $shipping_info['average_packing_fee'];

                $temp['average_mult_cost'] = sprintf("%.2f", $temp['average_mult_cost']);

                $cost = $temp['average_mult_cost'] * $record['sale_quantity']; //总综合成本（平均综合成本*数量？）

                $temp['profit'] = sprintf("%.2f", $temp['sales_amount'] - $cost);//利润
                $temp['average_profit'] = sprintf("%.2f", $temp['profit'] / $record['sale_quantity']);//平均利润
                $temp['profit_rate'] = $cost == 0 ? '' : sprintf("%.2f", $temp['profit'] / $cost*100).'%'; //利润率

                //上一期平均成本(总成本加权) 后面需要修改
                $last_average_cost = $temp['last_sale_quantity'] != 0 ? sprintf("%.2f", $last_info['total_sku_cost'] / $temp['last_sale_quantity']) : 0.00;
                //上一期平均平台分费用
                $last_average_channel_cost = $temp['last_sale_quantity'] != 0 ? sprintf("%.2f", $last_info['total_sku_channel_cost'] / $temp['last_sale_quantity']) : 0.00;
                //上一期平均综合成本（产品平均成本+头程费用+平均运费+平均平台费用+平均包装费用）
                $last_mult_cost = $last_average_cost + $record['first_fee'] + (!empty($last_shipping_info)?$last_shipping_info['average_shipping_fee']:0) + $last_average_channel_cost + (!empty($last_shipping_info)?$last_shipping_info['average_packing_fee']:0);

                //上期利润（上一期销售额-上次平均综合成本*上期销量）
                $temp['last_profit'] = sprintf("%.2f", $temp['last_sales_amount'] - $last_mult_cost * $temp['last_sale_quantity']);
                //利润环比
                //$temp['profit_ring_ratio'] = $temp['last_profit'] != 0 ? sprintf("%.2f", ($temp['profit'] - $temp['last_profit']) / $temp['last_profit']*100).'%' : '';
                //利润占比（sku利润/本期总利润）
                //$temp['profit_ratio'] = $total_profit != 0 ? sprintf("%.2f", $temp['profit'] / $total_profit*100).'%' : '';

                $temp['available_quantity'] = $stock_info[$temp['sku_id']]['available_quantity'] ?? 0;
                $temp['waiting_shipping_quantity'] = $stock_info[$temp['sku_id']]['waiting_shipping_quantity'] ?? 0;
                $temp['instransit_quantity'] = $stock_info[$temp['sku_id']]['instransit_quantity'] ?? 0;
                $temp['defects_quantity'] = $stock_info[$temp['sku_id']]['defects_quantity'] ?? 0;
                $temp['thirdparty_goods_sku'] = $stock_info[$temp['sku_id']]['thirdparty_goods_sku'] ?? '';

                //库存金额
                $temp['stock_amt'] = ($temp['available_quantity'] + $temp['waiting_shipping_quantity'] + $temp['instransit_quantity'] + $temp['defects_quantity']) * $temp['average_mult_cost'];
                $temp['stock_amt'] =  sprintf("%.2f", $temp['stock_amt']);
                //周转天数
                $temp['turn_days'] = !$diff_days ? '' : sprintf("%.1f", $temp['available_quantity'] / $record['sale_quantity'] * $diff_days);
                if (!empty($title)) {
                    $tt = [];
                    foreach ($title as $k => $v) {
                        $tt[$v] = $temp[$v];
                    }
                    $data[] = $tt;
                } else {
                    $data[] = $temp;
                }

            }
        }
        return $data;
    }

    /**
     * 查询总数
     * @param array $condition
     * @param array $join
     * @return int|string
     */
    protected function doCount(array $condition = [], array $join = [])
    {
        $data = $this->orderDetailModel->alias('d')->join($join)->where($condition)->group('d.sku_id')->count();
        return  $data;
    }

    /*
     *
     * 获取字段信息
     * @return string
     */
    protected function field()
    {
        $field =
            'd.sku_id,' .     //sku_id
            'd.sku,' .        //sku
            'd.goods_id,' .        //sku
            'o.site_code,' .        //站点
            'count(distinct(o.id)) as order_num,' . //订单数
            'sum(d.sku_quantity) as sale_quantity,'.       //发货数量（销售数量）
            'sum(d.sku_cost*d.sku_quantity) as total_sku_cost,'.       //成本
            'sum(d.sku_cost*d.sku_quantity/o.cost*o.channel_cost) as total_sku_channel_cost,'.   //平台费用（总的）
            'sum((d.sku_price*d.sku_quantity/o.goods_amount)*o.order_amount) as sales_amount ';   //销售额
        return $field;
    }

    /**
     * 关联数据
     * @return array
     */
    protected function join()
    {
        $join[] = ['order o', 'o.id = d.order_id', 'left'];
        $join[] = ['order_package p', 'p.id = d.package_id', 'left'];
        $join[] = ['goods g', 'g.id = d.goods_id', 'left'];
        return $join;
    }

    /**
     * 获取上期销量
     * @param array $where
     * @return array|false|\PDOStatement|string|Model
     */
    protected function getLastInfo($where, $sku_ids)
    {
        $where['d.sku_id'] = ['in', $sku_ids];
        $field =
            'd.sku_id, '.  //发货数量（销售数量）
            'sum(sku_quantity) as sku_quantity, '.  //发货数量（销售数量）
            'sum(d.sku_cost*d.sku_quantity/o.cost*o.channel_cost) as total_sku_channel_cost,'.       //平台费用（总的）
            'sum(d.sku_cost*d.sku_quantity) as total_sku_cost,'.       //成本
            'sum((d.sku_price*d.sku_quantity/o.goods_amount)*o.order_amount) as sales_amount '; //销售额

        $data =  $this->orderDetailModel
            ->alias('d')
            ->field($field)
            ->join($this->join())
            ->where($where)
            ->group('d.sku_id')
            ->select();
        $results = [];
        foreach($data as $item){
            $results[$item['sku_id']] = $item;
        }
        return $results;
    }

    /**
     * 获取上期销量
     * @param array $params
     * @param array $sku_ids
     * @return array
     */
    private function shippingInfo($params, $sku_ids)
    {

        $this->totalWhere($params, $where);

        $where['d.sku_id'] = ['in', $sku_ids];
        $lists = $this->orderDetailModel
            ->field('o.id, d.sku_id, p.shipping_time, p.shipping_fee, p.package_weight, p.packing_id, p.package_fee, d.sku_quantity, d.package_id')
            ->alias('d')
            ->join($this->join())
            ->where($where)
            ->select();
        $results['last'] = [];
        $results['this'] = [];
        $results['this_total'] = [];
        foreach($lists as $item){
            if($item['shipping_time']<$params['date_b']){
                $results['last'][$item['sku_id']][] = $item;
            }else{
                $results['this'][$item['sku_id']][] = $item;
                $results['this_total'][] = $item;
            }
        }
        return $results;
    }

    /**
     * 获取总的的销售数据
     * @param array $where
     * @param array $join
     * @return array
     */
    protected function getTotalInfo($where, $join)
    {
        $field =
            'sum(d.sku_quantity) as total_sale_quantity,' .  //总的销量
            'sum((d.sku_price*d.sku_quantity/o.goods_amount)*o.order_amount) as total_sales_amount ,'. //总的销售额
            'sum(d.sku_cost*d.sku_quantity) as total_sku_cost, '.    //总的成本
            'sum(d.sku_cost*d.sku_quantity/o.cost*o.channel_cost) as total_channel_cost, '.   //总的平台费用
            'sum(g.first_fee*d.sku_quantity) as total_first_fee '   ; //总的头程费用
        $results =  $this->orderDetailModel->alias('d')->field($field)->join($join)->where($where)->find();
        return $results;
    }

    /**
     * 获取最新一次采购价
     * @param array $sku_id
     * @return float
     */
    public function getLastPurchasePrice($sku_ids)
    {
        $where = [];
        $where['sku_id'] = ['in', $sku_ids];
        $data =  $this->purchaseOrderDetailModel->field('sku_id, price')->where($where)->order('create_time desc')->group('sku_id')->select();
        $results = [];
        foreach($data as $item){
            $results[$item['sku_id']] = sprintf("%.2f", $item['price']);
        }
        return $results;
    }

     /**
     * 获取sku运费(平均)
     * @param array $where
     * @param int $weight
     * @param array $total_goods
     * @return array
     */
    private function getShippingfee($lists, $weight, &$total_goods)
    {
        $total_sku_quantity = 0; //总的数量
        $total_packing_fee = 0; //总的包装费用
        
        if (!empty($lists)) {
            $total_fee = 0; //总运费
            foreach ($lists as $item) {
                $total_sku_quantity += $item['sku_quantity'];
                //订单有包裹
                if (!empty($item['shipping_time']) && !empty($item['package_id'])) {
                    if ($item['package_weight']>0) {
                        $sku_weight = $weight*$item['sku_quantity'];//订单货品重量（数量*单个sku重量）
                        //包裹数量
                        if(!isset($total_goods[$item['package_id']])){
                            $total_goods[$item['package_id']] = $this->getTotalGoods($item['package_id']);
                        }

                        //获取包裹包装重量
                        $packing_weight = $item['packing_id']!=0 ? $this->getPackingWeight($item['packing_id']) : 0;

                        //sku平摊包装重量
                        $sku_packping_weight = $total_goods[$item['package_id']]!=0 ? sprintf("%.2f", $packing_weight/$total_goods[$item['package_id']]*$item['sku_quantity']) : 0;
                        $sku_weight = $sku_packping_weight + $sku_weight;

                        //（包裹邮费 * 包裹订单货品重量 / 包裹重量)
                        $total_fee += sprintf("%.2f", $item['shipping_fee']*$sku_weight/$item['package_weight']);
                    } elseif ($item['package_weight'] == 0){
                        if(!isset($total_goods[$item['package_id']])) {
                            $total_goods[$item['package_id']] = $this->getTotalGoods($item['package_id']);
                        } elseif ($total_goods[$item['package_id']] > 0) {
                            //（ 包裹邮费 / 包裹中货品总数） * 订单货品数量
                            $total_fee += sprintf("%.4f", $item['shipping_fee']/$total_goods[$item['package_id']]*$item['sku_quantity']);
                        }
                    }
                    //货品包装费用（sku数量/包裹总数*包裹包装费用？）
                    $total_packing_fee += $total_goods[$item['package_id']]!=0 ?  sprintf("%.2f", ($item['sku_quantity']/$total_goods[$item['package_id']]*$item['package_fee'])) : 0;
                }

            }
        }
        //sku平均运费
        $data['average_shipping_fee'] = $total_sku_quantity==0 ? 0 : sprintf("%.2f", $total_fee/$total_sku_quantity);
        //sku平均包装费用（为计算综合成本）
        $data['average_packing_fee'] = $total_packing_fee==0 ? 0 : sprintf("%.2f", $total_packing_fee/$total_sku_quantity);

        return $data;
    }

    /**
     * 获取包裹总的货品数
     * @param int $package_id
     * @return int
     */
    private function getTotalGoods($package_id)
    {
        $result = $this->orderDetailModel->field('sum(sku_quantity) as total_quantity')->where('package_id', '=', $package_id)->find();
        return empty($result) ? 0 : $result['total_quantity'];
    }

    /**
     * 获取包裹包装重量
     * @param int $package_id
     * @return int
     */
    private function getPackingWeight($package_id)
    {
        $result = $this->packingModel->field('weight')->where('id', 'eq', $package_id)->find();
        return empty($result) ? 0 : $result['weight'];
    }

    /**
     * 获取本期总的运费
     * @param array $params
     * @param array $total_goods
     * @return int
     */
    private function getTotalShippingFee($params, $join, &$total_goods, &$shipping_data)
    {
        $where = [];
        $this->where($params, $where);
        $results['total_shipping_fee'] = 0;//总的邮费
        $results['total_packing_fee'] = 0; //总的包装费用
        //已发货,有包裹
        if(!isset($where['p.shipping_time'])){
            $where['p.shipping_time'] = ['<>', 0];
        }
        $where['d.package_id'] = ['<>', 0];

        if(!isset($where['d.sku_id'])){
            $field = 'sum(p.shipping_fee) as total_shipping_fee, p.packing_id, sum(p.package_fee) as total_packing_fee, d.package_id';
            $data =  $this->orderDetailModel
                ->alias('d')
                ->field($field)
                ->join($join)
                ->where($where)
                ->find();
            $results['total_shipping_fee'] = $results ? $results['total_shipping_fee'] : 0;//总的邮费
            $results['total_packing_fee'] = $results ? $results['total_packing_fee'] : 0; //总的包装费用
        }else {
            $sku_ids = explode(',', $params['sku_ids']);
            $shipping_data = $this->shippingInfo($params, $sku_ids);
            $lists  = $shipping_data['this_total'];
            if (!empty($lists)) {
                foreach ($results as $item) {
                    if ($item['package_weight'] !=0 ) {
                        //包裹数量
                        if (!isset($total_goods[$item['package_id']])) {
                            $total_goods[$item['package_id']] = $this->getTotalGoods($item['package_id']);
                        }
                        //获取包裹包装重量
                        $packing_weight = $item['packing_id']!=0 ? $this->getPackingWeight($item['packing_id']) : 0;

                        //货品包装重量（包装重量/包裹数量 * 订单包裹数量 ？）
                        $packing_weight = $total_goods[$item['package_id']] ? sprintf("%.2f",  $packing_weight/$total_goods[$item['package_id']]*$item['sku_quantity']) : 0;

                        //包裹订单货品重量（订单货品数量 * 货品重量+货品包装重量）
                        $item['weight'] =$item['weight'] + $packing_weight + $item['weight'];

                        //（包裹邮费 * 包裹订单货品重量 / 包裹重量)
                        $results['total_shipping_fee'] += sprintf("%.2f", $item['shipping_fee']*$item['weight']/$item['package_weight']);
                    } else {
                        //包裹数量
                        if (!isset($total_goods[$item['package_id']])) {
                            $total_goods[$item['package_id']] = $this->getTotalGoods($item['package_id']);
                        } elseif ($total_goods[$item['package_id']]>0) {
                            //（ 包裹邮费 / 包裹中货品总数） * 订单货品数量
                            $results['total_shipping_fee'] += sprintf("%.4f", $item['shipping_fee']/$total_goods[$item['package_id']]*$item['sku_quantity']);
                        }
                    }
                    //货品包装费用（sku数量/包裹总数*包裹包装费用？）
                    $results['total_packing_fee'] += $total_goods[$item['package_id']]!=0 ?  sprintf("%.2f", ($item['sku_quantity']/$total_goods[$item['package_id']]*$item['package_fee'])) : 0;
                }
            }
        }

        return $results;
    }
}