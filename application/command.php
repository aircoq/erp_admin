<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yunwuxin <448901948@qq.com>
// +----------------------------------------------------------------------

return [
    'command\Cmd',
    'command\Tasker',
    'command\Task',
    'command\ModelDataClass',
    'command\Wish',
    'command\Ebay',
    'command\Aliexpress',
    \app\cli\command\Tasker::class,
    \app\cli\command\Module::class,
    \command\TestQueue::class
];
