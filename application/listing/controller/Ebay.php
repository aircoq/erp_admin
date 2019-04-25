<?php

/**
 * Description of Ebay
 * @datetime 2017-6-20  9:40:27
 * @author joy
 */

namespace app\listing\controller;
use app\common\controller\Base;
use app\common\exception\JsonErrorException;
use app\listing\queue\EbayEndItem;
use app\listing\queue\EbayEndItemQueue;
use app\listing\queue\EbayPromotionQueue;
use app\listing\queue\EbayRelistItemQueue;
use app\publish\service\EbayCtrl;
use think\Request;
use app\publish\queue\WishQueue;
use app\listing\service\EbayListingHelper;
use app\listing\service\RedisListing;
use app\publish\queue\EbayGetItemQueue;
use app\common\service\UniqueQueuer;
use app\common\model\ebay\EbayListing;

/**
 * @module listing系统
 * @title Ebay在线listing管理
 * Class Ebay
 * @package app\listing\controller
 */
class Ebay extends Base{

    private $helper=null;
    private $redis=null;
    protected function init()
    {
        if(empty($this->helper))
        {
            $this->helper = new EbayListingHelper;
        }

        if(empty($this->redis))
        {
            $this->redis = new RedisListing;
        }
    }

    /**
     * @title 同步促销规则
     * @url /rsync-ebay-promotion
     * @author joy
     * @method post
     * @param think\Request $request
     * @return type
     */
    public function rsyncPromotion(Request $request)
    {
        try{
            $data = $request->param('data');

            if(empty($data))
            {
                return json(['message'=>'你提交的数据为空'],400);
            }else{
                $jobs = explode(',',$data);
            }

            if($jobs && is_array($jobs))
            {
                foreach ($jobs as $job)
                {
                    (new EbayListingHelper())->pushParamToQueue($job,EbayPromotionQueue::class);
                }
                return json(['message'=>'同步成功，稍后自动执行']);
            }else{
                return json(['message'=>'你提交的数据为空'],400);
            }
        }catch (JsonErrorException $exp){
            throw new JsonErrorException($exp->getFile().$exp->getLine().$exp->getMessage());
        }


    }
    /**
     * @title 应用公共模块
     * @url /application-ebay-common-module
     * @author joy
     * @method post
     * @param think\Request $request
     * @return type
     */
    public  function appCommonModule()
    {
        $data = $this->request->instance()->param('data');
        
        if(empty($data))
        {
            return json(['message'=>'你提交的数据为空'],400);
        }
        #$response = $this->helper->commonModule($data,'common');
        $response = $this->helper->saveCommonModule($data);
        if($response['result'])
        {
            return json(['message'=>$response['message']]);
        }else{
            return json(['message'=>$response['message']],500);
        } 
        
    }
    /**
     * @title 获取商品所有图片
     * @url /update-ebay-product-sale_note
    * @author joy
     * @method post
     * @param think\Request $request
     * @return type
     */
    public  function updateProdcutSale()
    {
        $data = $this->request->instance()->param('data');
        
        if(empty($data))
        {
            return json(['message'=>'你提交的数据为空'],400);
        }
       
        $response = $this->helper->editProductData($data,'sale');
        
        if($response['result'])
        {
            return json(['message'=>$response['message']]);
        }else{
            return json(['message'=>$response['message']],500);
        } 
       
    }
     /**
     * @title 获取商品所有图片
     * @url /get-ebay-product-images
    * @author joy
     * @method get
     * @param think\Request $request
     * @return type
     */
    public  function getProductImages()
    {
        $ids = request()->param('data');
        
        if(empty($ids))
        {
            return json(['message'=>'你提交的数据为空'],400);
        }
       
        $data = $this->helper->ProductImages($ids);
        
        return json(['data'=>$data],isset($data['result'])?500:200);
    }
    
