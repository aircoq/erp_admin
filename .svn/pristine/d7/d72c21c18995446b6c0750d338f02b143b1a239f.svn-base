<?php
namespace app\common\model;

use think\Model;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/10/29
 * Time: 9:17
 */
class GoodsLang extends Model
{
    /**
     * 初始化数据
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }
    
    /**
     * 描述记录是否存在
     */
    public function check($where)
    {
        $info = $this->get($where);
        if (empty($info)) {
            return false;
        }
        return true;
    }

    /**
     * 卖点描述
     */
    const SELLING_POINT = [
        'amazon_point_1',
        'amazon_point_2',
        'amazon_point_3',
        'amazon_point_4',
        'amazon_point_5',
    ];
    private function getSellingPoint($key){
        $data = $this->selling_point?json_decode($this->selling_point,true):[];
        return isset($data[$key])?$data[$key]:'';
    }


    public function getAmazonPoint_1Attr($value,$data){
      return $this->getSellingPoint('amazon_point_1');
    }
    public function getAmazonPoint_2Attr($value,$data){
        return $this->getSellingPoint('amazon_point_2');
    }
    public function getAmazonPoint_3Attr($value,$data){
        return $this->getSellingPoint('amazon_point_3');
    }
    public function getAmazonPoint_4Attr($value,$data){
        return $this->getSellingPoint('amazon_point_4');
    }
    public function getAmazonPoint_5Attr($value,$data){
        return $this->getSellingPoint('amazon_point_5');
    }

}