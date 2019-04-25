<?php
namespace app\customerservice\controller;

use app\carrier\task\AliSellerAddress;
use app\common\controller\Base;
use app\common\exception\JsonErrorException;
use app\common\model\aliexpress\AliexpressMsgRelation;
use app\common\model\aliexpress\AliexpressMsgDetail;
use app\common\model\aliexpress\AliexpressOnlineOrder;
use app\common\model\aliexpress\AliexpressAccount;
use app\common\model\customerservice\EmailSentList;
use app\common\model\Order;
use app\common\service\Common;
use app\common\service\OrderStatusConst;
use app\common\service\UniqueQueuer;
use app\customerservice\queue\AliExpressMsgQueueNew;
use app\customerservice\service\AliexpressHelp;
use app\common\model\MsgTemplate as MsgTemplateModel;
use app\customerservice\service\MsgTemplateHelp;
use app\customerservice\validate\AliInboxValidate;
use app\common\cache\Cache;
use app\order\task\AliexpressOrder;
use app\publish\task\AliexpressGrabGoods;
use think\Request;
use \Exception;
use think\Db;
use app\common\service\ChannelAccountConst;

/**
 * @module 客服管理
 * @title 速卖通收件箱
 * @url /aliexpress-msg
 * @package app\customerservice\controller
 * @author Tom
 */
class AliexpressMsg extends Base
{
    private $_validate;
    
    public function __construct(Request $request = null) {
        parent::__construct($request);
        $this->_validate = new AliInboxValidate();
    }
    
    /**
     * @title 收件箱列表
     * @apiRelate app\index\controller\MemberShip::member
     * @apiRelate app\order\controller\Order::account
     * @apiRelate app\customerservice\controller\MsgTemplate::getTemplates
     * @apiRelate app\customerservice\controller\AliexpressMsg::getRnakStatistics
     * @apiRelate app\customerservice\controller\AliexpressMsg::getRanks
     * @apiFilter app\customerservice\filter\AliexpressAccountFilter
     * @return type
     */
    public function index()
    {
        $result = [
            'data' => [],
            'page' => 1,
            'pageSize' => 10,
            'count' => 0,
        ];
        
        try {
            $request = Request::instance();
            $page = $request->param('page', 1);
            $pageSize = $request->param('pageSize', 10);
            $params = $request->param();
            if($count = $this->getIndexData($params, 'count(*)')){
                $field = 'distinct(r.id), r.id as aliexpress_msg_relation_id,r.channel_id,r.aliexpress_account_id,r.msg_type,r.unread_count,r.read_status,r.msg_time,r.other_name,r.other_login_id,
            r.deal_status,r.rank,level';
                $order = $request->param('sort', 1)==1 ? 'msg_time desc' : 'msg_time asc';
                $result['data'] =  $this->getIndexData($params, $field, $pageSize, $page, $order);
            }
            $result['count'] = $count;
            $result['page'] = $page;
            $result['pageSize'] = $pageSize;
            return json($result, 200);
            
        } catch (Exception $ex) {
            return json(['message'=>$ex->getMessage(),500]);
        }
        
    }
    
    /**
     * @title 站内信明细
     * @apiParam name:id type:int desc:会话ID
     * @apiReturn array:数组信息@
     * @array channel_id:会话标示 gmt_create:消息时间 sender_name:发送人 receiver_name:接收人 content:内容 message_type:消息类型 file_path:图片 product_url:产品链接 type_id:类型值
     * @param type $id
     * @return type
     */
    public function read($id)
    {
        try {
            $model = new AliexpressMsgDetail();
            if(!$id){
                return json(['message'=>'参数错误'],400);
            }
            $where['aliexpress_msg_relation_id'] = $id;
            $relationInfo = AliexpressMsgRelation::where(['id'=>$id])->field('aliexpress_account_id,other_login_id')->find();
            $account = [];

            //增加aliexpress_account_id不等于0 的判断 等于0会返回全部的account
            if ($relationInfo['aliexpress_account_id'] != 0) {
                $account = Cache::store('AliexpressAccount')->getTableRecord($relationInfo['aliexpress_account_id']);
            }else{
                $account['code'] = '';
            }
            $field = 'channel_id,gmt_create,sender_name,content,message_type,file_path,summary,sender_login_id,receiver_name,type_id,aliexpress_msg_relation_id';
            $messages = $model->field($field)->where($where)->order('gmt_create desc')->select();
            if (empty($messages)) {
                return json(['message' => '没有任何消息'], 400);
            }

            foreach($messages as $k=>$item){
                /*
                 * 处理消息内容中包含图片的问题
                 */
                $file_path=json_decode($item['file_path'], 1);

                $messages[$k]['img']='';
                if(!empty($file_path)){//如果有图片，将图片标识符去掉，并给图片字段赋值路径
                    //$img="<img src='".$file_path['file_path'][0]['m_path']."'/>";
                    $messages[$k]['img']=$file_path['file_path'];
                    $messages[$k]['content']=str_replace("< img >",'',$messages[$k]['content']);
                }


                $summary = json_decode($item['summary'], 1);
                $messages[$k]['file_path'] = json_decode($item['file_path'], 1);
                unset($messages[$k]['summary']);
                if(isset($summary['product_image_url'])&&strpos($summary['product_image_url'],'http://')===false){
                    $summary['product_image_url'] = 'http://ae01.alicdn.com/kf/'.$summary['product_image_url'];
                }
                $messages[$k]['product_url'] = isset($summary['product_detail_url'])?$summary['product_detail_url']:'';
                $messages[$k]['product_img'] = isset($summary['product_image_url'])?$summary['product_image_url']:'';

                $messages[$k]['sender_name'] = $item['sender_login_id']==$relationInfo['other_login_id']?$item['sender_name']:$account['code'];
                $messages[$k]['receiver_name'] = $item['sender_login_id']==$relationInfo['other_login_id']?$account['code']:$item['receiver_name'];
                $messages[$k]['is_own'] = $summary['sender_login_id']==$relationInfo['other_login_id']?0:1;
            }

            return json($messages, 200);
        } catch (Exception $ex) {
            return json(['message'=>'数据异常:'.$ex->getMessage()],500);
        }
    }
    
