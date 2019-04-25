<?php
namespace app\report\service;

use app\common\cache\Cache;
use app\common\model\Message;
use app\common\model\report\ReportStatisticByMessage;
use app\common\service\Common;
use app\common\service\MessageStatusConst;
use app\common\service\Report;
use app\report\queue\CustomerMessageExportQueue;
use think\Db;
use think\Exception;
use think\Loader;
use app\common\exception\JsonErrorException;
use app\common\service\CommonQueuer;
use app\report\model\ReportExportFiles;
use app\report\queue\SaleStockExportQueue;
use app\index\service\Department as DepartmentServer;
use app\index\service\DepartmentUserMapService;
use app\report\validate\FileExportValidate;


Loader::import('phpExcel.PHPExcel', VENDOR_PATH);
/**  客服业绩统计页面
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/08/24
 * Time: 15:24
 */
class StatisticMessage
{
    protected $colMap = [
        'title' => [
            'A' => ['title' => '回复日期', 'width' => 15],
            'B' => ['title' => '平台', 'width' => 30],
            'C' => ['title' => '客服姓名', 'width' => 30],
            'D' => ['title' => '站内信处理数量', 'width' => 15],
            'E' => ['title' => '回复买家数', 'width' => 15],
            'F' => ['title' => '纠纷处理数', 'width' => 20],
        ],
        'data' => [
            'dateline' =>                     ['col' => 'A', 'type' => 'str'],
            'channel' =>                   ['col' => 'B', 'type' => 'str'],
            'customer' =>                  ['col' => 'C', 'type' => 'str'],
            'buyer_qauntity' =>               ['col' => 'D', 'type' => 'str'],
            'message_quantity' =>             ['col' => 'E', 'type' => 'str'],
            'dispute_quantity' =>             ['col' => 'F', 'type' => 'str'],
        ]
    ];

    protected $reportStatisticByMessageModel = null;

    public function __construct()
    {
        if (is_null($this->reportStatisticByMessageModel)) {
            $this->reportStatisticByMessageModel = new ReportStatisticByMessage();
        }
    }

    /**
     * 获取绑定账号的客服列表
     */
    public function getCustomer($channelId = 1)
    {
        return Cache::store('User')->getChannelCustomer($channelId);
    }

    /** 列表数据
     * @param $page
     * @param $pageSize
     * @param $params
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function lists($page,$pageSize,$params)
    {
        $where = [];
        $group = [];
        $this->where($params, $where,$group);
        $count = $this->doCount($where,$group);
        $returnArr = $this->doSearch($where, $page, $pageSize,$group);

        return [
            'count' => $count,
            'data' => $returnArr,
            'page' => $page,
            'pageSize' => $pageSize
        ];
    }

    /** 搜索条件
     * @param $data
     * @param $where
     * @return \think\response\Json
     */
    private function where($data, &$where,&$groupBy=[])
    {

        $groupBy['field'] = 'id,channel_id,customer_id,buyer_qauntity as bq,message_quantity as mq,dispute_quantity as dq,dateline as start_time,dateline as end_time';
        $groupBy['group'] = '';
        if (isset($data['channel_id']) && !empty($data['channel_id'])) {
            $where['channel_id'] = ['eq', $data['channel_id']];
        }
        if (isset($data['customer_id']) && !empty($data['customer_id'])) {
            $where['customer_id'] = ['eq', $data['customer_id']];
        }
        $data['date_b'] = isset($data['date_b']) && $data['date_b'] ? $data['date_b'] : '2018-1-1';
        $data['date_e'] = isset($data['date_e']) && $data['date_e'] ? $data['date_e'] : date('Y-m-d');


        $condition = timeCondition($data['date_b'], $data['date_e']);

        if (!is_array($condition)) {
            return json(['message' => '日期格式错误'], 400);
        }
        if (!empty($condition)) {
            $where['dateline'] = $condition;
            $groupBy['group'] = 'customer_id';
            $groupBy['field'] = 'id,channel_id,customer_id,sum(buyer_qauntity) as bq,sum(message_quantity) as mq,sum(dispute_quantity) as dq,min(dateline) as start_time,max(dateline) as end_time';
        }
    }


