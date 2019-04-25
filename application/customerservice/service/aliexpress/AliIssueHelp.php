<?php
namespace app\customerservice\service\aliexpress;

use app\common\model\aliexpress\AliexpressAccount;
use app\common\model\aliexpress\AliexpressIssueProcess;
use app\common\model\aliexpress\AliexpressIssueSolution;
use app\common\model\aliexpress\AliexpressSellerAddress;
use app\common\model\Order;
use app\common\service\UniqueQueuer;
use app\customerservice\queue\AliIssueQueue;
use service\alinew\AliexpressApi;
use think\Db;
use think\Exception;
use erp\AbsServer;
use app\common\cache\Cache;
use app\common\model\aliexpress\AliexpressIssue;
use app\common\service\ChannelAccountConst;
use app\customerservice\service\MsgRuleHelp;
use app\common\service\OrderStatusConst;
use app\order\queue\ChangeOrderStatusByAliexpress;
use app\common\service\Common;
use app\common\service\Report;

class AliIssueHelp extends AbsServer
{
    /**
     * 获取纠纷列表
     * @param type $page
     * @param type $pageSize
     * @param type $where
     * @param type $order
     * @return type
     */
    public function getList($where, $page, $pageSize, $order = 'i.issue_modified_time desc')
    {
        $model = new AliexpressIssue();
        $join = $where['join'];
        $where = $where['where'];
        $model = $model->alias('i');
        if ($join) {
            foreach ($join as $v) {
                $model = $model->join($v[0], $v[1], $v[2]);
            }
            $model = $model->group('i.id');
        }
        $countModel = clone $model;
        $count = $countModel->where($where)->count();
        $list = $model->field('i.*')->where($where)->order($order)->page($page, $pageSize)->select();
        $data = [];
        if (!empty($list)) {
            foreach ($list as $item) {
                $account = Cache::store('AliexpressAccount')->getTableRecord($item['aliexpress_account_id']);
                $data[] = [
                    'id' => $item['id'],
                    'issue_id' => $item['issue_id'],
                    'order_id' => $item['order_id'],
                    'account' => param($account, 'code'),
                    'sys_order_id' => $item['sys_order_id'],
                    'sys_order_number' => $item['sys_order_number'],
                    'buyer_login_id' => $item['buyer_login_id'],
                    'pay_amount' => $item['pay_amount'],
                    'expire_time' => $item['expire_time'],
                    'issue_create_time' => $item['issue_create_time'],
                    'issue_modified_time' => $item['issue_modified_time'],
                    'reason_cn' => $item['reason_cn'],
                    'reason_en' => $item['reason_en'],
                    'issue_status' => $item['issue_status'],
                    'after_sale_warranty' => $item['after_sale_warranty'],
                    'aliexpress_account_id' => $item['aliexpress_account_id'],
                ];
            }
        }
        return [
            'data' => $data,
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
        ];
    }

