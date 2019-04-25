<?php
// +----------------------------------------------------------------------
// | 
// +----------------------------------------------------------------------
// | File  : ProfitStatement.php
// +----------------------------------------------------------------------
// | Author: LiuLianSen <3024046831@qq.com>
// +----------------------------------------------------------------------
// | Date  : 2017-08-07
// +----------------------------------------------------------------------
// +----------------------------------------------------------------------
namespace app\report\service;

use app\common\model\ReportUnshippedByDate;
use erp\AbsServer;
use think\Exception;
use think\Validate;
use app\common\cache\Cache;
use app\common\service\Common;
use think\Db;
use app\common\model\Warehouse;

use app\common\exception\JsonErrorException;
use app\report\model\ReportExportFiles;
use app\common\service\CommonQueuer;
use app\report\queue\ReportUnshippedExportQueue;
use app\report\validate\FileExportValidate;

use think\Loader;
use app\common\traits\Export;
use app\order\service\AuditOrderService;

Loader::import('phpExcel.PHPExcel', VENDOR_PATH);
class ReportUnshippedService extends  AbsServer
{
    use Export;

    protected $colMap=[
        'order' => [
            'title' => [
                'A' => ['title' => '统计日期', 'width' => 10],
                'B' => ['title' => '合计', 'width' => 25],
                'C' => ['title' => '中山仓', 'width' => 15],
                'D' => ['title' => '金华仓', 'width' => 15],
                'E' => ['title' => '中山滞销仓', 'width' => 15],
            ],
            'data' => [
                'dateline' => ['col' => 'A', 'type' => 'time'],
                '0' => ['col' => 'B', 'type' => 'time'],
                '2' => ['col' => 'C', 'type' => 'str'],
                '6' => ['col' => 'D', 'type' => 'str'],
                '218' => ['col' => 'E', 'type' => 'str'],
            ]
        ]
    ];
    /**
     * 标题
     */
    public function title()
    {
        $title = [
            'dateline' => [
                'title' => 'dateline',
                'remark' => '统计日期',
                'is_show' => 1
            ],
            '0' => [
                'title' => '0',
                'remark' => '合计',
                'is_show' => 1],
            '2' => [
                'title' => '2',
                'remark' => '中山仓',
                'is_show' => 1
            ],
            '6' => [
                'title' => '6',
                'remark' => '金华仓',
                'is_show' => 1
            ],
            '218' => [
                'title' => '218',
                'remark' => '中山滞销仓',
                'is_show' => 1
            ],
        ];
        return $title;
    }
    public function shipped($params)
    {
        $model = new ReportUnshippedByDate();
        return $this->assembled($model, $params);
    }

