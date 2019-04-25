<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-6-6
 * Time: 下午5:01
 */

namespace app\publish\queue;


use app\common\cache\Cache;
use app\common\exception\QueueException;
use app\common\model\aliexpress\AliexpressCategory;
use app\common\service\SwooleQueueJob;
use app\publish\service\AliexpressTaskHelper;
use think\Exception;

class AliexpressCategoryQueue extends SwooleQueueJob
{
    private $config;
    public function getName(): string
    {
        return '速卖通分类队列';
    }

    public function getDesc(): string
    {
        return '速卖通分类队列';
    }

    public function getAuthor(): string
    {
        return 'hao';
    }
    public static function swooleTaskMaxNumber():int
    {
        return 10;
    }
    public function init()
    {

    }

    public function execute()
    {
        try{
            $category = $this->params;
            if($category){

                $hash_key = 'AliexpressGetCategory';

                if(!Cache::handler()->hExists($hash_key, $category)) {
                    return;
                }

                $result = Cache::handler()->hGet($hash_key, $category);

                if($result) {
                    $cate = \GuzzleHttp\json_decode($result, true);


                    //删除缓存
                    Cache::handler()->hDel($hash_key, $category);
                    $model = new AliexpressCategory();

                    //写入hash,执行完成再删除
                    $categoryInfo = $model->where('category_id','=',$category)->field('account_id')->find();

                    if($categoryInfo)
                    {
                        $categoryInfo = $categoryInfo->toArray()['account_id'];
                        $accountIds = $categoryInfo ? explode(',', $categoryInfo) : [];

                        if($accountIds && !in_array($cate['account_id'],$accountIds)) {
                            array_push($accountIds,$cate['account_id']);
                        }

                        if($accountIds) {
                            $cate['account_id'] = implode(',', $accountIds);
                        }

                        $model->update($cate,['category_id'=>$category]);
                    }else{
                        $model->insertGetId($cate);
                    }
                }
            }
        }catch (Exception $exp){
            throw new QueueException($exp->getMessage());
        }

    }

}