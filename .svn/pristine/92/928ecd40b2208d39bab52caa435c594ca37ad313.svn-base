<?php

namespace app\goods\controller;

use app\common\service\Common;
use think\Controller;
use think\Exception;
use think\Request;
use app\common\controller\Base;
use think\Db;
use app\common\cache\Cache;
use app\common\model\Category as CategoryModel;
use app\common\model\CategoryMap;
use app\common\model\Goods as GoodsModel;
use app\goods\service\CategoryHelp;
use app\goods\service\CategoryLog;

/**
 * Class category
 * @title 分类管理
 * @module 商品系统
 * @author ZhaiBin
 * @url /categories
 * @package app\goods\controller
 */
class Category extends Base
{
    /**
     * 显示资源列表
     * @title 产品分类列表
     * @url /categories
     * @method get
     * @return \think\Response
     */
    public function index()
    {
        try {
            $request = Request::instance();
            if (isset($request->header()['X-Result-Fields'])) {
                $field = $request->header()['X-Result-Fields'];
            }
            $page = $request->get('page', 1);
            $pageSize = $request->get('pageSize', 10);
            $category_list = Cache::store('category')->getCategoryTree();
            foreach ($category_list as &$list) {
                if (empty($list['id'])) {
                    continue;
                }
                $list['purchaser'] = empty($list['purchaser_id']) ? '' : $this->getRealNameById($list['purchaser_id']);
                $list['developer'] = empty($list['developer_id']) ? '' : $this->getRealNameById($list['developer_id']);
                $list['sku_checking'] = empty($list['sku_checking_id']) ? '' : $this->getRealNameById($list['sku_checking_id']);
            }
           // $count = count($category_list);
            $params = $request->param();
            if (isset($params['purchaser_id']) && $params['purchaser_id']) {
                $tmp = [];
                $tmpId = [];
                foreach ($category_list as $k => $v) {
                    if ($k == 'child_ids') {
                        continue;
                    }
                    if ($v['purchaser_id'] == $params['purchaser_id']) {
                        $tmpId[] = $v['id'];
                        if ($v['child_ids']) {
                            $tmpId = array_merge($tmpId, $v['child_ids']);
                        }
                    }
                    $tmp[$v['id']] = $v;
                }
                $tmpCategory = [];
                foreach ($tmpId as $id) {
                    $row = $tmp[$id];
                    if($row['purchaser_id']){
                        if($row['purchaser_id']!= $params['purchaser_id']){
                            continue;
                        }
                    }
                    $tmpCategory[] = $row;
                }
                $category_list = $tmpCategory;

            }
            unset($category_list['purchaser_id']);

            if (isset($params['sku_checking_id']) && $params['sku_checking_id']) {
                $tmp = [];
                $tmpId = [];
                foreach ($category_list as $k => $v) {
                    if ($k == 'child_ids') {
                        continue;
                    }

                    if ($v['sku_checking_id'] == $params['sku_checking_id']) {
                        $tmpId[] = $v['id'];
                        if ($v['child_ids']) {
                            $tmpId = array_merge($tmpId, $v['child_ids']);
                        }
                    }
                    $tmp[$v['id']] = $v;
                }
                $tmpCategory = [];
                foreach ($tmpId as $id) {
                    $row = $tmp[$id];
                    if($row['sku_checking_id']){
                        if($row['sku_checking_id']!= $params['sku_checking_id']){
                            continue;
                        }
                    }
                    $tmpCategory[] = $row;
                }
                $category_list = $tmpCategory;

            }
            unset($category_list['sku_checking_id']);

            foreach($category_list as $key=>$val){
                if ($key != 'child_ids') {
                    if ($val['pid'] == 0) {
                        $category_list[$key]['purchaser'] = $val['purchaser'] != ''?$val['purchaser']:'暂无绑定';
                        $category_list[$key]['sku_checking'] = $val['sku_checking'] != ''?$val['sku_checking']:'暂无绑定';
                    }
                    if ($val['pid'] != 0 && $val['purchaser'] == '') {
                        $category_list[$key]['purchaser'] = '暂无绑定';
                    }
                    if ($val['pid'] != 0 && $val['sku_checking'] == '') {
                        $category_list[$key]['sku_checking'] = '暂无绑定';
                    }
                }
            }

            $count = count($category_list);
            if (isset($params['page'])) {
                $new_array = Cache::page($category_list, $page, $pageSize);
                $result = [
                    'data' => $new_array,
                    'page' => $page,
                    'pageSize' => $pageSize,
                    'count' => $count,
                ];
            } else {
                $result = $category_list;
            }

            return json($result, 200);
        } catch (Exception $e) {
            return json(['message' => '数据异常' . $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()], 500);
        }
    }

