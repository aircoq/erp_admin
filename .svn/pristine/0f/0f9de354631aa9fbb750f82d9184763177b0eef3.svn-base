<?php
namespace app\customerservice\queue;

use app\common\model\aliexpress\AliexpressOnlineOrder;
use Exception;
use app\common\cache\Cache;
use \service\alinew\AliexpressApi;
use app\common\service\SwooleQueueJob;
use app\common\model\aliexpress\AliexpressEvaluate;

class AliEvaluateQueue extends SwooleQueueJob
{
//, , , , , ,

    //查询订单已生效的评价信息参数
    private $parent_order_ids = '700638211382371,91897038795951';//父订单ID集合，最多50
    private $buyer_product_ratings =null;//买家评价星级（1-5星）
    private $end_order_complete_date = null;//订单完成结束时间
    private $end_valid_date = null;//评价生效结束时间
    private $product_id = null;//商品id
    private $start_order_complete_date = null;//订单完成开始时间
    private $start_valid_date = null;//评价生效开始时间

   //查询待卖家评价的订单信息参数
    private $page_size= 50;
    private $current_page = 1;
    private $order_finish_time_end=null;
    private $order_finish_time_start=null;
    private $seller_feedback_status =null;
    private $order_ids = null;
    private $child_order_ids = null;

