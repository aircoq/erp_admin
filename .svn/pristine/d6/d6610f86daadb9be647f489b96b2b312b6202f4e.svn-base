<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-3-2
 * Time: 下午3:10
 */

namespace app\index\service;


use app\common\exception\JsonErrorException;
use think\Validate;

abstract class AbsTasker
{
    private $error = [];
    private $data = [];
    private $logs = [];

    public function getId()
    {
        return static::class;
    }

    /**
     * 定义任务名称
     * @return string
     */
    public abstract function getName();

    /**
     * 定义任务描述
     * @return string
     */
    public abstract function getDesc();

    /**
     * 定义任务作者
     * @return string
     */
    public abstract function getCreator();

    /**
     * 定义任务参数规则
     * @return array
     */
    public abstract function getParamRule();

    public function getParamConfigs()
    {
        $rules = $this->getParamRule();
        $result = [];
        foreach ($rules as $key => $rule){
            if(is_string($rule)){
                $temp = [];
                $rule = explode('|', $rule);
                $validate = [];
                foreach ($rule as $value){
                    switch ($value){
                        case 'require':
                        case 'required':
                           $validate['require'] = true;
                           break;
                        default:
                            if(preg_match("/select:(.*)/", $value, $match)){
                                $temp['type'] = 'select';
                                $matchOptions = explode(',',$match[1]);
                                $options = [];
                                foreach ($matchOptions as $matchOption){
                                    $matchOption = explode(':',$matchOption);
                                    $options[] = [
                                        $matchOption[0] =>
                                            $matchOption[1] ?? $matchOption[0]
                                    ];
                                }
                                $temp['options'] = $options;
                                break;
                            }
                            if(preg_match('/in:(.*)/', $value, $match)){
                                $matchOptions = explode(',',$match[1]);
                                $temp['type'] = 'select';
                                $options = [];
                                foreach ($matchOptions as $matchOption){
                                    $options[] = [$matchOption=>$matchOption];
                                }
                                $temp['options'] = $options;
                                break;
                            }
                            if(preg_match("/min:([\d]+)/", $value, $match)){
                                $validate['min'] = $match[1];
                                break;
                            }
                            if(preg_match("/max:([\d]+)/", $value, $match)){
                                $validate['max'] = $match[1];
                                break;
                            }
                            if(preg_match("/between:([\d]+),([\d]+)/", $value, $match)){
                                $validate['min'] = $match[1];
                                $validate['max'] = $match[2];
                                break;
                            }

                    }
                }
                $key = explode('|', $key);
                $temp['key'] = $key[0];
                $temp['name'] = $key[1] ?? $key[0];
                $temp['validate'] = $validate;
                $rule = $temp;
            }else{
                $rule['key'] = $key;
            }
            $rule['type'] = $rule['type'] ?? 'input';
            $rule['validate'] = $rule['validate'] ?? [];
            $result[] = $rule;
        }
        return $result;
    }

    /**
     * 任务执行内容
     * @return void
     */
    public abstract function execute();

    /**
     * @给任务添加log
     * @param string $log
     * @param string $file
     * @param int $line
     */
    protected function addLog(string $log, string $file, int $line)
    {
        $this->logs[] = [
            'log'=>$log,
            'file'=>$file,
            'line'=>$line,
            'time'=>now()

        ];
    }

    public function getLogs()
    {
        return $this->logs;
    }


    /** 开启新的任务 beforeExec,execute,afterExec,
     * @param $taskClassParam
     * @param $time
     * @param $name
     * @return bool
     */
    public final function register($taskClassParam, $time, $name)
    {
        return TaskScheduler::register($taskClassParam, $time, $name);
    }

    /**
     * 验证参数合法性
     */
    public final function checkRule()
    {
        $errors = [];
        foreach ($this->getParamRule() as $key => $value) {
            if(is_numeric($key)){
                $key = param($value, 'key');
            }
            $name = param($value, 'name');
            if(isset($this->data[$key])){
                $data = $this->data[$key];
                switch (param($value, 'type')){
                    case 'input':
                        foreach (param($value, 'validate', []) as $valid=>$validValue){
                            switch ($valid){
                                case 'max':
                                    if($data > $validValue){
                                        $errors[] = "$name 参数大于 $validValue";
                                    }
                                    break;
                                case 'min':
                                    if($data < $validValue){
                                        $errors[] = "$name 参数小于 $validValue";
                                    }
                                    break;
                                case 'preg':
                                case 'regexp':
                                    if(!preg_match($validValue, $data)){
                                        $errors[] = "$name 匹配不上 $validValue";
                                    }
                            }
                        }
                        break;
                    case 'select':
                        if($options = param($value, 'options')){
                            if(!in_array($data, array_values($options))){
                                $errors[] = "$name Select 值不在options中";
                            }
                        }else{
                            $errors[] = "$name is Select but not define options";
                        }
                        break;
                }
            }else{
                $valids = param($value, 'validate', []);
                if(in_array('require', $valids)){
                    $errors[] = "$name 参数必填";
                }
            }
        }
        if(!empty($errors)){
            throw new JsonErrorException(join('; ',$errors));
        }
    }

    /**
     * 任务运行前调用,
     * @return bool false时不会调用execute
     */
    public function beforeExec()
    {
        return true;
    }

    /**
     * 任务运行完后调用,
     *
     */
    public function afterExec()
    {
    }

    /**
     * 添加任务失败，获取失败原因
     * @return array
     */
    public final function getError()
    {
        return $this->error;
    }

    /**
     * 设置任务执行参数
     * @param array $data
     */
    public final function setData($data)
    {
        if(is_string($data)){
            $data= json_decode($data,true);
        }
        $this->data = $data;
    }

    /**
     * 获取任务执务参数
     * @return array | string
     */
    protected final function getData(){
        $args = func_get_args();
        switch (count($args)){
            case 0:
                return $this->data;
            case 1:
                $param = $args[0];
                if(isset($this->data[$param])){
                    return $this->data[$param];
                }else{
                    $rules = $this->getParamRule();
                    if(isset($rules[$param])){
                        $rule = $rules[$param];
                        $rules = explode('|',$rule);
                        foreach($rules as $rule){
                            $match = "";
                            if(preg_match("/^default:(.*)/", $rule, $match)){
                                return $match;
                            }
                        }
                        return null;
                    }
                    return null;
                }
                break;
            case 2:
                return isset($this->data[$args[0]]) ? $this->data[$args[0]] : $args[1];
        }
    }

    protected function getParam($key, $def = null)
    {
        if(isset($this->data[$key])){
            return $this->data[$key];
        }else{
            $params = $this->getParamRule();
            foreach ($params as $param) {
                if($param['key'] === $key){
                    return $param['default'] ?? $def;
                }
            }
        }
        return null;
    }

}
