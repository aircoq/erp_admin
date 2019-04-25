<?php
/**
 * Created by NetBeans.
 * User: joy
 * Date: 2017-3-15
 * Time: 上午11:19
 */

namespace app\publish\controller;

use app\common\exception\JsonErrorException;
use app\common\model\GoodsTortDescription;
use app\common\model\wish\WishAccount;
use app\common\model\wish\WishColor;
use app\common\model\wish\WishWaitUploadProductVariant;
use app\publish\service\ExpressHelper;
use app\publish\service\GoodsImage;
use app\publish\service\YixuanpinService;
use think\Debug;
use think\Request;
use think\Response;
use think\Cache;
use think\Exception;
use app\publish\service\WishHelper;
use app\common\controller\Base;
use app\listing\service\WishListingHelper;
use app\publish\service\GoodsHelp;
use app\publish\service\GoodsImage as GoodsImageService;
use app\publish\validate\UploadImageValidate;
use app\publish\validate\WishAddMany;
use app\common\service\Common;
use app\common\cache\Cache as DCache;

/**
 * @module 刊登系统
 * @title wish刊登控制器
 * Class wish
 * packing app\publish\controller
 */
class Wish extends Base
{
    /**
     * @title wish部门所有员工
     * @url publish/wish/wishUsers
     * @method get
     * @author joy
     * @access public
     * @return json
     */
    public function wishUsers()
    {
        try{
            $response = (new WishHelper())->getWishUsers();
            return json($response);
        }catch (JsonErrorException $exp){
            throw new JsonErrorException($exp->getMessage());
        }
    }
    /**
     * @title 删除草稿箱
     * @url publish/wish/deleteDraft
     * @method post
     * @author joy
     * @access public
     * @return json
     */
    public function deleteDraft(Request $request)
    {
        try {

            $param = $request->param('id');

            if (empty($param)) {
                return json(['messae' => '请选择...'], 400);
            }

            $uid = Common::getUserInfo($request) ? Common::getUserInfo($request)['user_id'] : 0;

            $ids = explode(';', $param);
            $where['id'] = ['IN', $ids];
            $service = new WishHelper();
            $response = $service->deleteDraft($where,$uid);
            if (is_numeric($response) && $response>0)
            {
                return json(['message' => '删除成功['.$response.']条']);
            } else {
                return json(['message' => '删除失败'], 400);
            }

        } catch (Exception $exp) {
            throw new JsonErrorException($exp->getFile() . $exp->getLine() . $exp->getMessage());
        }
    }

    /**
     * @title 草稿箱列表
     * @url publish/wish/draft
     * @method get
     * @author joy
     * @access public
     * @return json
     */
    public function draft(Request $request)
    {
        try {
            $param = $request->param();
            $page = $request->param('page', 1);
            $pageSize = $request->param('pageSize', 50);
            $uid = Common::getUserInfo($request) ? Common::getUserInfo($request)['user_id'] : 0;
            $response = (new WishHelper())->listDraft($param, $page, $pageSize, $uid);
            return json($response);
        } catch (JsonErrorException $exp) {
            throw new JsonErrorException($exp->getFile() . $exp->getLine() . $exp->getMessage());
        }

    }

    /**
     * @title 加入待刊登序列
     * @url publish/wish/pushQueue
     * @method post
     * @author joy
     * @access public
     * @return json
     */
    public function pushQueue()
    {
        $ids = request()->instance()->param('ids');
        if (empty($ids)) {
            return json(['message' => '请选择你要加入待刊登序列的数据'], 500);
        }
        $pids = explode(';', $ids);
        $num = 0;
        if (is_array($pids)) {
            foreach ($pids as $pid) {
                if ($rs = (new WishWaitUploadProductVariant())->where(['pid' => $pid, 'status' => 2])->setField('status', 0)) {
                    $num = $num + $rs;
                }
            }
        }
        return json(['message' => '成功加入[' . $num . ']条数据到待刊登序列']);
    }

