<?php

/**
 * Description of AliAuthCategory
 * @datetime 2017-6-6  10:54:54
 * @author joy
 */

namespace app\publish\controller;
use app\common\controller\Base;
use app\common\exception\JsonErrorException;
use app\publish\service\AliexpressCategoryService;
use think\Exception;
use think\Request;
/**
 * @title 速卖通授权分类
 * Class AliAuthCategory
 * @package app\listing\controller
 */
class AliAuthCategory extends Base{
    private  $service;
    protected function init() 
    {
        $this->service = new AliexpressCategoryService;
    }

	/**
	 * @title 获取模板内容
	 * @url /aliexpreee-product-template-content
	 * @method get
	 * @author Joy <joy_qhs@163.com>
	 * @param Request $request
	 * @return json
	 */

    public function detail(Request $request)
    {
    	try{
		    $id = $request->param('id');
		    if(empty($id))
		    {
			    return json(['message'=>'id不能为空'],500);
		    }
		    $data = (new AliexpressCategoryService())->getTemplateContent($id);
		    return json($data);
	    }catch (Exception $exp){
    		throw new JsonErrorException($exp->getFile().$exp->getLine().$exp->getMessage());
	    }


    }
    /**
     * @title 速卖通店铺准入行业列表
     * @url /aliexpreee-category-map-list
     * @method get
     * @author Joy <joy_qhs@163.com>
     * @apiRelate app\goods\controller\Category::index
     * @apiRelate app\order\controller\Order::account
     * @apiRelate app\publish\controller\Express::Getpostcategorybyid
     * @apiFilter app\publish\filter\AliexpressFilter
     * @apiFilter app\publish\filter\AliexpressDepartmentFilter
     * @param Request $request
     * @return json
     */
    public  function lists(Request $request)
    {
        $page = $request->param('page',1);
        
        $pageSize = $request->param('pageSize',100);
        
        $param = $request->instance()->param();
        
        $response = (new AliexpressCategoryService)->getList($page,$pageSize,$param);
        
        return json($response);      
    }
    /**
     * @title 新增刊登分类
     * @url /add-publish-ali-category
     * @author Joy <joy_qhs@163.com>
     * @method post
     * @param Request $request
     * @return type
     */
    public  function add(Request $request)
    {
        $post = $request->instance()->param();
        
        $response = $this->service->addPublishCategory($post);
        
        if($response['result'])
        {
            return json(['message'=>$response['message']]);
        }else{
            return json(['message'=>$response['message']],500);
        }
        
    }
    /**
     * @title 编辑刊登分类
     * @url /edit-publish-ali-category
     * @author Joy <joy_qhs@163.com>
     * @method post
     * @param Request $request
     * @return type
     */
    public  function edit(Request $request)
    {
        $post = $request->instance()->param();
         
        $response = $this->service->editPublishCategory($post);
        
        if($response['result'])
        {
            return json(['message'=>$response['message']]);
        }else{
            return json(['message'=>$response['message']],500);
        }
    }
    /**
     * @title 删除速卖通授权分类
     * @url /aliexpress-auth-category-delete
     * @method post
     * @author Joy <joy_qhs@163.com>
     * @param Request $request
     */
    public  function del(Request $request)
    {
        $post = $request->instance()->param('ids');
         
        if(empty($post))
        {
            return json(['message'=>'请选择你要删除的授权类目'],500);
        }
        $ids = explode(';', $post);  
        $message = $this->service->delete($ids);
        return json(['message'=>$message]);
    }
    
     /**
     * @title 编辑速卖通授权分类
     * @url /aliexpress-auth-category-edit
     * @method get
     * @author Joy <joy_qhs@163.com>
     * @param Request $request
     */
    public  function getEditData(Request $request)
    {
        $id = $request->instance()->param('id');
        if(empty($id))
        {
            return json(['message'=>'请选择你要编辑的类目'],500);
        }
        $data = $this->service->getEditData($id);
        return json(['data'=>$data]);
    }
    
    /**
     * @title 速卖通信息模板列表
     * @url /aliexpress-product-template-list
     * @apiRelate app\order\controller\Order::account
     * @method get
     * @author Joy <joy_qhs@163.com>
     * @param Request $request
     */
    public  function product_template_list()
    {
         
         $param = $this->request->instance()->param();
         $page = $this->request->param('page',1);
         $pageSize = $this->request->param('pageSize',30);
         $response = $this->service->getProductTemplates($param, $page, $pageSize);
         return json($response);
    }
    /**
     * @title 创建速卖通关联信息模板
     * @url /create-relation-product-template
     * @method post
     * @author joy <joy_qhs@163.com>
     * @return json
     */
    public  function create_relation_product_template()
    {
        $post = $this->request->instance()->param();
        $res = $this->service->create_relation_product_template($post);
        if($res['result'])
        {
            return json(['message'=>$res['message']]);
        }else{
            return json(['message'=>$res['message']],500);
        }
        
    }
    
    /**
     * @title 创建速卖通自定义信息模板
     * @url /create-custom-product-template
     * @method post
     * @author joy <joy_qhs@163.com>
     * @return json
     */
    public  function create_custom_product_template()
    {
        $post = $this->request->instance()->post();
        $res = (new AliexpressCategoryService)->create_custom_product_template($post);
        if($res['result'])
        {
            return json($res);
        }else{
            return json(['message'=>$res['message']],500);
        }
    }
    /**
     * @title 关联信息模板预览
     * @url /review
     * @method post
     * @return json
     */
    public  function review()
    {
        $product_ids = $this->request->param('product_ids');
        $data = $this->service->getTemplateHtml(explode(',',$product_ids));
        return json(['data'=>$data]);
    }
    /**
     * @title 删除速卖通信息模板
     * @url /delete-product-template
     * @method post
     * @return json
     */
    public function deleteProductTemplate()
    {
        $post = $this->request->instance()->param('ids');
         
        if(empty($post))
        {
            return json(['message'=>'请选择你要删除的模板'],500);
        }
        $ids = explode(',', $post);  
        $message = $this->service->delete_product_template($ids);
        return json(['message'=>$message]);
    }
    /**
     * @title 编辑速卖通信息模板
     * @url /edit-product-template
     * @method post
     * @return json
     */
    public  function editProductTemplate()
    {
        $post = $this->request->instance()->param();
        
        $res = (new AliexpressCategoryService)->edit_product_template($post);
        if($res['result'])
        {
             return json(['message'=>$res['message']]);
        }else{
             return json(['message'=>$res['message']],500);
        }
       
    }
    /**
     * @title 获取关联信息模板图片
     * @url /get-relation-template-images
     * @method get
     * @return json
     */
    public  function getRelationTemplateData()
    {
        $product_ids = $this->request->param('product_ids');
        
        $data = $this->service->getRelationTempateImages($product_ids);
        
        return json(['data'=>$data]);
    }
    /**
     * @title 获取关联信息模板和自定义信息模板
     * @url /get-relation-and-custom-template
     * @method get
     * @return json
     */
    public  function getRelationAndCustomTemplate()
    {
        $account_id = $this->request->param('account_id');
        if(empty($account_id))
        {
            return json(['message'=>'账号必填'],500);
        }
        
        $data = $this->service->getRelationAndCustomTemplate($account_id);
        
        return json($data);
    }
    
}
