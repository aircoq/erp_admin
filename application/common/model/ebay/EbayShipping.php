<?php
namespace app\common\model\ebay;

use think\Model;
use think\Exception;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/10/28
 * Time: 9:13
 */
class EbayShipping extends Model
{
    /**
     * 初始化
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }

    public function syncShipping($data)
    {
        try {
            $map['shippingserviceid'] = $data['shippingserviceid'];
            $map['siteid'] = $data['siteid'];
            $wh['siteid'] = $data['siteid'];
            $wh['shippingservice'] = $data['shippingservice'];
            if ($this->get($map)) {
                $this->update($data, $map);
            } else if ($this->get($wh)) {
                $this->update($data, $wh);
            } else {
                $this->save($data);
            }
        } catch(Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }
}