<?php
namespace app\common\model\aliexpress;

use think\Model;

/**
 * Created by ZendStudio.
 * User: Hot-Zr
 * Date: 2017年3月29日 
 * Time: 16:36:45
 */
class AliexpressFreightTemplate extends Model
{
    /**
     * 初始化数据
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }

    public static function getTemp($accountId,$tempId)
    {
        return self::where(['template_id'=>$tempId,'account_id'=>$accountId])->find();
    }

}