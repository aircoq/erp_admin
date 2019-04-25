<?php
namespace app\publish\controller;
use app\common\cache\Cache;
use app\common\controller\Base;
use app\common\exception\JsonErrorException;
use app\common\model\aliexpress\AliexpressApiException;
use app\listing\service\AliexpressListingHelper;
use app\publish\exception\AliPublishException;
use app\publish\service\AliexpressTaskHelper;
use app\publish\service\WishHelper;
use think\Exception;
use think\Request;
use app\publish\service\ExpressHelper;
use app\publish\validate\ExpressValidate;
use app\publish\service\FieldAdjustHelper;
use app\publish\task\AliexpressGrabImages;
use app\publish\service\AliProductHelper;
use app\common\service\Common;
use think\Route;
use app\publish\service\AliexpressPublishTaskHelper;


/**
 * @module 刊登系统
 * @title Aliexpress刊登
 * @url /publish/express
 * @author Hot-Zr
 * time:10:49:14
 */
class Express extends Base
{
    private $_validate;
    private $uid;
    public function __construct(Request $request = null) {
        parent::__construct($request);
        $this->_validate = new ExpressValidate();
        $this->uid=Common::getUserInfo($this->request) ? Common::getUserInfo($this->request)['user_id'] : 0;
    }
    /**
     * @title 获取速卖通部门所有员工
     * @url /publish/express/users
     * @method get
     * @author joy
     * @access public
     * @return json
     */
    public function users()
    {
        try{
            $response = (new WishHelper())->getWishUsers(4);
            return json($response);
        }catch (Exception $exp){
            throw new JsonErrorException($exp->getMessage());
        }
    }
    /**
     * @title 同步分类
     * @url /aliexpress-rsyn-category
     * @method post
     */
    public function rsyncCategory(Request $request)
    {
        try {
            $category_id = $request->param('category_id',0);
            if( empty($category_id)){
                return json_error('分类id必须');
            }
            $account_id = $request->param('account_id',0);
            if( empty($account_id)){
                return json_error('账号id必须');
            }
            $service = new AliexpressTaskHelper();
            $account = Cache::store('AliexpressAccount')->getAccountById($account_id);
            if(empty($account)){
                return json_error('账号缓存信息不存在');
            }
            $service->getAeCategory($account,$category_id);
            return json(['message'=>'同步成功']);
        } catch (Exception $exp) {
            throw new JsonErrorException($exp->getMessage());
        }
    }
    /**
     * @title 速卖通刊登错误码解释
     * @url /publish/express/error-explain
     * @access public
     * @method post
     * @param array $request
     * @output think\Response
     */
    public function publishError(Request $request){
        try{
            $params = $request->param();
            $page = $request->param('page',1);
            $pageSize = $request->param('pageSize',30);
            $response = (new AliexpressApiException())->lists($params,$page,$pageSize);
            return json($response);
        }catch (Exception $exp){
            throw new JsonErrorException($exp->getMessage());
        }
    }
    /**
     * @title 速卖通已刊登导出
     * @url /publish/express/download
     * @access public
     * @method post
     * @param array $request
     * @output think\Response
     */
    public function download(Request $request){
        $ids = $request->param('ids');
        if(empty($ids)){
            return json(['message'=>'请选择你要导出的数据'],400);
        }
        $response = (new ExpressHelper())->dowonload($ids);
        return json($response);
    }
    /**
     * @title 速卖通帐号本地授权分类列表
     * @method GET
     * @url /aliexpress-auth-local-category
     * @return []
     */

    public function getAuthCategorys(Request $request)
    {
        try{
            $account_id = $request->param('account_id',0);
            if(empty($account_id))
            {
                return json(['message'=>'请选择帐号'],400);
            }
            $response = (new ExpressHelper())->getAuthCategorys($account_id);
            return json($response);
        }catch (Exception $exp){
            throw new JsonErrorException($exp->getMessage());
        }

    }

    /**
     * @title 速卖通帐号
     * @method GET
     * @url /aliexpress-accounts
     * @apiFilter app\publish\filter\AliexpressFilter
     * @apiFilter app\publish\filter\AliexpressDepartmentFilter
     * @return []
     */

    public function getAccounts(Request $request)
    {
        $spu = $request->param('spu','');
        $userInfo = Common::getUserInfo();


        $response = (new ExpressHelper())->getAccounts($userInfo['user_id'],$spu);
        return json($response);
    }
    /**
     * @title 速卖通刊登详情预览
     * @method GET
     * @apiRelate app\publish\controller\AliAuthCategory::review
     * @apiRelate app\publish\controller\AliAuthCategory::detail
     * @return []
     */

    public function review()
    {
        return [];
    }
    /**
     * @title 速卖通平台根据品牌获取分类
     * @method GET
     * @param account_id 帐号id
     * @param keywords  搜索值
     * @param  page 页码
     * @param  pageSize 每页显示数量
     * @url aliexpress-get-categories-by-brand
     * @return 返回一个JSON数组
     */
    public function getCategoryByBrand(Request $request)
    {
        try
        {
            $params = $request->param('keywords','');
            $account_id = $request->param('account_id',0);
            $brand_id = $request->param('brand_id',0);
            if($account_id==0)
            {
                throw new JsonErrorException("帐号id不能为0");
            }

            $page = $request->param('page',1);
            $pageSize=$request->param('pageSize',30);
            $ExpressHelper = new ExpressHelper();

            return json($ExpressHelper->getAliexpressAllCategorysByBrand($account_id,$params,$page,$pageSize,$brand_id));
        }catch (\Exception $exp) {
            throw new JsonErrorException("{$exp->getFile()};{$exp->getLine()};{$exp->getMessage()}");
        }
    }


    /**
     * @title 速卖通平台分类列表
     * @method GET
     * @url aliexpress-categories
     * @return 返回一个JSON数组
     */
    public function getAliexpressAllCategory(Request $request)
    {
        try
        {
            $params = $request->param('keywords','');
            $page = $request->param('page',1);
            $pageSize=$request->param('pageSize',30);
            $ExpressHelper = new ExpressHelper();

            return json($ExpressHelper->getAliexpressAllCategorys($params,$page,$pageSize));
        }
        catch (\Exception $e)
        {
            return json($e->getMessage(),400);
        }
    }


    /**
     * @title 保存速卖通刊登分类属性
     * @url /aliexpress-save-publish-template
     * @method post
     * @return \think\Response
     */
    public function savePublishTemplate(Request $request)
    {
        try{
            $param = $request->param();
            $goods_id  = $request->param('goods_id');
            $attr  = $request->param('attr');
            $category_id  = $request->param('category_id');
            if(empty($goods_id))
            {
                return json(['message'=>'商品id为0,不能保存'],500);
            }
            if(empty($attr))
            {
                return json(['message'=>'属性不能为空'],500);
            }
            if(empty($category_id))
            {
                return json(['message'=>'分类id不能为空'],500);
            }

            $response = (new ExpressHelper())->savePublishTemplateData($param,$this->uid);
            if($response)
            {
                return json(['message'=>'保存刊登模板成功']);
            }
        }catch (JsonErrorException $exp){
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }

    }
	/**
	 * @title 速卖通分类列表
	 * @url /aliexpress-category-tree
	 * @method get
	 * @return \think\Response
	 */
	public function category_tree()
	{
		try {
			$request = Request::instance();
			if (isset($request->header()['X-Result-Fields']))
			{
				$field = $request->header()['X-Result-Fields'];
			}

			$category_list = Cache::store('AliexpressCategoryCache')->getCategoryTree();
			$result = $category_list;
			return json($result, 200);
		} catch (Exception $e) {
			return json(['message' => '数据异常' . $e->getFile().$e->getLine().$e->getMessage()], 500);
		}
	}

