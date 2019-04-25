<?php


namespace app\goods\service;


use app\common\cache\Cache;
use app\common\model\GoodsSkuMapLog as GoodsSkuMapLogModel;
use app\index\service\BaseLog;
use app\order\service\OrderService;

class GoodsSkuMapLog extends BaseLog
{
    protected $oldRow = [];

    protected $newRow = [];

    protected $fields = [
        'channel_id' => '平台id',
        'account_id' => '账号id',
        'is_virtual_send' => '是否虚拟仓发货',
        'channel_sku' => '平台sku',
        'sku_code_quantity' => 'sku信息'
    ];

    /**
     * 参数与数据库表字段映射
     * @var array
     */
    protected $tableField = [
        'id' => 'map_id',
        'remark' => 'remark',
        'operator_id' => 'operator_id',
        'operator' => 'operator'
    ];

    public function __construct()
    {
        $this->model = new GoodsSkuMapLogModel();
    }

    public function add(string $name)
    {
        $list = [];
        $list['type'] = '平台sku';
        $list['val'] = $name;
        $list['data'] = [];
        $list['exec'] = 'add';
        $this->LogData[] = $list;
        return $this;
    }

    public function mdf($name, $old, $new)
    {
        $this->oldRow = $old;
        $this->newRow = $new;
        $data = $this->mdfData($old, $new);
        $info = [];
        foreach ($data as $key) {
            $row = [];
            $row['old'] = $old[$key];
            $row['new'] = $new[$key];
            $info[$key] = $row;
        }
        $this->mdfItem($name, $info);
        return $this;
    }

    public function getLogsByMapId($mapId)
    {
        $result = GoodsSkuMapLogModel::all(function ($query) use ($mapId) {
            $query->where('map_id', '=', $mapId)
                ->order('create_time desc');
        });
        return $result;
    }

    protected function mdfData($old, $new)
    {
        $data = [];
        foreach ($new as $key => $v) {
            if (in_array($key, array_keys($this->fields))) {
                //针对sku_code_quantity做特殊处理
                if ($key == 'sku_code_quantity') {
                    $oldSkuCodeQ = json_decode($old[$key], true);
                    $newSkuCodeQ = json_decode($v, true);
                    if (count($oldSkuCodeQ) == count($newSkuCodeQ)) {
                        foreach ($newSkuCodeQ as $k => $v) {
                            if (isset($oldSkuCodeQ[$k])) {
                                continue;
                            }
                            $data[] = $key;
                        }
                    } else {
                        $data[] = $key;
                    }
                    continue;
                }
                if ($v != $old[$key]) {
                    $data[] = $key;
                }
            }
        }
        return $data;
    }

    protected function mdfItem($name, $info)
    {
        $list = [];
        $list['type'] = '平台sku：' . $name;
        $list['val'] = '';
        $list['data'] = $info;
        $list['exec'] = 'mdf';
        $this->LogData[] = $list;
    }

    protected function sku_code_quantityText($row)
    {
        if (!$row) {
            return;
        }
        if ($row['old']) {
            $old = join('|', array_column(json_decode($row['old'], true), 'sku_code'));
        } else {
            $old = '';
        }
        if ($row['new']) {
            $new = join('|', array_column(json_decode($row['new'], true), 'sku_code'));
        } else {
            $new = '';
        }
        return "{$old} => {$new}";
    }

    protected function is_virtual_sendText($row)
    {
        $old = $row['old'] == 1 ? '是' : '否';
        $new = $row['new'] == 1 ? '是' : '否';
        return "{$old} => {$new}";
    }

    protected function channel_idText($row)
    {
        $oldChannelName = (Cache::store('channel')->getChannelName($row['old'])) ?: '';
        $newChannelName = (Cache::store('channel')->getChannelName($row['new'])) ?: '';
        return "{$oldChannelName} => {$newChannelName}";
    }

    protected function account_idText($row)
    {
        $OrderService = new OrderService();
        $oldAccountName = ($OrderService->getAccountName($this->oldRow['channel_id'], $row['old'])) ?: '';
        $newAccountName = ($OrderService->getAccountName($this->newRow['channel_id'], $row['new'])) ?: '';
        return "{$oldAccountName} => {$newAccountName}";
    }
}