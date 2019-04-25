<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
if(file_exists(APP_PATH.'route_auto.php')){
    include APP_PATH."route_auto.php";
}
if(file_exists(APP_PATH.'route_api.php')){
    include APP_PATH."route_api.php";
}
\think\Route::get('system/php_info', function(){
    phpinfo();
});
\think\Route::get('system/gen_routes/:id', "index/System/gen_routes2");
\think\Route::get('system/gen_routes', "index/System/gen_routes");
\think\Route::get('system/gen_filters', "index/System/gen_filters");

\think\Route::get('testCov', "index/test/cov");


return [
    '__pattern__' => [
        'name' => '\w+',
    ]
];