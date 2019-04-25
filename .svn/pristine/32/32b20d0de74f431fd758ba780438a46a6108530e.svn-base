<?php
/**
 * Created by NetBeans.
 * User: joy
 * Date: 2017-4-5
 * Time: 上午10:19
 */

namespace app\listing\controller;

use app\common\controller\Base;
use app\common\exception\JsonErrorException;
use app\common\service\UniqueQueuer;
use app\listing\queue\WishRsyncListing;
use app\listing\queue\WishProductDisable;
use app\listing\queue\WishProductEnable;
use app\listing\queue\WishUpdateListing;
use app\listing\queue\WishVariantDisable;
use app\listing\queue\WishVariantEnable;
use think\Request;
use think\Exception;
use app\publish\service\WishHelper;
use app\listing\service\WishListingHelper;
use app\listing\validate\WishListingValidate;
use think\Cache;
use app\common\service\Common;
use app\publish\queue\WishQueue;
use app\listing\service\RedisListing;
/**
 * @module listing系统
 * @title wish listing管理
 * Class Wish
 * @package app\listing\controller
 */

class Wish extends Base{
    private $redis;
    private $helper;
    private $validate;
    private $uid;
    protected function init()
    {
        $request = request();
    	if(is_null($this->redis))
	    {
		    $this->redis = new RedisListing;
	    }

	    if(is_null($this->helper))
	    {
		    $this->helper = new WishListingHelper;
	    }

	    if(is_null($this->validate))
	    {
		    $this->validate = new WishListingValidate;
	    }
        $this->uid=Common::getUserInfo($request) ? Common::getUserInfo($request)['user_id'] : 0;

    }
    /**
     * @title 取消wish express
     * @url /disable-wish-express
     * @author joy
     * @method post
     * @param Request $request
     * @return string
     */
    public function batachDisableWishExpress(Request $request)
    {
        try{
            $product_ids = $request->param('product_ids');
            if(empty($product_ids))
            {
                throw new JsonErrorException("请选中你要设置的商品");
            }
            $response = WishListingHelper::disableWishExpress($product_ids,$this->uid);
            return json($response);
        }catch (Exception $exp){
            throw new JsonErrorException($exp->getMessage());
        }
    }
    /**
     * @title 批量设置wish express数据
     * @url /batch-setting-wish-express
     * @author joy
     * @method post
     * @param Request $request
     * @return string
     */

    public function batchSettingWishExpress(Request $request)
    {
        try{
            $product_ids = $request->instance()->param('product_ids');

            $express = $request->instance()->param('all_country_shipping');

            if(empty($product_ids))
            {
                throw new JsonErrorException("请选中你要设置的商品");
            }

            if(empty($express))
            {
                throw new JsonErrorException("wish express数据能为空");
            }

            $response = WishListingHelper::batchSettingExpress($product_ids,$express,$this->uid);
            return json($response);
        }catch (Exception $exp){
            throw new JsonErrorException($exp->getMessage());
        }
    }

    /**
     * @title 获取wish express数据
     * @url listing/wish/getWishExpressData
     * @author joy
     * @method get
     * @param Request $request
     * @return string
     */

