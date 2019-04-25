<?php
namespace app\customerservice\queue;

use app\common\model\aliexpress\AliexpressIssueProcess;
use app\common\model\aliexpress\AliexpressIssueSolution;
use app\common\model\aliexpress\AliexpressOnlineOrder;
use app\common\model\Order;
use app\customerservice\service\aliexpress\AliIssueHelp;
use Exception;
use app\common\cache\Cache;
use \service\alinew\AliexpressApi;
use app\common\service\SwooleQueueJob;
use \app\common\model\aliexpress\AliexpressIssue;
use think\Db;

class AliIssueQueue extends SwooleQueueJob
{
    // 获取纠纷列表参数
    private $page_size = 50;
    private $current_page = 1;
    private $buyer_login_id = null;
    private $issue_status = null;
    private $order_no = null;
    private $endTime = 0; //抓取订单更新的结束时间

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
        return '获取纠纷列表';
    }

    /**
     * @desc 获取队列名称
     * @author johnny <1589556545@qq.com>
     * @date 2018-05-04 14:41:11
     * @return string
     */
    public function getName(): string
    {
        return '获取纠纷列表';
    }

    public static function swooleTaskMaxNumber(): int
    {
        return 10;
    }

    /**
     * @desc 执行
     * @author johnny <1589556545@qq.com>
     * @date 2018-05-04 14:40:11
     */
    public function execute()
    {
        try {
            //设置执行不超时
            set_time_limit(0);
            //获取执行的参数信息
            $params = $this->getParams();

            //获取账号信息
            $config = $this->getConfig($params['id']);


            //查询待卖家评价的订单信息
            $this->getIssuelist($config, $params);

        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }


    /**
     * @desc 获取纠纷列表
     * @param array $config 配置信息
     * @param array $params 运行时的参数信息
     * @author johnny <1589556545@qq.com>
     * @date 2018-05-04 15:20:11
     */
    private function getIssuelist($config, $params)
    {
        $api = AliexpressApi::instance($config)->loader('Issue');

        $syncTime = 0; //用来记录账号的拉取时间
        $page_total = 0;
        
        /*
         * 纠纷取消canceled_issue、纠纷完结,退款处理完成finish，查询已有处理中状态的最后创建时间
         */
        $last_processing_create_time = 0;
        if($this->issue_status == 'canceled_issue' || $this->issue_status == 'finish'){
            $where = [
                'aliexpress_account_id'=>$config['id'],
                'issue_status'=>'processing'
            ];
            $lastProcessingRe =  AliexpressIssue::field('issue_create_time')->where($where)->order('issue_create_time asc')->find();
            $last_processing_create_time = $lastProcessingRe['issue_create_time'] ?? 0;
        }
        do {
            $res = $api->getIssueList($this->page_size, $this->current_page, $this->buyer_login_id, $this->issue_status, $this->order_no);
            $res = $this->dealResponse($res);//对象转数组
            
            $need_get_next_page = true;//是否需要获取下一页
            if (isset($res['data_list']['issue_api_issue_dto']) && !empty($res['data_list']['issue_api_issue_dto'])) {
                $service = new AliIssueHelp();
                $list_count = count($res['data_list']['issue_api_issue_dto']);
                $last_issue_data = [];//最后一条纠纷数据
                foreach ($res['data_list']['issue_api_issue_dto'] as $k=>$issue_val) {
                    $issueList = $this->issueListInsertData($issue_val, $config['id']);//拿到纠纷列表组装数据
                    //最后一条纠纷数据
                    if($k == $list_count - 1){
                        $last_issue_data = $issueList;
                    }
                    /*
                     * 查询已有数据的最后更新时间，如果已有数据的最后更新时间与接口提供的没有变化，不拉取明细数据
                     */
                    $issueRe = AliexpressIssue::where(['issue_id'=>$issueList['issue_id']])->field('issue_modified_time')->find();
                    if($issueRe && $issueRe['issue_modified_time'] == $issueList['issue_modified_time']){
                        continue;
                    }
                    
                    $detail = $this->getDetail($issueList['buyer_login_id'], $issueList['issue_id'], $api);//获取详情数据
                    
                    /*
                     * 接口报错，抛出异常
                     * wangwei 2018-9-14 9:40:16
                     */
                    if(isset($detail['code']) && isset($detail['msg'])){
                        $err_msg = "getDetail data error ,buyer_login_id:{$issueList['buyer_login_id']},issue_id:{$issueList['issue_id']}";
                        $err_msg .= ",msg:{$detail['msg']},code:{$detail['code']}";
                        isset($detail['sub_code']) && $err_msg .= ",sub_code:{$detail['sub_code']}";
                        isset($detail['sub_msg']) && $err_msg .= ",sub_msg:{$detail['sub_msg']}";
                        throw new Exception($err_msg);
                    }
                    $issueDetail = $service->handleData($detail['result_object']);
                    $data = array_merge($issueList, $issueDetail);
                    $service->saveIssue($data);
                }
                $syncTime = time();
                
                //纠纷取消canceled_issue、纠纷完结,退款处理完成finish
                if($this->issue_status == 'canceled_issue' || $this->issue_status == 'finish'){
                    /*
                     * 最后一条纠纷存在系统中，且创建时间大于
                     * （接口给的纠纷数据按创建时间倒序，这样可以保证拉取到已有的最早的一条已处理纠纷数据，从而更新状态）
                     * 系统已有处理中状态的最后创建时间，查询下一页
                     */
                    $need_get_next_page = $last_processing_create_time && $last_issue_data['issue_create_time'] > $last_processing_create_time;
                }
            }
            
            isset($res['total_item']) || $res['total_item'] = 0;
            
            $page_total = ceil($res['total_item'] / $this->page_size); //总页数
            $this->current_page++;//当前页码累加
            $has_next_page = $this->current_page <= $page_total;//是否有下一页
            
            //获取下一页
            $get_next_page = $has_next_page && $need_get_next_page;
            
        } while ($get_next_page);
        if($syncTime){
            Cache::store('AliexpressAccount')->taskIssueTime($config['id'], $syncTime);
        }
    }

    /**
     * @title  获取纠纷详情
     *
     */
    public function getDetail($buyer_login_id, $issue_id, $api)
    {
        $res = $api->getDetail($buyer_login_id, $issue_id);
        $res = $this->dealResponse($res);//对象转数组
        return $res;
    }


    /**
     * @title 获取纠纷列表组装数据
     *
     */
    public function issueListInsertData($val, $aliexpress_account_id)
    {
        $data = [];
        $data['issue_id'] = $val['issue_id'];//纠纷id
        $data['aliexpress_account_id'] = $aliexpress_account_id;//卖家登录账号
        $data['order_id'] = $val['order_id'];//订单id
        $data['issue_status'] = $val['issue_status'];//纠纷状态 处理中 processing、 纠纷取消canceled_issue、纠纷完结,退款处理完成finish
        $data['issue_modified_time'] = strtotime($val['gmt_modified']);//最后修改时间
        $data['issue_create_time'] = strtotime($val['gmt_create']);//创建时间
        $data['expire_time'] = strtotime($val['gmt_create'] . ' +5 day');//纠纷响应过期时间。默认纠纷提出后5天必须响应
        $data['reason_cn'] = $val['reason_chinese'];//纠纷原因中文
        $data['reason_en'] = $val['reason_english'];//纠纷原因英文

        $data['buyer_login_id'] = $val['buyer_login_id'];//买家登录id
        $data['parent_order_id'] = $val['parent_order_id'];//父订单ID
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
     * @date 2018-05-25 09:53:11
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
        $this->buyer_login_id = param($data, 'buyer_login_id', null);
        $this->issue_status = param($data, 'issue_status', null);
        $this->order_no = param($data, 'order_no', null);

        //1 全部拉取 2 拉取待发货
        $data['task_type'] = param($data, 'task_type') ?: 1;
        return $data;
    }
    
//     public function setParams($params)
//     {
//         $this->params = $params;
        
//     }

}

