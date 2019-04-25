<?php

namespace app\report\service;

use app\common\cache\Cache;
use app\common\model\Goods;
use app\common\model\OrderDetail;
use app\common\model\OrderOos;
use app\common\model\OrderPackage;
use app\common\model\report\ReportStatisticByGoods;
use app\common\model\User;
use app\common\model\Warehouse;
use app\common\service\ChannelAccountConst;
use app\common\service\ChannelConst;
use app\common\service\Common;
use app\common\service\OrderStatusConst;
use app\common\service\Report;
use app\common\traits\Export;
use app\goods\service\GoodsSku;
use app\order\service\AuditOrderService;
use app\report\queue\StatisticByGoodsExportQueue;
use think\Db;
use think\Exception;
use think\Loader;
use app\common\exception\JsonErrorException;
use app\common\service\CommonQueuer;
use app\report\model\ReportExportFiles;
use app\report\queue\SaleStockExportQueue;
use app\index\service\Department as DepartmentServer;
use app\index\service\DepartmentUserMapService;
use app\report\validate\FileExportValidate;

Loader::import('phpExcel.PHPExcel', VENDOR_PATH);
/**
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/8/1
 * Time: 19:17
 */
class StatisticByGoods
{
    use Export;
    protected $reportStatisticByDeepsModel = null;

    public function __construct()
    {
        if (is_null($this->reportStatisticByDeepsModel)) {
            $this->reportStatisticByDeepsModel = new ReportStatisticByGoods();
        }
    }

    /**
     * 标题
     */
    public function title()
    {
        $title = [
            'sku' => [
                'title' => 'sku',
                'remark' => 'SKU',
                'is_show' => 1
            ],
            'spu_name' => [
                'title' => 'spu_name',
                'remark' => '产品名称',
                'is_show' => 1
            ],
            'purchaser_name' => [
                'title' => 'purchaser_name',
                'remark' => '采购员',
                'is_show' => 1
            ],
            'developer_name' => [
                'title' => 'developer_name',
                'remark' => '开发员',
                'is_show' => 1
            ],
            'category_name' => [
                'title' => 'category_name',
                'remark' => '商品分类',
                'is_show' => 1
            ],
            'supplier_name' => [
                'title' => 'supplier_name',
                'remark' => '供应商',
                'is_show' => 1
            ],
            'cost_price' => [
                'title' => 'cost_price',
                'remark' => '成本价',
                'is_show' => 1
            ],
            'd7' => [
                'title' => 'd7',
                'remark' => '最近7天的销量',
                'is_show' => 1
            ],
            'd7A' => [
                'title' => 'd7A',
                'remark' => '上一个7天的销量',
                'is_show' => 1
            ],
            'growth_rate' => [
                'title' => 'growth_rate',
                'remark' => '增长率(%)',
                'is_show' => 1
            ],
        ];
        return $title;
    }

