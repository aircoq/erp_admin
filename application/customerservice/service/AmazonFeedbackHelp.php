<?php

namespace app\customerservice\service;


use app\common\cache\Cache;
use app\common\model\Order;
use app\common\service\ChannelAccountConst;
use app\common\service\Common;
use think\Exception;
use think\Db;

use app\common\model\amazon\AmazonOrder as AmazonOrderModel;
use app\common\model\amazon\AmazonOrderDetail as AmazonOrderDetailModel;

use app\common\model\amazon\AmazonFeedback as AmazonFeedbackModel;

use app\common\exception\JsonErrorException;
use erp\AbsServer;

use Waimao\AmazonMws\AmazonConfig;
use Waimao\AmazonMws\AmazonReportConfig;
use Waimao\AmazonMws\AmazonReportRequest;
use Waimao\AmazonMws\AmazonReportRequestList;
use Waimao\AmazonMws\AmazonReport;
use app\common\model\amazon\AmazonFeedback;



/**
 * Created by yangweiquan
 * User: PHILL
 * Date: 2017/05/18
 * Time: 18:54
 */
class AmazonFeedbackHelp extends AbsServer
{
    public function lists($params, $page, $pageSize)
    {
        $order = new Order();
        $amazonFeedbackModel = new AmazonFeedbackModel();
        $where = $this->getWhere($params);
        //print_r($where);

        $sort_field = "comment_time";
        $sort_type = "desc";

        //排序字段
        if (isset($params['sort_field']) && in_array($params['sort_field'], ['comment_time', 'id'])) {
            $sort_field = $params['sort_field'];
        }
        if (isset($params['sort_type']) && in_array($params['sort_type'], ['desc', 'asc'])) {
            $sort_type = $params['sort_type'];
        }

        //$amazonOrderModel = new AmazonOrderModel();
        //$amazonOrderDetailModel = new AmazonOrderDetailModel();

        $count = $amazonFeedbackModel->where($where)->count();

        $field = '*';
        $list = $amazonFeedbackModel->field($field)->where($where)->page($page, $pageSize)->order($sort_field, $sort_type)->select();

        $accountCache = Cache::store('AmazonAccount');
        $boolArr = ['-', 'Yes', 'No', '-'];

        $data = [];
        $orderIds = [];
        foreach ($list as $k => $v) {
            $account = $accountCache->getAccount($v['account_id']);
            if (!empty($v['order_id'])) {
                $orderIds[] = $v['order_id'];
            }
            $row = [
                'id' => $v['id'],
                'rating' => $v['rating'],//当前评价

                'is_neutral_or_negative' => $boolArr[$v['is_neutral_or_negative']] ?? 'No',//是中评还是差评
                'is_arrived_on_time' => $boolArr[$v['is_arrived_on_time']] ?? 'No',//是否准时到达
                'is_product_description_accurate' => $boolArr[$v['is_product_description_accurate']] ?? 'No',//产品描述是否准确
                'is_customer_service_good' => $boolArr[$v['is_customer_service_good']] ?? 'No',//客户服务

                'comments' => $v['comments'],//买家评价
                'comment_time' => $v['comment_time'],//评价时间
                'seller_comments' => $v['seller_response'],//我的评价
                'seller_comment_time' => $v['seller_response_time'],

                'order_info' => $v['order_id'],//订单信息
                'order_id' => '',

                'email' => $v['email'],
                'role' => $v['role'],
                'order_payment_time' => $v['order_payment_time'],// 支付时间
                'order_lastest_ship_time' => $v['order_lastest_ship_time'],// 发货时间
                'order_site' => $v['order_site'],// 发货时间
                'account_short_name' => $account['code'] ?? '-',//买家账号简称
                'buyer_account' => $v['email'],//买家账号
                'order_transaction_date' => $v['order_payment_time'],//订单交易日期
                'order_skus' => json_decode($v['order_skus'], true),//下单日期
                'create_time' => $v['create_time'],

                'negative_neutral_reason' => $v['negative_neutral_reason'],//差评原因
                'negative_neutral_remark' => $v['negative_neutral_remark'],//备注
                'is_need_re_dispatched' => $v['is_need_re_dispatched'],//是否需要重发订单
                'handling_status' => $v['handling_status'],//处理状态
                'is_remove_negative_feedback' => $v['is_remove_negative_feedback'],//是否已移除 0.为未移除 1为已移除
            ];
            $data[] = $row;
        }

        //亚马逊订单ID不为空，则去找出系统单号用来点击显示；
        $sysOrderIds = [];
        if (!empty($orderIds)) {
            $sysOrderIds = $order->where(['channel_id' => ChannelAccountConst::channel_amazon, 'channel_order_number' => ['in', $orderIds]])->column('id', 'channel_order_number');
            if (!empty($sysOrderIds)) {
                foreach ($data as &$v) {
                    if (!empty($sysOrderIds[$v['order_info']])) {
                        $v['order_id'] = (string)$sysOrderIds[$v['order_info']];
                    }
                }
            }
        }

        $where['handling_status'] = ['EQ', 0];
        $number_handle_not_yet = $amazonFeedbackModel->where($where)->count();//未处理数
        $where['handling_status'] = ['EQ', 1];
        $number_handle_is_finish = $amazonFeedbackModel->where($where)->count();//已处理数
        $where['handling_status'] = ['EQ', 2];
        $number_handle_is_waiting = $amazonFeedbackModel->where($where)->count();//等待对方处理条数
        $where ['handling_status'] = ['EQ', 3];
        $number_handle_is_expired = $amazonFeedbackModel->where($where)->count();//已过期条数
        $result = [
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
            'number_handle_all' => $number_handle_not_yet + $number_handle_is_finish + $number_handle_is_waiting + $number_handle_is_expired,//未处理条数
            'number_handle_not_yet' => $number_handle_not_yet,//未处理条数
            'number_handle_is_finish' => $number_handle_is_finish,//已处理条数
            'number_handle_is_waiting' => $number_handle_is_waiting,//等待对方处理数
            'number_handle_is_expired' => $number_handle_is_expired,//过期条数
            'data' => $data,
        ];

        return $result;
    }

