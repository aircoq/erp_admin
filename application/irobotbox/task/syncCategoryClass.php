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

class syncCategoryClass extends AbsTasker
{
	public function getName()
    {
        return "获取赛和erp商品类目";
    }
    
    public function getDesc()
    {
        return "获取赛和erp商品类目";
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
        self::syncProductClass();           
    }

    public function syncProductClass(){
        $cla = new CategoryClass();
        $cate = $cla->where("status=0")->order("id")->find();
    	$iroApi = new IrobotboxApi("http://gg7.irobotbox.com/Api/API_ProductInfoManage.asmx?wsdl");
        if(empty($cate)){
            $parentId = 0;
        }else{
            $parentId = $cate->id;
            $cla->where(array("id"=>$cate['id']))->update(array("status"=>1));
        }
    	$result=$iroApi->createSoapCli()->GetProductClass($parentId);
        if(isset($result->GetProductClassResult->ProductClassList->ApiProductClass)){
            $cateArr = $result->GetProductClassResult->ProductClassList->ApiProductClass;
            foreach($cateArr as $k => $v){
                $rows['cate_id'] = $v->ID;
                $rows['parent_id'] = $v->ParentID;
                $rows['class_name'] = $v->ClassName;
                $rows['class_name_cn'] = $v->ClassNameCn;
                $rows['grade'] = $v->Grade;
                $rows['is_active'] = $v->IsActive;
                $rows['is_lock'] = $v->IsLock;
                $rs=$cla->where(array("cate_id"=>$rows['cate_id']))->find();
                if(!$rs){#
                    echo "<pre>";
                    print_r($rs);
                    #$cla->insert($rows);
                }
                unset($rows);
            }
        }
    }
}