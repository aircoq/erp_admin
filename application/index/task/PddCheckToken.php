<?php
namespace app\index\task;

use app\index\service\AbsTasker;
use app\common\cache\Cache;
use service\pdd\PddApi;
use think\Exception;
use app\common\model\pdd\PddAccount;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2017/3/28
 * Time: 15:59
 */
class PddCheckToken extends AbsTasker
{
    /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return '检查pddToken是否过期';
    }

    /**
     * 定义任务描述
     * @return string
     */
    public function getDesc()
    {
        return '检查pddToken是否过期';
    }

    /**
     * 定义任务作者
     * @return string
     */
    public function getCreator()
    {
        return 'zhaixueli';
    }

    /**
     * 定义任务参数规则
     * @return array
     */
    public function getParamRule()
    {
        return [];
    }

    /**
     * 执行方法
     */
    public function execute()
    {
        Cache::handler()->set('pddOrder:datas1', 1);
        $this->checkToken();
    }

    /**
     * 检测token是否过期
     * @throws \app\common\cache\Exception
     */
    private function checkToken()
    {
        $cache = Cache::store('PddAccount');

        $accountList = $cache->getAllAccounts();
        Cache::handler()->set('pddOrder:datas1', json_encode($accountList));

        $sdk = new PddApi();
        $model = new PddAccount();
        try{
            foreach ($accountList as $k => $v) {
                if(empty($v['client_id']) || empty($v['client_secret']) || empty($v['refresh_token'])){
                    continue;
                }
                //检测账号的token
                $result = $sdk->checkToken($v);
                Cache::handler()->set('pddOrder:datas2', json_encode($result));

                if(isset($result['access_token'])) {
                    $data = [];
                    $data['access_token'] = $this->access_token = $result['access_token'];
                    $data['refresh_token'] = $this->refresh_token = $result['refresh_token'];
                    $data['token_expire_time'] =time()+ $result['expires_in'];
                    $model->update($data, ['id' => $v['id']]);
                    foreach($data as $key=>$val) {
                        $cache->updateTableRecord($v['id'], $key, $val);
                    }
                    return '刷新成功';
                }
            }
        }catch (Exception $e){
            throw new Exception($e->getMessage());
        }
    }
}
