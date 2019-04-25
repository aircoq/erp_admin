<?php
namespace app\customerservice\queue;

use app\common\model\Channel;
use app\common\service\SwooleQueueJob;
use app\common\cache\Cache;
use app\index\service\ChannelConfig;
use Exception;
use app\common\model\MsgRuleSet;
use app\common\service\ChannelAccountConst;
use app\common\model\aliexpress\AliexpressOnlineOrder;
use app\common\model\aliexpress\AliexpressAccount;
use app\customerservice\service\AliexpressHelp;
use app\common\model\MsgEmailSend;
use app\customerservice\service\MsgRuleHelp;
use app\customerservice\service\MsgTemplateHelp;
use app\customerservice\service\EbayMessageHelp;
use think\Db;
use app\customerservice\service\EbayFeedbackHelp;
use app\common\model\MsgTemplate as MsgTemplateModel;
use app\customerservice\service\AmazonEmail;
use app\common\model\amazon\AmazonOrder;
use app\common\model\OrderSourceDetail;
use app\common\model\Order;
use app\order\service\AuditOrderService;

class MsgReviewAutoSendQueue extends SwooleQueueJob
{
    //队列redis键前缀
    private $queue_key_prefix = 'queue:customerservice:MsgReviewAutoSendQueue:';
    //队列过期时间（秒）
    private $queue_expired_time = 300;

    const SEND_MAX_COUNT = 3;//最大发送次数
    
    private $event_name = null;//触发事件名称msg_email_send.trigger_rule字段
    private $msg_rule_set_id = null;//规则设置表id
    
    private $msg_email_send_id = null;//站内信/邮件发送表id
    private $channel_id = null;//渠道id
    private $account_id = null;//账号id
    private $channel_order_number = null;//渠道订单号
    private $template_id = null;//模板id
    private $content = '';//发送的消息内容
    private $md5_content = '';//发送的消息内容md5值
    private $item_id = '';//ebay 商品唯一id
    private $receiver = '';//接收人
    private $extra_params = '';//自动发信参数

    private $check_send_log = '';//运行前检查，日志内容
    /*
     * 发送邮箱方式
     * 1：只发送到买家在销售渠道中的邮箱(如eBay邮箱)--email
     * 2：优先使用支付系统(如PayPal)邮箱,没有时使用销售渠道邮箱--payORemail
     * 3：如果存在则同时发送至支付系统邮箱,不存在则只发送至销售渠道邮箱--payANDemail
     * 4：只发送到买家站内信--msg
     */
    private $send_email_rule = null;

    public function getAuthor(): string
    {
        return 'wangwei';
    }

    /**
     * @desc 描述
     * @author wangwei
     * @date 2018-11-1 14:46:31
     * @return string
     */
    public function getDesc(): string
    {
        return '站内信/评价自动发送';
    }

    /**
     * @desc 获取队列名称
     * @author wangwei
     * @date 2018-11-1 14:46:50
     * @return string
     */
    public function getName(): string
    {
        return '站内信/评价自动发送';
    }

    public static function swooleTaskMaxNumber(): int
    {
        return 15;
    }

    /**
     * @desc 执行
     * @author wangwei
     * @date 2018-11-1 14:55:34
     */
    public function execute(){
        try {
            //设置执行不超时
            set_time_limit(0);
            
            //获取执行的参数信息
            $this->getParams();

            //调度发送方法
            $sdRe = $this->sendDispatch();

        } catch (Exception $ex) {
            //运行结束，删除锁
            if($this->msg_rule_set_id) {
                $this->del_lock();
            }
            $error_msg = 'error_msg:' . $ex->getMessage().';file:'.$ex->getFile().';line:'.$ex->getLine();
            $sdRe = ['ask'=>0,'message'=>$error_msg];
//             throw new Exception($error_msg);
        }

        //发送完成回调
        $this->sendComplete($sdRe);

        //运行结束，删除锁
        if($this->msg_rule_set_id) {
            $this->del_lock();
        }
        return true;
    }


