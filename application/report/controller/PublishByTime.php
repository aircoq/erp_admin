<?php
namespace app\report\controller;

use app\common\controller\Base;
use app\report\service\StatisticTime;
use think\Request;
use think\Validate;

/**
 * @module 报表系统
 * @title SPU上架时间统计
 * @url report/publish-by-times
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/9/26
 * Time: 17:43
 */
class PublishByTime extends Base
{
    protected $pickingService;



    protected function init()
    {
        if(is_null($this->pickingService)){
            $this->pickingService = new StatisticTime();
        }
    }

    /**
     * @title 列表详情
     * @param Request $request
     * @return \think\response\Json
     */
    public function index(Request $request)
    {
        $page = $request->get('page',1);
        $pageSize = $request->get('pageSize',10);
        $params = $request->param();
        $result = $this->pickingService->lists($page,$pageSize,$params);
        return json($result);
    }

    /**
     * @title 平台列表数据
     * @url channel
     * @method get
     * @return \think\response\Json
     */
    public function channel()
    {
        $result = $this->pickingService->getChannel();
        return json($result);
    }

    /**
     * @title 账号详情数据
     * @url shelf
     * @method get
     * @return \think\response\Json
     */
    public function shelf(Request $request)
    {
        $params = $request->param();
        $result = $this->pickingService->getShelf($params);
        return json($result);
    }


    /**
     * @title 导出
     * @url export
     * @method post
     */
    public function applyExport(Request $request)
    {

        $params = $request->param();
        if(isset($params['goods_list'])){
            unset($params['goods_list']);
        }
        try{
            $this->pickingService->applyExport($params);
            return json(['message'=> '成功加入导出队列']);
        } catch (\Exception $ex) {
            $code = $ex->getCode();
            $msg  = $ex->getMessage();
            if(!$code){
                $code = 400;
                $msg  = '程序内错误';
            }
            return json(['message'=>$msg], $code);
        }
    }

}