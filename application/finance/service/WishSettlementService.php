<?php
namespace app\finance\service;

use app\common\cache\Cache;
use app\common\service\ChannelAccountConst;
use app\common\service\CommonQueuer;
use app\common\service\Excel;
use think\Exception;
use app\index\service\ChannelUserAccountMap;
use app\common\model\wish\WishSettlementReport;
use app\common\cache\driver\User;
use app\report\model\ReportExportFiles;
use app\report\validate\FileExportValidate;
use app\common\model\wish\WishSettlementReportDetail;
use think\Loader;
use app\common\model\LogExportDownloadFiles;
use app\finance\queue\WishSettlementExport;
Loader::import('phpExcel.PHPExcel', VENDOR_PATH);

/**
 * User: wangwei
 * time 2018-12-11 17:01:19
 */
class WishSettlementService
{
    
    /**
     * @desc 获取列表数据 
     * @author wangwei
     * @date 2018-12-11 17:02:13
     * @param array $params
     */
    public function getIndexData($params){
        $return = [
            'data' => [],
            'page' =>1,
            'pageSize' =>20,
            'count' => 0
        ];
        //开始时间
        $start_time_str = paramNotEmpty($params,'date_s','2018-06-01');
        if(!$start_time = strtotime($start_time_str)){
            throw new Exception('错误的开始时间格式:' . $params['date_s']);
        }
        $params['date_s'] = $start_time;
        //结束时间
        $end_time_str = paramNotEmpty($params,'date_e',date('Y-m-d'));
        if(!$end_time = strtotime($end_time_str)){
            throw new Exception('错误的结束时间格式:' . $params['date_e']);
        }
        $params['date_e'] = $end_time;

        $where = $this->getWhere($params);
        $model = new WishSettlementReport();
        $field = [
            'account_id',
            'SUM( transaction_payment * to_cny_rate) AS payment_for_transactions_sum',
            'SUM( (refund_amount+refund_deduction) * to_cny_rate) AS refund_amount_sum',   //退款金额
            'SUM( withheld_transactions_released * to_cny_rate) AS withheld_transactions_released_sum',
            'SUM( other_payments * to_cny_rate) AS other_payments_sum',
//            'SUM( refund_deduction ) AS refund_deduction_sum',
            'SUM( amount_withheld * to_cny_rate) AS amount_withheld_sum',
            'SUM( fees * to_cny_rate) AS fees_sum',
            'SUM( fines * to_cny_rate) AS fines_sum',
            'SUM( wish_express_cash_back * to_cny_rate) AS wish_express_cash_back_sum',
            'SUM( gross_amount * to_cny_rate) AS gross_amount_sum',
            'SUM( transaction_payment * to_cny_rate) / 0.85 AS order_amount'
        ];
        $userCache = new User();
        $page = param($params,'page',1);
        $pageSize = param($params,'pageSize',50);
        if($res = $model->where($where)->field($field)->group('account_id')->page($page,$pageSize)->order('account_id')->select()){
            $count = $model->where($where)->field('account_id')->group('account_id')->count();
            $total = $model->where($where)->field($field)->group('account_id')->order('account_id')->select();
            $this->getPropertion($res);
            $sum = $this->getSum($total);
            foreach ($res as $k=>$re){
                $re = $re->toArray();
                //金额处理
                foreach ($re as $kk=>&$vv){
                    if($kk!='account_id'){
                        $is_sym = false;
                        if($vv < 0){
                            $is_sym = true;
                        }
                        $vv = number_format($vv,2);
//                        if($is_sym){
//                            $vv = str_replace('-', '-$', $vv);
//                        }else{
//                            $vv = '$'.$vv;
//                        }
                    }
                }
                //日期时间
                $re['date_s'] = $start_time_str;
                $re['date_e'] = $end_time_str;
                //账号简称
                if($accInfo = Cache::store('wishAccount')->getAccount($re['account_id'])){
                    $re['account'] = $accInfo['code'];
                }
                //查询销售人员
                $re['seller'] = '--';
                if($seller_id = ChannelUserAccountMap::getSellerId(ChannelAccountConst::channel_wish, $re['account_id'])){
                    if($userInfo = $userCache->getOneUser($seller_id)){
                        $re['seller'] = $userInfo['realname'];
                    }
                }
                $return['data'][] = $re;
                $return['page'] = $page;
                $return['pageSize'] = $pageSize;
                $return['count'] = $count;
            }
            $return['sum'] = $sum;
            $return['symbol'] = '¥';
        }
        return $return;
    }
    
