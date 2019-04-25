<?php
namespace app\index\task;

use app\common\model\Server;
use app\index\service\AbsTasker;
use app\index\service\ConfigService;
use app\internalletter\service\DingTalkService;



class ServerReportTimePastTask extends AbsTasker{
    public function getName()
    {
        return "定期检查服务器上报时间";
    }

    public function getDesc()
    {
        return "定期检查服务器上报时间";
    }

    public function getCreator()
    {
        return "libaimin";
    }

    public function getParamRule()
    {
        return [];
    }

    public function execute()
    {
        $time = date('H');
        if($time < 9 || $time > 21){
            return false;
        }
        $outTime = time();
        $where = [
            'status' => 0,
            'type' => 0,
        ];
        $field = 'reporting_time,name,ip,reporting_cycle,reporting_time';
        $pastServer = (new Server())->field($field)->where($where)->order('reporting_time,id')->select();
        $message = '';
        $messages = [];
        $max = 10;
        foreach ($pastServer as $k => $v) {
            $oTime = $outTime - $v['reporting_cycle'] * 60 * 3;
            if($oTime > $v['reporting_time'] ){
                $message .= '名称:'.$v['name'].'，IP:'.$v['ip'].',上次上报时间'.date('Y-m-d H:i:s',$v['reporting_time']).'
            ';
                if($k == $max){
                    $max += 10;
                    $messages[] = $message;
                    $message = '';
                }
            }

        }
        $messages[] = $message;

        $chatId = (new ConfigService())->getConfigData('system_chat_id_server');
        if(!$chatId){
            return false;
        }
        foreach ($messages as $message){
             if($message){
                $message = '【以下服务器上报时间超过3个周期，请处理】
            '.$message;
                $message = trim($message,',');
                $datas = [
                    'chat_id' => $chatId,
                    'content' => $message,
                ];
                $res = DingTalkService::send_chat_message_post($datas);
            }
        }
        return true;
    }



}