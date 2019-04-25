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
use app\common\model\aliexpress\AliexpressCategoryAttrVal;
use app\common\service\SwooleQueueJob;
use think\Exception;

class AliexpressCategoryAttrValQueue extends SwooleQueueJob
{
    private $config;
    public function getName(): string
    {
        return '速卖通分类属性值队列';
    }

    public function getDesc(): string
    {
        return '速卖通分类属性值队列';
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
            $id = $this->params;
            if($id){

                $hash_key = 'AliexpressCategoryAttrVal';

                if(!Cache::handler()->hExists($hash_key, $id)) {
                    return;
                }

                $result = Cache::handler()->hGet($hash_key, $id);
                    if($result) {
                        $attr_val = \GuzzleHttp\json_decode($result, true);

                        //删除缓存
                        Cache::handler()->hDel($hash_key, $id);

                        $model = new AliexpressCategoryAttrVal();

                        //写入hash,执行完成再删除
                         if($model->where(['id'=>$id])->find()) {
                             $model->update($attr_val,['id'=> $id]);
                         }else{
                             $model->insertGetId($attr_val);
                         }
                    }
            }
        }catch (Exception $exp){
            throw new QueueException($exp->getMessage());
        }

    }

}