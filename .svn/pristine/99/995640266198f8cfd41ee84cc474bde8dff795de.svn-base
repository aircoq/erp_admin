<?php
namespace app\customerservice\service\aliexpress;

use app\common\model\Order;
use app\common\service\UniqueQueuer;
use app\customerservice\exception\EvaluateException;
//use service\aliexpress\AliexpressApi;
use app\customerservice\queue\AliEvaluateReplyQueue;
use service\alinew\AliexpressApi;
use app\common\service\Common;
use think\Request;
use think\Exception;
use erp\AbsServer;
use app\common\cache\Cache;
use app\customerservice\service\MsgTemplateHelp;
use app\common\model\aliexpress\AliexpressAccount;
use app\common\model\aliexpress\AliexpressEvaluate;
use app\common\model\MsgTemplate;
use app\order\service\OrderService;

class AliEvaluateHelp extends AbsServer
{
    /**
     * 获取评价列表
     * @param type $page
     * @param type $pageSize
     * @param type $where
     * @param type $order_str
     * @return type
     */
    public function getList($page,$pageSize,$params)
    {
        $where = $this->getWhere($params);
        $order_str = 'order_pay_time desc';
        /*if(isset($params['sort'])&&$params['sort']){
            $sort = $params['sort']==1?'desc':'asc';
            $order_str = 'pay_money '.$sort;
        }*/
        $field = [
            'e.id','e.aliexpress_account_id','e.order_id','e.evaluate_content','e.evaluate_time','e.append_content','e.status','e.order_pay_time','e.pay_amount'
        ];
        $model = new AliexpressEvaluate();
        $count = $model->alias('e')->where($where)->count();
        $list = $model->alias('e')->field($field)->where($where)->order($order_str)
            ->page($page, $pageSize)->select();
        $data = [];
        if(!empty($list)){
            $orderServer = $this->invokeServer(OrderService::class);
            foreach($list as $k=>$item){
                $order_sale_info = $orderServer->orderSaleInfo($item['order_id']);
                $account = Cache::store('AliexpressAccount')->getTableRecord($item['aliexpress_account_id']);
                $pay_amount = json_decode($item['pay_amount'],true);
                $data[$k]['id'] = $item['id'];
                $data[$k]['account'] = $account['code'];
                $data[$k]['buyer_account'] = (empty($item['buyer_login_id'])||$item['buyer_login_id']=='null')?'':$item['buyer_login_id'];
                $data[$k]['order_no'] = $item['order_id'];
                $data[$k]['pay_time'] = $item['order_pay_time'];
                $data[$k]['pay_amount'] = $pay_amount['amount'].' '.$pay_amount['currencyCode'];
                $data[$k]['content'] = $item['evaluate_content']?$item['evaluate_content']:'';
                $data[$k]['time'] = $item['evaluate_time'];
                $data[$k]['order_no'] = $item['order_id'];
                $data[$k]['append'] = $item['append_content']?$item['append_content']:'';
                $data[$k]['has_refund']  = $order_sale_info['refund'];
                $data[$k]['has_return']  = $order_sale_info['return'];
                $data[$k]['has_reissue'] = $order_sale_info['redeliver'];
                $data[$k]['status']     = $item['status'];
                $data[$k]['status_name'] = AliexpressEvaluate::EVAULATE_STATUS[$item['status']];
            }
        }
        $result = [
                'data' => $data,
                'page' => $page,
                'pageSize' => $pageSize,
                'count' => $count,
            ];
        return $result;
    }
    
