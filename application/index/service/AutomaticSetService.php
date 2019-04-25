<?php

namespace app\index\service;

use app\common\model\AutomaticSet;


/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2017/5/25
 * Time: 11:17
 */
class AutomaticSetService
{
    protected $automaticSetModel;
    protected $error = '';

    public function __construct()
    {
        if (is_null($this->automaticSetModel)) {
            $this->automaticSetModel = new AutomaticSet();
        }
    }

    public function getError()
    {
        return $this->error;
    }

    /**
     * 获得可用账号列表
     * @param $type  //类型 1.为图片提取文字
     * @return array
     */
    public static function getAccount($type =1){
        $account = (new AutomaticSet())->where('cs','>',0)->where('type','=',$type)->order('cs desc')->find();
        if($account){
            $account = $account->toArray();
            self::delCS($account['id']);
        }
        return $account;
    }

    /**
     * 减一操作
     * @param $id
     * @return array
     */
    public static function delCS($id){
       if($id > 0){
           return (new AutomaticSet())->save(['cs'=>['exp','cs-1']],['id'=>$id]);
       }
       return false;
    }

    /**
     * 重置每天的次数
     * @return array
     */
    public static function setAllAccountInit(){
        return (new AutomaticSet())->save(['cs'=>['exp','everyday_cs']],'1=1');
    }

    /**
     * 保存Auth缓存操作
     * @param $app_key
     * @param $authobj
     * @return array
     */
    public static function setAuthObj($app_key,$authobj){
        return (new AutomaticSet())->save(['authobj'=>$authobj],['app_key'=>$app_key]);
    }

    /**
     * 读取Auth缓存操作
     * @param $app_key
     * @return array
     */
    public static function getAuthObj($app_key){
        return (new AutomaticSet())->where(['app_key'=>$app_key])->value('authobj');
    }


}