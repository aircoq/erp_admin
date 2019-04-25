<?php

use think\Db;
use think\Loader;
use think\Exception;
use app\common\exception\ParameterInvalidException;


define('IGNORE_USER_ABORT', ignore_user_abort(true));

// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------


/***
 * 字符串命名风格转换
 * type 0 将Java风格转换为C的风格 1 将C风格转换为Java的风格
 * @param string $name 字符串
 * @param integer $type 转换类型
 * @param bool $ucfirst 首字母是否大写（驼峰规则）
 * @return string
 */
function parseName($name, $type = 0, $ucfirst = true)
{
    if ($type) {
        $name = preg_replace_callback('/_([a-zA-Z])/', function ($match) {
            return strtoupper($match[1]);
        }, $name);
        return $ucfirst ? ucfirst($name) : lcfirst($name);
    } else {
        return strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $name), "_"));
    }
}

// 应用公共文件
if (!function_exists('snake')) {
    /**
     * Convert a string to snake case.
     * eg:translate helloWorld  to hello_world
     * @param  string $value
     * @param  string $delimiter
     * @return string
     */
    function snake($value, $delimiter = '_')
    {
        $replace = '$1' . $delimiter . '$2';

        return ctype_lower($value) ? $value : strtolower(preg_replace('/(.)([A-Z])/', $replace, $value));
    }
}
if (!function_exists('snakeArray')) {
    /**
     * Convert a string to snake case.
     * eg:translate helloWorld  to hello_world
     * @param  string $value
     * @param  string $delimiter
     * @return string
     */
    function snakeArray($arr)
    {

        if (!is_array($arr)) {   //如果非数组原样返回
            return $arr;
        }
        $temp = [];
        foreach ($arr as $key => $value) {
            $key = (string)$key; //in_array，
            //$value = is_json($value)?json_decode($value,true):$value;
            if (!in_array($key, ['error_code', 'error_message', 'request_id', 'sub_code', 'sub_msg'])) {
                $key1 = parseName($key);
                $value1 = snakeArray($value);
                $temp[$key1] = $value1;
            } else {
                $temp[$key] = $value;
            }
        }
        return $temp;
    }
}
if (!function_exists('unsnakeArray')) {
    /**
     * Convert a string to snake case.
     * eg:translate helloWorld  to hello_world
     * @param  string $value
     * @param  string $delimiter
     * @return string
     */
    function unsnakeArray($arr, $ucfirst = FALSE)
    {

        if (!is_array($arr)) {   //如果非数组原样返回
            return $arr;
        }
        $temp = [];
        $keys = '';
        foreach ($arr as $key => $value) {
            $key = (string)$key; //in_array，
            //$value = is_json($value)?json_decode($value,true):$value;
            if (!in_array($key, ['error_code', 'error_message', 'request_id', 'sub_code', 'sub_msg'])) {
                $key1 = unsnake($key, FALSE);
                $value1 = unsnakeArray($value);
                $temp[$key1] = $value1;
            } else {
                $temp[$key] = $value;
            }
        }
        return $temp;
    }
}
function unsnake($str, $ucfirst = true)
{
    $str = ucwords(str_replace('_', ' ', $str));
    $str = str_replace(' ', '', lcfirst($str));
    return $ucfirst ? ucfirst($str) : $str;
}

if (!function_exists('deal_review_status')) {
    function deal_review_status($str)
    {
        if ($str == 'approved') //approved
        {
            $res = 1;
        } elseif ($str == 'rejected') { //rejected
            $res = 2;
        } elseif ($str == 'pending') { //pending
            $res = 3;
        } else {
            $res = 0;
        }
        return $res;
    }
}

