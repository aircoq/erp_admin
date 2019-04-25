<?php

namespace app\goods\service;

use app\common\model\GoodsSkuAlias as SkuAliasModel;

class GoodsSkuAlias
{
    /**
     * 获取产品sku_id
     * @param string|array $alias
     * @return int|array
     */
    public static function getSkuIdByAlias($alias)
    {
        if (is_array($alias)) {
            $ids = [];
            $lists = SkuAliasModel::where('alias', 'in', $alias)->field(true)->select();
            foreach ($lists as $list) {
                $ids[] = $list['sku_id'];
            }
            $ids = array_unique($ids);
        } else {
            $lists = SkuAliasModel::where('alias', $alias)->field(true)->find();
            $ids = $lists ? $lists['sku_id'] : 0;
        }

        return $ids;
    }

    /**
     * 获取sku别名通过skuId
     * @param int $skuId
     * @return array
     */
    public static function getAliasBySkuId($skuId, $type = false)
    {
        $alias = [];
        $where = ['sku_id' => $skuId];
        if ($type !== false) {
            $where['type'] = $type;
        } else {
            $where['type'] = 2;
        }
        $rows = SkuAliasModel::where($where)->select();
        foreach ($rows as $row) {
            /*if ($row['default'] && !$default) {
                continue;
            }*/
            array_push($alias, $row['alias']);
        }

        return $alias;
    }

    public static function getAliasBySkuIds($skuId)
    {
        $alias = [];
        $where = ['sku_id' => ['in', $skuId],'type'=>2];
        $rows = SkuAliasModel::where($where)->select();
        foreach ($rows as $row) {
            $alias[$row['sku_id']][] = $row['alias'];
        }
        return $alias;
    }


    public function getListBySkuId($skuId, $type = null)
    {
        $where = ['sku_id' => $skuId];
        if ($type !== null) {
            $where['type'] = $type;
        }
        return SkuAliasModel::where($where)->select();
    }


    /**
     * 添加sku别名
     * @param int $skuId
     * @param string $alias
     * @param boolean $default
     * @return boolean
     */
    public function insert($skuId, $sku, $alias, $type)
    {
        $count = SkuAliasModel::where('alias', $alias)->count();
        if (!$count) {
            $skuAlias = new SkuAliasModel();
            $skuAlias->sku_id = $skuId;
            $skuAlias->sku_code = $sku;
            $skuAlias->alias = $alias;
            $skuAlias->type = $type;
            $skuAlias->create_time = time();
            return $skuAlias->isUpdate(false)->save();
        }
        return false;

    }

    /**
     * 删除sku别名
     * @param type $skuId
     * @param type $alias
     */
    public function delete($skuId, $alias)
    {
        return SkuAliasModel::where(['sku_id' => $skuId, 'alias' => $alias, 'default' => 0])->delete();
    }


    public function createNewAlias($skuId, $sku, $alias, $type)
    {

    }


}