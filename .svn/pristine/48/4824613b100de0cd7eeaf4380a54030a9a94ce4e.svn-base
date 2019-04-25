<?php
/**
 * Created by PhpStorm.
 * User: rondaful_user
 * Date: 2018/11/1
 * Time: 17:20
 */

namespace app\common\model;


use app\common\service\Common;
use think\Model;

class TitleKey extends Model
{
    protected $autoWriteTimestamp = true;
    protected $updateTime = '';

    public function __construct($data = [])
    {
        parent::__construct($data);
        self::event('before_insert',function($model){
            $userInfo = Common::getUserInfo();
            $model->create_id = $userInfo['user_id'] ?? 0;
        });
    }


}