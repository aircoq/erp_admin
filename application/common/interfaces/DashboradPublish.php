<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-5-15
 * Time: 上午10:50
 */

namespace app\common\interfaces;

/**
 * Interface 首页刊登接口
 * @package app\common\interfaces
 */
interface DashboradPublish
{
    //获取未刊登数量
    public function getNotyetPublish();

    //获取刊登中数量
    public function getListingIn();
    
    //获取刊登异常数量
    public function getExceptionListing();
    

    //获取停售待下架数量
    public function getStopSellWaitRelisting();
}