<?php
/**
 * Created by PhpStorm.
 * User: TOM
 * Date: 2017/8/9
 * Time: 20:06
 */

namespace app\carrier\controller;


use app\carrier\exception\AliexpressAddressException;
use app\carrier\service\AliSellerAddressService;
use app\common\controller\Base;
use think\Exception;
use think\Request;

/**
 * @module 仓库系统
 * @title 速卖通线上发货地址
 * @url /ali-address
 * @package app\carrier\controller
 * @author Tom
 */
class AliexpressAddress extends Base
{
    private $_service;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->_service = new AliSellerAddressService();
    }

    /**
     * @title 获取速卖通地址信息
     * @apiParam name:account_id type:int desc:速卖通账号ID
     * @apiParam name:shipping_id type:int desc:物流渠道ID require:true
     * @apiReturn pickup:揽收地址信息@
     * @pickup address_id:地址ID name:联系人 country:国家 province:省 city:城市
     * @apiReturn refund:退货地址信息@
     * @refund address_id:地址ID name:联系人 country:国家 province:省 city:城市
     * @apiReturn sender:发件地址信息@
     * @sender address_id:地址ID name:联系人 country:国家 province:省 city:城市
     * @apiReturn default_pickup:已选中揽收地址
     * @apiReturn default_refund:已选中退货地址
     * @apiReturn default_sender:已选中发件地址
     * @apiReturn account_id:速卖通账号ID
     * @apiReturn account_code:速卖通账号code
     * @param Request $request
     * @return \think\response\Json
     */
    public function index(Request $request)
    {
        try{
            $params = $request->param();
            if(!param($params,'shipping_id',false)){
                throw new AliexpressAddressException('参数错误');
            }
            $account_id = isset($params['account_id'])?$params['account_id']:0;
            $address = $this->_service->getAliSellerAddress($account_id,$params['shipping_id']);
            return json($address,200);
        }catch (Exception $ex){
            throw new AliexpressAddressException($ex->getMessage().$ex->getLine().$ex->getFile());
        }
    }

}