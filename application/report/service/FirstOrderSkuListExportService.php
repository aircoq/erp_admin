<?php
namespace app\report\service;

use app\common\model\report\ReportStatisticBySkuOrder;
use app\order\service\AuditOrderService;
use app\report\queue\FirstOrderSkuListExportQueue;
use app\common\cache\Cache;
use app\goods\service\GoodsSkuAlias;
use app\goods\service\CategoryHelp;
use app\common\traits\Export;
use app\common\service\ImportExport;
use app\common\service\Common;
use app\common\exception\JsonErrorException;
use app\common\traits\FilterHelp;
use app\report\model\ReportExportFiles;
use think\Loader;
use think\Exception;

Loader::import('phpExcel.PHPExcel', VENDOR_PATH);
/**
 * Created by PhpStorm.
 * User: hecheng
 * Date: 2018/9/20
 * Time: 9:44
 */
class FirstOrderSkuListExportService
{
    use Export;

    /**
     * 标题
     * @return array
     */
    public function title()
    {
        $title = [
            'channel' => [
                'title' => 'channel',
                'remark' => '平台',
                'is_show' => 1
            ],
            'sku_alias' => [
                'title' => 'sku_alias',
                'remark' => 'SKU（别名）',
                'is_show' => 1
            ],
            'goods_name' => [
                'title' => 'goods_name',
                'remark' => '产品名称',
                'is_show' => 1
            ],
            'category' => [
                'title' => 'category',
                'remark' => '所属分类',
                'is_show' => 1
            ],
            'developer' => [
                'title' => 'developer',
                'remark' => '开发员',
                'is_show' => 1
            ],
            'shelf_time' => [
                'title' => 'shelf_time',
                'remark' => '上架日期',
                'is_show' => 1
            ],
            'order_time' => [
                'title' => 'order_time',
                'remark' => '下单日期',
                'is_show' => 1
            ],
            'issue_time' => [
                'title' => 'issue_time',
                'remark' => '出单日期',
                'is_show' => 1
            ],
        ];
        return $title;
    }

