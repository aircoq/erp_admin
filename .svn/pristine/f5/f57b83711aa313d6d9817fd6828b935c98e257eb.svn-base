<?php


namespace app\goods\service;

use app\common\model\GoodsDevelopLog;
use app\common\model\Goods;
use app\common\cache\Cache;
use think\Exception;

class GoodsLog
{

    /**
     * 若修改数据超过
     */
    const LIMIT_SIZE = 5;

    protected $spuField = [
        'category_id' => '分类',
        'name' => '名称',
        'brand_id' => '品牌',
        'tort_id' => '侵权风险',
        'lowest_sale_price' => '产品最低限价',
        'transport_property' => '物流属性',
        'declare_name' => '中文报关名称',
        'declare_en_name' => '英文报关名称',
        'hs_code' => '海关编码',
        'developer_id' => '开发员',
        'purchaser_id' => '采购员',
        'width' => '宽',
        'height' => '高',
        'depth' => '长',
        'net_weight' => '净重',
        'weight' => '重量',
        'is_packing' => '是否含包装',
        'packing_id' => '包装材料',
        'unit_id' => '单位',
        'warehouse_id' => '默认仓库',
        'is_multi_warehouse' => '是否存在于多仓库',
        'sales_status' => '出售状态',
        'platform' => '各平台上架情况',
        'supplier_id' => '默认供应商',
        'pre_sale' => '是否支持空卖'
    ];
    protected $skuField = [
        'sku_attributes' => '属性',
        'cost_price' => '成本价',
        'retail_price' => '零售价',
        'market_price' => '市场价',
        'weight' => '产品重量',
        'status' => '状态',
        'length' => '长度',
        'width' => '宽度',
        'height' => '高度',
        'auto_update_time' => '校验时间',
        'old_weight' => '原重量'
    ];

    protected $urlField = [
        'source_url' => '参考地址'
    ];
    protected $langField = [
        'description' => '描述'
    ];


    protected $LogData = [];

    public function addUrl($url)
    {
        $list = [];
        $list['type'] = '参考地址';
        $list['val'] = $url;
        $list['data'] = [];
        $list['exec'] = 'add';
        $this->LogData[] = $list;
        return $this;
    }

    public function mdfUrl($url, $old, $new)
    {
        $data = $this->mdfUrlData($old, $new);

        $info = [];
        foreach ($data as $key) {
            $row = [];
            $row['old'] = $old[$key];
            $row['new'] = $new[$key];
            $info[$key] = $row;
        }
        $this->mdfUrlItem($url, $info);
        return $this;
    }

    public function delUrl($url)
    {
        $list = [];
        $list['type'] = '参考地址';
        $list['val'] = $url;
        $list['data'] = [];
        $list['exec'] = 'del';
        $this->LogData[] = $list;
        return $this;
    }

    protected function getLangMap()
    {
        $langArr = Cache::store('lang')->getLang();
        $lang = [];
        foreach ($langArr as $v) {
            $lang[$v['id']] = $v['name'];
        }
        return $lang;
    }

    public function delLang($lang_id)
    {
        $map = $this->getLangMap();
        $list = [];
        $list['type'] = '描述';
        $list['val'] = $map[$lang_id];
        $list['data'] = [];
        $list['exec'] = 'del';
        $this->LogData[] = $list;
        return $this;
    }

    public function delSku($sku)
    {
        $list = [];
        $list['type'] = 'sku';
        $list['val'] = $sku;
        $list['data'] = [];
        $list['exec'] = 'del';
        $this->LogData[] = $list;
        return $this;
    }

    public function delSpu($spu)
    {
        $list = [];
        $list['type'] = 'spu';
        $list['val'] = $spu;
        $list['data'] = [];
        $list['exec'] = 'del';
        $this->LogData[] = $list;
        return $this;
    }

