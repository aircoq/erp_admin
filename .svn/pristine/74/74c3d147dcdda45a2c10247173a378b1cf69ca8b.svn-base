<?php
namespace app\common\service;
use think\Exception;

/**
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/11/24
 * Time: 17:48
 */
class Container
{
    private $m = [];

    /**
     * 设置
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
       $this->m[$name] = $value;
    }

    /**
     * 获取
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
       return $this->build($this->m[$name]);
    }

    /**
     * 自动绑定，自动解析
     * @param $className
     * @return object
     * @throws Exception
     */
    public function build($className)
    {
        if($className instanceof \Closure){
            return $className($this);
        }
        $reflector = new \ReflectionClass($className);
        //检查类是否可实例化，排除抽象类abstract和对象接口interface
        if(!$reflector->isInstantiable()){
            throw new Exception("Can't instantiate this");
        }
        $constructor = $reflector->getConstructor();
        if(is_null($constructor)){
            return new $className;
        }
        $parameters = $constructor->getParameters();
        //递归解析构造函数的参数
        $dependencies = $this->getDependencies($parameters);
        //创建一个实例
        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * 递归解析构造函数的参数
     * @param $parameters
     * @return array
     * @throws Exception
     */
    public function getDependencies($parameters){
        $dependencies = [];
        foreach($parameters as $parameter){
            $dependency = $parameter->getClass();
            if(is_null($dependency)){
                //是变量,有默认值就设置默认值
                $dependencies[] = $this->resolveNonClass($parameter);
            }else{
                //如果是一个类，递归解析
                $dependencies[] = $this->build($parameter);
            }
        }
        return $dependencies;
    }

    /**
     * 有默认值就设置默认值
     * @param $parameter
     * @return mixed
     * @throws Exception
     */
    public function resolveNonClass($parameter){
        if($parameter->isDefaultValueAvailable()){
            return $parameter->getDefaultValue();
        }
        throw new Exception("I have no idea what to do here");
    }
}