<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 2018/5/14
 * Time: 16:14
 */

namespace app\publish\filter;


use app\common\filter\BaseFilter;
use app\common\model\Department;
use app\index\service\Department as DepartmentService;
//use app\index\service\MemberShipService;

class EbayOeDepartFilter extends BaseFilter
{
    protected $scope = 'EbayDepart';

    public static function getName(): string
    {
        return '部门 Ebay OE 过滤器';
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
//        $memModel = new MemberShipService();
        $users = [];
        foreach($options as $k => $opt){
            $temp = $dModel->getDepartmentUser($opt);
            $users = array_merge($users,$temp);
        }

//        $accountIds = [];
//        foreach($users as $k => $user){
//            $tempAc = $memModel->getAccountIDByUserId($user);
//            $accountIds = array_merge($accountIds,$tempAc);
//        }
        return $users;
    }

}