    public function wishExpress()
    {
        try{
            $response = WishListingHelper::wishExpressData();
            return json($response);
        }catch (JsonErrorException $exp){
            throw new JsonErrorException($exp->getMessage());
        }
    }
    /**
     * @title wish在线listing修改日志
     * @url listing/wish/logs
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
                return json(['message'=>'平台ID必需'],500);
            }

            $fields = "*";

            $data = (new WishListingHelper())->getLogs($param, $page, $pageSize, $fields);

            return  json($data);

        }catch(Exception $exp){
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }
    /**
     * @title 更新修改了的资料listing
     * @url listing/wish/rsyncEditListing
     * @author joy
     * @method post
     * @param Request $request
     * @return string
     */
    public function rsyncEditListing(Request $request)
    {
       try{
           $post = $request->instance()->param();

           if(!isset($post['product_id']))
           {
               return json(['message'=>'产品id必填']);
           }

           if(!empty($post['product_id']))
           {
               $queues = explode(',', $post['product_id']);
           }else{
               $queues=[];
           }

           if($queues)
           {
               if(is_array($queues))
               {
                   foreach($queues as  $queue)
                   {
                       try{
                           //如果该商品做了修改则不准更新
                           $product = WishListingHelper::getProductData(['product_id'=>$queue], 'lock_update');
                           if($product['lock_update']==1) //修改了资料
                           {
                               //$redis->score('wishUpdateDataListing',$queue, time());
                               (new WishQueue(WishUpdateListing::class))->push($queue);
                               //$this->redis->myZdd('wishUpdateDataListing',time(),$queue);
                           }
                       }catch(Exception $exp){
                           throw new Exception($exp->getFile().$exp->getLine().$exp->getMessage());
                       }
                   }
                   $message='提交成功，稍后自动执行';
               }
           }else{
               $message='请选择你要更新的商品';
           }
           return json(['message'=>$message]);
       }catch (JsonErrorException $exp){
           throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");

       }

    }
    /**
     * @title 批量编辑获取sku数据
     * @url listing/wish/batchEdit
     * @access public
     * @author joy
     * @method get
     * @param Request $request
     * @return json
     */
    public function batchEdit(Request $request)
    {
        try{
            $id  = $request->instance()->param('id');

            if(empty($id))
            {
                return json(['message'=>'请选择你要批量修改的商品']);
            }

            $where['pid']=['IN',$id];
            $fields='v.vid,v.pid,v.sku,v.main_image,v.color,v.size,v.price,v.shipping,v.shipping_time,v.inventory,v.msrp,v.variant_id,v.product_id,v.cost,a.account_name,a.code';
            $data = WishListingHelper::getProductVariantByPid($where,$fields);
            return json(['data'=>$data]);
        }catch (JsonErrorException $exp){
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }

    }

