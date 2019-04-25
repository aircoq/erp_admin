<?php
namespace app\customerservice\task;

use app\index\service\AbsTasker;
use app\customerservice\service\aliexpress\SynAliexpressMsg;
use app\common\exception\TaskException;
use think\Exception;

class AliexpressMsgSyn extends AbsTasker
{
    public function getName() {
        return 'Aliexpress站内信/订单留言';
    }
    
    public function getCreator() {
        return '龙志军';
    }
    
    public function getDesc() {
        return 'Aliexpress站内信/订单留言';
    }   
    
    public function getParamRule() {
        return [];
    }
    
    public function execute() {
        try {
            $synServer = new SynAliexpressMsg();
            $synServer->synMsg();
        } catch (Exception $ex) {
            throw new TaskException($ex->getMessage());
        }
    }
}

