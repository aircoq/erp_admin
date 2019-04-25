<?php
namespace app\common\model;

use think\Model;
use app\common\cache\Cache;
use app\common\traits\ModelFilter;
use think\db\Query;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/10/28
 * Time: 9:13
 */
class Warehouse extends Model
{
    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }

    //数据过滤器
    use ModelFilter;
    public function scopeAllocation(Query $query, $params)
    {
        if (!empty($params)) {
            $query->where('__TABLE__.id', 'in', $params);
        }
    }

    /** 检查是否存在
     * @param array $data
     * @return bool
     */
    public function check(array $data)
    {
        $result = $this->get($data);
        if (!empty($result)) {
            return true;
        }
        return false;
    }

    public static function getWarehouses()
    {
        return self::field('*')->select();
    }
    
}