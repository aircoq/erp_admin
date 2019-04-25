<?php

namespace app\index\service;

use app\common\cache\Cache;
use app\common\model\Job;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2017/5/8
 * Time: 10:45
 */
class JobService
{
    const Sales = 'sales';    //销售
    const Customer = 'customer';  //客服
    const Purchase = 'purchase';   //采购
    const Development = 'development';  //开发
    const Finance = 'finance';      //财务
    const Extension = 'extension';    //推广
    const Personnel = 'personnel';    //人事
    const Administration = 'administration';  //行政
    const Designer = 'designer';        //美工
    const Logistics = 'logistics';      //物流
    const IT = 'IT';             // IT
    const President = 'president';      //总裁办
    const Other = 'other';          //其他
    const Warehouse = 'warehouse';      //仓库
    const Virtual = 'virtual';        //虚拟订单


    /**
     * 代码列表
     * @param int $type
     * @param string $code
     * @param string $departmentType
     * @return array
     * @throws \think\Exception
     */
    public function codeList($type = 0, $code = '', $departmentType = '')
    {
        $departmentTypeService = new DepartmentTypeService();
        $jobList = Cache::store('job')->getJob();
        $jobData = [];
        if (!empty($code) && !empty($departmentType)) {
            $merge = [];
            foreach ($jobList as $key => $value) {
                if ($value['code'] == $code) {
                    $scope = json_decode($value['scope'], true);
                    $department = $departmentTypeService->info($departmentType);
                    $job = json_decode($department['job'], true);
                    $merge = array_intersect($scope, $job);
                    break;
                }
            }
            foreach ($merge as $key => $value) {
                if (isset($jobList[$value])) {
                    $temp['id'] = intval($jobList[$value]['id']);
                    $temp['code'] = $jobList[$value]['code'];
                    $temp['remark'] = $jobList[$value]['name'];
                    array_push($jobData, $temp);
                }
            }
        } else {
            foreach ($jobList as $key => $value) {
                if ($value['type'] == $type) {
                    $temp['id'] = intval($value['id']);
                    $temp['code'] = $value['code'];
                    $temp['remark'] = $value['name'];
                    array_push($jobData, $temp);
                }
            }
        }
        return $jobData;
    }

    /**
     * 获取职务职位的名称
     * @param $code
     * @return mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getName($code)
    {
//        $name = '';
//        $jobList = Cache::store('job')->getJob();
//        foreach($jobList as $key =>$value){
//            if($value['code'] == $code){
//                $name = $value['name'];
//                break;
//            }
//        }
//        return $name;
        $jobInfo = (new Job())->where(['code' => $code])->field('name')->find();
        return !empty($jobInfo['name']) ? $jobInfo['name'] : '';
    }

    /**
     * 通过职务名称查找代码
     * @param $name
     * @return string
     * @throws \think\Exception
     */
    public function getCode($name)
    {
        $code = '';
        $jobList = Cache::store('job')->getJob();
        foreach ($jobList as $key => $value) {
            if ($value['name'] == trim($name)) {
                $code = $value['code'];
                break;
            }
        }
        return $code;
    }

    /**
     * 通过职务名称查找代码
     * @param $name
     * @return string
     * @throws \think\Exception
     */
    public function getId($name)
    {
        $id = '';
        $jobList = Cache::store('job')->getJob();
        foreach ($jobList as $key => $value) {
            if ($value['name'] == trim($name)) {
                $id = $value['id'];
                break;
            }
        }
        return $id;
    }

    /**
     * @title 通过职务的code 获取 职务id
     * @param $code
     * @return string
     * @author starzhan <397041849@qq.com>
     */
    public function getIdByCode($code)
    {
        $id = '';
        $jobList = Cache::store('job')->getJob();
        foreach ($jobList as $key => $value) {
            if ($value['code'] == trim($code)) {
                $id = $value['id'];
                break;
            }
        }
        return $id;
    }

    /**
     * @title 根据类型查找
     * @param int $type
     * @return false|\PDOStatement|string|\think\Collection
     * @author starzhan <397041849@qq.com>
     */
    public function selectByType($type=1)
    {
        return (new Job())->where(['type' => $type])->field('id,name')->select();
    }

}