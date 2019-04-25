<?php
namespace app\report\service;

use app\common\cache\Cache;
use app\common\exception\JsonErrorException;
use app\common\service\Common;
use app\common\service\CommonQueuer;
use app\report\model\ReportExportFiles;
use app\report\queue\SaleRefundExportQueue;
use think\Db;
use think\Exception;
use think\Loader;
use app\common\model\AfterWastageGoods;
use app\common\model\AfterRedeliverDetail as AfterRedeliverDetailModel;
use app\common\model\OrderSourceDetail as OrderSourceDetailModel;
use app\report\validate\FileExportValidate;

Loader::import('phpExcel.PHPExcel', VENDOR_PATH);

/**
 * Created by PhpStorm.
 * User: laiyongfeng
 * Date: 2017/10/19
 * Time: 10:13
 */
class SaleRefundService
{
    protected $colMap = [
        'title' => [
            'A' => ['title' => 'SKU', 'width' => 10],
            'B' => ['title' => '货品名称', 'width' => 30],
            'C' => ['title' => '退款总额', 'width' => 15],
            'D' => ['title' => '退款（买家未收到货）', 'width' => 30],
            'E' => ['title' => '退款（买家反映物品质量有问题）', 'width' => 35],
            'F' => ['title' => '退款（买家反映物品与描述不符)	', 'width' => 35],
            'G' => ['title' => '退款（其他）', 'width' => 20],
            'H' => ['title' => '补发总数量', 'width' => 20],
            'I' => ['title' => '补发货（买家未收到货）', 'width' => 35],
            'J' => ['title' => '补发货（买家反映质量有问题）', 'width' => 35],
            'K' => ['title' => '补发货（买家反映物品与描述不符）', 'width' => 40],
            'L' => ['title' => '补发货（其他）', 'width' => 20],
        ],
        'data' => [
            'sku' =>                    ['col' => 'A', 'type' => 'str'],
            'goods_name' =>             ['col' => 'B', 'type' => 'str'],
            'total_refund' =>           ['col' => 'C', 'type' => 'numeric'],
            'refund_not_received' =>    ['col' => 'D', 'type' => 'numeric'],
            'refund_has_problem' =>     ['col' => 'E', 'type' => 'numeric'],
            'refund_not_match' =>       ['col' => 'F', 'type' => 'numeric'],
            'refund_other' =>           ['col' => 'G', 'type' => 'numeric'],
            'total_add_num' =>          ['col' => 'H', 'type' => 'numeric'],
            'num_not_received' =>       ['col' => 'I', 'type' => 'numeric'],
            'num_has_problem' =>        ['col' => 'J', 'type' => 'numeric'],
            'num_not_match_num' =>      ['col' => 'K', 'type' => 'numeric'],
            'num_other' =>              ['col' => 'L', 'type' => 'numeric'],
        ]
    ];
    private $afterRedeliverDetailModel;
    private $afterWastageGoodsModel;
    private $orderSourceDetailModel;

    public function __construct()
    {
        if (is_null($this->afterRedeliverDetailModel)) {
            $this->afterRedeliverDetailModel = new AfterRedeliverDetailModel();
        }
        if (is_null($this->afterWastageGoodsModel)) {
            $this->afterWastageGoodsModel = new AfterWastageGoods();
        }
        if (is_null($this->orderSourceDetailModel)) {
            $this->orderSourceDetailModel = new OrderSourceDetailModel();
        }
    }

    /**
     * 查询条件
     * @param $params
     * @param $where
     * @return \think\response\Json
     */
    private function where($params, &$where, &$order_join=0, &$package_join=0)
    {
        //仓库筛选
        if (isset($params['warehouse_id']) && !empty($params['warehouse_id'])) {
            $order_join = 1;
            $package_join = 1;
            $where['p.warehouse_id'] = ['eq', $params['warehouse_id']];
        }
        //sku_id筛选
        if (isset($params['sku_ids']) && !empty($params['sku_ids'])) {
            if (is_array($params['sku_ids'])) {
                $where['g.sku_id'] = ['in', $params['sku_ids']];
            } else {
                $where['g.sku_id'] = strpos($params['sku_ids'], ',') !== false ? ['in', explode(',', $params['sku_ids'])] : $params['sku_ids'];
            }
        }
        $params['date_b'] = isset($params['date_b']) ? $params['date_b'] : 0;
        $params['date_e'] = isset($params['date_e']) ? $params['date_e'] : 0;
        $condition = timeCondition($params['date_b'], $params['date_e']);
        if (!empty($condition)) {
            $order_join = 1;
            $where['o.pay_time'] = $condition;
        }
        //币种筛选
        if (isset($params['currency_code']) && !empty($params['currency_code'])) {
            $where['s.refund_currency'] = ['eq', $params['currency_code']];
        }
    }