    /**
     * 导出封装返回数据
     * @param $model
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function assembled($model, $params)
    {
        //统计日期
        $b_time = !empty(param($params, 'time_start')) ? $params['time_start'] . ' 00:00:00' : '';
        $e_time = !empty(param($params, 'time_end')) ? $params['time_end'] . ' 23:59:59' : '';
        if ($b_time && $e_time) {
            $where['dateline'] = ['BETWEEN', [strtotime($b_time), strtotime($e_time)]];
        } elseif ($b_time) {
           return"请输入结束时间";
        } elseif ($e_time) {
            return"请输入开始时间";
        }
        $sortField = 'dateline';
        $sortType = 'DESC';
        $field = 'warehouse_id,dateline,sum(quantity) as qty';


        $list = $model->field($field)->where($where)
            ->order($sortField, $sortType)
            ->group('warehouse_id,dateline')
            ->select();
        $warehouses = $this->getWarehouses();
        $beginDay = strtotime($params['time_start']);
        $days = $this->interval($beginDay, $this->showDay($params));
        $reData = [];
        foreach ($days as $v) {
            $v = date('Y-m-d', $v);
            $reData[$v] = [
                0 => 0,
            ];

            foreach ($warehouses as $one) {
                $reData[$v][$one['id']] = 0;
                $tt[]=$one['id'];
            }

        }
        foreach ($list as $v) {
            if(!in_array($v['warehouse_id'],$tt)){
                continue;
            }
            $v['dateline'] = date('Y-m-d', $v['dateline']);
            foreach ($reData as $key => &$day) {
                if ($v['dateline'] == $key) {
                    $day[$v['warehouse_id']] = $v['qty'];
                    $day[0] += $v['qty'];
                    break;
                }
            }
        }
        foreach ($days as &$v) {
            $v = date('Y-m-d', $v);
        }

        return $reData;
    }
    /**
     * 队列导出封装返回数据
     * @param $params
     * @param $page
     * @param $pageSize
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function shippedExport( $params,$page,$pageSize)
    {
        //统计日期
        $b_time = !empty(param($params, 'time_start')) ? $params['time_start'] . ' 00:00:00' : '';
        $e_time = !empty(param($params, 'time_end')) ? $params['time_end'] . ' 23:59:59' : '';
        if ($b_time && $e_time) {
            $where['dateline'] = ['BETWEEN', [strtotime($b_time), strtotime($e_time)]];
        } elseif ($b_time) {
            return"请输入结束时间";
        } elseif ($e_time) {
            return"请输入开始时间";
        }
        $sortField = 'dateline';
        $sortType = 'DESC';
        $field = 'warehouse_id,dateline,sum(quantity) as qty';
        $i=1;
        $model = new ReportUnshippedByDate();
        $list = $model->field($field)->where($where)
            ->order($sortField, $sortType)
            ->group('warehouse_id,dateline')
            ->page($page, $pageSize)
            ->select();

        $warehouses = $this->getWarehouses();
        $beginDay = strtotime($params['time_start']);
        $days = $this->interval($beginDay, $this->showDay($params));
        $reData = [];
        foreach ($days as $v) {
            $v = date('Y-m-d', $v);
            $reData[$v] = [
                0 => 0,
            ];

            foreach ($warehouses as $one) {
                $reData[$v][$one['id']] = 0;
                $tt[]=$one['id'];
            }

        }
        foreach ($list as $v) {

            if(!in_array($v['warehouse_id'],$tt)){
                continue;
            }
            $v['dateline'] = date('Y-m-d', $v['dateline']);
            foreach ($reData as $key => &$day) {
                if ($v['dateline'] == $key) {
                    $day[$v['warehouse_id']] = $v['qty'];
                    $day[0] += $v['qty'];
                    break;
                }
            }
        }
        foreach ($days as &$v) {
            $v = date('Y-m-d', $v);
        }
        $i++;
        return $reData;
    }
    public function logShipped($params)
    {
        $model = new ReportUnshippedByDate();
        return $this->getRedata($model,$params);
    }
    /**
     * 封装返回数据
     * @param $model
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function getRedata($model,$params)
    {

        $page = isset($params['page']) ? intval($params['page']) : 1;
        $pageSize = isset($params['pageSize']) ? intval($params['pageSize']) : 10;

        //统计日期
        $b_time = !empty(param($params, 'time_start')) ? $params['time_start'] . ' 00:00:00' : '';
        $e_time = !empty(param($params, 'time_end')) ? $params['time_end'] . ' 23:59:59' : '';
        if ($b_time && $e_time) {
            $where['dateline'] = ['BETWEEN', [strtotime($b_time), strtotime($e_time)]];
        } elseif ($b_time) {
           return "请输入结束时间";
        } elseif ($e_time) {
            return "请输入开始时间";
        }
        $sortField = 'dateline';
        $sortType =  'DESC';
        $field = 'warehouse_id,dateline,sum(quantity) as qty';
        $count=$this->showDay($params)+1;

        $list = $model->field($field)->where($where)
            ->order($sortField, $sortType)
            ->group('warehouse_id,dateline')
            ->page($page, $pageSize)->select();
        $warehouses = $this->getWarehouses();
        $beginDay = strtotime($params['time_start']);
        $days = $this->interval($beginDay, $this->showDay($params));
        $reData = [];
        foreach ($days as $v){
            $v=date('Y-m-d',$v);
            $reData[$v] = [
                0 => 0,
            ];
            foreach ($warehouses as $one){
                $reData[$v][$one['id']] = 0;
                $tt[]=$one['id'];
            }
        }
        foreach ($list as $v){
            if(!in_array($v['warehouse_id'],$tt)){
                continue;
            }
            $v['dateline'] = date('Y-m-d',$v['dateline']);
            foreach ($reData as $key=>&$day){
                if($v['dateline'] == $key){
                    $day[$v['warehouse_id']] = $v['qty'];
                    $day[0] += $v['qty'];
                    break;
                }
            }
        }

        foreach ($reData as $k => $v) {
            $v['dateline'] = $k;
            $temp[] = $v;
        }

        return [
            'data' => $temp,
            'count' => $count,
            'page' => $page,
            'pageSize' => $pageSize];
    }
    public  function showDay($params){
        $showDay= (strtotime($params['time_end'])- strtotime($params['time_start']))/86400;
        return  $showDay;
    }
    /**
     * 拉取需要统计的仓库信息
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getWarehouses()
    {
        $where['type'] = 1;
        $field = 'id,name';
        $warehouseIds = (new Warehouse())->where($where)->field($field)->select();
        return $warehouseIds;
    }
    /**
     * 返回符合的某天时间戳
     * @param int $day
     * @return false|float|int
     */
    public function getTime($day = 0)
    {
        if(!$day){
            $day = $this->showDay;
        }
        return strtotime(date('Y-m-d')) -  ($day - 1) * 86400;
    }
    private function interval($secs, $day)
    {
        $result = [];
        for ($i = 0; $day>=$i ; $i++) {
            $result[] = $secs;
            $secs += TIME_SECS_DAY;
        }
        return array_reverse($result);
    }

