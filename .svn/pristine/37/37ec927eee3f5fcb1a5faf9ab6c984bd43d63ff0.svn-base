<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\amazon\AmazonCategory as AmazonCategoryModel;

class AmazonCategory extends Cache
{
    private $hashPrefix = 'task:amazon:category:';

    public function savePathId($site, $path_id, $id)
    {
        $key = $this->hashPrefix. $site;
        $this->redis->hset($key, $path_id, $id);
        return true;
    }

    public function getIdByPathId($site, $path_id)
    {
        $key = $this->hashPrefix. $site;
        $id = $this->redis->hget($key, $path_id);
        if (empty($id)) {
            $id = AmazonCategoryModel::where(['site' => $site, 'path_id' => $path_id])->value('id');
        }
        return (int)$id;
    }
}
