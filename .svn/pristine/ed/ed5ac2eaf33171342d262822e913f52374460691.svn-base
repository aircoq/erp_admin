<?php
namespace app\irobotbox\controller;

use app\common\controller\Base;
use app\irobotbox\service\ProductHelper;
use app\irobotbox\task\syncCategoryInfo;
use think\Request;
use think\Db;
use app\irobotbox\task\syncProductImages;
use app\irobotbox\task\syncProductInventory;
use app\irobotbox\task\syncProductSupplierPrice;
use app\irobotbox\task\syncWareHouse;
use app\irobotbox\task\downLoadImg;
use app\irobotbox\task\syncProcurementDetails;

/**
 * @module 获取赛和erp数据
 * @title 获取赛和erp数据
 * @author RondaFul
 * @url /Irobotbox
 */

class Product extends Base
{
	/**
     * @title 获取商品信息
     * @description 获取商品信息
     * @author zengsh
     * @url Product/GetProducts
     */
    public function GetProducts()
    {
        $task = new syncCategoryInfo();
        $task->execute();
    }

    /**
     * @title 获取商品详细信息
     * @description 获取商品详细信息
     * @author zengsh
     * @url Product/GetProductClass
     */
    public function GetProductClass()
    {
        $task = new syncCategoryClass();
        $task->execute();
    }

    /**
     * @title 获取商品图片
     * @description 获取商品图片
     * @author zengsh
     * @url Product/GetProductImages
     */
    public function GetProductImages()
    {
        $task = new syncProductImages();
        $task->execute();
    }

    /**
     * @title 获取商品库存
     * @description 获取商品库存
     * @author zengsh
     * @url Product/GetProductInventory
     */
    public function GetProductInventory()
    {
        $task = new syncProductInventory();
        $task->execute();
    }

    /**
     * @title 获取商品采购信息
     * @description 获取商品采购信息
     * @author zengsh
     * @url Product/GetProductSupplierPrice
     */
    public function GetProductSupplierPrice()
    {
        $task = new syncProductSupplierPrice();
        $task->execute();
    }

    /**
     * @title 获取仓库信息
     * @description 获取仓库信息
     * @author zengsh
     * @url Product/GetWareHouseList
     */
    public function GetWareHouseList()
    {
        $task = new syncWareHouse();
        $task->execute();
    }

    /**
     * @title 下载图片
     * @description 下载图片
     * @author zengsh
     * @url Product/downLoadImg
     */
    public function downLoadImg()
    {
        $task = new DownLoadImg();
        $task->execute();
    }

    /**
     * @title 获取商品条码
     * @description 获取商品条码
     * @author zengsh
     * @url Product/getProductBar
     */
    public function getProductBar()
    {
        $task = new syncProcurementDetails();
        $task->execute();
    }

}
