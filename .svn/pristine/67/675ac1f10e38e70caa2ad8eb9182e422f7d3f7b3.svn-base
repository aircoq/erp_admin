<?php
namespace app\common\model;

use think\Model;
use app\common\exception\JsonErrorException;

/**
 * Created by tanbin.
 * User: XPDN
 * Date: 2017/03/31
 * Time: 9:13
 */
class MsgTemplate extends Model
{
    
    // 模板类型
    const TEM_TYPE = [
       1 => '回复模板',
       2 => '评价模板'
    ];
    

    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }
    
    
    public function group()
    {
        return $this->hasOne('MsgTemplateGroup','id', 'template_group_id')->field('id,group_name');
    }

    /**
     * 查找数据
     * @param number $id
     * @throws JsonErrorException
     */
    public function  find($id = 0)
    {
        $result = $this->where(['id' => $id])->find();
        if(empty($result)){   //不存在
            throw new JsonErrorException('该模板不存在');
        }
        return $result;
    }
    
    /** 检测数据是否存在
     * @param int $id
     * @return bool
     */
    public function isHas($id = 0)
    {       
        $result = $this->where(['id' => $id])->find();
        if(empty($result)){   //不存在
             throw new JsonErrorException('该模板不存在');
        }
        return true;
    }
    
    
    /** 检测分组是否被使用
     * @param int $id 分组id
     * @return bool
     */
    public function isHasGroup($id = 0)
    {
        $result = $this->where(['template_group_id' => $id])->find();
       
        if($result){   //不存在
            throw new JsonErrorException('该模板分组已经被使用');
        }
        return true;
    }
}