<?php

namespace app\report\service;

use app\common\model\ReportSkuByInventoryAgeDetail;
use app\common\model\User;
use app\warehouse\service\Warehouse;
use think\Db;
use think\Exception;
use think\Loader;
use app\common\model\ReportSkuByInventoryAge;
use app\report\model\ReportExportFiles;
use app\common\service\CommonQueuer;
use app\common\traits\Export;
use app\common\cache\Cache;
use app\common\service\Common;
use app\common\model\WarehouseGoods;
use app\warehouse\service\WarehouseConfig;
use app\warehouse\service\StockingAdviceService;
use app\common\model\LogExportDownloadFiles;
use app\internalletter\service\InternalLetterService;

Loader::import('phpExcel.PHPExcel', VENDOR_PATH);

/**
 * Created by PhpStorm.
 * User: laiyongfeng
 * Date: 2019/03/29
 * Time: 19:17
 */
class OverAgeService
{
    use Export;

    protected $model = null;
    protected $where = [];
    protected $colMap = [
        'SKU' => 'string',
        '商品名称' => 'string',
        '数量' => 'string',
        '备货计划单号' => 'string',
        '备货申请人' => 'string',
        '库龄' => 'string'
    ];

    public function __construct()
    {
        if (is_null($this->model)) {
            $this->model = new ReportSkuByInventoryAge();
        }
    }

    /**
     * @desc 查询条件
     * @param array $params
     */
    public function where($params)
    {
        if (param($params, 'warehouse_id')) {
            $this->where['warehouse_id'] = $params['warehouse_id'];
        }

        if (param($params, 'warehouse_id')) {
            $this->where['warehouse_id'] = $params['warehouse_id'];
        }
        $date_from = param($params, 'date_from');
        $date_to = param($params, 'date_to');
        if ($date_from || $date_to) {
            $start_time = $date_from ? strtotime($params['date_from']) : 0;
            $end_time = $date_to ? strtotime($params['date_to']) : 0;
            if ($start_time && $end_time) {
                $this->where['dateline'] = [['>=', $start_time], ['<=', $end_time]];
            } else {
                if ($start_time) {
                    $this->where['dateline'] = ['>=', $start_time];
                }
                if ($end_time) {
                    $this->where['dateline'] = ['<=', $end_time];
                }
            }
        }
    }


    /**
     * @desc 获取总数
     */
    public function getCount()
    {
        return $this->model->where($this->where)->count();

    }

    /**
     * @desc 获取超库龄列表
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function index($page = 1, $pageSize = 20)
    {
        $lists = $this->model->where($this->where)->page($page, $pageSize)->select();
        foreach ($lists as &$item) {
            $item['date'] = date('Y-m-d', $item['dateline']);
        }
        return $lists;
    }

    /**
     * @desc 获取需要生成超库龄报表仓库
     * @return array
     */
    public function getWarehouseIds()
    {
        $warehouses = (new Warehouse())->getWarehouseIdByConfig('warehouse_create_over_age_report');
        return array_column($warehouses, 'id');
    }

    /**
     * @desc 计算库龄(后期考虑做个公用的方法)
     * @param array $old
     * @param array $add
     * @return array
     */
    public function getAgeTime($old, $add)
    {
        return ($add['time'] - $old['time']) * $add['qty'] / ($add['qty'] + $old['qty']) + $old['time'];
    }

    /**
     * @desc 根据时间戳计算库龄
     * @param int $time
     * @return int
     */
    public function getAgeByTime($time)
    {
        return $time ? ceil((time() - $time) / 86400) : 0;
    }

    /**
     * @desc 获取备货申请库龄详情
     * @param  int $warehouse_id
     * @param  int $sku_id
     * @return array
     */
    public function getAgeDetail($warehouse_id, $sku_id)
    {
        $stockingAdviceService = new StockingAdviceService();
        $apply_lists = $stockingAdviceService->purchaseStorage($warehouse_id, $sku_id);
        $lists = [];
        foreach ($apply_lists as $apply_id => $item) {
            $lists[$item['apply_list_id']][] = $item;
        }
        $data = [];
        foreach ($lists as $apply_id => $value) {
            $age_time = 0;
            $use_qty = 0;
            //算备货申请的平均库龄
            $in_time = array_column($value, 'in_time');
            array_multisort($in_time, SORT_ASC, $value);
            foreach ($value as $v) {
                $age_time = $this->getAgeTime(['time' => $age_time, 'qty' => $use_qty], ['time' => $v['in_time'], 'qty' => $v['in_qty']]);
                $use_qty += $v['in_qty'];
            }
            //计算库龄转化为天
            $age = $this->getAgeByTime($age_time);
            $data[$apply_id] = ['age'=>$age, 'qty'=>$use_qty];
        }
        return $data;
    }


