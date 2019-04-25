<?php
namespace app\report\service;

use app\common\cache\Cache;
use app\common\model\OrderDetail;
use app\common\model\OrderNote;
use app\common\model\OrderPackage;
use app\common\model\OrderSourceDetail;
use app\common\model\SupplierGoodsOffer;
use app\order\service\OrderService;
use think\Exception;
use think\Loader;
use app\common\model\Order;

Loader::import('phpExcel.PHPExcel', VENDOR_PATH);

/**
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/8/16
 * Time: 9:35
 */
class SalesStatement
{
    protected $PCardRate = [
        'amazon' => 0.006,
        'wish' => 0.005,
    ];
    protected $colMap = [
        'order' => [
            'title' => [
                'A' => ['title' => '平台订单号', 'width' => 30],
                'B' => ['title' => '发货追踪号', 'width' => 10],
                'C' => ['title' => '发货产品渠道SKU', 'width' => 10],
                'D' => ['title' => '发货产品自定义SKU', 'width' => 10],
                'E' => ['title' => '发货产品数量', 'width' => 10],
                'F' => ['title' => '支付运费', 'width' => 10],
                'G' => ['title' => '产品总金额', 'width' => 30],
                'H' => ['title' => '订单总金额', 'width' => 15],
                'I' => ['title' => '退款金额', 'width' => 15],
                'J' => ['title' => '促销折扣', 'width' => 15],
                'K' => ['title' => '订单使用币种', 'width' => 20],
                'L' => ['title' => '兑换人民币汇率', 'width' => 20],
                'M' => ['title' => '订单来源渠道', 'width' => 20],
                'N' => ['title' => '发货产品最新采购价', 'width' => 20],
                'O' => ['title' => '发货产品供货价', 'width' => 10],
                'P' => ['title' => '订单商品总成本', 'width' => 20],
                'Q' => ['title' => '发货包裹运费', 'width' => 10],
                'R' => ['title' => '订单系统重量', 'width' => 10],
                'S' => ['title' => '运输方式', 'width' => 10],
                'T' => ['title' => '发货仓库', 'width' => 10],
                'U' => ['title' => '发货时间', 'width' => 10],
                'V' => ['title' => '系统订单号', 'width' => 10],
                'W' => ['title' => '收货国家中文名', 'width' => 10],
                'X' => ['title' => '本地追踪号', 'width' => 10],
                'Y' => ['title' => '包裹号', 'width' => 10],
                'Z' => ['title' => '平台选择运输方式', 'width' => 10],
                'AA' => ['title' => '转单号', 'width' => 10],
                'AB' => ['title' => '是否支付', 'width' => 30],
                'AC' => ['title' => '订单备注', 'width' => 30],
                'AD' => ['title' => '订单发货状态', 'width' => 30],
                'AE' => ['title' => '新建订单原因', 'width' => 30],
                'AF' => ['title' => '订单来源渠道类型', 'width' => 30],
                'AG' => ['title' => '站点', 'width' => 30],
                'AH' => ['title' => '平台佣金', 'width' => 30]
            ],
            'data' => [
                'channel_order_number' => ['col' => 'A', 'type' => 'str'],
                'synchronize_tracking_number' => ['col' => 'B', 'type' => 'str'],
                'channel_sku' => ['col' => 'C', 'type' => 'str'],
                'system_sku' => ['col' => 'D', 'type' => 'str'],
                'sku_quantity' => ['col' => 'E', 'type' => 'str'],
                'channel_shipping_free' => ['col' => 'F', 'type' => 'str'],
                'goods_amount' => ['col' => 'G', 'type' => 'str'],
                'order_amount' => ['col' => 'H', 'type' => 'str'],
                'refund_amount' => ['col' => 'I', 'type' => 'str'],
                'discount' => ['col' => 'J', 'type' => 'str'],
                'currency_code' => ['col' => 'K', 'type' => 'str'],
                'rate' => ['col' => 'L', 'type' => 'str'],
                'channel_account_id' => ['col' => 'M', 'type' => 'str'],
                'purchase_price' => ['col' => 'N', 'type' => 'str'],
                'gong_price' => ['col' => 'O', 'type' => 'str'],
                'order_goods_cost' => ['col' => 'P', 'type' => 'str'],
                'package_shipping_fee' => ['col' => 'Q', 'type' => 'str'],
                'package_weight' => ['col' => 'R', 'type' => 'str'],
                'shipping_name' => ['col' => 'S', 'type' => 'str'],
                'warehouse_id' => ['col' => 'T', 'type' => 'str'],
                'shipping_time' => ['col' => 'U', 'type' => 'str'],
                'order_number' => ['col' => 'V', 'type' => 'str'],
                'country_cn_name' => ['col' => 'W', 'type' => 'str'],
                'shipping_number' => ['col' => 'X', 'type' => 'str'],
                'number' => ['col' => 'Y', 'type' => 'str'],
                'buyer_selected_logistics' => ['col' => 'Z', 'type' => 'str'],
                'related_order_id' => ['col' => 'AA', 'type' => 'str'],
                'pay_time' => ['col' => 'AB', 'type' => 'str'],
                'note' => ['col' => 'AC', 'type' => 'str'],
                'deliver_goods_status' => ['col' => 'AD', 'type' => 'str'],
                'new_order_reason' => ['col' => 'AE', 'type' => 'str'],
                'channel_id' => ['col' => 'AF', 'type' => 'str'],
                'site_code' => ['col' => 'AG', 'type' => 'str'],
                'channel_cost' => ['col' => 'AH', 'type' => 'str']
            ]
        ],
    ];

