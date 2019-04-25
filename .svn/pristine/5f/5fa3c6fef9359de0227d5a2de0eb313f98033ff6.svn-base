<?php
namespace app\common\model;

use think\Model;
use app\common\exception\JsonErrorException;

/**
 * Created by tanbin.
 * User: tb
 * Date: 2017/04/01
 * Time: 9:13
 */
class MsgTemplateGroup extends Model
{
    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }
    
    
    

    /** 检测数据是否存在
     * @param int $id
     * @return bool
     */
    public function isHas($id = 0)
    {       
        $result = $this->where(['id' => $id])->find();       
        if(empty($result)){   //不存在
             throw new JsonErrorException('该模板分组不存在');
        }
        return true;
    }
    

}