<?php
namespace app\customerservice\controller;

use think\Controller;
use think\Request;
use think\Db;
use think\Exception;
use app\common\service\Common as CommonService;
use app\common\controller\Base;
use app\common\cache\Cache;
use app\common\model\MsgRuleSet as MsgRuleSetModel;
use app\common\model\MsgRuleSetItem as MsgRuleSetItemModel;
use app\customerservice\service\MsgRuleHelp as MsgRuleHelpService;
use app\common\model\MsgRuleSet;
use app\api\controller\Post;
use app\customerservice\service\MsgRuleHelp;
use app\customerservice\queue\MsgReviewAutoSendQueue;
use app\common\service\UniqueQueuer;
use app\common\model\MsgEmailSend;
use think\Validate;
use app\common\service\Common;
use app\common\model\ChannelUserAccountMap;
use app\index\service\Role;

/**
 * @module 客服管理
 * @title 站内信/评价自动发送规则
 * Date: 2017/04/08
 */
class MsgRule extends Base
{

    /**
     * @title 自动发送规则列表
     * @author tanbin
     * @method GET
     * @url /msg-rule
     * @apiRelate app\customerservice\controller\MsgRule::getTriggerRules&getTriggerRuleStatistics
     * @apiRelate app\customerservice\controller\MsgTemplate::getAllTemplates
     * @apiRelate app\goods\controller\ChannelCategory::getPartialChannel
     * @apiFilter app\customerservice\filter\MsgRuleByChannelFilter
     */
    public function index()
    {   
        $request = Request::instance();
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 10);
        $params = $request->param();

        $where = $this->index_where($params);

//        $userInfo = Common::getUserInfo();
//        $user_id = $userInfo['user_id'];
//
//        if($user_id == 0 || (new Role())->isAdmin($user_id)){
//            if(empty($params['platform'])){
//                $where['platform'] = 1;
//            }else{
//                $where['platform'] = $params['platform'];
//            }
//        }else{
//            $channelUserAccountMap = new ChannelUserAccountMap();
//
//            $user_where['seller_id'] = array('eq',$user_id);
//            $user_where['customer_id'] = array('eq',$user_id);
//            $channel_ids = $channelUserAccountMap->whereOr($user_where)->field('channel_id')->group('channel_id')->select();
//            $channel_ids = array_column($channel_ids,'channel_id');
//
//            if(empty($channel_ids)){
//                $where['platform'] = 0;
//            }else{
//                if(in_array($params['platform'],$channel_ids)){
//                    $where['platform'] = $params['platform'];
//                }else{
//                    $where['platform'] = 0;
//                }
//            }
//        }


        $count = MsgRuleSetModel::where($where)->count();
        $field = 'id,title,sort,rule_type,trigger_rule,status,operator,create_time,platform, send_email_rule';
        $lists = MsgRuleSetModel::field($field)->where($where)->page($page, $pageSize)->order('sort,id')->select();
        
        $rule_list = array_merge(MsgRuleSetModel::$TRIGGER_RULE,MsgRuleSetModel::$TRIGGER_RULE_FEEDBACK);
        foreach($lists as $k=>$v){           
            $lists[$k]['trigger_rule_str'] = param($rule_list, $v['trigger_rule']);
            $lists[$k]['action'] = param(MsgRuleSetModel::$SEND_EMAIL_RULE, $v['send_email_rule']);
        }
        $result = [
            'data' => $lists,
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
        ];
        
