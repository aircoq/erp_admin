<?php

namespace app\common\traits;

use app\common\cache\Cache;
use app\common\exception\JsonErrorException;
use app\common\service\Common;
use app\common\service\CommonQueuer;
use app\report\model\ReportExportFiles;
use app\common\model\Channel;
use app\order\service\OrderService;
use think\Loader;

Loader::import('XLSXWriter/xlsxwriter', EXTEND_PATH, '.class.php');  //PDF_PAGE_FORMAT

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2018/5/17
 * Time: 15:30
 */
trait Export
{
    /**
     * 导出标题数据
     * @param array $titleData
     * @return array
     */
    public function getExcelMap(array $titleData)
    {
        $titleMap = [];
        $dataMap = [];
        $row = 0;
        $column = '';
        $is_ok = false;
        foreach ($titleData as $k => $v) {
            if ($v['is_show'] == 1) {
                $col = $this->getExcelNumber($row, $column);
                if ($row == 25) {
                    $row = 0;
                    if (!$is_ok) {
                        $is_ok = true;
                        $column = 0;
                    } else {
                        $column++;
                    }
                } else {
                    if ($column == 25) {
                        $row++;
                        $column = 0;
                    } else {
                        if ($is_ok) {
                            $column++;
                        } else {
                            $row++;
                        }
                    }
                }
                $dataMap[$v['title']] = [
                    'col' => $col,
                    'type' => 'str'
                ];
                $titleMap[$col] = [
                    'title' => $v['remark'],
                    'width' => 20
                ];
            }
        }
        return [0 => $titleMap, 1 => $dataMap];
    }

    /**
     * 获取excel表的编号
     * @param $row [行]
     * @param $column [列]
     * @return string
     */
    public function getExcelNumber($row, $column)
    {
        $letter = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
        if ($column !== '') {
            $number = $letter[$row] . $letter[$column];
        } else {
            $number = $letter[$row];
        }
        return $number;
    }

    /**
     * 获取excel表的所有列
     * @param $column
     * @return array
     */
    public static function getExcelColumn($column)
    {
        $letter = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
        $count = count($letter);
        $times = ceil($column / $count);
        $excelColumn = [];
        $total = 0;
        for ($i = 0; $i < $times; $i++) {
            foreach ($letter as $k => $v) {
                if ($i > 0) {
                    $temp = $letter[$i-1] . $v;
                } else {
                    $temp = $v;
                }
                array_push($excelColumn, $temp);
                $total++;
                if ($total == $column) {
                    break;
                }
            }

        }
        return $excelColumn;
    }

    /**
     * 导出申请
     * @param $params
     * @param $object
     * @param $remark
     * @param $setFileName
     * @param $is_csv
     */
    public function exportApply($params, $object, $remark = '订单数据', $setFileName = 0, $is_csv = 0)
    {
        $userId = Common::getUserInfo()->toArray()['user_id'];
        $cache = Cache::handler();
        $lastApplyTime = $cache->hget('hash:export_order_apply', $userId);
        if ($lastApplyTime && time() - $lastApplyTime < 5) {
            throw new JsonErrorException('请求过于频繁', 400);
        } else {
            $cache->hset('hash:export_order_apply', $userId, time());
        }
        $model = new ReportExportFiles();
        $data['applicant_id'] = $userId;
        $data['apply_time'] = time();
        if (!empty($is_csv)) {
            $suffix = '.csv';
        } else {
            $suffix = '.xlsx';
        }
        if ($setFileName == 0) {
            $export_file_name = $this->createExportFileName($remark, $userId, $suffix);
        } else {
            $export_file_name = $remark . $suffix;
        }
        $file_name = $export_file_name;
        if (!empty($is_csv)) {
            $export_file_name = str_replace('.csv', '.zip', $export_file_name);
        }
        $data['export_file_name'] = $export_file_name;
        $data['status'] = 0;
        $data['applicant_id'] = $userId;
        $model->allowField(true)->isUpdate(false)->save($data);
        $params['file_name'] = $file_name;
        $params['apply_id'] = $model->id;
        (new CommonQueuer($object))->push($params);
    }

    /**
     * 创建导出文件名
     * @param string $remark
     * @param $userId
     * @param $suffix
     * @return string
     */
    protected function createExportFileName($remark, $userId, $suffix)
    {
        $fileName = $remark . '报表_' . $userId . '_' . date("Y_m_d_H_i_s") . $suffix;
        return $fileName;
    }

    /**
     * 保存
     * @param $titleOrderData
     * @param $fullName
     * @param $data
     */
    public function excelSave($titleOrderData, $fullName, $data)
    {
        $writer = new \XLSXWriter();
        $writer->writeSheetHeader('Sheet1', $titleOrderData);
        foreach ($data as $a => $r) {
            $writer->writeSheetRow('Sheet1', $r);
        }
        $writer->writeToFile($fullName);
    }

}