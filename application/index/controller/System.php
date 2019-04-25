<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-5-3
 * Time: 上午11:50
 */

namespace app\index\controller;


use Api\Doc\Doc;
use app\api\service\Base;
use app\common\cache\Cache;
use app\common\exception\JsonErrorException;
use app\common\filter\BaseFilter;
use app\common\model\McaNode;
use app\common\model\RoleAccess;
use app\system\server\Menu;
use erp\AbsDataFilter;
use Minime\Annotations\Reader;
use Nette\Reflection\ClassType;
use Nette\Utils\Reflection;
use PhpParser\Node\Expr\BooleanNot;
use think\Exception;
use think\exception\ErrorException;
use think\Request;
use think\Route;
use think\Db;

class System extends Base
{
    private $files = [];
    private $error = [];
    private $defines=[];

    public function __construct()
    {
        $this->files = dir_iteration(APP_PATH,function($file){return $file;});
    }

    public function index()
    {

    }

    public function gen_filters()
    {
    }

    public function gen_routes(Request $request)
    {
        set_time_limit(0);
        try{
            $list = $this->getRouteControllers();
            $controllers = $list['list'];
//            if($oldControllers = Cache::handler()->get('RouteControllersMd5')){
//                if(md5(json_encode($controllers)) == $oldControllers){
//                    return json(['msg'=>"路由不变,无需生成"]);
//                }
//            }
            $this->toRouteAuto($controllers);
            $this->toRouteIgnore($controllers);
            $this->toRouteInfo($controllers);
            //$this->refreshCache();
            $this->clearCache();
            Cache::handler()->set('RouteControllersMd5', md5(json_encode($controllers)));
            $this->doTmpFile(1);
            return json(['msg'=>"生成成功"]);
        }catch (Exception $ex){
            $this->doTmpFile();
            return json(['msg'=>"生成失败",'error'=>$ex->getMessage()]);
        }

    }

    private function refreshCache()
    {
        $menuServer = new Menu();
        $menuServer->clearUsersPages();
        $role = new \app\index\service\Role();
        $role->clearRbac();
        \app\index\service\Role::refreshAllPermissions();
    }

    private function toRouteInfo($controllers)
    {
        try{
            Db::startTrans();
            $ids = McaNode::all(function($query){
                $query->field('id');
            });
            $allIds = array_map(function($id){return $id['id'];},$ids);
            $exists = [];
            foreach ($controllers as $controller){
                    $this->checkController($controller);
                    $reflection = new \ReflectionClass($controller['class']);
                    $traits = $reflection->getTraitNames();
                    $filter_method = [];
                    if($traits){
                        foreach ($traits as $trait){
                            $filter_method = array_merge($filter_method, get_class_methods($trait));
                        }
                    }
                    foreach ($controller['actions'] as $action){
                        if($filter_method && in_array($action['method_name'], $filter_method)){
                            continue;
                        }
                        $this->checkAction($action);
                        $node = McaNode::get([
                            'mca'=> $action['mca']
                        ]);
                        $title = $this->fixTitle($action);
                        $route = $this->url2route($action['url']);
                        $relates = [];
                        if($apiRelates = param($action, 'apiRelate')){
                            foreach ($apiRelates as $apiRelate){
                                $apiRelate = trim($apiRelate);
                                if(preg_match('/[\\/|\\|]/',$apiRelate)){
                                    $msg = "{$action['mca']} relate error : $apiRelate not support";
                                    throw new Exception($msg);
                                }else{
                                    $apiRelate = explode('::', $apiRelate);
                                    switch (count($apiRelate)){
                                        case 1:
                                            $apiClass = $controller['class'];
                                            $apiMethod= $apiRelate[0];
                                            break;
                                        case 2:
                                            $apiClass = $apiRelate[0];
                                            $apiMethod= $apiRelate[1];
                                            break;
                                        default:
                                            $msg = "{$action['mca']} relate error ";
                                            throw new Exception($msg);
                                    }
                                    $apiClass = trim($apiClass);
                                    if(!class_exists($apiClass)){
                                        $msg = "{$action['mca']} relate error : class {$apiClass} not found";
                                        throw new Exception($msg);
                                    }
                                    $apiMethods = explode('&', trim($apiMethod));
                                    $classMethods = get_class_methods($apiClass);
                                    foreach ($apiMethods as $apiMethod){
                                        $apiMethod = trim($apiMethod);
                                        if(!in_array($apiMethod, $classMethods)){
                                            $msg = "{$action['mca']} relate error : class {$apiClass} method {$apiMethod} not found";
                                            throw new Exception($msg);
                                        }
                                        $relate = [$apiClass,$apiMethod];
                                        if(in_array($relate, $relates)){
                                            $msg = "{$action['mca']} relate error : class {$apiClass} method {$apiMethod} repetition";
                                            throw new Exception($msg);
                                        }
                                        $relates[] = $relate;
                                    }
                                }
                            }
                        }
                        $filters = [];
                        $apiFilters = param($action, 'apiFilter', []);
                        foreach ($apiFilters as $filter){
                            if(class_exists($filter)){
                                if(is_subclass($filter, BaseFilter::class)){
                                    $filters[] = $filter;
                                }
                            }
                        }
                        if(!$node){
                            if($node = McaNode::get([
                                'method' => $action['method'],
                                'route' => $route
                            ])){
                                $node->mca = $action['mca'];
                                $node->title = $title;
                                $node->name = $action['name'];
                                $node->class_title = $controller['title'];
                                $node->module= $controller['module'];
                                $node->relates= $relates;
                                $node->filternodes= $filters;
                                $node->save();
                                array_push($exists, $node->id);
                            }else{
                                McaNode::create([
                                    'title'=>$title,
                                    'mca'=>$action['mca'],
                                    'name'=>$action['name'],
                                    'class_title' => $controller['title'],
                                    'method'=>strtolower($action['method']),
                                    'route'=>$route,
                                    'relates'=>$relates,
                                    'module'=>$controller['module'],
                                    'pagenodes'=>[],
                                    'filternodes'=> $filters
                                ]);
                            }
                        }else{
                            array_push($exists, $node->id);
                            $node->method = strtolower($action['method']);
                            $node->route = $route;
                            $node->name = $action['name'];
                            $node->title = $title;
                            $node->module= $controller['module'];
                            $node->class_title = $controller['title'];
                            $node->relates= $relates;
                            $node->filternodes= $filters;
                            $node->save();
                        }
                    }
            }
            $removes = array_diff($allIds, $exists);
            Cache::handler()->hSet('hash:routeInfo' . date('Ymd') . ':' . date('H'),date('Y-m-d H:i:s'), json_encode(['all' => $allIds,'exists' => $exists,'controller' => $controllers]));
            if(!empty($removes)){
                McaNode::where(['id'=>['in', array_values($removes)]])->delete();
                RoleAccess::where(['node_id'=>['in', array_values($removes)]])->delete();
                Cache::handler()->hSet('hash:routeInfo:delete' . date('Ymd') . ':' . date('H'),date('Y-m-d H:i:s'), json_encode(['remove' => $removes]));
            }
            $cache = Cache::handler(false);
            $nodes = $cache->keys('node_*');
            if($nodes){
                call_user_func_array([$cache, 'delete'], $nodes);
            }
            Db::commit();
        }catch (Exception $exception){
            Db::rollback();
            throw new Exception($exception->getMessage());
            //$this->error[] = $exception->getMessage();
        }
    }

