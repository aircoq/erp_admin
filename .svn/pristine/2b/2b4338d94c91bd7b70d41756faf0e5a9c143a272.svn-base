<?php

namespace app\goods\service;

use app\common\cache\Cache;
use app\common\exception\JsonErrorException;
use app\common\model\Channel;
use app\common\model\GoodsSkuMap;
use app\common\traits\Export;
use app\common\service\Common;
use app\common\service\CommonQueuer;
use app\order\service\OrderService;
use app\report\model\ReportExportFiles;
use app\goods\queue\GoodsSkuMapExportQueue;
use think\Db;
use think\Exception;
use think\Loader;
use phpzip\PHPZip;

Loader::import('phpExcel.PHPExcel', VENDOR_PATH);

/**
 * Created by PhpStorm.
 * User: hecheng
 * Date: 2018/8/10
 * Time: 11:46
 */
class GoodsSkuMapExportService
{
    use Export;
    protected $goodsSkuMapModel = null;

    public function __construct()
    {
        if (is_null($this->goodsSkuMapModel)) {
            $this->goodsSkuMapModel = new GoodsSkuMap();
        }
    }

    /**
     * 标题
     */
    public function title()
    {
        $title = [
            ['title' => '渠道sku', 'key' => 'channel_sku', 'width' => 10],
            ['title' => '本地SKU及数量', 'key' => 'sku_code_quantity', 'width' => 10],
            ['title' => '平台', 'key' => 'channel', 'width' => 10],
            ['title' => '账号', 'key' => 'account', 'width' => 10],
            ['title' => '是否虚拟仓发货', 'key' => 'is_virtual_send', 'width' => 10],
        ];
        return $title;
    }

    /**
     * 产品映射管理队列导出申请
     * @param $params
     * @param $ids
     * @return array
     */
    public function exportApply($params, $ids)
    {
        $userId = Common::getUserInfo()->toArray()['user_id'];
        $cache = Cache::handler();
        $lastApplyTime = $cache->hget('hash:export_sku_map_apply', $userId);
        if ($lastApplyTime && time() - $lastApplyTime < 5) {
            throw new JsonErrorException('请求过于频繁', 400);
        } else {
            $cache->hset('hash:export_sku_map_apply', $userId, time());
        }
        Db::startTrans();
        try {
            $export_file_name = $this->createExportFileName($userId);
            $model = new ReportExportFiles();
            $data['applicant_id'] = $userId;
            $data['apply_time'] = time();
            $data['export_file_name'] = str_replace('.csv', '.zip', $export_file_name);
            $data['status'] = 0;
            $data['applicant_id'] = $userId;
            $model->allowField(true)->isUpdate(false)->save($data);
            $params['file_name'] = $export_file_name;
            $params['apply_id'] = $model->id;
            $params['ids'] = $ids;  //选中部分id
            (new CommonQueuer(GoodsSkuMapExportQueue::class))->push($params);
            Db::commit();
            return ['join_queue' => 1, 'message' => '成功加入导出队列'];
        } catch (\Exception $ex) {
            Db::rollback();
            throw new JsonErrorException('申请导出失败');
        }
    }

    /**
     * 创建导出文件名
     * @param $userId
     * @return string
     */
    protected function createExportFileName($userId)
    {
        $fileName = '产品映射管理报表_' . $userId . '_' . date("Y_m_d_H_i_s") . '.csv';
        return $fileName;
    }

