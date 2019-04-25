<?php
namespace app\irobotbox\task;
/**
 * rocky
 * 17-4-22
 * 获取商品图片
*/

use app\index\service\AbsTasker;
use service\irobotbox\IrobotboxApi;
use think\Db;

class syncProductImages extends AbsTasker
{
	public function getName()
    {
        return "获取商品图片";
    }
    
    public function getDesc()
    {
        return "获取商品图片";
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
        self::syncProductImages();           
    }

    public function syncProductImages(){
    	$iroApi = new IrobotboxApi("http://gg7.irobotbox.com/Api/API_ProductInfoManage.asmx?wsdl");
        $headers = array(
                    "Customer_ID"=>$iroApi->Customer_ID,
                    "Username"=>$iroApi->Username,
                    "Password"=>$iroApi->Password,
            );
        $nameSpace = "http://tempuri.org/";
        $className = "HeaderUserSoapHeader";
    	$rows=$iroApi->createSoapCli()->createSoapHeader($nameSpace,$className,$headers)->setClientHeaders()->GetProductImages();
    	echo "<pre>";
    	print_r($rows);
    }
}