    /**
     * @url purchaser
     * @method get
     * @title 分类设置采购员列表
     * @return \think\Response
     * @author starzhan <397041849@qq.com>
     */
    public function purchaser_id(){
        return $this->index();
    }

    /**
     * @url :id(\d+)/purchaser-save
     * @method put
     * @title 分类设置采购员保存
     * @return \think\Response
     * @author starzhan <397041849@qq.com>
     */
    public function purchaser_save($id){
        return $this->update($this->request,$id);
    }

    /**
     * @url :id(\d+)/sku-shecking-save
     * @method put
     * @title 分类设置sku查重员保存
     * @return \think\Response
     * @author zhuda
     */
    public function skuCheckingSave($id){
        return $this->update($this->request,$id);
    }

    /**
     * 保存新建的资源
     * @title 保存产品分类
     * @url /categories
     * @method post
     * @param  \think\Request $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        $params = $request->param();
        $data['title'] = $params['title'];
        $data['pid'] = isset($params['pid']) ? $params['pid'] : 0;
        $data['code'] = isset($params['code']) ? $params['code'] : '';
        $data['ch_customs_title'] = isset($params['ch_customs_title']) ? $params['ch_customs_title'] : '';
        $data['en_customs_title'] = isset($params['en_customs_title']) ? $params['en_customs_title'] : '';
        $data['name'] = $data['title'];
        $data['keywords'] = isset($params['keywords']) ? $params['keywords'] : '';
        $data['description'] = isset($params['description']) ? $params['description'] : '';
        $data['developer_id'] = isset($params['developer_id']) ? $params['developer_id'] : 0;
        $data['purchaser_id'] = isset($params['purchaser_id']) ? $params['purchaser_id'] : 0;
        $data['status'] = 1;
        // 父分类验证       
        if ($data['pid']) {
            $cateAttribute = Cache::store('category')->getAttribute($data['pid']);
            if ($this->isHasGoods($data['pid']) || (isset($cateAttribute['group']) && !empty($cateAttribute['group']))) {
                return json(['message' => '父分类已经绑定产品或绑定属性'], 500);
            }
        }
        $categoryModel = new CategoryModel();
        $validateCategory = validate('Category');
        if (!$validateCategory->scene('insert')->check($data)) {
            return json(['message' => $validateCategory->getError()], 500);
        }
        //启动事务
        Db::startTrans();
        try {
            $categoryModel->allowField(true)->isUpdate(false)->save($data);
            $cid = $categoryModel->id;
            $data['platform'] = isset($params['platform']) ? json_decode($params['platform'], true) : [];
            if (!empty($data['platform'])) {
                $categoryMap = new CategoryMap();
                $categoryMap->saveData($data['platform'], $cid);
            }
            CategoryLog::add($data['name']);
            CategoryLog::save(Common::getUserInfo()['user_id'], $cid);
            Db::commit();
            //删除缓存
            Cache::handler()->del('cache:category_tree:lang:1');
            Cache::handler()->del('cache:category_tree:lang:2');
            Cache::handler()->del('cache:category');
            return json(['message' => '新增成功'], 200);
        } catch (Exception $e) {
            Db::rollback();
            return json(['message' => '新增失败' . $e->getMessage()], 500);
        }
    }

    /**
     * 分类是否绑定产品
     *
     * @param int $category_id
     * @return boolean
     */
    private function isHasGoods($category_id)
    {
        $count = GoodsModel::where(['category_id' => $category_id])->count();
        if ($count) {
            return true;
        }
        return false;
    }

