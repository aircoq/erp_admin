<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-5-6
 * Time: 下午4:42
 */

namespace app\common\cache\driver;


use app\common\cache\Cache;

class Process extends Cache
{
    const tasks = "tasks:processAll";
    public function tasks()
    {
        $tasks = $this->redis->hGetAll(static::tasks);
        if($tasks){
            return $tasks;
        }else{
            return [];
        }
    }

    public function addTask($task, $num)
    {
        $this->redis->hSet(static::tasks, $task, $num);
    }

    public function delTask($task)
    {
        $this->redis->hDel(static::tasks, $task);
    }

    public function getTask($task)
    {
        return $this->redis->hGet(static::tasks, $task);
    }
}