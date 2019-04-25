<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-8-14
 * Time: 下午2:14
 */

namespace app\system\model;


use erp\ErpModel;

class ReleaseUserRead extends ErpModel
{
    protected $table = "version_userread";

    protected $pk = "id";

    public function getReadsAttr($reads)
    {
        return json_decode($reads, true);
    }

    public function setReadsAttr($reads)
    {
        return json_encode($reads);
    }
}