    /**
     * 显示指定的资源
     * @title 查看产品分类
     * @url /categories/:id(\d+)
     * @method get
     * @param  int $id
     * @return \think\Response
     */
    public function read($id)
    {
        $category_lists = Cache::store('category')->getCategory($id);
        $category[$id] = $category_lists;
        $category_list = Cache::filter($category, [], 'id,title,pid,ch_customs_title,en_customs_title,platform');
        $category_info = CategoryModel::where(['id' => $id])->field('keywords, description, code, status,developer_id,purchaser_id')->find();
        if (!empty($category_info)) {
            $category_list[$id]['keywords'] = $category_info->keywords;
            $category_list[$id]['description'] = $category_info->description;
            $category_list[$id]['code'] = $category_info->code;
            $category_list[$id]['status'] = $category_info->status;
            $category_list[$id]['developer_id'] = $category_info->developer_id;
            $category_list[$id]['developer'] = $category_info->developer_id ? $this->getUserNameById($category_info->developer_id) : '';
            $category_list[$id]['purchaser_id'] = $category_info->purchaser_id;
            $category_list[$id]['purchaser'] = $category_info->purchaser_id ? $this->getUserNameById($category_info->purchaser_id) : '';
        }
        $help = new CategoryHelp();
        $result = $category_list[$id];
        $result['platform'] = $help->getCategoryMap($id);
        return json($result, 200);
    }

    /**
     * 显示编辑资源表单页.
     * @title 编辑产品分类
     * @url /categories/:id(\d+)/edit
     * @method get
     * @param  int $id
     * @return \think\Response
     */
    public function edit($id)
    {
        $category_info = CategoryModel::where(['id' => $id])->field('id,title,pid,ch_customs_title,en_customs_title,keywords,description,code,status,developer_id,purchaser_id')->find();
        if (!empty($category_info)) {
            $category_list[$id]['keywords'] = $category_info->keywords;
            $category_list[$id]['description'] = $category_info->description;
            $category_list[$id]['code'] = $category_info->code;
            $category_list[$id]['status'] = $category_info->status;
            $category_list[$id]['developer'] = $category_info->developer_id ? $this->getUserNameById($category_info->developer_id) : '';
            $category_list[$id]['purchaser'] = $category_info->purchaser_id ? $this->getUserNameById($category_info->purchaser_id) : '';
        }
        $help = new CategoryHelp();
        $result = $category_info->toArray();
        $result['platform'] = $help->getCategoryMap($id);
        return json($result, 200);
    }

    /**
     * 保存更新的资源
     * @title 更新产品分类
     * @method put
     * @url /categories/:id(\d+)
     * @param  \think\Request $request
     * @param  int $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        $params = $request->param();
        $data['id'] = $id;
        !isset($params['title']) ?: ($data['title'] = $data['name'] = $params['title']);
        !isset($params['code']) ?: $data['code'] = $params['code'];
        !isset($params['keywords']) ?: $data['keywords'] = $params['keywords'];
        !isset($params['description']) ?: $data['description'] = $params['description'];
        !isset($params['status']) ?: $data['status'] = $params['status'];
        !isset($params['pid']) ?: $data['pid'] = $params['pid'];
        !isset($params['ch_customs_title']) ?: $data['ch_customs_title'] = $params['ch_customs_title'];
        !isset($params['en_customs_title']) ?: $data['en_customs_title'] = $params['en_customs_title'];
        !isset($params['developer_id']) ?: $data['developer_id'] = $params['developer_id'];
        !isset($params['purchaser_id']) ?: $data['purchaser_id'] = $params['purchaser_id'];
        !isset($params['sku_checking_id']) ?: $data['sku_checking_id'] = $params['sku_checking_id'];
        $categoryModel = new CategoryModel();
        $aCategory = $categoryModel->where('id', $id)->find();
        if (!$aCategory) {
            return json(['message' => '该分类不存在'], 500);
        }
        // 父分类是否变化
        if (isset($data['pid']) && !$categoryModel::where(['pid' => $data['pid'], 'id' => $id])->count()) {
            $flag = $this->isHasGoods($id);
            if ($flag) {
                return json(['message' => '该分类已绑定产品不能修改父分类']);
            }
            // 父分类情况
            $cateAttribute = Cache::store('category')->getAttribute($data['pid']);
            if ($this->isHasGoods($data['pid']) || (isset($cateAttribute['group']) && !empty($cateAttribute['group']))) {
                return json(['message' => '父分类已经绑定产品或绑定属性'], 500);
            }
        }
        $validateCategory = validate('Category');
        if (!$validateCategory->scene('update')->check($data)) {
            return json(['message' => $validateCategory->getError()], 500);
        }

        //启动事务
        Db::startTrans();
        try {
            if ($data) {
                $categoryModel->allowField(true)->save($data, ['id' => $id]);
                CategoryLog::mdf($aCategory['name'], $aCategory, $data);
                CategoryLog::save(Common::getUserInfo()['user_id'], $id);
            }
            $data['platform'] = isset($params['platform']) ? json_decode($params['platform'], true) : [];
            if ($data['platform']) {
                $categoryMap = new CategoryMap();
                $categoryMap->saveData($data['platform'], $id);
            }
            Db::commit();
            //删除缓存
            Cache::handler()->del('cache:categoryMap');
            Cache::handler()->del('cache:category_tree:lang:1');
            Cache::handler()->del('cache:category_tree:lang:2');
            Cache::handler()->del('cache:category');
            return json(['message' => '更新成功'], 200);
        } catch (\Exception $e) {
            Db::rollback();
            $err = [
                'message'=>$e->getMessage(),
                'file'=>$e->getFile(),
                'line'=>$e->getLine(),
            ];
            return json($err, 400);
        }
    }

    /**
     * @url :id(\d+)/logs
     * @method get
     * @title 获取日志列表
     * @author starzhan <397041849@qq.com>
     */
    public function logs($id)
    {
        try {
            $CategoryLog = new CategoryLog();
            $result = $CategoryLog->getLog($id);
            return json($result, 200);
        } catch (Exception $ex) {
            return json(['message' => '获取失败'], 500);
        }
    }