if (!function_exists('curl_do')) {
    function curl_do($url, $post = array())
    {
        $header = array();
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        if (!empty($post)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            @curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }
}

function hashCode64($str)
{
    $str = (string)$str;
    $hash = 0;
    $len = strlen($str);
    if ($len == 0)
        return $hash;

    for ($i = 0; $i < $len; $i++) {
        $h = $hash << 5;
        $h -= $hash;
        $h += ord($str[$i]);
        $hash = $h;
        $hash &= 0xFFFFFFFF;
    }
    return $hash;
}

/**
 * 字符串时间转换为int时间
 * @param string $time 03-30-2017T05:00:35
 * @return int
 */

if (!function_exists('str2time')) {
    function str2time($time)
    {
        if ($time) {
            if (strlen($time) >= 11) {
                $m = substr($time, 0, 2);
                $d = substr($time, 3, 2);
                $y = substr($time, 6, 4);
                $t = substr($time, 11);
                $new = $y . $m . $d . $t;
            } else {
                $m = substr($time, 0, 2);
                $d = substr($time, 3, 2);
                $y = substr($time, 6, 4);
                $t = substr($time, 11);
                $new = $y . $m . $d;
            }
            return strtotime($new);
        } else {
            return 0;
        }


    }
}

/** 时间分区
 * @param $model
 * @param $timestamp
 * @param $field
 * @param array $date [order_date]  分区字段
 * @param array $date [2013,2014,2015,2016]  默认分区需要传，普通分区不需要传该值
 * @param $shift
 * @return bool
 */
function time_partition($model, $timestamp, $field = null, $date = [], $shift = false)
{
    //opcache_reset();
    $model = strtolower(preg_replace('/((?<=[a-z])(?=[A-Z]))/', '_', $model));
    $model = explode('\\', $model);
    $table = array_pop($model);
    if (!empty($date)) {
        sort($date);
        foreach ($date as $k => $v) {
            for ($i = 0; $i < 12; $i++) {
                $current[1] = $i + 1;
                $current[0] = $date[$k];
                $result = merge_date($current, $date, $k);
                if ($result) {
                    if (!$shift) {
                        return execute($table, $result['start'], $result['less'], $field);
                    } else {
                        $twitterStart = 1262275200000;   //2010-01-01
                        $twitterTime = $result['less'] * 1000;
                        $nextId = (($twitterTime - $twitterStart) << 22) | (31 << 5) | (4095 << 12) | 0;
                        $result['less'] = $nextId;
                        return execute($table, $result['start'], $result['less'], $field);
                    }
                }
            }
        }
    } else {
        $current = explode('-', date('Y-m', $timestamp));
        if (!isset($current[1])) {
            return false;
        }
        $result = merge_date($current);
        if ($result) {
            if (!$shift) {
                return execute($table, $result['start'], $result['less'], $field);
            } else {
                $twitterStart = 1262275200000;   //2010-01-01
                $twitterTime = $result['less'] * 1000;
                $nextId = (($twitterTime - $twitterStart) << 22) | (31 << 5) | (4095 << 12) | 0;
                $result['less'] = $nextId;
                return execute($table, $result['start'], $result['less'], $field);
            }
        }
    }
}

/** 执行分区语句
 * @param $table 【表名称】
 * @param $start 【分区名称段】
 * @param $less 【分区时间戳】
 * @param $field 【分区字段】
 * @return bool
 */
function execute($table, $start, $less, $field)
{
    try {
        if (!is_null($field) && !empty($field)) {
            //检查该表是否分区了
            $is_partition = Db::query("SELECT partition_name part FROM information_schema.partitions  WHERE table_schema = schema() AND table_name= '" . $table . "'");
            if (isset($is_partition[0]) && empty($is_partition[0]['part'])) {
                $str = "ALTER table `" . $table . "`  PARTITION by RANGE(" . $field . ")(PARTITION p" . $start . " values LESS THAN (" . $less . ") engine=InnoDb )";
                //开启分区
                $bool = Db::query("ALTER table `" . $table . "`  PARTITION by RANGE(" . $field . ")(PARTITION p" . $start . " values LESS THAN (" . $less . ") engine=InnoDb )");
                if ($bool) {
                    return true;
                }
                return false;
            }
        }
        //当前时间，判断分区是否存在
        //$result = Db::query('ALTER TABLE `' . $table . '` CHECK partition p' . $start);
        $result = Db::query("SELECT partition_name FROM information_schema.partitions  WHERE table_name= '" . $table . "' order by partition_name desc limit 1");
        if (empty($result)) {
            //证明分区不存在，需要创建
            $bool = Db::query('ALTER TABLE `' . $table . '` ADD PARTITION (PARTITION p' . $start . ' VALUES LESS THAN (' . $less . '))');
            if ($bool) {
                return true;
            }
        } else if (isset($result[0])) {
            $partition = $result[0]['partition_name'];
            if (strpos($partition, 'p') !== false) {
                $partition = str_replace('p', '', $partition);
            }
            if ($start > $partition) {
                $bool = Db::query('ALTER TABLE `' . $table . '` ADD PARTITION (PARTITION p' . $start . ' VALUES LESS THAN (' . $less . '))');
                if ($bool) {
                    return true;
                }
            } else {
                return true;
            }
        }
        //if (isset($result[0]) && $result[0]['Msg_type'] != 'status' && $result[0]['Msg_text'] != 'OK') {
        //证明分区不存在，需要创建
//            $bool = Db::query('ALTER TABLE `' . $table . '` ADD PARTITION (PARTITION p' . $start . ' VALUES LESS THAN (' . $less . '))');
//            if ($bool) {
//                return true;
//            }
        //} else {
        //    return true;
        // }
    } catch (Exception $e) {
        return false;
    }
}

/** 日期组合
 * @param $current 【当前日期数组】
 * @param array $date 【默认分区数组】
 * @param int $k
 * @param bool $is_start
 * @return array|bool
 */
function merge_date($current, $date = [], $k = 0, $is_start = false)
{
    if (intval($current[1]) < 10) {
        $current[1] = '0' . intval($current[1]);
    }
    $start = $current[0] . $current[1];
    if ($is_start) {
        $end = intval($current[1]);
    } else {
        $end = intval($current[1]) + 1;
    }
    if ($end > 12) {
        if (!empty($date)) {
            if (isset($date[$k + 1])) {
                $current[0] = $date[$k + 1];
            } else {
                return false;
            }
        } else {
            $current[0] = intval($current[0]) + 1;
        }
        $end = '01';
    } else {
        if ($end < 10) {
            $end = '0' . $end;
        }
    }
    $less = strtotime($current[0] . '-' . $end . '-01 00:00:00');
    return ['start' => $start, 'less' => $less];
}

/**
 *  区间搜索
 */
if (!function_exists('searchInterval')) {
    function searchInterval($timestamp, $is_start = false)
    {
        $current = explode('-', date('Y-m', $timestamp));
        if (!isset($current[1])) {
            return [];
        }
        $result = merge_date($current, [], 0, $is_start);
        $twitterStart = 1262275200000;   //2010-01-01
        $twitterTime = $result['less'] * 1000;
        $nextId = (($twitterTime - $twitterStart) << 22) | (31 << 17) | (4095 << 5) | 0;
        $result['less'] = $nextId;
        return $result;
    }
}

/**
 * 将返回的数据集转换成树
 * @param  array $list 数据集
 * @param  string $pk 主键
 * @param  string $pid 父节点名称
 * @param  string $child 子节点名称
 * @param  integer $root 根节点ID
 * @return array          转换后的树
 */
function list_to_tree($list, $pk = 'id', $pid = 'pid', $child = '_child', $root = 0)
{
    $tree = array();// 创建Tree
    if (is_array($list)) {
        // 创建基于主键的数组引用
        $refer = array();
        foreach ($list as $key => $data) {
            $refer[$data[$pk]] = &$list[$key];
        }
        foreach ($list as $key => $data) {
            // 判断是否存在parent
            $parentId = $data[$pid];
            if ($root == $parentId) {
                $tree[$data[$pk]] = &$list[$key];
            } else {
                if (isset($refer[$parentId])) {
                    $parent = &$refer[$parentId];
                    $parent[$child][$data[$pk]] = &$list[$key];
                }
            }
        }
    }
    return $tree;
}

/**
 * 根据分隔符将字符串转换成数组
 * @param  string $value 字符串
 * @param  string $one 分隔符1,可以是多个
 * @param  string $two 分隔符2
 * @return array
 */
function string_to_list($value, $one = ',;', $two = ':')
{
    $items = preg_split('/[' . $one . '\r\n]+/', trim($value, ",;\r\n"));
    if (strpos($value, $two)) {
        $array = array();
        foreach ($items as $item) {
            list($key, $val) = explode($two, $item, 2);
            $array[$key] = $val ?: $key;
        }
        return $array;
    } else {
        return $items;
    }
}

/**
 * @param $code
 */
function httpCode($code)
{
    $http = array(
        100 => "HTTP/1.1 100 Continue",
        101 => "HTTP/1.1 101 Switching Protocols",
        200 => "HTTP/1.1 200 OK",
        201 => "HTTP/1.1 201 Created",
        202 => "HTTP/1.1 202 Accepted",
        203 => "HTTP/1.1 203 Non-Authoritative Information",
        204 => "HTTP/1.1 204 No Content",
        205 => "HTTP/1.1 205 Reset Content",
        206 => "HTTP/1.1 206 Partial Content",
        300 => "HTTP/1.1 300 Multiple Choices",
        301 => "HTTP/1.1 301 Moved Permanently",
        302 => "HTTP/1.1 302 Found",
        303 => "HTTP/1.1 303 See Other",
        304 => "HTTP/1.1 304 Not Modified",
        305 => "HTTP/1.1 305 Use Proxy",
        307 => "HTTP/1.1 307 Temporary Redirect",
        400 => "HTTP/1.1 400 Bad Request",
        401 => "HTTP/1.1 401 Unauthorized",
        402 => "HTTP/1.1 402 Payment Required",
        403 => "HTTP/1.1 403 Forbidden",
        404 => "HTTP/1.1 404 Not Found",
        405 => "HTTP/1.1 405 Method Not Allowed",
        406 => "HTTP/1.1 406 Not Acceptable",
        407 => "HTTP/1.1 407 Proxy Authentication Required",
        408 => "HTTP/1.1 408 Request Time-out",
        409 => "HTTP/1.1 409 Conflict",
        410 => "HTTP/1.1 410 Gone",
        411 => "HTTP/1.1 411 Length Required",
        412 => "HTTP/1.1 412 Precondition Failed",
        413 => "HTTP/1.1 413 Request Entity Too Large",
        414 => "HTTP/1.1 414 Request-URI Too Large",
        415 => "HTTP/1.1 415 Unsupported Media Type",
        416 => "HTTP/1.1 416 Requested range not satisfiable",
        417 => "HTTP/1.1 417 Expectation Failed",
        429 => "HTTP/1.1 429 Request is limited too much",
        500 => "HTTP/1.1 500 Internal Server Error",
        501 => "HTTP/1.1 501 Not Implemented",
        502 => "HTTP/1.1 502 Bad Gateway",
        503 => "HTTP/1.1 503 Service Unavailable",
        504 => "HTTP/1.1 504 Gateway Time-out"
    );
    @header($http[$code]);
    exit;
}

/**
 * @param $data
 * @param $url
 */
function insertSearch($data, $url)
{
//    $curl = ['headimg','uploadimage'];
//    if(!in_array($url,$curl))
//    {
    $model = new app\common\model\Search();
    $content = ($url . "#" . $data);
    if (strlen($content) < 2000) {
        $temp['content'] = $content;
        $temp['created'] = date('Y-m-d H:i:s', time());
        $model->isUpdate(false)->save($temp);
    }
    //  }
}

/** 返回文件从X行到Y行的内容 （支持php5,php4）
 * @param $filename
 * @param int $startLine
 * @param int $endLine
 * @param string $method
 * @param bool|false $conversion
 * @return array|string
 */
function getFileLines($filename, $startLine = 1, $endLine = 50, $conversion = false, $method = 'rb')
{
    $content = array();
    $count = $endLine - $startLine;
    //判断php版本（因为用到 SplFileObject,PHP >= 5.1.0）
    if (version_compare(PHP_VERSION, '5.1.0', '>=')) {
        $fp = new SplFileObject($filename, $method);
        $fp->seek($startLine - 1);  //转到第N行，seek方法参数从0开始计算
        for ($i = 0; $i <= $count; ++$i) {
            $temp = $fp->fgetcsv();   //current() 获取当前行内容
            if ($conversion) {
                $temp = @eval('return ' . iconv('gbk', 'utf-8', var_export($temp, true)) . ';');
            }
            $content[] = $temp;
            $fp->next();  //下一行
        }
    } else {  //php < 5.1
        $fp = @fopen($filename, $method);
        if (!$fp) {
            return 'error:can not read file';
        }
        for ($i = 1; $i < $startLine; ++$i) {  //跳过前$startLine行
            fgets($fp);
        }
        for ($i; $i <= $endLine; ++$i) {
            $temp = fgetc($fp); //读取文件行内容
            if ($conversion) {
                $temp = eval('return ' . iconv('gbk', 'utf-8', var_export($temp, true)) . ';');
            }
            $content[] = $temp;
        }
        fclose($fp);
    }
    return $content;
    //return array_filter($content);  //过滤 false,null,''  这些值
}

/** 获取文件的总行数
 * @param $filename
 * @return int
 */
function count_line($filename)
{
    $fp = @fopen($filename, 'rb');
    $i = 0;
    while (!feof($fp)) {
        if ($data = fread($fp, 1024 * 1024 * 2)) {
            $num = substr_count($data, PHP_EOL);
            $i += $num;
        }
    }
    fclose($fp);
    return $i;
}

/** 生成二维码
 * @param $content  内容
 * @param string $name 二维码名称
 * @param string $logo logo图片
 * @param string $original 原始二维码
 * @param bool $isLocal 是否生成文件在本地
 */
function getQrCode(
    $content,
    $name = "./images/code/code.png",
    $logo = "./images/logo.png",
    $original = "./images/qr/qrCode.png",
    $isLocal = true
)
{
    Loader::import('qrcode/qrcode', EXTEND_PATH, '.php');
    $errorCorrectionLevel = 'L';//容错级别
    $matrixPointSize = 6;//生成图片大小
    //生成二维码图片
    QRcode::png($content, $original, $errorCorrectionLevel, $matrixPointSize, 2);
    $QR = $original;//已经生成的原始二维码图
    if ($logo !== false && !empty($logo)) {
        $QR = imagecreatefromstring(file_get_contents($QR));
        $logo = imagecreatefromstring(file_get_contents($logo));
        $QR_width = imagesx($QR);//二维码图片宽度
        $logo_width = imagesx($logo);//logo图片宽度
        $logo_height = imagesy($logo);//logo图片高度
        $logo_qr_width = $QR_width / 5;
        $scale = $logo_width / $logo_qr_width;
        $logo_qr_height = $logo_height / $scale;
        $from_width = ($QR_width - $logo_qr_width) / 2;
        //重新组合图片并调整大小
        imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width,
            $logo_qr_height, $logo_width, $logo_height);
    }
    if ($isLocal) {
        //输出图片,生成图片在本地
        imagepng($QR, $name);
        echo '<img src="' . $name . '">';
    } else {
        //输出图片,不生成在本地
        Header("Content-type: image/png");
        ImagePng($QR);
    }
}


