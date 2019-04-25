<?php
namespace app\common\model;

use think\Model;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2016/10/28
 * Time: 9:13
 */
class CategoryQcItem extends Model
{
    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }

    /** 检测产品是否存在
     * @param int $id
     * @return bool
     */
    public function isHas($id = 0)
    {
        $result = $this->where(['id' => $id])->find();
        if(empty($result)){   //不存在
            return false;
        }
        return true;
    }
    
    public function getCateQcItemList($page=1,$pageSize=10){
        $list=$this->alias('item')->join('qc_item_value qciv','item.id = qciv.qc_item_id')->field('item.*,qciv.sort as val_sort,qciv.create_time as val_create_time,content')->page($page, $pageSize)->select();
        foreach($list as $k=>$v){
            $list[$k]['create_time']=date('Y-m-d H:i',$v['create_time']);
            $list[$k]['update_time']=date('Y-m-d H:i',$v['update_time']);
        }
        $count=$this->alias('item')->join('qc_item_value qciv','item.id = qciv.qc_item_id')->page($page, $pageSize)->count();
        return ['list'=>$list,'count'=>$count];
    }
    public function getCateQcItemInfo($id){
        $info=$this->alias('item')->join('qc_item_value qciv','item.id = qciv.qc_item_id')->where('item.id='.$id)->field('item.*,qciv.sort as val_sort,qciv.create_time as val_create_time,content')->find();
        return $info;
    }    
    
}