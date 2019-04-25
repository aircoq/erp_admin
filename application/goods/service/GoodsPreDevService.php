<?php

namespace app\goods\service;

use app\common\model\GoodsDevelopLog;
use app\common\model\GoodsPreCategoryMap;
use app\common\model\GoodsCategoryMap;
use app\goods\service\GoodsHelp;
use app\common\cache\Cache;
use app\common\model\GoodsPreDev as GoodsPreDevModel;
use app\common\model\GoodsImages;
use think\Exception;
use think\Db;
use app\common\exception\JsonErrorException;
use app\api\service\Goods;
use app\index\service\User;
use app\common\traits\ConfigCommon;

/**
 * Created by NetBeans
 * User: ZhaiBin
 * Date: 2017/07/24
 * Time: 15:21
 */
class GoodsPreDevService
{
    use ConfigCommon;
    const CANCEL = -1;//取消
    const SUBMIT = 1;//提交
    const KFAUDIT = 16;//开发主管审核
    const KFAUDITFAIL = 17; //开发主管审核失败
    const XSAUDIT = 48;//销售主管审核
    const XSAUDITFAIL = 49;//销售主管审核失败
    const SKUAUDIT = 112;//查重
    const SKUAUDITFAIL = 113;//查重失败
    const SKUAUDITSUCCESS = 240;//查重成功
    const COMPLETE = 496;//完成


    /**
     * 保存/更新预开发产品
     * @access public
     * @param array $data
     * @param int $user_id
     * @throws Exception
     * @return array
     */
    public function save($data, $user_id)
    {
        $GoodsLogs = new GoodsLog();
        $data['id'] = param($data, 'id') ? $data['id'] : 0;
        $model = new GoodsPreDevModel();
        $action = 'add';
        if ($data['id']) {
            $good_info = $model->field('*')->where(['id' => $data['id']])->find();
            if (empty($good_info)) {
                throw new JsonErrorException("数据不存在");
            }
            if ($good_info['process_id'] == self::COMPLETE) {
                throw new JsonErrorException('预开发完成，不能再进行编辑操作！');
            }
            $action = 'mdf';
        }
        array_walk($data, 'trim');
        if ($action == 'add') {
            $goodsValidate = validate('goodsPreDev')->scene('insert');
        } else {
            $goodsValidate = validate('goodsPreDev')->scene('edit');
        }
        if (!$goodsValidate->check($data)) {
            throw new JsonErrorException($goodsValidate->getError());
        }
        $platform = [];
        if (param($data, 'platform')) {
            $platform = json_decode($data['platform'], true);
        }
        //判断是否有图片
        $images = [];
        if (param($data, 'thumb')) {
            //上传图片
            $images = json_decode($data['thumb'], true);
            if (!is_array($images)) {
                throw new JsonErrorException('图片格式不正确');
            }
        }

        $log = [];


        $this->formatData($data, $log);
        // 开启事务
        Db::startTrans();
        try {
            if ($data['id'] && $good_info['process_id'] == self::SKUAUDITSUCCESS) {
                $data['process_id'] = self::COMPLETE;
            }
            if ($data['id']) {
                $operation = true;
                $data['update_time'] = time();
            } else {
                $data['code'] = $this->getCode();
                $data['create_time'] = time();
                $data['create_id'] = $user_id;
                $GoodsLogs->createGoods();
                $operation = false;
            }
            //按钮为提交审核时，状态为 16
            if (param($data, 'audit') == 1) {
                $data['process_id'] = self::KFAUDIT;
                $GoodsLogs->submitAudit();
            }
            if (isset($data['auth_book'])) {
                if ($data['auth_book']) {
                    //上传图片
                    $file_name = date('YmdHi') . uniqid() . mt_rand(0, 999);
                    $data['auth_path'] = $this->uploadFile($data['auth_book'], 'auth_book', $file_name);
                } else {
                    $data['auth_path'] = '';
                }
            }
            $model->allowField(true)->isUpdate($operation)->save($data);//保存信息
            $pre_id = $data['id'] ? $data['id'] : $model->getLastInsID();
            if ($images) {
                //上传图片
                $this->saveImage($pre_id, $images);
            }
            if ($platform) {
                $this->saveGoodsCategoryMap($pre_id, $platform, $user_id);
            }
            $GoodsLogs->preSave($user_id, $pre_id, $data['process_id']);
            Db::commit();
            return [
                'status' => true,
                'data' => $this->row($pre_id),
            ];

        } catch (Exception $ex) {
            Db::rollback();
            throw $ex;
        }
    }


