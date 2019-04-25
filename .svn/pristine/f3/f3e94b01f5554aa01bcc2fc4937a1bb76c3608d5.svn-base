<?php
namespace app\carrier\controller;

use think\Controller;
use think\Exception;
use think\Request;
use app\common\controller\Base;
use think\Db;
use app\common\cache\Cache;

use app\carrier\service\Shipping as ShippingService;
use app\order\service\PackageService;

use app\common\model\OrderPackage;


use app\common\service\Common as CommonService;


/**
 * Class 
 * @package app\goods\controller
 */
class Shipping extends Base
{
    
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {   
        set_time_limit(0);    
        $request = Request::instance();
        
        
        
        $packageId = $request->get('id', 1);
        
       // $packageId = 950686853631230016;
        if(!is_numeric($packageId)){
           return json(['message' => '参数错误'],400);
        }
        $packages = [];
        if ($packageId) {
            $orderPackageModel = new OrderPackage();
            $package           = $orderPackageModel->field('id,warehouse_id,shipping_id')->where(['id' => $packageId,'shipping_number' => ''])->find();
            $packages      = PackageService::detail($package['shipping_id'],$packageId, 1);
        }
        $data = [];
             
        if (empty($packages)) {
            return json(['message'=>'包裹不存在.'], 400);
        }
             
        $ShippingService = new ShippingService();
        $re = $ShippingService->uploadShipping($packageId);
        print_r($re);
        exit;
        
        
    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        $params = $request->post();        
        return json(['message' => intval($id)], 200);
       
    }

    /**
     * 显示指定的资源
     *
     * @param  int $id
     * @return \think\Response
     */
    public function read($id)
    {
        if(!is_numeric($id)){
            return json(['message' => '参数错误'],400);
        }
        return json(['message' => ''],400);        

    }
    
    /**
     * 显示编辑资源表单页.
     *
     * @param  int $id
     * @return \think\Response
     */
    public function edit($id)
    {
        if(!is_numeric($id)){
            return json(['message' => '参数错误'],400);
        }
        return json(['message' => intval($id)], 200);
    }

    /**
     * 保存更新的资源
     * @param  \think\Request $request
     * @param  int $id
     * @return \think\Response
     */
    public function update(Request $request,$id)
    {
        if(!is_numeric($id)){
            return json(['message' => '参数错误'],400);
        }
        return json(['message' => intval($id)], 200);
    }

    /**
     * 删除指定资源
     * @param  int $id
     * @return \think\Response
     */
    public function delete($id)
    {
        if(!is_numeric($id)){
            return json(['message' => '参数错误'],400);
        }
        return json(['message' => intval($id)], 200);
    }

   
}
