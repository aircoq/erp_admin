<?php

namespace app\test\controller;

/**
 * @title Funmarj接口测试
 * @description 接口说明
 * @url /funmart
 */
class FunmartTest
{
    /**
     * 获取token
     * @return \think\response\Json
     * @throws \Exception
     */
    public function getToken()
    {
        $url = 'http://dev9527.funmart.cn:1690/api/order/get-token';
        /** @var  $postData 以post下的 RAW 方式的 JSON 传送*/
        $postData = array(
            'name' => "利朗达",            //必需
            'phone' => "15914130311",   //必需
            'email' => "bhjgvjk@outlook.com",       //必需
            'secrect' => "cz5uax3pqyi3",        //必需
        );

        // $postData = http_build_query($postData);
        $postData = json_encode($postData);
        $result = $this->httpReader($url, 'POST', $postData);
        $res = json_decode($result, true);
        return json($res,200);
    }

    /**
     * 获取商品选项接口
     * @return \think\response\Json
     * @throws \Exception
     */
    public function getSimpleOptions()
    {
        $token = '2m%3D8XkSexSijmoA7T6-5dmLXcyvyJ00UQJABC5sZiEgfaEiGGvfjJ';     //必需
        $appkey = '11';                 //商户ID      //必需
        $url = "http://dev9527.funmart.cn:1690/api/product/get-simple-options?token={$token}&appkey={$appkey}";
        $result = $this->httpReader($url, 'get');
        $res = json_decode($result, true);
        return json($res,200);
    }


    /**
     * 获取商品列表接口
     * @return \think\response\Json
     * @throws \Exception
     */
    public function getProductList()
    {
        $token = '2m%3D8XkSexSijmoA7T6-5dmLXcyvyJ00UQJABC5sZiEgfaEiGGvfjJ';     //必需
        $appkey = '11';                 //商户ID      //必需
        $page = 1;                         //非必需
        $url = "http://dev9527.funmart.cn:1690/api/product/get-product-list?token={$token}&appkey={$appkey}&page={$page}";
        $result = $this->httpReader($url, 'get');
        $res = json_decode($result, true);
        return json($res,200);
    }

    public function getOrderList()
    {
        $token = '2m%3D8XkSexSijmoA7T6-5dmLXcyvyJ00UQJABC5sZiEgfaEiGGvfjJ';     //必需
        $appkey = '11';                 //商户ID      //必需
        //查询日期间隔为5天 多余5天报错
        $start = '2019-1-01 00:00:00';                         //必需
        $end = '2019-1-05 00:00:00';                         //必需
        $url = "http://dev9527.funmart.cn:1690/api/order/get-order-list?token={$token}&appkey={$appkey}&start={$start}&end={$end}";
        $result = $this->httpReader($url, 'get');
        $res = json_decode($result, true);
        return json($res,200);
    }


    public function getOrderStatus()
    {
        $token = '2m%3D8XkSexSijmoA7T6-5dmLXcyvyJ00UQJABC5sZiEgfaEiGGvfjJ';     //必需
        $appkey = '11';                 //商户ID      //必需
        //查询日期间隔为5天 多余5天报错
        $start = '2019-1-01 00:00:00';                         //必需
        $end = '2019-1-05 00:00:00';                         //必需
        $url = "http://dev9527.funmart.cn:1690/api/order/get-order-status?token={$token}&appkey={$appkey}&start={$start}&end={$end}";
        $result = $this->httpReader($url, 'get');
        $res = json_decode($result, true);
        return json($res,200);
    }
    /**
     * 创建商品接口
     * @return \think\response\Json
     * @throws \Exception
     */
    public function creteSimpleProduct()
    {
        $url = 'http://dev9527.funmart.cn:1690/api/product/create-simple-product';
        $postData = array(
            'token' => '',
            'appkey' => '',
            'category_code' => '',
            'sku_name' => '',
            'shop_spu' => '',
            'spec' => '',
            'color' => '',
            'hs_code' => '',
            'length' => '',
            'width' => '',
            'weight' => '',
            'price' => '',
            'stock_mode' => '',
            'stock_num' => '',
            'spu_main_image' => '',
            'sku_main_image' => '',
            'sku_image1' => '',
            'sku_image2' => '',
            'sku_image3' => '',
            'sku_image4' => '',
            'sku_image5' => '',
            'sku_image6' => '',
            'sku_image7' => '',
            'sku_image8' => '',
            'sku_image9' => '',
            'contraband' => '',
            'detail' => '',
            'tags' => '',
        );

        // $postData = http_build_query($postData);
        $postData = json_encode($postData);
        $result = $this->httpReader($url, 'POST', $postData);
        $res = json_decode($result, true);
        return json($res,200);

    }

