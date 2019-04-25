<?php
namespace app\common\model\wish;

use think\Model;
use think\Loader;
use think\Db;

class WishPlatformOnlineGoodsVariation extends Model
{
    /**
     * 初始化
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }

    /** 关系
     * @return \think\model\Relation
     */
    public function role()
    {
        //一对多的关系
        return $this->belongsTo('WishPlatformOnlineGoods');
    }

    /** 新增
     * @param array $data
     * @return false|int
     */
    public function add(array $data)
    {
        if (isset($data['variation_id'])) {
            //检查产品是否已存在
            if ($this->checkgoods(['variation_id' => $data['variation_id']])) {
                return $this->edit($data, ['variation_id' => $data['variation_id']]);
            }
        }
        return $this->insert($data);
    }

    /**
     * 批量新增
     * @param array $data [description]
     */
    public function addAll(array $data)
    {
        foreach ($data as $key => $value) {
            $this->add($value);
        }
    }

    /** 分区
     * @param $current
     * @return bool
     */
    public function parition($current)
    {
        //当前时间，判断分区是否存在
        $result = Db::query('ALTER TABLE `wish_platform_online_goods_variation` CHECK partition p' . $current);

        if ($result[0]['Msg_type'] != 'status' && $result[0]['Msg_text'] != 'OK') {
            //证明分区不存在，需要创建
            $bool = Db::query('ALTER TABLE `wish_platform_online_goods_variation` ADD PARTITION (PARTITION p' . $current . ' VALUES LESS THAN (' . ($current + 1) . '))');
            if ($bool) {
                return true;
            }
        }
        return true;
    }

    /** 修改产品
     * @param array $data
     * @param array $where
     * @return false|int
     */
    public function edit(array $data, array $where)
    {
        return $this->allowField(true)->save($data, $where);
    }

    /** 批量修改
     * @param array $data
     * @return false|int
     */
    public function editAll(array $data)
    {
        return $this->save($data);
    }

    /** 检查产品是否存在
     * @param array $data
     * @return bool
     */
    protected function checkgoods(array $data)
    {
        $result = $this->get($data);
        if (!empty($result)) {
            return true;
        }
        return false;
    }
}
