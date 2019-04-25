<?php
namespace app\common\validate;

use \think\Validate;

class Base extends  Validate
{
    /**
     * 验证单个字段规则
     * thinkphp原生checkItem非require字段当字段值为空字符串或null时不会进入验证规则     *
     * @access protected
     * @param string $field 字段名
     * @param mixed $value 字段值
     * @param mixed $rules 验证规则
     * @param array $data 数据
     * @param string $title 字段描述
     * @param array $msg 提示信息
     * @return mixed
     */
    protected function checkItem($field, $value, $rules, $data, $title = '', $msg = [])
    {
        // 支持多规则验证 require|in:a,b,c|... 或者 ['require','in'=>'a,b,c',...]
        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }
        $i = 0;
        foreach ($rules as $key => $rule) {
            if ($rule instanceof \Closure) {
                $result = call_user_func_array($rule, [$value, $data]);
                $info   = is_numeric($key) ? '' : $key;
            } else {
                // 判断验证类型
                if (is_numeric($key)) {
                    if (strpos($rule, ':')) {
                        list($type, $rule) = explode(':', $rule, 2);
                        if (isset($this->alias[$type])) {
                            // 判断别名
                            $type = $this->alias[$type];
                        }
                        $info = $type;
                    } elseif (method_exists($this, $rule)) {
                        $type = $rule;
                        $info = $rule;
                        $rule = '';
                    } else {
                        $type = 'is';
                        $info = $rule;
                    }
                } else {
                    $info = $type = $key;
                }

                // 如果不是require 有数据才会行验证
                if (0 === strpos($info, 'require') || (!is_null($value) && '' !== $value)) {
                    // 验证类型
                    $callback = isset(self::$type[$type]) ? self::$type[$type] : [$this, $type];
                    // 验证数据
                    $result = call_user_func_array($callback, [$value, $rule, $data, $field, $title]);
                } elseif (method_exists($this, $info)) {
                    $result = call_user_func_array([$this, $info], [$value, $rule, $data, $field, $title]);
                } else {
                    $result = true;
                }
            }

            if (false === $result) {
                // 验证失败 返回错误信息
                if (isset($msg[$i])) {
                    $message = $msg[$i];
                    if (is_string($message) && strpos($message, '{%') === 0) {
                        $message = Lang::get(substr($message, 2, -1));
                    }
                } else {
                    $message = $this->getRuleMsg($field, $title, $info, $rule);
                }
                return $message;
            } elseif (true !== $result) {
                // 返回自定义错误信息
                if (is_string($result) && false !== strpos($result, ':')) {
                    $result = str_replace([':attribute', ':rule'], [$title, (string) $rule], $result);
                }
                return $result;
            }
            $i++;
        }
        return $result;
    }


    /**
     * 将一个对象迭代转换成数组
     * @param $data
     * @return array
     * @throws \Exception
     */
    protected function iterable2Array($data)
    {
        $arr = [];
        if(is_object($data) || is_array($data)) {
            foreach($data as $k => $v){
                $arr[$k] = (is_object($v) || is_array($v)) ? $this->iterable2Array($v) : $v;
            }
        }else{
            throw new \Exception('Function iterable2Array must have an iterable parameter.');
        }
        return $arr;
    }
}