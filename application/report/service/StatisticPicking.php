<?php
namespace app\report\service;

use app\common\cache\Cache;
use app\common\model\Goods;
use app\common\model\User;
use app\common\model\Message;
use app\common\model\report\ReportStatisticPublishByPicking;
use app\common\model\report\ReportStatisticPublishByShelf;
use app\common\service\Common;
use app\common\service\MessageStatusConst;
use app\common\service\Report;
use app\order\service\OrderService;
use app\report\queue\PublishbyPickingExportQueue;
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
/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/08/24
 * Time: 15:24
 */
class StatisticPicking
{
    protected $colMap = [
        'title' => [
            'A' => ['title' => '平台', 'width' => 15],
            'B' => ['title' => '账号', 'width' => 30],
            'C' => ['title' => '日期', 'width' => 30],
            'D' => ['title' => '人员', 'width' => 15],
            'E' => ['title' => '分类', 'width' => 15],
            'F' => ['title' => 'GOODS', 'width' => 20],
            'G' => ['title' => '次数', 'width' => 20],
//            'H' => ['title' => 'sku 累加', 'width' => 20],
            'H' => ['title' => '部门', 'width' => 20],
        ],
        'data' => [
            'channel' =>                     ['col' => 'A', 'type' => 'str'],
            'account' =>                   ['col' => 'B', 'type' => 'str'],
            'dateline' =>                  ['col' => 'C', 'type' => 'time_stamp'],
            'shelf_name' =>               ['col' => 'D', 'type' => 'str'],
            'catetory' =>             ['col' => 'E', 'type' => 'str'],
            'goods' =>             ['col' => 'F', 'type' => 'str'],
            'goodsC' =>             ['col' => 'G', 'type' => 'str'],
//            'quantity' =>             ['col' => 'H', 'type' => 'str'],
            'department' =>             ['col' => 'I', 'type' => 'str'],
        ]
    ];

    protected $reportStatisticByMessageModel = null;

    public function __construct()
    {
        if (is_null($this->reportStatisticByMessageModel)) {
            $this->reportStatisticByMessageModel = new ReportStatisticPublishByPicking();
        }
    }