    private function row($id)
    {
        $model = new GoodsPreDevModel();
        $field = 'id, code, title, create_id, category_id, create_time, update_time, operator_id, process_id';
        $list = $model->field($field)->where('id', $id)->find();
        $goodsHelp = new GoodsHelp();
        $list['process'] = $this->process_btn[$list['process_id']]['btn_name'];
        isset($list['category_id']) ? $list['category'] = $goodsHelp->mapCategory($list['category_id']) : '';
        param($list, 'create_time') ? $list['create_time'] = date('Y-m-d H:i:s', (int)$list['create_time']) : '';
        isset($list['create_id']) ? $list['creator'] = $this->getUserNameById($list['create_id']) : '';
        param($list, 'update_time') ? $list['update_time'] = date('Y-m-d H:i:s', (int)$list['update_time']) : '';
        isset($list['operator_id']) ? $list['operator'] = $this->getUserNameById($list['operator_id']) : '';
        $list['is_edit'] = $list['is_create_sku'] = $list['is_complete'] = 0;
        if ($list['process_id'] == self::SUBMIT) {
            $list['is_edit'] = 1;
        } elseif ($list['process_id'] == self::SKUAUDITSUCCESS) {
            $list['is_create_sku'] = 1;
        } elseif ($list['process_id'] == self::COMPLETE) {
            $list['is_complete'] = 1;
        }
        return $list;
    }

    private function getImage($pre_id)
    {
        $GoodsImages = new GoodsImages();
        $ret = $GoodsImages->where("goods_pre_id", $pre_id)->select();
        $result = [];
        foreach ($ret as $v) {
            $result[$v['id']] = $v;
        }
        return $result;
    }

    private function saveImage($pre_id, $imgList)
    {

        $old = $this->getImage($pre_id);
        $postId = [];
        foreach ($imgList as $imgInfo) {
            $imgId = $imgInfo['id'];
            $imgContent = $imgInfo['path'];
            $is_update = $imgInfo['is_update'];
            $file_name = $pre_id . '-' . date('YmdHi') . uniqid() . mt_rand(0, 999);
            if (!$imgId) {
                $path = $this->uploadFile($imgContent, 'goods_images', $file_name);
                $img_insert_data = [
                    'goods_pre_id' => $pre_id,
                    'path' => $path,
                    'created_time' => time()
                ];
                $model = new GoodsImages();
                $model->allowField(true)->isUpdate(false)->save($img_insert_data);
            } else {
                $postId[] = $imgId;
                if ($is_update == 1) {
                    $path = $this->uploadFile($imgContent, 'goods_images', $file_name);
                    $img_insert_data = [
                        'goods_pre_id' => $pre_id,
                        'path' => $path,
                        'created_time' => time()
                    ];
                    $model = new GoodsImages();
                    $model->allowField(true)->isUpdate(false)->save($img_insert_data);
                }
            }
        }
        $delId = array_diff(array_keys($old), $postId);
        if ($delId) {
            $model = new GoodsImages();
            $model->where('id', 'in', $delId)->delete();
        }
    }

    /**
     * 格式数据
     * @param array $data
     * @param array $log
     * @return boolean
     */
    private function formatData(&$data, &$log)
    {
        $fields = ['length', 'width', 'height'];
        foreach ($fields as $field) {
            $data[$field] = isset($data[$field]) ? $data[$field] * 10 : 0;
        }
        if (isset($data['gross_profit'])) {
            $data['gross_profit'] /= 100;
        }
        if (isset($data['process_id']) && $data['process_id'] == self::KFAUDIT) {
            $data['process_id'] = self::KFAUDIT;
        } else {
            $data['process_id'] = self::SUBMIT;
        }
        // 备注
        if (isset($data['remark'])) {
            $log = ['remark' => $data['remark']];
        }
        // 平台销售状态
        $platform_sale = [];
        if (isset($data['platform_sale'])) {
            $platforms = json_decode($data['platform_sale'], true);
            foreach ($platforms as $platform) {
                $platform_sale[$platform['name']] = $platform['value_id'];
            }
            $GoodsHelp = new GoodsHelp();
            $data['platform'] = $GoodsHelp->getPlatform($platforms);
        }
        return true;
    }