    /**
     * @title 统计
     * @url publish/wish/stat
     * @method get
     * @author joy
     * @access public
     * @return json
     */
    public function stat(Request $request)
    {
        $interface = new \app\publish\interfaces\WishStat;
        $data = [
            'notPublish' => $interface->getNotyetPublish(),
            'publishing' => $interface->getListingIn(),
            'expListing' => $interface->getExceptionListing(),
            'stopSellListing' => $interface->getStopSellWaitRelisting(),
        ];
        return json($data);
    }

    /**
     * @title wish所有颜色值
     * @url publish/wish/colors
     * @method get
     * @author joy
     * @access public
     * @return json
     */
    public function colors(Request $request)
    {
        $helper = new WishHelper;
        $colors = $helper->wishColors();
        return json(['data' => $colors]);
    }

    /**
     * @title 验证颜色值是否合法
     * @url publish/wish/validateColor
     * @access public
     * @method post
     * @author joy
     * @param Request $request
     * @return type
     */
    public function validateColor(Request $request)
    {
        $color = $request->instance()->param('color');

        if (empty($color)) {
            return json(['message' => FALSE]);
        }
        $result = (new WishColor())->whereLike('color_value', $color)->find();
        if ($result) {
            return json(['message' => true]);
        } else {
            return json(['message' => FALSE]);
        }
    }

    /**
     * @title 验证size值是否合法
     * @url publish/wish/validateSize
     * @method post
     * @author joy
     * @access public
     * @param Request $request
     * @return type
     */
    public function validateSize(Request $request)
    {
        $size = $request->instance()->param('size');

        if (empty($size)) {
            return json(['message' => false]);
        } elseif (strlen($size) > 50) {
            return json(['message' => false]);
        }

        if (preg_match('/^[a-zA-Z0-9][\ ]*([a-zA-Z0-9.\-&\'\"\(\)\[\]\/][\ ]*)*$/is', $size)) //数字字母组合，长度为1~50
        {
            $res = true;
        } else {
            $res = false;
        }
        return json(['message' => $res]);
    }
    /**
     * @title 从产品库刊登保存草稿
     * @url publish/wish/saveMany
     * @access public
     * @method post
     * @apiRelate app\goods\controller\Goods::goodsToSpu
     * @author Joy <joy_qhs@163.com>
     * @param Request $request
     * @return string
     */
    public function saveMany(Request $request)
    {
        try{
            $post = $request->param();

            $sku = json_decode($post['sku'], true);

            $post['sku'] = $sku;

            $uid = Common::getUserInfo($request) ? Common::getUserInfo($request)['user_id'] : 0;

            $response = (new WishHelper())->saveManyDraft($post,$uid);
            return json($response);
        }catch (Exception $exp){
            throw new JsonErrorException($exp->getMessage());
        }



    }

    /**
     * @title 从产品库刊登多个商品
     * @url publish/wish/addMany
     * @access public
     * @method post
     * @apiRelate app\goods\controller\Goods::goodsToSpu
     * @author Joy <joy_qhs@163.com>
     * @param Request $request
     * @return string
     */
    public function addMany(Request $request)
    {

        $post = $request->instance()->post();

        $sku = json_decode($post['sku'], true);

        $post['sku'] = $sku;

        $uid = Common::getUserInfo($request) ? Common::getUserInfo($request)['user_id'] : 0;

        $post['uid'] = $uid;


        $validate = new WishAddMany();
        if ($error = $validate->checkData($post)) {
            return json(['message' => $error], 500);
        }

        $help = new WishHelper();

        $num = $help->insertManyData($post);

        if (is_int($num) && $num > 0) {
            /*$cron_time =strtotime($post['cron_time']);
            if($cron_time>0 && $cron_time>= time()) //定时刊登且刊登时间比当前时间大
            {
                return json(['message'=>'成功提交【'.$num.'】条listing,稍后刊登到平台']);  
            }else{ //保存并刊登到平台
                $vars = $post['sku'];     
                $account_id  = $post['account_id'];
                if(is_array($vars))
                {
                    $message = $help->startRsynAddManyProduct($vars, $account_id);
                    if($message)
                    {
                       return json(['message'=>$message]);
                    }  
                }
            } */
            return json(['message' => '成功提交【' . $num . '】条listing,稍后刊登到平台']);
        } else {
            return json(['message' => $num], 500);
        }
    }

