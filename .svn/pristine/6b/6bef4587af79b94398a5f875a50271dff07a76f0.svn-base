<?php
/**
 * Created by PhpStorm.
 * User: wlw2533
 * Date: 18-5-29
 * Time: 上午10:20
 */

namespace app\publish\task;

use app\index\service\AbsTasker;
use think\Exception;
use app\publish\service\AliexpressPublishTaskHelper;

class AliexpressPublishTask extends AbsTasker
{
    public function getName()
    {
        return '速卖通每日刊登自动分配';
    }

    public function getDesc()
    {
        return '速卖通每日刊登自动分配';
    }

    public function getCreator()
    {
        return 'hao';
    }

    public function getParamRule()
    {
       return [];
    }

    public function execute()
    {
        set_time_limit(0);
        try{
            (new AliexpressPublishTaskHelper())->publishDistribute();
            return true;
        }catch (Exception $exp){
            throw new Exception("{$exp->getMessage()}");
        }
    }


}