    /**
     * @desc 设置锁
     * @author denghaibo
     * @date 2019-2-26 17:19:48
     * @return boolean
     */
    private function set_lock()
    {
        //获取redis
        $key = $this->queue_key_prefix . $this->channel_id . $this->receiver;
        //设置原子锁
        return Cache::handler()->set($key, time(),['nx', 'ex' => $this->queue_expired_time]);
    }

    /**
     * @desc 运行结束
     * @author denghaibo
     * @date 2019-2-26 17:19:48
     */
    private function del_lock()
    {
        $key = $this->queue_key_prefix . $this->channel_id . $this->receiver;
        Cache::handler()->del($key);
    }


    /**
     * @desc 发送前检查
     * @author wangwei
     * @date 2018-11-2 16:03:12
     * @return boolean
     */
    private function checkSend(){
        $this->check_send_log = '';

        $duplication = (new ChannelConfig($this->channel_id))->getConfig('channel_duplication');
        $duplication = $duplication['value'] ?? 0;
        //检查发信频率
        if ($duplication)
        {
            if ($this->receiver && $this->template_id)
            {
//                $where['channel_id'] = $this->channel_id;
//                $where['account_id'] = $this->account_id;
                $where['receiver'] = $this->receiver;
                $where['md5_content'] = $this->md5_content;
                $where['status'] = 2;

                $send_time = MsgEmailSend::where($where)->value('send_time');
                if (!empty($send_time) && (time() - $send_time - 24*60*60)<0)
                {
                    return false;
                }
            }else{
                return true;
            }
        }

        //检查匹配规则
        if($this->event_name && $this->msg_rule_set_id){
            $event_name = $this->event_name;
            $order_data = [
                'channel_id'=>$this->channel_id,//Y 渠道id
                'account_id'=>$this->account_id,//Y 账号id
                'channel_order_number'=>$this->channel_order_number,//Y 渠道订单号
                'msg_rule_set_id'=>$this->msg_rule_set_id,//N 自动发信规则设置表id
                'receiver'=>$this->receiver,//Y 收信人
            ];
            $msgRuleHelp = new MsgRuleHelp();
            $teRe = $msgRuleHelp->triggerEvent($event_name, $order_data);
            if(!$teRe){
                $this->check_send_log = print_r($msgRuleHelp->getLogContentArr(), true);
            }
            return $teRe;
        }else{
            return true;
        }
    }
    