    /**
     * 在线导出
     * @param array $sku_id
     * @param array $field
     * @param array $params
     * @return array
     * @throws Exception
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     */
    public function exportOnline(array $sku_id = [], array $field = [], $params = [])
    {
        set_time_limit(0);
        $userInfo = Common::getUserInfo();
        try {
            if (empty($sku_id)) {
                if (Cache::store('order')->isExport($userInfo['user_id'])) {
                    throw new JsonErrorException('全部导出正在生成数据,请使用部分导出');
                }
                Cache::store('order')->setExport($userInfo['user_id']);
            }
            //获取导出文件名
            $fileName = $this->newExportFileName($params);
            if(isset($fileName) && $fileName != ''){
                $setFileName = 1;
                $name = $fileName . '首次出单SKU列表数据';
                $fileName = $name;
            }else {
                $setFileName = 0;
                $name = '首次出单SKU列表数据';
                $fileName = $name . date('YmdHis', time());
            }
            $downLoadDir = '/download/sale/';
            $saveDir = ROOT_PATH . 'public' . $downLoadDir;
            if (!is_dir($saveDir) && !mkdir($saveDir, 0777, true)) {
                throw new Exception('导出目录创建失败');
            }
            $fullName = $saveDir . $fileName;
            $titleData = $this->title();
            $condition = [];
            if (!empty($sku_id)) {
                $condition['sku_id'] = ['in', $sku_id];
                $params['ids'] = $sku_id;
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
            $count = $this->doCount($condition, $params);
            if ($count > 200) {
                $params['field'] = $field;
                $this->exportApply($params, FirstOrderSkuListExportQueue::class, $name, $setFileName);
                Cache::store('order')->delExport($userInfo['user_id']);
                return ['join_queue' => 1, 'message' => '已加入导出队列'];
            } else {
                $records = $this->doSearch($condition, $params);
                $data = $this->assemblyData($records, $title);
                ImportExport::excelSave($data, $remark, $fullName);
                $auditOrderService = new AuditOrderService();
                $result = $auditOrderService->record($fileName, $saveDir . $fileName);
                Cache::store('order')->delExport($userInfo['user_id']);
                return $result;
            }
        } catch (Exception $e) {
            Cache::store('order')->delExport($userInfo['user_id']);
            throw new JsonErrorException($e->getMessage());
        }
    }

    /**
     * 统计数据
     * @param array $condition
     * @param array $params
     * @return int|string
     * @throws \Exception
     */
    public function doCount(array $condition = [], $params = [])
    {
        if (empty($condition)) {
            $skuListService = new FirstOrderSkuListService();
            $condition = $skuListService->where($params);
        }
        $field = 'channel_id,sku,sku_id,goods_id,category_id,developer,shelf_time,order_time,issue_time';
        return (new ReportStatisticBySkuOrder())->field($field)->where($condition)->count();
    }

    /**
     * 查询数据
     * @param array $condition
     * @param array $params
     * @param int $page
     * @param int $pageSize
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function doSearch(array $condition = [], $params = [], $page = 0, $pageSize = 0)
    {
        $join = [];
        if (empty($condition)) {
            $skuListService = new FirstOrderSkuListService();
            $condition = $skuListService->where($params);
        }
        $field = 'channel_id,sku,sku_id,goods_id,category_id,developer,shelf_time,order_time,issue_time';
        if (!empty($page) && !empty($pageSize)) {
            $skuList = (new ReportStatisticBySkuOrder())->field($field)->where($condition)->join($join)->page($page,
                $pageSize)->select();
        } else {
            $skuList = (new ReportStatisticBySkuOrder())->field($field)->where($condition)->join($join)->select();
        }
        return $skuList;
    }

    /**
     * 整合数据
     * @param array $records
     * @param array $title
     * @return array
     * @throws \think\Exception
     */
    public function assemblyData(array $records, array $title)
    {
        try {
            $exportData = [];
            $goodsSkuAliasService = new GoodsSkuAlias();
            $categoryHelpService = new CategoryHelp();
            foreach ($records as $key => $value) {
                $newOrderData = $value;
                //获取SKU别名
                $alias = $goodsSkuAliasService->getAliasBySkuId($value['sku_id']);
                //获取商品名称
                $goodsInfo = Cache::store('goods')->getGoodsInfo($value['goods_id']);
                //获取商品分类
                $category = $categoryHelpService->getCategoryNameById($value['category_id']);
                //获取开发员
                $user = Cache::store('user')->getOneUser($value['developer'] ?? '') ?? '';
                $newOrderData['channel'] = Cache::store('channel')->getChannelName($value['channel_id']) ?? '';
                if (!empty($alias)) {
                    $newOrderData['sku_alias'] = $value['sku'] . '( '.$alias[0].' )';
                } else {
                    $newOrderData['sku_alias'] = $value['sku'];
                }
                $newOrderData['goods_name'] = $goodsInfo['name'] ?? '';
                $newOrderData['category'] = $category ?? '';
                $newOrderData['developer'] = $user['realname'] ?? '';
                $newOrderData['shelf_time'] = date('Y年m月d日',$value['shelf_time']);
                $newOrderData['order_time'] = date('Y年m月d日',$value['order_time']);
                $newOrderData['issue_time'] = $value['issue_time'] . '天';
                $temp = [];
                foreach ($title as $k => $v) {
                    $temp[$v] = $newOrderData[$v];
                }
                array_push($exportData, $temp);
            }
            return $exportData;
        } catch (Exception $e) {
            throw new Exception($e->getMessage() . $e->getFile() . $e->getLine());
        }
    }

    /**
     * 导出数据至excel文件
     * @param array $params
     */
    public function export(array $params)
    {
        opcache_reset();
        try {
            opcache_reset();
            //ini_set('memory_limit', '4096M');
            if (!isset($params['apply_id']) || empty($params['apply_id'])) {
                throw new Exception('导出申请id获取失败');
            }
            if (!isset($params['file_name']) || empty($params['file_name'])) {
                throw new Exception('导出文件名未设置');
            }
            $fileName = $params['file_name'];
            $downLoadDir = '/download/sale/';
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
            $lastCol = key($titleMap);
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
            $count = $this->doCount([], $params);
            $pageSize = 2000;
            $loop = ceil($count / $pageSize);
            Cache::handler()->hSet('hash:order:export', 1, 1);
            //分批导出
            for ($i = 0; $i < $loop; $i++) {
                $data = $this->assemblyData($this->doSearch([], $params, $i + 1, $pageSize), $title);
                foreach ($data as $a => $r) {
                    foreach ($dataMap as $field => $set) {
                        $cell = $sheet->getCell($set['col'] . $dataRowStartIndex);
                        switch ($set['type']) {
                            case 'time':
                                if (empty($r[$field])) {
                                    $cell->setValue('');
                                } else {
                                    $cell->setValue(date('Y-m-d H:i:s', $r[$field]));
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
                unset($data);
            }
            Cache::handler()->hSet('hash:order:export', 0, 1);
            $writer = \PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
            $writer->save($fullName);
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
                'hash:report_export',
                $params['apply_id'].'_'. time(),
                '申请id: ' . $params['apply_id'] . ',导出失败:' . $ex->getMessage() . $ex->getFile() . $ex->getLine());
            $applyRecord['status'] = 2;
            $applyRecord['error_message'] = $ex->getMessage();
            (new ReportExportFiles())->where(['id' => $params['apply_id']])->update($applyRecord);
        }
    }

    /**
     * @title 生成导出用户名
     * @param $params
     * @return string
     */
    public function newExportFileName($params)
    {
        $fileName = '';
        //平台
        if ($channel_id = param($params, 'channel_id')) {
            $title = Cache::store('channel')->getChannelName($params['channel_id']) ?? '';
            $fileName .= '平台：' . $title . '|';
        }

        //开发员
        if (isset($params['developer']) && $params['developer'] != ''){
            $cache = Cache::store('user');
            $user = $cache->getOneUser($params['developer'] ?? '') ?? '';
            $fileName .= '开发员：' . $user['realname'] . '|';
        }

        //日期筛选
        if (isset($params['snDate'])) {
            $params['date_b'] = isset($params['date_b']) ? $params['date_b'] : 0;
            $params['date_e'] = isset($params['date_e']) ? $params['date_e'] : 0;
            switch ($params['snDate']) {
                case 'shelf_time':
                    if (!empty($params['date_b']) && !empty($params['date_e'])) {
                        $fileName .= '上架时间：' . $params['date_b'] . '—' . $params['date_e'] . '|';
                    }
                    break;
                case 'order_time':
                    if (!empty($params['date_b']) && !empty($params['date_e'])) {
                        $fileName .= '下单时间：' . $params['date_b'] . '—' . $params['date_e'] . '|';
                    }
                    break;
                default:
                    break;
            }
        }
        return $fileName;
    }
}