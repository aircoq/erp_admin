<?php
namespace app\goods\service;

use app\common\model\GoodsDevelopLog;
use app\goods\service\GoodsHelp;
use app\common\cache\Cache;
use app\common\model\GoodsPreDev as GoodsPreDevModel;
use think\Exception;
use think\Db;

/**
 * Created by NetBeans
 * User: ZhaiBin
 * Date: 2017/07/24
 * Time: 15:21
 */
class GoodsPreDev
{
    
    /**
     * 保存预开发产品
     * @access public
     * @param array $data
     * @param int $user_id
     * @throws Exception
     * @return boolean
     */
    public function save($data, $user_id)
    {	
        array_walk($data, 'trim');
        $goodsValidate = validate('goodsPreDev');
        if (!$goodsValidate->check($data)) {
            throw new Exception($goodsValidate->getError());
        }
        $log = [];
        $this->formatData($data, $log);
        // 开启事务
        Db::startTrans();
        try {
            $data['code']        = $this->getCode();
            $data['create_time'] = time();
            $data['create_id']   = $user_id;
            $model = new GoodsPreDevModel();
            $model->allowField(true)->isUpdate(false)->save($data);
            Db::commit();
            return true;
        } catch (Exception $ex) {
            Db::rollback();
            throw $ex;
        }
        return true;
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
        foreach($fields as $field) {
            $data[$field] = isset($data[$field]) ? $data[$field] * 10 : 0;
        }
        $data['gross_profit'] /= 100;
        if (isset($data['process_id']) && $data['process_id'] == 16) {
            $data['process_id'] = 16;
        } else {
            $data['process_id'] = 1;
        }
        // 物流属性
        if (isset($data['properties'])) {
            $properties = json_decode($data['properties'], true);
            $goodsHelp = new GoodsHelp();
            $info['transport_property'] = $goodsHelp->formatTransportProperty($properties);
            $goodsHelp->checkTransportProperty($info['transport_property']);
        }
        // 产品关键词
        $tags = '';
        foreach (json_decode($data['tags'], true) as $tag) {
            $tags .= ($tags ? '\n' : '') . $tag;
        }
        $data['tags'] = $tags;
        // 备注
        if (isset($data['remark'])) {
            $log = ['remark' => $data['remark']];
        }
        // 平台销售状态
        $platform_sale = [];
        if (isset($data['platform_sale'])) {
            $platforms = json_decode($data['platform_sale'], true);
            foreach($platforms as $platform) {
                $platform_sale[$platform['name']] = $platform['value_id'];
            }
        }
        $data['platform_sale'] = json_encode($platform_sale);
        
        return true;
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
            $expireTime = time() + 24*3600;
            $redis->expireAt($key, $expireTime);
        }
        $code = 'GP' . $day . substr('0000' . $i, -4);
        return $code;
    }
    
    /**
     * 获取预开发信息
     * @param int $id
     * @return array
     */
    public function getInfo($id) 
    {
        $model = new GoodsPreDevModel();
        $result = $model->where(['id' => $id])->field(true)->find();
        if (!$result) {
            throw new Exception('资源不存在');
        }
        $goodsHelp = new GoodsHelp();
        $result['tags'] = explode('\n', $result['tags']);
        $result['properties'] = $goodsHelp->getProTransProperties($result['transport_property']);
        $result['platform_sale'] = $goodsHelp->resolvePlatformSale($result['platform_sale']);
        $result['gross_profit']   *= 100;
        $fields = ['length', 'width', 'height'];
        foreach($fields as $field) {
            $result[$field] = isset($result[$field]) ? $result[$field] / 10 : 0;
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
        if (isset($data['process_id']) && $data['process_id']) {
            $search = isset($this->process_btn[$data['process_id']]) ? $this->process_btn[$data['process_id']]['where'] : [];
            if (is_array($search)) {
                $wheres = $search;
            } else {
                $wheres[] = ['exp', $search];
            }
        }
        
        if (isset($data['create_time_start'])) {
            $wheres['create_time']= ['>=', strtotime($data['create_time_start'])];
        }
        
        if (isset($data['create_time_end']) && !empty($wheres['create_time'])) {
            $wheres['create_time']= [$wheres['create_time'], ['<=', strtotime($data['create_time_end'] . ' 23:59:59')]];
        } else if (isset($data['create_time_end'])) {
            $wheres['create_time']= ['<=', strtotime($data['create_time_end'] . ' 23:59:59')];
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
        foreach($lists as &$list) {
            $list['process'] = $this->getProcessNameById($list['process_id']);
            isset($list['category_id']) ? $list['category'] = $goodsHelp->mapCategory($list['category_id']) : '';
            isset($list['create_time']) ? $list['create_time'] = date('Y-m-d H:i:s', (int)$list['create_time']) : '';
            isset($list['create_id']) ? $list['creator'] = $this->getUserNameById($list['create_id']) : '';
            isset($list['update_time']) ? $list['update_time'] = date('Y-m-d H:i:s', (int)$list['update_time']) : '';
            isset($list['operator_id']) ? $list['operator'] = $this->getUserNameById($list['operator_id']) : '';
        }
        return $lists;
    }
    
    // 流程详情
    private $processes = [
        '0'  => ['process_id' => 0,  'name' => '作废',  'action' => []],
        '1'  => ['process_id' => 1,  'name' => '未提交','action' => ['submit', 'cancel']],
        '16' => ['process_id' => 16, 'name' => '待审核', 'action' => ['audit_success', 'audit_fail']],
        '17' => ['process_id' => 17, 'name' => '开发主管审核失败', 'action' => ['audit_success', 'audit_fail']],
    ];
    
    // 操作详情
    private $actions = [
        'submit'           => ['btn_name' => '提交审核', 'url' => '','next_process' => 0x10, 'prefix_process' => 0xfffffff0, 'remark' => false, 'batch' => true], // 待审核
        'cancel'           => ['btn_name' => '作废', 'url' => '', 'next_process' => 0x40000000, 'prefix_process' =>0xfffffffff, 'remark' => true, 'log_process' => 0x40000000],            // 作废
        'audit_success'    => ['btn_name' => '审核通过', 'url' => '', 'next_process' => 0x20, 'prefix_process' => 0xfffffff0, 'remark' => false, 'batch' => true], // 审核成功
        'audit_fail'       => ['btn_name' => '审核不通过', 'url' => '', 'next_process' => 0x11, 'prefix_process' => 0xfffffff1, 'remark' => true],  // 审核不通过
        'sampling'         => ['btn_name' => '采样下单', 'url' => '', 'next_process' => 0x21, 'prefix_process' => 0xfffff31, 'batch' => true],      // 等待收样
        'recSample'        => ['btn_name' => '接收样品', 'url' => '', 'next_process' => 0x80, 'prefix_process' => 0xfffffff0, 'batch' => true],    // 待质检
        'no_sampling'      => ['btn_name' => '不取样', 'url' => '', 'next_process' => 0x3B0, 'prefix_process' => 0xffffffff0, 'log_process' => 513, 'batch' => true],   //  不取样 'handler' => 'checkSupplier'
        'qc_fail'          => ['btn_name' => '质检不通过', 'url' => '', 'next_process' => 0x81, 'prefix_process' => 0xfffffff1, 'remark' => true],    // 质检不通过
        'qc_success'       => ['btn_name' => '质检通过', 'url' => '', 'next_process' => 0x100, 'prefix_process' =>0xfffffff0, 'batch' => true],   // 质检通过, 待供应商审核 'handler' => 'checkSupplier'
        // 'supplier_fail'    => ['btn_name' => '供应商审核失败', 'url' => '', 'next_process' => 0x102, 'prefix_process' => 0xfffffff2], // 没测
        // 'supplier_success' => ['btn_name' => '供应商审核通过', 'url' => '', 'next_process' => 0x200, 'prefix_process' => 0xfffffff0], // sku待生成
        'qc_confirm_fail'   => ['btn_name' => '退回质检', 'url' => '', 'next_process' => 0x101, 'prefix_process' => 0xfffffff1, 'remark' => true],
        'qc_confirm_success'=> ['btn_name' => '质检确认通过', 'url' => '', 'next_process' => 0x200, 'prefix_process' => 0xfffffff0, 'batch' => true], // sku待生成
        'generateSku'      => ['btn_name' => '生成SKU', 'url' => '', 'next_process' => 0x400, 'prefix_process' => 0xfffffff0, 'handler' => 'generateSpu'], // 已生成sku
        'photo'            => ['btn_name' => '拍图', 'url' => '', 'next_process' => 0x4800, 'prefix_process' => 0xfffffff0, 'log_process' => 2048],       // 需要拍照
        'rec_photo_sample' => ['btn_name' => '接收样品', 'url' => '', 'next_process' => 0x801, 'prefix_process' => 0xfffffff1, 'log_process' => 2049, 'execute' => true], // 拍照等待样品
        'photo_complete'   => ['btn_name' => '拍图完成', 'url' => '', 'next_process' => 0x802, 'prefix_process' => 0xfffffff2, 'log_process' => 2050, 'batch' => true],
        'photo_audit_fail' => ['btn_name' => '拍图审核不通过', 'url' => '', 'next_process' => 0x803, 'prefix_process' => 0xfffffff3, 'remark' => true,'log_process' => 2051],
        'photo_audit_success'=> ['btn_name' => '拍图审核通过', 'url' => '', 'next_process' => 0x1000, 'prefix_process' => 0xfffffff0,'log_process' => 4096, 'batch' => true],
        'no_photo'           => ['btn_name' => '不拍图', 'url' => '', 'next_process' => 0x1000, 'prefix_process' => 0xfffffff0, 'log_process' => 4096, 'remark' => true],
        'photoshop_allocate' => ['btn_name' => '分配修图', 'url'=> '', 'next_process' => 0x5001, 'prefix_process' => 0xfffffff1, 'log_process' => 4097, 'execute' => true],
        'photoshop_complete' => ['btn_name' => '修图完成', 'url' => '', 'next_process' => 0x1002, 'prefix_process' => 0xfffffff2, 'log_process' => 4098, 'batch' => true],
        'photoshop_audit_fail'=> ['btn_name' => '修图审核不通过', 'url' => '', 'next_process' => 0x1003, 'prefix_process' => 0xfffffff3, 'remark' => true, 'log_proces' => 4099],
        'photoshop_audit_success'=> ['btn_name' => '修图审核通过', 'url' => '', 'next_process' => 0x1004, 'prefix_process' => 0xfffffff4, 'log_proces' => 4100, 'batch' => true],
        'upload'             => ['btn_name' => '图片上传完成', 'url' => '', 'next_process' => 0x2000, 'prefix_process' => 0xfffffff0, 'log_process' => 8192, 'handler' => 'checkProcess'],
        'translate_allocate' => ['btn_name' => '翻译分配', 'url' => '', 'next_process' => 0x8000, 'prefix_process' => 0xffffffff, 'log_process' =>32768],
        'translate_complete' => ['btn_name' => '翻译完成', 'url' => '', 'next_process' => 0x10000, 'prefix_process' => 0xffffffff, 'log_process' => 65536, 'handler' => 'checkProcess', 'batch' => true],
        'onSale'             => ['btn_name' => '发布', 'url' => 'goodsdev/sale', 'next_process' => 0x10000000, 'prefix_process' => 0xfffffff0, 'log_process' =>536870912, 'handler' => 'onSale']
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
        switch($length) {
            case 31:
                $bit = pow(2,30); // 已作废
            break;
            case 30:
                $bit = pow(2,29); // 待上架
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
                if (($process_id&15) == 1) $bit = 4097; // 修图中（修图分配完成)
                elseif (($process_id&15) == 2) $bit = 4098; // 拍图完成,待审核
                elseif (($process_id&15) == 3) $bit = 4099; // 拍图不通过
                elseif (($process_id&15) == 4) $bit = 4100; // 拍图通过, 待上传
            break;
            case 12:
                $bit = 2048; // 待接样
                if (($process_id&15) == 1) $bit = 2049; // 接样成功，待拍图
                elseif (($process_id&15) == 2) $bit = 2050; // 拍图完成
                elseif (($process_id&15) == 3) $bit = 2051; // 拍图不通过
                elseif (($process_id&15) == 4) $bit = 2052; // 
            break;
            case 11: // sku已生成
                $bit = 1024;
            break;
            case 10: // 供应商审核通过
                $bit = 512;
            break;
            case 9: // 质检通过
                $bit = 256; // 质检通过
                if (($process_id&15) == 1) $bit = 257; // 不取样
                if (($process_id&15) == 2) $bit = 258; // 供应商审核失败
            break;
            case 8:
                $bit = 128; // 收货成功
                if (($process_id&15) == 1) $bit = 129; // 质检失败
            break;
            case 7: // 预留
                // $bit = 64;
            break;
            case 6:// 审核通过
                $bit = 32;
                if (($process_id&15) == 1) $bit = 33; // 取样下单
            break;
            case 5: // 已提交-待审核
                $bit = 16;
                if (($process_id&15) == 1) $bit = 17;// 审核失败
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
        foreach($this->process_btn as $process_id => $v) {
            $lists[] = [
                'process_id' => $process_id,
                'btn_name'   => $v['btn_name']
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
        $process= $this->analyseProcess($process_id);
        if (-1 != strpos(',', $process)) {
            $processes = explode(',', $process);
        } else {
            $processes[] = $process;
        }
        $name = '';
        foreach($processes as $process_id) {
            $name .= isset($this->processes[$process_id]) ? $this->processes[$process_id]['name']: '';
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
        $id = $this->analyseProcess($id);
        $ids = explode(',', $id);
        foreach($ids as $id) {
            if (!isset($this->processes[$id])) {
                continue;
            }
            foreach($this->processes[$id]['action'] as $action)  {
                if (isset($this->actions[$action]) && (!$level || !empty($this->actions[$action]['batch']))) {
                    $actions[] = [
                        'btn_name' => $this->actions[$action]['btn_name'],
                        'url'      => isset($this->actions[$action]['url'])&&!empty($this->actions[$action]['url']) ? $this->actions[$action]['url'] : 'goodsdev/process',
                        'remark'   => isset($this->actions[$action]['remark']) ? $this->actions[$action]['remark'] : false,
                        'code'     => $action,
                        'execute' => isset($this->actions[$action]['execute']) ? $this->actions[$action]['execute'] : false,
                    ];
                }
            }
        }
        return $actions;
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
        $goods_info = $goods->where(['id' => $id, 'status' => 0])->field('process_id')->find()->toArray();        
        if (!isset($params['code']) || empty($params['code'])) { // 检测code 不为空
            throw new Exception('操作编码不能缺少');
        }
        if (!$goods_info) { // 获取到产品信息
            throw new Exception('不存在此产品');
        }
        // 分析流程ID
        $process= $this->analyseProcess($goods_info['process_id']);
        if (-1 == strpos(',', $process)) {
            $processes[] = $process;
        } else {
            $processes  = explode(',', $process);
        }
        foreach($processes as $process_id) { // 检测code是否有相关的action
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
        // 处理流程
        Db::startTrans();
        try {
            $update_process_id = ($goods_info['process_id'] | $action['next_process']) & $action['prefix_process'];
            $goods->where(['id' => $id])->update(['process_id' => $update_process_id]);
            $log = [
                'goods_id' => $id,
                'process_id' => isset($action['log_process']) ? $action['log_process'] : $action['next_process'],
                'operator_id' => $user_id,
                'remark' => isset($params['remark']) ? $params['remark'] : '',
                'create_time' => time()
            ];
            $goodsDevelopLog = new GoodsDevelopLog();
            $goodsDevelopLog->save($log);
            if (!empty($action['handler'])) {
                $this->$action['handler']($id);
            }
            Db::commit();
        } catch (Exception $ex) {
            Db::rollback();
            throw new Exception('操作失败', 101);
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
        foreach($attributes as $attribute) {
            $attribute_info = [];
            $list          = [];
            foreach($category_attributes as $list) {
                if ($list['attribute_id'] == $attribute['attribute_id']) {
                    $attribute_info = $list;
                    break;
                }
            }
            if (empty($attribute_info)) {
                continue;
            }
            $alias                = $attribute_info['is_alias'];
            $list['attribute_id'] = $attribute['attribute_id'];
            $list['name']         = $attribute_info['name'];
            $list['attribute_value'] = [];
            foreach($attribute['attribute_value'] as $value) {
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
                'name'         => $attribute_info['name']
            ];
        }
        $sku_lists = [];
        $new_lists = $goodsHelp->getBaseSkuLists($lists, $sku_lists, $headers);
        if (empty($new_lists)) {
            $new_lists[] = [
                'thumb'        => '',
                'sku'          => '',
                'alias_sku'    => [],
                'id'           => 0,
                'name'         => '',
                'status'       =>  0,
                'cost_price'   => 0.00,
                'retail_price' => 0.00,
                'weight'       =>  $weight,
                'enabled'      => false
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
        
        foreach($lists as &$list) {
            // $list['create_time'] = $list['create_time'] ? date('Y-m-d H:i:s', $list['create_time']) : '';
            $list['operator']    = $this->getUserNameById($list['operator_id']);
            $list['process']     = $this->getProcessBtnNameById($list['process_id']);
            unset($list['id']);
        }
        return $lists;
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
}
