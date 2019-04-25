<?php
namespace app\index\controller;

use app\common\service\ChannelAccountConst;
use think\Exception;
use think\Request;
use app\common\controller\Base;
use app\common\service\Common as CommonService;
use app\index\service\LazadaAccountService;
use app\common\cache\Cache;
/**
 * @module 账号管理
 * @title lazada账号管理
 * @author phill
 * @url /lazada-account
 * Class Lazada
 * @package app\index\controller
 */
class LazadaAccount extends Base
{
    protected $lazadaAccountService;

    protected function init()
    {
        if(is_null($this->lazadaAccountService)){
            $this->lazadaAccountService = new LazadaAccountService();
        }
    }

    /**
 * @title 显示资源列表
 * @return \think\Response
 * @apiRelate app\index\controller\MemberShip::memberInfo
 * @apiRelate app\index\controller\MemberShip::save
 * @apiRelate app\index\controller\MemberShip::update
 * @apiRelate app\index\controller\User::staffs
 */
    public function index()
    {
        $request = Request::instance();
        if (isset($request->header()['X-Result-Fields'])) {
            $field = $request->header()['X-Result-Fields'];
            $field = explode(',',$field);
        }

        $result = $this->lazadaAccountService->accountList($request);
        return json($result, 200);
    }

    /**
     * @title 保存新建的资源
     * @param  \think\Request $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        $params = $request->param();
        $data = $params;
        $validateAccount = validate('LazadaAccount');

        if (!$validateAccount->check($data)) {
            return json(['message' => $validateAccount->getError()], 400);
        }

        //必须要去账号基础资料里备案
        \app\index\service\BasicAccountService::isHasCode(ChannelAccountConst::channel_Lazada,$data['code']);
        //获取操作人信息
        $user = CommonService::getUserInfo($request);
        if (!empty($user)) {
            $data['creator_id'] = $user['user_id'];
        }
        $accountInfo = $this->lazadaAccountService->save($data);
        return json(['message' => '新增成功','data' => $accountInfo]);
    }

    /**
     * @title 显示指定的资源
     * @param  int $id
     * @return \think\Response
     */
    public function read($id)
    {
        $result = $this->lazadaAccountService->read($id);
        return json($result, 200);
    }

    /**
     * @title 显示编辑资源表单页.
     * @param  int $id
     * @return \think\Response
     */
    public function edit($id)
    {
        $result = $result = $this->lazadaAccountService->read($id);
        return json($result, 200);
    }

    /**
     * @title 保存更新的资源
     * @param  \think\Request $request
     * @param  int $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        $params = $request->param();
        $data = $params;
        //获取操作人信息
        $user = CommonService::getUserInfo($request);

        if (!empty($user)) {
            $data['update_id'] = $user['user_id'];
        }
        $model=$this->lazadaAccountService->update($id,$data);
        return json(['message' => '操作成功', 'data' => $model]);
    }

    /**
     * @title 停用，启用账号
     * @url states
     * @method post
     */
    public function changeStates()
    {

        $request = Request::instance();
        $id = $request->post('id', 0);
        $state = $request->post('status', 0);
        $data['status'] = $state == 1 ? 1 : 0;
        //获取操作人信息
        $user = CommonService::getUserInfo($request);
        if (!empty($user)) {
            $data['update_id'] = $user['user_id'];
        }
        $this->lazadaAccountService->status($id,$data);
        return json(['message' => '操作成功']);
        exit("1111");
    }

    /**
     * @title 获取授权码
     * @url authorcode
     * @method post
     */
    public function authorcode()
    {
        $request = Request::instance();
        $client_id = $request->post('app_key', 0);
        if (empty($client_id)) {
            return json(['message' => '应用ID不能为空'], 400);
        }
        $url = 'https://auth.lazada.com/oauth/authorize?response_type=code&force_auth=true&redirect_uri=https://www.zrzsoft.com&client_id='. $client_id;
        return json(['url' => $url], 200);
    }

    /**
     * @title 查询lazada账号
     * @noauth
     * @url query
     * @method get
     */
    public function query(Request $request, lazadaAccountService $service)
    {
        $param = $request->param();
        $keyword = param($param, 'keyword');
        $page = param($param, 'page', 1);
        $pageSize = param($param, 'pageSize', 20);
        $json = $service->query($keyword, $page, $pageSize);
        return json($json);
    }

    /**
     * @title 获取Token
     * @url token
     * @method post
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
        $data['app_key'] = $request->post('app_key', 0);
        $data['app_secret'] = $request->post('app_secret', 0);
        $data['authorization_code'] = $request->post('authorization_code', 0);

        if (empty($data['app_secret']) ||  empty($data['authorization_code'])) {
            return json(['message' => '参数信息错误'], 400);
        }
        //获取操作人信息
        $user = CommonService::getUserInfo($request);
        if (!empty($user)) {
            $data['update_id'] = $user['user_id'];
        }
        $date = $this->lazadaAccountService->getToken($id,$data);
        return json(['message' => '获取成功','date' => $date]);
    }


    /**
     * @title 获取Token
     * @url refresh_token/:id
     * @method GET
     * @return \think\response\Json
     * @throws Exception
     */
    public function refresh_token($id = 0)
    {
        if($id == 0) {
            return json_error('缺少参数ID');
        }
        return $this->lazadaAccountService->refresh_token($id);
    }

    /**
     * @title 授权页面
     * @url authorization
     * @method post
     * @return \think\response\Json
     */
    public function authorization()
    {
        $request = Request::instance();
        $id = $request->post('id', 0);
        if (empty($id)) {
            return json(['message' => '参数错误'], 400);
        }
        $result = $this->lazadaAccountService->authorization($id);
        return json($result[0], 200);
    }

    /**
     * @title 获取Lazada站点
     * @url /lazada/site
     * @public
     * @method get
     * @return \think\Response
     */
    public function site()
    {
        $result = Cache::store('account')->lazadaSite();
        return json(array_values($result), 200);
    }

    /**
     * @title 批量修改账号的抓取状态
     * @url update_download
     * @method post
     * @return \think\response\Json
     */
    public function update_download(Request $request)
    {
        $params = $request->param();
        $ids = $request->post('ids');
        $data = $params;
        $model=$this->lazadaAccountService->update_download($ids,$data);
        return json(['message' => '操作成功', 'data' => $model]);

    }
}