    public function addLang($lang_id)
    {
        $map = $this->getLangMap();
        $list = [];
        $list['type'] = '描述';
        $list['val'] = $map[$lang_id];
        $list['data'] = [];
        $list['exec'] = 'add';
        $this->LogData[] = $list;
        return $this;
    }

    public function mdfLang($lang_id, $old, $new)
    {
        $data = $this->mdfLangData($old, $new);
        $info = [];
        foreach ($data as $key) {
            $row = [];
            $row['old'] = $old[$key];
            $row['new'] = $new[$key];
            $info[$key] = $row;
        }
        $this->mdfLangItem($lang_id, $info);
        return $this;
    }


    public function addSku($sku)
    {
        $list = [];
        $list['type'] = 'sku';
        $list['val'] = $sku;
        $list['data'] = [];
        $list['exec'] = 'add';
        $this->LogData[] = $list;
        return $this;
    }

    public function addSpu($spu)
    {
        $list = [];
        $list['type'] = 'spu';
        $list['val'] = $spu;
        $list['data'] = [];
        $list['exec'] = 'add';
        $this->LogData[] = $list;
        return $this;
    }

    public function createGoods($text = '')
    {
        $list = [];
        $list['type'] = '产品';
        $list['val'] = $text;
        $list['data'] = [];
        $list['exec'] = 'create';
        $this->LogData[] = $list;
        return $this;
    }

    public function submitAudit($text = '')
    {
        $list = [];
        $list['type'] = '提交审批';
        $list['val'] = $text;
        $list['data'] = [];
        $list['exec'] = 'submit_audit';
        $this->LogData[] = $list;
        return $this;
    }

    public function agree($text = '')
    {
        $list = [];
        $list['type'] = '同意';
        $list['val'] = $text;
        $list['data'] = [];
        $list['exec'] = 'agree';
        $this->LogData[] = $list;
        return $this;
    }

    public function disagree($text = '')
    {
        $list = [];
        $list['type'] = '不同意';
        $list['val'] = $text;
        $list['data'] = [];
        $list['exec'] = 'disagree';
        $this->LogData[] = $list;
        return $this;
    }

    public function checkingAgree($text = '')
    {
        $list = [];
        $list['type'] = '查重审核';
        $list['val'] = $text;
        $list['data'] = [];
        $list['exec'] = 'checking_agree';
        $this->LogData[] = $list;
        return $this;
    }

    public function checkingDisagree($text = '')
    {
        $list = [];
        $list['type'] = '查重审核';
        $list['val'] = $text;
        $list['data'] = [];
        $list['exec'] = 'checking_disagree';
        $this->LogData[] = $list;
        return $this;
    }


    protected function mdfSkuItem($sku, $info)
    {

        $list = [];
        $list['type'] = 'sku';
        $list['val'] = $sku;
        $list['data'] = $info;
        $list['exec'] = 'mdf';
        $this->LogData[] = $list;

    }

    protected function mdfLangItem($lang_id, $info)
    {
        $map = $this->getLangMap();
        $list = [];
        $list['type'] = 'lang';
        $list['val'] = $map[$lang_id];
        $list['data'] = $info;
        $list['exec'] = 'mdf';
        $this->LogData[] = $list;
    }

    protected function mdfUrlItem($url, $info)
    {
        $list = [];
        $list['type'] = 'url';
        $list['val'] = '';
        $list['data'] = $info;
        $list['exec'] = 'mdf';
        $this->LogData[] = $list;
    }


    protected function mdfSpuItem($sku, $info)
    {

        $list = [];
        $list['type'] = 'spu';
        $list['val'] = $sku;
        $list['data'] = $info;
        $list['exec'] = 'mdf';
        $this->LogData[] = $list;

    }

    public function mdfSpu($spu, $old, $new)
    {
        $data = $this->mdfSpuData($old, $new);

        $info = [];
        foreach ($data as $key) {
            $row = [];
            $row['old'] = $old[$key];
            $row['new'] = $new[$key];
            $info[$key] = $row;
        }
        $this->mdfSpuItem($spu, $info);
        return $this;
    }

