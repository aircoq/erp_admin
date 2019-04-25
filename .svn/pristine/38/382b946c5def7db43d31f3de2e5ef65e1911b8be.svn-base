<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-4-7
 * Time: 上午10:41
 */

namespace app\common\cache;


class TaskWorker extends CacheModel
{
    protected static $fields = [
        'name',
        'type',
        'task_id',
        'begin',
        'end',
        'max_count',
        'use_count',
        'mode',
        'loop_value',
        'loop_type',
        'param',
        'status',
        'run_tag',
        'run_status',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function getStatusAttr($val)
    {
        if(is_string($val)){
            $val = json_decode($val);
            return $val;
        }else{
            return $val;
        }
    }

    public function setStatusAttr($val)
    {
        if(!is_string($val)){
            $val = json_encode($val);
        }
        return $val;
    }

    public function getLoop_valueAttr($val)
    {
        if(!is_integer($val)){
            $val = (int)$val;
        }
        return $val;
    }

    public function setLoop_valueAttr($val)
    {
        if(is_integer($val)){
            $val = (string)$val;
        }
        return $val;
    }

    public function getLoop_typeAttr($val)
    {
        if(!is_integer($val)){
            $val = (int)$val;
        }
        return $val;
    }

    public function setLoop_typeAttr($val)
    {
        if(is_integer($val)){
            $val = (string)$val;
        }
        return $val;
    }

    public function getRun_tagAttr($val)
    {
        if(!is_integer($val)){
            $val = (int)$val;
        }
        return $val;
    }

    public function setRun_tagAttr($val)
    {
        if(is_integer($val)){
            $val = (string)$val;
        }
        return $val;
    }

    public function getParamAttr($val)
    {
        if(is_string($val)){
            $val = json_decode($val,true);
        }
        return $val;
    }

    public function getMax_countAttr($val)
    {
        if(!is_integer($val)){
            $val = (int)$val;
        }
        return $val;
    }

    public function setMax_countAttr($val)
    {
        if(is_integer($val)){
            $val = (string)$val;
        }
        return $val;
    }

    public function setParamAttr($val)
    {
        if(!is_string($val)){
            $val=json_encode($val);
        }
        return $val;
    }
}