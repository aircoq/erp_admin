<?php
namespace app\report\service;


/**
 * Created by PhpStorm.
 * User: libaimoin
 * Date: 18-10-31
 * Time: 上午10:28
 */
class MonthlyDepartmentTypeService
{
    const GROUP = 1;    //组
    const DEPARTMENT =0;  //部门
    const USER = 2;    //用户


    public function __construct()
    {

    }

    /**
     * 获取部门类型
     * @param int $type
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function info($type = 0)
    {
        $infoList = [
            [
                'id' => self::DEPARTMENT,
                'name' => '部门',
            ],
            [
                'id' => self::GROUP,
                'name' => '组',
            ],
            [
                'id' => self::USER,
                'name' => '用户',
            ],
        ];
        return $infoList;
    }
}