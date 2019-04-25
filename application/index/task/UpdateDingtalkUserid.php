<?php
namespace app\index\task;

use app\internalletter\service\InternalLetterService;
use app\index\service\AbsTasker;

class UpdateDingtalkUserid extends AbsTasker
{
    /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return '同步钉钉id';
    }

    /**
     * 定义任务描述
     * @return string
     */
    public function getDesc()
    {
        return '同步钉钉id';
    }

    /**
     * 定义任务作者
     * @return string
     */
    public function getCreator()
    {
        return 'denghaibo';
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
     * 同步钉钉id任务
     * @throws TaskExceptionEMail.php
     */
    public  function execute()
    {

        set_time_limit(0);
        $InternalLetterService = new InternalLetterService();
        $InternalLetterService->updateDingtalkUserid();
    }

}