    /**
     * @param $page
     * @param $pageSize
     * @param $parms
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getSpuMessage($page,$pageSize,$parms)
    {
        $where = [
            'dateline' => $parms['dateline'],
            'channel_id' => $parms['channel_id'],
            'account_id' => $parms['account_id'],
            'shelf_id' => $parms['shelf_id'],
        ];
        $list = $this->reportStatisticByMessageModel->where($where)->page($page,$pageSize)->select();
        $count = $this->reportStatisticByMessageModel->where($where)->count();
        $returnArr = [];

        foreach ($list as $data) {
            $one = $data;
            $one['goods'] =  $data->goods;
            $goods = $this->reportStatisticByMessageModel->getSpuAndName($data['goods_id']);
            $one['goodsName'] = $goods['goodsName'];
            $one['spu'] = $goods['spu'];
            $returnArr[] =$one;
        }
        unset($list);

        return [
            'count' => $count,
            'data' => $returnArr,
            'page' => $page,
            'pageSize' => $pageSize
        ];
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
        $this->where($params, $having,$where,$group,true);
        $count = $this->doCount($where,$group,$having);

        $returnArr = $this->doSearch($where, $page, $pageSize,$group,$params,$having);

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
    private function where($data,&$having, &$where,&$groupBy=[],$isTwo = false)
    {

        $groupBy['field'] = '*';
        $groupBy['group'] = '';
        if (isset($data['channel_id']) && !empty($data['channel_id'])) {
            $where['channel_id'] = ['eq', $data['channel_id']];
        }
        if (isset($data['account_code']) && !empty($data['account_code'])) {
            //传递了channel_id=1,账号简称account_code='auto'
            $channelId=$data['channel_id'];
            $accountCode=$data['account_code'];
            $channelAccountTable=[
                1=>'app\common\model\ebay\EbayAccount',
                2=>'app\common\model\amazon\AmazonAccount',
                3=>'app\common\model\wish\WishAccount',
                4=>'app\common\model\aliexpress\AliexpressAccount',
                5=>'app\common\model\cd\CdAccount',
                6=>'app\common\model\lazada\LazadaAccount',
                7=>'app\common\model\joom\JoomAccount',
                8=>'app\common\model\pandao\PandaoAccount',
                9=>'app\common\model\shopee\ShopeeAccount',
                10=>'app\common\model\paytm\PaytmAccount',
                11=>'app\common\model\walmart\WalmartAccount',
                12=>'app\common\model\vova\VovaAccount',
                13=>'app\common\model\jumia\JumiaAccount',
                14=>'app\common\model\umka\UmkaAccount',
            ];
            $accountIds=$channelAccountTable[$channelId]::where('code','like','%'.$accountCode.'%')->column('id');
            $where['account_id'] = ['in', $accountIds];
        }
        if (!empty($data['spu'])) {//本地SPU
            $spus = json_decode($data['spu'],true);
            if (!empty($spus)) {
                $goodsIds = Goods::whereIn('spu',$spus)->column('id');
                $where['goods_id'] = ['in', $goodsIds];
            }
        }
        if (isset($data['shelf_name']) && !empty($data['shelf_name'])) {
            $userId=User::where('realname','like','%'.$data['shelf_name'].'%')->column('id');
            $where['shelf_id'] = ['in', $userId];
        }
        if($data['min_num']!==''||$data['max_num']!==''){
            $goodsC='count(goods_id)';
            $minNum=$data['min_num'];
            $maxNum=$data['max_num'];
            if ($minNum =='') {
                $having = $goodsC.'<='.$maxNum;
            } else if ($maxNum =='') {
                $having = $goodsC.'>='.$minNum;
            } else {
                $having = $goodsC.'>='.$minNum.' and '.$goodsC.'<='.$maxNum;
            }
        }
        $data['date_b'] = isset($data['date_b']) ? $data['date_b'] : 0;
        $data['date_e'] = isset($data['date_e']) ? $data['date_e'] : 0;
        $condition = timeCondition($data['date_b'], $data['date_e']);
        if (!is_array($condition)) {
            return json(['message' => '日期格式错误'], 400);
        }
        if (!empty($condition)) {
            $where['dateline'] = $condition;
        }
        if($isTwo){
            $groupBy['field'] = 'dateline,channel_id,account_id,shelf_id,count(goods_id) as goodsC,department_id,catetory_id,goods_id';
            $groupBy['group'] = 'dateline,channel_id,account_id,shelf_id';
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
            (new CommonQueuer(PublishbyPickingExportQueue::class))->push($params);
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
        $fileName = '账号刊登下架spu统计 ('.date("Y_m_d_H_i_s").').xlsx';
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
            $lastCol   = 'H';
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
//            $sheet->setAutoFilter('A1:'.$lastCol.'1');

            //统计需要导出的数据行
            $where = [];
            $group = [];
            $this->where($params, $having,$where,$group,true);
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
                if ($applyRecord) {
                    $applyRecord->exported_time = time();
                    $applyRecord->download_url = $downLoadDir.$fileName;
                    $applyRecord->status = 1;
                    $applyRecord->allowField(true)->isUpdate(true)->save();
                }
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
     * 获取排序字段名
     * @param $params
     * @return string
     */
    public function getOrder($params)
    {
        $order='';
        if(!empty($params['sort_val'])){
            $order .= 'goodsC '.$params['sort_val'].',';
        }
        !empty($order) && $order = substr($order,0,'-1');
        return $order;

    }
    /**
     * 搜索
     * @param $field
     * @param array $condition
     * @param int $page
     * @param int $pageSize
     * @return false|\PDOStatement|string|\think\Collection
     */
    protected function doSearch(array $condition = [], $page = 1, $pageSize = 10,$group ='',$params='',$having='')
    {
        $order = $this->getOrder($params);
        $list = $this->reportStatisticByMessageModel->where($condition)
            ->field($group['field'])
            ->group($group['group'])
            ->order($order)
            ->having($having)
            ->page($page, $pageSize)->select();
        $returnArr = [];
        $departmentServer = new DepartmentServer();
        $orderServer = new OrderService();
        foreach ($list as $data) {
            $one = $data;
            $one['shelf_name'] =  $one['shelf_name'];
            $one['channel'] =  $data['channel'];
            $one['catetory'] =  $data->catetory;
            $one['account'] = $orderServer->getAccountName($data->channel_id,$data->account_id);
            $one['department'] =  $departmentServer->getDepartmentNames($data->department_id);
            $one['goods'] =  $data->goods;
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
    protected function doCount(array $condition = [],$group ='',$having='')
    {
        if($group['group']){
            $total =  $this->reportStatisticByMessageModel->where($condition)->group($group['group'])->having($having)->count();
        }else{
            $total =  $this->reportStatisticByMessageModel->where($condition)->count();
        }

        return $total;
    }

    /**
     * 添加下架刊登spu统计
     * @param $channel_id
     * @param $account_id
     * @param $shelf_id
     * @param $goods_id
     * @param int $times
     * @param int $quantity
     * @param string $dateline
     * @return false|int|string
     */
    public static function addReportPicking($channel_id, $account_id, $shelf_id, $goods_id, $times = 0, $quantity = 0, $dateline = '')
    {
        if(!$channel_id){
            return ['result'=>false,'message'=>'缺少必要参数channel_id'];
        }
        if(!$account_id){
            return ['result'=>false,'message'=>'缺少必要参数account_id'];
        }
        if(!$shelf_id){
            return ['result'=>false,'message'=>'缺少必要参数shelf_id'];
        }
        if(!$goods_id){
            return ['result'=>false,'message'=>'缺少必要参数goods_id'];
        }
        if(!$channel_id){
            return ['result'=>false,'message'=>'缺少必要参数channel_id'];
        }
        if(!$dateline){
            $dateline = time();
        }
        $dateline = strtotime(date('Y-m-d',$dateline));
        $pickingModel = new ReportStatisticPublishByPicking();
        $add = [
            'dateline' => $dateline,
            'channel_id' => $channel_id,
            'account_id' => $account_id,
            'shelf_id' => $shelf_id,
            'goods_id' => $goods_id,
            'times' => $times,
            'quantity' => $quantity,
        ];
        return $pickingModel->add($add);
    }
}