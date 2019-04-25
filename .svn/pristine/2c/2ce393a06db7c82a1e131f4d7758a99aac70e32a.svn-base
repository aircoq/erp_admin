<?php
namespace app\report\service;

use app\common\cache\Cache;
use app\common\model\wish\WishSettlement;
use think\Exception;
use app\common\model\wish\WishAccount;
use app\common\model\wish\WishSettlementReport;
use app\common\model\wish\WishSettlementReportDetail;
use app\common\model\wish\WishTransactionType;
use think\Db;
use app\common\model\wish\WishSettlementImport;
use app\common\service\Common;
use app\common\service\UniqueQueuer;
use app\report\queue\WishSettlementImportQueue;
use app\index\service\Currency;

/**
 * @desc wish结算报告服务类
 * @author wangwei
 * @date 2018-11-30 15:06:12
 */
class WishSettlementReportService
{
    //redis锁键前缀
    private $import_lock_key_prefix = 'lock:report:importWishSettlementReport:';
    //导入运行超时时间（秒）
    private $import_expired_time = 300;
    //结算单号
    private $import_invoice_number = null;
    //wish交易类型临时存储
    private $transaction_type_tmps = [];
    //redis保存文件键前缀
    private $import_save_key_prefix = 'file:upload:wish_settlement:';
    
    /**
     * @desc 导入wish结算报告数据
     * @author wangwei
     * @date 2018-11-30 15:09:14
     * @param string $file_name //Y 文件名称
     * @param string $content //Y 文件内容
     * @param bool $cover //N 是否允许覆盖更新
     */
    public function importSettlementReport($file_name, $content, $cover=false){
        $return = [
            'ask'=>0,
            'is_imported'=>0,//是否已导入
            'message'=>'importSettlementReport error',
        ];
        
        /**
         * 1、解析wish结算报告数据
         */
        $esdRe = $this->extractSettlementData($file_name, $content, $cover);
        if(!$esdRe['ask']){
            $return['message'] = 'extractSettlementData error:' . $esdRe['message'];
            return $return;
        }
        if($esdRe['ask'] == 2){
            $return['ask'] = 2;
            $return['is_imported'] = $esdRe['is_imported'];
            $return['message'] = $esdRe['message'];
            return $return;
        }
        
        /**
         * 2、导入前检查
         */
        //防止多进程同时导入
        $rcRe = $this->runCheck();
        if(!$rcRe['ask']){
            $return['ask'] = 2;
            $return['message'] = "当前结算单号：{$this->import_invoice_number}，正在导入。请{$rcRe['wait_time']}秒后再试!";
            return $return;
        }
        if(!$details = $esdRe['data']['details']){
            $return['message'] = 'extractSettlementData error:return details is Empty';
            return $return;
        }
        unset($esdRe['data']['details']);
        if(!$data = $esdRe['data']){
            $return['message'] = 'extractSettlementData error:return data is Empty';
            return $return;
        }
        
        /**
         * 3、整理数据
         */
        //1、wish_settlement_report表
        $wsr_row = [];////结算主表数据
        $this->arrangeSettlementReportData($data, $data['summary'], $wsr_row);
        
        //2、wish_settlement_report_detail表
        $wsrd_rows = [];//结算明细数据
        $order_datas = [];//订单放款退款数据
        //整理结算报告明细数据
        $this->arrangeSettlementReportDetailsData($details, $wsrd_rows, $order_datas, $wsr_row);
        //更新运行时间
        $this->updateRunTime();
        
        /**
         * 4、数据入库
         */
        try {
            Db::startTrans();
            //1、清空数据
            if($cover && $data['wish_settlement_report_id']){
                $this->clearDataById($data['wish_settlement_report_id']);
                //更新运行时间
                $this->updateRunTime();
            }
            
            //2、wish_settlement_report表
            $wish_settlement_report_id = (new WishSettlementReport())->insertGetId($wsr_row);
            
            //3、wish_settlement_report_detail表
            $wsrd_datas_arr = array_chunk($wsrd_rows, 1000);//按1000条拆分，避免数据过大，运行超时
            unset($wsrd_rows);//释放内存
            foreach ($wsrd_datas_arr as $k=>$wsrd_datas){
                foreach ($wsrd_datas as $kk=>$wsrd_data){
                    $wsrd_data['wish_settlement_report_id'] = $wish_settlement_report_id;
                    $wsrd_data['reason'] = param($wsrd_data, 'reason', '');
                    $wsrd_data['create_time'] = time();
                    (new WishSettlementReportDetail())->save($wsrd_data);
                }
                //更新运行时间
                $this->updateRunTime();
            }
            //4、wish_settlement表
            foreach ($order_datas as $wish_order_id=>$order_data){
//                 $order_data = [
//                     'order_id' => $wish_order_id,
//                     'account_id' => $data['account_id'],
//                     'refund_amount_by_report'=>-120.2,
//                     'refund_time_by_report'=>1124545,
//                     'transfer_amount'=>25.32,
//                     'transfer_time'=>857546464,
//                 ];
                $order_data['order_id'] = $wish_order_id;
                $order_data['account_id'] = $data['account_id'];
                $this->settleData($order_data);
            }
            Db::commit();
            $return['ask'] = 1;
            $return['message'] = '导入成功!';
        } catch (Exception $ex) {
            Db::rollback();
            $return['message'] = 'error_msg:' . $ex->getMessage().';file:'.$ex->getFile().';line:'.$ex->getLine();
        }
        
        //运行结束
        $this->runEnd();
        
        //返回数据
        return $return;
    }
    
    /**
     * @desc 整理结算报告主表数据
     * @author wangwei
     * @date 2018-11-30 20:53:37
     * @param array $data
     * @param array $summary
     * @param array $wsr_row
     */
    private function arrangeSettlementReportData($data, $summary, &$wsr_row){
        $wsr_row = [
            'account_id'=>$data['account_id'],
            'invoice_number'=>$data['invoice_number'],
            'invoice_date'=>$this->wishTime2LocTime(strtotime($data['invoice_date'])),
            'gross_amount'=>$data['gross_amount'],
            'price_and_shipping'=>$data['price_and_shipping'],
            'commission'=>$data['commission'],
            'payment_for_transactions'=>param($summary, 'payment_for_transactions',0),
            'transaction_payment'=>0,//统计交易款项（累加计算得出）
            'refund_amount'=>0,//退款金额（从明细里统计）
            'amount_withheld'=>param($summary, 'amount_withheld',0),
            'withheld_transactions_released'=>param($summary, 'withheld_transactions_released',0),
            'other_payments'=>param($summary, 'other_payments',0),
            'refund_deduction'=>param($summary, 'refund_deduction',0),
            'fees'=>param($summary, 'fees',0),
            'fines'=>param($summary, 'fines',0),
            'wish_express_cash_back'=>param($summary, 'wish_express_cash_back',0),
            'currency_code'=>'USD',
            'to_cny_rate'=>0,
            'create_time'=>time()
        ];
        $wsr_row['to_cny_rate'] = Currency::getCurrencyRateByTime($wsr_row['currency_code'],date('Y-m-d',$wsr_row['invoice_date']),'CNY');
    }
    
