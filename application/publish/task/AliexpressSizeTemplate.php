<?php
/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2017/5/10
 * Time: 9:35
 */

namespace app\publish\task;

use app\index\service\AbsTasker;
use app\publish\service\AliexpressTaskHelper;
use think\Exception;
use service\aliexpress\AliexpressApi;
use app\common\cache\Cache;
use app\common\exception\TaskException;


class AliexpressSizeTemplate extends AbsTasker
{
    public  function getCreator()
    {
        return '龙志军';
    }

    public  function getDesc()
    {
        return 'Aliexpress-获取尺码模板';
    }

    public  function getName()
    {
        return 'Aliexpress-获取尺码模板';
    }

    public  function getParamRule()
    {
        return [];
    }

    public  function execute()
    {
        try {
            //获取所有已授权并启用账号
            $accountList = Cache::store('AliexpressAccount')->getAccounts();
            //检测设置队列
            $redis = Cache::handler();
            if (!$redis->lLen('queue:ali_size_temp')) {
                if (!empty($accountList)) {
                    foreach ($accountList as $item) {
                        if ($item['is_invalid'] && $item['is_authorization']) {
                            $redis->lPush('queue:ali_size_temp', json_encode([
                                'account_id' => $item['id'],
                            ]));
                        }
                    }
                }
            }
            $account = $redis->rPop('queue:ali_size_temp');
            $account = json_decode($account, true);
            if(!isset($accountList[$account['account_id']])){
                throw new TaskException("ID为{$account['account_id']}的账号不存在");
            }
            $account = $accountList[$account['account_id']];
            $config    = [
                'id'                =>  $account['id'],
                'client_id'            => $account['client_id'],
                'client_secret'     => $account['client_secret'],
                'accessToken'    => $account['access_token'],
                'refreshtoken'      =>  $account['refresh_token'],
            ];
            $this->synTemp($config,$account['id']);
        } catch (Exception $ex) {
            throw new TaskException($ex->getMessage());
        }
    }

    public function synTemp($config,$accountId)
    {
        $helpServer = new AliexpressTaskHelper();
        //获取所有需要尺码模板的分类
        $arrCategory = $helpServer->getCategoryByCondition(['required_size_model'=>1]);
        $PostProduct = AliexpressApi::instance($config)->loader('PostProduct');
        //$PostProduct->setConfig($config);

        foreach($arrCategory as $category){
            $responseSize = $PostProduct->getSizeChartInfoByCategoryId($category['category_id']);
            if(isset($responseSize['sizechartDTOList'])&&!empty($responseSize['sizechartDTOList'])){
                foreach($responseSize['sizechartDTOList'] as $size){
                    $data = [
                        'category_id'=>$category['category_id'],
                        'sizechart_id'=>$size['sizechartId'],
                        'default'=>$size['default'] ? 1 : 0,
                        'model_name'=>$size['modelName'],
                        'name'=>$size['name'],
                        'account_id'=>$accountId,
                    ];
                    $helpServer->saveSizeTemp($data);
                }
            }
        }
    }
}