    /**
     * 删除指定资源
     * @title 删除产品分类
     * @method delete
     * @url /categories/:id(\d+)
     * @param  int $id
     * @return \think\Response
     */
    public function delete($id)
    {
        $categoryModel = new CategoryModel();
        if ($categoryModel->hasChild($id)) {
            return json(['message' => '请先删除子类'], 400);
        }
        //检查产品是否有该分类
        $goodsModel = new GoodsModel();
        $check = $goodsModel->where(['category_id' => $id])->select();
        if (!empty($check)) {
            return json(['message' => '该分类已有产品使用'], 400);
        }
        // 检查分类是否绑定属性
        $attributes = Cache::store('category')->getAttribute($id);
        if (!empty($attributes['group'])) {
            return json(['message' => '该分类已绑定属性，请先删除属性'], 400);
        }
        //启动事务
        Db::startTrans();
        try {
            $categoryModel->where(['id' => $id])->delete();
            $categoryMap = new CategoryMap();
            $categoryMap->where(['category_id' => $id])->delete();
            Db::commit();
            //删除缓存
            Cache::handler()->del('cache:category_tree');
            Cache::handler()->del('cache:category');
            return json(['message' => '删除成功'], 200);
        } catch (\Exception $e) {
            Db::rollback();
            return json(['message' => '删除失败 ' . $e->getMessage()], 400);
        }
    }

    /**
     * 删除缓存
     * @title 删除缓存
     * @url /categories/cache
     * @method get
     */
    public function delCache()
    {
        $request = Request::instance();
        $cache = $request->get('cache', 0);
        if (!empty($cache)) {
            Cache::handler()->del($cache);
        }
    }

    /**
     * 获取用户名称
     * $param int $id
     * @return string
     */
    private function getUserNameById($id)
    {
        static $result = [];
        if (in_array($id, array_keys($result))) {
            return $result[$id];
        }
        $user_info = Cache::store('user')->getOneUser($id);
        $result[$id] = empty($user_info) ? '' : $user_info['username'];
        return $result[$id];
    }

    private function getRealNameById($id)
    {
        static $result = [];
        if (in_array($id, array_keys($result))) {
            return $result[$id];
        }
        $user_info = Cache::store('user')->getOneUser($id);
        $result[$id] = empty($user_info) ? '' : $user_info['realname'];
        return $result[$id];
    }

    /**
     * 修改分类排序
     * @title 修改产品分类排序
     * @method put
     * @url /categories/sorts
     * @param  \think\Request $request
     * @return \think\Response
     */
    public function sorts(Request $request)
    {
        $sorts = $request->put('sort');
        if ($sorts) {
            $sorts = json_decode($sorts);
            if ($sorts) {
                Cache::store('Category')->updateSorts($sorts);
                return json(['message' => '更新成功']);
            } else {
                return json_error('非法请求');
            }
        } else {
            return json_error('非法请求');
        }
    }

    /**
     * 显示分类列表
     * @title 分类列表
     * @url /categories/lists
     * @method get
     * @return \think\Response
     */
    public function lists()
    {
        try {
            $help = new CategoryHelp();
            $result = $help->getCategoryLists();
            return json($result, 200);
        } catch (Exception $e) {
            return json(['message' => '数据异常' . $e->getMessage()], 400);
        }
    }

}
