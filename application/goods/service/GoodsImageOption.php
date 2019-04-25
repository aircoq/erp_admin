<?php
namespace app\goods\service;

use think\Exception;
use think\Db;
use app\common\model\Goods;
use app\common\cache\Cache;
use app\common\model\GoodsGallery;
use app\goods\service\GoodsHelp;
use think\File;
use think\Image;
use app\common\model\GoodsSku;
use think\Config;

/**
 * Class GoodsImageOption
 * @package app\goods\service
 */
class GoodsImageOption
{   
    
    //修图要求
    private $image_requirements = [
        //主选项
        'main_options' => [
            'name' => '主选项',
            'options'=> [
                ['name'=>'1001 * 1001',     'value'=>'option_1001_1001',    'checked'=>false],
                ['name'=>'800 * 800',       'value'=>'option_800_800',      'checked'=>false],
                ['name'=>'450 * 450',       'value'=>'option_450_450',      'checked'=>false],
                ['name'=>'1000 *  1300',    'value'=>'option_1001_1300',    'checked'=>false],
                ['name'=>'图片是否需要翻译',     'value'=>'option_translate',    'checked'=>false],
            ]
        ],
        //产品主图
        'main_img'=>[
            'name'=>'产品主图',
            'options'=>[
                ['name'=>'去除logo',                   'value'=>'main_erase_logo',    'checked'=>false],
                ['name'=>'多属性产品组合图（白底）',         'value'=>'main_group',         'checked'=>false],
                ['name'=>'拼图（多元化、角度、细节、场景）',    'value'=>'main_jigsaw',        'checked'=>false],
                ['name'=>'3D镂空图',                   'value'=>'main_3D',             'checked'=>false],
                ['name'=>'产品正面图',                   'value'=>'main_front',         'checked'=>false],
                ['name'=>'logo左上角',                 'value'=>'main_upleft',         'checked'=>false],
                ['name'=>'每个颜色一张展示颜色图',          'value'=>'main_color',          'checked'=>false],
            ]
        ],
        //角度图
        'angle'=>[
            'name'=>'角度图',
            'options'=>[
                ['name'=>'产品正面图',           'value'=>'angle_front',     'checked'=>false],
                ['name'=>'产品背面图',           'value'=>'angle_back',      'checked'=>false],
                ['name'=>'产品侧面图',           'value'=>'angle_side',      'checked'=>false],
                ['name'=>'产品俯视图',           'value'=>'angle_overlook',  'checked'=>false],
                ['name'=>'45度、90度角度图',      'value'=>'angle_45_90',     'checked'=>false],
                ['name'=>'产品底部图',           'value'=>'angle_bottom',     'checked'=>false],
            ]
    
        ],
        //功能效果图
        'fun_effect'=>[
            'name'=>'功能效果图',
            'options'=>[
                ['name'=>'场景效果展示图（单张）',     'value'=>'effect_01',       'checked'=>false],
                ['name'=>'场景效果展示图（拼图）',     'value'=>'effect_02',       'checked'=>false],
                ['name'=>'功能文案说明图',           'value'=>'effect_03',       'checked'=>false],
                ['name'=>'使用效果图',              'value'=>'effect_04',      'checked'=>false],
                ['name'=>'穿戴效果图',              'value'=>'effect_05',      'checked'=>false],
                ['name'=>'模特图（不能模糊处理）',      'value'=>'effect_06',      'checked'=>false],
                ['name'=>'模特图（站姿角度）',         'value'=>'effect_07',      'checked'=>false],
            ]
    
        ],
        //细节图
        'img_detail'=>[
            'name'=>'细节图',
            'options'=>[
                ['name'=>'功能部位局部放大图',           'value'=>'detail_01',       'checked'=>false],
                ['name'=>'文案描述细节图',             'value'=>'detail_02',       'checked'=>false],
                ['name'=>'产品内部展示图',             'value'=>'detail_03',       'checked'=>false],
                ['name'=>'拼图多款式对应描述图',         'value'=>'detail_04',      'checked'=>false],
                ['name'=>'产品包装展示图',             'value'=>'detail_05',      'checked'=>false],
                ['name'=>'产品配件图',                'value'=>'detail_06',      'checked'=>false],
            ]
    
        ],
        //尺寸图
        'img_size'=>[
            'name'=>'尺寸图',
            'options'=>[
                ['name'=>'整体尺寸图',               'value'=>'size_01',       'checked'=>false],
                ['name'=>'细节尺寸图',               'value'=>'size_02',       'checked'=>false],
                ['name'=>'产品配件尺寸图',             'value'=>'size_03',       'checked'=>false],
                ['name'=>'服装产品-尺码图',            'value'=>'size_04',      'checked'=>false],
                ['name'=>'产品容量对比图',             'value'=>'size_05',      'checked'=>false],
            ]
    
        ],
    ];
   
    
    /**
     * 获取修图属性列表
     */
    public function getImgRequirements(){
        return $this->image_requirements;
    }
    
}




