<?php
namespace app\report\task;

use app\common\cache\Cache;
use app\common\service\ChannelAccountConst;
use app\index\service\AbsTasker;
use app\index\service\BasicAccountService;
use think\Exception;

/**
 * 财务结算下载
 */
class SettlementReportDownload extends AbsTasker
{


    public function getCreator()
    {
        return 'linpeng';
    }

    public function getDesc()
    {
        return '财务结算触发下载';
    }

    public function getName()
    {
        return '财务结算触发下载';
    }

    public function getParamRule()
    {
        return [
            'channel|平台' => 'require|select:速卖通:4,wish:3',
            'account_id|账号id' => '',
            'init_time|指定时间(Y-m-d)' =>'',
        ];
    }

    public function execute()
    {
        try {
            $channel = $this->getData('channel');
            $account_id = $this->getData('account_id');
            $account_id = $account_id ?? 0;
            $init_time = $this->getData('init_time');
            //检验时间格式
            $timestamp_init = 0;
            if ($init_time && !($timestamp_init = strtotime($init_time))) {
                throw new Exception('strtotime()无法解析的时间格式');
            }

            switch ($channel){
                case ChannelAccountConst::channel_wish:
                    $account_list = Cache::store('WishAccount')->getAccount($account_id);
                    if ($account_id) {
                        $account_list = [$account_list];
                    }
                    $this->wishDownload($account_list,$timestamp_init);
            }



        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    public function wishDownload($account_list,$timestamp_init =0)
    {
        $basicAccountService = new BasicAccountService();
        foreach ($account_list as $account){
            if (!param($account, 'id')) {
                return false;
            }
            if (!param($account, 'code')) {
                return false;
            }

            /** 如果指定时间，下载指定时间 前后范围5天内的报告 */

            if ($timestamp_init) {
                $start_time  = date('Y-m-d',$timestamp_init - 86400 * 5);
                $end_time = date('Y-m-d',$timestamp_init + 86400 * 5);
                $basicAccountService->doCatchTransactionTotal(ChannelAccountConst::channel_wish, $account['code'], $start_time, $end_time);
                continue;
            }
            $lastUpdate = Cache::store('SettleReport')->getWishReportDownloadTime($account['id']);
            $fromTime = $lastUpdate ? strtotime($lastUpdate) : 0;
            $lastdownloadTime = $fromTime > 0 ? $fromTime : strtotime('2017-12-16');
            if ($fromTime > 0) {
                $diffTime = time() - $fromTime;
                $limit = 60 * 60 * 24 * 15 - 86400;
                if ($diffTime < $limit) {
                    continue;
                }
            }
            $downloadTime = $lastdownloadTime + 86400 * 15;     //下载日期往后延15天，进入下一个下载时间段;
            $start_time = date('Y-m-d',$downloadTime - 86400 * 5);       //下载范围 = 下载日期前五天
            $end_time = date('Y-m-d',$downloadTime + 86400 * 5);       //下载范围 = 下载日期后五天
            $basicAccountService->doCatchTransactionTotal(ChannelAccountConst::channel_wish, $account['code'], $start_time, $end_time);
        }


    }

}