    /**
     * @desc 整理结算报告明细数据
     * @author wangwei
     * @date 2018-11-30 20:23:11
     * @param array $details
     * @param array $wsrd_rows
     * @param array $order_datas
     * @param array $wsr_row
     */
    private function arrangeSettlementReportDetailsData($details, &$wsrd_rows, &$order_datas, &$wsr_row){
        //transactions_being_paid（交易款项）
        if($tbp_rows = paramNotEmpty($details, 'transactions_being_paid', [])){
            
            //只有14列,数组里没有RevenueShare%、Commission字段，其实是缺失Price、Shipping数据
            $check_row = $tbp_rows[0];
            $check_row_count = count($check_row);
            $missing_field = false;//字段缺失
            if($check_row_count!=16 || !isset($check_row['Commission']) || !isset($check_row['RevenueShare%'])){
                if($check_row_count != 14){
                    throw new Exception('transactions_being_paid 类型数据字段缺失,字段数量:' . $check_row_count);
                }
                $missing_field = true;
            }
            
            foreach ($tbp_rows as $k=>$tbp_row){
                $loc_time = strtotime($tbp_row['Date']);//格式：03-03-2018 18:10:49UTC，strtotime函数直接转北京时间
                $wish_order_id = $tbp_row['OrderID'];
                //缺失Price、Shipping数据特殊处理
                if($missing_field){
                    $tbp_row['Commission'] = $tbp_row['Price&Shipping'];
                    $tbp_row['RevenueShare%'] = $tbp_row['PaidAmount'];
                    $tbp_row['Price&Shipping'] = $tbp_row['RefundResponsibility'];
                    $tbp_row['PaidAmount'] = $tbp_row['RefundResponsibility%'];
                    $tbp_row['RefundResponsibility'] = $tbp_row['RefundedAmounttoUser'];
                    $tbp_row['RefundResponsibility%'] = $tbp_row['Total'];
                    $tbp_row['RefundedAmounttoUser'] = $tbp_row['ShippingCost'];
                    $tbp_row['Total'] = $tbp_row['Cost'];
                    $tbp_row['ShippingCost'] = $tbp_row['Shipping'];
                    $tbp_row['Cost'] = $tbp_row['Price'];
                    $tbp_row['Shipping'] = 0;
                    $tbp_row['Price'] = 0;
                }
                
                $wsrd_rows[] = [
                    'wish_transaction_type_id'=>$this->getTransactionTypeId('transactions_being_paid', '--'),
                    'wish_order_id'=>$wish_order_id,
                    'transaction_id'=>$tbp_row['TransactionID'],
                    'amount'=>$tbp_row['Total'],
                    'total'=>$tbp_row['Total'],
                    'date'=>$loc_time,
                    'org_data'=>json_encode($tbp_row),
                ];
                //统计交易款项
                $wsr_row['transaction_payment'] += $tbp_row['Total'];
                if($tbp_row['RefundResponsibility%'] > 0){//放款前退款
                    $wsrd_rows[] = [
                        'wish_transaction_type_id'=>$this->getTransactionTypeId('transactions_being_paid', 'refund_responsibility'),
                        'wish_order_id'=>$wish_order_id,
                        'transaction_id'=>$tbp_row['TransactionID'],
                        'amount'=>0 - $tbp_row['RefundResponsibility'],//转为负数
                        'date'=>$loc_time,
                        'org_data'=>json_encode($tbp_row),
                    ];
                    //记录订单退款数据
                    if(isset($order_datas[$wish_order_id])){
                        $order_datas[$wish_order_id]['refund_amount_by_report'] = param($order_datas[$wish_order_id], 'refund_amount_by_report', 0) + (0 - $tbp_row['RefundResponsibility']);
                        $order_datas[$wish_order_id]['refund_time_by_report'] = $wsr_row['invoice_date'];
                    }else{
                        $order_datas[$wish_order_id] = [
                            'refund_amount_by_report'=>0 - $tbp_row['RefundResponsibility'],
                            'refund_time_by_report'=>$wsr_row['invoice_date'],
                        ];
                    }
                    //统计结算主表未放款的退款金额
                    $wsr_row['refund_amount'] += (0 - $tbp_row['RefundResponsibility']);
                }
                //记录订单放款数据
                if(isset($order_datas[$wish_order_id])){
                    $order_datas[$wish_order_id]['transfer_amount'] = param($order_datas[$wish_order_id], 'transfer_amount', 0) + $tbp_row['Total'];
                    $order_datas[$wish_order_id]['transfer_time'] = $wsr_row['invoice_date'];
                }else{
                    $order_datas[$wish_order_id] = [
                        'transfer_amount'=>$tbp_row['Total'],
                        'transfer_time'=>$wsr_row['invoice_date'],
                    ];
                }
            }
        }
        //transactions_being_paid->wish_express_cash_back（现金返还）
        $wsrd_rows[] = [
            'wish_transaction_type_id'=>$this->getTransactionTypeId('transactions_being_paid', 'wish_express_cash_back'),
            'amount'=>$wsr_row['wish_express_cash_back'],
            'date'=>$wsr_row['invoice_date'],
            'org_data'=>'{}',
        ];
        
        //withheld_payments（扣除数额）
        if($wp_rows = paramNotEmpty($details, 'withheld_payments', [])){
            foreach ($wp_rows as $k=>$wp_row){
                $loc_time = strtotime($wp_row['Date']);//格式：03-03-2018 18:10:49UTC，strtotime函数直接转北京时间
                $wsrd_rows[] = [
                    'wish_transaction_type_id'=>$this->getTransactionTypeId('withheld_payments', paramNotEmpty($wp_row, 'ReasonType','--')),
                    'wish_order_id'=>$wp_row['OrderID'],
                    'other_id'=>$wp_row['OtherID'],
                    'amount'=>0 - $wp_row['WithheldAmount'],//转为负数
                    'date'=>$loc_time,
                    'reason'=>$wp_row['Reason'],
                    'org_data'=>json_encode($wp_row),
                ];
            }
        }
        
        //others_payments（其他款项）
        if($op_rows = paramNotEmpty($details, 'others_payments', [])){
            foreach ($op_rows as $k=>$op_row){
                $loc_time = strtotime($op_row['Date']);//格式：03-03-2018 18:10:49UTC，strtotime函数直接转北京时间
                $wsrd_rows[] = [
                    'wish_transaction_type_id'=>$this->getTransactionTypeId('others_payments', paramNotEmpty($op_row, 'ReasonType','--')),
                    'wish_order_id'=>$op_row['OrderID'],
                    'other_id'=>$op_row['OtherID'],
                    'amount'=>$op_row['Amount'],
                    'date'=>$loc_time,
                    'reason'=>$op_row['Reason'],
                    'org_data'=>json_encode($op_row),
                ];
            }
        }
        
        //withheld_transactions_released（释放暂扣款项）
        if($wtr_rows = paramNotEmpty($details, 'withheld_transactions_released', [])){
            foreach ($wtr_rows as $k=>$wtr_row){
                $loc_time = strtotime($wtr_row['Date']);//格式：03-03-2018 18:10:49UTC，strtotime函数直接转北京时间
                $wsrd_rows[] = [
                    'wish_transaction_type_id'=>$this->getTransactionTypeId('withheld_transactions_released', '--'),
                    'wish_order_id'=>$wtr_row['OrderID'],
                    'amount'=>$wtr_row['Amount'],
                    'date'=>$loc_time,
                    'org_data'=>json_encode($wtr_row),
                ];
            }
        }
        
        //transactions_being_refunded（扣除退款）
        if($wbr_rows = paramNotEmpty($details, 'transactions_being_refunded', [])){
            //只有11列,数组里没有DeductedAmount、RefundResponsibility字段，其实是缺失Price、Shipping数据
            $check_row = $wbr_rows[0];
            $check_row_count = count($check_row);
            $missing_field = false;//字段缺失
            if($check_row_count!=13 || !isset($check_row['DeductedAmount']) || !isset($check_row['RefundResponsibility'])){
                if($check_row_count != 11){
                    throw new Exception('transactions_being_refunded 类型数据字段缺失,字段数量:' . $check_row_count);
                }
                $missing_field = true;
            }
            foreach ($wbr_rows as $k=>$wbr_row){
                $loc_time = strtotime($wbr_row['Date']);//格式：03-03-2018 18:10:49UTC，strtotime函数直接转北京时间
                $wish_order_id = $wbr_row['OrderID'];
                //缺失Price、Shipping数据特殊处理
                if($missing_field){
                    $wbr_row['DeductedAmount'] = $wbr_row['RefundResponsibility%'];
                    $wbr_row['RefundResponsibility'] = $wbr_row['RefundedAmounttoUser'];
                    $wbr_row['RefundResponsibility%'] = $wbr_row['Total'];
                    $wbr_row['RefundedAmounttoUser'] = $wbr_row['ShippingCost'];
                    $wbr_row['Total'] = $wbr_row['Cost'];
                    $wbr_row['ShippingCost'] = $wbr_row['Shipping'];
                    $wbr_row['Cost'] = $wbr_row['Price'];
                    $wbr_row['Shipping'] = 0;
                    $wbr_row['Price'] = 0;
                }
                $wsrd_rows[] = [
                    'wish_transaction_type_id'=>$this->getTransactionTypeId('transactions_being_refunded', '--'),
                    'wish_order_id'=>$wish_order_id,
                    'transaction_id'=>$wbr_row['TransactionID'],
                    'amount'=>$wbr_row['DeductedAmount'],
                    'total'=>$wbr_row['Total'],
                    'date'=>$loc_time,
                    'org_data'=>json_encode($wbr_row),
                ];
                //记录订单退款数据
                if(isset($order_datas[$wish_order_id])){
                    $order_datas[$wish_order_id]['refund_amount_by_report'] = param($order_datas[$wish_order_id], 'refund_amount_by_report', 0) + $wbr_row['DeductedAmount'];
                    $order_datas[$wish_order_id]['refund_time_by_report'] = $wsr_row['invoice_date'];
                }else{
                    $order_datas[$wish_order_id] = [
                        'refund_amount_by_report'=>$wbr_row['DeductedAmount'],
                        'refund_time_by_report'=>$wsr_row['invoice_date'],
                    ];
                }
            }
        }
        
        //fines（罚款或暂扣货款金额）
        if($fines_rows = paramNotEmpty($details, 'fines', [])){
            foreach ($fines_rows as $k=>$fines_row){
                $loc_time = strtotime($fines_row['Date']);//格式：03-03-2018 18:10:49UTC，strtotime函数直接转北京时间
                $wsrd_rows[] = [
                    'wish_transaction_type_id'=>$this->getTransactionTypeId('fines', paramNotEmpty($fines_row, 'ReasonType','--')),
                    'wish_order_id'=>$fines_row['OrderID'],
                    'other_id'=>$fines_row['OtherID'],
                    'amount'=>$fines_row['Amount'],
                    'date'=>$loc_time,
                    'reason'=>$fines_row['Reason'],
                    'org_data'=>json_encode($fines_row),
                ];
            }
        }
        
        //fees（其他费用）
        if($fees_rows = paramNotEmpty($details, 'fees', [])){
            foreach ($fees_rows as $k=>$fees_row){
                $loc_time = strtotime($fees_row['Date']);//格式：03-03-2018 18:10:49UTC，strtotime函数直接转北京时间
                $wsrd_rows[] = [
                    'wish_transaction_type_id'=>$this->getTransactionTypeId('fees', paramNotEmpty($fees_row, 'ReasonType','--')),
                    'wish_order_id'=>$fees_row['OrderID'],
                    'other_id'=>$fees_row['OtherID'],
                    'amount'=>$fees_row['Amount'],
                    'date'=>$loc_time,
                    'reason'=>$fees_row['Reason'],
                    'org_data'=>json_encode($fees_row),
                ];
            }
        }
        
    }
    