    /**
     * @desc 生成超库龄报表
     * @return array
     * @throws Exception
     */
    public function createReport()
    {
        $warehouseGoodsModel = new WarehouseGoods();
        $stockingAdviceService = new StockingAdviceService();
        $model = new ReportSkuByInventoryAge();
        $modelDetail = new ReportSkuByInventoryAgeDetail();
        //获取需要生成报表的仓库
        $warehouse_ids = $this->getWarehouseIds();
        $dateline = strtotime(date('Y-m-d', time()));
        $detail = [];
        foreach ($warehouse_ids as $warehouse_id) {
            //如果已经生成了 不需要重新生成了
            if ($model->where('dateline', $dateline)->where('warehouse_id', $warehouse_id)->find()) {
                continue;
            }
            $where = [
                'warehouse_id' => $warehouse_id,
                'waiting_shipping_quantity' => ['>', 0],
                'quantity' => ['>', 0]
            ];
            $lists = $warehouseGoodsModel->where($where)->field('sku_id,waiting_shipping_quantity,sku')->select();
            $over_sku_ids = [];
            $sku_quantity = 0;
            $error_sku = [];
            foreach ($lists as $item) {
                $shipping_qty = $item['waiting_shipping_quantity'];
                $lock_qty = 0;
                $data = $this->getAgeDetail($warehouse_id, $item['sku_id']);
                foreach ($data as $apply_id => $value) {
                    $lock_qty += $value['qty'];
                    if ($value['age'] >= 8) {
                        $stocking = $stockingAdviceService->getStockingField($apply_id);
                        //大于等于8天发钉钉消息
                        $detail[] = [
                            'dateline' => $dateline,
                            'sku_id' => $item['sku_id'],
                            'warehouse_id' => $warehouse_id,
                            'quantity' => $value['qty'],
                            'ready_inventory_plan_id' => $apply_id,
                            'ready_inventory_proposer' => $stocking['submitter_id'],
                            'inventory_age' => $value['age'],
                        ];
                        //大于等于11天发送钉钉消息 生成报表
                        if ($value['age'] >= 11) {
                            array_push($over_sku_ids, $item['sku_id']);
                            $sku_quantity += $value['qty'];
                        }
                    }
                }

                if ( $shipping_qty < $lock_qty) {
                    $error_sku[] = $item['sku'];
                }
            }
            if ($error_sku) {
                cache::handler()->setex('over_age_error:'.$dateline, 864000, json_encode($error_sku));
            }
            $add_data = [
                'dateline' => $dateline,
                'warehouse_id' => $warehouse_id,
                'sku_type_quantity' => count(array_unique($over_sku_ids)),
                'sku_quantity' => $sku_quantity,
            ];
            Db::startTrans();
            try {
                (new ReportSkuByInventoryAge())->allowField(true)->save($add_data);
                $modelDetail->allowField(true)->saveAll($detail);
                Db::commit();
            } catch (Exception $e) {
                Db::rollback();
                throw new Exception($e->getMessage());
            }
        }
    }

    /**
     * @desc 生成超库龄报表
     * @return array
     * @throws Exception
     */
    public function sendLetter()
    {
        $dateline = strtotime(date('Y-m-d', time()));
        $modelDetail = new ReportSkuByInventoryAgeDetail();
        $letterService = new InternalLetterService;
        $stockingAdviceService = new StockingAdviceService;
        $where = [
            'dateline' => $dateline,
            'inventory_age' => ['>=', 8],
        ] ;
        $lists = $modelDetail->where($where)->select();
        foreach ($lists as $item) {
            $warehouse = cache::store('warehouse')->getWarehouse($item['warehouse_id']);
            $sku_info = cache::store('goods')->getSkuInfo($item['sku_id']);
            $stocking = $stockingAdviceService->getStockingField($item['ready_inventory_plan_id']);
            $audit_letter = [
                'dingtalk' => 1,
                'type' => 1,
                'create_id' => 0,
                'title' => '备货计划'.$stocking['code'].'中的'.$sku_info['sku'].'在'.param($warehouse, 'name').'的库龄已经'.$item['inventory_age'].'天；请及时安排调拨发货！',
                'content' => '备货计划'.$stocking['code'].'中的'.$sku_info['sku'].'在'.param($warehouse, 'name').'的库龄已经'.$item['inventory_age'].'天；请及时安排调拨发货！',
                'receive_ids' => $item['ready_inventory_proposer'],
            ];
            $letterService->sendLetter($audit_letter);
        }
    }