    /**
     * 获取字段信息
     * @return string
     */
    protected function field()
    {
        $field =
            'g.sku_id,' .           //sku_id
            'g.sku,' .               //sku
            'g.quantity,' .            //问题数量
            'g.after_sale_service_id,' .  //问题数量
            's.order_id,' .              //订单id
            's.channel_id,' .      //渠道id
            'e.order_source_detail_id,' .  //原订单来源id
            'r.code';          //原因
        return $field;
    }

    /**
     * 关联数据
     * @return array
     */
    protected function join($order_join, $package_join)
    {
        $join[] = ['after_sale_service s', 's.id = g.after_sale_service_id', 'left'];
        $join[] = ['after_service_reason r', 'r.id = s.reason', 'left'];
        $join[] = ['order_detail e', 'e.order_id = s.order_id and e.sku_id = g.sku_id', 'left'];

        if ($order_join) { //存在付款时间筛选
            $join[] = ['order o', 'r.id = s.order_id', 'left'];
        }
        if ($package_join) {//存在仓库筛选
            $join[] = ['order_package p', 'p.id = e.package_id', 'left'];
        }
        return $join;
    }

    /**
     * 查询总数
     * @param array $condition
     * @param array $join
     * @return int|string
     */
    protected function doCount(array $condition = [], array $join = [])
    {
        return $this->afterWastageGoodsModel->alias('g')->join($join)->where($condition)->count();
    }

    /**
     * 搜索
     * @param $field
     * @param array $condition
     * @param array $join
     * @param int $page
     * @param int $pageSize
     * @return false|\PDOStatement|string|\think\Collection
     */
    protected function doSearch($field, array $condition = [], array $join = [], $page = 1, $pageSize = 10)
    {
        return $this->afterWastageGoodsModel->alias('g')->field($field)->join($join)->where($condition)->order('g.create_time desc')->page($page,
            $pageSize)->select();
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
        $order_join = 0;
        $package_join = 0;
        $this->where($params, $where, $order_join, $package_join);
        $field = $this->field();
        $join = $this->join($order_join, $package_join);
        $count = $this->doCount($where, $join);
        $data = $this->assemblyData($this->doSearch($field, $where, $join, $page, $pageSize));
        $result = [
            'data' => $data,
            'count' => $count,
            'page' => $page,
            'pageSize' => $pageSize
        ];
        return $result;
    }
    /**
     * 组装查询返回数据
     * @param $records
     * @return array
     */
    protected function assemblyData($records)
    {
        $data = [];
        foreach ($records as $key => $record) {
            $temp = [];
            $temp['sku_id'] = $record['sku_id'];
            $temp['sku'] = $record['sku'];

            $temp['total_refund'] = 0;
            $temp['total_add_num'] = 0;
            //补发货
            $detail = $this->afterRedeliverDetailModel->field('quantity, type')->where(['sku_id' => $record['sku_id']])->where(['after_sale_service_id' => $record['after_sale_service_id']])->find();
            if (!empty($detail) && $detail['type'] == 2) {//补发货
                $is_refund = 0; //0补发货 1退款
                $temp['total_add_num'] = $record['quantity'];
            } elseif(empty($detail) || (!empty($detail) && $detail['type'] == 3)) {//退款
                $is_refund = 1; //0补发货 1退款
                $quantity = empty($detail) ? $record['quantity'] : $detail['quantity'];//退货数量
                $record['source'] = $this->orderSourceDetailModel->field('channel_sku_price')->where(['id' => $record['order_source_detail_id']])->find();
                $temp['total_refund'] = empty($record['source']) ? '': sprintf('%.2f', $record['source']['channel_sku_price']*$quantity);
            } else {
                break;
            }
            $skuInfo = Cache::store('goods')->getSkuInfo($record['sku_id']);
            $temp['goods_name'] = (!empty($skuInfo) && $skuInfo['spu_name']) ? $skuInfo['spu_name'] : '';//名称

            $temp['refund_not_received'] = 0;//(退款)卖家未收到货
            $temp['refund_has_problem'] = 0;//(退款)物品质量问题
            $temp['refund_not_match'] = 0;//(退款)与描述不符
            $temp['refund_other'] = 0;//(退款)与描述不符
            $temp['num_not_received'] = 0;//补发货（卖家未收到货)
            $temp['num_has_problem'] = 0;//补发货（物品质量问题)
            $temp['num_not_match'] = 0;//补发货（与描述不符）
            $temp['num_other'] = 0;//补发货（其他）

            switch ($record['code']) {
                case '买家未收到货':
                    $is_refund ? $temp['refund_not_received'] = $temp['total_refund'] : $temp['num_not_received'] = $record['quantity'];
                    break;
                case '买家反映物品质量有问题':
                    $is_refund ? $temp['refund_has_problem'] = $temp['total_refund'] : $temp['num_has_problem'] = $record['quantity'];
                    break;
                case '买家反映物品与描述不符':
                    $is_refund ? $temp['refund_not_match'] = $temp['total_refund'] : $temp['num_not_match'] = $record['quantity'];
                    break;
                default:
                    $is_refund ? $temp['refund_other'] = $temp['total_refund'] : $temp['num_other'] = $record  ['quantity'];
            }
            $data[] = $temp;
        }
        return $data;
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
        $lastApplyTime = $cache->hget('hash:export_refund_apply',$userId);
        if($lastApplyTime && time() - $lastApplyTime < 5){
            throw new JsonErrorException('请求过于频繁',400);
        }else{
            $cache->hset('hash:export_refund_apply',$userId,time());
        }
        try{
            $model = new ReportExportFiles();
            $data['applicant_id'] = $userId;
            $data['apply_time'] = time();
            $data['export_file_name'] = $this->createExportFileName($params);
            $data['status'] = 0;
            $data['applicant_id'] = $userId;
            $model->allowField(true)->isUpdate(false)->save($data);
            $params['file_name'] = $data['export_file_name'];
            $params['apply_id'] = $model->id;
            (new CommonQueuer(SaleRefundExportQueue::class))->push($params);
            return true;
        } catch (\Exception $ex) {
            Db::rollback();
            throw new JsonErrorException('申请导出失败');
        }
    }

