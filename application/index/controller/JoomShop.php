<?php
namespace app\index\controller;

use app\common\cache\Cache;
use app\common\exception\JsonErrorException;
use think\Controller;
use think\Exception;
use think\Request;
use app\common\controller\Base;
use think\Db;

use app\common\service\Common as CommonService;
use app\index\service\JoomShopService;
use \app\common\model\joom\JoomShop as JoomShopModel;

/**
 * @module 账号管理
 * @title joom账号管理
 * @author zhangdongdong
 * @url /joom-shop
 * Class Joom
 * @package app\index\controller
 */
class JoomShop extends Base
{
    protected $joomShopService;

    public function __construct()
    {
        parent::__construct();
        if(is_null($this->joomShopService)){
            $this->joomShopService = new JoomShopService();
        }
    }

    /**
     * @title joom帐号列表
     * @method GET
     * @url /joom-shop
     * @return \think\Response
     */
    public function index()
    {
        $request = Request::instance();
        $result = $this->joomShopService->shopList($request);
        return json($result, 200);
    }


    /**
     * @title 拉取帐号对应的店铺数量；
     * @method GET
     * @url /joom-shop/accounts
     * @return \think\response\Json
     */
    public function accounts()
    {
        $result = $this->joomShopService->accountCounts();
        return json($result, 200);
    }

    /**
     * @title 保存新建的资源
     * @method POST
     * @url /joom-shop
     * @param  \think\Request $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        $data = $request->param();

        $result = $this->validate($data, [
            'joom_account_id|账户ID' => 'require|number|gt:0',
            'code|店铺简称' => 'require|unique:joom_shop,code|length:3,30',
            'shop_name|店铺名称' => 'require|unique:joom_shop,shop_name|length:3,100',

            'email|邮箱' => 'email',
            'merchant_id|平台商户ID' => 'length:0,50',
            'download_order|下载订单参数' => 'number',
            'download_listing|下载型登参数' => 'number',
            'sync_delivery|同部发货参数' => 'number',
        ]);
        if ($result !== true) {
            return json(['message' => $result], 400);
        }
        //获取操作人信息
        $user = CommonService::getUserInfo($request);
        if (!empty($user)) {
            $data['creator_id'] = $user['user_id'];
        }
        $result = $this->joomShopService->save($data);

        if($result === false) {
            return json(['message' => $this->joomShopService->getError()], 400);
        }

        return json(['message' => '新增成功','data' => $result]);
    }

    /**
     * @title 显示指定的资源
     * @param  int $id
     * @method GET
     * @url /joom-shop/:id
     * @return \think\Response
     */
    public function read($id)
    {
        $result = $this->joomShopService->read($id);
        return json($result, 200);
    }

    /**
     * @title 显示编辑资源表单页.
     * @param  int $id
     * @method GET
     * @url /joom-shop/:id/edit
     * @return \think\Response
     */
    public function edit($id)
    {
        $result = $result = $this->joomShopService->read($id);
        return json($result, 200);
    }

