<?php
namespace app\report\controller;

use app\common\controller\Base;
use app\report\service\StatisticShelf;
use think\Request;
use think\Validate;

/**
 * @module 报表系统
 * @title 上架刊登spu统计
 * @url report/publish-by-shelf
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/8/24
 * Time: 17:43
 */
class PublishByShelf extends Base
{
    protected $shelfService;



    protected function init()
    {
        if(is_null($this->shelfService)){
            $this->shelfService = new StatisticShelf();
        }
    }

    /**
     * @title 列表详情
     * @param Request $request
     * @return \think\response\Json
     * @apiFilter app\report\filter\ChannelByChannelFilter
     */
    public function index(Request $request)
    {
        $page = $request->get('page',1);
        $pageSize = $request->get('pageSize',10);
        $params = $request->param();
        $result = $this->shelfService->lists($page,$pageSize,$params);
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
        $result = $this->shelfService->getSpuMessage($page,$pageSize,$params);
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
            $res = $this->shelfService->applyExport($params);
            return json($res);
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

    /**
     * @title 获取spu刊登统计列表
     * @url spu
     * @method get
     */
    public function spuStatistic(Request $request)
    {
        $params = $request->param();
        $res = $this->shelfService->spuStatistic($params);
        return json($res,$res['result']?200:500);
    }

    /**
     * @title 获取刊登的账号刊登次数
     * @url spu/account-detail
     * @method get
     * @param Request $request
     * @return \think\response\Json
     */
    public function getAccountDetail(Request $request)
    {
        try {
            $params = $request->param();
            $res = $this->shelfService->getAccountDetail($params);
            return json($res,$res['result']?200:500);
        } catch (\Exception $e) {
            return json(['result'=>false, 'message'=>$e->getMessage()], 500);
        }
    }
    
}