<?php

namespace app\customerservice\controller;

use think\Controller;
use think\Request;
use app\common\controller\Base;
use app\common\cache\Cache;
use app\common\model\MsgTemplate as MsgTemplateModel;
use app\common\model\MsgTemplateGroup as MsgTemplateGroupModel;
use app\common\service\Common;
use app\customerservice\service\MsgTemplateHelp;


/**
 * @module 客服管理
 * @title 回复模板分组
 */
class MsgTemplateGroup extends Base

{   
     
    /**
     * @title 获取指定平台模板分组列表
     * @author tanbin
     * @method GET
     * @apiParam name:template_type type:int desc:模板类型（1-回复模板 ，2-评价模板）
     * @apiParam name:channel_id type:int desc:平台id 
     * @url /msg-tpl-group
     */
    public function index()
    {   
        $request = Request::instance();
        $params = $request->param();
        $template_type = $request->get('template_type', 0);
        $channel_id = $request->get('channel_id', 0);
       
        $service = new MsgTemplateHelp();
        $list = $service->getTplGroup($channel_id,$template_type);
        
        $result = [
            'data' => $list
        ];
        return json($result, 200);   
    }
    
    
    /**
     * @title 查看
     * @author tanbin
     * @method GET
     * @apiParam name:id type:int require:1 desc:ID
     * @url /msg-tpl-group/:id
     */
    public function read($id)
    {
        if (!is_numeric($id)) {
            return json(['message' => '参数错误'], 400);
        }
        $result = MsgTemplateGroupModel::field('*')->where(['id' => $id])->find();
        $result = empty($result) ? [] : $result;
        return json($result, 200); 
    }
    

    /**
     * @title 新增
     * @author tanbin
     * @method POST
     * @apiParam name:id type:int require:1 desc:ID
     * @url /msg-tpl-group
     */
    public function save(Request $request){   
        
        $data['template_type'] = $request->post('template_type', '');
        $data['channel_id'] = $request->post('channel_id', '');
        $data['group_name'] = $request->post('group_name', '');        
        $data['create_time'] = $data['update_time'] = time();
        $userInfo = Common::getUserInfo($request);
        $data['create_id'] = $data['update_id'] = $userInfo['user_id'];
               
        $validate = validate('MsgTemplateGroup');
        if (!$validate->check($data)) {
            return json(['message' => $validate->getError()], 400);
        }
        
        $msgTemplateGroupModel = new MsgTemplateGroupModel();
        $bool = $msgTemplateGroupModel->allowField(true)->isUpdate(false)->save($data);
        //$id = $msgTemplateModel->id;
        //删除缓存
        Cache::handler()->del('cache:msgTemplateGroup');
        if ($bool) {
            return json(['message' => '新增成功'], 200);
        } else {
            return json(['message' => '新增失败'], 500);
        }
       
    }

    
    /**
     * @title 编辑
     * @author tanbin
     * @method GET
     * @apiParam name:id type:int require:1 desc:ID
     * @url /msg-tpl-group/:id/edit
     */
    public function edit($id)
    {
        if (!is_numeric($id)) {
            return json(['message' => '参数错误'], 400);
        }
        $result = MsgTemplateGroupModel::field('*')->where(['id' => $id])->find();
        $result = empty($result) ? [] : $result;
        return json($result, 200);
    }
    
    
    /**
     * @title 更新
     * @author tanbin
     * @method PUT
     * @apiParam name:id type:int require:1 desc:ID
     * @url /msg-tpl-group/:id
     */
    public function update(Request $request,$id)
    {
        if (!is_numeric($id)) {
            return json(['message' => '参数错误'], 400);
        }
        
        $msgTemplateGroupModel = new MsgTemplateGroupModel();
        //判读是否存在
        $msgTemplateGroupModel->isHas($id);
        
        $params = $request->param();
        $data['id']   = $id;       
        $data['group_name'] = param($params, 'group_name');   
        if(isset($params['channel_id'])){
            $data['channel_id'] = $params['channel_id'];
        }
        $data['template_type'] = param($params, 'template_type');       
        $data['update_time'] = time();
        $userInfo = Common::getUserInfo($request);
        $data['update_id'] = $userInfo['user_id'];
         
        //验证规则
        $validate = validate('MsgTemplateGroup');
        if (!$validate->check($data)) {
            return json(['message' => $validate->getError()], 500);
        }
        
        $result = $msgTemplateGroupModel->allowField(true)->save($data, ['id' => $id]);
        //删除缓存
        Cache::handler()->del('cache:msgTemplateGroup');
        if ($result) {
            return json(['message' => '更新成功'], 200);
        } else {
            return json(['message' => '更新失败'], 500);
        }
       
    }


    /**
     * @title 删除
     * @author tanbin
     * @method DELETE
     * @apiParam name:id type:int require:1 desc:ID
     * @url /msg-tpl-group/:id
     */
    public function delete($id)
    {
        if (!is_numeric($id)) {
            return json(['message' => '参数错误'], 400);
        }
        
       //判读分组是否被使用
        $msgTplmodel = new MsgTemplateModel();
        $msgTplmodel->isHasGroup($id);
        
        //判断是否存在
        $msgTplGroupModel = new MsgTemplateGroupModel();
        $msgTplGroupModel->isHas($id);
      
        $result = $msgTplGroupModel->where(['id' => $id])->delete();
        if ($result) {
            //删除缓存
            Cache::handler()->del('cache:msgTemplateGroup');
            return json(['message' => '删除成功'], 200);
        } else {
            return json(['message' => '删除失败'], 500);
        }
       
        return json(['message' => '访问错误'], 400);     
    }
    
    
     
}