    public function getIssueDetail($id)
    {
        if (strlen($id) > 11) {
            $where = ['issue_id' => $id];
        } else {
            $where = ['id' => $id];
        }
        $issueInfo = AliexpressIssue::relation('solution,process')->where($where)->find();
        $account = Cache::store('AliexpressAccount')->getTableRecord($issueInfo['aliexpress_account_id']);
        $result = [
            'id' => $issueInfo['id'],
            'issue_id' => $issueInfo['issue_id'],
            'order_no' => $issueInfo['order_id'],
            'account_id' => $issueInfo['aliexpress_account_id'],
            'account' => $account['code'],
            'create_time' => $issueInfo['issue_create_time'],
            'modified_time' => $issueInfo['issue_modified_time'],
            'issue_status' => $issueInfo['issue_status'],
            'reason_cn' => $issueInfo['reason_cn'],
            'reason_en' => $issueInfo['reason_en'],
            'refund_money_max' => $issueInfo['refund_money_max'],
            'refund_money_max_currency' => $issueInfo['refund_money_max_currency'],
            'refund_money_max_local' => $issueInfo['refund_money_max_local'],
            'refund_money_max_local_currency' => $issueInfo['refund_money_max_local_currency'],
            'product_name' => $issueInfo['product_name'],
            'product_price' => $issueInfo['product_price_currency'] . ' ' . $issueInfo['product_price'],
            'logistics_company' => $issueInfo['buyer_return_logistics_company'],
            'logistics_no' => $issueInfo['buyer_return_no'],
            'logistics_lpno' => $issueInfo['buyer_return_logistics_lpno'],
            'expire_time' => $issueInfo['expire_time'],
            'buyer_login_id' => $issueInfo['buyer_login_id'],
            'solution' => [
                'buyer' => [],
                'seller' => [],
                'platform' => [],
            ],
            'process' => [],
        ];
        if (!empty($issueInfo['solution'])) {
            $tmp = [
                'buyer' => [],
                'seller' => [],
                'platform' => [],
            ];
            foreach ($issueInfo['solution'] as $item) {
                $tmp[$item['solution_owner']][] = [
                    'solution_id' => $item['solution_id'],
                    'gmt_create' => $item['gmt_create'],
                    'gmt_modified' => $item['gmt_create'],
                    'refund_money' => $item['refund_money_currency'] . ' ' . $item['refund_money'],
                    'refund_money_post' => $item['refund_money_post'],
                    'is_default' => $item['is_default'],
                    'solution_owner' => $item['solution_owner'],
                    'content' => $item['content'],
                    'logistics_fee_bear_role' => $item['logistics_fee_bear_role'],
                    'solution_type' => $item['solution_type'],
                    'reached_time' => $item['reached_time'],
                    'status' => $item['status'],
                    'reached_type' => $item['reached_type'],
                    'buyer_accept_time' => $item['buyer_accept_time'],
                    'seller_accept_time' => $item['seller_accept_time'],
                    'logistics_fee_amount' => $item['logistics_fee_amount_currency'] . ' ' . $item['logistics_fee_amount'],
                ];
            }
            $result['solution'] = $tmp;
        }
        if (!empty($issueInfo['process'])) {
            $tmp = [];
            foreach ($issueInfo['process'] as $v) {
                $attachments = json_decode($v['attachments'], true);
                $file_path = $attachments ? array_column($attachments['api_attachment_dto'], 'file_path') : '';
                $buyer_login_id = $v['submit_member_type'] == 'buyer' ? $issueInfo['buyer_login_id'] : '';
                $tmp[] = [
                    'action_type' => $this->getProcessStatusText($v['action_type']),
                    'content' => $v['content'],
                    'gmt_create' => $v['gmt_create'],
                    'submit_member_type' => $v['submit_member_type'],
                    'file_path' => $file_path,
                    'buyer_login_id' => $buyer_login_id
                ];
            }
            $sortArr = array_column($tmp, 'gmt_create');
            array_multisort($sortArr, SORT_DESC, SORT_NUMERIC, $tmp);
            $result['process'] = $tmp;
        }
        return $result;
    }
    
    public function getStatus()
    {
        return AliexpressIssue::ISSUE_STATUS;
    }
    
    public function checkNewest($order_id,$issue_modified_time)
    {
        return AliexpressIssue::checkNewest($order_id, $issue_modified_time);
    }

