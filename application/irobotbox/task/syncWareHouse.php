<?php
namespace app\irobotbox\task;
/**
 * rocky
 * 17-4-22
 * 获取商品供应商信息
*/

use app\index\service\AbsTasker;
use service\irobotbox\IrobotboxApi;
use think\Db;

class syncWareHouse extends AbsTasker
{   
	public function getName()
    {
        return "获取仓库信息";
    }
    
    public function getDesc()
    {
        return "获取仓库信息";
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
        self::GetWareHouseList();           
    }

    public function GetWareHouseList(){
    	$iroApi = new IrobotboxApi("http://gg7.irobotbox.com/Api/API_Irobotbox_Orders.asmx?wsdl");
    	$rows=$iroApi->createSoapCli()->GetWareHouseList();
    	echo "<pre>";
    	print_r($rows);
    }
}