    /**
     * 获取查询查询
     * @param $params
     * @param $where
     * @return array
     * @throws Exception
     */
    protected function doSearch($params,&$where)
    {

        //统计日期
        $b_time = !empty(param($params, 'time_start')) ? $params['time_start'] . ' 00:00:00' : '';
        $e_time = !empty(param($params, 'time_end')) ? $params['time_end'] . ' 23:59:59' : '';
        if ($b_time && $e_time) {
            $where['dateline'] = ['BETWEEN', [strtotime($b_time), strtotime($e_time)]];
        } elseif ($b_time) {
            return "请输入结束时间";
        } elseif ($e_time) {
            return "请输入开始时间";
        }

    }


    /**
     * 导出申请
     * @param $params
     * @return bool
     * @throws Exception
     * @throws \Exception
     */
    public function applyExport($params)
    {
        $userId = Common::getUserInfo()->toArray()['user_id'];
        $cache = Cache::handler();
        $lastApplyTime = $cache->hget('hash:export_detail_apply', $userId);
        if ($lastApplyTime && time() - $lastApplyTime < 5) {
            throw new JsonErrorException('请求过于频繁', 400);
        } else {
            $cache->hset('hash:export_apply', $userId, time());
        }
        try {
        $fileName = $this->createExportFileName($params);
        $downLoadDir = '/download/express_unshipped/';
        $saveDir = ROOT_PATH . 'public' . $downLoadDir;
        if (!is_dir($saveDir) && !mkdir($saveDir, 0777, true)) {
            throw new Exception('导出目录创建失败');
        }

        $fullName = $saveDir . $fileName . '_' . $userId . '_' . date('Y-m-d', time()) . '.xlsx';

        $titleMap = $this->colMap['order']['title'];
        $title = [];
        $col = [];
        $titleData = $this->colMap['order']['data'];
        if (!empty($field)) {
            foreach ($field as $k => $v) {
                if (isset($titleData[$v])) {
                    array_push($title, $v);
                    $col[$titleData[$v]['col']] = $titleData[$v]['col'];
                }
            }
            $titleOrderData = [];
            foreach ($titleMap as $t => $tt){
                if (isset($col[$t])) {
                    $titleOrderData[$tt['title']] = 'string';
                }
            }
        } else {
            foreach ($titleData as $k => $v) {
                array_push($title, $k);
            }
            $titleOrderData = [];
            foreach ($titleMap as $t => $tt){
                $titleOrderData[$tt['title']] = 'string';
            }
        }
            $where = [];
            $field = 'warehouse_id,dateline,sum(quantity) as qty';
            $this->doSearch($params, $where);
        $listModel = new ReportUnshippedByDate();
            $count =$this->showDay($params)+1;
        if($count>1000){
            //加入队列
              Db::startTrans();
            try{
                $model = new ReportExportFiles();
                $data['applicant_id'] = $userId;
                $data['apply_time'] = time();
                $data['export_file_name'] = $this->createExportFileName($params) . '.xlsx';
                $data['status'] = 0;
                $data['applicant_id'] = $userId;
                $model->allowField(true)->isUpdate(false)->save($data);
                $params['file_name'] = $data['export_file_name'];
                $params['apply_id'] = $model->id;
               // $da=new ReportUnshippedExportQueue();
            //  $da->execute($params);
              (new CommonQueuer(ReportUnshippedExportQueue::class))->push($params);
              Db::commit();
                return ['join_queue' => 1, 'message' => '已加入导出队列'];
                } catch (\Exception $ex) {
                Db::rollback();
                throw new JsonErrorException('申请导出失败');
            }

        }else{
            //页面导出
            $info = [];
            $temp = [];
            $item = [];
            $data = $this->shipped($params);
            foreach ($data as $k => $v) {
                $v['dateline'] = $k;
                $item=$v;
                foreach ($title as $value) {
                    $temp[$value] = $item[$value];

                }
                array_push($info, $temp);
                $this->excelSave($titleOrderData, $fullName, $info);
            }
            $auditOrderService = new AuditOrderService();
            $result = $auditOrderService->record($fileName, $fullName);
            return $result;
        }

        } catch (\Exception $ex) {
            Db::rollback();
            throw new JsonErrorException('申请导出失败');
        }
    }

