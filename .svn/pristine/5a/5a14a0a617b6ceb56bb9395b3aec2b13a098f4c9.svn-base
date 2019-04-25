<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\traits\CacheTable;
use app\common\model\AttributeValue as AttributeValueModel;

class AttributeValue extends Cache
{
    use CacheTable;
    
    public function __construct()
    {
        $this->model(AttributeValueModel::class);
        parent::__construct();
    }
    
    /**
     * 获取属性值列表
     * @param int $id
     * @return array
     */
    public function getAttributeValue($id)
    {
        if (!$id) {
            return $id;
        }
        
        return $this->getTableRecord($id);
    }
}
