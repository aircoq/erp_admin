<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-7-5
 * Time: 上午9:44
 */

namespace app\goods\controller;


use app\common\controller\Base;
use app\common\exception\JsonErrorException;
use app\goods\service\GoodsDownloadService;
use think\Exception;
use think\Request;

/**
 * @module 商品系统
 * @title 商品导出刊登平台　
 */
class Download extends Base
{
    /**
     * @title 导出商品到shopee平台
     * @url /goods/download/shopee
     * @method POST
     * @param Request $request
     * @return \think\response\Json
     */
    public function index(Request $request){
       try{
           $ids = $request->param('ids');
           if(empty($ids)){
               throw new JsonErrorException("请选择你要导出的商品");
           }
           $response = GoodsDownloadService::download($ids);
           return json($response);
       }catch (Exception $exp){
            throw new JsonErrorException("{$exp->getFile()};{{$exp->getLine()}};{{$exp->getMessage()}}");
       }
    }
    /**
     * @title 导出商品到discount平台
     * @url /goods/download/discount
     * @method POST
     * @param Request $request
     * @return \think\response\Json
     */
    public function discount(Request $request){
        try{
            $ids = $request->param('ids');
            if(empty($ids)){
                throw new JsonErrorException("请选择你要导出的商品");
            }
            $response = GoodsDownloadService::download($ids,5);
            return json($response);
        }catch (Exception $exp){
            throw new JsonErrorException("{$exp->getFile()};{{$exp->getLine()}};{{$exp->getMessage()}}");
        }
    }
    /**
     * @title 导出商品到walmart平台
     * @url /goods/download/walmart
     * @method POST
     * @param Request $request
     * @return \think\response\Json
     */
    public function walmart(Request $request){
        try{
            $ids = $request->param('ids');
            if(empty($ids)){
                throw new JsonErrorException("请选择你要导出的商品");
            }
            $response = GoodsDownloadService::download($ids,11);
            return json($response);
        }catch (Exception $exp){
            throw new JsonErrorException("{$exp->getFile()};{{$exp->getLine()}};{{$exp->getMessage()}}");
        }
    }
    /**
     * @title 导出商品到lazada平台
     * @url /goods/download/lazada
     * @method POST
     * @param Request $request
     * @return \think\response\Json
     */
    public function lazada(Request $request){
        try{
            $ids = $request->param('ids');
            if(empty($ids)){
                throw new JsonErrorException("请选择你要导出的商品");
            }
            $response = GoodsDownloadService::download($ids,6);
            return json($response);
        }catch (Exception $exp){
            throw new JsonErrorException("{$exp->getFile()};{{$exp->getLine()}};{{$exp->getMessage()}}");
        }
    }
}