    /**
     * @desc 调度发送方法
     * @author wangwei
     * @date 2018-11-1 16:38:22
     */
    private function sendDispatch(){
        $return = [
            'ask'=>0,
            'message'=>'sendDispatch error'
        ];

        /*
         * 获取发送的消息内容
         */
        if(!$this->getSendContent()){
            $return['ask'] = 3;
            $return['message'] = '发信模板未获取到发信内容，不执行发送';
            return $return;
        }

        /*
         * 执行前检查
         */
        if(!$this->checkSend()){
            $return['ask'] = 3;
            $return['message'] = '执行前检查，不满足发信条件，检查日志：' . $this->check_send_log;
            return $return;

        }
        /*
         * 站点代码
         */
        $channelCode = '';
        switch ($this->channel_id){
            case ChannelAccountConst::channel_ebay:
                $channelCode = 'ebay';
                break;
            case ChannelAccountConst::channel_amazon:
                $channelCode = 'amazon';
                break;
            case ChannelAccountConst::channel_wish:
                $channelCode = 'wish';
                break;
            case ChannelAccountConst::channel_aliExpress:
                $channelCode = 'aliExpress';
                break;
            case ChannelAccountConst::channel_CD:
                $channelCode = 'cd';
                break;
            case ChannelAccountConst::channel_Lazada:
                $channelCode = 'lazada';
                break;
            case ChannelAccountConst::channel_Joom:
                $channelCode = 'joom';
                break;
            case ChannelAccountConst::channel_Pandao:
                $channelCode = 'pandao';
                break;
            case ChannelAccountConst::channel_Shopee:
                $channelCode = 'shopee';
                break;
            case ChannelAccountConst::channel_Paytm:
                $channelCode = 'paytm';
                break;
            case ChannelAccountConst::channel_Walmart:
                $channelCode = 'walmart';
                break;
            case ChannelAccountConst::channel_Vova:
                $channelCode = 'vova';
                break;
            case ChannelAccountConst::Channel_Jumia:
                $channelCode = 'jumia';
                break;
            case ChannelAccountConst::Channel_umka:
                $channelCode = 'umka';
                break;
            case ChannelAccountConst::channel_Newegg:
                $channelCode = 'newegg';
                break;
            case ChannelAccountConst::channel_Oberlo:
                $channelCode = 'oberlo';
                break;
            case ChannelAccountConst::channel_Shoppo:
                $channelCode = 'shoppo';
                break;
            case ChannelAccountConst::channel_Zoodmall:
                $channelCode = 'zoodmall';
                break;
            case ChannelAccountConst::channel_Pdd:
                $channelCode = 'pdd';
                break;
            case ChannelAccountConst::channel_Yandex:
                $channelCode = 'yandex';
                break;
            case ChannelAccountConst::channel_Distribution:
                $channelCode = 'distribution';
                break;
            default:
                break;
        }
        if(!$channelCode){
            $return['message'] = "暂不支持 订单渠道:{$this->channel_id}";
            return $return;
        } 
        
        /*
         * 动作名称
         */
        $action = '';
        switch ($this->send_email_rule){
            case 1:
                $action = 'email';
                break;
            case 2:
                $action = 'payORemail';
                break;
            case 3:
                $action = 'payANDemail';
                break;
            case 4:
                $action = 'msg';
                break;
            case 5:
                //评价
                $action = 'feedback';
                break;
            case 6:
                //回评
                $action = 'assessment';
                break;
            default:
                break;
        }
        if(!$action){
            $return['message'] = "暂不支持 发送邮箱方式:{$this->send_email_rule}";
            return $return;
        }
        
        /*
         * 调度执行方法
         */
        $funcName = "send_{$channelCode}_{$action}";
        if(!method_exists($this,$funcName)){
            $class = MsgReviewAutoSendQueue::class;
            $return['message'] = "类:{$class},暂不支持 方法:[{$funcName}]";
            return $return;
        }
        
        /*
         * 调用具体执行方法并返回
         */
        return $this->$funcName();
    }
    
    /**
     * @desc 获取发送的消息内容
     * @author wangwei
     * @date 2018-11-5 18:03:07
     * @return boolean
     */
    private function getSendContent(){
        if(empty($this->channel_order_number)){
            $tpl_info = (new MsgTemplateModel())->find($this->template_id);
            $this->content = $tpl_info['template_content'];
            $this->md5_content = md5($this->content);
            return !!$this->content;
        }elseif (empty($this->msg_rule_set_id)){
            $row = MsgEmailSend::where('id',$this->msg_email_send_id)->field('content')->find();
            $this->content = $row['content'];
            return !!$this->content;
        }else {
            $params=[
                'template_id'=>$this->template_id,
                'channel_id'=>$this->channel_id,
                'search_id'=>$this->channel_order_number,
                'search_type'=>'channel_order',
                'transform'=>'1',
            ];
            $this->content = (new MsgTemplateHelp())->matchTplContent($params);
            $this->md5_content = md5($this->content);
            return !!$this->content;
        }
    }
    
    
    /**
     * @desc 发送完成回调
     * @author wangwei
     * @date 2018-11-1 17:18:13
     */
    private function sendComplete($re){
        if(!$this->msg_email_send_id){
            throw new Exception('msg_email_send_id 不存在!');
        }
        $row = MsgEmailSend::where('id',$this->msg_email_send_id)->find();
        if(!$row){
            throw new Exception('msg email 为空!');
        }
        $update = [
            'content'=>$this->content,
            'md5_content'=>$this->md5_content,
            'send_time'=>time(),
            'count'=>['exp','count+1'],
            'error_msg'=>param($re, 'message', '未返回错误消息')
        ];

        if (empty($this->msg_rule_set_id))
        {
            unset($update['md5_content']);
        }

        $ask = param($re, 'ask', 2);
        if($ask==0){//发送失败
            if($row['count'] < self::SEND_MAX_COUNT){
                $update['status'] = '0';//失败次数小于最大次数，重发
            }else{
                $update['status'] = '3';
            }
        }else if($ask==1){//发送成功
            $update['status'] = '2';

            //问题订单联系退款备注
            if ($row['channel_id'] == 2 && $row['trigger_rule'] == 'E13' && $row['send_email_rule'] == 1)
            {
                $orderModel = new Order();
                $auditOrderService = new AuditOrderService();
                $order = $orderModel->where(['channel_id' => 2,'channel_order_number' => $row['channel_order_number']])->field('id')->find();
                $auditOrderService->linkBuyerOrderNote($order['id']);
            }
        }else if($ask==2){//未返回错误消息
            $update['status'] = '3';
        }else if($ask==3){//执行前检查，无需发送
            $update['status'] = '4';
        }
        return $row->save($update);
    }
    
