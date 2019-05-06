<?php
/**
 * Created by PhpStorm.
 * User: donghaibo
 * Date: 2019/4/26
 * Time: 18:11
 */

namespace app\index\task;


use app\common\cache\Cache;
use app\common\service\UniqueQueuer;
use app\index\queue\CheckEbayTokenQueue;
use app\index\service\AbsTasker;

class CheckEbayTokenExpire extends AbsTasker
{

    /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return "检查ebay账号token是否过期";
    }

    /**
     * 定义任务描述
     * @return string
     */
    public function getDesc()
    {
        return "检查ebay账号token是否过期";
    }

    /**
     * 定义任务作者
     * @return string
     */
    public function getCreator()
    {
        return "donghaibo";
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
     * 任务执行内容
     * @return void
     */
    public function execute()
    {
        $accounts = $accountList = Cache::store('EbayAccount')->getTableRecord();

        foreach ($accounts as $a)
        {
            if(empty($a['token']) || empty($a['app_id']) || empty($a['cert_id']) || empty($a['dev_id']))
            {
                continue;
            }
            //防止token出错
            $token = $a['token'];
            if (strpos('[', $token) === 0) {
                $token = json_decode($token);
                if ($token !== false && is_array($token)) {
                    $token = $token[0] ?? 0;
                }
            }

            $data['config'] = [
                'apiVersion'  => '1019',
                'siteId' => 0,
                'authToken' => $token,
                'credentials' => [
                    'appId'  => $a['app_id'],
                    'certId' => $a['cert_id'],
                    'devId'  => $a['dev_id'],]
             ];
            $data['account_id'] = $a['id'];

            (new UniqueQueuer(CheckEbayTokenQueue::class))->push($data);
        }
    }
}