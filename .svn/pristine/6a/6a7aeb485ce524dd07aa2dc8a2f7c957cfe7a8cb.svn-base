<?php
namespace app\index\controller;

use app\index\service\ConfigService;
use superbrowser\SuperBrowserBaseApi;
use think\Request;
use app\common\controller\Base;
use app\common\cache\Cache;
use app\common\model\Config as ConfigModel;
use think\Exception;

/**
 * @module 通用系统
 * @title 系统配置
 * @url /config
 * @author phill
 * Class Config
 * @package app\index\controller
 */
class Config extends Base
{
    protected $configService;

    protected function init()
    {
        if (is_null($this->configService)) {
            $this->configService = new ConfigService();
        }
    }


    /**
     * @title 显示资源列表
     * @param Request $request
     * @return \think\response\Json
     */
    public function index(Request $request)
    {
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 10);
        $search = [
            'group_id' => $request->get('group_id', ''),
            'status' => $request->get('status', ''),
            'name' => trim($request->get('name', '')),
            'type' => trim($request->get('type', ''))
        ];
        $where = [];
        if ($search) {
            //分组
            if ($search['group_id'] != '') {
                $where['group_id'] = intval($search['group_id']);
            }
            //状态
            if ($search['status'] != '') {
                $where['status'] = intval($search['status']);
            }
            if ($search['name'] != '') {
                $where['name'] = ['like', '%' . strtoupper($search['name']) . '%'];
            }
            if (isset($search['type']) && $search['type'] != '') {
                switch ($search['type']) {
                    case 0:  //参数
                        $where['group_id'] = ['<>', 0];
                        break;
                    case 1:  //分组
                        $where['group_id'] = ['=', 0];
                        break;
                }
            }
        }
        $result = $this->configService->configList($where, $page, $pageSize);
        return json($result, 200);
    }

    /**
     * @title 添加
     * @param  \think\Request $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        $params = $request->param();
        if (empty($params)) {
            return json(['message' => '请求参数错误'], 400);
        }
        if (empty($params['group_id'])) {
            $params['group_id'] = 0;
        }
        $f = preg_match('/^[a-z]{1}[a-z0-9_]+$/', $params['name']);
        if(!$f){
            throw new Exception("参数标识格式不正确，只能由小写字母和数字下划线组成，且开头必须为小写字母");
        }
        if (isset($params['group_id']) && $params['group_id'] > 0) {
            $result = Cache::store('configParams')->getConfig($params['group_id']);
            if($result){
                $params['name'] = $result['name']."_".$params['name'];
            }
        }
        $validateConfig = validate('Config');
        if (!$validateConfig->check($params)) {
            return json(['message' => $validateConfig->getError()], 400);
        }
        if (isset($params['setting'])) {
            $params['setting'] = trim($params['setting']);
        }
        $id = $this->configService->save($params);
        return json(['message' => '新增成功','id' => $id], 200);
    }

    /**
     * @title 显示指定的资源
     * @url /config/:id(\w+)
     * @param $id
     * @return \think\response\Json
     * @throws Exception
     */
    public function read($id)
    {
        if (empty($id)) {
            return json(['message' => '请求参数错误'], 400);
        }
        $result = Cache::store('configParams')->getConfig($id);
        if (!$result) {
            return json(['message' => '找不到对应分组'], 400);
        }
        $config = $this->configService->config($result);
        return json($config, 200);
    }

    /**
     * @title 站点配置
     * @url /config/site
     * @return \think\response\Json
     * @throws Exception
     */
    public function readSite()
    {
       return $this->read('site');
    }

    /**
     * @title 显示编辑资源表单页.
     * @param  int $id
     * @return \think\Response
     */
    public function edit($id)
    {
        if (empty($id)) {
            return json(['message' => '请求参数错误'], 400);
        }
        $params = Cache::store('configParams')->getConfig($id);
        if(is_json($params['setting'])){
            $params['setting'] = json_decode($params['setting'],true);
        }
        return json($params, 200);
    }

    /**
     * @title 保存更新的资源
     * @param  \think\Request $request
     * @param  int $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        if (empty($id)) {
            return json(['message' => '请求参数错误'], 400);
        }
        $params = $request->param();
        $validateConfig = validate('Config');
        if (!$validateConfig->check($params)) {
            return json(['message' => $validateConfig->getError()], 400);
        }
        $params['value'] = '';
        $this->configService->update($params, $id);
        return json(['message' => '更新成功'], 200);
    }

    /**
     * @title 保存更新的资源
     * @url paramsConfig/:id
     * @method put
     * @match ['id' => '\d+']
     * @param  \think\Request $request
     * @param  int $id
     * @return \think\Response
     */
    public function paramsConfig(Request $request, $id)
    {
        $params = $request->param();
        $this->configService->params($params);
        return json(['message' => '保存成功']);
    }

    /**
     * @title 删除
     * @param $id
     * @return \think\response\Json
     * @throws \think\Exception
     */
    public function delete($id)
    {
        if (empty($id)) {
            return json(['message' => '请求参数错误'], 400);
        }
        $this->configService->delete($id);
        return json(['message' => '删除成功'], 200);
    }

    /**
     * @title 获取分组
     * @url groups
     */
    public function groups()
    {
        $groups = ConfigModel::getGoroups();
        $types = [1 => '单行文本', 2 => '多行文本', 3 => '数组'];
        $choose_types = [0 => '单选', 1 => '多选'];
        $result = [
            'types' => $types,
            'choose_types' => $choose_types,
            'groups' => $groups,
        ];
        return json($result, 200);
    }

    /**
     * @title 停用，启用
     * @url status
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\Exception
     */
    public function changeStatus(Request $request)
    {
        $id = $request->get('id', 0);
        $status = $request->get('status', 0);
        if (empty($id) || !isset($status)) {
            return json(['message' => '请求参数错误'], 400);
        }
        $this->configService->status($id, $status);
        return json(['message' => '操作成功'], 200);
    }

    /**
     * @title 排序
     * @url sort
     * @method post
     * @param Request $request
     * @return \think\response\Json
     */
    public function sort(Request $request)
    {
        $sorts = $request->post('sorts', []);
        if (empty($sorts)) {
            return json(['message' => '请求参数错误'], 400);
        }
        $sorts = json_decode($sorts, true);
        if (!is_array($sorts)) {
            return json(['message' => '请求参数错误'], 400);
        }
        $this->configService->sort($sorts);
        return json(['message' => '操作成功'], 200);
    }

    /**
     * @title 数据类型
     * @url type
     * @method get
     * @return \think\response\Json
     */
    public function type()
    {
        $result = $this->configService->dataType();
        return json($result, 200);
    }
}
