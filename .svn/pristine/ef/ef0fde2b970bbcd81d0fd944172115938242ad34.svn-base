<?php
namespace app\publish\controller;

use app\common\exception\JsonErrorException;
use app\publish\service\AmazonCategoryHelper;
use think\Request;
use think\Response;
use think\Cache;
use think\Db;
use think\Exception;
use think\Validate;
use app\publish\service\AmazonAttriubteService;
use app\common\controller\Base;
use app\common\service\Common as CommonService;
use app\publish\service\AmazonCategoryXsdService;
use app\publish\service\AmazonElement as AmazonElementService;

/**
 * @module 刊登属性匹配
 * @title Amazon刊登属性
 * @author
 * @url /amazon-attribute/
 * Class AmazonAttributeMatch
 * @package app\publish\controller
 */
class AmazonAttribute extends Base
{

    private  $_attributeService;
    private  $_categoryService;
    protected function init()
    {
        $this->_attributeService = new AmazonAttriubteService();
        $this->_categoryService = new AmazonCategoryHelper();
    }


    /**
     * @title 属性匹配
     * @url /amazon-attribute/match
     * @method post
     * @return \think\Response
     * @apiRelate app\publish\controller\AmazonAttribute::match
     */
    public function match(Request $request)
    {
        try{
            $params = $request->param();
            $categoryId = isset($params['second_category_id']) && $params['second_category_id'] ? $params['second_category_id'] : $params['first_category_id'];
            $params['category_id'] = $categoryId;
            $this->_categoryService->checkParams($params,'search');
            $params['cat_id'] = $categoryId;
            $amazonAttributelService = new AmazonAttriubteService();
            $amazonAttributelService->saveExcelAttributeImport($params);
            return json(["message" => '上传excel模板成功'],200);
        }catch (Exception $e){
            return $e;
        }
    }



    /**
     * @title 导入XSD文件并解析入库
     * @url /amazon-attribute/import
     * @method get
     * @return \think\Response
     */
    public function importXsd(){
        set_time_limit(0);
        $amazonAttributelService = new AmazonCategoryXsdService();
        $file = "https://images-na.ssl-images-amazon.com/images/G/01/rainier/help/xsd/release_1_9/Product.xsd";
        $elementName = 'Product';
        $amazonAttributelService->saveXsd($file,$elementName);
    }
    /*
    public function importXsd()
    {
        set_time_limit(0);
        $amazonAttributelService = new \app\publish\service\AmazonCategoryXsdService();
        $amazonAttributelService->updateSiteElement();
    }
    */
    /**
     * @title 更新分类元素属站点
     * @method get
     * @url /amazon-attribute/elementSite
     */
    public function elementSite()
    {
        $amazonAttributelService = new AmazonCategoryXsdService();
        $amazonAttributelService->updateSiteElement();
    }
    
    /**
     * @title 获取产品基础信息
     * @method get
     * @url /amazon-attribute/productBase
     */
    public function productBase()
    {
        $amazonElementService = new AmazonElementService();
        $return = $amazonElementService->getProductBase();
        return json($return, 200);
    }

    
    /**
     * @title 亚马逊属性配置展示
     * @url /amazon-attribute/config
     * @method get
     * @return \think\Response
     */
    public function getXsdAttributeConfig(Request $request){
        $amazonAttributelService = new AmazonAttriubteService();
        $post = $request->param();
        $page = $post['page'];
        $pageSize = $post['pageSize'];
        $configs = $amazonAttributelService->getXsdCategoryConfig($post,$page,$pageSize);
        return json($configs);
    }



    /**
     * @title 获取XSD模板分类
     * @url /amazon-attribute/xsd-category
     * @method get
     * @param Request $request
     * @return json
     */
    public function getXsdCategory(Request $request){
        $attributeList = $this->_categoryService->getBaseAttributeList('US',243);

        return (json($attributeList));
        exit;

        $fid = $request->param('category_id',0);
        $data = $this->_categoryService->getAmazonXsdCategoryByFid($fid);
        return json($data);
    }


    /**
     * @title 保存站点属性配置
     * @url /amazon-save-xsd-attribute
     * @method post
     * @param Request $request
     * @return json
     */
    public function saveSelectedAttribute(Request $request){
        $post = $request->param();
        try{
            $uid = CommonService::getUserInfo($request)['user_id'] ?? 0;
            $post['attributes'] = json_decode($post['attributes'],true);
            $requestData = array(
                'uid' => $uid,
                'site'  => $post['site'],
                'category_id' => isset($post['second_category_id']) && $post['second_category_id'] ? $post['second_category_id'] : $post['first_category_id'],
                'attributes' => $post['attributes'],
            );
            $this->_categoryService->saveXsdAttributeByCatId($requestData);
            return json("保存属性设置成功");
        }catch (Exception $e){
            return json($e->getMessage(),400);
        }
    }


    /**
     * @title 获取XSD模板属性
     * @url /amazon-xsd-attribute
     * @method post
     * @param Request $request
     * @return json
     */
    public function getSelectAttribute(Request $request){
        try{
            $post = $request->param();
            $data = array();
            $categoryId = isset($post['second_category_id']) && $post['second_category_id'] ? $post['second_category_id'] : $post['first_category_id'];
            $post['category_id'] = $categoryId;
            $this->_categoryService->checkParams($post,'search');
            $data['base'] =  $this->_categoryService->getBaseAttributeList($post['site'],$post['category_id']);
            $data['common'] =  $this->_categoryService->getCommonAttributeList($post['site'],$post['category_id']);
            $data['variant'] =  $this->_categoryService->getVariantAttributeList($post['site'],$post['category_id']);

            $data["category"] =  $this->_categoryService->getCategoryTreeList();
            $data["fix"] =  $this->_categoryService->getBaseAttribute();
            return json($data);
        }catch (Exception $e){
            return $e;
        }

    }


    /**
     * @title 获取XSD模板分类树
     * @url /amazon-xsd-category-tree
     * @method get
     * @param Request $request
     * @return json
     */
    public function getXsdCategoryTree(Request $request){
        $data = array();
        $data["category"] = $this->_categoryService->getCategoryTreeList();
        $data["fix"] = $this->_categoryService->getBaseAttribute();
        return json($data);
    }


    public function getXsdCategoryConfig(Request $request){
        $where = [];
    }

}