    /**
     * @desc 获取指定类型id
     * @author wangwei
     * @date 2018-11-30 19:16:08
     * @param string $transaction_type
     * @param string $sub_type
     * @return number|mixed|unknown
     */
    private function getTransactionTypeId($transaction_type, $sub_type){
        $wish_transaction_type_id = 0;
        $type_str = $transaction_type . '|' . $sub_type;
        if(isset($this->transaction_type_tmps[$type_str])){
            $wish_transaction_type_id = $this->transaction_type_tmps[$type_str];
        }else{
            $wtt_row = [
                'transaction_type'=>$transaction_type,
                'sub_type'=>$sub_type
            ];
            if($wtt_has = WishTransactionType::where($wtt_row)->field('id')->find()){
                $wish_transaction_type_id = $wtt_has['id'];
            }else{
                $wtt_row['create_time'] = time();
                $wish_transaction_type_id = (new WishTransactionType())->insertGetId($wtt_row);
            }
            $this->transaction_type_tmps[$type_str] = $wish_transaction_type_id;
        }
        return $wish_transaction_type_id;
    }
    
    /**
     * @desc 速卖通时间转北京时间
     * @author wangwei
     * @date 2018-11-30 18:04:04
     * @param int $wishTime
     * @return int
     */
    public function wishTime2LocTime($wishTime){
        if($time = intval($wishTime)){
            return $time + 8 * 3600;
        }
        return 0;
    }
   
