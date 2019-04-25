<?php


namespace app\index\validate;


use think\Validate;

class Postoffice extends Validate
{
    protected $rule = [
        'post' => 'require',
        'imap_url' => 'require',
        'imap_port' => 'require',
        'smtp_url' => 'require',
        'smtp_port' => 'require',
        'status' => 'require',
        'creator_id' => 'require',
        'create_time' => 'require',
        'updater_id' => 'require',
        'update_time' => 'require'
    ];
    protected $message = [
        'post.require' => '邮局必填',
        'post.unique' => '邮局已存在',
        'post.checkUrl' => '邮局格式不正确',
        'imap_url.require' => '收件服务器地址必填',
        'imap_url.checkUrl' => '收件服务器地址格式不正确',
        'imap_port.require' => '收件服务器端口必填',
        'imap_port.number' => '收件服务器端口仅为数字',
        'smtp_url.require' => '发件服务器地址必填',
        'smtp_url.checkUrl' => '发件服务器地址格式不正确',
        'smtp_port.require' => '收件服务器端口必填',
        'smtp_port.number' => '收件服务器端仅为数字',
        'status.require' => '状态必填',
        'status.in' => '状态的值仅为0、1'

    ];
    protected $scene = [
        'update' => [
            'post'=>'checkUrl|unique:Postoffice,post',
            'imap_url'=>'checkUrl',
            'imap_port'=>'number',
            'smtp_url'=>'checkUrl',
            'smtp_port'=>'number',
            'status'=>'in:1,0',
            'updater_id'=>'require|number',
            'update_time'=>'require|number'
        ],
        'insert' => [
            'post'=>'require|checkUrl|unique:Postoffice,post',
            'imap_url'=>'checkUrl',
            'imap_port'=>'number',
            'smtp_url'=>'checkUrl',
            'smtp_port'=>'number',
            'status'=>'in:1,0',
            'creator_id'=>'require|number',
            'create_time'=>'require|number'
        ]
    ];


    public function checkUrl($value, $rule)
    {
        $int= preg_match('/^[A-Za-z0-9_\.\-]+$/', $value);
        if($int){
            return true;
        }else{
            return false;
        }
    }

}