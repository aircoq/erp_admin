<?php
namespace app\publish\task;
/**
 * 曾绍辉
 * 17-7-3
 * ebay用于监听listing重上与定时规则
*/

use app\index\service\AbsTasker;
use app\common\cache\Cache;
use service\ebay\EbayApi;
use think\Db;
use think\cache\driver;
use app\publish\queue\EbayTimingQueuer;
use app\publish\queue\EbayQueuer;
use app\publish\queue\EbayRelistQueuer;


class EbayListening extends AbsTasker
{
	public function getName()
    {
        return "ebay监听定时刊登队列";
    }
    
    public function getDesc()
    {
        return "ebay监听定时刊登队列";
    }
    
    public function getCreator()
    {
        return "曾绍辉";
    }
    
    public function getParamRule()
    {
        return [];
    }
    
    
    public function execute()
    {   
        self::listening();#定时刊登      
        self::restart();#定时重上
    }

    #监听定时刊登
    public function listening(){
        $timque = new EbayTimingQueuer('ebay_timing_publish');#定时刊登有序集合
        $que = new EbayQueuer();#ebay刊登队列
    	$t = time();
        $ids = $timque->consumptionRem(['start'=>0,'end'=>$t]);
        if($ids){
            $que->production($ids);
        }
    }

    #监听定时重上
    public function restart(){
    	$timque = new EbayTimingQueuer('ebay_timing_relist');#定时重上有序集合
        $que = new EbayRelistQueuer();#ebay重上队列
        $t = time();
        $ids = $timque->consumptionRem(['start'=>0,'end'=>$t]);
        if($ids){
            $que->production($ids);
        }
    }
}