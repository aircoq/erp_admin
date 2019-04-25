<?php
namespace app\common\model\aliexpress;

use think\Model;

/**
 * Created by ZendStudio.
 * User: Hot-Zr
 * Date: 2017年3月29日 
 * Time: 16:36:45
 */
class AliexpressProductSku extends Model
{
    protected $autoWriteTimestamp = true;
    /**
     * 初始化数据
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }

    public function product()
    {
        return $this->hasOne(AliexpressProduct::class,'id','ali_product_id');
    }

    public function productM()
    {
        return $this->hasOne(AliexpressProduct::class,'id','ali_product_id');
    }
    /**
     * 组合sku获取器
     * @param $value
     * @param $row
     * @return string
     */
    public function getCombineSkuAttr($value,$row)
    {
        
        if(empty($value))
        {
            $skuArray = explode("|",$row['sku_code']);

            if(!empty($skuArray))
            {
                $value=$skuArray[0]."*1";
            }else{
                $value = $row['sku']."*1";
            }
        }
        return $value;
    }

}