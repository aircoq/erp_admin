<?php
namespace app\report\service;

use app\common\cache\Cache;
use app\common\model\aliexpress\AliexpressAccount;
use app\common\model\aliexpress\AliexpressSettlement;
use Exception;
use app\common\model\aliexpress\AliexpressOnlineOrder;
use app\common\model\aliexpress\AliexpressSettlementImport;
use app\common\model\aliexpress\AliexpressSettlementReportDetail;
use app\common\model\aliexpress\AliexpressTransactionType;
use app\common\service\Common;
use app\common\service\UniqueQueuer;
use app\index\service\Currency;
use app\order\service\AliOrderServer;
use think\Db;
use app\report\queue\AliexpressSettlementImportQueue;
use app\common\service\Excel;

/**
 * @desc aliexpress结算报告服务类
 * @author wangwei
 * @date 2019-1-7 21:37:38
 */
class AliexpressSettlementReportService
{
    //redis锁键前缀
    private $import_lock_key_prefix = 'lock:report:importAliexpressSettlementReport:';
    //导入运行超时时间（秒）
    private $import_expired_time = 3600;
    //导入文件临时目录
    private $import_tmp_dir = APP_PATH . '../runtime/log/swoole/ali_tmp';
    //临时文件过期时间(秒)
    private $import_tmp_expire_time = 24 * 60 * 60;
    
    //报告文件md5值
    private $import_file_md5 = null;
    //账号id
    private $account_id = null;
    //币种代码
    private $currency_code = null;
    //报告内容最早时间
    private $min_time = null;
    //报告内容最晚时间
    private $max_time = null;
    //报告结算账户类型，1：速卖通账户，2：国际支付宝账户
    private $settlement_account_type = null;
    
    //速卖通账户结算报告标题
    private $aliexpress_account_title = ['时间','业务类型','交易信息','交易对方','资金明细'];
    //国际支付宝账户结算报告标题
    private $alipay_account_title = ['时间','业务类型','交易信息','入款','出款','余额'];
    
    //redis锁键值
    private $import_lock_data = [];
    
    //aliexpress交易类型临时存储
    private $transaction_type_tmps = [];
    //redis保存文件键前缀
    private $import_save_key_prefix = 'file:upload:aliexpress_settlement:';
    
