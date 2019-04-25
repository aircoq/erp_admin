<?php


namespace app\index\controller;


use app\common\controller\Base;
use app\common\service\Common;
use app\common\model\shopee\ShopeeAccount as ShopeeAccountModel;
use app\index\service\ShopeeAccountService;
use think\Db;
use think\Exception;
use think\Request;
use app\common\cache\Cache;

/**
 * @module 基础设置
 * @title shopee账户管理
 * @url shopee-account
 */
class ShopeeAccount extends Base
{
    /**
     * @title 获取shopee账户列表
     * @return \think\response\Json
     * @author starzhan <397041849@qq.com>
     */
    public function index()
    {
        try {
            $param = $this->request->param();
            $page = $param['page'] ?? 1;
            $pageSize = $param['pageSize'] ?? 20;
            $service = new ShopeeAccountService();
            return json($service->index($page, $pageSize, $param), 200);
        } catch (Exception $ex) {
            return json(['message' => $ex->getMessage()], 400);
        }
    }

    /**
     * @title 获取shopee账户详情
     * @param $id
     * @return \think\response\Json
     * @author starzhan <397041849@qq.com>
     */
    public function read($id)
    {
        try {
            $service = new ShopeeAccountService();
            $res = $service->getId($id);
            $res['download_order'] = intval($res['download_order']);
            $res['sync_delivery'] = intval($res['sync_delivery']);
            $res['download_listing'] = intval($res['download_listing']);
            return json($res, 200);
        } catch (Exception $ex) {
            return json(['message' => $ex->getMessage()], 400);
        }
    }

    /**
     * @title 保存shopee账户详情
     * @return \think\response\Json
     * @author starzhan <397041849@qq.com>
     */
    public function save()
    {
        try {
            $param = $this->request->param();
            $service = new ShopeeAccountService();
            $userInfo = Common::getUserInfo($this->request);
            return json($service->save($param, $userInfo['user_id']), 200);
        } catch (Exception $ex) {
            return json(['message' => $ex->getMessage()], 400);
        }
    }

    /**
     * @title 保存shopee账户授权
     * @method put
     * @url save-token
     * @param Request $request
     * @return \think\response\Json
     */
    public function saveToken(Request $request)
    {
        try {
            $params = $request->param();
            $service = new ShopeeAccountService();
            $userInfo = Common::getUserInfo($request);
            return json($service->saveToken($params, $userInfo['user_id']), 200);
        } catch (Exception $ex) {
            return json(['message' => $ex->getMessage()], 400);
        }
    }

    /**
     * @title 系统状态切换
     * @url change-status
     * @method post
     * @author starzhan <397041849@qq.com>
     */
    public function changeStatus()
    {
        try {
            $params = $this->request->param();
            if (!isset($params['id']) || !$params['id']) {
                throw new Exception('Id 不能为空');
            }
            if(!isset($params['platform_status'])){
                throw new Exception('platform_status 不能为空');
            }
            $id = $params['id'];
            $service = new ShopeeAccountService();
            $userInfo = Common::getUserInfo($this->request);
            return json($service->changeStatus($id,$params['platform_status'], $userInfo['user_id']), 200);
        } catch (Exception $ex) {
            return json(['message' => $ex->getMessage()], 400);
        }
    }

    /**
     * @title 获取站点
     * @url site
     * @author starzhan <397041849@qq.com>
     */
    public function getSite(){
        try {
            $service = new ShopeeAccountService();
            return json($service->getSite(), 200);
        } catch (Exception $ex) {
            return json(['message' => $ex->getMessage()], 400);
        }
    }

    /**
     * @title 获取账号
     * @url account
     * @param Request $request
     * @return \think\response\Json
     * @author linpeng
     */
    public function getAccount(Request $request)
    {
        try {
            $site_code = $request->get('site_code', '');  //站点code
            $service = new ShopeeAccountService();
            $res = $service->getAccount($site_code);
            return json($res,200);
        } catch (Exception $ex) {
            return json(['message' => $ex->getMessage()], 400);
        }
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
            'platform_status|系统状态' => 'require|number',
            'download_order|抓取Shopee订单功能' => 'require|number',
            'sync_delivery|同步发货状态到Shopee功能' => 'require|number',
            'download_listing|抓取Listing数据' => 'require|number',
        ]);

        if ($result != true) {
            throw new Exception($result);
        }
        //实例化模型
        $model = new ShopeeAccountModel();

        if (isset($params['platform_status']) && $params['platform_status'] != '') {
            $data['platform_status'] = (int)$params['platform_status'];   //1 为在用， 0 为停用
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
            $cache = Cache::store('ShopeeAccount');
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