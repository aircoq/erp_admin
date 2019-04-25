<?php
namespace app\common\model\ebay;

use think\Model;
use think\Loader;
use think\Db;

class EbayListingVariation extends Model
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

    /**
     * 同步listing数据(子产品)
     * @return [type] [description]
    */
    public function syncListingVarions($data,$listing_id){
        $wh['listing_id']=$listing_id;
        #$wh['v_sku']=$data['v_sku'];
        $wh['channel_map_code']=$data['channel_map_code'];
        $data['listing_id']=$listing_id;
        $rows=$this->get($wh);
        if($rows){#更新
            $this->save($data,array("id"=>$rows['id']));
        }else{#新增
            $this->create($data);
        }
        unset($data);
    }
    public function product()
    {
        return $this->belongsTo(EbayListing::class,'id','listing_id');
    }
    
}