<?php

/**
 * Description of EbayPublish
 * @datetime 2017-6-13  9:38:44
 * @author joy
 */

namespace app\publish\controller;
use app\common\controller\Base;
use app\common\exception\JsonErrorException;
use app\publish\service\EbayPublishService;

/**
 * @module 刊登系统
 * @title ebay未刊登
 * @author joy
 */

class EbayPublish extends Base{

    /**
     * @title eBay未刊登列表
     * @access public
     * @method get
     * @apiRelate app\publish\controller\JoomCategory::category
     * @url /ebay-unpublished
     * @return json
     */
    public  function unpublished()
    {
        try{
            $param = $this->request->instance()->param();
        
            $page = $this->request->param('page',1);

            $pageSize = $this->request->param('pageSize',30);
            $fields = "*";
            $response = (new EbayPublishService)->getWaitPublishGoods($param, $page, $pageSize, $fields);
            return json($response);
        } catch (JsonErrorException $e){
            return json(['message' => $e->getMessage(),'data'=>[]],500);
        }
        
        
    }
}
