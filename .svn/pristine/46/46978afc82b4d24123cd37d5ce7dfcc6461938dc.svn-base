<?php
namespace app\report\controller;

use app\common\controller\Base;
use app\report\service\StatisticPicking;
use think\Request;
use think\Validate;

/**
 * @module 报表系统
 * @title 下架刊登spu统计
 * @url report/publish-by-picking
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/8/24
 * Time: 17:43
 */
class PublishByPicking extends Base
{
    protected $pickingService;



    protected function init()
    {
        if(is_null($this->pickingService)){
            $this->pickingService = new StatisticPicking();
        }
    }

    /**
     * @title 列表详情
     * @param Request $request
     * @apiFilter app\report\filter\ChannelByChannelFilter
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
     * @title sup详情
     * @url sup
     * @method get
     * @param Request $request
     * @return \think\response\Json
     */
    public function sup(Request $request)
    {
        $page = $request->get('page',1);
        $pageSize = $request->get('pageSize',10);
        $params = $request->param();
        if(!$params['dateline'] || !$params['channel_id'] || !$params['account_id'] || !$params['shelf_id']){
            return json(['message'=>'缺少必要参数'], 400);
        }
        $result = $this->pickingService->getSpuMessage($page,$pageSize,$params);
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