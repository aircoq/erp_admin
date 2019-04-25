<?php

namespace app\goods\service;

use app\common\model\Goods;
use app\common\model\GoodsAttribute;
use app\common\model\GoodsDevMenu;
use app\common\model\GoodsLang;
use app\common\model\GoodsSku;
use app\common\service\Common;
use think\Exception;
use think\Db;
use app\common\model\GoodsDevelopLog;
use app\goods\service\GoodsHelp;
use app\goods\service\GoodsSku as ServiceGoodsSku;
use app\goods\service\GoodsNodeProcess as ServiceGoodsNodeProcess;
use app\common\model\Supplier;
use app\common\model\SupplierGoodsOffer;
use app\common\model\GoodsWarehouseCycle;
use app\common\model\Category;
use app\common\model\GoodsDev as ModelGoodsDev;
use app\common\model\GoodsImages as ModelGoodsImages;
use app\common\model\GoodsImgRequirement as ModelGoodsImgRequirement;
use app\common\model\GoodsNodeProcess as ModelGoodsNodeProcess;
use app\common\model\GoodsDevScore as ModelGoodsDevScore;
use app\common\cache\Cache;
use app\purchase\service\SupplierService;
use app\common\validate\Goods as ValidateGoods;
use app\common\validate\GoodsDev as ValidateGoodsDev;
use app\common\validate\GoodsImgReuirement as ValidateGoodsImgReuirement;
use app\common\validate\GoodsLang as ValidateGoodsLang;
use app\index\service\User;
use app\index\service\JobService;
use app\index\service\DepartmentUserMapService;


/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/12/6
 * Time: 18:14
 */
class Goodsdev extends GoodsHelp
{


    /**
     * 保存开发产品
     * @access public
     * @param array $data
     * @param int $user_id
     * @throws Exception
     * @return int
     */
    public function saveGoodsdev($data, $user_id)
    {
        $supplier = [];
        $info = [];
        $attributes = [];
        $description = [];
        $source_urls = [];
        $logs = [];
        $skus = [];
        $this->formatData($data, $info, $supplier, $attributes, $description, $logs, $source_urls, $skus, $user_id);
        $goodsHelp = new GoodsHelp();
        // 添加产品
        $info['spu'] = $goodsHelp->createSpu($info['category_id']);
        $goodsValidate = validate('goods');
        if (!$goodsValidate->check($info)) {
            throw new Exception($goodsValidate->getError());
        }
        $supplierService = new SupplierService();
        // 开启事务
        Db::startTrans();
        try {

            $info['status'] = 0;
            $info['create_time'] = $info['update_time'] = time();
            $info['developer_id'] = $user_id;
            $goods = new Goods();
            $goods->allowField(true)->isUpdate(false)->save($info);
            $goods_id = $goods->id;
            // 处理属性
            $results = [];

            $goodsHelp->checkGoodsAttributes(2, $goods_id, $attributes, $results, $info['category_id']);
            $records = [];
            foreach ($attributes as $attribute) {
                if ($attribute['type'] == 2) {
                    $record['value_id'] = 0;
                    $record['data'] = $attribute['attribute_value'];
                    $record['goods_id'] = $goods_id;
                    $record['attribute_id'] = $attribute['attribute_id'];
                    $records[] = $record;
                } else {
                    foreach ($attribute['attribute_value'] as $value) {
                        $record['value_id'] = $value['id'];
                        $record['data'] = '';
                        $record['alias'] = $attribute['is_alias'] ? $value['value'] : '';
                        $record['goods_id'] = $goods_id;
                        $record['attribute_id'] = $attribute['attribute_id'];
                        $records[] = $record;
                    }
                }
            }

            if ($records) {
                $goodsAttribute = new GoodsAttribute();
                $goodsAttribute->allowField(true)->isUpdate(false)->saveAll($records);
            }
            // 添加描述
            if ($description) {
                $description['goods_id'] = $goods_id;
                $description['lang_id'] = 1;
                $goodsLang = new GoodsLang();
                $goodsLang->allowField(true)->isUpdate(false)->save($description);
            }
            // 添加供应商
            if ($supplier) {
                $supplierService->supplier($goods_id, $supplier, $user_id);
            }

            // 添加参考链接
            if ($source_urls) {
                $goodsHelp = new GoodsHelp();
                $goodsHelp->saveSourceUrls($goods_id, $source_urls, $user_id);
            }

            // 保存sku
            if ($skus) {
                $goodsHelp = new GoodsHelp();
                $goodsHelp->saveSkuInfo($goods_id, $skus, false);
            }
            //保存图片
            if (isset($data['image']) && $data['image']) {
                $GoodsPreDevService = new GoodsPreDevService();
                $file_name = $goods_id . '-' . date('YmdHi') . uniqid() . mt_rand(0, 999);
                $path = $GoodsPreDevService->uploadFile($data['image'], 'goods_images', $file_name);
                $img_insert_data = [
                    'goods_id' => $goods_id,
                    'path' => $path,
                    'created_time' => time()
                ];
                Db::name('goods_images')->insert($img_insert_data);
            }
            // 添加日志
            $logs['goods_id'] = $goods_id;
            $logs['process_id'] = 1;
            $logs['operator_id'] = $user_id;
            $logs['create_time'] = time();
            $goodsDevelopLog = new GoodsDevelopLog();
            $goodsDevelopLog->allowField(true)->save($logs);
            if (16 == $info['process_id']) { // 增加提交日志
                $goodsDevelopLog = new GoodsDevelopLog();
                $logs['remark'] = '';
                $logs['process_id'] = 16;
                $goodsDevelopLog->allowField(true)->save($logs);
            }
            // 提交事务
            Db::commit();
            return $goods_id;
        } catch (Exception $ex) {
            Db::rollBack();
            throw new Exception('添加失败' . $ex->getMessage());
        }
    }

    /**
     * 根据开发人员id找出自己是什么渠道下的
     * @param $user_id
     * @return int
     * @author starzhan <397041849@qq.com>
     */
    private function getChannelIdByDeveloperId($user_id)
    {
        return 1;
    }

    /**
     * 格式数据
     * @param array $data
     * @param array $info
     * @param array $supplier
     * @param array $attributes
     * @return int
     */
    public function formatData($data, &$info, &$supplier, &$attributes, &$description, &$logs, &$source_urls, &$skus, $user_id)
    {
        // 基础信息
        $field = 'name,category_id,brand_id,tort_id,declare_name,declare_en_name,hs_code,weight,net_weight,height,width,depth,volume_weight,is_packing,packing_id,unit_id'
            . ',thumb,alias,retail_price,warehouse_id,is_multi_warehouse,same_weight';
        $fields = explode(',', $field);

        foreach ($fields as $key) {
            $info[$key] = isset($data[$key]) ? $data[$key] : '';
        }
        if (isset($data['process_id']) && $data['process_id'] == 16) {
            $info['process_id'] = 16;
        } else {
            $info['process_id'] = 1;
        }

        if (isset($data['properties'])) {
            $properties = json_decode($data['properties'], true);
            $goodsHelp = new GoodsHelp();
            $info['transport_property'] = $goodsHelp->formatTransportProperty($properties);
            $goodsHelp->checkTransportProperty($info['transport_property']);
        }

        if (isset($data['tags']) && $data['tags']) {
            $tags = json_decode($data['tags']);
            $description['tags'] = '';
            foreach ($tags as $tag) {
                $description['tags'] .= ($description['tags'] ? '\n' : '') . $tag;
            }
        }

        if (isset($data['supplier'])) {
            $supplier = json_decode($data['supplier'], true);
        }

        if (isset($data['description'])) {
            $description['description'] = $data['description'];
            $info['description'] = $data['description'];
        } else {
            $info['description'] = '';
        }
        $description['title'] = $data['name'];

        if (isset($data['remark'])) {
            $logs = ['remark' => $data['remark']];
        }

        if (isset($data['attributes'])) {
            $attributes = json_decode($data['attributes'], true);
        }

        if (isset($data['source_url'])) {
            $source_urls = json_decode($data['source_url'], true);
        }

        $platform_sale = [];
        if (isset($data['platform_sale'])) {
            $platforms = json_decode($data['platform_sale'], true);
            foreach ($platforms as $platform) {
                $platform_sale[$platform['name']] = $platform['value_id'];
            }
        }

        if (isset($data['skus'])) {
            $skus = json_decode($data['skus'], true);
        }
        $info['dev_platform_id'] = 3;
        $info['channel_id'] = $this->getChannelIdByDeveloperId($user_id);
        $info['platform_sale'] = json_encode($platform_sale);
        return 0;
    }

    /**
     * 保存供应商
     * @param int $goods_id
     * @param array $supplier
     * @param int $user_id
     */
    private function saveSupplier($goods_id, $supplier, $user_id)
    {
        if (!isset($supplier['supplier_id']) || empty($supplier['supplier_id'])) {
            $supplierModel = new Supplier();
            $supplier_data = [
                'company_name' => $supplier['company_name'],
                'link' => isset($supplier['link']) ? $supplier['link'] : '',
                'address' => isset($supplier['address']) ? $supplier['address'] : '',
                'tel' => isset($supplier['tel']) ? $supplier['tel'] : '',
                'contacts' => isset($supplier['contacts']) ? $supplier['contacts'] : '',
                'source' => 1,
                'create_time' => time(),
                'update_time' => time(),
                'creator_id' => $user_id
            ];
            $supplierModel->allowField(true)->save($supplier_data);
            $supplier['supplier_id'] = $supplierModel->id;
        }
        $goods_offer = [
            'goods_id' => $goods_id,
            'supplier_id' => $supplier['supplier_id'],
            'price' => $supplier['price'],
            'min_quantity' => isset($supplier['min_quantity']) ? $supplier['min_quantity'] : 1,
            'create_time' => time(),
            'creator_id' => $user_id,
            'update_time' => time()
        ];
        $supplierGoodsOffer = new SupplierGoodsOffer();
        $supplierGoodsOffer->allowField(true)->save($goods_offer);

        $cycle = [
            'goods_id' => $goods_id,
            'warehouse_id' => $supplier['warehouse_id'],
            'delivery_days' => $supplier['delivery_days'],
            'create_time' => time(),
            'update_time' => time(),
            'supplier_id' => $supplier['supplier_id']
        ];
        $goodsWarehouseCycle = new GoodsWarehouseCycle();
        $goodsWarehouseCycle->allowField(true)->save($cycle);
    }

    /**
     * 更新供应商
     * @param int $goods_id
     * @param array $supplier
     * @param int $user_id
     */
    public function updateSupplier($goods_id, $supplier, $user_id)
    {
        $info = SupplierGoodsOffer::where(['goods_id' => $goods_id])->find();
        $flag = true;
        do {
            if (!$info) {
                break;
            }
            // 供应商发生变化
            if (!isset($supplier['supplier_id']) || $supplier['supplier_id'] != $info['supplier_id']) {
                $supplier_count = Supplier::where(['id' => $info['supplier_id'], 'status' => 1, 'source' => 1])->count();
                $supplier_goods_count = SupplierGoodsOffer::where(['supplier_id' => $info['supplier_id'], 'goods_id' => $goods_id, 'status' => 1])->count();
                if ($supplier_count + $supplier_goods_count) {
                    throw new Exception('供应商信息不能修改');
                }
                break;
            }

            // 更新
            SupplierGoodsOffer::where(['goods_id' => $goods_id, 'supplier_id' => $info['supplier_id'], 'status' => 0])
                ->update(['price' => $supplier['price'], 'min_quantity' => $supplier['min_quantity']]);
            GoodsWarehouseCycle::where(['goods_id' => $goods_id, 'supplier_id' => $supplier['supplier_id']])
                ->update(['warehouse_id' => $supplier['warehouse_id'], 'delivery_days' => $supplier['delivery_days']]);
            $flag = false;
        } while (false);

        if ($flag) {
            $this->saveSupplier($goods_id, $supplier, $user_id);
        }

        if ($flag && $info && $info['supplier_id']) {
            Supplier::where(['id' => $info['supplier_id'], 'status' => 0, 'source' => 1])->delete();
            SupplierGoodsOffer::where(['goods_id' => $goods_id, 'supplier_id' => $info['supplier_id'], 'status' => 0])->delete();
            GoodsWarehouseCycle::where(['goods_id' => $goods_id, 'supplier_id' => $info['supplier_id']])->delete();
        }
    }

    /**
     * 获取产品开发基础信息
     * @param int $goods_id
     * @return array
     */
    public function getGoodsdevInfo($goods_id)
    {
        $goods = new GoodsHelp();
        $supplierService = new SupplierService();
        $result = $goods->getBaseInfo($goods_id);
        $result['specification'] = $goods->getAttributeInfo($goods_id, 1);
        $result['skus'] = $goods->getSkuLists($goods_id);
        $result['attributes'] = $goods->getAttributeInfo($goods_id, 0);
        $result['supplier'] = $supplierService->supplierInfoByGoods($goods_id);
        $result['log'] = $goods->getLog($goods_id);
        $description_info = GoodsLang::where(['lang_id' => 1, 'goods_id' => $goods_id])->field('description,tags')->find();
        $result['description'] = $description_info ? $description_info['description'] : '';
        $result['tags'] = isset($description_info['tags']) && !empty($description_info['tags']) ? explode('\n', $description_info['tags']) : [];
        return $result;
    }

    private function goodsInfo($id, $field = "*")
    {
        $model = new Goods();
        $goodsInfo = $model->field($field)->where('id', $id)->find();
        return $goodsInfo;
    }

    private function goodsDevInfo($id, $field = "*")
    {
        $modeGoodsDev = new ModelGoodsDev();
        return $modeGoodsDev->field($field)->where('goods_id', $id)->find();
    }

    /**
     * 获取供应商信息
     * @param int $goods_id
     * @return array
     */
    public function getSupplierInfo($goods_id)
    {
        $result = [
            'purchaser_id' => 0,
            'supplier_id' => 0,
            'cost_price' => 0.00,
            'min_quantity' => 0,
            'delivery_days' => 0,
            'contacts' => '',
            'tel' => ''
        ];
        $field = 'purchaser_id,supplier_id,cost_price';
        $goodsInfo = $this->goodsInfo($goods_id, $field);
        if (!$goodsInfo) {
            return $result;
        }
        $result['purchaser_id'] = $goodsInfo['purchaser_id'];
        $result['supplier_id'] = $goodsInfo['supplier_id'];
        $result['cost_price'] = $goodsInfo['cost_price'];
        $field = 'min_quantity,delivery_days';
        $goodsDevInfo = $this->goodsDevInfo($goods_id, $field);
        if ($goodsDevInfo) {
            $result['min_quantity'] = $goodsDevInfo['min_quantity'];
            $result['delivery_days'] = $goodsDevInfo['delivery_days'];
        }
        if ($result['supplier_id']) {
            $supplierInfo = SupplierService::getInfoById($result['supplier_id'], ['contacts', 'tel']);
            if ($supplierInfo) {
                $result['contacts'] = $supplierInfo['contacts'];
                $result['tel'] = $supplierInfo['tel'];
            }
        }
        return $result;
    }

