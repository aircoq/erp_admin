<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-4-19
 * Time: 下午2:09
 */

namespace app\common\model\pandao;


use think\Model;

class PandaoVariant extends Model
{
    protected $autoWriteTimestamp=true;
    protected function initialize(){
        parent::initialize();
    }
    public function getPidAttr($v){
        return (string)$v;
    }
    public function setUpdatedAtAttr($v){
        if(is_string($v)){
            return strtotime($v);
        }else{
            return $v;
        }
    }
    public function setEnabledAttr($value)
    {
        $value = strtolower($value);
        if($value=="true" || $value == 'enabled')
        {
            $value=1;
        }elseif($value=="false" || $value = 'disabled'){
            $value=0;
        }

        return $value;
    }
    public function product()
    {
        return $this->hasOne(PandaoProduct::class,'id','pid');
    }
}