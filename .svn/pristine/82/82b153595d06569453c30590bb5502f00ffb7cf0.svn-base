<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-4-21
 * Time: 上午11:35
 */

namespace app\publish\queue;


use app\common\cache\Cache;
use app\common\exception\QueueException;
use app\common\model\pandao\PandaoAccount;
use app\common\service\CommonQueuer;
use app\common\service\SwooleQueueJob;
use app\common\service\UniqueQueuer;
use app\publish\service\PandaoApiService;
use think\Exception;

class PandaoProductJobQueue extends SwooleQueueJob
{

    public function getName(): string
    {
       return '获取pandao批量下载商品job状态';
    }

    public function getDesc(): string
    {
        return '获取pandao批量下载商品job状态';
    }

    public function getAuthor(): string
    {
       return 'joy';
    }

    public function execute()
    {
        try{
            $params = $this->params;
            if($params){
                $flag=false;
                list($account_id,$job_id)=explode('|',$params);
                $account = Cache::store('PandaoAccountCache')->getAccountById($account_id);
                //$account = PandaoAccount::where('id',$account_id)->find()->toArray();
                $response = PandaoApiService::downloadJobStatus($account,['job_id'=>$job_id]);
                if(isset($response['code']) && $response['code']==0)
                {
                    $data = $response['data'];
                    if($data['status']=='FINISHED' && $data['download_link'])
                    {
                        $res = PandaoApiService::downloadFile($account_id,$job_id,$data['download_link']);
                        if($res)
                        {
                            $flag=true;
                            (new UniqueQueuer(PandaoProductInsertDb::class))->push($account_id.'|'.$job_id);
                        }
                    }
                }
                if($flag==false){
                    (new CommonQueuer(self::class))->push($params,strtotime('+ 30minute'));
                }
            }
        }catch (Exception $exp){
            throw new QueueException($exp->getMessage());
        }

    }
}