    public function index()
    {
        return json([]);
    }

    /**
     * @title 获取已刊登商品列表
     * @url product
     * @method GET
     * @apiRelate app\order\controller\Order::account
     * @apiRelate app\index\controller\MemberShip::publish
     * @apiRelate app\publish\controller\Express::getExpireSearch
     * @apiRelate app\publish\controller\Express::getProductStatus
     * @apiRelate app\publish\controller\AliAuthCategory::getRelationAndCustomTemplate
     * @apiRelate app\publish\controller\AliAuthCategory::product_template_list
     * @apiRelate app\listing\controller\Aliexpress::windowList
     * @apiFilter app\publish\filter\AliexpressFilter
     * @apiFilter app\publish\filter\AliexpressDepartmentFilter
     * @return \think\response\Json
     */
    public function getProduct(Request $request)
    {
        try{
            $arrRequest = $request->param();
            $helpServer = new AliProductHelper();
            $result = $helpServer->getAliProductList($arrRequest,$arrRequest['page'],$arrRequest['pageSize']);

            return json($result,200);
        }catch (\Exception $exp){
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }

    /**
     * @title 编辑商品
     * @method get
     * @url editProduct
     * @apiRelate app\index\controller\MemberShip::publish
     * @apiRelate app\index\controller\MemberShip::member
     * @apiRelate app\publish\controller\AliexpressBrand::brands
     * @apiRelate app\goods\controller\Category::index
     * @apiRelate app\goods\controller\GoodsImage::listing
     * @apiRelate app\index\controller\Currency::dictionary
     * @param Request $request
     * @return \think\response\Json
     */
    public function editProduct(Request $request)
    {
        try{
            $productId = $request->get('id');
            if(!$productId){
                throw new AliPublishException('参数错误');
            }
            $categoryId = $request->get('category_id',0);
            $helpServer = new AliProductHelper();
            $productDetail = $helpServer->getAliProductDetail($productId,$categoryId);
            return json($productDetail,200);
        }catch (Exception $exp){
            throw new JsonErrorException("{$exp->getFile()}{$exp->getLine()}{$exp->getMessage()}");
        }

    }

    /**
     * @title 未刊登商品列表
     * @method get
     * @url unpublish
     * @param Request $request
     * @apiFilter app\publish\filter\AliexpressFilter
     * @apiRelate app\publish\controller\JoomCategory::category
     * @apiFilter app\publish\filter\AliexpressDepartmentFilter
     * @return \think\response\Json
     */
    public function unPublish(Request $request)
    {
        $params = $request->param();
        $page = $request->param('page',1);
        $pageSize = $request->param('pageSize',50);

        $uid=Common::getUserInfo($this->request) ? Common::getUserInfo($this->request)['user_id'] : 0;

        $helpServer = new AliProductHelper();
        $result = $helpServer->getUnpublishList($params,$page,$pageSize, $uid);

        if(isset($result['message']) && $result['message']){
            return json(['message' => $result['message']],400);
        }

        return json($result,200);
    }

    /**
     * @title 根据pid查询子分类
     * @method GET
     * @url categorys
     * @param 分类ID category_id 默认为0，即顶层分类
     * @return 返回一个JSON数组
     */
    public function Getpostcategorybyid(Request $request)
    {
        try
        {
            $intCategoryPid = $request->get('category_id',0);
            //如果存在商户ID，则该方法在获取总分类的时候会过滤掉该商户未拥有刊登权限的分类
            $intAccountId = $request->get('account_id',0);

            $ExpressHelper = new ExpressHelper();
            return json($ExpressHelper->GetPostCategoryById($intCategoryPid,$intAccountId));
            
        }
        catch (\Exception $e)
        {
            return json($e->getMessage(),400);
        }
    }
    
    /**
     * @title 商品上架
     * @method post
     * @url online
     * @param productIds 这是一个由英文逗号连接起来的商品id
     * @return 返回一个JSON数组
     */
    public function Onlineaeproduct()
    {
        try
        {
            $ExpressHelper = new ExpressHelper();
            return json($ExpressHelper->OnLineaeProduct(Request::instance()->get('product_ids')));
        }
        catch (\Exception $e)
        {
            return json($e->getMessage(),400);
        }
    }
    
    /**
     * @title 商品下架
     * @method post
     * @url offline
     * @param productIds 这是一个由英文逗号连接起来的商品id
     * @return 返回一个JSON数组
     */
    public function Offlineaeproduct()
    {
        try
        {
            $ExpressHelper = new ExpressHelper();
            return json($ExpressHelper->OffLineaeProduct(Request::instance()->get('product_ids')));
        }
        catch (\Exception $e)
        {
            return json($e->getMessage(),400);
        }
    }
    
    /**
     * @title 根据类目ID获取适合的尺码模板
     * @url sizetemp
     * @method get
     * @param account_id 商户id
     * @param category_id  分类ID 必须为叶子分类
     * @return 返回一个JSON数组，如果是空则没有推荐的尺码模板
     */
    public function Getsizechartinfobycategoryid()
    {
        try
        {
            $account_id = Request::instance()->get('account_id',0);
            $category_id = Request::instance()->get('category_id',0);
            $ExpressHelper = new ExpressHelper();
            return json($ExpressHelper->GetSizeChartInfoyCategoryId($account_id,$category_id));
        }
        catch (\Exception $e)
        {
            return json($e->getMessage(),400);
        }
    }
    
    /**
     * @title 查询指定分类的属性
     * @method get
     * @url attributes
     * @param category_id 分类ID 必须为叶子分类ID
     * @param is_sku 是否查询SKU属性，0：否，1：是  ，无：所有
     * @return 返回一个JSON数组，如果是空则没有信息模板
     */
    public function Getattrbycategoryid()
    {
        try
        {
            $arrData = [];
            $arrData['category_id'] = Request::instance()->get('category_id');
            $arrData['$is_sku'] = Request::instance()->get('is_sku');
            $ExpressValidate = new ExpressValidate();
            if( $ExpressValidate->scene('categoryId')->check(['category_id'=>$arrData['category_id']]) == false)
            return json($ExpressValidate->getError(),400);
            $ExpressHelper = new ExpressHelper();
            return json($ExpressHelper->GetAttrByCategoryId($arrData));
        }
        catch (\Exception $e)
        {
            return json($e->getMessage(),400);
        }
        
    }

    /**
     * @title 获取商户账户列表
     * @method get
     * @url accounts
     * @param account_id 商户ID，必填
     * @return 返回一个JSON数组，如果是空则没有信息模板
     */
    public function Getaliexpressaccount()
    {
        try
        {
            $arrRequest = array_filter(Request::instance()->get());
           // $arrRequest['page'] =Request::instance()->get('page',1);
           // $arrRequest['pageSize'] =Request::instance()->get('pageSize',50);
           $arrRequest['is_invalid'] = 1;
            
            $ExpressHelper = new ExpressHelper();
            return json($ExpressHelper->GetAliexpressAccount($arrRequest));
        }
        catch (\Exception $e)
        {
            return json($e->getMessage(),400);
        }
    }

    /**
     * @title 获取商品列表
     * @method get
     * @url goods
     * @return 返回一个JSON数组，如果是空则没有信息模板
     */
    public function Getgoods()
    {
        try
        {
            $arrRequest = Request::instance()->get();
            if(isset($arrRequest['snType']) && isset($arrRequest['snType']) && !empty($arrRequest['snText']))
            {
                $arrRequest[$arrRequest['snType']] = $arrRequest['snText'];
            }
            unset($arrRequest['snType']);
            unset($arrRequest['snText']);
            $arrRequest['page'] =Request::instance()->get('page',1);
            $arrRequest['pageSize'] =Request::instance()->get('pageSize',50);
            $ExpressHelper = new ExpressHelper();
            return json($ExpressHelper->GetGoods($arrRequest));
        }
        catch (\Exception $e)
        {
            return json($e->getMessage(),400);
        }
    }
    
    /**
     * @title 根据产品ID获取映射到速卖通的分类ID
     * @method get
     * @url aliCategoryid
     * @throws \Exception
     * @return 如果存在则返回分类ID，如果不存在则返回一个false
     */
    public function Getgoodsaliexpresscategoryid()
    {
        try
        {
            $intGoodsId = Request::instance()->get('goods_id');
            if(empty($intGoodsId))throw new \Exception('产品ID不能为空！');
            $ExpressHelper = new ExpressHelper();
            return json($ExpressHelper->GetAliexpressCategoryIdByGoodsId($intGoodsId));
        }
        catch (\Exception $e)
        {
            return json($e->getMessage(),400);
        }
    }
    
    /**
     * @title 获取待刊登的产品详情
     * @method get
     * @url productInfo
     * @apiRelate app\publish\controller\AliexpressBrand::brands
     * @apiRelate app\index\controller\MemberShip::publish
     * @apiRelate app\index\controller\MemberShip::member
     * @apiRelate app\goods\controller\GoodsImage::listing
     * @apiRelate app\goods\controller\Brand::dictionary
     * @apiRelate app\index\controller\Currency::dictionary
     * @apiRelate app\publish\controller\AliexpressBrand::rsyncGroups
     * @apiRelate app\publish\controller\AliexpressBrand::rsyncTransport
     * @apiRelate app\publish\controller\AliexpressBrand::rsyncPromise
     * @throws \Exception
     */
    public function Getproductinfo()
    {
        try
        {
            //$intGoodsId = Request::instance()->get('goods_id',100005);
            $intGoodsId = Request::instance()->get('goods_id');
            if(empty($intGoodsId))throw new \Exception('产品ID不能为空！');
            $ExpressHelper = new ExpressHelper();
            return json($ExpressHelper->GetProductData($intGoodsId));
        }
        catch (\Exception $e)
        {
            return json($e->getMessage(),400);
        }
    }
    
    /**
     * @title 获取仓库列表
     * @method get
     * @url warehouses
     * @return 返回一个JSON数组
     */
    public function Getwarehouse()
    {
        try
        {
            $ExpressHelper = new ExpressHelper();
            return json($ExpressHelper->GetWareHouse());
        }
        catch (\Exception $e)
        {
            return json($e->getMessage(),400);
        }
    }
    
    /**
     * @disabled
     * @desc 由文宇统一提供
     * @title 获取销售员列表
     * @url sellers
     * @method get
     * @return 返回一个JSON数组
     */
    public function Getaccountlist(Request $request)
    {
        try
        {
            $params  = $request->param();
            if(empty($params['account_id'])){
                throw new AliPublishException('参数错误');
            }
            $ExpressHelper = new ExpressHelper();
            return json($ExpressHelper->GetAccountList());
        }
        catch (\Exception $e)
        {
            return json($e->getMessage(),400);
        }
    }
    
    /**
     * @title 获取商户运费模板
     * @method get
     * @url freightTemp
     * @param account_id 商户ID，必填
     * @return 返回一个JSON数组，如果是空则没有信息模板
     */
    public function Getfreighttemplate()
    {
        try
        {
            $intAccountId = Request::instance()->get('account_id');
            if(!$intAccountId){
                throw new AliPublishException('参数错误');
            }
            $ExpressHelper = new ExpressHelper();
            return json($ExpressHelper->GetFreightTemplate($intAccountId));
        }
        catch (\Exception $e)
        {
            return json($e->getMessage(),400);
        }
    }
    
    /**
     * @title 获取商户服务模板
     * @method get
     * @url promiseTemp
     * @param account_id 商户ID，必填
     * @return 返回一个JSON数组，如果是空则没有信息模板
     */
    public function Getpromisetemplate()
    {
        try
        {
            $intAccountId = Request::instance()->get('account_id');
            if(!$intAccountId){
                throw new AliPublishException('参数错误');
            }
            $ExpressHelper = new ExpressHelper();
            return json($ExpressHelper->GetPromiseTemplate($intAccountId));
        }
        catch (\Exception $e)
        {
            return json($e->getMessage(),400);
        }
    }
    
    /**
     * @title 获取商户商品分组
     * @method get
     * @url groups
     * @param account_id 商户ID，必填
     * @return 返回一个JSON数组，如果是空则没有信息模板
     */
    public function Getproductgroup()
    {
        try
        {
            $intAccountId = Request::instance()->get('account_id');
            if(!$intAccountId){
                throw new AliPublishException('参数错误');
            }
            $ExpressHelper = new ExpressHelper();
            return json($ExpressHelper->GetProductGroup($intAccountId));
        }
        catch (\Exception $e)
        {
            return json($e->getMessage(),400);
        }
    }   

    /**
     * @title 判断该商户是否拥有某样产品刊登的分类权限
     * @url categoryPermission
     * @method get
     * @throws \Exception
     * @return 如果有，怎返回分类名称和渠道分类ID和分类名称，否则返回false
     */
    public function Whethercategorypower()
    {
        try
        {
            $intGoodsId = Request::instance()->get('goods_id');
            if(empty($intGoodsId))throw new \Exception('产品ID不能为空！');
            $intAccountId = Request::instance()->get('account_id');
            if(empty($intAccountId))throw new \Exception('商户ID不能为空！');
            $ExpressHelper = new ExpressHelper();
            return json($ExpressHelper->WhetherCategoryPower($intAccountId,$intGoodsId));
        }
        catch (\Exception $e)
        {
            return json($e->getMessage(),400);
        }
    }

    /**
     * @title 根据产品ID、平台账号ID和分类ID获取公有数据
     * @method get
     * @url pulishData
     * @apiRelate app\goods\controller\Category::index
     * @param goods_id 产品ID，必填
     * @return 返回一个JSON数组
     */
    public function Getpulishdata()
    {
        try
        {
            $intGoodsId = Request::instance()->get('goods_id');
            if(empty($intGoodsId))throw new \Exception('产品ID不能为空！');
            $intCategoryId = Request::instance()->get('category_id');
            if(empty($intCategoryId))throw new \Exception('速卖通分类ID不能为空！');
            $intAccountId = Request::instance()->get('account_id');
            if(empty($intCategoryId))throw new \Exception('速卖通分类ID不能为空！');
            $ExpressHelper = new ExpressHelper();
            //$a = $ExpressHelper->GetPulishData($intGoodsId,$intCategoryId);
            return json($ExpressHelper->GetPulishData($intGoodsId,$intAccountId,$intCategoryId));
        }
        catch (\Exception $e)
        {
            return json("File:{$e->getFile()};Line:{$e->getLine()};Message:{$e->getMessage()}",400);
        }
    }
    
    /**
     * @title 根据产品ID获取产品可选的产品图片
     * @method get
     * @url images
     * @param goods_id 产品ID，必填
     * @return 返回一个JSON数组
     */
    public function Getpublishimage()
    {
        try
        {
            $intGoodsId = Request::instance()->get('goods_id');
            if(empty($intGoodsId))throw new \Exception('产品ID不能为空！');
            $ExpressHelper = new ExpressHelper();
            return json($ExpressHelper->GetPublishImage($intGoodsId));
        }
        catch (\Exception $e)
        {
            return json($e->getMessage(),400);
        }    
    }
    
    /**
     * @title 速卖通刊登
     * @method post
     * @url /publish/express/publish
     * @apiRelate app\goods\controller\GoodsImage::listing
     * @apiRelate app\index\controller\Currency::dictionary
     * @return 返回 product_id
     */
    public function Publish(Request $request)
    {
        try{

            $accountList = Cache::store('AliexpressAccount')->getAllAccounts();
            $uid=Common::getUserInfo($this->request) ? Common::getUserInfo($this->request)['user_id'] : 0;

            $arrData = $request->param();

            if(!isset($arrData['vars']))
            {
                return json(['message'=>'你提交的数据为空'],500);
            }


            $task_id = isset($arrData['task_id']) ? $arrData['task_id'] : 0;
            $prductData = json_decode($arrData['vars'],true);

            foreach($prductData as &$data)
            {

                $data['goods_id'] = $arrData['goods_id'];
                if(isset($arrData['id'])&&$arrData['id']){
                    $data['id'] = $arrData['id'];
                }
                $data['is_plan_publish'] = isset($arrData['is_plan_publish'])?$arrData['is_plan_publish']:0;
                $data['goods_spu'] = $arrData['spu'];
                $data['imgs'] = implode(';',$data['imgs']);
                $attr = $data['attr'];
                $sku = $data['sku'];
                unset($data['sku'],$data['attr']);
                $data = FieldAdjustHelper::adjust($data,'publish','HTU');
                $data['attr'] = $attr;
                $data['sku'] = $sku;
                $data['task_id'] = $task_id;
            }


            $ExpressHelper = new ExpressHelper();
            //数据检测，并且返回刊登后的本地商品ID，以数组形式返回
            $arrReturn = [
                'success'=>0,
                'fail'=>0,
                'error_message'=>[]
            ];


            foreach ($prductData as $value)
            {
                //遍历检测第一层数据
                if(isset($value['id'])&&$value['id']){
                    $this->checkParams($value,'edit');
                }else{
                    $this->checkParams($value,'publish');
                }


                //SKU 不能为空
                if(empty($value['sku']))return json('Listing不能为空！');
                //遍历检测Listing数据
                foreach($value['sku'] as $v)
                {
                    $this->checkParams($v,'listing');

                    //校验sku个数是否匹配sku值
                    $checkSkuAttr = $ExpressHelper->checkSkuAttr($value['sku']);

                    if(!$checkSkuAttr['status']){
                        return json(['message'=> $checkSkuAttr['message']],400);
                    }
                }

                $result = $ExpressHelper->savePublishData($value,$uid);

                if($result['status'] < 0){
                    return json(['message'=> $result['message']],400);
                }

                if($result['status']===true)
                {
                    $arrReturn['success']++;
                }
                else
                {
                    $arrReturn['fail']++;
                    $arrReturn['error_message'][] = $accountList[$value['account_id']]['code'];
                }
            }

            $msg = '成功'.$arrReturn['success'].',失败'.$arrReturn['fail'].'。'.implode(';',$arrReturn['error_message']);
            return json(['message'=>$msg],200);
        }catch (JsonErrorException $exp){
            throw new JsonErrorException($exp->getFile().$exp->getLine().$exp->getMessage());
        }

    }



    /**
     * @title 上传图片到临时目录
     * @method post
     * @url uploadTemp
     */
    public function UploadimageTemp()
    {
        $intAccount = Request::instance()->post('account_id');
        $strUrl = $_FILES['image']['tmp_name'];
        $strName = $_FILES['image']['name'];
//      $strUrl = 'http://test.com/1.jpg';
//      $intAccount=149;
        $ExpressHelper = new ExpressHelper();
        return json($ExpressHelper->uploadTempImage($strUrl,$intAccount));
    }
    
    /**
     * @title 图片上传到速卖通图片银行
     * @method post
     * @url upload
     * @return \think\response\Json
     */
    public function Uploadimage()
    {
        $strUrl = 'http://test.com/1.jpg';
        $ExpressHelper = new ExpressHelper();
        return json($ExpressHelper->uploadImage($strUrl,$intAccount=149));
        
    }

    /**
     * @title 获取速卖通产品计数单位
     * @method get
     * @url productUnit
     * @return \think\response\Json
     */
    public function getProductUnit()
    {
        try{
            $helper = new ExpressHelper();
            return json($helper->getProductUnit(),200);
        }catch (Exception $ex){
            return json($ex->getMessage(),400);
        }
    }

    /**
     * @title 违禁词检测
     * @method post
     * @url prohibited
     * @param Request $request
     * @return \think\response\Json
     */
    public function checkProhibitedWords(Request $request)
    {

        $type = [
            'titleProhibitedWords'=>'标题',
            'keywordsProhibitedWords'=>'关键字',
            'productPropertiesProhibitedWords'=>'类目属性',
            'detailProhibitedWords'=>'商品描述'
        ];
        $helpServer = new ExpressHelper();
        $prohibitedType = $helpServer->getProhibitedType();
        $params = $request->param();
        if(!isset($params['data'])||empty($params['data'])){
            return json('参数错误',400);
        }
        $datas = json_decode($params['data'],true);
        //验证参数
        foreach($datas as $data){
            $this->checkParams($data,'prohibited');
        }
        $error = [];
        $response = [];
        foreach($datas as $data){
            try{
                $result = $helpServer->checkProhibitedWords($data['account_id'],$data['category_id'],$data['title'],$data['detail'],$data['attr_val']);
                if(!empty($result)){
                    $account = Cache::store('account')->aliexpressAccount($data['account_id']);
                    foreach($result as $k=>$item){
                        if(!empty($item)){
                            foreach($item as $value){
                                $arr_reason = [];
                                foreach($value['types'] as $val){
                                    $arr_reason[] = $prohibitedType[$val];
                                }
                                $response[] = [
                                    'account_code'=>isset($account['code'])?$account['code']:'',
                                    'position'=>$type[$k],
                                    'word'=>$value['primaryWord'],
                                    'reason'=>implode('|',$arr_reason)
                                ];
                            }
                        }
                    }
                }
            }catch(Exception $exp){
                //$error[] = $data['account_code'].':'.$ex->getMessage();
                throw new Exception("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
            }
        }
        return json($response,200);
    }

    /**
     * @title 根据sku和分类获取listing信息
     * @method get
     * @url skuInfo
     * @param Request $request
     * @return \think\response\Json
     */
    public function getSkuInfo(Request $request)
    {
        $params = $request->param();
        $helpServer = new ExpressHelper();
        $this->checkParams($params,'sku_info');
        $result = $helpServer->getSkuList($params['category_id'],explode(',',$params['sku_ids']));
        return json($result,200);
    }

    /**
     * @title 批量修改标题、服务模板、运费模板、毛重
     * @method post
     * @url batchProduct
     * @param Request $request
     * @return \think\response\Json
     */
    public function batchEditProduct(Request $request)
    {
        try{
            $type = $request->param('type');
            $data = $request->param('data');
            $remark = $request->param('remark','');
            $cron_time = $request->param('cron_time',0);
            if(empty($type)||empty($data)){
             return json('参数错误',400);
            }

            switch($type)
            {
                case 'title':
                    $scene = 'subject';
                    break;
                case 'promiseTemp':
                    $scene = 'promiseTemplateId';
                    break;
                case 'freightTemp':
                    $scene = 'freightTemplateId';
                    break;
                case 'weight':
                    $scene = 'grossWeight';
                    break;
                default:
                    break;
            }

            $result = (new AliexpressListingHelper())->editProductData($data,$type,$this->uid,$remark,$cron_time);
            //$server = new AliProductHelper();
            //$result = $server->batchEditProduct($type,json_decode($data,true));
            return json($result,200);
        }catch (Exception $ex){
            throw new AliPublishException("File:{$ex->getFile()};Line:{$ex->getLine()};message:{$ex->getMessage()}");
        }
    }

    /**
     * @title 批量修改尺寸
     * @method post
     * @url batchSize
     * @param Request $request
     * @return \think\response\Json
     */
    public function batchEditSize(Request $request)
    {
        try{
            $params = $request->param();
            if(!param($params,'product_ids')){
                return json('参数错误',400);
            }
            $server = new AliProductHelper();
            $result = $server->batchEditSize($params,$this->uid);
            return json($result,200);
        }catch (Exception $ex){
            throw new Exception("File:{$ex->getFile()};Line:{$ex->getLine()};Message:{$ex->getMessage()}");
        }
    }

    /**
     * @title 批量修改商品计数单位
     * @method post
     * @url batchUnit
     * @param Request $request
     * @return \think\response\Json
     */
    public function batchEditProductUnit(Request $request)
    {
        try{
            $params = $request->param();
            if(!param($params,'product_ids')){
                return json('参数错误',400);
            }
            $server = new AliProductHelper();
            $result = $server->batchEditProductUnit($params,$this->uid);
            return json($result,200);
        }catch (Exception $ex){
            throw new AliPublishException($ex->getMessage());
        }
    }

    /**
     * @title 批量修改商品SKU价格
     * @method post
     * @url batchPrice
     * @param Request $request
     * @return \think\response\Json
     */
    public function batchEditSkuPrice(Request $request)
    {
        try{
            $params = $request->param();
            if(!param($params,'sku_data')){
                return json('参数错误',400);
            }
            $server = new AliProductHelper();
            $result = $server->batchEditSkuPrice(json_decode($params['sku_data'],true));
            return json($result,200);
        }catch (Exception $ex){
            throw new AliPublishException($ex->getMessage());
        }
    }

    /**
     * @title 批量删除
     * @method delete
     * @url batchDelete
     * @apiParam name:ids type:string desc:ID，例：1,22,33
     * @param Request $request
     * @return \think\response\Json
     */
    public function batchDelete(Request $request)
    {
        $params = $request->param();
        if(!isset($params['ids'])||empty($params['ids'])){
            return json('参数错误',400);
        }
        $arrIds = explode(',',$params['ids']);
        $helpServer = new ExpressHelper();
        return json(['message'=>$helpServer->deleteProduct($arrIds)],200);
    }

    /**
     * @title 获取平台产品状态
     * @method get
     * @url productStatus
     * @return \think\response\Json
     */
    public function getProductStatus()
    {
        $helpServer = new AliProductHelper();
        $arrStatus = $helpServer->getProductStatus();
        return json($arrStatus,200);
    }

    /**
     * @title 剩余有效期
     * @url expireSearch
     * @return \think\response\Json
     */
    public function getExpireSearch()
    {
        $arr = [
            //['id'=>0,'name'=>'全部'],
            ['id'=>3,'name'=>'有效期剩余3天内'],
            ['id'=>7,'name'=>'有效期剩余7天内'],
        ];
        return json($arr,200);
    }

    /**
     * @title 获取已刊登商品简易列表
     * @url productList
     * @apiParam name:account_id type:int desc:平台账号ID
     * @apiParam name:title type:string desc:标题
     * @apiParam name:expire_day type:int desc:剩余有效期
     * @apiParam name:group_id type:int desc:分组ID
     * @apiParam name:page type:int desc:当前页
     * @apiParam name:pageSize type:int desc:每页记录数
     * @return \think\response\Json
     */
    public function getProductList(Request $request)
    {
        try{
            $params = $request->param();
            $server = new AliProductHelper();
            $list = $server->getProductList($params);
            return json($list,200);
        }catch(Exception $exp){
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }

    /**
     * @title 复制商品
     * @url copy
     * @apiRelate app\goods\controller\Category::index
     * @apiRelate app\index\controller\MemberShip::publish
     * @apiRelate app\index\controller\MemberShip::member
     * @apiRelate app\publish\controller\AliexpressBrand::brands
     * @apiRelate app\goods\controller\GoodsImage::listing
     * @apiRelate app\index\controller\Currency::dictionary
     * @param Request $request
     * @return \think\response\Json
     */
    public function copyProduct(Request $request)
    {
        $productId = $request->get('id');
        if(!$productId){
            throw new AliPublishException('参数错误');
        }
        $categoryId = $request->get('category_id',0);
        $helpServer = new AliProductHelper();
        $productDetail = $helpServer->getCopyData($productId,$categoryId);
        return json($productDetail,200);
    }

    /**
     * @title 根据账号和分类获取品牌信息
     * @url brands
     * @method get
     * @apiParam name:account_id type:int desc:平台账号ID require:1
     * @apiParam name:category_id type:int desc:分类ID require:1
     * @param Request $request
     * @return \think\response\Json
     */
    public function brands(Request $request)
    {
        $accountId = $request->get('account_id',0);
        if(!$accountId){
            throw new AliPublishException('参数错误：缺少平台账号ID');
        }
        $categoryId = $request->get('category_id',0);
        if(!$categoryId){
            throw new AliPublishException('参数错误：缺少分类ID');
        }
        $helpServer = new AliProductHelper();
        $brands = $helpServer->getBrands($accountId,$categoryId);
        return json($brands,200);
    }

    /**
     * @title 获取信息模板
     * @url :account_id(\d+)/:type(\w+)/productTemp
     * @apiParam name:account_id type:int desc:平台账号ID
     * @apiParam name:type type:string desc:模板类型:relation;custom
     * @param Request $request
     * @return \think\response\Json
     */
    public function getProductTemp(Request $request)
    {
        $params = $request->param();
        if(!param($params,'account_id')){
            throw new AliPublishException('参数错误：缺少平台账号ID');
        }
        if(!param($params,'type')){
            throw new AliPublishException('参数错误：缺少分类');
        }
        $helpServer = new AliProductHelper();
        $brands = $helpServer->getProductTemp($params['account_id'],$params['type']);
        return json($brands,200);
    }

    /**
     * @title 草稿箱
     * @url drafts
     * @method GET
     * @apiRelate app\order\controller\Order::account
     * @apiRelate app\index\controller\MemberShip::publish
     * @apiRelate app\publish\controller\Express::getExpireSearch
     * @apiRelate app\publish\controller\Express::getProductStatus
     * @apiRelate app\publish\controller\AliAuthCategory::getRelationAndCustomTemplate
     * @apiRelate app\publish\controller\AliAuthCategory::product_template_list
     * @apiRelate app\listing\controller\Aliexpress::windowList
     * @apiFilter app\publish\filter\AliexpressFilter
     * @apiFilter app\publish\filter\AliexpressDepartmentFilter
     * @return \think\response\Json
     */
    public function drafts(Request $request)
    {
        try{
            $arrRequest = $request->param();
            $helpServer = new AliProductHelper();
            $result = $helpServer->draftsList($arrRequest,$arrRequest['page'],$arrRequest['pageSize'],0);
            return json($result,200);
        }catch (\Exception $e){
            return json($e->getMessage().$e->getFile().$e->getLine(),400);
        }
    }

    /**
     * @title 待刊登列表
     * @url wait-publish
     * @method GET
     * @apiRelate app\order\controller\Order::account
     * @apiRelate app\index\controller\MemberShip::publish
     * @apiRelate app\publish\controller\Express::getExpireSearch
     * @apiRelate app\publish\controller\Express::getProductStatus
     * @apiRelate app\publish\controller\AliAuthCategory::getRelationAndCustomTemplate
     * @apiRelate app\publish\controller\AliAuthCategory::product_template_list
     * @apiRelate app\listing\controller\Aliexpress::windowList
     * @apiFilter app\publish\filter\AliexpressFilter
     * @apiFilter app\publish\filter\AliexpressDepartmentFilter
     * @return \think\response\Json
     */
    public function waitPublish(Request $request)
    {
        try{
            $arrRequest = $request->param();
            $helpServer = new AliProductHelper();
            $result = $helpServer->waitPublishList($arrRequest,$arrRequest['page'],$arrRequest['pageSize'],3);
            return json($result,200);
        }catch (\Exception $e){
            return json($e->getMessage().$e->getFile().$e->getLine(),400);
        }
    }

    /**
     * @title 刊登异常列表
     * @url /publish/express/fail-publish
     * @method GET
     * @apiRelate app\order\controller\Order::account
     * @apiRelate app\index\controller\MemberShip::publish
     * @apiRelate app\publish\controller\Express::getExpireSearch
     * @apiRelate app\publish\controller\Express::getProductStatus
     * @apiRelate app\publish\controller\AliAuthCategory::getRelationAndCustomTemplate
     * @apiRelate app\publish\controller\AliAuthCategory::product_template_list
     * @apiRelate app\listing\controller\Aliexpress::windowList
     * @apiFilter app\publish\filter\AliexpressFilter
     * @apiFilter app\publish\filter\AliexpressDepartmentFilter
     * @return \think\response\Json
     */
    public function failPublish(Request $request)
    {
        try{
            $arrRequest = $request->param();
            $helpServer = new AliProductHelper();
            $result = $helpServer->failPublishList($arrRequest,$arrRequest['page'],$arrRequest['pageSize'],4);
            return json($result,200);
        }catch (\Exception $e){
            return json($e->getMessage(),400);
        }
    }

    /**
     * @disabled
     * @return \think\response\Json
     */
    public function test()
    {
        $a = new AliexpressGrabImages();
        $a->execute();
        exit;
        
        $ExpressHelper = new ExpressHelper();
        
        echo '<pre>';
        print_r($ExpressHelper->ListImagePagination(['account_id'=>149,'pageSize'=>'200']));
        exit;
        
        return json($ExpressHelper->ListImagePagination(['account_id'=>149]));
    }

    /**
     * 验证传入参数
     * @param type $params
     * @param type $scene
     * @return boolean
     */
    private function checkParams($params,$scene)
    {
        $result = $this->_validate->scene($scene)->check($params);
        if (true !== $result)
        {
            // 验证失败 输出错误信息
           return json(['message'=>'参数验证失败：' . $this->_validate->getError()],400);
        }

    }


    /**
     * @title 更改成本价
     * @url /publish/express/change-cost-price
     * @method GET
     * @return \think\response\Json
     */
    public function changeCostPrice(Request $request)
    {
        try{

            $params = $request->param('productIds');
            if(!$params){
                throw new AliPublishException('参数错误：缺少产品ID');
            }

            $helpServer = new AliProductHelper();
            $result = $helpServer->changeCostPrice($params);
            return json($result,200);
        }catch (\Exception $e){
            return json($e->getMessage().$e->getFile().$e->getLine(),400);
        }
    }


    /**
     * @title 速卖通批量复制为草稿箱
     * @url /publish/express/batch-copy
     * @method POST
     * @return 返回 product_id
     */
    public function batchCopy(Request $request)
    {
        try{
            $productIds = $request->param('product_ids');
            $accountIds = $request->param('account_ids');

            if(!isset($productIds)){
                return json(['message'=>' 请选择商品信息'],500);
            }

            if(!isset($accountIds)){
                return json(['message'=>' 请选择账号'],500);
            }

            $accountList = Cache::store('AliexpressAccount')->getAllAccounts();
            $uid=Common::getUserInfo($this->request) ? Common::getUserInfo($this->request)['user_id'] : 0;


            //查询产品信息
            $helpServer = new AliProductHelper();
            $prductData = $helpServer->getAliProductDetails($productIds, $accountIds);
            $ExpressHelper = new ExpressHelper();
            //数据检测，并且返回刊登后的本地商品ID，以数组形式返回
            $arrReturn = [
                'success'=>0,
                'fail'=>0,
                'error_message'=> []
            ];

            foreach ($prductData as $value)
            {
                $result = $ExpressHelper->saveDraftData($value,$uid);

                //添加商品禁止上架
                if($result['status'] < 0){
                    return json(['message'=>'商品禁止上架'],400);
                }

                if($result['status']===true) {
                    $arrReturn['success']++;
                } else {
                    $arrReturn['fail']++;
                    $arrReturn['error_message'][] = $accountList[$value['account_id']]['code'];
                }
            }

            $msg = '成功'.$arrReturn['success'].',失败'.$arrReturn['fail'].'。'.implode(';',$arrReturn['error_message']);
            return json(['message'=>$msg],200);
        }catch (JsonErrorException $exp){
            throw new JsonErrorException($exp->getFile().$exp->getLine().$exp->getMessage());
        }

    }


    /**
     * @title 未刊登列表品牌搜索
     * @url /publish/express/product-brand
     * @method GET
     * @return \think\response\Json
     */
    public function productBrand(Request $request)
    {
        try{

            $helpServer = new AliProductHelper();
            $result = $helpServer->brandList($request);
            return json($result,200);
        }catch (\Exception $e){
            return json($e->getMessage().$e->getFile().$e->getLine(),400);
        }
    }



    /**
     * @title 分组列表
     * @url /publish/express/region-group
     * @method GET
     * @return \think\response\Json
     */
    public function regionGroup(Request $request)
    {
        try{
            $service = new ExpressHelper();
            $result = $service->regionGroup();

            return json($result, 200);
        }catch (\Exception $e){
            return json($e->getMessage().$e->getFile().$e->getLine(),400);
        }
    }


    /**
     * @title 区域模板列表
     * @url /publish/express/region-template
     * @method GET
     * @return \think\response\Json
     */
    public function regionTemplate(Request $request)
    {
        try{
            $groupId = $request->get('region_group_id',0);


            $service = new ExpressHelper();
            $result = $service->regionTemplate($groupId);

            return json($result, 200);
        }catch (\Exception $e){
            return json($e->getMessage().$e->getFile().$e->getLine(),400);
        }
    }


    /**
     * @title 添加分组
     * @url /publish/express/add-region-group
     * @method POST
     * @return \think\response\Json
     */
    public function addRegionGroup(Request $request)
    {
        try{

            $post = $request->post();

            if(empty($post)){
                return json(['message'=>' 请提交数据'],500);
            }

            return $this->regionAddEdit($post);
        }catch (\Exception $e){
            return json($e->getMessage().$e->getFile().$e->getLine(),400);
        }
    }


    /**
     * @title 添加区域模板
     * @url /publish/express/add-region-template
     * @method POST
     * @return \think\response\Json
     */
    public function addRegionTemplate(Request $request)
    {
        try{

            $post = $request->post();

            if(empty($post)){
                return json(['message'=>' 请提交数据'],500);
            }

            if(isset($post['parent_id']) && empty($post['parent_id'])){
                return json(['message'=>' 请选择分组'],200);
            }

            if(isset($post['type']) && empty($post['type'])){
                return json(['message' => '请选选择区域类型'], 200);
            }

            return $this->regionAddEdit($post);

        }catch (\Exception $e){
            return json($e->getMessage().$e->getFile().$e->getLine(),400);
        }
    }



    /**
     *区域分组 添加,编辑
     *
     */
    protected function  regionAddEdit($post)
    {
        if(!isset($post['region_template_id'])){
            //验证
            $check = $this->checkParams($post,'add_region_group');

            if($check){
                return $check;
            }
        }

        $uid=Common::getUserInfo($this->request) ? Common::getUserInfo($this->request)['user_id'] : 0;

        $post['create_id'] = $uid;
        $service = new ExpressHelper();
        $result = $service->addRegionGroup($post);

        if(empty($result['status'])){
            return json(['message'=>$result['message']],200);
        }

        return json(['message'=>$result['message']],400);
    }


    /**
     * @title 编辑区域模板
     * @url /publish/express/edit-region-template
     * @method POST
     * @return \think\response\Json
     */
    public function editRegionTemplate(Request $request)
    {
        try{

            $post = $request->post();

            if(empty($post)){
                return json(['message'=>' 请提交数据'],500);
            }

            if(isset($post['id']) && empty($post['id'])){
                return json(['message' => '模板id必填'], 200);
            }

            if(isset($post['type']) && empty($post['type'])){
                return json(['message' => '请选选择区域类型'], 200);
            }

            return $this->regionAddEdit($post);
        }catch (\Exception $e){
            return json($e->getMessage().$e->getFile().$e->getLine(),400);
        }
    }


    /**
     * @title 编辑分组
     * @url /publish/express/edit-region-group
     * @method POST
     * @return \think\response\Json
     */
    public function editRegionGroup(Request $request)
    {
        try{

            $post = $request->post();

            if(empty($post)){
                return json(['message'=>' 请提交数据'],500);
            }

            return $this->regionAddEdit($post);
        }catch (\Exception $e){
            return json($e->getMessage().$e->getFile().$e->getLine(),400);
        }
    }



    /**
     * @title 删除分组
     * @url /publish/express/delete-region-group
     * @method post
     * @return \think\response\Json
     */
    public function deleteRegionGroup(Request $request)
    {
        try{

            $params = $request->post();

            if(empty($params)){
                return json(['message'=>' 参数id错误'],500);
            }

            $uid=Common::getUserInfo($this->request) ? Common::getUserInfo($this->request)['user_id'] : 0;
            $params['create_id'] = $uid;

            $service = new ExpressHelper();
            $result = $service->regionDel($params);

            return json(['message' => $result], 200);
        }catch (\Exception $e){
            return json($e->getMessage().$e->getFile().$e->getLine(),400);
        }
    }


    /**
     * @title 删除区域模板
     * @url /publish/express/delete-region-template
     * @method post
     * @return \think\response\Json
     */
    public function deleteRegionTemplate(Request $request)
    {
        try{

            $params = $request->post();

            if(empty($params)){
                return json(['message'=>' 参数id错误'],500);
            }

            $uid=Common::getUserInfo($this->request) ? Common::getUserInfo($this->request)['user_id'] : 0;
            $params['create_id'] = $uid;

            $service = new ExpressHelper();
            $result = $service->regionDel($params);

            return json(['message' => $result], 200);
        }catch (\Exception $e){
            return json($e->getMessage().$e->getFile().$e->getLine(),400);
        }
    }

    /**
     * @title 根据模板id获取模板
     * @url /publish/express/region-template-info
     * @method GET
     * @return \think\response\Json
     */
    public function regionTemplateInfo(Request $request)
    {
        try{
            $templateId = $request->get('region_template_id');

            if(!$templateId){
                return json(['message'=>' 参数错误'],500);
            }

            $service = new ExpressHelper();
            $result = $service->regionTemplateInfo($templateId);

            return json($result, 200);
        }catch (\Exception $e){
            return json($e->getMessage().$e->getFile().$e->getLine(),400);
        }
    }


    /**
     * @title 刊登失败批量提交刊登
     * @param Request $request
     * @return \think\response\Json
     * @url /publish/express/batch-add-fail-publish
     * @method POST
     */
    public function batchAddFailPublish(Request $request)
    {
        try{

            $params = $request->post('ids');

            if(empty($params)){
                return json(['message'=>' 参数ids错误'],500);
            }

            $params = explode(',', $params);

            $service = new ExpressHelper();
            $result = $service->batchAddFailPublish($params);

            if($result['status']){
                return json(['message' => $result['message']], 200);
            }

            return json(['message' => $result['message']], 400);
        }catch (\Exception $e){
            return json($e->getMessage().$e->getFile().$e->getLine(),400);
        }
    }



    /**
     * @title 速卖通刊登保存草稿
     * @method post
     * @url /publish/express/save-draft
     * @apiRelate app\goods\controller\GoodsImage::listing
     * @apiRelate app\index\controller\Currency::dictionary
     * @return 返回 product_id
     */
    public function saveDraft(Request $request)
    {
        try{

            //保存草稿分为:新加草稿,更新草稿

            $accountList = Cache::store('AliexpressAccount')->getAllAccounts();
            $uid=Common::getUserInfo($this->request) ? Common::getUserInfo($this->request)['user_id'] : 0;

            $arrData = $request->param();
            if(!isset($arrData['vars']))
            {
                return json(['message'=>'你提交的数据为空'],500);
            }

            $prductData = json_decode($arrData['vars'],true);

            $task_id = isset($arrData['task_id']) ? $arrData['task_id'] : 0;
            foreach($prductData as &$data)
            {

                $data['goods_id'] = $arrData['goods_id'];
                if(isset($arrData['id'])&&$arrData['id']){
                    $data['id'] = $arrData['id'];
                }
                $data['is_plan_publish'] = 0;
                $data['product_id'] = 0;
                $data['goods_spu'] = $arrData['spu'];
                $data['imgs'] = implode(';',$data['imgs']);
                $attr = $data['attr'];
                $sku = $data['sku'];
                unset($data['sku'],$data['attr']);
                $data = FieldAdjustHelper::adjust($data,'publish','HTU');
                $data['attr'] = $attr;
                $data['sku'] = $sku;
                $data['task_id'] = $task_id;
            }

            $ExpressHelper = new ExpressHelper();
            //数据检测，并且返回刊登后的本地商品ID，以数组形式返回
            $arrReturn = [
                'success'=>0,
                'fail'=>0,
                'error_message'=>[]
            ];


            foreach ($prductData as $value)
            {

                $result = $ExpressHelper->saveDraftData($value,$uid);

                //添加商品禁止上架
                if($result['status'] < 0){
                    return json(['message'=>'商品禁止上架'],400);
                }

                if($result['status']===true)
                {
                    $arrReturn['success']++;
                }
                else
                {
                    $arrReturn['fail']++;
                    $arrReturn['error_message'][] = $accountList[$value['account_id']]['code'];
                }
            }
            $msg = '成功'.$arrReturn['success'].',失败'.$arrReturn['fail'].'。'.implode(';',$arrReturn['error_message']);
            return json(['message'=>$msg],200);
        }catch (JsonErrorException $exp){
            throw new JsonErrorException($exp->getFile().$exp->getLine().$exp->getMessage());
        }

    }


    /**
     * @title 刊登队列批量提交刊登
     * @param Request $request
     * @return \think\response\Json
     * @url /publish/express/batch-add-wait-publish
     * @method POST
     */
    public function batchAddWaitPublish(Request $request)
    {
        try{

            $params = $request->post('ids');

            if(empty($params)){
                return json(['message'=>' 参数ids错误'],500);
            }

            $service = new ExpressHelper();
            $result = $service->batchAddWaitPublish($params);

            if($result['status']){
                return json(['message' => $result['message']], 200);
            }

            return json(['message' => $result['message']], 400);
        }catch (\Exception $e){
            return json($e->getMessage().$e->getFile().$e->getLine(),400);
        }
    }



    /**
     * @title 未刊登侵权信息
     * @param Request $request
     * @return \think\response\Json
     * @url /publish/express/goods-tort-info
     * @method POST
     */
    public function goodsTortInfo(Request $request)
    {
        $goods_id = $request->post('goods_id');

        if(empty($goods_id)) {
            return json(['message'=>' 参数goods_id错误'],500);
        }

        $service = new AliProductHelper();
        $result = $service->goodsTortInfo($goods_id);
        return json($result);
    }


    /**
     * @title 修复线上速卖通刊登异常数据
     * @param Request $request
     * @return \think\response\Json
     * @url /publish/express/fail-publish-save
     * @method GET
     */
    public function failPublishSave(Request $request)
    {

        set_time_limit(0);
        $service = new ExpressHelper();

        $params = $request->param();

        if(empty($params)) {
            return;
        }
   
        $result = $service->failPublishSave($params);

        if($result['status']){
            return json(['message' => $result['message']], 200);
        }

        return json(['message' => $result['message']], 400);
    }


    /**
     * @title 速卖通每日刊登列表
     * @param Request $request
     * @return \think\response\Json
     * @url /publish/express/everyday-publish
     * @method GET
     */
    public function everyDayPublish(Request $request)
    {
        try{

            $params = $request->param();

            $service = new AliexpressPublishTaskHelper();
            $result = $service->everydayPublish($params);

            if($result){
                return json($result, 200);
            }

            return json(['message' => $result['message']], 400);
        }catch (\Exception $e){
            return json($e->getMessage().$e->getFile().$e->getLine(),400);
        }
    }


    /**
     * @title 每日刊登导出
     * @param Request $request
     * @return \think\response\Json
     * @url /publish/express/everyday-publish-export
     * @method GET
     */
    public function everyDayPublishExport(Request $request)
    {
        try {
            $ids = $request->get('ids');
            if (empty($ids)) {
                return json(['message' => '导出参数ids为空'], 400);
            }

            $service = new AliexpressPublishTaskHelper();
            $result = $service->everyDayPublishExport($ids);

            if(isset($result['message'])) {
                $result['message'] = '导出成功';
            }
            return json($result);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title 同步分类属性
     * @param Request $request
     * @url /publish/express/sync-category-attr/:category_id
     * @method GET
     */
    public function syncCategoryAttr(Request $request)
    {
        try{
            $categoryId = $request->param('category_id');
            if(empty($categoryId)) {
                return json(['message' => '请选择分类'], 400);
            }

            $server = new ExpressHelper();
            $result = $server->syncCategoryAttr($categoryId);
            return json($result);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }

    }
}