    /**
     * @desc 获取查询条件 
     * @author wangwei
     * @date 2018-12-11 18:39:23
     * @param array $param
     * @return unknown[]|string[][]|array[][]|unknown[][]
     */
    public function getWhere($param){
        $where = [];
        $account_id_arr = [];
        if($account_id_str = trim(param($param,'account_ids'))) {
            $account_id_arr = explode(',', $account_id_str);
        }
        if($seller_id = param($param,'seller_id')) {
            //获取当前销售负责的账号id
            $account_ids = ChannelUserAccountMap::getAccountBySellerId(ChannelAccountConst::channel_wish, $seller_id);
            if($account_id_arr){
                //账号id取交集
                $account_id_arr = array_intersect($account_id_arr, $account_ids);
            }else{
                $account_id_arr = $account_ids;
            }
            //为空时弄一个查不到的值，保证数据查不到
            $account_id_arr  = $account_id_arr ? $account_id_arr : ['-1'];
        }
        if($account_id_arr){
            if(count($account_id_arr)==1){
                $where['account_id'] = $account_id_arr[0];
            }else{
                $where['account_id'] = ['in', $account_id_arr];
            }
        }
        $start_time = param($param,'date_s');
        $end_time = param($param,'date_e');
        if($start_time && $end_time) {
            $where['invoice_date'] = [['>=', $start_time],['<=',$end_time]];
        }else{
            if($start_time){
                $where['invoice_date'] = ['>=', $start_time];
            }else if($end_time){
                $where['invoice_date'] = ['<=', $end_time];
            }
        }
        return $where;
    }

    /**
     * @desc 导出报告 
     * @author wangwei
     * @date 2018-12-11 19:10:16
     * @param array $params
     * @param unknown $user
     * @throws Exception
     */
    public function export($params, $user_id)
    {
        //开始时间
        $start_time_str = paramNotEmpty($params,'date_s','2018-06-01');
        if(!$start_time = strtotime($start_time_str)){
            throw new Exception('错误的开始时间格式:' . $params['date_s']);
        }
        $params['date_s'] = $start_time;
        //结束时间
        $end_time_str = paramNotEmpty($params,'date_e',date('Y-m-d'));
        if(!$end_time = strtotime($end_time_str)){
            throw new Exception('错误的结束时间格式:' . $params['date_e']);
        }
        $params['date_e'] = $end_time;
        
        $exportFileName = "wish店铺资金核算({$start_time_str}-{$end_time_str})";
        $model = new ReportExportFiles();
        $model->applicant_id = $user_id;
        $model->apply_time = time();
        $model->export_file_name = $exportFileName. '.xlsx';
        $model->status = 0;
        if (!$model->save()) {
            throw new Exception('导出请求创建失败');
        }
        $params['file_name'] = $exportFileName;
        $params['apply_id'] = $model->id;
        (new CommonQueuer(WishSettlementExport::class))->push($params);
    }
    
