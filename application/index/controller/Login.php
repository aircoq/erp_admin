<?php
namespace app\index\controller;

use app\common\cache\Cache;
use app\common\service\Common;
use think\Request;
use app\common\model\User as UserModel;
use app\common\controller\Base;
use Odan\Jwt\JsonWebToken;
use think\Config as ThinkConfig;
use app\index\service\User as UserServer;

/**
 * @module 用户系统
 * @title 登录
 * @author phill
 * @url /login
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/11/17
 * Time: 14:57
 */
class Login extends Base
{
    /**
     * @noauth
     * @title 显示资源列表
     * @return \think\Response
     */
    public function index()
    {
        $request = Request::instance();
        if ($auth = $request->header('authorization')) {
            $jwt = new JsonWebToken();
            $key = ThinkConfig::get('jwt_key');
            $payload = $jwt->decode($auth, $key);
            if ($payload['valid'] == true) {
                return json(['is_login' => true],200);
            }
        }else{
            return json(['is_login' => false],200);
        }
    }

    /**
     * @noauth
     * @title 登录
     * @param Request $request
     * @return mixed
     */
    public function save(Request $request)
    {
        $username = $request->post('username','');
        $password = $request->post('password','');
        $code = $request->post('code','');
        $key = $request->post('captcha','');
        $user_model = new UserModel();
        // 检查IP黑名单
        if ($user_model::checkBlacklist(5, 15)) {
            return json(['message' => '你已经被限制登陆15分钟!'],400);
        }
        if (empty($username) || empty($password)) {
            return json(['message' => '用户名或者密码不能为空，请重新输入!'],400);
        }
        if (empty($code)) {
            return json(['message' => '请输入验证码!'],400);
        }
//        if(empty($key)){
//            return json(['message' => '验证码错误，请重新输入!'],400);
//        }
        //验证码开始验证
//        if(!captcha_check($code,$key)){
//            return json(['message' => '验证码错误或者已过期，请重新输入!'],400);
//        };
        $result = $user_model->login($username, $password, 0);
        if (!$result['state']) {
            return json(['message' => $result['message']],400);
        } else {
            return json(['message' => '登录成功','token' => $result['token']],200);
        }
    }

    /**
     * @noauth
     * @title 退出
     * @url quit
     * @method post
     */
    public function quit()
    {
        $user_model = new UserModel();
        $user_model->quit();
    }

    /**
     * @noauth
     * @title 权限
     * @url permission
     * @param UserServer $user
     * @return \think\response\Json
     */
    public function permission(UserServer $user)
    {
        $permission = $user->permission();
        return json($permission);
    }

    /**
     * @noauth
     * @title 获取登录信息
     * @url info
     * @return \think\response\Json
     */
    public function info()
    {

        $userInfo = Common::getUserInfo();
        $userInfo = $userInfo->toArray();
        $userCache = Cache::store('User')->getOneUser($userInfo['user_id']);
        $userInfo['is_first'] = $userCache['is_first'] ?? 0;
        return json($userInfo);
    }

    /**
     * @title 获取websocket token
     * @url ws-token
     */
    public function webSocketToken()
    {
        return json("");
    }

    /**
     * @noauth
     * @title 获取验证码
     * @url code
     * @param string $id
     * @return mixed
     */
    public function captcha($id="")
    {
        return captcha($id);
    }
}