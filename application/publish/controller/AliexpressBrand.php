<?php
/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2017/6/26
 * Time: 15:10
 */

namespace app\publish\controller;


use app\common\controller\Base;
use app\publish\service\AliexpressBrandHelper;
use think\Exception;
use think\Request;

/**
 * @title Aliexpress品牌设置管理
 * @url ali-brand/
 * @author Tom
 * Class AliexpressBrand
 * @package app\publish\controller
 */
class AliexpressBrand extends Base
{

    /**
     * @title 获取Aliexpress分类下面所有品牌
     * @url brands
     * @apiParam name:category_id type:int desc:分类ID
     * @apiParam name:account_id type:int desc:平台账号ID
     * @param Request $request
     * @return \think\response\Json
     */
    public function brands(Request $request)
    {
        try{
            $params = $request->param();
            $helper = new AliexpressBrandHelper();
            $brands = $helper->brands($params);
            return json($brands,200);
        }catch(Exception $exception){
            return json($exception->getMessage(),500);
        }


    }

    /**
     * @title 保存品牌设置
     * @method post
     * @url set-brands
     * @apiParam name:category_id type:int desc:分类ID
     * @apiParam name:account_id type:int desc:平台账号ID
     * @apiParam name:brands type string desc:选中的品牌ID，多个用','分隔
     * @param Request $request
     * @return \think\response\Json
     */
    public function setBrands(Request $request)
    {
        try{
            $params = $request->param();
            $helper = new AliexpressBrandHelper();
            $result = $helper->saveAccountBrands($params);
            return json($result,200);
        }catch(Exception $exception){
            return json($exception->getMessage(),500);
        }
    }

    /**
     * @title 获取最新品牌
     * @method post
     * @url syn-brands
     * @apiParam name:account_id type:int desc:平台账号ID
     */
    public function synBrands(Request $request)
    {
        try{
            $params = $request->param();
            $helper = new AliexpressBrandHelper();
            $result = $helper->synBrands($params);

            if(empty($result['status'])) {
                return json(['success'=>$result['message']],400);
            }
            return json(['success'=>$result['message']],200);
            
        }catch(Exception $exception){
            return json($exception->getMessage(),500);
        }
    }

    /**
     * @title 获取产品分组
     * @method get
     * @url /rsync-aliexpress-groups
     * @apiParam name:account_id type:int desc:平台账号ID
     * @return  json
     */
    public function rsyncGroups(Request $request)
    {
        try{
            $params = $request->param();
            $helper = new AliexpressBrandHelper();
            $response = $helper->rsyncGroups($params);
            if($response['result'])
            {
                return json($response);
            }else{
                return json($response,400);
            }

        }catch(Exception $exception){
            return json($exception->getMessage(),400);
        }
    }

    /**
     * @title 获取运费模板
     * @method get
     * @url /rsync-aliexpress-transport
     * @apiParam name:account_id type:int desc:平台账号ID
     * @return  json
     */
    public function rsyncTransport(Request $request)
    {
        try{
            $params = $request->param();
            $helper = new AliexpressBrandHelper();
            $response = $helper->rsyncTransport($params);
            if($response['result'])
            {
                return json($response);
            }else{
                return json($response,400);
            }

        }catch(Exception $exception){
            return json($exception->getMessage(),400);
        }
    }

    /**
     * @title 获取服务模板
     * @method get
     * @url /rsync-aliexpress-promise
     * @apiParam name:account_id type:int desc:平台账号ID
     * @return  json
     */
    public function rsyncPromise(Request $request)
    {
        try{
            $params = $request->param();
            $helper = new AliexpressBrandHelper();
            $response = $helper->rsyncPromise($params);
            if($response['result'])
            {
                return json($response);
            }else{
                return json($response,400);
            }

        }catch(Exception $exception){
            return json($exception->getMessage(),400);
        }
    }
}