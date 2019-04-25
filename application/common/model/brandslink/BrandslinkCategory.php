<?php

namespace app\common\model\brandslink;

use app\common\cache\Cache;
use think\Model;

class BrandslinkCategory extends Model
{
    protected $table = 'brandslink_category';

    protected $autoWriteTimestamp = true;

    protected $createTime = 'create_time';

    protected $updateTime = null;
}