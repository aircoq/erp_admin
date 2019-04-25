<?php
namespace app\index\task;

use app\index\service\AbsTasker;
use app\common\cache\Cache;
use umka\UmkaAccountApi;
use think\Exception;
use app\common\model\umka\UmkaAccount;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2017/3/28
 * Time: 15:59
 */
class UmkaCheckToken extends AbsTasker
//class UmkaCheckToken
{
    /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return '检查UmkaToken是否过期';
    }

    /**
     * 定义任务描述
     * @return string
     */
    public function getDesc()
    {
        return '检查UmkaToken是否过期';
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
        $this->checkToken();
    }

    /**
     * 检测token是否过期
     * @throws \app\common\cache\Exception
     */
    private function checkToken()
    {
        $cache = Cache::store('UmkaAccount');
        $accountList = $cache->getAllAccounts();
        $sdk = new UmkaAccountApi();
        $model = new UmkaAccount();
        try{
            foreach ($accountList as $k => $v) {
                if(empty($v['client_id']) || empty($v['client_secret']) || empty($v['refresh_token'])){
                    continue;
                }
                //检测账号的token
                $result = $sdk->checkToken($v);
                if(isset($result['access_token'])) {
                    $data = [];
                    $data['access_token'] = $this->access_token = $result['access_token'];
                    $data['refresh_token'] = $this->refresh_token = $result['refresh_token'];
                    $data['expiry_time'] = $result['expires_in'];
                  $model->update($data, ['id' => $v['id']]);
                    foreach($data as $key=>$val) {
                        $cache->updateTableRecord($v['id'], $key, $val);

                    }
                    return true;
                }
            }
        }catch (Exception $e){
            throw new Exception($e->getMessage());
        }
    }
}
