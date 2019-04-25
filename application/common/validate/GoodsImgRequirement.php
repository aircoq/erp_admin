<?php


namespace app\common\validate;


use think\Validate;

class GoodsImgRequirement extends Validate
{
    protected $rule = [
        ['goods_id', 'require|number', '商品id不能为空|商品id为整数'],
        ['is_photo', 'require', '是否拍照不能为空'],
        ['photo_remark', 'require', '拍照要求不能为空'],
        ['undisposed_img_url', 'require', '未处理图片路径不能为空'],
        ['ps_requirement', 'require', '修图要求不能为空'],

    ];
}