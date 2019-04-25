<?php
namespace app\index\controller;

use app\common\cache\Cache;
use app\common\service\ChannelAccountConst;
use think\Request;
use app\common\controller\Base;
use app\common\service\Common as CommonService;
use app\common\model\joom\JoomAccount as JoomAccountModel;
use app\index\service\JoomAccountService;
use app\index\service\AccountService;
use think\Db;
use think\Exception;

/**
 * @module 账号管理
 * @title joom账号管理
 * @author zhangdongdong
 * @url /joom-account
 * Class Joom
 * @package app\index\controller
 */
class JoomAccount extends Base
{
    protected $joomAccountService;

    public function __construct()
    {
        parent::__construct();
        if(is_null($this->joomAccountService)){
            $this->joomAccountService = new JoomAccountService();
        }
    }

    /**
     * @title joom帐号列表
     * @method GET
     * @url /joom-account
     * @return \think\Response
     */
    public function index()
    {
        $request = Request::instance();

        $result = $this->joomAccountService->getList($request->param());
        // $result = $this->joomAccountService->accountList($request);
        return json($result, 200);
    }

    /**
     * @title 保存新建的资源
     * @method POST
     * @url /joom-account
     * @param  \think\Request $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        $params = $request->param();
        $data = $params;
        $result = $this->validate($data, [
            'name|joom用户名' => 'require|unique:joom_account,account_name|length:2,50',
            'code|帐户简称' => 'require|unique:joom_account,code|length:2,50',
            'company|公司名称' => 'require|length:3,255',
        ]);
        if ($result !== true) {
            return json(['message' => $result], 400);
        }

        //必须要去账号基础资料里备案
        \app\index\service\BasicAccountService::isHasCode(ChannelAccountConst::channel_Joom,$data['code']);
        //获取操作人信息
        $user = CommonService::getUserInfo($request);
        if (!empty($user)) {
            $data['creator_id'] = $user['user_id'];
        }
        $accountInfo = $this->joomAccountService->save($data);
        return json(['message' => '新增成功','data' => $accountInfo]);
    }

    /**
     * @title 显示指定的资源
     * @param  int $id
     * @method GET
     * @url /joom-account/:id
     * @return \think\Response
     */
    public function read($id)
    {
        $result = $this->joomAccountService->read($id);
        return json($result, 200);
    }

    /**
     * @title 显示指定的资源
     * @param  int $id
     * @method GET
     * @url /joom-account/:id/edit
     * @return \think\Response
     */
    public function edit($id)
    {
        $result = $this->joomAccountService->read($id);
        return json($result, 200);
    }

    /**
     * @title 保存更新的资源
     * @param  \think\Request $request
     * @param  int $id
     * @method PUT
     * @url /joom-account/:id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        $params = $request->param();
        $data = $params;
        $result = $this->validate($data, [
            'name|joom用户名' => 'require|unique:joom_account,account_name|length:2,50',
            'company|公司名称' => 'require|length:3,255',
        ]);
        if ($result !== true) {
            return json(['message' => $result], 400);
        }
        //获取操作人信息
        $user = CommonService::getUserInfo($request);
        if (!empty($user)) {
            $data['updater_id'] = $user['user_id'];
            $data['realname'] = $user['realname'];
        }
        $this->joomAccountService->update($id,$data);
        return json(['message' => '更改成功']);
    }

    /**
     * @title JOOM账号停用，启用
     * @method POST
     * @url /joom-account/status
     * @return \think\Response
     */
    public function changeStatus()
    {
        $request = Request::instance();
        $id = $request->post('id', 0);
        $data = $request->post();

        //判断参数是否存在；
        if(!isset($data['is_invalid']) && !isset($data['platform_status'])) {
            return json(['message' => '参数为空，请传参数 is_invalid 或 platform_status ']);
        }

        //获取操作人信息
        $user = CommonService::getUserInfo($request);
        if (!empty($user)) {
            $data['updater_id'] = $user['user_id'];
            $data['realname'] = $user['realname'];
        }
        $this->joomAccountService->status($id,$data);
        return json(['message' => '操作成功']);
    }

    /**
     * @title 批量开启
     * @url batch-set
     * @method post
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\Exception
     */
    public function batchSet(Request $request)
    {
        $params = $request->post();
        $result = $this->validate($params, [
            'ids|帐号ID' => 'require|min:1',
            'is_invalid|系统状态' => 'require|number',
        ]);

        if ($result != true) {
            throw new Exception($result);
        }

        //实例化模型
        $model = new JoomAccountModel();

        if (isset($params['is_invalid']) && $params['is_invalid'] != '') {
            $data['is_invalid'] = (int)$params['is_invalid'];   //1 启用， 0未启用
        }

        $idArr = array_merge(array_filter(array_unique(explode(',',$params['ids']))));

        //开启事务
        Db::startTrans();
        try {
            if (empty($data)) {
                return json(['message' => '数据参数不能为空'], 200);
            }

            $data['update_time'] = time();
            $model->allowField(true)->update($data,['id' => ['in', $idArr]]);

            $new_data = $data;
            $new_data['site_status'] = !empty($params['site_status']) ? intval($params['site_status']) : 0;
            $user = CommonService::getUserInfo($request);
            $operator = [];
            $operator['operator_id'] = $user['user_id'];
            $operator['operator'] = $user['realname'];

            foreach ($idArr as $id) {
                $old_data = $model::get($id);
                $operator['account_id'] = $id;
                if (in_array($new_data['site_status'], [1, 2, 3, 4])) {
                    $old_data['site_status'] = AccountService::setSite(
                        ChannelAccountConst::channel_Joom,
                        $old_data['base_account_id'],
                        $old_data['code'],
                        $operator['operator_id'],
                        $new_data['site_status']
                    );
                }
                JoomAccountService::addJoomLog($operator, 1, $new_data, $old_data);
            }
            Db::commit();

            //更新缓存
            $cache = Cache::store('JoomAccount');
            foreach ($idArr as $id) {
                foreach ($data as $k => $v) {
                    $cache->updateTableRecord($id, $k, $v);
                }
            }
            return json(['message' => '更新成功'], 200);
        } catch (Exception $ex) {
            Db::rollback();
            return json(['message' => '更新失败'.$ex->getMessage()], 400);
        }
    }

    /**
     * @title 获取Joom账号日志
     * @method get
     * @url /joom-account/log/:id
     * @param  \think\Request $request
     * @param  string $site
     * @return \think\Response
     */
    public function getLog(Request $request)
    {
        return json(
            (new JoomAccountService)->getJoomLog($request->param())
            , 200
        );
    }
}
