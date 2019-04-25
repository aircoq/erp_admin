<?php

/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-1-19
 * Time: 上午11:23
 */
namespace app\report\service;

class DateTime
{
    private $source = '';
    private $y;
    private $m;
    private $d;
    function __construct($ymd)
    {
        $this->source = $ymd;
        list($this->y,$this->m,$this->d) = explode('-',$this->source);
    }

    public function withMiddle()
    {
        if($this->d >= 21){
            return 3;
        }
        if($this->d >= 11){
            return 2;
        }
        if($this->d >= 1){
            return 1;
        }
    }

    public function dayStart()
    {
        $date = date_create("{$this->source} 0:0:0");
        return $date->getTimestamp();
    }

    public function middleStart()
    {
        switch ($this->withMiddle()){
            case 1:
                $this->d = 1;
                break;
            case 2:
                $this->d = 11;
                break;
            case 3:
                $this->d = 21;
                break;
        }
        $this->refreshSource();
        return $this->dayStart();
    }

    public function middleEnd()
    {
        switch ($this->withMiddle()){
            case 1:
                $this->d = 10;
                break;
            case 2:
                $this->d = 20;
                break;
            case 3:
                $date = strtotime($this->source);
                $this->d = date('t',$date);
                break;
        }
        $this->refreshSource();
        return $this->dayEnd();
    }

    public function lastMiddle($n = 1)
    {
        switch ($this->withMiddle()){
            case 1:
                if($this->m > 1){
                    $this->m = $this->m - 1;
                }else{
                    $this->m = 12;
                }
                $this->d = 21;
                break;
            case 2:
                $this->d = 1;
                break;
            case 3:
                $this->d = 11;
                break;
        }
        $this->refreshSource();
        return $this;
    }

    public function monthStart()
    {
        $this->d = 1;
        $this->refreshSource();
        return $this->dayStart();
    }

    public function monthEnd()
    {
        $date = strtotime($this->source);
        $this->d = date('t',$date);
        return $this->dayEnd();
    }

    public function lastMonth($n = 1)
    {
        if($this->m > $n){
            $this->m = $this->m - $n;
        }else{
            $this->m = 12;
        }
        $this->refreshSource();
        return $this;
    }

    public function dayEnd()
    {
        return $this->dayStart() + 24 * 60 * 60 -1;
    }

    private function refreshSource()
    {
        $this->source = "{$this->y}-{$this->m}-{$this->d}";
    }
}