<?php
namespace app\irobotbox\task;
/**
 * rocky
 * 17-4-11
 * 获取赛和erp商品类目
*/

use app\index\service\AbsTasker;
use app\common\model\CategoryClass;
use service\irobotbox\IrobotboxApi;
use think\Db;
class downLoadImg extends AbsTasker
{
	public function getName()
    {
        return "下载赛和图片信息";
    }
    
    public function getDesc()
    {
        return "下载赛和图片信息";
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
        self::downLoadImg();           
    }

    public function downLoadImg(){
    	$url = "http://img9.irobotbox.com/public";
    	set_time_limit(0); 
    	$imgsArr=Db::name("goods_sku_img")->where(array("status"=>0))->order("id asc")->limit(0,200)->select();
    	foreach($imgsArr as $k => $imgs){
    		if(!empty($imgs['image_url'])){
    			$imgUrl = $url.$imgs['image_url'];
		    	$path = substr(dirname(__FILE__),0,strpos(dirname(__FILE__),"application"))."irobotbox";

		    	$fileName = str_replace("/","\\",substr($imgUrl,strpos($imgUrl,"product/")));
		    	$filePath = $path."\\".substr($fileName,0,strripos($fileName,"\\"));#目录路径

		    	if(!is_dir($filePath)){#如果目录不存在则创建
		    		$rs=mkdir($filePath,0777,true);
		    	}
		    	$imagePath = $path."\\".$fileName;
		    	if(($str=@file_get_contents($imgUrl))!==false){
		    		$res=file_put_contents($imagePath,file_get_contents($imgUrl));
                    if($res>0){
                        $upDa['status']=1;#下载完成
                        $upDa['download_path'] = $fileName;
                    }else{
                        $upDa['status']=2;#下载失败
                    }
		    	}else{
		    		$upDa['status']=2;#下载失败
		    	}
    		}else{
    			$upDa['status']=2;#下载失败
    		}
    		Db::name("goods_sku_img")->where(array("id"=>$imgs['id']))->update($upDa);
    	}
    }
}