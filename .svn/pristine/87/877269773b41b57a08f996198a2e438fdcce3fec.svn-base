<?php

namespace app\index\controller;

use app\common\service\ChannelAccountConst;
use app\index\service\AmazonAccountHealthService;
use think\Exception;
use think\Request;
use app\common\controller\Base;
use app\common\service\Common as CommonService;
use think\Db;
use app\common\cache\Cache;
use app\common\model\amazon\AmazonAccount as AmazonAccountModel;
use app\index\service\AmazonAccountService;
use app\index\service\AccountService;

/**
 * @title  amazon账号管理
 * @author ZhaiBin
 * @module 账号管理
 * Class Amazon
 * @package
 */
class AmazonAccount extends Base
{
    /**
     * @title Amazon账号列表
     * @module 账号管理
     * @url /amazon-account
     * @return \think\Response
     * @apiRelate app\index\controller\MemberShip::memberInfo
     * @apiRelate app\index\controller\MemberShip::save
     * @apiRelate app\index\controller\MemberShip::update
     * @apiRelate app\index\controller\User::staffs
     */
    public function index(Request $request)
    {
        if (isset($request->header()['X-Result-Fields'])) {
            $field = $request->header()['X-Result-Fields'];
        }

        $res = (new AmazonAccountService)->getList($request->param());
        return json($res,200);
    }

    /**
     * @title 保存账号信息
     * @method post
     * @url /amazon-account
     * @param  \think\Request $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        $params = $request->param();
        $data['account_name'] = $params['account_name'];
        $data['code'] = $params['code'];
        $data['site'] = $params['site'];
        $data['assessment_of_usage']=$request->post('assessment_of_usage',0);//账号使用情况考核 0-开启 1-不开启
        $data['download_order'] = $request->post('download_order', 0);
        $data['sync_delivery'] = $request->post('sync_delivery', 0);
        $data['sync_feedback'] = $request->post('sync_feedback', 0);
        $data['download_order'] = $request->post('download_listing', 0);
        $data['download_health'] = $request->post('download_health', 0);
        if (empty($data['account_name'])) {
            return json(['message' => 'account_name 不能为空'], 400);
        }
        
//        $data['is_authorization'] = 1;
//         //判断授权；
//         if (!empty($data['merchant_id']) && !empty($data['access_key_id']) && !empty($data['secret_key'])) {
//             $data['is_authorization'] = 1;
//         } else {
//             $data['is_authorization'] = 0;
//         }

        $amazonAccount = new AmazonAccountModel();
        $validateAccount = validate('AmazonAccount');
        if (!$validateAccount->scene('add')->check($data)) {
            return json(['message' => $validateAccount->getError()], 400);
        }
        $re = $amazonAccount->where(['account_name' => trim($params['account_name'])])->find();
        if ($re) {
            return json(['message' => '账户名重复.'], 400);
        }
        \app\index\service\BasicAccountService::isHasCode(ChannelAccountConst::channel_amazon,$data['code'],$data['site']);
        //启动事务
        Db::startTrans();
        try {
            $data['create_time'] = time();
            //获取操作人信息
            $user = CommonService::getUserInfo($request);
            $data['created_user_id'] = $user['user_id'];
            $amazonAccount->allowField(true)->isUpdate(false)->save($data);


            //开通wish服务时，新增一条list数据，如果存在，则不加
            if (isset($data['download_health'])) {
                (new AmazonAccountHealthService())->openAmazonHealth($amazonAccount->id, $data['download_health']);
            }

            Db::commit();
            //新增缓存
            Cache::store('AmazonAccount')->setTableRecord($amazonAccount->id);
            return json(['message' => '新增成功', 'id' => $amazonAccount->id], 200);
        } catch (\Exception $e) {
            Db::rollback();
            return json(['message' => '新增失败'], 500);
        }
    }

    /**
     * @title 显示指定Amazon账号
     * @method get
     * @url /amazon-account/:id(\d+)
     * @param  int $id
     * @return \think\Response
     */
    public function read($id)
    {
        $account = Cache::store('AmazonAccount')->getTableRecord($id);
        $result = [$account];
        $result[0]['assessment_of_usage']=(int)$result[0]['assessment_of_usage'];
        $result[0]['download_order'] = (int)$result[0]['download_order'];
        $result[0]['sync_delivery'] = (int)$result[0]['sync_delivery'];
        $result[0]['sync_feedback'] = (int)$result[0]['sync_feedback'];
        $result[0]['download_listing'] = (int)$result[0]['download_listing'];
        $result[0]['download_health'] = (int)$result[0]['download_health'];
        $result[0]['authorization_type'] = (int)$result[0]['authorization_type'];
        return json($result, 200);
    }

