<?php

namespace app\customerservice\queue;

use app\common\cache\driver\AliexpressMsgDetail;
use think\Exception;
use app\common\cache\Cache;
use \service\alinew\AliexpressApi;
use app\common\service\SwooleQueueJob;
use app\order\service\AliOrderServer;
use app\common\model\aliexpress\AliexpressMsgRelation;
use app\index\service\ChannelUserAccountMap;
use app\common\service\ChannelAccountConst;


/**
 * @desc 速卖通站内信
 * @author johnny <1589556545@qq.com>
 * @date 2018-05-04 14:30:11
 */
class AliExpressMsgQueueNew extends SwooleQueueJob
{

    //站内信关系列表参数
    private $page_size = 100;//每页条数,pageSize取值范围(0~100) 最多返回前5000条数据
    private $current_page = 1;//当前页码
    private $start_time = null;//会话时间查询范围－截至时间，如果不填则取当前时间，从1970年起计算的毫秒时间戳
    private $end_time = null;//会话时间查询范围－截至时间，如果不填则取当前时间，从1970年起计算的毫秒时间戳
    private $only_un_dealed = null;//是否只查询未处理会话
    private $only_un_readed = null;//是否只查询未读会话
    private $rank = null;//标签值(0,1,2,3,4,5)依次表示为白，红，橙，绿，蓝，紫
    private $seller_id = null;//指定查询某帐号的会话列表，如果不填则返回整个店铺所有帐号的会话列表
    private $endTime = 0; //抓取订单更新的结束时间
    private $channel_id = null;//要抓取的会话通道id
    private $execute_time = 0;
    private $is_continue = true;

    /**
     * @desc 作者
     * @author Johnny <1589556545@qq.com>
     * @date 2018-05-12 14:43:11
     * @return string
     */

    public function getAuthor(): string
    {
        return 'Johnny';
    }

    /**
     * @desc 描述
     * @author johnny <1589556545@qq.com>
     * @date 2018-05-04 14:42:11
     * @return string
     */
    public function getDesc(): string
    {
        return '速卖通新接口抓取站内信';
    }

    /**
     * @desc 获取队列名称
     * @author johnny <1589556545@qq.com>
     * @date 2018-05-04 14:41:11
     * @return string
     */
    public function getName(): string
    {
        return '速卖通抓取站内信';
    }

    public static function swooleTaskMaxNumber():int
    {
        return 30;
    }

