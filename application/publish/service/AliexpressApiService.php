<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-3-26
 * Time: 上午9:42
 */

namespace app\publish\service;


use org\Curl;

class AliexpressApiService
{
    private static $apiServiceUrl='http://120.27.143.32/api';


    /**
     * post产品并且返回post成功后的产品的id
     * @param $params
     */
    public static function postaeproduct($params)
    {

        $response = Curl::curlPost(self::$apiServiceUrl,$params);
        return $response;
    }
    /**
     * 商品列表查询接口。主账号可查询所有商品，子账号只可查询自身所属商品。
     * @param $params
     */
    public static function findproductinfolistquery($params)
    {
        $response = Curl::curlPost(self::$apiServiceUrl,$params);
        return $response;
    }
    /**
     * 上架一个或者多个商品，待上架的产品ID通过参数productIds指定，产品ID之间使用英文分号(;)隔开, 最多一次只能上架50个商品
     * @param $params
     */
    public static function onlineaeproduct($params)
    {
        $response = Curl::curlPost(self::$apiServiceUrl,$params);
        return $response;
    }

    /**
     * 商品下架接口。需要下架的商品的通过productIds参数指定，多个商品之间用英文分号隔开。
     * @param $params
     */
    public static function offlineaeproduct($params)
    {
        $response = Curl::curlPost(self::$apiServiceUrl,$params);
        return $response;
    }
    /**
     * 原发编辑商品多语言标题或详描描述（英文版本除外）。试用
     * @param $params
     */
    public static function editmultilanguageproduct($params)
    {
        $response = Curl::curlPost(self::$apiServiceUrl,$params);
        return $response;
    }
    /**
     * 根据商品id查询单个商品的详细信息。
     * @param $params
     */
    public static function findaeproductbyid($params)
    {
        $response = Curl::curlPost(self::$apiServiceUrl,$params);
        return $response;
    }
    /**
     * 编辑产品类目、属性、sku
     * @param $params
     */
    public static function editproductcidattidsku($params)
    {
        $response = Curl::curlPost(self::$apiServiceUrl,$params);
        return $response;
    }

    /**
     * 设置单个产品的产品分组信息，最多设置三十个分组。
     * @param $params
     */
    public static function setgroups($params)
    {
        $response = Curl::curlPost(self::$apiServiceUrl,$params);
        return $response;
    }

    /**
     * 编辑商品的类目属性，用给定的类目属性覆盖原有的类目属性。(试用)
     * @param $params
     */
    public static function editproductcategoryattributes($params)
    {
        $response = Curl::curlPost(self::$apiServiceUrl,$params);
        return $response;
    }
    /**
     * 编辑商品单个SKU的库存信息.
     * @param $params
     */
    public static function editsingleskustock($params)
    {
        $response = Curl::curlPost(self::$apiServiceUrl,$params);
        return $response;
    }

    /**
     * 编辑商品的单个SKU价格信息。
     * @param $params
     */
    public static function editsingleskuprice($params)
    {
        $response = Curl::curlPost(self::$apiServiceUrl,$params);
        return $response;
    }

    /**
     * 编辑商品的单个字段(目前使用api.editSimpleProductFiled这个接口 暂不支持商品分组、商品属性、SKU、服务模板的修改。请注意！)
     * @param $params
     */
    public static function editsimpleproductfiled($params)
    {
        $response = Curl::curlPost(self::$apiServiceUrl,$params);
        return $response;
    }

    /**
    *编辑单个商品的一个或者多个SKU可售库存。将要修改的SKU的库存值保存在skuStocks参数中(Map类型数据)，其中key为SKU ID(字符串), value为对应的库存值(Long型)。     * @param $params
     */
    public static function editmutilpleskustocks($params)
    {
        $response = Curl::curlPost(self::$apiServiceUrl,$params);
        return $response;
    }

    /**
     * 卖家post商品时可选择商品有效期。 每次延长的有效期=post商品时卖家选择的商品有效期
     * @param $params
     * @return mixed
     */
    public static function renewexpire($params){
        $response = Curl::curlPost(self::$apiServiceUrl,$params);
        return $response;
    }
    /**
     * 商品编辑接口（修改任何一个商品信息时，必须将其他所有信息进行填写全面后再提交，否则会出现报错情况出现。）
     * @param $params
     * @return mixed
     */
    public static function editaeproduct($params){
        $response = Curl::curlPost(self::$apiServiceUrl,$params);
        return $response;
    }

    /**
     * 获取某个卖家橱窗商品目前使用情况详情。
     * @param $params
     * @return mixed
     */
    public function getwindowproducts($params){
        $response = Curl::curlPost(self::$apiServiceUrl,$params);
        return $response;
    }
}