<?php

/**
 * Description of Aliexpress
 * @datetime 2017-5-25  17:05:42
 * @author joy
 */

namespace app\listing\controller;
use app\common\controller\Base;
use app\common\exception\JsonErrorException;
use app\listing\queue\AliexpressOfflineProductQueue;
use app\listing\queue\AliexpressOnlineProductQueue;
use app\listing\queue\AliexpressRenewExpireQueue;
use app\listing\queue\AliexpressRsyncEditListingQueue;
use app\listing\queue\AliexpressRsyncProductQueue;
use app\listing\task\AliexpressOnlineProduct;
use app\listing\task\AliexpressRenewExpire;
use app\listing\task\AliexpressRsyncProduct;
use think\Request;
use app\listing\service\AliexpressListingHelper;
use app\common\service\Common;
use think\Exception;
use app\publish\queue\WishQueue;
/**
 * @module listing系统
 * @title 速卖通listing管理
 * Class Aliexpress
 * @package app\listing\controller
 */

class Aliexpress extends Base{
    private $helper;
    private $redis;
    private $uid;
    protected function init()
    {
        $this->helper = new AliexpressListingHelper;
        //$this->redis = new \app\listing\service\RedisListing;
        $this->uid=Common::getUserInfo($this->request) ? Common::getUserInfo($this->request)['user_id'] : 0;
    }

    /**
     * @title 获取选中spu的分类
     * @url listing/aliexpress/get-same-spu-category
     * @author joy
     * @method get
     * @param Request $request
     * @return string
     */
    public function getSameSpuCategory(Request $request)
    {
        $id = $request->param('id');
        if(!$id)
        {
            throw new JsonErrorException('参数错误');
        }

        $response = (new AliexpressListingHelper())->getSameSpuCategoryService($id);

        return json($response);

    }
    /**
     * @title 查询所选SPU的产品分类值,及对应的属性、属性值
     * @url listing/aliexpress/get-same-spu-Attribute
     * @author joy
     * @method get
     * @param Request $request
     * @return string
     */
    public function getSameSpuAttribute(Request $request)
    {
        $id = $request->get('id');

        if(!$id)
        {
            throw new JsonErrorException('参数错误');
        }

        $goods_id = $request->param('goods_id');
        if(!$goods_id)
        {
            throw new JsonErrorException('商品id必填');
        }
        $account_id = $request->param('account_id');
        if(!$account_id)
        {
            throw new JsonErrorException('帐号id必填');
        }

        $response = (new AliexpressListingHelper())->getSameSpuAttribute($id,$goods_id,$account_id);

        return json($response);
    }
    /**
     * @title 速卖通获取类似产品
     * @url listing/aliexpress/getSameSpu
     * @author joy
     * @method get
     * @param Request $request
     * @return string
     */
    public function getSameSpu(Request $request)
    {
        /*
        $spu = $request->param('spu');

        if(empty($spu))
        {
            throw new JsonErrorException('spu不能为空');
        }*/

        $account_id = $request->param('account_id');
        if(empty($account_id))
        {
            throw new JsonErrorException('帐号id不能为空');
        }
        $response = (new AliexpressListingHelper())->getSameSpuService($account_id);
        return json($response);


    }
    /**
     * @title 速卖通在线listing修改日志
     * @url listing/aliexpress/logs
     * @author joy
     * @method get
     * @param Request $request
     * @return string
     */

