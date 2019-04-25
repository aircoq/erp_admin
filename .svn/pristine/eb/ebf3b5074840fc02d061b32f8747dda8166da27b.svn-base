<?php

namespace app\index\controller;

use app\common\model\VirtualOrderUser;
use app\index\service\VirtualUserService;
use app\order\service\VirtualOrderHelp;
use think\Request;
use app\common\model\User as UserModel;
use app\common\controller\Base;
use Odan\Jwt\JsonWebToken;


/**
 * @title 国外刷手登录
 * @author phill
 * @url /virtual-user
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/11/17
 * Time: 14:57
 */
class VirtualUser extends Base
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
            $key = VirtualOrderUser::JWT_KEY;
            $payload = $jwt->decode($auth, $key);
            if ($payload['valid'] == true) {
                return json(['is_login' => true], 200);
            }
        } else {
            return json(['is_login' => false], 200);
        }
    }


    /**
     * @noauth
     * @title 登录
     * @url login
     * @method post
     * @param Request $request
     * @return mixed
     */
    public function login(Request $request)
    {
        $username = $request->post('username', '');
        $password = $request->post('password', '');
        $code = $request->post('code', '');
        $key = $request->post('captcha', '');
        $user_model = new UserModel();
        // 检查IP黑名单
        if ($user_model::checkBlacklist(5, 15)) {
            return json(['message' => '你已经被限制登陆15分钟!'], 400);
        }
        if (empty($username) || empty($password)) {
            return json(['message' => '用户名或者密码不能为空，请重新输入!'], 400);
        }
        if (empty($code)) {
//            return json(['message' => '请输入验证码!'], 400);
        }
        if (empty($key)) {
//            return json(['message' => '验证码错误，请重新输入!'], 400);
        }
        //验证码开始验证
        if (!captcha_check($code, $key)) {
            //return json(['message' => '验证码错误或者已过期，请重新输入!'],400);
        };
        $users = new VirtualOrderUser();
        $result = $users->login($username, $password, 0);
        if (!$result['state']) {
            return json(['message' => $result['message']], 500);
        } else {
            return json(['message' => '登录成功', 'token' => $result['token']], 200);
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
        $user_model = new VirtualOrderUser();
        $user_model->quit();
    }


    /**
     * @noauth
     * @title 获取登录信息
     * @url info
     * @return \think\response\Json
     */
    public function info()
    {
        return json(VirtualOrderUser::getUserInfo());
    }

    /**
     * @noauth
     * @title 获取国家信息
     * @url country
     * @return \think\response\Json
     */
    public function country()
    {
        $virtualUser = new VirtualUserService();
        $country = $virtualUser->getCountry();
        return json($country);
    }

    /**
     * @noauth
     * @title 获取平台信息
     * @url channel
     * @return \think\response\Json
     */
    public function channel()
    {
        $virtualUser = new VirtualUserService();
        $country = $virtualUser->getChannel();
        return json($country);
    }


    /**
     * @noauth
     * @title 获取验证码
     * @url code
     * @param string $id
     * @return mixed
     */
    public function captcha($id = "")
    {
        return captcha($id);
    }

    /**
     * @noauth
     * @title 注册
     * @method POST
     * @url register
     * @param  \think\Request $request
     * @return json
     */
    public function register(Request $request)
    {
        $params = $request->param();
        $data = $params;
        $virtualUser = new VirtualUserService();
        $allMessage = $virtualUser->getAllMessage($params);

        $result = $this->validate($data, [
            ['email', 'require', $allMessage['contactEmail']],
            ['country', 'require', $allMessage['contactCountry']],
            ['city', 'require',$allMessage['contactCity']],
            ['platform', 'require', $allMessage['contactPlatform']],
            'password|密码' => 'require|length:6,18',
        ]);
        if ($result !== true) {
            return json(['message' => $result], 400);
        }

        $user = $virtualUser->add($data);
        $message = $allMessage['succeedRegister'];
        return json(['message' =>$message, 'data' => $user]);
    }


    /**
     * @title 刷单任务列表
     * @url list
     * @method get
     * @autor libaimin
     */
    public function missionList()
    {
        $param = $this->request->param();
        $virtualUser = new VirtualUserService();
        $page = isset($param['page']) ? $param['page'] : 1;
        $pageSize = isset($param['pageSize']) ? $param['pageSize'] : 50;
        $message = $virtualUser->getSingleTaskList($page, $pageSize, $param);
        return json($message, 200);
    }

    /**
     * @title 刷单任务状态列表
     * @url status
     * @method get
     * @autor libaimin
     */
    public function missionStatus()
    {
        $param = $this->request->param();
        $virtualUser = new VirtualUserService();
        $message = $virtualUser->getSingleTaskStatus($param);
        return json($message, 200);
    }


    /**
     * @title 刷单任务处理
     * @url dispose
     * @method post
     * @autor libaimin
     */
    public function dispose()
    {
        $userId = VirtualOrderUser::getUserInfo()['user_id'];
        $param = $this->request->param();
        $virtualUser = new VirtualUserService();
        $taskNumber = isset($param['task_number']) ? $param['task_number'] : '';
        $message = $virtualUser->setDispose($taskNumber, $param, $userId);
        return json($message, 200);
    }

    /**
     * @title 刷单任务回评
     * @url review
     * @method post
     * @autor libaimin
     */
    public function review()
    {
        $userId = VirtualOrderUser::getUserInfo()['user_id'];
        $param = $this->request->param();
        $virtualUser = new VirtualUserService();
        $taskNumber = isset($param['task_number']) ? $param['task_number'] : '';
        $message = $virtualUser->setReview($taskNumber, $userId,$param);
        return json($message, 200);
    }

    /**
     * @title 用户详细信息
     * @url user-info
     * @method get
     * @autor libaimin
     */
    public function userInfo()
    {
        $userId = VirtualOrderUser::getUserInfo()['user_id'];
        $virtualUser = new VirtualUserService();
        $message = $virtualUser->getOne($userId);
        return json($message, 200);
    }

    /**
     * @title 更新用户密码
     * @url user-save
     * @method post
     * @autor libaimin
     */
    public function userSave()
    {
        $userId = VirtualOrderUser::getUserInfo()['user_id'];
        $param = $this->request->param();

        $result = $this->validate($param, [
            'old_pwd|旧密码' => 'require|length:6,18',
            'new_pwd|新密码' => 'require|length:6,18',
        ]);
        if ($result !== true) {
            return json(['message' => $result], 400);
        }
        $virtualUser = new VirtualUserService();
        $oldPwd = isset($param['old_pwd']) ? $param['old_pwd'] : '';
        $newPwd = isset($param['new_pwd']) ? $param['new_pwd'] : '';
        $message = $virtualUser->setUserPwd($userId, $newPwd, $oldPwd);
        return json($message, 200);
    }

    /**
     * @title 更新用户信息
     * @method POST
     * @url editor
     * @param  \think\Request $request
     * @return json
     */
    public function editor(Request $request)
    {
        $params = $request->param();
        $virtualUser = new VirtualUserService();
        $allMessage = $virtualUser->getAllMessage($params);
        $data = $params;
        $result = $this->validate($data, [
            ['email', 'require', $allMessage['contactEmail']],
            ['country', 'require', $allMessage['contactCountry']],
            ['city', 'require',$allMessage['contactCity']],
            ['platform', 'require', $allMessage['contactPlatform']],
        ]);
        if ($result !== true) {
            return json(['message' => $result], 400);
        }
        $virtualUser = new VirtualUserService();
        $userId = VirtualOrderUser::getUserInfo()['user_id'];
        $user = $virtualUser->update($data, $userId);

        $message = $allMessage['succeed'];
        return json(['message' => $message, 'data' => $user]);
    }

    /**
     * @title 货币类型
     * @url currency
     * @method get
     * @autor libaimin
     */
    public function currency()
    {
        $virtualOrderHelp = new VirtualOrderHelp();
        $message = $virtualOrderHelp->getCcurrency();
        return json($message, 200);
    }

    /**
     * @noauth
     * @title 关于我们
     * @url about-us
     * @method get
     * @autor libaimin
     */
    public function aboutUs(Request $request)
    {
        $params = $request->param();
        $virtualUser = new VirtualUserService();
        $language = $params['language'] ?? 'CN';
        $message = $virtualUser->aboutUs($language);
        return json($message, 200);
    }

}