<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\goods\service\CategoryHelp;
use \think\Db;
use think\Exception;
use app\common\model\Category as CategoryModel;
use app\common\model\CategoryMap;
use app\common\model\CategoryAttribute;
use app\common\model\AttributeGroup;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/10/29
 * Time: 10:16
 */


class Category extends Cache
{
    public function updateSorts($sorts)
    {
        self::handler()->del('cache:category_tree');
        self::handler()->del('cache:category');        
        foreach ($sorts as $sort){
            $model = new CategoryModel();
            $model->where('id', $sort->id)->update(['sort' => $sort->sort]);
            // $model->sort = $sort->sort;
            // $model->pid = $sort->pid;
            // $model->update();
        }
    }
    /** 获取分类树
     * @return array|mixed
     */
    public function getCategoryTree($lang_id=1)
    {
        $cacheKey = 'cache:category_tree:lang:'.$lang_id;
        $result = [];
        if ($this->redis->exists($cacheKey)) {
            $result = json_decode($this->redis->get($cacheKey), true);
            return $result;
        } else {
            $category_list = Db::table('category')
                ->field('id,pid,name,title,code,developer_id,purchaser_id,sku_checking_id')
                ->order('sort ASC, id ASC')
                ->select();
        }
        try {
            if ($category_list) {
                $child = '_child';
                $child_ids = [];
                $temp = [
                    'depr' => '-',
                    'parents' => [],
                    'child_ids' => [],
                    'dir' => [],
                    '_child' => [],
                ];
                $func = function ($tree) use (&$func, &$result, &$temp, &$child, &$icon, &$child_ids,$lang_id) {
                    $aLangMap = [];
                    if($lang_id!=1){
                        $CategoryHelp = new CategoryHelp();
                        $aLangMap = $CategoryHelp->getLang($lang_id);
                    }
                    foreach ($tree as $k => $v) {
                        $v['parents'] = $temp['parents']; //所有父节点
                        $v['depth'] = count($temp['parents']); //深度
                        if($aLangMap){
                            $v['name'] = $aLangMap[$v['id']]??$v['name'];
                            $v['title'] = $aLangMap[$v['id']]??$v['title'];
                        }
                        $v['name_path'] = empty($temp['name']) ? $v['name'] : implode($temp['depr'],
                                $temp['name']) . $temp['depr'] . $v['name']; //英文名路径
                        if (isset($v[$child])) {
                            $_tree = $v[$child];
                            unset($v[$child]);
                            $temp['parents'][] = $v['id'];
                            $temp['name'][] = $v['name'];
                            $result[$k] = $v;
                            if ($v['pid'] == 0) {
                                if (empty($child_ids)) {
                                    $child_ids = [$k];
                                } else {
                                    array_push($child_ids, $k);
                                }
                            }
                            $func($_tree);
                            foreach ($result as $value) {
                                if ($value['pid'] == $k) {
                                    $temp['child_ids'] = array_merge($temp['child_ids'], [$value['id']]);
                                }
                            }
                            $result[$k]['child_ids'] = $temp['child_ids']; //所有子节点
                            $temp['child_ids'] = [];
                            array_pop($temp['parents']);
                            array_pop($temp['name']);
                        } else {
                            $v['child_ids'] = [];
                            $result[$k] = $v;
                            if ($v['pid'] == 0) {
                                if (empty($child_ids)) {
                                    $child_ids = [$k];
                                } else {
                                    array_push($child_ids, $k);
                                }
                            }
                        }
                    }
                };
                $_list = [];
                foreach ($category_list as $k => $v) {
                    $_list['model'][$v['id']] = $v;
                }
                foreach ($_list as $k => $v) {
                    $func(list_to_tree($v));
                }

            }
            //$result = list_to_tree($result);
            $result['child_ids'] = $child_ids;
            //加入redis中
            $this->redis->set($cacheKey, json_encode($result));
        } catch (Exception $e) {
            var_dump($e->getMessage());
            die;
        }

        return $result;
    }
    
    
    
