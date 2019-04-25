<?php
/**
 * Created by PhpStorm.
 * User: panguofu
 * Date: 2018/10/8
 * Time: 下午5:59
 */

namespace app\publish\validate;
use think\Validate;


class WishExpressValidate extends Validate
{

    protected $rules = [
        ['name','require|unique:wish_express_template|min:6|max:50','模版name必须项|模版name已存在|长度6-50|长度6-50'],
        ['transport_property','require','模版transport_property必须项'],
        ['all_country_shipping','require','模版all_country_shipping必须项'],
        ['from_price','require|float|gt:0','模版from_price必须项|数字类型|大于0'],
        //-------------edit--------------
        ['id','require|number|gt:0','id必须项|必须数字类型|必须大于0'],

        ];


    protected $scene = [
        'add'  => ['name','transport_property','all_country_shipping','from_price','to_price'],
        'edit'  => ['id','name','transport_property','all_country_shipping','from_price','to_price'],
        ];


    public  function  checkData($post=array(),$scene='add')
    {
        $this->rules[]=['to_price','require|float|gt:0|gt:'.$post['from_price'],'模版to_price必须项|数字类型|大于0|必须大于from_price'];
        $this->check($post,$this->rules, $scene);

        if($error = $this->getError())
        {
            return $error;
        }
    }


}