    /**
     * @desc 清理数据
     * @author wangwei
     * @date 2018-11-30 17:33:57
     * @param int $wish_settlement_report_id;
     */
    private function clearDataById($wish_settlement_report_id){
        if($wish_settlement_report_id){
            WishSettlementReport::where(['id'=>$wish_settlement_report_id])->delete();
            WishSettlementReportDetail::where(['wish_settlement_report_id'=>$wish_settlement_report_id])->delete();
        }
    }

    /**
     * @desc 解析wish结算报告数据
     * @author wangwei
     * @date 2018-11-28 18:34:47
     * @param string $file_name //Y 文件名称
     * @param string $content //Y 文件内容
     * @param bool $cover //N 是否允许覆盖更新
     */
    public function extractSettlementData($file_name, $content, $cover=false){
        $return = [
            'ask'=>0,
            'message'=>'extractSettlementData error',
            'is_imported'=>0,//是否已导入
            'data'=>[],
        ];
        
        /**
         * 1、参数校验
         */
        if(empty($file_name)){
            $return['message'] = 'file_name not Empty';
            return $return;
        }
        if(!is_string($file_name)){
            $return['message'] = 'file_name not string';
            return $return;
        }
        if(empty($content)){
            $return['message'] = 'content not Empty';
            return $return;
        }
        if(!is_string($content)){
            $return['message'] = 'content not string';
            return $return;
        }
        set_time_limit(0);
        //设置正则最大回溯值
        ini_set('pcre.backtrack_limit', 999999999);
        //设置最大内存
        ini_set('memory_limit', '2048M');
        
        /**
         * 2、校验wish账号
         */
        //在文件名中提取wish商户ID
        $abfnRe = $this->getAccountByFileName($file_name);
        if(!$abfnRe['ask']){
            $return['message'] = 'getAccountByFileName error:' . $abfnRe['message'];
            return $return;
        }
        $wa_row = $abfnRe['wish_account'];
        $invoice_number = $abfnRe['invoice_number'];
        $invoice_date = $abfnRe['invoice_date'];

        /**
         * 3、提取头尾数据
         */
        $hfdRe = $this->getHeaderFootData($content);
        if(!$hfdRe['ask']){
            $return['message'] = $hfdRe['message'];
            return $return;
        }
        $h_data = $hfdRe['h_data'];
        $f_data = $hfdRe['f_data'];
        //如果文件里没有结算单号和结算时间，从名字里取
        if(!(isset($h_data['invoice_number']) && $h_data['invoice_number'])){
            if(!$invoice_number){
                $return['message'] = '结算报告里没有invoice_number,请在文件名中维护它!';
                return $return;
            }
            $h_data['invoice_number'] = $invoice_number;
        }
        if(!(isset($h_data['invoice_date']) && $h_data['invoice_date'])){
            if(!$invoice_date){
                $return['message'] = '结算报告里没有invoice_date,请在文件名中维护它!';
                return $return;
            }
            $h_data['invoice_date'] = $invoice_date;
        }
        
        //设置当前导入的结算报告单号
        $this->import_invoice_number = $h_data['invoice_number'];
        //检查是否已经导入
        if($wsr_row = WishSettlementReport::where(['invoice_number'=>$h_data['invoice_number']])->field('id')->find()){
            if(!$cover){
                $return['ask'] = 2;
                $return['is_imported'] = 1;
                $return['message'] = "结算单号:{$h_data['invoice_number']}已导入,无需重复导入!";
                return $return;
            }
        }
        
        /**
         * 4、各个类型的数据
         */
        $b_match = preg_match_all('/-+\n\|(.*?)\|\n-+\n(.*?)\n-+[\nTotal]?\s+(.*?)\n/is', $content, $t_m);
        if(!$b_match){
            $return['message'] = 'content format error:004';
            return $return;
        }
        if(count($t_m) != 4){
            $return['message'] = 'content format error:005';
            return $return;
        }
        $details = [];
        $summary = [];
        $total_arr = $t_m[3];//每个明细下的total
        $total_tmp = [];//临时total数据
        $withheld_payments_total = 0;//对withheld_payments特殊处理，累加得到total
        foreach ($t_m[1] as $tk=>$tv){
            //类型键名
            $key = strtolower(preg_replace('/[^A-Za-z0-9]+/', '_', trim($tv)));
            
            //替换多个换行为一个换行
            $val_str = preg_replace('/\n{2,}/', "\n",$t_m[2][$tk]);
            //去除最左侧空字符
            $val_str = ltrim($val_str);
            //根据换行拆分行数据
            $val_arr = preg_split('/\n/', $val_str);
            //解析行数据
            //summary特殊处理
            if($key=='summary'){
                foreach ($val_arr as $vk=>$vv){
                    //去掉$符、|符、和首位空格和后面的空格，保留负号
                    $vv = trim(preg_replace('/\|?\s*(.*?)(-?)\s*\$\s*(.*[^\|])\|?/', '\1  \2\3',$vv));
                    if(empty($vv)){
                        continue;
                    }
                    //按空格拆分
                    $vv_arr = preg_split('/\s{2,}/', $vv);
                    if(count($vv_arr) != 2){
                        continue;
                    }
                    $key = strtolower(preg_replace('/[^A-Za-z0-9]+/', '_', $vv_arr[0]));
                    $summary[$key] = $this->extractAmount($vv_arr[1]);
                }
                continue;
            }
            //其他类型统一处理
            $val_data = [];//数据
            $val_data_title = [];//标题
            foreach ($val_arr as $vk=>$vv){
                //去除首位的|符和空格
                $vv = preg_replace('/\|?\s*(.*[^\|\s])\s*\|?/', '\1',$vv);
                if(empty($vv)){
                    continue;
                }
                //拆分每一个字段
                $vv_arr = preg_split('/\s*\|\s*/', $vv);
                //首行为标题
                if($vk == 0){
                    //去除空格处理
                    foreach ($vv_arr as $vv_arr_val){
                        $val_data_title[] = preg_replace('/\s*/', '',$vv_arr_val);
                    }
                }else{
                    if(empty($val_data_title)){
                        $return['message'] = 'content format error:006';
                        return $return;
                    }
                    $vv_arr_data = [];
                    //针对others_payments、withheld_payments、fines、fees类型提取出订单号和关键词
                    if(in_array($key, ['others_payments','withheld_payments','fines','fees'])){
                        foreach ($vv_arr as $vvk=>$vvv){
                            $vad_key = $val_data_title[$vvk];
                            if($vad_key == 'Reason'){
                                //从Reason里提取订单号和关键词
                                $this->extractOrderNoByReason(trim($vvv), $vv_arr_data);
                            }else if($vad_key=='Date'){
                                //时间格式调整
                                $vv_arr_data[$vad_key] = preg_replace('/^\s*(\d{2})-(\d{2})-(\d{4})(.*)\s*$/', '\3-\1-\2\4',$vvv);
                            }else {
                                //去除左边的$符和右边的%及两端空格
                                $vv_arr_data[$vad_key] = $this->extractAmount($vvv);
                            }
                            //累加withheld_payments类型的WithheldAmount字段
                            if($key=='withheld_payments' && $vad_key=='WithheldAmount'){
                                $withheld_payments_total += $vv_arr_data[$vad_key];
                            }
                        }
                    }else{
                        foreach ($vv_arr as $vvk=>$vvv){
                            $vad_key = $val_data_title[$vvk];
                            if($vad_key=='Date'){
                                //时间格式调整
                                $vv_arr_data[$vad_key] = preg_replace('/^\s*(\d{2})-(\d{2})-(\d{4})(.*)\s*$/', '\3-\1-\2\4',$vvv);
                            }else{
                                //去除左边的$符和右边的%及两端空格
                                $vv_arr_data[$vad_key] = $this->extractAmount($vvv);
                            }
                        }
                    }
                    $val_data[] = $vv_arr_data;
                }
            }
            $details[$key] = $val_data;
            $total_tmp[$key] = preg_replace('/[^0-9\.\-]+/', '', param($total_arr, $tk, 0));
        }
        //没有给出汇总数据，从total中取
        if(!isset($summary['payment_for_transactions']) && isset($total_tmp['transactions_being_paid'])){
            $summary['payment_for_transactions'] = $total_tmp['transactions_being_paid'];
        }
        if(!isset($summary['amount_withheld']) && isset($total_tmp['withheld_payments'])){
            $summary['amount_withheld'] = $total_tmp['withheld_payments'] > 0 ? $total_tmp['withheld_payments'] : $withheld_payments_total;
        }
        if(!isset($summary['withheld_transactions_released']) && isset($total_tmp['withheld_transactions_released'])){
            $summary['withheld_transactions_released'] = $total_tmp['withheld_transactions_released'];
        }
        if(!isset($summary['other_payments']) && isset($total_tmp['others_payments'])){
            $summary['other_payments'] = $total_tmp['others_payments'];
        }
        if(!isset($summary['refund_deduction']) && isset($total_tmp['transactions_being_refunded'])){
            $summary['refund_deduction'] = $total_tmp['transactions_being_refunded'];
        }
        if(!isset($summary['fees']) && isset($total_tmp['fees'])){
            $summary['fees'] = $total_tmp['fees'];
        }
        if(!isset($summary['fines']) && isset($total_tmp['fines'])){
            $summary['fines'] = $total_tmp['fines'];
        }
        //数据校验
        if(empty($summary)){
            $return['message'] = 'content format error:007';
            return $return;
        }
        if(empty($details)){
            $return['message'] = 'content format error:008';
            return $return;
        }
        
        /**
         * 5、整理返回数据
         */
        $return['ask'] = 1;
        $return['message'] = 'success';
        $return['data'] = [
            'wish_settlement_report_id'=>param($wsr_row, 'id', null),
            'account_id'=>$wa_row['id'],
            'merchant_id'=>$wa_row['merchant_id'],
            'invoice_number'=>$h_data['invoice_number'],
            'invoice_date'=>$h_data['invoice_date'],
            'gross_amount'=>$f_data['gross_amount'],
            'price_and_shipping'=>param($f_data, 'price_and_shipping',0),
            'commission'=>param($f_data, 'commission',0),
            'summary'=>$summary,
            'details'=>$details,
        ];
        return $return;
    }
    
