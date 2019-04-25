<?php

namespace app\common\model;

use app\common\cache\Cache;
use think\Model;
use app\index\service\DepartmentUserMapService;
use app\index\service\Department as DepartmentServer;
/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/12/8
 * Time: 17:46
 */
class Software extends Model
{
    /**
     * 软件管理
     */
    protected function initialize()
    {
        parent::initialize();
    }

    //0-账号app、1-打印软件、2-服务软件
    const type_account = 0;
    const type_print = 1;
    const type_server = 2;
    const type_agency = 3;

    const TYPE = [
        Software::type_account => '账号app',
        Software::type_print => '打印软件',
        Software::type_server => '服务软件',
        Software::type_agency => '代理软件',
    ];


    /**
     * @param $where
     * @return array|bool|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function isHas($where)
    {
        $result = $this->where($where)->find();
        if (empty($result)) {   //不存在
            return false;
        }
        return $result;
    }

    public function getCreatorTxtAttr($value, $data)
    {
        return Cache::store('user')->getOneUserRealname($data['creator_id']);
    }

    public function getDepartmentNameAttr($value, $data)
    {
        $departmentUserMapService = new DepartmentUserMapService();
        $department_ids = $departmentUserMapService->getDepartmentByUserId($data['creator_id']);
        $departmentInfo = '';
        $departmentServer = new DepartmentServer();
        foreach ($department_ids as $d => $department) {
            if (!empty($department)) {
                $departmentInfo .= $departmentServer->getDepartmentNames($department) . '   ,   ';
            }
        }
        $departmentInfo = rtrim($departmentInfo, '   ,   ');
        return $departmentInfo;
    }
}