    /** 获取分类树 用于导数据，防止修改,Wms专用
     * @return array|mixed
     */
    public function getCategoryTreeWms()
    {
        $result = [];
        if ($this->redis->exists('cache:category_tree_wms')) {
            $result = json_decode($this->redis->get('cache:category_tree_wms'), true);
            return $result;
        } else {
            $category_list = Db::table('category')->field('id,pid,name,title,code,developer_id,purchaser_id')->order('sort ASC, id ASC')->select();
        }
        try {
            if ($category_list) {
                $child = '_child';
                $child_ids = [];
                $temp = [
                    'depr' => '-',
                    'parents' => [],
                    'child_ids' => [],
                    'dir' => [],
                    '_child' => [],
                ];
                $func = function ($tree) use (&$func, &$result, &$temp, &$child, &$icon, &$child_ids) {
                    foreach ($tree as $k => $v) {
                        $v['parents'] = $temp['parents']; //所有父节点
                        $v['depth'] = count($temp['parents']); //深度
                        $v['name_path'] = empty($temp['name']) ? $v['name'] : implode($temp['depr'],
                            $temp['name']) . $temp['depr'] . $v['name']; //英文名路径
                            if (isset($v[$child])) {
                                $_tree = $v[$child];
                                unset($v[$child]);
                                $temp['parents'][] = $v['id'];
                                $temp['name'][] = $v['name'];
                                $result[$k] = $v;
                                if ($v['pid'] == 0) {
                                    if (empty($child_ids)) {
                                        $child_ids = [$k];
                                    } else {
                                        array_push($child_ids, $k);
                                    }
                                }
                                $func($_tree);
                                foreach ($result as $value) {
                                    if ($value['pid'] == $k) {
                                        $temp['child_ids'] = array_merge($temp['child_ids'], [$value['id']]);
                                    }
                                }
                                $result[$k]['child_ids'] = $temp['child_ids']; //所有子节点
                                $temp['child_ids'] = [];
                                array_pop($temp['parents']);
                                array_pop($temp['name']);
                            } else {
                                $v['child_ids'] = [];
                                $result[$k] = $v;
                                if ($v['pid'] == 0) {
                                    if (empty($child_ids)) {
                                        $child_ids = [$k];
                                    } else {
                                        array_push($child_ids, $k);
                                    }
                                }
                            }
                    }
                };
                $_list = [];
                foreach ($category_list as $k => $v) {
                    $_list['model'][$v['id']] = $v;
                }
                foreach ($_list as $k => $v) {
                    $func(list_to_tree($v));
                }
    
            }
            //$result = list_to_tree($result);
            $result['child_ids'] = $child_ids;
            //加入redis中
            $this->redis->set('cache:category_tree_wms', json_encode($result));
        } catch (Exception $e) {
            var_dump($e->getMessage());
            die;
        }
        return $result;
    }
    
    
    

    /** 获取分类以及映射的关系
     * @param int $category_id
     * @return array|mixed
     */
    public function getCategory($category_id = 0)
    {
        if ($this->redis->exists('cache:category')) {
            if (!empty($category_id)) {
                $result = $this->redis->zRangeByScore('cache:category', $category_id, $category_id);
                $new_array = [];
                foreach ($result as $k => $v) {
                    $new_array = json_decode($v, true);
                }
                return $new_array;
            } else {
                $result = $this->redis->zRange('cache:category', 0, -1, true);
                $new_array = [];
                foreach ($result as $v => $k) {
                    $new_array[$k] = json_decode($v, true);
                }
                return $new_array;
            }
        }
        $categoryModel = new CategoryModel();
        $category_list = $categoryModel->select();
        $categoryMap = new CategoryMap();
        $category_map = $categoryMap->select();
        $new_array = [];
        foreach ($category_list as $k => $v) {
            $temp = [];
            foreach ($category_map as $key => $value) {
                if ($v['id'] == $value['category_id']) {
                    // $value['create_time'] = !empty($value['create_time']) ? date('Y-m-d H:i:s', $value['create_time']) : '';
                    // $value['update_time'] = !empty($value['update_time']) ? date('Y-m-d H:i:s', $value['update_time']) : '';
                    $temp[$value['id']] = $value;
                }
            }
            $new_array[$v['id']] = $v;
            $new_array[$v['id']]['platform'] = $temp;
        }
        foreach ($new_array as $k => $v) {
            $this->redis->zAdd('cache:category', $k, json_encode($v));
        }
        if (!empty($category_id)) {
            return isset($new_array[$category_id]) ? $new_array[$category_id] : [];
        }
        return $new_array;

    }

