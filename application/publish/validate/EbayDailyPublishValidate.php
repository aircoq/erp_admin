<?php
/**
 * Created by PhpStorm.
 * User: rondaful_user
 * Date: 2019/3/22
 * Time: 17:06
 */

namespace app\publish\validate;


use think\Validate;

class EbayDailyPublishValidate extends Validate
{
    protected $listRule = [
        ['categoryId|分类id','gt:0'],
        ['status|任务状态','in:0,1,2,3'],
        ['startDate|起始时间','date'],
        ['endDate|结束时间','date'],
        ['sellerId|销售员id','gt:0'],
        ['departmentId|部门id','gt:0'],
        ['page|页码','gt:0'],
        ['pageSize|每页条目数','gt:0'],
    ];
    protected $ssRule = [
        ['id|每日刊登表id','require|number|gt:0'],
        ['seller_id|销售员id','require|number|gt:0'],
    ];

    public function myCheck($data,$rule,$repeat=false)
    {
        $rule .= 'Rule';
        if ($repeat) {
            $flag = true;
            foreach ($data as $dt) {
                $flag = $this->check($dt,$this->$rule) && $flag;
            }
            return $flag;
        } else {
            return $this->check($data, $this->$rule);
        }
    }



}