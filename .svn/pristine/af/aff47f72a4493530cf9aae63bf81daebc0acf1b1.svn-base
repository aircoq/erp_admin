<?php

namespace app\customerservice\controller;

use think\Controller;
use think\Request;
use app\common\controller\Base;
use app\common\cache\Cache;
use app\common\model\MsgTemplate as MsgTemplateModel;
use app\common\service\Common;
use app\customerservice\service\MsgTemplateHelp;
use app\common\exception\JsonErrorException;
use app\common\service\Filter;
use app\common\traits\User;
use app\common\service\ChannelAccountConst;
use app\customerservice\filter\MsgTemplateChannelFilter;

/**
 * @module 客服管理
 * @title 回复模板
 */
class MsgTemplate extends Base
{
    use User;
    protected $msgTplService = null;
    protected function init()
    {
        if(is_null($this->msgTplService)){
            $this->msgTplService = new MsgTemplateHelp();
        }
    } 
    
    /**
     * @title 回复模板列表
     * @author tanbin
     * @method GET
     * @url /msg-tpl
     * @apiRelate app\customerservice\controller\MsgTemplate::getTypes
     * @apiRelate app\customerservice\controller\MsgTemplateGroup::index
//     * @apiRelate app\goods\controller\ChannelCategory::index
     * @apiFilter app\customerservice\filter\MsgTemplateChannelFilter
     */
    public function index()
    {   
        $request  = Request::instance();
        $page     = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 10);
        $params = $request->param();

        $channels = [];

        if (!$this->isAdmin()) {
            $object = new Filter(MsgTemplateChannelFilter::class,true);
            if ($object->filterIsEffective()) {
                $channels = $object->getFilterContent();
            }
        } else {
            $channels = [ChannelAccountConst::channel_ebay, ChannelAccountConst::channel_amazon, ChannelAccountConst::channel_aliExpress];
        }

        $params['channels'] = $channels;
        $result = $this->msgTplService->lists($params,$page,$pageSize);
         