    public function uploadImages($issueId, $img)
    {
        $info = AliexpressIssue::where(['issue_id'=>$issueId])->find();
        if(empty($info)) throw new Exception('找不到对应的纠纷数据');
        $imgUrl = $img['imgUrl'];
        $imgExt = $img['imgExt'];
        $imageBytes = base64_encode(file_get_contents($imgUrl));
        //调用奇门接口
        $config = AliexpressAccount::getAliConfig($info['aliexpress_account_id']);
        $config && $config['token'] = $config['accessToken'];
        $issueServer =   AliexpressApi::instance($config)->loader('Issue');
        $response        =   $issueServer->imageUpload($info['buyer_login_id'], $imgExt, $info['issue_id'], $imageBytes);
        $response = $this->dealResponse($response);
        if(!isset($response['result_object']) && isset($response['sub_msg'])){
            throw new Exception($response['sub_msg']);
        }
        $resultData = [];
        if($response['result_object']){
            $resultData = $this->syncRealTime($config, $info['buyer_login_id'], $info['issue_id']);
        }
        return ['status'=>$response['result_object'], 'data'=>$resultData];
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

    public function agreeSolution($params)
    {
        $solutionInfo = AliexpressIssueSolution::where(['solution_id'=>$params['solution_id']])->find();
        if(empty($solutionInfo)) throw new Exception('找不到该方案');
        if($solutionInfo['solution_owner'] != 'buyer') throw new Exception('不是买家提出的方案');
        $issueInfo = AliexpressIssue::where(['issue_id'=>$solutionInfo['issue_id']])->find();
        if(empty($issueInfo)) throw new Exception('找不到对应的纠纷信息');
        $return_address_id = null;
        if($solutionInfo['solution_type'] == 'return_and_refund'){
            if(empty(param($params, 'return_address_id'))){
                throw new Exception('退货方案必须选择地址');
            }
            $return_address_id = $params['return_address_id'];
        }
        $config = AliexpressAccount::getAliConfig($issueInfo['aliexpress_account_id']);
        $config && $config['token'] = $config['accessToken'];
        $issueServer = AliexpressApi::instance($config)->loader('Issue');
        $response = $issueServer->solutionAgree($issueInfo['buyer_login_id'], $solutionInfo['issue_id'] ,$params['solution_id'] ,$return_address_id);
        $response = $this->dealResponse($response);
        if(!isset($response['result_object']) && isset($response['sub_msg'])){
            throw new Exception($response['sub_msg']);
        }
        $resultData = [];
        if($response['result_object']){
            $resultData = $this->syncRealTime($config, $issueInfo['buyer_login_id'], $issueInfo['issue_id']);
        }

        //统计数据；
        $userInfo = Common::getUserInfo();
        Report::statisticMessage(ChannelAccountConst::channel_aliExpress, $userInfo['user_id'], time(), ['dispute_quantity' => 1]);

        return ['status'=>$response['result_object'], 'data'=>$resultData];
    }

    public function checkSaveSolutionParams($params, $isAdd = 1)
    {
        if(! param($params, 'buyer_login_id')) throw new Exception('缺少买家登陆id');
        if(! param($params, 'issue_id')) throw new Exception('缺少纠纷id');
        if(! isset($params['refund_amount'])) throw new Exception('缺少退款金额');
        if(! param($params, 'refund_amount_currency')) throw new Exception('缺少退款金额币种');
        if(! param($params, 'solution_context')) throw new Exception('缺少理由说明');
        if(! param($params, 'add_solution_type')) throw new Exception('缺少方案类型');
        $data = [
            'add_seller_solution' => $isAdd ? "true" : "false",
            'buyer_login_id' => $params['buyer_login_id'],
            'issue_id' => strval($params['issue_id']),
            'refund_amount' => strval($params['refund_amount']),
            'refund_amount_currency' => $params['refund_amount_currency'],
            'solution_context' => $params['solution_context'],
            'add_solution_type' => $params['add_solution_type'],
            'return_good_address_id' => null,
            'buyer_solution_id' => null,
            'modify_seller_solution_id' => null
        ];
        if($params['add_solution_type'] == 'return_and_refund'){
            if(! param($params, 'return_good_address_id')) throw new Exception('退货方案必须选择地址');
            $data['return_good_address_id'] = strval($params['return_good_address_id']);
        }
        if($isAdd){
            if(! param($params, 'buyer_solution_id')) throw new Exception('缺少拒绝的买家方案id');
            $data['buyer_solution_id'] = strval($params['buyer_solution_id']);
        }else{
            if(! param($params, 'modify_seller_solution_id')) throw new Exception('缺少修改的卖家方案id');
            $data['modify_seller_solution_id'] = strval($params['modify_seller_solution_id']);
        }
        return $data;
    }

    public function saveSolution($data)
    {
        $issueInfo = AliexpressIssue::where(['issue_id'=>$data['issue_id']])->find();
        if(empty($issueInfo)) throw new Exception('找不到对应的纠纷信息');
        #退款金额美金转换成本国币种
        $data['refund_amount_currency'] = $issueInfo['refund_money_max_local_currency'];
        if($data['refund_amount'] > 0 && $issueInfo['refund_money_max_local_currency'] != 'USD'){
            $data['refund_amount'] = round($data['refund_amount']*($issueInfo['refund_money_max_local']/$issueInfo['refund_money_max']), 4);
            if($data['refund_amount'] > $issueInfo['refund_money_max_local']){
                throw new Exception("转化后金额为{$issueInfo['refund_money_max_local_currency']} {$data['refund_amount']},超出最大退款金额");
            }
        }
        #
        $config = AliexpressAccount::getAliConfig($issueInfo['aliexpress_account_id']);
        $config && $config['token'] = $config['accessToken'];
        $issueServer = AliexpressApi::instance($config)->loader('Issue');
        $response = $issueServer->solutionSave($data);
        $response = $this->dealResponse($response);
        if(!isset($response['result_object']) && isset($response['sub_msg'])){
            throw new Exception($response['sub_msg']);
        }
        $resultData = [];
        if($response['result_object']){
            $resultData = $this->syncRealTime($config, $issueInfo['buyer_login_id'], $issueInfo['issue_id']);
        }

        //统计数据；
        $userInfo = Common::getUserInfo();
        Report::statisticMessage(ChannelAccountConst::channel_aliExpress, $userInfo['user_id'], time(), ['dispute_quantity' => 1]);

        return ['status'=>$response['result_object'], 'data'=>$resultData];
    }

    public function getLabelStatistics($where = [])
    {
        $labels = AliexpressIssue::ISSUE_LABEL;
        $result = $join = [];
        if($where['join']) $join[] = $where['join'];
        foreach ($labels as $v) {
            if(empty($v['condition'])){
                $count = 0;
            }else{
                $labelWhere = array_merge($where['where'], $v['condition']);
                if(isset($v['join'])) $join[] = $v['join'];
                $model = AliexpressIssue::alias('i');
                if($join){
                    foreach($join as $item){
                        $model = $model->join($item[0], $item[1], $item[2]);
                    }
                    $count = $model->where($labelWhere)->group('i.id')->count();
                }else{
                    $count = $model->where($labelWhere)->count();
                }

            }
            $result[] = ['label' => $v['name'], 'value' => $v['value'], 'count' => $count];
        }
        return $result;
    }

    public function getWhere($params, $needLabel = 1)
    {
        $where = $processWhere = $join = [];
        if (param($params, 'account_id')) {
            $where['i.aliexpress_account_id'] = $params['account_id'];
        }
        if (param($params, 'order_no')) {
            $where['i.sys_order_number'] = ['like', '%' . $params['order_no'] . '%'];
        }
        if (param($params, 'process_start_time') && param($params, 'process_end_time')) {
            $processWhere['p.gmt_create'] = ['between', [strtotime($params['process_start_time']. ' 00:00:00'), strtotime($params['process_end_time'].' 23:59:59')]];
        } else if (param($params, 'process_start_time')) {
            $processWhere['p.gmt_create'] = ['>=', strtotime($params['process_start_time'].' 00:00:00')];
        } else if (param($params, 'process_end_time')) {
            $processWhere['p.gmt_create'] = ['<=', strtotime($params['process_end_time'].' 23:59:59')];
        }
        if (param($params, 'buyer_login_id')) {
            $where['i.buyer_login_id'] = ['like', '%' . $params['buyer_login_id'] . '%'];
        }
        if ($processWhere) {
            $join[] = ['aliexpress_issue_process p', 'i.issue_id=p.issue_id', 'left'];
            $where['p.submit_member_type'] = 'seller';
            $where = array_merge($where, $processWhere);
        }
        //标签
        if($needLabel){
            $labels = AliexpressIssue::ISSUE_LABEL;
            if (param($params, 'label_id') && isset($labels[$params['label_id']])) {
                if (isset($labels[$params['label_id']]['join'])) {
                    $join[] = $labels[$params['label_id']]['join'];
                }
                $where = array_merge($where, $labels[$params['label_id']]['condition']);
            }
        }
        return ['where'=>$where, 'join'=>$join];
    }

    public function getProcess($issueId)
    {
        $data = AliexpressIssueProcess::where(['issue_id'=>$issueId, 'submit_member_type'=>['in', ['buyer', 'seller']]])->order('gmt_create desc')->select();
        $issueInfo = AliexpressIssue::where(['issue_id'=>$issueId])->find();
        if(empty($issueInfo)) throw new Exception('找不到对应的纠纷信息');
        $result = [];
        foreach($data as $v){
            $attachments = json_decode($v['attachments'], true);
            $file_path = $attachments ? array_column($attachments['api_attachment_dto'], 'file_path') : '';
            $buyer_login_id = $v['submit_member_type'] == 'buyer' ? $issueInfo['buyer_login_id'] : '';
            $result[] = [
                'action_type' => $this->getProcessStatusText($v['action_type']),
                'content' => $v['content'],
                'gmt_create' => $v['gmt_create'],
                'submit_member_type' => $v['submit_member_type'],
                'file_path' => $file_path,
                'buyer_login_id' => $buyer_login_id
            ];

        }
        return $result;
    }

    private function getProcessStatusText($index)
    {
        $processStatus = [
            'initiate' => '发起纠纷',
            'cancel' => '买家取消纠纷',
            'buyer_accept' => '买家同意方案',
            'seller_accept' => '卖家同意方案',
            'buyer_refuse' => '买家拒绝方案',
            'buyer_create_solution' => '买家创建方案',
            'buyer_modify_solution' => '买家修改方案',
            'buyer_delete_solution' => '买家删除方案',
            'seller_create_solution' => '卖家创建方案',
            'seller_modify_solution' => '卖家修改方案',
            'seller_delete_solution' => '卖家删除方案',
            'seller_create_proof' => '卖家上传凭证',
            'buyer_create_proof' => '买家上传凭证',
            'buyer_modify_issue_reason' => '买家修改纠纷原因'
        ];
        return $processStatus[$index] ?? $index;
    }

    public function syncRealTime($config, $buyer_login_id, $issue_id)
    {
        $api = AliexpressApi::instance($config)->loader('Issue');
        $res = $api->getDetail($buyer_login_id, $issue_id);
        $res = $this->dealResponse($res);//对象转数组
        $data = $res['result_object'];
        $updateData = $this->handleData($data);
        $result = $this->saveIssue($updateData);
        if($result){
            return $this->getIssueDetail($issue_id);
        }else{
            return [];
        }
    }

    public function handleData($data)
    {
        $issueData = [
            'refund_money_max' => $data['refund_money_max'],//退款上限
            'refund_money_max_currency' => $data['refund_money_max_currency'],//退款上限币种
            'refund_money_max_local' => $data['refund_money_max_local'],//退款上限本币
            'refund_money_max_local_currency' => $data['refund_money_max_local_currency'],//退款上限当地货币币种
            'product_name' => $data['product_name'],//产品名称
            'product_price' => $data['product_price'],//产品价格
            'product_price_currency' => $data['product_price_currency'],//产品价格币种
            'after_sale_warranty' => $data['after_sale_warranty'] ? 1 : 0,//是否售后宝纠纷
            'issue_reason_id' => $data['issue_reason_id'],//纠纷原因id
            'buyer_login_id' => $data['buyer_login_id'],
            'buyer_return_logistics_company' => param($data, 'buyer_return_logistics_company'),
            'buyer_return_logistics_lpno' => param($data, 'buyer_return_logistics_lp_no'),
            'buyer_return_no' => param($data, 'buyer_return_no'),
            'issue_status' => $data['issue_status'],
            'reason_en' => $data['issue_reason'],
            'order_id' => $data['order_id'],
            'parent_order_id' => $data['parent_order_id'],
            'issue_id' => $data['id'],
            'solution_data' => [],
            'process_data' => []
        ];
        if(isset($data['buyer_solution_list']['solution_api_dto']) && $data['buyer_solution_list']['solution_api_dto']){
            $buyerSolution = $this->getSolutionData($data['buyer_solution_list']['solution_api_dto']);
            $issueData['solution_data'] = array_merge($issueData['solution_data'], $buyerSolution);
        }
        if(isset($data['platform_solution_list']['solution_api_dto']) && $data['platform_solution_list']['solution_api_dto']){
            $platformSolution = $this->getSolutionData($data['platform_solution_list']['solution_api_dto']);
            $issueData['solution_data'] = array_merge($issueData['solution_data'], $platformSolution);
        }
        if(isset($data['seller_solution_list']['solution_api_dto']) && $data['seller_solution_list']['solution_api_dto']){
            $sellerSolution = $this->getSolutionData($data['seller_solution_list']['solution_api_dto']);
            $issueData['solution_data'] = array_merge($issueData['solution_data'], $sellerSolution);
        }
        if(isset($data['process_dto_list']['api_issue_process_dto']) && $data['process_dto_list']['api_issue_process_dto']){
            $issueData['process_data'] = $this->getProcessData($data['process_dto_list']['api_issue_process_dto']);
        }
        return $issueData;
    }

    private function getSolutionData($solutionList)
    {
        $solutionData = [];
        foreach ($solutionList as $solution){
            $solutionData[] = [
                'buyer_accept_time' => strtotime($solution['buyer_accept_time']),
                'content' => param($solution, 'content'),
                'is_default' => $solution['is_default'] ? 1 : 0,
                'gmt_create' => strtotime($solution['gmt_create']),
                'gmt_modified' => strtotime($solution['gmt_modified']),
                'solution_id' => $solution['id'],
                'issue_id' => $solution['issue_id'],
                'logistics_fee_amount' => param($solution, 'logistics_fee_amount'),
                'logistics_fee_amount_currency' => param($solution, 'logistics_fee_amount_currency'),
                'logistics_fee_bear_role' => param($solution, 'logistics_fee_bear_role'),
                'order_id' => $solution['order_id'],
                'reached_time' => strtotime($solution['reached_time']),
                'reached_type' => param($solution, 'reached_type'),
                'refund_money' => param($solution, 'refund_money'),
                'refund_money_currency' => param($solution, 'refund_money_currency'),
                'refund_money_post' => param($solution, 'refund_money_post'),
                'refund_money_post_currency' => param($solution, 'refund_money_post_currency'),
                'seller_accept_time' => strtotime($solution['seller_accept_time']),
                'solution_owner' => $solution['solution_owner'],
                'solution_type' => param($solution, 'solution_type'),
                'status' => $solution['status'],
                'version' => param($solution, 'version')
            ];
        }
        return $solutionData;
    }

    private function getProcessData($processList)
    {
        $processData = [];
        foreach ($processList as $process){
            $processData[] = [
                'process_id' => param($process, 'id'),
                'action_type' => param($process, 'action_type'),
                'attachments' => param($process, 'attachments') ? json_encode($process['attachments']) : '',
                'content' => param($process, 'content'),
                'gmt_create' => param($process, 'gmt_create') ? strtotime($process['gmt_create']) : 0,
                'has_buyer_video' => param($process, 'has_buyer_video') ? 1 : 0,
                'has_seller_video' => param($process, 'has_seller_video') ? 1 : 0,
                'issue_id' => param($process, 'issue_id'),
                'receive_goods' => param($process, 'receive_goods') ? 1 : 0,
                'submit_member_type' => param($process, 'submit_member_type')
            ];
        }
        return $processData;
    }

    /**
     * @param array $data
     * @return number|boolean true:保存成功，false:保存失败
     */
    public function saveIssue($data)
    {
        try{
            $orderInfo = Order::field('id,order_number,pay_time,pay_fee,currency_code,shipping_time,status')->where(['channel_order_number'=>$data['parent_order_id']])->find();
            Db::startTrans();
            if($orderInfo){
                $data['sys_order_id'] = $orderInfo['id'];
                $data['sys_order_number'] = $orderInfo['order_number'];
                $data['pay_time'] = $orderInfo['pay_time'];
                $data['pay_amount'] = $orderInfo['currency_code'].' '.$orderInfo['pay_fee'];
            }
            if($data['solution_data']){
                $sortArr = array_column($data['solution_data'], 'gmt_create');
                array_multisort($sortArr, SORT_DESC,SORT_NUMERIC,$data['solution_data']);
                if($data['solution_data'][0]['solution_owner'] == 'buyer' && $data['solution_data'][0]['status'] == 'wait_seller_accept' && $data['issue_status'] == 'processing'){
                    $data['wait_seller_accept'] = 1;
                    $data['expire_time'] = $data['solution_data'][0]['gmt_create']+5*24*3600;
                }else{
                    $data['wait_seller_accept'] = 0;
                    $data['expire_time'] = 0;//已响应
                }
            }

            $issueModel = new AliexpressIssue();
            $check = $issueModel->saveData($data);

            if($data['solution_data']){
                AliexpressIssueSolution::where(['issue_id'=>$data['issue_id']])->delete();
                foreach($data['solution_data'] as $v){
                    (new AliexpressIssueSolution())->allowField(true)->isUpdate(false)->save($v);
                }
            }
            if($data['process_data']){
                AliexpressIssueProcess::where(['issue_id'=>$data['issue_id']])->delete();
                foreach($data['process_data'] as $v){
                    (new AliexpressIssueProcess())->allowField(true)->isUpdate(false)->save($v);
                }
            }

            Db::commit();

            if(empty($check)){//纠纷是新加入表中,触发事件
                $event_name = 'E10';
                $order_data = [
                    'channel_id'=>ChannelAccountConst::channel_aliExpress,//Y 渠道id
                    'account_id'=>$data['aliexpress_account_id'],//Y 账号id
                    'channel_order_number'=>$data['order_id'],//Y 渠道订单号
                    'receiver'=>$data['buyer_login_id'],//Y 买家登录帐号
                ];
                (new MsgRuleHelp())->triggerEvent($event_name, $order_data);
            }
            
            /*
             * 新纠纷，且订单未发货、未作废。拉入人工审核
             * wangwei 2019-2-14 11:14:39
             */
            if(!$check && $orderInfo){
                if($orderInfo['shipping_time'] == 0 && $orderInfo['status'] != OrderStatusConst::SaidInvalid){
                    $remark = '后台--订单产生纠纷';
                    (new UniqueQueuer(ChangeOrderStatusByAliexpress::class))->push(['order_id' => $orderInfo['id'], 'reason' => $remark]);
                }
            }

            return true;
        }catch (Exception $e){
            Db::rollback();
            return false;
        }
    }

    public function sync($ids)
    {
        $result = [];
        foreach($ids as $v){
            $data['page_size']= 50;
            $data['current_page'] = 1;
            $data['buyer_login_id']=null;
            $data['order_no'] =null;
            $data['id'] = $v;
            $data['issue_status']=null;
            /*
             * 分别抓取各个状态的纠纷
             * wangwei 2018-9-18 11:15:58
             */
            foreach (AliexpressIssue::ISSUE_STATUS as $issue_status){
                $data['issue_status'] = $issue_status;
                (new UniqueQueuer(AliIssueQueue::class))->push(json_encode($data));
            }
        }
        return $result;
    }
}
