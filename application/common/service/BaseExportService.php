<?php


namespace app\common\service;


use app\common\cache\Cache;
use app\common\service\Filter;
use app\common\service\OrderType;
use app\common\traits\Export;
use app\common\traits\User;
use app\order\filter\OrderByAccountFilter;
use app\report\model\ReportExportFiles;
use think\Exception;
use ZipArchive;

/**
 * 报表导出基类
 * Class BaseExportService
 * @package app\order\service
 */
abstract class BaseExportService
{
    use Export;
    use User;

    protected $bigPageSize = 1000000;  //1000000
    protected $smallPageSize = 5000;  //5000
    public static $logTime = 0;
    public static $logTextArr = [];
    public static $logId = '';

    protected $countryData = [];
    protected $requestFields = [];

    public function __construct()
    {

    }

    /**
     * 导出主函数
     * @param array $params
     */
    public function export(array $params)
    {
        $zip = new \ZipArchive();
        self::$logTextArr = [];
        self::$logId = $params['apply_id'] ?? 0;
        $this->monitorMem(1);
        try {
            opcache_reset();
            set_time_limit(0);
            $this->checkParams($params);
            $pathData = $this->getPathData($params);
            $fullName = $pathData['full_name'];
            $downloadDir = $pathData['download_dir'];

            $fields = $this->getRequestFields($params['field']);
            $this->requestFields = array_flip($fields);  //作为key来保存，以便判断字段是否存在时效率更快
            $this->monitorMem(2);
            $count = $this->getCount($params);
            $this->monitorMem(3);
            $this->prepareData();

            $bigPageSize = $this->bigPageSize;
            $smallPageSize = $this->smallPageSize;
            $loop = ceil($count / $bigPageSize);

            if ($loop > 1) {
                $zipFileName = $this->getZipFileName($fullName);
                $zip->open($zipFileName, ZipArchive::CREATE);
            }
            $tempFilenames = [];

            for ($i = 0; $i < $loop; $i ++) {
                $writer = $this->getWriterInstance();
                $this->writeSheetHeader($fields, $writer);
                $bigOffset = $i*$bigPageSize;
                $orderIds = $this->getIds($params, $bigOffset, $bigPageSize);
                $this->monitorMem( "4.{$i}");
                $smallCount = count($orderIds);
                $lo = ceil($smallCount / $smallPageSize);
                for ($j=0; $j<$lo; $j++) {
                    $ids = array_slice($orderIds, $j*$smallPageSize, $smallPageSize);
                    $this->queryData($ids);
                    if ($j<2) {
                        $this->monitorMem( "4.{$j}.1");
                    }
                    $data = $this->restructData();
                    if ($j<2) {
                        $this->monitorMem("4.{$j}.2");
                    }
                    foreach ($data as $a => $r) {
                        $writer->writeSheetRow('Sheet1', $r);
                    }
                    unset($ids);
                    unset($data);
                }

                if ($loop > 1) {
                    $tempFilename = $this->renameFileName($fullName, $bigOffset, $smallCount<$bigPageSize ? $smallCount : $bigPageSize);
                    $tempFilenames[] = $tempFilename;
                    $writer->writeToFile($tempFilename);
                }else {
                    $writer->writeToFile($fullName);
                }
            }

            if (!empty($tempFilenames)) {
                foreach ($tempFilenames as $tempFilename) {
                    $zip->addFile($tempFilename, basename($tempFilename));
                }
                $zip->close();
                //删除源文件
                foreach ($tempFilenames as $tempFilename) {
                    if (!empty($tempFilename)) {
                        @unlink($tempFilename);
                    }
                }

                $downloadUrl = $downloadDir.basename($zipFileName);
                $fullName = $zipFileName;
            } else {
                $downloadUrl = $downloadDir.basename($fullName);
            }

            $this->monitorMem("5");
            if (is_file($fullName)) {
                $applyRecord['exported_time'] = time();
                $applyRecord['download_url'] = $downloadUrl;
                $applyRecord['status'] = 1;
                (new ReportExportFiles())->where(['id' => $params['apply_id']])->update($applyRecord);
            } else {
                throw new Exception('文件写入失败');
            }
        }catch (\Exception $e) {
            isset($zip) && @$zip->close();
            Cache::handler()->hset(
                'hash:report_export',
                $params['apply_id'].'_'.time(),
                '申请id: ' . $params['apply_id'] . ',导出失败:' . $e->getMessage() . $e->getFile() . $e->getLine());
            $applyRecord['status'] = 2;
            $applyRecord['error_message'] = $e->getMessage();
            (new ReportExportFiles())->where(['id' => $params['apply_id']])->update($applyRecord);
        }
    }

    /**
     * 构建字段
     * @param $fields
     * @return array
     */
    protected function buildFields($fields)
    {
        $newFields = [];
        foreach ($fields as $t => $fs) {
            foreach ($fs as $f) {
                $newFields[] = "{$t}.{$f}";
            }
        }
        $newFields = array_unique($newFields);

        return $newFields;
    }

    /**
     * 准备公共数据
     * @throws Exception
     */
    private function prepareData()
    {
        $this->countryData = Cache::store('country')->getCountry();
    }

    private function getWriterInstance()
    {
        return new \XLSXWriter();
    }