    /**
     * @desc wish资金核算详细导出，现暂不用 time 2019/1/11 11:11 linpeng
     * @author wangwei
     * @date 2018-12-11 21:11:59
     * @param array $params
     * @throws Exception
     * @return boolean
     */
    public function exportHandle($params){
        try {
            $validate = new FileExportValidate();
            if (!$validate->scene('export')->check($params)) {
                throw new Exception($validate->getError());
            }
            //查询主表数据
            $where = $this->getWhere($params);
            if(!$res = (new WishSettlementReport())->where($where)->field('id,account_id')->select()){
                throw new Exception('暂无数据');
            }
            //查出账号简称
            $wspRe = [];
            foreach ($res as $k=>$re){
                $re = $re->toArray();
                $wspRe[$re['id']] = $re;
                $wspRe[$re['id']]['account'] = '--';
                //账号简称
                if($accInfo = Cache::store('wishAccount')->getAccount($re['account_id'])){
                    $wspRe[$re['id']]['account'] = $accInfo['code'];
                }
            }
            //执行导出
            $result = $this->executeExport($wspRe,$params['file_name']);
            if(!$result['ask']){
                throw new Exception($result['message']);
            }
            if(is_file($result['file_path'])){
                if($applyRecord = ReportExportFiles::get($params['apply_id'])){
                    $applyRecord->exported_time = time();
                    $applyRecord->download_url = $result['download_url'];
                    $applyRecord->status = 1;
                    $applyRecord->isUpdate()->save();
                }
            } else {
                throw new Exception('文件写入失败');
            }
        }catch (Exception $ex){
            if($applyRecord = ReportExportFiles::get($params['apply_id'])){
                $applyRecord->status = 2;
                $applyRecord->error_message = $ex->getMessage();
                $applyRecord->isUpdate()->save();
            }
            $error_msg = 'error_msg:' . $ex->getMessage().';file:'.$ex->getFile().';line:'.$ex->getLine();
            throw new Exception($error_msg);
        }
        
        return true;
    }
    
    /**
     * @desc 执行导出 
     * @author wangwei
     * @date 2018-12-11 18:49:47
     */
    public function executeExport($wspRe,$file_name){
        $return = [
            'ask'=>0,
            'message'=>'executeExport error',
            'file_path'=>'',
            'download_url'=>'',
        ];
        set_time_limit(0);
        ini_set('memory_limit','4096M');
        
        /**
         * 2、生成表格
         */
        $objExcel = new \PHPExcel();
        $wsp_ids = array_keys($wspRe);
        $type_map = [
            'transactions_being_paid'=>'交易款项',
            'withheld_payments'=>'扣除数额',
            'withheld_transactions_released'=>'释放暂扣款项',
            'others_payments'=>'其他款项',
            'transactions_being_refunded'=>'扣除退款',
            'fees'=>'其他费用',
            'fines'=>'罚款或暂扣货款金额',
        ];
        $sheetIndex = 0;
        foreach ($type_map as $type=>$name){
            $objExcel->createSheet($sheetIndex);
            $objExcel->setActiveSheetIndex($sheetIndex);
            $objActSheet = $objExcel->getActiveSheet();
            $objActSheet->setTitle($name);
            $this->setExportData($objActSheet, $type, $wsp_ids, $wspRe);
            $sheetIndex++;
        }
        //设置默认到第一页
        $objExcel->setActiveSheetIndex(0);
        
        $path = 'wish_settle_export';
        $downFileName = $file_name . '.xlsx';
        $fileDir = ROOT_PATH . 'public' . DS . 'download' . DS . $path;
        $filePath = $fileDir . DS . $downFileName;
        //无文件夹，创建文件夹
        if(!is_dir($fileDir)){
            mkdir($fileDir, 0777, true);
        }
        if(!is_dir($fileDir)){
            $return['message'] = '创建文件夹失败';
            return $return;
        }
        $objWriter = \PHPExcel_IOFactory::createWriter($objExcel,'Excel2007');
        $objWriter->save($filePath);
        
        /**
         * 3、创建导出文件日志
         */
        try {
            $ledfModel = new LogExportDownloadFiles();
            $ledf_row = [
                'file_extionsion'=>'xlsx',
                'saved_path'=>$filePath,
                'download_file_name'=>$downFileName,
                'type'=>'supplier_export',
                'created_time'=>time(),
                'updated_time'=>time(),
            ];
            $ledfModel->save($ledf_row);
            $ledf_up = [
                'id'=>$ledfModel->id,
                'file_code'=>date('YmdHis') . $ledfModel->id
            ];
            $ledfModel->isUpdate(true)->save($ledf_up);
        } catch (Exception $e) {
            $return['message'] = '创建导出文件日志失败。' . $e->getMessage();
            @unlink($filePath);
            return $return;
        }
        
        /**
         * 4、整理返回数据
         */
        $return['ask'] = 1;
        $return['message'] = 'OK';
        $return['file_code'] = $ledf_up['file_code'];
        $return['file_name'] = $file_name;
        $return['file_path'] = $filePath;
        $return['download_url']  =  DS .'download'. DS . $path . DS .$downFileName;
        return $return;
    }
    
