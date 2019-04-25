<?php
namespace app\customerservice\service;

use app\common\exception\JsonErrorException;
use app\common\model\aliexpress\AliexpressEvaluate;
use app\common\model\aliexpress\AliexpressIssue;
use app\common\model\Channel;
use app\common\model\Order;
use app\common\model\OrderProcess;
use app\customerservice\exception\MessageException;
use app\order\service\OrderService;
use service\alinew\AliexpressApi;
use app\common\model\aliexpress\AliexpressMsgRelation;
use app\common\model\aliexpress\AliexpressMsgDetail;
use app\common\model\ChannelUserAccountMap;
use app\common\service\Common;
use think\Request;
use think\Exception;
use app\common\model\aliexpress\AliexpressSendMsg;
use think\Db;
use erp\AbsServer;
use app\customerservice\service\MsgTemplateHelp;
use app\common\model\aliexpress\AliexpressOnlineOrder;
use app\common\model\User;
use app\common\model\aliexpress\AliexpressOnlineOrderDetail;
use app\common\model\OrderPackage;
use app\common\model\ShippingMethodChannel;
use app\index\service\DeveloperService;
use app\common\cache\Cache;
use app\common\service\ChannelAccountConst;
use app\customerservice\filter\EbayCustomerFilter;
use app\customerservice\filter\AliexpressAccountFilter;
use app\common\service\Filter;
use app\index\service\User as UserService;
use app\index\service\AccountService;
use app\common\traits\User as UserTraits;
use app\common\model\OrderDetail;
use app\common\service\Report;

class AliexpressHelp extends AbsServer
{
    public static $msg_type_map = [
        1=>'product',
        2=>'order',
        3=>'member',
    ]; 
    use UserTraits;
    
    /**
     * 回复信息/发送新消息
     * @param array $config
     * @param array $data
     * @example $data = [
     *          'content'=>'',//Y
     *          'seller_id'=>'',//Y
     *          'msg_type'=>'',//Y 消息类型，order、product、member
     *          'buyer_id'=>'',//Y 买家id
     *          'buyer_name'=>'',//Y 买家名称
     *          
     *          'extern_id'=>'',//N
     *          'img_url'=>'',//N
     *          'channel_id'=>'',//N
     *          'id'=>'',//N  aliexpress_msg_relation.id
     * ];
     * 
     * @return array
     */
    public function addMsg($config,$data,$is_reply=0)
    {
        $userInfo = Common::getUserInfo();
        $msgServer = AliexpressApi::instance($config)->loader('Message');
        $msgServer instanceof \service\alinew\operation\Message;
        
        $content = param($data, 'content','');
        $extern_id = param($data, 'extern_id',null);
        $channel_id = param($data, 'channel_id',null);
        $img_url = param($data, 'img_url','');
        $aliexpress_msg_relation_id = param($data, 'id',0);
        
        $reponse = $msgServer->addMsg($data['seller_id'],$data['msg_type'],$data['buyer_id'],$content,$extern_id,$channel_id,$img_url);
        $reponse = $this->dealResponse($reponse);
        $sendMsgModel = new AliexpressSendMsg();
        $relationModel = new AliexpressMsgRelation();
        $detailModel = new AliexpressMsgDetail();
        
        $sendData = [
            'aliexpress_msg_relation_id' => $aliexpress_msg_relation_id,
            'channel_id' => $channel_id,
            'aliexpress_account_id' => $config['id'],
            'content' => $content,
            'buyer_id' => $data['buyer_id'],
            'buyer_name' => $data['buyer_name'],
            'msg_type' => $data['msg_type']=='order' ? '2' : '1',
            'img_path' => $img_url,
            'sender_uid' => $userInfo['user_id'],
            'status' => AliexpressSendMsg::STATUS_FAIL
        ];
        $status  = false;
        $r_data  = [];
        $insert_data=[];
        if(isset($reponse['is_success']) && $reponse['is_success'] && $reponse['error_msg'] == 'success!'){
            $status  = true;
            $r_data  = [
                'content'=>$content
            ];
            /*
             * 如果回复消息成功，则需要更新卖家回复内容到数据库中
             */
            $before_last_msg_id = Cache::store('AliexpressMsg')->getLastMessageId($config['id'], $reponse['channel_id']);  //拿到上次抓取的最后一条消息ID
            $config['token'] = $config['accessToken'];
            //调用接口获取详情数据
            $res = $this->getMsgDetailList($config, $reponse['channel_id'], $before_last_msg_id);
            $update_data = [
                'deal_status'=>  AliexpressMsgRelation::DEAL_STATUS_SUCCESS,
                'channel_id'=>$reponse['channel_id']
            ];
            if(isset($res['detail']) && !empty($res['detail'])){
                $update_data['last_is_own'] = 1;
                $update_data = array_merge($res['relation'],$update_data);
            }
            $sendData['status'] = AliexpressSendMsg::STATUS_SUCCESS;
            $insert_data['relation'] = $update_data;
            $insert_data['detail'] = $res['detail'];
            $sendData['aliexpress_msg_relation_id'] = $relationModel->add($insert_data,$config['id']);//将组装好的数据入库
        }
        $sendMsgModel->save($sendData);
        if($sendData['aliexpress_msg_relation_id']){
            $relationModel->update(['handler_id'=> $userInfo['user_id'], 'handle_time'=>  time()],['id'=>$sendData['aliexpress_msg_relation_id']]);
        }
        $error_msg = param($reponse, 'error_msg', param($reponse, 'msg', ''));

        if($status)
        {
            $data['buyer_qauntity'] = 1;
            if($is_reply)
            {
               $data['message_quantity'] = 1;
            }
            //统计数据；
            Report::statisticMessage(ChannelAccountConst::channel_aliExpress, $userInfo['user_id'], time(), $data);
        }

        return ['status'=>$status,'message'=>$error_msg,'data'=>$r_data];
    }
    
