<?php
namespace app\api\validate;
use app\common\model\Attribute;
use app\common\model\AttributeValue;
use app\common\model\Category;
use app\goods\service\GoodsImport;
use think\Validate;

/**
 * Created by PhpStorm.
 * User: TOM
 * Date: 2017/8/18
 * Time: 11:39
 */
class ProductValidate extends Validate
{
    public $temporaryData;
    public function __construct(array $rules = [], array $message = [], array $field = [])
    {
        parent::__construct($rules, $message, $field);
    }

    protected $rule = [
        ['spu','require|unique:Goods','产品spu不能为空|产品spu已存在'],
        ['skus','require','产品sku信息不能为空'],
        ['sku','require|unique:GoodsSku','产品sku不能为空|产品sku已存在'],
        ['category','require|checkCategory','产品分类不能为空|产品分类错误'],
        ['shuxlx','checkAttr','产品sku属性错误'],
    ];

    protected $scene = [
        'spu'=> ['spu','category'],
        'sku'=> ['sku','shuxlx'],
        'update'=>['sku|require','shuxlx']
    ];

    /**
     * sku属性验证
     * checkAttr
     * @param $value
     * @param $rule
     * @param $data
     */
    protected function checkAttr($value, $rule, $data)
    {
        if(empty($value)){
            return true;
        }
        $attributes = GoodsImport::$oa_attr_map;
        $sku_attrs = [];
        $error_msg = [];
        foreach ($value as $attr){
            $attr['typeValue'] =strip_tags($attr['typeValue']);
            $attr['typeValue'] =html_entity_decode($attr['typeValue']);
            $name = trim($attr['typeName']);
            if ($name == '单属性') {
                continue;
            }
            if(isset($attributes[$name])){
                $attribute_id = $attributes[$name];
            }else{
                $attributeInfo = Attribute::where(['name' => ['like', $name . '%']])->field('id')->find();
                if(empty($attributeInfo)){
                    $error_msg[] = '属性 '. $name . ' 不在系统中';
                    continue;
                    //return '属性 '. $name . ' 不在系统中';
                }
                $attribute_id = $attributeInfo['id'];
            }
            if (in_array($attribute_id, GoodsImport::$diy_attr)) {
                $sku_attrs[] = [
                    'attribute_id' => $attribute_id,
                    'value' => $attr['typeValue'],
                    'value_id' => 0
                ];
            } else {
                $valueInfo = AttributeValue::where(['attribute_id' => $attribute_id, 'value' => ['like', $attr['typeValue'] . '%']])->field('id')->find();

                if (!$valueInfo) {
                    $error_msg[] = '属性值 '. $attr['typeValue'] . ' 不在系统中';
                    continue;
                }
                $sku_attrs[] = [
                    'attribute_id' => $attribute_id,
                    'value_id' => $valueInfo['id']
                ];
            }
        }
        $this->temporaryData['sku_attr'] = $sku_attrs;
        if(!empty($error_msg)){
            return implode(';',$error_msg);
        }
        return true;
    }

    /**
     * 验证产品分类
     * checkCategory
     * @param $value
     * @param $rule
     * @param $data
     * @return bool|string
     */
    protected function checkCategory($value, $rule, $data)
    {
        $categories = explode('/', $value);
        if (count($categories) != 2) {
            return '分类格式不对';
        }
        $parentInfo = Category::where(['title' => trim($categories[0]), 'pid' => 0])->field('id')->find();
        if (!$parentInfo) {
            return '分类 ' . $categories[0] . ' 不在系统中';
        }
        $subInfo = Category::where(['title' => trim($categories[1]), 'pid' => $parentInfo['id']])->field('id')->find();
        if (!$subInfo) {
            return '分类 '. $categories[1] . ' 不在系统中';
        }
        $this->temporaryData['category_id'] = $subInfo['id'];
        return true;
    }
}