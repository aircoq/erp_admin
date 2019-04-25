<?php

namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\MsgTemplate as MsgTemplateModel;
use app\common\model\MsgTemplateGroup as MsgTemplateGroupModel;
use think\Db;
/**
 * Created by tanbin.
 * User: 1
 * Date: 2017/03/31 
 * Time: 17:45
 */
class MsgTemplate extends Cache
{
    /** 获取消息模板列表
     * @param string $str  [ $channel_id-$template_type ] 组合文本
     * @return array
     */
    public function getMsgTemplate($str ='')
    {
        if($this->redis->exists('cache:msgTemplate')){
            $result = json_decode($this->redis->get('cache:msgTemplate'),true);
            return !empty($result)?$result:[];
        }
        //查表
        $msgTemplateModel = new MsgTemplateModel();
        $result = $msgTemplateModel->order('use_count desc , id desc')->select();//优先使用次数排序

        $this->redis->set('cache:msgTemplate',json_encode($result));
        if($str)    return isset($result[$str])?$result[$str]:[];
        else        return json_decode(json_encode($result),true);
    }
    
    
    
    
    /** 获取常用的消息模板列表（常用的需要经常更新，所以不用 getMsgTemplate，默认一天更新一次）
     * @param string $str  [ $channel_id-$template_type ] 组合文本
     * @return array
     */
    public function getFrequentlyUsedTpl($str ='')
    {
        if($this->redis->exists('cache:frequentlyUsedMsgTpl')){
            $result = json_decode($this->redis->get('cache:frequentlyUsedMsgTpl'),true);
            if($str)    return isset($result[$str])?$result[$str]:[];
            else        return $result;
        }
        //查表
        $msgTemplateModel = new MsgTemplateModel();
        $res = $msgTemplateModel->order('use_count desc , id desc')->select();//优先使用次数排序
        $result = [];
        foreach ($res as $key=>$vo){
            $k =   $vo['channel_id'].'-'.$vo['template_type'];
            $result[$k][] = $vo;
        }        
        $this->redis->set('cache:useCountTplLastTime',json_encode(time()));
        $this->redis->set('cache:frequentlyUsedMsgTpl',json_encode($result));
        if($str)    return isset($result[$str])?$result[$str]:[];
        else        return $result;
    }
   
    
    /**
     * 获取最后统计常用模板次数的时间
     * @return mixed
     */
    function useCountLastTime(){
        $result = [];
        if($this->redis->exists('cache:useCountTplLastTime')){
            $result = json_decode($this->redis->get('cache:useCountTplLastTime'),true);
        }
        return $result;
    }

    /**
     * 获取消息模板分组列表
     * @param int $templateType
     * @return mixed|unknown
     */
    public function getMsgTemplateGroup($templateType='')
    {
        if($this->redis->exists('cache:msgTemplateGroup')){
            $result = json_decode($this->redis->get('cache:msgTemplateGroup'),true);
            return !empty($result)?$result:[];
            
        }
        //查表       
        $result = MsgTemplateGroupModel::field('id,group_name,template_type,channel_id')->select();

        $this->redis->set('cache:msgTemplateGroup',json_encode($result));
        return json_decode(json_encode($result),true);
    }
    
    
    /**
     * 获取模板字段列表
     * @param string $str  [ $channel_id-$template_type ] 组合文本
     */
    public function getMsgTplField()
    {
        //test by tb  暂时关闭缓存
//         if($this->redis->exists('cache:msgTplField')){
//             $result = json_decode($this->redis->get('cache:msgTplField'),true);
//             return !empty($result)?$result:[];
    
//         }
        //查表
        $result = Db::table('msg_template_field')->field('id,channel_id,template_type,field_key,field_value,field_db')->select();
        
        $this->redis->set('cache:msgTplField',json_encode($result));
        return json_decode(json_encode($result),true);
    }
    
    
    /**
     * 获取模板字段列表
     * @param string $str  [ $channel_id-$template_type ] 组合文本
     */
    public function getMsgTplField2222($str ='')
    {
        if($this->redis->exists('cache:msgTplField')){
            $result = json_decode($this->redis->get('cache:msgTplField'),true);
            if($str)   return isset($result[$str])?$result[$str]:[];
            else       return $result;
    
        }
        //查表
        $res = Db::table('msg_template_field')->field('id,channel_id,template_type,field_key,field_value,field_db')->select();
    
        $result = [];
        foreach ($res as $key=>$vo){
            $k =   $vo['channel_id'].'-'.$vo['template_type'];
            $data = [];
            $data['field_key'] = $vo['field_key'];
            $data['field_val'] = $vo['field_value'];
            $data['field_db'] = $vo['field_db'];
            $result[$k][] = $data;
        }
         
        $this->redis->set('cache:msgTplField',json_encode($result));
        if($str)    return isset($result[$str])?$result[$str]:[];
        else        return $result;
    }
    
}


