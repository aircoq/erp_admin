<?php

namespace app\api\service;

use app\common\cache\Cache;
use app\common\model\ServerUserAccountInfo;
use app\common\model\User;
use app\index\service\ManagerServer;
use think\Exception;
use think\helper\Str;
use think\Config;
use Odan\Jwt\JsonWebToken;

/**
 * Created by PhpStorm.
 * User: phill
 * Date: 2018/12/19
 * Time: 8:28 PM
 */
class SuperBrowser extends Base
{

    /**
     * 获取验证码
     */
    public function code()
    {
        $username = $this->requestData['username'];
        try {
            $where = [
                'username' => $username,
                'is_invalid' => 1
            ];
            $userId = (new User())->where($where)->value('id');
            if (!$userId) {
                $this->retData['status'] = -1;
                $this->retData['message'] = '用户不存在';
            } else {
                //1.生成codes
                $codes = (new Automation())->getRand();
                //2.保存缓存
                Cache::store('User')->userCodes($userId, $codes);
                //3.发送验证码
                $server = new Server();
                $server->sendMessage($userId,
                    '超级浏览器登录验证码为' . $codes . ',打死也不要告诉别人。',
                    '超级浏览器登录验证码');
                $this->retData['message'] = '发送成功，请查收,5分钟内有效';
                $this->retData['status'] = 'success';
                $this->retData['ret'] = 0;
            }

        } catch (Exception $e) {
            $this->retData['status'] = 'failed';
            $this->retData['message'] = 'ERP错误，请联系管理员';
        }
        return $this->retData;
    }

    /**
     * 登录
     */
    public function login()
    {
        $username = $this->requestData['username'];
        $codes = $this->requestData['passwd'];
        try {
            $where = [
                'username' => $username,
                'is_invalid' => 1
            ];
            $user = (new User())->where($where)->find();
            $userId = $user['id'] ?? '';
            if (!$userId) {
                $this->retData['status'] = -1;
                $this->retData['message'] = '用户不存在';
            } else {
                //1.拿缓存codes
                $cacheCodes = Cache::store('User')->userCodes($userId);
                //2.验证codes
                if (!$cacheCodes) {
                    $this->retData['status'] = -1;
                    $this->retData['message'] = '验证码已过期，请重新获取';
                } elseif ($cacheCodes != $codes) {
                    $this->retData['status'] = -1;
                    $this->retData['message'] = '验证码不正确，请查证后再次输入';
                } else {
                    //3.生成tokens
                    $this->retData['websites'] = (new ManagerServer())->getShopListByUserId($userId);
                    $this->retData['outTime'] = 3600 * 24 * 1;
                    $this->retData['message'] = '登录成功';
                    $this->retData['status'] = 'success';
                    $this->retData['id'] = $userId;
                    $this->retData['ret'] = 0;
                    $this->retData['level'] = 0;
                    $this->retData['is_limit_login'] = 1;
                    $this->retData['auth_starttime'] = '00:00:00';
                    $this->retData['auth_endtime'] = '23:59:59';
                    $this->retData['lastlogintime'] = date('Y-m-d H:i:s');
                    $tokens = (new \app\common\model\User())->createToken($user);
                    $this->retData['tokens'] = $tokens;
                }
            }

        } catch (Exception $e) {
            $this->retData['message'] = 'ERP错误，请联系管理员';
            $this->retData['status'] = 'failed';
        }
        return $this->retData;
    }

    /**
     * 获取店铺列表
     */
    public function shopList()
    {
        $token = $this->requestData['tokens'];
        $userId = $this->checkUser($token);
        if($userId){
            $this->retData['websites'] = (new ManagerServer())->getShopListByUserId($userId);
            $this->retData['message'] = '获取成功';
            $this->retData['status'] = 'success';
            $this->retData['ret'] = 0;
        }else{
            $this->retData['status'] = 'failed';
        }
        return $this->retData;
    }

