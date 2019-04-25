<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\common\model\wish;

use app\common\cache\Cache;
use think\Model;
use think\Db;

/**
 * Description of WishWaitUploadProductVariant
 *
 * @author RondaFul
 */

class WishWaitUploadProductVariant extends Model
{
//    const ENABLED=Enabled;
//    const DISABLED=Disabled;
//    const TRUE=True;
//    const PRODUCT_STATUS = [
//        self::ENABLED=>'上架',
//        self::DISABLED=>'下架',
//        self::TRUE=>'审核中',
//    ];
     /**
     * 初始化
     */
     private $cacheDriver ;
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
        $this->cacheDriver = Cache::store('Goods');
    }
    
    public function getVidAttr($value)
    {
        return (string)$value;
    }

    public $sku_status = [
        0=>'未关联',
        1 => '在售',
        2 => '停售',
        3 => '待发布',
        4 => '卖完下架',
        5 => '缺货',
    ];

    public function getSellStatusAttr($value,$row)
    {
        $sku_id = $row['sku_id'];
        $status = 0;
        if($sku_id){
            $sku = $this->cacheDriver->getSkuInfo($sku_id);
            $status = $sku?$sku['status']:0;
        }

        return $this->sku_status[$status];

//        if(is_null($value)){
//            return '无状态';
//        }elseif($value==0){
//            return '未上架';
//        }elseif($value==1){
//            return '在售';
//        }elseif($value==2){
//            return '停售';
//        }elseif($value==3){
//            return '待发布';
//        }elseif($value==4){
//            return '卖完下架';
//        }elseif($value==5){
//            return '缺货';
//        }
    }

    public function setSellStatusAttr($value)
    {
        if($value=='未上架')
        {
            return 0;
        }elseif($value=='在售'){
            return 1;
        }elseif($value=='停售'){
            return 2;
        }elseif($value=='卖完下架'){
            return 4;
        }elseif($value=='缺货'){
            return 5;
        }else{
            return 0;
        }
    }
    
    public function getPidAttr($value)
    {
        return (string)$value;
    }
    
     public function getEnabledAttr($value)
    {
        if($value==1)
        {
            $value='上架';
        }elseif($value==0){
            $value='下架';
        }elseif($value==2){
            $value='审核中';
        }elseif($value==-1){
             $value='无效';
        }
        return $value;
    }

    public function setEnabledAttr($value)
    {
        $value = strtolower($value);
        if($value=="true" || $value == 'enabled')
        {
            $value=1;
        }elseif($value=="false" || $value = 'disabled'){
            $value=0;
        }
        
        return $value;
    }

    /**
     * 组合sku获取器
     * @param $value
     * @param $row
     * @return string
     */
    public function getCombineSkuAttr($value,$row){

        if(empty($value))
        {
            if($row['sku_id'])
            {
                $skuArray = Cache::store('Goods')->getSkuInfo($row['sku_id']);
                if($skuArray)
                {
                    $value = $skuArray['sku']."*1";
                }
            }
        }
        return $value;
    }


    /**
     * 新增变体信息
     * @param type $data
     * @return boolean
     */
    public function add($data)
    {
        if (!isset($data['variant_id'])) {
            return false;
        }
        
        //检查sku是否已存在
        if ($this->checkVariant(['variant_id' => $data['variant_id']]) ) {
            $this->edit($data, ['variant_id' => $data['variant_id']]);
        }else{
            $data['add_time'] = time();
            time_partition(__CLASS__,$data['add_time']);
            $this->allowField(true)->isUpdate(false)->save($data);
        }
    }
    
     /** 修改变体
     * @param array $data
     * @param array $where
     * @return false|int
     */
    public function edit(array $data, array $where)
    {
        return $this->allowField(true)->save($data, $where);
    }
    
    /**
     * 批量新增
     * @param array $data
     */
    public function  addAll($data)
    {
        foreach ($data as $key => $value) 
        {
           $this->add($value);
        }
    }
    /**
     * 检查变体是否存在
     * @param array $where
     * @return boolean
     */
    private function checkVariant($where)
    {
        $result = $this->get($where);
        if (!empty($result)) {
            return true;
        }
        return false;
    }
    
    
    /** 新增
     * @param array $data
     * @return false|int
     */
    public function addData(array $data)
    {
        if (isset($data['pid']) && isset($data['sku'])) 
        {
            //检查产品是否已存在
            if (!$this->check(['pid' => $data['pid'],'sku'=>$data['sku']])) 
            {
                if(count($data) == count($data,1))
                {
                    $res = $this->allowField(true)->isUpdate(false)->save($data);
                }else{
                    $res = $this->allowField(true)->isUpdate(false)->saveAll($data);
                }            
            }
            
        }      
    }
    
    /** 检查是否存在
     * @param array $data
     * @return bool
     */
    public   function check(array $data)
    {
        $result = $this->get($data);
        if (!empty($result)) {
            return true;
        }
        return false;
    }
    /**
     * 获取一个sku信息
     * @param array $where
     * @return array
     */
    public  static function  getOne($where=array(),$with=[])
    {
        return self::get($where,$with);
    }
    
    /**
     * 获取变体关联的产品
     * @return object
     */
    public function product()
    {
        return $this->hasOne(WishWaitUploadProduct::class, 'id', 'pid', [], 'LEFT');
    }
    
    public  function skumap()
    {
        return $this->hasOne(\app\common\model\GoodsSkuMap::class, 'channel_sku', 'sku', [], 'LEFT');
    }
    public function spu()
    {
    	return $this->belongsTo(WishWaitUploadProduct::class,'id','pid');
    }
    
    
    
}