    /**
     * @title 删除
     * @url publish/wish/del
     * @method post
     * @author Joy <joy_qhs@163.com>
     * @access public
     *
     */
    public function del(Request $request)
    {
        try {
            $post = $request->instance()->param();

            if (!isset($post['id'])) {
                return json(['message' => '商品id必需']);
            }

            if (!empty($post['id'])) {
                $ids = explode(',', $post['id']);
            } else {
                $ids = [];
            }

            if ($ids) {
                $len = 0;
                foreach ($ids as $id) {
                    if ((new WishHelper())->delete($id)) {
                        ++$len;
                    }
                }
                if ($len > 0) {
                    $message = '成功删除[' . $len . ']条记录';
                } else {
                    $message = '删除失败';
                }
            } else {
                $message = '请选择你要删除的商品';
            }
            if ($len > 0) {
                return json(['message' => $message]);
            } else {
                return json(['message' => $message], 400);
            }
        } catch (JsonErrorException $exp) {
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }

    }

    /**
     * @title 获取wish在线tags
     * @url publish/wish/getWishOnlineTags
     * @method get
     * @author Joy <joy_qhs@163.com>
     * @param Request $request
     * @return json
     */

    public function getWishOnlineTags(Request $request)
    {
        $q = $request->param('q');
        $l = $request->param('l', 1);

        try {
            $response = (new YixuanpinService())->getTags($q, $l);
            return json($response);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * @title 获取wish size设置
     * @url publish/wish/getWishSize
     * @access public
     * @method get
     * @author Joy <joy_qhs@163.com>
     * @return json
     */
    public function getWishSize(Request $request)
    {
        //获取wish尺寸设置
        $status = $request->instance()->param('status');

        $wishHelp = new WishHelper();
        $wishSize = $wishHelp->getWishSize($status);
        return json(['data' => $wishSize]);
    }

    /**
     * @title 上传网络图片
     * @url publish/wish/createNetImage
     * @param Request $request
     * @method post
     * @author joy
     * @return array
     */
    public function createNetImage(Request $request)
    {
        $post = $request->instance()->param();

        $Validate = new UploadImageValidate();

        $error = $Validate->checkNetImages($post);

        if ($error) {
            return json(['message' => $error]);
        }

        $goods_id = $post['id'];
        $images = explode('|', $post['images']);


        if (empty($images)) {
            return json(['message' => '缺少图片链接地址'], 400);
        }
        if (empty($images)) {
            return json(['message' => '缺少图片详情'], 400);
        }
        try {

            $service = new GoodsImageService();
            $data = $service->saveNetImages($goods_id, $images);
            return json(['message' => '上传成功', 'data' => $data], 200);
        } catch (Exception $ex) {
            return json(['message' => '上传失败' . $ex->getMessage()], 400);
        }


    }

    /**
     * @title 上传图片
     * @url publish/wish/uploadImages
     * @param Request $request
     * @method post
     * @author joy
     * @return array
     */
    public function uploadImages(Request $request)
    {

        $goods_id = $request->param('id');

        $images = json_decode($request->param('images'), true);

        if (empty($images)) {
            return json(['message' => '缺少图片详情'], 400);
        }
        try {

            $service = new GoodsImageService();
            $data = $service->save($goods_id, $images);
            return json(['message' => '上传成功', 'data' => $data], 200);
        } catch (Exception $ex) {
            return json(['message' => '上传失败' . $ex->getMessage()], 400);
        }
    }

    /**
     * @title 获取商品相册
     * @url publish/wish/gallery
     * @access public
     * @method get
     * @author joy
     * @param Request $request
     * @return json
     */
    public function gallery(Request $request)
    {
        $post = $request->param();
        if (!isset($post['goods_id'])) {
            return json(['message' => '商品id必填']);
        }

        if (is_numeric($post['goods_id']) && $post['goods_id'] <= 0) {
            return json(['message' => '商品id不正确，请核对']);
        }

        $where['goods_id'] = array('IN', explode(',', $post['goods_id']));
        $data = WishHelper::gallery($where);
        return json(['data' => $data]);
    }

    /**
     * @title 获取wish销售人员账号信息
     * @url publish/wish/getSellers
     * @access public
     * @method get
     * @author joy
     * @param Request $request
     * @return json
     */
    public function getSellers(Request $request)
    {

        $spu = $request->param('spu','');
        $userInfo = Common::getUserInfo();

        $data = (new ExpressHelper())->getAccounts($userInfo['user_id'],$spu,3);

        return json(['data' => $data]);

        $get = $request->instance()->param();

        $warehouse_type = $request->get('type', '');

        if (isset($get['spu']) && !empty($get['spu'])) {

            $spu = $get['spu'];
            $sellers = WishHelper::sellers($warehouse_type);
            $data = WishHelper::filterSeller($sellers, $spu);
        } else {
            $data = WishHelper::sellers($warehouse_type);
        }
        return json(['data' => $data]);
    }

    /**
     * @title 获取品牌
     * @url publish/wish/getBrands
     * @access public
     * @method get
     * @param Request $request
     * @return json
     */
    public function getBrands(Request $request)
    {
        $brands = WishHelper::getBrands();
        return json(['data' => $brands]);
    }

    /**
     * @title 获取刊登页面需要的数据
     * @url publish/wish/getData
     * @access public
     * @method get
     * @apiRelate app\publish\controller\Wish::getSellers
     * @apiRelate app\warehouse\controller\Delivery::getWarehouseChannel
     * @apiRelate app\goods\controller\Brand::dictionary
     * @apiRelate app\publish\controller\Wish::getWishSize
     * @apiRelate app\publish\controller\Wish::getWishOnlineTags
     * @apiRelate app\publish\controller\Wish::uploadImages
     * @apiRelate app\publish\controller\Wish::createNetImage
     * @apiRelate app\publish\controller\Wish::colors
     * @apiRelate app\publish\controller\PricingRule::calculate
     * @apiRelate app\goods\controller\Tag::dictionary
     * @param int goods_id 商品id
     * @return Response json
     */
    public function getData(Request $request = null)
    {
        $get = $request->instance()->param();

        if (isset($get['id']) && $get['id']) {
            $id = $get['id'];

            $where['id'] = ['eq', $id];

            $status = isset($get['status']) ? $get['status'] : '';

            $data = WishListingHelper::getProductVariant($where, $id, $status);

            $data['channel_id'] = 3;

            return json(['data' => $data]);
        }

        //先获取是否有需要补充资料的待刊登商品
        $goods_id = $request->instance()->get('goods_id');

        //$uid = $request->instance()->get('uid',10);

        $uid = Common::getUserInfo($request) ? Common::getUserInfo($request)['user_id'] : 0;;

        $lang_id = $request->instance()->get('lang_id', 2);//默认获取英文资料

        if (empty($goods_id)) {
            return json(['message' => '商品id不能为空'],400);
        }
        $tortFlag = GoodsTortDescription::where('goods_id',$goods_id)->value('id') ? 1 : 0;

        $goodsHelp = new GoodsHelp();

        $baseInfo = $goodsHelp->getBaseInfo($goods_id);

        if(empty($baseInfo))
        {
            throw new JsonErrorException("商品不存在");
        }


        //获取wish销售
        unset($baseInfo['properties']);
        unset($baseInfo['platform_sale']);


        $multiAttr = $goodsHelp->getSkuInfo($goods_id,$baseInfo['channel_id']);

        $wishHelp = new WishHelper();

        if ($multiAttr) {
            $skus = $multiAttr['lists'];
            //如果只有一个sku，且sku=spu，则是单属性
//            if ( count($skus) == 1   ) {
//                $skus = [];
//                $multiSize['size'] = '';
//            } else {
//
//            }
            //如果是多属性商品
            $skus = $wishHelp->getSkuAttr($skus, $goods_id);
            $multiSize = $wishHelp->multiSize($goods_id);
        } else {
            $skus = [];
            $multiSize['size'] = '';
        }


        $images = GoodsImage::getPublishImages($goods_id,3);

        $galleries = $images['spuImages'];

        $skuImages = $images['skuImages'];

        if($skus && $skuImages)
        {
            $skus = GoodsImage::replaceSkuImage($skus,$skuImages,3);
        }

        if (empty($galleries)) {
            $galleries = [];
        }

        $titleDesc = $wishHelp->getProductDescription($goods_id, $lang_id);

        if ($titleDesc) {
            $name = @$titleDesc['title'];
            $description = @$titleDesc['description'];

            $sellingPoints = json_decode($titleDesc['selling_point'], true);
            if (!empty($sellingPoints)) {
                $spStr = 'Bullet Points:<br>';
                $i = 1;
                foreach ($sellingPoints as $sellingPoint) {
                    if (empty($sellingPoint)) {
                        continue;
                    }
                    $spStr .= (string)$i.'. '.$sellingPoint.'<br>';
                    $i++;
                }
                $spStr .= '<br>';
                $description = $spStr.$description;
            }


            $description = str_replace('<br>', "\n", $description);
            $description = str_replace('<br />', "\n", $description);
            $description = str_replace('&nbsp;', " ", $description);

            if ($titleDesc['tags']) {
                $tags = explode(',', $titleDesc['tags']);
                $newTags = $wishHelp->arrayAddKey($tags, 'name');
            } else {
                $newTags = [];
            }

        } else {
            $description = '';
            $name = '';
            $newTags = [];
        }
        $vars = array(
            array(
                'accountid' => '',
                'account_code' => '',
                'account_name' => '',
                'is_virtual_send' => 0,
                'name' => $name,
                'inventory' => '',
                'msrp' => 0,
                'price' => $baseInfo['retail_price'],
                'tags' => $newTags,
                'shipping' => '',
                'shipping_time' => '',
                'cron_time' => '',
                'description' => $description,
                'images' => $galleries,
                'variant' => $skus,
            ),
        );

        //读取缓存数据
        $options['type'] = 'file';

        Cache::connect($options);

        $cacheData = Cache::has('wishPublishCache:' . $goods_id . '_' . $uid);

        //缓存文件存在
        if ($cacheData) {
            //获取缓存数据
            $data = Cache::get('wishPublishCache:' . $goods_id . '_' . $uid);

            if ($data)
            {
                if ($data['vars'] == '[]' || empty($data['vars']))
                {
                    $data['vars'] = $vars;
                    $data['accountid'] = '';
                    $data['account_code'] = '';
                    $data['account_name'] = '';
                } else {
                    $vars = json_decode($data['vars'], true);
                    foreach ($vars as &$v)
                    {
                        if ($v['tags']) {
                            if(!is_array($v['tags']))
                            {
                                $varTags = explode(',', $v['tags']);
                                $v['tags'] = $wishHelp->arrayAddKey($varTags, 'name');
                            }
                        } else {
                            $v['tags'] = [];
                        }

//                        if (is_array($v['images']))
//                        {
//                            $v['images'] = $wishHelp->arrayAddKey($v['images'], 'path');
//                        }

                        $data['accountid'] = $v['accountid'];
                        $accountInfo = (new WishAccount())->where(['id' => $v['accountid']])->find();
                        $data['account_code'] = isset($accountInfo['account_code']) ? $accountInfo['account_code'] : '';
                        $data['account_name'] = isset($accountInfo['account_name']) ? $accountInfo['account_name'] : '';
                    }
                    //$data['wishSize']=$wishSize;
                    $data['vars'] = $vars;
                }
                $data['base_url'] =  DCache::store('configParams')->getConfig('innerPicUrl')['value'].DS;
                $data['tort_flag'] = $tortFlag;
                $data['channel_id'] = 3;
                $data['source'] = 'cache'; // 来源缓存
                return json(['data' => $data]);
            }
        }

        if (count($multiSize['size']) > 1) {
            $isMultiSize = 1;
        } else {
            $isMultiSize = 0;
        }

        $data = array(
            'goods_id' => $goods_id,
            'tort_flag' => $tortFlag,
            //'accountid'=>array(),
            'zh_name' => $baseInfo['name'],
            'parent_sku' => $baseInfo['spu'],
            'brand' => $baseInfo['brand'],
            'upc' => '',
            'isMultiSize' => $isMultiSize,
            'multiSize' => $multiSize['size'],
            'landing_page_url' => $baseInfo['source_url'] ? $baseInfo['source_url'] : '',
            'warehouse' => $baseInfo['warehouse'],
            'warehouse_type' => $baseInfo['warehouse_type'],
            'cost' => $baseInfo['cost_price'],
            'weight' => $baseInfo['weight'],
            'source' => 'original', //原始数据
            //'wishSize'=>$wishSize,
            'tags'=> $baseInfo['tags'], //-pan
            'vars' => $vars,
            'base_url'=> DCache::store('configParams')->getConfig('innerPicUrl')['value'].DS,
            'transport_property'=>$baseInfo['transport_property'],

        );
        $data['channel_id'] = 3;
        return json(['data' => $data]);
    }

    /**
     * @title 保存并同步到平台
     * @url publish/wish/rsync
     * @access public
     * @method post
     * @input Request  mixed $request
     * @return Response Json
     *
     */
    public function rsync(Request $request)
    {
        //获取post过来的数据

        $post = $request->instance()->post();

        $helper = new WishHelper();

        $error = $helper->validatePost($post);

        if ($error) {
            return json(['message' => $error], 500);
        }
        $goods_id = $post['goods_id'];
        $parent_sku = $post['parent_sku'];

        //$uid = $post['uid'];

        $uid = Common::getUserInfo($request) ? Common::getUserInfo($request)['user_id'] : 0;;
        $post['uid'] = $uid;

        $num = $helper->insertData($post);

        if ($num > 0) {
            //Cache::rm('wishPublishCache:'.$goods_id.'_'.$uid);

            $options['type'] = 'file';
            Cache::connect($options);
            if (Cache::has('wishPublishCache:' . $goods_id . '_' . $uid)) {
                Cache::rm('wishPublishCache:' . $goods_id . '_' . $uid);
            }

            $vars = json_decode($post['vars'], true);

            if (is_array($vars)) {
                $message = $helper->startRsynAddProduct($vars, $parent_sku);
                if (strpos($message, '成功') !== FALSE) {
                    return json(['message' => $message]);
                } else {
                    return json(['message' => $message . ',请移步异常刊登中修改'], 500);

                }
            }
        } else {
            return json(['message' => '刊登失败：' . $num], 500);
        }
    }


    /**
     * @title wish刊登保存功能
     * @url publish/wish/save
     * @access public
     * @method post
     * @input Request $request
     * @return json
     */
    public function save(Request $request)
    {
        try {
            $post = $request->instance()->param();

            //$uid = @$post['uid'];

            $uid = Common::getUserInfo($request) ? Common::getUserInfo($request)['user_id'] : 0;

            $post['uid'] = $uid;

            $spu = @$post['parent_sku'];

            $goods_id = @$post['goods_id'];

            if (empty($spu)) {
                return json(['message' => '商品spu必须'], 500);
            }

            /**
             * notice：此处还需要传入一个用户sessionid来区分是哪个用户刊登的
             */
            $options['type'] = 'file';

            Cache::connect($options);

            if (isset($post['vars']) && $post['vars']) {
                $vars = json_decode($post['vars'], true);

                foreach ($vars as &$v) {
                    if (is_string($v['cron_time'])) {
                        $v['cron_time'] = strtotime($v['cron_time']);
                    }
                    $variants = $v['variant'];
                    foreach ($variants as &$variant) {
                        if (!isset($variant['cost_price'])) {
                            $variant['cost_price'] = isset($variant['cost']) ? $variant['cost'] : 9999;
                        }
                    }
                    $v['variant'] = $variants;
                }
                $post['vars'] = json_encode($vars);
                //$post['vars'] = json_encode($vars);
            }

            $res = Cache::set('wishPublishCache:' . $goods_id . '_' . $uid, $post, 0);

            if ($res) {
                if ((new WishHelper())->saveDraft($post)) {
                    return json(['message' => '保存为草稿成功']);
                } else {
                    return json(['message' => '保存为草稿失败'], 400);
                }
            } else {
                return json(['message' => '缓存写入失败'], 500);
            }
        } catch (JsonErrorException $exp) {
            throw new JsonErrorException($exp->getMessage());
        }
    }

    /**
     * @title wish刊登功能
     * @url publish/wish/add
     * @access public
     * @method post
     * @param Request $request
     * @return json
     */
    public function add(Request $request)
    {

        //获取post过来的数据
        $post = $request->param();

        $goods_id = $request->param('goods_id');

        $spu = $request->param('parent_sku');

        $uid = Common::getUserInfo($request) ? Common::getUserInfo($request)['user_id'] : 0;

        $post['uid'] = $uid;

        $helper = new WishHelper();

        $error = $helper->validatePost($post);

        if ($error) {
            return json(['message' => $error], 400);
        }

        $num = $helper->insertData($post);

        if (is_int($num) && $num > 0) {
            $options['type'] = 'file';
            Cache::connect($options);
            if (Cache::has('wishPublishCache:' . $goods_id . '_' . $uid)) {
                Cache::rm('wishPublishCache:' . $goods_id . '_' . $uid);
            }
            (new WishHelper())->deleteDraft(['goods_id' => $goods_id, 'uid' => $uid],$uid);
            return json(['message' => '刊登成功【' . $num . '】']);
        } else {
            return json(['message' => $num], 500);
        }

    }

    /**
     * @title 获取wish待刊登商品列表
     * @url publish/wish/productList
     * @access public
     * @method get
     * @apiRelate app\publish\controller\Wish::getSellers
     * @apiRelate app\publish\controller\JoomCategory::category
     * @param array $request
     * @output think\Response
     */

    public function productList()
    {

        try {
            $request = Request::instance();
            $page = $request->get('page', 1);
            $pageSize = $request->get('pageSize', 50);
            $helper = new WishHelper();
            //搜索条件
            $param = $request->param();

            $fields = "*";

            $data = $helper->waitPublishGoodsMap($param, $page, $pageSize, $fields);

            return json($data);

        } catch (Exception $e) {
            throw new JsonErrorException($e->getFile() . $e->getLine() . $e->getMessage());
            return json(['message' => '数据异常', 'data' => []]);
        }
    }


    /**
     * @title wish已刊登列表
     * @url publish/wish/lists
     * @access public
     * @method get
     * @apiRelate app\publish\controller\Wish::getSellers
     * @apiRelate app\publish\controller\Wish::addMany
     * @apiRelate app\listing\controller\Wish::batchEnable
     * @apiRelate app\listing\controller\Wish::rsyncListing
     * @apiRelate app\listing\controller\Wish::rsyncEditListing
     * @apiRelate app\listing\controller\Wish::batchEditAction
     * @apiRelate app\publish\controller\Wish::del
     * @apiFilter app\publish\filter\WishFilter
     * @apiFilter app\publish\filter\WishDepartmentFilter
     * @return json
     */
    public function lists(Request $request)
    {

        $post = $request->instance()->get();

        $page = $request->get('page', 1);

        $pageSize = $request->get('pageSize', 30);

        if (isset($post['fields'])) {
            $fields = $post['fields'];
        } else {
            $fields = "Distinct p.id ,p.local_spu,is_promoted,lowest_price,highest_price,lowest_shipping,highest_shipping,p.id,p.uid,p.goods_id,wish_express_countries,p.inventory,p.cron_time,p.lock_update,p.id,p.product_id,p.parent_sku,p.name,p.tags,p.review_status,p.number_sold,p.number_saves,p.date_uploaded,p.last_updated,p.main_image,p.parent_sku,p.accountid,p.auto_sp";
        }

        $return = (new WishHelper())->getLists($post, $page, $pageSize, $fields);
        $return['base_url']=DCache::store('configParams')->getConfig('innerPicUrl')['value'].DS;

        return json($return);
    }

    /**
     * @title wish已刊登变体信息
     * @url publish/wish/getSkus
     * @access public
     * @method get
     * @param Request $request
     * @param $id
     */
    public function getSkus(Request $request)
    {
        $id = $request->param('id');
        if(empty($id))
        {
            return json(['message'=>'id不能为空'],400);
        }
        $response = (new WishHelper())->getVariants($id);
        return json($response);
    }

    /**
     * @title 导出商品转成joom格式
     * @author starzhan
     * @date 2017-10-23
     * @url /publish/wish/export
     * @method get
     */
    public function export(Request $request)
    {
        try {
            //搜索条件
            $params = $request->param();
            $ids = [];
            if (isset($params['ids'])) {
                $ids = json_decode($params['ids'], true);
            }
            if (!$ids) {
                throw  new Exception('勾选ID不能为空');
            }
            $helper = new WishHelper();
            $result = $helper->export($ids);
            return json($result, 200);
        } catch (Exception $ex) {
            return json(['message' => $ex->getMessage()], 500);
        }
    }
    /**
     * @title wish导出所有商品
     * @date 2018-06-13
     * @url /publish/wish/download-all
     * @method get
     */
    public function downloadAll(Request $request){
       try{
           $fields = $request->param('fields','');
           $channel = $request->param('channel','wish');
           if(empty($fields)){
               return json_error('请勾选要导出的字段');
           }
           $uid = Common::getUserInfo($request) ? Common::getUserInfo($request)['user_id'] : 0;
           $response = (new WishHelper())->downloadAll($fields,$uid,$channel);
           return json($response);
       }catch (Exception $exp){
           throw new JsonErrorException($exp->getMessage());
       }

    }

    /**
     * @title wish导出字段
     * @date 2018-06-13
     * @url /publish/wish/download-fields
     * @method get
     */
    public function downloadFields(Request $request)
    {
        try {
            $response = (new WishHelper())->getDownloadFields();
            return json($response);
        } catch (Exception $exp) {
            throw new JsonErrorException($exp->getMessage());
        }
    }

    /**
     * @title 调整成本价
     * @url /wish/adjust-cost/batch
     * @method put
     * @param Request $request
     * @return \think\response\Json
     */
    public function adjustCostPrice(Request $request)
    {
        try {
            $data = json_decode($request->param('data'), true);
            (new WishHelper())->adjustCostPrice($data);
            return json(['result'=>true, 'message'=>'修改成功'], 200);
        } catch (Exception $e) {
            return json(['result'=>false, 'message' =>$e->getMessage()], 500);
        }
    }

    /**
     * @title 未刊登侵权信息
     * @param Request $request
     * @return \think\response\Json
     * @url /publish/wish/showTort
     * @method GET
     */
    public function showTort(Request $request)
    {
        $goods_id = $request->param('goods_id');

        if (empty($goods_id)) {
            return json(['message' => 'goods_id为空'], 400);
        }

        $result = ( new WishHelper() )->goodsTortInfo($goods_id);

        return json($result);
    }
}