    /**
     * 导出数据至excel文件
     * @param array $params
     */
    public function export(array $params)
    {
        try {

            set_time_limit(0);
            //ini_set('memory_limit', '4096M');
            if (!isset($params['apply_id']) || empty($params['apply_id'])) {
                throw new Exception('导出申请id获取失败');
            }
            if (!isset($params['file_name']) || empty($params['file_name'])) {
                throw new Exception('导出文件名未设置');
            }
            $fileName = $params['file_name'];
            $downLoadDir = '/download/sales/';
            $saveDir = ROOT_PATH . 'public' . $downLoadDir;
            if (!is_dir($saveDir) && !mkdir($saveDir, 0777, true)) {
                throw new Exception('导出目录创建失败');
            }
            $filePath = $saveDir . $fileName;
            //创建excel对象
            $header = $this->title();
            $aHeader = [];
            foreach ($header as $v) {
                $v['title'] = $this->charset($v['title'], 1);
                $aHeader[] = $v['title'];
            }

            $fp = fopen($filePath, 'w+');
            fputcsv($fp, $aHeader);

            //统计需要导出的数据行
            $count = $this->doCount([], $params);
            $pageSize = 10000;
            $loop = ceil($count / $pageSize);
            //分批导出
            for ($i = 0; $i < $loop; $i++) {
                $this->assemblyData($this->doSearch([], $params, $i + 1, $pageSize), $header, $fp);
            }
            fclose($fp);
            if (is_file($filePath)) {
                $fileName = str_replace('.csv', '', $params['file_name']);
                $zipPath = $saveDir . DS . $fileName . ".zip";
                $PHPZip = new PHPZip();
                $zipData = [
                    [
                        'name' => $fileName,
                        'path' => $filePath
                    ]
                ];
                $PHPZip->saveZip($zipData, $zipPath);
                @unlink($filePath);
                $applyRecord = ReportExportFiles::get($params['apply_id']);
                $applyRecord['exported_time'] = time();
                $applyRecord['download_url'] = $downLoadDir . DS . $fileName . ".zip";
                $applyRecord['status'] = 1;
                $applyRecord->allowField(true)->isUpdate(true)->save();
            } else {
                throw new Exception('文件写入失败');
            }
        } catch (\Exception $ex) {
            Cache::handler()->hset(
                'hash:report_export',
                'error_' . time(),
                '申请id: ' . $params['apply_id'] . ',导出失败:' . $ex->getMessage() . $ex->getFile() . $ex->getLine());
            $applyRecord['status'] = 2;
            $applyRecord['error_message'] = $ex->getMessage();
            (new ReportExportFiles())->where(['id' => $params['apply_id']])->update($applyRecord);
        }
    }

    private function charset($value, $no = false)
    {
        return mb_convert_encoding($value, "GBK", "UTF-8");
    }

    /**
     * 查询总数
     * @param array $condition
     * @param array $params
     * @return int|string
     * @throws \think\Exception
     */
    public function doCount(array $condition = [], $params = [])
    {
        $this->where($condition, $params);
        $field = 'id,sku_code,channel_id as channel,account_id as account,channel_sku,quantity,updater_id as update_user,update_time,sku_code_quantity';
        $count = $this->goodsSkuMapModel->field($field)->where($condition)->count();
        return $count;
    }

    /**
     * 查询数据
     * @param array $condition
     * @param array $params
     * @param int $page
     * @param int $pageSize
     * @return false|\PDOStatement|string|\think\Collection
     * @throws Exception
     */
    public function doSearch(array $condition = [], $params = [], $page = 0, $pageSize = 0)
    {
        try {
            $this->where($condition, $params);
            $field = 'channel_id,account_id,channel_sku,sku_code_quantity,is_virtual_send';
            $goodsSkuList = $this->goodsSkuMapModel->field($field)->where($condition)->page($page, $pageSize)->select();
        } catch (Exception $e) {
            throw new Exception($e->getMessage() . $e->getFile() . $e->getLine());
        }
        return $goodsSkuList;
    }

    /**
     * 组装数据
     * @param array $records
     * @param array $title
     * @param $fp
     * @throws Exception
     */
    public function assemblyData(array $records, array $title, $fp)
    {
        try {
            $newGoodsData = [];
            foreach ($records as $k => $v) {
                $newGoodsData['channel_sku'] = $v['channel_sku'] ?? '';
                //获取本地SKU及数量
                $sku_code_quantity = '';
                $sku_code_quantityInfo = json_decode($v['sku_code_quantity'], true);
                if ($sku_code_quantityInfo) {
                    foreach ($sku_code_quantityInfo as $key => $value) {
                        if (!empty($value['sku_code']) && !empty($value['quantity'])) {
                            $sku_code_quantity .= !empty($sku_code_quantity) ? ',' . $value['sku_code'] . '*' . $value['quantity'] : $value['sku_code'] . '*' . $value['quantity'];
                        }
                    }
                }
                $newGoodsData['is_virtual_send'] = $v['is_virtual_send']==1?'是':'否';
                $newGoodsData['sku_code_quantity'] = $sku_code_quantity;

                //获取平台名
                if (isset($v['channel_id']) && !empty($v['channel_id'])) {
                    $channel = new Channel();
                    $channel_title = $channel->where('id', $v['channel_id'])->value('title');
                    $newGoodsData['channel'] = $channel_title;
                } else {
                    $newGoodsData['channel'] = '';
                }

                //获取账号名
                if (isset($v['account_id']) && !empty($v['account_id'])) {
                    $order_service = new OrderService();
                    $accountName = $order_service->getAccountName($v['channel_id'], $v['account_id']);
                    $newGoodsData['account'] = $accountName;
                } else {
                    $newGoodsData['account'] = '';
                }
                $temp = [];
                foreach ($title as $h) {
                    $field = $h['key'];
                    $value = isset($newGoodsData[$field]) ? $newGoodsData[$field] : '';
                    $content = mb_convert_encoding($value, "GBK", "UTF-8");
                    $temp[] = $content;
                }
                fputcsv($fp, $temp);
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage() . $e->getFile() . $e->getLine());
        }
    }