    public function toRouteIgnore($controllers)
    {
        $ignoreFile = APP_PATH.'ignore_auths.php';
        if(!file_exists($ignoreFile)){
            $open = fopen($ignoreFile, 'w');
        }else{
            $open = fopen($ignoreFile.'.new', 'w');
        }
        fwrite($open, "<?php\n");
        $ignores = [];
        foreach ($controllers as $controller){
            foreach ($controller['actions'] as $action){
                if(isset($action['noauth'])){
                    $class = $controller['class'];
                    $name  = $action['method_name'];
                    $api = strtolower($action['method'])."|".$this->url2route($action['url'], true);
                    $ignores[] = "\t[{$class}::class, '{$name}', '{$api}']";
                }
            }
        }
        $ignores = join(",\n", $ignores);
        fwrite($open, "return [\n{$ignores}\n];");
    }

    public function fixTitle($action)
    {
        $mca = param($action, 'mca');
        $title = param($action, 'title');
        if(preg_match('/.*index$/i', $mca)){
            return $title."（页面）";
        }else{
            return $title;
        }
    }

    private function checkAction($action, $controller = [])
    {
        $class = param($controller, 'class');
        $mca = param($action, 'name');
        if(!param($action, 'title')){
            throw new Exception("class:{$class} action:{$mca} not define `title`");
        }
        if(!param($action, 'url')){
            throw new Exception("class:{$class} action:{$mca} not define `url`");
        }
        if(!param($action, 'method')){
            throw new Exception("class:{$class} action:{$mca} not define `method`");
        }
        if(!param($action, 'mca')){
            throw new Exception("class:{$class} action:{$mca} not define `mca`");
        }
    }

    public function checkController($controller)
    {
        $class = param($controller, 'class');
        if(!param($controller, 'title')){
            throw new Exception("class {$class} not define title");
        }

        if(!param($controller, 'module')){
            throw new Exception("class {$class} not define module");
        }

    }

