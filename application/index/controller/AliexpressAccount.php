<?php
namespace app\index\controller;

use app\common\service\ChannelAccountConst;
use app\index\service\AliexpressAccountHealthService;
use think\Exception;
use think\Request;
use app\common\controller\Base;
use think\Db;
use app\common\cache\Cache;
use service\aliexpress\AliexpressApi;
use app\common\model\aliexpress\AliexpressAccount as AliexpressAccountModel;
use think\Config as ThinkConfig;
use app\index\service\AliexpressAccountService;
use \service\alinew\AliexpressApi as AliexpressApiNew;
use app\common\model\ChannelAccountLog;
use app\index\service\AccountService;
use app\common\service\Common;

/**
 * @module 账号管理
 * @title 速卖通账号
 * @url aliexpress-account
 * @package app\index\controller
 * @author 叶瑞
 */
class AliexpressAccount extends Base
{
    /**
     * @title 显示资源列表
     * @return \think\Response
     * @apiRelate app\index\controller\MemberShip::memberInfo
     * @apiRelate app\index\controller\MemberShip::save
     * @apiRelate app\index\controller\MemberShip::update
     * @apiRelate app\index\controller\User::staffs
     */
    public function index(Request $request)
    {
        $list = (new AliexpressAccountService())->getList($request->param());

        if (!empty($list['data'])) {
            foreach ($list['data'] as &$v) {
                $this->updateAliexpressEnabled($v);
                $v['expiry_time'] = !empty($v['expiry_time']) ? date('Y-m-d', $v['expiry_time']) : '';
                $v['is_invalid'] = (int)$v['is_invalid'];
            }
        }
        return json(
            $list,
            200
        );
    }

    /*
     * @title 二维数组排序
     * @array 要排序的数组，引用值
     * @field 要排序的字段
     * @是否降序排序，如否则默认升序排序
     */
    private  function sortArrByOneField(&$array, $field, $desc = false)
    {
       $fieldArr = array();
       foreach ($array as $k => $v) {
         $fieldArr[$k] = $v[$field];
       }
    $sort = $desc == false ? SORT_ASC : SORT_DESC;
    array_multisort($fieldArr, $sort, $array);
    }

