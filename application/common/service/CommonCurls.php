<?php

namespace app\common\service;

use \Exception;
/**
 * Curl处理类
 * @author Jacky  2017-10-27 10:08:14
 */
class CommonCurls {

    /** 请求schema (如：http://、https://) @var string */
    public $_req_schema = null;
    /** 请求host (如：www.amazon.com)@var string */
    public $_req_host = null;
    /** 请求的域名 (如:https://www.amazon.com) @var string */
    public $_req_domain = null;
    /** 请求地址中域名后面的部分(如：/dp/B008VULGVI) @var string */
    public $_req_endpoint = null;
    /** 请求get数据 @var string|array */
    public $_req_get_data = null;
    /** 请求url(带get参数) @var string */
    public $_req_url = null;
    /** 请求方法 @var string */
    public $_req_method = null;
    /** 请求header @var array */
    public $_req_header = null;
    /** 请求cookie  @var string|array */
    public $_req_cookies = null;
    /** 请求post数据 @var string|array */
    public $_req_post_data = null;

    /** 请求配置项 @var array */
    public $_configs = array();

    /** 是否开启debug模式 @var bool */
    public $_option_debug = false;
    /** 是否跟随 http redirect 跳转  @var bool */
    public $_option_redirect = true;
    /** 最大 http redirect 调整数 @var int */
    public $_option_redirect_max = 2;
    /** 请求超时时间 @var int */
    public $_option_time_out = 60;
    /** tcp握手超时时间 @var int */
    public $_option_connect_time_out = false;
    /** 是否开启cookie跟踪 @var bool */
    public $_option_use_cookie_track = false;
    /** cookie跟踪存储目录 @var string */
    public $_option_cookie_track_dir = '/';
    /** cookie跟踪存储前缀 @var string */
    public $_option_cookie_file_prefix = '';
    /** cookie过期时间(秒) @var int */
    public $_option_cookie_expired_time = 3600;

    /** 响应http状态码 @var string */
    public $_rep_http_statu = null;
    /** 响应curl错误信息 @var string */
    public $_rep_error = null;
    /** 响应结果集 @var string */
    public $_rep_result = null;
    /** 响应cookie @var array */
    public $_rep_cookies = null;
    /** 响应cookies数组 @var array */
    public $_rep_cookies_arr = null;
    /** 响应header(除去cookies) @var array */
    public $_rep_header = null;

    /** 当前 http redirect 跳转次数 @var int */
    public $_run_redirect_count = 0;

    /**
     * 构造函数
     * @param array $configs  配置参数(注意req_url相关的参数顺序,后面的值会覆盖前面的值)
     */
    public function __construct(array $configs = array()) {
        $this->setConfigs($configs);
    }

    /**
     * 设置配置
     * @param array $configs
     * @return Common_Curl
     */
    public function setConfigs(array $configs){
        $this->_configs = $configs;
        return $this;
    }

    /**
     * 初始化参数
     * @return Common_Curl
     */
    public function initCurl(){
        $this->_req_schema = null;
        $this->_req_host = null;
        $this->_req_domain = null;
        $this->_req_endpoint = null;
        $this->_req_get_data = null;
        $this->_req_url = null;
        $this->_req_method = null;
        $this->_req_header = null;
        $this->_req_cookies = null;
        $this->_req_post_data = null;

//     	$this->_configs = array();

        $this->_option_redirect = true;
        $this->_option_redirect_max = 2;
        $this->_option_time_out = 60;
        $this->_option_connect_time_out = false;
        $this->_option_use_cookie_track = false;
        $this->_option_cookie_track_dir = '/';

        $this->_rep_http_statu = null;
        $this->_rep_error = null;
        $this->_rep_result = null;
        $this->_rep_cookies = null;
        $this->_rep_cookies_arr = null;
        $this->_rep_header = null;

        $this->_run_redirect_count = 0;

        return $this;
    }

