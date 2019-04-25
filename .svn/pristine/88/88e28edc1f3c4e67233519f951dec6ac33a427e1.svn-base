<?php
namespace app\goods\service;

use app\common\cache\Cache;
use app\common\exception\JsonErrorException;
use app\common\model\GoodsDeclareInfo;
use think\Exception;

/**
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/11/30
 * Time: 11:33
 */
class GoodsDeclare
{
    protected $goodsDeclareInfoModel;

    public function __construct()
    {
        if (is_null($this->goodsDeclareInfoModel)) {
            $this->goodsDeclareInfoModel = new GoodsDeclareInfo();
        }
    }

    /**
     * 列表
     * @param $page
     * @param $pageSize
     * @param array $where
     * @return array
     */
    public function lists($page, $pageSize, $where = [])
    {
        $field = 'id,sku_id,title,desc,declare_price,thumb,sku,create_time';
        $count = $this->goodsDeclareInfoModel->field($field)->where($where)->count();
        $infoList = $this->goodsDeclareInfoModel->field($field)->where($where)->page($page, $pageSize)->order('create_time desc')->select();
        $result = [
            'count' => $count,
            'page' => $page,
            'pageSize' => $pageSize,
            'data' => $infoList
        ];
        return $result;
    }

    /**
     * 更新产品申报价
     * @param $id
     * @param $declare_price
     * @param $data
     */
    public function update($id, $declare_price, $data)
    {
        try {
            $info['thumb'] = $data['thumb'];
            $info['desc'] = $data['desc'];
            $info['title'] = $data['title'];
            $info['declare_price'] = $declare_price;
            $info['id'] = $id;
            $validate = validate('GoodsDeclareInfo');
            if (!$validate->scene('edit')->check($info)) {
                throw new Exception($validate->getError());
            }
            unset($info['id']);
            $this->goodsDeclareInfoModel->where(['id' => $id])->update($info);
        } catch (Exception $e) {
            throw new JsonErrorException($e->getMessage());
        }
    }

    /**
     * 获取详情
     * @param $id
     * @param bool|false $is_sku
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws Exception
     */
    public function info($id, $is_sku = false)
    {
        if ($is_sku) {
            $where['sku_id'] = ['eq', $id];
        } else {
            $where['id'] = ['eq', $id];
        }
        $info = $this->goodsDeclareInfoModel->field(true)->where($where)->find();
        if (!empty($info)) {
            $info = $info->toArray();
        }
        $skuInfo = Cache::store('goods')->getSkuInfo($info['sku_id']);
        if (!empty($skuInfo)) {
            $goodsHelp = new GoodsHelp();
            $sku_attributes = json_decode($skuInfo['sku_attributes'], true);
            $attributesData = $goodsHelp->getAttrbuteInfoBySkuAttributes($sku_attributes,
                $skuInfo['goods_id']);
            $sku_attributes = '';
            foreach ($attributesData as $a => $attr) {
                $sku_attributes .= $attr['value'] . ',';
            }
            $sku_attributes = rtrim($sku_attributes, ',');
            $info['attributes'] = $sku_attributes;
        }
        return $info;
    }

    /**
     * 新增
     * @param $sku
     * @param $declare_price
     * @param array $data
     * @return mixed
     */
    public function add($sku, $declare_price, $data = [])
    {
        try {
            $sku_id = GoodsHelp::sku2id($sku);
            $skuInfo = Cache::store('goods')->getSkuInfo($sku_id);
            if (empty($skuInfo)) {
                throw new Exception('sku不存在');
            }
            $info['sku'] = $skuInfo['sku'];
            $info['thumb'] = isset($data['thumb']) && !empty($data['thumb']) ? $data['thumb'] : $skuInfo['thumb'];
            $info['title'] = isset($data['title']) && !empty($data['title']) ? $data['title'] : $skuInfo['spu_name'];
            $info['sku_id'] = $sku_id;
            $info['desc'] = isset($data['desc']) && !empty($data['desc']) ? $data['desc'] : $skuInfo['name'];
            $info['declare_price'] = $declare_price;
            $info['create_time'] = time();
            $validate = validate('GoodsDeclareInfo');
            if (!$validate->check($info)) {
                throw new Exception($validate->getError());
            }
            $this->goodsDeclareInfoModel->allowField(true)->isUpdate(false)->save($info);
            $id = $this->goodsDeclareInfoModel->id;
            return $this->lists(1, 1, ['id' => $id])['data'];
        } catch (Exception $e) {
            throw new JsonErrorException($e->getMessage());
        }
    }

    /**
     * 删除
     * @param $id
     */
    public function del($id)
    {
        try {
            $this->goodsDeclareInfoModel->where(['id' => $id])->delete();
        } catch (Exception $e) {
            throw new JsonErrorException($e->getMessage());
        }
    }
}