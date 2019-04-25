<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-3-9
 * Time: 下午2:06
 * Doc:注入器
 */

namespace app\common\traits;


trait Injector
{
    private $executes = [];

    /**
     * 添加执行方法
     * @param $execute
     */
    public function add($execute)
    {
        $this->executes[] = $execute;
    }

    /**
     * 移除执行方法
     * @param $execute
     * @return bool
     */
    public function del($execute)
    {
        foreach ($this->executes as $key => $exec){
            if($execute === $exec){
                unset($this->executes[$key]);
                return true;
            }
        }
        return false;
    }

    /**运行
     * @param string $call = 'run'
     */
    public function exec($call = 'run')
    {
        foreach ($this->executes as $execute)
        {
            try{
                call_user_func([$execute, $call]);
            }catch (\Exception $exp){
            }
        }
    }
}