    /** 列表数据
     * @param $data
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function lists($page, $pageSize, $params)
    {
        $where = [];
        $having = '';
        $this->where($params, $where, $having);

        $join = $this->join();
        $count = $this->doCount($where, $join,$params,$having);
        $data = $this->doSearch($where, $join, $page, $pageSize, $params,$having);

        $result = [
            'data' => $data,
            'count' => $count,
            'page' => $page,
            'pageSize' => $pageSize
        ];
        return $result;
    }

    /**
     * 搜索条件
     * @param $data
     * @param $where
     * @return \think\response\Json
     */
    private function where(&$data, &$where,&$having = '')
    {
        $where['bg.order_quantity'] = ['>', 0];
        if (isset($data['channel_id']) && !empty($data['channel_id'])) {
            $where['bg.channel_id'] = ['eq', $data['channel_id']];
        }
        if (isset($data['warehouse_id']) && !empty($data['warehouse_id'])) {
            $where['bg.warehouse_id'] = ['eq', $data['warehouse_id']];
        }
        if (isset($data['warehouse_type']) && !empty($data['warehouse_type'])) {
            $where['bg.warehouse_type'] = ['eq', $data['warehouse_type']];
        }
        if (isset($data['goods_id']) && !empty($data['goods_id'])) {
            $where['bg.goods_id'] = ['eq', $data['goods_id']];
        }
        if (isset($data['sku_id']) && !empty($data['sku_id'])) {
            $where['bg.sku_id'] = ['eq', $data['sku_id']];
        }
        if (isset($data['supplier_id']) && !empty($data['supplier_id'])) {
            $where['g.supplier_id'] = ['eq', $data['supplier_id']];
        }
        if (isset($data['sku']) && !empty($data['sku']) && $data['sku']) {
            $texts = is_json($data['sku']) ? json_decode($data['sku'], true) : (array)$data['sku'];
            if($texts){
                $sku_id = (new \app\common\model\GoodsSku())->where('sku','in',$texts)->column('id');
                $where['bg.sku_id'] = ['in', $sku_id];
            }

        }
        if (isset($data['category_id']) && !empty($data['category_id'])) {
            $where['bg.category_id'] = ['eq', $data['category_id']];
        }
        $times = date('Y-m-d');
        $data['date_b'] = isset($data['date_b']) ? $data['date_b'] : date('Y-m-d',strtotime('-7 day'));
        $data['date_e'] = isset($data['date_e']) ? $data['date_e'] : $times;
        $condition = timeCondition($data['date_b'], $data['date_e']);
        if (!is_array($condition)) {
            return json(['message' => '日期格式错误'], 400);
        }
        if (!empty($condition)) {
            $where['dateline'] = $condition;
        }

        $d7min = isset($data['d7_min']) && !empty($data['d7_min']);
        $d7max = isset($data['d7_max']) && !empty($data['d7_max']);
        if($d7min && $d7max){
            $having = 'd7 >= '.$data['d7_min'] . ' and d7 <='.$data['d7_max'];
        }else if($d7min){
            $having = 'd7 >= '.$data['d7_min'];
        }else if($d7max){
            $having = 'd7 <= '.$data['d7_max'];
        }

        if (isset($data['snType']) && isset($data['snText']) && !empty($data['snText'])) {
            $text = is_json($data['snText']) ? json_decode($data['snText'], true) : (array)$data['snText'];
            if($text){
                switch (trim($data['snType'])) {
                    //开发人员
                    case 'developer_id':
                        $where['bg.developer_id'] = ['IN', $text];
                        break;
                    //采购人员ID
                    case 'purchaser_id':
                        $where['bg.purchaser_id'] = ['IN', $text];
                        break;
                }
            }
        }
        if (isset($data['purchaser_id']) && !empty($data['purchaser_id'])) {
            $where['bg.purchaser_id'] = ['eq', $data['purchaser_id']];
        }
        if (isset($data['developer_id']) && !empty($data['developer_id'])) {
            $where['bg.developer_id'] = ['eq', $data['developer_id']];
        }
    }


