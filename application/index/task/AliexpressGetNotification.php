<?php
namespace app\index\task;

use app\index\service\AbsTasker;
use app\common\exception\TaskException;
use think\Exception;
use app\common\cache\Cache;
use app\common\service\UniqueQueuer;
use \service\alinew\AliexpressApi;
use app\common\model\aliexpress\AliexpressMsgRelation;
use app\customerservice\service\AliexpressHelp;
use think\Db;
use app\index\service\AliexpressAccountService;
use app\common\model\aliexpress\AliexpressMsgDetail;
use app\customerservice\queue\AliExpressMsgQueueNew;
use app\order\queue\AliExpressOrderOneQueue;
use app\order\service\OrderHelp;
use app\common\model\Order;
use app\order\task\AliexpressOrder;
use app\common\model\aliexpress\AliexpressOnlineOrder;

class AliexpressGetNotification extends AbsTasker
{
    //任务redis键前缀
    private $task_key_prefix = 'task:aliexpress:notification:';
    //任务过期时间（秒）
    private $task_expired_time = 120;
    //日志自增索引
    private $log_index = 0;
    
    //消费多条消息参数
    private  $group_name = 'message';//消息推送分组组名
    private  $quantity = 100;//每次消费的数量
    
    public function getCreator() {
        return 'wangwei';
    }

    public function getDesc() {
        return '速卖通获取消息推送';
    }

    public function getName() {
        return '速卖通获取消息推送';
    }

    public function getParamRule() {
        return [
            'group_name'=>'require|select:' . join(',', $this->getGroupNameArr())
        ];
    }
    
    /**
     * @desc 返回所有可以用的消息分组 
     * @author wangwei
     * @date 2018-10-5 15:15:16
     * @return array
     */
    private function getGroupNameArr(){
        $group_name_arr = array_keys(AliexpressAccountService::$topicListMap);
        $group_name_arr[] = 'default';
        return $group_name_arr;
    }

    /**
     * 执行任务
     * {@inheritDoc}
     * @see \app\index\service\AbsTasker::execute()
     */
    public function execute()
    {
        
        try {
            //运行检查
            if(!$this->runCheck()){
                $this->showLog('not need run');
                return false;
            }
            
            //获取运行参数 
            $params = $this->getRunParams();
            
            //获取账号信息
            if(!$config = $this->getConfig($params['id'])){
                return false;
            }
            
            //消费多条消息
            $this->messagesConsume($config);
            
            //运行结束
            $this->runEnd();
            
        } catch (Exception $ex) {
            
            //运行结束
            $this->runEnd();
            
            throw new TaskException($ex->getMessage());
        }
    }
    
