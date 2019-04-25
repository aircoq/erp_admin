<?php

namespace app\index\filter;

use app\common\model\User;
use app\common\model\Department;
use app\common\filter\BaseFilter;
use app\index\service\Department as DepartmentService;
use app\index\service\MemberShipService;

/** ebayAccountHealth 过滤器
 * User: wlw2533
 * Date: 2019/1/2
 */

class DepartFilter extends BaseFilter
{
    protected $scope = 'Depart';

    public static function getName(): string
    {
        return '部门过滤器';
    }

    public static function config(): array
    {
        $model = new Department();
        $options = $model->field('id as value, name as label')->select();
        $departs = [];
        if($options)
        {
            foreach ($options as $k =>$option)
            {
                #$option['label']=(new DepartmentService)->getDepartmentNames($option['value']);
                $departs[$k]['value'] = $option['value'];
                $departs[$k]['label'] = (new DepartmentService)->getDepartmentNames($option['value']);
            }
        }
        
        return [
            'key' => 'type',
            'type' => static::TYPE_SELECT,
            'options' => $departs
        ];
        
    }

    public function generate()
    {
        $options = $this->getConfig();
        $dModel = new DepartmentService();
        $memModel = new MemberShipService();
        $users = [];
        foreach($options as $k => $opt){
            $temp = $dModel->getDepartmentUser($opt);
            $users = array_merge($users,$temp);
        }

        $accountIds = [];
        $users = array_unique($users);
        $jobs = User::whereIn('id',$users)->column('job','id');
        foreach($users as $k => $user){
            if (!isset($jobs[$user]) || $jobs[$user]!='sales') {
                continue;
            }
            $tempAc = $memModel->getAccountIDByUserId($user);
            $accountIds = array_merge($accountIds,$tempAc);
        }
        return array_values(array_unique($accountIds));
    }
}