    /**
     * 查询数据，并组装
     * @param $condition
     * @param $join
     * @param $page
     * @param $pageSize
     * @param $params
     * @param string $having
     * @return false|\PDOStatement|string|\think\Collection
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function doSearch($condition, $join, $page, $pageSize, $params,$having='')
    {
        $field = $this->field();
        $order = $this->getOrder($params);


        $timesS = strtotime($params['date_b']); //时间
        $timesd7 = $timesS - 3600 * 24 * 7;

        $having2 = $this->getHavingRate($params);

        if($having2){

            if($having){
                $having .= ' AND '.$having2;
            }else{
                $having .= $having2;
            }
            $field .= $this->getAddField($timesd7,$timesS);
            $results = $this->reportStatisticByDeepsModel->alias('bg')->field($field)->join($join)->where($condition)->group('bg.sku_id')->having($having)->order($order)->page($page, $pageSize)->select();
            foreach ($results as &$v) {
                if ($v['d7A'] > 0) {
                    $v['growth_rate'] = intval($v['d7'] / $v['d7A'] * 100);
                } else {
                    $v['growth_rate'] = 100;
                }
                // 采购员
                $v['purchaser_name'] = $this->getUserName($v['purchaser_id']);
                //开发员
                $v['developer_name'] = $this->getUserName($v['developer_id']);

                //供应商名称
                $v['supplier_name'] = Cache::store('Supplier')->getSupplierName($v['supplier_id']);

                //SKU  产品名称  成本价
                $goodsSku = Cache::store('Goods')->getSkuInfo($v['sku_id']);
                $v['sku'] = $goodsSku['sku'];
                $v['spu_name'] = $goodsSku['spu_name'];
                $v['cost_price'] = $goodsSku['cost_price'];

                //商品分类
                $v['category_name'] = Cache::store('Category')->getFullNameById($v['category_id'], '');
            }
        }else {
            $results = $this->reportStatisticByDeepsModel->alias('bg')->field($field)->join($join)->where($condition)->group('bg.sku_id')->having($having)->order($order)->page($page, $pageSize)->select();
            if($results){
                $skuIds = [];
                foreach ($results as $v) {
                    $skuIds[] = $v['sku_id'];
                }
                if(isset($condition['g.supplier_id'])){
                    unset($condition['g.supplier_id']);
                }
                $condition['dateline'] = ['between', [$timesd7, $timesS]];
                $condition['sku_id'] = ['in', $skuIds];
                $resultsd7 = $this->reportStatisticByDeepsModel->alias('bg')->where($condition)->group('bg.sku_id')->column('sum(order_quantity)', 'sku_id');
                foreach ($results as &$v) {
                    $v['d7A'] = $resultsd7[$v['sku_id']] ?? 0;
                    if ($v['d7A'] > 0) {
                        $v['growth_rate'] = intval($v['d7'] / $v['d7A'] * 100);
                    } else {
                        $v['growth_rate'] = 100;
                    }

                    // 采购员
                    $v['purchaser_name'] = $this->getUserName($v['purchaser_id']);
                    //开发员
                    $v['developer_name'] = $this->getUserName($v['developer_id']);

                    //供应商名称
                    $v['supplier_name'] = Cache::store('Supplier')->getSupplierName($v['supplier_id']);

                    //SKU  产品名称  成本价
                    $goodsSku = Cache::store('Goods')->getSkuInfo($v['sku_id']);

                    $v['sku'] = $goodsSku['sku'] ?? '';
                    $v['spu_name'] = $goodsSku['spu_name'] ?? '';
                    $v['cost_price'] = $goodsSku['cost_price'] ?? '';

                    //商品分类
                    $v['category_name'] = Cache::store('Category')->getFullNameById($v['category_id'], '');

                }
            }

        }
        return $results;
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
                $order_by = ' bg.sku_id asc';
        }
        return $order_by;

    }

    /**
     * 想要的数据
     * @return string
     */
    public function field()
    {
        $field = 'bg.goods_id,bg.sku_id,bg.category_id,bg.developer_id,bg.purchaser_id,sum(order_quantity) as d7,g.supplier_id';
        return $field;
    }

    /**
     * 关联的表
     * @return array
     */
    public function join()
    {
        $join[] = ['goods g', 'g.id = bg.goods_id', 'left'];
        return $join;
    }

    /**
     * 统计条数
     * @param $where
     * @param $join
     * @param $params
     * @param string $having
     * @return int|string
     */
    public function doCount($where,$join,$params,$having='')
    {
        $field = $this->field();

       $having2 = $this->getHavingRate($params);
        if($having2){
            if($having){
                $having .= ' AND '.$having2;
            }else{
                $having .= $having2;
            }
            $timesS = strtotime($params['date_b']); //时间
            $timesd7 = $timesS - 3600 * 24 * 7;
            $field .= $this->getAddField($timesd7,$timesS);
            $count = $this->reportStatisticByDeepsModel->alias('bg')->join($join)->field($field)->where($where)->group('bg.sku_id')->having($having)->count();
        }else{
            $count = $this->reportStatisticByDeepsModel->alias('bg')->join($join)->field($field)->where($where)->group('bg.sku_id')->having($having)->count();
        }


        return $count;
    }

    /**
     * 读取用户的真实姓名
     * @param $userId
     * @return string
     * @throws Exception
     */
    public function getUserName($userId)
    {
        $user = Cache::store('user')->getOneUser($userId);
        if ($user) {
            return $user['realname'];
        }
        return '';
    }

