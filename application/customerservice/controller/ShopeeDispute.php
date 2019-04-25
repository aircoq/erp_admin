<?php
/**
 * Created by PhpStorm.
 * User: Dave
 * Date: 2018/9/19
 * Time: 14:16
 */

namespace app\customerservice\controller;

use app\common\controller\Base;
use app\common\service\Common;
use app\customerservice\service\ShopeeDisputeService;
use think\Exception;
use think\Request;
use think\Db;

/**
 * @module 客服管理
 * @title Shopee纠纷
 * @url /shopee-dispute
 */
class ShopeeDispute extends Base
{
    protected $shopeeDisputeService;
    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->shopeeDisputeService = new ShopeeDisputeService();
    }

    /**
     * @title 纠纷清单（取消订单、退款退货）
     * @method GET
     * @url /shopee-dispute
     * @return \think\response\Json
     * @apiRelate  app\customerservice\controller\ShopeeDispute::export
     * @apiRelate  app\index\controller\DownloadFile::downExportFile
     */
    public function index()
    {
        $param = $this->request->param();
        try {
            $param['page'] = $param['page'] ?? 1;
            $param['page_size'] = $param['page_size'] ?? 50;
            $param['user_id'] = Common::getUserInfo()['user_id'];
            $result = $this->shopeeDisputeService->index($param);
            if(isset($result['file'])){
                return json(['message'=>'完成文件生成','file'=>$result['file']]);
            }
            return json($result, 200);
        } catch (Exception $e) {
//            var_dump($e->getFile() . $e->getLine());
            return json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * @title 导出纠纷数据
     * @method POST
     * @url export
     * @apiParam name:ids type:string require:1 desc:导出id[1,2]
     * @apiReturn status:状态 1-成功
     * @apiReturn message:操作信息
     * @apiReturn file_code:文件状态码
     */
    public function export()
    {
        $params = $this->request->param();
        $params['export'] = 1;
        $result = $this->shopeeDisputeService->index($params);
        return json($result);
    }

    /**
     * @title 订单取消分组统计数量
     * @method GET
     * @url cancel/group-count
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cancelGroupCount()
    {
        $result = $this->shopeeDisputeService->cancelGroupCount();
        return json($result);
    }

    /**
     * @title 订单退货分组统计数量
     * @method GET
     * @url return/group-count
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function returnGroupCount()
    {
        $result = $this->shopeeDisputeService->returnGroupCount();
        return json($result);
    }

    /**
     * @title 刷新订单取消
     * @method POST
     * @url cancel/refresh
     * @return \think\response\Json
     */
    public function refreshCancel()
    {
        $params = $this->request->param();
        try {
            $fault = [];
            $ordersn = json_decode($params['ordersn'],true);
            if(empty($ordersn)){
                return json(['message'=>'ordersn参数异常']);
            }
            foreach ($ordersn as $v){
                $result = $this->shopeeDisputeService->refreshCancel($v);
                if(!$result){
                    $fault[] = $v;//记录刷新失败的取消单
                }
            }
            if(count($fault)){
                $message = sprintf('以下订单取消申请刷新失败：%s',join(',',$fault));
            }else{
                $message = '刷新成功';
            }
            return json(['message'=>$message]);
        } catch (Exception $e) {
            return json($e->getMessage() . $e->getFile() . $e->getLine());
        }
    }

    /**
     * @title 刷新订单退货
     * @method POST
     * @url return/refresh
     * @return \think\response\Json
     */
    public function refreshReturn()
    {
        $params = $this->request->param();
        try {
            $fault = [];
            $returnsn = json_decode($params['returnsn'],true);
            if(empty($returnsn)){
                return json(['message'=>'returnsn参数异常']);
            }
            foreach ($returnsn as $v){
                $result = $this->shopeeDisputeService->refreshReturn($v);
                if(!$result){
                    $fault[] = $v;//记录刷新失败的退货单
                }
            }
            if(count($fault)){
                $message = sprintf('以下订单退货申请刷新失败：%s',join(',',$fault));
            }else{
                $message = '刷新成功';
            }
            return json(['message'=>$message]);
        } catch (Exception $e) {
            return json($e->getMessage() . $e->getFile() . $e->getLine());
        }
    }


    /**
     * @title 订单取消申请商品详情
     * @method get
     * @url cancel/:ordersn
     * @param $ordersn
     * @return \think\response\Json
     */
    public function getCancelDetail($ordersn)
    {
        try {
            $user_id = Common::getUserInfo()['user_id'];
            $result = $this->shopeeDisputeService->getCancelDetail($ordersn, $user_id);
            return json($result, 200);
        } catch (Exception $e) {
//            var_dump($e->getFile() . $e->getLine());
            return json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * @title 订单取消日志详情
     * @method get
     * @url :ordersn/cancel-log
     * @param $ordersn
     * @return \think\response\Json
     */
    public function getCancelLog($ordersn)
    {
        try {
            $result = $this->shopeeDisputeService->getCancelLog($ordersn);
            return json($result, 200);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * @title 订单退货申请详情
     * @method get
     * @url return/:returnsn
     * @param $returnsn
     * @return \think\response\Json
     */
    public function getReturnDetail($returnsn)
    {
        try {
            $user_id = Common::getUserInfo()['user_id'];
            $result = $this->shopeeDisputeService->getReturnDetail($returnsn, $user_id);
            return json($result, 200);
        } catch (Exception $e) {
//            var_dump($e->getFile() . $e->getLine());
            return json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * @title 订单退货申请纠纷
     * @method get
     * @url :returnsn/dispute
     * @param $returnsn
     * @return \think\response\Json
     */
    public function getReturnDispute($returnsn)
    {
        try {
            $result = $this->shopeeDisputeService->getReturnDispute($returnsn);
            return json($result, 200);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * @title 订单退货申请日志
     * @method get
     * @url :returnsn/log
     * @param $returnsn
     * @return \think\response\Json
     */
    public function getReturnLog($returnsn)
    {
        try {
            $result = $this->shopeeDisputeService->getReturnLog($returnsn);
            return json($result, 200);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    public function afterSale($ordersn)
    {
        try {
            $this->shopeeDisputeService->afterSale($ordersn);
            return json(['message'=>'订单有付款']);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * @title 关联售后单ID
     * @method put
     * @url :returnsn/after-sale
     * @param $returnsn
     * @return \think\response\Json
     */
    public function relateAfterSale($returnsn)
    {
        $aftersale_id = $this->request->param('aftersale_id');
        try {
            if($this->shopeeDisputeService->relateAfterSale($returnsn, $aftersale_id))
                return json(['message'=>'创建成功']);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * @title 卖方取消订单
     * @method put
     * @url :ordersn/cancel
     * @param $ordersn
     * @return \think\response\Json
     */
    public function cancelOrder($ordersn)
    {
        $param = $this->request->param();
        DB::startTrans();
        try {
            //todo 审核
            $this->shopeeDisputeService->cancelOrder($ordersn, $param['cancel_reason'],
                $param['item_id'], $param['variation_id']);
            DB::commit();
            return json(['message'=>'卖方取消订单成功']);
        } catch (Exception $e) {
            DB::rollback();
            return json(['message'=>$e->getMessage()],400);
        }
    }

    /**
     * @title 接受买方取消订单
     * @method put
     * @url :ordersn/accept
     * @param string $ordersn
     * @return \think\response\Json
     */
    public function acceptBuyerCancellation($ordersn)
    {
        DB::startTrans();
        try {
            //todo 审核
            $this->shopeeDisputeService->acceptBuyerCancellation($ordersn);
            DB::commit();
            return json(['message'=>'接受【买方取消订单】操作成功']);
        } catch (Exception $e) {
            DB::rollback();
            return json(['message'=>$e->getMessage()],400);
        }
    }

    /**
     * @title 拒绝买方取消订单
     * @method put
     * @url :ordersn/reject
     * @param $ordersn
     * @return \think\response\Json
     */
    public function rejectBuyerCancellation($ordersn)
    {
        DB::startTrans();
        try {
            //todo 审核
            $this->shopeeDisputeService->rejectBuyerCancellation($ordersn);
            DB::commit();
            return json(['message'=>'拒绝【买方取消订单】操作成功']);
        } catch (Exception $e) {
            DB::rollback();
            return json(['message'=>$e->getMessage()],400);
        }
    }

    /**
     * @title 卖方接受退货
     * @method put
     * @url :returnsn/confirm
     * @param string $returnsn
     * @return \think\response\Json
     */
    public function confirmReturn($returnsn)
    {
        DB::startTrans();
        try {
            //todo 审核
            $result = $this->shopeeDisputeService->confirmReturn($returnsn);
            DB::commit();
            return json(['message'=>'接受【买方退货申请】操作成功']);
        } catch (Exception $e) {
            DB::rollback();
            return json(['message'=>$e->getMessage()],400);
        }

    }


    /**
     * @title 卖方争议退货
     * @method post
     * @url :returnsn/dispute
     * @param string $returnsn
     * @return \think\response\Json
     */
    public function disputeReturn($returnsn)
    {
        $param = $this->request->param();
        DB::startTrans();
        try {
            //todo 上传图片
            $param['files'] = json_decode($param['files'], true);
            $images = $this->shopeeDisputeService->uploadPictures($param['files']);
            $images = array_slice($images,0,3);
//            var_dump($images);//die;
            //发起纠纷
            $result = $this->shopeeDisputeService->disputeReturnOrder($returnsn, $param['email'], $param['dispute_reason'], $param['dispute_text_reason'], $images);
//            var_dump($result);
            DB::commit();
            return json(['message'=>'发起纠纷成功']);
        } catch (Exception $e) {
            DB::rollback();
            return json(['message'=>$e->getMessage()],400);
        }
    }


}