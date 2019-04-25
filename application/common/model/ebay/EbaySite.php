<?php
namespace app\common\model\ebay;

use think\Model;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2017/4/10
 * Time: 17:49
 */
class EbaySite extends Model
{
    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }

    /**
     * 修改
     * @param  array $data [description]
     * @return [type]       [description]
     */
    public function edit(array $data, array $where)
    {
        return $this->allowField(true)->save($data, $where);
    }

    #按照时间排序获取一个站点信息->用于获取ebay商户类目
    public function getSiteOne(){
    	$rows=$this->order('up_date')->find();
        $this->edit(array('up_date'=>time()),array('sid'=>$rows['sid']));
        return $rows;
    }
}