    protected function mdfSpuData($old, $new)
    {
        $data = [];
        foreach ($new as $key => $v) {
            if (in_array($key, array_keys($this->spuField))) {
                if ($v != $old[$key]) {
                    $data[] = $key;
                }
            }
        }
        return $data;
    }

    protected function mdfUrlData($old, $new)
    {
        $data = [];
        foreach ($new as $key => $v) {
            if (in_array($key, array_keys($this->urlField))) {
                if ($v != $old[$key]) {
                    $data[] = $key;
                }
            }
        }
        return $data;
    }

    protected function mdfLangData($old, $new)
    {
        $data = [];
        foreach ($new as $key => $v) {
            if (in_array($key, array_keys($this->langField))) {
                if ($v != $old[$key]) {
                    $data[] = $key;
                }
            }
        }
        return $data;
    }

    public function mdfSku($sku, $old, $new)
    {
        $data = $this->mdfSkuData($old, $new);
        $info = [];
        foreach ($data as $key) {
            $row = [];
            $row['old'] = $old[$key];
            $row['new'] = $new[$key];
            $info[$key] = $row;
        }
        $this->mdfSkuItem($sku, $info);
        return $this;
    }

    public function addSourceUrl($url)
    {

    }

    protected function mdfSkuData($old, $new)
    {
        $data = [];
        foreach ($new as $key => $v) {
            if ($key == 'sku_attributes') {
                $v = preg_replace("/(\s|\&nbsp\;|　|\xc2\xa0)/", "", $v);
                $old[$key] = preg_replace("/(\s|\&nbsp\;|　|\xc2\xa0)/", "", $old[$key]);
            }
            if (in_array($key, array_keys($this->skuField))) {
                if ($v != $old[$key]) {
                    $data[] = $key;
                }
            }
        }
        return $data;
    }

    protected function getTitle($list)
    {
        if (in_array($list['type'], ['spu', 'sku'])) {
            return $list['type'] . ":{$list['val']};";
        }
        return '';
    }

    protected function getText()
    {
        $ret = [];
        foreach ($this->LogData as $list) {
            $total = count($list['data']);
            if ($total > self::LIMIT_SIZE) {
                $page_size = self::LIMIT_SIZE;
                $total_page = ceil($total / $page_size);
                for ($page = 1; $page < $total_page; $page++) {
                    $offset = ($page - 1) * $page_size;
                    $tmp1 = $list;
                    $tmp1['data'] = array_slice($list['data'], $offset, $page_size);
                    $ret[] = $tmp1;
                }
            } else {
                $ret[] = $list;
            }
        }
        $tmp = [];
        foreach ($ret as $list) {
            $result = '';
            if ($list['exec'] == 'mdf') {
                if (!$list['data']) {
                    continue;
                }
                $exec = '修改';
                $title = $this->getTitle($list);
                $result .= $exec . $title;
                $arr_temp = [];
                foreach ($list['data'] as $key => $row) {
                    $str = '';
                    if ($list['type'] == 'sku') {
                        $keyName = $this->skuField[$key];
                        $str .= $keyName . ":";
                    } else if ($list['type'] == 'spu') {
                        $keyName = $this->spuField[$key];
                        $str .= $keyName . ":";
                    } else if ($list['type'] == 'url') {
                        $keyName = $this->urlField[$key];
                        $str .= $keyName . ":";
                    } else if ($list['type'] == 'lang') {
                        $keyName = $this->langField[$key];
                        $str .= $keyName;
                    }
                    $strFun = $key . "Text";
                    if (in_array($strFun, get_class_methods(self::class))) {
                        $str .= $this->$strFun($row);
                    } else {
                        $str .= $this->otherText($row);
                    }
                    $arr_temp[] = $str;
                }
                $result .= implode(";", $arr_temp);
            } else if ($list['exec'] == 'add') {
                $exec = '新增';
                $result .= $exec . $list['type'] . ":{$list['val']};";
            } else if ($list['exec'] == 'del') {
                $exec = '删除';
                $result .= $exec . $list['type'] . ":{$list['val']};";
            } else if ($list['exec'] == 'create') {
                $exec = '创建';
                $result .= $exec . $list['type'] . "{$list['val']}";
            } else if ($list['exec'] == 'agree') {
                $exec = '审批通过' . $list['val'];
                $result .= $exec;
            } else if ($list['exec'] == 'disagree') {
                $exec = '审批不通过,' . $list['val'];
                $result .= $exec;
            } else if ($list['exec'] == 'submit_audit') {
                $exec = '提交审批';
                $result .= $exec;
            } else if ($list['exec'] == 'checking_agree') {
                $exec = '查重审核通过';
                $result .= $exec;
            } else if ($list['exec'] == 'checking_disagree') {
                $exec = '查重审核不通过';
                $result .= $exec . "," . $list['val'];
            } else {
                throw new Exception('无此操作' . $list['exec']);
            }
            $tmp[] = $result;
        }

        return $tmp;
    }

