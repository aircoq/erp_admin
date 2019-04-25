<?php
/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2019/4/4
 * Time: 16:44
 */

namespace app\index\service;


use app\common\cache\Cache;
use app\common\model\Account;
use app\common\model\amazon\AmazonAccount;
use app\common\model\amazon\AmazonAccountHealth;
use app\common\model\amazon\AmazonAccountHealthGoal;
use app\common\model\amazon\AmazonAccountHealthList;
use app\common\model\Server;
use app\common\service\Encryption;
use think\Exception;

class AccountHealthService
{

    /**
     * 发送信息至分布式爬虫服务器；
     * @param $data
     * @param $channel_id
     * @return bool|mixed|string
     * @throws \Exception
     */
    public function sendAccount2Spider($data, $channel_id)
    {


        $server = $this->getServer($data['base_account_id']);
        //亚马逊
//        $server['ip'] = '172.18.12.245';
//        $server['name'] = 'yidai';
//        $data['base_account_id'] = 4000;

        //速卖通
//        $server['ip'] = '172.18.11.201';
//        $server['name'] = 'KMINGDA';

        //wish
//        $server['ip'] = '172.18.11.201';
//        $server['name'] = 'KMINGDA';
//        $data['base_account_id'] = 1262;

//        ebay
//        $server['ip'] = '172.18.12.14';
//        $server['name'] = 'pony';
//        $data['base_account_id'] = 3872;

        $sendUrl = $this->buildUrl($server['ip']);
        $postData = $this->buildPostData($data, $channel_id,$server);
        Cache::handler()->hset('task:health:'.$channel_id.':'. $data['id'], 'sendurl', $sendUrl);
        Cache::handler()->hset('task:health:'.$channel_id.':'. $data['id'], 'postdata', $postData);
        //去请求执行
        $result = $this->httpReader($sendUrl, 'POST', $postData, ['timeout' => 30]);
        Cache::handler()->hset('task:health:'.$channel_id.':'. $data['id'], 'result', $result);
        $result = json_decode($result, true);


        return $result;
    }


    /**
     * 查服务器信息
     * @param $id
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function getServer($id)
    {
        $join[] = ['server s', 's.id = a.server_id', 'left'];
        $field = 's.*';
        $where = [
            'a.id' => $id,
        ];
        $server = (new Account())->alias('a')->join($join)->where($where)->field($field)->find();
        return $server;
    }


    /**
     * 生成访问的URL；
     * @param $ip
     * @return string
     */
    private function buildUrl($ip)
    {
        $url = 'https://' . $ip . ':10088/crawler-work';
        return $url;
    }

    /**
     * 组成时post,urlencode编码数组；
     * @param $data
     * @param $account_id
     * @return string
     */
    private function buildPostData($data,$channelId,$server)
    {
        $post = [
            'channel_id' => $channelId,
            'thread_num' => $data['thread_num'] ?? 10,
            'account_name' => $data['code'] ?? $data['account_name'] ?? '',
            'account_id' => $data['id'],
            'site' => $data['site'] ?? '',
            'name' => $server['name'],
            'channel' => Cache::store('Channel')->getChannelName($channelId),
            'id' => $data['base_account_id'],
            'callbackurl' => $data['callbackurl'],
            'operate_type' => $data['operate_type'] ?? 1,
            'tokens' => $this->getTokens(),
            'other' => $data['other'] ?? '',
         ];
        return http_build_query($post);
    }


    private function getTokens($userId = 1)
    {
        $user = Cache::store('user')->getOneUser($userId, 'id,realname,username,job');
        if(!$user){
            throw new Exception('用户数据错误');
        }
        $tokens = (new \app\common\model\User())->createToken($user);
        return $tokens;
    }

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
            //throw new \Exception(json_encode(['error' => curl_error($ci), 'debugInfo' => $http_info]));
            return json_encode(['error' => curl_error($ci), 'debugInfo' => $http_info]);
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