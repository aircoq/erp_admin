<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-4-24
 * Time: 上午10:58
 */

namespace app\index\controller;

use app\common\cache\Cache;
use app\common\controller\Base;
use app\common\exception\JsonErrorException;
use app\common\service\Common;
use app\index\service\PaytmAccountService;
use think\Db;
use think\Exception;
use think\Request;
use app\common\model\paytm\PaytmAccount as PaytmAccountModel;
use app\index\service\AccountService;
use app\common\model\ChannelAccountLog;
use app\common\service\ChannelAccountConst;

/**
 * @module 账号管理
 * @title paytm账号
 * @url /paytm-account
 * @package app\publish\controller
 * @author libiamin
 */
class PaytmAccount extends Base
{
    protected $paytmAccountService;

    public function __construct()
    {
        parent::__construct();
        if(is_null($this->paytmAccountService)){
            $this->paytmAccountService = new PaytmAccountService();
        }
    }
    
    /**
     * @title paytm账号列表
     * @method GET
     * @param Request $request
     * @return \think\response\Json
     * @throws \Exception
     */
    public function index(Request $request)
    {
        try{
            $response = $this->paytmAccountService->getList($request->param());
            return json($response);
        }catch (Exception $exp){
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }

    /**
     * @title 添加账号
     * @method POST
     * @url /paytm-account
     * @param Request $request
     * @return \think\response\Json
     */
    public function add(Request $request)
    {
        try{
            $params = $request->param();
            $user = Common::getUserInfo($request);
            $uid = $user['user_id'];
            $response = $this->paytmAccountService->add($params,$uid);
            if($response === false) {
                return json(['message' => $this->paytmAccountService->getError()], 400);
            }
            return json(['message' => '添加成功','data' => $response]);
        }catch (Exception $exp){
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }

    /**
     * @title 更新账号
     * @method PUT
     * @url /paytm-account
     * @return \think\Response
     */
    public function update(Request $request)
    {
        try{
            $params = $request->param();
            $user = Common::getUserInfo($request);
            $uid = $user['user_id'];
            $response = $this->paytmAccountService->add($params,$uid);
            if($response === false) {
                return json(['message' => $this->paytmAccountService->getError()], 400);
            }
            return json(['message' => '更新成功','data' => $response]);
        }catch (Exception $exp){
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }

    /**
     * @title 查看账号
     * @method GET
     * @param  int $id
     * @url /paytm-account/:id
     * @return \think\response\Json
     */
    public function read($id)
    {
        $response = $this->paytmAccountService->getOne($id);
        $response and $response['site_status'] = AccountService::getSiteStatus($response['base_account_id'], $response['code']);
        return json($response);
    }



    /**
     * @title 获取订单授权信息
     * @method GET
     * @param  int $id
     * @url /paytm-account/token/:id
     * @return \think\response\Json
     */
    public function getToken($id)
    {
        $response = $this->paytmAccountService->getTokenOne($id);
        return json($response);
    }

    /**
     * @title  paytm订单账号授权
     * @method PUT
     * @url /paytm-account/token
     * @param Request $request
     * @return \think\response\Json
     */

    public function updaeToken(Request $request)
    {
        try{
            $params = $request->param();
            $uid = Common::getUserInfo($request) ? Common::getUserInfo($request)['user_id'] : 0;
            $response = $this->paytmAccountService->refresh_token($params, $uid);
            if($response === false) {
                return json(['message' => $this->paytmAccountService->getError()], 400);
            }
            return json(['message' => '授权成功','data' => $response]);
        }catch (Exception $exp){
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }

    /**
     * @title  paytm商品账号授权
     * @method PUT
     * @url /paytm-account/tokencat
     * @param Request $request
     * @return \think\response\Json
     */
    public function updaeTokenCat(Request $request)
    {
        try{
            $params = $request->param();
            $uid = Common::getUserInfo($request) ? Common::getUserInfo($request)['user_id'] : 0;
            $response = $this->paytmAccountService->refresh_token_cat($params, $uid);
            if($response === false) {
                return json(['message' => $this->paytmAccountService->getError()], 400);
            }
            return json(['message' => '授权成功','data' => $response]);
        }catch (Exception $exp){
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }

    }

    /**
     * @title 停用，启用账号
     * @method post
     * @url /paytm-account/states
     */
    public function changeStatus(Request $request)
    {
        $params = $request->param();

        if (!isset($params['id']) || !isset($params['status'])) {
            return json(['message' => '参数错误[id,status]'], 400);
        }

        $id = $params['id'] ?? 0;
        $status = $params['status'] ?? null;

        $response = $this->paytmAccountService->changeStatus($id, $status);
        if ($response === true) {
            return json(['message' => '操作成功'], 200);
        }

        return json(['message' => $response], 400);
    }

    /**
     * @title 批量开启
     * @url batch-set
     * @method post
     * @param Request $request
     * @return \think\response\Json
     * @throws Exception
     */
    public function batchSet(Request $request)
    {
        $params = $request->post();
        $result = $this->validate($params, [
            'ids|帐号ID' => 'require|min:1',
            'is_invalid|系统状态' => 'require|number',
            'download_order|抓取Paytm订单功能' => 'require|number',
            'sync_delivery|同步发货状态到Paytm功能' => 'require|number',
            'download_listing|抓取Listing数据' => 'require|number',
        ]);

        if ($result != true) {
            throw new Exception($result);
        }

        //实例化模型
        $model = new PaytmAccountModel();

        if (isset($params['is_invalid']) && $params['is_invalid'] != '') {
            $data['is_invalid'] = (int)$params['is_invalid'];   //1 启用， 0未启用
        }
        if (isset($params['download_order']) && $params['download_order'] != '') {
            $data['download_order'] = (int)$params['download_order'];
        }
        if (isset($params['sync_delivery']) && $params['sync_delivery'] != '') {
            $data['sync_delivery'] = (int)$params['sync_delivery'];
        }
        if (isset($params['download_listing']) && $params['download_listing'] != '') {
            $data['download_listing'] = (int)$params['download_listing'];
        }

        $idArr = array_merge(array_filter(array_unique(explode(',',$params['ids']))));
        if (!$idArr) {
            throw new Exception('参数错误');
        }
        $old_data_list = $model
            ->where(['id' => ['in', $idArr]])
            ->select();

        $user = Common::getUserInfo(Request::instance());
        $operator = [];
        $operator['operator_id'] = $user['user_id'] ?? 0;
        $operator['operator'] = $user['realname'] ?? '';

        $new_data = $data;

        /**
         * 判断是否可更改状态
         */
        if (isset($new_data['is_invalid'])) {
            (new \app\index\service\ChannelAccountService())->checkChangeStatus(ChannelAccountConst::channel_Paytm, $idArr);
        }

        //开启事务
        Db::startTrans();
        try {
            if (empty($data)) {
                return json(['message' => '数据参数不能为空'], 200);
            }

            $data['update_time'] = time();
            $model->allowField(true)->update($data,['id' => ['in', $idArr]]);

            $new_data['site_status'] = isset($params['site_status']) ? intval($params['site_status']) : 0;
            foreach ($old_data_list as $old_data) {
                if (in_array($new_data['site_status'], [1, 2, 3, 4])) {
                    $old_data['site_status'] = AccountService::setSite(
                        ChannelAccountConst::channel_Paytm,
                        $old_data,
                        $operator['operator_id'],
                        $new_data['site_status']
                    );
                }
                $operator['account_id'] = $old_data['id'];
                PaytmAccountService::addLog(
                    $operator, 
                    ChannelAccountLog::UPDATE, 
                    $new_data, 
                    $old_data
                );
            }

            Db::commit();

            //更新缓存
            $cache = Cache::store('PaytmAccount');
            foreach ($idArr as $id) {
                foreach ($data as $k => $v) {
                    $cache->updateTableRecord($id, $k, $v);
                }
            }
            return json(['message' => '更新成功'], 200);
        } catch (Exception $ex) {
            Db::rollback();
            return json(['message' => '更新失败' . $ex->getMessage()], 400);
        }
    }

    /**
     * @title 获取paytm账号日志
     * @method get
     * @url /paytm-account/log/:id
     * @param  \think\Request $request
     * @param  string $site
     * @return \think\Response
     */
    public function getLog(Request $request)
    {
        return json(
            $this->paytmAccountService->getPaytmLog($request->param())
            , 200
        );
    }
}