    /**
     * @title 修改商品图片
     * @url /update-ebay-product-images
    * @author joy
     * @method post
     * @param think\Request $request
     * @return type
     */
    public  function productImages()
    {
        $data = $this->request->instance()->param('data');
        
        if(empty($data))
        {
            return json(['message'=>'你提交的数据为空'],400);
        }
       
        $message = (new EbayListingHelper())->updateProductImages($data);
        return json(['message'=>$message]);
    }
    
    
    /**
     * @title 促销折扣设置
     * @url /ebay-promotion
    * @author joy
     * @method post
     * @param think\Request $request
     * @return type
     */
    public  function promotion_cost()
    {
        $data = $this->request->instance()->param('data');
        
        if(empty($data))
        {
            return json(['message'=>'你提交的数据为空'],400);
        }
       
        $response = $this->helper->editProductData($data,'promotion');
        
        if($response['result'])
        {
            return json(['message'=>$response['message']]);
        }else{
            return json(['message'=>$response['message']],500);
        } 
        
         
    }
    /**
     * @title 自动补货设置
     * @url /ebayReplenishment
    * @author joy
     * @method post
     * @param think\Request $request
     * @return type
     */
    public function ebayReplenishment(Request $request)
    {
        $data = $request->instance()->param('data');
        
        if(empty($data))
        {
            return json(['message'=>'你提交的数据为空'],400);
        }
       
        $response = $this->helper->ebayReplenishmentService($data,'buhuo');
        
        if($response['result'])
        {
            return json(['message'=>$response['message']]);
        }else{
            return json(['message'=>$response['message']],500);
        } 
    }
    
     /**
     * @title 重新上架规则
     * @url /ebayReshelf
     * @author joy
     * @method post
     * @param think\Request $request
     * @return type
     */
    public function ebayReshelf(Request $request)
    {
        $data = $request->instance()->param('data');
        if(empty($data))
        {
            return json(['message'=>'你提交的数据为空'],400);
        }
       
        $response = $this->helper->editProductSettingData($data,'reshelf');
        
        if($response['result'])
        {
            return json(['message'=>$response['message']]);
        }else{
            return json(['message'=>$response['message']],500);
        }
        
        
    }
    /**
     * @title Ebay上架
     * @url /onlineEbayProduct
     * @method post
     * @author joy
     * @access public
     */
    public function onlineEbayProduct(Request $request)
    {
       $productIds = $request->instance()->param('productIds') ;
        
       if(empty($productIds))
       {
           return json(['message'=>'请选择你要上架的商品'],400);
       }
       $jobs = explode(';', $productIds);
       
       if($jobs)
       {
           foreach ($jobs as $key => $job) 
           {
               $this->helper->updateListingStatus(3, ['item_id'=>$job]);
	           (new EbayListingHelper())->pushParamToQueue($job,EbayRelistItemQueue::class);
               //$this->redis->myZdd('onlineEbayProduct',time(),$job);
           }
       }
       return json(['message'=>'上架成功，稍后执行...']);
    }
    
    /**
     * @title Ebay下架
     * @url /offlineEbayProduct
     * @author joy
     * @method post
     * @param think\Request $request
     * @access public
     */
    public function offlineEbayProduct (Request $request)
    {
       $productIds =$request->instance()->param('productIds') ;
       
       if(empty($productIds))
       {
           return json(['message'=>'请选择你要下架的商品'],400);
       }
       $jobs = explode(',', $productIds);
        $res = (new EbayCtrl())->endItems($jobs);
       
//       if($jobs)
//       {
//           foreach ($jobs as $key => $job)
//           {
//               $this->helper->updateListingStatus(9, ['item_id'=>$job]);
//	           (new EbayListingHelper())->pushParamToQueue($job,EbayEndItemQueue::class);
//               //$this->redis->myZdd('offlineEbayProduct',time(),$job);
//           }
//       }
       return json($res);
    }
   
    /**
     * @title 店铺分类
     * @url /editEbayShopCategory
    * @author joy
     * @method post
     * @param think\Request $request
     * @return type
     */
    public function editEbayShopCategory(Request $request)
    {
        $data = $request->instance()->param('data');
        
        if(empty($data))
        {
            return json(['message'=>'你提交的数据为空'],400);
        }
       
        $response = $this->helper->editProductData($data,'shop_category');
        if($response['result'])
        {
            return json(['message'=>$response['message']]);
        }else{
            return json(['message'=>$response['message']],500);
        } 
    }
    
