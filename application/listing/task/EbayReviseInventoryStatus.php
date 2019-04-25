<?php

/**
 * Description of EbayReviseInventoryStatus
 * @datetime 2017-6-21  17:40:24
 * @author joy
 */

namespace app\listing\task;
use app\index\service\AbsTasker;

class EbayReviseInventoryStatus extends AbsTasker{
    /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return "ebay修改一口价listing的售价和库存";
    }
    /**
     * 定义任务描述
     * @return string
     */
    public function getDesc()
    {
        return "ebay修改一口价listing的售价和库存";
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
        $where['status']=['=',0];
        $model = new \app\common\model\ebay\ebayListingPriceQuanlity;
        $service = new \app\listing\service\EbayListingHelper;
        $page=1;
        $pageSize=30;
        do{
            $products = $model->field('a.*,c.token')->alias('a')->join('ebay_listing b','a.item_id=b.item_id','LEFT')->join('ebay_account c','b.account_id=c.id','LEFT')->where($where)->page($page,$pageSize)->select();
            if(empty($products))
            {
                break;
            }else{
                $service->reviseInventoryStatus($products);
                $page = $page + 1;
            }
        }while (count($products)==$pageSize);
    }
    
}
