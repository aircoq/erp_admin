<?php
namespace app\api\service;

use app\common\cache\Cache;
use app\common\model\App;
use app\common\model\ChannelNode;
use app\common\service\Filter;
use app\common\service\MonthlyModeConst;
use app\index\service\ManagerServer;
use app\index\service\ServerLog;
use app\index\service\User;
use app\internalletter\service\InternalLetterService;
use app\report\service\MonthlyTargetAmountService;
use think\Exception;
use think\Request;
use think\Config;
use Odan\Jwt\JsonWebToken;
use app\common\exception\JsonErrorException;

/** 与钉钉的接口
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/12/4
 * Time: 15:56
 */
class Dingtalk extends Base
{

    /**
     * 检查是否为合法用户
     * @param string $filed
     * @return array|bool|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUser($filed = '')
    {

        $job_number = $this->getJobNumber();
        if(!$job_number){
            $this->retData['status'] = -1001;
            $this->retData['message'] = 'Error: Job is not valid';
            return false;
        }
        if(!$filed){
            $filed = '*';
        }
        $user = (new \app\common\model\User())->field($filed)->where('job_number',$job_number)->find();
        if(!$user){
            $this->retData['status'] = -1002;
            $this->retData['message'] = 'Error: User is not valid';
        }
        return $user;
    }


    public function getTokenDingTalk()
    {
        $user = $this->getUser();
        if($user){
            $token = (new \app\common\model\User())->createToken($user);
            $this->retData['tokens'] = $token;
            $this->retData['requestModel'] = 'DINGTALK';
        }
        return $this->retData;
    }

    public function getJobNumber()
    {
        //获取免登授权码
        $authCode = $this->requestData['authCode'];
        //获取用户详情
        try{
            $userInfo = $this->getUserInfo($authCode);
        }catch (Exception $e){
            $this->retData['getException'] = $e->getMessage();
        }
        return $userInfo['jobnumber'] ?? '';
    }

    public function getUserId($accessToken,$authCode)
    {
        $url = 'https://oapi.dingtalk.com/user/getuserinfo';
        $data = [
            'access_token' => $accessToken,
            'code' => $authCode,
        ];
        $result = $this->httpReader($url, 'GET', $data);
        $results = json_decode($result, true);
        if(!isset($results['userid'])){
            $this->retData['getuserinfoException'] = $result;
        }
        return $results['userid'] ?? '';
    }

    public function getUserInfo($authCode)
    {
        //获取access_token
//        $accessToken = InternalLetterService::getAccessTokenText();
        $accessToken = InternalLetterService::getAccessTokenText();
        //获取用户userid
        $userId = $this->getUserId($accessToken,$authCode);
        if(!$userId){
            return false;
        }

        $url = 'https://oapi.dingtalk.com/user/get';
        $data = [
            'access_token' => $accessToken,
            'userid' => $userId,
        ];
        $result = $this->httpReader($url, 'GET', $data);
        $results = json_decode($result, true);
        if(!isset($results['jobnumber'])){
            $this->retData['getuserException'] = $result;
            return false;
        }
        return $results;
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
    public function httpReader($url, $method = 'GET', $bodyData = [], $extra = [], &$responseHeader = null, &$code = 0, &$protocol = '', &$statusText = '') {
        $ci = curl_init ();

        if (isset($extra['timeout'])) {
            curl_setopt ( $ci, CURLOPT_TIMEOUT, $extra['timeout'] );
        }
        curl_setopt ( $ci, CURLOPT_RETURNTRANSFER, true );
        curl_setopt ( $ci, CURLOPT_HEADER, true );
        curl_setopt ( $ci, CURLOPT_AUTOREFERER, true);
        curl_setopt ( $ci, CURLOPT_FOLLOWLOCATION, true);

        if (isset($extra['proxyType'])) {
            curl_setopt ($ci, CURLOPT_PROXYTYPE, $extra['proxyType']);

            if (isset($extra['proxyAdd'])) {
                curl_setopt ($ci, CURLOPT_PROXY, $extra['proxyAdd']);
            }

            if (isset($extra['proxyPort'])) {
                curl_setopt ($ci, CURLOPT_PROXYPORT, $extra['proxyPort']);
            }

            if (isset($extra['proxyUser'])) {
                curl_setopt ($ci, CURLOPT_PROXYUSERNAME, $extra['proxyUser']);
            }

            if (isset($extra['proxyPass'])) {
                curl_setopt ($ci, CURLOPT_PROXYPASSWORD, $extra['proxyPass']);
            }
        }

        if (isset($extra['caFile'])) {
            curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, 2); //SSL证书认证
            curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, true); //严格认证
            curl_setopt($ci, CURLOPT_CAINFO, $extra['caFile']); //证书
        } else {
            curl_setopt ( $ci, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt ( $ci, CURLOPT_SSL_VERIFYHOST, false);
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
                if(!empty( $bodyData ) ) {
                    if (is_array($bodyData)) {
                        $url .= (stristr( $url, '?' ) === false ? '?' : '&') . http_build_query( $bodyData );
                    } else {
                        curl_setopt ( $ci, CURLOPT_POSTFIELDS, $bodyData );
                    }
                }
                break;
            case 'POST':
                curl_setopt ( $ci, CURLOPT_POST, true );
                if (! empty ( $bodyData )) {
                    curl_setopt ( $ci, CURLOPT_POSTFIELDS, $bodyData );
                }
                break;
            case 'PUT':
                //                 curl_setopt ( $ci, CURLOPT_PUT, true );
                curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'PUT');
                if (! empty ( $bodyData )) {
                    curl_setopt ( $ci, CURLOPT_POSTFIELDS, $bodyData );
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

        if(!isset($extra['header']) || !isset($extra['header']['Host'])){
            $urldata = parse_url($url);
            $extra['header']['Host'] = $urldata['host'];
            unset($urldata);
        }

        $header_array = array ();
        foreach ( $extra['header'] as $k => $v ){
            $header_array[] = $k . ': ' . $v;
        }

        curl_setopt ( $ci, CURLOPT_HTTPHEADER, $header_array );
        curl_setopt ( $ci, CURLINFO_HEADER_OUT, true);

        curl_setopt ( $ci, CURLOPT_URL, $url );

        $response = curl_exec ( $ci );

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
                } else if(preg_match('/(.+?)\s(\d+)\s(.*)/', $v , $matches) > 0) {
                    $protocol = $matches[1];
                    $code = $matches[2];
                    $statusText = $matches[3];
                }
            }
        }

        curl_close ( $ci );
        return $body;
    }


}