    /**
     * @desc 从Reason里提取订单号和关键词
     * @author wangwei
     * @date 2018-12-28 14:51:36
     * @param string $reason
     * @param array $vv_arr_data
     */
    public function extractOrderNoByReason($reason,&$vv_arr_data){
        $vv_arr_data['Reason'] = $reason;
        $vv_arr_data['ReasonType'] = '';
        $vv_arr_data['OrderID'] = '';
        $vv_arr_data['OtherID'] = '';
        //提取订单号和关键词
        $reason_length = strlen($vv_arr_data['Reason']);
        if(empty($vv_arr_data['Reason'])){//为空,不提取
            $vv_arr_data['ReasonType'] = '--';
        }else if($reason_length <= 120){//小于120,直接提取
            if(preg_match('/^(.*?order)\:?\s*([a-z0-9]{24})$/is', $vv_arr_data['Reason'], $vvv_m)){
                $vv_arr_data['ReasonType'] = strtolower(preg_replace('/[^A-Za-z0-9]+/', '_', $vvv_m[1]));
                $vv_arr_data['OrderID'] = $vvv_m[2];
            }else if(preg_match_all('/([^\s]+)\:?\s*([a-z0-9]{24})/is', $vv_arr_data['Reason'], $vvv_m)){
                $title_arr = $vvv_m[1];
                $no_arr = $vvv_m[2];
                foreach ($title_arr as $ta_k=>$title){
                    $title = strtolower($title);
                    if($title=='order'){
                        $vv_arr_data['OrderID'] = $no_arr[$ta_k];
                    }else{
                        $vv_arr_data['OtherID'] = $no_arr[$ta_k];
                    }
                }
                $ReasonTypeTmp = str_replace($no_arr, '#no', $vv_arr_data['Reason']);
                $vv_arr_data['ReasonType'] = strtolower(preg_replace('/[^A-Za-z0-9\#]+/', '_', $ReasonTypeTmp));
            }else if(preg_match('/^(.*?)([a-z0-9]{24})(.*?)$/', $vv_arr_data['Reason'], $vvv_m)){//提取不到单号
                $vv_arr_data['ReasonType'] = strtolower(preg_replace('/[^A-Za-z0-9\#]+/', '_', $vvv_m[1].'_#no_'.$vvv_m[3]));
                $vv_arr_data['OtherID'] = $vvv_m[2];
            }else{
                $vv_arr_data['ReasonType'] = strtolower(preg_replace('/[^A-Za-z0-9]+/', '_', substr($vv_arr_data['Reason'],0,100)));
            }
        }else{//大于120,截取首位提取
            $sub_reason = substr($vv_arr_data['Reason'],0,30) . '_#sub_' . substr($vv_arr_data['Reason'],-15);
            $vv_arr_data['ReasonType'] = strtolower(preg_replace('/[^A-Za-z0-9\#]+/', '_', $sub_reason));
        }
    }
    
