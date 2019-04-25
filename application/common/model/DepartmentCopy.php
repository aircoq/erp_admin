<?php
namespace app\common\model;
use traits\model\SoftDelete;
use think\Model;

/**
 * Created by Netbeans.
 * User: empty
 * Date: 2016/12/23
 * Time: 12:03
 */
class DepartmentCopy extends Department
{
    protected $table = 'department_copy';
}