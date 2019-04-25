<?php
/**
 * Created by PhpStorm.
 * User: rondaful_user
 * Date: 2019/2/26
 * Time: 14:06
 */

namespace app\publish\controller;


use app\publish\service\EbayBestOfferService;
use think\Exception;
use think\Request;


/**
 * @module ebay Best Offer管理
 * @title Ebay Best Offer 管理
 * @author wlw2533
 */
class EbayBestOffer
{
    private  $service;

    public function __construct()
    {
        $this->service = new EbayBestOfferService();
    }

    /**
     * @title 获取best offers列表
     * @url /ebay/best-offers
     * @method GET
     */
    public function index(Request $request)
    {
        try {
            $params = $request->param();
            $res = $this->service->index($params);
            return json($res);
        } catch (\Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }

    /**
     * @title 同步best offer
     * @url /ebay/best-offers/sync
     * @method post
     * @param Request $request
     * @return \think\response\Json
     */
    public function sync(Request $request)
    {
        try {
            $params = $request->param();
            $res = $this->service->sync($params);
            return json($res);
        } catch (\Exception $e) {
            return json(['message'=>$e->getMessage()], 500);
        }
    }

    /**
     * @title 删除best offer
     * @url /ebay/best-offers/batch
     * @method delete
     * @param Request $request
     * @return \think\response\Json
     */
    public function del(Request $request)
    {
        try {
            $ids = explode(',',$request->param('ids'));
            $this->service->del($ids);
            return json(['message'=>'操作成功']);
        } catch (\Exception $e) {
            return json(['message'=>$e->getMessage()], 500);
        }
    }

    /**
     * @title 处理best offer
     * @url /ebay/best-offers/batch
     * @method post
     * @param Request $request
     * @return \think\response\Json
     */
    public function deal(Request $request)
    {
        try {
            $params = $request->param();
            if (!($params['action']??'')) {
                throw new Exception('不明确的操作');
            }
            $this->service->deal($params);
        } catch (\Exception $e) {
            return json(['message'=>$e->getMessage()], 500);
        }
    }



}