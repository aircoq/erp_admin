<?php
namespace app\common\service;
use app\common\cache\Cache;
use app\common\model\LogExportDownloadFiles;
use app\report\model\ReportExportFiles;
use think\Loader;
use app\common\exception\JsonErrorException;
use think\Exception;
Loader::import('phpExcel.PHPExcel', VENDOR_PATH);
require_once(APP_PATH.'/../extend/XLSXWriter/xlsxwriter.class.php');
/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/11/2
 * Time: 17:44
 */
class ImportExport
{
    /**
     * 导出excel方法
     * @param array $data 需要导出的数据
     * @param array $title excel表头
     * @param string $name 导出后的文件名
     * excelExport($arr,array('id','账户','密码','昵称'),'文件名!');
     */
    public static function excelExport($data, $title = [], $name = '')
    {
        $PHPExcel = new \PHPExcel();
        if (!empty($title)) {
            array_unshift($data, $title);
        }
        if (empty($name)) {
            $name = time();
        }
        foreach ($data as $k => $v) {
            $count = count($v);
            for ($i = 1; $i <=$count; $i++) {
                $tr = self::getLetter($i).($k+1);
                //PHP版本低，代码不兼容
//                $buffer[$tr]=array_values($v)[$i-1];
//                $PHPExcel->getActiveSheet()->setCellValue($tr, array_values($v)[$i-1]);
                $arr = array_values($v);
                $j = ($i-1);
                $buffer[$tr]=$arr[$j];
                $PHPExcel->getActiveSheet()->setCellValue($tr, $arr[$j]);
            }
        }
//         $PHPExcel->setActiveSheetIndex(0);
        header( "Accept-Ranges:  bytes ");
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename=' . $name . '.xls'); //文件名称
        header('Cache-Control: max-age=0');
        $result = \PHPExcel_IOFactory::createWriter($PHPExcel, 'Excel2007');
        $result->save('php://output');
    }

