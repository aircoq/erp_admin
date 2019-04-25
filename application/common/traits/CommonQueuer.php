<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 18-3-7
 * Time: 下午1:57
 */

namespace app\common\traits;


trait CommonQueuer
{
    public static $QUEUER_TYPE = \app\common\service\CommonQueuer::class;

    private $__queuer = null;
    public function push(...$argvs)
    {
        if(!$this->__queuer){
            $this->__queuer = new \app\common\service\CommonQueuer(get_class($this));
        }
        call_user_func_array([$this,"push"], $argvs);
    }

    public function pop()
    {
        if(!$this->__queuer){
            $this->__queuer = new \app\common\service\CommonQueuer(get_class($this));
        }
        return $this->__queuer->pop();
    }
}