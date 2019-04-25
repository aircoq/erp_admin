<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-3-6
 * Time: 上午9:48
 */

namespace app\index\service;


class TaskTimer
{
    private $loop_type;
    private $loop_value;
    private $max_count;
    private $begin;
    private $end = 0;

    public static function create()
    {
        return new self();
    }

    public function loop_type($looptype)
    {
        $this->loop_type = $looptype;
        return $this;
    }

    public function loop_type_secs()
    {
        return $this->loop_type(1);
    }

    public function loop_type_mins()
    {
        return $this->loop_type(2);
    }

    public function loop_type_hours()
    {
        return $this->loop_type(3);
    }

    public function loop_type_day()
    {
        return $this->loop_type(2);
    }

    public function loop_type_week()
    {
        return $this->loop_type(1);
    }

    public function loop_value($loopvalue)
    {
        $this->loop_value = $loopvalue;
        return $this;
    }

    public function max_count($maxcount)
    {
        $this->max_count = $maxcount;
        return $this;
    }

    public function begin($begin = null)
    {
        if(is_null($begin)){
            $begin = new \DateTime();
            $begin->setDate(2017,1,1);
            $begin->setTime(0,0,0);
            $begin = $begin->getTimestamp();
        }
        if(is_int($begin)){
            $time = new \DateTime();
            $time = $time->getTimestamp();
            $begin += $time;
        }
        if(is_string($begin)){
            $time = new \DateTime($begin);
            $begin = $time->getTimestamp();
        }
        $this->begin = $begin;
        return $this;
    }

    public function end($end = null)
    {
        if(is_null($end)){
            $time = new \DateTime();
            $time->setDate(2099,12,30);
            $time->setTime(0,0,0);
            $time = $time->getTimestamp();
            $end += $time;
        }
        if(is_int($end)){
            $time = new \DateTime();
            $time = $time->getTimestamp();
            $end += $time;
        }
        if(is_string($end)){
            $time = new \DateTime($end);
            $end = $time->getTimestamp();
        }
        $this->end = $end;
        return $this;
    }

    public function param()
    {
        if(!$this->end){
            $time = new \DateTime();
            $time->setDate(2030,12,30);
            $time->setTime(0,0,0);
            $time = $time->getTimestamp();
            $this->end = $time;
        }
        if(!$this->begin){
            $begin = new \DateTime();
            $begin->setDate(2017,1,1);
            $begin->setTime(0,0,0);
            $this->begin = $begin->getTimestamp();
        }
        return [
            'loop_type' => $this->loop_type,
            'loop_value' => $this->loop_value,
            'end' => $this->end,
            'begin' => $this->begin,
            'max_count' => $this->max_count,
        ];
    }
}