<?php
namespace app\index\controller;

use app\common\controller\Base;
use think\Request;
use think\Db;
use app\index\service\DeveloperService;
use app\common\service\Common as CommonService;
use think\Exception;
use app\goods\service\CategoryHelp;

/**
 * @module 通用系统
 * @title 分组信息
 * @url /developers
 * @author phill
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2017/3/2
 * Time: 19:21
 */
class DeveloperTeam extends Base
{
    /**
     * @var DeveloperService
     */
    protected $developerService;

    protected function init()
    {
        if (is_null($this->developerService)) {
            $this->developerService = new DeveloperService();
        }
    }

    /**
     * @title 分组列表
     */
    public function  index()
    {
        $request = Request::instance();
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 10);
        $name = $request->get('name', '');
        $params = $request->param();
        $where = [];
        if (!empty($name)) {
            $where['a.name'] = ['like', '%' . $name . '%'];
        }
        if (isset($params['snType']) && isset($params['snText']) && !empty($params['snText'])) {
            $snText = $params['snText'];
            switch ($params['snType']) {
                case 'developer':
                    $where['a.developer_id'] = ['=', $snText];
                    break;
                case 'category':
                    $where['a.category_id'] = ['=', $snText];
                    break;
                default:
                    break;
            }
        }
        $result = $this->developerService->developerList($where, $page, $pageSize);
        return json($result, 200);
    }

    /**
     * @title 读取
     * @param $id
     * @return \think\response\Json
     */
    public function read($id)
    {
        if (!is_numeric($id) || empty($id)) {
            return json(['message' => '测试错误'], 400);
        }
        $result = $this->developerService->read($id);
        return json($result, 200);
    }

    /**
     * @title 获取编辑信息
     * @param $id
     * @return \think\response\Json
     */
    public function edit($id)
    {
        if (!is_numeric($id) || empty($id)) {
            return json(['message' => '参数错误'], 400);
        }
        $result = $this->developerService->read($id);
        return json($result, 200);
    }

    /**
     * @title 保存
     * @param Request $request
     * @return \think\response\Json
     */
    public function save(Request $request)
    {
        $data = $request->param();
        if (!isset($data['name']) || empty($data['name'])) {
            return json(['message' => '分组名称不能为空'], 400);
        }
        $validateDeveloperGroup = validate('DeveloperTeam');
        if (!$validateDeveloperGroup->check($data)) {
            return json(['message' => $validateDeveloperGroup->getError()], 400);
        }
        //查出是谁操作的
        $user = CommonService::getUserInfo($request);
        if (!empty($user)) {
            $data['creator_id'] = $user['user_id'];
            $data['updater_id'] = $user['user_id'];
        }
        $result = $this->developerService->add($data);
        return json(['message' => '新增成功','data' => $result]);
    }

    /**
     * @title 更新
     * @param Request $request
     * @param $id
     * @return \think\response\Json
     */
    public function update(Request $request, $id)
    {
        if (!is_numeric($id) || empty($id)) {
            return json(['message' => '参数错误'], 400);
        }
        $data = $request->param();
        //查出是谁操作的
        $user = CommonService::getUserInfo($request);
        if (!empty($user)) {
            $data['updater_id'] = $user['user_id'];
        }
        $validateDeveloperGroup = validate('DeveloperTeam');
        $data['id'] = $id;
        if (!$validateDeveloperGroup->scene('edit')->check($data)) {
            return json(['message' => $validateDeveloperGroup->getError()], 400);
        }
        unset($data['id']);
        $result = $this->developerService->update($data, $id);
        return json(['message' => '更新成功', 'data' => $result]);
    }

    /**
     * @title 删除
     * @param $id
     * @return \think\response\Json
     */
    public function delete($id)
    {
        if (!is_numeric($id) || empty($id)) {
            return json(['message' => '参数错误'], 400);
        }
        $this->developerService->delete($id);
        return json(['message' => '删除成功'], 200);
    }

    /**
     * @title 批量删除
     * @url batch/:type(\w+)
     * @method post
     * @return \think\response\Json
     */
    public function batch()
    {
        $request = Request::instance();
        $this->developerService->batch($request);
        return json(['message' => '删除成功'], 200);
    }

    /**
     * @title 获取分类信息
     * @url categories
     * @return \think\response\Json
     */
    public function category()
    {
        $request = Request::instance();
        $pid = $request->get('subclass', 0);   // 0大类 1-子类
        $content = $request->get('content', '');
        $categoryHelp = new CategoryHelp();
        $result = $categoryHelp->info($pid, $content);
        return json($result, 200);
    }
}