    /**
     * @desc 速卖通--发送站内信 
     * @author wangwei
     * @date 2018-11-1 17:18:13
     */
    private function send_aliExpress_msg(){
        $return = [
            'ask'=>0,
            'message'=>'send_aliExpress_msg_error'
        ];

        /**
         * 1、组装参数
         */
        //找对应的订单数据
        $order_con = [
            'order_id'=>$this->channel_order_number,
            'account_id'=>$this->account_id
        ];
        $order_data = AliexpressOnlineOrder::where($order_con)
        ->field('id,account_id,buyer_login_id,buyer_signer_fullname')
        ->find();
        if (empty($order_data)) {
            $return['message'] = "未找到平台订单:{$this->channel_order_number}";
            return $return;
        }
        //获取当前Aliexpress账号信息
        $accountInfo = Cache::store('AliexpressAccount')->getTableRecord($order_data['account_id']);
        if(empty($accountInfo)) {
            $return['message'] = "获取账号数据错误:{$order_data['account_id']}";
            return $return;
        }
        $config = [
            'id' => $accountInfo['id'],
            'client_id' => $accountInfo['client_id'],
            'client_secret' => $accountInfo['client_secret'],
            'accessToken' => $accountInfo['access_token'],
            'refreshtoken' => $accountInfo['refresh_token'],
        ];
        $data = [
            'content'=>$this->content,
            'seller_id'=>$accountInfo['login_id'] ? $accountInfo['login_id'] : $accountInfo['user_nick'],
            'msg_type'=>'order',
            'buyer_id'=>$order_data['buyer_login_id'],
            'buyer_name'=>$order_data['buyer_signer_fullname'],
            'extern_id'=>$this->channel_order_number
        ];

        /**
         * 2、发送消息
         */
        $aliexpressHelp = new AliexpressHelp();
        $amRe = $aliexpressHelp->addMsg($config, $data);

        /**
         * 3、整理返回数据
         */
        if(!$amRe['status']){
            $return['message'] = "速卖通消息发送失败:{$amRe['message']}";
            return $return;
        }
        $return['ask'] = 1;
        $return['message'] = 'success';
        return $return;
    }
    
    /**
     * @desc ebay--发送站内信
     */
    private function send_ebay_msg(){
        $return = [
            'ask'=>0,
            'message'=>'send_ebay_msg_error'
        ];

        $data = [];

        /**
         * 1、组装参数
         */
        if(empty($this->channel_order_number)){

            $item_id = '';

            if (isset($this->extra_params['item_id'])){
                $item_id = $this->extra_params['item_id'];
            }else{
                $return['message'] = "item_id 不存在";
                return $return;
            }

            $data = [
                'text'=>$this->content,
                'item_id'=>$item_id,
                'buyer_id'=>$this->receiver,
                'account_id'=>$this->account_id,
            ];
        }else{
            $order_id = Db::table('order')->where('channel_order_number',$this->channel_order_number)->value('id');
            $data = [
                'text'=>$this->content,
                'order_id'=>$order_id,
            ];
        }

        /**
         * 2、发送消息
         */
        $ebayMessageHelp = new EbayMessageHelp();
        $amRe = $ebayMessageHelp->sendEbayMsgLockRun($data);

        /**
         * 3、整理返回数据
         */
        if(!$amRe['status']){
            $return['message'] = "ebay消息发送失败:{$amRe['message']}";
            return $return;
        }
        $return['ask'] = 1;
        $return['message'] = 'success';
        return $return;
    }