    /**
     * 导出数据至excel文件
     * @param array $condition
     * @return bool
     * @throws Exception
     */
    public function export(array $condition = [])
    {
        try {
            //ini_set('memory_limit', '4096M');
            $fileName = '销售统计(' . date('Y-m-d', time()) . ').xlsx';
            $downLoadDir = '/download/sales/';
            $saveDir = ROOT_PATH . 'public' . $downLoadDir;
            if (!is_dir($saveDir) && !mkdir($saveDir, 0777, true)) {
                throw new Exception('导出目录创建失败');
            }
            $fullName = $saveDir . $fileName;
            //创建excel对象
            $excelObj = new \PHPExcel();
            $excelObj->setActiveSheetIndex(0);
            $sheet = $excelObj->getActiveSheet();
            $titleRowIndex = 1;
            $dataRowStartIndex = 2;
            $titleMap = $this->colMap['order']['title'];
            $lastCol = 'FF';
            $dataMap = $this->colMap['order']['data'];
            //设置表头和表头样式
            foreach ($titleMap as $col => $set) {
                $sheet->getColumnDimension($col)->setWidth($set['width']);
                $sheet->getCell($col . $titleRowIndex)->setValue($set['title']);
                $sheet->getStyle($col . $titleRowIndex)
                    ->getFill()
                    ->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('E8811C');
                $sheet->getStyle($col . $titleRowIndex)
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
            }
            $sheet->setAutoFilter('A1:' . $lastCol . '1');

            //统计需要导出的数据行
            $orderList = $this->doSearch($condition);
            //分批导出
            $data = $this->assemblyData($orderList);
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
            $writer = \PHPExcel_IOFactory::createWriter($excelObj, 'Excel2007');
            $writer->save($fullName);
        } catch (\Exception $ex) {
            throw new Exception($ex->getMessage() . $ex->getFile() . $ex->getLine());
        }
        return true;
    }

