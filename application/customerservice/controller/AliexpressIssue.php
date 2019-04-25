<?php
namespace app\customerservice\controller;

use app\carrier\service\AliSellerAddressService;
use app\common\controller\Base;
use app\common\model\aliexpress\AliexpressIssueSolution;
use app\common\service\Common;
use app\common\service\CommonQueuer;
use app\customerservice\service\aliexpress\AliIssueHelp;
use app\customerservice\task\AliIssue;
use app\goods\queue\SyncGoodsImgQueue;
use app\goods\service\GoodsImageDownloadService;
use app\goods\service\GoodsImageMovedService;
use app\publish\task\AliexpressGrabGoods;
use app\publish\task\AliexpressGrabProductGroup;
use app\purchase\service\SupplierService;
use think\Request;
use think\Exception;

/**
 * @module 客服管理
 * @title 速卖通纠纷
 * @url ali-issue
 * @package app\customerservice\controller
 */
class AliexpressIssue extends Base
{
    /**
     * @title 纠纷列表
     * @apiRelate app\order\controller\Order::account
     * @apiFilter app\customerservice\filter\AliexpressAccountFilter
     */
    public function index()
    {
        try {
            $request = Request::instance();
            $page = $request->param('page', 1);
            $pageSize = $request->param('pageSize', 20);
            $params = $request->param();
            $help = new AliIssueHelp();
            $where = $help->getWhere($params);
            if(param($params, 'sort_type')){
                $order = 'i.expire_time '.$params['sort_type'];
                $result = $help->getList($where, $page, $pageSize, $order);
            }else{
                $result = $help->getList($where, $page, $pageSize);
            }
            return json($result, 200);
        } catch (Exception $ex) {
            return json(['message' => $ex->getMessage(), 500]);
        }
    }

    /**
     * @title 查询纠纷详细
     * @method get
     * @param $id
     * @return \think\response\Json
     */
    public function read($id)
    {
        try {
            if(!$id){
                return json(['message'=>'参数错误'],400);
            }
            $help = new AliIssueHelp();
            $detail = $help->getIssueDetail($id);
            return json($detail,200);
        } catch (Exception $ex) {
            return json(['message'=>$ex->getMessage()],500);
        }
        
    }

    /**
     * @title 上传纠纷图片
     * @method post
     * @url upload-images
     * @param Request $request
     * @return \think\response\Json
     * @throws \Exception
     * @author Reece
     * @date 2018-07-25 19:05:25
     */
    public function uploadImages(Request $request)
    {
        try{
            $userId = param(Common::getUserInfo($request), 'user_id', 0);
            $img = $request->post('img');
            $issueId = $request->post('issue_id', 0);
            if(empty($img)) throw new Exception('请选择图片');
            if(empty($issueId)) throw new Exception('纠纷id不能为空');
            $checkImg = ['size'=>2*1024*1024, 'ext'=>['jpg', 'jpeg', 'png']];
            $res = Common::base64DecImg($img, 'upload/issue/'.date('Y-m-d'), md5(time().$userId.rand(1000,9999)), $checkImg);
            if(!$res['status']) throw new Exception($res['message']);
            $service = new AliIssueHelp();
            $result = $service->uploadImages($issueId, $res);
            if($result['status']){
                return json(['message'=> '操作成功', 'data'=>$result['data']], 200);
            }else{
                return json(['message'=> '操作失败'], 400);
            }
        }catch (Exception $ex){
            return json(['message'=> $ex->getMessage()], 400);
        }
    }

    /**
     * @title 同意普通纠纷方案
     * @method post
     * @url agree-solution
     * @param Request $request
     * @return \think\response\Json
     * @throws \Exception
     * @author Reece
     * @date 2018-07-27 15:29:24
     */
    public function agreeSolution(Request $request)
    {
        try{
            $params = $request->post();
            if(!param($params, 'solution_id')) throw new Exception('方案ID不能为空');
            $service = new AliIssueHelp();
            $result = $service->agreeSolution($params);
            if($result['status']){
                return json(['message'=> '操作成功', 'data'=>$result['data']], 200);
            }else{
                return json(['message'=> '操作失败'], 400);
            }
        }catch (Exception $ex){
            return json(['message'=> $ex->getMessage()], 400);
        }
    }