    /**
     * @desc 消费多条消息
     * @param array $config 配置信息
     * @param array $params 运行时的参数信息
     * @author wangwei
     * @date 2018-9-21 16:11:45
     */
    private function messagesConsume($config)
    {
        $api = AliexpressApi::instance($config)->loader('MessageNotification');
        $api instanceof \service\alinew\operation\MessageNotification;
        
        /*
         * 1、调用接口获取消息
         */
        $result = $api->messagesConsume($this->group_name,$this->quantity);
        $result = $this->dealResponse($result);
        
        $this->showLog($result);//日志输出
        
        if (isset($result['msg']) && $result['msg']) {
            throw new Exception($result['msg'], $result['code']);
        }
        
        if(isset($result['messages']) && !empty($result['messages'])){
            $tmc_message_list = $result['messages']['tmc_message'];
            
            //处理成功的通知消息id
            $success_message_id_arr = [];
            
            /*
             * 2、分发处理
             */
            foreach ($tmc_message_list as $tmc_message){
                //消息id
                $msg_id = $tmc_message['id'];
                
                //检查消息
                $cmRe = $this->checkMessage($tmc_message);
                if(!$cmRe['ask']){
                    $this->showLog("{$msg_id}=>checkMessage Error:{$cmRe['message']}");//日志输出
                    continue;
                }
                
                $this->updateRunTime();//更新运行时间
                
                switch ($tmc_message['topic']) {
                    case 'aliexpress_message_Pushnewmsg'://站内信消息
                        if($this->message_Pushnewmsg_process($cmRe['config'], $msg_id, $cmRe['msg_content'])){
                            $success_message_id_arr[] = $msg_id;
                        }
                        break;
                    case 'aliexpress_order_Finish'://交易成功
                        $success_message_id_arr[] = $msg_id;
                        break;
                    case 'aliexpress_order_FundProcessing'://资金处理中
                        $success_message_id_arr[] = $msg_id;
                        break;
                    case 'aliexpress_order_InCancel'://取消订单中
                        $success_message_id_arr[] = $msg_id;
                        if($this->aliexpress_order_InCancel_process($cmRe['config'], $cmRe['msg_content'])){
                            $success_message_id_arr[] = $msg_id;
                        }
                        break;
                    case 'aliexpress_order_WaitBuyerAcceptGoods'://等待买家收货
                        $success_message_id_arr[] = $msg_id;
                        if($this->aliexpress_order_WaitBuyerAcceptGoods_process($cmRe['config'], $cmRe['msg_content'])){
                            $success_message_id_arr[] = $msg_id;
                        }
                        break;
                    case 'aliexpress_order_SellerPartSendGoods'://等待部分发货
                        $success_message_id_arr[] = $msg_id;
                        if($this->aliexpress_order_SellerPartSendGoods_process($cmRe['config'], $cmRe['msg_content'])){
                            $success_message_id_arr[] = $msg_id;
                        }
                        break;
                    case 'aliexpress_order_WaitSellerSendGoods'://等待卖家发货
                        $success_message_id_arr[] = $msg_id;
                        if($this->aliexpress_order_WaitSellerSendGoods_process($cmRe['config'], $cmRe['msg_content'])){
                            $success_message_id_arr[] = $msg_id;
                        }
                        break;
                    case 'aliexpress_order_WaitGroupSuccess'://等待成团
                        $success_message_id_arr[] = $msg_id;
                        break;
                    case 'aliexpress_order_WaitSellerExamineMoney'://待卖家验款
                        $success_message_id_arr[] = $msg_id;
                        break;
                    case 'aliexpress_order_RiskControl'://风控24小时
                        $success_message_id_arr[] = $msg_id;
                        break;
                    case 'aliexpress_order_PlaceOrderSuccess'://下单成功
                        $success_message_id_arr[] = $msg_id;
                        if($this->aliexpress_order_PlaceOrderSuccess_process($cmRe['config'], $cmRe['msg_content'])){
                            $success_message_id_arr[] = $msg_id;
                        }
                        break;
                    default:
                        break;
                }
            }
            
            $this->updateRunTime();//更新运行时间
            
            /*
             * 3、确认消费消息的状态
             */
            $this->showLog('messagesConfirm=>' . json_encode($success_message_id_arr));//日志输出
            
            if(!empty($success_message_id_arr)){
                $this->messagesConfirm($this->group_name, $success_message_id_arr, $api);//确认收到消息
            }

        }
        
        return true;
    }

    /**
     * @desc 处理-订单取消中-通知
     * @author wangwei
     * @date 2018-10-4 16:33:15
     * @param array $config
     * @param int $msg_id
     * @param array $msg_content
     */
    private function aliexpress_order_InCancel_process($config, $msg_content)
    {
        $return = false;
        
        try {
            
            /*
             * 查询系统订单，如果系统订单存在，标记为人工审核；如果系统订单不存在，更新平台订单
             */
            $order_where = [
                'channel_account'=> ChannelAccountConst::channel_aliExpress * 10000 + $config['id'],
                'channel_order_number'=>$msg_content['order_id']
            ];
            $ali_order_where = [
                'account_id'=>$config['id'],
                'order_id'=>$msg_content['order_id']
            ];
            if($order = Order::field('id')->where($order_where)->find()){
                
                //标记为人工审核
                (new OrderHelp())->signYouReviewThe($order['id'],'收到速卖通消息通知，订单取消中');
                
            }else if(AliexpressOnlineOrder::field('id')->where($ali_order_where)->find()){
                
                //插入列队抓取单个订单，更新平台订单数据
                $account_id = $config['id'];
                $order_id = $msg_content['order_id'];
                (new UniqueQueuer(AliExpressOrderOneQueue::class))->push($account_id. '|'. $order_id);
                
            }
            
            $return = true;
            
        } catch (Exception $e) {
            
        }
        
        return $return;
    }
    
    /**
     * @desc 处理-下单成功-通知
     * @author wangwei
     * @date 2018-10-4 16:27:33
     * @param array $config
     * @param int $msg_id
     * @param array $msg_content
     */
    private function aliexpress_order_PlaceOrderSuccess_process($config, $msg_content)
    {
        $return = false;
        
        try {
            
            //插入列队抓取单个订单
            $account_id = $config['id'];
            $order_id = $msg_content['order_id'];
            (new UniqueQueuer(AliExpressOrderOneQueue::class))->push($account_id. '|'. $order_id);
            
            $return = true;
            
        } catch (Exception $e) {
            
        }
        
        return $return;
    }
    
