<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-1-7
 * Time: 上午9:09
 */

namespace app\publish\validate;


use think\Validate;

class JoomValidate extends Validate
{
    protected $rules = [
        ['goods_id','require|number','商品id必填,且为大于0的数字'],
        ['uid','require|number|gt:0','用户id必须，且为大于0的数字'],
        ['product_id','require','商品id必须'],
        ['variant_id','require','sku id必填'],
        ['parent_sku','require','SPU必填'],
        //['main_image','require','商品主图main_image必填'],
        ['price','require|number|gt:0','商品售价必填,且为大于0的数字'],
        ['shipping','require|number|egt:0','运费必填，大于等于0'],
        ['shipping_time','require','发货时间必填'],
        ['inventory','require|number|gt:0|elt:500000','商品库存inventory必填,且大于0小于等于500000'],
        //['variant','require','商品多属性（变体）必填'],
        //['zh_name','require','商品中文标题必填'],
        ['vars','require','vars必填'],
        ['account_id','require|number|gt:0','刊登账号必填,且为大于0的数字'],
        ['shop_id','require|number|gt:0','刊登店铺必填,且为大于0的数字'],
        ['name','require','刊登标题必填'],
        ['images','require|array','商品相册不能为空'],
        ['main_image','require','sku图片不能为空'],
        ['description','require','商品描述详情description必填'],
        ['tags','require','商品tags必填'],
        ['declared_value','require|number|gt:0','报关价价必填,且为大于0的数字'],
    ];

    protected $scene = [
        'add'  =>  ['goods_id','parent_sku','vars'],
        'save'  =>  ['goods_id','uid'],
        'single'=>['account_id','shop_id','name','inventory','price','tags','description','shipping','shipping_time','images'],
        'var'=>['account_id','shop_id','name','tags','description','images'],
        'variant'=>['sku','color','size','price','shipping','shipping_time','inventory,declared_value'],
        'buhuo'=>['product_id','variant_id','inventory','uid'],
    ];

    /**
     * @node 刊登数据验证
     * @access public
     * @param array $post
     * @return string
     */
    public  function  checkData($post=array())
    {
        $this->check($post,$this->rules,'add');
        if($error = $this->getError())
        {
            return $error;
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
            if($error)
            {
                return '账号['.$post[$key]['account_code'].']'.$error;
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
        }

        if(isset($var['images']) && empty($var['images']))
        {
            $error='商品相册不能为空,且不能超过20张';
            return $error;
        }

        if(isset($var['images']) && count($var['images'])>20)
        {
            $error='商品相册不能超过20张';
            return $error;
        }

        if(isset($var['variant']) && $var['variant'])
        {
            $this->check($var,$this->rules,'var');
        }else{
            $this->check($var,$this->rules,'single');
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
                if($var['color']=='' && $var['size']=='')
                {
                    return '['.$var['sku'].']color和size不能同时为空';
                }
            }
            $this->check($var,$this->rules,$scene);
            if($error = $this->getError())
            {
                return '['.$var['sku'].']'.$error;
            }
        }
    }

    /**
     * @node 验证wish刊登保存功能
     * @access public
     * @param array $post
     * @return string
     */
    public  function checkSave($post)
    {
        $this->check($post, $this->rules, 'save');
        if($error = $this->getError())
        {
            return $error;
        }
    }

}