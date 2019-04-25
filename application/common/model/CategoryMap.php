<?php
namespace app\common\model;

use think\Model;
use app\common\cache\Cache;
/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/11/4
 * Time: 10:17
 */
class CategoryMap extends Model
{
    /**
     * 初始化
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }

    /** 保存映射
     * @param array $data
     * @param int $category_id
     */
    public function saveData(array $data,$category_id)
    {
        //读取缓存信息
        $category_map = Cache::store('category')->getCategoryMap($category_id);
        if(isset($category_map[$category_id])){
            $category_map = Cache::filter($category_map[$category_id],[],'channel_id,channel_category_id,path,label,site_id');
        }
        $is_diff = serialize($category_map) == serialize($data) ? true : false;
        if(count($category_map) != count($data) || !$is_diff){
            //删除之前所有记录
            $this->where(['category_id' => $category_id])->delete();
            $map = [];
            foreach($data as $v){
                $map[$v['channel_id']]['category_id'] = $category_id;
                $map[$v['channel_id']]['channel_id'] = $v['channel_id'];
                $map[$v['channel_id']]['channel_category_id'] = $v['channel_category_id'];
                $map[$v['channel_id']]['path']        = $v['path'];
                $map[$v['channel_id']]['label']       = json_encode($v['label']);
                $map[$v['channel_id']]['site_id']     = $v['site_id'];
                $map[$v['channel_id']]['create_time'] = time();
                $map[$v['channel_id']]['update_time'] = time();
            }
            $this->allowField(true)->insertAll($map);
        }
    }
}