    /**
     * @desc 处理-等待买家收货-通知
     * @author wangwei
     * @date 2018-10-5 15:24:44
     * @param array $config
     * @param int $msg_id
     * @param array $msg_content
     */
    private function aliexpress_order_WaitBuyerAcceptGoods_process($config, $msg_content)
    {
        $return = false;
        
        try {
            
            //插入列队抓取单个订单
            $account_id = $config['id'];
            $order_id = $msg_content['order_id'];
            (new UniqueQueuer(AliExpressOrderOneQueue::class))->push($account_id. '|'. $order_id);
            
            $return = true;
            
        } catch (Exception $e) {
            
        }
        
        return $return;
    }
    
    /**
     * @desc 处理-等待部分发货-通知
     * @author wangwei
     * @date 2018-10-5 15:23:25
     * @param array $config
     * @param int $msg_id
     * @param array $msg_content
     */
    private function aliexpress_order_SellerPartSendGoods_process($config, $msg_content)
    {
        $return = false;
        
        try {
            
            //插入列队抓取单个订单
            $account_id = $config['id'];
            $order_id = $msg_content['order_id'];
            (new UniqueQueuer(AliExpressOrderOneQueue::class))->push($account_id. '|'. $order_id);
            
            $return = true;
            
        } catch (Exception $e) {
            
        }
        
        return $return;
    }
    
    /**
     * @desc 处理-等待卖家发货-通知
     * @author wangwei
     * @date 2018-10-4 16:20:44
     * @param array $config
     * @param int $msg_id
     * @param array $msg_content
     */
    private function aliexpress_order_WaitSellerSendGoods_process($config, $msg_content)
    {
        $return = false;
        
        try {
            
            //插入列队抓取单个订单
            $account_id = $config['id'];
            $order_id = $msg_content['order_id'];
            (new UniqueQueuer(AliExpressOrderOneQueue::class))->push($account_id. '|'. $order_id);
            
            $return = true;
            
        } catch (Exception $e) {
            
        }
        
        return $return;
    }
    
    /**
     * @desc 处理-站内信消息-通知
     * @author wangwei
     * @date 2018-10-4 16:14:45
     * @param array $config
     * @param int $msg_id
     * @param array $msg_content
     * @throws Exception
     * @return boolean
     */
    private function message_Pushnewmsg_process($config, $msg_id, $msg_content)
    {
        $return = false;
        try {
            /*
             * 1、根据买家ID获取站内信channel_id
             */
            if(!$channel_id = $this->getChannelId($config, $msg_content['sender_login_id'])){
                throw new Exception('getChannelId error',2100);
            }
            
            $this->showLog("{$msg_id}=>channel_id:{$channel_id}");//日志输出
            
            /*
             * 2、判断channel_id是否存在数据库中
             */
            $msgRelation = AliexpressMsgRelation::where('channel_id',$channel_id)->field('id')->find();
            //a、不存在，压入队列，获取消息关系和消息详情
            if(!$msgRelation){
                
                $this->showLog("{$msg_id}=>addQueue");//日志输出
                
                $queue_data = [
                    'id'=>$config['id'],
                    'only_un_readed'=>false,
                    'rank'=>null,
                    'task_type'=>1,
                    'channel_id'=>$channel_id
                ];
                (new UniqueQueuer(AliExpressMsgQueueNew::class))->push(json_encode($queue_data));
            }
            
            //b、存在，获取消息详情
            if($msgRelation){
                
                $this->showLog("{$msg_id}=>getMsgDetailList");//日志输出
                
                $last_msg_id = Cache::store('AliexpressMsg')->getLastMessageId($config['id'],$channel_id);
                //如果消息ID小于上次拿的最后一条消息ID，则表明该消息已经抓取过，不需要重复存库
                if($last_msg_id && $msg_content['id'] <= $last_msg_id) {
                    throw new Exception('not need get msg_id:' . $msg_content['id'] ,2000);
                }
                
                //调用接口获取站内信详情
                $AliexpressHelp = new AliexpressHelp();
                $data = $AliexpressHelp->getMsgDetailList($config, $channel_id, $last_msg_id, $msgRelation['id']);
                
                if(empty($data['detail'])){
                    throw new Exception('getMsgDetailList detail is empty',2000);
                }
                
                $this->showLog("msg_relation_id=>{$msgRelation['id']}");//日志输出
                $this->showLog($data);//日志输出
                
                //保存消息详情数据
                $this->saveMsgDetail($data, $msgRelation['id']);
            }
            
            $return = true;
            
        } catch (Exception $e) {
            $code = $e->getCode();
            $msg = $e->getMessage();
            
            $not_error_arr = [2000,2100];//不算失败的
            $success_code_arr = [2000];//成功的确认处理的
            
            if(in_array($code, $not_error_arr, true)){
                $return = in_array($code, $success_code_arr, true);
                $this->showLog("{$msg_id}=>Non_fatal_error:{$msg},error_code:{$code}");//日志输出
            }else{
                $this->showLog("{$msg_id}=>fatal_error:{$msg},error_code:{$code}");//日志输出
                throw new Exception($e->getMessage());
            }
        }
        
        return $return;
    }
    
