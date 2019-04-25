<?php
namespace app\common\model\ebay;

use think\Model;
use think\Loader;
use think\Db;

class EbayListingSpecifics extends Model
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
     * 同步listing数据
     * @return [type] [description]
     */
    public function syncListingSpecifics($data,$listing_id)
    {
        $this->destroy(["listing_id"=>$listing_id,"d_load"=>1]);
        $this->saveAll($data);
    }

    public function upListingSpecifics($data,$listing_id)
    {
        $this->destroy(["listing_id"=>$listing_id]);
        $this->saveAll($data);
    }
    
}