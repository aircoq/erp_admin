<?php
namespace app\index\service;

use app\common\model\RoleUser;
use app\common\model\ServerVisitLog;
use app\common\model\User;

/**
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/8/7
 * Time: 19:03
 */
class ServerLog
{
    protected $serverVisitLogModel;

    public function __construct()
    {
        if (is_null($this->serverVisitLogModel)) {
            $this->serverVisitLogModel = new ServerVisitLog();
        }
    }

    /** 日志列表
     * @param array $where
     * @param $page
     * @param $pageSize
     * @return array
     */
    public function logList(array $where, $page, $pageSize)
    {
        $field = 's.id,s.visit_user_id,s.visit_channel_id,s.visit_account_code,s.visit_time,s.visit_server_name,u.username,u.realname,ss.ip';
        $count = $this->serverVisitLogModel->alias('s')->field($field)->join('user u','s.visit_user_id = u.id','left')->join('server ss','ss.name = s.visit_server_name','left')->where($where)->count();
        $logList = $this->serverVisitLogModel->alias('s')->field($field)->join('user u','s.visit_user_id = u.id','left')->join('server ss','ss.name = s.visit_server_name','left')->where($where)->page($page, $pageSize)->order('visit_time desc')->select();
        $roleUserModel = new RoleUser();
        $logData = [];
        foreach($logList as $key => $value){
            $temp = $value;
            $roleUserList = $roleUserModel->alias('u')->field('u.user_id,u.role_id,r.name')->where(['user_id' => $value['visit_user_id']])->join('role r','r.id = u.role_id')->select();
            if(!empty($roleUserList)){
                $roleName = '';
                foreach($roleUserList as $r => $role){
                    $roleName .= $role['name'].',';
                }
                $roleName = rtrim($roleName,',');
                $temp['role'] = $roleName;
            }else{
                $temp['role'] = '';
            }
            array_push($logData,$temp);
        }
        $result = [
            'data' => $logData,
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
        ];
        return $result;
    }

    /** 新增访问日志
     * @param array $data
     */
    public function add(array $data)
    {
        if (!empty($data)) {
            $data['visit_user_id'] = $data['visit_user_id'] ??  0;
            $data['visit_time'] = time();
            $this->serverVisitLogModel->allowField(true)->isUpdate(false)->save($data);
        }
    }
}