    /**
     * 创建导出文件名
     * @param $params
     * @return string
     */
    protected function createExportFileName($params)
    {
        if (!empty($params['time_start']) && !empty($params['time_end'])) {
            $date_b = strtotime($params['time_start']);
            $date_b = gmdate('Y-m-d',$date_b);
            $data_e = strtotime($params['time_end']);
            $data_e = date('Y-m-d',$data_e);
            $fileName = '未发货记录报表（' . $date_b . '-' .$data_e . ')';
        } else {
            $fileName ='未发货记录报表';
        }
        return $fileName;
    }
    /**
     * 导出数据至excel文件
     * @param array $params
     * @return bool
     * @throws Exception
     */
    public function export(array $params)
    {
        set_time_limit(0);
        try {
            //ini_set('memory_limit', '4096M');
            if (!isset($params['apply_id']) || empty($params['apply_id'])) {
                throw new Exception('导出申请id获取失败');
            }
            if (!isset($params['file_name']) || empty($params['file_name'])) {
                throw new Exception('导出文件名未设置');
            }
            $fileName = $params['file_name'];
            $downLoadDir = '/download/express_unshipped/';
            $saveDir = ROOT_PATH . 'public' . $downLoadDir;

            if (!is_dir($saveDir) && !mkdir($saveDir, 0777, true)) {
                throw new Exception('导出目录创建失败');
            }

            $fullName = $saveDir . $fileName;
            $where = [];
            $this->doSearch($params,$where);
            $where = is_null($where) ? [] : $where;
            $fields = '';
            $model = new ReportUnshippedByDate();
            $count =$this->showDay($params)+1;
            $pageSize = 1000;
            $loop = ceil($count / $pageSize);

            //创建excel对象
            $writer = new \XLSXWriter();
            $col = [];
            $title = [];
            $titleMap = $this->colMap['order']['title'];
            $titleData = $this->colMap['order']['data'];
            $field = $params['field'] ?? [];
            if (!empty($field)) {
                foreach ($field as $k => $v) {
                    if (isset($titleData[$v])) {
                        array_push($title, $v);
                        $col[$titleData[$v]['col']] = $titleData[$v]['col'];
                    }
                }
                $titleOrderData = [];
                foreach ($titleMap as $t => $tt){
                    if (isset($col[$t])) {
                        $titleOrderData[$tt['title']] = 'string';
                    }
                }
            } else {
                foreach ($titleData as $k => $v) {
                    array_push($title, $k);
                }
                $titleOrderData = [];
                foreach ($titleMap as $t => $tt){
                    $titleOrderData[$tt['title']] = 'string';
                }
            }

            $writer->writeSheetHeader('Sheet1', $titleOrderData);

            //分批导出
            for ($i = 0; $i < $loop; $i++) {
                $info=[];
                $temp=[];
                $item=[];
                $data=$this->shippedExport($params,$i+1,$pageSize);
                foreach($data as $k=>$v){
                    $v['dateline']=$k;
                    $item=$v;

                    foreach($title as $value){
                        $temp[$value]=$item[$value];
                    }
                    array_push($info,$temp);

                }
                foreach($info as $ll){
                    $writer->writeSheetRow('Sheet1', $ll);
                }
            }

            $writer->writeToFile($fullName);
            if (is_file($fullName)) {
                $applyRecord['exported_time'] = time();
                $applyRecord['download_url'] = $downLoadDir . $fileName;
                $applyRecord['status'] = 1;

                (new ReportExportFiles())->where(['id' => $params['apply_id']])->update($applyRecord);
            } else {

                throw new Exception('文件写入失败');
            }
        } catch (\Exception $ex) {
            $applyRecord = ReportExportFiles::get($params['apply_id']);
            $applyRecord['status'] = 2;
            $applyRecord['error_message'] = $ex->getMessage();
            $applyRecord->isUpdate(true)->save();
            Cache::handler()->hset(
                'hash:report_unshipped',
                $params['apply_id'] .'_'. time(),
                '申请id: ' . $params['apply_id'] . ',导出失败:' . $ex->getMessage());
        }
    }
}
