<?php
/**
 * Created by PhpStorm.
 * User: starzhan
 * Date: 2017/11/23
 * Time: 14:33
 */

namespace app\common\model;


use erp\ErpModel;

class PackageCollectionDetail extends ErpModel
{

    const STATUS_WAIT = 0;
    const STATUS_OUTED = 1;
    const STATUS_TXT = [
        self::STATUS_WAIT => '未出库',
        self::STATUS_OUTED => '以出库'
    ];

    public function packageCollection()
    {
        return $this->belongsTo(PackageCollection::class, 'package_collection_id', 'id');
    }
}