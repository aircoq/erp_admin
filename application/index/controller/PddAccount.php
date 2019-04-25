<?php
namespace app\index\controller;
use think\Db;
use app\common\exception\JsonErrorException;
use app\common\service\Common;
use think\Exception;
use think\Request;
use app\common\controller\Base;
use app\common\service\Common as CommonService;
use app\index\service\PddAccountService;
use app\common\cache\Cache;
/**
 * @module 账号管理
 * @title pdd账号管理
 * @author phill
 * @url /pdd-account
 * Class Pdd
 * @package app\index\controller
 */
class PddAccount extends Base
{
    protected $pddAccountService;

    protected function init()
    {
        if(is_null($this->pddAccountService)){
            $this->pddAccountService = new PddAccountService();
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

        $result = $this->pddAccountService->getList($request->param());
        return json($result, 200);
    }


    /**
     * @title 添加账号
     * @method POST
     * @url /pdd-account
     * @param Request $request
     * @return \think\response\Json
     */
    public function add(Request $request)
    {
        try{
            $params = $request->param();
            $user = Common::getUserInfo($request);
            $uid = $user['user_id'];
            $response = $this->pddAccountService->add($params,$uid);
            if($response === false) {
                return json(['message' => $this->pddAccountService->getError()], 400);
            }
            return json(['message' => '添加成功','data' => $response]);
        }catch (Exception $exp){
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }

    /**
     * @title 显示指定的资源
     * @method GET
     * @param  int $id
     * @url /pdd-account/:id
     * @return \think\Response
     */
    public function read($id)
    {
        $result = $this->pddAccountService->read($id);
        return json($result, 200);
    }

    /**
     * @title 更新账号
     * @method PUT
     * @url /pdd-account
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
            $response = $this->pddAccountService->add($params,$uid);
            if($response === false) {
                return json(['message' => $this->pddAccountService->getError()], 400);
            }
            return json(['message' => '更新成功','data' => $response]);
        }catch (Exception $exp){
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }



    /**
     * @title 停用，启用账号
     * @url states
     * @method post
     */

    public function changeStatus(Request $request)
    {
        $params = $request->param();
        $user = Common::getUserInfo($request);
        if (!empty($user)) {
            $params['user_id'] = $user['user_id'];
            $params['realname'] = $user['realname'];
        }
        $response = $this->pddAccountService->changeStatus($params);
        if($response === false) {
            return json(['message' => $this->pddAccountService->getError()], 400);
        }
        return json(['message' => '操作成功','data' => $response]);
    }


    /**
     * @title 获取授权码
     * @url authorcode
     * @method post
     */
    public function authorcode()
    {
        $request = Request::instance();
        $client_id = $request->post('client_id', 0);
        if (empty($client_id)) {
            return json(['message' => '应用ID不能为空'], 400);
        }
        $url = 'https://mms.pinduoduo.com/open.html?response_type=code&client_id=70ce27f9d2c44086b77d04ccb1bb42eb&redirect_uri=https://47.90.53.65/test.php&state=1212';
        return json(['url' => $url], 200);
    }

    /**
     * @title 查询pdd账号
     * @noauth
     * @url query
     * @method get
     */
    public function query(Request $request, pddAccountService $service)
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
        $data['client_id'] = $request->post('client_id', 0);
        $data['client_secret'] = $request->post('client_secret', 0);
        $data['authorization_code'] = $request->post('authorization_code', 0);
        if (empty($data['client_secret']) ||  empty($data['authorization_code'])) {
            return json(['message' => '参数信息错误'], 400);
        }
        //获取操作人信息
        $user = CommonService::getUserInfo($request);
        if (!empty($user)) {
            $data['update_id'] = $user['user_id'];
        }
        $date = $this->pddAccountService->getToken($id,$data);
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
        //echo(111);die;
        if($id == 0) {
            return json_error('缺少参数ID');
        }
        return $this->pddAccountService->refresh_token($id);
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
        $result = $this->pddAccountService->authorization($id);
        return json($result[0], 200);
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
        $model=$this->pddAccountService->update_download($ids,$params);
        return json(['message' => '操作成功', 'data' => $model]);

    }

    /**
     * @title 获取拼多多账号日志
     * @method get
     * @url /pdd-account/log/:id
     * @param  \think\Request $request
     * @param  string $site
     * @return \think\Response
     */
    public function getLog(Request $request)
    {
        return json(
            $this->pddAccountService->getPddLog($request->param())
            , 200
        );
    }
}