    /**
     * 组装增长率的查询sql
     * @param $timesd7
     * @param $timesS
     * @return string
     */
    private function getAddField($timesd7,$timesS)
    {
        $field = ',(SELECT SUM(bg2.order_quantity) FROM report_statistic_by_goods bg2 WHERE bg2.sku_id = bg.sku_id AND (bg2.`dateline` BETWEEN '.$timesd7.' AND '.$timesS.') ) as d7A ';
        return $field;
    }

    /**
     * 获取增长率的限制
     * @param $params
     * @return string
     */
    private function getHavingRate($params)
    {
        $d7min = isset($params['rate_min']) && !empty($params['rate_min']);
        $d7max = isset($params['rate_max']) && !empty($params['rate_max']);
        $having2 = '';
        if($d7min && $d7max){
            $having2 = 'd7/d7A >= '.$params['rate_min'] . ' and d7/d7A <= '.$params['rate_max'];
        }else if($d7min){
            $having2 = 'd7/d7A >= '.$params['rate_min'] / 100;
        }else if($d7max){
            $having2 = 'd7/d7A <= '.$params['rate_max'] / 100;
        }
        return $having2;
    }

    /**
     * 导出记录
     * @param array $sku_ids
     * @param array $field
     * @param array $params
     * @return array
     * @throws Exception
     */
    public function exportOnLine(array $sku_ids = [], array $field = [], $params = [])
    {
        set_time_limit(0);
        $userInfo = Common::getUserInfo();
        try {

            //获取导出文件名
            $fileName = $this->newExportFileName($params);
            //判断是否存在筛选条件，更改导出名
            if(isset($fileName) && $fileName != ''){
                $setFileName = 1;
                $name = $fileName . (isset($params['name']) ? $params['name'] : $userInfo['realname']);
                $fileName = $name;
            }else{
                $setFileName = 0;
                $name = isset($params['name']) ? $params['name'] : $userInfo['realname'];
                $fileName = $name . date('YmdHis', time());
            }

            $downLoadDir = '/download/customer_message/';
            $saveDir = ROOT_PATH . 'public' . $downLoadDir;
            if (!is_dir($saveDir) && !mkdir($saveDir, 0777, true)) {
                throw new Exception('导出目录创建失败');
            }
            $fullName = $saveDir . $fileName;
            $titleData = $this->title();
            $where = [];
            if (!empty($sku_ids)) {
                $where['bg.sku_id'] = ['in', $sku_ids];
            }
            $remark = [];
            if (!empty($field)) {
                $title = [];
                foreach ($field as $k => $v) {
                    if (isset($titleData[$v])) {
                        array_push($title, $v);
                        array_push($remark, $titleData[$v]['remark']);
                    }
                }
            } else {
                $title = [];
                foreach ($titleData as $k => $v) {
                    if ($v['is_show'] == 1) {
                        array_push($title, $k);
                        array_push($remark, $v['remark']);
                    }
                }
            }

            $having = '';
            $this->where($params, $where, $having);

            $join = $this->join();
            $count = $this->doCount($where, $join,$params,$having);
            if ($count > 100) {
                $params['field'] = $field;
                $this->exportApply($params, StatisticByGoodsExportQueue::class, $name, $setFileName);
                return ['join_queue' => 1, 'message' => '已加入导出队列'];
            } else {
                $data = $this->doSearch($where, $join, 1, 100, $params,$having);
                $titleOrderData = [];
                foreach ($remark as $t => $tt){
                    $titleOrderData[$tt] = 'string';
                }
                $data = $this->getMapAll($data);
                $this->excelSave($titleOrderData,$fullName,$data);
                $auditOrderService = new AuditOrderService();
                $result = $auditOrderService->record($fileName, $saveDir . $fileName);
                return $result;
            }
        } catch (Exception $e) {
            throw new JsonErrorException($e->getMessage());
        }
    }

