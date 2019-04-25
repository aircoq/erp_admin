<?php
namespace app\api\components;

use think\exception\HttpResponseException;
use think\Request;
use app\common\model\User;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2017/4/6
 * Time: 10:13
 */
class ApiPost
{
    /** 请求检查
     * @param Request $request
     * @return bool
     */
    public static function requestCheck(Request $request)
    {
        if (!$request->isPost() && !$request->isGet()) {
            self::error('The request method must be POST or GET');
        }
        if ($request->get('url', false) === false) {
            self::error("The request url can't be empty");
        }
        //判断来源
//        if ($request->post('app', false) === false) {
//            self::error("app can't be empty");
//        }
//        if ($request->post('version', false) === false) {
//            self::error("version can't be empty");
//        }
//        if ($request->post('mark', false) === false) {
//            self::error("mark can't be empty");
//        }
        return true;
    }

    /** 开始执行
     * @param $apiData
     * @return \think\response\Json
     */
    public static function requestStart($apiData)
    {
        $request = Request::instance();
        $retData['uid'] = 0;
        $retData['status'] = 1;
        $param = $request->post('param') ? json_decode($request->post('param'), true) : '';
        $token = (!empty($param) && isset($param['token'])) ? $param['token'] : null;
        if (!isset($apiData['status']) || $apiData['status'] != 1 || !isset($apiData['make'])) {
            self::error('api is closed');
        }
        //检验sign
        if (isset($apiData['sign']) && $apiData['sign'] == 1) {
            $app = $request->get('app','app');
            $version = $request->get('version','1.0');
            $mark = $request->get('mark',1);
            $sign = $request->get('sign', false);
            $_sign = md5("app=" . $app . "&version=" . $version . "&mark=" . $mark);
            if ($sign === false) {
                self::error("sign cant't be empty");
            }
            if ($_sign != $sign) {
                self::error("sign error");
            }
        }
        //登录检验
        if (isset($apiData['make']) && $apiData['make'] == 2) {
            if (empty($token)) {
                self::error("none token");
            }
            $token_return = self::checkUserToken($token);
            if ($token_return['status'] != 1) {
                return json($token_return);
            }
            $retData['uid'] = $token_return['data']['uid'];
        } else {
            if (!empty($token)) {
                $token_return = self::checkUserToken($token);
                if ($token_return['status'] == 1) {
                    $retData['uid'] = $token_return['data']['uid'];
                }
            }
        }
        //记录日志
        if (isset($apiData['logs']) && $apiData['logs'] == 1) {
            insertSearch(json_encode($request->param()), $request->url());
        }
        return $retData;
    }

    /** 检验token
     * @param null $token
     * @return array
     */
    public static function checkUserToken($token = null)
    {
        $uid = ApiCommon::undoToken($token);
        if ($uid <= 0) {
            self::error("token_error",2);
        }
        $userModel = new User();
        $userData = $userModel->where(['id' => $uid, 'status' => 1])->find();
        if (empty($userData)) {
            self::error("no login",2);
        }
        if (empty($userData['token']) || $userData['token'] != $token) {
            self::error("no login",2);
        }
        return ['status' => 1, 'data' => ['uid' => $uid]];
    }

    /** 请求数据
     * @param array $postData
     * @return array
     */
    public static function requestPostData($postData = [])
    {
        if (isset($postData['mark'])) {
            unset($postData['mark']);
        }
        if (isset($postData['version'])) {
            unset($postData['version']);
        }
        if (isset($postData['sign'])) {
            unset($postData['sign']);
        }
        if (isset($postData['token'])) {
            unset($postData['token']);
        }
        return $postData;
    }

    /** 请求结束
     * @param $return
     * @return \think\response\Json
     */
    public static function requestEnd($return)
    {
        if (isset($return['request_time'])) {
            $return['time'] = time() - $return['request_time'] . 's';
            unset($return['request_time']);
        }
        if (isset($return['token'])) {
            unset($return['token']);
        }
        if (isset($return['uid'])) {
            unset($return['uid']);
        }
        if (isset($return['sid'])) {
            unset($return['sid']);
        }
        if (isset($return['data']) && empty($return['data'])) {
            $return['data'] = [];
        }
        $encode = !empty($return) ? $return : ['message' => 'No data is returned','status' => -10];
        throw new HttpResponseException(json($encode),200);
    }

    /** 错误信息输出
     * @param string $message
     * @param int $status
     */
    public static function error($message = '',$status = -10)
    {
        throw new HttpResponseException(json(['message' => $message,'status' => $status]),200);
    }
}