<?php
namespace app\common\model\ebay;

use think\Model;
use think\Loader;
use think\Db;

class EbayListingImage extends Model
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
     * 同步listing数据(图片)
     * @return [type] [description]
     */
    public function syncListingImages($data,$listing_id){
    	$this->destroy(array("listing_id"=>$listing_id));
    	$this->saveAll($data);
    }

    /**
     * 更新图片信息
     * @return [type] [description]
     */
    public function syncListingImg($data,$listing_id){
        $rs=$this->where(["eps_path"=>$data['eps_path']])->find();
        if($rs){#更新
            $img['sort'] = $data['sort'];
            $img['eps_path'] = $data['eps_path'];
            return $this->where(['id'=>$rs['id']])->update($img);
        }else{
            return Db::name("ebay_listing_image")->insertGetId($data);
        }
    }
}