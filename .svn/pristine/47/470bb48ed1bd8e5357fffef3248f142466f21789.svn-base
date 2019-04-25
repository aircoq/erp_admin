<?php
namespace app\common\model\monthly;

use think\Model;
use think\Db;
use traits\model\SoftDelete;

class MonthlyTargetDepartment extends Model
{
    protected $deleteTime = 'delete_time';
    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }

    public function isHas($id, $name,$pid,$mode = 0)
    {
        $where['id'] = ['<>',$id];
        $where['pid'] = ['=',$pid];
        $where['mode'] = ['=',$mode];
        return $this->where($where)->where('name', $name)->find();
    }

}