    /**
     * @desc 获取导出所需的交易类型数据 
     * @author wangwei
     * @date 2018-12-12 13:55:59
     * @param string $transaction_type
     * @param array $wsp_ids
     * @param array $wspRe
     * @return array[]|string[][]|unknown[]
     */
    public function setExportData(&$objActSheet, $transaction_type, $wsp_ids, $wspRe){
        /**
         * 1、查询数据
         */
        $con = [
            'wsrd.wish_settlement_report_id'=>['in',$wsp_ids],
            'wtt.transaction_type'=>$transaction_type,
        ];
        $field = [
            'wsrd.wish_settlement_report_id as account',//字段顺序需要,取别名
            'wsrd.date',
            'wsrd.wish_transaction_type_id',
            'wtt.name',
            'wtt.transaction_type',
            'wtt.sub_type',
            'wsrd.wish_order_id',
            'wsrd.other_id',
            'wsrd.transaction_id',
            'wsrd.amount',
            'wsrd.org_data',
        ];
        $wsrdModel = new WishSettlementReportDetail();
        $datas = $wsrdModel->getByConditionLeftJoinWTT($con, $field, 0, 1, 'wsrd.wish_settlement_report_id asc,wsrd.wish_transaction_type_id asc');
        foreach ($datas as &$row){
            $row = $row->toArray();
            //账号
            $row['account'] = $wspRe[$row['account']]['account'];
            //原始数据
            $org_data = json_decode($row['org_data'], true);
            foreach ($org_data as $kk=>$vv){
                $row['od_' . $kk] = $vv;
            }
            unset($row['org_data']);
            $row['date'] = date('Y-m-d H:i:s',$row['date']);
            $row['name'] = $row['name'] ? $row['name'] : '未定义类型';
        }
        
        /**
         * 2、表头
         */
        $title = [
            'account'=>'账号简称',
            'date'=>'时间',
            'wish_transaction_type_id'=>'交易类型ID',
            'name'=>'交易类型',
            'transaction_type'=>'结算类型',
            'sub_type'=>'结算子类型',
            'wish_order_id'=>'订单号',
            'other_id'=>'OtherID',
            'transaction_id'=>'交易号',
            'amount'=>'交易金额',
        ];
        $title_tmp = [];
        if($transaction_type=='transactions_being_paid'){
            $title_tmp = [
                'od_Quantity'=>'订购数量',
                'od_Price'=>'商品价格',
                'od_Shipping'=>'运费',
                'od_Cost'=>'扣除佣金后的商品价格',
                'od_ShippingCost'=>'扣除佣金后运费',
                'od_Total'=>'扣除佣金后订单总金额',
                'od_RefundedAmounttoUser'=>'用户退款金额',
                'od_RefundResponsibility%'=>'退款责任占比%',
                'od_RefundResponsibility'=>'退款责任金额',
                'od_PaidAmount'=>'实际支付金额',
                'od_Price&Shipping'=>'商品总价',
                'od_RevenueShare%'=>'佣金率%',
                'od_Commission'=>'佣金',
            ];
        }else if($transaction_type=='withheld_payments'){
            $title_tmp = [
                'od_Reason'=>'扣除数额明细',
                'od_WithheldAmount'=>'扣除数额',
                'od_Amount'=>'金额',
            ];
        }else if($transaction_type=='withheld_transactions_released'){
            $title_tmp = [
                'od_PaidAmount'=>'释放暂扣货款金额',
            ];
        }else if($transaction_type=='others_payments'){
            $title_tmp = [
                'od_Reason'=>'其他款项明细',
                'od_Amount'=>'其他款项',
            ];
        }else if($transaction_type=='transactions_being_refunded'){
            $title_tmp = [
                'od_Quantity'=>'数量',
                'od_Price'=>'商品价格',
                'od_Shipping'=>'运费',
                'od_Cost'=>'扣除佣金后商品价格',
                'od_ShippingCost'=>'扣除佣金后运费',
                'od_Total'=>'扣除佣金后订单总金额',
                'od_RefundedAmounttoUser'=>'用户退款金额',
                'od_RefundResponsibility%'=>'退款责任占比%',
                'od_RefundResponsibility'=>'退款责任金额',
                'od_DeductedAmount'=>'扣除退款金额',
            ];
        }else if($transaction_type=='fees'){
            $title_tmp = [
                'od_Reason'=>'其他费用明细',
                'od_Amount'=>'其他费用',
            ];
        }else if($transaction_type=='fines'){
            $title_tmp = [
                'od_Reason'=>'罚款或暂扣货款明细',
                'od_Amount'=>'罚款或暂扣货款金额',
            ];
        }
        $title = array_merge($title,$title_tmp);
        
        /**
         * 3、写入数据
         */
        $letter = range('A', 'Z');
        $line = 1;//行
        //生成列头
        $col = 0;//列
        foreach ($title as $title_view){
            $objActSheet->setCellValue($letter[$col].$line, $title_view);
            $col++;
        }
        $line++;
        //设置数据
        foreach ($datas as $k=>$data){
            $col = 0;//列
            foreach ($title as $kk=>$vv){
                $val = isset($data[$kk]) ? $data[$kk] : '';
                $objActSheet->setCellValue($letter[$col].$line, $val);
                $col++;
            }
            $line++;
        }
    }

