<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-3-15
 * Time: 下午4:04
 */

namespace app\common\model;


use app\common\traits\SoftDelete;
use erp\ErpModel;
use think\db\Query;

/**
 * Class Test
 * @package app\common\model
 *
 *
 *
 */

class Test extends ErpModel
{
    use SoftDelete;

    public function getNameAttr($name)
    {
        return $this->id.";".$this->name2;
    }

    public function setNameAttr($name)
    {
        explode(';', $name);
    }


}