    /**
     * 获取路径信息
     * @param array $params
     * @return array
     * @throws Exception
     */
    private function getPathData(array $params)
    {
        $fileName = $params['file_name'];
        $downLoadDir = '/download/order_detail/';
        $saveDir = ROOT_PATH . 'public' . $downLoadDir;
        if (!is_dir($saveDir) && !mkdir($saveDir, 0777, true)) {
            throw new Exception('导出目录创建失败');
        }
        $fullName = $saveDir . $fileName;

        return ['full_name'=>$fullName, 'download_dir'=>$downLoadDir];
    }

    /**
     * 写入excel标题行
     * @param $fields
     * @param \XLSXWriter $writer
     */
    protected function writeSheetHeader($fields, \XLSXWriter $writer)
    {
        $titles = $this->title();
        foreach ($fields as $k => $v) {
            if (isset($titles[$v])) {
                $titleData[$v] = $titles[$v];
            }
        }
        list($titleMap, $dataMap) = $this->getExcelMap($titleData);
        $titleOrderData = [];
        foreach ($titleMap as $k => $title){
            $titleOrderData[$title['title']] = 'string';
        }
        //国家信息
        $writer->writeSheetHeader('Sheet1', $titleOrderData);
    }

    /**
     * 获取导出的字段
     * @param $fields
     * @return array
     */
    private function getRequestFields($fields)
    {
        $titles = $this->title();
        if (empty($fields)) {
            foreach ($titles as $k=>$title) {
                if ($title['is_show'] == 1) {
                    $fields[] = $title['title'];
                }
            }
        }else{
            $allFields = array_flip(array_keys($titles));
            foreach ($fields as $k=>$field) {
                if (!isset($allFields[$field])) {
                    unset($fields[$k]);
                }
            }
        }

        return $fields;
    }

    /**
     * 检查参数
     * @param $params
     * @throws Exception
     */
    protected function checkParams($params)
    {
        if (!isset($params['apply_id']) || empty($params['apply_id'])) {
            throw new Exception('导出申请id获取失败');
        }
        if (!isset($params['file_name']) || empty($params['file_name'])) {
            throw new Exception('导出文件名未设置');
        }
    }

    /**
     * 监控内存，写到redis中
     * @param $step
     */
    public function monitorMem($step)
    {
        self::$logTextArr[] = [date('Y-m-d H:i:s').", {$step}当前分配内存:".floor(memory_get_usage()/1024/1014)."M", date('Y-m-d H:i:s').", 内存峰值为:".floor(memory_get_peak_usage()/1024/1014)."M". ' 时间戳:'.$this->getMicroTime()];
        Cache::handler(false, 1)->setex('string:excel_report_test_order_queue_'.self::$logId, 86400, json_encode( self::$logTextArr, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
    }

    private function getMicroTime()
    {
        return array_sum(explode( ' ', microtime()));
    }

    /**
     * 获取zip文件名称，超过100万行数据后需要分文件,多个文件打包压缩成一个zip文件
     * @param $xlsxFileName
     * @return string
     */
    private function getZipFileName($xlsxFileName)
    {
        $fileNameArr = explode('.', $xlsxFileName);
        count($fileNameArr)>1 && array_pop($fileNameArr);
        array_push($fileNameArr, 'zip');
        $zipFileName = implode('.', $fileNameArr);

        return $zipFileName;
    }

    /**
     * 超过100万行数据后需要分文件，重命名文件名称
     * @param $filename
     * @param $bigOffset
     * @param $bigPageSize
     * @return mixed|string
     */
    private function renameFileName($filename, $bigOffset, $bigPageSize)
    {
        $extention = '.xlsx';
        $tempFilename = str_replace($extention, '',$filename);
        $tempFilename = $tempFilename."(".($bigOffset+1)."-".($bigOffset+$bigPageSize).'行)';
        $tempFilename = $tempFilename.$extention;

        return $tempFilename;
    }

    /**
     * 获取账号ID，用于订单模块导出
     * @param $params
     * @return array|mixed
     */
    protected function getAccountIdByPermissionForOrder($params)
    {
        $accountIds = [];
        if (!$this->isAdmin()) {
            $object = new Filter(OrderByAccountFilter::class);
            if (isset($params['user_id']) && $params['user_id'] > 0) {
                $object->setUserId($params['user_id']);
            }
            if ($object->filterIsEffective()) {
                $accountIds = $object->getFilterContent();
            }

            if (isset($params['account_id']) && $params['account_id']) {
                if (isset($params['channel_id']) && $params['channel_id']) {
                    if (is_json($params['account_id'])) {
                        $array = json_decode($params['account_id'], true);
                        foreach ($array as $v) {
                            $virtual = $params['channel_id'] * OrderType::ChannelVirtual + $v;
                            array_push($accountIds, $virtual);
                        }

                    } else {
                        $virtual = $params['channel_id'] * OrderType::ChannelVirtual + $params['channel_id'];
                        array_push($accountIds, $virtual);
                    }
                }
            }
        }

        return $accountIds;
    }

    public function exportOnLine()
    {

    }

    /**
     * 查询导出总数
     * @param $params
     * @return int
     */
    protected function getCount($params)
    {
        return 0;
    }

    /**
     * 根据id集合，查询和准备数据
     * @param $ids
     * @return array
     */
    protected function queryData($ids)
    {
        return [];
    }

    /**
     * 所有字段与标题
     * @return array
     */
    public function title()
    {
        return [];
    }

    protected function getIds($params, $offset, $pageSize)
    {
        return [];
    }

    /**
     * 重组数据
     * @return array
     */
    protected function restructData()
    {
        return [];
    }

}