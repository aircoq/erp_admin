<?php
/**
 * Created by PhpStorm.
 * User: wlw2533
 * Date: 2018/7/10
 * Time: 20:13
 */

namespace app\common\service\ebay;

use think\Exception;

class EbayRestful
{
    private $header = [];
    private $method = '';

    public function __construct(string $method, array $header)
    {
        $this->method = strtoupper($method);
        $this->header = $header;
    }

    public function sendRequest(string $url, array $data=[], $extra=[])
    {
        try {
            $ci = curl_init();

            if (isset($extra['timeout'])) {
                curl_setopt($ci, CURLOPT_TIMEOUT, $extra['timeout']);//超时时长
            }
            curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);//将curl_exec()获取的信息以文件流的形式返回，而不是直接输出。

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
            switch ($this->method) {
                case 'GET':
                    break;
                case 'POST':
                    curl_setopt($ci, CURLOPT_POST, true);
                    if (!empty ($data)) {
                        curl_setopt($ci, CURLOPT_POSTFIELDS, http_build_query($data));
                    }
                    break;
                default:
                    throw new Exception(json_encode(['error' => '不支持的HTTP方式']));
            }
            $header_array = array();
            foreach ($this->header as $k => $v) {
                $header_array[] = $k . ': ' . $v;
            }

            curl_setopt($ci, CURLOPT_HTTPHEADER, $header_array);
            curl_setopt($ci, CURLINFO_HEADER_OUT, true);
            curl_setopt($ci, CURLOPT_URL, $url);

            $response = curl_exec($ci);

            curl_close($ci);
            return $response;
        } catch (Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }

}