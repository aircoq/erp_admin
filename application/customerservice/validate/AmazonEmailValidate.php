<?php
namespace app\customerservice\validate;

use app\common\validate\Base;

class AmazonEmailValidate extends Base
{
    protected $rule = [
        'account_id'          =>  'require|integer',
        'order_number_type'   => 'require|in:system,channel',
        'order_number'        => 'require|string',
        'reply_email_id'      => 'require|integer',
        'customer_id'         => 'require|integer',
        'send_by_account_id'  => 'integer',
        'buyer_name'          => 'require|string',
        'buyer_email'          => 'require|email',
        'subject'             => 'require|string',
        'content'             => 'require|string',
        'email_account'       => 'require|email',
        'email_password'      => 'require|string',
        'imap_url'            => 'require|string',
        'imap_ssl_port'       => 'require|integer',
        'smtp_url'            => 'require|string',
        'smtp_ssl_port'       => 'require|integer',
        'is_enabled'          => 'require|in:0,1',
        'allowed_receive'       => 'require|in:0,1',
        'allowed_send'          => 'require|in:0,1',

    ];

    protected $message = [
        'send_by_account_id.integer' => '平台账号id必须是一个整数',
        'subject.require'            => '邮件主题未设置',
        'subject.string'             => '邮件主题必须是一个字符串',
        'content.require'            => '邮件正文未设置',
        'content.string'             => '邮件正文必须是字符串',
        'order_number_type.require'  => '单号类型未设置',
        'order_number_type.in'       => '单号类型不是有效值',
        'order_number.require'       => '单号未设置',
        'order_number.string'        => '单号数据类型错误',
        'reply_email_id.require'     => '回复邮件id未设置',
        'reply_email_id.integer'      => '回复邮件id应该是一个整数',
        'customer_id.require'      => '客服id未设置',
        'customer_id.integer'      => '客服id应该是一个整数',
        'account_id.require'       => '平台账号id未设置',
        'account_id.integer'       => '平台账号id应该是一个整数',
        'buyer_name.require'       => '买家姓名未设置',
        'buyer_name.string'        => '买家姓名应该是一个字符串',
        'buyer_email.require'      => '买家邮箱未设置',
        'buyer_email.email'        => '买家邮箱不合法',
        'email_account.require'    => '平台账号邮箱未设置',
        'email_account.email'      => '平台账号邮箱不合法',
        'email_password.require'   => '邮箱密码未设置',
        'email_password.string'    => '邮箱密码不合法',
        'imap_url.require'         => '邮箱imap地址未设置',
        'imap_url.string'          => '邮箱imap地址不合法',
        'imap_ssl_port.require'    => '邮箱imap ssl端口未设置',
        'imap_ssl_port.integer'    => '邮箱imap ssl端口不合法',
        'smtp_url.require'         => '邮箱smtp地址未设置',
        'smtp_url.string'          => '邮箱smtp地址不合法',
        'smtp_ssl_port.require'    => '邮箱smtp ssl端口未设置',
        'smtp_ssl_port.integer'    => '邮箱smtp ssl端口不合法',
        'is_enabled.require'       => '是否启用未设置',
        'is_enabled.in'       => '是否启用设置值不合法',
        'allowed_receive.require'    => '是否允许收件未设置',
        'allowed_receive.in'    => '是否允许收件设置值不合法',
        'allowed_send.require'       => '是否允许发件未设置',
        'allowed_send.in'       => '是否允许发件设置值不合法',


    ];

    protected $scene = [
        'send_by_order'      => [
                                'order_number_type',
                                'order_number',
//                                'customer_id',
                                'subject',
                                'content'],
        'send_without_order' => [
                                'account_id',
//                                'customer_id',
                                'buyer_name',
                                'buyer_email',
                                'subject',
                                'content'],
        'reply'              => [
                                'reply_email_id',
                                'content'],
        'add_email_account'  => [
                                'account_id',
                                'email_account',
                                'email_password',
                                'imap_url',
                                'imap_ssl_port',
                                'smtp_url',
                                'smtp_ssl_port',
                                'is_enabled',
                                'allowed_receive',
                                'allowed_send',]
    ];

    /**
     * 检查是否是字符串
     * @param $value
     * @return bool
     */
    protected function string($value){
        return is_string($value);
    }
}