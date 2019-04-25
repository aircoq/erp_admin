<?php

namespace app\index\service;

use app\common\model\DepartmentType;

/**
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/10/18
 * Time: 15:31
 */
class DepartmentTypeService
{
    const GROUP = 1;    //组
    const DEPARTMENT = 2;  //部门
    const COMPANY = 3;    //公司
    const PARCEL_DEPARTMENT = 4; //分部

    const TYPE_TXT = [
        self::GROUP => '组',
        self::DEPARTMENT => '部门',
        self::COMPANY => '公司',
        self::PARCEL_DEPARTMENT => '分部',
    ];
    protected $departmentTypeModel = null;

    public function __construct()
    {
        if (is_null($this->departmentTypeModel)) {
            $this->departmentTypeModel = new DepartmentType();
        }
    }

    /**
     * 获取部门类型
     * @param int $type
     * @return array|false|\PDOStatement|string|\think\Collection|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function info($type = 0)
    {
        if (!empty($type)) {
            $where['id'] = ['eq', $type];
            $infoList = $this->departmentTypeModel->field(true)->where($where)->find();
        } else {
            $infoList = $this->departmentTypeModel->field(true)->select();
        }
        return $infoList;
    }


}