<?php

namespace app\common\behavior;

use app\common\exception\JsonErrorException;
use app\common\exception\NotLoginException;
use app\common\model\VirtualOrderUser;
use app\index\controller\Login;
use app\system\controller\Menu;
use erp\ErpRbac;
use Odan\Jwt\JsonWebToken;
use think\Request;
use think\Config;
use app\common\service\Common;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/10/27
 * Time: 16:37
 */
class CheckAuth
{
    public function run(&$params)
    {
        $request = Request::instance();
        if (ErpRbac::withoutVisits($request)) {
            return true;
        } else {
            if (strtolower($request->module()) == 'api' || strtolower($request->controller()) == 'virtualuser') {
//                $app = $request->get('app');
//                $version = $request->get('version');
//                $mark = $request->get('mark');
//                $sign = $request->get('sign', false);
//                if ($sign === false) {
//                    echo json_encode(['message' => "sign can't be empty"]);
//                    httpCode(400);
//                    exit;
//                }
//                $_sign = md5("app=" . $app . "&version=" . $version . "&mark=" . $mark);
//                if ($sign != $_sign) {
//                    echo json_encode(['message' => "sign error"]);
//                    httpCode(406);
//                    exit;
//                }
            } else {
                //保留方法信息
                //insertSearch(json_encode($request->param()), $request->url());
                try {
                    $tokeTypes = $request->header('tokeTypes', '');
                    if ($tokeTypes == 'Virtual') {
                        VirtualOrderUser::getUserInfo();
                    } else {
                        Common::getUserInfo();
                    }
                } catch (NotLoginException $exception) {
                    throw new JsonErrorException("请先登录...");
                }
            }
        }
    }
}