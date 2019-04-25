<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-3-20
 * Time: 下午2:18
 */

namespace app\common\behavior;


use think\Config;
use think\exception\HttpResponseException;
use think\Request;
use think\Response;

class RuleCheck
{
    public function run(&$param)
    {
        $config = Config::get('rule_check');
        if(!$config){
            return;
        }
        $request = Request::instance();
        $method = $request->method();
        $model = $request->module();
        $contr = $request->controller();
        $action= $request->action();
        $validate = 'app\\'.$model."\\validate\\".$contr;
        try{
            $validate = new $validate;
        }catch (\Exception $exception){
            $res = Response::create($validate."未定义", 'json', 400, [], []);
            throw new HttpResponseException($res);
        }
        if(in_array($method,$validate->getAuthoMethods())){
            $data = $request->param();
            if(is_callable([$validate, $action])){
                $validate->$action($data);
            }else{
                $validate->scene($action)->check($data);
            }
            if($error = $validate->getError()){
                $res = Response::create($error, 'json', 400, [], []);
                throw new HttpResponseException($res);
            }
        }
    }
}