    /**
     * @title 编辑Amazon账号
     * @url /amazon-account/:id(\d+)/edit
     * @method get
     * @param  int $id
     * @return \think\Response
     */
    public function edit($id)
    {
        $account = Cache::store('AmazonAccount')->getTableRecord($id);
        $result = [$account];
        $result[0]['download_order'] = (int)$result[0]['download_order'];
        $result[0]['sync_delivery'] = (int)$result[0]['sync_delivery'];
        $result[0]['sync_feedback'] = (int)$result[0]['sync_feedback'];
        $result[0]['download_listing'] = (int)$result[0]['download_listing'];
        $result[0]['download_health'] = (int)$result[0]['download_health'];
        $result[0]['authorization_type'] = (int)$result[0]['authorization_type'];
        $result[0]['site_status'] = $account ? AccountService::getSiteStatus($account['base_account_id'], $account['code']) : null;
        return json($result, 200);
    }

    /**
     * @title 更新Amazon账号
     * @method put
     * @url /amazon-account/:id(\d+)
     * @param  \think\Request $request
     * @param  int $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        $params = $request->param();
        $data['code'] = $params['code'];
        $data['assessment_of_usage']=$params['assessment_of_usage'];
        $data['download_order'] = $params['download_order'];
        $data['sync_delivery'] = $params['sync_delivery'];
        $data['sync_feedback'] = $params['sync_feedback'];
        $data['download_listing'] = $params['download_listing'];
        $data['download_health'] = $params['download_health'];
        $data['id'] = $id;
        $data['site'] = $params['site'];
        $validateAccount = validate('AmazonAccount');
        if (!$validateAccount->scene('edit')->check($data)) {
            return json(['message' => $validateAccount->getError()], 400);
        }

        $new_data = $data;
        $new_data['site_status'] = !empty($params['site_status']) ? intval($params['site_status']) : 0;
        $user = CommonService::getUserInfo($request);
        $operator = [];
        $operator['operator_id'] = $user['user_id'];
        $operator['operator'] = $user['realname'];
        $operator['account_id'] = $id;
        //判断授权；
//         if (!empty($data['merchant_id']) && !empty($data['access_key_id']) && !empty($data['secret_key'])) {
//             $data['is_authorization'] = 1;
//         } else {
//             $data['is_authorization'] = 0;
//         }

        $model = new AmazonAccountModel();
        //启动事务
        Db::startTrans();
        try {
            if (empty($data)) {
                return json(['message' => '数据参数不能为空'], 200);
            }
            $data['updated_time'] = time();

            $old_data = $model::get($id);
            $model->allowField(true)->save($data, ['id' => $id]);

            //开通wish服务时，新增一条list数据，如果存在，则不加
            if (isset($data['download_health'])) {
                (new AmazonAccountHealthService())->openAmazonHealth($id, $data['download_health']);
            }

            if (in_array($new_data['site_status'], [1, 2, 3, 4])) {
                $old_data['site_status'] = AccountService::setSite(
                    ChannelAccountConst::channel_amazon,
                    $old_data['base_account_id'],
                    $old_data['code'],
                    $operator['operator_id'],
                    $new_data['site_status']
                );
            }
            AmazonAccountService::addAmazonLog($operator, 1, $new_data, $old_data);

            Db::commit();

            //更新缓存
            $cache = Cache::store('AmazonAccount');
            foreach ($data as $key => $val) {
                $cache->updateTableRecord($id, $key, $val);
            }
            return json(['message' => "更新成功!"], 200);
        } catch (\Exception $e) {
            Db::rollback();
            return json(['message' => '更新失败'], 500);
        }
    }


    /**
     * @title 批量设置amazon账号有效状态
     * @method put
     * @url /amazon-account/batch-set-valid
     * @param Request $request
     * @author linpeng
     * @return \think\response\Json
     */
    public function batchUpdateIsValid(Request $request)
    {
        try {
            $params = $request->param();
            $ids = json_decode(param($params,'ids'),true);
            if (!count($ids)) {
                return json(['message' => '无选定账号'],400);
            }
            $model = new AmazonAccountModel();
            $update['is_invalid'] = 1;
            $res = $model->allowField(true)->save($update,['id' => ['in',$ids]]);
            $cache = Cache::store('AmazonAccount');

            // 更新缓存
            if ($res) {
                foreach ($ids as $id){
                    $cache->updateTableRecord($id, 'is_invalid', '1');
                }
            }

            return json(['message' => "更新成功!"], 200);
        } catch (Exception $ex) {
            return json(['message' => $ex->getMessage()], 400);
        }

    }