    public function getSum(&$datas)
    {
        $sum = [
            'payment_for_transactions_sum' => 0,
            'refund_amount_sum' => 0,
            'withheld_transactions_released_sum' => 0,
            'other_payments_sum' => 0,
            'amount_withheld_sum' => 0,
            'fees_sum' => 0,
            'fines_sum' => 0,
            'wish_express_cash_back_sum' => 0,
            'gross_amount_sum' => 0,
            'order_amount' => 0,
            'gross_amount_sum_propertion' =>0,
            'refund_amount_aum_propertion' =>0
        ];
        foreach ($datas as $item) {
            $sum['payment_for_transactions_sum'] += $item['payment_for_transactions_sum'];
            $sum['refund_amount_sum'] += $item['refund_amount_sum'];
            $sum['withheld_transactions_released_sum'] += $item['withheld_transactions_released_sum'];
            $sum['other_payments_sum'] += $item['other_payments_sum'];
            $sum['amount_withheld_sum'] += $item['amount_withheld_sum'];
            $sum['fees_sum'] += $item['fees_sum'];
            $sum['fines_sum'] += $item['fines_sum'];
            $sum['wish_express_cash_back_sum'] += $item['wish_express_cash_back_sum'];
            $sum['gross_amount_sum'] += $item['gross_amount_sum'];
            $sum['order_amount'] += $item['order_amount'];
        }
        $sum['gross_amount_sum_propertion'] = $sum['order_amount'] >0 ? ($sum['gross_amount_sum'] /$sum['order_amount']) * 100 : 0;
        $sum['refund_amount_aum_propertion'] = $sum['order_amount'] >0 ? ($sum['refund_amount_sum'] /$sum['order_amount']) * 100: 0;
        foreach ($sum as &$k){
            $k = number_format($k,2);
        }
        return $sum;
    }

    public function getPropertion(&$data)
    {
        foreach ($data as &$v){
            $v['gross_amount_sum_propertion'] = $v['order_amount'] >0 ? ($v['gross_amount_sum'] /$v['order_amount']) * 100 : 0;
            $v['refund_amount_aum_propertion'] = $v['order_amount'] >0 ? ($v['refund_amount_sum'] /$v['order_amount']) * 100: 0;
        }
    }