    /**
     * 回评
     * @param type $evaluateModel
     * @param type $score
     * @param type $content
     * @return boolean
     */
    public function evaluate($evaluateModel,$score,$content)
    {
        try {
            if($evaluateModel['status']==AliexpressEvaluate::FINSH_EVALUATE){
                return ['status'=>0,'msg'=>'已经评价过了，不能重复评价'];
            }
            //获取当前Aliexpress账号信息
            $config = AliexpressAccount::getAliConfig($evaluateModel['aliexpress_account_id']);
            $config && $config['token'] = $config['accessToken'];
            $evaluateServer =   AliexpressApi::instance($config)->loader('Evaluate');
            $response        =   $evaluateServer->saveSellerFeedback($evaluateModel['order_id'],$content,$score);
            $response = $this->dealResponse($response);
            if($response['is_success']){
                $request = Request::instance();
                $user_info = Common::getUserInfo($request);
                $evaluateModel->save(['evaluate_content'=>$content,'score'=>$score,'handler_uid'=>$user_info['user_id'],'status'=>AliexpressEvaluate::FINSH_EVALUATE,'evaluate_time'=>  time()]);
                return ['status'=>1,'msg'=>'评价成功'];
            }else{
                return ['status'=>0,'msg'=>$response['error_message'] ?? '评价失败'];
            }
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
        
        
    }
    
    /**
     * 批量回评
     * @param type $ids
     * @param type $score
     * @param type $content
     * @throws Exception
     */
    public function batchEvaluate($score,$content,$ids,$isAll=false)
    {
        try {
            $where['status'] = AliexpressEvaluate::WAIT_EVALUATE;
            if(!$isAll){
                $where['id'] = ['in',  explode(',', $ids)];
            }
            $evaluateModel = new AliexpressEvaluate();
            $idsArr = $evaluateModel->where($where)->order('gmt_create asc')->column('id');
            if($idsArr){
                foreach($idsArr as $id){
                    $params = [
                        'evaluate_id' => $id,
                        'score' => $score,
                        'content' => $content
                    ];
                    (new UniqueQueuer(AliEvaluateReplyQueue::class))->push(json_encode($params));
                }
            }
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }   
    }
    
    /**
     * 追加评论
     * @param type $id
     * @param type $content
     * @return boolean
     * @throws Exception
     */
    public function appendEvaluate($id,$content)
    {
        $evaluateModel = new AliexpressEvaluate();
        $evaluate_info = $evaluateModel->where(['id'=>$id])->find();
        if(empty($evaluate_info)){
            throw new Exception('未找到相关信息');
        }
        if($evaluate_info['append_content']){
            throw new Exception('不能再追加评论了');
        }
        $config         = AliexpressAccount::getAliConfig($evaluate_info['aliexpress_account_id']);
        $config && $config['token'] = $config['accessToken'];
        $evaluateServer =   AliexpressApi::instance($config)->loader('Evaluate');
        $response        =   $evaluateServer->evaluationReply($evaluate_info['order_id'], $evaluate_info['parent_order_id'], $content);
        $response = $this->dealResponse($response);
        if(param($response, 'target')){
            $request    = Request::instance();
            $user_info  = Common::getUserInfo($request);
            $evaluate_info->save(['append_content'=>$content,'append_uid'=>$user_info['user_id'],'append_time'=>time()]);
            return true;
        }else{
            return false;
        }
    }
    
    /**
     * 获取评价模板内容
     * @param type $orderId
     * @param type $tmpId
     * @param type $request
     * @return type
     */
    public function getEvaluateTmpContent($orderId,$tmpId,$request,$isRandom)
    {
        if($isRandom){
            $tmp = MsgTemplate::where(['template_type'=>2,'channel_id'=>4])->order('rand()')->field('id')->find();
            $tmpId = $tmp['id'];
        }
        $tmpServer = $this->invokeServer(MsgTemplateHelp::class);
        $data = [];
        $content = $tmpServer->getTplFieldContent($tmpId,$data);
        return $content;
    }
    
    /**
     * 获取评价明细
     * @param type $id
     * @return type
     */
    public function getEvaluateDetail($id)
    {
        $model = new AliexpressEvaluate();
        $result = $model->where(['id'=>$id])->field('id,evaluate_content,score,status,order_id,aliexpress_account_id')->find();
        return $result;
    }

    /**
     * 获取订单买家评价类型（中差好评）
     *
     * @param string $order_id
     * @param string $channel_account_id
     * @return mixed
     */
    function getOrderCommentType($order_id = '', $channel_account_id = '')
    {
        $result = AliexpressEvaluate::field('evaluate_content,buyer_evaluation,status,buyer_feedback')->where([
            'order_id' => $order_id,
            'aliexpress_account_id' => $channel_account_id
        ])->find();
        if(empty($result)){
            $result['buyer_evaluation']='0';//买家未评价
            $result['buyer_feedback']='';//买家评价内容
            $result['evaluate_content']='';//卖家评价内容
            $result['status']=0;//卖家等待回评

        }
        return $result;
    }

    /**
     * 获取各状态数量
     * @return type
     * @throws Exception
     */
    public function getEvaluateCount()
    {
        try {
            $data = AliexpressEvaluate::EVAULATE_STATUS;
            array_walk($data, function(&$value,$key){
                $count = AliexpressEvaluate::getCountByStatus($key);
                $value = ['id'=>$key,'name'=>$value,'count'=>$count];
            });
            array_unshift($data, ['id'=>-1,'name'=>'全部','count'=> array_sum(array_column($data, 'count'))]);
            return $data;
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    /**
     * 系统订单回评
     * @param $params
     * @return array
     */
    public function evaluateToOrder($params)
    {
        $orderModel = new Order();
        $order = $orderModel->where(['id'=>$params['order_id'],'channel_id'=>4])->field('buyer_id,channel_account_id,channel_order_number')->find();
        if(empty($order)){
            throw new EvaluateException('订单不存在');
        }
        $account = Cache::store('AliexpressAccount')->getTableRecord($order['channel_account_id']);
        $config = [
            'id'            =>  $account['id'],
            'client_id'     =>  $account['client_id'],
            'client_secret' =>  $account['client_secret'],
            'accessToken'   =>  $account['access_token'],
            'refreshtoken'  =>  $account['refresh_token'],
        ];
        $evaluateServer =   AliexpressApi::instance($config)->loader('Evaluate');
        $reponse        =   $evaluateServer->saveSellerFeedback($order['channel_order_number'],$params['content'],$params['score']);
        //获取当前操作用户
        $userInfo       =   Common::getUserInfo();
        if($reponse['status']){
            $msg = '[评价成功] - '.$params['score'].'星 <'.$params['content'].'> ';
            $data = ['status'=>true,'msg'=>'评价成功'];
        }else{
            $msg = '[评价失败] - '.$params['score'].'星 <'.$params['content'].'> ';
            $data = ['status'=>false,'msg'=>isset($reponse['msg'])?$reponse['msg']:'评价失败'];
        }
        Common::addOrderLog($params['order_id'],$msg,$userInfo['realname'],$order['status'],$userInfo['user_id']);
        return $data;
    }
    
    public function getEvaluateByStatus($status='',$page=1,$pageSize='50')
    {
        if(!$status){
            $status = AliexpressEvaluate::PADDING_EVALUATE;
        }
        $list = AliexpressEvaluate::where(['status'=>$status])->page($page,$pageSize)->select();
        return $list;
    }
    private function dealResponse($data)
    {
        //已经报错了,抛出异常信息
        if (isset($data->error_response) && $data->error_response) {
            throw new Exception($data->sub_msg, $data->code);
        }
        //如果没有result
        if (!isset($data->result)) {
            throw new Exception(json_encode($data));
        }
        return json_decode($data->result, true);
    }

    public function getEvaluateScoreCount()
    {
        try {
            $data = AliexpressEvaluate::SCORE_LABEL;
            array_walk($data, function(&$value){
                $count = $this->count($value['condition']);
                $value['count'] = $count;unset($value['condition']);
            });
            array_unshift($data, ['id'=>0,'name'=>'全部','count'=> array_sum(array_column($data, 'count'))]);
            return $data;
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    public function count($where)
    {
        return AliexpressEvaluate::where($where)->count();
    }

    public function getWhere($params, $needLabel = 1)
    {
        $where = [];
        if(param($params, 'order_no')){
            $where['e.order_id'] = ['like','%'.$params['order_no'].'%'];
        }
        if(isset($params['status']) && $params['status'] >= 0){
            $where['e.status'] = intval($params['status']);
        }
        if(param($params, 'account_id')){
            $where['e.aliexpress_account_id'] = $params['account_id'];
        }
        if(param($params, 'start_time') && param($params, 'end_time')){
            $where['e.order_pay_time'] = ['between', [strtotime($params['start_time'].' 00:00:00'),strtotime($params['end_time'].' 23:59:59')]];
        }else if(param($params, 'start_time')){
            $where['e.order_pay_time'] = ['>=', strtotime($params['start_time'].' 00:00:00')];
        }else if(param($params, 'end_time')){
            $where['e.order_pay_time'] = ['<=', strtotime($params['end_time'].' 23:59:59')];
        }
        if($needLabel){
            if(param($params, 'type_id')){
                $labels = AliexpressEvaluate::SCORE_LABEL;
                if(isset($labels[$params['type_id']])){
                    $where = array_merge($where, $labels[$params['type_id']]['condition']);
                }
            }
        }
        return $where;
    }
}

