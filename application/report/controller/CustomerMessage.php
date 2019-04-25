<?php
namespace app\report\controller;

use app\common\controller\Base;
use app\report\service\StatisticMessage;
use think\Request;
use think\Validate;

/**
 * @module 报表系统
 * @title 客服业绩统计
 * @url report/customer-message
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/8/24
 * Time: 17:43
 */
class CustomerMessage extends Base
{
    protected $messageService;



    protected function init()
    {
        if(is_null($this->messageService)){
            $this->messageService = new StatisticMessage();
        }
    }

    /**
     * @title 列表详情
     * @param Request $request
     * @return \think\response\Json
     * @apiRelate app\warehouse\controller\Warehouse::warehouses
     * @apiRelate app\goods\controller\Category::lists
     */
    public function index(Request $request)
    {
        $page = $request->get('page',1);
        $pageSize = $request->get('pageSize',10);
        $params = $request->param();
        $result = $this->messageService->lists($page,$pageSize,$params);
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
            $this->messageService->applyExport($params);
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

    /**
     * @title 客服账号列表
     * @method GET
     * @url customer
     * @return \think\Response
     */
    public function getCustomer(Request $request)
    {
        $channel_id = $request->get('channel_id',1);
        $result = $this->messageService->getCustomer($channel_id);
        return json($result, 200);
    }
}