    /**
     * @title 获取Aliexpress标签
     * @method get
     * @url rank
     * @return \think\Response
     */
    public function getRanks()
    {
        try {
            $ranks = AliexpressMsgRelation::MSG_RANK;
            array_walk($ranks, function(&$value,$key){
                $value = ['id'=>$key,'name'=>$value];
            } );
            return json($ranks,200);
        } catch (Exception $ex) {
            return json(['message'=>'数据异常'],500);
        }
        
    }
    
    /**
     * @title 获取消息明细
     * @method get
     * @url :id(\d+)/detail
     * @param type $id
     * @return type
     */
    public function getDetail($id)
    {
        try {
            if(!$id){
                return json(['message'=>'参数错误'],400);
            }
            $detail = AliexpressMsgDetail::get($id);
            $relationInfo = AliexpressMsgRelation::where(['id'=>$detail['aliexpress_msg_relation_id']])->field('aliexpress_account_id,other_login_id')->find();
            $account = [];
            if ($relationInfo['aliexpress_account_id']) {
                $account = Cache::store('AliexpressAccount')->getTableRecord($relationInfo['aliexpress_account_id']);
            }else{
                $account['code'] = '';
            }

            if(empty($detail)){
                return json(['message' => '消息不存在'], 400);
            }
            $detail['file_path'] = json_decode($detail['file_path'], 1);

            /*
                 * 处理消息内容中包含图片的问题
                 */
            $file_path=$detail['file_path'];
            $detail['img']='';
            if(!empty($file_path)){//如果有图片，将图片标识符去掉，并给图片字段赋值路径
                $detail['img']=$file_path['file_path'];
                $detail['content']=str_replace("< img >",'',$detail['content']);
            }



            $summary = json_decode($detail['summary'], 1);
            $detail['summary'] = $summary;
            $detail['sender_name'] = $summary['sender_login_id']==$relationInfo['other_login_id']?$detail['sender_name']:$account['code'];
            $detail['receiver_name'] = $summary['sender_login_id']==$relationInfo['other_login_id']?$account['code']:$summary['receiver_name'];
            $detail['product_url'] = isset($summary['product_detail_url'])?$summary['product_detail_url']:'';

            if(isset($summary['product_image_url'])&&strpos($summary['product_image_url'],'http://')===false){
                $summary['product_image_url'] = 'http://ae01.alicdn.com/kf/'.$summary['product_image_url'];
            }
            $detail['product_img'] = isset($summary['product_image_url'])?$summary['product_image_url']:'';
            $detail['is_own'] = $summary['sender_login_id']==$relationInfo['other_login_id']?0:1;
            return json($detail, 200);
        } catch (Exception $ex) {
            return json(['message'=>'数据异常'.$ex->getMessage()],500);
        }
    }
    
    /**
     * @title 获取所有标签下站内信数量
     * @method get
     * @url rankStatistics
     * @apiFilter app\customerservice\filter\AliexpressAccountFilter
     * @return \think\Response
     */
    public function getRnakStatistics()
    {
        try {
            $data = AliexpressMsgRelation::MSG_RANK;
            //前端提交过来的查询数据
            $params = $this->request->param();
            //数据查询
            array_walk($data, function(&$value,$key,$params){
                $count = AliexpressMsgRelation::getCountByRank($key,$params);
                $value = ['id'=>$key,'name'=>$value,'count'=>$count];
            },$params);
            array_unshift($data, ['id' => -1, 'name' => '全部', 'count' => AliexpressMsgRelation::getCountByRank(-1, $params)]);
            return json($data,200);
        } catch (Exception $ex) {
            return json(['message'=>'数据异常'],500);
        }
    }
    
