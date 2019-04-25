<?php

namespace app\index\task;

use app\common\exception\TaskException;
use app\index\service\AbsTasker;
use app\index\service\Currency as CurrencyService;
use think\Exception;

class CurrencyTask extends AbsTasker
{
    public function getName()
    {
        return "自动更新汇率";
    }

    public function getDesc()
    {
        return "自动更新汇率";
    }

    public function getCreator()
    {
        return "詹老师";
    }

    public function getParamRule()
    {
        return [];
    }

    public function execute()
    {
        try{
            $CurrencyService = new CurrencyService();
            $CurrencyService->updateOfficialRate(true);
        }catch (Exception $ex){
            throw new TaskException($ex->getMessage());
        }

    }

}