    /**
     ** 客服业绩统计信息写入数据库
     * @return bool
     * @throws Exception
     */
    public function writeInMessage()
    {
        $orderData = Cache::store('report')->getSaleByMessage();
        foreach ($orderData as $k => $v) {
            $reportMessageModel = new ReportStatisticByMessage();
            try {
                $temp = $v;
                if (!isset($temp['dateline']) || $temp['dateline'] < 0) {
                    //删除缓存
                    Cache::store('report')->delSaleByMessage($k);
                    continue;
                }
                //查看是否存在该记录了
                $reportMessageInfo = $reportMessageModel->where([
                    'dateline' => $temp['dateline'],
                    'channel_id' => $temp['channel_id'],
                    'customer_id' => $temp['customer_id']
                ])->find();
                if (!empty($reportMessageInfo)) {
                    $new['buyer_qauntity'] = $reportMessageInfo['buyer_qauntity'] + $temp['buyer_qauntity'];
                    $new['message_quantity'] = $reportMessageInfo['message_quantity'] + $temp['message_quantity'];
                    $new['dispute_quantity'] = $reportMessageInfo['dispute_quantity'] + $temp['dispute_quantity'];
                    $new = $this->checkData($new);
                    $reportMessageModel->where([
                        'dateline' => $temp['dateline'],
                        'channel_id' => $temp['channel_id'],
                        'customer_id' => $temp['customer_id']
                    ])->update($new);
                } else {
                    if (!Cache::store('partition')->getPartition('ReportStatisticByMessage', time())) {
                        Cache::store('partition')->setPartition('ReportStatisticByMessage', time(), null, []);
                    }
                    $temp = $this->checkData($temp);
                    $reportMessageModel->allowField(true)->isUpdate(false)->save($temp);
                }
                //删除缓存
                Cache::store('report')->delSaleByMessage($k);
            } catch (Exception $e) {
                throw new Exception($e->getMessage() . $e->getFile() . $e->getLine());
            }
        }
        return true;
    }

    /**
     * 检查数据
     * @param array $data
     * @return array
     */
    private function checkData(array $data)
    {
        $newData = [];
        foreach ($data as $k => $v) {
            if (is_numeric($v)) {
                if ($v < 0) {
                    $newData[$k] = 0;
                } else {
                    $newData[$k] = $v;
                }
            } else {
                $newData[$k] = $v;
            }
        }
        return $newData;
    }


    /**
     * 获取缓存
     * @param $channel_id
     * @return array
     */
    public function getCacheMessage($channel_id)
    {
        $key = $channel_id . ':*';
        $cache = Cache::handler(true);
        $reportKeys = $cache->keys(Report::statisticMessagePrefix.$key);
        $reportData = [];
        foreach($reportKeys as $k => $value){
            $data = $cache->hGetAll($value);
            if (isset($reportData[$data['dateline']]['buyer_qauntity'])) {
                $reportData[$data['dateline']]['buyer_qauntity'] += intval($data['buyer_qauntity']);
            } else {
                $reportData[$data['dateline']]['buyer_qauntity'] = [];
                $reportData[$data['dateline']]['buyer_qauntity'] = intval($data['buyer_qauntity']);
            }

            if (isset($reportData[$data['dateline']]['message_quantity'])) {
                if (isset($data['message_quantity'])) {
                    $reportData[$data['dateline']]['message_quantity'] += intval($data['message_quantity']);
                }
            } else {
                $reportData[$data['dateline']]['message_quantity'] = [];
                $reportData[$data['dateline']]['message_quantity'] = isset($data['message_quantity']) ? intval($data['message_quantity']): 0;
            }

            if (isset($reportData[$data['dateline']]['dispute_quantity'])) {
                if (isset($data['dispute_quantity'])) {
                    $reportData[$data['dateline']]['dispute_quantity'] += intval($data['dispute_quantity']);
                }
            } else {
                $reportData[$data['dateline']]['dispute_quantity'] = [];
                $reportData[$data['dateline']]['dispute_quantity'] = isset($data['dispute_quantity']) ? intval($data['dispute_quantity']): 0;
            }
        }
        return $reportData;
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
        $lastApplyTime = $cache->hget('hash:export_stock_apply',$userId);
        if ($lastApplyTime && time() - $lastApplyTime < 5) {
            throw new JsonErrorException('请求过于频繁',400);
        } else {
            $cache->hset('hash:export_stock_apply',$userId,time());
        }
        try {
            $model = new ReportExportFiles();
            $data['applicant_id'] = $userId;
            $data['apply_time'] = time();
            $data['export_file_name'] = $this->createExportFileName($userId);
            $data['status'] = 0;
            $data['applicant_id'] = $userId;
            $model->allowField(true)->isUpdate(false)->save($data);
            $params['file_name'] = $data['export_file_name'];
            $params['apply_id'] = $model->id;
            (new CommonQueuer(CustomerMessageExportQueue::class))->push($params);
            return true;
        } catch (\Exception $ex) {
            Db::rollback();
            throw new JsonErrorException('申请导出失败');
        }
    }