    /**
     * @title 获取客服对应的账号
     * @author tanbin
     * @method GET
     * @apiFilter app\customerservice\filter\AliexpressAccountFilter
     * @apiReturn show_all:是否显示全部  ； 显示全部是用下拉框显示所有账号。（1-是 0-否）
     * @apiReturn data:账号信息
     * @url /aliexpress-msg/account
     * @return \think\Response
     */
    public function getAccountList(Request $request)
    {
        try {
            
            $is_filter = true;
            if (strpos($request->header('referer'),'member-ship') !== false) {
                $is_filter = false;
            }
            
            $datas = (new AliexpressHelp())->CountNoReplayMsg($is_filter);
            $result = [
                'data' => $datas,
                'show_all' => 0,
            ];
            
            return json($result);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }
    
    /**
     * @title 获取站内信处理优先级
     * @method get
     * @url level
     * @return \think\Response
     */
    public function getLevel()
    {
        try {
            $ranks = AliexpressMsgRelation::MSG_HANDLE_LEVEL;
            array_walk($ranks, function(&$value,$key){
                $value = ['id'=>$key,'name'=>$value];
            } );
            $ranks = array_values($ranks);
            return json($ranks,200);
        } catch (Exception $ex) {
            return json(['message'=>'数据异常'],500);
        }
    }

    /**
     * @title 获取站内信各优先级下数量
     * @method get
     * @url levelStatistics
     * @return type
     */
    public function getLevelStatistics()
    {
        try {
            $data = AliexpressMsgRelation::MSG_HANDLE_LEVEL;
            array_walk($data, function(&$value,$key){
                $count = AliexpressMsgRelation::getCountByLevel($key);
                $value = ['id'=>$key,'name'=>$value,'count'=>$count];
            });
            array_unshift($data, ['id'=>0,'name'=>'全部','count'=>  AliexpressMsgRelation::getCountByLevel()]);
            return json($data,200);
        } catch (Exception $ex) {
            return json(['message'=>$ex->getMessage()],500);
        }
    }
    
    /**
     * @title 修改优先级
     * @method post
     * @url :id(\w+)/changeLevel/:level(\d+)
     * @param Request $request
     * @return type
     */
    public function changeLevel(Request $request)
    {
        $params = $request->param();
        $check = $this->checkParams($params,'level');
        if(TRUE !== $check){
            return json(['message'=>$check],400);
        }
        //获取通道信息
        $aliexpressChannel = AliexpressMsgRelation::get($params['id']);
        if(empty($aliexpressChannel)){
            return json(['message'=>'相关信息不存在'],200);
        }
        if($aliexpressChannel->update(['level'=>$params['level']],['id'=>$params['id']])){
            return json(['message'=>'操作成功'],200);
        }else{
            return json(['message'=>'操作失败'],200);
        }
        
    }

    /**
     * @title 获取相关订单信息
     * @method get
     * @url :id(\d+)/orders
     * @apiParam name:id type:int desc:会话ID
     * @apiReturn array:相关系统订单数据@
     * @array id:系统订单ID order_id:平台订单ID order_no:系统订单号 country:国家简码 order_status:订单状态 pay_amount:付款金额 currency_code:币种 gmt_create:下单时间
     */
    public function getRelatedOrders($id)
    {
        try {
            if(!$id){
                return json(['message'=>'参数错误'],400);
            }
            $helpService = new AliexpressHelp();
            $result = $helpService->getRelatedOrders($id);
           // var_dump($result);
            return json($result,200);
        } catch (Exception $ex) {
            return json(['message'=>$ex->getMessage()],400);
        }
    }
    
    /**
     * @title 回复消息
     * @method post
     * @url replay
     * @param Request $request
     * @return type
     */
    public function replay(Request $request)
    {
        try {
            $data = [];
            $params = $request->param();

            $check = $this->checkParams($params, 'replay');
            if (TRUE !== $check) {
                return json(['message' => $check], 400);
            }

            $data['channel_id'] = $params['channel_id'];
            $data['content'] = $params['content'];
            #防止重复发送相同的消息
            $userId = param(Common::getUserInfo($request), 'user_id', 0);
            $uniqueKey = md5($params['channel_id'].$data['content']);
            $cache = Cache::handler();
            $key = "ali_msg_replay_$userId:".$uniqueKey;
            if($cache->get($key)){
                throw new Exception('请求过于频繁',400);
            }
            $cache->set($key, 1, 5);
            
            //获取消息通道信息
            $aliexpressChannel = AliexpressMsgRelation::where(['channel_id' => $params['channel_id']])
                ->field('id,aliexpress_account_id,msg_type,other_login_id,other_name')
                ->find();
            if (empty($aliexpressChannel)) {
                return json(['status' => false, 'message' => '未找到要回复的消息', 'data' => []], 400);
            }
            
            //查消息类型和id
            $amd_where = [
                'aliexpress_msg_relation_id'=>$aliexpressChannel['id'],
                'sender_login_id'=>$aliexpressChannel['other_login_id'],
            ];
            $aliexpressMsgDetail = AliexpressMsgDetail::where($amd_where)->field('message_type,type_id')->order('msg_id desc')->find();
            $data['msg_type'] = 'member';
            if(!empty($aliexpressMsgDetail)){
                $data['msg_type'] = param(AliexpressHelp::$msg_type_map, $aliexpressMsgDetail['message_type'], 'member');
                $data['extern_id'] = $aliexpressMsgDetail['type_id'];
            }
            
            // $msg_type = AliexpressMsgRelation::MSG_TYPE;
            $data['id'] = $aliexpressChannel['id'];
            $data['buyer_id'] = $aliexpressChannel['other_login_id'];
            $data['buyer_name'] = $aliexpressChannel['other_name'];
            $data['aliexpress_account_id'] = $aliexpressChannel['aliexpress_account_id'];

            //获取当前Aliexpress账号信息
            $accountInfo = Cache::store('AliexpressAccount')->getTableRecord($aliexpressChannel['aliexpress_account_id']);
            if(empty($accountInfo)) {
                return json(['status' => false, 'message' => '账号错误', 'data' => []], 400);
            }
            $config = [
                'id' => $accountInfo['id'],
                'client_id' => $accountInfo['client_id'],
                'client_secret' => $accountInfo['client_secret'],
                'accessToken' => $accountInfo['access_token'],
                'refreshtoken' => $accountInfo['refresh_token'],
            ];
            $data['seller_id'] = $accountInfo['login_id'] ? $accountInfo['login_id'] : $accountInfo['user_nick'];//获取卖家账号

            $img = $request->param("img");
            $aliexpressHelp = new AliexpressHelp();

            if(!empty($img))
            {
                $strs="QWERTYUIOPASDFGHJKLZXCVBNM1234567890qwertyuiopasdfghjklzxcvbnm";
                $img_name = substr(str_shuffle($strs),mt_rand(0,strlen($strs)-11),10).".jpg";
                $picturedata = $this->base64ToImg($img,3);
                $aliimg_url = $aliexpressHelp->uploadImgToAli($config, base64_decode($picturedata), $img_name);
                if ($aliimg_url) {
                    $data['img_url'] = $aliimg_url;
                } else {
                    return json(['status' => false, 'message' => '图片上传错误', 'data' => []], 400);
                }
            }
 
           /* //上传图片
            $file = $request->file("img");

            $aliexpressHelp = new AliexpressHelp();
            if (isset($file) && $file) {
                $rs = $file->validate(['size' => 3 * 1024 * 1024, 'ext' => 'jpg,jpeg,png,gif'])->move(ROOT_PATH . 'public/upload/aliexpress_msg/' . date("Ymd", time()) . '/', $aliexpressChannel['id'] . '_' . time());
                $img_url = $rs->getPathname();//完整路径
                $img_name = $rs->getFilename();//图片原名
                $picturedata = file_get_contents($img_url);//拿到图片二进制流
                $aliimg_url = $aliexpressHelp->uploadImgToAli($config, $picturedata, $img_name);
                if ($aliimg_url) {
                    $data['img_url'] = $aliimg_url;
                } else {
                    return json(['status' => false, 'message' => '图片上传错误', 'data' => []], 400);
                }
            }*/
            //回复消息
            $aliexpressHelp = new AliexpressHelp();
            $result = $aliexpressHelp->addMsg($config, $data, 1);
            $code = $result['status'] ? 200 : 400;
            $result['message'] =  '回复' . ($result['status'] ? '成功:'  : '失败:' ) . $result['message'];
            return json($result, $code);
        } catch (Exception $ex) {
            return json(['message' => $ex->getMessage()], 400);
        }

    }

    /**
     * @param $data    base64图片数据
     * @param $maxSize   单位M
     */
    private function base64ToImg($data,$maxSize)
    {

        if (strstr($data,",")){
            $image = explode(',',$data);
            $base_img = $image[1];
        }
        return $base_img;
        $img_len = strlen($base_img);
        $file_size = $img_len - ($img_len / 8) * 2;
        $file_size = number_format(($file_size / 1024), 2).'kb';
        if ($file_size > (1024 * 1024 * $maxSize)) {
            return false;
        }
        return $base_img;
    }

    
    /**
     * @title 发送新站内信消息
     * @method post
     * @url add-msg
     * @param Request $request
     * @return type
     */
    public function addMsg(Request $request)
    {
        try {
            $params = $request->param();
            /*
             * 1、参数校验
             */
            if(!$order_no = param($params, 'order_no', '')){
                throw new Exception('平台单号不能为空',400);
            }
            if(!$content = param($params, 'content', '')){
                throw new Exception('消息内容不能为空',400);
            }
            $userId = param(Common::getUserInfo($request), 'user_id', 0);
            $uniqueKey = md5($order_no . $content);
            $cache = Cache::handler();
            $key = "ali_msg_addMsg_$userId:".$uniqueKey;
            if($cache->get($key)){
                throw new Exception('请求过于频繁',400);
            }
            $cache->set($key, 1, 5);
            
            //找对应的订单数据
            $order_data = AliexpressOnlineOrder::where(['order_id' => $order_no])
            ->field('id,account_id,buyer_login_id,buyer_signer_fullname')
            ->find();
            if (empty($order_data)) {
                return json(['status' => false, 'message' => '未找到平台订单', 'data' => []], 400);
            }
            
            //获取当前Aliexpress账号信息
            $accountInfo = Cache::store('AliexpressAccount')->getTableRecord($order_data['account_id']);
            if(empty($accountInfo)) {
                return json(['status' => false, 'message' => '账号错误', 'data' => []], 400);
            }
            $config = [
                'id' => $accountInfo['id'],
                'client_id' => $accountInfo['client_id'],
                'client_secret' => $accountInfo['client_secret'],
                'accessToken' => $accountInfo['access_token'],
                'refreshtoken' => $accountInfo['refresh_token'],
            ];
            $data = [
                'content'=>$content,
                'seller_id'=>$accountInfo['login_id'] ? $accountInfo['login_id'] : $accountInfo['user_nick'],//卖家账号
                'msg_type'=>'order',
                'buyer_id'=>$order_data['buyer_login_id'],
                'buyer_name'=>$order_data['buyer_signer_fullname'],
                'extern_id'=>$order_no
            ];
            
            //上传图片
            $file = $request->file("img");
            if (isset($file) && $file) {
                $rs = $file->validate(['size' => 3 * 1024 * 1024, 'ext' => 'jpg,jpeg,png,gif'])->move(ROOT_PATH . 'public/upload/aliexpress_msg/' . date("Ymd", time()) . '/', $order_data['id'] . '_' . time());
                $img_url = $rs->getPathname();//完整路径
                $img_name = $rs->getFilename();//图片原名
                $picturedata = file_get_contents($img_url);//拿到图片二进制流
                $aliexpressHelp = new AliexpressHelp();
                $aliimg_url = $aliexpressHelp->uploadImgToAli($config, $picturedata, $img_name);
                if ($aliimg_url) {
                    $data['img_url'] = $aliimg_url;
                } else {
                    return json(['status' => false, 'message' => '图片上传错误', 'data' => []], 400);
                }
            }
            
            //发送消息
            $aliexpressHelp = new AliexpressHelp();
            $result = $aliexpressHelp->addMsg($config, $data);
            $result['message'] =  '发送' . ($result['status'] ? '成功:'  : '失败:' ) . $result['message'];
            $code = $result['status'] ? 200 : 400;
            return json($result, $code);
        } catch (Exception $ex) {
            return json(['message' => $ex->getMessage()], 400);
        }
    }

    public function check_image_type($image)
    {
        $bits = array('JPEG' => "\xFF\xD8\xFF", 'GIF' => "GIF", 'PNG' => "\x89\x50\x4e\x47\x0d\x0a\x1a\x0a", 'BMP' => 'BM',);
        foreach ($bits as $type => $bit) {
            if (substr($image, 0, strlen($bit)) === $bit) {
                return $type;
            }
        }
        return 'UNKNOWN IMAGE TYPE';
    }


    /**
     * @title 打标签(已改为奇门接口)
     * @method post
     * @url :id(\w+)/changeRank/:rank(\d+)
     * @param Request $request
     * @return type
     */
    public function changeRank(Request $request)
    {
        $params = $request->param();
        $check = $this->checkParams($params,'rank');
        if(TRUE !== $check){
            return json(['message'=>$check],400);
        }        
        //获取通道信息
        $aliexpressChannel = AliexpressMsgRelation::where(['id'=>$params['id']])
                    ->field('aliexpress_account_id,msg_type,other_login_id,channel_id')
                    ->find();
        if(empty($aliexpressChannel)){
            return json(['message'=>'相关信息不存在'],200);
        }
        //获取当前Aliexpress账号信息
        $config = AliexpressAccount::getAliConfig($aliexpressChannel['aliexpress_account_id']);
        if(empty($config)){
            return json(['message'=>'账号错误'],200);
        }
        $aliexpressHelp = new AliexpressHelp();
        $result = $aliexpressHelp->changeRank($config,['channel_id'=>$aliexpressChannel['channel_id'],'rank'=>$params['rank']]);
        if($result){
            return json(['message'=>'操作成功'],200);
        }else{
            return json(['message'=>'操作失败'],200);
        }
        
    }
    
    /**
     * @title 处理消息(已改为奇门接口)
     * @method post
     * @url batchProcessed
     * @param Request $request
     * @return type
     */
    public function processedMsg(Request $request)
    {
        try {
            if(!$request->has('ids')){
                return json(['message'=>'参数错误'],400);
            }
            $params = $request->param();
            $ids = explode(',', $params['ids']);
            //获取通道信息
            $aliexpressChannel = AliexpressMsgRelation::where(['id'=>['in',$ids]])
                        ->field('channel_id,aliexpress_account_id,msg_type,deal_status,id')
                        ->select();

            if(empty($aliexpressChannel)){
                return json(['message'=>'没有要处理的消息'],200);
            }
            foreach($aliexpressChannel as $item){
                //获取当前Aliexpress账号信息
                $config = AliexpressAccount::getAliConfig($item['aliexpress_account_id']);

                if(empty($config)){
                    return json(['message'=>'账号错误'],200);
                }
                $aliexpressHelp = new AliexpressHelp();
                if($item['deal_status']==AliexpressMsgRelation::DEAL_STATUS_FAIL){
                    $aliexpressHelp->changeDealStat($config,$item['channel_id']);
                }  
            }

            return json(['message'=>'操作成功'],200);
        } catch (Exception $ex) {
            return json(['message'=>$ex->getMessage()],500);
        }
    }

    /**
     * @title 标记消息已读(已改为奇门接口)
     * @method post
     * @url :id(\d+)/readMsg
     * @param type $id
     * @return type
     */
    public function readMsg($id)
    {
        if(!$id){
            return json(['message'=>'参数错误',400]);
        }
        try {
            //获取通道信息
            $aliexpressChannel = AliexpressMsgRelation::where(['id'=>$id])
                        ->field('channel_id,aliexpress_account_id,msg_type,read_status')
                        ->find();
            if(empty($aliexpressChannel)){
                return json(['message'=>'没有要处理的消息'],200);
            }
            //获取当前Aliexpress账号信息
            if ($aliexpressChannel['aliexpress_account_id']!=0) {
                $config =AliexpressAccount ::getAliConfig($aliexpressChannel['aliexpress_account_id']);
            }else{
                return json(['message' => '该消息未关联账号', 200]);
            }
            if(empty($config)){
                return json(['message'=>'账号错误'],200);
            }
            $aliexpressHelp = new AliexpressHelp();
            if($aliexpressChannel['read_status']==AliexpressMsgRelation::NO_READ){
                $aliexpressHelp->readMsg($config,$aliexpressChannel['channel_id']);
                return json(['message'=>'处理成功'],200);
            }else{
                return json(['message'=>'已读消息不需要处理'],200);
            }  
            
        } catch (Exception $ex) {
            return json(['message'=>$ex->getMessage()],500);
        }
        
    }
    
    /**
     * @title 获取回复模板内容
     * @method get
     * @url tmpContent
     * @param Request $request
     * @return type
     */
    public function getTemplateDetail(Request $request)
    {
        try {
            $param = $request->param();
            $check = $this->checkParams($param,'tmp');
            if(TRUE !== $check){
                return json(['message'=>$check],400);
            }
            $help = new MsgTemplateHelp();
            if($param['type']==1){
                $tmp_id = $param['code'];
            }else{
                $tmp_info = MsgTemplateModel::where(['template_no'=>$param['code']])->field('id')->find();
                if(empty($tmp_info)){
                    return json(['message'=>'模板不存在'],404);
                }
                $tmp_id = $tmp_info['id'];
            }
            if (isset($param['order_id']) && $param['order_id'] ) {
                $orderId = $param['order_id'];
            }else{
                $msg = AliexpressMsgRelation::where(['id'=>$param['id']])->field('channel_id,msg_type,other_login_id,other_name')->find();
                $order = AliexpressMsgDetail::where('channel_id',$msg['channel_id'])->where('message_type',2)->field('type_id')->order('msg_id desc')->find();
                $orderId = $order['type_id'];
            }

            $params=[
                'template_id'=>$tmp_id,
                'channel_id'=>4,
                'search_id'=> $orderId,
                'search_type'=>'channel_order',
                'transform'=>'1',
            ];
            $res= $help->matchTplContent($params);
            return json(['content'=>$res],200);
        } catch (Exception $ex) {
            return json($ex->getMessage(),500);
        }
        
    }
    
    /**
     * @title 获取客服
     * @method get
     * @url customer
     * @return type
     */
    public function getCustomers()
    {
        try {
            $help = new AliexpressHelp();
            $result = $help->queryCustomers();
            return json($result,200);
        } catch (Exception $ex) {
            return json(['message'=>$ex->getMessage()],500);
        }        
    }

    /**
     * @title 联系订单买家
     * @method post
     * @apiParam name:order_id type:int desc:系统订单ID
     * @apiParam name:content type:string desc:发送内容
     * @apiReturn message:提示信息
     * @param Request $request
     * @return \think\response\Json
     */
    public function replayOnOrder(Request $request)
    {
        try{
            $params = $request->post();
            $check = $this->checkParams($params,'order_replay');
            if(true !== $check){
                return json(['message'=>$check],400);
            }
            $helpService = new AliexpressHelp();
            $result = $helpService->replayOrder($params);
            $code = 200;
            if(!$result['status']){
                $code = 400;
            }
            return json(['message'=>$result['msg']],$code);
        }catch(Exception $ex){
            return json(['message'=>$ex->getMessage()],500);
        }
    }

    /**
     * @title 获取联系买家模板内容
     * @method get
     * @url temp-detail-order
     * @apiParam name:id type:int desc:订单ID
     * @apiParam name:code type:string|int desc:模板ID或code
     * @apiParam name:type type:int desc:获取方式,1模板id/2模板编号
     * @apiReturn content:模板内容
     * @param Request $request
     * @return \think\response\Json
     */
    public function getTempDetailForOrder(Request $request)
    {
        try {
            $param = $request->param();
            $check = $this->checkParams($param,'tmp');
            if(true !== $check){
                return json(['message'=>$check],400);
            }
            $help = new AliexpressHelp();
            if($param['type']==1){
                $tmp_id = $param['code'];
            }else{
                $tmp_info = MsgTemplateModel::where(['template_no'=>$param['code']])->field('id')->find();
                if(empty($tmp_info)){
                    return json(['message'=>'模板不存在'],404);
                }
                $tmp_id = $tmp_info['id'];
            }
            $content = $help->getTempContentForOrder($param['id'],$tmp_id);
            return json(['content'=>$content],200);
        } catch (Exception $ex) {
            return json(['message'=>$ex->getMessage()],500);
        }
    }

    /**
     * @title 展开更多消息
     * @method get
     * @url more-msg
     * @apiParam name:page type:int desc:当前页数
     * @apiParam name:pageSize type:int desc:每页记录数
     * @apiParam name:id type:int desc:会话ID
     * @apiParam name:time type:int desc:时间
     * @param Request $request
     * @return \think\response\Json
     */
    public function getMoreMsg(Request $request)
    {
        try{
            $model = new AliexpressMsgDetail();
            $page = $request->param('page', 1);
            $pageSize = $request->param('pageSize', 5);
            $relation_id = $request->param('id',0);
            $time = $request->param('time',0);
            if(!$relation_id||!$time){
                throw new JsonErrorException('参数错误');
            }
            $relation = AliexpressMsgRelation::get($relation_id);
            if(empty($relation)){
                throw new JsonErrorException('会话不存在');
            }
            $account = Cache::store('AliexpressAccount')->getTableRecord($relation['aliexpress_account_id']);
            $where = ['aliexpress_msg_relation_id'=>$relation_id,'gmt_create'=>['<',$time]];
            $count = $model->where($where)->order('gmt_create desc')->count();
            $details = $model->where($where)->order('gmt_create desc')->page($page,$pageSize)->select();
            $list = [];
            foreach ($details as $i=>$v){
                $list[$i]['id'] = $v['id'];
                $list[$i]['sender_name'] = $v['sender_login_id']==$relation['other_login_id']?$v['sender_name']:$account['code'];
                $list[$i]['receiver_name'] = $v['sender_login_id']==$relation['other_login_id']?$account['code']:$v['receiver_name'];
                $list[$i]['content'] = $v['content'];
                $list[$i]['gmt_create'] = $v['gmt_create'];
                $list[$i]['type_id'] = $v['type_id'];
                $list[$i]['message_type'] = $v['message_type'];

                $summary = json_decode($v['summary'], 1);
                if(isset($summary['product_image_url'])&&strpos($summary['product_image_url'],'http://')===false){
                    $summary['product_image_url'] = 'http://ae01.alicdn.com/kf/'.$summary['product_image_url'];
                }
                $list[$i]['product_url'] = isset($summary['product_detail_url'])?$summary['product_detail_url']:'';
                $list[$i]['product_img'] = isset($summary['product_image_url'])?$summary['product_image_url']:'';
                $list[$i]['is_own'] = $summary['sender_login_id'] == $relation['other_login_id'] ? 0 : 1;
            }
            $result = [
                'data' => $list,
                'page' => $page,
                'pageSize' => $pageSize,
                'count' => $count,
            ];
            return json($result, 200);
        }catch (Exception $exception){
            throw new JsonErrorException($exception->getMessage().$model->getLastSql());
        }
    }

    /**
     * @title 测试同步
     * @url testSyn
     */
    public function testSyn()
    {
        set_time_limit(0);
        try {
            //$server = new AliexpressMsgSyn();
            //$server = new \app\customerservice\task\AliIssue();
            //$server = new \app\order\task\AliexpressOrder();
            //$server = new \app\order\task\AliexpressToLocal();
            //$server = new \app\carrier\task\AliShippingMethod();
            //$server = new \app\order\task\AliexpressRefreshToken();
            //$server = new \app\customerservice\task\AliEvaluate();
            //$server = new \app\customerservice\task\AliexpressMsgSyn();
            //$server = new \app\order\task\AliexpressUpdateOrder();
            //$server = new \app\order\task\AmazonFeedback();
            //$server = new \app\order\task\AliUpdateWaitSend();
            //$server = new AliexpressGrabCategory();
            //$server = new AliexpressCategoryHasSize();
            $server = new AliexpressGrabGoods();
            //$server = new AliexpressGrabAccountCategoryPower();
            //$server = new AliexpressGrabProductGroup();
            //$server = new AliUpdateOrder();
            //$server = new AliSellerAddress();
            $server->execute();die('ok');
        } catch (Exception $ex) {
            print_r($ex->getMessage());
        }
    }

    /**
     * 获取列表筛选条件
     * @param Request $request
     * @return array
     */
    protected function getFilterCondition($params)
    {
        $where = $join = [];
        //处理状态
        if(isset($params['status'])&&$params['status']!==''){
            if($params['status']==2){
                $where['r.deal_status'] = 0;
                $where['r.msg_time'] = ['lt',  (time()-172800)];
            }else{
                $where['r.deal_status'] = $params['status'];
            }            
        }
        //标签
        if(isset($params['rank'])&&$params['rank']>=0){
            $where['r.rank'] = $params['rank'];
        }
        //处理优先级
        if(isset($params['level'])&&$params['level']){
            $where['r.level'] = $params['level'];
        }
        //是否已读        
        if(isset($params['read'])&&$params['read']!==''){
            $where['r.read_status'] = $params['read'];
        }
        //店铺账号
        if(isset($params['account_id'])&&$params['account_id']!==''){
            $account_id_arr = explode(',', $params['account_id']);
            if(count($account_id_arr) == 1){
                $where['r.aliexpress_account_id'] = $account_id_arr[0];
            }else{
                $where['r.aliexpress_account_id'] = ['in',$account_id_arr];
            }
        }
        //时间
        if(!empty($params['start']) && !empty($params['end'])){
            $where['r.msg_time']  =  ['BETWEEN', [strtotime($params['start']), strtotime($params['end'])]];
        }elseif (!empty($params['start'])) {
            $where['r.msg_time']  =  ['EGT',strtotime($params['start'])];
        }elseif (!empty($params['end'])) {
            $where['r.msg_time']  =  ['ELT',strtotime($params['end'])];
        }
        //客服账号
        if(isset($params['customer_id'])&&$params['customer_id']){
            $where['r.owner_id'] = $params['customer_id'];
        }
        //消息类别
        if(isset($params['msg_type'])&&$params['msg_type']){
            switch($params['msg_type']){
                case 1:
                    $where['r.has_product'] = 1;
                    break;
                case 2:
                    $where['r.has_order'] = 1;
                    break;
                case 3:
                    $where['r.has_order'] = 1;
                    $where['o.status'] = OrderStatusConst::ForDistribution;
                    $join['aliexpress_msg_detail'] = ['aliexpress_msg_detail d','r.id=d.aliexpress_msg_relation_id','left'];
                    $join['order'] = ['order o','d.type_id=o.channel_order_number','left'];
                    break;
                case 4:
                    $where['r.has_other'] = 1;
                    break;
                default:
                    break;
            }
        }
        //关键字
        if(isset($params['filter_type']) && isset($params['filter_text']) && !empty($params['filter_text'])){
            switch ($params['filter_type'])
            {
                case 'order_id'://系统订单号
                    $join['aliexpress_msg_detail'] = ['aliexpress_msg_detail d','r.id=d.aliexpress_msg_relation_id','left'];
                    //$where['r.has_other'] = 1;
//                     $where['r.has_order'] = 1;
                    $order_id_arr = explode('-', $params['filter_text']);
                    $where['d.type_id'] = ['like',(isset($order_id_arr[1]) ? $order_id_arr[1] : $order_id_arr[0]) . '%'];
                    break;
                case 'channel_order_id'://平台订单号
                    $join['aliexpress_msg_detail'] = ['aliexpress_msg_detail d','r.id=d.aliexpress_msg_relation_id','left'];
                    //$where['r.has_other'] = 1;
//                     $where['r.has_order'] = 1;
                    $where['d.type_id'] = ['like',$params['filter_text'].'%'];
                    break;
                case 'channel_order_id_eq'://平台订单号(等于)
                    $join['aliexpress_msg_detail'] = ['aliexpress_msg_detail d','r.id=d.aliexpress_msg_relation_id','left'];
                    //$where['r.has_other'] = 1;
//                     $where['r.has_order'] = 1;
                    $where['d.type_id'] = $params['filter_text'];
                    break;
                case 'buyer_id':
                    $where['r.other_login_id|other_name'] = ['like','%'.$params['filter_text'].'%'];
                    break;
                default :
                    break;
            }
        }
        return ['where'=>$where,'join'=>array_values($join)];
    }
    
    /**
    * 验证传入参数
    * @param array $params
    * @param string $scene
    * @return boolean
    */
    private function checkParams($params,$scene)
    {
        $result = $this->_validate->scene($scene)->check($params);
        if (true !== $result) {
            // 验证失败 输出错误信息
            return '参数验证失败：' . $this->_validate->getError();
        }
        return TRUE;
    }

    /**
     * @title 同步站内信
     * @method post
     * @url sync
     * @param Request $request
     * @return \think\response\Json
     * @author Reece
     * @date 2018-09-03 19:03:41
     */
    public function sync(Request $request)
    {
        try{
            $id = $request->post('account_id');
            if(empty($id)) throw new Exception('请选择一个账号');
            $accountInfo = Cache::store('AliexpressAccount')->getTableRecord($id);
            if(empty($accountInfo)) throw new Exception('账号不存在');
            if($accountInfo['is_invalid'] && $accountInfo['is_authorization'] && $accountInfo['download_message'] > 0){
                $data = [
                    'id' => $id,
                    'task_type' => 1
                ];
                (new UniqueQueuer(AliExpressMsgQueueNew::class))->push(json_encode($data));
                return json(['message'=>'操作成功'],200);
            }else{
                throw new Exception('账号未生效');
            }
        }catch (Exception $ex){
            return json(['message'=>$ex->getMessage()],400);
        }
    }
    
    /**
     * @desc 
     * @author wangwei
     * @date 2018-10-25 16:47:59
     * @param array $condition
     * @param string $field
     * @param int $pageSize
     * @param unknown $page
     * @param unknown $order
     * @return unknown|string|unknown
     */
    private function getIndexData($condition, $field, $pageSize=10, $page=1, $order=null){
        $arrFilter = $this->getFilterCondition($condition);
        $where = $arrFilter['where'];
        $join = $arrFilter['join'];
        $relationModel = new AliexpressMsgRelation();
        if($field=='count(*)'){
            if (!empty($join)) {
                $count = $relationModel->alias('r')->join($join)->where($where)->count('distinct(r.id)');
            } else {
                $count = $relationModel->alias('r')->where($where)->count();
            }
            return $count;
        }
        if (!empty($join)) {
            $list = $relationModel->with('detail')->alias('r')->join($join)->where($where)->field($field)->order($order)->page($page, $pageSize)->select();
        } else {
            $list = $relationModel->with('detail')->alias('r')->field($field)->where($where)->order($order)->page($page, $pageSize)->select();
        }
        if(!empty($list)){
            foreach($list as $k=>$item){
                $count_msg = 0;
                $account = [];
                if ($item['aliexpress_account_id'] !=0) {
                    $account = Cache::store('AliexpressAccount')->getTableRecord($item['aliexpress_account_id']);
                }else {
                    $account['code'] = '';
                }
                $list[$k]['account_code'] = $account['code'];
                $details = $item->detail;
                
                $msg_list = [];
                $message_type = null;
                if(!empty($details)){
                    foreach ($details as $i=>$v){
                        if($i>0){
                            continue;
                        }
                        $msg_list[$i]['id'] = $v['id'];
                        $msg_list[$i]['sender_name'] = $v['sender_login_id']==$item['other_login_id']?$v['sender_name']:$account['code'];
                        $msg_list[$i]['receiver_name'] = $v['sender_login_id']==$item['other_login_id']?$account['code']:$v['receiver_name'];
                        $msg_list[$i]['content'] = $v['content'];
                        $msg_list[$i]['gmt_create'] = $v['gmt_create'];
                        $msg_list[$i]['type_id'] = $v['type_id'];
                        $msg_list[$i]['message_type'] = $v['message_type'];
                        
                        $summary = json_decode($v['summary'], 1);
                        if(isset($summary['product_image_url'])&&strpos($summary['product_image_url'],'http://')===false){
                            $summary['product_image_url'] = 'http://ae01.alicdn.com/kf/'.$summary['product_image_url'];
                        }
                        $msg_list[$i]['product_url'] = isset($summary['product_detail_url'])?$summary['product_detail_url']:'';
                        $msg_list[$i]['product_img'] = isset($summary['product_image_url'])?str_replace('.jpg_120x120','',$summary['product_image_url']):'';
                        $msg_list[$i]['is_own'] = $summary['sender_login_id']==$item['other_login_id']?0:1;
                        $list[$k]['time'] = $v['gmt_create'];
                        $list[$k]['message_type'] = $v['message_type'];
                        $list[$k]['type_id'] = $v['type_id'];
                        $message_type = $v['message_type'];
                    }
                }
                unset($list[$k]['detail']);
                $list[$k]['msg_list'] = $msg_list;
                $list[$k]['count_msg'] = count($details);
                /*
                 * 查订单id和订单号
                 * wangwei 2018-9-15 14:04:20
                 */
                $list[$k]['order_id'] = '';
                $list[$k]['order_number'] = '';
                if($message_type=='2'){
                    $order_where = [
                        'channel_account'=> ChannelAccountConst::channel_aliExpress * 10000 + $list[$k]['aliexpress_account_id'],
                        'channel_order_number'=>$list[$k]['type_id']
                    ];
                    if($order = Order::field('id,order_number')->where($order_where)->find()){
                        $list[$k]['order_id'] = $order['id'];
                        $list[$k]['order_number'] = $order['order_number'];
                    }
                }
            }
        }
        return $list;
    }
    
    /**
     * @title 根据平台订单号获取站内信消息
     * @method get
     * @url order/:order_no(\d+)
     * @param Request $request
     * @return \think\response\Json
     * @author wangwei
     * @date 2018-10-25 16:15:30
     */
    public function getMsgByOrderNo($order_no)
    {
        $result = [
            'data' => [],
        ];
        try {
            $condition = [
                'filter_type'=>'channel_order_id_eq',
                'filter_text'=>$order_no,
            ];
            $field = 'distinct(r.id), r.id as aliexpress_msg_relation_id,r.channel_id,r.aliexpress_account_id,r.msg_type,r.unread_count,r.read_status,r.msg_time,r.other_name,r.other_login_id,
            r.deal_status,r.rank,level';
            $result['data'] = $this->getIndexData($condition, $field, 10, 1 , 'msg_time desc');
            return json($result, 200);
            
        } catch (Exception $ex) {
            return json(['message'=>$ex->getMessage(),500]);
        }
    }
    
}

