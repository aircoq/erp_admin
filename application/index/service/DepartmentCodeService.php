<?php
namespace app\index\service;

use app\common\cache\Cache;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2017/5/8
 * Time: 10:45
 */
class DepartmentCodeService
{
    /** 代码列表
     * @return mixed
     * @throws \think\Exception
     */
    public function codeList()
    {
        $codeList = Cache::store('department')->code();
        return $codeList;
    }

    public function getName($code)
    {
        $lists = $this->codeList();
        foreach ($lists as $list){
            if($list['code'] === $code){
                return $list['remark'];
            }
        }
        return '';
    }
}