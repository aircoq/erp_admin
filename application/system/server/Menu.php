<?php
namespace app\system\server;

use app\common\cache\Cache;
use app\common\service\UniqueQueuer;
use app\index\service\Role;
use app\index\service\User;
use app\system\cache\MenuPages;
use erp\AbsServer;
use app\common\exception\JsonErrorException;
use app\common\model\system\Menu as MenuModel;
use erp\ErpRbac;
use think\db\Query;

/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-3-20
 * Time: 下午3:18
 */
class Menu extends AbsServer
{
    const TYPE_CLASSIFY = 0;// 0分类
    const TYPE_LINK = 1;// 1链接
    const TYPE_SYSTEM = 2;// 2内部
    protected $model = MenuModel::class;
    protected $roleServer = Role::class;

    private $queuer = null;

    /**
     * @var MenuPages
     */
    private $cache = null;

    public function __construct()
    {
        parent::__construct();
        $this->cache = Cache::moduleStore('menuPages');
        $this->queuer = new UniqueQueuer(static::class);
    }

    public function clearUsersPages()
    {
        $this->queuer->push('xxx');
        $this->cache->delAll();
    }

    public function execute()
    {
        $params = $this->queuer->pop();

    }

    public function push($param)
    {
        $this->queuer->push($param);
    }

    /**
     * 创建菜单
     * @param $post
     * @return mixed
     */
    public function create($post)
    {
        $menu = new MenuModel();
        $menu->remark = $post['remark'];
        if($post['pid']){
            if(!($parent = $this->get($post['pid']))){
                throw new JsonErrorException("非法创建菜单，不存在的父级菜单");
            }
            if($parent->type !== static::TYPE_CLASSIFY){
                throw new JsonErrorException("非法创建菜单，父级菜单不是目录");
            }
        }
        $sql = "INSERT INTO menu (`pid`, `title`, `type`, `paths`, `sort`, `group`, `status`, `english_title`)";
        $sql .= "VALUES ('{$post['pid']}', '{$post['title']}', {$post['type']}, '{$post['paths']}', {$post['sort']}, {$post['group']},{$post['status']},'{$post['english_title']}')";
        $menu->execute($sql);
        return $menu->getLastInsID();
//        $menu->pid = $post['pid'];
//        $menu->title = $post['title'];
//        $menu->type = $post['type'];
//        $menu->paths = $post['paths'];
//        $menu->sort = $post['sort'];
//        $menu->group = $post['group'];
//        $menu->status = $post['status'];
//        $menu->english_title = $post['english_title'];
//        $menu->save();
//        return $menu->id;
    }

    /**
     * 获取菜单列表
     * @param $group
     * @return array
     */
    public function get_list($where, $status = null)
    {
        $this->model->order('sort');
        $list = $this->select($where);
        if(is_null($status)){
            return $list;
        }
        function getMenu($menuId, $list){
            foreach ($list as $menu){
                if($menu->id === $menuId){
                    return $menu;
                }
            }
            return false;
        }
        function isPass($menu, $status, $list){
            if($menu->pid === 0){
                return $menu->status === $status;
            }
            $parent = getMenu($menu->pid, $list);
            if($status !== $parent->status){
                return false;
            }
            return isPass($parent, $status, $list);
        }
        $result = [];
        foreach ($list as $menu){
            if(static::TYPE_CLASSIFY === $menu->type && $menu->status !== $status){
                continue;
            }
            if(isPass($menu, $status, $list)){
                $result[] = $menu;
            }
        }
        return $result;
    }

    public function get_pages($param)
    {
        $userId = User::getCurrent();
        $rbac = ErpRbac::getRbac($userId);
        $roleServer = new Role();
        $list = $this->get_list(['group'=>0],1);
        if($rbac->isAdmin()){
            $result = $list;
        }else{
            if($pages = $this->cachePages($userId)){
                return $pages;
            }
            $roles = $rbac->getRoles();
            $pages = [];
            foreach ($roles as $role){
                $page = $roleServer->getPages($role->role_id);
                if($page){
                    $pages = array_merge_plus($pages, $page);
                }else{
                    echo $role->role_id;
                }
            }
            foreach ($list as $key=>$item){
                if($this->isRouteMenu($item) && !in_array($item->paths, $pages)){
                    unset($list[$key]);
                }
            }
            $pages = array_values($list);
            $this->cachePages($userId, $pages);
            $result = $pages;
        }
                 $item=[];
        if(isset($param['lang'])&&$param['lang']=='en'){
            foreach($result as $item){
                $item['title']=($item['english_title'])?$item['english_title']:$item['title'];
            }
        }

        return $result;
    }

    public function cachePages($userId, $pages = null)
    {
        if($pages){
            $this->cache->setPages($userId, $pages);
            return $pages;
        }else{
            return $this->cache->getPages($userId);
        }
    }

    public function cachePagesDel($userId)
    {
        $this->cache->delPages($userId);
    }

    public function isRouteMenu($menu)
    {
        if($menu->type === 1){//0,1,2
            return true;
        }else{
            return false;
        }
    }

    /**
     * 批量删除
     * @param $deletes
     * @throws JsonErrorException
     */
    public function deletes($deletes)
    {
        $models = MenuModel::all(function(Query $query)use($deletes){
            $deletes = join(",",$deletes);
            $query->where('id', 'in', $deletes);;
        });
        //先处理删除的
        foreach ($models as $model) {
            if($model->childs('count')){
                throw new JsonErrorException('存在子菜单');
            }
            $model->delete();
            $this->clearUsersPages();
        }
    }

    /**
     * 批量排序
     * @param $sorts
     */
    public function sorts($sorts)
    {
        foreach ($sorts as $sort){
            foreach ($sort as $item){
                $menu = MenuModel::get($item->id);
                $menu->pid = $item->pid;
                $menu->sort = $item->sort;
                $menu->save();
                $this->clearUsersPages();
            }
        }
    }

    /**
     * 修改菜单状态
     * @param $id
     * @param $status
     * @throws JsonErrorException
     */
    public function changeStatus($id, $status)
    {
        $menu = $this->get($id);
        if(!$menu){
            throw new JsonErrorException("不存在的菜单");
        }
        $menu->status = $status;
        $menu->save();
        $this->clearUsersPages();
    }

    /**
     * 修改菜单
     * @param $id
     * @param $params
     * @return bool
     * @throws JsonErrorException
     */
    public function modify($id, $params)
    {
        $menu = $this->get($id);
        if($menu){
            if($params['pid'] > 0){
                $parent = $this->get($params['pid']);
                if(!$parent){
                    throw new JsonErrorException('非法父菜单');
                }
                if(($parent->type) > 0) {
                    throw new JsonErrorException('非法设置');
                }
            }
            $menu->type = $params['type'];
            $menu->status = $params['status'];
            $menu->title = $params['title'];
            $menu->pid = $params['pid'];
            $menu->sort = $params['sort'];
            $menu->paths = $params['paths'];
            $menu->english_title=$params['english_title'];
            $menu->remark = $params['remark'];
            $menu->group = $params['group'];
            $menu->save();
            $this->clearUsersPages();
            return true;
        }else{
            throw new JsonErrorException("不存在的菜单");
        }
    }
}