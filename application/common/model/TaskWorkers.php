<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-3-3
 * Time: 下午4:10
 */

namespace app\common\model;


use think\Model;

class TaskWorkers extends Model
{
    /**
     * @param $workId
     * @return self
     */
    public static function getWorker($workId)
    {
        $self = new static();
        $self->where('deleted_at', 0);
        $self->where('id', $workId);
        return $self->find();
    }
}