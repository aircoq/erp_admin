<?php


namespace app\common\model;


use think\Model;

//侵权记录邮件上传附件表 模型
class TortEmailAttachment extends Model
{
    protected $table = 'tort_email_attachment';

    protected $autoWriteTimestamp = true;

    protected $createTime = 'create_time';

    protected $updateTime = null;
}