    /**
     * @desc 执行
     * @author johnny <1589556545@qq.com>
     * @date 2018-05-04 14:40:11
     */
    public function execute()
    {
        try {
            $this->is_continue = true;
            $this->current_page = 1;
            //设置执行不超时
            set_time_limit(0);
            
            //获取执行的参数信息
            $params = $this->getParams();
            
            //获取账号信息
            $config = $this->getConfig($params['id']);

            //站内信关系列表
            $this->getMsgRelationList($config, $params);

        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }


    /**
     * @desc 通过API抓取速卖通站内信关系列表
     * @param array $config 配置信息
     * @param array $params 运行时的参数信息
     * @author johnny <1589556545@qq.com>
     * @date 2018-05-04 15:20:11
     */

    private function getMsgRelationList($config, $params)
    {
        //记录站内信执行时间
        $execute_time = date('Y-m-d H:i:s', time());
        Cache::store('AliexpressAccount')->taskMsgExecuteTime($config['id'], $execute_time);
        $api = AliexpressApi::instance($config)->loader('Message');
        $api instanceof \service\alinew\operation\Message;
        
        $count_all = 0;
        $count = true;
        $syncTime = 0; //用来记录账号的拉取时间
        $last_msg_time = 0;//最新一条消息的发送时间
        do {
            //  Cache::store('AliexpressAccount')->taskMsgTestTime($config['id']."_1", '开始向速卖通抓单');
            //调用SDK获取关系列表的数据
            /*
             * 参数顺序错误，调整成正确的顺序
             * wangwei 2018-9-20 15:36:03
             */
//             $res = $api->querymsgrelationlist($this->page_size, $this->current_page, $this->start_time, $this->end_time, $this->only_un_dealed, $this->only_un_readed, $this->rank, $this->seller_id);
            $res = $api->querymsgrelationlist($this->page_size, $this->current_page, $this->start_time, $this->only_un_dealed, $this->only_un_readed, $this->rank, $this->seller_id, $this->end_time);
            
            //  Cache::store('AliexpressAccount')->taskMsgTestTime($config['id']."_2", "抓到结果");
            $res = $this->dealResponse($res, [[$api, 'querymsgrelationlist'], [$this->page_size, $this->current_page, $this->start_time, $this->only_un_dealed, $this->only_un_readed, $this->rank, $this->seller_id, $this->end_time]]);//对象转数组

            $res = isset($res['result']) ? $res['result'] : '';//两层result结果包起来的，需要去掉一层
            if (!$res || !$res['is_success'] || $res['error_code'] !== 0) {
                //如果抓取失败或没有抓取到数据信息就跳过
                break;
            }
            if (!count($res['relation_list'])) {
                //echo "没有要抓取的数据！";
                $count = false;
                break;
            }

            /*$messageTimeLast = Cache::store('AliexpressMsgDetail')->getLastMessageTime($config['id']); //当前账号最近的messageTime 用来去重
            $tempTime = $messageTimeLast; //临时存储，保证原来从缓存里面取出的数据不变*/

            $syncTime = $this->endTime ? $this->endTime : date('Y-m-d H:i:s');
            //  Cache::store('AliexpressAccount')->taskMsgTestTime($config['id']."_3", "开始组装数据");

            //记录最新一条消息的发生时间
            if ($this->current_page == 1)//取第一页第一条消息的发送时间，为最新时间
            {
                $last_msg_time = $res['relation_list']['relation_result'][0]['message_time'];
            }

            $this->insertRelactionList($res['relation_list']['relation_result'], $config['id'], $api);//将抓取到的关系列表数据进行封装入库
            if (!$this->is_continue) {
                break;
            }

            $page_count = count($res['relation_list']['relation_result']);//本页抓取到的数据条数

            $count_all = $count_all + $page_count;

            if ($page_count < $this->page_size || $page_count < 1) {//如果不足每页应有条数，或者没有条数，则说明本次任务抓取完了，结束循环
                $count = false;
            }
            ++$this->current_page;//累加，取下一页数据
        } while ($count);//
        // echo "共".$count_all."条数据<br/>";
        // echo "共".($this->current_page-1)."页";
        //设置每个账号的更新时间
        if ($syncTime) {
            $params['task_type'] = isset($params['task_type']) ? $params['task_type'] : 1;
            $this->setSyncTime($syncTime, $last_msg_time, $params['task_type'], $config['id']);
        }
    }

    /**
     * @desc 设置账号的抓取时间
     * @author Jimmy <554511322@qq.com>
     * @date 2018-03-21 14:32:11
     */
    private function setSyncTime($syncTime, $last_msg_time, $taskType, $accountId)
    {
        $time = $taskType == 2 ? ['un_dealed' => $syncTime] : ['aliexpressMsg' => $syncTime, 'last_msg_time' => $last_msg_time];
        Cache::store('AliexpressAccount')->taskMsgTime($accountId, $time);
    }

    private function insertRelactionList($res, $accountId, $api)
    {
        foreach ($res as $val) {
            //如果是抓取指定channel_id，找到这个channel_id后不再调用获取会话关系接口
            if($this->channel_id && param($val, 'channel_id') == $this->channel_id){
                $this->is_continue = false;
            }
            $data = [];
            $data['relation'] = $this->getRelation($val, $accountId);//关系列表，拿到组装后的数据
            $model = new AliexpressMsgRelation();
            $before_last_msg_id = Cache::store('AliexpressMsg')->getLastMessageId($accountId, $val['channel_id']);  //拿到上次抓取的最后一条消息ID
            if ($before_last_msg_id && $before_last_msg_id == $val['last_message_id'])//如果该channel_id已经存表，且表中最后一条消息ID已抓取到的最后一条消息ID相等，则表明没有新的消息需要存库
            {
                if ($val['message_time'] < time() - 14*3600*24) {//如果该消息时间小于上次抓的最新消息时间，则表明没有新消息需要抓，跳过
                    $this->is_continue = false;
                    break;
                }
                $read_continue = 0;
            } else {
                $read_continue = 1;
            }
            //获取站内信详情
            $current_page = 1;//当前页码
            $pageSize = 20;//每页最多条数
            $extern_id = '';//拓展ID
            $resCount = 20;
            $data['detail'] = [];//清空上个通道存的详情列表
            while($read_continue && $resCount == $pageSize) {
                $res_detail = $api->queryMsgDetailList($extern_id, $val['channel_id'], $pageSize, $current_page);
                $res_detail = $this->dealResponse($res_detail,[[$api,'queryMsgDetailList'], [$extern_id,$val['channel_id'],$pageSize,$current_page]]);//站内信详情
                if (!count($res_detail['result']['message_detail_list'])) {//如果消息为空，退出循环，不再取值
                    break;
                }
                $res_detail = $res_detail['result']['message_detail_list']['message_detail'];
                $resCount = count($res_detail);
                foreach ($res_detail as $val_detail) {
                    if ($val_detail['id'] == $val['last_message_id']) {
                        $read_continue = 2;
                    }
                    //对比组装aliexpress_msg_detail 表数据字段
                    if($before_last_msg_id && $val_detail['id']<=$before_last_msg_id) {//如果消息ID小于上次拿的最后一条消息ID，则表明该消息已经抓取过，不需要重复存库
                        continue;
                    }
                    switch ($val_detail['message_type']) {
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
                    $data['detail'][] = $this->getMsgDetail($val_detail, $val['channel_id']);
                }
                ++$current_page;//累加页码，继续读数据
            }
            if (1 == $read_continue) {
                continue;
            }
            $model->add($data, $accountId);//数据组装好入库
        }

    }

    private function getMsgDetail($val, $channel_id)
    {
        $data = [];

        //  $data['aliexpress_msg_relation_id']=$accountId;//对应aliexpress_msg_relation表ID
        $data['channel_id'] = $channel_id;//消息通道ID，既关系ID（订单留言直接为订单号）
        $data['msg_id'] = param($val, 'id');// aliexpress平台消息ID
        $data['gmt_create'] = substr(param($val, 'gmt_create'), 0, 10);// 消息创建时间
        $data['sender_name'] = param($val, 'sender_name');// 发送者名字
        $data['sender_login_id'] = param($val['summary'], 'sender_login_id');// 发送人loginId
        $data['receiver_name'] = param($val['summary'], 'receiver_name');// 接收人名称
        $data['message_type'] = param($val, 'message_type');// 消息类别(1:product/2:order/3:member/4:store)
        $data['type_id'] = param($val, 'extern_id');// (product/order/member/store)不同的消息类别，type_id为相应的值，如messageType为product,typeId为productId,对应summary中有相应的附属性信，如果为product,则有产品相关的信息
        $data['content'] = param($val, 'content');// 消息详细
        $data['file_path'] = json_encode($val['file_path_list']);// 图片地址 （json格式）
        $data['summary'] = json_encode($val['summary']);// 附属信息  （json格式）

        /*$data['create_time']='';//数据插入时间
        $data['update_time']='';//数据更新时间*/
        return $data;
    }

    /**
     * 组装关系列表数据
     * @param $val
     * @param $accountId
     * @return array
     */
    private function getRelation($val, $accountId)
    {
        $data = [];
        $data['channel_id'] = param($val, 'channel_id');//消息通道ID，既关系ID（订单留言直接为订单号）
        $data['aliexpress_account_id'] = $accountId;//平台账号ID，对应aliexpress_account表ID
        $data['msg_type'] = 1;// 消息类型。1和2分别表示message_center站内信；order_msg订单留言（因为速卖通参数没有分，这里默认定为1）
        $data['unread_count'] = param($val, 'unread_count');// 未读数
        $data['read_status'] = param($val, 'read_stat');// 未读状态。0未读；1已读
        $data['last_msg_id'] = param($val, 'last_message_id');// 最后一条消息ID
        $data['last_msg_content'] = param($val, 'last_message_content');// 最后一条消息内容
        $data['last_is_own'] = param($val, 'last_message_is_own');// 最后一条消息是否自己这边发送。1是；0不是
        $data['child_name'] = param($val, 'child_name');// 消息所属账号名字
        $data['msg_time'] = substr(param($val, 'message_time'), 0, 10);// 最后一条消息时间
        $data['child_id'] = param($val, 'child_id');// 消息所属账号ID
        $data['order_id'] = param($val, 'order_id');// 订单ID
        $data['other_name'] = param($val, 'other_name');// 与当前卖家或子账号建立关系的买家名字
        $data['other_login_id'] = param($val, 'other_login_id');// 与当前卖家或子账号建立关系的买家账号
        $data['other_ali_id'] = param($val, 'other_ali_id');// 与当前卖家或子账号建立关系的买家ID
        $data['deal_status'] = param($val, 'deal_stat');// 处理状态。0未处理；1已处理
        $data['level'] = 0;//*
        $data['has_other'] = 0;// 是否包含其他留言。如member、store
        $data['has_order'] = 0;// 是否包含订单留言
        $data['has_product'] = 0;// 是否包含产品留言

        $data['rank'] = param($val, 'rank', '0');// 标签值。(0,1,2,3,4,5)依次表示为白，红，橙，绿，蓝，紫
        $data['owner_id']= ChannelUserAccountMap::getCustomerId(ChannelAccountConst::channel_aliExpress, $accountId);//客服负责人
        
        /* $data['owner_id']='';// 负责人
         $data['handler_id']='';// 处理人ID
         $data['handle_time']='';// 最新处理时间
         $data['create_time']='';//数据插入时间
         $data['update_time']='';//数据更新时间*/
        return $data;

    }

    /**
     * @desc 处理响应数据
     * @param string $data 执行api请求返回的订单数据json字符串
     * @return array 结果集
     * @author Jimmy <554511322@qq.com>
     * @date 2018-03-19 15:20:11
     */
    private function dealResponse($data, $callback = [], $repeat = 3)
    {
        //已经报错了,抛出异常信息
        if (isset($data->error_response) && $data->error_response) {
            throw new Exception($data->sub_msg, $data->code);
        }
        //如果没有result
        if (!isset($data->result)) {
            if($callback && $repeat>=0){
                if($data->code == 15 && $data->sub_code == 'isp.http-read-timeout'){
                    $res = call_user_func_array($callback[0], $callback[1]);
                    $this->dealResponse($res, $callback, $repeat-1);
                }
            }
            throw new Exception(json_encode($data));
        }
        return json_decode($data->result, true);
    }

    /**
     * @desc 获取订单更新的起始时间
     * 1、数据库里面存的是服务器时区的时间，需要将获取到的时间转化为太平洋时区的时间（速卖通指定时区）
     * 2、指定重复抓取的时间段，防止漏抓
     * @param string $datetime 需要进行转化的时间（‘Y-m-d H:i:s’）默认为本地服务器时间。
     * @param int $repeatTime 重复抓取时间。
     * @return string $res 转化之后的时间。
     * @author Jimmy
     * @date 2018-03-13 15:52:11
     */
    private function getStartTime($datetime, $repeatTime)
    {
        $time = $datetime ? strtotime($datetime) : time() - $this->defaultTime;
        return $time - $repeatTime;
    }

    /**
     * @desc 获取账号的配置信息
     * @param int $id 账号对应的数据库表ID
     * @return array $config 账号配置信息
     * @author Jimmy <554511322@qq.com>
     * @date 2018-03-13 15:03:11
     */
    private function getConfig($id)
    {
        $info = Cache::store('AliexpressAccount')->getTableRecord($id);
        if (!$info || !isset($info['id'])) {
            throw new Exception('账号信息缺失');
        }
        if (!param($info, 'client_id')) {
            throw new Exception('账号ID缺失,请先授权!');
        }
        if (!param($info, 'client_secret')) {
            throw new Exception('账号秘钥缺失,请先授权!');
        }
        if (!param($info, 'access_token')) {
            throw new Exception('access token缺失,请先授权!');
        }
        $config['id'] = $info['id'];
        $config['client_id'] = $info['client_id'];
        $config['client_secret'] = $info['client_secret'];
        $config['token'] = $info['access_token'];
        return $config;
    }

    /**
     * @desc 获取任务执行的参数,过滤参数信息
     * @return array $data 检验后的数据信息
     * @author Jimmy <554511322@qq.com>
     * @date 2018-03-13 14:53:11
     */
    private function getParams()
    {
        //获取任务参数
        $data = json_decode($this->params, true);
        if (!param($data, 'id')) {
            throw new Exception('账号ID不能为空!');
        }
        $this->page_size = param($data, 'page_size', 50);
        $this->current_page = param($data, 'current_page', 1);
        $this->start_time = param($data, 'start_time', null);
        $this->end_time = param($data, 'end_time', null);
        $this->only_un_dealed = param($data, 'only_un_dealed', null);
        $this->only_un_readed = param($data, 'only_un_readed', null);
        $this->rank = param($data, 'rank', null);
        $this->seller_id = param($data, 'seller_id', null);
        $this->channel_id = param($data, 'channel_id', null);
        
        //1 全部拉取 2 拉取待发货
        $data['task_type'] = param($data, 'task_type') ?: 1;
        return $data;
    }

}
