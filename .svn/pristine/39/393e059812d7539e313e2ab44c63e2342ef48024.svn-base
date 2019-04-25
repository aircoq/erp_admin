<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-4-22
 * Time: 下午2:13
 */

namespace app\common\service;


class DataToObjArr implements \ArrayAccess , \JsonSerializable
{
    private $objArr = null;
    public function __construct($objArr)
    {
        if(is_string($objArr)){
            $objArr = json_decode($objArr);
        }
        $this->objArr = $objArr;
    }
    public function offsetExists($offset)
    {
        if(is_array($this->objArr)){
            return isset($this->objArr[$offset]);
        }else{
            return isset($this->objArr->$offset);
        }
    }

    public function offsetGet($offset)
    {
        if(is_array($this->objArr)){
            return $this->objArr[$offset];
        }else{
            return $this->objArr->$offset;
        }
    }

    public function offsetSet($offset, $value)
    {
        if($this->offsetExists($offset)){
            if(is_array($this->objArr)){
                $this->objArr[$offset] = $value;
            }else{
                $this->objArr->$offset = $value;
            }
        }
    }

    public function offsetUnset($offset)
    {
        if(is_array($this->objArr)){
            unset($this->objArr[$offset]);
        }else{
            unset($this->objArr->$offset);
        }
    }

    public function __get($key)
    {
        if(is_array($this->objArr)){
            return $this->objArr[$key];
        }else{
            return $this->objArr->$key;
        }
    }

    public function __set($key, $val)
    {
        if($this->offsetExists($key)){
            if(is_array($this->objArr)){
                $this->objArr[$key] = $val;
            }else{
                $this->objArr->$key = $val;
            }
        }
    }

    public function toArray()
    {
        if(is_array($this->objArr)){
            return $this->objArr;
        }else{
            return json_decode($this->__toString(),true);
        }
    }

    public function __toString()
    {
        return json_encode($this);
    }

    function jsonSerialize()
    {
        return $this->objArr;
    }


}