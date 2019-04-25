<?php
namespace app\customerservice\controller;

use app\common\controller\Base;
use app\common\model\aliexpress\AliexpressSendMsg;
use app\common\model\aliexpress\AliexpressMsgRelation;
use app\common\model\aliexpress\AliexpressAccount;
use app\customerservice\service\AliexpressHelp;
use think\Exception;
use think\Request;
use app\common\cache\Cache;
use think\Db;

/**
 * @module 客服管理
 * @title 速卖通发件箱
 * @url /ali-outbox
 * @package app\customerservice\controller
 * @author Tom
 */
class AliexpressOutbox extends Base
{
    /**
     * @title 发件箱列表
     * @apiRelate app\index\controller\MemberShip::member
     * @apiRelate app\customerservice\controller\AliexpressMsg::getRelatedOrders
     * @apiFilter app\customerservice\filter\AliexpressAccountFilter
     * @return type
     */
    public function index()
    {
        try {
            $request = Request::instance();
            $page = $request->param('page', 1);
            $pageSize = $request->param('pageSize', 10);
            $sort = $request->param('sort', 1);
            $where = $this->getFilterCondition($request);
            $model = new AliexpressSendMsg();
            $field = 'id,content,buyer_id,buyer_name,sender_name,status,update_time,aliexpress_account_id,aliexpress_msg_relation_id';
            $order_str = $sort==1 ? 'update_time desc' : 'update_time asc';
            $count = $model->where($where)->count();
            $list = $model->field($field)->where($where)->order($order_str)->page($page,
                $pageSize)->select();
            
            if(!empty($list)){
                $send_type = AliexpressSendMsg::SEND_STATUS;
                foreach($list as $k=>$item){
                    $account = Cache::store('AliexpressAccount')->getTableRecord($item['aliexpress_account_id']);
                    $list[$k]['account'] = $account['code'];
                    $list[$k]['status'] = $send_type[$item['status']];
                    $list[$k]['msg_id'] = $item['aliexpress_msg_relation_id'];
                }
            }
            $result = [
                'data' => $list,
                'page' => $page,
                'pageSize' => $pageSize,
                'count' => $count,
            ];
            return json($result, 200);
        } catch (Exception $ex) {
            return json(['message'=>$ex->getMessage(),500]);
        }
    }
    
    /**
     * @title 消息明细
     * @param type $id
     * @return type
     */
    public function read($id)
    {
        try {
            $model = new AliexpressSendMsg();
            if(!$id){
                return json(['message' => '参数错误'], 400);
            }
            $where['id'] = $id;
            $field = 'id,content,buyer_id,buyer_name,status,update_time,img_path,aliexpress_account_id,aliexpress_msg_relation_id';
            $messages = $model->field($field)->where($where)->find();
            if (empty($messages)) {
                return json(['message' => '没有任何消息'], 400);
            }
            $account = Cache::store('AliexpressAccount')->getTableRecord($messages['aliexpress_account_id']);
            $messages['account'] = $account['code'];
            return json($messages, 200);
        } catch (Exception $ex) {
            return json(['message'=>'数据异常'],500);
        }
    }
    
    /**
     * @title 重发消息
     * @method post
     * @url :id(\d+)/resend
     * @param type $id
     * @return type
     */
    public function resend($id)
    {
        if(!$id){
            return json(['message'=>'参数错误',400]);
        }
        try {
            $msg = AliexpressSendMsg::where(['id'=>$id])->field('id,channel_id,content,buyer_id,img_path,msg_type,aliexpress_account_id')->find();
            $data = $msg->toArray();
            //获取当前Aliexpress账号信息
            $config = AliexpressAccount::getAliConfig($data['aliexpress_account_id']);
            if(empty($config)){
                return json(['message'=>'账号错误'],200);
            }
            $msg_type = AliexpressMsgRelation::MSG_TYPE;
            $data['msgSources'] = $msg_type[$data['msg_type']];
            $helpSever = new AliexpressHelp();
            $result = $helpSever->reSend($config,$data);
            if($result){
                return json(['message'=>'操作成功'],200);
            }
            return json(['message'=>'操作失败'],200);
        } catch (Exception $ex) {
            return json(['message'=>$ex->getMessage(),500]);
        }
    }
    
    /**
     * @node 删除消息
     * @param type $id
     * @return type
     */
    public function delete($id)
    {
        if(!$id){
            return json(['message'=>'参数错误'],400);
        }
        if(AliexpressSendMsg::destroy($id)){
            return json(['message'=>'删除成功'],200);
        }else{
            return json(['message'=>'删除失败'],200);
        }
    }
    
    /**
     * 获取列表筛选条件
     * @param Request $request
     * @return array
     */
    protected function getFilterCondition(Request $request)
    {
        $where = [];
        $params = $request->param();
        //状态
        if(isset($params['status'])){
            $where['status'] = $params['status'];
        }
        //时间
        if(!empty($params['start']) && !empty($params['end'])){
            $where['create_time']  =  ['BETWEEN', [strtotime($params['start']), strtotime($params['end'])]];
        }elseif (!empty($params['start'])) {
            $where['create_time']  =  ['EGT',strtotime($params['start'])];
        }elseif (!empty($params['end'])) {
            $where['create_time']  =  ['ELT',strtotime($params['end'])];
        }
        //发件人账号
        if(isset($params['customer_id'])&&$params['customer_id']){
            $where['sender_uid'] = $params['customer_id'];
        }
        //消息类别
        if(isset($params['msg_type'])&&$params['msg_type']){
            $where['msg_type'] = $params['msg_type'];
        }
        //关键字
        if(isset($params['filter_type']) && isset($params['filter_text']) && !empty($params['filter_text'])){
            switch ($params['filter_type'])
            {
                case 'order_id':
                    $aliexpress_msg_relation_id_arr = [];
                    if($params['filter_text']){
                        $amd_con = [
                            'message_type'=>2,
                            'type_id'=>$params['filter_text']
                        ];
                        if($amd_rows = Db::table('aliexpress_msg_detail')->where($amd_con)->field('aliexpress_msg_relation_id')->select()){
                            $aliexpress_msg_relation_id_arr = array_column($amd_rows, 'aliexpress_msg_relation_id');
                        }
                    }
                    if($aliexpress_msg_relation_id_arr){
                        $where['aliexpress_msg_relation_id'] = ['in',$aliexpress_msg_relation_id_arr];
                    }else{
                        $where['aliexpress_msg_relation_id'] = '0';
                    }
                    break;
                case 'buyer_id':
                    $where['buyer_id'] = ['like','%'.$params['filter_text'].'%'];
                    break;
                default :
                    break;
            }
        }
        return $where;
    }
}

