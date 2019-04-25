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
use app\common\model\aliexpress\AliexpressCategoryAttr;
use app\common\service\SwooleQueueJob;
use app\publish\service\AliexpressTaskHelper;
use think\Exception;

class AliexpressCategoryAttributeQueue extends SwooleQueueJob
{
    private $config;
    public function getName(): string
    {
       return '速卖通分类属性队列';
    }

    public function getDesc(): string
    {
        return '速卖通分类属性队列';
    }

    public function getAuthor(): string
    {
        return 'joy';
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
            $params = $this->params;
            if($params){
                $attributeModel = new AliexpressCategoryAttr();
                $helper = new AliexpressTaskHelper();
                $config = Cache::store('AliexpressAccount')->getAccountById(34);
                $helper->getAeAttribute($config, $attributeModel, $params);
            }
        }catch (Exception $exp){
            throw new QueueException($exp->getMessage());
        }

    }

}