    /**
     * @desc 提取金额，去除左边的$符和右边的%及两端空格
     * @author wangwei
     * @date 2019-3-12 17:17:24
     */
    public function extractAmount($v){
        $val = preg_replace('/^(\-?)\$?\s*(.*?)\s*\%?$/s', '\1\2',$v);
        return str_replace(',', '', $val);
    }
    
    /**
     * @desc 根据文件名称获取账号数据 
     * @author wangwei
     * @date 2018-12-6 20:40:24
     * @param string $file_name
     */
    public function getAccountByFileName($file_name){
        $return = [
            'ask'=>0,
            'wish_account'=>[],
            'invoice_number'=>'',
            'invoice_date'=>'',
            'message'=>'getAccountByFileName error'
        ];
        if(empty($file_name)){
            $return['message'] = 'file_name not empty';
            return $return;
        }
        if(!$name_explode = explode('_', $file_name)){
            $return['message'] = 'explode file_name error';
            return $return;
        }
        if(!$merchant_id = trim($name_explode[0])){
            $return['message'] = 'merchant_id not Empty';
            return $return;
        }
        //从名字截取日期和单号
        if(preg_match('/_(\d{4}-\d{2}-\d{2})#([a-z0-9]+)\.?/', $file_name, $m)){
            $return['invoice_date'] = $m[1];
            $return['invoice_number'] = $m[2];
        }
        //通过merchant_id查询账号
        if(!$wa_rows = WishAccount::where(['merchant_id'=>$merchant_id])->field('id,code,merchant_id')->select()){
            $return['message'] = "wish商户ID：{$merchant_id}，未绑定wish账户，请先设置绑定!";
            return $return;
        }
        if(count($wa_rows) > 1){
            $code_str = join(',', array_column($wa_rows, 'code'));
            $return['message'] = "wish商户ID：{$merchant_id}，绑定多个wish账户：{$code_str}。请检查授权绑定!";
            return $return;
        }
        //返回数据
        $return['ask'] = 1;
        $return['wish_account'] = end($wa_rows);
        $return['message'] = 'success';
        return $return;
    }
    
    /**
     * @desc 提取头尾数据
     * @author wangwei
     * @date 2018-12-7 15:16:42
     * @param string $content
     */
    public function getHeaderFootData($content){
        $return = [
            'ask'=>0,
            'messsage'=>'getHeaderFootData error',//错误信息
            'h_data'=>[],//头部数据
            'f_data'=>0,//尾部数据
        ];
        
        /**
         * 1、简单校验
         */
        if(empty($content)){
            $return['message'] = 'content not Empty';
            return $return;
        }
        if(!is_string($content)){
            $return['message'] = 'content not string';
            return $return;
        }
        $hf_arr = preg_split('/-+\n(.*)-+\s/is', $content);
        if(count($hf_arr) != 2){
            $return['message'] = 'content format error:001';
            return $return;
        }
        
        /**
         * 2、头部数据
         */
        $h_str = $hf_arr[0];
        $h_data = [];
        if(preg_match_all('/(.*?)\s*\:\s*(.*?)\n/i',$h_str,$h_m)){
            foreach ($h_m[1] as $hk=>$k_str){
                $key = strtolower(preg_replace('/[^A-Za-z0-9]+/', '_', $k_str));
                $h_data[$key] = preg_replace('/\:?\s*(.*)/s', '\1',$h_m[2][$hk]);
            }
        }
        if(empty($h_data)){
            $return['message'] = 'content format error:002';
            return $return;
        }
        //校验invoice_number、invoice_date字段是否存在
//         if(!(isset($h_data['invoice_number']) && $h_data['invoice_number'])){
//             $return['message'] = 'h_data not invoice_number';
//             return $return;
//         }
//         if(!(isset($h_data['invoice_date']) && $h_data['invoice_date'])){
//             $return['message'] = 'h_data not invoice_date';
//             return $return;
//         }
        
        /**
         * 3、尾部数据
         */
        $f_str = $hf_arr[1];
        $f_str = trim($f_str);
        $f_data = [];
        if($f_arr = preg_split('/\n/', $f_str)){
            foreach ($f_arr as $f_item){
                //去除首位的|符和空格
                $f_item = preg_replace('/\|?\s*(.*[^\|])\|?/s', '\1',$f_item);
                if(empty($f_item)){
                    continue;
                }
                //按空格拆分
                $f_item_arr = preg_split('/\s{4,}/', $f_item);
                if(count($f_item_arr) != 2){
                    continue;
                }
                $key = strtolower(preg_replace('/[^A-Za-z0-9]+/', '_', $f_item_arr[0]));
                $f_data[$key] = $this->extractAmount(trim($f_item_arr[1]));
            }
        }
        if(empty($f_data)){
            $return['message'] = 'content format error:003';
            return $return;
        }
        //校验gross_amount字段是否存在
        if(!(isset($f_data['gross_amount']) && $f_data['gross_amount'])){
            $return['message'] = 'f_data not gross_amount';
            return $return;
        }
        
        /**
         * 4、返回数据
         */
        $return['ask'] = 1;
        $return['message'] = 'success';
        $return['h_data'] = $h_data;//头部数据
        $return['f_data'] = $f_data;//尾部数据
        return $return;
    }
    