    /**
     * @title 更新Amazon账号授权信息
     * @method put
     * @url /amazon-account-token/:id(\d+)
     * @param Request $request
     * @param $id
     * @return \think\response\Json
     */
    public function saveToken(Request $request, $id)
    {
        $params = $request->param();
        $data['account_name'] = $params['account_name'];
        $data['code'] = $params['code'];
        $data['site'] = $params['site'];
        $data['id'] = $id;
        $data['merchant_id'] = trim($params['merchant_id']);
        $data['access_key_id'] = trim($params['access_key_id']);
        $data['secret_key'] = trim($params['secret_key']);
        $data['authorization_type'] = trim($params['authorization_type']);//授权类型，0：自授权，1：第三方授权
        $data['developer_access_key_id'] = trim($params['developer_access_key_id']);
        $data['developer_secret_key'] = trim($params['developer_secret_key']);
        $data['auth_token'] = trim($params['auth_token']);
        if(empty($data['merchant_id'])) {
            return json(['message' => 'merchant_id 不能为空'], 400);
        }
        if (empty($data['site'])) {
            return json(['message' => '站点 不能为空'], 400);
        }
        if(!in_array($data['authorization_type'], ['0','1'])){
            return json(['message' => '授权类型 错误'], 400);
        }
        if($data['authorization_type']=='0'){
            if (empty($data['access_key_id'])) {
                return json(['message' => 'API密码 不能为空'], 400);
            }
            if (empty($data['secret_key'])) {
                return json(['message' => 'API签名 不能为空'], 400);
            }
            //自授权,清空三方授权数据
            $data['developer_access_key_id'] = $data['developer_secret_key'] = $data['auth_token'] = '';
        }else{
            if (empty($data['developer_access_key_id'])) {
                return json(['message' => '第三方API密码 不能为空'], 400);
            }
            if (empty($data['developer_secret_key'])) {
                return json(['message' => '第三方API签名 不能为空'], 400);
            }
            if (empty($data['auth_token'])) {
                return json(['message' => '第三方授权Token 不能为空'], 400);
            }
        }
        if (empty($data['account_name'])) {
            return json(['message' => 'account_name 不能为空'], 400);
        }

         //判断授权；
//         if (!empty($data['merchant_id']) && !empty($data['access_key_id']) && !empty($data['secret_key'])) {
//             $data['is_authorization'] = 1;
//         } else {
//             $data['is_authorization'] = 0;
//         }
        $data['is_authorization'] = 1;
        $data['is_invalid'] = 1;
        $model = new AmazonAccountModel();
         if (!$id) {
             return json(['message' => 'id 不能为空'], 400);
         }
         $res = $model->field('id')->where('id', $id)->find();
         if (!$res) {
             return json(['message' => '账号不存在'], 400);
         }
        Db::startTrans();
        try {
            if (empty($data)) {
                return json(['message' => '数据参数不能为空'], 200);
            }
            $data['updated_time'] = time();
            $model->allowField(true)->save($data, ['id' => $id]);

            //开通wish服务时，新增一条list数据，如果存在，则不加
            if (isset($data['download_health'])) {
                (new AmazonAccountHealthService())->openAmazonHealth($id, $data['download_health']);
            }
            Db::commit();
            //更新缓存
            $cache = Cache::store('AmazonAccount');
            foreach ($data as $key => $val) {
                $cache->updateTableRecord($id, $key, $val);
            }
            return json(['message' => "更新成功!"], 200);
        } catch (\Exception $e) {
            Db::rollback();
            return json(['message' => '更新失败'], 500);
        }
    }