    private function getWhere($params)
    {
        //单号（系统订单号 交易单号 买家邮箱）
        if (!empty($params['s_order_number']) && isset($params['s_search_by_order_no_type']) && in_array($params['s_search_by_order_no_type'], [1, 2, 3])) {
            switch ($params['s_search_by_order_no_type']) {
                case '1':
                    $where['order_number'] = ['EQ', $params['s_order_number']];
                    break;
                case '2':
                    $where['order_id'] = ['EQ', $params['s_order_number']];
                    break;
                case '3':
                    $where['email'] = ['EQ', $params['s_order_number']];
                    break;
            }
        }
        //按下单时间或评论时间搜索
        if (isset($params['s_search_by_time_type']) && in_array($params['s_search_by_time_type'], [1, 2])) {
            $dateArr = [];
            if (!empty($params['s_start_time']) && empty($params['s_end_time'])) {
                $dateArr = ['>=', strtotime($params['s_start_time'])];
            } else if (empty($params['s_start_time']) && !empty($params['s_end_time'])) {
                $dateArr = ['<=', strtotime($params['s_end_time']. ' 23:59:59')];
            } else if (!empty($params['s_start_time']) && !empty($params['s_end_time'])) {
                $dateArr = ['between', [strtotime($params['s_start_time']), strtotime($params['s_end_time']. ' 23:59:59')]];
            }
            if (!empty($dateArr)) {
                switch ($params['s_search_by_order_no_type']) {
                    case '1':
                        $where['comment_time'] = $dateArr;
                        break;
                    case '2':
                        $where['order_transaction_date'] = $dateArr;
                        break;
                }
            }
        }
        //是中评还是差评
        if (!empty($params['is_neutral_or_negative']) && in_array($params['is_neutral_or_negative'], array('Yes', 'No', '-'))) {
            $where['is_neutral_or_negative'] = ['EQ', array_search($params['is_neutral_or_negative'], ['-', 'Yes', 'No'])];
        }
        //是否已回复
        if (isset($params['s_reply_status']) && in_array($params['s_reply_status'], array('0', '1'))) {
            $where['reply_status'] = ['EQ', $params['s_reply_status']];
        }
        //账户简称
        if (!empty($params['s_account_short_name'])) {
            $where['account_id'] = ['EQ', $params['s_account_short_name']];
        }
        //是否移除中差评
        if (isset($params['s_is_removed_negative_feedback']) && in_array($params['s_is_removed_negative_feedback'], ['0', '1'])) {
            $where['is_remove_negative_feedback'] = ['EQ', $params['s_is_removed_negative_feedback']];
        }
        //是否重发订单
        if (isset($params['s_is_need_re_dispatch_order']) && in_array($params['s_is_need_re_dispatch_order'], ['0', '1'])) {
            $where['is_need_re_dispatched'] = ['EQ', $params['s_is_need_re_dispatch_order']];
        }
        //是否退款
        if (isset($params['s_is_refund']) && in_array($params['s_is_refund'], ['1', '2', '3'])) {
            $where['is_refund'] = ['EQ', $params['s_is_refund'] % 3];
        }
        //是否fba
        if (isset($params['s_is_fba_order']) && in_array($params['s_is_fba_order'], ['0', '1'])) {
            $where['is_fba_order'] = ['EQ', $params['s_is_fba_order']];
        }
        //是否已处理
        if (isset($params['s_handling_status']) && in_array($params['s_handling_status'], ['0', '1', '2', '3'])) {
            $where['handling_status'] = ['EQ', (int)$params['s_handling_status']];
        }
        //账号
        if (!empty($params['s_customer_service_officer_id'])) {
            //通过客服id找到所管理ebay账号id
            $accountids = Cache::store('User')->getCustomerAccount($params['s_customer_service_officer_id'], 2);
            if ($accountids) {
                $where['account_id'] = $whereMes['account_id'] = ['in', $accountids];
            } else {
                $where['id'] = 0;
            }
        }
        return $where;
    }