    public function applyExportSum($params,$user_id)
    {
        //开始时间
        $start_time_str = paramNotEmpty($params,'date_s','2018-06-01');
        if(!$start_time = strtotime($start_time_str)){
            throw new Exception('错误的开始时间格式:' . $params['date_s']);
        }
        //结束时间
        $end_time_str = paramNotEmpty($params,'date_e',date('Y-m-d'));
        if(!$end_time = strtotime($end_time_str)){
            throw new Exception('错误的结束时间格式:' . $params['date_e']);
        }

        $exportFileName = "wish店铺资金核算({$start_time_str}-{$end_time_str})".time();
        $params['time_scope'] = "{$start_time_str}-{$end_time_str}";
        $model = new ReportExportFiles();
        $model->applicant_id = $user_id;
        $model->apply_time = time();
        $model->export_file_name = $exportFileName. '.xlsx';
        $model->status = 0;
        if (!$model->save()) {
            throw new Exception('导出请求创建失败');
        }

        $params['file_name'] = $exportFileName;
        $params['apply_id'] = $model->id;
        (new CommonQueuer(WishSettlementExport::class))->push($params);
    }

    public function allExport($params)
    {
        try {
            set_time_limit(0);
            ini_set('memory_limit','1024M');
            $validate = new FileExportValidate();
            if (!$validate->scene('export')->check($params)) {
                throw new Exception($validate->getError());
            }
            $result = $this->exportSum($params, $params['file_name'], 1);
            if(!$result['status']) throw new Exception($result['message']);
            if(is_file($result['file_path'])){
                $applyRecord = ReportExportFiles::get($params['apply_id']);
                $applyRecord->exported_time = time();
                $applyRecord->download_url = $result['download_url'];
                $applyRecord->status = 1;
                $applyRecord->isUpdate()->save();
            } else {
                throw new Exception('文件写入失败');
            }
        }catch (Exception $ex){
            $applyRecord = ReportExportFiles::get($params['apply_id']);
            $applyRecord->status = 2;
            $applyRecord->error_message = $ex->getMessage();
            $applyRecord->isUpdate()->save();
            throw new Exception($ex->getMessage());
        }
    }

    public function exportSum($params, $fileName = '', $isQueue = 0)
    {
        $header = $this->getHeader();
        $exportList = $this->getExportData($params);
        $this->formatSumExportData($exportList);
        if (!$exportList) {
            return '无相关数据';
        }
        $file = [
            'name' => $fileName ? $fileName: 'wish资金核算' .date("YmdHis"),
            'path' => 'wish_settle_export'
        ];
        $result = Excel::exportExcel2007($header, $exportList , $file ,$isQueue);
        return $result;
    }

    public function formatSumExportData(&$exportList){
        if (!count($exportList)) {
            return false;
        }
        foreach ($exportList as &$v){
            $v['gross_amount_sum_propertion'] = $v['gross_amount_sum_propertion'] . '%';
            $v['refund_amount_aum_propertion'] = $v['refund_amount_aum_propertion'] . '%';
        }
    }

    public function getHeader()
    {
        $header = [
            [ 'title'=>'日期范围	', 'key'=>'time_scope', 'width'=>25 , 'need_merge' => 0],
//            [ 'title'=>'账号id	', 'key'=>'account_id', 'width'=>20 , 'need_merge' => 0],
            [ 'title'=>'账号简称', 'key'=>'account', 'width'=>20 , 'need_merge' => 0],
            [ 'title'=>'销售员', 'key'=>'seller', 'width'=>15, 'need_merge' => 0 ],
            [ 'title'=>'订单金额', 'key'=>'order_amount', 'width'=>15, 'need_merge' => 0],
            [ 'title'=>'交易款项-放款', 'key'=>'payment_for_transactions_sum', 'width'=>20, 'need_merge' => 0 ],
            [ 'title'=>'退款金额', 'key'=>'refund_amount_sum', 'width'=>15, 'need_merge' => 0 ],
            [ 'title'=>'退款比例', 'key'=>'refund_amount_aum_propertion', 'width'=>15, 'need_merge' => 0 ],
            [ 'title'=>'释放暂扣货款', 'key'=>'withheld_transactions_released_sum', 'width'=>15, 'need_merge' => 0 ],
            [ 'title'=>'其他款项', 'key'=>'other_payments_sum', 'width'=>15, 'need_merge' => 0],
            [ 'title'=>'其他费用', 'key'=>'fees_sum', 'width'=>15, 'need_merge' => 0],
            [ 'title'=>'罚款或暂扣贷款金额', 'key'=>'fines_sum', 'width'=>15, 'need_merge' => 0],
            [ 'title'=>'现金返还', 'key'=>'wish_express_cash_back_sum', 'width'=>15, 'need_merge' => 0],
            [ 'title'=>'转账金额', 'key'=>'gross_amount_sum', 'width'=>15, 'need_merge' => 0],
            [ 'title'=>'转账比例', 'key'=>'gross_amount_sum_propertion', 'width'=>15, 'need_merge' => 0],
            [ 'title'=>'扣除数额', 'key'=>'amount_withheld_sum', 'width'=>15, 'need_merge' => 0],
        ];
        return $header;
    }

