<?php

namespace app\goods\service;

use app\common\model\DeveloperTeam;
use app\common\model\DeveloperSubclassMap;
use app\common\model\Category;
use app\common\cache\Cache;
use app\common\model\CategoryMap;
use app\common\model\Goods;
use app\common\model\CategoryLang;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2017/3/4
 * Time: 13:48
 */
class CategoryHelp
{
    /**
     * 读取分类信息
     * @param int $pid
     * @param string $content
     * @param int $group_id
     * @return array
     */
    public function read($pid = 0, $content = '', $group_id = 0)
    {
        $where['pid'] = ['=', $pid];
        if (!empty($content)) {
            $where['name'] = ['like', '%' . $content . '%'];
        }
        if (!empty($pid)) {
            //排除已选的
            $whereExp = [];
            $whereTwo = [];
            $category = [];
            if (!empty($group_id)) {
                $whereExp['id'] = ['<>', $group_id];
                $whereTwo['team_id'] = ['<>', $group_id];
            }
            $developerModel = new DeveloperTeam();
            $developerSubclassMapModel = new DeveloperSubclassMap();
            $list = $developerModel->field('category_id')->where($whereExp)->select();
            $subclass = $developerSubclassMapModel->field('category_id')->where($whereTwo)->select();
            foreach ($list as $k => $v) {
                array_push($category, $v['category_id']);
            }
            foreach ($subclass as $k => $v) {
                array_push($category, $v['category_id']);
            }
            if (!empty($category)) {
                $where['id'] = ['not in', $category];
            }
        }
        //读取分类
        $categoryModel = new Category();
        $categoryList = $categoryModel->field('id,name')->where($where)->select();
        return !empty($categoryList) ? $categoryList : [];
    }

    /**
     * 获取分类信息
     * @param bool|false $subclass false 大类  true 子类
     * @param string $content
     * @return array|false|\PDOStatement|string|\think\Collection
     */
    public function info($subclass = false, $content = '')
    {
        $where['pid'] = ['=', 0];
        if ($subclass) {
            $where['pid'] = ['<>', 0];
        }
        if (!empty($content)) {
            $where['name'] = ['like', '%' . $content . '%'];
        }
        //读取分类
        $categoryModel = new Category();
        $categoryList = $categoryModel->field('id,name')->where($where)->select();
        return !empty($categoryList) ? $categoryList : [];
    }

    /**
     * 获取子分类ID
     * @param int $pid 父分类
     * @return array
     */
    public function getSubIds($pid)
    {
        $categories = Cache::store('category')->getCategoryTree();
        if ($pid == 0) {
            $child_ids = $categories['child_ids'];
        } else if (isset($categories[$pid])) {
            $child_ids = $categories[$pid]['child_ids'];
        } else {
            $child_ids = [];
        }

        return $child_ids;
    }

    /**
     * 获取category map
     * @param int $category_id
     * @return array
     */
    public function getCategoryMap($category_id)
    {
        $result = [];
        $lists = CategoryMap::where(['category_id' => $category_id])->field('channel_id,channel_category_id,path,label,site_id')->select();
        foreach ($lists as $list) {
            $result[] = $list->toArray();
        }

        return $result;
    }

    /**
     * 获取渠道分类Id
     * @param int $channel_id 渠道id
     * @param int $category_id 分类id
     * @param int $site_id 站点id
     * return int|null
     */
    public function getChannelCategoryId($channel_id, $category_id, $site_id = 0)
    {
        $info = CategoryMap::where(['channel_id' => $channel_id, 'category_id' => $category_id, 'site_id' => $site_id])->field('channel_category_id')->find();
        if ($info) {
            return $info['channel_category_id'];
        }

        return null;
    }

    /**
     * 获取子分类列表
     * @return array
     */
    public function getCategoryLists($lang_id = 1)
    {
        $category_list = Cache::store('category')->getCategoryTree();
        $aLang = [];
        if($lang_id!=1){
            $aLang = $this->getLang($lang_id);
            unset($aLang[-1]);
        }
        $result = $this->assemblyTreeData($category_list, 0, $aLang);
        return $result;
    }

    private $_langData = [];

    public function getLang($lang_id)
    {
        if (!isset($_langData[$lang_id])) {
            $model = new CategoryLang();
            $ret = $model->field('category_id,title')->select();
            $result = [-1 => ''];
            foreach ($ret as $v) {
                $result[$v['category_id']] = $v['title'];
            }
            $this->_langData[$lang_id] = $result;
        }
        return $this->_langData[$lang_id];
    }

    /**
     * 组装分类返回数据
     * @return array
     */
    private function assemblyTreeData($list, $parent, $aLang = [])
    {
        $tree = array();
        foreach ($list as $row) {
            $temp = [];
            if (empty($row['id'])) {
                continue;
            }
            if ($row['pid'] == $parent) {
                $temp['id'] = $row['id'];
                $langTitle = '';
                if($aLang){
                    $langTitle = $aLang[$row['id']]??'';
                }
                $temp['title'] = $langTitle?$langTitle:$row['title'];
                $temp['childs'] = $this->assemblyTreeData($list, $row['id'], $aLang);
                $tree[] = $temp;
            }
        }
        return $tree;
    }

    /**
     * @title 根据分类id获取分类名称
     * @param $id
     * @return mixed|string
     * @author starzhan <397041849@qq.com>
     */
    public function getCategoryNameById($id,$lang_id=1)
    {
        $goods = new Goods();
        return $goods->getCategoryAttr(null, ['category_id' => $id],$lang_id);
    }

    /**
     * @title 根据分类id来获取默认采购员
     * @param $category_id
     * @return int
     * @author starzhan <397041849@qq.com>
     */
    public function getPurchaserIdByCategoryId($category_id)
    {
        $category = Cache::store('category')->getCategory($category_id);
        if ($category) {
            while ($category['pid'] != 0 && $category['purchaser_id'] == 0) {
                $category = Cache::store('category')->getCategory($category['pid']);
            }
            return $category['purchaser_id'];
        }
        return 0;
    }

    /**
     * @title 根据名称返回id
     * @author starzhan <397041849@qq.com>
     */
    public function getIdByAName($aName)
    {
        $result = [];
        $categoryModel = new Category();
        if ($aName) {
            foreach ($aName as &$name) {
                $name = trim($name);
            }
            $ret = $categoryModel->where('name', 'in', $aName)->field('id,name')->select();
            foreach ($ret as $v) {
                $result[$v['name']] = $v['id'];
            }
        }
        return $result;
    }
}