    /** 获取分类的属性
     * @param int $category_id
     * @return array|mixed
     */
    public function getAttribute($category_id = 0)
    {
        $result = [];
        if ($this->redis->exists('cache:categoryAttribute')) {
            $result = json_decode($this->redis->get('cache:categoryAttribute'), true);
            if (!empty($category_id)) {
                return isset($result[$category_id]) ? $result[$category_id] : [];
            }
            return $result;
        }
        $category_data = $this->getCategory();
        $attributeModel = new CategoryAttribute();
        $attribute_data = $attributeModel->select();
        $attributeGroup = new AttributeGroup();
        $attribute_group = $attributeGroup->select();
        $new_array = [];
        foreach ($attribute_data as $k => $v) {
            $v = $v->toArray();
            $v['group_sort'] = 0;
            foreach ($attribute_group as $key => $value) {
                if ($v['group_id'] == $value['id']) {
                    $v['group_name'] = $value['name'];
                    $v['group_sort'] = $value['sort'];
                }
            }
            array_push($new_array, $v);
        }
        $new_temp = [];
        foreach ($category_data as $k => $v) {
            $temp = [];
            foreach ($new_array as $key => $value) {
                if ($value['category_id'] == $v['id']) {
                    array_push($temp, $value);
                }
            }
            $new_temp[$k] = $temp;
        }
        foreach ($new_temp as $k => $v) {
            $temp = [];
            if (!empty($v)) {
                foreach ($v as $a => $m) {
                    $m['attribute_value'] = json_decode($m['value_range'], true);
                    if (isset($temp[$m['group_id']])) {
                        unset($m['group_sort']);
                        unset($m['group_name']);
                        unset($m['value_range']);
                        array_push($temp[$m['group_id']]['attributes'], $m);
                    } else {
                        $temp[$m['group_id']]['group_id']   = $m['group_id'];
                        $temp[$m['group_id']]['sort']       = $m['group_sort'];
                        $temp[$m['group_id']]['name']       = isset($m['group_name']) ? $m['group_name'] : '';
                        unset($m['group_sort']);
                        unset($m['group_name']);
                        unset($m['value_range']);
                        $temp[$m['group_id']]['attributes'] = [$m];
                        
                    }
                }
            }
            $result[$k]['group'] = $temp;
        }

        $this->redis->set('cache:categoryAttribute', json_encode($result));
        if (!empty($category_id)) {
            return isset($result[$category_id]) ? $result[$category_id] : [];
        }
        return $result;
    }
    
