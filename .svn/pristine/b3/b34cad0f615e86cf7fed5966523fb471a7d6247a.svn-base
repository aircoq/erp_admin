<?php
namespace app\customerservice\controller;

use app\common\controller\Base;
use app\customerservice\service\aliexpress\AliEvaluateHelp;
use app\customerservice\validate\EvaluateValidate;
use think\Exception;
use think\Request;

/**
 * @module 客服管理
 * @title 速卖通评价
 * @url /ali-evaluate
 * @package app\customerservice\controller
 * @author Tom
 */
class AliexpressEvaluate extends Base
{
    private $_validate;
    
    public function __construct(Request $request = null) {
        parent::__construct($request);
        $this->_validate = new EvaluateValidate();
    }

    /**
     * @title 评价列表
     * @apiRelate app\order\controller\Order::account
     * @apiRelate app\customerservice\controller\MsgTemplate::getTemplates
     * @apiFilter app\customerservice\filter\AliexpressAccountFilter
     */
    public function index()
    {
        try {
            $request = Request::instance();
            $page = $request->param('page', 1);
            $pageSize = $request->param('pageSize', 10);
            $params = $request->param();
            $help = new AliEvaluateHelp();
            $result = $help->getList($page, $pageSize, $params);
            return json($result, 200);
        } catch (Exception $ex) {
            return json(['message'=>$ex->getMessage(),500]);
        }
    }

    /**
     * @title 评价明细
     * @param $id
     * @return \think\response\Json
     */
    public function read($id)
    {
        try {
            $help = new AliEvaluateHelp();

            if(!$id){
                return json(['message'=>'参数错误'],400);
            }
            $result = $help->getEvaluateDetail($id);
            return json($result,200);
        } catch (Exception $ex) {
            return json(['message'=>$ex->getMessage()],500);
        }
    }
    
    /**
     * @title 回评
     * @method post
     * @url evaluate
     * @return type
     */
    public function evaluate()
    {
        try {
            $request = Request::instance();
            $params = $request->param();
            $check = $this->checkParams($params,'evaluate');
            if(TRUE !== $check){
                return json(['message'=>$check],400);
            }
            $help = new AliEvaluateHelp();
            $evaluate_info = $help->getEvaluateDetail($params['id']);
            if(empty($evaluate_info)){
                return json(['message'=>'未找到相关信息'],400);
            }
            $result = $help->evaluate($evaluate_info, $params['score'], $params['content']);
            if(!$result['status']){
                return json(['message'=>((isset($result['msg'])&&$result['msg'])?$result['msg']:'操作失败')],400);
            }
            return json(['message'=>'操作成功'],200);
        } catch (Exception $ex) {
            return json(['message'=>$ex->getMessage(),500]);
        }
    }
    
    /**
     * @title 批量回评
     * @method post
     * @url batchEvaluate
     * @return type
     */
    public function batchEvaluate()
    {
        try {
            $request = Request::instance();
            $params  = $request->param();
            $scene   = $params['is_all']==1 ? 'all' : 'batch';
            $check = $this->checkParams($params,$scene);
            if(TRUE !== $check){
                return json(['message'=>$check],400);
            }
            $help = new AliEvaluateHelp();
            $help->batchEvaluate($params['score'], $params['content'],$params['ids'],$params['is_all']);            
            return json(['message'=>'操作成功,等待中'],200);
        } catch (Exception $ex) {
            return json(['message'=>$ex->getMessage(),500]);
        }
    }
    
    /**
     * @title 追加评论
     * @method post
     * @url append
     * @return type
     */
    public function appendEvaluate()
    {
        try {
            $request = Request::instance();
            $params = $request->param();            
            // 验证数据
            $check = $this->checkParams($params, 'append');
            if(TRUE !== $check){
                return json(['message'=>$check],400);
            }
            $help = new AliEvaluateHelp();
            $result = $help->appendEvaluate($params['id'], $params['content']);
            if(!$result){
                return json(['message'=>'操作失败'], 400);
            }
            return json(['message'=>'操作成功'],200);
        } catch (Exception $ex) {
            return json(['message'=>$ex->getMessage()],400);
        }
    }

    /**
     * @title 获取评价模板内容
     * @method get
     * @url tmpContent
     * @return type
     */
    public function getTmpContent()
    {
        try {
            $request = Request::instance();
            $params = $request->param();
            // 验证数据
            $scene   = $params['is_random']==1 ? 'tmp1' : 'tmp2';
            $check = $this->checkParams($params, $scene);
            if(TRUE !== $check){
                return json(['message'=>$check],400);
            }
            $help = new AliEvaluateHelp();
            $content = $help->getEvaluateTmpContent($params['order_id'], $params['tmp_id'], $request,$params['is_random']);
            return json(['content'=>$content],200);
        } catch (Exception $ex) {
            return json(['message'=>$ex->getMessage()],500);
        }

    }
    
    /**
     * @title 获取各状态数量
     * @method get
     * @url statistics
     * @apiFilter app\customerservice\filter\AliexpressAccountFilter
     * @return type
     */
    public function statistics()
    {
        try {
            $help = new AliEvaluateHelp();
            $result = $help->getEvaluateCount();
            return json($result,200);
        } catch (Exception $ex) {
            return json(['message'=>$ex->getMessage()],500);
        }
    }

    /**
     * @title 系统订单评价
     * @url evaluate-order
     * @method post
     * @apiParam name:order_id type:int desc:系统订单ID
     * @apiParam name:content type:string desc:评价内容
     * @apiParam name:score type:int desc:评分(只能是1,2,3,4,5)
     * @apiReturn message:提示信息
     * @param Request $request
     * @return \think\response\Json
     */
    public function evaluateOnOrder(Request $request)
    {
        try{
            $params = $request->param();
            $check = $this->checkParams($params,'evaluate_order');
            if(true !== $check){
                return json(['message'=>$check],400);
            }
            $helper = new AliEvaluateHelp();
            $result = $helper->evaluateToOrder($params);
            $code = 200;
            if(!$result['status']){
                $code = 400;
            }
            return json(['message'=>$result['msg']],$code);
        }catch (Exception $ex){
            return json(['message'=>$ex->getMessage()],500);
        }
    }
    
    /**
    * 验证传入参数
    * @param array $params
    * @param string $scene
    * @return boolean
    */
    private function checkParams($params,$scene)
    {
        $result = $this->_validate->scene($scene)->check($params);
        if (true !== $result) {
            // 验证失败 输出错误信息
            return '参数验证失败：' . $this->_validate->getError();
        }
        return TRUE;
    }

    /**
     * @title 获取评价分类标签
     * @method get
     * @url statistics-score
     * @apiFilter app\customerservice\filter\AliexpressAccountFilter
     * @return type
     */
    public function statisticsByScore()
    {
        try {
            $help = new AliEvaluateHelp();
            $result = $help->getEvaluateScoreCount();
            return json($result,200);
        } catch (Exception $ex) {
            return json(['message'=>$ex->getMessage()],500);
        }
    }
}