    /**
     * 打标签
     * @param type $config
     * @param type $data
     * @return type
     */
    public function changeRank($config,$data)
    {
        $msgServer      =   AliexpressApi::instance($config)->loader('Message');

        $reponse        =   $msgServer->updateMsgRank($data['channel_id'],$data['rank']);
        $res=$this->dealResponse($reponse );
        $status=isset($res['is_success'])&&$res['is_success']?1:false;

        if($status){
            AliexpressMsgRelation::update(['rank'=>$data['rank']], ['channel_id'=>$data['channel_id']]);
        }
        return $status ? true : false;
    }
    
    /**
     * 获取消息明细
     * @param type $id
     * @return type
     */
    public function getMsgDetail($id)
    {
        $details = AliexpressMsgDetail::where(['aliexpress_msg_relation_id'=>$id])
                ->field('id,sender_name,gmt_create,message_type,type_id,content,summary,file_path,sender_login_id,receiver_name')
                ->order('gmt_create desc')
                ->select();
        if(!empty($details)){
            foreach($details as $k=>$item){
                $summary = json_decode($item['summary'], 1);
                $file_path = json_decode($item['file_path'], 1);
                $details[$k]['summary'] = $summary;
                $details[$k]['file_path'] = $file_path;
                $details[$k]['product_url'] = isset($summary['productDetailUrl'])?$summary['productDetailUrl']:'';
            }
        }
        return $details;
    }
    