    /** 获取分类的质检
     * @param int $category_id
     * @return array|mixed
     */
    public function getQcItems($category_id = 0)
    {
        $result = [];
        if ($this->redis->exists('cache:categoryQc')) {
            $result = json_decode($this->redis->get('cache:categoryQc'), true);
            if (!empty($category_id)) {
                return isset($result[$category_id]) ? $result[$category_id] : [];
            }
            return $result;
        }
        $category_data = $this->getCategory();
        $attributeModel = new CategoryAttribute();
        $attribute_data = $attributeModel->where(['is_qc'=>1])->field('category_id,attribute_id,is_qc_required,qc_group_id,check_tool')->select();
        $attributeGroup = new AttributeGroup();
        $attribute_group = $attributeGroup->where(['type' => 1])->select();
        $new_array = [];
        foreach ($attribute_data as $k => $v) {
            $v = $v->toArray();
            $v['group_sort'] = 0;
            foreach ($attribute_group as $key => $value) {
                if ($v['qc_group_id'] == $value['id']) {
                    $v['group_name'] = $value['name'];
                    $v['group_sort'] = $value['sort'];
                }
            }
            array_push($new_array, $v);
        }
        $new_temp = [];
        foreach ($category_data as $k => $v) {
            $temp = [];
            foreach ($new_array as $key => $value) {
                if ($value['category_id'] == $v['id']) {
                    array_push($temp, $value);
                }
            }
            $new_temp[$k] = $temp;
        }
        foreach ($new_temp as $k => $v) {
            $temp = [];
            if (!empty($v)) {
                foreach ($v as $a => $m) {
                    $m['check_tool'] = json_decode($m['check_tool'], true);
                    if (isset($temp[$m['qc_group_id']])) {
                        unset($m['group_sort']);
                        unset($m['group_name']);
                        unset($m['value_range']);
                        array_push($temp[$m['qc_group_id']]['items'], $m);
                    } else {
                        $temp[$m['qc_group_id']]['sort']       = $m['group_sort'];
                        $temp[$m['qc_group_id']]['qc_group_id']       = $m['qc_group_id'];                        
                        $temp[$m['qc_group_id']]['name']       = isset($m['group_name']) ? $m['group_name'] : '';
                        unset($m['group_sort']);
                        unset($m['group_name']);
                        unset($m['value_range']);
                        $temp[$m['qc_group_id']]['items'] = [$m];
                        
                    }
                }
            }
            $result[$k]['group'] = $temp;
        }

        $this->redis->set('cache:categoryQc', json_encode($result));
        if (!empty($category_id)) {
            return isset($result[$category_id]) ? $result[$category_id] : [];
        }
        return $result;
    }

    /** 获取分类映射数据
     * @param int $category
     * @return array|mixed
     */
    public function getCategoryMap($category = 0)
    {
        if ($this->redis->exists('cache:categoryMap')) {
            $map = json_decode($this->redis->get('cache:categoryMap'), true);
            if (!empty($category)) {
                return isset($map[$category]) ? $map[$category] : [];
            }
            return $map;
        }
        //查表
        $categoryMap = new \app\common\model\CategoryMap();
        $result = $categoryMap->select();
        $new_array = [];
        foreach ($result as $k => $v) {
            $new_array[$v['category_id']][$k] = $v->toArray();
        }
        $this->redis->set('cache:categoryMap', json_encode($new_array));
        if (!empty($category)) {
            return isset($new_array[$category]) ? $new_array[$category] : [];
        }
        return $new_array;
    }

    /**
     * 根据分类ID组成分类全名
     * @param $category_id
     * @return string
     */
    public function getFullNameById($category_id, $tree) {
        $name = $this->redis->hGet('cache:category_fullname', $category_id);
        if (!empty($name)) {
            return $name;
        }
        $tree = empty($tree)? $this->getCategoryTree() : $tree;
        $name = $this->builderCategoryName($tree, $category_id);
        $this->redis->hSet('cache:category_fullname', $category_id, $name);
        return $name;
    }


    private function builderCategoryName($tree, $id)
    {
        $name = '';
        if (empty($id) || empty($tree[$id])) {
            return $name;
        }
        $name = $tree[$id]['name'] ?? '';
        if ($tree[$id]['pid'] != 0) {
            $pname = $this->builderCategoryName($tree, $tree[$id]['pid']);
            if ($pname) {
                $name = trim($pname. '>'. $name, '>');
            }
        }
        return $name;
    }
}