    /**
     * 获取店铺详情
     */
    public function shopDetail()
    {
        $token = $this->requestData['tokens'];
        $userId = $this->checkUser($token);
        if($userId){
            $id = $this->requestData['website_id'];
            $shop = (new ManagerServer())->getShopDetailByIds($id,$userId);
            if($shop){
                $this->retData['env_website'] = $shop['env_website'];
                $this->retData['websites'] = $shop['websites'];
                $this->retData['message'] = '获取成功';
                $this->retData['status'] = 'success';
                $this->retData['ret'] = 0;
            }else{
                $this->retData['message'] = '店铺不属于你的';
                $this->retData['status'] = 'failed';
            }
        }else{
            $this->retData['message'] = '没有权限';
            $this->retData['status'] = 'failed';
        }
        return $this->retData;
    }

    /**
     * 上报钱包余额提醒
     * @return array
     */
    public function balance()
    {
        $token = $this->requestData['tokens'];
        $userId = $this->checkUser($token,true);
        if($userId){
            $reData = [
                'balance_amount' => $this->requestData['balance_amount'],
                'msg' => $this->requestData['msg'],
                'machine_string' => $this->requestData['machine_string'],
            ];
            $key = 'hash:superBrowser:balance';
            Cache::handler()->hSet($key, time(), json_encode($reData,JSON_UNESCAPED_UNICODE));
            $this->retData['message'] = '提醒成功';
            $this->retData['status'] = 'success';
            $this->retData['ret'] = 0;
        }else{
            $this->retData['message'] = '没有权限';
            $this->retData['status'] = 'failed';
        }

        return $this->retData;
    }

    /**
     * 记录cookie
     */
    public function record()
    {
        $token = $this->requestData['tokens'];
        $userId = $this->checkUser($token);
        $key = 'hash:superBrowser:cookie';
        Cache::handler()->hSet($key, time(), json_encode($this->requestData,JSON_UNESCAPED_UNICODE));
        if($userId) {
            $data = [
                'user_id' => $userId,
                'channel_id' => 0,
                'server_id' => 0,
                'account_id' => $this->requestData['website_id'],
                'cookie' => $this->requestData['cookie'],
                'profile' => $this->requestData['profile'],
                'relation_module' => $this->requestData['relation_module'] ?? 0,
            ];
            try {
                (new ServerUserAccountInfo())->add($data);
                $this->retData['message'] = '记录成功';
                $this->retData['status'] = 'success';
                $this->retData['ret'] = 0;
            } catch (Exception $e) {
                $this->retData['status'] = 'failed';
                $this->retData['message'] = 'ERP错误，请联系管理员';
            }
        }else{
            $this->retData['status'] = 'failed';
        }
        return $this->retData;
    }

    /**
     * 检查是否为合法用户
     * @param $auth
     * @return bool
     */
    public function checkUser($auth ,$isJum = false)
    {
        if($isJum && $auth == 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOjEsImV4cCI6MTU1MjQzOTkxOSwiYXVkIjoiIiwibmJmIjoxNTUyMzUzNTE5LCJpYXQiOjE1NTIzNTM1MTksImp0aSI6IjVjODcwOGVmOTBmZDU0LjExNTkxMjg0IiwidXNlcl9pZCI6MjIyOCwicmVhbG5hbWUiOiJcdTY3NGVcdTRmNzBcdTY1NGYiLCJ1c2VybmFtZSI6IjEzNTM1MDUwOTg0In0.b3fb449808ea0119754bb42c25d5f86198f6315db02a5962dcd05b06a1eb2562')
        {
            return 1;
        }
        $key = Config::get('jwt_key');
        $jwt = new JsonWebToken();
        $payload = $jwt->decode($auth, $key);
        if (!$payload['valid']) {
            $this->retData['status'] = -101;
            $this->retData['message'] = 'Error: Token is not valid';
            return false;
        }
        return $payload['claimset']['user_id'];
    }

}