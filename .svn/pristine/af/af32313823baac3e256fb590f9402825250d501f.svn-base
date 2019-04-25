<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use think\Loader;
/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/11/5
 * Time: 11:44
 */
class Attribute extends Cache
{
    /** 获取属性信息
     * @param int $attribute_id
     * @return array|mixed
     */
    public function getAttribute($attribute_id = 0)
    {
        if($this->redis->exists('cache:attribute')){
            $result = json_decode($this->redis->get('cache:attribute'),true);
            if(!empty($attribute_id)){
                return isset($result[$attribute_id]) ? $result[$attribute_id] : [];
            }
            return $result;
        }
        //查表
        $attribute = Loader::model('Attribute')->order('sort asc')->select();
        $attribute_value = Loader::model('AttributeValue')->order('sort asc')->select();
        $new_array = [];
        foreach($attribute as $k => $v){
            $new_array[$v['id']] = $v;
            $temp = [];
            foreach($attribute_value as $key => $value){
                if($v['id'] == $value['attribute_id']){
                    $temp[$value['id']] = $value;
                }
            }
            $new_array[$v['id']]['value'] = $temp;
        }
        $this->redis->set('cache:attribute',json_encode($new_array));
        $result = json_decode($this->redis->get('cache:attribute'),true);
        if(!empty($attribute_id)){
            return isset($result[$attribute_id]) ? $result[$attribute_id] : [];
        }
        return $result;
    }
    
    
    /** 获取属性信息 - 导数据专用
     * @param int $attribute_id
     * @return array|mixed
     */
    public function getAttributeWms($attribute_id = 0)
    {
        if($this->redis->exists('cache:attributeWms')){
            $result = json_decode($this->redis->get('cache:attributeWms'),true);
            if(!empty($attribute_id)){
                return isset($result[$attribute_id]) ? $result[$attribute_id] : [];
            }
            return $result;
        }
        //查表
        $attribute = Loader::model('Attribute')->order('sort asc')->select();
        $attribute_value = Loader::model('AttributeValue')->order('sort asc')->select();
        $new_array = [];
        $skuSize = [];
        foreach($attribute as $k => $v){
            $new_array[$v['id']] = $v;
            $temp = [];
            foreach($attribute_value as $key => $value){
                if($v['id'] == $value['attribute_id']){
                    $temp[$value['id']] = $value;
                }    
            }
            $new_array[$v['id']]['value'] = $temp;
           
        }
        
        foreach ($new_array as $k => $i){
            if(in_array($k, [2,3,4,5,6,7,8,9,10,12,13,14]) ){
                foreach ($i['value'] as $k2 => $j){
                    $skuSize[$k2] = $j;
                }
                
                 
            }
        }
        $new_array[100]['value'] = $skuSize;
        $this->redis->set('cache:attributeWms',json_encode($new_array));
        $result = json_decode($this->redis->get('cache:attributeWms'),true);
        if(!empty($attribute_id)){
            return isset($result[$attribute_id]) ? $result[$attribute_id] : [];
        }
        return $result;
    }
    
}