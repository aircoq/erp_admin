<?php
namespace app\index\task;

use app\index\service\AbsTasker;
use app\common\cache\Cache;
use joom\JoomAccountApi;
use app\common\model\joom\JoomShop;
use think\Exception;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2017/3/28
 * Time: 15:59
 */
class JoomCheckToken extends AbsTasker
{
    /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return '检查JoomToken是否过期';
    }

    /**
     * 定义任务描述
     * @return string
     */
    public function getDesc()
    {
        return '';
    }

    /**
     * 定义任务作者
     * @return string
     */
    public function getCreator()
    {
        return '冬';
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
        $cache = Cache::store('JoomShop');
        $account_list = $cache->getAccount();
        $sdk = new JoomAccountApi();
        $joomShopModel = new JoomShop();
        try{
            foreach ($account_list as $k => $v) {
                if(empty($v['client_id']) || empty($v['client_secret']) || empty($v['refresh_token'])){
                    continue;
                }
                //检测账号的token
                $result = $sdk->task_refresh_access_token($v);
                if(isset($result['code']) && $result['code'] == 0) {
                    $data = [];
                    $data['access_token'] = $this->access_token = $result['data']['access_token'];
                    $data['refresh_token'] = $this->refresh_token = $result['data']['refresh_token'];
                    $data['expiry_time'] = $result['data']['expiry_time'];
                    $data['merchant_id'] = $result['data']['merchant_user_id'];
                    $joomShopModel->update($data, ['id' => $v['id']]);
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
