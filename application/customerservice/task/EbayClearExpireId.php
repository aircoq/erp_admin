<?php
namespace app\customerservice\task;

use app\index\service\AbsTasker;
use app\common\cache\Cache;


class EbayClearExpireId extends AbsTasker{
    public function getName()
    {
        return "Ebay清除过期 ID";
    }

    public function getDesc()
    {
        return "Ebay清除过期 ID（站内信）";
    }

    public function getCreator()
    {
        return "TanBin";
    }

    public function getParamRule()
    {
        return [];
    }

    public function execute()
    {
        //删除过期站内信ID
        $service = Cache::store('EbayMessage');
        $service->delExpireMessage();
        return true;
    }
    
}