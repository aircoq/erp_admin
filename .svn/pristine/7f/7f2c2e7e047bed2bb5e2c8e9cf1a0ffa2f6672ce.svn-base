<?php


namespace app\common\validate;

use \think\Validate;

class GoodsImgReuirement extends Validate
{
    protected $rule = [

    ];
    protected $message = [
        'goods_id.require'=>'goodsId不能为空',
        'goods_id.number'=>'goodsId为整数',
        'is_photo.in'=>'是否拍照的值须为“是”和“否”',
        'photo_remark.require'=>'拍照要求不能为空',
        'undisposed_img_url.require'=>'未处理图片路径不能为空',
        'ps_requirement.require'=>'修图要求不能为空',
        'create_time.require'=>'创建时间不能为空',
        'update_time.require'=>'更新时间不能为空',
    ];
    protected $scene = [
        'insert'=>[
            'goods_id'=>'require|number',
            'create_time'=>'require',
            'create_id'=>'require',
        ],
        'update'=>[
            'update_time'=>'require',
            'is_photo'=>'in:0,1',
        ]
    ];
}