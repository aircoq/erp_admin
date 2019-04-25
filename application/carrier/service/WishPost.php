<?php
/**
 * Created by PhpStorm.
 * User: Tom
 * Date: 2017/6/12
 * Time: 16:47
 */

namespace app\carrier\service;


use erp\AbsServer;
use org\Curl;
use org\Xml;
use think\Exception;
use think\Validate;

/**
 * wish邮授权类
 * Class WishPost
 * @package app\carrier\service
 */
class WishPost extends AbsServer
{
    private $permissionUrl = 'http://wishpost.wish.com/oauth/authorize';

    private $apiBaseUrl = 'https://wishpost.wish.com/api/v2/oauth/';

    private $client_id;

    private $code;

    private $client_secret;

    private $redirect_uri;

    private $grant_type = 'authorization_code';

    public $expires_in;     //过期剩余时间，多少秒

    public $expiry_time;    //过期时间，时间戳

    public $refresh_token;  //刷新令牌

    public $access_token;   //访问令牌，有效期30天

    public $error = '';

    /**
     * 获取授权许可地址
     * @param $client_id
     * @return string
     */
    public function getPermissionUrl($client_id)
    {
        return $this->permissionUrl.'?client_id='.$client_id;
    }

    /**
     * 获取访问令牌
     * @param $config
     * @return bool
     */
    public function getAccessToken($config)
    {
        try{
            if(!$this->checkConfig('obtaining',$config)){
                return false;
            }
            $arrXmlData = [
                'client_id'     => $config['client_id'],
                'client_secret' => $config['client_secret'],
                'code'          => $config['code'],
                'grant_type'    => $this->grant_type,
                'redirect_uri'  => $config['redirect_uri']
            ];
            $objXml = new Xml();
            $xml = $objXml->arrayToXml($arrXmlData,'root');
            $header = ['Content-type:text/xml'];
            $response = Curl::curlPost($this->apiBaseUrl.'access_token',$xml,$header);
            $result = Xml::xmlToArray($response);
            if($result['status']==0){
                $this->access_token = $result['access_token'];
                $this->refresh_token = $result['refresh_token'];
                $this->expires_in = $result['expires_in'];
                $this->expiry_time = $result['expiry_time'];
                return true;
            }else{
                $this->error = $result['error_message'];
            }
        }catch(Exception $ex){
            $this->error = $ex->getMessage();
        }
        return false;
    }

    /**
     * 通过refresh_token获取访问令牌
     * @param $config
     * @return bool
     */
    public function getAccessTokenByRefresh($config)
    {
        try{
            if(!$this->checkConfig('refresh',$config)){
                return false;
            }
            $arrXmlData = [
                'client_id'     => $config['client_id'],
                'client_secret' => $config['client_secret'],
                'refresh_token' => $config['refresh_token'],
                'grant_type'    => 'refresh_token',
            ];
            $objXml = new Xml();
            $xml = $objXml->arrayToXml($arrXmlData,'root');
            $header = ['Content-type:text/xml'];
            $response = Curl::curlPost($this->apiBaseUrl.'refresh_token',$xml,$header);
            $result = Xml::xmlToArray($response);
            if($result['status']==0){
                $this->access_token = $result['access_token'];
                $this->refresh_token = $result['refresh_token'];
                $this->expires_in = $result['expires_in'];
                $this->expiry_time = $result['expiry_time'];
                return true;
            }else{
                $this->error = $result['error_message'];
            }
        }catch(Exception $ex){
            $this->error = $ex->getMessage();
        }
        return false;
    }

    /**
     * 验证access_token是否有效
     * @param $accessToken
     * @return bool
     */
    public function checkAccessToken($accessToken)
    {
        try{
            $header = ['Content-type:text/xml'];
            $objXml = new Xml();
            $xml = $objXml->arrayToXml(['access_token'=>$accessToken],'root');
            $response = Curl::curlPost('https://wishpost.wish.com/api/v2/auth_test',$xml,$header);
            $result = Xml::xmlToArray($response);
            if($result['status']==0){
                return true;
            }else{
                $this->error = $result['error_message'];
            }
        }catch(Exception $ex){
            $this->error = $ex->getMessage();
        }
        return false;
    }

    private function checkConfig($scene,$config)
    {
        $rule = [
            ['client_id','require','缺少客户ID'],
            ['code','require','缺少授权码'],
            ['client_secret','require','缺少客户端秘钥'],
            ['redirect_uri','require','缺少重新载入URI'],
            ['refresh_token','require','缺少refresh_token'],
        ];
        $validate = new Validate($rule);
        $validate->scene('obtaining', ['client_id', 'code','client_secret','redirect_uri']);
        $validate->scene('refresh', ['client_id', 'client_secret','refresh_token']);
        if (!$validate->scene($scene)->check($config)) {
            $this->error = $validate->getError();
            return false;
        }
        return true;
    }
}