    /**
     * @title 批量编辑提交
     * @url listing/wish/batchEditAction
     * @author joy
     * @access public
     * @method post
     * @param Request $request
     * @return json
     */
    public function batchEditAction(Request $request)
    {
        try{
            $post  = $request->instance()->param('data');

            $type  = $request->instance()->param('type');

            $cron_time  = $request->instance()->param('cron_time',0);

            $remark  = $request->instance()->param('remark','');

            if(empty($type))
            {
                return json(['message'=>'要修改的字段必填']);
            }

            if(empty($post))
            {
                return json(['message'=>'提交数据为空，请核对']);
            }else{
                $data = json_decode($post,true);
            }

            $validate =  new WishListingValidate();

            if($error=$validate->checkBatchEdit($data,$type))
            {
                return json($error);
            }
            $helper = new WishListingHelper;

            $data['uid']=$this->uid;


            $res = $helper->batchEditSingleField($data, $type,$cron_time,$remark);

            if($res)
            {
                $message = $res;
                $code=400;
            }else{
                $message='修改成功';
                $code=200;
            }

            return json(['message'=>$message],$code);
        }catch (JsonErrorException $exp){
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }
    /**
     * @title 同步listing
     * @url listing/wish/rsyncListing
     * @author joy
     * @method post
     * @param Request $request
     * @return json
     */
    public function rsyncListing(Request $request)
    {
        try{
            $post = $request->instance()->param();

            $validate = new WishListingValidate();
            if($error = $validate->checkRsyncListing($post))
            {
                return json($error);
            }
            $queues =  explode(',', $post['queue']);
            $total=0;
            if($queues)
            {
                if(is_array($queues))
                {
                    foreach($queues as  $queue)
                    {
                        if(false !== $this->helper->updateProductLockStatus(['product_id'=>$queue]))
                        {
                            (new UniqueQueuer(WishRsyncListing::class))->push($queue);
                            ++$total;
                        }
                    }
                    $message='成功加入队列['.$total.']条';
                }
            }

            return json(['message'=>$message]);

        }catch (JsonErrorException $exp){
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }

    }


    /**
     * @title 编辑指定国家的运费
     * @url listing/wish/updateShipping
     * @access public
     * @author joy
     * @method post
     * @param Request $request
     * @return type
     */
    public function updateShipping(Request $request)
    {
        try{
            $param = $request->param();

            $validate = new WishListingValidate();

            if($message = $validate->checkShipping($param))
            {
                return json_error($message);
            }

            //$uid = $request->param('uid');

            $uid =Common::getUserInfo($request) ? Common::getUserInfo($request)['user_id'] : 0;

            $product_id = $request->param('product_id');

            $url = 'https://china-merchant.wish.com/api/v2/product/update-shipping';

            $helper =new WishListingHelper;

            $data = $helper->createUpdateShippingData($param, $url);
            $where=[
                'uid'=>$uid,
                'product_id'=>$product_id,
                'name'=>$data['name'],
                'action'=>$data['action'],
                'code'=>0,
            ];

            $res = $helper->wishActionLog($data, $where);
            if($res)
            {
                $message=$data['action'].'成功';
            }else{
                $message='设置失败';
            }
            return json($message);
        }catch (JsonErrorException $exp){
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }

    }

    /**
     * @title 编辑产品的所有的国家航运价格
     * @url listing/wish/updateMultiShipping
     * @access public
     * @author joy
     * @method post
     * @param Request $request
     * @return type
     */

    public function updateMultiShipping(Request $request)
    {
        try{
            $product_id = $request->param('product_id');

            //$uid = $request->param('uid');

            $uid=Common::getUserInfo($request) ? Common::getUserInfo($request)['user_id'] : 0;

            $post['uid']=$uid;

            $all_country_shipping = $request->param('all_country_shipping');

            if(empty($product_id) )
            {
                return json(['message'=>'产品id不能为空'],400);
            }

            if(empty($uid))
            {
                return json(['message'=>'用户uid不能为空'],400);
            }

            if(empty($product_id))
            {
                return json(['message'=>'产品id不能为空'],400);
            }

            if(empty($all_country_shipping))
            {
                return json(['message'=>'wish express设置数据不能为空'],400);
            }
            $message = WishListingHelper::addMultiShipping($product_id, $uid, $all_country_shipping);
            if($message['result'])
            {
                return json($message);
            }else{
                return json($message,400);
            }
        }catch (JsonErrorException $exp){
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }

    }

    /**
     * @title 编辑产品的所有的国家航运价格
     * @url listing/wish/updateMultiShippingRightNow
     * @access public
     * @author joy
     * @param Request $request
     * @method post
     * @return type
     */

    public function updateMultiShippingRightNow(Request $request)
    {
        try{
            $product_id = $request->param('product_id');

            //$uid = $request->param('uid');

            $uid=Common::getUserInfo($request) ? Common::getUserInfo($request)['user_id'] : 0;

            $all_country_shipping = $request->param('all_country_shipping');

            if(empty($product_id))
            {
                return json(['message'=>'产品id不能为空'],400);
            }

            if(empty($uid))
            {
                return json(['message'=>'用户uid不能为空'],400);
            }

            if(empty($product_id))
            {
                return json(['message'=>'产品id不能为空'],400);
            }

            if(empty($all_country_shipping))
            {
                return json(['message'=>'wish express设置数据不能为空'],400);
            }
            $message = WishListingHelper::addMultiShipping($product_id, $uid, $all_country_shipping);
            if (!$message['result']) //这个会检测设置的价格是否大于20%-pan
            {
                return json($message,400);
            }

            $WishListingHelper = new WishListingHelper;
            $result = $WishListingHelper->updateMultiShipping($product_id, $uid);
            if($result['result'])
            {
                return json($result);
            }else{
                return json($result,400);
            }
        }catch (JsonErrorException $exp){
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }


    }
    /**
     * @title 获取wish  express
     * @url listing/wish/getShipping
     * @param Request $request
     * @method get
     * @author joy
     * @return json
     */
    public function getShipping(Request $request)
    {
        try{
            $product_id = $request->param('product_id');

            if(empty($product_id))
            {
                return json(['message'=>'产品id不能为空'],400);
            }
            $response = WishListingHelper::getShippingByProductId($product_id);
            $productInfo = WishListingHelper::getProductData(['product_id'=>$product_id], 'name,main_image');
            $data['product'] = $productInfo;
            $data['all_country_shipping']=$response;

            return json(['data'=>$data]);
        }catch (JsonErrorException $exp){
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }

    }
    /**
     * @title 更新wish在线listing数据
     * @url listing/wish/updateListing
      * @author joy
     * @method post
     * @access public
     * @param Request $request
     * @return \think\response\Json
     */
    public  function updateListing(Request $request)
    {
        try{

            //获取post过来的数据
            $post =$request->param();

            $wishListingHelper = new WishListingHelper();

            $error = $wishListingHelper->validateUpdate($post);

            if($error)
            {
                return json(['message'=>$error],500);
            }

            if(isset($post['product_id']) && !empty($post['product_id']) && $post['product_id']!='null')
            {
                $product_id = @$post['product_id'];
            }else{
                $product_id ='';
            }

            //$uid = $post['uid'];

            $uid=Common::getUserInfo($request) ? Common::getUserInfo($request)['user_id'] : 0;

            $post['uid'] = $uid ;

            if(isset($post['id']) && $post['id'])
            {
                $id=$post['id'];
            }

            //如果该商品还未刊登，则更新数据库，如果已经刊登过，则缓存到文件中再更新

            if($wishListingHelper->getVariantPublishStatus($id)) //未刊登
            {
                $wishHelper = new WishHelper;
                $res = $wishHelper->updateData($post);
            }else{
                $res = $wishListingHelper->editPublishedData($post);
            }
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
     * @title 更新已刊登listing数据
     * @url listing/wish/updatePublishedListing
     * @access public
     *  @author joy
     * @method post
     * @param Request $request
     * @return \think\response\Json
     */
    public  function updatePublishedListing(Request $request)
    {
        //获取post过来的数据
        $post =$request->instance()->param();

        $helper = new WishListingHelper();

        $error = $helper->validateUpdate($post);

        if($error)
        {
            return json(['message'=>$error],400);
        }

        if(isset($post['product_id']) && !empty($post['product_id']))
        {
            $product_id = @$post['product_id'];
        }else{
            $product_id ='';
        }

        //$uid = $post['uid'];

        $uid = Common::getUserInfo($request) ? Common::getUserInfo($request)['user_id'] : 0;

        $post['uid'] = $uid ;

        if(isset($post['id']) && $post['id'])
        {
            $id=@$post['id'];
        }

        //如果该商品还未刊登，则更新数据库，如果已经刊登过，则缓存到文件中再更新

        if(empty($product_id)) //未刊登
        {   $wishHelper = new WishHelper;
            $message = $wishHelper->updateData($post);
        }else{
            try {

                //与数据表中的数据比较，记录已经修改的数据，没有修改的数据不进行更新
                $postProductData=[]; //更新post过来的数据
                $postSkuData=[];

                $vars = json_decode($post['vars'], true)[0];
                $postSkuData=$vars['variant'];
                $postProductData=[
                    'name'=>$vars['name'],
                    'main_image'=>$vars['images'][0],
                    'description'=>$vars['description'],
                    'tags'=>$vars['tags'],
                    'brand'=>$post['brand'],
                    'upc'=>$post['upc'],
                    'landing_page_url'=>$post['landing_page_url'],
                    'extra_images'=> array_shift($vars['images']),
                    'warehouse'=>$post['warehouse'],
                ];

                $productFields = array_keys($postProductData);

                //获取数据库product数据
                $dbProductData = $helper->getProductData(['id'=>$id],$productFields);

                //将更新的数据与数据库的数据比较

                $product_diff = array_diff($postProductData, $dbProductData);
                //比较修改的sku与数据库中的sku的差异
                $sku_diff = $helper->variantArrayDiff($postSkuData);

                if($product_diff || $sku_diff)
                {
                    $updateData=[];
                    $updateData['id']=$id; //商品信息
                    $updateData['account_name']=$post['account_name']; //商品信息

                    if(!empty($product_diff)) //如果更改了商品数据
                    {
                        $product_diff['id']=$post['product_id'];
                        $product_diff['parent_sku']=$post['parent_sku'];
                        $updateData['product'] = $product_diff;
                        //生成操作日志
                        $helper->createUpdateLog($updateData['product'], 'product', $uid);
                    }

                    if(!empty($sku_diff)) //如果更改了sku数据
                    {
                        $updateData['vars']=$sku_diff; //sku信息

                        //生成操作日志
                        foreach($updateData['vars'] as $variant)
                        {

                            $helper->createUpdateLog($variant, 'variant', $uid);
                        }

                    }

                    $options['type']='file';

                    Cache::connect($options);

                    $fileCacheResponse =Cache::set('wishUpdateListingProductId:'.$id,$updateData,0);//将要修改的数据写入文件缓存

                    //$redis = Cache::store('redis')->handler();

                    //$redis->ZADD('wishUpdateListing',time(),$id);//将要修改的商品id写入redis缓存

                    //$redis = myCache::handler(false); 

                    //$redis->score('wishUpdateListing',$id,time());

                    //$this->redis->myZdd('wishUpdateListing',time(),$id);
	                (new WishQueue(WishUpdateListing::class))->push($id);
                    if($fileCacheResponse)
                    {
                        $message='更新成功，稍后自动执行....';
                    }else{
                       $message='写入缓存失败....';
                    }
                }else{
                    $message='数据没有做任何修改，不能更新...';
                }

            } catch (Exception $exc) {
               $message= $exc->getTraceAsString();
            }
        }
        return json(['message'=>$message]);
    }

    /**
     * @title wish刊登模块查看功能
     * @url listing/wish/view
     * @access public
     * @author joy
     * @method get
     * @param Request $request
     * @return json
     */
    public function view(Request $request)
    {

        $get = $request->instance()->param();

        if(!isset($get['id']))
        {
            return json(['message'=>'产品id必须']);
        }else{
            $id = $get['id'];
            $where['id']=['eq',$id];
        }

        $data = WishListingHelper::getProductVariant($where,$id);

        return json($data);
    }

    /**
     * @title wish刊登模块编辑功能
     * @url listing/wish/edit
     * @access public
     * @author joy
     * @method get
     * @param Request $request
     * @return json
     */
    public function edit(Request $request)
    {
        $get = $request->instance()->param();

        if(!isset($get['id']))
        {
            return json(['message'=>'产品id必须']);
        }else{
            $id = $get['id'];
            $where['id']=['eq',$id];
        }

        $data = WishListingHelper::getProductVariant($where,$id);

        return json($data);
    }

    /**
     * @title wish刊登模块复制功能
     * @url listing/wish/copy
     * @access public
     * @author joy
     * @method get
     * @param Request $request
     * @return json
     */
    public function copy(Request $request)
    {
        $get = $request->instance()->param();

        if(!isset($get['id']))
        {
            return json(['message'=>'产品id必须']);
        }else{
            $id = $get['id'];
            $where['id']=['eq',$id];
        }

        $help = new WishHelper();

        $data = WishListingHelper::getProductVariant($where,$id);

        return json($data);
    }

    /**
     * @title 补货
     * @url listing/wish/buhuo
     * @access public
     * @author joy
     * @method post
     * @param Request $request
     * @return string
     */

    public function buhuo(Request $request)
    {
        $post = $request->instance()->param();

        $validate = new WishListingValidate();

        if($message = $validate->checkBuhuo($post))
        {
            return json_error($message);
        }

        $message = WishListingHelper::addBuhuo($post);

        return json(['message'=>$message]);

    }

    /**
     * @title 批量上架
     * @url listing/wish/batchEnable
     * @access public
     * @author joy
     * @method post
     * @param Request $request
     * @return json
     */

    public function batchEnable(Request $request)
    {
        $post = $request->instance()->param();

        $cron_time = $request->instance()->param('cron_time',0);

        $remark = $request->instance()->param('remark','');

        if(!isset($post['product_id']) || empty($post['product_id']))
        {
            return json_error('产品id必填');
        }else{
            $pids = explode(',', $post['product_id']);
        }

        //$redis = myCache::handler(true);
        $total=0;
        if(is_array($pids))
        {
            foreach ($pids as $pid)
            {
                 //$this->redis->mySadd('wishBatchEnable',$pid);
	             //(new WishQueue(WishProductEnable::class))->push($pid);
                 //$this->helper->updateVariantOnlinStatus($pid, 'Enabled');
                if((new WishListingHelper())->disableInableAction($pid,'enableProduct',$this->uid,'上架',$cron_time,$remark))
                {
                    ++$total;
                }
            }
        }
        return json(['message'=>'批量上架成功['.$total.']条，稍等将自动执行！']);
        //dump($redis->smembers('wishBatchEnable'));
    }

    /**
     * @title 批量下架
     * @url listing/wish/batchDisable
     * @access public
     * @author joy
     * @method post
     * @param Request $request
     * @return json
     */

    public function batchDisable(Request $request)
    {
        $post = $request->instance()->param();

        $cron_time = $request->instance()->param('cron_time',0);

        $remark = $request->instance()->param('remark','');

        if(!isset($post['product_id']) || empty($post['product_id']))
        {
            return json(['message'=>'产品id必填'],500);
        }else{
            $pids = explode(',', $post['product_id']);
        }

        //$redis = myCache::handler(true);              
        $total=0;
        if(is_array($pids))
        {
            foreach ($pids as $pid)
            {

                if((new WishListingHelper())->disableInableAction($pid,'disableProduct',$this->uid,'下架',$cron_time,$remark))
                {
                    ++$total;
                }
                 //$this->redis->mySadd('wishBatchDisable',$pid);
	            //(new WishQueue(WishProductDisable::class))->push($pid);
                 //$this->helper->updateVariantOnlinStatus($pid, 'Disabled');
            }
        }

         return json(['message'=>'批量下架成功['.$total.']条，稍等将自动执行！']);

    }

    /**
     *  @title 在线产品上架
     *  @url listing/wish/enable
     *  @access public
     * @author joy
     * @method post
     *  @param  Request $request
     *  @return json
     */

    public function enable(Request $request)
    {
        $post = $request->instance()->param();

        $where = array();

        if(isset($post['product_id']))
        {
            $product_id = $post['product_id'];
            $where['product_id'] = array('eq',$product_id);
        }

        if(isset ($post['parent_sku']))
        {
            $parent_sku = $post['parent_sku'];
            $where['parent_sku'] = array('eq',$parent_sku);
        }

        if(empty($product_id) && empty($parent_sku))
        {
            return json(['message'=>'产品id或spu必须填写一个'],400);
        }
        $helper = new WishHelper;

        $goods = $helper->hasOne($where);

        if($goods)
        {
            $data['id']  = $goods['product_id'];
            $data['parent_sku']  = $goods['parent_sku'];
            $data['access_token']  = $goods->account->access_token;

            $url="https://china-merchant.wish.com/api/v2/product/enable";

            $response = json_decode(curl_do($url,$data),true);

            if($response['code']==0)
            {
            	//$this->helper->updateProductEnabled($where,['']);
                $response['message'] = '上架成功';
            }else{
                $response['message'] = '上架失败,原因:'.$response['message'];
            }
        }else{
            $response['message']='商品不存在,无法上架';
        }

        return json($response);
    }

    /**
     *  @title 在线产品下架
     *  @url listing/wish/disable
     *  @access public
     * @method post
     *  @param  Request $request
     *  @return json
     */

    public function disable(Request $request)
    {
        $post = $request->instance()->param();

        $where = array();

        if(isset($post['product_id']))
        {
            $product_id = $post['product_id'];
            $where['product_id'] = array('eq',$product_id);
        }

        if(isset ($post['parent_sku']))
        {
            $parent_sku = $post['parent_sku'];
            $where['parent_sku'] = array('eq',$parent_sku);
        }

        if(empty($product_id) && empty($parent_sku))
        {
            return json(['message'=>'产品id或spu必须填写一个'],400);
        }
        $helper = new WishHelper;

        $goods = $helper->hasOne($where);

        if($goods)
        {
            $data['id']  = $goods['product_id'];
            $data['parent_sku']  = $goods['parent_sku'];
            $data['access_token']  = $goods->account->access_token;

            $url="https://china-merchant.wish.com/api/v2/product/disable";

            $response = json_decode(curl_do($url,$data),true);

            if($response['code']==0)
            {
                $response['message'] = '下架成功';
            }else{
                $response['message'] = '下架失败,原因:'.$response['message'];
            }
        }else{
            $response['message']='商品不存在,无法下架';
        }

        return json($response);
    }

    /**
     * @title sku下架
     * @url listing/wish/disableVariant
     * @access public
     * @method post
     * @param Request $request
     * @return json
     */
    public function  disableVariant(Request $request)
    {
        $post = $request->instance()->param();

        $cron_time = $request->instance()->param('cron_time',0);

        $remark = $request->instance()->param('remark','');

        if(isset($post['sku']) && $post['sku'])
        {
            $variant_id=$post['sku'];
            $response = (new WishListingHelper())->variantOnOff($variant_id,'disableVariant',$this->uid,$cron_time,$remark);
            if($response['result'])
            {
                return json($response);
            }else{
                return json($response,400);
            }
        }else{
            return json(['message'=>'上架sku必须填写sku'],400);
        }

//        $helper = new WishListingHelper();
//
//        $variant = $helper->getOneVariant($where,['product']);
//
//
//        if($variant)
//        {
//            $data['sku']  = $variant->sku;
//
//            $accountid  = $variant->product->accountid;
//
//            $accountInfo = $helper->getAccount(['id'=>$accountid]);
//
//            if($accountInfo)
//            {
//                $data['access_token']  = $accountInfo['access_token'];
//            }else{
//                return json(['message'=>'账号信息不存在']);
//            }
//
//            $url="https://china-merchant.wish.com/api/v2/variant/disable";
//
//            $response = json_decode(curl_do($url,$data),true);
//
//            if($response['code']==0)
//            {
//	            $this->helper->updateVariantEnabled($where,['enabled'=>'Disabled']);
//                $response['message'] = 'sku下架成功';
//            }else{
//                $response['message'] = 'sku下架失败,原因:'.$response['message'];
//            }
//        }else{
//            $response['message']='sku不存在,无法下架';
//        }
//
//        return json($response);
    }


     /**
     * @title skus上架
     * @url listing/wish/enableVariant
     * @access public
      * @method post
     * @param Request $request
     * @return json
     */
    public function  enableVariant(Request $request)
    {
        $post = $request->instance()->param();

        $cron_time = $request->instance()->param('cron_time',0);

        $remark = $request->instance()->param('remark','');

        if(isset($post['sku']) && $post['sku'])
        {
            $variant_id=$post['sku'];
            $response = (new WishListingHelper())->variantOnOff($variant_id,'enableVariant',$this->uid,$cron_time,$remark);
            if($response['result'])
            {
                return json($response);
            }else{
                return json($response,400);
            }
        }else{
            return json(['message'=>'上架sku必须填写sku'],400);
        }

//        $helper = new WishListingHelper();
//
//        $variant = $helper->getOneVariant($where,['product']);
//
//        if($variant)
//        {
//           $data['sku']  = $variant->sku;
//
//
//            $accountid  = $variant->product->accountid;
//
//            $accountInfo = $helper->getAccount(['id'=>$accountid]);
//
//            if($accountInfo)
//            {
//                $data['access_token']  = $accountInfo['access_token'];
//            }else{
//                return json(['message'=>'账号信息不存在'],400);
//            }
//
//            $url="https://china-merchant.wish.com/api/v2/variant/disable";
//
//            $response = json_decode(curl_do($url,$data),true);
//
//            if($response['code']==0)
//            {
//            	$this->helper->updateVariantEnabled($where,['enabled'=>'Enabled']);
//                $response['message'] = 'sku上架成功';
//            }else{
//                $response['message'] = 'sku上架失败,原因:'.$response['message'];
//            }
//        }else{
//            $response['message']='sku不存在,无法上架';
//        }
//
//        return json($response);
    }

    /**
     * @title 批量上架sku
     * @url /listing/wish/batchEnableVariant
     * @access public
     * @method post
     * @param Request $request
     * @return json
     */

    public function batchEnableVariant(Request $request)
    {
        try{
            $post = $request->instance()->param();

            if(!isset($post['variants']) || empty($post['variants']))
            {
                return json(['message'=>'变体variant_id必填'],400);
            }else{
                $variants = json_decode($post['variants']);
            }

            $service = new WishListingHelper();
            $num=0;
            if(is_array($variants))
            {
                foreach ($variants as $variant)
                {
                    $response = $service->variantOnOff($variant,'enableVariant',$this->uid);
                    if(isset($response['result']) && $response['result'])
                    {
                        ++$num;
                    }
                }
            }
            return json(['message'=>'批量上架成功['.$num.']条sku，稍等将自动执行！']);
        }catch (Exception $exp){
            throw new JsonErrorException("{$exp->getFile()};{$exp->getLine()};{$exp->getMessage()}");
        }

    }

    /**
     * @title 批量下架sku
     * @url /listing/wish/batchDisableVariant
     * @access public
     * @method post
     * @param Request $request
     * @return json
     */

    public function batchDisableVariant(Request $request)
    {

        try{
            $post = $request->instance()->param();

            if(!isset($post['variants']) || empty($post['variants']))
            {
                return json(['message'=>'变体variant_id必填'],400);
            }else{
                $variants = json_decode($post['variants']);
            }

            $service = new WishListingHelper();
            $num=0;
            if(is_array($variants))
            {
                foreach ($variants as $variant)
                {
                    $response = $service->variantOnOff($variant,'disableVariant',$this->uid);
                    if(isset($response['result']) && $response['result'])
                    {
                        ++$num;
                    }
                }
            }
            return json(['message'=>'批量下架成功['.$num.']条sku，稍等将自动执行！']);
        }catch (Exception $exp){
            throw new JsonErrorException("{$exp->getFile()};{$exp->getLine()};{$exp->getMessage()}");
        }
    }


    /**
     * @title 批量同步listing,不走队列
     * @url /listing/wish/rsyncNowListing
     * @access public
     * @method post
     * @param Request $request
     * @author pan
     * @return json
     */

    public function rsyncNowListing(Request $request)
    {
        try{
            $post = $request->instance()->param();

            $validate = new WishListingValidate();
            if($error = $validate->checkRsyncListing($post))
            {
                return json($error);
            }
            $queues =  explode(',', $post['queue']);
            $total=0;
            if($queues)
            {
                if(is_array($queues))
                {
                    foreach($queues as  $queue)
                    {
                        if(false !== $this->helper->updateProductLockStatus(['product_id'=>$queue]))
                        {
                            $task = new WishRsyncListing($queue);
                            $task->beforeExec();
                            $task->execute();
                            $task->afterExec();
                            ++$total;
                        }
                    }
                    $message='同步成功['.$total.']条';
                }
            }

            return json(['message'=>$message]);

        }catch (JsonErrorException $exp){
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }

    }

}