    public function logs()
    {
        try {
            $request = Request::instance();
            $page = $request->get('page', 1);
            $pageSize = $request->get('pageSize', 50);

            //搜索条件
            $param = $request->param();

            if(!isset($param['product_id']))
            {
                return json(['message'=>'缺少参数product_id'],500);
            }

            $fields = "*";

            $data = (new AliexpressListingHelper())->getLogs($param, $page, $pageSize, $fields);

            return  json($data);

        }catch(Exception $exp){
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");

            return json(['message' => '数据异常','data'=>[]]);
        }
    }
     /**
     * @title 速卖通卖家橱窗设置
     * @url /aliexpress-windows-detail
     * @author joy
     * @method get
     * @param think\Request $request
     * @return type
     */
    public  function windowdetail()
    {
        try{
            
            $id = $this->request->instance()->param('id');
            if(empty($id))
            {
                return json(['message'=>'橱窗id必填'],500);
            }
            
            $response = $this->helper->getWindowDetail($id);
            
            return json($response);
        } catch (Exception $exp){
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }
    
    /**
     * @title 速卖通卖家橱窗设置
     * @url /aliexpress-windows-list
     * @author joy
     * @method get
     * @param think\Request $request
     * @return type
     */
    public  function windowList()
    {
        try{
            $page = $this->request->param('page',1);
        
            $pageSize = $this->request->param('pageSize',100);

            $param = $this->request->instance()->param();

            $response = $this->helper->getWindowList($page,$pageSize,$param);
            
            return json($response);
        } catch (Exception $exp){
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }
   /**
     * @title 速卖通卖家橱窗设置
     * @url /setWindowProducts
     * @author joy
     * @method post
     * @param think\Request $request
     * @return type
     */
    public  function window()
    {
        $data = $this->request->instance()->param('data');
        
        if(empty($data))
        {
            return json(['message'=>'你提交的数据为空'],400);
        }
       
        $res = $this->helper->editWindowData($data,'window',$this->uid);
        
        if($res['result'])
        {
            return json(['message'=>$res['message']]);
        }else{
            return json(['message'=>$res['message']],500);
        }
    }
    /**
     * @title 修改sku库存信息
     * @url /editAeStock
     * @author joy
     * @method post
     * @param think\Request $request
     * @return type
     */
    public  function editStock()
    {
        try{
            $data = $this->request->instance()->param('data');

            $remark = $this->request->instance()->param('remark','');

            $cron_time = $this->request->instance()->param('cron_time',0);

            if(empty($data))
            {
                return json(['message'=>'你提交的数据为空'],400);
            }

            $res = (new AliexpressListingHelper())->editSkuData($data,'stock',$this->uid,$remark,$cron_time);

            if($res['result'])
            {
                return json(['message'=>$res['message']]);
            }else{
                return json(['message'=>$res['message']],400);
            }
        }catch (JsonErrorException $exp){
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }

    }
    
    /**
     * @title 修改sku售价信息
     * @url /editAePrice
     * @author joy
     * @method post
     * @param think\Request $request
     * @return type
     */
    public  function editPrice()
    {
        try{
            $data = $this->request->instance()->param('data');

            $remark= $this->request->instance()->param('remark','');

            $cron_time= $this->request->instance()->param('cron_time',0);

            if(empty($data))
            {
                return json(['message'=>'你提交的数据为空'],400);
            }

            $res = (new AliexpressListingHelper())->editSkuData($data,'price',$this->uid,$remark,$cron_time);

            if($res['result'])
            {
                return json(['message'=>$res['message']]);
            }else{
                return json(['message'=>$res['message']],500);
            }
        }catch (JsonErrorException $exp){
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }
    
    /**
     * @title 修改信息模板
     * @url /editAeTemlate
    * @author joy
     * @method post
     * @param think\Request $request
     * @return type
     */
    public function editTemlate(Request $request)
    {
        try{

            $data = $request->instance()->param('data');

            $remark= $this->request->instance()->param('remark','');

            $cron_time= $this->request->instance()->param('cron_time',0);

            if(empty($data))
            {
                return json(['message'=>'你提交的数据为空'],400);
            }

            $data = is_json($data)?json_decode($data,true):$data;

            $res = (new AliexpressListingHelper())->editMulitProductData($data,'template',$this->uid,$remark,$cron_time);

            if($res['result'])
            {
                return json(['message'=>$res['message']]);
            }else{
                return json(['message'=>$res['message']],500);
            }
        }catch (JsonErrorException $exp){
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};{$exp->getMessage()}");
        }
    }
    
    /**
     * @title 速卖通上架
     * @url /onlineAeProduct
     * @method post
     * @author joy
     * @access public
     */
    public function onlineAeProduct(Request $request)
    {
        try{
            $productIds = $request->instance()->param('productIds') ;

            $cron_time = $request->instance()->param('cron_time',0) ;

            $remark = $request->instance()->param('remark','') ;

            if(empty($productIds))
            {
                return json(['message'=>'请选择你要上架的商品'],400);
            }
            $jobs = explode(';', $productIds);

            if($jobs)
            {
                $ip_address = $request->ip();
                foreach ($jobs as $key => $job)
                {
                    (new AliexpressListingHelper())->onOffLineProductLog($job,$this->uid,'online',$cron_time,$remark, $ip_address);
                    //$this->redis->myZdd('onlineAeProduct',time(),$job);
//                    (new WishQueue(AliexpressOnlineProductQueue::class))->push($job);
//                    $where['product_id']=['eq',$job];
//                    $data['product_status_type']=1;
//                    (new AliexpressListingHelper())->updateProductInfo($data, $where);
                }
            }
            return json(['message'=>'上架成功，稍后执行...']);
        }catch (JsonErrorException $exp){
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};{$exp->getMessage()}");
        }

    }
    
    /**
     * @title 速卖通下架
     * @url /offlineAeProduct
     * @author joy
     * @method post
     * @param think\Request $request
     * @access public
     */
    public function offlineAeProduct (Request $request)
    {
        try{
            $productIds =$request->instance()->param('productIds') ;

            if(empty($productIds))
            {
                return json(['message'=>'请选择你要下架的商品'],400);
            }
            $jobs = explode(';', $productIds);

            if($jobs)
            {
                $productIds = $request->instance()->param('productIds') ;

                $cron_time = $request->instance()->param('cron_time',0) ;

                $remark = $request->instance()->param('remark','') ;

                if(empty($productIds))
                {
                    return json(['message'=>'请选择你要上架的商品'],400);
                }
                $jobs = explode(';', $productIds);

                if($jobs)
                {

                    $ip_address = $request->ip();
                    foreach ($jobs as $key => $job)
                    {
                        (new AliexpressListingHelper())->onOffLineProductLog($job,$this->uid,'offline',$cron_time,$remark, $ip_address);
                        //$this->redis->myZdd('onlineAeProduct',time(),$job);
//                    (new WishQueue(AliexpressOnlineProductQueue::class))->push($job);
//                    $where['product_id']=['eq',$job];
//                    $data['product_status_type']=1;
//                    (new AliexpressListingHelper())->updateProductInfo($data, $where);
                    }
                }
            }
            return json(['message'=>'下架成功，稍后执行...']);
        }catch (JsonErrorException $exp){
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};{$exp->getMessage()}");
        }

    }
    /**
     * @title 修改产品分组
     * @url /editAeGroupId
     * @author joy
     * @method post
     * @param think\Request $request
     * @return json
     */
    public function editGroupId(Request $request)
    {
        try{
            $data = $request->instance()->param('data');

            $remark = $request->instance()->param('remark','');

            $cron_time = $request->instance()->param('cron_time',0);

            if(empty($data))
            {
                return json(['message'=>'你提交的数据为空'],400);
            }

            $res = (new AliexpressListingHelper())->editProductData($data,'GroupId',$this->uid,$remark,$cron_time);
            if($res['result'])
            {
                return json(['message'=>$res['message']]);
            }else{
                return json(['message'=>$res['message']],500);
            }
        }catch (JsonErrorException $exp){
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};{$exp->getMessage()}");
        }

    }
    /**
     * @title 编辑发货期
     * @url /editAeDeliveryTime
     * @author joy
     * @method post
     * @param think\Request $request
     * @return type
     */
    public function editDeliveryTime(Request $request)
    {
        try{
            $data = $request->instance()->param('data');

            $remark = $request->instance()->param('remark','');

            $cron_time = $request->instance()->param('cron_time',0);

            if(empty($data))
            {
                return json(['message'=>'你提交的数据为空'],400);
            }

            $res = (new AliexpressListingHelper())->editProductData($data,'deliveryTime',$this->uid,$remark,$cron_time);
            if($res['result'])
            {
                return json(['message'=>$res['message']]);
            }else{
                return json(['message'=>$res['message']],500);
            }
        }catch (JsonErrorException $exp){
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};{$exp->getMessage()}");
        }

    }
    /**
     * @title 延长商品有效期
     * @url /editAeWsValidNum
     * @author joy
     * @method post
     * @param think\Request $request
     * @return type
     */
    public function editWsValidNum(Request $request)
    {
        try{
            $productIds = $request->instance()->param('productIds') ;

            $remark = $request->instance()->param('remark','');

            $cron_time = $request->instance()->param('cron_time',0);

            if(empty($productIds))
            {
                return json(['message'=>'请选择你要延长商品有效期的商品'],400);
            }
            $jobs = explode(',', $productIds);

            if($jobs)
            {
                foreach ($jobs as $key => $job)
                {
                    //$this->redis->myZdd('renewExpire',time(),$job);
                    //(new WishQueue(AliexpressRenewExpireQueue::class))->push($job);
                    (new AliexpressListingHelper())->onOffLineProductLog($job,$this->uid,'renewExpire',$cron_time,$remark);
                }
            }
            return json(['message'=>'操作成功，稍后执行...']);
        }catch (JsonErrorException $exp){
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};{$exp->getMessage()}");
        }

    }
    
    /**
     * @title 商品标题
     * @url /editAeSubject
    * @author joy
     * @method post
     * @param think\Request $request
     * @return type
     */
    public function editSubject(Request $request)
    {
        try{
            $data = $request->instance()->param('data');

            if(empty($data))
            {
                return json(['message'=>'你提交的数据为空'],400);
            }

            $res = $this->helper->editProductData($data,'subject');
            if($res['result'])
            {
                return json(['message'=>$res['message']]);
            }else{
                return json(['message'=>$res['message']],500);
            }
        }catch (JsonErrorException $exp){
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};{$exp->getMessage()}");
        }

    }
    
    
    /**
     * @title 商品销售单元
     * @url /editAeProductUnit
    * @author joy
     * @method post
     * @param think\Request $request
     * @return type
     */
    public function editProductUnit(Request $request)
    {
        try{
            $data = $request->instance()->param('data');

            if(empty($data))
            {
                return json(['message'=>'你提交的数据为空'],400);
            }

            $res = $this->helper->editProductData($data,'product_unit');
            if($res['result'])
            {
                return json(['message'=>$res['message']]);
            }else{
                return json(['message'=>$res['message']],500);
            }
        }catch (JsonErrorException $exp){
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};{$exp->getMessage()}");
        }

    }
    /**
     * @title 修改产品毛重
     * @url /editAeGrossWeight
     * @access public
    * @author joy
     * @method post
     * @param think\Request $request
     * @return type
     */
    public function editGrossWeight(Request $request)
    {
        $data = $request->instance()->param('data');
        
        if(empty($data))
        {
            return json(['message'=>'你提交的数据为空'],400);
        }
 
        $res = $this->helper->editProductData($data,'gross_weight');
        if($res['result'])
        {
            return json(['message'=>$res['message']]);
        }else{
            return json(['message'=>$res['message']],500);
        }
    }
    /**
     * @title 修改包装尺寸
     * @url /editAePackage
     * @access public
    * @author joy
     * @method post
     * @param think\Request $request
     * @return type
     */
    public function package(Request $request)
    {
        $data = $request->instance()->param('data');
        
        if(empty($data))
        {
            return json(['message'=>'你提交的数据为空'],400);
        }
  
        $res = $this->helper->editProductData($data,'package');
         if($res['result'])
        {
            return json(['message'=>$res['message']]);
        }else{
            return json(['message'=>$res['message']],500);
        }
    }
    /**
     * @title 服务模板设置
     * @url /editAePromiseTemplateId
     * @access public
     * @method post
     * @author joy
     * @param think\Request $request
     * @return type
     */
    public function promiseTemplateId(Request $request)
    {
        $data = $request->instance()->param('data');
        
        if(empty($data))
        {
            return json(['message'=>'你提交的数据为空'],400);
        }
        
        $res = $this->helper->editProductData($data,'promiseTemplateId');
        if($res['result'])
        {
            return json(['message'=>$res['message']]);
        }else{
            return json(['message'=>$res['message']],500);
        }
    }
    /**
     * @title 运费模板设置
     * @url /editAeFreightTemplateId
     * @access public
    * @author joy
     * @method post
     * @param think\Request $request
     * @return type
     */
    public function freightTemplateId(Request $request)
    {
        $data = $request->instance()->param('data');
        
        if(empty($data))
        {
            return json(['message'=>'你提交的数据为空'],400);
        }
 
        $res = $this->helper->editProductData($data,'freightTemplateId');
        if($res['result'])
        {
            return json(['message'=>$res['message']]);
        }else{
            return json(['message'=>$res['message']],500);
        }
    }
    /**
     * @title 商品一口价
     * @url /editAeProductPrice
     * @access public
     * @author joy
     * @method post
     * @param think\Request $request
     * @return type
     */
    public function productPrice(Request $request)
    {
        $data = $request->instance()->param('data');
        
        if(empty($data))
        {
            return json(['message'=>'你提交的数据为空'],400);
        }
        
        $res = $this->helper->editProductData($data,'productPrice');
        if($res['result'])
        {
            return json(['message'=>$res['message']]);
        }else{
            return json(['message'=>$res['message']],500);
        }
    }
    
    /**
     * @title 同步listing
     * @method post
     * @url /rsyncAeProduct
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
        $jobs = explode(';', $product_ids);
        
        if(is_array($jobs) && $jobs)
        {
           $count = 0;
           foreach ($jobs as $key => $job) 
           {
               $res = (new AliexpressListingHelper())->rsyncListing($this->uid,$job);
               if($res)
               {
                   $count = $count + 1;
               }
           }
        }
        return json(['message'=>'同步['.$count.']条listing，稍后将自动执行...']);
    }
    
    /**
     * @title 更新修改了资料的listing
     * @url /rsyncEditAeProduct
     * @access public
    * @author joy
     * @method post
     * @param think\Request $request
     * @return type
     */
    public function rsyncEditAeProduct(Request $request)
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
               if($this->helper->getAeProductUpdateStatus($job,1))
               {
                    $count = $count + 1;
                    //$this->redis->myZdd('editAeProduct',time(),$job);
	               (new WishQueue(AliexpressRsyncEditListingQueue::class))->push($job);
               }
           }
        }
        return json(['message'=>'更新['.$count.']条listing，稍后将自动执行...']);
    }


    /**
     * @title 本地sku搜索平台sku信息
     * @url /listing/aliexpress/sku-code-search
     * @access public
     * @author hao
     * @method post
     * @param think\Request $request
     */
    public function skuCodeSearch(Request $request)
    {
        try{
            $productIds = $request->post('product_ids');
            if(empty($productIds)) {

                return json(['message'=>'未勾选商品ID'],400);
            }

            $skus = $request->post('sku');

            if(empty($skus)) {
                return json(['message' => '未填写本地sku'], 400);
            }

            $result = $this->helper->skuCodeSearch($productIds,$skus);

            if($result['status']) {
                return json($result['data']);
            }

            return json($result['message'],400);
        }catch (JsonErrorException $exp){
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};{$exp->getMessage()}");
        }

    }

}