        return json($result, 200);
    }

    /**
     * @title 渠道
     * @method GET
     * @url /msg-tpl/channels
     * @return \think\response\Json
     * @throws \think\Exception
     */
    public function channnels()
    {
        $result=[];
        $channelList = Cache::store('channel')->getChannel();
        $channels = [ChannelAccountConst::channel_ebay, ChannelAccountConst::channel_amazon, ChannelAccountConst::channel_aliExpress];
        foreach ($channelList as $key => $value) {
            if (in_array($value['id'], $channels)) {
                array_push($result, $value);
            }
        }
        return json($result, 200);
    }

    /**
     * @title 查看
     * @author tanbin
     * @method GET
     * @apiParam name:id type:int require:1 desc:ID
     * @url /msg-tpl/:id
     */
    public function read($id)
    {
        if (!is_numeric($id)) {
            return json(['message' => '参数错误'], 400);
        } 
        $result = $this->msgTplService->info($id);
        return json($result, 200);

    }
    

    /**
     * @title 新增
     * @author tanbin
     * @method POST
     * @apiParam name:template_no type:string require:1 desc:模板编号
     * @url /msg-tpl
     * @apiRelate app\customerservice\controller\MsgTemplate::getFieldDatas
     * @apiRelate app\customerservice\controller\MsgTemplateGroup::index
     * @apiRelate app\goods\controller\ChannelCategory::index
     */
    public function save(Request $request){    
        $data['template_no'] = $request->post('template_no', '');
        $data['template_name'] = $request->post('template_name', '');
        $data['channel_id'] = $request->post('channel_id', '');
        $data['template_type'] = $request->post('template_type', '');
        $data['template_group_id'] = $request->post('group_id', '');
        $data['remark'] = $request->post('remark', '');
        $data['template_content'] = $request->post('content', '');
        $data['create_time'] = $data['update_time'] = time();
        $userInfo = Common::getUserInfo($request);
        $data['create_id'] = $data['update_id'] = $userInfo['user_id'];
        
        $msgTemplateModel = new MsgTemplateModel();
        $validateTemplate = validate('MsgTemplate');
        if (!$validateTemplate->check($data)) {
            return json(['message' => $validateTemplate->getError()], 400);
        }
       
        $bool = $msgTemplateModel->allowField(true)->isUpdate(false)->save($data);
        $id = $msgTemplateModel->id;   
   
        //删除缓存
        Cache::handler()->del('cache:msgTemplate');
        if ($bool) {
            return json(['message' => '新增成功','id' => $id], 200);
        } else {
            return json(['message' => '新增失败'], 500);
        }

    }
    
    /**
     * @title 编辑
     * @author tanbin
     * @method GET
     * @apiParam name:id type:int require:1 desc:ID
     * @url /msg-tpl/:id/edit
     * @apiRelate app\customerservice\controller\MsgTemplate::getFieldDatas
     * @apiRelate app\customerservice\controller\MsgTemplateGroup::index
     * @apiRelate app\goods\controller\ChannelCategory::index
     */
    public function edit($id)
    {
        if (!is_numeric($id)) {
            return json(['message' => '参数错误'], 400);
        }
        $result = MsgTemplateModel::field('*')->where(['id' => $id])->find();
        $result = empty($result) ? [] : $result;
        
        if($result){
            $result['group_id'] = $result['template_group_id'];
            $result['content'] = $result['template_content'];
            unset($result['template_group_id']);
            unset($result['template_content']);
        }
        return json($result, 200);
    }
    
    /**
     * @title 更新
     * @author tanbin
     * @method PUT
     * @apiParam name:id type:int require:1 desc:ID
     * @url /msg-tpl/:id
     * @apiRelate app\customerservice\controller\MsgTemplate::getFieldDatas
     * @apiRelate app\customerservice\controller\MsgTemplateGroup::index
     * @apiRelate app\goods\controller\ChannelCategory::index
     */
    public function update(Request $request,$id)
    {
        if (!is_numeric($id)) {
            return json(['message' => '参数错误'], 400);
        }
        
        $msgTemplateModel = new MsgTemplateModel();        
        //判读是否存在        
        $msgTemplateModel->isHas($id);
        
        $params = $request->param();     
        $data['id']   = $id;
        $data['template_no'] = param($params, 'template_no') ;
        $data['template_name'] = param($params, 'template_name');
        $data['channel_id'] = param($params, 'channel_id');
        $data['template_type'] = param($params, 'template_type');       
        $data['template_group_id'] = param($params, 'group_id');
        $data['remark'] = param($params, 'remark');
        $data['template_content'] = param($params, 'content');
        $data['update_time'] = time();
        $userInfo = Common::getUserInfo($request);
        $data['update_id'] = $userInfo['user_id'];    
       
        //验证规则
        $validateMsgTemplate = validate('MsgTemplate');
        if (!$validateMsgTemplate->check($data)) {
            return json(['message' => $validateMsgTemplate->getError()], 500);
        }
        unset($data['template_no']);//不能更新模板编号
        
        $result = $msgTemplateModel->allowField(true)->save($data, ['id' => $id]);
        //删除缓存
        Cache::handler()->del('cache:msgTemplate');
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
     * @url /msg-tpl/:id
     */
    public function delete($id)
    {
        if (!is_numeric($id)) {
            return json(['message' => '参数错误'], 400);
        }
        
        $msgTemplateModel = new MsgTemplateModel();
        //判读是否存在
        $msgTemplateModel->isHas($id);
       
        $result = $msgTemplateModel->where(['id' => $id])->delete();        
        if ($result) {
            //删除缓存
            Cache::handler()->del('cache:msgTemplate');
            return json(['message' => '删除成功'], 200);            
        } else {
            return json(['message' => '删除失败'], 500);
        }
      
    }
    
 
    /**
     * @title 删除
     * @author tanbin
     * @method POST
     * @apiParam name:ids type:string require:1 desc:ID（1,2,3）
     * @url /msg-tpl/batch/delete
     *  
     */
    function batchDelete()
    {
        $request = Request::instance();
        $ids = $request->post('ids', 0);
        if (empty($ids)) {
            return json(['message' => '参数错误'], 400);
        }

        $datas = explode(',', $ids);
        $msgTemplateModel = new MsgTemplateModel();      
        $where['id'] = ['IN',$datas] ;
        $result = $msgTemplateModel->where($where)->delete();        
        if ($result) {
            //删除缓存
            Cache::handler()->del('cache:msgTemplate');
            return json(['message' => '删除成功'], 200);            
        } else {
            return json(['message' => '删除失败'], 500);
        }
    }
    
  
    /**
     * @title 获取模板分类
     * @author tanbin
     * @method GET
     * @url /msg-tpl/getTypes
     */
    public function getTypes(){
        $msgTemplateHelp = new MsgTemplateHelp();
        $data = $msgTemplateHelp->getTemplateType();
        
        $result = [
            'data' => $data
        ];
        return json($result, 200);
    }
    

    /**
     * @title 获取模板数据字段列表
     * @author tanbin
     * @method GET
     * @apiParam name:channel_id type:int require:1 desc:平台Id
     * @apiParam name:template_type type:int require:1 desc:模板类型（1-回复模板 ，2-评价模板）
     * @url /msg-tpl/getFields
     */
    public function getFieldDatas(){
        $request = Request::instance();       
        $params = $request->param();
       
        if(!isset($params['channel_id']) || !isset($params['template_type'])){
            return json(['message' => '参数错误'], 400);
        }
        
        $channel_id = $params['channel_id'];
        $tpl_type = $params['template_type'];

        $msgTemplateHelp = new MsgTemplateHelp();        
        $data = $msgTemplateHelp->getFieldDatas($channel_id,$tpl_type);

        $result = [
            'data' => $data
        ];
        return json($result, 200);
    }
   
    

    /**
     * @title 获取指定平台的所有模板列表
     * @author tanbin
     * @method GET
     * @apiParam name:channel_id type:int require:1 desc:平台Id
     * @apiParam name:template_type type:int require:1 desc:模板类型（1-回复模板 ，2-评价模板）
     * @apiParam name:list_type type:int require:1 desc:列表类型 (1-所有， 2-使用次数最多）
     * @apiParam name:group_id type:int require:0 desc:分组id
     * @apiParam name:page_size type:int require:0 desc:获取数量条数
     * @url /msg-tpl/getTemplates
     */
    function getTemplates(){
        $request = Request::instance();
        $channel_id = $request->get('channel_id','');//平台
        $template_type = $request->get('template_type','');//列表类型
        $type = $request->get('list_type',1);//列表类型 type=1 所有， type=2 常用使用次数最多
        $group_id = $request->get('group_id',0);//分组id
        $page_size = $request->get('page_size','');//列表类型

        $msgTemplateHelp = new MsgTemplateHelp();
        $data = $msgTemplateHelp->getTemplates($channel_id,$template_type,$group_id,$type,$page_size);
  
        $result = [
            'data' => $data
        ];
        return json($result, 200);
    }
    

    /**
     * @title 获取所有平台的所有模板
     * @author tanbin
     * @method GET
     * @apiParam name:template_type type:int require:1 desc:模板类型（1-回复模板 ，2-评价模板）
     * @url /msg-tpl/getAllTpls
     */
    function getAllTemplates(){
        $request = Request::instance(); 
        $template_type = $request->get('template_type','');//列表类型       
        $msgTemplateHelp = new MsgTemplateHelp();
        $data = $msgTemplateHelp->getAllTemplates($template_type);

        $result = [
            'data' => $data
        ];
        return json($result, 200);
    }
    
    
  
    
    /**
     * @title 获取模板内容
     * @author tanbin
     * @method GET
     * @apiParam name:template_id type:int desc:模板id
     * @apiParam name:template_no type:int desc:模板编号
     * @apiParam name:is_random type:int desc:随机获取:1
     * @apiParam name:search_id type:int desc:查找id
     * @apiParam name:search_type type:string desc:查找类型（msg、order、channel_order、evaluate）
     * @apiParam name:channel_id type:int desc:平台 id(1、2、3、4)
     * @remark search_type、search_id、channel_id 是组合参数，不传获取不转换字段内容；传了获取已经转换字段的内容<br />
     * @remark search_type 的值： msg -站内信 、邮件  | order -订单 | channel_order -订单  | evaluate -评价 <br />
     * @remark search_id 的值： 站内信id | 系统订单id | 评价id <br />
     * @remark channel_id 的值： ebay - 1  | amazon - 2 | wish - 3 | aliExpress - 4  （ 此字段传值 1、2、3、4...）<br />
     * @url /msg-tpl/content
     */
    function getTplContent(){
        $request = Request::instance();
        $params = $request->param(); 
        
        if((!param($params, 'template_id') && !param($params, 'template_no') && !param($params, 'is_random'))){
            throw new JsonErrorException('参数错误!');
        }
        
        if(param($params, 'search_id') && param($params, 'search_type') && param($params, 'channel_id')){
            $params['transform'] = 1;
        }
        
        $msgTemplateHelp = new MsgTemplateHelp();        
        $content = $msgTemplateHelp->matchTplContent($params);

        $result = [
            'data' => $content
        ];
        return json($result, 200);
    }
    
}