    /**
     * 应用配置
     */
    public function applyConfigs(){
        if($this->_configs && is_array($this->_configs)){
            //$attr = req_url  | reqUrl | ReqUrl
            foreach ($this->_configs as $attr=>$val){
                //setReqUrl
                $func = 'set' . preg_replace_callback('/(^|_)([a-z])/',function($m){
                        return $m ? strtoupper($m[2]) : '';
                    },$attr);
                $attr_tmp = '_' . $attr;
                if(method_exists($this, $func)){
                    $this->$func($val);
                }else if(property_exists($this,$attr_tmp)){
                    $this->$attr_tmp = $val;
                }else{
//                    Common_Common::log("Common_Curl::setConfigs()==>func:{$func} and attr:{$attr_tmp} not exist");
                }
            }
        }
        return $this;
    }

    /**
     * 执行cURL命令并解析结果
     * @param string $curlCommand   利用Firefox浏览器复制cURL命令
     * @param callable $callbackBefore   对curl结果前置处理，如更换用户名、密码等
     * @param callable $callbackAfter   对采集结果后置处理，如解析结果的csrf token等
     * @throws Exception
     * @return string
     */
    public function execCurlCommand($curlCommand, $callbackBefore = null, $callbackAfter = null) {
        //初始化参数、解析curl命令、应用配置
        $this->initCurl()->parseCurl($curlCommand)->applyConfigs();
        //前置处理
        if(!empty($callbackBefore) && is_callable($callbackBefore)) {
            $callbackBefore($this);
        }
        //执行curl请求
        $this->execCurl();
        //后置处理
        if(!empty($callbackAfter) && is_callable($callbackAfter)) {
            $callbackAfter($this);
        }
        //返回结果
        return $this->getRepResult();
    }

    /**
     * 根据请求配置执行cURL
     * @param callable $callbackBefore 对curl结果前置处理，如更换用户名、密码等
     * @param callable $callbackAfter 对采集结果后置处理，如解析结果的csrf token等
     * @throws Exception
     * @return string
     */
    public function execCurlConfigs($callbackBefore = null, $callbackAfter = null){
        if(empty($this->_configs)){
            throw new Exception('the configs not empty');
        }
        //初始化参数、应用配置
        $this->initCurl()->applyConfigs();
        //前置处理
        if(!empty($callbackBefore) && is_callable($callbackBefore)) {
            $callbackBefore($this);
        }
        //执行curl请求
        $this->execCurl();
        //后置处理
        if(!empty($callbackAfter) && is_callable($callbackAfter)) {
            $callbackAfter($this);
        }
        //返回结果
        return $this->getRepResult();
    }

    /**
     * 解析curl信息
     * @param string $curlContent 利用Firefox浏览器复制cURL命令
     * @throws Exception
     * @return Common_Curl
     */
    public function parseCurl($curlCommand) {
        if(!preg_match("#curl '((http[s]?:\/\/)(.*?))'#is", $curlCommand, $urlMatch)){
            throw new Exception('the command no curlCommand');
        }
        $host_pos = strpos($urlMatch[3], '/');
        $get_pos = strpos($urlMatch[3], '?');

        $host = ($pos = ($host_pos ? $host_pos : $get_pos)) ? substr($urlMatch[3],0,$pos) : $urlMatch[3];
        $endpoint = $host_pos ? ( $get_pos ? substr($urlMatch[3],$host_pos,$get_pos-$host_pos) : substr($urlMatch[3],$host_pos) ) : '';
        $get_data = $get_pos ? substr($urlMatch[3],$get_pos+1) : '';

        //设置请求schema(如： https://)
        $this->setReqSchema($urlMatch[2]);
        //设置请求host(如：www.amazon.cn)
        $this->setReqHost($host);
        //设置请求endpoint,请求地址中域名后面的部分(如：/dp/B008VULGVI)
        $this->setReqEndpoint($endpoint);
        //设置请求get数据
        $this->setReqGetData($get_data);

        //cookie
        if(preg_match("#-H 'Cookie:([^']*)'#is", $curlCommand, $cookieMatch)){
            $this->setReqCookies($cookieMatch[1]);
            //移除header中的cookie
            $curlCommand = preg_replace("#-H 'Cookie:[^']*'#is", '', $curlCommand);
        }
        //header
        if(preg_match_all("#-H '([^']*?)'#is", $curlCommand, $headerMatch)) {
            $this->setReqHeader($headerMatch[1]);
        }
        //postData
        if(preg_match("#--data '([^']*?)'#is", $curlCommand, $postDataMatch)){
            $this->setReqPostData($postDataMatch[1]);
        }

        return $this;
    }

