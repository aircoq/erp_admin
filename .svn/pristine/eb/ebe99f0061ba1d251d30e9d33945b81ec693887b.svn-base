<?php
namespace app\common\model\aliexpress;
use think\Model;
use think\Cache;
class AliexpressCategoryAttr extends Model
{
    /**
     * 初始化
     * @return [type] [description]
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }

    public function attributeVal()
    {
        return $this->hasMany(AliexpressCategoryAttrVal::class,'attr_id','id');
    }
    
    public function aliexpressCategorySkuVal()
    {
        return $this->hasMany('AliexpressCategoryAttrVal');
    }
    
    /**
     * 添加分类属性
     */
    public function addCategoryAttr($data = [])
    {
        $code = 0;
        $code = $this->save($data);
        return $code;
    }
    
    /**
     * 根据分类id更新分类属性
     * @param number $cateId
     * @param unknown $data
     */
    public function updateCategoryAttr($data = [], $where = [])
    {
        $code = 0;
        $code = $this->allowField(true)->save($data, $where);
        return $code;
    }

    /**
     * @param $categoryId
     * @param $id
     * @return bool|mixed
     */
    public static function getNameById($categoryId,$id)
    {
        $attrModel = self::cache(true,600)->field('names_zh')->find(['category_id'=>$categoryId,'id'=>$id]);
        return empty($attrModel)?false:$attrModel->names_zh;
    }
    
    /**
     * 根据分类id获取分类属性
     * @param number $cateID
     * @param int $type 0普通属性；1sku属性；2所有属性
     */
    public function getCategoryAttr($cateId = 0,$type=2,array $condition=[])
    {
        $where['category_id'] = $cateId;
        if($type!=2){
            $where['sku'] = $type;
        }
        if(!empty($condition)){
            foreach($condition as $k=>$item){
                $where[$k] = $item;
            }
        }
        $ids   = $this->where($where)->order('spec ASC')->select();
        if ($ids) {
            return $ids;
        }
        return false;
    }
    
    
    public function getListValAttr($value)
    {
        if(!empty($value))
        {
            $result = AliexpressCategoryAttrVal::where('id','IN',$value)->cache(true,1*24*60)->order('id ASC')->select();
            return  empty($result)?$result:collection($result)->toArray();
        }
        return $value;
    }
   
}