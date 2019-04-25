<?php
/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2017/6/21
 * Time: 16:33
 */

namespace app\publish\interfaces;


use app\common\interfaces\DashboradPublish;
use app\publish\service\AliProductHelper;

class AliexpressStatistics implements DashboradPublish
{
    //获取未刊登数量
    public function getNotyetPublish()
    {
        $helpService = new AliProductHelper();
        return $helpService->getUnpublishCount();
    }

    //获取刊登中数量
    public function getListingIn()
    {
        $helpService = new AliProductHelper();
        $where = [
            'status'=>['in',[3,1,0]]
        ];
        return $helpService->getProductCount($where);
    }

    //获取刊登异常数量
    public function getExceptionListing()
    {
        $helpService = new AliProductHelper();
        $where = [
            'status'=>4
        ];
        return $helpService->getProductCount($where);
    }

    //获取停售待下架数量
    public function getStopSellWaitRelisting()
    {
        $helpService = new AliProductHelper();
        return $helpService->getUnsaleCount();
    }
}