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

class syncProductSupplierPrice extends AbsTasker
{
	public function getName()
    {
        return "获取商品供应商信息";
    }
    
    public function getDesc()
    {
        return "获取商品供应商信息";
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
        self::GetProductSupplierPrice();           
    }

    public function GetProductSupplierPrice(){
    	$iroApi = new IrobotboxApi("http://gg7.irobotbox.com/Api/API_ProductInfoManage.asmx?wsdl");
    	$rows=$iroApi->createSoapCli()->GetProductSupplierPrice();
    	echo "<pre>";
    	print_r($rows);
    }
}