    /**
     * @title 生成导出用户名
     * @param $params
     * @return string
     */
    public function newExportFileName($params)
    {
        $fileName = 'SKU销量动态表';
        $times = date('Y-m-d');
        $date_b = isset($params['date_b']) ? $params['date_b'] : date('Y-m-d',strtotime('-7 day'));
        $date_e = isset($params['date_e']) ? $params['date_e'] : $times;
        $fileName .=  $date_b . '--' .$date_e;

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
        opcache_reset();
        try {
            //ini_set('memory_limit', '4096M');
            if (!isset($params['apply_id']) || empty($params['apply_id'])) {
                throw new Exception('导出申请id获取失败');
            }
            if (!isset($params['file_name']) || empty($params['file_name'])) {
                throw new Exception('导出文件名未设置');
            }
            $fileName = $params['file_name'];
            $downLoadDir = '/download/customer_message/';
            $saveDir = ROOT_PATH . 'public' . $downLoadDir;
            if (!is_dir($saveDir) && !mkdir($saveDir, 0777, true)) {
                throw new Exception('导出目录创建失败');
            }
            $fullName = $saveDir . $fileName;
            //创建excel对象
            $writer = new \XLSXWriter();
            $fields = $params['field'] ?? [];
            $titleData = $this->title();
            $title = [];
            if (!empty($fields)) {
                $titleNewData = [];
                foreach ($fields as $k => $v) {
                    if (isset($titleData[$v])) {
                        array_push($title, $v);
                        $titleNewData[$v] = $titleData[$v];
                    }
                }
                $titleData = $titleNewData;
            } else {
                foreach ($titleData as $k => $v) {
                    if ($v['is_show'] == 0) {
                        unset($titleData[$k]);
                    } else {
                        array_push($title, $k);
                    }
                }
            }
            list($titleMap, $dataMap) = $this->getExcelMap($titleData);
            end($titleMap);
            $titleOrderData = [];
            foreach ($titleMap as $t => $tt){
                $titleOrderData[$tt['title']] = 'string';
            }
            //统计需要导出的数据行
            $having = '';
            $this->where($params, $where, $having);
            $join = $this->join();
            $count = $this->doCount($where, $join,$params,$having);
            $pageSize = 1000;
            $loop = ceil($count / $pageSize);
            if(empty($loop)){
                $loop = 1;
            }
            $writer->writeSheetHeader('Sheet1', $titleOrderData);
            //分批导出
            for ($i = 0; $i < $loop; $i++) {
                $data = $this->doSearch($where, $join, $i + 1, $pageSize, $params,$having);
                foreach ($data as $a => $r) {
                    $mapOne = $this->getMapOne($r);
                    $writer->writeSheetRow('Sheet1', $mapOne);
                }
                unset($data);
            }
            $writer->writeToFile($fullName);
            if (is_file($fullName)) {
                $applyRecord['exported_time'] = time();
                $applyRecord['download_url'] = $downLoadDir . $fileName;
                $applyRecord['status'] = 1;
                (new ReportExportFiles())->where(['id' => $params['apply_id']])->update($applyRecord);
            } else {
                throw new Exception('文件写入失败');
            }
        } catch (\Exception $ex) {
            Cache::handler()->hset(
                'hash:report_export:statistic_by_goods',
                $params['apply_id'].'_'. time(),
                '申请id: ' . $params['apply_id'] . ',导出失败:' . $ex->getMessage() . $ex->getFile() . $ex->getLine());
            $applyRecord['status'] = 2;
            $applyRecord['error_message'] = $ex->getMessage();
            (new ReportExportFiles())->where(['id' => $params['apply_id']])->update($applyRecord);
        }
    }

    /**
     * 将对象转化数组数据（单一个）
     * @param $data
     * @return array
     */
    private function getMapOne($data)
    {
        $one = [
            'sku' => $data['sku'],
            'spu_name' => $data['spu_name'],
            'purchaser_name' => $data['purchaser_name'],
            'developer_name' => $data['developer_name'],
            'category_name' => $data['category_name'],
            'supplier_name' => $data['supplier_name'],
            'cost_price' => $data['cost_price'],
            'd7' => $data['d7'],
            'd7A' => $data['d7A'],
            'growth_rate' => $data['growth_rate'],
        ];
        return $one;
    }

    /**
     * 将对象转化数组数据
     * @param $data
     * @return array
     */
    private function getMapAll($data)
    {
        $reData = [];
        foreach ($data as $v){
            $reData[] = $this->getMapOne($v);
        }
        return $reData;
    }

}