    /**
     * @desc 保存消息详情数据
     * @author wangwei
     * @date 2018-9-22 9:40:59
     * @param array $data
     * @param int $msg_relation_id
     * @throws Exception
     */
    private function saveMsgDetail($data, $msg_relation_id)
    {
        try {
            Db::startTrans();
            
            AliexpressMsgRelation::where('id',$msg_relation_id)->update($data['relation']);
            
            foreach ($data['detail'] as $item){
                $detailModel = new AliexpressMsgDetail();
                $map = [
                    'aliexpress_msg_relation_id'=>$item['aliexpress_msg_relation_id'],
                    'msg_id'=>$item['msg_id']
                ];
                $detailHas = $detailModel->where($map)->field('id')->find();
                $detailModel->save($item, $detailHas ? ['id'=>$detailHas['id']] : []);
            }
            Db::commit();
        } catch (Exception $ex) {
            Db::rollback();
            
            $log_arr['data'] = $data;
            $log_arr['msg_relation_id'] = $msg_relation_id;
            $log_arr['error_msg']=$ex->getMessage();
            $this->writeLog(print_r($log_arr,1));
            
            throw new Exception($ex->getMessage());
        }
    }
    
    /**
     * @titel 根据买家ID获取站内信对话ID
     * @param $config
     * @param $buyer_id
     * @return mixed
     */
    private function getChannelId($config, $buyer_id)
    {
        $api = AliexpressApi::instance($config)->loader('Message');
        $api instanceof \service\alinew\operation\Message;
        $res = $api->queryMsgChannelIdByBuyerId($buyer_id);
        $res = $this->dealResponse($res);
        return $res['channel_id'];
    }
    
    /**
     * @desc 检查消息
     * @author wangwei
     * @date 2018-10-4 16:03:40
     * @param string $tmc_message
     * @return number[]|string[]|array[]|number[]|string[]|array[]|mixed[]|unknown[][]
     */
    private function checkMessage($tmc_message)
    {
        $return = [
            'ask'=>0,
            'message'=>'message Error',
            'msg_content'=>[],
            'config'=>[]
        ];
        $msg_content = (isset($tmc_message['content']) && !empty($tmc_message['content'])) ? json_decode($tmc_message['content'],true) : [];
        if(!(is_array($msg_content) && !empty($msg_content))){
            $return['message'] = 'message content json_decode error';
            return $return;
        }
        if(!$config = $this->getConfig($tmc_message['user_nick'],false)){
            $return['message'] = "user_nick:{$tmc_message['user_nick']},getConfig by nick error";
            return $return;
        }
        $return['ask'] = 1;
        $return['message'] = 'success';
        $return['msg_content'] = $msg_content;
        $return['config'] = $config;
        return $return;
    }
    
    /**
     * 确认消费消息的状态
     * @param string $group_name
     * @param array $success_message_id_arr
     * @param \service\alinew\operation\MessageNotification $api
     * @return array
     */
    private function messagesConfirm($group_name, $s_message_id_arr, $api)
    {
        $result = $api->messagesConfirm($group_name, $s_message_id_arr);
        $result = $this->dealResponse($result);
        return $result;
    }
    
    /**
     * @desc 处理响应数据
     * @param string $data 执行api请求返回的订单数据json字符串
     * @return array 结果集
     * @author wangwei
     * @date 2018-9-21 16:19:48
     */
    private function dealResponse($data)
    {
        //已经报错了,抛出异常信息
        if (isset($data->error_response) && $data->error_response) {
            throw new Exception($data->sub_msg, $data->code);
        }
        if (isset($data->sub_message) && $data->sub_message) {
            $err_msg = $data->sub_message;
            isset($data->sub_code) && $err_msg .= ', sub_code:' . $data->sub_code;
            throw new Exception($err_msg);
        }
        //如果没有result
        if (!isset($data->result)) {
            throw new Exception(json_encode($data));
        }
        return json_decode($data->result, true);
    }
    