    /**
     * @title 保存供应商
     * @param $goods_id
     * @param $param
     * @author starzhan <397041849@qq.com>
     */
    public function saveSupplierInfo($goods_id, $param)
    {
        try {
            $goods = [];
            $goods_dev = [];
            if (isset($param['purchaser_id']) && $param['purchaser_id']) {
                $goods['purchaser_id'] = $param['purchaser_id'];
            }
            if (isset($param['supplier_id']) && $param['supplier_id']) {
                $goods['supplier_id'] = $param['supplier_id'];
            }
            if (isset($param['min_quantity']) && $param['min_quantity']) {
                $goods_dev['min_quantity'] = $param['min_quantity'];
            }
            if (isset($param['delivery_days']) && $param['delivery_days']) {
                $goods_dev['delivery_days'] = $param['delivery_days'];
            }
            if ([] === $goods && [] === $goods_dev) {
                throw new Exception('无内容更新');
            }
            Db::startTrans();
            try {
                if ($goods) {
                    $modelGoods = new Goods();
                    $modelGoods->allowField(true)
                        ->isUpdate(true)
                        ->save($goods, ['id' => $goods_id]);
                }
                if ($goods_dev) {
                    $modelGoodsDev = new ModelGoodsDev();
                    $modelGoodsDev->allowField(true)
                        ->isUpdate(true)
                        ->save($goods_dev, ['goods_id' => $goods_id]);
                }
                Db::commit();
                return ['message' => '保存成功!'];
            } catch (Exception $e) {
                Db::rollback();
                throw $e;
            }
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * 更新产品开发
     * @param int $goods_id
     * @param array $data
     * @param int $user_id
     * @throws Exception
     * @return array
     */
    public function updateGoodsdev($goods_id, $data, $user_id)
    {
        $supplier = [];
        $info = [];
        $attributes = [];
        $description = [];
        $source_urls = [];
        $logs = [];
        $skus = [];
        $this->formatData($data, $info, $supplier, $attributes, $description, $logs, $source_urls, $skus, $user_id);
        $info['id'] = $goods_id;
        $goodsValidate = validate('goods');
        if (!$goodsValidate->scene('update')->check($info)) {
            throw new Exception($goodsValidate->getError());
        }
        // 开启事务
        Db::startTrans();
        try {
            // 更新产品详情
            $goodsHelp = new GoodsHelp();
            Goods::where(['status' => 0, 'id' => $goods_id])->update($info);
            // 添加属性
            if ($attributes) {
                $goodsHelp->modifyAttribute($goods_id, $attributes);
            }
            // 添加供应商
            if ($supplier) {
                $this->updateSupplier($goods_id, $supplier, $user_id);
            }

            // 添加参考链接
            if ($source_urls) {
                $goodsHelp = new GoodsHelp();
                $goodsHelp->saveSourceUrls($goods_id, $source_urls, $user_id);
            }

            // 添加描述
            if ($description) {
                $description['goods_id'] = $goods_id;
                $description['lang_id'] = 1;
                $goodsLang = new GoodsLang();
                if ($goodsLang->where(['goods_id' => $goods_id, 'lang_id' => 1])->count()) {
                    $goodsLang->allowField(true)->where(['goods_id' => $goods_id, 'lang_id' => 1])->update($description);
                    \think\Log::write($description);
                } else {
                    $goodsLang->allowField(true)->isUpdate(false)->save($description);
                }
            }

            // 保存sku
            if ($skus) {
                $goodsHelp = new GoodsHelp();
                $goodsHelp->saveSkuInfo($goods_id, $skus, false);
            }

            if (16 == $info['process_id'] || $logs) { // 增加提交日志
                $goodsDevelopLog = new GoodsDevelopLog();
                $logs['goods_id'] = $goods_id;
                $logs['process_id'] = $info['process_id'] ? $info['process_id'] : 0;
                $logs['create_time'] = time();
                $logs['operator_id'] = $user_id;
                $goodsDevelopLog->allowField(true)->save($logs);
            }
            // 提交事务
            Db::commit();
            return ['message' => '操作成功'];
        } catch (Exception $ex) {
            Db::rollBack();
            throw new Exception($ex->getMessage());
        }
    }

    /**
     * @title 注释..
     * @param $param
     * @return $this|Goods
     * @author starzhan <397041849@qq.com>
     */
    public function _getWhere($param, $user_id)
    {
        $o = new Goods();
        $o = $o->where('g.status', 0)->alias('g');
        if (isset($param['process_id']) && $param['process_id']) {
            $where = $this->process_btn[$param['process_id']]['where'];
            $o = $o->where($where);
        }
        if (isset($param['status']) && $param['status']) {

            switch ($param['status']) {
                case '1':
                    $aUserId = [$user_id];
                    $userInfo = Cache::store('user')->getOneUser($user_id);
                    if (isset($userInfo['job']) && $userInfo['job']) {
                        $JobService = new JobService();
                        $jobIb = $JobService->getIdByCode($userInfo['job']);
                        if ($jobIb) {
                            $aUserId[] = $jobIb * 5000000;
                            $DepartmentUserMapService = new DepartmentUserMapService();
                            $workIds = $DepartmentUserMapService->getWorkId($userInfo['id']);
                            foreach ($workIds as $workId) {
                                $aUserId[] = $jobIb * 5000000 + $workId * 50000;
                            }
                        }
                    }
                    if ($aUserId) {
                        $ServiceGoodsNodeProcess = new ServiceGoodsNodeProcess();
                        $aGoodsId = $ServiceGoodsNodeProcess->searchByCurrentNode($aUserId);
                        $o = $o->where('id', 'in', $aGoodsId);
                    } else {
                        $o = $o->where('id', '-1');
                    }
                    break;
                case '2':
                    $aUserId = [$user_id];
                    $userInfo = Cache::store('user')->getOneUser($user_id);
                    if (isset($userInfo['job']) && $userInfo['job']) {
                        $JobService = new JobService();
                        $jobIb = $JobService->getIdByCode($userInfo['job']);
                        if ($jobIb) {
                            $aUserId[] = $jobIb * 5000000;
                            $DepartmentUserMapService = new DepartmentUserMapService();
                            $workIds = $DepartmentUserMapService->getWorkId($userInfo['id']);
                            foreach ($workIds as $workId) {
                                $aUserId[] = $jobIb * 5000000 + $workId * 50000;
                            }
                        }
                    }
                    if ($aUserId) {
                        $ServiceGoodsNodeProcess = new ServiceGoodsNodeProcess();
                        $aGoodsId = $ServiceGoodsNodeProcess->searchByCurrentNode($aUserId);
                        $o = $o->where('id', 'not in', $aGoodsId);
                    } else {
                        $o = $o->where('id', '-1');
                    }
                    break;
            }

        }
        if (isset($param['snType']) && $param['snType'] && !empty($param['snText'])) {
            $param['snText'] = trim($param['snText']);
            switch ($param['snType']) {
                case 'name':
                    $o = $o->where('g.name', 'like', $param['snText'] . '%');
                    break;
                case 'spu':
                    $o = $o->where('g.spu', '=', $param['snText']);
                    break;
                case  'sku':
                    $o = $o->join('goods_sku s', "g.id=s.goods_id", 'left');
                    $o = $o->where("s.sku", '=', $param['snText']);
                    break;
            }
        }
        if (isset($param['developer_id']) && $param['developer_id']) {
            $o = $o->where("g.developer_id", '=', $param['developer_id']);
        }
        if (isset($param['create_time_st']) && $param['create_time_st']) {
            $create_time_st = strtotime($param['create_time_st']);
            if ($create_time_st) {
                $o = $o->where('create_time', '>=', $create_time_st);
            }

        }
        if (isset($param['create_time_nd']) && $param['create_time_nd']) {
            $create_time_nd = strtotime($param['create_time_nd'] . " 23:59:59");
            $o = $o->where('create_time', '<=', $create_time_nd);
        }
        return $o;
    }

    /**
     * 获取产品开发列表
     * @param array $where
     * @param int $page
     * @param int $pageSize
     * @param string $field
     * @param string $order
     * @return array
     */
    public function index($page, $pageSize, $param, $user_id)
    {
        $result = ['list' => []];
        $result['page'] = $page;
        $result['page_size'] = $pageSize;
        $result['count'] = $this->_getWhere($param, $user_id)->count();
        if ($result['count'] == 0) {
            return $result;
        }
        $field = 'g.id,g.spu,g.category_id,g.name,g.create_time,g.process_id,g.developer_id';

        $ret = $this->_getWhere($param, $user_id)->page($page, $pageSize)->field($field)->order('id', 'desc')->select();
        $result['list'] = $this->fillData($ret);
        return $result;
    }

    public function fillData($ret)
    {
        $result = [];
        foreach ($ret as $v) {
            $row = [];
            $row['id'] = $v['id'];
            $row['name'] = $v['name'];
            $row['category_name'] = $v->Category;
            $row['developer'] = $v->developer;
            $row['process_id'] = $v->process_id;
            $row['create_time'] = $v->create_time;
            $row['spu'] = $v->spu;
            $row['status_txt'] = $this->processes[$v->process_id]['name'] ?? '';
            $result[] = $row;
        }
        return $result;
    }

    // 流程详情
    private $processes = [
        '1' => ['process_id' => 1, 'name' => '未提交', 'action' => ['submit','audit_close'], 'btn' => '创建', 'curr_node' => 'developer'],//0 1
        '16' => ['process_id' => 16, 'name' => '待审核', 'action' => ['audit_success', 'audit_fail', 'audit_close'], 'btn' => '提交审核', 'curr_node' => 'leader'],//1 0
        '17' => ['process_id' => 17, 'name' => '审核未通过', 'action' => ['cancel', 'submit'], 'btn' => '审核不通过', 'curr_node' => 'developer', 'back' => [17]],//1
        '32' => ['process_id' => 32, 'name' => '等待收样', 'action' => ['recSample', 'audit_close'], 'btn' => '采样下单', 'curr_node' => 'developer'],
        '128' => ['process_id' => 128, 'name' => '待分配质检', 'action' => ['audit_close'], 'btn' => '确认'],
        '129' => ['process_id' => 129, 'name' => '待质检', 'action' => ['audit_close'], 'btn' => '接收样品', 'curr_node' => 'inspector'],
        '130' => ['process_id' => 130, 'name' => '质检中', 'action' => ['qc_audit_success', 'qc_audit_fail', 'audit_close'], 'btn' => '接收样品', 'curr_node' => 'inspector'],
        '131' => ['process_id' => 131, 'name' => '质检未通过', 'action' => ['submit', 'cancel'], 'btn' => '开始质检', 'curr_node' => 'developer', 'back' => [128, 129, 130, 131]],
        '512' => ['process_id' => 512, 'name' => '待SKU生成', 'action' => ['audit_close'], 'btn' => '质检确认通过', 'curr_node' => 'developer', 'back' => [512]],
        '1024' => ['process_id' => 1024, 'name' => '待完善报关信息', 'action' => ['audit_close'], 'btn' => '质检确认通过', 'curr_node' => 'developer'],
        '2048' => ['process_id' => 2048, 'name' => '待分配拍图', 'action' => ['audit_close'], 'btn' => '拍照', 'curr_node' => 'grapher_mater'],
        '2049' => ['process_id' => 2049, 'name' => '待拍图', 'action' => ['audit_close'], 'btn' => '拍照', 'curr_node' => 'grapher'],
        '2050' => ['process_id' => 2050, 'name' => '拍图中', 'action' => ['photo_submit', 'audit_close'], 'btn' => '拍照', 'curr_node' => 'grapher'],
        '2051' => ['process_id' => 2051, 'name' => '拍图待审核', 'action' => ['photo_audit_success', 'photo_audit_fail', 'audit_close'], 'btn' => '拍照完成', 'curr_node' => 'grapher_mater'],
        '2052' => ['process_id' => 2052, 'name' => '拍图未通过', 'action' => ['photo_submit', 'audit_close'], 'btn' => '拍照审核不通过', 'curr_node' => 'grapher', 'back' => [2048, 2049, 2050, 2051, 2052]],
        '8192' => ['process_id' => 8192, 'name' => '待分配翻译', 'action' => ['audit_close'], 'btn' => '', 'curr_node' => 'translator_master'],
        '8193' => ['process_id' => 8193, 'name' => '待翻译', 'action' => ['audit_close'], 'btn' => '', 'curr_node' => 'translator'],
        '8194' => ['process_id' => 8194, 'name' => '翻译中', 'action' => ['translator_submit', 'audit_close'], 'btn' => '翻译分配', 'curr_node' => 'translator'],
        '8195' => ['process_id' => 4099, 'name' => '翻译待审核', 'action' => ['translator_audit_success', 'translator_audit_fail', 'audit_close'], 'btn' => '翻译完成', 'curr_node' => 'translator_master'],
        '8196' => ['process_id' => 4100, 'name' => '翻译未通过', 'action' => ['translator_submit', 'audit_close'], 'btn' => '翻译完成', 'curr_node' => 'translator', 'back' => [8192, 8193, 8194, 8195, 8196]],

        '32768' => ['process_id' => 32768, 'name' => '待分配修图', 'action' => ['audit_close'], 'btn' => '拍照审核通过', 'curr_node' => 'designer_master'],
        '32769' => ['process_id' => 32769, 'name' => '待修图', 'action' => ['audit_close'], 'btn' => '拍照审核通过', 'curr_node' => 'designer'],
        '32770' => ['process_id' => 32770, 'name' => '修图中', 'action' => ['ps_submit', 'audit_close'], 'btn' => '分配修图', 'curr_node' => 'designer'],
        '32771' => ['process_id' => 32771, 'name' => '修图待审核', 'action' => ['ps_audit_success', 'ps_audit_fail', 'audit_close'], 'btn' => '修图完成', 'curr_node' => 'designer_master'],
        '32772' => ['process_id' => 32772, 'name' => '修图审核未通过', 'action' => ['ps_submit', 'audit_close'], 'btn' => '修图审核通过', 'curr_node' => 'designer', 'back' => [32768, 32769, 32770, 32771, 32772]],


        '131072' => ['process_id' => 131072, 'name' => '待开发复审', 'action' => ['final_submit', 'final_audit_fail', 'audit_close'], 'btn' => '', 'curr_node' => 'developer', 'back' => [131072]],
        '262144' => ['process_id' => 262144, 'name' => '待发布', 'action' => ['release', 'release_audit_fail', 'audit_close'], 'btn' => '', 'curr_node' => 'leader'],
        '524288' => ['process_id' => 524288, 'name' => '已发布', 'action' => [], 'btn' => ''],
        '1073741824' => ['process_id' => 1073741824, 'name' => '已作废', 'action' => [], 'btn' => '作废']
    ];

    // 操作详情
    private $actions = [
        'submit' => ['btn_name' => '提交审核', 'url' => '', 'next_process' => 16, 'prefix_process' => 0xfffffff0, 'remark' => false, 'batch' => true, 'agree' => true], // 待审核
        'cancel' => ['btn_name' => '作废', 'url' => '', 'next_process' => 1073741824, 'prefix_process' => 0xfffffffff, 'remark' => true, 'log_process' => 0x40000000],            // 作废
        'audit_success' => ['btn_name' => '审核通过', 'url' => '', 'next_process' => 32, 'prefix_process' => 0xfffffff0, 'remark' => false, 'batch' => true, 'agree' => true], // 审核成功
        'audit_fail' => ['btn_name' => '审核未通过', 'url' => '', 'next_process' => 17, 'prefix_process' => 0xfffffff1, 'remark' => true],  // 审核不通过

        'recSample' => ['btn_name' => '收到样品', 'url' => '', 'next_process' => 129, 'prefix_process' => 0xfffffff0, 'batch' => true, 'agree' => true],    // 待质检

        'qc_audit_success' => ['btn_name' => '审核通过', 'url' => '', 'next_process' => 512, 'prefix_process' => 0xfffffff0, 'remark' => false, 'batch' => true, 'agree' => true], // 审核成功
        'qc_audit_fail' => ['btn_name' => '审核未通过', 'url' => '', 'next_process' => 131, 'prefix_process' => 0xfffffff1, 'remark' => true],  // 审核不通过

        'generate_sku' => ['btn_name' => '生成sku', 'url' => 'goodsdev/:id/generate-sku', 'next_process' => 1024, 'prefix_process' => 0xfffffff1, 'remark' => false],  // 审核不通过
        'submit_declare' => ['btn_name' => '保存报关', 'url' => 'goodsdev:id/declare', 'next_process' => 2048, 'prefix_process' => 0xfffffff1, 'remark' => false],
        'set_grapher' => ['btn_name' => '分配摄影师', 'url' => 'goodsdev/:id/set-grapher', 'next_process' => 2049, 'prefix_process' => 0xfffffff1, 'remark' => false],
        'start_photo' => ['btn_name' => '开始拍照', 'url' => 'goodsdev/:id/start-photo', 'next_process' => 2050, 'prefix_process' => 0xfffffff1, 'remark' => false],

        'audit_close' => ['btn_name' => '关闭', 'url' => 'javescript', 'next_process' => 0, 'prefix_process' => 0xfffffff0, 'remark' => true],
        'photo_submit' => ['btn_name' => '提交审核', 'url' => '', 'next_process' => 2051, 'prefix_process' => 0xfffffff0, 'remark' => false, 'agree' => true],

        'photo_audit_success' => ['btn_name' => '审核通过', 'url' => '', 'next_process' => 8192, 'prefix_process' => 0xfffffff0, 'remark' => false, 'batch' => true, 'agree' => true], // 审核成功
        'photo_audit_fail' => ['btn_name' => '审核未通过', 'url' => '', 'next_process' => 2052, 'prefix_process' => 0xfffffff1, 'remark' => true],  // 审核不通过

        'set_translator' => ['btn_name' => '设置翻译人员', 'url' => 'goodsdev/:id/set-translator', 'next_process' => 8193, 'prefix_process' => 0xfffffff1, 'remark' => false],  // 审核不通过,
        'start_translator' => ['btn_name' => '开始翻译', 'url' => 'goodsdev/:id/translator-starting', 'next_process' => 8194, 'prefix_process' => 0xfffffff1, 'remark' => false],  // 审核不通过,
        'translator_submit' => ['btn_name' => '提交审核', 'url' => 'goodsdev/:id/:lang_id/translator-submit', 'next_process' => 8195, 'prefix_process' => 0xfffffff0, 'remark' => true, 'agree' => true],
        'translator_audit_success' => ['btn_name' => '审核通过', 'url' => '', 'next_process' => 32768, 'prefix_process' => 0xfffffff0, 'remark' => false, 'batch' => true, 'agree' => true], // 审核成功
        'translator_audit_fail' => ['btn_name' => '审核未通过', 'url' => 'goodsdev/:id/translator-back', 'next_process' => 8196, 'prefix_process' => 0xfffffff1, 'remark' => true],  // 审核不通过

        'designer_setting' => ['btn_name' => '指定美工', 'url' => 'goodsdev/:id/designer-setting', 'next_process' => 32769, 'prefix_process' => 0xfffffff1, 'remark' => false],  // 审核不通过
        'designer_starting' => ['btn_name' => '开始修图', 'url' => 'goodsdev/:id/designer-starting', 'next_process' => 32770, 'prefix_process' => 0xfffffff1, 'remark' => false],  // 审核不通过
        'ps_submit' => ['btn_name' => '提交审核', 'url' => '', 'next_process' => 32771, 'prefix_process' => 0xfffffff0, 'remark' => false, 'agree' => true],
        'ps_audit_success' => ['btn_name' => '审核通过', 'url' => '', 'next_process' => 131072, 'prefix_process' => 0xfffffff0, 'remark' => false, 'batch' => true, 'agree' => true], // 审核成功
        'ps_audit_fail' => ['btn_name' => '审核未通过', 'url' => '', 'next_process' => 32772, 'prefix_process' => 0xfffffff1, 'remark' => true],  // 审核不通过

        'final_submit' => ['btn_name' => '提交终审', 'url' => '', 'next_process' => 262144, 'prefix_process' => 0xfffffff0, 'remark' => false, 'batch' => true, 'agree' => true], // 待审核
        'final_audit_fail' => ['btn_name' => '审核未通过', 'url' => 'javescript', 'next_process' => 0, 'prefix_process' => 0xfffffff1, 'remark' => true],  // 审核不通过

        'release' => ['btn_name' => '发布产品', 'url' => 'goodsdev/:id/release', 'next_process' => 0x10, 'prefix_process' => 0xfffffff0, 'remark' => false, 'agree' => true], // 待审核
        'release_audit_fail' => ['btn_name' => '审核未通过', 'url' => 'javescript', 'next_process' => 0x10, 'prefix_process' => 0xfffffff0, 'remark' => false], // 待审核


    ];

    private $process_btn = [
        0 => ['btn_name' => '全部', 'where' => []],
        1 => ['btn_name' => '未提交', 'where' => ['g.process_id' => 1]],    // 0001 创建成功
        16 => ['btn_name' => '待审核', 'where' => ['g.process_id' => 16]],   // 提交成功 1 0000
        17 => ['btn_name' => '审核未通过', 'where' => ['g.process_id' => 17]],// 审核不通过 1 0001
        32 => ['btn_name' => '等待收样', 'where' => ['g.process_id' => 48]],
        128 => ['btn_name' => '待分配质检', 'where' => ['g.process_id' => 176]],
        129 => ['btn_name' => '待质检', 'where' => ['g.process_id' => 176]],
        130 => ['btn_name' => '质检中', 'where' => ['g.process_id' => 176]],
        131 => ['btn_name' => '质检未通过', 'where' => ['g.process_id' => 177]],
        512 => ['btn_name' => '待SKU生成', 'where' => ['g.process_id' => 512]],
        1024 => ['btn_name' => '待完善报关信息', 'where' => ['g.process_id' => 1024]],
        2048 => ['btn_name' => '待分配拍图', 'where' => ['g.process_id' => 2048]],
        2049 => ['btn_name' => '待拍图', 'where' => ['g.process_id' => 2049]],
        2050 => ['btn_name' => '拍图中', 'where' => ['g.process_id' => 2050]],
        2051 => ['btn_name' => '拍图待审核', 'where' => ['g.process_id' => 2051]],
        2052 => ['btn_name' => '拍图未通过', 'where' => ['g.process_id' => 2052]],
        8192 => ['btn_name' => '待分配翻译', 'where' => ['g.process_id' => 8192]],
        8193 => ['btn_name' => '待翻译', 'where' => ['g.process_id' => 8193]],
        8194 => ['btn_name' => '翻译中', 'where' => ['g.process_id' => 8194]],
        8195 => ['btn_name' => '翻译待审核', 'where' => ['g.process_id' => 8195]],
        8196 => ['btn_name' => '翻译未通过', 'where' => ['g.process_id' => 8196]],
        32768 => ['btn_name' => '待分配修图', 'where' => ['g.process_id' => 32768]],
        32769 => ['btn_name' => '待修图', 'where' => ['g.process_id' => 32769]],
        32770 => ['btn_name' => '修图中', 'where' => ['g.process_id' => 32770]],
        32771 => ['btn_name' => '修图待审核', 'where' => ['g.process_id' => 32771]],
        32772 => ['btn_name' => '修图未通过', 'where' => ['g.process_id' => 32772]],
        131072 => ['btn_name' => '待开发复审', 'where' => ['g.process_id' => 131072]],
        262144 => ['btn_name' => '待发布', 'where' => ['g.process_id' => 262144]],
        524288 => ['btn_name' => '已发布', 'where' => ['g.process_id' => 524288]],
        1073741824 => ['btn_name' => '已作废', 'where' => ['g.process_id' => 1073741824]]
    ];

    /*
     * 解析分解流程号
     * @param int $process_id
     * @return int
     */
    private function analyseProcess($process_id)
    {
        $bit = 0;
        $bin = decbin($process_id);
        $length = strlen($bin);
        switch ($length) {
            case 31:
                $bit = pow(2, 30); // 已作废
                break;
            case 30:
                $bit = pow(2, 29); // 待上架
                break;
            case 19:
                $bit = pow(2, 18); // 待上架
                break;
            case 17:
                $bit = 65536; // 翻译完成
                $process_id = $process_id & 0x3fff;
                $sec_bit = $this->analyseProcess($process_id);
                $bit .= ',';
                $bit .= $sec_bit;
                break;
            case 16:
                $bit = 32768; // 翻译中
                if (($process_id & 15) == 1) $bit = 32769;
                elseif (($process_id & 15) == 2) $bit = 32770; // 拍图完成,待审核
                elseif (($process_id & 15) == 3) $bit = 32771; // 拍图完成,待审核
                elseif (($process_id & 15) == 4) $bit = 32772; // 拍图完成,待审核
//
                break;
            case 15:
                $bit = 16384; //待翻译分配
//                $process_id = $process_id & 0x03fff;
//                $sec_bit = $this->analyseProcess($process_id);
//
//                $bit .= ',';
//                $bit .= $sec_bit;
                break;
            case 14:
                $bit = 8192; // 图片完成（上传完成）
                if (($process_id & 15) == 1) $bit = 8193;
                elseif (($process_id & 15) == 2) $bit = 8194; // 拍图完成,待审核
                elseif (($process_id & 15) == 3) $bit = 8195; // 拍图不通过
                elseif (($process_id & 15) == 4) $bit = 8196; // 拍图通过, 待上传
                break;
            case 13:
                $bit = 4096; // 拍图通过, 待分配修图,不拍图也是
                if (($process_id & 15) == 1) $bit = 4097; // 修图中（修图分配完成)
                elseif (($process_id & 15) == 2) $bit = 4098; // 拍图完成,待审核
                elseif (($process_id & 15) == 3) $bit = 4099; // 拍图不通过
                elseif (($process_id & 15) == 4) $bit = 4100; // 拍图通过, 待上传
                break;
            case 12:
                $bit = 2048; // 待接样
                if (($process_id & 15) == 1) $bit = 2049; // 接样成功，待拍图
                elseif (($process_id & 15) == 2) $bit = 2050; // 拍图完成
                elseif (($process_id & 15) == 3) $bit = 2051; // 拍图不通过
                elseif (($process_id & 15) == 4) $bit = 2052; //
                break;
            case 11: // sku已生成
                $bit = 1024;
                break;
            case 10: // 供应商审核通过
                $bit = 512;
                break;
            case 9: // 质检通过
                $bit = 256; // 质检通过
                if (($process_id & 15) == 1) $bit = 257; // 不取样
                if (($process_id & 15) == 2) $bit = 258; // 供应商审核失败
                break;
            case 8:
                $bit = 128; // 待分配质检
                if (($process_id & 15) == 1) $bit = 129; // 待质检
                if (($process_id & 15) == 2) $bit = 130; // 质检失败
                break;
            case 7: // 预留
                // $bit = 64;
                break;
            case 6:// 审核通过
                $bit = 32;
                if (($process_id & 15) == 1) $bit = 33; // 取样下单
                break;
            case 5: // 已提交-待审核
                $bit = 16;
                if (($process_id & 15) == 1) $bit = 17;// 审核失败
                break;
            case 1: // 未提交
                $bit = 1;
                break;
        }
        return $bit;
    }

    /**
     * 页面获取按钮
     * @return array
     */
    public function getProcessBtn()
    {
        $lists = [];
        foreach ($this->process_btn as $process_id => $v) {
            $lists[] = [
                'process_id' => $process_id,
                'btn_name' => $v['btn_name']
            ];
        }

        return $lists;
    }

    /**
     * 获取流程处理操作名称 根据流程Id
     * @param int $process_id
     * @return string
     */
    public function getProcessBtnNameById($process_id)
    {
        return isset($this->processes[$process_id]) ? $this->processes[$process_id]['btn'] : '';
    }

    /**
     * 获取流程名称 根据流程Id
     * @param int $process_id
     * @return string
     */
    public function getProcessNameById($process_id)
    {

        $name = isset($this->processes[$process_id]) ? $this->processes[$process_id]['name'] : '';
        return $name;
    }

    /**
     * 获取流程处理按钮 根据process　id
     * @param int $id
     * @param int $level
     * @return array $action
     */
    public function getProcessBtnById($id, $level = 0)
    {
        $actions = [];
        $id = $this->analyseProcess($id);
        $ids = explode(',', $id);
        foreach ($ids as $id) {
            if (!isset($this->processes[$id])) {
                continue;
            }
            foreach ($this->processes[$id]['action'] as $action) {
                if (isset($this->actions[$action]) && (!$level || !empty($this->actions[$action]['batch']))) {
                    $actions[] = [
                        'btn_name' => $this->actions[$action]['btn_name'],
                        'url' => isset($this->actions[$action]['url']) && !empty($this->actions[$action]['url']) ? $this->actions[$action]['url'] : 'goodsdev/process/:id',
                        'remark' => isset($this->actions[$action]['remark']) ? $this->actions[$action]['remark'] : false,
                        'code' => $action,
                        'execute' => isset($this->actions[$action]['execute']) ? $this->actions[$action]['execute'] : false,
                    ];
                }
            }
        }
        return $actions;
    }


    public function same2NodeProcess($goodsInfo, $next_process_id, $user_id, $intro = '')
    {

        $this_process_id = $goodsInfo['process_id'];
        $next_process = $this->processes[$next_process_id];
        $ServiceGoodsNodeProcess = new ServiceGoodsNodeProcess();
        Db::startTrans();
        $result = '';
        try {
            $ServiceGoodsNodeProcess->finishThisProcess($goodsInfo['id'], $this_process_id, $user_id, $intro);
            if (!isset($next_process['curr_node'])) {
                $next_process['curr_node'] = 'none';
            }
//            if ($this_process_id == 1) {//生成spu
//                $this->generateSpu($goodsInfo);
//            }
            switch ($next_process['curr_node']) {
                case 'developer':
                    $flag = $ServiceGoodsNodeProcess->updateStatusOldCurrByUserId($next_process_id, $goodsInfo['developer_id']);
                    if (!$flag) {
                        $infoData = [];
                        $infoData['goods_id'] = $goodsInfo['id'];
                        $infoData['process_id'] = $next_process_id;
                        $infoData['current_user_id'] = $goodsInfo['developer_id'];
                        $infoData['intro'] = '';
                        $infoData['current_job_id'] = 0;
                        $infoData['current_work_id'] = 0;
                        $ServiceGoodsNodeProcess->insert($infoData);
                    }
                    $userInfo = Cache::store('user')->getOneUser($goodsInfo['developer_id']);
                    $result = $userInfo['realname'];
                    break;
                case 'leader':
                    $leader = $this->getLeader($goodsInfo['developer_id']);
                    $flag = $ServiceGoodsNodeProcess->updateStatusOldCurrByUserId($next_process_id, $leader);
                    $tmpNodeName = [];
                    if (!$flag) {
                        foreach ($leader as $lid){
                            $infoData = [];
                            $infoData['goods_id'] = $goodsInfo['id'];
                            $infoData['process_id'] = $next_process_id;
                            $infoData['current_user_id'] = $lid;
                            $infoData['intro'] = '';
                            $infoData['current_job_id'] = 0;
                            $infoData['current_work_id'] = 0;
                            $ServiceGoodsNodeProcess->insert($infoData);
                            $userInfo = Cache::store('user')->getOneUser($lid);
                            $tmpNodeName[] = $userInfo['realname'];
                        }

                    }
                    $result = implode('、',$tmpNodeName);
                    break;
                case 'inspector':
                    $JobService = new JobService();
                    $jobIb = $JobService->getId('inspector');
                    if (!$jobIb) {
                        throw new Exception('质检员获取失败，请检查职位设置');
                    }
                    $flag = $ServiceGoodsNodeProcess->updateStatusOldCurrByJobId($next_process_id, $jobIb);
                    if (!$flag) {
                        $infoData = [];
                        $infoData['goods_id'] = $goodsInfo['id'];
                        $infoData['process_id'] = $next_process_id;
                        $infoData['current_user_id'] = 0;
                        $infoData['intro'] = '';
                        $infoData['current_job_id'] = $jobIb;
                        $infoData['current_work_id'] = 0;
                        $ServiceGoodsNodeProcess->insert($infoData);
                    }
                    $result = '质检员';
                    break;
                case 'grapher_mater':
                    $JobService = new JobService();
                    $jobIb = $JobService->getIdByCode('grapher');
                    if (!$jobIb) {
                        throw new Exception('摄影获取失败，请检查职位设置');
                    }
                    $workId = $JobService->getIdByCode('groupLeader');
                    if (!$workId) {
                        throw new Exception('组长获取失败，请检查职位设置');
                    }
                    $flag = $ServiceGoodsNodeProcess->updateStatusOldCurrByWorkId($next_process_id, $jobIb, $workId);
                    if (!$flag) {
                        $infoData = [];
                        $infoData['goods_id'] = $goodsInfo['id'];
                        $infoData['process_id'] = $next_process_id;
                        $infoData['current_user_id'] = 0;
                        $infoData['intro'] = '';
                        $infoData['current_job_id'] = $jobIb;
                        $infoData['current_work_id'] = $workId;
                        $ServiceGoodsNodeProcess->insert($infoData);
                    }
                    $result = '摄影组长';
                    break;
                case 'grapher':
                    $grapher = $goodsInfo['grapher'];
                    $flag = $ServiceGoodsNodeProcess->updateStatusOldCurrByUserId($next_process_id, $grapher);
                    if (!$flag) {
                        $infoData = [];
                        $infoData['goods_id'] = $goodsInfo['id'];
                        $infoData['process_id'] = $next_process_id;
                        $infoData['current_user_id'] = $grapher;
                        $infoData['intro'] = '';
                        $infoData['current_job_id'] = 0;
                        $infoData['current_work_id'] = 0;
                        $ServiceGoodsNodeProcess->insert($infoData);
                    }
                    $userInfo = Cache::store('user')->getOneUser($grapher);
                    $result = $userInfo['realname'];
                    break;
                case 'translator_master':
                    $JobService = new JobService();
                    $jobIb = $JobService->getIdByCode('translator');
                    if (!$jobIb) {
                        throw new Exception('翻译员获取失败，请检查职位设置');
                    }
                    $workId = $JobService->getIdByCode('groupLeader');
                    if (!$workId) {
                        throw new Exception('组长获取失败，请检查职位设置');
                    }
                    $flag = $ServiceGoodsNodeProcess->updateStatusOldCurrByWorkId($next_process_id, $jobIb, $workId);
                    if (!$flag) {
                        $infoData = [];
                        $infoData['goods_id'] = $goodsInfo['id'];
                        $infoData['process_id'] = $next_process_id;
                        $infoData['current_user_id'] = 0;
                        $infoData['intro'] = '';
                        $infoData['current_job_id'] = $jobIb;
                        $infoData['current_work_id'] = $workId;
                        $ServiceGoodsNodeProcess->insert($infoData);
                    }
                    $result = '翻译组长';
                    break;
                case 'translator':
                    $translatorList = json_decode($goodsInfo['translator'], true);
                    $aName = [];
                    foreach ($translatorList as $translatorInfo) {
                        $flag = $ServiceGoodsNodeProcess->updateStatusOldCurrByUserId($next_process_id, $translatorInfo['translator']);
                        if (!$flag) {
                            $infoData = [];
                            $infoData['goods_id'] = $goodsInfo['id'];
                            $infoData['process_id'] = $next_process_id;
                            $infoData['current_user_id'] = $translatorInfo['translator'];
                            $infoData['intro'] = $translatorInfo['lang'];
                            $infoData['current_job_id'] = 0;
                            $infoData['current_work_id'] = 0;
                            $ServiceGoodsNodeProcess->insert($infoData);
                        }
                        $aName[] = $this->getRealName($translatorInfo['translator']);
                    }
                    $result = implode(',', $aName);
                    break;
                case 'designer_master':
                    $JobService = new JobService();
                    $jobIb = $JobService->getIdByCode('designer');
                    if (!$jobIb) {
                        throw new Exception('美工获取失败，请检查职位设置');
                    }
                    $workId = $JobService->getIdByCode('groupLeader');
                    if (!$workId) {
                        throw new Exception('组长获取失败，请检查职位设置');
                    }
                    $flag = $ServiceGoodsNodeProcess->updateStatusOldCurrByWorkId($next_process_id, $jobIb, $workId);
                    if (!$flag) {
                        $infoData = [];
                        $infoData['goods_id'] = $goodsInfo['id'];
                        $infoData['process_id'] = $next_process_id;
                        $infoData['current_user_id'] = 0;
                        $infoData['intro'] = '';
                        $infoData['current_job_id'] = $jobIb;
                        $infoData['current_work_id'] = $workId;
                        $ServiceGoodsNodeProcess->insert($infoData);
                    }
                    $result = '美工组长';
                    break;
                case 'designer':
                    $flag = $ServiceGoodsNodeProcess->updateStatusOldCurrByUserId($next_process_id, $goodsInfo['designer']);
                    if (!$flag) {
                        $infoData = [];
                        $infoData['goods_id'] = $goodsInfo['id'];
                        $infoData['process_id'] = $next_process_id;
                        $infoData['current_user_id'] = $goodsInfo['designer'];
                        $infoData['intro'] = '';
                        $infoData['current_job_id'] = 0;
                        $infoData['current_work_id'] = 0;
                        $ServiceGoodsNodeProcess->insert($infoData);
                    }
                    $userInfo = Cache::store('user')->getOneUser($goodsInfo['designer']);
                    $result = $userInfo['realname'];
                    break;
            }
            Db::commit();
            return $result;
        } catch (Exception $e) {
            Db::rollback();
            throw $e;
        }

    }

    /**
     * @title 获取上级
     * @param $user_id
     * @return mixed
     * @throws Exception
     * @author starzhan <397041849@qq.com>
     */
    public function getLeader($user_id)
    {
        $User = new User();
        $user_id = $User->getLeader($user_id);
        if (!$user_id) {
            throw new Exception('无法获取该员工的上级');
        }
        return $user_id;
    }

    /**
     * @title 获取下一个流程
     * @author starzhan <397041849@qq.com>
     */
    private function getNextProcess($goods_info, $goods_dev = [], $action)
    {
        $update_process_id = $action['next_process'];
        if ($goods_dev['back_process']) {
            $aBackProcess = explode(',', $goods_dev['back_process']);
            $key = array_search($goods_info['process_id'], $aBackProcess);
            if ($key !== false) {
                $next_key = $key + 1;
                if (isset($aBackProcess[$next_key])) {
                    $next_base_process_id = $aBackProcess[$next_key];
                    $aProcess = $this->processes[$next_base_process_id];
                    if (!isset($aProcess['back'])) {
                        throw new Exception('该流程不是退回重审核流程');
                    }
                    $update_process_id = reset($aProcess['back']);
                }

            }
        }
        if (!isset($action['agree'])) {
            return $update_process_id;
        }
        $update_process_id = $this->checkNextProcessId($update_process_id, $goods_dev);
        return $update_process_id;
    }

    private function checkNextProcessId($nextProcessId, $goods_dev)
    {
        if ($nextProcessId == 32) {
            if ($goods_dev['is_demo'] == 0) {
                return $this->checkNextProcessId(129, $goods_dev);
            }
            return 32;
        } else if ($nextProcessId == 129) {
            if ($goods_dev['is_quanlity_test'] == 0) {
                return 512;
            }
            return 129;
        } else if ($nextProcessId == 2048) {
            if ($goods_dev['is_photo'] == 0) {
                return $this->checkNextProcessId(8192, $goods_dev);
            }
            if ($goods_dev['grapher'] == 0) {
                return 2048;
            }
            return 2049;
        } else if ($nextProcessId == 8192) {
            if ($goods_dev['translator'] == '{}') {
                return 8192;
            }
            return 8193;
        } else if ($nextProcessId == 32768) {
            if ($goods_dev['is_design'] == 0) {
                return $this->checkNextProcessId(131072, $goods_dev);
            }
            if ($goods_dev['designer'] == 0) {
                return 32768;
            }
            return 32769;
        }
        return $nextProcessId;

    }

    /**
     * 流程处理
     * @param int $id
     * @param array $params
     * @param int $user_id
     * @return int
     * @throws Exception
     */
    public function handle($id, $params, $user_id)
    {
        $goods = new Goods();
        $goods_info = $goods->where(['id' => $id, 'status' => 0])->field('id,category_id,process_id,developer_id')->find()->toArray();
        if (!isset($params['code']) || empty($params['code'])) { // 检测code 不为空
            throw new Exception('操作编码不能缺少');
        }
        if (!$goods_info) { // 获取到产品信息
            throw new Exception('不存在此产品');
        }
        $baseInfogoodsDev = $this->getBaseInfoForGoodsDev($id);
        // 分析流程ID
        $process = $this->analyseProcess($goods_info['process_id']);
        if (-1 == strpos(',', $process)) {
            $processes[] = $process;
        } else {
            $processes = explode(',', $process);
        }
        foreach ($processes as $process_id) { // 检测code是否有相关的action
            if (!isset($this->processes[$process_id])) {
                continue;
            }
            $process_info = $this->processes[$process_id];
            if (in_array($params['code'], $process_info['action'])) {
                $action = $this->actions[$params['code']];
                break;
            }
        }
        if (!isset($action)) {
            throw new Exception('没有找到相应的操作');
        }
        if (!empty($action['remark']) && empty($params['remark'])) { // 备注不能为空
            throw new Exception('备注不能为空');
        }
        $action['code'] = $params['code'];
        // 处理流程
        Db::startTrans();
        try {
            $goods_info['translator'] = $baseInfogoodsDev['translator'];
            $goods_info['designer'] = $baseInfogoodsDev['designer'];
            $update_process_id = $this->getNextProcess($goods_info, $baseInfogoodsDev, $action);
            $goods->where(['id' => $id])->update(['process_id' => $update_process_id]);
            $this->same2NodeProcess($goods_info, $update_process_id, $user_id);
            if (!empty($action['handler'])) {
                $this->{$action['handler']}($id);
            }
            Db::commit();
        } catch (Exception $ex) {
            Db::rollback();
            throw $ex;
        }
        return 0;
    }

    /**
     * 获取分类sku列表
     * @param int $category_id
     * @param array $attributes
     * @param int $weight
     * @return array
     */
    public function getCategorySkuLists($category_id, $attributes, $weight)
    {
        $goodsHelp = new GoodsHelp();
        $category_attributes = $goodsHelp->getCategoryAttribute($category_id, 1);
        $lists = [];
        $headers = [];
        foreach ($attributes as $attribute) {
            $attribute_info = [];
            $list = [];
            foreach ($category_attributes as $list) {
                if ($list['attribute_id'] == $attribute['attribute_id']) {
                    $attribute_info = $list;
                    break;
                }
            }
            if (empty($attribute_info)) {
                continue;
            }
            $alias = $attribute_info['is_alias'];
            $list['attribute_id'] = $attribute['attribute_id'];
            $list['name'] = $attribute_info['name'];
            $list['attribute_value'] = [];
            foreach ($attribute['attribute_value'] as $value) {
                if (isset($attribute_info['attribute_value'][$value['id']])) {
                    $alias ? $attribute_info['attribute_value'][$value['id']]['value'] = $value['value'] : '';
                    $list['attribute_value'][] = $attribute_info['attribute_value'][$value['id']];
                }
            }
            if (empty($list['attribute_value'])) {
                continue;
            }
            $lists[] = $list['attribute_value'];
            $headers[] = [
                'attribute_id' => $attribute['attribute_id'],
                'name' => $attribute_info['name']
            ];
        }
        $sku_lists = [];
        $new_lists = $goodsHelp->getBaseSkuLists($lists, $sku_lists, $headers);
        if (empty($new_lists)) {
            $new_lists[] = [
                'thumb' => '',
                'sku' => '',
                'alias_sku' => [],
                'id' => 0,
                'name' => '',
                'status' => 0,
                'cost_price' => 0.00,
                'retail_price' => 0.00,
                'weight' => $weight,
                'enabled' => false
            ];
        }
        return ['lists' => $new_lists, 'headers' => array_values($headers)];
    }

    /**
     * 获取流程图显示节点
     * @param int $goods_id 产品Id
     * @return array
     */
    public function getNode($goods_id)
    {
        $lists = GoodsDevelopLog::where(['goods_id' => $goods_id, 'process_id' => ['>', 0]])->select();

        foreach ($lists as &$list) {
            // $list['create_time'] = $list['create_time'] ? date('Y-m-d H:i:s', $list['create_time']) : '';
            $list['operator'] = $this->getUserNameById($list['operator_id']);
            $list['process'] = $this->getProcessBtnNameById($list['process_id']);
            unset($list['id']);
        }
        return $lists;
    }

    /**
     * 检测供应商是否审核
     * @param int $goods_id
     */
    private function checkSupplier($goods_id)
    {
        $supplierGoods = SupplierGoodsOffer::where(['goods_id' => $goods_id])->find();
        if (!$supplierGoods || empty($supplierGoods['supplier_id'])) {
            return false;
        }
        $count = Supplier::where(['id' => $supplierGoods['supplier_id'], 'status' => 1])->count();
        if (!$count) {
            return false;
        }

        Goods::where(['id' => $goods_id])->update(['process_id' => 944]);
    }

    /**
     * 检测流程是否已经达到待上架
     * @param int $goods_id
     */
    private function checkProcess($goods_id)
    {
        $goods_info = Goods::where(['id' => $goods_id, 'status' => 0])->field('process_id')->find();
        if (!$goods_info) {
            return false;
        }
        $process = $this->analyseProcess($goods_info['process_id']);
        if (!strpos($process, ',')) {
            return false;
        }
        $processes = explode(',', $process);
        if (65536 == $processes[0] && 8192 == $processes[1]) {
            Goods::where(['id' => $goods_id])->update(['process_id' => $goods_info['process_id'] + 0x20000000]);
        }
    }

    /**
     * 上架更改状态
     * @param int $goods_id
     */
    private function onSale($goods_id)
    {
        Goods::where(['id' => $goods_id, 'status' => 0])->update(['status' => 1, 'publish_time' => time(), 'sales_status' => 1]);
    }

    /**
     * 生成spu sku
     * @param int goods_id
     */
    public function generateSpu($goods_info)
    {
        $goodsHelp = new GoodsHelp();
        $category_id = $goods_info['category_id'];
        $spu = $goodsHelp->createSpu($category_id);
        if ($spu) {
            $ModelGoods = new Goods();
            $ModelGoods->isUpdate(true)
                ->allowField(true)
                ->save(['spu' => $spu, 'update_time' => time()], ['id' => $goods_info['id']]);
        }
//        $lists = GoodsSku::where(['goods_id' => $goods_id])->select();
//        if (empty($lists)) {
//            return false;
//        }
//
//        $search_attributes = $goodsHelp->getAttributeInfo($goods_id, 1);
//        $goods_attributes = [];
//        foreach ($search_attributes as $attribute) {
//            $values = [];
//            foreach ($attribute['attribute_value'] as $k => $list) {
//                if (empty($list['selected'])) {
//                    unset($attribute['attribute_value'][$k]);
//                    continue;
//                }
//                $values[$list['id']] = $list;
//            }
//            $attribute['attribute_value'] = $values;
//            $goods_attributes[$attribute['attribute_id']] = $attribute;
//        }
//
//        $list_array = [];
//        foreach ($lists as $list) {
//            $rule = [];
//            $attributes = json_decode($list['sku_attributes'], true);
//            foreach ($attributes as $attribute => $value_id) {
//                $attribute_id = str_replace('attr_', '', $attribute);
//                $rule[] = [
//                    'code' => $goods_attributes[$attribute_id]['code'],
//                    'attribute_id' => $attribute_id,
//                    'value_code' => $goods_attributes[$attribute_id]['attribute_value'][$value_id]['code'],
//                    'value_id' => $value_id
//                ];
//            }
//            $sku = $goodsHelp->createSku($spu, $rule, $goods_id, 0, $list_array);
//            $list_array[] = $sku;
//            GoodsSku::where(['id' => $list['id']])->update(['sku' => $sku]);
//        }
    }


    /**
     * 获取用户名 根据id
     * @param int $id
     * @return string
     */
    public function getUserNameById($id)
    {
        if (!$id) {
            return '';
        }
        $userInfo = Cache::store('user')->getOneUser($id);
        if (!$userInfo) {
            return '';
        }

        return $userInfo['realname'];
    }

    #############分割线######以上为翟彬开发内容，以下为詹老师开发内容


    /**
     * @title 组装商品的数据
     * @author starzhan <397041849@qq.com>
     */
    private function buildGoods($param)
    {
        $goods = [];
        $GoodsHelp = new GoodsHelp();
        $userInfo = Common::getUserInfo();
        isset($param['id']) && $goods['id'] = $param['id'];
        isset($param['category_id']) && $goods['category_id'] = $param['category_id'];
        isset($param['channel_id']) && $goods['channel_id'] = $param['channel_id'];
        isset($param['developer_id']) && $goods['developer_id'] = $userInfo['user_id'];
        isset($param['name']) && $goods['name'] = $param['name'];
        isset($param['brand_id']) && $goods['brand_id'] = $param['brand_id'];
        isset($param['tort_id']) && $goods['tort_id'] = $param['tort_id'];
        isset($param['cost_price']) && $goods['cost_price'] = $param['cost_price'];
        isset($param['retail_price']) && $goods['retail_price'] = $param['retail_price'];
        if (isset($param['properties']) && $param['properties']) {
            $properties = json_decode($param['properties'], true);
            $goods['transport_property'] = $GoodsHelp->formatTransportProperty($properties);
            $GoodsHelp->checkTransportProperty($goods['transport_property']);
        }
        if (isset($param['depth']) && $param['depth']) {
            $goods['depth'] = $param['depth'] * 10;
        }
        if (isset($param['width']) && $param['width']) {
            $goods['width'] = $param['width'] * 10;
        }
        if (isset($param['height']) && $param['height']) {
            $goods['height'] = $param['height'] * 10;
        }
        isset($param['volume_weight']) && $goods['volume_weight'] = $param['volume_weight'];
        isset($param['weight']) && $goods['weight'] = $param['weight'];
        isset($param['is_packing']) && $goods['is_packing'] = $param['is_packing'];
        isset($param['packing_id']) && $goods['packing_id'] = $param['packing_id'];
        isset($param['after_packing_id']) && $goods['after_packing_id'] = $param['after_packing_id'];
        isset($param['unit_id']) && $goods['unit_id'] = $param['unit_id'];
        isset($param['warehouse_id']) && $goods['warehouse_id'] = $param['warehouse_id'];
        isset($param['is_multi_warehouse']) && $goods['is_multi_warehouse'] = $param['is_multi_warehouse'];
        isset($param['source_url']) && $goods['source_url'] = $param['source_url'];
        $ValidateGoods = new ValidateGoods();
        $saveType = 'edit';
        if (isset($goods['id']) && $goods['id']) {
            $goods['update_time'] = time();
            $flag = $ValidateGoods->scene('dev_baseInfo_update')->check($goods);
        } else {
            $goods['status'] = 0;
            $goods['create_time'] = time();
            $goods['platform_sale'] = '{}';
            $goods['process_id'] = 1;
            $saveType = 'insert';
            $flag = $ValidateGoods->scene('dev_baseInfo_insert')->check($goods);
        }
        if ($flag === false) {
            throw new Exception($ValidateGoods->getError());
        }
        return ['data' => $goods, 'type' => $saveType];
    }


    /**
     * @title 组装GoodsDev
     * @param $param
     * @author starzhan <397041849@qq.com>
     */
    private function buildGoodsDev($param, $user_id)
    {

        $goodDev = [];
        isset($param['is_demo']) && $goodDev['is_demo'] = $param['is_demo'];
        isset($param['is_quanlity_test']) && $goodDev['is_quanlity_test'] = $param['is_quanlity_test'];
        isset($param['is_photo']) && $goodDev['is_photo'] = $param['is_photo'];
        isset($param['lowest_sale_price']) && $goodDev['lowest_sale_price'] = $param['lowest_sale_price'];
        isset($param['competitor_price']) && $goodDev['competitor_price'] = $param['competitor_price'];
        isset($param['developer_id']) && $goodDev['developer_id'] = $param['developer_id'];
        isset($param['grapher']) && $goodDev['grapher'] = $param['grapher'];
        isset($param['translator']) && $goodDev['translator'] = $param['translator'];
        isset($param['designer']) && $goodDev['designer'] = $param['designer'];
        isset($param['purchase_url']) && $goodDev['purchase_url'] = $param['purchase_url'];
        isset($param['gross_profit']) && $goods['gross_profit'] = $param['gross_profit'];
        isset($param['dev_remark']) && $goods['dev_remark'] = $param['dev_remark'];
        if (empty($goodDev)) {
            return ['data' => '', 'type' => 'edit'];
        }
        $ValidateGoodsDev = new ValidateGoodsDev();
        $model = new ModelGoodsDev();
        $type = 'edit';
        if (isset($param['id']) && $param['id']) {
            $old = $model
                ->where('goods_id', $param['id'])
                ->find();
            if ($old) {
                $goodDev['update_time'] = time();
                $flag = $ValidateGoodsDev->scene('update')->check($goodDev);
            } else {
                $type = 'insert';
                $goodDev['create_id'] = $user_id;
                $goodDev['create_time'] = time();
                $goodDev['translator'] = '{}';
                $flag = $ValidateGoodsDev->scene('insert')->check($goodDev);
            }
        } else {
            $goodDev['create_id'] = $user_id;
            $goodDev['create_time'] = time();
            $goodDev['translator'] = '{}';
            $flag = $ValidateGoodsDev->scene('insert')->check($goodDev);
            $type = 'insert';
        }
        if ($flag === false) {
            throw new Exception($ValidateGoodsDev->getError());
        }
        return ['data' => $goodDev, 'type' => $type];
    }

    private function buildGoodsImgRequirement($param, $user_id)
    {
        $goodsImgRequirement = [];
        isset($param['is_photo']) && $goodsImgRequirement['is_photo'] = $param['is_photo'];
        isset($param['photo_remark']) && $goodsImgRequirement['photo_remark'] = $param['photo_remark'];
        isset($param['undisposed_img_url']) && $goodsImgRequirement['undisposed_img_url'] = $param['undisposed_img_url'];
        isset($param['ps_requirement']) && $goodsImgRequirement['ps_requirement'] = $param['ps_requirement'];
        isset($param['remark']) && $goodsImgRequirement['remark'] = $param['remark'];
        if (empty($goodsImgRequirement)) {
            return ['data' => '', 'type' => 'edit'];
        }
        $ValidateGoodsImgReuirement = new ValidateGoodsImgReuirement();
        $type = 'edit';
        $model = new ModelGoodsImgRequirement();
        if (isset($param['id']) && $param['id']) {

            $old = $model->where('goods_id', $param['id'])->find();
            if ($old) {
                $goodsImgRequirement['update_time'] = time();
                $flag = $ValidateGoodsImgReuirement->scene('update')->check($goodsImgRequirement);
            } else {
                $type = 'insert';
                $goodsImgRequirement['create_time'] = time();
                !isset($goodsImgRequirement['ps_requirement']) && $goodsImgRequirement['ps_requirement'] = '{}';
                $flag = $ValidateGoodsImgReuirement->scene('insert')->check($goodsImgRequirement);
            }

        } else {
            $type = 'insert';
            !isset($goodsImgRequirement['ps_requirement']) && $goodsImgRequirement['ps_requirement'] = '{}';
            $goodsImgRequirement['create_time'] = time();
            $flag = $ValidateGoodsImgReuirement->scene('insert')->check($goodsImgRequirement);
        }

        if ($flag === false) {
            throw new Exception($ValidateGoodsImgReuirement->getError());
        }
        return ['data' => $goodsImgRequirement, 'type' => $type];
    }

    private function buildGoodslang($param, $user_id)
    {
        $data = [];
        isset($param['description']) && $data['description'] = $param['description'];
        isset($param['template']) && $data['template'] = $param['template'];
        isset($param['name']) && $data['title'] = $param['name'];
        isset($param['tags']) && $data['tags'] = $param['tags'];
        isset($param['packing_name']) && $data['packing_name'] = $param['packing_name'];
        isset($param['declare_name']) && $data['declare_name'] = $param['declare_name'];
        if (isset($param['selling_point']) && $param['selling_point']) {
            $selling_point = json_decode($param['selling_point'], true);
            $j = 1;
            $tmp = [];
            foreach ($selling_point as $v) {
                $key = 'amazon_point_' . $j;
                $tmp[$key] = $v;
                $j++;
            }
            $data['selling_point'] = json_encode($tmp);
        }
        if (empty($data)) {
            return ['data' => '', 'type' => 'edit'];
        }
        $type = 'edit';
        $model = new GoodsLang();
        if (isset($param['id']) && $param['id']) {
            $old = $model->where('goods_id', $param['id'])->find();
            if ($old) {

            } else {
                $data['lang_id'] = 1;
                $type = 'insert';
            }
        } else {
            $data['lang_id'] = 1;
            $type = 'insert';
        }
        return ['data' => $data, 'type' => $type];
    }

    private function buildGoodsImage($param, $user_id)
    {
        $aExist = [];
        $data = [];
        $now = time();
        $GoodsPreDevService = new GoodsPreDevService();
        $Model = new ModelGoodsImages();
        if (isset($param['thumb']) && $param['thumb']) {
            $aImage = json_decode($param['thumb'], true);
            foreach ($aImage as $image) {
                if (is_numeric($image)) {
                    $aExist[] = $image;
                } else {
                    $file_name = $param['id'] . '-' . date('YmdHi') . uniqid() . mt_rand(0, 999);
                    $path = $GoodsPreDevService->uploadFile($image['path'], 'goods_images', $file_name);
                    $img_insert_data = [
                        'goods_id' => $param['id'],
                        'path' => $path,
                        'created_time' => $now
                    ];
                    $data[] = $img_insert_data;
                }
            }
            $o = $Model->where('goods_id', $param['id']);
            if ($aExist) {
                $o = $o->where('id', 'not in', $aExist);
            }
            $o->delete();
        }
        return $data;
    }

    private function checkDeveloper($developer_id)
    {
        $GoodsDeveloper = new GoodsDeveloper();
        $aDeveloperInfo = $GoodsDeveloper->getDeveloperInfo($developer_id);
        if (!$aDeveloperInfo) {
            throw new Exception('请添加对应矩阵信息');
        }
        return $aDeveloperInfo;
    }

    /**
     * @title 注释..
     * @param $param
     * @param $user_id
     * @author starzhan <397041849@qq.com>
     */
    public function saveBaseInfo($param, $user_id)
    {
        try {
            $goods = $this->buildGoods($param);
            $goodsDev = $this->buildGoodsDev($param, $user_id);
            $goodsLang = $this->buildGoodslang($param, $user_id);
            $goodsImgRequireMent = $this->buildGoodsImgRequirement($param, $user_id);
            $modelGoods = new Goods();
            $modelGoodsDev = new ModelGoodsDev();
            $modelGoodsLang = new GoodsLang();
            $modelGoodsImage = new ModelGoodsImages();
            $modelGoodsImgRequirement = new ModelGoodsImgRequirement();
            $goods_id = $param['id'] ?? 0;
            Db::startTrans();
            $is_insert_process = false;
            try {
                if ($goods['data']) {
                    if ($goods['type'] == 'edit') {
                        $modelGoods->allowField(true)
                            ->isUpdate(true)
                            ->save($goods['data'], ['id' => $param['id']]);
                    } else {
                        $aDeveloperInfo = $this->checkDeveloper($goods['data']['developer_id']);
                        $goodsDev['data']['grapher'] = $aDeveloperInfo['grapher'];
                        $goodsDev['data']['translator'] = $aDeveloperInfo['translator'];
                        $goodsDev['data']['designer_master'] = $aDeveloperInfo['designer_master'];
                        $goodsHelp = new GoodsHelp();
                        $goods['data']['spu'] = $goodsHelp->createSpu($goods['data']['category_id']);
                        $modelGoods->allowField(true)
                            ->isUpdate(false)
                            ->save($goods['data']);
                        $goods_id = $modelGoods->id;
                        $is_insert_process = true;
                    }
                };
                if ($goodsDev['data']) {
                    $auth_book = $this->saveAuthBook($param);
                    $goodsDev['data'] = array_merge($goodsDev['data'], $auth_book);
                    if ($goodsDev['type'] == 'edit') {
                        $modelGoodsDev->allowField(true)
                            ->isUpdate(true)
                            ->save($goodsDev['data'], ['goods_id' => $param['id']]);
                    } else {
                        $goodsDev['data']['goods_id'] = $goods_id;
                        $modelGoodsDev->allowField(true)
                            ->isUpdate(false)
                            ->save($goodsDev['data']);
                    }

                }
                if ($goodsLang['data']) {
                    if ($goodsLang['type'] == 'edit') {
                        $modelGoodsLang->allowField(true)
                            ->isUpdate(true)
                            ->save($goodsLang['data'], ['goods_id' => $param['id'], 'lang_id' => 1]);
                    } else {
                        $goodsLang['data']['goods_id'] = $goods_id;
                        $modelGoodsLang->allowField(true)
                            ->isUpdate(false)
                            ->save($goodsLang['data']);
                    }
                }

                if ($goodsImgRequireMent['data']) {
                    if ($goodsImgRequireMent['type'] == 'edit') {
                        $modelGoodsImgRequirement->allowField(true)
                            ->isUpdate(true)
                            ->save($goodsImgRequireMent['data'], ['goods_id' => $param['id']]);
                    } else {
                        $goodsImgRequireMent['data']['goods_id'] = $goods_id;
                        $modelGoodsImgRequirement->allowField(true)
                            ->isUpdate(false)
                            ->save($goodsImgRequireMent['data']);
                    }
                }
                $param['id'] = $goods_id;
                $goodsImage = $this->buildGoodsImage($param, $user_id);
                if ($goodsImage) {
                    $modelGoodsImage->saveAll($goodsImage);
                }
                if ($is_insert_process) {
                    $ServiceGoodsNodeProcess = new ServiceGoodsNodeProcess();
                    $flag = $ServiceGoodsNodeProcess->updateStatusOldCurrByUserId(1, $goods['data']['developer_id']);
                    if (!$flag) {
                        $infoData = [];
                        $infoData['goods_id'] = $goods_id;
                        $infoData['process_id'] = 1;
                        $infoData['current_user_id'] = $goods['data']['developer_id'];
                        $infoData['intro'] = '';
                        $infoData['current_job_id'] = 0;
                        $infoData['current_work_id'] = 0;
                        $ServiceGoodsNodeProcess->insert($infoData);
                    }
                    $userInfo = Cache::store('user')->getOneUser($goods['data']['developer_id']);
                    $modelGoodsDev->allowField(true)
                        ->isUpdate(true)
                        ->save(['curr_node' => $userInfo['realname']], ['goods_id' => $goods_id]);
                }
                $GoodsDevLog = new GoodsDevLog();
                $GoodsDevLog->editBaseInfo()->save($user_id, $goods_id, 1);
                Db::commit();
                return ['message' => '保存成功', 'data' => ['id' => $goods_id]];
            } catch (Exception $ex) {
                Db::rollback();
                throw $ex;
            }

        } catch (Exception $ex) {
            throw $ex;
        }
    }

    private function saveAuthBook($param)
    {
        if (!isset($param['auth_book'])) {
            return $param;
        }
        if ($param['auth_book']) {
            $aAuthBook = json_decode($param['auth_book'], true);
            if (!is_array($aAuthBook)) {
                return $param;
            }
            $GoodsPreDevService = new GoodsPreDevService();
            foreach ($aAuthBook as $authBookInfo) {
                $file_name = $param['id'] . '-' . date('YmdHi') . uniqid() . mt_rand(0, 999);
                $path = $GoodsPreDevService->uploadFile($authBookInfo, 'auth_book', $file_name);
                $param['auth_book'][] = $path;
            }
            $param['auth_book'] = json_encode($param['auth_book'], true);
            return $param;
        }
        $param['auth_book'] = '[]';
        return $param;
    }

    /**
     * @title 根据id获取基础详情信息
     * @author starzhan <397041849@qq.com>
     */
    public function getBaseInfo($id)
    {
        $table = ['Goods', 'GoodsDev', 'GoodsLang', 'GoodsImages', 'GoodsImgRequirement'];
        $result = [];
        $prefix = "getBaseInfoFor";
        foreach ($table as $v) {
            $fun = $prefix . $v;
            $row = $this->$fun($id);
            if ($row) {
                $result = array_merge($result, $row);
            }
        }
        return $result;
    }

    private function getBaseInfoForGoods($id)
    {
        $model = new Goods();
        $filedArr = [
            'id',
            'spu',
            'category_id',
            'channel_id',
            'developer_id',
            'name',
            'brand_id',
            'tort_id',
            'cost_price',
            'retail_price',
            'transport_property',
            'depth',
            'width',
            'height',
            'volume_weight',
            'weight',
            'is_packing',
            'packing_id',
            'after_packing_id',
            'unit_id',
            'warehouse_id',
            'is_multi_warehouse',
            'source_url',
            'process_id'
        ];
        $field = implode(',', $filedArr);
        $aGoods = $model->field($field)->where('id', $id)->find();
        $result = [];
        if ($aGoods) {
            $GoodsHelp = new GoodsHelp();
            $aGoods['category_name'] = $aGoods->category;
            $aGoods['developer'] = $aGoods->developer;
            $aGoods['properties'] = $GoodsHelp->getProTransProperties($aGoods->transport_property);
            $aGoods['depth'] = $aGoods['depth'] / 10;
            $aGoods['width'] = $aGoods['width'] / 10;
            $aGoods['height'] = $aGoods['height'] / 10;
            $result = $aGoods->toArray();
        }
        return $result;
    }

    private $BaseInfoGoodsDev = [];

    private function getBaseInfoForGoodsDev($id, $field = '')
    {
        if ($field == '') {
            $field = 'goods_id,is_demo,is_quanlity_test,is_photo,auth_book,lowest_sale_price,competitor_price,purchase_url,';
            $field .= 'translator,gross_profit,dev_remark,designer,back_process';
        }
        if ($this->BaseInfoGoodsDev === []) {
            $model = new ModelGoodsDev();
            $tmp = $model->field($field)->where('goods_id', $id)->find();
            $result = [];
            if ($tmp) {
                $result = $tmp->toArray();
                isset($result['purchase_url']) && $result['purchase_url'] = json_decode($result['purchase_url'], true);
                isset($result['translator']) && $result['translator'] = json_decode($result['translator'], true);
                if (isset($result['auth_book'])) {
                    if ($result['auth_book']) {
                        $result['auth_book'] = json_decode($result['auth_book'], true);
                    } else {
                        $result['auth_book'] = [];
                    }
                }
            }
            $this->BaseInfoGoodsDev = $result;
        }
        return $this->BaseInfoGoodsDev;
    }

    private $BaseInfoGoodsLang = [];

    private function getBaseInfoForGoodsLang($id)
    {
        if ($this->BaseInfoGoodsLang === []) {
            $model = new GoodsLang();
            $field = 'goods_id,description,title,tags,selling_point';
            $aLang = $model->field($field)->where('goods_id', $id)
                ->where('lang_id', 1)
                ->find();
            $result = [];
            if ($aLang) {
                $result = $aLang->toArray();
                $result['selling_point'] = json_decode($result['selling_point'], true);
                $result['tags'] = json_decode($result['tags'], true);
            }
            $this->BaseInfoGoodsLang = $result;
        }
        return $this->BaseInfoGoodsLang;
    }

    private $BaseInfoForGoodsImages = [];

    private function getBaseInfoForGoodsImages($id)
    {
        if ($this->BaseInfoForGoodsImages === []) {
            $model = new ModelGoodsImages();
            $field = 'id,goods_id,path';
            $aImg = $model->field($field)
                ->where('goods_id', $id)
                ->select();
            $result = ['thumb' => []];
            foreach ($aImg as $img) {
                $row = [];
                $row['id'] = $img['id'];
                $row['path'] = $img['path'];
                $result['thumb'][] = $row;
            }
            $this->BaseInfoForGoodsImages = $result;
        }
        return $this->BaseInfoForGoodsImages;
    }

    private $BaseInfoForGoodsImgRequirement = [];

    private function getBaseInfoForGoodsImgRequirement($id)
    {
        if ($this->BaseInfoForGoodsImgRequirement === []) {
            $model = new ModelGoodsImgRequirement();
            $filed = 'goods_id,photo_remark,undisposed_img_url,ps_requirement';
            $aGoodsImgRequirement = $model->field($filed)->where('goods_id', $id)->find();
            $result = [];
            if ($aGoodsImgRequirement) {
                $result = $aGoodsImgRequirement->toArray();
            }
            $this->BaseInfoForGoodsImgRequirement = $result;
        }
        return $this->BaseInfoForGoodsImgRequirement;
    }

    /**
     * @title 获取平台上架情况
     * @param $id
     * @author starzhan <397041849@qq.com>
     */
    public function getPlatformSale($id)
    {
        $field = "id,platform";
        $model = new Goods();
        $aGoods = $model->field($field)->where('id', $id)->find();
        if (!$aGoods) {
            throw new Exception('商品不存在');
        }
        $result = [];
        $goodsHelp = new GoodsHelp();
        $result['platform_sale'] = $goodsHelp->getPlatformSale($aGoods['platform']);
        $result['categoryMap'] = $goodsHelp->getGoodsCategoryMap($id);
        return $result;
    }

    /**
     * @title 保存平台分类
     * @param $id
     * @param $param
     * @param $user_id
     * @author starzhan <397041849@qq.com>
     */
    public function putPlatformSale($id, $param, $user_id)
    {
        $goodsHelp = new GoodsHelp();
        $model = new Goods();
        $goods = [];
        if (isset($param['platform_sale'])) {
            $lists = json_decode($param['platform_sale'], true);
            $goods['platform'] = $goodsHelp->getPlatform($lists);
            $goods['update_time'] = time();
        }
        $goodsMap = [];
        if (isset($param['platform'])) {
            $goodsMap['platform'] = json_decode($param['platform'], true);
        }
        if (empty($goods) && empty($goodsMap)) {
            throw new Exception('更新内容为空');
        }

        Db::startTrans();
        try {
            if ($goods) {
                $model->allowField(true)
                    ->isUpdate(true)
                    ->save($goods, ['id' => $id]);
            }
            if ($goodsMap) {
                $goodsHelp->saveGoodsCategoryMap($id, $goodsMap['platform'], $user_id);
            }
            $GoodsDevLog = new GoodsDevLog();
            $GoodsDevLog->editPlatform()->save($user_id, $id, 1);
            Db::commit();
            return ['message' => '保存成功'];
        } catch (Exception $ex) {
            Db::rollback();
            throw $ex;
        }

    }

    protected function getRealName($id)
    {
        $userInfo = Cache::store('user')->getOneUser($id);
        return $userInfo['realname'] ?? '';
    }

    /**
     * @title 保存sku列表信息
     * @param $id
     * @param $list
     * @param $user_id
     * @author starzhan <397041849@qq.com>
     */
    public function saveSkuList($id, $list, $user_id)
    {
        $ServerGoodsSku = new ServiceGoodsSku();
        $GoodsHelp = new GoodsHelp();
        Db::startTrans();
        try {
            $result = $ServerGoodsSku->saveSkuInfo($id, $list, $user_id, null, 'dev');
            $goodsDevLog = new GoodsDevLog();
            $goodsDevLog->editSpecification()->save($user_id, $id, 1);
            Db::commit();
            $result['data'] = $GoodsHelp->getSkuInfo($id);
            return $result;
        } catch (Exception $ex) {
            Db::rollback();
            throw $ex;
        }

    }

    public function generateSkus($goods_id, $aSkuId)
    {
        $goodsInfo = $this->getBaseInfo($goods_id);
        if (!$goodsInfo) {
            throw new Exception('商品信息未空');
        }
        $result = [];
        $sku_array = [];
        $singleSku = false;
        if (count($aSkuId) == 1) {
            $singleSku = true;
        }
        foreach ($aSkuId as $sku_id) {
            $sku = [];
            $sku['id'] = $sku_id;
            $sku['sku'] = $this->createSku($goodsInfo['spu'], [], $goods_id, 0, $sku_array, $singleSku);
            $result[] = $sku;
        }
        return $result;
    }

    /**
     * @title 确认生成sku
     * @param $goods_id
     * @param $sku_list
     * @param $user_id
     * @return array
     * @throws Exception
     * @author starzhan <397041849@qq.com>
     */
    public function sureGenerateSku($goods_id, $sku_list, $user_id)
    {
        $goods_info = $this->getBaseInfoForGoods($goods_id);
        if (!$goods_info) {
            throw new Exception('该商品信息不存在');
        }
        if ($goods_info['process_id'] != 512) {
            throw new Exception('当前请求有误');
        }
        $aSku = [];
        foreach ($sku_list as $v) {
            $aSku[$v['id']] = $v['sku'];
        }
        if (empty($aSku)) {
            throw new Exception('sku信息不能为空');
        }
        $aBaseInfoGoodsDev = $this->getBaseInfoForGoodsDev($goods_id);
        $action = $this->actions['generate_sku'];
        $next_process_id = $this->getNextProcess($goods_info, $aBaseInfoGoodsDev, $action);
        Db::startTrans();
        try {
            $GoodsSKu = new GoodsSku();
            $aSkuId = array_keys($aSku);
            $aSkuList = $GoodsSKu->where('id', 'in', $aSkuId)->field('id,sku')->select();
            foreach ($aSkuList as $skuInfo) {
                $skuInfo->sku = $aSku[$skuInfo->id];
                $skuInfo->save();
            }
            $ServiceGoodsNodeProcess = new ServiceGoodsNodeProcess();
            $ServiceGoodsNodeProcess->finishThisProcess($goods_id, $goods_info['process_id'], $user_id);
            $flag = $ServiceGoodsNodeProcess->updateStatusOldCurrByUserId($next_process_id, $goods_info['developer_id']);
            if (!$flag) {
                $infoData = [];
                $infoData['goods_id'] = $goods_id;
                $infoData['process_id'] = $next_process_id;
                $infoData['current_user_id'] = $goods_info['developer_id'];
                $infoData['intro'] = '';
                $infoData['current_job_id'] = 0;
                $infoData['current_work_id'] = 0;
                $ServiceGoodsNodeProcess->insert($infoData);
            }
            $ModelGoods = new Goods();
            $ModelGoods->allowField(true)->isUpdate(true)->save(['process_id' => $next_process_id, 'update_time' => time()], ['id' => $goods_id]);
            $GoodsDevLog = new GoodsDevLog();
            $GoodsDevLog->createSku()->save($goods_id, $user_id, $goods_info['process_id']);
            ModelGoodsDev::where('goods_id', $goods_id)->update(['curr_node' => $this->getRealName($goods_info['developer_id'])]);
            $GoodsDevLog = new GoodsDevLog();
            $GoodsDevLog->createSku()->save($user_id, $goods_id, 512);
            Db::commit();
            return ['message' => '操作成功'];
        } catch (Exception $ex) {
            Db::rollback();
            throw $ex;
        }
    }

    public function saveDeclare($goods_id, $data, $user_id)
    {
        $ModelGoods = new Goods();
        $goods_info = $ModelGoods->field('id,process_id')->where('id', $goods_id)->find();
        if (!$goods_info) {
            throw new Exception('商品信息不存在');
        }
        if ($goods_info['process_id'] != 1024) {
            throw new Exception('当前请求有误');
        }
        $goods_dev = $this->getBaseInfoForGoodsDev($goods_id);
        $action = $this->actions['submit_declare'];
        $next_process_id = $this->getNextProcess($goods_info, $goods_dev, $action);
        Db::startTrans();
        try {
            $curr_node = $this->same2NodeProcess($goods_info, $next_process_id, $user_id);
            $data['update_time'] = time();
            $data['process_id'] = $next_process_id;
            $goods_info->save($data);
            if ($curr_node) {
                ModelGoodsDev::where('goods_id', $goods_id)
                    ->update(['curr_node' => $curr_node]);
            }
            $GoodsDevLog = new GoodsDevLog();
            $GoodsDevLog->editDeclare()->save($user_id, $goods_id, 1024);
            Db::commit();
            return ['message' => '保存成功'];
        } catch (Exception $ex) {
            Db::rollback();
            throw $ex;
        }

    }

    /**
     * @title 设置摄影师
     * @param $goods_id
     * @param $grapher
     * @param $user_id
     * @return array
     * @throws Exception
     * @author starzhan <397041849@qq.com>
     */
    public function setGrapher($goods_id, $grapher, $user_id)
    {
        $ModelGoods = new Goods();
        $goods_info = $ModelGoods->field('id,process_id')->where('id', $goods_id)->find();
        if (!$goods_info) {
            throw new Exception('商品信息不存在');
        }
        if ($goods_info['process_id'] != 2048) {
            throw new Exception('当前请求有误');
        }
        $ModelGoodsDev = new ModelGoodsDev();
        $goods_dev = $ModelGoodsDev->field('goods_id,grapher')->where('goods_id', $goods_id)->find();
        if (!$goods_dev) {
            throw new Exception('商品信息不存在');
        }
        $action = $this->actions['set_grapher'];
        $next_process_id = $this->getNextProcess($goods_info, $goods_dev, $action);
        Db::startTrans();
        try {
            $goods_info['grapher'] = $grapher;
            $currNode = $this->same2NodeProcess($goods_info, $next_process_id, $user_id);
            $data['update_time'] = time();
            $data['process_id'] = $next_process_id;
            $goods_info->allowField(true)->save($data);
            $goods_dev->grapher = $grapher;
            if ($currNode) {
                $goods_dev->curr_node = $currNode;
            }
            $goods_dev->save();
            $GoodsDevLog = new GoodsDevLog();
            $GoodsDevLog->Assign($currNode)->save($user_id, $goods_id, 2048);
            Db::commit();
            return ['message' => '保存成功'];
        } catch (Exception $ex) {
            Db::rollback();
            throw $ex;
        }

    }

    public function startPhoto($goods_id, $user_id)
    {
        $ModelGoods = new Goods();
        $goods_info = $ModelGoods->field('id,process_id')->where('id', $goods_id)->find();
        if (!$goods_info) {
            throw new Exception('商品信息不存在');
        }
        if ($goods_info['process_id'] != 2049) {
            throw new Exception('当前请求有误');
        }
        $ModelGoodsDev = new ModelGoodsDev();
        $goods_dev = $ModelGoodsDev->field('goods_id,grapher')->where('goods_id', $goods_id)->find();
        if (!$goods_dev) {
            throw new Exception('商品信息不存在');
        }
        $action = $this->actions['start_photo'];
        $next_process_id = $this->getNextProcess($goods_info, $goods_dev, $action);
        Db::startTrans();
        try {
            $data = [];
            $goods_info['grapher'] = $goods_dev->grapher;
            $currNode = $this->same2NodeProcess($goods_info, $next_process_id, $user_id);
            $data['update_time'] = time();
            $data['process_id'] = $next_process_id;
            $goods_info->allowField(true)->save($data);
            if ($currNode) {
                ModelGoodsDev::where('goods_id', $goods_id)
                    ->update(['curr_node' => $currNode]);
            }
            $GoodsDevLog = new GoodsDevLog();
            $GoodsDevLog->start('拍图')->save($user_id, $goods_id, 2049);
            Db::commit();
            return ['message' => '保存成功'];
        } catch (Exception $ex) {
            Db::rollback();
            throw $ex;
        }
    }

    public function setPhotoPath($goods_id, $photo_path, $user_id)
    {
        $ModelGoods = new Goods();
        $goods_info = $ModelGoods->field('id,process_id')->where('id', $goods_id)->find();
        if (!$goods_info) {
            throw new Exception('商品信息不存在');
        }
        if ($goods_info['process_id'] != 2050) {
            throw new Exception('当前请求有误');
        }
        $ModelGoodsImgRequirement = new ModelGoodsImgRequirement();
        $imgInfo = $ModelGoodsImgRequirement->field('goods_id,undisposed_img_url')->where('goods_id', $goods_id)->find();
        if (!$imgInfo) {
            throw new Exception('图片相关信息不存在');
        }
        $data = [];
        $data['update_time'] = time();
        $data['undisposed_img_url'] = $photo_path;
        $imgInfo->allowField(true)->save($data);
        return ['message' => '保存成功'];
    }

    public function getPhotoInfo($goods_id)
    {
        $ModelGoodsDev = new ModelGoodsDev();
        $result = [
            'grapher' => 0,
            'photo_path' => ''
        ];
        $goods_info = $ModelGoodsDev->field('id,grapher')->where('goods_id', $goods_id)->find();
        if (!$goods_info) {
            throw new Exception('商品信息不存在');
        }
        $result['grapher'] = $goods_info['grapher'];
        $ModelGoodsImgRequirement = new ModelGoodsImgRequirement();
        $imgInfo = $ModelGoodsImgRequirement->where('goods_id', $goods_id)->field('undisposed_img_url')->find();
        if ($imgInfo) {
            $result['photo_path'] = $imgInfo->undisposed_img_url;
        }
        return $result;
    }

    public function setTranslator($goods_id, $aTranslator, $user_id)
    {
        $ModelGoods = new Goods();
        $goods_info = $ModelGoods->field('id,process_id')->where('id', $goods_id)->find();
        if (!$goods_info) {
            throw new Exception('商品信息不存在');
        }
        if ($goods_info['process_id'] != 8192) {
            throw new Exception('当前请求有误');
        }
        $ModelGoodsDev = new ModelGoodsDev();
        $goods_dev = $ModelGoodsDev->field('goods_id,translator')->where('goods_id', $goods_id)->find();
        if (!$goods_dev) {
            throw new Exception('商品信息不存在');
        }
        $aData = [];
        foreach ($aTranslator as $v) {
            $row = [];
            $row['lang'] = $v['lang'];
            $row['translator'] = $v['translator'];
            $aData[$v['lang']] = $row;
        }
        if (!$aData) {
            throw new Exception('数据为空');
        }
        $action = $this->actions['set_translator'];
        $next_process_id = $this->getNextProcess($goods_info, $goods_dev, $action);
        Db::startTrans();
        try {
            $goods_info['translator'] = json_encode($aData);
            $curr_node = $this->same2NodeProcess($goods_info, $next_process_id, $user_id);
            $data['update_time'] = time();
            $data['process_id'] = $next_process_id;
            $goods_info->allowField(true)->save($data);
            $goods_dev->translator = json_encode($aData);
            if ($curr_node) {
                $goods_dev->curr_node = $curr_node;
            }
            $goods_dev->save();
            $GoodsDevLog = new GoodsDevLog();
            $GoodsDevLog->Assign($curr_node)->save($user_id, $goods_id, 8192);
            Db::commit();
            return ['message' => '保存成功'];
        } catch (Exception $ex) {
            Db::rollback();
            throw $ex;
        }
    }

    public function getTranslatorInfo($goods_id, $user_id)
    {
        $ModelGoodsDev = new ModelGoodsDev();
        $goods_dev = $ModelGoodsDev->field('goods_id,translator')->where('goods_id', $goods_id)->find();
        if (!$goods_dev) {
            throw new Exception('商品信息不存在');
        }
        $result = [];
        $result['translator'] = json_decode($goods_dev['translator'], true);
        $result['user_id'] = $user_id;
        return $result;
    }

    public function startTranslator($goods_id, $user_id)
    {
        $ModelGoods = new Goods();
        $goods_info = $ModelGoods->field('id,process_id')->where('id', $goods_id)->find();
        if (!$goods_info) {
            throw new Exception('商品信息不存在');
        }
        $old_process_id = $goods_info['process_id'];
        if (!in_array($goods_info['process_id'], [8193, 8194])) {
            throw new Exception('当前请求有误');
        }
        if ($goods_info['process_id'] == 8194) {
            return ['message' => '操作成功'];
        }
        $ModelGoodsDev = new ModelGoodsDev();
        $goods_dev = $ModelGoodsDev->field('goods_id,translator')->where('goods_id', $goods_id)->find();
        if (!$goods_dev) {
            throw new Exception('商品信息不存在');
        }
        $action = $this->actions['start_translator'];
        $next_process_id = $this->getNextProcess($goods_info, $goods_dev, $action);
        Db::startTrans();
        try {
            $curr_node = $this->same2NodeProcess($goods_info, $next_process_id, $user_id);
            $data['update_time'] = time();
            $data['process_id'] = $next_process_id;
            $goods_info->allowField(true)->save($data);
            if ($curr_node) {
                ModelGoodsDev::where('goods_id', $goods_id)
                    ->update(['curr_node' => $curr_node]);
            }
            $GoodsDevLog = new GoodsDevLog();
            $GoodsDevLog->start('翻译')->save($user_id, $goods_id, $old_process_id);
            Db::commit();
            return ['message' => '保存成功'];
        } catch (Exception $ex) {
            Db::rollback();
            throw $ex;
        }
    }

    /**
     * @title id转成code
     * @param $lang_id
     * @author starzhan <397041849@qq.com>
     */
    private function langIdToCode($lang_id)
    {
        $code = '';
        $result = Cache::store('lang')->getLang();
        foreach ($result as $v) {
            if ($v['id'] == $lang_id) {
                $code = $v['code'];
                return $code;
            }
        }
        return $code;
    }

    public function translatorIng($goods_id, $param, $user_id)
    {
        if (!isset($param['lang_id']) || !$param['lang_id']) {
            throw new Exception('lang_id不能为空');
        }
        $data = [];
        if (isset($param['translator']) && $param['translator']) {
            $ModelGoodsDev = new ModelGoodsDev();
            $goods_dev = $ModelGoodsDev
                ->field('goods_id,translator')
                ->where('goods_id', $goods_id)
                ->find();
            if (!$goods_dev) {
                throw new Exception('商品信息不存在');
            }
            $code = $this->langIdToCode($param['lang_id']);
            if (!$code) {
                throw new Exception('该lang_id找不到对应的cdoe');
            }

            $data = [
                'translator' => ['exp', "JSON_SET(translator,'$.{$code}.translator',{$param['translator']})"]
            ];
            $ModelGoodsDev->allowField(true)->isUpdate(true)->save($data, ['goods_id' => $goods_id]);
            return ['message' => '转移成功'];
        }

        if (isset($param['tags']) && $param['tags']) {
            $aTags = json_decode($param['tags'], true);
            $data['tags'] = implode('\n', $aTags);
        }
        if (isset($param['selling_point']) && $param['selling_point']) {
            $aSellingPoint = json_decode($param['selling_point'], true);
            $i = 1;
            $baseKey = 'amazon_point_';
            $aTmp = [];
            foreach ($aSellingPoint as $selling_point_info) {
                $key = $baseKey . $i;
                $aTmp[$key] = $selling_point_info;
                $i++;
            }
            $data['selling_point'] = json_encode($aTmp);
        }
        isset($param['description']) && $data['description'] = $param['description'];
        isset($param['title']) && $data['title'] = $param['title'];
        isset($param['declare_name']) && $data['declare_name'] = $param['declare_name'];
        $ModelGoodsLang = new GoodsLang();
        $aGoodsLangInfo = $ModelGoodsLang->where('goods_id', $goods_id)
            ->where('lang_id', $param['lang_id'])
            ->find();
        if ($aGoodsLangInfo) {

            $ModelGoodsLang->allowField(true)->isUpdate(true)->save($data, [
                'goods_id' => $goods_id,
                'lang_id' => $param['lang_id'],
            ]);
        } else {
            $ValidateGoodsLang = new ValidateGoodsLang();
            $flag = $ValidateGoodsLang->scene('dev_insert')->check($data);
            if ($flag === false) {
                throw new Exception($ValidateGoodsLang->getError());
            }
            $data['lang_id'] = $param['lang_id'];
            $data['goods_id'] = $goods_id;
            if (!isset($data['selling_point'])) {
                $data['selling_point'] = '{}';
            }
            $ModelGoodsLang->allowField(true)->isUpdate(false)->save($data);
        }
        return ['message' => '保存成功'];

    }

    /**
     * @title
     * @author starzhan <397041849@qq.com>
     */
    public function translatorSubmit($goods_id, $lang_id, $user_id)
    {
        $goods = new Goods();
        $goods_info = $goods->where(['id' => $goods_id, 'status' => 0])->field('id,category_id,process_id,developer_id')->find()->toArray();
        if (!$goods_info) { // 获取到产品信息
            throw new Exception('不存在此产品');
        }
        $old_process_id = $goods_info['process_id'];
        if (!in_array($goods_info['process_id'], [8194, 8196])) {
            throw new Exception('当前请求有误');
        }
        $code = $this->langIdToCode($lang_id);
        if (!$code) {
            throw new Exception('当前语言id对应code为空');
        }
        $ModelGoodsDev = new ModelGoodsDev();
        $goods_dev = $ModelGoodsDev
            ->field('goods_id,translator')
            ->where('goods_id', $goods_id)
            ->find();
        if (!$goods_dev) {
            throw new Exception('商品信息不存在');
        }
        // 处理流程
        Db::startTrans();
        try {
            $ServiceGoodsNodeProcess = new ServiceGoodsNodeProcess();
            $ServiceGoodsNodeProcess->finishThisProcess($goods_id, $goods_info['process_id'], $user_id, $code);
            $unFinishCount = $ServiceGoodsNodeProcess->countForUnFinishByProcessId($goods_id, $goods_info['process_id']);
            if ($unFinishCount == 0) {
                $action = $this->actions['translator_submit'];
                $next_process_id = $this->getNextProcess($goods_info, $goods_dev, $action);
                $goods->where(['id' => $goods_id])->update(['process_id' => $next_process_id]);
                $JobService = new JobService();
                $jobIb = $JobService->getIdByCode('translator');
                if (!$jobIb) {
                    throw new Exception('翻译员获取失败，请检查职位设置');
                }
                $workId = $JobService->getIdByCode('groupLeader');
                if (!$workId) {
                    throw new Exception('组长获取失败，请检查职位设置');
                }
                $flag = $ServiceGoodsNodeProcess->updateStatusOldCurrByWorkId($next_process_id, $jobIb, $workId);
                if (!$flag) {
                    $infoData = [];
                    $infoData['goods_id'] = $goods_id;
                    $infoData['process_id'] = $next_process_id;
                    $infoData['current_user_id'] = 0;
                    $infoData['intro'] = '';
                    $infoData['current_job_id'] = $jobIb;
                    $infoData['current_work_id'] = $workId;
                    $ServiceGoodsNodeProcess->insert($infoData);
                }
                ModelGoodsDev::where('goods_id', $goods_id)
                    ->update(['curr_node' => '翻译组长']);
            }
            $GoodsDevLog = new GoodsDevLog();
            $GoodsDevLog->submit()->save($user_id, $goods_id, $old_process_id);
            Db::commit();
            return ['message' => '保存成功'];
        } catch (Exception $ex) {
            Db::rollback();
            throw $ex;
        }

    }

    public function translatorBack($goods_id, $aLang, $user_id, $remark)
    {
        $goods = new Goods();
        $goods_info = $goods->where(['id' => $goods_id, 'status' => 0])->field('id,category_id,process_id,developer_id')->find()->toArray();
        if (!$goods_info) { // 获取到产品信息
            throw new Exception('不存在此产品');
        }
        if (!in_array($goods_info['process_id'], [8195])) {
            throw new Exception('当前请求有误');
        }
        $goods_dev = $this->getBaseInfoForGoodsDev($goods_id, 'goods_id,translator');
        $action = $this->actions['translator_audit_fail'];
        $next_process_id = $this->getNextProcess($goods_info, $goods_dev, $action);
        $oldTranslator = json_decode($goods_dev['translator'], true);
        $data = [];
        foreach ($aLang as $lang) {
            if (isset($oldTranslator[$lang])) {
                $data[$lang] = $oldTranslator[$lang];
            }
        }
        // 处理流程
        Db::startTrans();
        try {
            $goods_info['translator'] = json_encode($data);
            $curr_node = $this->same2NodeProcess($goods_info, $next_process_id, $user_id);
            if ($curr_node) {
                ModelGoodsDev::where('goods_id', $goods_id)
                    ->update(['curr_node' => $curr_node]);
            }
            $goods->where(['id' => $goods_id])->update(['process_id' => $next_process_id]);
            $GoodsDevLog = new GoodsDevLog();
            $GoodsDevLog->back($remark, $curr_node)->save($user_id, $goods_id, 8195);
            Db::commit();
            return ['message' => '保存成功'];
        } catch (Exception $ex) {
            Db::rollback();
            throw $ex;
        }
    }

    /**
     * @title 指定修图美工
     * @param $goods_id
     * @param $designer
     * @param $user_id
     * @author starzhan <397041849@qq.com>
     */
    public function designerSetting($goods_id, $designer, $user_id)
    {
        $goods = new Goods();
        $goods_info = $goods->where(['id' => $goods_id, 'status' => 0])->field('id,category_id,process_id,developer_id')->find()->toArray();
        if (!$goods_info) { // 获取到产品信息
            throw new Exception('不存在此产品');
        }
        if (!in_array($goods_info['process_id'], [32768])) {
            throw new Exception('当前请求有误');
        }
        $goods_dev = $this->getBaseInfoForGoodsDev($goods_id);
        $action = $this->actions['designer_setting'];
        $next_process_id = $this->getNextProcess($goods_info, $goods_dev, $action);
        // 处理流程
        Db::startTrans();
        try {
            $ModelGoodsDev = new ModelGoodsDev();
            $ModelGoodsDev->allowField(true)->isUpdate(true)->save(
                [
                    'designer' => $designer,
                    'update_time' => time()
                ], ['goods_id' => $goods_id]
            );
            $goods_info['designer'] = $designer;
            $curr_node = $this->same2NodeProcess($goods_info, $next_process_id, $user_id);
            $goods->where(['id' => $goods_id])->update(['process_id' => $next_process_id, 'update_time' => time()]);
            if ($curr_node) {
                ModelGoodsDev::where('goods_id', $goods_id)
                    ->update(['curr_node' => $curr_node]);
            }
            $GoodsDevLog = new GoodsDevLog();
            $GoodsDevLog->Assign($curr_node)->save($user_id, $goods_id, 32768);
            Db::commit();
            return ['message' => '保存成功'];
        } catch (Exception $ex) {
            Db::rollback();
            throw $ex;
        }
    }

    public function designerStarting($goods_id, $user_id)
    {
        $goods = new Goods();
        $goods_info = $goods->where(['id' => $goods_id, 'status' => 0])->field('id,category_id,process_id,developer_id')->find()->toArray();
        if (!$goods_info) { // 获取到产品信息
            throw new Exception('不存在此产品');
        }
        if (!in_array($goods_info['process_id'], [32769])) {
            throw new Exception('当前请求有误');
        }
        $goods_dev = $this->getBaseInfoForGoodsDev($goods_id, 'goods_id,designer');
        $action = $this->actions['designer_starting'];
        $next_process_id = $this->getNextProcess($goods_info, $goods_dev, $action);
        Db::startTrans();
        try {
            $goods_info['designer'] = $goods_dev['designer'];
            $curr_node = $this->same2NodeProcess($goods_info, $next_process_id, $user_id);
            $goods->where(['id' => $goods_id])->update(['process_id' => $next_process_id, 'update_time' => time()]);
            if ($curr_node) {
                ModelGoodsDev::where('goods_id', $goods_id)
                    ->update(['curr_node' => $curr_node]);
            }
            $GoodsDevLog = new GoodsDevLog();
            $GoodsDevLog->start('修图')->save($user_id, $goods_id, 32769);
            Db::commit();
            return ['message' => '操作成功'];
        } catch (Exception $ex) {
            Db::rollback();
            throw $ex;
        }

    }

    public function psImgUrl($goods_id, $ps_img_url, $user_id)
    {
        $goods = new Goods();
        $goods_info = $goods->where(['id' => $goods_id, 'status' => 0])->field('id,category_id,process_id,developer_id')->find()->toArray();
        if (!$goods_info) { // 获取到产品信息
            throw new Exception('不存在此产品');
        }
        if (!in_array($goods_info['process_id'], [32770])) {
            throw new Exception('当前请求有误');
        }
        Db::startTrans();
        try {
            $ModelGoodsImgRequirement = new ModelGoodsImgRequirement();
            $ModelGoodsImgRequirement->allowField(true)
                ->isUpdate(true)
                ->save(['ps_img_url' => $ps_img_url, 'update_time' => time()], ['goods_id' => $goods_id]);
            Db::commit();
            return ['message' => '操作成功'];
        } catch (Exception $ex) {
            Db::rollback();
            throw $ex;
        }

    }

    public function finalSubmit($goods_id, $grapher, $designer, $translator, $user_id)
    {
        $goods = new Goods();
        $goods_info = $goods->where(['id' => $goods_id, 'status' => 0])->field('id,category_id,process_id,developer_id')->find()->toArray();
        if (!$goods_info) { // 获取到产品信息
            throw new Exception('不存在此产品');
        }
        if (!in_array($goods_info['process_id'], [131072])) {
            throw new Exception('当前请求有误');
        }
        $goods_dev = $this->getBaseInfoForGoodsDev($goods_id);
        $action = $this->actions['final_submit'];
        $next_process_id = $this->getNextProcess($goods_info, $goods_dev, $action);
        if (!(0 < $grapher && $grapher <= 10)) {
            throw new Exception('摄影师评分取值范围错误');
        }
        if (!(0 < $designer && $designer <= 10)) {
            throw new Exception('美工评分取值范围错误');
        }
        if (!(0 < $translator && $translator <= 10)) {
            throw new Exception('翻译评分取值范围错误');
        }
        $ModelDevScore = new ModelGoodsDevScore();
        Db::startTrans();
        try {
            $curr_node = $this->same2NodeProcess($goods_info, $next_process_id, $user_id);
            $goods->where(['id' => $goods_id])->update(['process_id' => $next_process_id, 'update_time' => time()]);
            $aScore = $ModelDevScore->where('goods_id', $goods_id)->find();
            if ($aScore) {
                $data = [
                    'grapher' => $grapher,
                    'designer' => $designer,
                    'translator' => $translator,
                ];
                $aScore->allowField(true)->save($data);
            } else {
                $data = [
                    'goods_id' => $goods_id,
                    'grapher' => $grapher,
                    'designer' => $designer,
                    'translator' => $translator,
                    'create_id' => $user_id,
                    'create_time' => time()
                ];
                $ModelDevScore->allowField(true)->isUpdate(false)->save($data);
            }
            if ($curr_node) {
                ModelGoodsDev::where('goods_id', $goods_id)
                    ->update(['curr_node' => $curr_node]);
            }
            $GoodsDevLog = new GoodsDevLog();
            $GoodsDevLog->submit()->save($user_id, $goods_id, 131072);
            Db::commit();
            return ['message' => '操作成功'];
        } catch (Exception $ex) {
            Db::rollback();
            throw $ex;
        }
    }

    public function getBackProcess($goods_id)
    {
        $goods = new Goods();
        $goods_info = $goods->where(['id' => $goods_id, 'status' => 0])->field('id,category_id,process_id,developer_id')->find()->toArray();
        if (!$goods_info) { // 获取到产品信息
            throw new Exception('不存在此产品');
        }
        $goods_dev = $this->getBaseInfoForGoodsDev($goods_id);
        $aProcess = $this->processes;
        $result = [];
        foreach ($aProcess as $k => $v) {
            if (isset($v['back'])) {
                $row = [
                    'process_id' => $k,
                    'name' => $v['name']
                ];
                $result[$k] = $row;
            }
        }
        if ($goods_dev['is_quanlity_test'] == 0) {
            unset($result[131]);
        }
        if ($goods_dev['is_photo'] == 0) {
            unset($result[2052]);
        }
        if ($goods_info['process_id'] == 262144) {
            unset($result[131072]);
        }
        $ret = [];
        foreach ($result as $v) {
            $ret[] = $v;
        }
        return $ret;
    }

    public function backProcess($goods_id, $aProcessId, $remark, $user_id)
    {
        $goods = new Goods();
        $goods_info = $goods->where(['id' => $goods_id, 'status' => 0])->field('id,category_id,process_id,developer_id')->find()->toArray();
        if (!$goods_info) { // 获取到产品信息
            throw new Exception('不存在此产品');
        }
        $old_process_id = $goods_info['process_id'];
        if (!in_array($goods_info['process_id'], [131072, 262144])) {
            throw new Exception('当前请求有误');
        }
        $goods_dev = $this->getBaseInfoForGoodsDev($goods_id, 'goods_id,designer,grapher,translator');
        $next_process_id = reset($aProcessId);
        $modelGoodsDev = new ModelGoodsDev();
        Db::startTrans();
        try {
            $goods_info['designer'] = $goods_dev['designer'];
            $goods_info['grapher'] = $goods_dev['grapher'];
            $goods_info['translator'] = $goods_dev['translator'];
            $curr_node = $this->same2NodeProcess($goods_info, $next_process_id, $user_id);
            $goods->where(['id' => $goods_id])->update(['process_id' => $next_process_id, 'update_time' => time()]);
            $modelGoodsDev->allowField(true)->isUpdate(true)->save([
                'back_process' => implode(',', $aProcessId),
                'update_time' => time(),
                'curr_node' => $curr_node
            ], ['goods_id' => $goods_id]);
            $GoodsDevLog = new GoodsDevLog();
            $aTxt = [];
            foreach ($aProcessId as $nProcessId) {
                $aTxt[] = $this->processes[$nProcessId]['name'];
            }
            $GoodsDevLog->back($remark, implode(',', $aTxt))->save($user_id, $goods_id, $old_process_id);
            Db::commit();
            return ['message' => '操作成功'];
        } catch (Exception $ex) {
            Db::rollback();
            throw $ex;
        }


    }

    public function release($goods_id, $user_id)
    {
        $goods = new Goods();
        $goods_info = $goods->where(['id' => $goods_id, 'status' => 0])->field('id,category_id,process_id,developer_id')->find()->toArray();
        if (!$goods_info) { // 获取到产品信息
            throw new Exception('不存在此产品');
        }
        if (!in_array($goods_info['process_id'], [262144])) {
            throw new Exception('当前请求有误');
        }
        Db::startTrans();
        try {
            $this->same2NodeProcess($goods_info, 524288, $user_id);
            $goods->where(['id' => $goods_id])->update(['process_id' => 524288, 'status' => 1, 'update_time' => time()]);
            $GoodsDevLog = new GoodsDevLog();
            $GoodsDevLog->publish()->save($user_id, $goods_id, 262144);
            Db::commit();
            return ['message' => '操作成功'];
        } catch (Exception $ex) {
            Db::rollback();
            throw $ex;
        }
    }

    public function menu($id)
    {
        $goodsDevModel = new GoodsDevMenu();
        $menu = $goodsDevModel->select();
        $goodsInfo = $this->goodsInfo($id, 'id,status,process_id');
        $bin = decbin($goodsInfo['process_id']);
        $length = strlen($bin);
        $aMenu = [];
        foreach ($menu as $v) {
            $aMenu[$v['code']] = $v;
        }
        $m = [];
        switch ($length) {
            case 1:
                $m = ['base', 'platform', 'supplier', 'specification', 'attribute', 'requirement'];
                break;
            case 5:
                $m = ['base', 'platform', 'supplier', 'specification', 'attribute', 'requirement', 'logs'];
                break;
            case 6:
                $m = ['base', 'platform', 'supplier', 'specification', 'attribute', 'requirement', 'logs'];
                break;
            case 8:
                $m = ['base', 'platform', 'supplier', 'specification', 'attribute', 'requirement', 'qc', 'logs'];
                break;
            case 10:
                $m = ['base', 'platform', 'supplier', 'specification', 'attribute', 'requirement', 'qc', 'logs'];
                break;
            case 11:
                $m = ['base', 'platform', 'supplier', 'specification', 'attribute', 'requirement', 'qc', 'declare', 'logs'];
                break;
            case 12:
                $m = ['base', 'platform', 'supplier', 'specification', 'attribute', 'requirement', 'qc', 'declare', 'images', 'logs'];
                break;
            case 14:
                $m = ['base', 'platform', 'supplier', 'specification', 'attribute', 'requirement', 'qc', 'declare', 'images', 'description', 'logs'];
                break;
            case 16:
                $m = ['base', 'platform', 'supplier', 'specification', 'attribute', 'requirement', 'qc', 'declare', 'images', 'description', 'logs'];
                break;
            case 18:
                $m = ['base', 'platform', 'supplier', 'specification', 'attribute', 'requirement', 'qc', 'declare', 'images', 'description', 'logs'];
                break;
            case 19:
                $m = ['base', 'platform', 'supplier', 'specification', 'attribute', 'requirement', 'qc', 'declare', 'images', 'description', 'logs'];
                break;
            case 20:
                $m = ['base', 'platform', 'supplier', 'specification', 'attribute', 'requirement', 'qc', 'declare', 'images', 'description', 'logs'];
                break;
            case 31:
                $m = ['base', 'platform', 'supplier', 'specification', 'attribute', 'requirement', 'qc', 'declare', 'images', 'description', 'logs'];
                break;
        }

        $k = array_keys($aMenu);
        $resultKey = array_intersect($k, $m);
        $result = [];
        foreach ($resultKey as $key) {
            $row = $aMenu[$key];
            $result[] = $row;
        }
        return $result;

    }

    public function saveImgRequirement($goods_id, $params)
    {
        Db::startTrans();
        try {
            if ($params['is_design'] == 1) {
                parent::saveImgRequirement($goods_id, $params);
            }
            $ModelGoodsDev = new ModelGoodsDev();
            $data = [];
            $data['update_time'] = time();
            $data['is_design'] = $params['is_design'];
            $ModelGoodsDev->allowField(true)->isUpdate(true)->save($data, ['goods_id' => $goods_id]);
            Db::commit();
        } catch (Exception $ex) {
            Db::rollback();
            throw $ex;
        }

    }

    public function getImgRequirement($goods_id)
    {
        $goodsDevInfo = $this->getBaseInfoForGoodsDev($goods_id,'is_design');
        $base = parent::getImgRequirement($goods_id);
        return array_merge($base, $goodsDevInfo);
    }

}
