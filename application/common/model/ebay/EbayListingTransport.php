<?php
namespace app\common\model\ebay;

use think\Model;
use think\Loader;
use think\Db;

class EbayListingTransport extends Model
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
     * 同步listing数据(物流方式)
     * @return [type] [description]
     */
    public function syncListingTrans($data,$listing_id){
    	$this->destroy(array("listing_id"=>$listing_id));
    	$this->saveAll($data);
    }
    
}