/**
 * 生成PDF
 * @param [type] $html      [description]
 * @param [type] $user_id   [description]
 */
if (!function_exists('generatePdf')) {
    function generatePdf($html, $name, $format = '', $orientation = 'P')
    {
        Loader::import('tcpdf/tcpdf', EXTEND_PATH, '.class.php');  //PDF_PAGE_FORMAT
        $pdf = new TCPDF($orientation, PDF_UNIT, $format, true, 'UTF-8', false);
        try {
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor('');
            $pdf->SetTitle('');
            $pdf->SetSubject('');
            $pdf->SetKeywords('');
            $pdf->setPrintHeader(false);
            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
            $pdf->SetMargins(2, 0, 2);
            $pdf->SetHeaderMargin(0);
            $pdf->SetFooterMargin(0);
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
            $pdf->SetFont('msyh', '', 10);
            $pdf->AddPage();
            $pdf->setFooterData(array(0, 64, 0), array(255, 255, 255));
            $pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', false);
            $pdf->Output(ROOT_PATH . "/public/pdf/" . $name . ".pdf", 'F');
            return true;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
/**
 * html模板
 */
if (!function_exists('template')) {
    function template($data, $type)
    {
        $sourceNum = count($data['source']);
        $dataHtml = '';
        switch ($type) {
            case 1:
                foreach ($data['source'] as $key => $value) {
//                    $dataHtml .= '<tr>';
//                    $dataHtml .= '<td style="text-align: center;border: 1px solid #000;">' . ($key + 1) . '</td>';
//                    $dataHtml .= '<td colspan="3" style="text-align: center;border: 1px solid #000;">' . $value['channel_sku_title'] . '</td>';
//                    $dataHtml .= '<td style="text-align: center;border: 1px solid #000;">' . $value['channel_sku_quantity'] . '</td>';
//                    $dataHtml .= '<td style="text-align: center;border: 1px solid #000;">' . sprintf("%.2f", $value['channel_sku_price']) . '</td>';
//                    $dataHtml .= '<td style="text-align: center;border: 1px solid #000;">' . sprintf("%.2f",$value['channel_sku_price'] * $value['channel_sku_quantity']) . '</td>';
//                    $dataHtml .= '</tr>';
                    $dataHtml .= '<tr>';
                    $dataHtml .= '<td style="border: 1px solid #000">' . ($key + 1) . '</td>';
                    $dataHtml .= '<td style="border:1px solid #000;" colspan="2">' . $value['channel_item_id'] . '</td>';
                    $dataHtml .= '<td style="border:1px solid #000;" colspan="3">' . $value['channel_sku_title'] . '</td>';
                    $dataHtml .= '<td style="border:1px solid #000;" colspan="2">' . $value['channel_sku_quantity'] . '</td>';
                    $dataHtml .= '<td style="border:1px solid #000;" colspan="2">' . $data['rule']['tax_rate'] . '%</td>';
                    if ($data['channel_id'] == 1 && $data['site_code'] == 'Germany') {
                        $dataHtml .= '<td style="border:1px solid #000;" colspan="2">' . sprintf("%.2f", $value['channel_sku_price'] / 1.19) . '</td>';
                        $dataHtml .= '<td style="border:1px solid #000;" colspan="3">' . sprintf("%.2f", $value['channel_sku_price'] - ($value['channel_sku_price'] / 1.19)) . '</td>';
                    } else {
                        $dataHtml .= '<td style="border:1px solid #000;" colspan="2">' . sprintf("%.2f", $value['channel_sku_price']) . '</td>';
                        if ($sourceNum == 1) {
                            $dataHtml .= '<td style="border:1px solid #000;" colspan="3">' . sprintf("%.2f", $data['pay_fee'] * ($data['rule']['tax_rate'] / 100)) . '</td>';
                        } else {
                            $dataHtml .= '<td style="border:1px solid #000;" colspan="3">' . sprintf("%.2f", $value['channel_sku_price'] * ($data['rule']['tax_rate'] / 100)) . '</td>';
                        }
                    }
                    $dataHtml .= '</tr>';
                };
                $html = dhlTemplate($data, $dataHtml);
                break;
            default:
                foreach ($data['source'] as $key => $value) {
                    $dataHtml .= '<tr>';
                    $dataHtml .= '<td style="border:1px solid #000;">' . ($key + 1) . '</td>';
                    $dataHtml .= '<td style="border:1px solid #000;" colspan="3">' . $value['channel_item_id'] . '</td>';
                    $dataHtml .= '<td style="border:1px solid #000;" colspan="6">' . $value['channel_sku_title'] . '</td>';
                    $dataHtml .= '<td style="border:1px solid #000;">' . $value['channel_sku_quantity'] . '</td>';
                    $dataHtml .= '<td style="border:1px solid #000;">' . $data['rule']['tax_rate'] . '%</td>';
                    if ($data['channel_id'] == 1 && $data['site_code'] == 'Germany') {
                        $dataHtml .= '<td style="border:1px solid #000;">' . sprintf("%.2f", $value['channel_sku_price'] / 1.19) . '</td>';
                        $dataHtml .= '<td style="border:1px solid #000;" colspan="2">' . sprintf("%.2f", $value['channel_sku_price'] - ($value['channel_sku_price'] / 1.19)) . '</td>';
                    } else {
                        $dataHtml .= '<td style="border:1px solid #000;">' . sprintf("%.2f", $value['channel_sku_price']) . '</td>';
                        if ($sourceNum == 1) {
                            $dataHtml .= '<td style="border:1px solid #000;" colspan="2">' . sprintf("%.2f", $data['pay_fee'] * ($data['rule']['tax_rate'] / 100)) . '</td>';
                        } else {
                            $dataHtml .= '<td style="border:1px solid #000;" colspan="2">' . sprintf("%.2f", $value['channel_sku_price'] * ($data['rule']['tax_rate'] / 100)) . '</td>';
                        }
                    }
                    $dataHtml .= '</tr>';
                };
                $html = generalTemplate($data, $dataHtml);
                break;
        }
        return $html;
    }
}

/**
 * 一般发票模板
 * @param $data
 * @param $dataHtml
 * @return string
 */
function generalTemplate($data, $dataHtml)
{
    if ($data['channel_id'] == 1 && $data['site_code'] == 'Germany') {
        $totalTaxAmount = sprintf("%.2f", $data['pay_fee'] - ($data['pay_fee'] / 1.19));
    } else {
        $totalTaxAmount = sprintf("%.2f", $data['pay_fee'] * ($data['rule']['tax_rate'] / 100));
    }
    $html = <<<EOD
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Title</title>
</head>
<body>
    <div style="font-size:14px;color:#000;margin:0px;border:1px solid #ddd;max-width:830px;">
        <!--标题-->
        <div style="font-size:30px;margin:0px;">Rechnung</div>
        <!--区域1  start-->
        <div style="float:right;width:100%">
            <div style="font-size:10px;text-align:right;">{$data['rule']['custom_area1']}</div>
        </div>
        <!--区域1  end-->
        <!--区域2  start-->
        <div style="font-size:10px;margin:0px;padding:0px;">{$data['rule']['custom_area2']}</div>
        <!--区域2  end-->
        <!--invoice地址B1  start-->
        <div style="border:1px solid #000;padding:0px;font-size:10px;">
            <div style="font-weight:bold;">{$data['address']['recipient']}</div>
            <div style="font-weight:bold;">{$data['address']['address']}</div>
            <div style="font-weight:bold;">{$data['address']['city']}{$data['address']['area_info']}{$data['address']['country_code']}</div>
            <div style="font-weight:bold;">{$data['address']['tel']}</div>
        </div>
        <!--invoice地址B1  end-->
        <!--invoice编号B2,b3,b4,A2  start-->
        <div style="font-size:10px;">
            <div style="border:1px solid #000;padding:0px;">
                Rechnung Nr.:{$data['invoice_code']} {$data['invoice_date']}
            </div>
            <div style="border:1px solid #000;padding:0px;">
                <div style="overflow:hidden">{$data['rule']['custom_area3']}</div>
                <div>Kunden-Nr.:{$data['invoice_date']}</div>
            </div>
        </div>
        <!--invoice编号B2,b3,b4,A2  end-->
        <!--表格   start-->
        <table style="width:100%;font-size:12px;border-collapse:collapse;">
            <tr>
                <th style="border:1px solid #000;">Pos</th>
                <th style="border:1px solid #000;" colspan="3">Art-Nr.</th>
                <th style="border:1px solid #000;" colspan="6">Bezeichnung</th>
                <th style="border:1px solid #000;">Anz</th>
                <th style="border:1px solid #000;">Mwst-Satz</th>
                <th style="border:1px solid #000;">NettoPreis</th>
                <th style="border:1px solid #000;" colspan="2">Zzgl.{$data['rule']['tax_rate']}% MwSt.t</th>
            </tr>
            {$dataHtml}
            <tr>
                <td colspan="13" style="border:1px solid #000;text-align:right">Zzgl.{$data['rule']['tax_rate']}% MwSt.t</td>
                <td colspan="2"  nowrap="nowrap" style="border:1px solid #000;text-align:right">{$totalTaxAmount}</td>
            </tr>
            <tr>
                <td colspan="13" style="border:1px solid #000;text-align:right">Gesamt</td>
                <td colspan="2"  nowrap="nowrap" style="border:1px solid #000;text-align:right">{$data['goods_amount']}{$data['currency_code']}</td>
            </tr>
            <tr>
                <td colspan="13"  style="border:1px solid #000;text-align:right">Versandkosten(Netto)</td>
                <td colspan="2"  nowrap="nowrap" style="border:1px solid #000;text-align:right">0.00</td>
            </tr>
            <tr>
                <td colspan="13"  style="border:1px solid #000;text-align:right">Versandkosten(Mwst)</td>
                <td colspan="2"  nowrap="nowrap" style="border:1px solid #000;text-align:right">0.00</td>
            </tr>
            <tr>
                <td colspan="13" style="border:1px solid #000;font-size: 15px;text-align:right;font-weight:bold;">Gesamtbetrag</td>
                <td colspan="2" nowrap="nowrap" style="border:1px solid #000;font-size: 15px;text-align:right;font-weight:bold;">{$data['pay_fee']}{$data['currency_code']}</td>
            </tr>
            <tr>
                <td colspan="4" style="border:1px solid #000;">Bestellungsnummer</td>
                <td colspan="7" style="border:1px solid #000;">{$data['rule']['custom_area5']}</td>
                <td style="border:1px solid #000;"></td>
                <td style="border:1px solid #000;"></td>
                <td colspan="2" style="border:1px solid #000;"></td>
            </tr>
            <tr>
                <td colspan="4" style="border:1px solid #000;">Versandart</td>
                <td colspan="6" style="border:1px solid #000;">Packetversand</td>
                <td style="border:1px solid #000;"></td>
                <td style="border:1px solid #000;"></td>
                <td style="border:1px solid #000;"></td>
                <td colspan="2" style="border:1px solid #000;"></td>
            </tr>
            <tr>
                <td colspan="15" style="border:1px solid #000;">Vielen Dank fur  lhren Auftrag am {$data['order_time']}</td>
            </tr>
        </table>
        <!--表格   end-->
        <!--end-->
        <div style="border-top:1px solid #000;font-size:12px;font-weight:bold;color:#000">{$data['rule']['custom_area4']}</div>
    </div>
</body>
</html>
EOD;
    return $html;
}

/**
 * dhl发票模板
 * @param $data
 * @param $dataHtml
 * @return string
 */
function dhlTemplate($data, $dataHtml)
{
    if ($data['channel_id'] == 1 && $data['site_code'] == 'Germany') {
        $totalTaxAmount = sprintf("%.2f", $data['pay_fee'] - ($data['pay_fee'] / 1.19));
    } else {
        $totalTaxAmount = sprintf("%.2f", $data['pay_fee'] * ($data['rule']['tax_rate'] / 100));
    }
    $html = <<<EOD
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Title</title>
</head>
<body>
<div style="font-size:14px;color:#000;margin:0px;border:1px solid #ddd;max-width:830px;">
    <!--标题-->
    <div style="font-size:30px;margin:0px;">COMMERCIAL INVOICE</div>
    <!--区域1  start-->
    <div style="float:right;width:100%">
        <div style="font-size:10px;text-align:right;">{$data['rule']['custom_area1']}</div>
    </div>
    <!--区域1  end-->
    <!--区域2  start-->
    <div style="font-size:10px;margin:0px;padding:0px;">{$data['rule']['custom_area2']}</div>
    <!--区域2  end-->
    <!--invoice地址B1  start-->
    <div style="border:1px solid #000;padding:0px;font-size:10px;">
        <div style="font-weight:bold;">{$data['address']['recipient']}</div>
        <div style="font-weight:bold;">{$data['address']['address']}</div>
        <div style="font-weight:bold;">{$data['address']['city']}{$data['address']['area_info']}{$data['address']['country_code']}</div>
        <div style="font-weight:bold">{$data['address']['tel']}</div>
    </div>
    <!--invoice地址B1  end-->
    <!--invoice编号B2,b3,b4,A2  start-->
    <div style="font-size:10px; margin-top: 15px">
        <div style="border:1px solid #000;padding:0px;">
            <div style="display: inline">Invoice No.:{$data['invoice_code']}</div>
            <div style="display: inline;float: right">{$data['invoice_date']}GMT</div>
        </div>
        <div style="border:1px solid #000;padding:0px;clear: right">
            <div style="overflow:hidden">Dear Ladies and Gentlem</div>
            <div>according to your order we will charge you the following order:</div>
        </div>
    </div>
    <!--invoice编号B2,b3,b4,A2  end-->
    <!--表格   start-->
    <table style="width:100%;font-size:12px;border-collapse:collapse;margin-top: 15px">
        <tr>
            <th style="border:1px solid #000;">Pos</th>
            <th style="border:1px solid #000;" colspan="2">Item No.</th>
            <th style="border:1px solid #000;" colspan="3">Description</th>
            <th style="border:1px solid #000;" colspan="2">Quantity</th>
            <th style="border:1px solid #000;" colspan="2">Vat Rate</th>
            <th style="border:1px solid #000;" colspan="2">Net Price</th>
            <th style="border:1px solid #000;" colspan="3">Surcharges.{$data['rule']['tax_rate']}% VAT.t</th>
        </tr>
        {$dataHtml}
        <tr>
            <td colspan="12" style="border:1px solid #000;text-align:right">Subtotal</td>
            <td colspan="3"  nowrap="nowrap" style="border:1px solid #000;text-align:right">{$data['goods_amount']}{$data['currency_code']}</td>
        </tr>
        <tr>
            <td colspan="12"  style="border:1px solid #000;text-align:right">{$data['rule']['tax_rate']}%VAT</td>
            <td colspan="3"  nowrap="nowrap" style="border:1px solid #000;text-align:right">{$totalTaxAmount}</td>
        </tr>
        <tr>
            <td colspan="12" style="border:1px solid #000;font-size: 15px;text-align:right;font-weight:bold;">Total</td>
            <td colspan="3" nowrap="nowrap" style="border:1px solid #000;font-size: 15px;text-align:right;font-weight:bold;">{$data['pay_fee']}{$data['currency_code']}</td>
        </tr>
        <tr>
            <td colspan="3" style="border:1px solid #000;">Order Number</td>
            <td colspan="5" style="border:1px solid #000;">{$data['rule']['custom_area5']}</td>
            <td colspan="2" style="border:1px solid #000;"></td>
            <td colspan="2" style="border:1px solid #000;"></td>
            <td colspan="3" style="border:1px solid #000;"></td>
        </tr>
        <tr>
            <td colspan="3" style="border:1px solid #000;">Shipping</td>
            <td colspan="3" style="border:1px solid #000;">Sending Parcels</td>
            <td colspan="2" style="border:1px solid #000;"></td>
            <td colspan="2" style="border:1px solid #000;"></td>
            <td colspan="2" style="border:1px solid #000;"></td>
            <td colspan="3" style="border:1px solid #000;"></td>
        </tr>
        <tr>
            <td colspan="15" style="border:1px solid #000;">THANK YOU FOR YOUR ORDER at {$data['order_time']}G</td>
        </tr>
    </table>
    <!--表格   end-->
    <!--end-->
    <div style="border-top:1px solid #000;font-size:12px;font-weight:bold;color:#000;margin-top: 30px">GB {$data['rule']['custom_area4']}</div>
</div>
</body>
</html>
EOD;
    return $html;
}

///**
// * dhl发票模板
// * @param $data
// * @param $dataHtml
// * @return string
// */
//function dhlTemplate($data, $dataHtml)
//{
//    $html = <<<EOD
//<!DOCTYPE html>
//<html lang="en" style="margin: 0px;padding: 0px;">
//<head>
//    <meta charset="UTF-8">
//    <style></style>
//</head>
//<body style="margin: 0px;padding: 0px;">
// <div style="width: 480px;margin: 0px auto;padding: 0px;text-align: center">
//     <span style="font-size: 20px;">INVOICE</span>
//     <div style="margin: 0px !important;padding: 0px !important;text-align:right;font-size: 16px;">
//        <span>NO.<span></span>{$data['invoice_code']}</span>
//     </div>
//     <table style="clear: both;font-size: 14px;border-collapse:collapse;">
//         <tr>
//             <td colspan="7" style="text-align: left;padding-left: 5px;font-weight: 700;border: 1px solid #000;">
//                 FROM(COLLECTION ADDRESS)
//             </td>
//         </tr>
//         <tr>
//             <td style="text-align: right;border: 1px solid #000;" >
//                 公司名称:<br>NAME:
//             </td>
//             <td colspan="2" style="text-align:left;padding-left: 5px;border: 1px solid #000;" valign="bottom">
//                 <span style="display: inline-block;width:150px;word-wrap:break-word;">salena shen</span>
//             </td>
//             <td style="text-align: right;border: 1px solid #000;" >
//                 运单号:<br>AWB.NO.:
//             </td>
//             <td colspan="3" style="text-align:left;padding-left: 5px;border: 1px solid #000;" valign="bottom">{$data['shipping_number']}</td>
//         </tr>
//         <tr>
//             <td style="text-align: right;border: 1px solid #000;" >
//                 地址:<br>ADD:
//             </td>
//             <td colspan="2" style="text-align:left;padding-left: 5px;border: 1px solid #000;" valign="bottom">
//                 <span style="display: inline-block;width:150px;word-wrap:break-word;" >China,guangdong,Zhongshan,Sanjiao,Jinsan Street,No. thirty-nine east</span>
//             </td>
//             <td style="text-align: right;border: 1px solid #000;" >
//                 重量:<br>WEIGHT:
//             </td>
//             <td colspan="3" style="text-align:left;padding-left: 5px;border: 1px solid #000;" valign="bottom">{$data['weight']}g</td>
//         </tr>
//         <tr>
//             <td style="text-align: right;border: 1px solid #000;" >
//                 发件人:<br>FROM:
//             </td>
//             <td colspan="2" style="text-align:left;padding-left: 5px;border: 1px solid #000;" valign="bottom">salena shen</td>
//             <td style="text-align: right;border: 1px solid #000;" >
//                 电话:<br>PHONE:
//             </td>
//             <td colspan="3" style="text-align:left;padding-left: 5px;border: 1px solid #000;" valign="bottom"></td>
//         </tr>
//         <tr>
//             <td colspan="7" style="text-align: left;padding-left: 5px;font-weight: 700;border: 1px solid #000;">
//                 TO (Receiver)
//             </td>
//         </tr>
//         <tr>
//             <td style="text-align: right;border: 1px solid #000;" >
//                 公司名称:<br>NAME:
//             </td>
//             <td colspan="6" style="text-align: left;padding-left: 5px;border: 1px solid #000;" valign="bottom"></td>
//         </tr>
//         <tr>
//             <td style="text-align: right;border: 1px solid #000;" >
//                 地址:<br>ADD:
//             </td>
//             <td colspan="6" style="text-align: left;padding-left: 5px;border: 1px solid #000;" valign="bottom">{$data['address']['address']}{$data['address']['city']}{$data['address']['area_info']}{$data['address']['country_code']}</td>
//         </tr>
//         <tr>
//             <td style="text-align: right;border: 1px solid #000;" >
//                 收件人:<br>FROM:
//             </td>
//             <td style="text-align: left;padding-left: 5px;border: 1px solid #000;" valign="bottom">{$data['address']['recipient']}</td>
//             <td style="text-align: right;border: 1px solid #000;" >
//                 电话:<br>TEL:
//             </td>
//             <td colspan="2" style="text-align: left;padding-left: 5px;border: 1px solid #000;" valign="bottom">{$data['address']['tel']}</td>
//             <td style="text-align: right;border: 1px solid #000;" >
//                 日期:<br>DATE:
//             </td>
//             <td style="text-align: left;border: 1px solid #000;" valign="bottom">{$data['invoice_date']}</td>
//         </tr>
//         <tr>
//             <td style="text-align: right;border: 1px solid #000;">
//                 城市:<br>CITY:
//             </td>
//             <td style="text-align: left;padding-left: 5px;border: 1px solid #000;" valign="bottom">{$data['address']['city']}</td>
//             <td style="text-align: right;border: 1px solid #000;" >
//                 邮编:<br>post&nbsp;code:
//             </td>
//             <td colspan="2" style="text-align: left;padding-left: 5px;border: 1px solid #000;" valign="bottom">{$data['address']['zipcode']}</td>
//             <td style="text-align: right;border: 1px solid #000;" >
//                 国家:<br>COUNTRY:
//             </td>
//             <td style="text-align: left;border: 1px solid #000;" valign="bottom">{$data['country_code']}</td>
//         </tr>
//         <tr>
//             <td colspan="7" style="height: 24px !important;border: 1px solid #000;"></td>
//         </tr>
//         <tr>
//             <td style="text-align: center;border: 1px solid #000;">
//                 唛头<br>Mark
//             </td>
//             <td colspan="3" style="text-align: center;border: 1px solid #000;">
//                 货物确指名称及协调制度商品编号<br>Description&Harmonised Code
//             </td>
//             <td style="text-align: center;border: 1px solid #000;">
//                 数量<br>Quantity
//             </td>
//             <td style="text-align: center;border: 1px solid #000;">
//                 单价<br>Unit Price
//             </td>
//             <td style="text-align: center;border: 1px solid #000;">
//                 金额<br>Amount
//             </td>
//         </tr>
//        {$dataHtml}
//         <tr>
//             <td colspan="4" style="border: 1px solid #000;"></td>
//             <td style="text-align: left;padding-left: 5px;border: 1px solid #000;">
//                 合计:<br>TOTALValue:
//             </td>
//             <td colspan="2" style="text-align: left;padding-left: 5px;border: 1px solid #000;">
//                 {$data['currency_code']} <span>{$data['pay_fee']}</span>
//             </td>
//         </tr>
//       <tr>
//             <td colspan="7" style="text-align: left;border: 1px solid #000;">
//                 声明
//                 <br>
//                 DECLARATION:
//                 <br>
//                 1、发件人对填制本发票的准确性及真实性承担一切责任。
//                 <br>
//                 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;We (The shipper) hereby guarantee to be responsible for the correctness and reality of the information given by the invoice.
//                 <br>
//                 2、本发票使用英文填制，金额栏使用USD标价，货名须加填中文货名。
//                 <br>
//                 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;The invoice should be filled in with English and peiced in USD.The description of the goods should    have the translation of Chinese.
//                 <div style="text-align: right;">发件人签字及盖章:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div>
//                 <div style="text-align: right;">Shipper's Signature & Stamp:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div>
//             </td>
//         </tr>
//     </table>
// </div>
//
//</body>
//</html>
//EOD;
//    return $html;
//}

/**
 * 申报发票模板
 * @param $data
 * @return string
 */
function declareTemplate($data)
{
    $dataHtml = '';
    foreach ($data['declare'] as $k => $declare) {
        $dataHtml .= '<tr>';
        $dataHtml .= '<td style="text-align: center">' . ($k + 1) . '</td>';
        $dataHtml .= '<td colspan="3" style="text-align: center">' . $declare['goods_name_cn'] . ' ' . $declare['goods_name_en'] . '</td>';
        $dataHtml .= '<td style="text-align: center">' . $declare['quantity'] . '</td>';
        $dataHtml .= '<td style="text-align: center">' . sprintf("%.2f", $declare['unit_price']) . '</td>';
        $dataHtml .= '<td style="text-align: center">' . sprintf("%.2f", $declare['unit_price'] * $declare['quantity']) . '</td>';
        $dataHtml .= '</tr>';
    }
    $html = <<<EOD
<!DOCTYPE html>
<html lang="en" style="margin: 0;padding: 0;">
<head>
    <meta charset="UTF-8">
    <style></style>
</head>
<body style="margin: 0;padding: 0;width:100%;height:100%;font: 8px/1.5 tahoma,arial,'Hiragino Sans GB','\5b8b\4f53',sans-serif;">
 <div style="margin: 0 auto;padding: 0;font-size:12px;color:#000;max-width:649px;max-height:978px;">
     <p style="text-align: center;font-size: 28px;margin: 0;padding: 0">INVOICE</p>
     <p style="margin: 0;padding: 0;text-align:right;font-size: 16px;">
        <span>NO.{$data['number']}<span>&nbsp&nbsp&nbsp</span></span>
     </p>
     <table width="100%" border="1" cellpadding="0" cellspacing="0" style="clear: both;font-size: 13px;">
         <tr>
             <td colspan="7" style="text-align: left;height: 35px;padding-left: 5px;">
                 <b>FROM(COLLECTION ADDRESS)</b>
             </td>
         </tr>
         <tr>
             <td style="width: 90px;text-align: right;height: 40px;" >
                 <p style="margin: 0;padding: 0">公司名称</p>
                 <p style="margin: 0;padding: 0">NAME:</p>
             </td>
             <td colspan="2" style="vertical-align: bottom;padding-left: 5px;">{$data['sender']['name']}</td>
             <td style="width: 75px;text-align: right;height: 40px;" >
                 <p style="margin: 0;padding: 0">运单号</p>
                 <p style="margin: 0;padding: 0">AWB.NO.:</p>
             </td>
             <td colspan="3" style="vertical-align: bottom;padding-left: 5px;">{$data['shipping_number']}</td>
         </tr>
         <tr>
             <td style="width: 75px;text-align: right;height: 40px;" >
                 <p style="margin: 0;padding: 0">地址</p>
                 <p style="margin: 0;padding: 0">ADD:</p>
             </td>
             <td colspan="2" style="word-wrap:break-word;vertical-align: bottom;padding-left: 5px;">
                 <p style="width:230px;margin: 0;padding: 0;">{$data['sender']['add']}</p>
             </td>
             <td style="width: 75px;text-align: right;height: 40px;" >
                 <p style="margin: 0;padding: 0">重量</p>
                 <p style="margin: 0;padding: 0">WEIGHT:</p>
             </td>
             <td colspan="3" style="vertical-align: bottom;padding-left: 5px;">{$data['package_weight']}</td>
         </tr>
         <tr>
             <td style="width: 75px;text-align: right;height: 40px;" >
                 <p style="margin: 0;padding: 0">发件人</p>
                 <p style="margin: 0;padding: 0">FROM:</p>
             </td>
             <td colspan="2" style="vertical-align: bottom;padding-left: 5px;">{$data['sender']['from']}</td>
             <td style="width: 75px;text-align: right;height: 40px;" >
                 <p style="margin: 0;padding: 0">电话</p>
                 <p style="margin: 0;padding: 0">PHONE:</p>
             </td>
             <td colspan="3" style="vertical-align: bottom;padding-left: 5px;">{$data['sender']['phone']}</td>
         </tr>
         <tr>
             <td colspan="7" style="text-align: left;padding-left: 5px;height: 35px;">
                 <b>TO (Receiver)</b>
             </td>
         </tr>
         <tr>
             <td style="width: 75px;height: 40px;text-align: right" >
                 <p style="margin: 0;padding: 0">公司名称</p>
                 <p style="margin: 0;padding: 0">NAME:</p>
             </td>
             <td colspan="6" style="vertical-align: bottom;padding-left: 5px;"></td>
         </tr>
         <tr>
             <td style="width: 75px;text-align: right;height: 40px;" >
                 <p style="margin: 0;padding: 0">地址</p>
                 <p style="margin: 0;padding: 0">ADD:</p>
             </td>
             <td colspan="6" style="vertical-align: bottom;padding-left: 5px;">{$data['address']['address']}{$data['address']['city']}{$data['address']['area_info']}{$data['address']['country_code']}</td>
         </tr>
         <tr>
             <td style="width: 75px;text-align: right;height: 40px;" >
                 <p style="margin: 0;padding: 0">收件人</p>
                 <p style="margin: 0;padding: 0">FROM:</p>
             </td>
             <td style="width:160px;padding-left: 5px;vertical-align: bottom">{$data['address']['consignee']}</td>
             <td style="width: 75px;text-align: right;height: 40px;" >
                 <p style="margin: 0;padding: 0">电话</p>
                 <p style="margin: 0;padding: 0">TEL:</p>
             </td>
             <td colspan="2" style="width: 165px;vertical-align:bottom;padding-left: 5px;">{$data['address']['tel']}</td>
             <td style="width: 75px;text-align: right;height: 40px;" >
                 <p style="margin: 0;padding: 0">日期</p>
                 <p style="margin: 0;padding: 0">DATE:</p>
             </td>
             <td style="width: 90px;text-align: center;vertical-align: bottom">{$data['date']}</td>
         </tr>
         <tr>
             <td style="width: 75px;text-align: right;height: 40px;">
                 <p style="margin: 0;padding: 0">城市</p>
                 <p style="margin: 0;padding: 0">CITY:</p>
             </td>
             <td style="padding-left: 5px;vertical-align: bottom">{$data['address']['city']}</td>
             <td style="width: 75px;text-align: right;height: 40px;" >
                 <p style="margin: 0;padding: 0">邮编</p>
                 <p style="margin: 0;padding: 0">post&nbsp;code:</p>
             </td>
             <td colspan="2" style="text-align: left;vertical-align: bottom;padding-left: 5px;">{$data['address']['zipcode']}</td>
             <td style="width: 75px;text-align: right;height: 40px;" >
                 <p style="margin: 0;padding: 0">国家</p>
                 <p style="margin: 0;padding: 0">COUNTRY:</p>
             </td>
             <td style="width: 90px;text-align: center;vertical-align: bottom">{$data['country']}</td>
         </tr>
         <tr>
             <td colspan="7" style="height: 24px !important;"></td>
         </tr>
         <tr>
             <td style="text-align: center">
                 <p style="margin: 0;padding: 0">唛头</p>
                 <p style="margin: 0;padding: 0">Mark</p>
             </td>
             <td colspan="3" style="text-align: center">
                 <p style="margin: 0;padding: 0">货物确指名称及协调制度商品编号</p>
                 <p style="margin: 0;padding: 0">Description&Harmonised Code</p>
             </td>
             <td style="text-align: center">
                 <p style="margin: 0;padding: 0">数量</p>
                 <p style="margin: 0;padding: 0">Quantity</p>
             </td>
             <td style="text-align: center">
                 <p style="margin: 0;padding: 0">单价</p>
                 <p style="margin: 0;padding: 0">Unit Price</p>
             </td>
             <td style="text-align: center">
                 <p style="margin: 0;padding: 0">金额</p>
                 <p style="margin: 0;padding: 0">Amount</p>
             </td>
         </tr>
         {$dataHtml}
         <tr>
             <td colspan="4"></td>
             <td style="text-align: left;padding-left: 5px;">
                 <p style="margin: 0;padding: 0">合计:</p>
                 <p style="margin: 0;padding: 0">TOTALValue:</p>
             </td>
             <td colspan="2" style="text-align: left;vertical-align: bottom;padding-left: 5px;">
                 {$data['declared_currency_code']} <span>{$data['declared_amount']}</span>
             </td>
         </tr>
         <tr>
             <td colspan="7" style="text-align: left">
                 <p style="margin: 0;padding: 0">声明</p>
                 <p style="margin: 0;padding: 0">DECLARATION:</p>
                 <p style="margin: 0;padding: 0">1、 发件人对填制本发票的准确性及真实性承担一切责任。</p>
                 <p style="text-indent: 2em;margin: 0;padding: 0">We (The shipper) hereby guarantee to be responsible for the correctness and reality of the information given by the invoice.</p>
                 <p style="margin: 0;padding: 0">2、 本发票使用英文填制，金额栏使用USD标价，货名须加填中文货名。</p>
                 <p style="text-indent: 2em;margin: 0;padding: 0">The invoice should be filled in with English and peiced in USD.The description of the goods should    have the translation of Chinese.</p>
                 <br>
                 <br>
                 <p style="text-align:right;padding: 0;margin: 0;padding-right: 160px;">发件人签字及盖章</p>
                 <p style="text-align: right;padding: 0;margin: 0;padding-right: 160px;">Shipper's Signature & Stamp:</p>
             </td>
         </tr>
     </table>
 </div>
</body>
</html>
EOD;
    return $html;
}

/**
 *  获取二维数组下标数组
 * @param $array ,$key
 * @return array
 */
function arrays_get_vals_by_key($array, $key)
{
    $ret = [];
    foreach ($array as $val) {
        array_push($ret, $val[$key]);
    }
    return $ret;
}

if (!function_exists('json_error')) {
    function json_error($message, $code = 400)
    {
        return json(['message' => $message], $code);
    }
}

if (!function_exists('json_confirm')) {
    function json_confirm($message, $code = 500)
    {
        return json(['message' => $message, 'code' => 'confirm'], $code);
    }
}

if (!function_exists('hump2other')) {
    function hump2other($hump, $seq = "_")
    {
        $ret = preg_replace_callback("/[A-Z]{1}/", function ($match) use ($seq) {
            $ord = ord($match[0]);
            $chr = chr($ord + ord('a') - ord('A'));
            return "{$seq}{$chr}";
        }, $hump);
        return $ret;
    }
}

/**
 * 判断字符串是否为json格式
 */
if (!function_exists('is_json')) {
    function is_json($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
}
if (!function_exists('is_true_json')) {
    function is_true_json($string)
    {
        $o = json_decode($string);
        $b = json_last_error() == JSON_ERROR_NONE;
        if (!$b) {
            return $b;
        }
        $type = gettype($o);
        if($type=='integer'){
            return false;
        }
        return $b;
    }
}

/**
 *  时间条件查询
 */
if (!function_exists('timeCondition')) {
    function timeCondition($start, $end)
    {
        date_default_timezone_set("PRC");
        $condition = [];
        if (!empty($start) && !empty($end)) {
            $is_date = strtotime($start) ? strtotime($start) : false;
            if (!$is_date) {
                return false;
            }
            $start = strtotime($start);
            $is_date = strtotime($end) ? strtotime($end) : false;
            if (!$is_date) {
                return false;
            }
            $end = strtotime($end . " 23:59:59");
            $condition = ['between', [$start, $end]];
        } else {
            if (!empty($start)) {
                $is_date = strtotime($start) ? strtotime($start) : false;
                if (!$is_date) {
                    return false;
                }
                $start = strtotime($start);
                $condition = ['>=', $start];
            } else {
                if (!empty($end)) {
                    $is_date = strtotime($end) ? strtotime($end) : false;
                    if (!$is_date) {
                        return false;
                    }
                    $end = strtotime($end . " 23:59:59");
                    $condition = ['<=', $end];
                }
            }
        }
        return $condition;
    }
}


/**
 * 对象转数组
 * @param object $obj
 * @return array
 */
if (!function_exists('obj2Array')) {
    function obj2Array($obj)
    {
        if (is_object($obj)) {
            $array = (array)$obj;
        }
        if (is_array($obj)) {
            $array = [];
            foreach ($obj as $key => $value) {
                $array[$key] = obj2array($value);
            }
        }
        return $array;
    }
}

/**
 * 字段排序
 */
if (!function_exists('fieldSort')) {
    function fieldSort($params)
    {
        $orderBy = '';
        if (isset($params['sort_field']) && !empty($params['sort_field'])) {
            $type = 'asc';
            if (isset($params['sort_type']) && !empty($params['sort_type'])) {
                $type = $params['sort_type'];
            }
            $orderBy = $params['sort_field'] . ' ' . $type . ',';
        }
        return $orderBy;
    }
}


/**
 * 下载pdf文件
 * @param unknown $filename
 * @return boolean|number
 */
function downloadFile($fullPath)
{
    // Must be fresh start
    if (headers_sent()) {
        die('Headers Sent');
    }
    // Required for some browsers
    if (ini_get('zlib.output_compression')) {
        ini_set('zlib.output_compression', 'Off');
    }
    // File Exists?
    if (file_exists($fullPath)) {
        // Parse Info / Get Extension
        $fsize = filesize($fullPath);
        $path_parts = pathinfo($fullPath);
        $ext = strtolower($path_parts["extension"]);
        // Determine Content Type
        switch ($ext) {
            case "pdf":
                $ctype = "application/pdf";
                break;
            case "exe":
                $ctype = "application/octet-stream";
                break;
            case "zip":
                $ctype = "application/zip";
                break;
            case "doc":
                $ctype = "application/msword";
                break;
            case "xls":
                $ctype = "application/vnd.ms-excel";
                break;
            case "ppt":
                $ctype = "application/vnd.ms-powerpoint";
                break;
            case "gif":
                $ctype = "image/gif";
                break;
            case "png":
                $ctype = "image/png";
                break;
            case "jpeg":
            case "jpg":
                $ctype = "image/jpg";
                break;
            default:
                $ctype = "application/force-download";
        }

        header("Pragma: public"); // required
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: private", false); // required for certain browsers
        header("Content-Type: $ctype");
        header("Content-Disposition: attachment; filename=\"" . basename($fullPath) . "\";");
        header("Content-Transfer-Encoding: binary");
        header("Content-Length: " . $fsize);
        ob_clean();
        flush();
        readfile($fullPath);
    } else {
        die('File Not Found');
    }
}

/**
 *
 * @param $datetime
 * @return int
 */
function datetime2timestamp($datetime)
{
    if (is_int($datetime)) {
        return $datetime;
    }
    if (!($datetime instanceof DateTime)) {
        $datetime = new DateTime($datetime);
    }
    return $datetime->getTimestamp();
}

function now($format = 'Y-m-d H:i:s')
{
    return date($format, time());
}

function dump_detail($dump)
{
    $debug = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
    $pid = getmypid();
    echo "PID:{$pid} FILE:{$debug[0]['file']} LINE:{$debug[0]['line']}\n";
    dump($dump);
}

function createRedis($config): \erp\Redis
{
    $redis = new \erp\Redis();
    try{
        $redis->connect($config['host'], $config['port']);
    }catch (Exception $e)
    {
        var_dump($e->getMessage());
    }


    if (isset($config['password'])) {
        $redis->auth($config['password']);
    }
    return $redis;
}

function class2path($class){
    return str_replace('\\', '/', $class);
}

function path2class($path){
    return str_replace('/', '\\', $path);
}

function set_swoole_timeout($ms, $params)
{
    if (($ms > 0) && defined('SWOOLE_WORKER')) {
        $param = ['ms' => $ms, 'pid' => getmypid(), 'worker_id' => SWOOLE_WORKER, 'op' => 'wait', 'params' => $params];
        $message = new \swoole\messageAction\KillProcess($param);
        getSwooleInstance()->sendPipeMessage($message, 1);
        return function () use ($ms, $message) {
            echo "$ms timeout\n";
            $message->cancel();
        };
    } else {
        return function () use ($ms) {
            echo "not timeout $ms\n";
        };
    }
}

function getSwooleInstance()
{
    return \swoole\SwooleTasker::getInstance();
}

function isConnectRedis(\Redis $redis): bool
{
    try {
        $pong = $redis->ping();
        return $pong === '+PONG';
    } catch (\RedisException $exception) {
        return false;
    }
}

function unicode2utf8($str)
{
    if (!$str) {
        return $str;
    }
    $decode = json_decode($str);
    if ($decode) {
        return $decode;
    }
    $str = '["' . $str . '"]';
    $decode = json_decode($str);
    if (count($decode) == 1) {
        return $decode[0];
    }
    return $str;
}

define('globalDefaultKey', 'globalDefaultKey');

function globalPush($data, $key = globalDefaultKey)
{
    global $globalPush;
    if (!isset($globalPush[$key])) {
        $globalPush[$key] = [];
    }
    $globalPush[$key][] = $data;
}

function globalPop($key = globalDefaultKey)
{
    global $globalPush;
    if (isset($globalPush[$key]) && (count($globalPush[$key]) > 0)) {
        array_pop($globalPush[$key]);
    }
}

function globalGet($key = globalDefaultKey)
{
    global $globalPush;
    if (isset($globalPush[$key])) {
        return $globalPush[$key];
    } else {
        return [];
    }
}

function globalEarse($key = globalDefaultKey)
{
    global $globalPush;
    unset($globalPush[$key]);
}

function queueRunLog(string $log)
{
    globalPush($log, "queueRunLog");
}

function millisecond()
{
    list($t1, $t2) = explode(' ', microtime());
    return (float)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
}

function list_dir($dir, $callback, &$files = [])
{
    $break = false;
    if (is_dir($dir)) {
        if ($handle = opendir($dir)) {
            while (!$break && $file = readdir($handle)) {
                if ($file != '.' && $file != '..') {
                    if (is_dir($dir . DIRECTORY_SEPARATOR . $file)) {
                        list_dir($dir . DIRECTORY_SEPARATOR . $file, $callback, $files);
                    } else {
                        $file = str_replace(APP_PATH, "", $dir . DIRECTORY_SEPARATOR . $file);
                        $callback($file, $files, $break);
                    }
                }
            }
        }
        closedir($handle);
    } else {
        echo '非有效目录!';
    }
}

function dir_iteration($dir, $callback)
{
    if (is_dir($dir)) {
        if ($handle = opendir($dir)) {
            $result = [];
            if ($ret = $callback($dir, 'dir')) {
                $result[] = $ret;
            }
            while ($file = readdir($handle)) {
                if ($file != '.' && $file != '..') {
                    $file = $dir . DIRECTORY_SEPARATOR . $file;
                    $iter = dir_iteration($file, $callback);
                    if (is_dir($file)) {
                        $result = array_merge($result, $iter);
                    } else if ($iter) {
                        $result[] = $iter;
                    }
                }
            }
            closedir($handle);
            return $result;
        }
    } else {
        return $callback($dir, 'file');
    }
}

function recursion_dir($dir, $call)
{
    if (is_dir($dir)) {
        if ($handle = opendir($dir)) {
            while ($file = readdir($handle)) {
                if ($file != '.' && $file != '..') {
                    if (is_dir($dir . DIRECTORY_SEPARATOR . $file)) {
                        recursion_dir($dir . DIRECTORY_SEPARATOR . $file, $call);
                    } else {
                        $file = str_replace(APP_PATH, "", $dir . DIRECTORY_SEPARATOR . $file);
                        $call($file);
                    }
                }
            }
        }
        closedir($handle);
    } else {
        echo '非有效目录!';
    }
}

function param($params, $key, $def = '')
{
    if (isset($params[$key])) {
        return $params[$key];
    } else {
        return $def;
    }
}

function paramNotEmpty($params, $key, $def = '')
{
    if (isset($params[$key]) && !empty($params[$key])) {
        return $params[$key];
    } else {
        return $def;
    }
}

/**
 * 判断参数存在且是数字
 *
 * @param array $params 请求参数数组
 * @param mixed $key 键名
 * @return boolean
 */
function isParamNumber(array $params, $key): bool
{
    if (isset($params[$key]) && is_numeric($params[$key])) {
        return true;
    } else {
        return false;
    }
}

if (! function_exists('filterNumeric')) {
    /**
     * 筛选出数字
     *
     * @param string $code
     * @return string
     */
    function filterNumeric(string $code): string
    {
        return preg_replace('/\D+/', '', $code);
    }
}

/**
 * 选择时间格式
 *
 * @param string $beginTime 开始时间
 * @param string $endTime 结束时间
 * @param string $key 键名
 * @return array
 */
function chooseDate(string $beginTime = '', string $endTime = '', $key = ''): array
{
    $where = [];
    if ($beginTime || $endTime) { // 按创建时间
        $start_time = "";
        $end_time = "";

        if (!$beginTime) {
            $start_time = 0;
        } else {
            $start_time = strtotime($beginTime);
        }

        if (!$endTime) {
            $end_time = 10413763200;
        } else {
            $end_time = strtotime($endTime . ' 23:59:59');
        }
        $where[$key] = ['between', [$start_time, $end_time]];
    }
    return $where;
}


function param_json($params, $key, $def = '')
{
    return json_decode(param($params, $key, $def));
}

function file2namespace($filepath)
{
    $DS = DIRECTORY_SEPARATOR;
    $NS = "\\";
    return preg_replace("/(\\{$DS})/", $NS, $filepath);
}

function parseControllerNode($controller, $match)
{
    $class = new \ReflectionClass($controller);
    $methods = $class->getMethods();
    $nodes = [];
    foreach ($methods as $method) {
        $comment = $method->getDocComment();
        if (preg_match("/@node( [a-z0-9_u4e00-u9fa5]+)?/i", $comment, $nodeName)) {
            $nodeName = isset($nodeName[1]) ? $nodeName[1] : "";
            $nodes[$match[1]][$match[2]][] = ['name' => $method->getName(), 'node' => $nodeName];
        }
    }
    unset($class);
    return $nodes;
}

function value_type($value)
{
    if (is_array($value)) {
        return 'array';
    }
    if (is_string($value)) {
        return 'string';
    }
    if (is_object($value)) {
        return 'object';
    }
    if (is_bool($value)) {
        return 'boolean';
    }
    if (is_null($value)) {
        return 'null';
    }
    return null;
}

function make_sure_file($file)
{
    if (file_exists($file)) {
        return;
    }
    $DS = DIRECTORY_SEPARATOR;
    $files = explode($DS, $file);
    $path = "";
    foreach ($files as $file) {
        if (preg_match('/\./', $file)) {
            $path .= $DS . $file;
            if (!file_exists($path)) {
                $o = fopen($path, 'w');
                fclose($o);
            }
        } else {
            $path .= $DS . $file;
            if (!is_dir($path)) {
                mkdir($path);
            }
        }
    }
}

function firstUpper($str)
{
    return strupper(substring($str, 0, 1)) . substring($str, 1);
}

function substring($s, $start, $length = null)
{
    if (function_exists('mb_substr')) {
        return mb_substr($s, $start, $length, 'UTF-8'); // MB is much faster
    } elseif ($length === null) {
        $length = strlen($s);
    } elseif ($start < 0 && $length < 0) {
        $start += strlen($s); // unifies iconv_substr behavior with mb_substr
    }
    return iconv_substr($s, $start, $length, 'UTF-8');
}

function strupper($str)
{
    return mb_strtoupper($str, 'UTF-8');
}

function config_get($group_param)
{
    $group = explode('.', $group_param);
    if (isset($group[1])) {
        return \app\Configure::param($group[0], $group[1]);
    } else {
        return \app\Configure::group($group[0]);
    }
}

/**
 * @info 生成uid
 * @param string $prefix
 * @return string
 */
function create_uuid($key = "", $prefix = "")
{
    $str = empty($key) ? md5(uniqid(mt_rand(), true)) : $key;
    $uuid = substr($str, 0, 8) . '-';
    $uuid .= substr($str, 8, 4) . '-';
    $uuid .= substr($str, 12, 4) . '-';
    $uuid .= substr($str, 16, 4) . '-';
    $uuid .= substr($str, 20, 12);
    return $prefix . $uuid;
}

// 说明： 删除非空目录的解决方案
function removeDir($dirName)
{
    if (!is_dir($dirName)) {
        return false;
    }
    $handle = @opendir($dirName);
    while (($file = @readdir($handle)) !== false) {
        if ($file != '.' && $file != '..') {
            $dir = $dirName . '/' . $file;
            is_dir($dir) ? removeDir($dir) : @unlink($dir);
        }
    }
    closedir($handle);
    return rmdir($dirName);
}

function array_last(array $array)
{
    $nth = count($array) - 1;
    return $array[$nth];
}

function array_first(array $array)
{
    return $array[0];
}

function array_filtermap(callable $callback, array $array, $keepKey = false)
{
    $result = [];
    foreach ($array as $key => $value) {
        $ret = $callback($value, $key);
        if ($ret === false) {
            continue;
        }
        if ($keepKey) {
            $result[$key] = $ret;
        } else {
            $result[] = $ret;
        }
    }
    return $result;
}

function is_extends($class, $extends)
{
    $class = new \Nette\Reflection\ClassType($class);
    $parent = $class->getParentClass();
    if ($parent) {
        if ($parent->name === $extends) {
            return true;
        }
        return is_extends($parent->name, $extends);
    } else {
        return $class->name === $extends;
    }
}

function is_subclass($subclass, $supclass)
{
    $class = new \Nette\Reflection\ClassType($subclass);
    $parent = $class->getParentClass();
    if ($parent) {
        if ($parent->name === $supclass) {
            return true;
        }
        return is_extends($parent->name, $supclass);
    } else {
        return false;
    }
}

function is_implements($class, $interface)
{
    $class = new \Nette\Reflection\ClassType($class);
    return in_array($interface, $class->getInterfaceNames());
}

/**
 * @doc 计算获取文件夹最后修改时间（子文件夹）
 * @param $dir
 * @return int
 */
function dirmtime($dir, $callback = null)
{
    if ($callback && is_callable($callback)) {
        if (!$callback($dir)) {
            $mtime = 0;
        } else {
            $mtime = filemtime($dir);
        }
    } else {
        $mtime = filemtime($dir);
    }
    $d = opendir($dir);
    while ($r = readdir($d)) {
        $subdir = $dir . DS . $r;
        if ($r != '.' && $r != '..' && is_dir($subdir)) {
            $dmtime = dirmtime($subdir);
            $mtime = max($mtime, $dmtime);
        }
    }
    closedir($d);
    return $mtime;
}

function array_foldl(array $array, $acc, $callback)
{
    foreach ($array as $key => $value) {
        $callback($value, $acc, $key);
    }
    return $acc;
}

function array_merge_plus($_ = null)
{
    $args = func_get_args();
    $arrays = [];
    foreach ($args as $arg) {
        if ($arg instanceof \app\common\service\DataToObjArr) {
            $arrays[] = $arg->toArray();
        } else {
            $arrays[] = $arg;
        }
    }
    return call_user_func_array('array_merge', $arrays);
}

/** 检验数据类型是否正确
 * @param $data
 * @param string $type
 * @return bool
 */
function request_verify($data, $type = 'string')
{
    if (is_array($data)) {
        return false;
    }
    switch ($type) {
        case 'string':
            if (!is_string($data)) {
                return false;
            }
            break;
        case 'int':
            if (!intval($data)) {
                return false;
            }
            break;
        case 'bool':
            if (!is_bool($data)) {
                return false;
            }
            break;
        case 'float':
            if (!floatval($data)) {
                return false;
            }
            break;
    }
    return true;
}


/**
 * 返回包含状态码和执行信息的json
 * @param string $message
 * @param int $status 200表示成功，状态码与http status code大致含义相同
 * @param array $append 需要追加的信息数组，一般在成功执行完后存在返回数据时使用
 * @return \think\response\Json
 * @throws \app\common\exception\ParameterInvalidException
 */
function getResultJson($message = '', $status = 500, $append = array(), &$result = null)
{
    if ($result) {
        if (!is_array($result)) {
            throw new \app\common\exception\ParameterInvalidException('result 参数类型错误');
        }
        if (!isset($result['status'])) $result['status'] = $status;
        if (!isset($result['message'])) $result['message'] = $message;
    } else {
        $result = [
            'status' => $status,
            'message' => $message
        ];
    }
    if (!is_array($append)) {
        throw new \app\common\exception\ParameterInvalidException('append 参数类型错误');
    }
    $result = array_merge($result, $append);
    return json($result);
}

/**
 * 将一个对象迭代转换成数组
 * @param $data
 * @return array
 * @throws \Exception
 */
function iterable2Array($data)
{
    $arr = [];
    if (is_object($data) || is_array($data)) {
        foreach ($data as $k => $v) {
            $arr[$k] = (is_object($v) || is_array($v)) ? iterable2Array($v) : $v;
        }
    } else {
        throw new ParameterInvalidException('Function iterable2Array must have an iterable parameter.');
    }
    return $arr;
}

/**
 * 判断字符是否为 base64编码
 * @param $str
 * @return bool
 */
function checkStringIsBase64($str)
{
    return $str == base64_encode(base64_decode($str)) ? true : false;
}

/**
 * 搜索内容
 * @param $snText
 * @param $where
 * @param $field
 */
if (!function_exists('search')) {
    function search($snText, &$where, $field)
    {
        if (is_json($snText)) {
            $snText = json_decode($snText, true);
            if (!empty($snText)) {
                $where[$field] = ['in', $snText];
            }
        } else {
            $where[$field] = ['eq', trim($snText)];
        }
    }
}
/**
 * @desc 下划线转驼峰
 * @param string $str 需要转换的字符串
 * @param Boolean $ucfirst 首字母是否小写，默认true
 * @return string 下划线转换成驼峰之后的字符串
 * @author Jimmy <554511322@qq.com>
 * @date 2018-04-08 11:28:11
 */
function underline2hump($str, $ucfirst = true)
{
    $array = explode('_', $str);
    foreach ($array as $key => $val) {
        $array[$key] = ucfirst($val);
    }
    if (!$ucfirst) {
        $array[0] = strtolower($array[0]);
    }
    return implode('', $array);
}

/**
 * @desc 更换二维数组的键名
 * @author wangwei
 * @date 2018-10-12 15:58:46
 * @param array $arr
 * @param string $key
 * @return unknown[]
 */
function arrayKeyChange($arr, $key)
{
    $return = array();
    foreach ($arr as $v) {
        isset($v[$key]) && $return[$v[$key]] = $v;
    }
    return $return;
}

/**
 * 判断数组是否是下标数组
 * @param array $var
 * @return boolean
 */
function isNumericArray($var)
{
    $return = false;
    if (is_array($var)) {
        $sz = sizeof($var);
        $return = $sz === 0 || array_keys($var) === range(0, $sz - 1);
    }
    return $return;
}

/**
 * @desc 获取给定的时间前最后一个工作日
 * @param $time
 * @return int
 * @author Reece
 * @date 2018-12-19 17:48:38
 */
function getLastWorkDay($time)
{
    for ($i = $time; $i >= $time - 7 * 86400; $i -= 86400) {
        if (!in_array(date('w', $i), [0, 6])) {
            return $i;
        }
    }
}

/**
 * @desc 获取给定时间的月份的最后一天(默认当月)
 * @param $time
 * @param string $offset
 * @return false|int
 * @author Reece
 * @date 2018-12-19 18:35:48
 */
function getMonthLastDay($time, $offset = '')
{
    $firstDay = date('Y-m-01 H:i:s', $time);
    return strtotime("$firstDay $offset +1 month -1 day");
}

/**
 * 验证时间格式Y-m-d H:i:s
 * @param string $date
 * @return boolean
 */
function isDateTime($date){
    if($date == date('Y-m-d H:i:s',strtotime($date))){
        return true;
    }else{
        return false;
    }
}

/**
 * 验证时间格式Y-m-d
 * @param string $date
 * @return boolean
 */
function isDate($date){
    if($date == date('Y-m-d',strtotime($date))){
        return true;
    }else{
        return false;
    }
}