    /**
     * 创建导出文件名
     * @param $userId
     * @return string
     */
    protected function createExportFileName($params)
    {
        $warehouse_name = '';
        $fileName = '销退报表';
        if (isset($params['warehouse_id']) && intval($params['warehouse_id'])) {
            $warehouse_name = Cache::store('warehouse')->getWarehouseNameById($params['warehouse_id']);
            $fileName .= '_'.$warehouse_name;
        }
        if (isset($params['currency_code']) && $params['currency_code']) {
            $currency_code = '币种: '.$params['currency_code'];
            $fileName .= '_'.$currency_code;
        }
        $fileName .='_'. date("Y_m_d_H_i_s").'.xlsx';
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
        try {
            //ini_set('memory_limit', '4096M');

            //验证申请id以及文件名不能为空
            $validate = new FileExportValidate();
            if(!$validate->scene('export')->check($params)){
                throw new Exception($validate->getError());
            }
            $fileName = $params['file_name'];
            $downLoadDir = '/download/sale_refund/';
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
            $lastCol   = 'L';
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
            $order_join = 0;
            $package_join = 0;
            $this->where($params, $where, $order_join, $package_join);
            $field = $this->field();
            $join = $this->join($order_join, $package_join);
            $count = $this->doCount($where, $join);
            $pageSize = 10000;
            $loop     = ceil($count/$pageSize);
            //分批导出
            for ($i = 0; $i<$loop; $i++) {
                $data = $this->assemblyData($this->doSearch($field, $where, $join ,$i+1, $pageSize));
                foreach ($data as $r) {
                    foreach ($dataMap as $field => $set) {
                        $cell = $sheet->getCell($set['col']. $dataRowStartIndex);
                        switch ($set['type']) {
                            case 'time_stamp':
                                if (empty($r[$field])) {
                                    $cell->setValue('');
                                } else {
                                    $cell->setValue(date('Y-m-d',$r[$field]));
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

}