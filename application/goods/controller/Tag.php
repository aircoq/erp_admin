<?php
namespace app\goods\controller;

use think\Controller;
use think\Exception;
use think\Request;
use app\common\controller\Base;
use think\Db;
use app\common\model\Tag as tagModel;
use app\common\cache\Cache;
use app\common\service\Common;

/**
 * Class Tag
 * @title 标签管理
 * @module 商品系统
 * @author ZhaiBin
 * @package app\goods\controller
 */
class Tag extends Base
{
    /**
     * @title 显示标签列表
     * @method get
     * @url /tag
     * @return \think\Response
     */
    public function index()
    {
        $request = Request::instance();
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 10);
        $model = new tagModel();
        $count = $model->count();
        $tagList = $model->field('*')->page($page, $pageSize)->select();
        foreach($tagList as $k=>$v){
            // $tagList[$k]['create_time']=date('Y-m-d H:i:s', (int)$v['create_time']);
            // $tagList[$k]['update_time']=date('Y-m-d H:i:s', (int)$v['update_time']);
            $tagList[$k]['creater']=Cache::store('user')->getOneUserRealname($v['create_id']);
            $tagList[$k]['updater']=Cache::store('user')->getOneUserRealname($v['update_id']);
        }
        $result = [
            'data' => $tagList,
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
        ];
        return json($result, 200);
    }

    /**
     * @title 保存标签
     * @url /tag
     * @method post
     * @param  \think\Request $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        $data['name'] = $request->post('name', '');
        $data['description'] = $request->post('desc', '');
        $data['create_time'] = time();
        $data['update_time'] = time();
        $data['create_id']=Common::getUserInfo($request) ? Common::getUserInfo($request)['user_id'] : 0;
        $tagModel = new tagModel();
        $validateTag = validate('Tag');
        if (!$validateTag->check($data)) {
            return json(['message' => $validateTag->getError()], 400);
        }
        $bool = $tagModel->allowField(true)->isUpdate(false)->save($data);
        $id = $tagModel->id;
        if ($bool) {
            Cache::handler()->del('cache:tag');
            return json(['message' => '新增成功','id' => $id], 200);
        } else {
            return json(['message' => '新增失败'], 500);
        }
        
    }

    /**
     * @title 编辑标签
     * @method get
     * @url /tag/:id(\d+)/edit
     * @param  int $id
     * @return \think\Response
     */
    public function edit($id)
    {
        if (!is_numeric($id)) {
            return json(['message' => '参数错误'], 400);
        }
        $tagModel = new tagModel();
        $result = $tagModel->field('*')->where(['id' => $id])->find();
        // $result['create_time']=date('Y-m-d H:i',$result['create_time']);
        // $result['update_time']=date('Y-m-d H:i',$result['update_time']);
        $result = empty($result) ? [] : $result;
        return json($result, 200);
    }

    /**
     * @title 更新标签
     * @method put
     * @url /tag/:id(\d+)
     * @param  \think\Request $request
     * @param  int $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
       if (!is_numeric($id)) {
            return json(['message' => '参数错误'], 400);
        }
        $params = $request->param();
        $data['name'] = isset($params['name']) ? $params['name'] : '';
        $data['description'] = isset($params['desc']) ? $params['desc'] : '';
        $data['update_time'] = time();
        $data['update_id']=Common::getUserInfo($request) ? Common::getUserInfo($request)['user_id'] : 0;
        $tagModel = new tagModel();
        if (!$tagModel->isHas($id)) {
            return json(['message' => '该标签不存在'], 500);
        }
        //判断名称是否重复
        $where = "name = '".$data['name']."' and id != ".$id;
        $tagList = $tagModel->where($where)->select();
        if(!empty($tagList)){
            return json(['message' => '该标签已存在'], 500);
        }
        $result = $tagModel->allowField(true)->save($data, ['id' => $id]);
        if ($result) {
            Cache::handler()->del('cache:tag');
            return json(['message' => '更新成功'], 200);
        } else {
            return json(['message' => '更新失败'], 500);
        }
    }

    /**
     * @title 删除标签
     * @method delete
     * @url /tag/:id(\d+)
     * @param  int $id
     * @return \think\Response
     */
    public function delete($id)
    {
        if (!is_numeric($id)) {
            return json(['message' => '参数错误'], 400);
        }
        /*$goodsTag=Cache::store('goods')->getGoodsTag();
        if(in_array($id,$goodsTag)){
            return json(['message' => '不能删除商品中已存在的标签'], 500);
        }*/
        $tagModel = new tagModel();
        $result = $tagModel->where(['id' => $id])->delete();
        if ($result) {
            Cache::handler()->del('cache:tag');
            return json(['message' => '删除成功'], 200);
        } else {
            return json(['message' => '删除失败'], 500);
        }
    }
    
    /**
     * @title 获取标签字段值
     * @method get
     * @url /tag/dictionary
     * @return \think\Response
     */
    public function dictionary()
    {
        $result = Cache::store('tag')->getTag();
        return json($result);
    }
}