    /**
     * @desc 记录导出记录
     * @param $filename
     * @param $path
     * @return array
     */
    public function record($filename, $path)
    {
        $model = new LogExportDownloadFiles();
        $temp['file_code'] = date('YmdHis');
        $temp['created_time'] = time();
        $temp['download_file_name'] = $filename;
        $temp['type'] = 'order_export';
        $temp['file_extionsion'] = 'xls';
        $temp['saved_path'] = $path;
        $model->allowField(true)->isUpdate(false)->save($temp);
        return ['file_code' => $temp['file_code'], 'file_name' => $temp['download_file_name']];
    }

    /**
     * @desc 批量导出
     * @param array $params
     * @return array
     * @throws Exception
     */
    public function batchExport($params)
    {
        /*if (!param($params, 'warehouse_id', 489)) {
            throw new Exception('仓库不能为空');
        }*/
        $warehouse_id = param($params, 'warehouse_id', 489);
        if (!param($params, 'ids')) {
            throw new Exception('记录不能为空');
        }
        $date_arr = json_decode($params['ids'], true);
        if (!$date_arr) {
            throw new Exception('记录不能为空');
        }
        $downLoadDir = '/download/over_age/';
        $saveDir = ROOT_PATH . 'public' . $downLoadDir;
        if (!is_dir($saveDir) && !mkdir($saveDir, 0777, true)) {
            throw new Exception('导出目录创建失败');
        }
        $fileName = $this->createExportFileName($date_arr, $warehouse_id);
        $fullName = $saveDir . $fileName;
        //创建excel对象
        $writer = new \XLSXWriter();
        $title = $this->colMap;
        foreach ($date_arr as $date) {
            $writer->writeSheetHeader($date, $title);
            $data = $this->getDetail($date, $warehouse_id);
            //分批导出
            foreach ($data as $r) {
                $writer->writeSheetRow($date, $r);
            }
        }
        $writer->writeToFile($fullName);
        $result = $this->record($fileName, $fullName);
        return $result;
    }

    /**
     * @desc 获取导出详情
     * @param string $date
     * @param int $warehouse_id
     * @return array
     * @throws Exception
     */
    public function getDetail($date, $warehouse_id)
    {
        date_default_timezone_set('PRC');
        $where = [
            'dateline' => strtotime(date('Y-m-d',strtotime($date))),
            'warehouse_id' => $warehouse_id,
        ];
        $modelDetail = new ReportSkuByInventoryAgeDetail();
        $stockingAdviceService = new StockingAdviceService();
        $lists = $modelDetail->where($where)->select();
        $data = [];
        foreach($lists as $item) {
            if ($item['inventory_age']>=11) {
                $sku_info = cache::store('goods')->getSkuInfo($item['sku_id']);
                $stocking = $stockingAdviceService->getStockingField($item['ready_inventory_plan_id']);
                $data[] = [
                    'sku' => $sku_info['sku'],
                    'spu_name' => $sku_info['spu_name'],
                    'quantity' => $item['quantity'],
                    'code' => $stocking['code'],
                    'submitter' => cache::store('user')->getOneUserRealname($stocking['submitter_id']),
                    'age' => $item['inventory_age'],
                ];
            }
        }
        return $data;
    }

    /**
     * @desc 创建导出文件名
     * @param array $date_arr
     * @param int $warehouse_id
     * @return string
     */
    protected function createExportFileName($date_arr, $warehouse_id)
    {
        $warehouse = cache::store('warehouse')->getWarehouse($warehouse_id);
        $fileName = $warehouse['name'].'超库龄报表'.date('YmdHis').'.xlsx';
        return $fileName;
    }

}