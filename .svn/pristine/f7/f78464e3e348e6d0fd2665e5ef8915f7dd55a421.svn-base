<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-6-1
 * Time: 下午3:30
 */

namespace app\publish\validate;


use think\Validate;

class ShopeePublishValidate extends Validate
{
    protected $rule=[
        ['id','require|number|gt:0','id必填,且大于等于0'],
        ['goods_id','require|number','商品id必填,且为大于0的数字'],
        ['item_id','require','商品id必须'],
        ['variant_id','require','sku id必填'],
        ['spu','require','SPU必填'],
        ['price','require|number|gt:0','商品售价必填,且为大于0的数字'],
        ['days_to_ship','require','发货时间必填'],
        ['stock','require|number|gt:0|elt:500000','商品库存inventory必填,且大于0小于等于500000'],
        ['vars','require','vars必填'],
        ['account_id','require|number|gt:0','刊登账号id必填,且为大于0的数字'],
        ['name','require','刊登标题必填'],
        ['images','require|array','商品相册不能为空'],
        ['description','require','商品描述详情description必填'],
        ['variant_sku','require','sku必须'],
        ['price','require','售价必须'],
        ['category_id','require','刊登分类必填'],
        ['attributes','require','分类属性必填'],
        ['sku','require','sku必填'],
    ];
    protected $scene=[
        'create'=>['goods_id','spu','vars'],
        'update'=>['id','vars'],
        'var'=>['category_id','account_id','item_sku','description'],
        'variant'=>['name','price','stock'],
        'single'=>['category_id','account_id','account_code','item_sku','description','images','category_id','attributes','name'],
    ];

    public function checkData($params,$scene){
        $this->check($params,$this->rule,$scene);
        if($this->error){
            return $this->error;
        }
    }
    /**
     * @node 验证刊登数据中的var变量数据合法性
     * @access public
     * @param array $post
     * @param string $scene
     * @return string
     */
    public function  checkVars($post = array(),$scene='var')
    {
        foreach ($post as $key => $var)
        {

            if(is_array($var))
            {
                $error = $this->checkVar($var);
            }

            if($error && isset($post[$key]['account_code']))
            {
                return '账号['.$post[$key]['account_code'].']'.$error;
            }else{
                return $error;
            }
        }
    }
    /**
     * 校验每个账号的数据
     * @param type $var
     * @return string
     */
    protected  function checkVar($var)
    {

        if(isset($var['variant']) && $var['variant'])
        {
            $error = $this->checkVariant($var['variant'], 'variant');
            if($error)
            {
                return $error;
            }
        }else{ //没有sku
            $this->check($var,$this->rule,'single');
        }

        if(isset($var['images']) && empty($var['images']))
        {
            $error='商品相册不能为空,且不能超过20张';
            return $error;
        }

        if(isset($var['images']) && count($var['images'])>9)
        {
            $error='商品相册不能超过9张';
            return $error;
        }

        $error = $this->getError();

        if($error)
        {
            return $error;
        }else{
            return '';
        }
    }


    /**
     * 校验sku信息
     * @param type $post
     * @param type $scene
     * @return string
     */
    protected  function checkVariant($post,$scene='variant')
    {

        foreach ($post as $key => $var)
        {
            //如果不是单个sku的
            if(count($post)>1)
            {
                if($var['name']=='')
                {
                    return '['.$var['sku'].']name不能为空';
                }
            }

            $this->check($var,$this->rule,$scene);
            if($error = $this->getError())
            {
                return '['.$var['sku'].']'.$error;
            }
        }
    }
}