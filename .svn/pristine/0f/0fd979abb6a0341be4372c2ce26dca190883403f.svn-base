<?php
namespace app\common\model\ebay;

use think\Model;
use think\Loader;
use think\Db;

class EbayListingSetting extends Model
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
//    public  function setReturnPolicyAttr($value)
//    {
//        if($value=='ReturnsAccepted')
//        {
//            return 1;
//        }else{
//            return 0;
//        }
//    }
    
    /**
    *同步listing设置信息
    **/
    public function syncSetting(array $data){
        $rows=$this->get(["id"=>$data['id']]);
        if($rows){#更新
            $wh['id']=$rows['id'];
            $this->where($wh)->update($data);
            return $rows['id'];
        }else{#添加
            $this->save($data);
            return $data['id'];
        }
    }
}