    private function toRouteAuto($controllers)
    {
        $apiAuto = APP_PATH.'route_api.php';
        $routeAuto = APP_PATH.'route_auto.php';
        if(!file_exists($routeAuto)){
            $routeOpen = fopen($routeAuto, 'w');
        }else{
            $routeAutoNew = $routeAuto.'.new';
            $routeOpen = fopen($routeAutoNew, 'w');
        }
        if(!file_exists($apiAuto)){
            $apiOpen = fopen($apiAuto, 'w');
        }else{
            $apiAutoNew = $apiAuto.'.new';
            $apiOpen = fopen($apiAutoNew, 'w');
        }
        fwrite($routeOpen, "<?php\n");
        fwrite($apiOpen, "<?php\n");
        try{
            foreach ($controllers as $controller){
                $str = "\n//控制器：".$controller['class']."\n";
                fwrite($routeOpen, $str);
                fwrite($apiOpen, $str);
                $reflection = new \ReflectionClass($controller['class']);
                $traits = $reflection->getTraitNames();
                $filter_method = [];
                if($traits){
                    foreach ($traits as $trait){
                        $filter_method = array_merge($filter_method, get_class_methods($trait));
                    }
                }
                foreach ($controller['actions'] as $route){
                    if($filter_method && in_array($route['method_name'], $filter_method)){
                        continue;
                    }
                    $this->checkAction($route, $controller);
                    $str = "//{$route['title']}\n";
                    fwrite($routeOpen, $str);
                    $routeStr = $this->urlConfig2route($route['url'],$route['method'],$route['mca']);
                    if($api = $this->urlConfig2Api($route)){
                        fwrite($apiOpen, $api);
                    }
                    fwrite($routeOpen, $routeStr);
                }
            }
        }catch (Exception $exception){
            fclose($routeOpen);
            throw new Exception($exception->getMessage());
        }
        fclose($routeOpen);
    }

    private function urlConfig2route($url, $method, $mca)
    {
        $preg = '/\/:?:([\d\w]+)(?:(\([\\\\\d\w\+]+\)))?/';
        $matchUrl = [];
        if(preg_match_all($preg, $url, $match)){
            foreach ($match[1] as $key=>$val){
                $val2 = $match[2][$key];
                if(!$val2){
                    continue;
                }
                $matchUrl[$val] = $val2;
            }
        }
        $url = $this->url2route($url);
        $matchUrl = $this->array2string($matchUrl);
        return Route::class."::{$method}('{$url}$', '{$mca}',[], $matchUrl);\n";
    }

    private function urlConfig2Api($action)
    {
        list('url'=>$url, 'method'=>$method,'title'=>$title,'mca'=>$mca) = $action;
        $preg = '/\/:?:([\d\w]+)(?:(\([\\\\\d\w\+]+\)))?/';
        $matchUrl = [];
        if(preg_match_all($preg, $url, $match)){
            foreach ($match[1] as $key=>$val){
                $val2 = $match[2][$key];
                if(!$val2){
                    continue;
                }
                $matchUrl[$val] = $val2;
            }
        }
        $url = $this->url2route($url,true);
        $api = strtolower($method)."|".$url;
        $name = "API_".strtoupper($method)."_".preg_replace_callback("/(\\/|:|(-([a-z])))/",function($val){
                switch ($val[1]){
                    case '/':
                        return "_";
                    case ':':
                        return "__";
                    default:
                        return chr(ord(array_last($val))-32);
                }
            },$url);
        if(array_key_exists($name, $this->defines)){
            $this->error[] = "[{$this->defines[$name]}]的路由和[$mca] ： $method=>$url 相同";
            return false;
        }
        $this->defines[$name] = $mca;
        return "//@title $title\ndefine('$name','$api');\n";
    }

    private function url2route($url, $fix = false)
    {
        $url = preg_replace("/\([\\\\\d\w\+]+\)/","",$url);
        if($fix){
            $url = preg_replace("/^\\//", "", $url);
        }
        return $url;
    }

    private function array2string($array)
    {
        $ret = [];
        foreach ($array as $key => $val){
            if(is_array($val)){
                $val = $this->array2string($val);
                $ret[] = "'$key'=>$val";
            }else{
                $ret[] = "'$key'=>'$val'";
            }
        }
        $ret = join(", ",$ret);
        return "[$ret]";

    }

    private function getRouteControllers()
    {
        $config = \think\Config::get('doc');
        $doc = new Doc($config);
        return $doc->getList();
    }

    public function gen_routes2(Request $request, $id)
    {
        var_dump($request->routeInfo());
        var_dump($id);
    }

    private function doTmpFile($isSuccess = false)
    {
        $tmpFileArr = [
             APP_PATH.'route_api.php',
             APP_PATH.'route_auto.php',
             APP_PATH.'ignore_auths.php'
        ];
        foreach($tmpFileArr as $file){
            if(file_exists($fileNew = $file.'.new')){
                if($isSuccess){
                    rename($fileNew, $file);
                }else{
                    @unlink($fileNew);
                }
            }
        }

    }

    private function clearCache()
    {
        $menuServer = new Menu();
        $menuServer->clearUsersPages();
        $role = new \app\index\service\Role();
        $role->clearRbac();
        $roleCache = new \app\index\cache\Role();
        $roleCache->clearPages();
        $roleCache->clearPermission();
    }
}