    /**
     * 执行curl请求
     * @throws Exception
     */
    private function execCurl() {
        //设置请求地址
        if(!$this->_req_domain){
            $this->setReqDomain($this->_req_schema . $this->_req_host);
        }
        if(!$this->_req_url){
            $url = $this->_req_domain . $this->_req_endpoint;
            $get_data = is_array($this->_req_get_data) ? $this->genGetData($this->_req_get_data) : $this->_req_get_data;
            $this->setReqUrl($url . ($get_data ? '?'.$get_data : ''));
        }
        if(!$this->_req_url){
            throw new Exception('the req_url not empty');
        }

        //实例化curl
        $ch = curl_init($this->_req_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //返回数据不直接输出
        curl_setopt($ch, CURLOPT_ENCODING, "gzip"); //指定gzip压缩
        curl_setopt($ch, CURLOPT_HEADER, true);//请求头是否包含在响应中
        curl_setopt($ch, CURLOPT_TIMEOUT,$this->_option_time_out);//设置超时时间
        if($this->_option_connect_time_out){
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,$this->_option_connect_time_out);//设置tcp握手超时时间
        }

        //设置请求方式及请求数据
        if(strtoupper($this->_req_method) == 'GET'){
            curl_setopt ($ch, CURLOPT_HTTPGET, true);
            curl_setopt ($ch, CURLOPT_POST, false);
        }else{
            if(!empty($this->_req_post_data)){
                $post_data = '';
                if(is_array($this->_req_post_data)){
                    $post_data = $this->genPostData($this->_req_post_data);
                }else{
                    $post_data = $this->_req_post_data;
                }
                curl_setopt ($ch, CURLOPT_HTTPGET, false);
                curl_setopt ($ch, CURLOPT_POST, true);
                curl_setopt($ch,CURLOPT_POSTFIELDS, $post_data);
            }
        }

        //设置请求header
        if(!empty($this->_req_header)){
            curl_setopt($ch,CURLOPT_HTTPHEADER,$this->_req_header);
        }

        //关闭SSL
        if(substr($this->_req_url, 0, 5) == 'https') {
//             curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
//             curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
//             curl_setopt($ch, CURLOPT_CAINFO, APPLICATION_PATH . '/../libs/ssl.pem');

            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//是否检查ssl证书
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);//从证书中检查SSL加密算法是否存在
        }

        //add 302 support
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $this->_option_redirect);//是否跟随 http redirect 跳转

        //获取跟踪cookie
        $track_cookie = $this->_option_use_cookie_track ? $this->getTrackCookies() : array();
        $cookie_arr_tmp = $this->mergeCookies($this->_req_cookies, $track_cookie);
        if($cookie = $this->genCookies($cookie_arr_tmp['cookies_arr'])){

//            $this->_option_debug && Common_Common::log('请求cookies:' . print_r($cookie_arr_tmp['cookies_arr'],1));

            curl_setopt($ch,CURLOPT_COOKIE, $cookie); //设置cookie
        }

        //最多循环三次
        $request_count  = 1;
        while ( $request_count <= 3 ) {
            //执行请求
//            Common_Common::log ( "CommonCurl 进行第 {$request_count} 次请求: {$this->_req_url}" );
            $this->_rep_result = curl_exec($ch);
            //获取curl请求信息
            $curlInfo = curl_getinfo($ch);
            $this->_rep_http_statu = $curlInfo['http_code'];

            //截取响应头中的cookies
            if($curlInfo['header_size']){
                //解析响应头
                $this->parseRepHeader(trim(substr($this->_rep_result, 0, $curlInfo['header_size'])));
                //截取html
                $this->_rep_result = trim(substr($this->_rep_result, $curlInfo['header_size']));
            }

            //curl是否发生错误
            if($errNo = curl_errno($ch)){
                $errMsg = curl_error($ch);
//                Common_Common::log ( "CommonCurl第 {$request_count} 次请求失败,ErrNo:{$errNo},Error:{$errMsg}" );
                $this->_rep_error = 'PayPalApiCurlRequestError,ErrNo:'.$errNo.',Error:'.$errMsg;
            }else{
//                Common_Common::log ( "CommonCurl第 {$request_count} 次请求成功!" );
                $this->_rep_error = '';
                break;
            }
            $request_count ++;//请求次数累加
        }
        curl_close($ch); //关闭curl

