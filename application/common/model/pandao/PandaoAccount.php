<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-4-19
 * Time: 下午2:10
 */

namespace app\common\model\pandao;


use think\Model;

class PandaoAccount extends Model
{
    protected $autoWriteTimestamp=true;
    protected function initialize(){
        parent::initialize();
    }
}