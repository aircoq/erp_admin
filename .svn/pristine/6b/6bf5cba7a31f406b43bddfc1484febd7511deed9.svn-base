<?php
namespace app\common\model\report;

use app\index\service\DepartmentUserMapService;
use think\Model;
use app\common\cache\Cache;

/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/9/18
 * Time: 13:49
 */
class ReportStatisticPublishByShelf extends Model
{
    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }

    public function getShelfNameAttr($value, $data)
    {
        $user = Cache::store('user')->getOneUser($data['shelf_id']);
        if ($user) {
            return $user['realname'];
        }
        return '';
    }

    public function getChannelAttr($value, $data)
    {
        return Cache::store('channel')->getChannelName($data['channel_id']);
    }

    public function getGoodsAttr($value, $data)
    {
        $goods = Cache::store('Goods')->getGoodsInfo($data['goods_id']);
        if(!$goods){
           return  $data['goods_id'];
        }
        return $goods['name'] .'/' .$goods['spu'];
    }

    public function getCatetoryAttr($value, $data)
    {
        return Cache::store('Category')->getFullNameById($data['catetory_id'],'');

    }
    public function add($data)
    {
        $map = [
            'dateline' => $data['dateline'],
            'channel_id' => $data['channel_id'],
            'account_id' => $data['account_id'],
            'shelf_id' => $data['shelf_id'],
            'goods_id' => $data['goods_id'],
        ];
        $old = $this->isHas($map);
        if($old){
            $save = [
                'times' => $data['times'] + $old['times'],
                'quantity' => $data['quantity'] + $old['quantity'],
            ];
            return $this->save($save,$map);
        }else{
            $goods = Cache::store('Goods')->getGoodsInfo($data['goods_id']);
            $data['catetory_id'] = $goods['category_id'] ?? 0;
            $departmentIds = (new DepartmentUserMapService())->getDepartmentByUserId($data['shelf_id']);
            $data['department_id'] = $departmentIds[rand(0,count($departmentIds)-1)]??0;
            return $this->insert($data);
        }
    }

    public function isHas($map)
    {
        return $this->where($map)->find();
    }

}