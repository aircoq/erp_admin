<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-8-14
 * Time: 下午2:14
 */

namespace app\system\model;


use erp\ErpModel;

class Release extends ErpModel
{
    protected $table = "version_release";

    protected $pk = "id";

    public function items()
    {
        return $this->hasMany(ReleaseItem::class, 'release_id', "id");
    }

}