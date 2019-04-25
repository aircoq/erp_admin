<?php
/**
 * Created by PhpStorm.
 * User: starzhan
 * Date: 2017/11/2
 * Time: 9:33
 */

namespace app\common\validate;

use think\Validate;

class GoodsGallery extends Validate
{
    protected $rule = [
        ['channel_id', 'require|in:0,1,2,3,4', '渠道id不能为空！| 渠道id为[0,1,2,3,4]'],
        ['goods_id', 'require', '产品ID不能为空！'],
        ['path', 'require', '路径不能为空'],
        ['original_path', 'require', '原始路径不能为空'],
        ['unique_code', 'require', '图片唯一码（md5加密）不能为空！'],
        ['is_default', 'require|in:1,2,4,8', '图片类型不能为空|图片类型的取值为[1,2,4,8]'],
    ];
    protected $scene = [
        'check'=>['channel_id','is_default']
    ];
}