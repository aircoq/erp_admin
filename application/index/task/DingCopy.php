<?php
namespace app\index\task;

use app\common\model\DepartmentCopy;
use app\common\model\UserCopy;
use app\common\model\UserMapCopy;
use app\index\service\AbsTasker;
use service\dingding\DingApi;
use app\common\model\Department;
use app\common\model\User;
use app\common\model\UserMap;
use app\common\cache\Cache;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2017/4/1
 * Time: 15:19
 */
class DingCopy extends Ding
{
    protected $copy = true;

    public function getDesc()
    {
        return "拉取钉钉数据Copy";
    }

    public function __construct()
    {
        $this->user = new UserCopy();
        $this->userMap = new UserMapCopy();
        $this->department = new DepartmentCopy();
    }
}