    function convertGoodsDatas($goods, $param)
    {
        $channel_id = Cache::store('channel')->getChannelId($goods['dev_platform']);
        if ($channel_id === false) {
            throw  new Exception('无法获取该渠道id' . $channel_id);
        }
        $data = [
            'category_id' => $goods['category_id'],
            'name' => param($param, 'title'),
            'description' => $goods['description'],
            'tags' => $goods['tags'],
            'process_id' => 1968,
            'developer_id' => $goods['create_id'],//开发者
            'create_time' => time(),
            'update_time' => time(),
            'unit_id' => $goods['unit_id'],//单位
            'weight' => $goods['weight'],//重量
            'width' => $goods['width'],//宽
            'height' => $goods['height'],//高
            'depth' => $goods['length'],//长
            'is_packing' => $goods['is_packing'],//是否有包装
            'packing_id' => param($param, 'packing_id', 0),//前置包装材料
            'packing_back_id' => param($param, 'packing_back_id', 0),//后置包装材料
            'source_url' => param($param, 'source_url'),//来源地址
            'link' => param($param, 'purchase_url'),//来源地址
            'supplier_id' => param($param, 'supplier_id', 0),//供应商价格
            'cost_price' => $goods['purchase_price'],//采购价格
            'retail_price' => $goods['advice_price'],//建议零售价格
            'lowest_sale_price' => $goods['lowest_sale_price'],//最低限价
            'competitor_price' => $goods['competitor_price'],//竞争对手价格
            'brand_id' => $goods['brand_id'],//品牌id
            'is_impower' => param($param, 'is_impower', 0),//是否授权
            'warehouse_id' => param($param, 'warehouse_id', 0),//默认仓库
            'is_multi_warehouse' => param($param, 'is_multi_warehouse', 0),//是否存在多仓库
            'is_sampling' => param($param, 'is_sampling', 0),
            'platform_sale' => $goods['platform_sale'],//各平台上架情况
            'transport_property' => $goods['transport_property'],//物流属性
            'net_weight' => $goods['net_weight'],//毛重
            'tort_id' => $goods['tort_id'],//侵权风险id
            'attributes' => param($param, 'attributes', ''),//属性
            'skus' => param($param, 'skus', ''),
            'img_requirement' => param($param, 'img_requirement'),//修复要求
            'is_photo' => param($param, 'is_photo'),//是否拍照
            'photo_remark' => param($param, 'photo_remark'),//拍照
            'undisposed_img_url' => param($param, 'undisposed_img_url'),//拍照路径
            'channel_id' => $channel_id,
            'gross_profit' => param($param, 'gross_profit')//本平台毛利率
        ];

        return $data;
    }

    /**
     * 获取预开发产品code
     * @return string
     */
    private function getCode()
    {
        $day = date('Ymd');
        $redis = Cache::handler();
        $key = 'cache:GoodsPreDev:' . $day;
        $i = $redis->incr($key);
        if (1 == $i) {
            $expireTime = time() + 24 * 3600;
            $redis->expireAt($key, $expireTime);
        }
        $code = 'GP' . $day . substr('0000' . $i, -4);
        return $code;
    }

    private function getApiIpCfg()
    {
        $this->setConfigIdentification('api_ip');
        return $this->getConfigData();
    }

    /**
     * 获取预开发信息
     * @param int $id
     * @return array
     */
    public function getInfo($id)
    {
        $result = $this->find($id, '*');
        if ($result['process_id'] == self::SKUAUDITSUCCESS) {
            $result['is_create_sku'] = 1;
        }
        $goodsHelp = new GoodsHelp();
        //$result['tags'] = explode('\n', $result['tags']);
        //$result['properties'] = $goodsHelp->getProTransProperties($result['transport_property']);
        $result['platform_sale'] = $goodsHelp->getPlatformSale($result['platform']);
        $result['purchase_url'] = json_decode($result['purchase_url'], true);
        $result['gross_profit'] *= 100;
        $imgList = Db::name('goods_images')->field('id,path')->where(['goods_pre_id' => $id])->select();
        $result['thumb'] = [];
        $host = $this->getApiIpCfg();
        foreach ($imgList as $v) {
            $v['path'] = $host . "/" . $v['path'];
            $result['thumb'][] = $v;
        }
        $fields = ['length', 'width', 'height'];
        foreach ($fields as $field) {
            $result[$field] = isset($result[$field]) ? $result[$field] / 10 : 0;
        }
        if ($result['auth_path']) {
            $result['auth_book'] = $host . "/" . $result['auth_path'];
        } else {
            $result['auth_book'] = '';
        }
        $result['platform'] = $this->getGoodsCategoryMap($id);
        return $result;
    }