    public static function excelExportSmallPlat($data, $title = [], $name = '',$path)
    {
        try {
            $PHPExcel = new \PHPExcel();
            if (!empty($title)) {
                array_unshift($data, $title);
            }
            if (empty($name)) {
                $name = time();
            }
            $bgColor = ['FF008080', 'FF00FA9A', 'FF556B2F', 'FF66CDAA', 'FF778899', 'FF8A2BE2', 'FFA0522D', 'FFBC8F8F', 'FFD3D3D3', 'FFEEE8AA', 'FFFFD700','FF00FFFF','FF5F9EA0'];
            $PHPExcel->getActiveSheet()->getDefaultRowDimension()->setRowHeight(-1);
            $colorIndex = 0;
            $oldvalue = '';
            foreach ($data as $k => $v) {
                $count = count($v);
                for ($i = 1; $i <= $count; $i++) {
                    $tr = self::getLetter($i) . ($k + 1);
                    $arr = array_values($v);
                    $j = ($i - 1);
                    $buffer[$tr] = $arr[$j];
                    if($k>0 && $oldvalue==$arr[0]){
                        //Set fills 设置填充
                        $PHPExcel->getActiveSheet()->getStyle($k+1)->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID);
                        $PHPExcel->getActiveSheet()->getStyle($k+1)->getFill()->getStartColor()->setARGB($bgColor[$colorIndex%13]);
                    }else if($k>0){
                        $colorIndex++;//用新颜色
                        $oldvalue = $arr[0];
                        //Set fills 设置填充
                        $PHPExcel->getActiveSheet()->getStyle($k+1)->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID);
                        $PHPExcel->getActiveSheet()->getStyle($k+1)->getFill()->getStartColor()->setARGB($bgColor[$colorIndex%13]);
                    }
                    $PHPExcel->getActiveSheet()->getColumnDimension(self::getLetter($i))->setWidth(30);
                    //设置字体
                    $PHPExcel->getActiveSheet()->getStyle($tr)->getFont()->setName('宋体')->setSize(11);
                    $PHPExcel->getActiveSheet()->setCellValue($tr, $arr[$j]);
                }
            }
            $result = \PHPExcel_IOFactory::createWriter($PHPExcel, 'Excel2007');
            $result->save($path);
            return ['result'=>true];
        }catch (\PHPExcel_Exception $e){
            return ['message'=>$e->getFile().'|'.$e->getLine().'|'.$e->getMessage(),'result'=>false];
        }
    }

    /**
     * 导入excel方法
     * @param path string 文件名（路径）
     * @return bool|array
     */
    public static function excelImport($file)
    {
        $PHPReader = new \PHPExcel_Reader_Excel2007();
        if (!$PHPReader->canRead($file)) {
            $PHPReader = new \PHPExcel_Reader_Excel5();
            if (!$PHPReader->canRead($file)) {
                return false;
            }
        }
        $ex = $PHPReader->load($file);
        $cur = $ex->getSheet(0);  // 读取第一个表
        $end = $cur->getHighestColumn(); // 获得最大的列数
        $line = $cur->getHighestRow(); // 获得最大总行数
        // 获取数据数组
        $info = $data = $dataList = [];
        for ($row = 1; $row <= $line; $row ++) {
            for ($column = 'A'; $column <= $end; $column ++) {
                $val = $cur->getCellByColumnAndRow(ord($column) - 65, $row)->getValue();
                $info[$row][] = $val;
            }
        }
        for ($i = 2; $i <= count($info); $i ++) {
            for ($j = 0; $j < count($info[$i]); $j ++) {
                for ($k = 0; $k < count($info[1]); $k ++) {
                    $data[$i][trim($info[1][$k])] = $info[$i][$k];
                }
            }
        }
        $dataList = array_values($data);
        if ($dataList) {
            return $dataList;
        }
        return [];
    }
    
    /**
     * 导入csv方法
     * @param unknown $file : 文件路径
     * $data ： 返回所有数据
     */
    public static function csvImport($file,$transCode=1)
    {
        if (empty($file)) {
            return false;
        }
        $fileHandle = @fopen($file, 'r');
        $data = [];
        while ($row = fgetcsv($fileHandle)) {
            if ($transCode) {
                $data[] = array_map(function ($a) {
                    return mb_convert_encoding($a,'utf8','gbk,utf8');
                },$row);
            } else {
                $data[] = $row;
            }
//            $data[] = eval('return '.iconv('gbk', 'utf-8', var_export($row, true)).';');
        }
        fclose($fileHandle);
        @unlink($file);
        return $data;
    }
    
    /**
     * 数字转字母
     * @param unknown $num
     * @return string
     */
    public static function getLetter($num) 
    {
        $str = $num;
        $num = intval($num);
        if ($num <= 26) {
            $ret = chr(ord('A') + intval($str) - 1);
        } else {
            $first_str = chr(ord('A') + intval(floor($num / 26)) - 1);
            $second_str = chr(ord('A') + intval($num % 26) - 1);
            if ($num % 26 == 0){
                $first_str = chr(ord('A') + intval(floor($num / 26)) - 2);
                $second_str = chr(ord('A') + intval($num % 26) + 25);
            }
            $ret = $first_str.$second_str;
        }
        return $ret;
    }
    
    
    /**
     * 上传文件
     * @param Request $request
     * @param type $pathName
     * @param type $fileName
     * @return string
     * @throws Exception
     */
    public function uploadFile($baseData, $pathName)
    {
    
        if (!$baseData) {
            throw new JsonErrorException('未检测到文件');
        }
        $dir = date('Y-m-d');
        $base_path = ROOT_PATH . 'public' . DS . 'upload' . DS . $pathName . DS . $dir;
    
        if (!is_dir($base_path) && !mkdir($base_path, 0777, true)) {
            throw new JsonErrorException('目录创建失败');
        }
    
        try {
    
            $fileName = $pathName . date('YmdHis') . '.xlsx';
            $start = strpos($baseData, ',');
            $content = substr($baseData, $start + 1);
            file_put_contents($base_path . DS . $fileName, base64_decode(str_replace(" ", "+", $content)));
            return $base_path . DS . $fileName;
    
        } catch (Exception $ex) {
            throw new JsonErrorException($ex->getMessage());
        }
    }


    public static function excelImportByIndex($file,$index)
    {
        $PHPExcel = new \PHPExcel();
        $PHPReader = new \PHPExcel_Reader_Excel2007();
        if (!$PHPReader->canRead($file)) {
            $PHPReader = new \PHPExcel_Reader_Excel5();
            if (!$PHPReader->canRead($file)) {
                return false;
            }
        }
        $ex = $PHPReader->load($file);
        $cur = $ex->getSheet($index);  // 读取第N个活动表格
        $end = $cur->getHighestColumn(); // 获得最大的列数
        $line = $cur->getHighestRow(); // 获得最大总行数
        $endIndex = \PHPExcel_Cell::columnIndexFromString($end);//由列名转为列数('AB'->28)
        // 获取数据数组
        $info = $data = $dataList = [];
        for ($row = 1; $row <= $line; $row ++) {
            for ($column = 0; $column <= $endIndex; $column ++) {
                $columnName = \PHPExcel_Cell::stringFromColumnIndex($column);//由列数反转列名(0->'A')
                $val = $cur->getCell($columnName . $row)->getValue();
                $info[$row][] = $val;
            }
        }
        for ($i = 2; $i <= count($info); $i ++) {
            for ($j = 0; $j < count($info[$i]); $j ++) {
                for ($k = 0; $k < count($info[1]); $k ++) {
                    $data[$i][trim($info[1][$k])] = $info[$i][$k];
                }
            }
        }
        $dataList = array_values($data);
        if ($dataList) {
            return $dataList;
        }
        return [];

    }


    /**
     * 写入excel
     * @param $data
     * @param array $title
     * @param string $path
     * @throws Exception
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     */
    public static function excelSave($data, $title = [], $path = '')
    {
        $PHPExcel = new \PHPExcel();
        if (!empty($title)) {
            array_unshift($data, $title);
        }
        if (empty($path)) {
            throw new Exception('the path is need');
        }
        foreach ($data as $k => $v) {
            $count = count($v);
            for ($i = 1; $i <=$count; $i++) {
                $tr = self::getLetter($i).($k+1);
                $arr = array_values($v);
                $j = ($i-1);
                $buffer[$tr]=$arr[$j];
                $PHPExcel->getActiveSheet()->setCellValue($tr, $arr[$j]);
            }
        }
        $PHPExcel->setActiveSheetIndex(0);
        $result = \PHPExcel_IOFactory::createWriter($PHPExcel, 'Excel2007');
        $result->save($path);
    }

    /**
     * @param $data
     * @param $name
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    public static function excelExportWithSet($data,$masterTitle,$subTitle,$name,$path)
    {
        try {
            $PHPExcel = new \PHPExcel();
            if (empty($name)) {
                $name = time();
            }

            $bgColor = ['FF008080', 'FF00FA9A', 'FF556B2F', 'FF66CDAA', 'FF778899', 'FF8A2BE2', 'FFA0522D', 'FFBC8F8F', 'FFD3D3D3', 'FFEEE8AA', 'FFFFD700','FF00FFFF','FF5F9EA0'];
            //自动调整行高
            $PHPExcel->getActiveSheet()->getDefaultRowDimension()->setRowHeight(-1);
//            $PHPExcel->getActiveSheet()->getColumnDimension()->setWidth(50);
            //先处理标题
            $offset = 0;
            $i = 0;
            foreach ($subTitle as $k => $v) {
                //合并开始位置
                $start = self::getLetter($offset+1) . '1';
                //合并结束位置
                $end = self::getLetter(count($v) + $offset) . '1';
                //合并单元格
                $PHPExcel->getActiveSheet()->mergeCells($start.':'.$end);
                //设置水平居中
                $PHPExcel->getActiveSheet()->getStyle($start.':'.$end)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                //设置填充颜色
                $PHPExcel->getActiveSheet()->getStyle($start.':'.$end)->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID);
                $PHPExcel->getActiveSheet()->getStyle($start.':'.$end)->getFill()->getStartColor()->setARGB($bgColor[$i]);
                //设置字体
                $PHPExcel->getActiveSheet()->getStyle($start.':'.$end)->getFont()->setName('宋体')->setSize(18)->setBold(true);
                //设置大标题
                $PHPExcel->getActiveSheet()->setCellValue($start, $masterTitle[$i]);
                //设置小标题
                foreach ($v as $value) {
                    $offset++;
                    $tr = self::getLetter($offset) . '2';//写在第二行
                    $PHPExcel->getActiveSheet()->getColumnDimension(self::getLetter($offset))->setWidth(15);
                    $PHPExcel->getActiveSheet()->setCellValue($tr, $value);
                }
                $i++;
            }
            //写数据

            $row=3;//行,第三行开始
            foreach ($data as $k => $v) {//循环行
                $col=1;//列
                $row_offset = 0;//行偏移
                foreach ($v as $key=>$value) {//循环列
                   $coltmp = self::getLetter($col);
                    $tr = $coltmp .$row;
                    if(is_array($value)){
                        foreach($value as $kk=>$vv){
                            $PHPExcel->getActiveSheet()->setCellValue($tr, $vv);
                            $tr = $coltmp.($row+1+$kk);//列不变，行偏移
                        }
                        $row_offset = max($row_offset,count($value));
                    }else {
                        $PHPExcel->getActiveSheet()->setCellValue($tr, $value);
                    }
                    $col++;
                }
                $row += max($row_offset,1);
            }
            $result = \PHPExcel_IOFactory::createWriter($PHPExcel, 'Excel2007');
            $result ->save($path);
            return ['result'=>true];
        }catch (\PHPExcel_Exception $e){
            return ['message'=>$e->getFile().'|'.$e->getLine().'|'.$e->getMessage(),'result'=>false];
        }
    }

    /**
     * 导入excel方法,旧方法有问题，重新编写
     * @param $file string 文件名（路径）
     * @return array
     */
    public static function excelImportNew($file)
    {
        try {
            $PHPExcel = new \PHPExcel();
            $PHPReader = new \PHPExcel_Reader_Excel2007();
            if (!$PHPReader->canRead($file)) {
                $PHPReader = new \PHPExcel_Reader_Excel5();
                if (!$PHPReader->canRead($file)) {
                    return json(['message'=>'不支持的文件格式','result'=>false],500);
                }
            }
            $ex = $PHPReader->load($file);
            $cur = $ex->getSheet(0);  // 读取第一个表
            $end = $cur->getHighestColumn(); // 获得最大的列数
            $line = $cur->getHighestRow(); // 获得最大总行数

            $endNum = self::getNumber($end);//转换成数字

            // 获取数据数组
            $info = $data = $dataList = [];
            for ($row = 1; $row <= $line; $row++) {
                for ($column = 0; $column < $endNum; $column++) {
                    $val = $cur->getCellByColumnAndRow($column, $row)->getValue();
                    $info[$row][] = $val;
                }
            }
            $dataList = array_values($info);
            return $dataList;
        }catch (\PHPExcel_Exception $e){
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }

    /**
     * 根据输入的字符串返回列的索引
     * 例如：输入'AB',返回28
     * @param $str
     * @return float|int
     */
    public static function getNumber($str){
        $len = strlen($str);
        $num = 0;
        if($len == 1){
            $num = ord($str)-64;
        }else if($len == 2){
            $num = (ord($str[0])-64)*26+ord($str[1])-64;
        }else{
            //太大了不考虑
            $num = 0;
        }
        return $num;
    }

    /**
     * 导出
     * @param $lists
     * @param $header
     * @param $file
     * @return array
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    public static function export($lists, $header, $file)
    {
        $result = ['status' => 0, 'message' => 'error'];
        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->getProperties()->setCreator("Rondaful")
            ->setLastModifiedBy("Rondaful")
            ->setTitle($file['name'] . "数据")
            ->setSubject($file['name'] . "数据")
            ->setDescription($file['name'] . "数据")
            ->setKeywords($file['name'] . "数据")
            ->setCategory($file['name'] . "数据");
        $objPHPExcel->setActiveSheetIndex(0);
        //本行title有最大31个字符的限制，有的下载要求把下载条件放在名称后面会导致title超长，所以在这里把title分出来；
        $title = empty($file['title'])? $file['name']. '数据' : $file['title'];
        $objPHPExcel->getActiveSheet()->setTitle($title);

        /*生成标题*/
        $index = 1;
        for ($i = 0; $i < count($header); $i++) {
            $objPHPExcel->getActiveSheet()->setCellValue((self::getLetter($i+1)) . "{$index}", $header[$i]['title']);
            $objPHPExcel->getActiveSheet()->getColumnDimension(self::getLetter($i+1))->setWidth($header[$i]['width']);
            $objPHPExcel->getActiveSheet()->getStyle(self::getLetter($i+1))->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_TEXT);
        }

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $fileName = $file['name'];
        $downFileName = $fileName . '.xlsx';

        foreach ($lists as $key => $value) {
            $index++;
            for ($i = 0; $i < count($header); $i++) {
                $objPHPExcel->getActiveSheet()->setCellValue(self::getLetter($i+1) . "{$index}", $value[$header[$i]['key']]);
            }
        }
        $file = ROOT_PATH . 'public' . DS . 'download' . DS . $file['path'];
        $filePath = $file . DS . $downFileName;
        //无文件夹，创建文件夹
        if (!is_dir($file) && !mkdir($file, 0777, true)) {
            $result['message'] = '创建文件夹失败。';
            @unlink($filePath);
            return $result;
        }
        $objWriter->save($filePath);
        $logExportDownloadFiles = new LogExportDownloadFiles();
        try {
            $data = [];
            $data['file_extionsion'] = 'xlsx';
            $data['saved_path'] = $filePath;
            $data['download_file_name'] = $downFileName;
            $data['type'] = 'supplier_export';
            $data['created_time'] = time();
            $data['updated_time'] = time();
            $logExportDownloadFiles->allowField(true)->isUpdate(false)->save($data);
            $udata = [];
            $udata['id'] = $logExportDownloadFiles->id;
            $udata['file_code'] = date('YmdHis') . $logExportDownloadFiles->id;
            $logExportDownloadFiles->allowField(true)->isUpdate(true)->save($udata);
        } catch (\Exception $e) {
            $result['message'] = '创建导出文件日志失败。' . $e->getMessage();
            @unlink($filePath);
            return $result;
        }
        $result['status'] = 1;
        $result['message'] = 'OK';
        $result['file_code'] = $udata['file_code'];
        $result['file_name'] = $fileName;
        $result['file_path'] = $filePath;
        return $result;
    }

    public static function exportCsv($lists, $header, $file=[], int $transCode = 0, int $isQueue = 0)
    {
        $result = ['status' => 0, 'message' => 'error'];
        try {
            $aHeader = [];
            foreach ($header as $v) {
                if ($transCode) {
                    $v['title'] = mb_convert_encoding($v['title'], 'gbk', 'utf-8');
                }
                $aHeader[] = $v['title'];
            }

            $fileName = $file['name'];
            $pathCode = $file['path'];
            $downFileName = $fileName . '.csv';
            $file = ROOT_PATH . 'public' . DS . 'download' . DS . $file['path'];
            $filePath = $file . DS . $downFileName;
            //无文件夹，创建文件夹
            if (!is_dir($file) && !mkdir($file, 0777, true)) {
                $result['message'] = '创建文件夹失败。';
                @unlink($filePath);
                return $result;
            }
            $fp = fopen($filePath, 'a');
            fputcsv($fp, $aHeader);
            foreach ($lists as $i => $row) {
                $rowContent = [];
                foreach ($header as $h) {
                    $field = $h['key'];
                    $value = isset($row[$field]) ? $row[$field] : '';
                    if ($transCode) {
                        $value = mb_convert_encoding($value,'gbk','utf-8');
                    }
                    $rowContent[] = is_numeric($value) ? $value . "\t" : $value; // 避免数字过长导致打开变科学计数法
                }
                fputcsv($fp, $rowContent);
            }
            fclose($fp);
            try {
                $logExportDownloadFiles = new LogExportDownloadFiles();
                $data = [];
                $data['file_extionsion'] = 'csv';
                $data['saved_path'] = $filePath;
                $data['download_file_name'] = $downFileName;
                $data['type'] = 'supplier_export';
                $data['created_time'] = time();
                $data['updated_time'] = time();
                $logExportDownloadFiles->allowField(true)->isUpdate(false)->save($data);
                $udata = [];
                $udata['id'] = $logExportDownloadFiles->id;
                $udata['file_code'] = date('YmdHis') . $logExportDownloadFiles->id;
                $logExportDownloadFiles->allowField(true)->isUpdate(true)->save($udata);
            } catch (\Exception $e) {
                $result['message'] = '创建导出文件日志失败。' . $e->getMessage();
                @unlink($filePath);
                return $result;
            }
            $result['status'] = 1;
            $result['message'] = 'OK';
            $result['file_code'] = $udata['file_code'];
            $result['file_name'] = $fileName;
            if ($isQueue) { // 队列导出
                $result['file_path'] = $filePath;
                $result['download_url'] = DS .'download'. DS . $pathCode . DS .$downFileName;
            }

            return $result;
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    public static function xlsxwriterExport($header,$data,$file,$applyId=0)
    {
        try {
            if ($applyId) {
                $applyRecord = ReportExportFiles::get($applyId);
            }
            $fileName = $file['name'];
            $pathCode = $file['path'];
            $downFileName = $fileName . '.xlsx';
            $file = ROOT_PATH . 'public' . DS . 'download' . DS . $file['path'];
            $filePath = $file . DS . $downFileName;
            //无文件夹，创建文件夹
            if (!is_dir($file) && !mkdir($file, 0777, true)) {
                @unlink($filePath);
                throw new Exception('创建文件夹失败');
            }
            $writer = new \XLSXWriter();
            $writer->writeSheetHeader('Sheet1', $header);
            foreach ($data as $dt) {
                $writer->writeSheetRow('Sheet1', $dt);
            }
            $writer->writeToFile($filePath);

            if (is_file($filePath)) {//导出成功
                if ($applyId) {//记录报表管理表
                    $applyRecord['exported_time'] = time();
                    $applyRecord['download_url'] = DS .'download'. DS . $pathCode . DS .$downFileName;
                    $applyRecord['status'] = 1;
                    $applyRecord->isUpdate()->save();
                }
                //记录导出日志
                $logExportDownloadFiles = new LogExportDownloadFiles();
                $data = [];
                $data['file_extionsion'] = 'xlsx';
                $data['saved_path'] = $filePath;
                $data['download_file_name'] = $downFileName;
                $data['type'] = 'supplier_export';
                $data['created_time'] = time();
                $data['updated_time'] = time();
                $logExportDownloadFiles->allowField(true)->isUpdate(false)->save($data);
                $udata = [];
                $udata['id'] = $logExportDownloadFiles->id;
                $udata['file_code'] = date('YmdHis') . $logExportDownloadFiles->id;
                $logExportDownloadFiles->allowField(true)->isUpdate(true)->save($udata);
                $result['status'] = 1;
                $result['message'] = 'OK';
                $result['file_code'] = $udata['file_code'];
                $result['file_name'] = $fileName;
                return $result;
            }
            if ($applyId) {
                $applyRecord['status'] = 2;
                $applyRecord['error_message'] = '文件生成失败';
                $applyRecord->isUpdate()->save();
                Cache::handler()->hset(
                    'hash:report_export',
                    'error_' . time(),
                    '申请id: ' . $applyId . ',导出失败:文件生成失败');
            }          
           throw new Exception('文件生成失败');
        } catch (\Throwable $e) {
            if ($applyId) {
                $applyRecord['status'] = 2;
                $applyRecord['error_message'] = $e->getMessage();
                $applyRecord->isUpdate()->save();
                Cache::handler()->hset(
                    'hash:report_export',
                    'error_' . time(),
                    '申请id: ' . $applyId . ',导出失败:'.$e->getMessage());
            }
            throw new Exception('文件生成失败:'.$e->getMessage());
        }
    }

}