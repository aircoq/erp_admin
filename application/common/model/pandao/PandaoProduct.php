<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-4-19
 * Time: 下午2:08
 */

namespace  app\common\model\pandao;
use app\common\model\Goods;
use app\common\model\User;
use think\Model;
class PandaoProduct extends Model
{
    protected function initialize(){
        parent::initialize();
    }
    public function getIdAttr($v){
        return (string)$v;
    }
    public function getPublishStatusAttr($v,&$row){
        return $v;
    }
    public function getReviewStatusAttr($v){
        switch ($v){
            case 1:
                return '审核通过';
                break;
            case 2:
                return '审核失败';
                break;
            default :
                return '审核中';
                break;
        }
    }
    public function setLastUpdatedAttr($v){
            if(is_string($v)){
                $v= strtotime($v);
            }
            return $v;
    }
    public function setDateUploadedAttr($v){
        if(is_string($v)){
            $v= strtotime($v);
        }
        return $v;
    }

//    public function getLastUpdatedAttr($v){
//        if(empty($v)){
//            return '';
//        }else{
//            return date('Y-m-d H:i:s',$v);
//        }
//    }
    public function getDateUploadedAttr($v){
        if(empty($v)){
            return '';
        }else{
            return date('Y-m-d',$v);
        }
    }

    public function getLockUpdateAttr($v){
        switch ($v){
            case 0:
                return '未更新';
                break;
            case 1:
                return '已更新';
                break;
            default:
                return '更新失败';
                break;
        }
    }
    public function setReviewStatusAttr($v){
        switch ($v){
            case 'Enabled':
                return 1;
                break;
            case 'Disabled':
                return 2;
                break;
            default:
                return 0;
                break;
        }
    }
    /**
     *  获取待刊登商品列表
     *  一个待上传商品product对应多个variant
     */
    //====================关联模型
    //关联变体
    public function variants()
    {
        return $this->hasMany(PandaoVariant::class,'pid','id');
    }

    public function info()
    {
        return $this->hasOne(PandaoProductInfo::class,'id','id');
    }

    public function skus()
    {
        return $this->hasMany(PandaoVariant::class,'pid','id');
    }

    public function user()
    {
        return $this->hasOne(User::class,'id','create_id',[],'LEFT')->field('id,realname');
    }
    /**
     *
     * @return type
     */
    //关联账户
    public function account()
    {
        return $this->hasOne(PandaoAccount::class,'id','account_id');
    }
    //关联商品
    public  function goods()
    {
        return $this->hasOne(Goods::class,'id','goods_id');
    }
}