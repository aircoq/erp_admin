<?php
namespace app\index\controller;

use app\common\controller\Base;
use app\index\service\ServerLog as ServerLogService;
use think\Request;

/**
 * @module 基础设置
 * @title 服务器日志
 * @author phill
 * @url server-logs
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/8/7
 * Time: 19:02
 */
class ServerLog extends Base
{
    protected $serverLogService;

    protected function init()
    {
        if (is_null($this->serverLogService)) {
            $this->serverLogService = new ServerLogService();
        }
    }

    /**
     * @title 服务器访问日志列表
     * @param Request $request
     * @return \think\response\Json
     */
    public function index(Request $request)
    {
        $params = $request->param();
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 10);
        $where = [];
        if(isset($params['snType']) && isset($params['snText']) && !empty($params['snText'])) {
            $text=$params['snText'];
            switch (trim($params['snType'])) {
                //服务器名
                case 'server':
                    $where['s.visit_server_name'] = ['like','%'.$text.'%'];
                    break;
                //用户名
                case 'user_name':
                    $where['u.username'] = ['like','%'.$text.'%'];
                    break;
                //平台账号
                case 'account_code':
                    $where['s.visit_account_code'] = ['like','%'.$text.'%'];
                    break;

            }

        }
        $params['date_b'] = isset($params['date_b']) ? $params['date_b'] : 0;
        $params['date_e'] = isset($params['date_e']) ? $params['date_e'] : 0;
        if (!empty($params['date_b']) || !empty($params['date_e'])) {
            $condition = timeCondition($params['date_b'], $params['date_e']);
            if (!is_array($condition)) {
                return json(['message' => '日期格式错误'], 400);
            }
            if (!empty($condition)) {
                $where['s.visit_time'] = $condition;
            }
        }
        $result = $this->serverLogService->logList($where, $page, $pageSize);
        return json($result, 200);
    }
}