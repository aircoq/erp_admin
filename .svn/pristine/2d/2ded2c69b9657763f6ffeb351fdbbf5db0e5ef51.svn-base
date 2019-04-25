<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-7-24
 * Time: 上午11:40
 */

namespace app\common\service;


class Param
{
    private $params;
    private $value;
    public function __construct($params)
    {
        $this->params = $params;
    }

    public function __invoke($name)
    {
        if(isset($this->params[$name])){
            $this->value = $this->params[$name];
        }
        return $this;
    }

    public function getValue($default = '')
    {
        if(is_null($this->value)){
            return $default;
        }else{
            $value = $this->value;
            $this->value = null;
            return $value;
        }
    }
}