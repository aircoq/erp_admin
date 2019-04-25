<?php
namespace app\publish\task;
/**
 * 曾绍辉
 * 17-8-5
 * ebay用于监听listing状态加入缓存队列
*/

use app\index\service\AbsTasker;
use app\common\cache\Cache;
use service\ebay\EbayApi;
use think\Db;
use think\cache\driver;
use app\publish\queue\EbayQueuer;

class EbayPublishQueuer extends AbsTasker{
	public function getName()
    {
        return "ebay监听队列";
    }
    
    public function getDesc()
    {
        return "ebay用于监听listing状态加入缓存队列";
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
    	self::joinQueuer();#加入队列
    }

    public function joinQueuer(){
    	$queuer = new EbayQueuer();
    	$queuer->production();
    }
}