    /**
     * 检查预开发信息
     * @param unknown $id
     */
    function find($id = 0, $field = 'id')
    {
        $model = new GoodsPreDevModel();
        $result = $model->where(['id' => $id])->field($field)->find()->toArray();
        if (!$result) {
            throw new JsonErrorException('数据不存在');
        }
        return $result;
    }

    /**
     * 获取搜索条件
     * @param array $data
     * @return array
     */
    public function getWhere($data)
    {
        $wheres = [];
        //开发流程
        if (param($data, 'process_id')) {
            $search = isset($this->process_btn[$data['process_id']]) ? $this->process_btn[$data['process_id']]['where'] : [];
            if (is_array($search)) {
                $wheres = $search;
            } else {
                $wheres[] = ['exp', $search];
            }
        }

        //申请时间
        if (isset($data['create_time_start'])) {
            $wheres['create_time'] = ['>=', strtotime($data['create_time_start'])];
        }
        if (isset($data['create_time_end']) && !empty($wheres['create_time'])) {
            $wheres['create_time'] = [$wheres['create_time'], ['<=', strtotime($data['create_time_end'] . ' 23:59:59')]];
        } else if (isset($data['create_time_end'])) {
            $wheres['create_time'] = ['<=', strtotime($data['create_time_end'] . ' 23:59:59')];
        }

        //申请人
        if (param($data, 'create_id')) {
            $wheres['create_id'] = $data['create_id'];
        }

        //键值搜索
        if (param($data, 'search_key') && param($data, 'search_val')) {
            $wheres[$data['search_key']] = ['LIKE', $data['search_val'] . '%'];
        }

        return $wheres;
    }

