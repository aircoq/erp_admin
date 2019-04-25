<?php
namespace app\publish\task;
/**
 * rocky
 * 17-4-17
 * ebay获取账号自定义类目
*/

use think\Db;
use app\index\service\AbsTasker;
use app\publish\service\JoomReptitle as JoomReptitleService;

class JoomReptitle extends AbsTasker
{
	public function getName()
    {
        return "Joom下载color和size";
    }
    
    public function getDesc()
    {
        return "Joom下载color和size";
    }
    
    public function getCreator()
    {
        return "张冬冬";
    }
    
    public function getParamRule()
    {
        return [];
    }
    
    
    public function execute()
    {
        $service = new JoomReptitleService();
        $service->getColor();
        $service->getSize();
    }

}