    /**
     * @desc 运行检查
     * @author wangwei
     * @date 2018-11-30 18:26:02
     */
    private function runCheck()
    {
        $return = [
            'ask'=>0,
            'wait_time'=>0,//等待时间(秒)
        ];
        //获取redis
        $key = $this->import_lock_key_prefix . $this->import_invoice_number;
        if($run_time = Cache::handler()->get($key)){
            if(time() - $run_time > $this->import_expired_time){
                $return['ask'] = 1;
            }else{
                $return['wait_time'] = $this->import_expired_time - (time() - $run_time);
            }
        }else{
            Cache::handler()->set($key, time());
            $return['ask'] = 1;
        }
        return $return;
    }
    
    /**
     * @desc 更新运行时间
     * @author wangwei
     * @date 2018-11-30 18:26:19
     * @return unknown
     */
    private function updateRunTime()
    {
        $key = $this->import_lock_key_prefix . $this->import_invoice_number;
        return Cache::handler()->set($key, time());
    }
    
    /**
     * @desc 运行结束
     * @author wangwei
     * @date 2018-11-30 18:26:25
     */
    private function runEnd()
    {
        $key = $this->import_lock_key_prefix . $this->import_invoice_number;
        Cache::handler()->del($key);
    }
    
    /**
     * @desc 判断文件是否存在 
     * @author wangwei
     * @date 2018-12-7 10:00:49
     */
    private function fileExists($file_md5)
    {
        $key = $this->import_save_key_prefix . $file_md5;
        //设置原子锁
        if(!(Cache::handler()->set($key, time(), ['nx', 'ex' => 120]))){
            return true;
        }
        return Cache::handler(true)->exists($key);
    }
    
    /**
     * @desc 文件存储到redis
     * @author wangwei
     * @date 2018-12-7 10:00:49
     */
    private function saveFile($file_md5,$content)
    {
        $key = $this->import_save_key_prefix . $file_md5;
        //清理原子锁
        Cache::handler()->del($key);
        return Cache::handler(true)->set($key, $content);
    }
    
    /**
     * @desc 从redis获取文件内容
     * @author wangwei
     * @date 2018-12-7 10:00:49
     */
    public function getFile($file_md5)
    {
        $key = $this->import_save_key_prefix . $file_md5;
        return Cache::handler(true)->get($key);
    }
    
    /**
     * @desc 从redis删除文件
     * @author wangwei
     * @date 2018-12-7 10:00:49
     */
    private function deleteFile($file_md5)
    {
        $key = $this->import_save_key_prefix . $file_md5;
        return Cache::handler(true)->del($key);
    }
    
    /**
     * 处理wish settle数据
     * @param $order
     * @example $order = [
     *         'account_id'=>'',//Y 账号id
     *         'order_id'=>'',//Y 平台订单id
     *         'transfer_time'=>'',//N 结算(放款)时间
     *         'transfer_amount'=>'',//N 结算(放款)金额
     *         'payment_time'=>'',//N 付款时间
     *         'payment_amount'=>'',//N 付款金额
     *         'refund_time'=>'',//N 退款时间
     *         'refund_amount'=>'',//N 退款金额
     *         'refund_time_by_report'=>'',//N 退款时间(来自结算报告)
     *         'refund_amount_by_report'=>'',//N 退款金额(来自结算报告)
     *         'currency_code'=>'',//N 币种
     * ];
     * @throws \think\Exception
     */
    public function settleData($order)
    {
        if (!param($order, 'order_id') || !param($order, 'account_id')) {
            return;
        }

        $settleModel = new WishSettlement();

        $settle = [];
        $settle['account_id'] = $order['account_id'];
        $settle['wish_order_id'] = $order['order_id'];

        $res = $settleModel->field('payment_time,transfer_time,to_cny_rate')->where('wish_order_id', $order['order_id'])->find();
        $settle['transfer_time'] = param($order,'transfer_time',0)>0 ? param($order,'transfer_time',0) :param($res,'transfer_time',0);
        $settle['payment_time'] = param($order,'payment_time',0)>0 ? param($order,'payment_time',0) :param($res,'payment_time',0);
       if(isset($order['refund_time'])){
           $settle['refund_time'] = $order['refund_time'];
       }
       if (isset($order['refund_amount'])) {
           $settle['refund_amount'] = $order['refund_amount'];
       }
       if(isset($order['refund_amount_by_report'])){
           $settle['refund_amount_by_report'] = $order['refund_amount_by_report'];
       }
       if (isset($order['refund_time_by_report'])) {
           $settle['refund_time_by_report'] = $order['refund_time_by_report'];
       }
       if (isset($order['transfer_amount'])) {
           $settle['transfer_amount'] = $order['transfer_amount'];
       }
       if (isset($order['payment_amount'])) {
           $settle['payment_amount'] = $order['payment_amount'];
       }
       if (isset($order['shipping_time']) && $order['shipping_time']>0) {
           $settle['shipping_time'] = $order['shipping_time'];
       }
       if (isset($order['shipping_status']) && $order['shipping_status']) {
           $settle['shipping_status'] = $order['shipping_status'];
       }
        $settle['currency_code'] = param($order, 'currency_code','USD');
        //将当前币种的汇率，只在第一次插入，使用付款时间的汇率
        if (param($settle,'currency_code') && $settle['payment_time']>0) {
            //查询对人民币汇率
            if(!intval(param($res,'to_cny_rate'))){
                $settle['to_cny_rate'] = Currency::getCurrencyRateByTime($settle['currency_code'],date('Y-m-d',$settle['payment_time']),'CNY');
            }
        }
        if ($settle['transfer_time'] > 0 && $settle['payment_time'] > 0 && ($settle['transfer_time'] - $settle['payment_time'] > 0)) {
            $settle['account_period_week'] = round(($settle['transfer_time'] - $settle['payment_time']) / 604800);
        }
        if (!$res) {
            $settle['create_time'] = time();
            $settleModel->isUpdate(false)->save($settle);
        } else {
            $settle['update_time'] = time();
            $res->isUpdate(true)->save($settle);
        }
    }
    