    /**
     *  提交差评原因
     * @param array $data [order_id,text]
     * @throws JsonErrorException
     * @return boolean
     */
    function submitFeedbackReasonProcessing($data)
	{
    
      
        $feedbakcInfo = AmazonFeedbackModel::field('id')->where(['id' => $data['feedback_id']])->find();

        if (empty($feedbakcInfo)) {
                throw new JsonErrorException('该条中差评信息不存在。');
        }
	   
	    $amazonFeedbackModel = new AmazonFeedbackModel();
		
		$where = ['id' => $data['feedback_id']];
		$save_data = [
			'is_need_re_dispatched'=>$data['is_need_re_dispatched'],//是否需要重新发货
			'negative_neutral_reason'=>$data['negative_neutral_reason'],//差评原因
			'negative_neutral_remark'=>$data['negative_neutral_remark'],//差评原因的备注
		];
		$save_res = $amazonFeedbackModel->save($save_data,$where);
    }
	
	
    /**
     *  提交差评处理状态
     * @param array $data [order_id,text]
     * @throws JsonErrorException
     * @return boolean
     */
    function submitFeedbackDealingStatusProcessing($data)
	{
        $feedbakcInfo = AmazonFeedbackModel::field('id')->where(['id' => $data['feedback_id']])->find();

        if (empty($feedbakcInfo)) {
                throw new JsonErrorException('该条中差评信息不存在。');
        }
       
	   
	    $amazonFeedbackModel = new AmazonFeedbackModel();
		
		$where = ['id' => $data['feedback_id']];
		$save_data = [
			'handling_status'=>$data['modify_status_id'],//处理状态				
		];
		
		if($data['modify_status_id'] == 1){
			$save_data['is_remove_negative_feedback'] = $data['is_remove_negative_feedback'];//是否移除了中差评
		}
		
		$save_res = $amazonFeedbackModel->save($save_data,$where);    

    }
	
	
    /**
     *  通过买家邮箱判断是否是差评
     * @param string $email
     * @throws JsonErrorException
     * @return boolean
     */
    public static function checkIsNegativeByEmail($email) 
    {
        $feedbakcInfo = AmazonFeedbackModel::field('id')->where(['email' => $email, 'is_neutral_or_negative' => 'No'])->find();
        if ($feedbakcInfo) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 组成亚码逊请求参数；
     * @param array $accountInfo
     * @return array
     */
    public function apiParams(array $accountInfo)
    {
        $apiParams = [
            'merchantId' => $accountInfo['merchant_id'],
            'marketplaceId' => AmazonConfig::$marketplaceId[strtoupper($accountInfo['site'])],
            'keyId' => $accountInfo['access_key_id'],
            'secretKey' => $accountInfo['secret_key'],
            'amazonServiceUrl' => AmazonConfig::$serverUrl[strtoupper($accountInfo['site'])]
        ];

        return $apiParams;
    }

    /**
     * 格式化成格林威治时间
     * @param string $timestamp
     * @return false|string
     */
    public function getFormattedTimestamp($timestamp = '')
    {
        $timestamp = (trim($timestamp) != '') ? $timestamp : time();
        return gmdate("Y-m-d\TH:i:s\Z", $timestamp);
    }

    /**
     * 发送请求报告
     * @param array $apiParams
     * @param array $timeLimits
     * @return bool|string
     */
    public function requestReport(array $apiParams, array $timeLimits)
    {
        $reportType = '_GET_SELLER_FEEDBACK_DATA_';

        $amz = new AmazonReportRequest($apiParams);
        $amz->setReportType($reportType);
        $amz->setMarketplaces($apiParams['marketplaceId']);
        $amz->setTimeLimits($timeLimits['StartDate'],$timeLimits['EndDate']);
        $amz->requestReport();
        return $amz->getReportRequestId();
    }

    /**
     * 获取发送请求报告后，服务器生成的列表
     * @param array $apiParams
     * @param int $reportRequestId
     * @return array
     */
    public function reportRequestList(array $apiParams, $reportRequestId = 0)
    {
        $reportType = '_GET_SELLER_FEEDBACK_DATA_';

        $amz2 = new AmazonReportRequestList($apiParams);
        $amz2->setRequestIds($reportRequestId);
        $amz2->setReportTypes($reportType);
        $amz2->fetchRequestList();
        //结果列表，之前只返回第一个reportrequestId；可能会丢失后面的数据，应该取出列表来查看，并返回；
        $resultLists = $amz2->getList();
        if(empty($resultLists)) {
            return [];
        }
        $returnLists = [];
        foreach($resultLists as $val) {
            //这种情况不属于本次请求的类别；
            if ($val['ReportType'] != $reportType) {
                continue;
            }
            if($val['ReportProcessingStatus'] == '_DONE_' && !empty($val['GeneratedReportId'])) {
                $returnLists[] = $val['GeneratedReportId'];
            }
        }
        return $returnLists;
    }

    /**
     * 保存报告到指定位置；
     * @param array $apiParams
     * @param $reportId
     * @param string $reportPath
     * @return bool
     */
    public function saveReport(array $apiParams, $reportId, $reportPath = '')
    {
        $amz3 = new AmazonReport($apiParams, $reportId);
        $amz3->setReportId($reportId);
        $amz3->fetchReport();
        return $amz3->saveReport($reportPath);
    }

    /**
     * 读取feedback.xls文件并保存
     * @param $job
     * @return bool
     * @throws Exception
     */
    public function updateFeedback($job)
    {
        if (!$job) {
            throw new Exception('job 为空');
        }
        $path = $job['path'];
        $ache = Cache::store("AmazonAccount");
        $account = $ache->getTableRecord($job['account_id']);
        if (!file_exists($path)) {
            throw new Exception($path . ' File not existed!');
        }
        $reports = $this->handleReportContent($path, $account['site']);
        $result = $this->saveReportToDb($account, $reports);
        return $result;
    }

    /**
     * 日/月/年 转成时间戳
     * @param $date
     * @return int
     */
    public function makeTimestamp($date, $site) {
        if(empty($date)) {
            return 0;
        }
        $dateArr = preg_split('/[\.\/_]{1}/', $date);
        $m = $d = $y = 0;
        switch (strtoupper($site)) {
            case 'US':
                list($m, $d, $y) = $dateArr;
                break;
            case 'JP':
                list($y, $m, $d) = $dateArr;
                break;
            default:
                list($d, $m, $y) = $dateArr;
        }

        if ($m > 12 && $d <= 12) {
            $d = [$m, $m = $d][0];
        }
        $timestamp = strtotime('20'. $y. '-'. $m. '-'. $d);

        return (int)$timestamp;
    }

    /**
     * Yes/No/-转成1/0/2等数字
     * @param $str
     * @return int
     */
    public function strToint($str) {
        switch($str) {
            case '-':
                return 0;
            case 'Yes': //US/UK/
            case 'Ja':  //DE
            case 'Sí':  //ES
            case 'Sì':  //CA/IT
            case 'Oui': //FR
            case 'はい':  //JP
            case 'да':
            case '是':
            case 'Sim':
                return 1;
            case 'No': //US/UK/
            case 'Nein':  //DE
            //case 'No':  //ES
            //case 'No':  //CA/IT
            case 'Non': //FR
            case 'いいえ':  //JP
            case 'нет':
            case '否':
            case 'Não':
                return 2;
            default:
                return 3;
        }
    }

    /**
     * 过滤掉非utf-8字符；
     * @param $str
     * @return string
     */
    public function filterUtf8($str)
    {
        /*utf8 编码表：
        * Unicode符号范围           | UTF-8编码方式
        * u0000 0000 - u0000 007F   | 0xxxxxxx
        * u0000 0080 - u0000 07FF   | 110xxxxx 10xxxxxx
        * u0000 0800 - u0000 FFFF   | 1110xxxx 10xxxxxx 10xxxxxx
        *
        */
        $re = '';
        $str = str_split(bin2hex($str), 2);

        $mo =  1<<7;
        $mo2 = $mo | (1 << 6);
        $mo3 = $mo2 | (1 << 5);         //三个字节
        $mo4 = $mo3 | (1 << 4);          //四个字节
        $mo5 = $mo4 | (1 << 3);          //五个字节
        $mo6 = $mo5 | (1 << 2);          //六个字节


        for ($i = 0; $i < count($str); $i++)
        {
            if ((hexdec($str[$i]) & ($mo)) == 0)
            {
                $re .=  chr(hexdec($str[$i]));
                continue;
            }

            //4字节 及其以上舍去
            if ((hexdec($str[$i]) & ($mo6) )  == $mo6)
            {
                $i = $i +5;
                continue;
            }

            if ((hexdec($str[$i]) & ($mo5) )  == $mo5)
            {
                $i = $i +4;
                continue;
            }

            if ((hexdec($str[$i]) & ($mo4) )  == $mo4)
            {
                $i = $i +3;
                continue;
            }

            if ((hexdec($str[$i]) & ($mo3) )  == $mo3 )
            {
                $i = $i +2;
                if (((hexdec($str[$i]) & ($mo) )  == $mo) &&  ((hexdec($str[$i - 1]) & ($mo) )  == $mo)  )
                {
                    $r = chr(hexdec($str[$i - 2])).
                        chr(hexdec($str[$i - 1])).
                        chr(hexdec($str[$i]));
                    $re .= $r;
                }
                continue;
            }



            if ((hexdec($str[$i]) & ($mo2) )  == $mo2 )
            {
                $i = $i +1;
                if ((hexdec($str[$i]) & ($mo) )  == $mo)
                {
                    $re .= chr(hexdec($str[$i - 1])) . chr(hexdec($str[$i]));
                }
                continue;
            }
        }
        return $re;
    }

    /**
     * 保存或更新评价
     * @param $account
     * @param $reports
     * @return bool
     */
    public function saveReportToDb($account, $reports)
    {
        if (empty($reports)) {
            return true;
        }
        $time = time();
        $amazonOrderModel = new AmazonOrderModel();
        $amazonOrderDetailModel = new AmazonOrderDetailModel();
        $model = new AmazonFeedback();
        foreach ($reports as $k => $v) {
            try {
                $report = [];
                $report['order_id'] = $v['order_id'];
                $report['account_id'] = $account['id'];
                $report['rating'] = $v['rating'];
                $report['is_neutral_or_negative'] = ($v['rating'] == 3)? 1 : 2;

                $report['comments'] = $v['comments'];
                $report['comment_time'] = $this->makeTimestamp($v['date'], $account['site']);
                $report['seller_response'] = $v['your_response'];

                $report['is_arrived_on_time'] = $this->strToint($v['arrived_on_time']); //准时到达
                $report['is_product_description_accurate'] = $this->strToint($v['item_as_described']);  //产品描述
                $report['is_customer_service_good'] = $this->strToint($v['customer_service']);  //客户服务

                $report['role'] = $v['rater_role'] ?? 'Buyer';
                $report['reply_status'] = empty($v['your_response'])? 0 : 1; //订单回复状态；
                $report['email'] = $v['rater_email'];
                $report['merchant_id'] = $account['merchant_id'];
                $report['create_time'] = $time;

                //这里附加订单的参数
                $order = $amazonOrderModel->where(array('order_number' => $report['order_id']))->find();
                $order_sku_rows = [];
                if($order){
                    $order_sku_rows = $amazonOrderDetailModel->where(array('amazon_order_id' => $order['id']))->select();
                }
                if ($order) {
                    $report['order_payment_time'] = $order['payment_time'];//订单支付时间
                    $report['order_site'] = $order['site'];// 发货时间
                    $report['order_lastest_ship_time'] = $order['latest_ship_time'];// 发货时间
                }

                $report['order_skus'] = [];
                if ($order_sku_rows) {
                    foreach ($order_sku_rows as $order_sku_row) {
                        $skus = [
                            'item_title' => $order_sku_row['item_title'],
                            'item_qty' => $order_sku_row['qty'],
                            'item_url' => $order_sku_row['item_url'],
                        ];
                        $report['order_skus'][] = $skus;
                    }
                }
                $report['order_skus'] = json_encode($report['order_skus'], JSON_UNESCAPED_UNICODE);

                $report_where = [
                    'order_id' => $report['order_id'],
                    'email' => $report['email'],
                    'role' => 'Buyer',
                ];
                $check = $model->where($report_where)->find();
                if (!$check) {//不存在
                    $model->add($report);
                } else {
                    $report['id'] = $check['id'];
                    $model->add($report);
                }
            } catch (Exception $e) {
                throw new Exception($e->getMessage(). '|'. json_encode($v));
            }
        }
        return true;
    }

    /**
     * 读取xld
     * @param $path
     * @return array
     */
    protected function handleReportContent($path, $site)
    {
        $handle = fopen($path, 'r');
        $key = 0;
        $result = [];
        //碰见一个坑，英国的票题栏是英文，德国的票题栏是德文，按理来说别的国的标题栏可能也是国家语言，所以这里给一个固定的票题；
        $title = ['date', 'rating', 'comments', 'your_response', 'arrived_on_time', 'item_as_described', 'customer_service', 'order_id', 'rater_email'];
        while ($line = fgets($handle)) {
            $rows = explode("\t", $line);
            if ($key != 0) {
                if (empty($rows)) {
                    continue;
                }
                $data = [];
                foreach ($rows as $k => $value) {
                    $value = trim($value);
                    $data[$title[$k]] = $this->convert_string($value, $site);
                }
                $result[] = $data;
                unset($data);
            }
            $key++;
        }
        return $result;
    }

    /**
     * 根据站点国家的编码来转译字符串；
     * @param $value
     * @param $site
     * @return string
     */
    public function convert_string($value, $site)
    {
        $site = strtoupper($site);
        $string = '';
        switch ($site) {
            case 'US':
            case 'UK':
                $string = $value;
                break;
            case 'DE':
            case 'ES':
            case 'FR':
            case 'CA':
            case 'IT':
                $string = iconv('ISO-8859-1', 'utf-8//IGNORE', $value);
                break;
            case 'JP':
                $string = iconv('Shift_JIS', 'utf-8//IGNORE', $value);
                break;
            default:
                $string = $value;
                break;
        }

        return $string;
        //iconv('Shift_JIS', 'utf-8', $value);//mb_convert_encoding($value, "utf-8", "utf-8,ISO-8859-1,gbk,ASCII,UNICODE,Shift_JIS");
    }

}