    /**
     * 更改消息处理状态
     * @param type $config
     * @param type $channelId
     * @throws Exception
     */
    public function changeDealStat($config,$channelId)
    {
        try {
            $msgServer      =   AliexpressApi::instance($config)->loader('Message');
            
            $msgServer instanceof \service\alinew\operation\Message;

            $reponse        =   $msgServer->updateMsgProcessed($channelId);
            $res=$this->dealResponse($reponse);

            $status=isset($res['is_success'])&&$res['is_success']?1:false;
            if($status){
               AliexpressMsgRelation::update(['deal_status'=>  AliexpressMsgRelation::DEAL_STATUS_SUCCESS], ['channel_id'=>$channelId]);
            }
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
        
    }
    
    /**
     * 重新发送
     * @param type $config
     * @param type $data
     * @return boolean
     */
    public function reSend($config,$data)
    {
        Db::startTrans();
        try {
            $msgServer      =   AliexpressApi::instance($config)->loader('Message');
            $reponse        =   $msgServer->addMsg($data['channel_id'],$data['msgSources'],$data['buyer_id'],$data['content'],$data['img_path']);
            if($reponse['status']==1){  
                AliexpressMsgRelation::update(['deal_status'=>  AliexpressMsgRelation::DEAL_STATUS_SUCCESS], ['channel_id'=>$data['channel_id']]);
                AliexpressSendMsg::update(['status'=>  AliexpressSendMsg::STATUS_SUCCESS], ['id'=>$data['id']]);
            }
            Db::commit();
            return true;
        } catch (Exception $ex) {
            Db::rollback();
            return false;
        }
    }
    
    /**
     * 更改消息是否已读
     * @param type $config
     * @param type $channelId
     * @param type $msgSources
     * @throws Exception
     */
    public function readMsg($config,$channelId,$msgSources=null)
    {
        try {
            $msgServer      =   AliexpressApi::instance($config)->loader('Message');
            $reponse        =   $msgServer->updateMsgRead($channelId);
            $res=$this->dealResponse($reponse);
            $status=isset($res['is_success'])&&$res['is_success']?1:false;

             //\think\log::write($reponse,"coming_yes4");
            if($status){
                AliexpressMsgRelation::update(['read_status'=>  AliexpressMsgRelation::IS_READ,'unread_count'=>0], ['channel_id'=>$channelId]);
            }
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }
    
    /**
     * 获取模板内容
     * @param type $msgId
     * @param type $tmpId
     * @param type $request
     * @return type
     */
    public function getTemplateContent($msgId,$tmpId,$request)
    {
        $user_info = Common::getUserInfo($request);
        $userModel = User::where(['id'=>$user_info['user_id']])->field('email')->find();
        $tmpServer = $this->invokeServer(MsgTemplateHelp::class);
        //准备模板相关资料
        $data = [];
        $msg = AliexpressMsgRelation::where(['id'=>$msgId])->field('channel_id,msg_type,other_login_id,other_name')->find();
        $data['buyer_name']     = $msg['other_name'];                         //买家名
        $data['buyer_id']  = $msg['other_login_id'];                     //买家loginId
        //获取一条消息详细
        $msg_detail = AliexpressMsgDetail::where(['aliexpress_msg_relation_id'=>$msgId])->field('type_id,summary,channel_id,message_type')->order('gmt_create asc')->find();                
        $summary = json_decode($msg_detail['summary'], 1);                
        $seller_name = $msg['other_login_id']==$summary['sender_login_id']?$summary['receiver_name']:$summary['sender_name'];
        $data['seller_id']  = $seller_name;                                                 //卖家账号
        $data['seller_email']    = $userModel['email'];                                          //卖家Email

        //获取订单相关信息
        if($msg['msg_type']==2){
            $data['product_name'] = $summary['product_name'];//产品名称
            $product_info = AliexpressOnlineOrderDetail::where(['order_id'=>$msg['channel_id'],'product_name'=>$summary['product_name']])->field('product_id')->find();
            $data['product_id']   = $product_info['product_id'];//产品ID
            $order_info = AliexpressOnlineOrder::where(['order_id'=>$msg['channel_id']])
                    ->field('buyer_login_id,buyer_signer_fullname,order_id,receipt_address,pay_amount,gmt_pay_time,gmt_send_goods_time,logistic_info')
                    ->find();
            if(!empty($order_info)){
                $address_info = json_decode($order_info['receipt_address'], 1);
                $recipientAddress = (isset($address_info['address2'])?$address_info['address2'].',':'').
                        $address_info['detailAddress'].','.($address_info['city']?$address_info['city'].',':'').
                        ($address_info['province']?$address_info['province'].',':'').$address_info['country'];
                $logistic_info = json_decode($order_info['logistic_info'], 1);
                $pay_info = json_decode($order_info['pay_amount'], 1);
                $data['order_url'] = $summary['order_url'];
                $data['order_id'] = $order_info['order_id'];                                         //订单ID                
                $data['payment_date'] = date('Y-m-d H:i:s',$order_info['gmt_pay_time']);             //付款时间
                $data['delivery_date'] = date('Y-m-d H:i:s',$order_info['gmt_send_goods_time']);     //发货时间
                $data['amount'] = $pay_info['amount'].$pay_info['currencyCode'];                    //付款金额
                $data['recipient_name'] = $address_info['contactPerson'];                            //收件人
                $data['recipient_address'] = $recipientAddress;                                      //收件地址
                $carrier = $carrierUrl = $trackNumber = [];
                if(!empty($logistic_info)){
                    foreach ($logistic_info as $item){
                        $carrier[$item['logisticsNo']]      =   $item['logisticsServiceName'];
                        $trackNumber[$item['logisticsNo']]  =   $item['logisticsNo'];
                        $orderPackage = OrderPackage::where(['shipping_number'=>$item['logisticsNo']])->field('shipping_id')->find();
                        if(!empty($orderPackage)){
                            $ShippingMethodChannel = ShippingMethodChannel::where(['shipping_method_id'=>$orderPackage['shipping_id']])->find();                            
                            $carrierUrl[$item['logisticsNo']]   =   !empty($ShippingMethodChannel)?$ShippingMethodChannel['tracking_url']:'';
                        }                                                
                    }                   
                }
                $data['carrier']        = implode(',', $carrier);                                          //承运人
                $data['carrier_url']     = implode(',', $carrierUrl);                                      //承运人查询网址
                $data['shipping_number']    = implode(',', $trackNumber);                                     //跟踪号
                
            }
        }
        $content = $tmpServer->getTplFieldContent($tmpId,$data);
        return $content;
    }
    

    /**
     * 匹配模板内容
     * @param int $msgId
     */
    public function matchFieldData($msgId = 0)
    {
        $data = [];
        $tmpServer = $this->invokeServer(MsgTemplateHelp::class);
        //准备模板相关资料      
        $msg = AliexpressMsgRelation::where(['id'=>$msgId])->field('aliexpress_account_id,channel_id,msg_type,other_login_id,other_name')->find();
        $data['buyer_name']     = $msg['other_name'];                         //买家名
        $data['buyer_id']       = $msg['other_login_id'];                     //买家loginId
        //获取一条消息详细
        $msg_detail = AliexpressMsgDetail::where(['aliexpress_msg_relation_id'=>$msgId])->field('type_id,summary,channel_id,message_type')->order('gmt_create asc')->find();
        $summary = json_decode($msg_detail['summary'], 1);
        $seller_name = $msg['other_login_id']==$summary['senderLoginId']?$summary['receiverName']:$summary['senderName'];
        $data['seller_id']  = $seller_name;                                                 //卖家账号
        
       
        //获取卖家id
        $seller_email = '';
        if(param($msg, 'aliexpress_account_id')){
            $account = Cache::store('AliexpressAccount')->getTableRecord($msg['aliexpress_account_id']);
            $seller_email = $account['email'];
        }
        $data['seller_email']    = $seller_email;                                          //卖家Email
    
        //获取订单相关信息
        if($msg['msg_type']==2){
            $data['product_name'] = $summary['productName'];//产品名称
            $product_info = AliexpressOnlineOrderDetail::where(['order_id'=>$msg['channel_id'],'product_name'=>$summary['productName']])->field('product_id')->find();
            $data['product_id']   = $product_info['product_id'];//产品ID
            $order_info = AliexpressOnlineOrder::where(['order_id'=>$msg['channel_id']])
            ->field('buyer_login_id,buyer_signer_fullname,order_id,receipt_address,pay_amount,gmt_pay_time,gmt_send_goods_time,logistic_info')
            ->find();
            if(!empty($order_info)){
                $address_info = json_decode($order_info['receipt_address'], 1);
                $recipientAddress = (isset($address_info['address2'])?$address_info['address2'].',':'').
                $address_info['detailAddress'].','.($address_info['city']?$address_info['city'].',':'').
                ($address_info['province']?$address_info['province'].',':'').$address_info['country'];
                $logistic_info = json_decode($order_info['logistic_info'], 1);
                $pay_info = json_decode($order_info['pay_amount'], 1);
                $data['order_url'] = $summary['orderUrl'];
                $data['order_id'] = $order_info['order_id'];                                         //订单ID
                $data['payment_date'] = date('Y-m-d H:i:s',$order_info['gmt_pay_time']);             //付款时间
                $data['delivery_date'] = date('Y-m-d H:i:s',$order_info['gmt_send_goods_time']);     //发货时间
                $data['amount'] = $pay_info['amount'].$pay_info['currencyCode'];                    //付款金额
                $data['recipient_name'] = $address_info['contactPerson'];                            //收件人
                $data['recipient_address'] = $recipientAddress;                                      //收件地址
                $carrier = $carrierUrl = $trackNumber = [];
                if(!empty($logistic_info)){
                    foreach ($logistic_info as $item){
                        $carrier[$item['logisticsNo']]      =   $item['logisticsServiceName'];
                        $trackNumber[$item['logisticsNo']]  =   $item['logisticsNo'];
                        $orderPackage = OrderPackage::where(['shipping_number'=>$item['logisticsNo']])->field('shipping_id')->find();
                        if(!empty($orderPackage)){
                            $ShippingMethodChannel = ShippingMethodChannel::where(['shipping_method_id'=>$orderPackage['shipping_id']])->find();
                            $carrierUrl[$item['logisticsNo']]   =   !empty($ShippingMethodChannel)?$ShippingMethodChannel['tracking_url']:'';
                        }
                    }
                }
                $data['carrier']        = implode(',', $carrier);                                          //承运人
                $data['carrier_url']     = implode(',', $carrierUrl);                                      //承运人查询网址
                $data['shipping_number']    = implode(',', $trackNumber);                                     //跟踪号
    
            }
        }
        return $data;
    }
    
    
    
    public function queryCustomers()
    {
        return  Cache::store('User')->getChannelCustomer(4);
    }

    /**
     * 上传文件
     * @param Request $request
     * @param type $pathName
     * @param type $fileName
     * @return string
     * @throws Exception
     */
    public function uploadFile(Request $request,$pathName,$fileName)
    {
        $filePath = '';
        $file = $request->file($fileName);
        if(!$file){
            throw new Exception('未检测到图片');
        }
        $base_path = ROOT_PATH . 'public' . DS . 'upload' . DS . $pathName;
        
        $dir = date('Y-m-d');
        if (!is_dir($base_path . '/' . $dir) && !mkdir($base_path . DS . $dir, 0777, true)) {
            throw new Exception('目录创建失败');
        }
        $info  = $file->validate(['ext' => 'jpg,gif,png'])->move($base_path . DS . $dir , date('YmdHis'), false);      
        if (empty($info)) {
            throw new Exception($file->getError());
        }
        $filePath = $dir . DS . $info->getFilename();
        return $filePath;
    }
    
    /**
     * 反编译data/base64数据流并创建图片文件
     * @author Lonny ciwdream@gmail.com
     * @param string $baseData  data/base64数据流
     * @param string $Dir           存放图片文件目录
     * @param string $fileName   图片文件名称(不含文件后缀)
     * @return mixed 返回新创建文件路径或布尔类型
    */
    function base64DecImg($baseData, $Dir, $fileName){
        $base_path = ROOT_PATH.'/public/';
        $imgPath = $base_path.'/'.$Dir;
        try{
            if (!is_dir($imgPath) && !mkdir($imgPath, 0777, true)) {
                return false;
            }
            $expData = explode(';',$baseData);
            $postfix   = explode('/',$expData[0]);
            if( strstr($postfix[0],'image') ){
                $postfix   = $postfix[1] == 'jpeg' ? 'jpg' : $postfix[1];
                $storageDir = $imgPath.'/'.$fileName.'.'.$postfix;
                $export = base64_decode(str_replace("{$expData[0]};base64,", '', $baseData));
                try{
                    file_put_contents($storageDir, $export);
                    return $Dir.'/'.$fileName.'.'.$postfix;;
                }catch(Exception $e){
                    return false;
                }
            }else{
                return false;
            }
        }catch(Exception $e){
            return false;
        }
        return false;
    }

    /*public function uploadImgToAli($config,$url)
    {
        $loacimg_url = url($url,'','',true);
        $sendConfig = [
            'client_id'=>$config['client_id'],
            'client_secret'=>$config['client_secret'],
            'access_token'=>$config['accessToken'],
            'refresh_token'=>$config['refreshtoken'],
        ];
        $ApiImages = AliexpressApi::instance()->loader('Images');
        $ApiImages->setConfig($sendConfig);
        $result = $ApiImages->uploadTempImage($loacimg_url);
        @unlink($url);
        if(!isset($result['url'])){
            throw new JsonErrorException('图片上传失败');
        }
        return $result['url'];
    }*/
    public function uploadImgToAli($config,$img_byte,$img_name)
    {
        //$loacimg_url = url($url,'','',true);
        $ApiImages = AliexpressApi::instance($config)->loader('Images');
        $result = $ApiImages->uploadTempImage($img_byte,$img_name);

        $result=$this->dealResponse($result);
        if (isset($result['result']['is_success'])&&$result['result']['is_success']){
            return $result['result']['url'];
        }
        else{
            return false;
        }

    }

    
    /**
     * 根据用户获取被授权平台账号
     * @param type $uid     系统用户ID
     * @param type $channelId       平台ID
     */
    public function getAccountByUid($uid,$channelId=0)
    {
        $return = ['0'];
        $map = [];
        $map['customer_id'] = $uid;
        if($channelId){
            $map['channel_id'] = $channelId;
        }
        $field = 'account_id';
        $infos = ChannelUserAccountMap::where($map)->field($field)->select();
        if(!empty($infos)){
            foreach($infos as $item){
                array_push($return, $item['account_id']);
            }
        }
        return $return;
    }

    /**
     * 联系订单买家
     * @param $params
     * @return array
     */
    public function replayOrder($params)
    {
        $orderId = $params['order_id'];
        $orderModel = new Order();
        $order = $orderModel->where(['id'=>$orderId,'channel_id'=>4])->field('id,buyer_id,channel_account_id,status,buyer,order_number')->find();
        $channel_order_number= substr(strstr($order['order_number'],'-'), 1);
        if(empty($order)){
            throw new MessageException('订单有误或不存在');
        }
        $accountId = $order['channel_account_id'];
        //获取账号授权信息
        $account = Cache::store('AliexpressAccount')->getTableRecord($accountId);

        
        if(empty($account)){
            throw new MessageException('平台账号不存在');
        }
        $config = [
            'id'            =>  $account['id'],
            'client_id'     =>  $account['client_id'],
            'client_secret' =>  $account['client_secret'],
            'accessToken'   =>  $account['access_token'],
            'refreshtoken'  =>  $account['refresh_token'],
        ];
//        $data['seller_id'] = $accountInfo['login_id'] ? $accountInfo['login_id'] : $accountInfo['user_nick'];//获取卖家账号
        $order['seller_id']=$account['login_id']? $account['login_id'] : $account['user_nick'];//获取卖家账号
        $data = [
            'content'=>$params['content'],
            'seller_id'=>$order['seller_id'],
            'msg_type'=>'order',
            'buyer_id'=>$order['buyer_id'],
            'buyer_name'=>$order['buyer'],
            'extern_id'=>$channel_order_number
        ];
        $amRe = $this->addMsg($config, $data);
        $msg = '内容：<'.$params['content'].'>';
        //获取当前操作用户
        $userInfo       =   Common::getUserInfo();
        if($amRe['status']==1){
            $msg = '[发送成功] '.$msg;
            $data = ['status'=>true,'msg'=>'发送成功'];
        }else{
            $msg = '[消息发送失败]'.(isset($amRe['message'])?$amRe['message']:'');
            $data = ['status'=>false,'msg'=>'发送失败'];
        }
        Common::addOrderLog($orderId,$msg,$userInfo['realname'],$order['status'],$userInfo['user_id']);
        return $data;
    }

    /**
     * 获取模板内容
     * @param $orderId
     * @param $tmpId
     * @return mixed
     */
    public function getTempContentForOrder($orderId,$tmpId)
    {
        $orderModel = new Order();
        $user_info = Common::getUserInfo();
        $userModel = User::where(['id'=>$user_info['user_id']])->field('email')->find();
        $tmpServer = $this->invokeServer(MsgTemplateHelp::class);
        //准备模板相关资料
        $data = [];
        $order_field = 'buyer,buyer_id,channel_order_number,pay_time,shipping_time,order_amount,pay_fee,currency_code';
        $order = $orderModel->where(['id'=>$orderId])->with('address')->field('')->find();
        $data['buyer_name']     = $order['buyer'];                         //买家名
        $data['buyer_id']  = $order['buyer_id'];                     //买家loginId
        $data['seller_id']  = '';                                                 //卖家账号
        $data['seller_email']    = $userModel['email'];                                          //卖家Email
        $data['product_name'] = '';//产品名称
        $data['product_id']   = '';//产品ID
        $data['order_url'] = '';
        $data['order_id'] = $order['channel_order_number'];                                         //订单ID
        $data['payment_date'] = date('Y-m-d H:i:s',$order['pay_time']);             //付款时间
        $data['delivery_date'] = date('Y-m-d H:i:s',$order['shipping_time']);     //发货时间
        $data['amount'] = $order['pay_fee'].$order['currency_code'];                    //付款金额
        $data['recipient_name'] = $order['address']['consignee'];                            //收件人
        $address = $order['address']['address2'].$order['address']['address'].$order['address']['city'].$order['address']['province'].
            $order['address']['country_code'];
        $data['recipient_address'] = $address;                                      //收件地址
        $data['carrier']        = '';                                          //承运人
        $data['carrier_url']     = '';                                      //承运人查询网址
        $data['shipping_number']    = '';                                     //跟踪号
        $content = $tmpServer->getTplFieldContent($tmpId,$data);
        return $content;
    }

    /**
     * 获取会话买家相关系统订单
     * @param $id
     */
    public function getRelatedOrders($id)
    {
        $msgInfo = AliexpressMsgRelation::field('aliexpress_account_id,other_login_id')->find(['id'=>$id]);
        if(empty($msgInfo)){
            return [];
        }
        //获取买家相关系统订单
        $orderModel = new Order();
        $where = [
            'channel_account'=> ChannelAccountConst::channel_aliExpress * 10000 + $msgInfo['aliexpress_account_id'],
            'buyer_id'=>$msgInfo['other_login_id']
        ];
        $orders = $orderModel->field('id,country_code,pay_fee,status,channel_order_id,currency_code,order_time,
                    order_number,channel_order_number,pay_time,shipping_time')->where($where)->select();
        if(empty($orders)){
            return [];
        }

        $data = [];
        $result = [];
        $orderProcessModel = new OrderProcess();
        $orderServer = $this->invokeServer(OrderService::class);
        foreach ($orders as $order)
        {
            $status = $orderProcessModel->getStatusName($order['status']);
            $order_sale_info = $orderServer->orderSaleInfo($order['id']);

            $onlineOrderModel=new AliexpressOnlineOrder();
            $logistic_info=$onlineOrderModel->field('logistic_info')->where(['order_id'=>$order['channel_order_number']])->find();
            $logistic_info=json_decode($logistic_info['logistic_info'],true);

            $result['shipping_name'] = '';
            $result['shipping_number'] = '';
            $result['arrival_time'] = '';
            $result['sku'] = '';
            $result['sku_quantity'] = '';
            $shipping_name = '';
            $shipping_number = '';
            //查出包裹号
            $packageList = OrderPackage::alias('p')
                ->join(['shipping_method' => 's'], 's.id=p.shipping_id', 'left')
                ->where(['p.order_id' => $order['id'], 'p.shipping_id' => ['>', 0]])
                ->field('p.shipping_number,s.shortname')
                ->select();

            if (!empty($packageList)) {
                foreach ($packageList as $p => $package) {
                    $shipping_number .= $package['shipping_number'] . ',';
                    $shipping_name .= $package['shortname'] . ',';
                }
            }
            $result['shipping_name'] = rtrim($shipping_name, ',');
            $result['shipping_number'] = rtrim($shipping_number, ',');

            //查出详情
            $details = OrderDetail::where(['order_id' => $order['id']])->field('sku,sku_quantity')->select();
            if (!empty($details)) {
                foreach ($details as $k=>$d) {
                    if ($k == 0) {
                        $result['sku'] = $d['sku'];
                        $result['sku_quantity'] = $d['sku_quantity'];
                    } else {
                        $result['sku'] .= ','. $d['sku'];
                        $result['sku_quantity'] .= ','. $d['sku_quantity'];
                    }
                }
            }


            $data[] = [
                'id'                 => $order['id'],
                'order_id'          => $order['channel_order_id'],
                'order_no'          => $order['order_number'],
                'channel_order_number' => $order['channel_order_number'],
                'country'           => $order['country_code'],
                'order_status'      => $status,
                'pay_amount'        => $order['pay_fee'],
                'currency_code'     => $order['currency_code'],
                'gmt_create'        => $order['order_time'],
                'is_current'        => 0,
                'return'            => $order_sale_info['return'],//退货
                'gmt_send_date'     => $logistic_info['aeopTpLogisticInfoDto'][0]['gmtSend'] ?? '',
                'logistics_no'     => $logistic_info['aeopTpLogisticInfoDto'][0]['logisticsNo'] ?? '',
                'logistics_type_code'     => $logistic_info['aeopTpLogisticInfoDto'][0]['logisticsTypeCode'] ?? '',
                'logistics_service_name'     => $logistic_info['aeopTpLogisticInfoDto'][0]['logisticsServiceName'] ?? '',
                'issue' => AliexpressIssue::where(['parent_order_id'=>$order['channel_order_number']])->value('issue_id', 0),
                'evaluate' => AliexpressEvaluate::where(['parent_order_id'=>$order['channel_order_number']])->value('id', 0),
                'pay_time'          => $order['pay_time'],  //付款时间
                'shipping_time'     => $order['shipping_time'],  //发货日期
                'shipping_number'   => $result['shipping_number'], //追踪号
                'shipping_name'     => $result['shipping_name'], //物流商
                'sku'               => $result['sku'], //SKU
                'sku_quantity'      => $result['sku_quantity'],  //数量
                'arrival_time'      => '', //预计到达时间（没有）
            ];

        }
        return $data;
    }





    /**
     * @desc 处理响应数据
     * @param string $data 执行api请求返回的订单数据json字符串
     * @return array 结果集
     * @author Jimmy <554511322@qq.com>
     * @date 2018-03-19 15:20:11
     */
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
    
    /**
     * 获取客服在某平台所管理的"账号ID"-[店铺id/account_id]
     * @param number $customerId 客服id
     * @param number $channelId 平台id
     */
    public function getCustomerAccount($customerId = 0, $channelId = 1)
    {
        return Cache::store('User')->getCustomerAccount($customerId, $channelId);
    }
    
    /**
     * 统计客服负责的速卖通账号下，未处理的邮件的条数
     * @param bool $is_filter
     * @return array
     */
    public function CountNoReplayMsg($is_filter)
    {
        $channelId = ChannelAccountConst::channel_aliExpress;
        //取得本操作员所管的帐号；
        $allAccount = (new AccountService())->accountInfo($channelId,0,'order',$is_filter);
        $account_ids = [];   //用来装应该显示多少帐号；
        if(!empty($allAccount['account'])) {
            $account_ids = array_unique(array_column($allAccount['account'], 'value'));
        }
        
        $result = [];
        if (!empty($account_ids)) {
            $where['r.deal_status'] = 0;
            $where['a.id'] = ['in', $account_ids];
            
            $relationModel = new AliexpressMsgRelation();
            $field = 'a.id id,a.code code,count(1) count';
            $result = $relationModel->alias('r')
            ->join('aliexpress_account a','r.aliexpress_account_id=a.id','left')
            ->where($where)->field($field)->group('r.aliexpress_account_id')
            ->order('count desc')
//             ->fetchSql(true)
            ->select();
        }
        
        return $result;
    }
    
    /**
     * @desc 拉取站内信详情数据
     * @author wangwei
     * @date 2018-9-27 11:18:44
     * @param array $config
     * @param int $channel_id
     * @param int $last_msg_id
     * @param int $msg_relation_id
     * @return number
     */
    public function getMsgDetailList($config, $channel_id, $last_msg_id, $msg_relation_id=0)
    {
        $api = AliexpressApi::instance($config)->loader('Message');
        $api instanceof \service\alinew\operation\Message;
        
        $data = [
            'relation'=>[
                'last_msg_id'=>$last_msg_id,
                'last_msg_content'=>'',
                'msg_time'=>time(),
                'update_time'=>time()//数据更新时间
            ],
            'detail'=>[]
        ];
        
        //获取站内信详情
        $current_page = 1;//当前页码
        $pageSize = 20;//每页最多条数
        $get_next_page = true;
        $extern_id = '';
        do{
            $res_detail = $api->queryMsgDetailList($extern_id, $channel_id, $pageSize, $current_page);
            $res_detail = $this->dealResponse($res_detail);//站内信详情
            if(!count($res_detail['result']['message_detail_list'])){//如果消息为空，退出循环，不再取值
                break;
            }
            $res_detail = $res_detail['result']['message_detail_list']['message_detail'];
            
            foreach($res_detail as $val_detail){
                //如果当前消息id为要抓取的id，或者小于拿到的最后一条消息id，不再继续
                if($val_detail['id'] < $last_msg_id){
                    $get_next_page = false;
                    break;
                }
                switch ($val_detail['message_type']){
                    case 'order':
                        $data['relation']['has_order'] = 1;
                        break;
                    case 'product':
                        $data['relation']['has_product'] = 1;
                        break;
                    default:
                        $data['relation']['has_other'] = 1;
                        break;
                }
                $detail = [
                    'channel_id'=>$channel_id,//消息通道ID，既关系ID（订单留言直接为订单号）
                    'msg_id'=>param($val_detail,'id'),// aliexpress平台消息ID
                    'gmt_create'=>substr(param($val_detail,'gmt_create'),0,10),// 消息创建时间
                    'sender_name'=>param($val_detail,'sender_name'),// 发送者名字
                    'sender_login_id'=>param($val_detail['summary'],'sender_login_id'),// 发送人loginId
                    'receiver_name'=>param($val_detail['summary'],'receiver_name'),// 接收人名称
                    'message_type'=>param($val_detail,'message_type'),// 消息类别(1:product/2:order/3:member/4:store)
                    'type_id'=>param($val_detail,'extern_id'),// (product/order/member/store)不同的消息类别，type_id为相应的值，如messageType为product,typeId为productId,对应summary中有相应的附属性信，如果为product,则有产品相关的信息
                    'content'=>param($val_detail,'content'),// 消息详细
                    'file_path'=>json_encode($val_detail['file_path_list']),// 图片地址 （json格式）
                    'summary'=>json_encode($val_detail['summary']),// 附属信息  （json格式）
                    'create_time'=>time(),//数据插入时间
                    'update_time'=>time(),//数据更新时间
                ];
                $msg_relation_id && $detail['aliexpress_msg_relation_id'] = $msg_relation_id;//对应aliexpress_msg_relation表ID
                $data['detail'][] = $detail;
                //取最新的消息ID和消息内容，以便更新关系列表
                if($val_detail['id'] > $data['relation']['last_msg_id']){
                    $data['relation']['last_msg_id'] = $detail['msg_id'];
                    $data['relation']['last_msg_content'] = $detail['content'];
                    $data['relation']['msg_time'] = $detail['gmt_create'];
                }
                
            }
            ++$current_page;//累加页码，继续读数据
            
        }while($get_next_page);
        
        return $data;
    }

    /**
     * 触发第一封站内信事件
     * @param array $order
     */
    public function trigger_first_msg_event($other_login_id, $msg_id, $channel_id, $account_id)
    {
        //获取买家相关系统订单
        $orderModel = new Order();
        $where = [
            'channel_account' => ChannelAccountConst::channel_aliExpress * 10000 + $account_id,
            'buyer_id' => $other_login_id,
        ];
        $orders = $orderModel->field('channel_order_number')->where($where)->find();

        $event_name = 'E11';
        $order_data = [
            'channel_id' => ChannelAccountConst::channel_aliExpress,//Y 渠道id
            'account_id' => $account_id,//Y 账号id
            'channel_order_number'=>$orders['channel_order_number'],//Y 渠道订单号
            'receiver'=>$other_login_id,//Y 买家登录ID
            'extra_params'=>[ //N
                'message_id'=>$msg_id,
                'aliexpress_channel_id'=>$channel_id,
            ]
        ];
        (new MsgRuleHelp())->triggerEvent($event_name, $order_data);
    }
}

