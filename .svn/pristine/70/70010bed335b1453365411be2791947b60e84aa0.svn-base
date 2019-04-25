<?php


namespace app\listing\task;

use app\index\service\AbsTasker;
use app\common\model\wish\WishWaitUploadProduct;
use app\common\model\wish\WishWaitUploadProductVariant;

/**
 * wish平台批量下载产品任务job
 *
 * @author joy
 */
class WishProductStat extends AbsTasker{
     /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return "统计wish变体信息";
    }
     /**
     * 定义任务描述
     * @return string
     */
    public function getDesc()
    {
        return "统计wish变体信息";
    }
    /**
     * 定义任务作者
     * @return string
     */
   
    public function getCreator()
    {
        return "joy";
    }
    /**
     * 定义任务参数规则
     * @return array
     */
    public function getParamRule()
    {
        return [];
    }
     /**
     * 任务执行内容
     * @return void
     */
    
    public  function execute()
    {
        set_time_limit(0);
        $productModel = new WishWaitUploadProduct;
        $variantModel = new WishWaitUploadProductVariant;
        $service =  new \app\listing\service\WishListingHelper;
        $page =1;
        $pageSize=50;
        try{
           do {     
                $products = $productModel->field('id')->page($page,$pageSize)->order('id desc')->select();
                
                if(empty($products)){
                     break;
                } else{
                    
                    foreach ($products as $product)
                    {   
                        $v = $product->toArray();
                        
                        $where['pid']=['=',$v['id']];
                        $update = $service->ProductStat($variantModel, $where);
                        if($update)
                        {
                             $productModel->update($update, ['id'=>$v['id']]); 
                        } 
                    } 
                    $page++;
                }        
            } while (count($products)==$pageSize);
        } catch (TaskException $exp){
            var_dump($exp->getMessage());
        }
         
    }
}