    /**
     * HTTP读取
     * @param string $url 目标URL
     * @param string $method 请求方式
     * @param array|string $bodyData 请求BODY正文
     * @param array $responseHeader 传变量获取请求回应头
     * @param int $code 传变量获取请求回应状态码
     * @param string $protocol 传变量获取请求回应协议文本
     * @param string $statusText 传变量获取请求回应状态文本
     * @param array $extra 扩展参数,可传以下值,不传则使用默认值
     * header array 头
     * host string 主机名
     * port int 端口号
     * timeout int 超时(秒)
     * proxyType int 代理类型; 0 HTTP, 4 SOCKS4, 5 SOCKS5, 6 SOCK4A, 7 SOCKS5_HOSTNAME
     * proxyAdd string 代理地址
     * proxyPort int 代理端口
     * proxyUser string 代理用户
     * proxyPass string 代理密码
     * caFile string 服务器端验证证书文件名
     * sslCertType string 安全连接证书类型
     * sslCert string 安全连接证书文件名
     * sslKeyType string 安全连接证书密匙类型
     * sslKey string 安全连接证书密匙文件名
     * @return string|array 请求结果;成功返回请求内容;失败返回错误信息数组
     * error string 失败原因简单描述
     * debugInfo array 调试信息
     */
    public function httpReader($url, $method = 'GET', $bodyData = [], $extra = [], &$responseHeader = null, &$code = 0, &$protocol = '', &$statusText = '')
    {
        $ci = curl_init();

        if (isset($extra['timeout'])) {
            curl_setopt($ci, CURLOPT_TIMEOUT, $extra['timeout']);
        }
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ci, CURLOPT_HEADER, true);
        curl_setopt($ci, CURLOPT_AUTOREFERER, true);
        curl_setopt($ci, CURLOPT_FOLLOWLOCATION, true);

        if (isset($extra['proxyType'])) {
            curl_setopt($ci, CURLOPT_PROXYTYPE, $extra['proxyType']);

            if (isset($extra['proxyAdd'])) {
                curl_setopt($ci, CURLOPT_PROXY, $extra['proxyAdd']);
            }

            if (isset($extra['proxyPort'])) {
                curl_setopt($ci, CURLOPT_PROXYPORT, $extra['proxyPort']);
            }

            if (isset($extra['proxyUser'])) {
                curl_setopt($ci, CURLOPT_PROXYUSERNAME, $extra['proxyUser']);
            }

            if (isset($extra['proxyPass'])) {
                curl_setopt($ci, CURLOPT_PROXYPASSWORD, $extra['proxyPass']);
            }
        }

        if (isset($extra['caFile'])) {
            curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, 2); //SSL证书认证
            curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, true); //严格认证
            curl_setopt($ci, CURLOPT_CAINFO, $extra['caFile']); //证书
        } else {
            curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, false);
        }

        if (isset($extra['sslCertType']) && isset($extra['sslCert'])) {
            curl_setopt($ci, CURLOPT_SSLCERTTYPE, $extra['sslCertType']);
            curl_setopt($ci, CURLOPT_SSLCERT, $extra['sslCert']);
        }

        if (isset($extra['sslKeyType']) && isset($extra['sslKey'])) {
            curl_setopt($ci, CURLOPT_SSLKEYTYPE, $extra['sslKeyType']);
            curl_setopt($ci, CURLOPT_SSLKEY, $extra['sslKey']);
        }

        $method = strtoupper($method);
        switch ($method) {
            case 'GET':
                curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'GET');
                if (!empty($bodyData)) {
                    if (is_array($bodyData)) {
                        $url .= (stristr($url, '?') === false ? '?' : '&') . http_build_query($bodyData);
                    } else {
                        curl_setopt($ci, CURLOPT_POSTFIELDS, $bodyData);
                    }
                }
                break;
            case 'POST':
                curl_setopt($ci, CURLOPT_POST, true);
                if (!empty ($bodyData)) {
                    curl_setopt($ci, CURLOPT_POSTFIELDS, $bodyData);
                }
                break;
            case 'PUT':
                //                 curl_setopt ( $ci, CURLOPT_PUT, true );
                curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'PUT');
                if (!empty ($bodyData)) {
                    curl_setopt($ci, CURLOPT_POSTFIELDS, $bodyData);
                }
                break;
            case 'DELETE':
                curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
            case 'HEAD':
                curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'HEAD');
                break;
            default:
                throw new \Exception(json_encode(['error' => '未定义的HTTP方式']));
                return ['error' => '未定义的HTTP方式'];
        }

        if (!isset($extra['header']) || !isset($extra['header']['Host'])) {
            $urldata = parse_url($url);
            $extra['header']['Host'] = $urldata['host'];
            unset($urldata);
        }

        $header_array = array();
        foreach ($extra['header'] as $k => $v) {
            $header_array[] = $k . ': ' . $v;
        }

        curl_setopt($ci, CURLOPT_HTTPHEADER, $header_array);
        curl_setopt($ci, CURLINFO_HEADER_OUT, true);

        curl_setopt($ci, CURLOPT_URL, $url);

        $response = curl_exec($ci);

        if (false === $response) {
            $http_info = curl_getinfo($ci);
            throw new \Exception(json_encode(['error' => curl_error($ci), 'debugInfo' => $http_info]));
            return ['error' => curl_error($ci), 'debugInfo' => $http_info];
        }

        $responseHeader = [];
        $headerSize = curl_getinfo($ci, CURLINFO_HEADER_SIZE);
        $headerData = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        $responseHeaderList = explode("\r\n", $headerData);

        if (!empty($responseHeaderList)) {
            foreach ($responseHeaderList as $v) {
                if (false !== strpos($v, ':')) {
                    list($key, $value) = explode(':', $v, 2);
                    $responseHeader[$key] = ltrim($value);
                } else if (preg_match('/(.+?)\s(\d+)\s(.*)/', $v, $matches) > 0) {
                    $protocol = $matches[1];
                    $code = $matches[2];
                    $statusText = $matches[3];
                }
            }
        }

        curl_close($ci);
        return $body;
    }
}