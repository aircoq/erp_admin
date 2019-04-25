<?php
namespace app\common\model\ebay;

use think\Model;
use think\Loader;
use think\Db;

class EbayListingTransportIn extends Model
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
    public  function setExpeditedServiceAttr($v)
    {
        if($v=='true')
        {
            return 1;
        }else{
            return 0;
        }
    }
    /**
     * 同步listing数据(物流方式)
     * @return [type] [description]
     */
    public function syncListingTransIn($data,$listing_id){
    	$this->destroy(array("listing_id"=>$listing_id));
    	$this->saveAll($data);
    }
    
}