    protected function otherText($row)
    {
        return "{$row['old']} => {$row['new']}";
    }

    protected function category_idText($row)
    {
        $Goods = new Goods();
        $old = $Goods->getCategoryAttr(null, ['category_id' => $row['old']]);
        $new = $Goods->getCategoryAttr(null, ['category_id' => $row['new']]);
        return "{$old} => {$new}";
    }


    protected function weightText($row)
    {
        $old = $row['old'] . "g";
        $new = $row['new'] . "g";
        return "{$old} => {$new}";
    }

    protected function auto_update_timeText($row)
    {
        if (!$row['old']) {
            $old = '未校验';
        } else {
            $old = date('Y-m-d H:i:s', $row['old']);
        }
        if (!$row['new']) {
            $new = '未校验';
        } else {
            $new = date('Y-m-d H:i:s', $row['new']);
        }
        return "{$old} => {$new}";
    }

    protected function old_weightText($row)
    {
        $old = $row['old'] . "g";
        $new = $row['new'] . "g";
        return "{$old} => {$new}";
    }

    protected function brand_idText($row)
    {
        $Goods = new Goods();
        $old = $Goods->getBrandAttr(null, ['brand_id' => $row['old']]);
        $new = $Goods->getBrandAttr(null, ['brand_id' => $row['new']]);
        return "{$old} => {$new}";
    }

    protected function sales_statusText($row)
    {
        $GoodsHelp = new GoodsHelp();
        $statusText = $GoodsHelp->sales_status;
        $old = $statusText[$row['old']] ?? '';
        $new = $statusText[$row['new']] ?? '';
        return "{$old} => {$new}";
    }

    protected function statusText($row)
    {
        $GoodsHelp = new GoodsHelp();
        $statusText = $GoodsHelp->sku_status;
        $old = $statusText[$row['old']] ?? '';
        $new = $statusText[$row['new']] ?? '';
        return "{$old} => {$new}";
    }

    protected function tort_idText($row)
    {
        $GoodsHelp = new GoodsHelp();
        $old = $GoodsHelp->getTortById($row['old']);
        $new = $GoodsHelp->getTortById($row['new']);
        return "{$old} => {$new}";
    }

    protected function transport_propertyText($row)
    {
        $GoodsHelp = new GoodsHelp();
        $old = $GoodsHelp->getProTransPropertiesTxt($row['old']);
        $new = $GoodsHelp->getProTransPropertiesTxt($row['new']);
        return "{$old} => {$new}";
    }