//        Common_Common::log('CommonCurl请求响应http状态:'.$this->_rep_http_statu);

        //跟随 http redirect 跳转
        if(($this->_rep_http_statu == '301' || $this->_rep_http_statu == '302') &&
            $this->_option_redirect && $this->_run_redirect_count < $this->_option_redirect_max &&
            isset($this->_rep_header['location']) && $this->_rep_header['location']){
            $this->_run_redirect_count++;
            $new_req_url = $this->_req_schema . $this->_req_host . $this->_rep_header['location'];
//            Common_Common::log('连接跟随重置:' . $new_req_url);
            $this->setReqUrl($new_req_url);
            $this->execCurl();
        }

        return $this;
    }

    /**
     * 设置请求schema
     * @param string $req_schema
     * @return Common_Curl
     */
    public function setReqSchema($req_schema){
        $this->_req_schema = $req_schema;
        return $this;
    }

    /**
     * 设置请求host
     * @param string $req_host
     * @return Common_Curl
     */
    public function setReqHost($req_host){
        $this->_req_host = $req_host;
        return $this;
    }

    /**
     * 获取请求host
     * @return string
     */
    public function getReqHost(){
        return $this->_req_host;
    }

    /**
     * 设置请求的域名(如:https://www.amazon.com)
     * @param string $req_domain
     * @return Common_Curl
     */
    public function setReqDomain($req_domain){
        $this->_req_domain = $req_domain;
        if(!$this->_req_schema || !$this->_req_host){
            if(!preg_match("#(http[s]?://)(.*)?#is", $req_domain, $urlMatch)){
                throw new Exception('the req_domain error');
            }
            $this->setReqSchema($urlMatch[1]);
            $arr = preg_split('#/#', $urlMatch[2],2);
            $this->setReqHost($arr[0]);
        }
        return $this;
    }

    /**
     * 获取请求的域名
     * @return string
     */
    public function getReqDomain(){
        return $this->_req_domain;
    }

    /**
     * 设置请求endpoint,请求地址中域名后面的部分(如：/dp/B008VULGVI)
     * @param string $req_endpoint
     * @return Common_Curl
     */
    public function setReqEndpoint($req_endpoint){
        $this->_req_endpoint = $req_endpoint;
        return $this;
    }

    /**
     * 设置请求get数据
     * @param string|array $req_get_data
     * @return Common_Curl
     */
    public function setReqGetData($req_get_data){
        $this->_req_get_data = $req_get_data;
        return $this;
    }

    /**
     * 解析请求get数据为数组
     * @param string $get_data_str
     * @return array
     */
    public function parseGetData($get_data_str){
        $get_data_arr = array();
        if(!empty($get_data_str) && is_string($get_data_str)){
            $arr = explode('&', $get_data_str);
            foreach ($arr as $k=>$item){
                if($item){
                    $item_arr = explode('=', $item);
                    $kk = $item_arr[0];
                    $vv = isset($item_arr[1]) ? urldecode($item_arr[1]) : '';//url解码
                    $get_data_arr[$kk] = $vv;
                }
            }
        }
        return $get_data_arr;
    }

    /**
     * 生成请求get数据的字符串
     * @param string $get_data_arr
     * @return string
     */
    public function genGetData($get_data_arr){
        $get_data_str = '';
        if(!empty($get_data_arr) && is_array($get_data_arr)){
            $get_data_str = http_build_query($get_data_arr);
        }
        return $get_data_str;
    }

    /**
     * 设置请求url(带get参数)
     * @param string $req_url
     * @return Common_Curl
     */
    public function setReqUrl($req_url){
        $this->_req_url = $req_url;
        //设置请求域名
        if(!$this->_req_domain){
            if(!preg_match("#(http[s]?://)(.*)?#is", $req_url, $urlMatch)){
                throw new Exception('the req_url error');
            }
            $this->setReqSchema($urlMatch[1]);
            $arr = preg_split('#/#', $urlMatch[2],2);
            $this->setReqHost($arr[0]);
            $this->setReqDomain($urlMatch[1] . $arr[0]);
        }
        return $this;
    }

    /**
     * 获取请求url(带get参数)
     * @return string
     */
    public function getReqUrl(){
        return $this->_req_url;
    }

    /**
     * 设置请求方法
     * @param string $req_method
     * @return Common_Curl
     */
    public function setReqMethod($req_method){
        $this->_req_method = $req_method;
        return $this;
    }

    /**
     * 设置请求header
     * @param array $req_header
     * @return Common_Curl
     */
    public function setReqHeader($req_header){
        $this->_req_header = $req_header;
        return $this;
    }

    /**
     * 设置请求cookie
     * @param string|array $req_cookies
     * @return Common_Curl
     */
    public function setReqCookies($req_cookies){
        $this->_req_cookies = $req_cookies;
        return $this;
    }

    /**
     * 生成cookies数组的字符串
     * @param array $cookies_arr
     * @return string
     */
    public function genCookies($cookies_arr){
        $cookies_str = '';
        if(!empty($cookies_arr) && is_array($cookies_arr)){
            foreach ($cookies_arr as $k=>$v){
                $cookies_str .= $k . '=' . $v . ';';
            }
            $cookies_str = trim($cookies_str,';');
        }
        return $cookies_str;
    }

    /**
     * 解析cookies字符串为数组
     * @param string $cookies_str
     */
    public function parseCookies($cookies_str){
        $return = array(
            'time'=>0,
            'cookies_arr'=>array(),
        );
        $cookies_arr = array();
        $time = 0;
        if($cookies_str && is_string($cookies_str) && $arr_tmp = preg_split('#\r\n#', $cookies_str,2)){
            $str_tmp = '';
            if(isset($arr_tmp[1])){
                $time = $arr_tmp[0];
                $str_tmp = $arr_tmp[1];
            }else{
                $str_tmp = $arr_tmp[0];
            }
            if($str_tmp){
                $arrs = explode(';', $str_tmp);
                foreach ($arrs as $k=>$v){
                    $arr = preg_split('/=/', $v,2);
                    if(count($arr)==2 && $arr[0]!='' && $arr[1]!=''){
                        $cookies_arr[$arr[0]] = $arr[1];
                    }
                }
            }
        }
        $return['time'] = $time;
        $return['cookies_arr'] = $cookies_arr;
        return $return;
    }

    /**
     * 合并cookie
     * @param string | array  $cookie1
     * @param string | array  $cookie2
     */
    public function mergeCookies($cookie1,$cookie2){
        $return = array(
            'time'=>0,
            'cookies_arr'=>array(),
        );
        $cookie_tmp1 = is_string($cookie1) ? $this->parseCookies($cookie1) : (is_array($cookie1) ? $cookie1 : array());
        $cookie_tmp2 = is_string($cookie2) ? $this->parseCookies($cookie2) : (is_array($cookie2) ? $cookie2 : array());
        $cookie1 = isset($cookie_tmp1['cookies_arr'])?$cookie_tmp1['cookies_arr'] : $cookie_tmp1;
        $cookie2 = isset($cookie_tmp2['cookies_arr'])?$cookie_tmp2['cookies_arr'] : $cookie_tmp2;
        $time = isset($cookie_tmp1['time']) ? $cookie_tmp1['time'] : 0;
        $time = isset($cookie_tmp2['time']) ? $cookie_tmp2['time'] : $time;
        $cookie = array_merge($cookie1,$cookie2);
        $cookie_low = array_merge(array_change_key_case($cookie1,CASE_LOWER),array_change_key_case($cookie2,CASE_LOWER));
        $dif_keys = array();
        $cookies_arr = array();
        foreach ($cookie as $k=>$v){
            $k_low = strtolower($k);
            if(!in_array($k_low, $dif_keys) && isset($cookie_low[$k_low])){
                $cookies_arr[$k] = $cookie_low[$k_low];
                $dif_keys[] = $k_low;
            }
        }
        $return['time'] = $time;
        $return['cookies_arr'] = $cookies_arr;
        return $return;
    }

    /**
     * 获取跟踪cookies
     * @throws Exception
     * @return Ambigous <multitype:, multitype:Ambigous <> >
     */
    public function getTrackCookies(){
        //创建cookie目录
        if(!is_dir($this->_option_cookie_track_dir)){
            mkdir($this->_option_cookie_track_dir, 0777, true);
            chmod($this->_option_cookie_track_dir, 0777);
        }
        if(!is_dir($this->_option_cookie_track_dir)){
            throw new Exception('create cookie track dir error');
        }
        //查找cookie文件
        $cookie_file_arr = scandir($this->_option_cookie_track_dir);
        $available_cookie_file_arr = array();//可用的cookie文件
        $case_sensitive = preg_match('/win/i',PHP_OS) ? 'i' : '';//win系统文件名不区分大小写
        foreach ($cookie_file_arr as $cookie_file){
            if($cookie_file!='.' && $cookie_file!='..'){
                if($this->_option_cookie_file_prefix!='' && !preg_match("#^{$this->_option_cookie_file_prefix}#{$case_sensitive}", $cookie_file)){
                    continue;
                }
                $cookie_file = $this->_option_cookie_file_prefix!='' ? preg_replace("#^{$this->_option_cookie_file_prefix}#{$case_sensitive}", '' , $cookie_file) : $cookie_file;
                if(preg_match("#{$cookie_file}$#{$case_sensitive}", $this->_req_host)){
                    $available_cookie_file_arr[] = $cookie_file;
                }
            }
        }
        //根据域名降序排列(一级域名的cookie可以在二级域名下使用)
        arsort($available_cookie_file_arr);
        $cookies_arr = array();
        if($available_cookie_file_arr){
            foreach ($available_cookie_file_arr as $file){
                if($cookie_str = file_get_contents($this->_option_cookie_track_dir . '/' . $this->_option_cookie_file_prefix . $file)){
                    $cookies_arr_tmp = $this->mergeCookies($cookies_arr, $cookie_str);
                    $cookies_arr = $cookies_arr_tmp['cookies_arr'];
                }
            }
        }
        return $cookies_arr;
    }

    /**
     * 存储跟踪cookie
     */
    public function saveTrackCookies(){
        if($this->_rep_cookies_arr){
            $cookie_file = $this->_option_cookie_track_dir . '/' . $this->_option_cookie_file_prefix . $this->_req_host;
            $org_cookie_str = is_file($cookie_file) ? file_get_contents($cookie_file) : '';
            $cookie_arr_tmp = $this->mergeCookies($org_cookie_str,$this->_rep_cookies_arr);
            $time = $org_cookie_str && isset($cookie_arr_tmp['time']) && $cookie_arr_tmp['time']>0 ? $cookie_arr_tmp['time'] : time();
            $cookies_str = $time . "\r\n" . $this->genCookies($cookie_arr_tmp['cookies_arr']);
            if(!file_put_contents($cookie_file, $cookies_str)){
                throw new Exception('save track cookies error');
            }

        }
    }

    /**
     * 返回Cookies是否过期
     * @return boolean
     */
    public function trackCookiesIsExpired($option = array()){
        $cookie_file = $this->_option_cookie_track_dir . '/' . $this->_option_cookie_file_prefix . $this->_req_host;
        $is_expired = true;
        if($org_cookie_str = is_file($cookie_file) ? file_get_contents($cookie_file) : ''){
            $cookie_arr_tmp = $this->parseCookies($org_cookie_str);
            $is_expired = time() - $cookie_arr_tmp['time'] >= $this->_option_cookie_expired_time;
            $cookie_str = $this->genCookies($cookie_arr_tmp['cookies_arr']);
            //对cookie文本长度的最小限制
            if(isset($option['cookie_text_length_min'])){
                $is_expired = $is_expired || !(strlen($cookie_str) > $option['cookie_text_length_min']);
            }
            $is_expired && file_put_contents($cookie_file, 0 . "\r\n" . $this->genCookies($cookie_arr_tmp['cookies_arr']));
        }
        return $is_expired;
    }

    /**
     * 解析响应头
     * @param string $header_str
     * @return array
     */
    public function parseRepHeader($header_str){
        $cookies = array();
        $headers = array();
        $cookies_map = '';
        if($header_str && $header_arr = explode("\r\n", $header_str)){
            foreach ($header_arr as $k=>$v){
                if(empty($v)){
                    continue;
                }
                if(preg_match('/^Set-Cookie:\s+(.*)/i', $v,$m)){
                    $cookie = array();
                    $arr = explode(';', $m[1]);
                    foreach ($arr as $kk=>$vv){
                        if(preg_match('/^(.*?)=(.*)/i', $vv,$mm)){
                            $ck = strtolower(trim($mm[1]));
                            $cv = trim($mm[2]);
                            if($kk===0){
                                $cookie['name'] = $ck;
                                $cookie['value'] = $cv;
                            }else{
                                $cookie[$ck] = $cv;
                            }
                        }
                    }
                    $cookies[] = $cookie;
                }else if(preg_match('/(.*?):\s+(.*)/i', $v,$mmm)){
                    $hk = strtolower(trim($mmm[1]));
                    $hv = trim($mmm[2]);
                    $headers[$hk] = $hv;
                }else{
                    $headers[] = $v;
                }
            }
//             Common_Common::log($headers);
//             Common_Common::log($cookies);
//             die;
        }
        //设置响应cookie映射
        $this->_rep_cookies = $cookies;
        $this->_rep_header = $headers;
        //设置响应cookie映射
        $this->setRepCookiesMap();
        //如果跟踪cookies，存储请求cookies
        $this->_option_use_cookie_track && $this->saveTrackCookies();
    }

    /**
     * 设置请求post数据
     * @param string|array $req_post_data
     * @return Common_Curl
     */
    public function setReqPostData($req_post_data){
        $this->_req_post_data = $req_post_data;
        return $this;
    }

    /**
     * 解析请求post数据为数组
     * @param string $post_data_str
     * @return array
     */
    public function parsePostData($post_data_str){
        $post_data_arr = array();
        if(!empty($post_data_str) && is_string($post_data_str)){
//             $post_data_str = urldecode($post_data_str);//url解码
            $arr = explode('&', $post_data_str);
            foreach ($arr as $k=>$item){
                $item_arr = explode('=', $item);
                $kk = isset($item_arr[1]) ? trim($item_arr[0]) : $k;
                $vv = isset($item_arr[1]) ? trim($item_arr[1]) : trim($item_arr[0]);
                $post_data_arr[$kk] = $vv;
            }
        }
        return $post_data_arr;
    }

    /**
     * 生成请求post数据的字符串
     * @param string $post_data_arr
     * @return string
     */
    public function genPostData($post_data_arr){
        $post_data_str = '';
        if(!empty($post_data_arr) && is_array($post_data_arr)){
            $post_data_str = http_build_query($post_data_arr);
        }
        return $post_data_str;
    }

    /**
     * 获取响应http状态码
     */
    public function getRepHttpStatu(){
        return $this->_rep_http_statu;
    }

    /**
     * 获取响应curl错误信息
     */
    public function getRepError(){
        return $this->_rep_error;
    }

    /**
     * 获取响应结果集
     */
    public function getRepResult(){
        return $this->_rep_result;
    }

    /**
     * 获取响应cookie
     */
    public function getRepCookies(){
        return $this->_rep_cookies;
    }

    /**
     * 设置响应cookie映射
     */
    public function setRepCookiesMap(){
        if($this->_rep_cookies){
            foreach ($this->_rep_cookies as $k=>$v){
                if($v['name'] && $v['value']){
                    $this->_rep_cookies_arr[$v['name']] = $v['value'];
                }
            }
        }
    }

    /**
     * 获取响应cookie映射
     */
    public function getRepCookiesMap(){
        return $this->_rep_cookies;
    }

    /**
     * 获取响应header
     */
    public function getRepHeader(){
        return $this->_rep_header;
    }

}