    /**
     * 获取总数条数
     * @param array $where
     * @return int
     */
    public function getCount($where)
    {
        return GoodsPreDevModel::where($where)->count();
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
    public function getList($where, $page, $pageSize, $field = '*', $order = null)
    {
        $lists = GoodsPreDevModel::where($where)->field($field)->order($order)->page($page, $pageSize)->select();
        $goodsHelp = new GoodsHelp();
        foreach ($lists as &$list) {
            $list['process'] = $this->process_btn[$list['process_id']]['btn_name'];
            isset($list['category_id']) ? $list['category'] = $goodsHelp->mapCategory($list['category_id']) : '';
            param($list, 'create_time') ? $list['create_time'] = date('Y-m-d H:i:s', (int)$list['create_time']) : '';
            isset($list['create_id']) ? $list['creator'] = $this->getUserNameById($list['create_id']) : '';
            param($list, 'update_time') ? $list['update_time'] = date('Y-m-d H:i:s', (int)$list['update_time']) : '';
            isset($list['operator_id']) ? $list['operator'] = $this->getUserNameById($list['operator_id']) : '';
            $list['is_edit'] = $list['is_create_sku'] = $list['is_complete'] = 0;
            if ($list['process_id'] == self::SUBMIT) {
                $list['is_edit'] = 1;
            } elseif ($list['process_id'] == self::SKUAUDITSUCCESS) {
                $list['is_create_sku'] = 1;
            } elseif ($list['process_id'] == self::COMPLETE) {
                $list['is_complete'] = 1;
            }
        }
        return $lists;
    }


    // 流程详情
    private $processes = [
        '0' => ['process_id' => 0, 'name' => '全部', 'action' => [], 'status' => 1, 'sort' => 0],
        self::CANCEL => ['process_id' => self::CANCEL, 'name' => '作废', 'action' => [], 'status' => 1, 'sort' => 7],
        self::SUBMIT => ['process_id' => self::SUBMIT, 'name' => '未提交', 'action' => ['submit', 'cancel'], 'status' => 1, 'sort' => 1],
        self::KFAUDIT => ['process_id' => self::KFAUDIT, 'name' => '待开发主管审核 ', 'action' => ['kf_audit_success', 'kf_audit_fail'], 'status' => 1, 'sort' => 2],
        self::KFAUDITFAIL => ['process_id' => self::KFAUDITFAIL, 'name' => '开发主管审核未通过', 'action' => ['kf_audit_success', 'cancel'], 'status' => 1, 'sort' => 3],
        self::XSAUDIT => ['process_id' => self::XSAUDIT, 'name' => '待销售主管审核', 'action' => ['xs_audit_success', 'xs_audit_fail'], 'status' => 0, 'sort' => 0],
        self::XSAUDITFAIL => ['process_id' => self::XSAUDITFAIL, 'name' => '销售主管审核不通过', 'action' => ['xs_audit_success', 'cancel'], 'status' => 0, 'sort' => 0],
        self::SKUAUDIT => ['process_id' => self::SKUAUDIT, 'name' => '待sku查重', 'action' => ['sku_audit_success', 'sku_audit_fail'], 'status' => 1, 'sort' => 4],
        self::SKUAUDITFAIL => ['process_id' => self::SKUAUDITFAIL, 'name' => 'sku查重不通过', 'action' => ['sku_audit_success', 'cancel'], 'status' => 1, 'sort' => 5],
        self::SKUAUDITSUCCESS => ['process_id' => self::SKUAUDITSUCCESS, 'name' => 'sku查重通过', 'action' => [], 'status' => 0, 'sort' => 0],
        self::COMPLETE => ['process_id' => self::COMPLETE, 'name' => '预开发完成', 'action' => [], 'status' => 1, 'sort' => 6],
    ];


    private $process_btn = [
        0 => ['btn_name' => '全部', 'where' => []],
        self::CANCEL => ['btn_name' => '作废', 'where' => ['process_id' => -1]],
        self::SUBMIT => ['btn_name' => '未提交', 'where' => ['process_id' => 1]],    // 0001 创建成功
        self::KFAUDIT => ['btn_name' => '待开发主管审核', 'where' => ['process_id' => 16]],   // 提交成功 1 0000
        self::KFAUDITFAIL => ['btn_name' => '开发主管审核未通过', 'where' => ['process_id' => 17]],// 审核不通过 1 0001
        self::XSAUDIT => ['btn_name' => '待销售主管审核', 'where' => ['process_id' => 48]], //  审核通过 11 0000
        self::XSAUDITFAIL => ['btn_name' => '销售主管审核不通过', 'where' => ['process_id' => 49]],  // 11 0001
        self::SKUAUDIT => ['btn_name' => '待sku查重', 'where' => ['process_id' => 112]],  // 取样成功  1011 0000
        self::SKUAUDITFAIL => ['btn_name' => 'sku查重不通过', 'where' => ['process_id' => 113]], // 1011 0001,
        self::SKUAUDITSUCCESS => ['btn_name' => 'sku查重通过', 'where' => ['process_id' => 240]],  // 1 1011 0000,
        self::COMPLETE => ['btn_name' => '预开发完成', 'where' => ['process_id' => 496]]
    ];


    // 操作详情
    private $actions = [
        'submit' => ['btn_name' => '提交审核', 'url' => '', 'next_process' => self::KFAUDIT, 'prefix_process' => self::SUBMIT, 'remark' => false], // 待审核
        'cancel' => ['btn_name' => '作废', 'url' => '', 'next_process' => self::CANCEL, 'prefix_process' => self::SUBMIT, 'remark' => true],            // 作废
        'kf_audit_success' => ['btn_name' => '审核通过', 'url' => '', 'next_process' => self::SKUAUDIT, 'prefix_process' => 1, 'remark' => false], // 开发主管审核通过
        'kf_audit_fail' => ['btn_name' => '审核不通过', 'url' => '', 'next_process' => self::KFAUDITFAIL, 'prefix_process' => 1, 'remark' => true],  // 开发主管审核未通过
        'sku_audit_success' => ['btn_name' => '审核通过', 'url' => '', 'next_process' => self::COMPLETE, 'prefix_process' => self::KFAUDIT, 'remark' => false], // sku查重通过
        'sku_audit_fail' => ['btn_name' => '审核不通过', 'url' => '', 'next_process' => self::SKUAUDITFAIL, 'prefix_process' => self::KFAUDIT, 'remark' => true],  // sku查重不通过
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
            case 17:
                $bit = 65536; // 翻译完成
                $process_id = $process_id & 0x3fff;
                $sec_bit = $this->analyseProcess($process_id);
                $bit .= ',';
                $bit .= $sec_bit;
                break;
            case 16:
                $bit = 32768; // 翻译中 
                $process_id = $process_id & 0x3fff;
                $sec_bit = $this->analyseProcess($process_id);
                $bit .= ',';
                $bit .= $sec_bit;
                break;
            case 15:
                $bit = 16384; //待翻译分配
                $process_id = $process_id & 0x03fff;
                $sec_bit = $this->analyseProcess($process_id);
                $bit .= ',';
                $bit .= $sec_bit;
                break;
            case 14:
                $bit = 8192; // 图片完成（上传完成）
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
                $bit = 128; // 收货成功
                if (($process_id & 15) == 1) $bit = 129; // 质检失败
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
     * 获取流程处理列表
     */
    public function getProcessList()
    {
        $result = [];
        foreach ($this->processes as $vo) {
            $result[] = [
                'process_id' => $vo['process_id'],
                'title' => $vo['name'],
                'status' => $vo['status'],
                'sort' => $vo['sort']
            ];
        }
        return $result;
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
        $process = $this->analyseProcess($process_id);
        if (-1 != strpos(',', $process)) {
            $processes = explode(',', $process);
        } else {
            $processes[] = $process;
        }
        $name = '';
        foreach ($processes as $process_id) {
            $name .= isset($this->processes[$process_id]) ? $this->processes[$process_id]['name'] : '';
        }
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
        if (!isset($this->processes[$id])) {
            return $actions;
        }

        $process = $this->processes[$id];
        if ($process['action']) {
            foreach ($process['action'] as $op) {
                $actions[] = [
                    'btn_name' => $this->actions[$op]['btn_name'],
                    'url' => $this->actions[$op]['url'],
                    'remark' => $this->actions[$op]['remark'],
                    'code' => $op,

                ];
            }
        }

        return $actions;
    }

    /**
     * 流程处理
     * @param int $id
     * @param array $params
     * @param int $user_id
     * @return array
     * @throws Exception
     */
    public function handle($id, $params, $user_id)
    {
        if (!isset($params['code']) || empty($params['code'])) { // 检测code 不为空
            throw new JsonErrorException('操作编码不能缺少');
        }

        $goods = new GoodsPreDevModel();
        $goods_info = $this->find($id, 'id,process_id');
        if ($goods_info['process_id'] == -1) {
            throw new JsonErrorException('流程已作废，不能再进行其他操作！');
        }

        //根据当前流程判断用户权限
        //throw new JsonErrorException('对不起！您没有权限审核。该流程的操作人为：开发经理。');

        $processes = isset($this->processes[$goods_info['process_id']]) ? $this->processes[$goods_info['process_id']] : '';
        $action = isset($this->actions[$params['code']]) ? $this->actions[$params['code']] : '';

        if (empty($processes) || empty($processes['action']) || empty($action) || empty($action['next_process'])) {
            throw new JsonErrorException('没有找到相应的操作');
        }
        if (!in_array($params['code'], $processes['action'])) {
            throw new JsonErrorException('当前流程为:[ ' . $processes['name'] . ' ]，不能进行 :[ ' . $this->processes[$action['next_process']]['name'] . ' ] 操作！');
        }

        if (!empty($action['remark']) && empty($params['remark'])) { // 备注不能为空
            throw new JsonErrorException('备注不能为空');
        }
        $GoodsLog = new GoodsLog();
        // 处理流程
        Db::startTrans();
        try {
            $goods->where(['id' => $id])->update(['process_id' => $action['next_process']]);
            if ($params['code'] == 'sku_audit_fail') {
                $GoodsLog->checkingDisagree($params['remark']);
            } else if ($params['code'] == 'sku_audit_success') {
                $GoodsLog->checkingAgree();
            } else if ($params['code'] == 'kf_audit_success') {
                $GoodsLog->agree();
            } else if ($params['code'] == 'kf_audit_fail') {
                $GoodsLog->disagree($params['remark']);
            }
            $GoodsLog->preSave($user_id, $id, $action['next_process']);
            Db::commit();
            return ['message'=>'操作成功','data'=>$this->row($id)];
        } catch (Exception $ex) {
            Db::rollback();
            throw new JsonErrorException('操作失败:' . $ex->getMessage(), 101);
        }
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
     * @param $baseData
     * @param $pathName
     * @param string $fileName
     * @return string
     * @author starzhan <397041849@qq.com>
     */
    public function uploadFile($baseData, $pathName, $fileName = '')
    {
        if (!$baseData) {
            throw new JsonErrorException('未检测到文件');
        }
        $dir = date('Y-m-d');
        $savePath = 'upload' . DS . $pathName . DS . $dir;
        $base_path = ROOT_PATH . 'public' . DS . $savePath;
        if (!is_dir($base_path) && !mkdir($base_path, 0777, true)) {
            throw new JsonErrorException('目录创建失败');
        }
        try {
            $fileName = $fileName . '.jpg';
            $start = strpos($baseData, ',');
            $content = substr($baseData, $start + 1);
            file_put_contents($base_path . DS . $fileName, base64_decode(str_replace(" ", "+", $content)));
            return $savePath . DS . $fileName;

        } catch (Exception $ex) {
            throw new JsonErrorException($ex->getMessage());
        }
    }

    /**
     * 获取用户名 根据id
     * @param int $id
     * @return string
     */
    private function getUserNameById($id)
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

    public function saveGoodsCategoryMap($id, $platform, $user_id = 0)
    {
        if (!$id || !$platform) {
            throw new Exception('id或platform不能为空');
        }
        $GoodsCategoryMap = new GoodsPreCategoryMap();
        $aDatas = [];
        foreach ($platform as $v) {
            $aData = [];
            $aData['pre_goods_id'] = $id;
            $aData['channel_id'] = $v['channel_id'];
            $aData['channel_category_id'] = $v['channel_category_id'];
            $aData['site_id'] = $v['site_id'] ?? 0;
            $aData['create_time'] = time();
            $aData['update_time'] = time();
            $aData['operator_id'] = $user_id;
            $aDatas[] = $aData;
        }
        Db::startTrans();
        try {
            $GoodsCategoryMap->where('pre_goods_id', $id)->delete();
            if ($aDatas) {
                $GoodsCategoryMap->allowField(true)->insertAll($aDatas);
            } else {
                throw  new Exception('添加失败,platform为空');
            }
            Db::commit();
            return '';
        } catch (Exception $ex) {
            Db::rollback();
            throw  new Exception($ex->getMessage());
        }
    }

    public function getGoodsCategoryMap($goods_id)
    {
        if (!$goods_id) {
            return [];
        }
        $GoodsCategoryMap = new GoodsPreCategoryMap();
        $aGoodsCategoryMap = $GoodsCategoryMap->where('pre_goods_id', $goods_id)->select();
        $result = [];
        if ($aGoodsCategoryMap) {
            foreach ($aGoodsCategoryMap as $v) {
                $row = [];
                $row['goods_id'] = $v->pre_goods_id;
                $row['channel_id'] = $v->channel_id;
                $row['site_id'] = $v->site_id;
                $row['channel_category_id'] = $v->channel_category_id;
                $row['label'] = '';
                $row['path'] = '[]';
                $Channel = GoodsCategoryMap::getChannel($v->channel_id);
                $sile = [];
                if ($Channel && $v->site_id) {
                    $sile = GoodsCategoryMap::getsite($Channel['name'], $v->site_id);
                }
                $label = [];
                $path = [];
                $channel_category = GoodsCategoryMap::getCategoty($Channel['name'], $v->site_id, $row['channel_category_id']);
                $label[] = $Channel['name'];
                $path[] = ['label' => $Channel['name'], 'is_site' => 1, 'id' => $v->channel_id];
                if ($sile) {
                    $label[] = $sile['name'];
                    $path[] = ['id' => $v->site_id, 'code' => $sile['code'], 'label' => $sile['name']];
                }
                foreach ($channel_category as $cate) {
                    $label[] = $cate['category_name'];
                    $path[] = ['id' => $cate['category_id'], 'label' => $cate['category_name']];
                }
                $row['label'] = implode('>>', $label);
                $row['path'] = json_encode($path, JSON_UNESCAPED_UNICODE);
                $result[] = $row;
            }
        }
        return $result;

    }


    public function getLog($id)
    {

        $where = ['pre_goods_id' => $id, 'type' => 1];
        $lists = GoodsDevelopLog::where($where)->select();
        $goodsdev = new Goodsdev();
        $goodsLog = new GoodsLog();
        foreach ($lists as &$list) {
            $list['operator'] = $this->getUserNameById($list['operator_id']);
            $list['process'] = $goodsdev->getProcessBtnNameById($list['process_id']);
            $list['remark'] = $goodsLog->getRemark($list['remark']);
            unset($list['operator_id'], $list['process_id'], $list['id']);
        }
        return $lists;
    }
}
