<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-8-30
 * Time: 下午1:45
 */

namespace app\common\filter;


use app\common\exception\FilterRuleException;
use app\common\exception\JsonErrorException;
use app\common\interfaces\IModelFilter;
use think\db\Query;
use think\Model;

abstract class BaseFilter implements IModelFilter
{
    private $config;

    protected $scope;

    public final function __construct($config)
    {
        $this->config = $config;
    }

    protected abstract function generate();
    /**
     * @doc 获取过滤器设置好的参数
     * @param $key string 参数key,
     * @return mixed
     */
    protected function getConfig()
    {
        return $this->config;
    }

    private static function checkConfigOptions($options)
    {
        if(!is_array($options)){
            throw new JsonErrorException("options必需为数组结构");
        }
    }

    public static function checkConfig()
    {
        $config = static::config();
        $key = param($config, 'key', static::class);
        $type= param($config, 'type');
        $name= static::getName();
        $class= static::class;
        if(!$name){
            throw new JsonErrorException("配置必需指定名称");
        }
        if(!is_string($key)){
            throw new JsonErrorException("key必需为字符串");
        }
        if(!in_array($type, static::TYPES)){
            throw new JsonErrorException('配置少了类型，或不合法的类型');
        }
        if(in_array($type, static::TYPES_SELECT)){
            if(!$options = param($config, 'options')){
                throw new JsonErrorException("selet类型配置，必需配置options");
            }
            self::checkConfigOptions($options);
        }
        $config['class'] = $class;
        $config['name'] = $name;
        return $config;
    }

    protected function getCommonConfig($key)
    {
        if(class_exists($key)){
            if(is_subclass($key, BaseFilter::class)){
                return forward_static_call([$key, 'configs']);
            }
        }
        return null;
    }

    public function filter()
    {
        $params = $this->generate();
        return ['class'=>static::class, 'scope'=>$this->scope, 'params'=>$params];
    }
}