    /**
     * @desc ebay--评价
     */
    private function send_ebay_feedback(){
        $return = [
            'ask'=>0,
            'message'=>'send_ebay_feedback_error'
        ];

        $data = [];
        /**
         * 1、组装参数
         */
        if (empty($this->channel_order_number))
        {
            $data = [
                'text'=>$this->content,
                'id'=>param($this->extra_params,'feedback_id'),
            ];
        } else {
            $order_id = Db::table('order')->where('channel_order_number',$this->channel_order_number)->value('id');
            $data = [
                'text'=>$this->content,
                'order_id'=>$order_id
            ];

            if(empty($order_id)){
                $return['message'] = '订单号为空';
                return $return;
            }
        }

        /**
         * 2、发送评价
         */
        $ebayFeedbackHelp = new EbayFeedbackHelp();
        try{
            $amRe = $ebayFeedbackHelp->leaveFeedback($data);
            /**
             * 3、整理返回数据
             */
            if(!$amRe){
                $return['message'] = "ebay评价失败";
                return $return;
            }
        } catch (Exception $e) {
            $return['message'] = $e->getMessage();
            return $return;
        }

        $return['ask'] = 1;
        $return['message'] = 'success';
        return $return;
    }

    /**
     * @desc ebay--回评
     */
    private function send_ebay_assessment(){
        $return = [
            'ask'=>0,
            'message'=>'send_ebay_assessment_error'
        ];

        /**
         * 1、组装参数
         */
//        $order_id = Db::table('order')->where('channel_order_number',$this->channel_order_number)->value('id');
//        $feedback_id = Db::table('ebay_feedback')->where('order_id',$order_id)->value('id');
        $data = [
            'text'=>$this->content,
            'id'=>param($this->extra_params,'feedback_id'),
        ];

        /**
         * 2、发送评价
         */
        $ebayFeedbackHelp = new EbayFeedbackHelp();
        $amRe = $ebayFeedbackHelp->reply($data);

        /**
         * 3、整理返回数据
         */
        if(!$amRe){
            $return['message'] = "ebay回评失败";
            return $return;
        }
        $return['ask'] = 1;
        $return['message'] = 'success';
        return $return;
    }

    /**
     * @desc amazon--邮件
     */
    private function send_amazon_email(){
        $return = [
            'ask'=>0,
            'message'=>'send_amazon_email_error'
        ];

        $amRe = [
            //status 0 一般错误，1发送成功，2并发超三个，3每分钟超30条，4每天超10000条，5 3或者4
            'status'=>0,
            'message'=>'',
        ];

        /**
         * 1、组装参数
         */
        $orderSourceDetail = new OrderSourceDetail();
        $buyer = AmazonOrder::where('order_number', $this->channel_order_number)->find();
        $order_id = Db::table('order')->where('channel_order_number',$this->channel_order_number)->value('id');
        $channel_item_id = $orderSourceDetail->where(['order_id' => $order_id])->value('channel_item_id');
        $sendMailAttachRoot = ROOT_PATH .'public/upload/email/amazon';
        $data = [
            'account_id'=>$this->account_id,
            'customer_id'=>2,
            'buyer_name'=>$buyer['user_name'],
            'buyer_email'=>$buyer['email'],
            'subject'=>'A message : ' . $channel_item_id,
            'content'=>$this->content,
        ];

        if(empty($buyer))
        {
            $return['message'] = '通过channel_order_number查不到收件人信息！';
            return $return;
        }
        if(empty($data['account_id']) || empty($data['subject']) || empty($data['content'])){
            $return['message'] = '发送邮件数据不完整！';
            return $return;
        }
        /**
         * 2、发送邮件
         */
        $amazonEmail = new AmazonEmail();
        $amRe = $amazonEmail->senMail($data,$sendMailAttachRoot);

        if ($amRe['status'] == 2 || $amRe['status'] == 3 || $amRe['status'] == 4 || $amRe['status'] == 5)
        {
            $data['status'] = 0;
            switch ($amRe['status']){
                case 2:
                    $data['cron_time'] = time() + 1;
                    break;
                case 3:
                    $data['cron_time'] = time() + 60;
                    break;
                case 4:
                    $data['cron_time'] = time() + 86400;
                    break;
                case 5:
                    $data['cron_time'] = time() + 60;
                    break;
                default:
                    break;
            }

            Db::table('msg_email_send')->where(['id'=>$this->msg_email_send_id])->update($data);
        }

        /**
         * 3、整理返回数据
         */
        if(!$amRe['status']==1){
            $return['message'] = $amRe['message'];
            return $return;
        }
        $return['ask'] = 1;
        $return['message'] = $amRe['message'];
        return $return;
    }