    /**
     * 搜索条件
     * @param $where
     * @param $params
     * @throws \think\Exception
     */
    public function where(&$where, $params)
    {
        if (isset($params['ids']) && !empty($params['ids'])) {
            $where['id'] = ['in', $params['ids']];
        }

        if (isset($params['snType']) && isset($params['snText']) && !empty($params['snText'])) {
            $snText = $params['snText'];
            switch ($params['snType']) {
                case 'channel_sku':
                    $where['channel_sku'] = ['like', $snText . '%'];
                    break;
                case 'sku':
                    $sku_id = GoodsSkuAliasService::getSkuIdByAlias($snText);//别名 （不支持模糊匹配）
                    if ($sku_id) {
                        $where['sku_id'] = ['=', $sku_id];
                    } else {
                        $where['sku_code'] = ['like', $snText . '%'];
                    }
                    break;
                default:
                    break;
            }
        }
        //平台
        if (isset($params['channel_id']) && !empty($params['channel_id'])) {
            if (is_numeric($params['channel_id'])) {
                $where['channel_id'] = ['=', $params['channel_id']];
            }
        }
        //账号
        if (isset($params['account_id']) && !empty($params['account_id'])) {
            if (is_numeric($params['account_id'])) {
                $where['account_id'] = ['=', $params['account_id']];
            }
        }
        //分类
        if (isset($params['category_id']) && $params['category_id'] != '') {
            if (is_numeric($params['category_id'])) {
                $goods_ids = [];
                $category_ids = [$params['category_id']];
                //求出分类
                $category = Cache::store('category')->getCategoryTree();
                if ($category[$params['category_id']]) {
                    array_merge($category_ids, $category[$params['category_id']]['child_ids']);
                }
                //查出所有的goods_id
                $goodsModel = new Goods();
                $goodsList = $goodsModel->field('id')->where('category_id', 'in', $category_ids)->select();
                if (!empty($goodsList)) {
                    foreach ($goodsList as $goods => $list) {
                        array_push($goods_ids, $list['id']);
                    }
                }
                $where['goods_id'] = ['in', $goods_ids];
            } else {
                throw new JsonErrorException('分类参数错误', 400);
            }
        }
        //更新人
        if (isset($params['update_user_id']) && !empty($params['update_user_id'])) {
            if (is_numeric($params['update_user_id'])) {
                $where['updater_id'] = ['=', $params['update_user_id']];
            }
        }
        //时间
        $params['date_b'] = isset($params['date_b']) ? trim($params['date_b']) : 0;
        $params['date_e'] = isset($params['date_e']) ? trim($params['date_e']) : 0;
        $condition = timeCondition($params['date_b'], $params['date_e']);
        if (is_array($condition) && !empty($condition)) {
            $where['update_time'] = $condition;
        }
    }

    public function batchSetVirtual($ids, $is_virtual_send, $userInfo)
    {
        $update = [
            'is_virtual_send' => 1,
            'updater_id' => $userInfo['user_id'],
            'is_virtual_send' => $is_virtual_send,
            'update_time' => time()
        ];
        GoodsSkuMap::where('id', 'in', $ids)->update($update);
        return ['message' => '保存成功'];
    }

}