    /**
     * @desc 获取账号的配置信息
     * @author wangwei
     * @date 2018-9-21 18:10:51
     * @param int | string $key 键
     * @param bool $key_is_id 键是否是id
     */
    private function getConfig($key, $key_is_id = true)
    {
        $config = [];
        $store = Cache::store('AliexpressAccount');
        $info = $key_is_id ? $store->getTableRecord($key) : $store->getAccountByNick($key);
        //如果不是根据id获取账号信息，没有获取到数据不算错误
        if(!$key_is_id && !$info){
            return $config;
        }
        if (!($info && isset($info['id']))) {
            return $config;
//             throw new Exception('账号信息缺失');
        }
        if (!param($info, 'client_id')) {
            return $config;
//             throw new Exception('账号ID缺失,请先授权!');
        }
        if (!param($info, 'client_secret')) {
            return $config;
//             throw new Exception('账号秘钥缺失,请先授权!');
        }
        if (!param($info, 'access_token')) {
            return $config;
//             throw new Exception('access token缺失,请先授权!');
        }
        $config['id'] = $info['id'];
        $config['client_id'] = $info['client_id'];
        $config['client_secret'] = $info['client_secret'];
        $config['token'] = $info['access_token'];
        return $config;
    }
    
    /**
     * @desc 获取运行参数 
     * @author wangwei
     * @date 2018-9-25 16:52:11
     * @throws TaskException
     * @return string[]|number[]|unknown[]
     */
    private function getRunParams()
    {
        $group_name = $this->getData('group_name');
        //可用的消息分组
        $canGroupNameArr = $this->getGroupNameArr();
        if(!in_array($group_name, $canGroupNameArr)){
            throw new TaskException('不合法的消息分组:' . $group_name);
        }
        $nick = 'cn1518808694uwth';
        $account = Cache::store('AliexpressAccount')->getAccountByNick($nick);
        if(!($account)){
            throw new TaskException('未获取到账号数据');
        }
        $data = [
            'id'=>$account['id'],//账号ID
            'group_name'=>$group_name,//消息分组组名
            'quantity'=>100,//每次消费消息数量
        ];
        $this->group_name = param($data, 'group_name', 'message');
        //一次获取消息条数
        $this->quantity = param($data, 'quantity', 100);
        return $data;
    }
    
    /**
     * @desc 运行检查
     * @author wangwei
     * @date 2018-9-25 17:34:59
     * @return boolean
     */
    private function runCheck()
    {
        //设置超时时间
        set_time_limit(0);
        
        //获取redis
        $key = $this->task_key_prefix . $this->group_name;
        if($run_time = Cache::handler()->get($key)){
            return time() - $run_time > $this->task_expired_time;
        }else{
            return Cache::handler()->set($key, time());
        }
    }
    
    /**
     * @desc 更新运行时间
     * @author wangwei
     * @date 2018-9-25 18:34:48
     * @return unknown
     */
    private function updateRunTime()
    {
        $key = $this->task_key_prefix . $this->group_name;
        return Cache::handler()->set($key, time());
    }
    
    /**
     * @desc 运行结束
     * @author wangwei
     * @date 2018-9-25 18:43:46
     */
    private function runEnd()
    {
        $key = $this->task_key_prefix . $this->group_name;
        Cache::handler()->del($key);
    }
    
    /**
     * @desc 输出日志 
     * @author wangwei
     * @date 2018-9-27 11:58:45
     * @param unknown $log
     */
    private function showLog($log)
    {
//         //页面输出
//         $echo_log = is_string($log) ? $log : print_r($log,1);
//         echo $echo_log. "<br/>";

//         $this->writeLog($log);
    }
    
    /**
     * @desc 记录日志
     * @author wangwei
     * @date 2018-9-27 11:59:27
     * @param unknown $log
     */
    private function writeLog($log)
    {
        $hash_key = 'log:aliexpress_get_notification';
        $hash_field = date('Y-m-d H:i:s') . '_' . getmypid() . '_' . ($this->log_index++);
        $log = is_string($log) ? $log : json_encode($log);
        Cache::handler()->hSet($hash_key, $hash_field, $log);
    }
}
