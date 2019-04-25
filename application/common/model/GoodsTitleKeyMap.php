<?php
/**
 * Created by PhpStorm.
 * User: rondaful_user
 * Date: 2018/11/1
 * Time: 17:36
 */

namespace app\common\model;


use app\common\service\Common;
use think\Model;

class GoodsTitleKeyMap extends Model
{
    protected $autoWriteTimestamp = true;

    public function __construct($data = [])
    {
        parent::__construct($data);

        self::event('before_insert',function($m) {
            $userInfo = Common::getUserInfo();
            $m->create_id = $userInfo['user_id'] ?? 0;
        });
        self::event('before_update',function($m) {
            $userInfo = Common::getUserInfo();
            $m->update_id = $userInfo['user_id'] ?? 0;
        });
    }

}