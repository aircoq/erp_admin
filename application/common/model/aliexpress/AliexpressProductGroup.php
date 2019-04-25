<?php
namespace app\common\model\aliexpress;

use think\Model;

/**
 * Created by ZendStudio.
 * User: Hot-Zr
 * Date: 2017年3月29日 
 * Time: 16:36:45
 */
class AliexpressProductGroup extends Model
{
    private $strSqlData;
    /**
     * 初始化数据
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }


    public static function getNameByGroupId($accountId,$groupId)
    {
        if(empty($groupId)){
            return '';
        }
        static $group_names = [];
        if(is_array($groupId)){
            $key = md5($accountId.implode(',',$groupId));
        }else{
            $key = md5($accountId.$groupId);
        }

        if(isset($group_names[$key])){
            return $group_names[$key];
        }
        $where['account_id'] = $accountId;
        if(is_array($groupId)){
            $where['group_id'] = ['in',$groupId];
        }else{
            $where['group_id'] = $groupId;
        }
        $result = self::where($where)->field('group_name')->select();

        if(empty($result)){
            return '';
        }
        $result = collection($result)->toArray();
        $group_name = implode(',',array_column($result,'group_name'));
        $group_names[$key] = $group_name;
        return $group_name;
    }

    public function getGroupTree($intAccountId)
    {
        $arrData = self::where('account_id',$intAccountId)->select();
        return self::getTree($arrData);
    }
    
    public function getTree($a,$pid=0)
    {  
        $tree = array();                              
        foreach($a as $v)
        {  
            if($v['group_pid'] == $pid)
            {                    
                $v['children'] = $this->getTree($a,$v['group_id']); 
                if($v['children'] == null)
                {  
                    unset($v['children']);           
                }  
                $tree[] = $v;                         
            }  
        }  
        return $tree;                                  
    } 
}