    /**
     * @desc 获取任务执行的参数,过滤参数信息
     * @return array $data 检验后的数据信息
     * @author wangwei
     * @date 2018-11-1 16:06:02
     */
    private function getParams(){
        //获取任务参数
        $data = json_decode($this->params, true);
//         $data = [
//             'msg_email_send_id'=>'',////Y 站内信/邮件发送表id
//             'channel_id'=>'',//Y 渠道id
//             'account_id'=>'',//Y 账号id
//             'channel_order_number'=>'',//Y 渠道订单号
//             'send_email_rule'=>'',//Y 发送邮箱方式
//             'template_id'=>'',//Y 模板id
//             'event_name'=>'',//N 事件名称
//             'msg_rule_set_id'=>'',//N 规则设置表id
//             'item_id'=>'',//N ebay 商品唯一id
//             'sender'=>'',//N ebay 站内信消息发送人
//             'extra_params'=>'',//N 附加字段 json格式
//         ];
        $this->msg_email_send_id = param($data, 'msg_email_send_id',null);
        $this->event_name = param($data,'event_name',null);
        $this->msg_rule_set_id = param($data,'msg_rule_set_id',null);

        if(!$channel_id = param($data, 'channel_id',0)){
            if ($this->msg_rule_set_id)
            {
                throw new Exception('渠道id不能为空!');
            }
        }
        if(!$account_id = param($data, 'account_id',0)){
            if ($this->msg_rule_set_id) {
                throw new Exception('账号id不能为空!');
            }
        }
        if(!$receiver = param($data, 'receiver','')){
            if ($this->msg_rule_set_id) {
                throw new Exception('收件人不能为空!');
            }
        }

        $extra_params = param($data, 'extra_params');

        if($channel_id == 1 || $channel_id == 2){
            $channel_order_number = param($data, 'channel_order_number');
//            $item_id = param($data, 'item_id',0);
//            $sender = param($data, 'sender');
        }else{
            if(!$channel_order_number = param($data, 'channel_order_number')){
                throw new Exception('渠道订单号不能为空!');
            }
//            $item_id = param($data, 'item_id',0);
//            $sender = param($data, 'sender');
        }
        if(!$send_email_rule = param($data, 'send_email_rule')){
            throw new Exception('发送邮箱方式不能为空!');
        }
        if(!isset(MsgRuleSet::$SEND_EMAIL_RULE[$send_email_rule])){
            throw new Exception("暂时不支持的发送邮箱方式:{$send_email_rule}");
        }
        if(!$template_id = param($data, 'template_id', 0)){
            if ($this->msg_rule_set_id) {
                throw new Exception('模板id不能为空!');
            }
        }
        $this->channel_id = $channel_id;
        $this->account_id = $account_id;
        $this->channel_order_number = $channel_order_number;
//        $this->item_id = $item_id;
        $this->receiver = $receiver;
        $this->send_email_rule = $send_email_rule;
        $this->template_id = $template_id;
        $this->extra_params = $extra_params;
        return $data;
    }
    
    /**
     * @desc 设置消息
     * @author wangwei
     * @date 2018-11-1 17:49:18
     * @see \app\common\service\SwooleQueueJob::setParams()
     * @param string $params
     */
    public function setParams($params){
        $this->params = $params;
    }
    
}