    /**
     * @title 新增(拒绝某个买家方案)
     * @method post
     * @url add-solution
     * @param Request $request
     * @return \think\response\Json
     * @throws \Exception
     * @author Reece
     * @date 2018-07-27 15:29:24
     */
    public function addSolution(Request $request)
    {
        try{
            $params = $request->post();
            $service = new AliIssueHelp();
            $data = $service->checkSaveSolutionParams($params);
            $result = $service->saveSolution($data);
            if($result['status']){
                return json(['message'=> '操作成功', 'data'=>$result['data']], 200);
            }else{
                return json(['message'=> '操作失败'], 400);
            }
        }catch (Exception $ex){
            return json(['message'=> $ex->getMessage()], 400);
        }

    }

    /**
     * @title 修改方案
     * @method post
     * @url edit-solution
     * @param Request $request
     * @return \think\response\Json
     * @throws \Exception
     * @author Reece
     * @date 2018-07-27 15:29:24
     */
    public function editSolution(Request $request)
    {
        try{
            $params = $request->post();
            $service = new AliIssueHelp();
            $data = $service->checkSaveSolutionParams($params, 0);
            $result = $service->saveSolution($data);
            if($result['status']){
                return json(['message'=> '操作成功', 'data'=>$result['data']], 200);
            }else{
                return json(['message'=> '操作失败'], 400);
            }
        }catch (Exception $ex){
            return json(['message'=> $ex->getMessage()], 400);
        }

    }

    /**
     * @title 获取标签统计数量
     * @url get-label
     * @method get
     * @return \think\response\Json
     * @throws \Exception
     * @author Reece
     * @date 2018-08-06 18:12:22
     */
    public function getLabelStatistics(Request $request)
    {
        try{
            $service = new AliIssueHelp();
            $params = $request->param();
            $where = $service->getWhere($params, 0);
            $data = $service->getLabelStatistics($where);
            return json($data, 200);
        }catch (Exception $ex){
            return json(['message'=> $ex->getMessage()], 400);
        }
    }

    /**
     * @title 获取速卖通卖家退货地址
     * @method get
     * @url get-refund-address/:account_id
     * @param $account_id
     * @return \think\response\Json
     * @author Reece
     * @date 2018-08-09 20:50:57
     */
    public function getRefundAddress($account_id)
    {
        try{
            $data = (new AliSellerAddressService())->getSellerAddress($account_id, 'refund');
            return json($data, 200);
        }catch (Exception $ex){
            return json(['message'=>$ex->getMessage()], 400);
        }
    }

    public function export()
    {

    }

    /**
     * @title 获取纠纷历史
     * @method get
     * @url get-process/:issue_id
     * @param $issue_id
     * @return \think\response\Json
     * @throws \Exception
     * @author Reece
     * @date 2018-08-14 10:07:56
     */
    public function getProcess($issue_id)
    {
        try{
            $service = new AliIssueHelp();
            $result = $service->getProcess($issue_id);
            return json($result, 200);
        }catch (Exception $ex){
            return json(['message'=>$ex->getMessage()], 400);
        }
    }

    /**
     * @title 立即抓取
     * @method post
     * @url sync
     * @param Request $request
     * @return \think\response\Json
     * @throws \Exception
     * @author Reece
     * @date 2018-08-27 15:58:59
     */
    public function sync(Request $request)
    {
        try{
            $ids = $request->post('account_id');
            $service = new AliIssueHelp();
            $ids = json_decode($ids, true);
            if(empty($ids) || !is_array($ids)){
                throw new Exception('勾选错误');
            }
            $result = $service->sync($ids);
            return json(['message'=>'操作成功', 'data'=>$result], 200);
        }catch (Exception $ex){
            return json(['message'=>$ex->getMessage()], 400);
        }
    }

}