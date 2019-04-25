<?php
namespace app\report\model;


use app\common\model\User;
use erp\ErpModel;

class ReportExportFiles extends ErpModel
{
    public function user()
    {
        return $this->hasOne(User::class,'id','applicant_id')->bind([
            'applicant_name' => 'realname'
        ]);
    }

    public function getStatusTextAttr($value,$data)
    {
        switch ($data['status']){
            case 0: return '未生成';
            case 1: return '已生成';
            case 2: return '生成失败';
            default :return '';
        }
    }

}