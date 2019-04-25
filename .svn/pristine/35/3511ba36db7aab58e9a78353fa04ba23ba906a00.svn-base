<?php

/**
 * Description of EbayReviseItem
 * @datetime 2017-6-22  15:00:24
 * @author joy
 */

namespace app\listing\task;
use app\index\service\AbsTasker;
class EbayReviseItem extends AbsTasker{
    /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return "ebay修改item";
    }
    /**
     * 定义任务描述
     * @return string
     */
    public function getDesc()
    {
        return "ebay修改item";
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
       $model=new \app\common\model\ebay\EbayListing;
       $helper = new \app\listing\service\EbayListingHelper;
       
        
       $page=1;
       $pageSize=30;
       $where['listing_status']=['=',5];
       //$where['id']=['=',14];
       do{
           $products= $model->alias('a')->with(['account'=>function($query){$query->field('id,token,code');},'promotion','variant','images'=>function($query){$query->where(['status'=>0]);},'setting','specifics','internationalShipping','shipping'])->where($where)->page($page,$pageSize)->select();
           if(empty($products))
           {
               break;
           }else{
               $helper->reviseFixedPriceItem($products);
               ++$page; 
           }
       }while(count($products)==$pageSize);
    }
}
