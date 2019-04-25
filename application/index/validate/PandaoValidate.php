<?php
namespace app\index\validate;
/**
 * Created by PhpStorm.
 * User: phill
 * Date: 2019/1/18
 * Time: 10:49 AM
 */


use think\Validate;

class PandaoValidate extends Validate
{
    protected $rule=[
        ['id','require|number|gt:0','id必填,且大于等于0'],
        ['client_id','require','client_id必填'],
        ['client_secret','require','client_secret必填'],
        ['username','require','用户名必填'],
        ['password','require','密码必填'],
        ['code','require|max:30|min:4','账号简称必填,最少４个字符，最多30个字符'],
        ['account_name','require','账号必填'],
        ['sync_listing','require|number|egt:0','同步listing必填,且大于等于0'],
        ['sync_delivery','require|number|egt:0','同步发货必填,且大于等于0'],
        ['download_order','require|number|egt:0','同步订单必填,且大于等于0'],
        ['is_invalid','require|boolean','系统状态必须,只能是0或者1'],

        ['goods_id','require|number','商品id必填,且为大于0的数字'],
        ['product_id','require','商品id必须'],
        ['variant_id','require','sku id必填'],
        ['parent_sku','require','SPU必填'],
        ['price','require|number|gt:0','商品售价必填,且为大于0的数字'],
        ['shipping','require|number|egt:0','运费必填，大于等于0'],
        ['shipping_time','require','发货时间必填'],
        ['inventory','require|number|gt:0|elt:500000','商品库存inventory必填,且大于0小于等于500000'],
        ['vars','require','vars必填'],
        ['account_id','require|number|gt:0','刊登账号必填,且为大于0的数字'],
        ['name','require','刊登标题必填'],
        ['images','require|array','商品相册不能为空'],
        ['main_image','require','sku图片不能为空'],
        ['description','require','商品描述详情description必填'],
        ['tags','require','商品tags必填'],
        ['sku','require','sku必须'],
//        ['color','require','颜色必须'],
//        ['size','require','尺码必须'],
        ['price','require','售价必须'],
        ['shipping','require','运费必须'],
        ['shipping_time','require','发货时间必须'],
        ['inventory','require','可售量必须'],


    ];
    protected $scene=[
        'add_account'=>['code','account_name','sync_listing','download_order'],
        'update_account'=>['id','account_name','sync_delivery','sync_listing','download_order'],
        'authorization'=>['id','client_id','client_secret','username','password'],
        'change_status'=>['id','is_invalid'],
        'create'=>['goods_id','parent_sku','vars'],
        'single'=>['account_id','name','inventory','price','tags','description','shipping','shipping_time','images'],
        'var'=>['account_id','name','tags','description','images'],
        'variant'=>['sku','color','size','price','shipping','shipping_time','inventory'],
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

//        if(isset($var['variant']) && $var['variant'])
//        {
//            $this->check($var,$this->rule,'var');
//        }else{
//            $this->check($var,$this->rule,'single');
//        }

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

            $this->check($var,$this->rule,$scene);
            if($error = $this->getError())
            {
                return '['.$var['sku'].']'.$error;
            }
        }
    }
}