    /**
     * @title amazon批量设置抓取参数；
     * @author 冬
     * @method post
     * @apiParam ids:多个ID用英文,分割
     * @url /amazon-account/set
     */
    public function batchSet(Request $request)
    {
        try {
            $params = $request->post();
            $result = $this->validate($params, [
                'ids|帐号ID' => 'require|min:1',
                'status|系统状态' => 'require|number',
                'download_listing|抓取Amazon Listing功能' => 'require|number',
                'download_order|抓取Amazon订单功能' => 'require|number',
                'download_health|抓取Amazon健康功能' => 'require|number',
                'sync_delivery|同步发货状态到Amazon功能' => 'require|number',
                'sync_feedback|同步中差评到Amazon功能' => 'require|number',
            ]);
            if ($result !== true) {
                throw new Exception($result);
            }
            $model = new AmazonAccountModel();

            $data['status'] = (int)$params['status'];
            $data['download_listing'] = (int)$params['download_listing'];
            $data['download_health'] = (int)$params['download_health'];
            $data['download_order'] = (int)$params['download_order'];
            $data['sync_delivery'] = (int)$params['sync_delivery'];
            $data['sync_feedback'] = (int)$params['sync_feedback'];

            $idArr = array_merge(array_filter(array_unique(explode(',', $params['ids']))));  
            $data['updated_time'] = time();
            if (empty($data)) {
                return json(['message' => '数据参数不能为空'], 200);
            }
        
            // $model->allowField(true)->update($data, ['id' => ['in', $idArr]]);
            // if (isset($data['download_health'])) {
            //     foreach($idArr as $id){
            //         (new AmazonAccountHealthService())->openAmazonHealth($id, $data['download_health']);
            //     }          
            // }

            $new_data = $data;
            $new_data['site_status'] = !empty($params['site_status']) ? intval($params['site_status']) : 0;
            $user = CommonService::getUserInfo($request);
            $operator = [];
            $operator['operator_id'] = $user['user_id'];
            $operator['operator'] = $user['realname'];

            $Amazon_Account_Health_Service = new AmazonAccountHealthService();
            foreach ($idArr as $id) {
                $old_data = $model::get($id);
                if (!$old_data) {
                    break;
                }
                $model->allowField(true)->update($data, ['id' => $id]);
                $operator['account_id'] = $id;
                if (isset($data['download_health'])) {
                    $Amazon_Account_Health_Service->openAmazonHealth($id, $data['download_health']);
                }
                if (in_array($new_data['site_status'], [1, 2, 3, 4])) {
                    $old_data['site_status'] = AccountService::setSite(
                        ChannelAccountConst::channel_amazon,
                        $old_data['base_account_id'],
                        $old_data['code'],
                        $operator['operator_id'],
                        $new_data['site_status']
                    );
                }
                AmazonAccountService::addAmazonLog($operator, 1, $new_data, $old_data);
            }
            Db::commit();
            //更新缓存
            $cache = Cache::store('AmazonAccount');
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
     * @title 停用，启用账号
     * @method post
     * @url /amazon-account/status
     * @param Request $request
     * @return \think\Response
     */
    public function changeStatus(Request $request)
    {
        $id = $request->post('id', 0);
        $data['status'] = $request->post('status', 0);
        $accountModel = new AmazonAccountModel();
        if (!is_numeric($data['status'])) {
            return json(['message' => '参数有误!'], 400);
        }
        if (!is_numeric($id)) {
            return json(['message' => '参数有误!'], 400);
        }
        $user = CommonService::getUserInfo($request);
        try {
            $data['updated_time'] = time();

            $info = $accountModel::get($id);
            if (!$info) {
                throw new Exception('找不到记录');
            }
            $log_data = $data;

            $accountModel->allowField(true)->save($data, ['id' => $id]);

            $operator = [];
            $operator['operator_id'] = $user['updater_id'];
            $operator['operator'] = $user['realname'];
            $operator['account_id'] = $id;
            AmazonAccountService::addAmazonLog($operator, 1, $log_data, $info);
            //更新缓存
            $cache = Cache::store('AmazonAccount');
            foreach ($data as $key => $val) {
                $cache->updateTableRecord($id, $key, $val);
            }
            return json(['message' => '操作成功'], 200);
        } catch (Exception $e) {
            return json(['message' => '操作失败'], 500);
        }
    }

    /**
     * @title 获取Amazon站点
     * @url /amazon/site
     * @public
     * @method get
     * @return \think\Response
     */
    public function site()
    {
        $result = Cache::store('account')->amazonSite();
        return json(array_values($result), 200);
    }
    
    /**
     * @title 获取亚马逊开发者授权信息
     * @method get
     * @url /amazon-account/get-developer-account/:site
     * @param  \think\Request $request
     * @param  string $site
     * @return \think\Response
     */
    public function getDeveloperAccount($site)
    {
        $return = [
            'ack'=>0,
            'message'=>'getDeveloperAccount error',
            'data'=>[],
        ];
        $site = trim($site);
        $service = new AmazonAccountService();
        if($daRe = $service->getDeveloperAccount($site)){
            $return['ack'] = 1;
            $return['message'] = 'success';
            $return['data']['developer_access_key_id'] = $daRe['access_key_id'];
            $return['data']['developer_secret_key'] = $daRe['secret_key'];
        }else{
            $return['message'] = "暂不支持 {$site} 站点的第三方授权！";
        }
        return json($return, 200);
    }

    /**
     * @title 二维数组排序
     * @url /amazon/my_sort
     * @public
     * @method get
     * @return $arrays
     */
    public function my_sort($arrays, $sort_key, $sort_order = SORT_ASC, $sort_type = SORT_STRING)
    {
        if (is_array($arrays)) {
            foreach ($arrays as $array) {
                if (is_array($array)) {
                    $key_arrays[] = $array[$sort_key];
                } else {
                    return false;
                }
            }
        } else {
            return false;
        }
        array_multisort($key_arrays, $sort_order, $sort_type, $arrays);
        return $arrays;
    }

    /**
     * @title 获取Amazon账号日志
     * @method get
     * @url /amazon-account/log/:id
     * @param  \think\Request $request
     * @param  string $site
     * @return \think\Response
     */
    public function getLog(Request $request)
    {
        return json(
            (new AmazonAccountService)->getAmazonLog($request->param())
            , 200
        );
    }
}