        return json($result, 200);
    }

    /**
     * 显示指定的消息
     *
     * @param  int $id
     * @return \think\Response
     */
    /**
     * @disabled
     */
    public function read($id)
    {
       
        return json(['message' => '待开发'], 400);
    }
    

    /**
     * @title 新增
     * @author tanbin
     * @method POST
     * @url /msg-rule
     * @apiRelate app\customerservice\controller\MsgRule::getTriggerRules&getSendEmailRules
     * @apiRelate app\order\controller\Rule::resources
     */
    public function save(Request $request){  
        
        $ruleSet['title'] = $request->post('name', '');
        $ruleSet['trigger_rule'] = $request->post('trigger_rule', 0);
        $ruleSet['send_email_rule'] = $request->post('send_email_rule', 0);        
        $ruleSet['status'] = $request->post('status', 0);
        $ruleSet['delay_time_send'] = $request->post('delay_data', 0);
        $ruleSet['platform'] = $request->post('platform', '');

        $rules = $request->post('rules', 0);
        
        if (empty($rules)) {
            return json(['message' => '请选择一条规则条件'], 400);
        }
        
        $rules = json_decode($rules, true);
        if (empty($rules)) {
            return json(['message' => '请选择一条规则条件'], 400);
        }
        
        $msgRuleSetModel = new MsgRuleSetModel();
        $msgRuleSetItemModel = new MsgRuleSetItemModel();
        $validateRuleSet = validate('MsgRuleSet');
        if (!$validateRuleSet->check($ruleSet)) {
            return json(['message' => $validateRuleSet->getError()], 400);
        }
        
        if(substr($ruleSet['trigger_rule'], 0,1)=='E'){
            $ruleSet['rule_type'] = 1;
        }elseif(substr($ruleSet['trigger_rule'], 0,1)=='F'){
            $ruleSet['rule_type'] = 2;
        }
        
        //验证延迟发送时间规则
        $service = new MsgRuleHelp();
        $service->checkDelayTimeSend($ruleSet['delay_time_send']);

        //启动事务
        Db::startTrans();
        try {
            $ruleSet['create_time'] = time();
            $ruleSet['update_time'] = time();
            $ruleSet['sort'] = 9999;
            //查出是谁操作的
            $user = CommonService::getUserInfo($request);
            if(!empty($user)){
                $ruleSet['operator'] = $user['realname'];
                $ruleSet['operator_id'] = $user['user_id'];
            }
            $msgRuleSetModel->allowField(true)->isUpdate(false)->save($ruleSet);
            $rule_id = $msgRuleSetModel->id; 
            $ruleSetItem = [];
            foreach ($rules as $k => $v) {
                $ruleSetItem[$k]['rule_id'] = $rule_id;
                $ruleSetItem[$k]['create_time'] = time();
                $ruleSetItem[$k]['update_time'] = time();
                $ruleSetItem[$k]['rule_item_id'] = $v['item_id'];
                $ruleSetItem[$k]['param_value'] = json_encode($v['item_value']);
            }
           
            $msgRuleSetItemModel->allowField(true)->isUpdate(false)->saveAll($ruleSetItem);
            Db::commit();
            //删除缓存
            Cache::handler(true)->del('cache:triggerRuleCount');
            Cache::handler(true)->del('cache:MsgRuleItem');
            Cache::handler(true)->del('cache:MsgRuleSet');
            return json(['id' => $rule_id,'message'=>'新增成功'], 200);
        } catch (Exception $e) {
            Db::rollback();
            return json(['message' => '新增失败 '], 500);
        }
         
    }
    

    /**
     * @title 编辑
     * @author tanbin
     * @method GET
     * @apiParam name:id type:int require:1 desc:ID
     * @url /msg-rule/:id/edit
     * @apiRelate app\customerservice\controller\MsgRule::getTriggerRules&getSendEmailRules
     * @apiRelate app\order\controller\Rule::resources
     */
    public function edit($id)
    {
        if (!is_numeric($id) || empty($id)) {
            return json(['message' => '参数错误'], 400);
        }
        $ruleSetItemModel = new MsgRuleSetItemModel();
        $ruleSetModel = new MsgRuleSetModel();
        $ruleSetList = $ruleSetModel->field('id,title as name,status,trigger_rule,send_email_rule,delay_time_send as delay_data,platform')->where(['id' => $id])->find();
        if (empty($ruleSetList)) {
            return json(['message' => '不存在该规则'], 500);
        }
        $result = $ruleSetList;
        $ruleSetItemList = $ruleSetItemModel->where(['rule_id' => $id])->select();
        $rules = [];
        foreach ($ruleSetItemList as $k => $v) {
            $temp['item_id'] = $v['rule_item_id'];
            $item_value = json_decode($v['param_value'], true);
            $temp['choose'] = $item_value;
            array_push($rules, $temp);
        }
        $result['rules'] = $rules;
        $result['delay_data'] = json_decode($result['delay_data'],true);
        return json($result, 200);
    }
    
    
    /**
     * @title 更新
     * @author tanbin
     * @method PUT
     * @apiParam name:id type:int require:1 desc:ID
     * @url /msg-rule/:id
     * @apiRelate app\customerservice\controller\MsgRule::getTriggerRules&getSendEmailRules
     * @apiRelate app\order\controller\Rule::resources
     */
    public function update(Request $request,$id)
    {     
        if (!is_numeric($id) || empty($id)) {
            return json(['message' => '参数错误'], 400);
        }
        $ruleSet['id']   = $id;
        $ruleSet['title'] = $request->put('name', '');
        $ruleSet['trigger_rule'] = $request->put('trigger_rule', '');
        $ruleSet['send_email_rule'] = $request->put('send_email_rule', '');
        $ruleSet['status'] = $request->put('status', 0);
        $ruleSet['delay_time_send'] = $request->put('delay_data', 0);
       
                //验证延迟发送时间规则
        $service = new MsgRuleHelp();
        $service->checkDelayTimeSend($ruleSet['delay_time_send']); 
        
        $rules = $request->put('rules', 0);
        if (empty($rules)) {
            return json(['message' => '请选择一条规则条件'], 400);
        }
        
        $rules = json_decode($rules, true);
        if (empty($rules)) {
            Db::rollback();
            return json(['message' => '请选择一条规则条件'], 400);
        }
        
       $validateRuleSet = validate('MsgRuleSet');
         if (!$validateRuleSet->check($ruleSet)) {
            return json(['message' => $validateRuleSet->getError()], 400);
        }
        
        if(substr($ruleSet['trigger_rule'], 0,1)=='E'){
            $ruleSet['rule_type'] = 1;
        }elseif(substr($ruleSet['trigger_rule'], 0,1)=='F'){
            $ruleSet['rule_type'] = 2;
        }
       
        $msgRuleSetModel = new MsgRuleSetModel();
        $msgRuleSetItemModel = new MsgRuleSetItemModel();
        //启动事务
        Db::startTrans();
        try {
            $ruleSet['update_time'] = time();
            //查出是谁操作的
            $user = CommonService::getUserInfo($request);
            if(!empty($user)){
                $ruleSet['operator'] = $user['realname'];
                $ruleSet['operator_id'] = $user['user_id'];
            }
            $msgRuleSetModel->where(["id" => $id])->update($ruleSet);
            
            //删除原来的规则设置条件
            $msgRuleSetItemModel->where(['rule_id' => $id])->delete();
            $ruleSetItem = [];
            foreach ($rules as $k => $v) {
                $ruleSetItem[$k]['rule_id'] = $id;
//                $ruleSetItem[$k]['create_time'] = time();
                $ruleSetItem[$k]['update_time'] = time();
                $ruleSetItem[$k]['rule_item_id'] = $v['item_id'];
                $ruleSetItem[$k]['param_value'] = json_encode($v['item_value']);
            }
            $msgRuleSetItemModel->allowField(true)->isUpdate(false)->saveAll($ruleSetItem);
            Db::commit();
            //删除缓存
            Cache::handler(true)->del('cache:MsgRuleItem');
            Cache::handler(true)->del('cache:triggerRuleCount');
            Cache::handler(true)->del('cache:MsgRuleSet');
            return json(['message' => '修改成功'], 200);
        } catch (Exception $e) {
            Db::rollback();
            var_dump($e->getMessage());
            return json(['message' => '修改失败'], 500);
        }
         
    }     
    
    
    /**
     * @title 删除
     * @author tanbin
     * @method DELETE
     * @apiParam name:id type:int require:1 desc:ID
     * @url /msg-rule/:id
     */
    public function delete($id)
    {
    
        if (!is_numeric($id) || empty($id)) {
            return json(['message' => '参数错误'], 400);
        }
        $msgRuleSetModel = new MsgRuleSetModel();
        $msgRuleSetItemModel = new MsgRuleSetItemModel();
        if (!$msgRuleSetModel->isHas($id)) {
            return json(['message' => '该规则不存在'], 400);
        }
        
        //查看规则是否在启用中
        $info = $msgRuleSetModel->where(['id' => $id])->find();
        if($info['status'] == 0){
            return json(['message' => '请先停用该规则！'],500);
        }
        
        //启动事务
        Db::startTrans();
        try {        
            //删除规则条件
            $msgRuleSetItemModel->where(['rule_id' => $id])->delete();
            //删除规则
            $msgRuleSetModel->where(['id' => $id])->delete();
            Db::commit();
            //删除缓存
            Cache::handler(true)->del('cache:triggerRuleCount');
            Cache::handler(true)->del('cache:MsgRuleItem');
            Cache::handler(true)->del('cache:MsgRuleSet');
            return json(['message' => '删除成功'], 200);
        } catch (Exception $e) {
            Db::rollback();
            return json(['message' => '删除失败'], 500);
        }
    }

    
    /**
     * @title 更新状态（开启/停用）
     * @author tanbin
     * @method POST
     * @apiParam name:id type:int require:1 desc:ID
     * @url /msg-rule/batch/update
     */
    public function updateStatus(Request $request){  
        $request = Request::instance();
        $id = $request->post('id', 0);
        $status = $request->post('status', 0);
       
        if (empty($id) || !is_numeric($id)) {
            return json(['message' => 'id参数错误'], 400);
        }
        
        $MsgRuleSetModel = new MsgRuleSetModel();
        $MsgRuleSetModel->find($id);     

        $result = $MsgRuleSetModel->allowField(true)->save(['status'=>$status], ['id' => $id]);
        if ($result) {
            return json(['message' => '操作成功'], 200);
        } else {
            return json(['message' => '操作失败'], 500);
        }
    }
    
   
    /**
     * @title  排序
     * @author tanbin
     * @method POST
     * @apiParam name:sort type:json require:1 desc:排序json数据
     * @url /msg-rule/changeSort
     */
    public function changeSort()
    {
        $request = Request::instance();
        $params = $request->param();
        if (!isset($params['sort'])) {
            return json(['message' => '参数错误'], 400);
        }
        $arr[] = array('id'=>1,'sort'=>4);
        
        $sort = json_decode($params['sort'], true);    
        $msgRuleSetModel = new MsgRuleSetModel();
          
        //启动事务
        Db::startTrans();
        try {
            foreach ($sort as $k => $v) {
                $msgRuleSetModel->where(['id' => $v['id']])->update(['sort' => $v['sort']]);
            }
            Db::commit();
            return json(['message' => '保存成功'], 200);
        } catch (Exception $e) {
            Db::rollback();
            var_dump($e->getMessage());
            return json(['message' => '保存失败'], 500);
        }
    }
    

    /**
     * @title  统计每个触发时间下面的规则条数
     * @author tanbin
     * @method GET
     * @url /msg-rule/triggerStatistics
     */
    public function getTriggerRuleStatistics()
    {
        $result = Cache::store('rule')->getTriggerRuleCount();
        return json($result, 200);
    }
    

    /**
     * @title  触发规则条件列表
     * @author tanbin
     * @method GET
     * @url /msg-rule/triggerRules
     */
    public function getTriggerRules(Request $request)
    {
        $params = $request->param();
        $service = new MsgRuleHelpService();
        $result = $service->getTriggerRules($params['channel_id']);
        return json($result, 200);
    }
    
    
    /**
     * @title  发送邮规则条件列表
     * @author tanbin
     * @method GET
     * @url /msg-rule/emailRules
     */
    public function getSendEmailRules(Request $request)
    {
        $params = $request->param();
        $service = new MsgRuleHelpService();
        $result = $service->getSendEmailRules($params['channel_id'],$params['trigger_rule']);
        return json($result, 200);
    }

    /**
     * @title  平台列表
     * @method GET
     * @url /msg-rule/platform
     */
    public function getPlatform()
    {
        $service = new MsgRuleHelpService();
        $result = $service->getPlatform();
        return json($result, 200);
    }

    /**
     * @title 匹配测试
     * @method post
     * @url /msg-rule/triggerEventTest
     * @param Request $request
     * @return \think\response\Json
     * @throws Exception
     */
    public function triggerEventTest(Request $request)
    {
        $params = $request->param();
        if(!$event_name = param($params,'event_name','')){
            return json('event_name 不能为空！', 500);
        }
        if(!$channel_id = param($params,'channel_id')){
            return json('channel_id 不能为空！', 500);
        }
        if(!$channel_order_number = param($params, 'channel_order_number')){
            return json('channel_order_number 不能为空！', 500);
        }
        if(!$account_id = param($params,'account_id')){
            if($channel_id==4){
                if(!$aoo_row = Db::table('aliexpress_online_order')->field('account_id')->where('order_id',$channel_order_number)->find()){
                    return json('平台订单号:' . $channel_order_number.',订单数据不存在' , 500);
                }
                $account_id = $aoo_row['account_id'];
            }
        }
        if(!$account_id){
            return json('account_id 不能为空！', 500);
        }
        
        $msg_rule_set_id = param($params,'msg_rule_set_id', 0 );
        
//        $params = [
//            'channel_order_number'=>'702444585321984',
//            'channel_id'=>'4',
//            'account_id'=>'1',
//            'event_name'=>'E2',
//            'match_data'=>[
//                'source_site'=>'Belgium_Dutch',//订单站点
//                'buyer_selected_logistics_arr'=>['DHL_ES','China Post Registered Air Mail'],//买家选择的运输类型--order.buyer_selected_logistics（逗号拆分）
//                'child_order'=>false,//是否是子订单--
//                'country_code'=>'CN',//订单目的国家代码--order.country_code
//                'country_zone_code'=>'Asia',//订单目的地--根据order.country_code 查 country.zone_code字段
//                'warehouse_id_arr'=>['70','50'],//发货仓库--Cache::store('Goods')->getGoodsInfo($good_id).warehouse_id
//                'shipping_id_arr'=>[2032,454],//实际发货邮寄方式order_package.shipping_id
//                'tag_arr'=>['易碎','衣服'],//订单货品属性包含(数组)----Cache::store('Goods')->getGoodsInfo($good_id).tags
//                'category_id_arr'=>[33,34,35,36,37,38,39,40,41,42],//订单至少存在一件货品属于(数组)--Cache::store('Goods')->getGoodsInfo($good_id).category_id
//                'sku_arr'=>["LA00001ZZ","LA00003ZZ","LA00002ZZ"],//订单货品包含(数组)--Cache::store('goods')->getSkuInfo($sku_id).sku
//            ],
//        ];

        if(param($params,'show_log')==1){
            define('___SHOW_LOG___',true);
        }
        if(param($params,'write_log')==1){
            define('___WRITE_LOG___',true);
        }
        $service = new MsgRuleHelpService();
        $order_data = [
            'channel_id'=>$channel_id,//Y 渠道id
            'account_id'=>$account_id,//Y 账号id
            'channel_order_number'=>$channel_order_number,//Y 渠道订单号
            'msg_rule_set_id'=>$msg_rule_set_id,//N 自动发信规则设置表id
            'channel_order'=>[],//N 渠道订单数据
        ];
        $result = $service->triggerEvent($event_name, $order_data);
        return json($result, 200);
    }
    
    /**
     * @title 加入站内信/评价自动发送列队
     * @method post
     * @url /msg-rule/msgReviewAutoSendQueueTest
     * @param Request $request
     * @return \think\response\Json
     * @throws Exception
     */
    public function msgReviewAutoSendQueueTest(Request $request)
    {
        $params = $request->param();
        if(!$event_name = param($params,'event_name','')){
            return json('event_name 不能为空！', 500);
        }
        if(!$channel_id = param($params,'channel_id')){
            return json('channel_id 不能为空！', 500);
        }
        if(!$channel_order_number = param($params, 'channel_order_number')){
            return json('channel_order_number 不能为空！', 500);
        }
        if(!$account_id = param($params,'account_id')){
            if($channel_id==4){
                if(!$aoo_row = Db::table('aliexpress_online_order')->field('account_id')->where('order_id',$channel_order_number)->find()){
                    return json('平台订单号:' . $channel_order_number.',订单数据不存在' , 500);
                }
                $account_id = $aoo_row['account_id'];
            }
        }
        if(!$account_id){
            return json('account_id 不能为空！', 500);
        }
        if(!$msg_rule_set_id = param($params,'msg_rule_set_id')){
            return json('send_email_rule 不能为空！', 500);
        }
        if(!$send_email_rule = param($params,'send_email_rule')){
            return json('send_email_rule 不能为空！', 500);
        }
        if(!$template_id = param($params,'template_id')){
            return json('template_id 不能为空！', 500);
        }
        if(!$msg_email_send_id = param($params,'msg_email_send_id')){
            return json('msg_email_send_id 不能为空！', 500);
        }
        $sendData = [
            'channel_id'=>$channel_id,//Y 渠道id
            'account_id'=>$account_id,//Y 账号id
            'channel_order_number'=>$channel_order_number,//Y 渠道订单号
            'send_email_rule'=>$send_email_rule,//Y 发送邮箱方式
            'template_id'=>$template_id,//Y 模板id
            'event_name'=>$event_name,//N 事件名称
            'msg_rule_set_id'=>$msg_rule_set_id,//N 规则设置表id
            'msg_email_send_id'=>$msg_email_send_id,
        ];
        (new UniqueQueuer(MsgReviewAutoSendQueue::class))->push(json_encode($sendData));
        
//         $obj = new \app\customerservice\queue\MsgReviewAutoSendQueue();
//         $obj->setParams(json_encode($sendData));
//         $obj->execute();
        return json('操作成功', 200);
    }
    
    /**
     * @title 手动加入站内信队列
     * @method post
     * @url /msg-rule/addSendMsg
     * @param Request $request
     * @return \think\response\Json
     * @throws Exception
     */
    public function addSendMsg(Request $request)
    {
        $params = $request->param();
        if(!$template_id = param($params,'template_id','')){
            return json('template_id 不能为空！', 500);
        }
        $page = param($params,'page',1);
        $con = [
//             'biz_type'=>'AE_PRE_SALE',
            'gmt_pay_time'=>0,
            'order_status'=>1,
            'gmt_create'=>['>', strtotime('2018-11-11 00:00:00')]
        ];
        $field = 'id,account_id,order_id';
        $pageSize = 1000;
        $order = 'id asc';
        $count = 0;
        $send_count = 0;
        while ($rows = Db::table('aliexpress_online_order')->where($con)->field($field)->page($page,$pageSize)->order($order)->select()){
            foreach ($rows as $row){
                $data = [
                    'channel_id'=>4,//Y 渠道id
                    'account_id'=>$row['account_id'],//Y 账号id
                    'channel_order_number'=>$row['order_id'],//Y 渠道订单号
                    'send_email_rule'=>4,//Y 发送邮箱方式
                    'template_id'=>$template_id,//Y 模板id
                    'cron_time'=>time(),// 预计发送时间
                    'create_time'=>time(),// 插入时间
                    'delay_second'=>2,// 延迟发送秒数
                ];
                //防止重复触发
                $has_con = [
                    'channel_id'=>$data['channel_id'],
                    'account_id'=>$data['account_id'],
                    'channel_order_number'=>$data['channel_order_number'],
                    'delay_second'=>$data['delay_second'],
                    'template_id'=>$data['template_id'],
                ];
                if(!MsgEmailSend::where($has_con)->field('id')->find()){
                    (new MsgEmailSend())->save($data);
                    $send_count++;
                }
//                 $sendData = [
//                     'channel_id'=>4,//Y 渠道id
//                     'account_id'=>$row['account_id'],//Y 账号id
//                     'channel_order_number'=>$row['order_id'],//Y 渠道订单号
//                     'send_email_rule'=>4,//Y 发送邮箱方式
//                     'template_id'=>$template_id,//Y 模板id
//                 ];
//                 (new UniqueQueuer(MsgReviewAutoSendQueue::class))->push(json_encode($sendData));

                $count++;
            }
            if(count($rows) < $pageSize){
                break;
            }
            $page++;
        }
        
        return json("共查到{$send_count}个订单数据，加入{$count}个发信队列", 200);
    }

    /**
     * @title 自动发送规则列表条件
     * @method get
     * @url where
     * @param $params
     * @return mixed
     */
    public function index_where($params)
    {
        $where = [];

        //启用状态
        if (isset($params['status']) && ($params['status'] === '0' || $params['status'] === '1')) {
            $where['status'] = ['EQ', $params['status']];
        }
        //触发规则条件
        if (!empty(param($params, 'trigger_rule'))) {
            $where['trigger_rule'] = ['EQ', $params['trigger_rule']];
        }

        //平台
        if (!empty(param($params, 'platform'))) {
            $where['platform'] = $params['platform'];
        }

        //规则名称
        if (!empty(param($params, 'rule_name'))) {
            $rule_name = trim($params['rule_name']);
            $rule_name = '%' . $rule_name . '%';
            $where['title'] = ['like', $rule_name];
        }

        $b_time = !empty(param($params, 'start_date')) ? $params['start_date'] . ' 00:00:00' : '';
        $e_time = !empty(param($params, 'end_date')) ? $params['end_date'] . ' 23:59:59' : '';

        if ($b_time) {
            if (Validate::dateFormat($b_time, 'Y-m-d H:i:s')) {
                $b_time = strtotime($b_time);
            } else {
                throw new Exception('起始日期格式错误(格式如:2017-01-01)', 400);
            }
        }

        if ($e_time) {
            if (Validate::dateFormat($e_time, 'Y-m-d H:i:s')) {
                $e_time = strtotime($e_time);
            } else {
                throw new Exception('截止日期格式错误(格式如:2017-01-01)', 400);
            }
        }

        if ($b_time && $e_time) {
            $where['create_time'] = ['BETWEEN', [$b_time, $e_time]];
        } elseif ($b_time) {
            $where['create_time'] = ['EGT', $b_time];
        } elseif ($e_time) {
            $where['create_time'] = ['ELT', $e_time];
        }
        return $where;
    }

    /**
     * @title 设置回复内容md5值(临时)
     * @method post
     * @url /msg-rule/content_md5
     */
    public function set_content_md5()
    {
        $msgRuleHelp = new MsgRuleHelp();
        $msgRuleHelp->set_content_md5();
    }

    /**
     * @title 设置去重字段only_key md5值（临时）
     * @method post
     * @url /msg-rule/only_key_md5
     */
    public function set_only_key_md5()
    {
        $msgRuleHelp = new MsgRuleHelp();
        $msgRuleHelp->set_only_key_md5();
    }

}

