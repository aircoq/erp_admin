<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use \think\Db;
use think\Exception;
use app\common\model\ThirdCategory as CategoryModel;


/**
 * Created by PhpStorm.
 * User: laiyongfeng
 * Date: 2018/05/19
 * Time: 16:29
 */
class ThirdCategory extends Cache
{
    const cachePrefix = 'cache:third_category_tree';

    /**
     * @desc 设置分类
     * @param int $type
     * @param array $tree_data
     */
    public function setCategoryTree($type, $tree_data)
    {
        $key = self::cachePrefix.':'.$type;
        $this->redis->set($key, json_encode($tree_data));
    }

    /**
     * @desc 获取分类树
     * @param int $type
     * @return array
     * @throws Exception
     */
    public function getCategoryTree($type)
    {
//        $key = self::cachePrefix.':'.$type;
        /* if ($this->redis->exists($key)) {
              $result = json_decode($this->redis->get($key), true);
              return $result;
          }*/
        $category_list = (new CategoryModel())->where('warehouse_type', $type)->field('category_id as id, parent_category_id as pid,category_name,category_name_en,category_level,warehouse_type')->order('parent_category_id asc, category_id asc')->select();
        try {
            $data = [];
            foreach($category_list as $category){
                $data[$category['id']] = $category->toArray();
                $data[$category['id']]['child_ids'] = [];
            }
            $result = $this->getChild($data);
            //$this->setCategoryTree($type, $result);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        return $result;
    }

    /**
     * @desc 获取子分类信息
     */
    private function getChild($data)
    {
        foreach($data as $item){
            if($item['pid']){
                if(isset($data[$item['pid']])){
                    array_push($data[$item['pid']]['child_ids'], $item['id']);
                }
            }
        }
        return $data;
    }
}