    protected function developer_idText($row)
    {
        $Goods = new Goods();
        $old = $Goods->getDeveloperAttr(null, ['developer_id' => $row['old']]);
        $new = $Goods->getDeveloperAttr(null, ['developer_id' => $row['new']]);
        return "{$old} => {$new}";
    }

    protected function purchaser_idText($row)
    {

        $Goods = new Goods();
        $old = $Goods->getDeveloperAttr(null, ['developer_id' => $row['old']]);
        $new = $Goods->getDeveloperAttr(null, ['developer_id' => $row['new']]);
        return "{$old} => {$new}";
    }

    protected function depthText($row)
    {
        $old = $row['old'] / 10;
        $new = $row['new'] / 10;
        return "{$old}cm => {$new}cm";
    }


    protected function heightText($row)
    {
        $old = $row['old'] / 10;
        $new = $row['new'] / 10;
        return "{$old}cm => {$new}cm";
    }

    protected function widthText($row)
    {
        $old = $row['old'] / 10;
        $new = $row['new'] / 10;
        return "{$old}cm => {$new}cm";
    }

    protected function lengthText($row)
    {
        $old = $row['old'] / 10;
        $new = $row['new'] / 10;
        return "{$old}cm => {$new}cm";
    }

    protected function is_packingText($row)
    {
        $map = [
            '0' => '不含',
            '1' => '含'
        ];
        $old = $map[$row['old']];
        $new = $map[$row['new']];
        return "{$old} => {$new}";
    }

    protected function pre_saleText($row)
    {
        $map = [
            '0' => '否',
            '1' => '是'
        ];
        $old = $map[$row['old']];
        $new = $map[$row['new']];
        return "{$old} => {$new}";
    }


    protected function packing_idText($row)
    {
        $GoodsHelp = new GoodsHelp();
        $old = $GoodsHelp->getPackageById($row['old']);
        $new = $GoodsHelp->getPackageById($row['new']);
        return "{$old} => {$new}";
    }

    protected function unit_idText($row)
    {
        $GoodsHelp = new GoodsHelp();
        $old = $GoodsHelp->getUnitById($row['old']);
        $new = $GoodsHelp->getUnitById($row['new']);
        return "{$old} => {$new}";
    }

    protected function warehouse_idText($row)
    {
        $GoodsHelp = new GoodsHelp();
        $old = $GoodsHelp->getWarehouseById($row['old']);
        $new = $GoodsHelp->getWarehouseById($row['new']);
        return "{$old} => {$new}";
    }

    protected function is_multi_warehouseText($row)
    {
        $map = [
            '0' => '不存在',
            '1' => '存在'
        ];
        $old = $map[$row['old']];
        $new = $map[$row['new']];
        return "{$old} => {$new}";
    }

    protected function platformText($row)
    {
        $GoodsHelp = new GoodsHelp();
        $oldVal = $GoodsHelp->getPlatformSaleJson($row['old']);
        $newVal = $GoodsHelp->getPlatformSaleJson($row['new']);

        $tmpOld = [];
        $tmpNew = [];
        $map = $GoodsHelp->platform_sale_status;
        foreach ($oldVal as $k => $v) {
            $tmpOld[] = $k . ">" . $map[$v];
        }
        $oldText = implode(' , ', $tmpOld);
        foreach ($newVal as $k => $v) {
            $tmpNew[] = $k . ">" . $map[$v];
        }
        $newText = implode(' , ', $tmpNew);
        return "{$oldText} => {$newText}";
    }

    protected function descriptionText($row)
    {
        return "";
    }

    protected function supplier_idText($row)
    {
        $GoodsImport = new GoodsImport();

        $oldVal = $GoodsImport->getSupplierName($row['old']);
        $newVal = $GoodsImport->getSupplierName($row['new']);
        return "{$oldVal} => {$newVal}";
    }

