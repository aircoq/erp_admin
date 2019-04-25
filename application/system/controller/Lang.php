<?php
namespace app\system\controller;

use app\common\controller\Base;
use app\common\cache\Cache;

/**
 * Class Lang
 * @title 语言管理
 * @module 系统设置
 * @package app\goods\controller
 */
class Lang extends Base
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        
    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        
    }

    /**
     * 显示指定的资源
     *
     * @param  int $id
     * @return \think\Response
     */
    public function read($id)
    {
    }

    /**
     * 显示指定的资源
     *
     * @param  int $id
     * @return \think\Response
     */
    public function edit($id)
    {
        
    }

    /**
     * 保存更新的资源
     * @param  \think\Request $request
     * @param  int $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
       
    }

    /**
     * 删除指定资源
     * @param  int $id
     * @return \think\Response
     */
    public function delete($id)
    {
        
    }
    
    /**
     * @title 获取语言字典
     * @method get
     * @url /lang/dictionary
     * @return \think\Response
     */
    public function dictionary()
    {
        $lang = $this->request->header('Lang','zh');
        if($lang=='zh'){
            $result = Cache::store('lang')->getLang();
        }else{
            $result = Cache::store('lang')->getLang();
            foreach ($result as &$v){
                $v['name'] = $v['code'];
            }
        }
        return json($result);
    }
}

