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
use app\common\model\zoodMall\YandexAccount as YandexAccountModel;
use app\index\service\YandexAccountService;
use think\Db;
use think\Exception;
use think\Request;

/**
 * @module 账号管理
 * @title yandex账号
 * @url /yandex-account
 * @package app\publish\controller
 * @author lanshushu
 */
class YandexAccount extends Base
{
    protected $yandexAccountService;

    public function __construct()
    {
        parent::__construct();
        if(is_null($this->yandexAccountService)){
            $this->yandexAccountService = new YandexAccountService();
        }
    }

    /**
     * @title yandex账号列表
     * @method GET
     * @param Request $request
     * @return \think\response\Json
     * @throws \Exception
     */
    public function index(Request $request)
    {
        try{
            $params = $request->param();
            $page = $request->get('page', 1);
            $pageSize = $request->get('pageSize', 20);
            $response = $this->yandexAccountService->accountList($params,$page,$pageSize);
            return json($response);
        }catch (Exception $exp){
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }

    /**
     * @title 添加账号
     * @method POST
     * @url /yandex-account
     * @param Request $request
     * @return \think\response\Json
     */
    public function add(Request $request)
    {
        try{
            $params = $request->param();
            $user = Common::getUserInfo($request);
            $uid = $user['user_id'];

            $response = $this->yandexAccountService->add($params,$uid);
            if($response === false) {
                return json(['message' => $this->yandexAccountService->getError()], 400);
            }
            return json(['message' => '添加成功','data' => $response]);
        }catch (Exception $exp){
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }

    /**
     * @title 更新账号
     * @method PUT
     * @url /yandex-account
     * @return \think\Response
     */
    public function update(Request $request)
    {
        try{
            $params = $request->param();
            if(!$params['id']) {
                return json(['message' => '缺少必要参数ID'], 400);
            }
            $user = Common::getUserInfo($request);
            $uid = $user['user_id'];
            $response = $this->yandexAccountService->add($params,$uid);
            if($response === false) {
                return json(['message' => $this->yandexAccountService->getError()], 400);
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
     * @url /yandex-account/:id
     * @return \think\response\Json
     */
    public function read($id)
    {
        $response = $this->yandexAccountService->getOne($id);
        return json($response);
    }



    /**
     * @title 获取订单授权信息
     * @method GET
     * @param  int $id
     * @url /yandex-account/token/:id
     * @return \think\response\Json
     */
    public function getToken($id)
    {
        $response = $this->yandexAccountService->getTokenOne($id);
        return json($response);
    }

    /**
     * @title  yandex订单账号授权
     * @method PUT
     * @url /yandex-account/token
     * @param Request $request
     * @return \think\response\Json
     */
    public function updaeToken(Request $request)
    {
        try{
            $params = $request->param();
            if(!$params['id']) {
                return json(['message' => '缺少必要参数ID'], 400);
            }
            $uid = Common::getUserInfo($request) ? Common::getUserInfo($request)['user_id'] : 0;
            $response = $this->yandexAccountService->updateToken($params, $uid);
            if($response === false) {
                return json(['message' => $this->yandexAccountService->getError()], 400);
            }
            return json(['message' => '授权成功','data' => $response]);
        }catch (Exception $exp){
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }


    /**
     * @title 停用，启用账号
     * @method post
     * @url /yandex-account/states
     */
    public function changeStatus(Request $request)
    {
        $params = $request->param();
        if(!$params['id']) {
            return json(['message' => '缺少必要参数ID'], 400);
        }
        $response = $this->yandexAccountService->changeStatus($params);
        if($response === false) {
            return json(['message' => $this->yandexAccountService->getError()], 400);
        }
        return json(['message' => '操作成功','data' => $response]);
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
        $result = $this->validate($params,[
            'ids|帐号ID' => 'require|min:1',
            'is_invalid|系统状态' => 'require|number',
            'download_order|抓取Walmart订单功能' => 'require|number',
            'sync_delivery|同步发货状态到Walmart功能' => 'require|number',
            'download_listing|抓取Listing数据' => 'require|number',
        ]);

        if ($result != true) {
            throw new Exception($result);
        }
        //实例化模型
        $model = new YandexAccountModel();

        $data['is_invalid'] = (int)$params['is_invalid'];   //0-停用 1-启用
        $data['download_order'] = (int)$params['download_order'];
        $data['sync_delivery'] = (int)$params['sync_delivery'];
        $data['download_listing'] = (int)$params['download_listing'];

        $idArr = array_merge(array_filter(array_unique(explode(',',$params['ids']))));
        //开启事务
        Db::startTrans();
        try {
            if (empty($data)) {
                return json(['message' => '数据参数不能为空'], 200);
            }

            $data['update_time'] = time();
            $model->allowField(true)->update($data, ['id' => ['in', $idArr]]);
            Db::commit();

            //更新缓存
            $cache = Cache::store('YandexAccount');
            foreach ($idArr as $id) {
                foreach ($data as $k => $v) {
                    $cache->updateTableRecord($id, $k, $v);
                }
            }
            return json(['message' => '更新成功'], 200);
        } catch (Exception $ex) {
            Db::rollback();
            return json(['message' => '更新失败'], 400);
        }
    }

}