    /**
     * @title 保存更新的资源
     * @param  \think\Request $request
     * @param  int $id
     * @method PUT
     * @url /joom-shop/:id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        $data = $request->param();

        $result = $this->validate($data, [
            'code|店铺简称' => 'require|unique:joom_shop,code|length:3,30',
            'shop_name|店铺名称' => 'require|unique:joom_shop,shop_name|length:3,100',

            'email|邮箱' => 'email',
            'merchant_id|平台商户ID' => 'length:0,50',
            'download_order|下载订单参数' => 'number',
            'download_listing|下载型登参数' => 'number',
            'sync_delivery|同部发货参数' => 'number',
        ]);
        if ($result !== true) {
            return json(['message' => $result], 400);
        }
        //获取操作人信息
        $user = CommonService::getUserInfo($request);
        if (!empty($user)) {
            $data['updater_id'] = $user['user_id'];
        }
        $this->joomShopService->update($id,$data);
        return json(['message' => '更改成功']);
    }



    /**
     * @title joom批量设置抓取参数；
     * @author 冬
     * @method post
     * @apiParam ids:多个ID用英文,分割
     * @url /joom-shop/set
     */
    public function batchSet(Request $request)
    {
        try {
            $params = $request->post();
            $result = $this->validate($params, [
                'ids|店铺ID' => 'require|min:1',
                'status|系统状态' => 'require|number',
                'download_order|抓取订单功能' => 'require|number',
                'download_listing|抓取Listing功能' => 'require|number',
                'sync_delivery|同步好评功能' => 'require|number',
            ]);
            if ($result !== true) {
                throw new Exception($result);
            }
            $model = new JoomShopModel();

            $data['is_invalid'] = (int)$params['status'];
            $data['download_order'] = (int)$params['download_order'];
            $data['sync_delivery'] = (int)$params['sync_delivery'];
            $data['download_listing'] = (int)$params['download_listing'];

            $idArr = array_merge(array_filter(array_unique(explode(',', $params['ids']))));

            $data['update_time'] = time();
            if (empty($data)) {
                return json(['message' => '数据参数不能为空'], 200);
            }
            $model->allowField(true)->update($data, ['id' => ['in', $idArr]]);

            Db::commit();
            //更新缓存
            $cache = Cache::store('JoomShop');
            foreach ($idArr as $id) {
                foreach($data as $key=>$val) {
                    $cache->updateTableRecord($id, $key, $val);
                }
            }

            return json(['message' => '更新成功'], 200);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title JOOM店铺停用，启用
     * @method POST
     * @url /joom-shop/status
     * @return \think\Response
     */
    public function changeStates()
    {
        $request = Request::instance();
        $id = $request->post('id', 0);
        $data = $request->post();
        if (!isset($data['is_invalid']) || !in_array($data['is_invalid'], [0, 1])) {
            return json(['message' => '操作失败，缺少参数is_invalid'], 400);
        }

        //获取操作人信息
        $user = CommonService::getUserInfo($request);
        if (!empty($user)) {
            $data['updater_id'] = $user['user_id'];
        }
        $this->joomShopService->status($id,$data);
        return json(['message' => '操作成功']);
    }

    /**
     * @title joom获取授权码code
     * @method post
     * @url /joom-shop/authorCode
     * @return \think\Response
     */
    public function authorCode()
    {
        $request = Request::instance();
        $client_id = $request->post('client_id', '');
        if (empty($client_id)) {
            return json(['message' => '应用ID不能为空'], 400);
        }
        $url = 'https://api-merchant.joom.com/api/v2/oauth/authorize?client_id=' . $client_id;
        return json(['url' => $url], 200);
    }

    /**
     * @title joom获取Token
     * @method post
     * @url /joom-shop/token
     * @return \think\response\Json
     * @throws Exception
     */
    public function token()
    {
        set_time_limit(0);
        $request = Request::instance();
        $id = $request->post('id', 0);
        if (empty($id)) {
            return json(['message' => '参数错误'], 400);
        }
        $data['client_id'] = $request->post('client_id', 0);
        $data['client_secret'] = $request->post('client_secret', 0);
        $data['authorization_code'] = $request->post('authorization_code', 0);
        $data['redirect_uri'] = $request->post('redirect_url', 0);
        if (empty($data['client_id']) || empty($data['client_secret']) || empty($data['authorization_code'])) {
            return json(['message' => '参数信息错误'], 400);
        }
        //获取操作人信息
        $user = CommonService::getUserInfo($request);
        if (!empty($user)) {
            $data['updater_id'] = $user['user_id'];
        }
        $data['joom_enabled'] = 1;//设置为有效
        $date = $this->joomShopService->getToken($id,$data);
        return json(['message' => '获取成功','date' => $date]);
    }

    /**
     * @title joom打开授权页面
     * @method post
     * @url /joom-shop/authorization
     * @return \think\response\Json
     */
    public function authorization()
    {
        $request = Request::instance();
        $id = $request->post('id', 0);
        if (empty($id)) {
            return json(['message' => '参数错误'], 400);
        }
        $result = $this->joomShopService->authorization($id);
        return json($result, 200);
    }
}