    public function getExportData($params)
    {
        $return = [];
        $start_time_str = paramNotEmpty($params,'date_s','2018-06-01');
        if(!$start_time = strtotime($start_time_str)){
            throw new Exception('错误的开始时间格式:' . $params['date_s']);
        }
        $params['date_s'] = $start_time;
        //结束时间
        $end_time_str = paramNotEmpty($params,'date_e',date('Y-m-d'));
        if(!$end_time = strtotime($end_time_str)){
            throw new Exception('错误的结束时间格式:' . $params['date_e']);
        }
        $params['date_e'] = $end_time;
        $where = $this->getWhere($params);
        $model = new WishSettlementReport();
        $field = [
            'account_id',
            'SUM( transaction_payment * to_cny_rate ) AS payment_for_transactions_sum',
            'SUM( (refund_amount+refund_deduction) * to_cny_rate ) AS refund_amount_sum',   //退款金额
            'SUM( withheld_transactions_released * to_cny_rate ) AS withheld_transactions_released_sum',
            'SUM( other_payments * to_cny_rate ) AS other_payments_sum',
            'SUM( amount_withheld * to_cny_rate ) AS amount_withheld_sum',
            'SUM( fees * to_cny_rate ) AS fees_sum',
            'SUM( fines * to_cny_rate ) AS fines_sum',
            'SUM( wish_express_cash_back * to_cny_rate ) AS wish_express_cash_back_sum',
            'SUM( gross_amount * to_cny_rate ) AS gross_amount_sum',
            'SUM( transaction_payment * to_cny_rate ) / 0.85 AS order_amount'
        ];
        $userCache = new User();
        // $page = param($params,'page',1);
        // $pageSize = param($params,'pageSize',50);
        if($res = $model->where($where)->field($field)->group('account_id')->order('account_id')->select()){
            $this->getPropertion($res);
            foreach ($res as $k=>$re){
                $re = $re->toArray();
                //金额处理
                foreach ($re as $kk=>&$vv){
                    if($kk!='account_id'){
                        $is_sym = false;
                        if($vv < 0){
                            $is_sym = true;
                        }
                        $vv = number_format($vv,2,'.','');
//                        if($is_sym){
//                            $vv = str_replace('-', '-$', $vv);
//                        }else{
//                            $vv = '$'.$vv;
//                        }
                    }
                }
                //日期时间
                $re['date_s'] = $start_time_str;
                $re['date_e'] = $end_time_str;
                $re['time_scope'] = $start_time_str . '-'.$end_time_str;
                //账号简称
                if($accInfo = Cache::store('wishAccount')->getAccount($re['account_id'])){
                    $re['account'] = $accInfo['code'];
                }
                //查询销售人员
                $re['seller'] = '--';
                if($seller_id = ChannelUserAccountMap::getSellerId(ChannelAccountConst::channel_wish, $re['account_id'])){
                    if($userInfo = $userCache->getOneUser($seller_id)){
                        $re['seller'] = $userInfo['realname'];
                    }
                }
                $return[] = $re;
            }
        }
        return $return;
    }
}