    /**
     * @desc 保存结算报告文件
     * @author wangwei
     * @date 2018-12-6 21:02:27
     * @param string $file_name //Y 文件名称
     * @param string $content//Y 文件内容
     */
    public function saveSettlementFile($file_name, $content){
        $return = [
            'ask'=>0,
            'message'=>'saveSettlementFile error',
        ];
        /**
         * 1、参数校验
         */
        if(empty($file_name)){
            $return['message'] = 'file_name not Empty';
            return $return;
        }
        if(!is_string($file_name)){
            $return['message'] = 'file_name not string';
            return $return;
        }
        if(empty($content)){
            $return['message'] = 'content not Empty';
            return $return;
        }
        if(!is_string($content)){
            $return['message'] = 'content not string';
            return $return;
        }
        
        /**
         * 2、部分数据解析
         */
        //在文件名中提取wish商户ID
        $abfnRe = $this->getAccountByFileName($file_name);
        if(!$abfnRe['ask']){
            $return['message'] = $abfnRe['message'];
            return $return;
        }
        $wa_row = $abfnRe['wish_account'];
        $invoice_number = $abfnRe['invoice_number'];
        $invoice_date = $abfnRe['invoice_date'];
        
        //提取头尾数据
        $hfdRe = $this->getHeaderFootData($content);
        if(!$hfdRe['ask']){
            $return['message'] = '解析文件头尾数据错误:' . $hfdRe['message'];
            return $return;
        }
        $h_data = $hfdRe['h_data'];//头部数据
        $f_data = $hfdRe['f_data'];//尾部数据
        //如果文件里没有结算单号和结算时间，从名字里取
        if(!(isset($h_data['invoice_number']) && $h_data['invoice_number'])){
            if(!$invoice_number){
                $return['message'] = '结算报告里没有invoice_number,请在文件名中维护它!';
                return $return;
            }
            $h_data['invoice_number'] = $invoice_number;
        }
        if(!(isset($h_data['invoice_date']) && $h_data['invoice_date'])){
            if(!$invoice_date){
                $return['message'] = '结算报告里没有invoice_date,请在文件名中维护它!';
                return $return;
            }
            $h_data['invoice_date'] = $invoice_date;
        }
        
        /**
         * 3、导入校验
         */
        $file_md5 = md5($content);
        if($this->fileExists($file_md5)){
            $return['ask'] = 2;
            $return['message'] = "当前文件：{$file_name}，已加入处理队列，无需重复导入!";
            return $return;
        }
        //校验文件是否已导入成功
        $wsiModel = new WishSettlementImport();
        $wsi_has = $wsiModel->where('file_md5',$file_md5)->field('id,status')->find();
        if(param($wsi_has, 'status') == '2'){
            $return['ask'] = 2;
            $return['message'] = "当前文件：{$file_name}，已导入完成，无需重复导入!";
            return $return;
        }
        
        /**
         * 4、写入数据库、文件存储、加入列队
         */
        //写入wish结算报告导入表
        $wsi_row = [
            'id'=>param($wsi_has, 'id' ,0),
            'invoice_number'=>$h_data['invoice_number'],
            'account_id'=>$wa_row['id'],
            'invoice_date'=>$h_data['invoice_date'],
            'file_name'=>$file_name,
            'file_md5'=>$file_md5,
            'user_id'=>Common::getUserInfo()->toArray()['user_id'],
            'status'=>1,
            'create_time'=>time(),
        ];
        if(!$wsiModel->isUpdate(isset($wsi_row['id']) && $wsi_row['id'])->save($wsi_row)){
            $return['message'] = '操作wish结算报告导入表数据失败';
            return $return;
        }
        $id = $wsiModel->id;//主键id
        //文件存储
        if(!$this->saveFile($file_md5, $content)){
            $return['message'] = '文件存储失败';
            return $return;
        }
        //加入列队
        $queue_data = [
            'id'=>$id,//N wish结算报告导入表id
            'file_md5'=>$file_md5,//Y 文件名称
        ];
        (new UniqueQueuer(WishSettlementImportQueue::class))->push(json_encode($queue_data));
        
        
        /**
         * 5、更新时间卡
         */
        /*
         * TODO:lingpeng
         */
        if (param($h_data, 'invoice_date')) {
            Cache::store('SettleReport')->setWishReportDownloadTime($wa_row['id'],strval(param($h_data, 'invoice_date')));
        }

        /**
         * 6、整理返回数据
         */
        $return['ask'] = 1;
        $return['message'] = '导入成功!';
        return $return;
    }
    
    /**
     * @desc 处理结算报告文件
     * @author wangwei
     * @date 2018-12-6 21:39:27
     * @param int $wish_settlement_import_id //Y 导入表id
     * @param bool $cover //N 是否允许覆盖更新
     */
    public function processSettlementFile($wish_settlement_import_id, $cover=false){
        $return = [
            'ask'=>0,
            'message'=>'processSettlementFile error',
        ];
        
        /**
         * 1、参数校验
         */
        if(empty($wish_settlement_import_id)){
            $return['message'] = 'wish_settlement_import_id not Empty';
            return $return;
        }
        $wsi_row = (new WishSettlementImport())->where('id',$wish_settlement_import_id)->field('file_name,file_md5')->find();
        if(empty($wsi_row)){
            $return['message'] = 'wish_settlement_import data not Exists';
            return $return;
        }
        
        /**
         * 2、获取文件
         */
        if(!$content = $this->getFile($wsi_row['file_md5'])){
            $return['message'] = 'getFile is Empty';
            return $return;
        }
        
        /**
         * 3、处理文件
         */
        $isrRe = $this->importSettlementReport($wsi_row['file_name'], $content, $cover);
        //处理成功或者已经导入，删除文件
        if($isrRe['ask']==1 || $isrRe['is_imported']==1){
            $isrRe['ask']=1;
            /*
             * 试运行，不删除文件
             * wangwei 2018-12-15 14:45:10
             */
//             $this->deleteFile($wsi_row['file_md5']);
        }
        
        /**
         * 4、整理返回数据
         */
        $return['ask'] = $isrRe['ask'];
        $return['message'] = $isrRe['message'];
        return $return;
    }
    
}