    protected function sku_attributesText($row)
    {
        $old = json_decode($row['old'], true);
        $new = json_decode($row['new'], true);
        $mdfold = [];
        $mdfnew = [];
        foreach ($old as $k => $v) {
            if (!isset($new[$k])) {
                $mdfold[$k] = $v;
            } else if ($v != $new[$k]) {
                $mdfold[$k] = $v;
                $mdfnew[$k] = $new[$k];
            }
        }
        foreach ($new as $k => $v) {
            if (!isset($old[$k])) {
                $mdfnew[$k] = $v;
            }
        }
        $oldName = GoodsHelp::getAttrbuteInfoBySkuAttributes($mdfold, $this->goods_id);
        $newName = GoodsHelp::getAttrbuteInfoBySkuAttributes($mdfnew, $this->goods_id);
        $tmp = [];
        foreach ($oldName as $k => $v) {
            $val = isset($newName[$k]) ? $newName[$k]['value'] : '';
            $tmp[] = "" . $v['name'] . ":{$v['value']} => {$val}";
        }
        return implode(';', $tmp);
    }


    protected $goods_id = 0;

    /**
     * @title 基础保存
     * @param $user_id
     * @param $goods_id
     * @param string $resource
     * @param int $type
     * @author starzhan <397041849@qq.com>
     */
    public function baseSave($user_id, $goods_id = 0, $resource = '', $type = 2, $process_id = 0, $pre_goods_id = 0)
    {
        if (!$resource) {
            $resource = $this->getOrigin(debug_backtrace());
        }
        $this->goods_id = $goods_id;
        $texts = $this->getText();
        if ($texts) {
            foreach ($texts as $text) {
                $data = [];
                $data['goods_id'] = $goods_id;
                $data['pre_goods_id'] = $pre_goods_id;
                $data['operator_id'] = $user_id;
                $data['process_id'] = $process_id;
                $data['remark'] = $resource . $text;
                $data['type'] = $type;
                $data['create_time'] = time();
                $GoodsDevelopLog = new GoodsDevelopLog();
                $GoodsDevelopLog->allowField(true)->isUpdate(false)->save($data);
            }
        }
        $this->LogData = [];
    }

    /**
     * @title 保存商品日志
     * @param $user_id
     * @param $goods_id
     * @param string $resource
     * @author starzhan <397041849@qq.com>
     */
    public function save($user_id, $goods_id, $resource = '')
    {
        $this->baseSave($user_id, $goods_id, $resource, 2);
    }

    /**
     * @title 保存预开发日志
     * @param $user_id
     * @param $goods_id
     * @param string $resource
     * @author starzhan <397041849@qq.com>
     */
    public function preSave($user_id, $goods_id, $process_id = 0, $resource = '')
    {
        $this->baseSave($user_id, 0, $resource, 1, $process_id, $goods_id);
    }

    /**
     * @title 保存开发日志
     * @param $user_id
     * @param $goods_id
     * @param string $resource
     * @author starzhan <397041849@qq.com>
     */
    public function devSave($user_id, $goods_id, $resource = '')
    {
        $this->baseSave($user_id, $goods_id, $resource, 0);
    }

    const Tags = [
        'oa_update' => 'OA接口推送',
        'excell_update' => 'excell导入',
        'cost_price_update' => '成本价队列'
    ];

    protected function getOrigin($origin)
    {
        $str = '';
        $tmp = $origin[1] ?? [];
        if (!$tmp) {
            return $str;
        }
        if (strpos($tmp['class'], 'ProductHelp') !== false) {
            return '[oa_update]';
        }
        if (strpos($tmp['class'], 'GoodsImport') !== false) {
            return '[excell_update]';
        }
        return $str;
    }

    public function getRemark($remark)
    {
        foreach (self::Tags as $k => $v) {
            $remark = str_replace("[{$k}]", "【{$v}】", $remark);
        }
        $flag = preg_match("/\{e:(.+?)\}/", $remark, $data);
        if ($flag) {
            $remark = str_replace($data[0], '', $remark);
        }
        return $remark;
    }
}