    /**
     * 组装查询返回数据
     * @param $records
     * @return array
     */
    protected function assemblyData($records)
    {
        $newOrderData = [];
        $countryList = Cache::store('country')->getCountry();
        $orderSourceDetailModel = new OrderSourceDetail();
        $orderDetailModel = new OrderDetail();
        $orderService = new OrderService();
        $orderPackageModel = new OrderPackage();
        $supplierOfferModel = new SupplierGoodsOffer();
        $noteModel = new OrderNote();
        foreach ($records as $key => $record) {
            $value = $record->toArray();
            $package_ids = [];
            //查询来源表
            $sourceData = $orderSourceDetailModel->field('channel_sku,channel_sku_quantity')->where(['order_id' => $value['id']])->select();
            $value['channel_sku'] = '';
            $value['sku_quantity'] = '';
            if (!empty($sourceData)) {
                foreach ($sourceData as $s => $source) {
                    $value['channel_sku'] .= $source['channel_sku'] . ',';
                }
                $value['channel_sku'] = rtrim($value['channel_sku'], ',');
            }
            $value['order_goods_cost'] = $record['cost'];
            //系统sku
            $value['system_sku'] = '';
            $value['purchase_price'] = '';
            $detailData = $orderDetailModel->field('sku,sku_id,sku_quantity,package_id')->where(['order_id' => $value['id']])->select();
            foreach ($detailData as $d => $detail) {
                $value['system_sku'] .= $detail['sku'] . ',';
                $value['sku_quantity'] .= $detail['sku_quantity'] . ',';
                //获取最新的采购报价
                $offer = $supplierOfferModel->field('price')->where(['sku_id' => $detail['sku_id']])->find();
                $purchasePrice = !empty($offer) ? $offer['price'] : 0;
                $value['purchase_price'] .= $purchasePrice . ',';
                if (!in_array($detail['package_id'], $package_ids)) {
                    array_push($package_ids, $detail['package_id']);
                }
            }
            $value['system_sku'] = rtrim($value['system_sku'], ',');
            $value['sku_quantity'] = rtrim($value['sku_quantity'], ',');
            $value['purchase_price'] = rtrim($value['purchase_price'], ',');
            //时间转换
            $value['shipping_time'] = date('Y-m-d H:i:s', $value['shipping_time']);
            $value['pay_time'] = $value['pay_time'] > 0 ? '是' : '否';
            $value['deliver_goods_status'] = '全部发货';
            $value['new_order_reason'] = '';
            $value['channel_account_id'] = $orderService->getAccountName($value['channel_id'],
                $value['channel_account_id']);
            $value['channel_id'] = !empty($value['channel_id']) ? Cache::store('channel')->getChannelName($value['channel_id']) : '';
            $value['country_cn_name'] = isset($countryList[$value['country_code']]['country_cn_name']) ? !empty($countryList[$value['country_code']]['country_cn_name']) ? $countryList[$value['country_code']]['country_cn_name'] : '' : '';

            $value['refund_amount'] = 0;
            //包裹信息
            $packageList = $orderPackageModel->field('number,shipping_fee,package_weight,shipping_number,process_code,warehouse_id,shipping_id,shipping_name')->where('id',
                'in',
                $package_ids)->select();
            $value['package_shipping_fee'] = '';
            $value['package_weight'] = '';
            $value['number'] = '';
            $value['warehouse_id'] = '';
            $value['shipping_number'] = '';
            $value['shipping_name'] = '';
            foreach ($packageList as $p => $package) {
                $value['package_weight'] .= $package['package_weight'] . ',';
                $value['number'] .= $package['number'] . ',';
                $value['package_shipping_fee'] .= $package['shipping_fee'] . ',';
                $shippingNumber = empty($package['shipping_number']) ? $package['process_code'] : $package['shipping_number'];
                $value['shipping_number'] .= $shippingNumber . ',';
                //仓库名称
                $warehouseInfo = Cache::store('warehouse')->getWarehouseNameById($package['warehouse_id']);
                $value['warehouse_id'] .= $warehouseInfo . ',';
                $value['shipping_name'] .= $package['shipping_name'] . ',';
            }
            $value['shipping_name'] = rtrim($value['shipping_name'], ',');
            $value['warehouse_id'] = rtrim($value['warehouse_id'], ',');
            $value['shipping_number'] = rtrim($value['shipping_number'], ',');
            $value['package_shipping_fee'] = rtrim($value['package_shipping_fee'], ',');
            $value['package_weight'] = rtrim($value['package_weight'], ',');
            $value['number'] = rtrim($value['number'], ',');
            $value['gong_price'] = '';   //供货价
            //订单备注
            $value['note'] = '';
            $noteList = $noteModel->field('note')->where(['order_id' => $value['id']])->select();
            foreach ($noteList as $n => $note) {
                $value['note'] .= $note['note'] . ',';
            }
            $value['note'] = rtrim($value['note'], ',');
            array_push($newOrderData, $value);
        }
        return $newOrderData;
    }

    /**
     * 搜索
     * @param array $condition
     * @return false|\PDOStatement|string|\think\Collection
     */
    protected function doSearch(array $condition = [])
    {
        $field = 'o.id,o.channel_order_number,o.synchronize_tracking_number,p.shipping_fee,o.goods_amount,o.order_amount,o.discount,
        o.currency_code,o.country_code,o.rate,o.channel_account_id,p.package_weight,p.shipping_name,p.warehouse_id,o.shipping_time,o.order_number,
        p.shipping_number,p.process_code,p.number,o.buyer_selected_logistics,o.related_order_id,o.pay_time,o.channel_id,o.channel_cost,o.site_code,o.channel_shipping_free,o.cost';
        $condition['date_b'] = strtotime('2018-01-01 00:00:00');
        $condition['date_e'] = strtotime('2018-01-31 23:59:59');
        $where['o.channel_id'] = ['eq',4];
        $where['o.shipping_time'] = ['between', [$condition['date_b'], $condition['date_e']]];
        $orderModel = new Order;
        $orderList = $orderModel->alias('o')->field($field)->join('order_package p', 'o.id = p.order_id',
            'left')->where($where)->order('order_time desc')->select();
        return $orderList;
    }
}