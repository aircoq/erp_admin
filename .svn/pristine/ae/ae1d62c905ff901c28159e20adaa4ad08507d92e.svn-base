<?php
namespace app\common\model\aliexpress;

use think\Model;

class AliexpressAccountBrand extends Model
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

    public static function getBrandByAccount($accountId,$categoryId=false)
    {
        $where['account_id'] = $accountId;
        if($categoryId){
            $where['category_id'] = $categoryId;
        }
        $arrBrand = self::where($where)->field('attr_value_id')->select();
        $result = [];
        if(!empty($arrBrand)){
            foreach($arrBrand as $brand){
                $result[] = [
                    'id'        =>  $brand['attr_value_id'],
                    'name_zh'   =>  $brand['brandAttrVal']['name_zh'],
                    'name_en'   =>  $brand['brandAttrVal']['name_en'],
                ];
            }
        }
        return $result;
    }

    public function brandAttrVal()
    {
        return $this->belongsTo(AliexpressCategoryAttrVal::class,'attr_value_id','id');
    }
}