    /**
     * @desc 导入aliexpress结算报告数据
     * @author wangwei
     * @date 2018-11-30 15:09:14
     * @param string $file_name //Y 文件名称
     * @param string $content //Y 文件内容
     * @param int $settlement_account_type //Y 结算账户类型，1：速卖通账户，2：国际支付宝账户
     * @param string $account_code //N 文件所属账号
     */
    public function importSettlementReport($file_name, $content, $settlement_account_type,$account_code=''){
        $return = [
            'ask'=>0,
            'message'=>'importSettlementReport error',
            'count'=>0,
            'account_id'=>'',
            'min_time'=>'',
            'max_time'=>'',
            'currency_code'=>'',
        ];
        
        /**
         * 1、解析aliexpress结算报告数据
         */
        $esdRe = $this->extractSettlementData($file_name, $content, $settlement_account_type, $account_code);
        //返回值，处理回写时用
        $return['account_id'] = $esdRe['data']['account_id'];
        $return['min_time'] = $esdRe['data']['min_time'];
        $return['max_time'] = $esdRe['data']['max_time'];
        $return['currency_code'] = $esdRe['data']['currency_code'];
        //解析错误
        if(!$esdRe['ask']){
            $return['message'] = 'extractSettlementData error:' . $esdRe['message'];
            return $return;
        }
        if(empty($esdRe['data']['account_id'])){
            $return['message'] = '解析的内容里account_id不能为空';
            return $return;
        }
        if(empty($esdRe['data']['currency_code'])){
            $return['message'] = '解析的内容里currency_code不能为空';
            return $return;
        }
        
        /**
         * 2、解析后数据校验
         */
        //表格无数据
        if(empty($esdRe['data']['details'])){
            $return['ask'] = 1;
            $return['message'] = '表格无数据';
            return $return;
        }

        /**
         * 3、设置属性
         */
        $this->settlement_account_type = $settlement_account_type;
        $this->account_id = $esdRe['data']['account_id'];
        $this->currency_code = $esdRe['data']['currency_code'];
        $this->min_time = $esdRe['data']['min_time'];
        $this->max_time = $esdRe['data']['max_time'];
        $this->import_lock_data = [
            'min_time'=>$this->min_time,
            'max_time'=>$this->max_time
        ];
        $this->import_file_md5 = $esdRe['data']['import_file_md5'];
        
        /**
         * 4、导入前检查
         */
        //防止多进程同时导入
        $rcRe = $this->runCheck();
        if(!$rcRe['ask']){
            $return['ask'] = 2;
            if($rcRe['min_time']>0){
                $min_data = date('Y-m-d',$rcRe['min_time']);
                $max_data = date('Y-m-d',$rcRe['max_time']);
                $this_min_data = date('Y-m-d',$this->min_time);
                $this_max_data = date('Y-m-d',$this->max_time);
                $return['message'] = "正在导入结算时间{$min_data}至{$max_data}的{$this->currency_code}币种的报告文件，";
                $return['message'] .="与该报告文件结算时间段{$this_min_data}至{$this_max_data}时间交叉。请{$rcRe['wait_time']}秒后再试!";
            }else{
                $return['message'] = "当前报告文件：{$this->import_file_md5}，正在导入。请{$rcRe['wait_time']}秒后再试!";
            }
            return $return;
        }
        
        /**
         * 5、整理数据
         */
        //1、aliexpress_settlement_report_detail表
        $asrd_rows = [];//结算明细数据
        $order_datas = [];//订单放款退款数据
        //整理结算报告明细数据
        $this->arrangeSettlementReportDetailsData($esdRe['data'], $asrd_rows, $order_datas);
        //更新运行时间
        $this->updateRunTime();
        
        /**
         * 6、数据入库
         */
        try {
            Db::startTrans();
            //1、清除数据
            if($asrd_rows && $this->min_time && $this->max_time){
                $this->clearDataByTime();
            }
            //2、aliexpress_settlement_report_detail表
            $asrd_datas_arr = array_chunk($asrd_rows, 1000);//按1000条拆分，避免数据过大，运行超时
            unset($asrd_rows);//释放内存
            $count = 0;
            foreach ($asrd_datas_arr as $k=>$asrd_datas){
                foreach ($asrd_datas as $kk=>$asrd_data){
                    $asrd_data['create_time'] = time();
                    (new AliexpressSettlementReportDetail())->save($asrd_data);
                    $count++;
                }
                //更新运行时间
                $this->updateRunTime();
            }
            //3、aliexpress_settlement表
            foreach ($order_datas as $aliexpress_order_id=>$order_data){
//                 $order_data = [
//                     'has_substitute_fee' => 1,
//                     'refund_time_by_report' => '4514457',
//                     'transfer_time_by_report' => '787855',
//                 ];
                $order_data['order_id'] = $aliexpress_order_id;
                $order_data['account_id'] = $this->account_id;
                AliexpressSettlement::settleData($order_data);
            }
            Db::commit();
            $return['ask'] = 1;
            $return['count'] = $count;
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
     * @desc 整理结算报告明细数据
     * @author wangwei
     * @date 2019-1-8 22:03:20
     * @param array $data
     * @param array $asrd_rows
     * @param array $order_datas
     */
    private function arrangeSettlementReportDetailsData($data, &$asrd_rows, &$order_datas){
        if($details = param($data, 'details',[])){
            foreach ($details as $k=>$detail){
                $asrd_row = [
                    'account_id'=>$this->account_id,
                    'settlement_account_type'=>$this->settlement_account_type,
                    'condition_code'=>$detail['condition_code'],
                    'aliexpress_transaction_type_id'=>$this->getTransactionTypeId($detail['业务类型']),
                    'aliexpress_order_id'=>$detail['aliexpress_order_id'],
                    'other_id'=>$detail['other_id'],
                    'package_number'=>$detail['package_number'],
                    'other_id'=>'',
                    'amount'=>$detail['amount'],
                    'trading_partner'=>$detail['交易对方'],
                    'to_usd_rate'=>0,
                    'to_cny_rate'=>0,
                    'currency_code'=>$detail['currency_code'],
                    'transaction_time'=>AliOrderServer::aliexpressTime2LocTime($detail['transaction_time_org']),
                    'transaction_time_org'=>$detail['transaction_time_org'],
                    'transaction_desc'=>$detail['交易信息'],
                    'org_data'=>$detail['org_data'],
                ];
                //汇率
                $form_code = strtoupper($asrd_row['currency_code']);
                $form_code = $form_code=='CNH' ? 'CNY' : $form_code;//离岸人民币取人民币汇率
                $asrd_row['to_usd_rate'] = Currency::getCurrencyRateByTime($form_code, date('Y-m-d',$asrd_row['transaction_time']), 'USD');
                $asrd_row['to_cny_rate'] = Currency::getCurrencyRateByTime($form_code, date('Y-m-d',$asrd_row['transaction_time']), 'CNY');
                //更新订单表字段
                if($aliexpress_order_id = $asrd_row['aliexpress_order_id']){
                    if($detail['业务类型']=='代扣' || $detail['业务类型']=='代扣运费'){
                        $order_datas[$aliexpress_order_id]['has_substitute_fee'] = 1;
                    }else if($detail['业务类型']=='交易售后退款' || $detail['业务类型']=='退款'){
                        $order_datas[$aliexpress_order_id]['refund_time_by_report'] = $asrd_row['transaction_time'];
                    }else if($detail['业务类型']=='放款'){
                        $order_datas[$aliexpress_order_id]['transfer_time_by_report'] = $asrd_row['transaction_time'];
                    }
                }
                $asrd_rows[] = $asrd_row;
                
                //更新运行时间
                if ($k % 3000 == 1) {
                    $this->updateRunTime();
                }
                
            }
        }
    }
    
    /**
     * @desc 获取指定类型id
     * @author wangwei
     * @date 2019-1-8 15:30:28
     * @param string $transaction_type
     * @return number|mixed|unknown
     */
    private function getTransactionTypeId($transaction_type){
        $aliexpress_transaction_type_id = 0;
        $type_str = $transaction_type;
        if(isset($this->transaction_type_tmps[$type_str])){
            $aliexpress_transaction_type_id = $this->transaction_type_tmps[$type_str];
        }else{
            $att_row = [
                'transaction_type'=>$transaction_type,
            ];
            if($att_has = AliexpressTransactionType::where($att_row)->field('id')->find()){
                $aliexpress_transaction_type_id = $att_has['id'];
            }else{
                $att_row['create_time'] = time();
                $aliexpress_transaction_type_id = (new AliexpressTransactionType())->insertGetId($att_row);
            }
            $this->transaction_type_tmps[$type_str] = $aliexpress_transaction_type_id;
        }
        return $aliexpress_transaction_type_id;
    }
    
    /**
     * @desc 清理数据
     * @author wangwei
     * @date 2019-1-8 22:08:15
     */
    private function clearDataByTime(){
        if(!($this->account_id && $this->currency_code && $this->min_time && $this->max_time && $this->settlement_account_type)){
            $msg_arr = [
                'account_id'=>$this->account_id,
                'currency_code'=>$this->currency_code,
                'min_time'=>$this->min_time,
                'max_time'=>$this->max_time,
                'settlement_account_type'=>$this->settlement_account_type,
            ];
            throw new Exception('clearDataByTime error,params:' . print_r($msg_arr,true));
        }
        $del_con = [
            'account_id'=>$this->account_id,
            'currency_code'=>$this->currency_code,
            'settlement_account_type'=>$this->settlement_account_type
        ];
        $min_time = $this->min_time;
        $max_time = $this->max_time;
        //分段删除，避免等待超时
        $step = 3600 * 24 * 10;
        for ($exec_min_time = $min_time; $exec_min_time < $max_time; $exec_min_time += $step){
            //当前分段结束时间
            $exec_max_time = $exec_min_time + $step;
            //如果当前分段结束时间大于结束时间，取结束时间
            if($exec_max_time > $max_time){
                $exec_max_time = $max_time;
            }
            $del_con['transaction_time_org'] = ['between',[$exec_min_time, $exec_max_time]];
            //状态改成待删除
            AliexpressSettlementReportDetail::where($del_con)->update(['wait_delete'=>1]);
            //更新运行时间
            $this->updateRunTime();
        }
    }

    /**
     * @desc 解析aliexpress结算报告数据
     * @author wangwei
     * @date 2018-11-28 18:34:47
     * @param string $file_name //Y 文件名称
     * @param string $content //Y 文件内容
     * @param int $settlement_account_type //Y 结算账户类型，1：速卖通账户，2：国际支付宝账户
     * @param string $account_code //N 账号代码
     */
    public function extractSettlementData($file_name, $content, $settlement_account_type=1, $account_code=''){
        $return = [
            'ask'=>0,
            'message'=>'extractSettlementData error',
            'data'=>[
                'account_id'=>'',
                'currency_code'=>'',
                'import_file_md5'=>'',
                'min_time'=>0,
                'max_time'=>0,
                'details'=>[],
            ]
        ];
        
        /**
         * 1、参数校验
         */
        $cipRe = $this->checkImportParams($file_name, $content, $account_code);
        if(!$cipRe['ask']){
            $return['message'] = 'importParams error:' . $cipRe['message'];
            return $return;
        }
        if($settlement_account_type!='1' && $settlement_account_type!='2'){
            $return['message'] = "不合法的结算账户类型:{$settlement_account_type}";
            return $return;
        }
        $return['data']['import_file_md5'] = $cipRe['file_md5'];
        $account_id = param($cipRe['wa_row'], 'id', 0);
        //设置最大运行时间
        set_time_limit(0);
        //设置最大内存
        ini_set('memory_limit', '2048M');

        /**
         * 2、根据不同类型，解析数据
         */
        $esbRe = [
            'ask'=>0,
            'message'=>'extractSettlementData error',
        ];
        if($settlement_account_type=='1'){
            $esbRe = $this->extractSettlementDataByAliexpress($content);
        }else{
            $esbRe = $this->extractSettlementDataByAlipay($content);
        }
        if(!$esbRe['ask']){
            $return['message'] = $esbRe['message'];
            return $return;
        }
        $settlementData = paramNotEmpty($esbRe, 'data', []);
        if(empty($settlementData)){
            $return['message'] = 'data is empty!';
            return $return;
        }
        $return['data']['min_time'] = $settlementData['min_time'];
        $return['data']['max_time'] = $settlementData['max_time'];
        $return['data']['details'] = $settlementData['details'];
        $report_currency_code = paramNotEmpty($settlementData, 'currency_code');
        //币种不能为空
        if(empty($report_currency_code)){
            $return['message'] = '报告内容里无法获取到币种！';
            return $return;
        }
        $return['data']['currency_code'] = $report_currency_code;
        
        /**
         * 3、获取账号id
         */
        $report_account_id = paramNotEmpty($settlementData, 'report_account_id');
        $report_order_id = paramNotEmpty($settlementData, 'report_order_id');
        if(empty($report_account_id) && empty($account_id)){
            $return['message'] = '报告内容里无法取到账号，请在文件名中指定账号简称！';
            return $return;
        }
        if($account_id && $report_account_id && $account_id!=$report_account_id){
            $return['message'] = '指定的账号与内容里订单所属账号不一致，请确认报告所属账号，订单号：' . $report_order_id;
            return $return;
        }
        //未指定的账号从报告订单号取
        if(empty($account_id) && $report_account_id){
            $account_id = $report_account_id;
        }
        
        /**
         * 4、整理返回数据
         */
        $return['ask'] = 1;
        $return['message'] = 'success';
        $return['data']['account_id'] = $account_id;
        return $return;
    }
    
    /**
     * @desc 解析速卖通账户的csv文件
     * @author wangwei
     * @date 2019-3-19 10:48:14
     */
    public function extractSettlementDataByAliexpress($content){
        $return = [
            'ask'=>0,
            'message'=>'extractSettlementDataByAliexpress error',
            'data'=>[
                'report_account_id'=>'',//通过报告里的订单查询到的账号id
                'report_order_id'=>'',//通过报告查询的订单号
                'currency_code'=>'',
                'min_time'=>'',
                'max_time'=>'',
                'details'=>[]
            ],
        ];
        
        /**
         * 1、简单校验
         */
        if(empty($content)){
            $return['message'] = '文件内容为空！';
            return $return;
        }
        
        /**
         * 2、数据解析
         */
        //设置正则最大回溯值
        ini_set('pcre.backtrack_limit', 999999999);
        //设置编码方式
        $encode = mb_detect_encoding($content, ['ASCII','UTF-8','GB2312','GBK','BIG5']);
        if($encode!='UTF-8'){
            $content = mb_convert_encoding($content, 'UTF-8', $encode);
        }
        $val_str_arr = preg_split('/\n/', trim($content));
        //仅含标题的空文件
        if(count($val_str_arr)==1){
            $return['message'] = '仅含标题的空文件！';
            return $return;
        }
        $details = [];
        $title = [];
        $title_check = $this->aliexpress_account_title;
        $has_title = false;
        $min_time = 0;//最小交易时间
        $max_time = 0;//最大交易时间
        $report_account_id = 0;//通过报告里的订单查询到的账号id
        $report_order_id = 0;//通过报告查询的订单号
        $report_currency_code = '';//报告里的币种
        $has_select_order_ids = [];//记录已经查过的订单号
        foreach ($val_str_arr as $val_str){
            if($val_str = trim($val_str)){
                $val_arr = preg_split('/\s?,\s?/', $val_str);
                if(!$has_title){
                    //检验键是否存在
                    foreach ($title_check as $title_name){
                        if(!in_array($title_name, $val_arr)){
                            $return['message'] = '错误的标题:' . print_r($val_arr, 1);
                            return $return;
                        }
                    }
                    $title = $val_arr;
                    $has_title = true;
                }else{
                    $data_item_tmp = [];
                    foreach ($val_arr as $val_k=>$item){
                        $title_tmp = $title[$val_k];
                        $data_item_tmp[$title_tmp] = trim($item);
                    }
                    //特征码
                    $data_item_tmp['condition_code'] = md5(join('', $data_item_tmp));
                    //原始数据json
                    $org_data_arr = $data_item_tmp;
                    unset($org_data_arr['condition_code']);
                    $data_item_tmp['org_data'] = json_encode($org_data_arr);
                    //取得最小时间和最大时间
                    $data_item_tmp['transaction_time_org'] = strtotime($data_item_tmp['时间']);
                    if($min_time==0){
                        $min_time = $data_item_tmp['transaction_time_org'];
                    }
                    if($max_time==0){
                        $max_time = $data_item_tmp['transaction_time_org'];
                    }
                    //最小交易时间
                    if($data_item_tmp['transaction_time_org'] < $min_time){
                        $min_time = $data_item_tmp['transaction_time_org'];
                    }
                    //最大交易时间
                    if($data_item_tmp['transaction_time_org'] > $max_time){
                        $max_time = $data_item_tmp['transaction_time_org'];
                    }
                    $data_item_tmp['aliexpress_order_id'] = '';
                    $data_item_tmp['package_number'] = '';
                    $data_item_tmp['other_id'] = '';
                    $data_item_tmp['amount'] = 0;
                    $data_item_tmp['currency_code'] = '';
                    //从交易信息提取相关数据
                    $transaction_id = 0;
                    if(preg_match('/transaction\s?id:(\d+)$/i', $data_item_tmp['交易信息'],$tim)){
                        $transaction_id = $tim[1];
                    }
                    //有交易号
                    if($transaction_id){
                        if($data_item_tmp['业务类型']=='null'){
                            $data_item_tmp['other_id'] = $transaction_id;
                        }else{
                            $data_item_tmp['aliexpress_order_id'] = $transaction_id;
                        }
                    }else if(preg_match('/_([^_]+)_(\d+)$/i', $data_item_tmp['交易信息'],$opm)){
                        $data_item_tmp['package_number'] = $opm[1];
                        $data_item_tmp['aliexpress_order_id'] = $opm[2];
                    }else{
                        $str_arr = explode('_', $data_item_tmp['交易信息']);
                        if(count($str_arr) > 1){
                            $data_item_tmp['other_id'] = end($str_arr);
                        }
                    }
                    //金额、币种
                    if(preg_match('/([+-])([a-zA-Z]{3})\s+([\d\.]+)$/i', $data_item_tmp['资金明细'],$am)){
                        $data_item_tmp['amount'] = $am[1] != '-' ? $am[3] : (0 - $am[3]);
                        $data_item_tmp['currency_code'] = $am[2];
                    }
                    //根据订单号查询账号id(已经查过的不再查询)
                    if(!$report_account_id && $data_item_tmp['aliexpress_order_id'] && !in_array($data_item_tmp['aliexpress_order_id'], $has_select_order_ids)){
                        if($aoo_row = AliexpressOnlineOrder::where(['order_id'=>$data_item_tmp['aliexpress_order_id']])->field('account_id')->find()){
                            $report_account_id = $aoo_row['account_id'];
                            $report_order_id = $data_item_tmp['aliexpress_order_id'];
                        }
                        //已经查过的不再查询
                        $has_select_order_ids[] = $data_item_tmp['aliexpress_order_id'];
                    }
                    //币种
                    if(empty($report_currency_code)){
                        $report_currency_code = $data_item_tmp['currency_code'];
                    }
                    if($report_currency_code != $data_item_tmp['currency_code']){
                        $return['message'] = '错误的币种:' . $data_item_tmp['currency_code'];
                        return $return;
                    }
                    $details[] = $data_item_tmp;
                }
            }
        }
        $return['data']['min_time'] = $min_time;
        $return['data']['max_time'] = $max_time;
        $return['data']['details'] = $details;
        //释放内存
        unset($has_select_order_ids);
        //币种不能为空
        if(empty($report_currency_code)){
            $return['message'] = '报告内容里无法获取到币种！';
            return $return;
        }
        $return['data']['currency_code'] = $report_currency_code;
        
        /**
         * 3、整理返回数据
         */
        $return['ask'] = 1;
        $return['message'] = 'success';
        $return['data']['report_account_id'] = $report_account_id;
        $return['data']['report_order_id'] = $report_order_id;
        return $return;
    }
    
    /**
     * @desc 解析国际支付宝账户的xls文件
     * @author wangwei
     * @date 2019-3-19 10:48:14
     */
    public function extractSettlementDataByAlipay($content){
        $return = [
            'ask'=>0,
            'message'=>'extractSettlementDataByAlipay error',
            'data'=>[
                'report_account_id'=>'',//通过报告里的订单查询到的账号id
                'report_order_id'=>'',//通过报告查询的订单号
                'currency_code'=>'',
                'min_time'=>'',
                'max_time'=>'',
                'details'=>[]
            ],
        ];
        
        /**
         * 1、简单校验
         */
        if(empty($content)){
            $return['message'] = '文件内容为空！';
            return $return;
        }
        
        /**
         * 2、内容存入临时文件
         */
        $ctfRe = $this->createTmpFile($content, 'xls', true);
        if(!$ctfRe['ask']){
            $return['message'] = '创建临时文件失败:' . $ctfRe['message'];
            return $return;
        }
        $file_path = $ctfRe['file_path'];
        
        /**
         * 3、数据解析
         */
        $rows = Excel::readExcel($file_path, 6);
        //删除临时文件
        @unlink($file_path);
        if(empty($rows)){
            $return['message'] = '文件内容解析的数据为空!';
            return $return;
        }
        $title_check = $this->alipay_account_title;
        //检查标题
        $title = array_keys($rows[0]);
        foreach ($title_check as $t){
            if(!in_array($t, $title)){
                $return['message'] = "错误的标题，标题{$t}不存在";
                return $return;
            }
        }
        $details = [];
        $min_time = 0;//最小交易时间
        $max_time = 0;//最大交易时间
        $report_account_id = 0;//通过报告里的订单查询到的账号id
        $report_order_id = 0;//通过报告查询的订单号
        $report_currency_code = '';//报告里的币种
        $has_select_order_ids = [];//记录已经查过的订单号
        foreach ($rows as $row){
            //特征码
            $row['condition_code'] = md5(join('', $row));
            //原始数据json
            $org_data_arr = $row;
            unset($org_data_arr['condition_code']);
            $row['org_data'] = json_encode($org_data_arr);
            //清洗处理
            $row['交易信息'] = str_replace('"', '', $row['交易信息']);
            $row['交易对方'] = 'Alipay';
            if($row['入款']){
                $row['资金明细'] = '+' . $row['入款'];
            }else if($row['出款']){
                $row['资金明细'] = '-' . $row['出款'];
            }
            if(isset($row['资金明细'])){
                $row['资金明细'] = str_replace(' ', '', $row['资金明细']);
            }
            unset($row['入款']);
            unset($row['出款']);
            //取得最小时间和最大时间
            $row['transaction_time_org'] = strtotime($row['时间']);
            if($min_time==0){
                $min_time = $row['transaction_time_org'];
            }
            if($max_time==0){
                $max_time = $row['transaction_time_org'];
            }
            //最小交易时间
            if($row['transaction_time_org'] < $min_time){
                $min_time = $row['transaction_time_org'];
            }
            //最大交易时间
            if($row['transaction_time_org'] > $max_time){
                $max_time = $row['transaction_time_org'];
            }
            $row['aliexpress_order_id'] = '';
            $row['package_number'] = '';
            $row['other_id'] = '';
            $row['amount'] = 0;
            $row['currency_code'] = '';
            //从交易信息提取相关数据
            $transaction_id = 0;
            if(preg_match('/交易放款\s?:(\d+)$/i', $row['交易信息'],$tim)){
                $transaction_id = $tim[1];
            }
            //有交易号
            if($transaction_id){
                if($row['业务类型']=='null'){
                    $row['other_id'] = $transaction_id;
                }else{
                    $row['aliexpress_order_id'] = $transaction_id;
                }
            }else if(preg_match('/交易退款\:(\d+)/i', $row['交易信息'],$opm)){
                $row['aliexpress_order_id'] = $opm[1];
            }else if(preg_match('/订单号\:\s?(\d+)/i', $row['交易信息'],$opm)){
                $row['aliexpress_order_id'] = $opm[1];
            }else if(preg_match('/Order：(\d+)/i', $row['交易信息'],$opm)){
                $row['aliexpress_order_id'] = $opm[1];
            }else if(preg_match('/_(\d+)_(\d+)_/i', $row['交易信息'],$opm)){
                $row['package_number'] = $opm[1];
                $row['aliexpress_order_id'] = $opm[2];
            }else if(preg_match('/_([^_]+)_(\d+)$/i', $row['交易信息'],$opm)){
                $row['package_number'] = $opm[1];
                $row['aliexpress_order_id'] = $opm[2];
            }else{
                $str_arr = explode('_', $row['交易信息']);
                if(count($str_arr) > 1){
                    $row['other_id'] = end($str_arr);
                }
            }
            //金额、币种
            if(preg_match('/([+-])([a-zA-Z]{3})([\d\.]+)$/i', $row['资金明细'],$am)){
                $row['amount'] = $am[1] != '-' ? $am[3] : (0 - $am[3]);
                $row['currency_code'] = $am[2];
            }
            //根据订单号查询账号id(已经查过的不再查询)
            if(!$report_account_id && $row['aliexpress_order_id'] && !in_array($row['aliexpress_order_id'], $has_select_order_ids)){
                if($aoo_row = AliexpressOnlineOrder::where(['order_id'=>$row['aliexpress_order_id']])->field('account_id')->find()){
                    $report_account_id = $aoo_row['account_id'];
                    $report_order_id = $row['aliexpress_order_id'];
                }
                //已经查过的不再查询
                $has_select_order_ids[] = $row['aliexpress_order_id'];
            }
            //币种
            if(empty($report_currency_code)){
                $report_currency_code = $row['currency_code'];
            }
            if($report_currency_code != $row['currency_code']){
                $return['message'] = '错误的币种:' . $row['currency_code'];
                return $return;
            }
            $details[] = $row;
        }
        $return['data']['min_time'] = $min_time;
        $return['data']['max_time'] = $max_time;
        $return['data']['details'] = $details;
        //释放内存
        unset($has_select_order_ids);
        //币种不能为空
        if(empty($report_currency_code)){
            $return['message'] = '报告内容里无法获取到币种！';
            return $return;
        }
        $return['data']['currency_code'] = $report_currency_code;
        
        /**
         * 3、整理返回数据
         */
        $return['ask'] = 1;
        $return['message'] = 'success';
        $return['data']['report_account_id'] = $report_account_id;
        $return['data']['report_order_id'] = $report_order_id;
        return $return;
    }
    
    /**
     * @desc 创建临时文件
     * @author wangwei
     * @date 2019-4-16 19:13:21
     * @param string $content
     * @param string $type
     * @param bool $clear
     * @return number[]|string[]
     */
    private function createTmpFile($content, $type='xls', $clear=false){
        $return = [
            'ask'=>0,
            'message'=>'createTmpFile error',
            'file_path'=>'',
        ];
        
        //创建临时目录
        if(!is_dir($this->import_tmp_dir)){
            mkdir($this->import_tmp_dir, 0777, true);
            chmod($this->import_tmp_dir, 0777);
        }
        if(!is_dir($this->import_tmp_dir)){
            $return['message'] = '创建临时目录失败!';
            return $return;
        }
        //清理过期文件
        if($clear){
            $files = [];//被删除的文件
            $this->clearExpireFile($this->import_tmp_dir, $files);
        }
        //文件存入临时目录
        $file_md5 = md5($content);
        $file_path = $this->import_tmp_dir . '/' . $file_md5 . ".{$type}";
        if(!$size = file_put_contents($file_path, $content)){
            $return['message'] = '文件存入临时目录失败!';
            return $return;
        }
        //整理返回数据
        $return['ask'] = 1;
        $return['message'] = 'success';
        $return['file_path'] = $file_path;
        return $return;
    }
    
    /**
     * @desc 清理过期文件
     * @author wangwei
     * @date 2019-3-19 15:45:13
     * @param string $path
     * @param array $files
     */
    public function clearExpireFile($path,&$files){
        if(is_dir($path)){
            $dp=dir($path);
            while($file=$dp->read()){
                if($file!='.'&& $file!='..'){
                    $this->clearExpireFile($path.'/'.$file,$files);
                }
            }
            $dp->close();
        }
        //删除过期文件
        if(is_file($path) && (time() - filemtime($path) > $this->import_tmp_expire_time)){
            $files[] = $path;
            @unlink($path);
        }
    }
    
    /**
     * @desc 校验导入参数
     * @author wangwei
     * @date 2019-1-8 14:25:28
     * @param string $file_name //Y 文件名称
     * @param string $content //Y 文件内容
     * @param string $account_code //N 账号代码
     */
    public function checkImportParams($file_name, $content, &$account_code){
        $return = [
            'ask'=>0,
            'message'=>'params error',
            'settlement_account_type'=>'',
            'start_date'=>'',
            'end_date'=>'',
            'file_md5'=>'',
            'wa_row'=>[],
        ];
        if(empty($file_name)){
            $return['message'] = 'file_name not Empty';
            return $return;
        }
        if(!is_string($file_name)){
            $return['message'] = 'file_name not string';
            return $return;
        }
        $settlement_account_type = ''; //1:速卖通,2:支付宝
        $account_code_fn = '';
        if(preg_match('/^([a-zA-Z0-9\-]+-)?(\d{4})(\d{2})(\d{2})_(\d{4})(\d{2})(\d{2}).*?\.csv$/',$file_name,$fnm)){
            $return['start_date'] = "{$fnm[2]}-{$fnm[3]}-{$fnm[4]}";
            $return['end_date'] = "{$fnm[5]}-{$fnm[6]}-{$fnm[7]}";
            $account_code_fn = isset($fnm[1]) ? trim(trim($fnm[1],'-')) : '';
            //校验时间格式
            if(isDate($return['start_date']) && isDate($return['end_date'])){
                $settlement_account_type = '1';
            }
        }else if(preg_match('/^([a-zA-Z0-9\-]+-)?account_record_\d{8}_\d{13}.*?\.xls$/',$file_name,$fnm)){
            $return['start_date'] = '1970-01-01';
            $return['end_date'] = '1970-01-01';
            $account_code_fn = isset($fnm[1]) ? trim(trim($fnm[1],'-')) : '';
            $settlement_account_type = '2';
        }
        //文件名不合法
        if(empty($settlement_account_type)){
            $return['message'] = "不合法的文件名:{$file_name}";
            return $return;
        }
        $return['settlement_account_type'] = $settlement_account_type;
        //如果文件名里有账号简称
        if($account_code_fn){
            //如果指定了账号，但与文件名里的账号简称不一致，认为错误
            if($account_code && strtolower($account_code_fn)!=strtolower($account_code)){
                $return['message'] = "文件名中账号简称:{$account_code_fn},与选择的账号:{$account_code}不一致,请核实后再导入！";
                return $return;
            }
            $account_code = $account_code_fn;
        }
        //文件内容
        if(empty($content)){
            $return['message'] = 'content not Empty';
            return $return;
        }
        if(!is_string($content)){
            $return['message'] = 'content not string';
            return $return;
        }
        $return['file_md5'] = md5($content);
        $wa_row = [];
        //校验aliexpress账号
        if($account_code){
            if(!$wa_row = AliexpressAccount::where(['code'=>$account_code])->field('id,code')->find()){
                $return['message'] = "aliexpress账号简称：{$account_code}，不存在，请核对改正后再导入，或者联系IT技术人员";
                return $return;
            }
        }
        $return['ask'] = 1;
        $return['message'] = 'success';
        $return['wa_row'] = $wa_row;
        return $return;
    }
    
    /**
     * @desc 校验文件标题
     * @author wangwei
     * @date 2019-4-16 20:10:18
     * @param string $content
     * @param string $settlement_account_type
     * @return number[]|string[]
     */
    public function checkSettlementTitle($content, $settlement_account_type){
        $return = [
            'ask'=>0,
            'message'=>'checkSettlementTitle error',
        ];
        
        //正确的标题
        $check_title = [];
        if($settlement_account_type=='1'){
            $check_title = $this->aliexpress_account_title;
        }else if($settlement_account_type=='2'){
            $check_title = $this->alipay_account_title;
        }
        if(empty($check_title)){
            $return['message'] = '错误的账号类型';
            return $return;
        }
        $title = $this->getSettlementTitle($content, $settlement_account_type);
        $title_str = join(',', $title);
        foreach ($check_title as $t){
            if(!in_array($t, $title)){
                $return['message'] = "当前文件标题：{$title_str}，不存在标题：{$t}，请勿修改标题！";
                return $return;
            }
        }
        $return['ask'] = 1;
        $return['message'] = 'success';
        return $return;
    }
    
    /**
     * @desc 获取标题 
     * @author wangwei
     * @date 2019-4-16 18:28:37
     * @param string $content
     * @param int $settlement_account_type 1:速卖通,2:支付宝
     */
    public function getSettlementTitle($content, $settlement_account_type){
        $title = [];
        
        /**
         * 1、简单检验
         */
        if(empty($content)){
            return $title;
        }
        if($settlement_account_type!='1' && $settlement_account_type!='2'){
            return $title;
        }
        
        /**
         * 2、根据不同类型分发处理
         */
        $type = '';
        $allColumn = 0;
        if($settlement_account_type == '1'){
            $encode = mb_detect_encoding($content, ['ASCII','UTF-8','GB2312','GBK','BIG5']);
            if($encode!='UTF-8'){
                $content = mb_convert_encoding($content, 'UTF-8', $encode);
            }
            $type = 'csv';
            $allColumn = 5;
        }else if($settlement_account_type == '2'){
            $type = 'xls';
            $allColumn = 6;
        }
        
        /**
         * 3、创建临时文件
         */
        $ctfRe = $this->createTmpFile($content, $type);
        if(!$ctfRe['ask']){
            $return['message'] = '创建临时文件失败:' . $ctfRe['message'];
            return $return;
        }
        $file_path = $ctfRe['file_path'];
        
        /**
         * 4、读取标题
         */
        $title = Excel::readExcelByRow($file_path, $allColumn, 1);
        @unlink($file_path);
        
        return $title;
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
            'min_time'=>0,//在导入的报告开始时间
            'max_time'=>0,//在导入的报告结束时间
        ];
        //redis键
        $key = $this->import_lock_key_prefix . $this->account_id.'_'.$this->currency_code.'_'.$this->settlement_account_type;
        //当前账号币种有
        if($run_arrs = Cache::handler()->hGetAll($key)){
            $run_time = 0;
            $min_time = 0;
            $max_time = 0;
            foreach ($run_arrs as $file_md5=>$run_json){
                $run_arr = json_decode($run_json, true);
                //删除运行超时键
                if(time() - $run_arr['run_time'] > $this->import_expired_time){
                    Cache::handler()->hDel($key, $file_md5);
                    continue;
                }
                //当前文件在执行
                if($file_md5 == $this->import_file_md5){
                    $run_time =  $run_arr['run_time'];
                    break;
                }
                //如果时间段有交叉
                if(!(($this->max_time < $run_arr['min_time']) || ($run_arr['max_time'] < $this->min_time))){
                    $run_time =  $run_arr['run_time'];
                    $min_time = $run_arr['min_time'];
                    $max_time = $run_arr['max_time'];
                    break;
                }
            }
            //有在执行的文件
            if($run_time > 0){
                $return['wait_time'] = $this->import_expired_time - (time() - $run_time);
                $return['min_time'] = $min_time;
                $return['max_time'] = $max_time;
                return $return;
            }
        }
        //无在执行的文件
        $this->import_lock_data['run_time'] = time();
        Cache::handler()->hSet($key, $this->import_file_md5, json_encode($this->import_lock_data));
        $return['ask'] = 1;
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
        //redis键
        $key = $this->import_lock_key_prefix . $this->account_id.'_'.$this->currency_code.'_'.$this->settlement_account_type;
        $this->import_lock_data['run_time'] = time();
        return Cache::handler()->hSet($key, $this->import_file_md5, json_encode($this->import_lock_data));
    }
    
    /**
     * @desc 运行结束
     * @author wangwei
     * @date 2018-11-30 18:26:25
     */
    private function runEnd()
    {
        //redis键
        $key = $this->import_lock_key_prefix . $this->account_id.'_'.$this->currency_code.'_'.$this->settlement_account_type;
        Cache::handler()->hDel($key, $this->import_file_md5);
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
     * @desc 保存结算报告文件
     * @author wangwei
     * @date 2019-1-9 17:20:43
     * @param string $file_name //Y 文件名称
     * @param string $content //Y 文件内容
     * @param string $account_code //N 文件所属账号
     */
    public function saveSettlementFile($file_name, $content, $account_code=''){
        $return = [
            'ask'=>0,
            'message'=>'saveSettlementFile error',
        ];
        
        /**
         * 1、导入前校验
         */
        //1、校验导入参数
        $cipRe = $this->checkImportParams($file_name, $content, $account_code);
        if(!$cipRe['ask']){
            $return['message'] = 'importParams error:' . $cipRe['message'];
            return $return;
        }
//         //导入的文件必须指定账号简称
//         if(!$cipRe['wa_row']){
//             $return['message'] = '请在文件名开头指定账号简称!';
//             return $return;
//         }
        $file_md5 = $cipRe['file_md5'];
        $account_id = param($cipRe['wa_row'], 'id', 0);
        //2、校验导入文件标题
        $cstRe = $this->checkSettlementTitle($content, $cipRe['settlement_account_type']);
        if(!$cstRe['ask']){
            $return['message'] = '错误的标题,' . $cstRe['message'];
            return $return;
        }
        
        /**
         * 2、导入准备
         */
        //校验文件是否已导入成功
        $asiModel = new AliexpressSettlementImport();
        $asi_has = $asiModel->where('file_md5',$file_md5)->field('id,status')->find();
        if(param($asi_has, 'status') == '2'){
            $return['ask'] = 2;
            $return['message'] = "当前文件：{$file_name}，已导入完成，无需重复导入!";
            return $return;
        }
        if($this->fileExists($file_md5)){
            $return['ask'] = 2;
            $return['message'] = "当前文件：{$file_name}，已加入处理队列，无需重复导入!";
            return $return;
        }
        
        /**
         * 3、写入数据库、文件存储、加入列队
         */
        //写入aliexpress结算报告导入表
        $asi_row = [
            'id'=>param($asi_has, 'id' ,0),
            'account_id'=>$account_id,
            'account_code'=>$account_code,
            'start_date'=>$cipRe['start_date'],
            'end_date'=>$cipRe['end_date'],
            'file_name'=>$file_name,
            'file_md5'=>$file_md5,
            'settlement_account_type'=>$cipRe['settlement_account_type'],
            'user_id'=>Common::getUserInfo()->toArray()['user_id'],
            'status'=>1,
            'create_time'=>time(),
        ];
        if(!$asiModel->isUpdate(isset($asi_row['id']) && $asi_row['id'])->save($asi_row)){
            $return['message'] = '操作aliexpress结算报告导入表数据失败';
            return $return;
        }
        $id = $asiModel->id;//主键id
        //文件存储
        if(!$this->saveFile($file_md5, $content)){
            $return['message'] = '文件存储失败';
            return $return;
        }
        //加入列队
        $queue_data = [
            'id'=>$id,//N aliexpress结算报告导入表id
            'file_md5'=>$file_md5,//Y 文件名称
        ];
        (new UniqueQueuer(AliexpressSettlementImportQueue::class))->push(json_encode($queue_data));
        
        
        /**
         * 4、更新时间卡
         */
        /*
         * TODO:lingpeng
         */
        
        
        /**
         * 5、整理返回数据
         */
        $return['ask'] = 1;
        $return['message'] = '导入成功!';
        return $return;
    }
    
    /**
     * @desc 处理结算报告文件
     * @author wangwei
     * @date 2019-1-9 18:30:20
     * @param int $aliexpress_settlement_import_id //Y 导入表id
     */
    public function processSettlementFile($aliexpress_settlement_import_id){
        $return = [
            'ask'=>0,
            'message'=>'processSettlementFile error',
            'account_id'=>'',
            'min_time'=>'',
            'max_time'=>'',
            'currency_code'=>'',
        ];
        
        /**
         * 1、参数校验
         */
        if(empty($aliexpress_settlement_import_id)){
            $return['message'] = 'aliexpress_settlement_import_id not Empty';
            return $return;
        }
        $asi_row = (new AliexpressSettlementImport())->where('id',$aliexpress_settlement_import_id)->field('file_name,file_md5,settlement_account_type,account_code')->find();
        if(empty($asi_row)){
            $return['message'] = 'aliexpress_settlement_import data not Exists';
            return $return;
        }
        
        /**
         * 2、获取文件
         */
        if(!$content = $this->getFile($asi_row['file_md5'])){
            $return['message'] = 'getFile is Empty';
            return $return;
        }
        
        /**
         * 3、处理文件
         */
        $isrRe = $this->importSettlementReport($asi_row['file_name'], $content,$asi_row['settlement_account_type'], $asi_row['account_code']);
        /*
         * 处理成功或者已经导入，删除文件
         * 试运行，不删除文件
         * wangwei 2019-1-9 18:33:53
         */
        if($isrRe['ask']==1){
//             $this->deleteFile($asi_row['file_md5']);
        }
        
        /**
         * 4、整理返回数据
         */
        $return['ask'] = $isrRe['ask'];
        $return['message'] = $isrRe['message'];
        $return['account_id'] = $isrRe['account_id'];
        $return['min_time'] = $isrRe['min_time'];
        $return['max_time'] = $isrRe['max_time'];
        $return['currency_code'] = $isrRe['currency_code'];
        return $return;
    }
    
}