<?php
namespace app\index\controller;

use app\common\controller\Base;
use app\index\service\ServerNetwork as serverNetworkService;
use think\Request;
use app\common\exception\JsonErrorException;

/**
 * @module 基础设置
 * @title 服务器使用记录
 * @author phill
 * @url server-network
 * Created by PhpStorm.
 * User: zhaixueli
 * Date: 2017/11/29
 * Time: 17:35
 */
class ServerNetwork extends Base
{
    protected $serverNetworkService;

    protected function init()
    {
        if (is_null($this->serverNetworkService)) {
            $this->serverNetworkService = new serverNetworkService();
        }
    }

    /**
     * @title 服务器使用记录
     * @param Request $request
     * @return \think\response\Json
     */
    public function index(Request $request)
    {

        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 10);
        $where = $this->serverNetworkService->getWhere($request);
        $result = $this->serverNetworkService->list($where,$page,$pageSize);
        return json($result, 200);





    }

    /**
     * @title 保存服务器信息
     * @param Request $request
     * @return \think\response\Json
     */

    public function save(Request $request)
    {
        try {

            $params = $request->param();
            $result = $this->serverNetworkService->add($params);
            if ($result === false) {
                return json(['message' => $this->serverNetworkService->getError()], 400);

            }
            return json(['message' => '新增成功', 'params' => $result], 200);
        } catch (Exception $exp) {
            throw new JsonErrorException("File:{$exp->getFile()};
            Line:{$exp->getLine()};Message:{$exp->getMessage()}
            ");

        }
    }

}