    /**
     * 创建导出文件名
     * @param int $user_id
     * @return string
     */
    protected function createExportFileName($user_id)
    {
        $fileName = '客服业绩统计_'.$user_id.'_'.date("Y_m_d_H_i_s").'.xlsx';
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
            ini_set('memory_limit', '4096M');
            //验证时申请id和文件名不能为空
            $validate = new FileExportValidate();
            if (!$validate->scene('export')->check($params)) {
                throw new Exception($validate->getError());
            }

            $fileName = $params['file_name'];
            $downLoadDir = '/download/customer_message/';
            $saveDir = ROOT_PATH . 'public' . $downLoadDir;
            if (!is_dir($saveDir) && !mkdir($saveDir, 0777, true)) {
                throw new Exception('导出目录创建失败');
            }
            $fullName = $saveDir . $fileName;
            //创建excel对象
            $excel = new \PHPExcel();
            $excel->setActiveSheetIndex(0);
            $sheet = $excel->getActiveSheet();
            $titleRowIndex = 1;
            $dataRowStartIndex = 2;
            $titleMap  = $this->colMap['title'];
            $lastCol   = 'F';
            $dataMap   = $this->colMap['data'];
            //设置表头和表头样式
            foreach ($titleMap as $col => $set) {
                $sheet->getColumnDimension($col)->setWidth($set['width']);
                $sheet->getCell($col . $titleRowIndex)->setValue($set['title']);
                $sheet->getStyle($col . $titleRowIndex)
                    ->getFill()
                    ->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('FF9900');
                $sheet->getStyle($col . $titleRowIndex)
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
            }
            $sheet->setAutoFilter('A1:'.$lastCol.'1');

            //统计需要导出的数据行
            $where = [];
            $group = [];
            $this->where($params, $where,$group);
            $where = is_null($where) ? [] : $where;
            $fields =  $this->getField();
            $count = $this->doCount($where,$group);
            $pageSize = 1000;
            $loop     = ceil($count/$pageSize);
            //分批导出
            for ($i = 0; $i<$loop; $i++) {
                $data = $this->doSearch($where, $i+1, $pageSize,$group);
                foreach ($data as $r) {
                    foreach ($dataMap as $field => $set){
                        $cell = $sheet->getCell($set['col']. $dataRowStartIndex);
                        switch ($set['type']) {
                            case 'time_stamp':
                                if (empty($r[$field])) {
                                    $cell->setValue('');
                                } else {
                                    $cell->setValue(date('Y-m-d', $r[$field]));
                                }
                                break;
                            case 'numeric':
                                $cell->setDataType(\PHPExcel_Cell_DataType::TYPE_NUMERIC);
                                if (empty($r[$field])) {
                                    $cell->setValue(0);
                                } else {
                                    $cell->setValue($r[$field]);
                                }
                                break;
                            default:
                                if (is_null($r[$field])) {
                                    $r[$field] = '';
                                }
                                $cell->setValue($r[$field]);
                        }
                    }
                    $dataRowStartIndex++;
                }
            }
            $writer = \PHPExcel_IOFactory::createWriter($excel,'Excel2007');
            $writer->save($fullName);
            if (is_file($fullName)) {
                $applyRecord = ReportExportFiles::get($params['apply_id']);
                $applyRecord['exported_time'] = time();
                $applyRecord['download_url'] = $downLoadDir.$fileName;
                $applyRecord['status'] = 1;
                $applyRecord->allowField(true)->isUpdate(true)->save();
            } else {
                throw new Exception('文件写入失败');
            }
        } catch (\Exception $ex) {
            $applyRecord = ReportExportFiles::get($params['apply_id']);
            $applyRecord['status'] = 2;
            $applyRecord['error_message'] = $ex->getMessage();
            $applyRecord->isUpdate(true)->save();
            Cache::handler()->hset(
                'hash:report_export',
                $params['apply_id'].'_'.time(),
                '申请id: '.$params['apply_id'].',导出失败:'.$ex->getMessage());
        }
    }

    /**
     * @desc 获取字段
     */
    private function getField()
    {
        $fields = '*';
        return $fields;
    }

    /**
     * 搜索
     * @param $field
     * @param array $condition
     * @param int $page
     * @param int $pageSize
     * @return false|\PDOStatement|string|\think\Collection
     */
    protected function doSearch(array $condition = [], $page = 1, $pageSize = 10,$group ='')
    {
        $list = $this->reportStatisticByMessageModel->where($condition)->field($group['field'])->group($group['group'])->page($page, $pageSize)->select();
        $returnArr = [];

        $departmentServer = new DepartmentServer();
        $departmentUserMapService = new DepartmentUserMapService();

        foreach ($list as $data) {
            $one = [
                'id' => $data->id,
                'customer_id' => $data->customer_id,
                'customer' => $data->customer,
                'dateline' => $data->dateline,
                'message_quantity' => $data->mq,
                'buyer_qauntity' => $data->bq,
                'dispute_quantity' => $data->dq,
                'channel_id' => $data->channel_id,
                'channel' => $data->channel,
            ];

            $department_ids = $departmentUserMapService->getDepartmentByUserId($data->customer_id);
            $departmentInfo = '';
            foreach ($department_ids as $d => $department) {
                if (!empty($department)) {
                    $departmentInfo .= $departmentServer->getDepartmentNames($department) . ',';
                }
            }
            $departmentInfo = rtrim($departmentInfo, ',');
            $one['customer'] =  $departmentInfo .  $one['customer'];
            $returnArr[] =$one;
        }
        unset($list);
        return $returnArr;
    }

    /**
     * 查询总数
     * @param array $condition
     * @param array $join
     * @return int|string
     */
    protected function doCount(array $condition = [],$group ='')
    {
        if($group['group']){
            $total =  $this->reportStatisticByMessageModel->where($condition)->group($group['group'])->count();
        }else{
            $total =  $this->reportStatisticByMessageModel->where($condition)->count();
        }

        return $total;
    }

}