    private $endTime = 0; //抓取订单更新的结束时间


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
        return '速卖通订单评价';
    }

    /**
     * @desc 获取队列名称
     * @author johnny <1589556545@qq.com>
     * @date 2018-05-04 14:41:11
     * @return string
     */
    public function getName(): string
    {
        return '速卖通订单评价';
    }

    public static function swooleTaskMaxNumber():int
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
            // $params = json_decode('{"id":"34","only_un_dealed":false,"only_un_readed":false,"rank":null,"task_type":1}', true);

            //获取账号信息
            $config = $this->getConfig($params['id']);

            //查询订单已生效的评价信息
            //$this->getListOrderEvaluation($config, $params);

            //查询待卖家评价的订单信息
             $this->querySellerEvaluationOrderList($config, $params);

        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }


    /**
     * @desc 查询待卖家评价的订单信息
     * @param array $config 配置信息
     * @param array $params 运行时的参数信息
     * @author johnny <1589556545@qq.com>
     * @date 2018-05-04 15:20:11
     */
    private function querySellerEvaluationOrderList($config, $params)
    {
        $api = AliexpressApi::instance($config)->loader('Evaluate');

        $page=1;
        do {
            //调用SDK查询待卖家评价的订单信息
            $res=$api->querySellerEvaluationOrderList($this->page_size, $page, $this->order_finish_time_end,$this->order_finish_time_start, $this->seller_feedback_status, $this->order_ids, $this->child_order_ids);
            $res= $this->dealResponse($res);//对象转数组
            $res = $res['result'];//两层result结果包起来的，需要去掉一层
            if(!isset($res['result_list'])||empty($res['result_list']['json'])) break;
            $data = $res['result_list']['json'];

            $parent_order_ids=[];

            foreach($data as $key=>$item){
                $item=json_decode($item,true);
                $parent_order_ids[]=$item['orderId'];
            }
            $res_evaluation=$api->getListOrderEvaluation($parent_order_ids, $buyer_product_ratings =null, $end_order_complete_date = null, $end_valid_date = null, $product_id = null, $start_order_complete_date = null, $start_valid_date = null);
            $res_evaluation= $this->dealResponse($res_evaluation);//对象转数组
            if (isset($res_evaluation['target_list']['trade_evaluation_open_dto']) && !empty($res_evaluation['target_list']['trade_evaluation_open_dto']) )
            {
                //$evaluation_list=[];
                foreach ($res_evaluation['target_list']['trade_evaluation_open_dto'] as $evaluation_val){
                    $saveData=$this->getInsertEvaluationData($config['id'],$evaluation_val);
                    $evaluateModel = new AliexpressEvaluate();
                    $where = [
                        'parent_order_id'=>$saveData['parent_order_id'],
                        'order_id'=>$saveData['order_id'],
                    ];
                    $evaluateInfo = $evaluateModel->where($where)->find();
                    if($evaluateInfo){
                        $evaluateModel->allowField(true)->isUpdate(true)->save($saveData, $where);
                    }else{
                        $evaluateModel->allowField(true)->isUpdate(false)->save($saveData);
                    }
                }
            }

            $count = ceil($res['total_item'] / $this->page_size); //向上取整
            $page++;
        } while ($page <= $count);

    }

    /**
     * @title 组装数据（查询订单已生效的评价信息）
     * @param $order_id
     * @param $val
     * @return array
     */
    private function getInsertEvaluationData($account_id,$val){
        $data = [
            'parent_order_id' => $val['parent_order_id'],//父订单id
            'order_id' => $val['order_id'],
            'buyer_evaluation' => $val['buyer_evaluation'],//买家评价星级
            'buyer_fb_date' => strtotime($val['buyer_fb_date']),//买家已评时间
            'buyer_feedback' => param($val, 'buyer_feedback'),//买家评价内容
            'buyer_login_id' => $val['buyer_login_id'],//买家登录帐号
            'buyer_reply' => $val['buyer_reply'] ?? '', //买家回复内容
            'gmt_create' => strtotime($val['gmt_create']),//记录创建时间
            'gmt_modified' => strtotime($val['gmt_modified']),//记录最后修改时间
            'gmt_order_complete' => strtotime($val['gmt_order_complete']),//订单完成时间
            'product_id' => $val['product_id'],//商品id
            'score' => $val['seller_evaluation'],//卖家评价星级
            'seller_login_id' => $val['seller_login_id'],//卖家登录帐号
            'valid_date' => strtotime($val['valid_date']),//评价生效日期
            'aliexpress_account_id' => $account_id,
            'create_time' => time(),
        ];
        $orderInfo = AliexpressOnlineOrder::field('gmt_create,gmt_pay_time,pay_amount')->where(['order_id'=>$data['parent_order_id']])->find();
        if($orderInfo){
            $data['order_create_time'] = $orderInfo['gmt_create'];
            $data['order_pay_time'] = $orderInfo['gmt_pay_time'];
            $data['pay_amount'] = $orderInfo['pay_amount'];
        }
        return $data;
    }

    /**
     * @desc 设置账号的抓取时间
     * @author Johnny <1589556545@qq.com>
     * @date 2018-05-23 21:32:11
     */
    private function setSyncTime($syncTime, $taskType, $accountId)
    {
        $time = $taskType == 2 ? ['listOrderEvaluation' => $syncTime] : ['evaluationOrderList' => $syncTime];
        Cache::store('AliexpressAccount')->taskEvaluationTime($accountId, $time);
    }


    /**
     * @desc 查询订单已生效的评价信息
     * @param array $config 配置信息
     * @param array $params 运行时的参数信息
     * @author johnny <1589556545@qq.com>
     * @date 2018-05-04 15:20:11
     */
    private function getListOrderEvaluation($config, $params)
    {
        $api = AliexpressApi::instance($config)->loader('Evaluate');
        $count_all=0;
        $count=true;
        $syncTime = 0; //用来记录账号的拉取时间
        do {

            //调用SDK获取关系列表的数据
            $res=$api->getListOrderEvaluation($this->parent_order_ids, $this->buyer_product_ratings, $this->end_order_complete_date, $this->end_valid_date, $this->product_id, $this->start_order_complete_date, $this->start_valid_date);
            $res= $this->dealResponse($res);//对象转数组
            $res = $res['target_list'];//两层result结果包起来的，需要去掉一层
        } while ($count);//
        // echo "共".$count_all."条数据<br/>";
        // echo "共".($this->current_page-1)."页";
        //设置每个账号的更新时间
        if ($syncTime) {
            $this->setSyncTime($syncTime, $params['task_type'], $config['id']);
        }
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
     * @date 2018-03-13 14:53:11
     */
    private function getParams()
    {
        //获取任务参数
        $data = json_decode($this->params, true);
        if (!param($data, 'id')) {
            throw new Exception('账号ID不能为空!');
        }
       /* $this->page_size = param($data, 'page_size',50);
        $this->current_page=param($data, 'current_page',1);
        $this->start_time=param($data, 'start_time',null);
        $this->end_time=param($data, 'end_time',null);
        $this->only_un_dealed=param($data, 'only_un_dealed',null);
        $this->only_un_readed=param($data, 'only_un_readed',null);
        $this->rank=param($data, 'rank',null);
        $this->seller_id=param($data, 'seller_id',null);*/

        //1 全部拉取 2 拉取待发货
        $data['task_type'] = param($data, 'task_type') ?: 1;
        return $data;
    }
    
}

