<?php
namespace app\api\service;

use app\common\model\Goods;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2017/4/11
 * Time: 16:58
 */
class Install extends Base
{
    public function test()
    {
        $spu = $this->requestData['data'];
        $spuArr = explode(',',$spu);
        $goodsModel = new Goods();
        $goodsInfo = $goodsModel->field('id,spu')->where('spu','in',$spuArr)->select();
        $this->retData['goods'] = $goodsInfo;
        return $this->retData;
    }
}