    /**
     * @title 商品标题
     * @url /editEbayTitle
    * @author joy
     * @method post
     * @param think\Request $request
     * @return type
     */
    public function editEbayTitle(Request $request)
    {
        $data = $request->instance()->param('data');
        
        if(empty($data))
        {
            return json(['message'=>'你提交的数据为空'],400);
        }
       
        $response = $this->helper->editProductData($data,'title');
        if($response['result'])
        {
            return json(['message'=>$response['message']]);
        }else{
            return json(['message'=>$response['message']],500);
        } 
    }
    
    /**
     * @title 商品一口价和可售数量
     * @url /editEbayProductPriceQuantity
     * @access public
     * @author joy
     * @method post
     * @param think\Request $request
     * @return type
     */
    public function editEbayProductPriceQuantity(Request $request)
    {
        $data = $request->instance()->param('data');
        
        if(empty($data))
        {
            return json(['message'=>'你提交的数据为空'],400);
        }
        
        $response = $this->helper->updateListingOrVariant($data,'productPriceQuantity');
        if($response['result'])
        {
            return json(['message'=>$response['message']]);
        }else{
            return json(['message'=>$response['message']],500);
        } 
    }
    
    /**
     * @title 商品拍卖价
     * @url /editEbayProductAuctionPrice
     * @access public
     * @author joy
     * @method post
     * @param think\Request $request
     * @return type
     */
    public function editEbayProductAuctionPrice(Request $request)
    {
        $data = $request->instance()->param('data');
        
        if(empty($data))
        {
            return json(['message'=>'你提交的数据为空'],400);
        }
        
        $response = $this->helper->editProductData($data,'auctionPrice');
        if($response['result'])
        {
            return json(['message'=>$response['message']]);
        }else{
            return json(['message'=>$response['message']],500);
        } 
    }
    
    /**
     * @title 同步listing
     * @method post
     * @url /rsyncEbayProduct
     * @access public
   * @author joy
     * @param think\Request $request
     * @return type
     */
    public function rsync(Request $request)
    {
        $product_ids = $request->instance()->param('product_ids');
        
        if(empty($product_ids))
        {
            return json(['message'=>'请选择listing'],400);
        }
        $jobs = explode(',', $product_ids);
        if(is_array($jobs) && $jobs)
        {
           $count = 0;
           $itemAccountIds = EbayListing::where(['item_id'=>['in',$jobs],'draft'=>0])->column('account_id','item_id');
            if (count($jobs) == 1) {//如果仅有一条，不加入队列
                $res = $this->helper->syncItem($jobs[0],$itemAccountIds[$jobs[0]]);
                return json($res['message'],$res['result']?200:500);
            }
           foreach ($jobs as $key => $job) 
           {
                (new UniqueQueuer(EbayGetItemQueue::class))->push($itemAccountIds[$job].','.$job);
                $count = $count + 1;
           }
        }
        return json(['message'=>'同步['.$count.']条listing，稍后将自动执行...']);
    }
    
    /**
     * @title 更新修改了资料的listing
     * @url /rsyncEditEbayProduct
     * @access public
    * @author joy
     * @method post
     * @param think\Request $request
     * @return type
     */
    public function rsyncEditEbayProduct(Request $request)
    {
        $product_ids = $request->instance()->param('product_ids');
        
        if(empty($product_ids))
        {
            return json(['message'=>'请选择listing'],400);
        }
        $jobs = explode(';', $product_ids);
        
        if(is_array($jobs) && $jobs)
        {
           $count = 0;
           foreach ($jobs as $key => $job) 
           {
               if($this->helper->getEbayProductUpdateStatus($job,"=",5))
               {
                    $count = $count + 1;
	               (new EbayListingHelper())->pushParamToQueue($job,EbayRelistItemQueue::class);
                    //$this->redis->myZdd('editEbayProduct',time(),$job);
               }
           }
        }
        return json(['message'=>'更新['.$count.']条listing，稍后将自动执行...']);
    }

}
