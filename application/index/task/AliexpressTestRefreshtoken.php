<?php
namespace app\index\task;

use app\index\service\AbsTasker;
use app\common\cache\Cache;
use app\common\model\aliexpress\AliexpressAccount as AliexpressAccountModel;
use service\aliexpress\AliexpressApi;
use think\Exception;
use app\common\exception\TaskException;



class AliexpressTestRefreshtoken extends AbsTasker{
    public function getName()
    {
        return "速卖通检测授权";
    }

    public function getDesc()
    {
        return "速卖通通过刷新access_token检测是否授权成功";
    }

    public function getCreator()
    {
        return "张冬冬";
    }

    public function getParamRule()
    {
        return [];
    }

    public function execute()
    {
        try{
            $account_list = Cache::store('AliexpressAccount')->getTableRecord();
            if (!empty($account_list)) {
                //更新记录和缓存
                $accountModel = new AliexpressAccountModel();
                //更新缓存
                $cache = Cache::store('AliexpressAccount');
                $time = time();
                foreach ($account_list as $k=>$v) {
                    $updateData['update_time'] = $time;

                    //有效时间过的，直接设置未授权；
                    if ($v['expiry_time'] < $time) {
                        $updateData['is_authorization'] = 0;
                    } else {    //没过有效期的，
                        $config['client_id'] = $v['client_id'];
                        $config['client_secret'] = $v['client_secret'];
                        $config['refresh_token'] = $v['refresh_token'];
                        //去拿access_token,成功，则已授权，不成功则未授权；
                        $result = AliexpressApi::instance()->loader('common')->getTokenByRefreshToken($config);
                        if (empty($result['access_token'])){
                            $updateData['is_authorization'] = 0;
                        } else {
                            $updateData['is_authorization'] = 1;
                            $updateData['access_token'] = $result['access_token'];
                            if (isset($result['refresh_token'])) {
                                $updateData['refresh_token'] = $result['refresh_token'];
                            }
                            if (isset($result['expiry_time'])) {
                                $updateData['expiry_time'] = strtotime($result['expiry_time']);
                            }
                        }
                    }

                    $accountModel->update($updateData, ['id'=>$v['id']]);
                    foreach($updateData as $key=>$val) {
                        $cache->updateTableRecord($v['id'], $key, $val);
                    }

                }
            }
        }catch (Exception $e){
            throw new TaskException($e->getMessage());
        }

    }
    
}