    /**
     * @desc 更新账号是否有效标识
     * @param array $data 速卖通账号信息
     * @author Jimmy
     * @date 2017-11-09 20:03:11
     */
    private function updateAliexpressEnabled(&$data)
    {
        try {
            //授权已失效
            if ($data['expiry_time'] < time()) {
                $data['aliexpress_enabled'] = 0;
                //修改表
                $model = AliexpressAccountModel::get($data['id']);
                if ($model) {
                    $model->aliexpress_enabled = 0;
                    $model->save();

                    //更新缓存
                    $cache = Cache::store('AliexpressAccount');
                    $cache->updateTableRecord($data['id'], 'aliexpress_enabled', $data['aliexpress_enabled']);
                    // foreach($data as $key=>$val) {
                    //     $cache->updateTableRecord($data['id'], $key, $val);
                    // }
                }
            }
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    /**
     * @title 保存新建的资源
     * @param  \think\Request $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        $params = $request->param();
        $data['account_name'] = trim($params['account_name']);
        $data['code'] = $request->post('code', '');
        $data['download_order'] = $request->post('download_order', 0);
        $data['sync_delivery'] = $request->post('is_sync', 0);
        $data['trad_percent'] = $request->post('trad_percent', 0);
        $data['download_message'] = $request->post('download_message', 0);
        $data['download_listing'] = $request->post('download_listing', 0);
        $data['download_health'] = $request->post('download_health', 0);
        $aliexpressAccountModel = new AliexpressAccountModel();
        $validateAccount = validate('AliexpressAccount');
        if (!$validateAccount->check($data)) {
            return json(['message' => $validateAccount->getError()], 400);
        }
        \app\index\service\BasicAccountService::isHasCode(ChannelAccountConst::channel_aliExpress,$data['code']);
        //启动事务
        Db::startTrans();
        try {
            $data['create_time'] = time();
            $aliexpressAccountModel->allowField(true)->isUpdate(false)->save($data);
            //获取最新的数据返回
            $new_id = $aliexpressAccountModel->id;
            Db::commit();
            //删除缓存
            //Cache::handler()->del('cache:AliexpressAccount');
            //开通后立即加一条数据；
            if (isset($data['download_health'])) {
                (new AliexpressAccountHealthService())->openHealth($new_id, $data['download_health']);
            }
            $data['id'] = $new_id;
            //$account = \app\common\model\aliexpress\AliexpressAccount::get($new_id);
            Cache::store('AliexpressAccount')->readTable($new_id);
            return json(['id' => $new_id,'message' => '新增成功'], 200);
        } catch (\Exception $e) {
            Db::rollback();
            return json(['message' => '新增失败'], 500);
        }
    }

    /**
     * @title 显示指定的资源
     * @param  int $id
     * @return \think\Response
     */
    public function read($id)
    {
        $accountModel = new AliexpressAccountModel();
        $result = $accountModel->field('base_account_id,code,account_name,download_order,download_health,sync_delivery, trad_percent, download_message,download_listing')->where(['id' => $id])->find();
        $result && $result['site_status'] = AccountService::getSiteStatus($result['base_account_id'], $result['code']);
        return json($result, 200);
    }

    /**
     * @title 显示编辑资源表单页.
     * @param  int $id
     * @return \think\Response
     */
    public function edit($id)
    {
        $accountModel = new AliexpressAccountModel();
        $result = $accountModel->field('id,code,base_account_id,account_name,download_order,download_health,sync_delivery, trad_percent, download_message,download_listing')->where(['id' => $id])->find();
        $result && $result['site_status'] = AccountService::getSiteStatus($result['base_account_id'], $result['code']);
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
        
        $aliexpressAccountModel = new AliexpressAccountModel();
        if ($aliexpressAccountModel->isHas($id, $data['code'])) {
            return json(['message' => '代码已存在'], 400);
        }

        $new_data = $data;
        isset($params['site_status']) and $new_data['site_status'] = intval($params['site_status']);

        $old_data = $aliexpressAccountModel::get($id);

        $user = Common::getUserInfo($request);
        $operator = [];
        $operator['operator_id'] = $user['user_id'];
        $operator['operator'] = $user['realname'];

        //启动事务
        Db::startTrans();
        try {
            $data['update_time'] = time();
            $aliexpressAccountModel->allowField(true)->save($data, ['id' => $id]);

            if (in_array($new_data['site_status'], [1, 2, 3, 4])) {
                $old_data['site_status'] = AccountService::setSite(
                    ChannelAccountConst::channel_aliExpress,
                    $old_data['base_account_id'],
                    $old_data['code'],
                    $operator['operator_id'],
                    $new_data['site_status']
                );
            }
            $operator['account_id'] = $old_data['id'];
            AliexpressAccountService::addAliexpressLog($operator, 1, $new_data, $old_data);

            Db::commit();

            //开通后立即加一条数据；
            if (isset($data['download_health'])) {
                (new AliexpressAccountHealthService())->openHealth($id, $data['download_health']);
            }

            //更新缓存
            $cache = Cache::store('AliexpressAccount');
            foreach($data as $key=>$val) {
                $cache->updateTableRecord($id, $key, $val);
            }
            return json(['message' => '更新成功'], 200);
        } catch (Exception $e) {
            Db::rollback();
            return json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * @title 停用，启用账号
     * @method post
     * @url states
     */
    public function changeStates(Request $request)
    {
        $id = (int) $request->post('id', 0);
        $data['is_invalid'] = (int) $request->post('is_invalid', 0);
        $accountModel = new AliexpressAccountModel();
        /**
         * 判断账号是否存在
         */
        $is_invalid = $accountModel->where('id', $id)->value('is_invalid');
        if (is_null($is_invalid)) {
            return json(['message' => '账号不存在'], 400);
        }
        /**
         * 日志数据处理
         */
        $new_data = $data;
        $old_data = ['is_invalid' => $is_invalid];

        $userInfo = Common::getUserInfo($request);
        $operator = [];
        $operator['operator_id'] = $userInfo['user_id'];
        $operator['operator'] = $userInfo['realname'];
        $operator['account_id'] = $id;

        try {
            $accountModel->allowField(true)->save($data, ['id' => $id]);
            AliexpressAccountService::addAliexpressLog($operator, 1, $new_data, $old_data);
            //更新缓存
            $cache = Cache::store('AliexpressAccount');
            foreach($data as $key=>$val) {
                $cache->updateTableRecord($id, $key, $val);
            }
            return json(['message' => '操作成功'], 200);
        } catch (Exception $e) {
            return json(['message' => '操作失败'], 500);
        }
    }

    /**
     * @title 显示授权页面
     * @method post
     * @url authorization
     * @return \think\response\Json
     */
    public function authorization()
    {
        $request = Request::instance();
        $id = $request->post('id', 0);
        if (empty($id)) {
            return json(['message' => '参数错误'], 400);
        }
        $accountModel = new AliexpressAccountModel();
        $result = $accountModel->field('client_id,client_secret')->where(['id' => $id])->select();
        return json($result, 200);
    }

    /**
     * @title 为已授权的用户开通消息服务
     * @method get
     * @url user-permit
     * @return \think\Response\Json
     */
    public function userPermit()
    {
        $params = $this->request->param();
        if(!isset($params['account_id'])||empty($params['account_id'])||!isset($params['topics_id'])||empty($params['topics_id'])) {
            return json(['message' => '参数错误'], 400);
        }
        $account_id = $params['account_id'];//登录账号ID
        $topics_ids = param($params,'topics_id') ? explode(',',$params['topics_id']) : [];
        
        $service = new AliexpressAccountService();
        $nupRe = $service->notificationUserPermit($account_id, $topics_ids);
        
        return json(['message'=>$nupRe['message']], 200);
    }
    
    /**
     * @title 批量为已授权的用户开通消息服务
     * @method get
     * @url user-permit-batch
     * @return \think\Response\Json
     */
    public function userPermitBatch()
    {
//         set_time_limit(0);
//         $where = [
//             'update_time'=>['>',0],
//             'is_invalid'=>1,
//             'is_authorization'=>1,
//             'aliexpress_enabled'=>1,
//             'download_order'=>['>',0],
//             'update_time'=>0,
//             'user_nick'=>['exp',"!=''"],
//         ];
//         $acc_arrs = AliexpressAccountModel::where($where)->field('id,update_time')->select();
//         var_dump(count($acc_arrs));
//         $topics_ids = [1,7,11];
//         $id_str = '';
//         foreach ($acc_arrs as $acc_arr){
//             $service = new AliexpressAccountService();
//             $nupRe = $service->notificationUserPermit($acc_arr['id'], $topics_ids);
//             if($nupRe['ask']){
//                 $id_str .= $acc_arr['id'] . ',' ;
//             }
//         }
//         echo $id_str;
//         die;
        
        $params = $this->request->param();
        if (!isset($params['account_ids'])||empty($params['account_ids'])||!isset($params['topics_id'])||empty($params['topics_id'])) {
            return json(['message' => '参数错误'], 400);
        }
        $account_ids = explode(',',$params['account_ids']);//登录账号ID
        $topics_ids = param($params,'topics_id') ? explode(',',$params['topics_id']) : [];
        
        $service = new AliexpressAccountService();
        $nupRe = $service->notificationUserPermitBatch($account_ids, $topics_ids);
        if(!empty($nupRe['errors'])){
            return json(['message' => '操作异常:<br/>'.join('<br/>', $nupRe['errors'])], 200);
        }else{
            return json(['message' => '批量操作成功'], 200);
        }
    }

    /**
     * @title 获取已开通消息主题列表
     * @method get
     * @url topic
     * @return \think\Response\Json
     */
    public function notificationTopicList(){
        $params = $this->request->param();
        if (!isset($params['account_id'])||empty($params['account_id'])) {
            return json(['message' => '参数错误'], 400);
        }
        
        $model = new AliexpressAccountModel();
        $topics = $model::where(['id'=>$params['account_id']])->field('topics')->find();
        if(empty($topics['topics'])){//如果数据库消息主题为空，返回默认的数据给前端
            $result = AliexpressAccountService::$topicListMap;
            return json($result);
        }else{
            $result = $topics['topics'];
            return $result;
        }
    }

    /**
     * @title 取消用户的消息服务
     * @method post
     * @url userCancel
     * @author johnny <1589556545@qq.com>
     * @date 2018-06-05 15:18:11
     */
    public function userCancel(){
        $params = $this->request->param();
        if (!isset($params['account_id'])||empty($params['account_id'])) {
            return json(['message' => '参数错误'], 400);
        }
        $account_id=$params['account_id'];
        $service=new AliexpressAccountService();
        $config=$service->getConfig($account_id);
        $api = AliexpressApiNew::instance($config)->loader('MessageNotification');

        $nick='cn1518808694uwth';
        $user_platform='ae';
        $res=$api->userCancel($nick,$user_platform);
        $res=$service->dealResponse($res);
        return json($res, 200);
    }

    /**
     * 获取列表
     */

    /**
     * @title 获得用户已开通的消息服务
     */
    private function userGet($config){
        $api = AliexpressApiNew::instance($config)->loader('MessageNotification');
        $field_arr = ['user_nick','topics'];
        $nick='cn1518808694uwth';
        $res=$api->userGet($field_arr,$nick, 'icbu');
        $service=new AliexpressAccountService();
        $res=$service->dealResponse($res);
        var_dump($res);
        return json($res, 200);
    }

    /**
     * @title 获取授权码
     * @method post
     * @url getAuthorCode
     */
    public function getAuthorCode()
    {
        $params = $this->request->param();
        if (empty($params['client_id'])) {
            return json(['message' => '请求错误，请输入账户ID！'], 400);
        }
        $redirectUrl = 'http://14.118.130.21';
        $url = "https://oauth.aliexpress.com/authorize?response_type=code&client_id={$params['client_id']}&redirect_uri={$redirectUrl}&state=授权token&view=web&sp=ae";
        return json(['url' => $url], 200);
    }

    private function getTokenOld()
    {
        $request = Request::instance();
        $id = $request->post('id', 0);
        
        if (empty($id)) {
            return json(['message' => '参数错误'], 400);
        }

        $data['client_id'] = $request->post('client_id', 0);
        $data['client_secret'] = $request->post('client_secret', 0);
        $data['code'] = $request->post('authorization_code', 0);
        $data['redirect_uri'] = ThinkConfig::get('redirect_uri');

        if (empty($data['client_id']) || empty($data['client_secret']) || empty($data['code'])) {
            return json(['message' => '账号信息错误'], 400);
        }

        $common = AliexpressApi::instance($data)->loader('common');
        $result = $common->getToken($data);

        if ($result && isset($result['access_token'])) {
            $data['access_token'] = $result['access_token'];
            $data['refresh_token'] = $result['refresh_token'];
            $data['expiry_time'] = $common->convertTime($result['refresh_token_timeout']);
        } else {
            return json(['message' => '获取失败'], 500);
        }
        
        $data['update_time'] = time();
        try {
            unset($data['code']);
            $accountModel = AliexpressAccountModel::get(['id' => $id]);
            
            $data['is_authorization'] = 1;
            $accountModel->allowField(true)->save($data, ['id' => $id]);

            //更新缓存
            $cache = Cache::store('AliexpressAccount');
            foreach($data as $key=>$val) {
                $cache->updateTableRecord($id, $key, $val);
            }
            return json(['message' => '获取成功'], 200);
        } catch (Exception $e) {
            return json(['message' => '获取失败'], 500);
        }
    }
    /**
     * @title 获取Token
     * @method post
     * @url getToken
     * @author Jimmy <554511322@qq.com>
     * @date 2018-04-10 11:02:11
     * @return \think\response\Json
     * @throws Exception
     */
    public function getToken(Request $request)
    {
        try {
            //验证数据信息
            $params = $request->param();

            $userInfo = Common::getUserInfo($request);
            $params['user_id'] = $userInfo['user_id'];
            $params['realname'] = $userInfo['realname'];

            $service = new AliexpressAccountService();
            $service->getToken($params);
            return json(['message' => '操作成功'], 200);
        } catch (Exception $ex) {
            return json(['message' => '获取失败' . $ex->getMessage()], 500);
        }
    }

    /**
     * @title 批量设置
     * @method post
     * @url batch-update
     * @param Request $request
     * @return \think\response\Json
     * @throws \Exception
     * @author Reece
     * @date 2018-08-14 17:51:14
     */
    public function batchUpdate(Request $request)
    {
        try{
            $params = $request->post();
            if(!param($params, 'ids')) throw new Exception('请先选择数据');
            $ids = json_decode($params['ids'], true);
            if (!$ids) {
                throw new Exception('数据格式错误');
            }
            $userInfo = Common::getUserInfo($request);
            $params['user_id'] = $userInfo['user_id'];
            $params['realname'] = $userInfo['realname'];

            $service = new AliexpressAccountService();
            $result = $service->batchUpdate($ids, $params);
            return json(['message'=>'操作成功', 'data'=>$result]);
        }catch (Exception $ex){
            return json(['message'=>$ex->getMessage()], 400);
        }
    }

    /**
     * @title 获取Aliexpress账号日志
     * @method get
     * @url /aliexpress-account/log/:id
     * @param  \think\Request $request
     * @param  string $site
     * @return \think\Response
     */
    public function getLog(Request $request)
    {
        return json(
            (new AliexpressAccountService)->getAliexpressLog($request->param())
            , 200
        );
    }
}
