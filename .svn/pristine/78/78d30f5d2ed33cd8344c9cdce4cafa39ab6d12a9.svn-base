<?php
namespace app\report\service;

use app\common\cache\Cache;
use app\common\model\Goods;
use app\common\model\Message;
use app\common\model\report\ReportStatisticPublishByTime;
use app\common\model\report\ReportStatisticPublishByShelf;

use app\common\service\ChannelAccountConst;
use app\common\service\Common;
use app\common\service\MessageStatusConst;
use app\common\service\Report;
use app\order\service\OrderService;
use app\report\queue\PublishbyTimeExportQueue;
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
class StatisticTime
{
    protected $colMap = [
        'title' => [
            'A' => ['title' => 'SPU', 'width' => 15],
            'B' => ['title' => '开发员', 'width' => 30],
            'C' => ['title' => 'eBay平台', 'width' => 30],
            'D' => ['title' => '亚马逊平台', 'width' => 15],
            'E' => ['title' => 'wish平台', 'width' => 15],
            'F' => ['title' => '速卖通平台', 'width' => 20],
            'G' => ['title' => 'joom平台', 'width' => 20],
            'H' => ['title' => 'Mymall平台', 'width' => 20],
        ],
        'data' => [
            'spu' => ['col' => 'A', 'type' => 'str'],
            'developer' => ['col' => 'B', 'type' => 'str'],
            'channel_1' => ['col' => 'C', 'type' => 'str'],
            'channel_2' => ['col' => 'D', 'type' => 'str'],
            'channel_3' => ['col' => 'E', 'type' => 'str'],
            'channel_4' => ['col' => 'F', 'type' => 'str'],
            'channel_7' => ['col' => 'G', 'type' => 'str'],
            'channel_8' => ['col' => 'H', 'type' => 'str'],
        ]
    ];

    protected $gooodsModel = null;

    public function __construct()
    {
        if (is_null($this->gooodsModel)) {
            $this->gooodsModel = new Goods();
        }
    }

    public function getChannel()
    {
        $data = [
            ChannelAccountConst::channel_ebay => [
                'channel_id' => ChannelAccountConst::channel_ebay,
                'name' => 'eBay平台',
                'num' => 0,
            ],
            ChannelAccountConst::channel_amazon => [
                'channel_id' => ChannelAccountConst::channel_amazon,
                'name' => '亚马逊平台',
                'num' => 0,
            ],
            ChannelAccountConst::channel_wish => [
                'channel_id' => ChannelAccountConst::channel_wish,
                'name' => 'Wish平台',
                'num' => 0,
            ],
            ChannelAccountConst::channel_aliExpress => [
                'channel_id' => ChannelAccountConst::channel_aliExpress,
                'name' => '速卖通平台',
                'num' => 0,
            ],
            ChannelAccountConst::channel_Joom => [
                'channel_id' => ChannelAccountConst::channel_Joom,
                'name' => 'Joom平台',
                'num' => 0,
            ],
            ChannelAccountConst::channel_Pandao => [
                'channel_id' => ChannelAccountConst::channel_Pandao,
                'name' => 'MyMall平台',
                'num' => 0,
            ],
            ChannelAccountConst::channel_Shoppo => [
                'channel_id' => ChannelAccountConst::channel_Shoppo,
                'name' => 'shoppo平台',
                'num' => 0,
            ],
            ChannelAccountConst::channel_Shopee => [
                'channel_id' => ChannelAccountConst::channel_Shopee,
                'name' => 'Shopee平台',
                'num' => 0,
            ],
        ];
        return $data;
    }

    /**
     *
     * @param $params
     * @return array|false|\PDOStatement|string|\think\Collection
     * @throws Exception
     */
    public function getShelf($params)
    {
        $channelId = $params['channel_id'] ?? 0;
        $goodsId = $params['id'] ?? 0;
        if(!$channelId || !$goodsId){
            return [];
        }
        $list = (new StatisticShelf())->getAccountByGoodsIdChannelId($goodsId,$channelId);
        if($list){
            $allAccount = Cache::store('Account')->getAccountByChannel($channelId);
            foreach ($list as &$item){
                $item['accountName'] = $allAccount[$item['account_id']]['code'] ?? '';
            }
            return $list;
        }
        return [];
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
        $this->where2($params, $where, $group);
        $count = $this->doCount2($where,$group);


        $order_str = 'publish_time DESC';
        if(isset($params['order_type'])&&!empty($params['order_type'])){
            $sort = $params['order_sort']?$params['order_sort']:'desc';
            switch ($params['order_type']){
                case 'publish_time':
                    $order_str = 'publish_time '.$sort;
                    break;
                case 'total_num':
                    $order_str = 'total_num '.$sort;
                    break;
            }
        }

        $returnArr = $this->doSearch2($where, $page, $pageSize, $group,false, $order_str);

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
    private function where($data, &$where,&$groupBy=[],$isTwo = false)
    {

        $groupBy['field'] = 'id,publish_time,spu,developer_id';//publish_time:商品上架时间
        $groupBy['group'] = '';
        if (isset($data['channel_id']) && !empty($data['channel_id'])) {
            $where['channel_id'] = ['eq', $data['channel_id']];
        }
        if (isset($data['account_id']) && !empty($data['account_id'])) {
            $where['account_id'] = ['eq', $data['account_id']];
        }
        if (isset($data['spu']) && !empty($data['spu'])) {
            $where['spu'] = ['eq', $data['spu']];
        }
        $data['date_b'] = isset($data['date_b']) ? $data['date_b'] : 0;
        $data['date_e'] = isset($data['date_e']) ? $data['date_e'] : 0;
        $condition = timeCondition($data['date_b'], $data['date_e']);
        if (!is_array($condition)) {
            return json(['message' => '日期格式错误'], 400);
        }
        $timeType = 'publish_time'; // 发布时间
        if (!empty($condition)) {
            $where[$timeType] = $condition;
        }
        if($isTwo){
            $groupBy['field'] = 'dateline,channel_id,account_id,shelf_id,count(goods_id) as goodsC,department_id,catetory_id,goods_id';
            $groupBy['group'] = 'dateline,channel_id,account_id,shelf_id';
        }
    }



    private function where2($data, &$where,&$groupBy=[],$isTwo = false)
    {

        $groupBy['field'] = 'id,publish_time,spu,developer_id,total_num,ebay,amazon,wish,aliExpress,Joom,Pandao,Shopee,Shoppo';//publish_time:商品上架时间
        $groupBy['group'] = '';




        //-----------------------------
        if (isset($data['channel_id']) && !empty($data['channel_id'])) {

            //
            $field='';
            switch ($data['channel_id'])
            {
                case 1:
                    $field='ebay';
                    break;
                case 2:
                    $field='amazon';
                    break;
                case 3:
                    $field='wish';
                    break;
                case 4:
                    $field='aliExpress';
                    break;
                case 7:
                    $field='Joom';
                    break;
                case 8:
                    $field='Pandao';
                    break;
                case 9:
                    $field='Shopee';
                    break;
                case 17:
                    $field='Shoppo';
                    break;
            }

//            $where['channel_id'] = ['eq', $data['channel_id']];
            if (!empty($field))
            {
                if (@intval($data['total_b'])>0 && @intval($data['total_e'])>0)
                {
                    $where[$field]=['between',[$data['total_b'],$data['total_e']]];
                }
                if (@intval($data['total_b'])==0 && @intval($data['total_e'])>0)
                {
                    $where[$field]=['<=',$data['total_e']];
                }
                if (@intval($data['total_b'])>0 && @intval($data['total_e'])==0)
                {
                    $where[$field]=['>=',$data['total_b']];
                }

            }
//

        } else {
            if (@intval($data['total_b'])>0 && @intval($data['total_e'])>0)
            {
                $where['total_num']=['between',[$data['total_b'],$data['total_e']]];
            }
            if (@intval($data['total_b'])==0 && @intval($data['total_e'])>0)
            {
                $where['total_num']=['<=',$data['total_e']];
            }
            if (@intval($data['total_b'])>0 && @intval($data['total_e'])==0)
            {
                $where['total_num']=['>=',$data['total_b']];
            }

        }
        if (isset($data['developer_id']) && !empty($data['developer_id'])) {
            $where['developer_id'] = ['eq', $data['developer_id']];
        }
        if (isset($data['spu']) && !empty($data['spu'])) {
            if (is_json($data['spu']))
            {
                $arr=json_decode($data['spu'],true);
                $where['spu']=['IN',$arr];
            } else {
                $where['spu']=['eq',$data['spu']];
            }

        }
        $data['date_b'] = isset($data['date_b']) ? $data['date_b'] : 0;
        $data['date_e'] = isset($data['date_e']) ? $data['date_e'] : 0;
        $condition = timeCondition($data['date_b'], $data['date_e']);
        if (!is_array($condition)) {
            return json(['message' => '日期格式错误'], 400);
        }
        $timeType = 'publish_time'; // 发布时间
        if (!empty($condition)) {
            $where[$timeType] = $condition;
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
            (new CommonQueuer(PublishbyTimeExportQueue::class))->push($params);
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
        $fileName = 'spu上架时间统计_'.$user_id.'_'.date("Y_m_d_H_i_s").'.xlsx';
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
            $sheet->setAutoFilter('A1:'.$lastCol.'1');

            //统计需要导出的数据行
            $where = [];
            $group = [];
            $this->where2($params, $where,$group);
            $where = is_null($where) ? [] : $where;
            $fields =  $this->getField();
            $count = $this->doCount2($where,$group);
            $pageSize = 1000;
            $loop     = ceil($count/$pageSize);
            //分批导出
            for ($i = 0; $i<$loop; $i++) {
                $data = $this->doSearch2($where, $i+1, $pageSize,$group,true,'');

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
    protected function doSearch(array $condition = [], $page = 1, $pageSize = 20,$group ='',$isExport = false)
    {
        $list = $this->gooodsModel->where($condition)->field($group['field'])
            ->group($group['group'])->order('publish_time desc')->page($page, $pageSize)->select();
        $returnArr = [];

        if($isExport){ //导出
            foreach ($list as $data) {
                $one = $data;
                $one['developer'] = $data->developer;
                $statistics = $this->getStatistics($data->id,$isExport);
                foreach ($statistics as $k=>$v){
                    $one['channel_'.$k] = $v['num'];
                }
                $returnArr[] =$one;
            }
        }else{
            foreach ($list as $data) {
                $one = $data;
                $one['developer'] = $data->developer;
                $statisticsa = [];
                $statistics = $this->getStatistics($data->id,$isExport);
                $tmp_total=0;
                foreach ($statistics as $k=>$v){
                    $statisticsa[] = $v;
                }
                $one['statistics'] = $statisticsa;
                $returnArr[] =$one;
            }
        }
        unset($list);
        return $returnArr;
    }


    /**
     * 替换老的方法-潘多拉
     * @param array $condition
     * @param int $page
     * @param int $pageSize
     * @param string $group
     * @param bool $isExport
     * @param $order_str
     * @return array
     */
    protected function doSearch2(array $condition = [], $page = 1, $pageSize = 20, $group ='',$isExport = false ,$order_str)
    {
//        $list = $this->gooodsModel->where($condition)->field($group['field'])
//            ->group($group['group'])->order('publish_time desc')->page($page, $pageSize)->select();


        $Goods = new Goods();
        $Report= new ReportStatisticPublishByShelf();

        $subsql=Db::table('report_statistic_publish_by_channel') //$Report->getTable()
            ->where([])
            ->field('goods_id ,COUNT( case channel_id 
				when 1 then goods_id end) \'ebay\',
			COUNT( case channel_id 
				when 2 then goods_id end) \'amazon\',
			COUNT( case channel_id 
				when 3 then goods_id end) \'wish\',
			COUNT( case channel_id 
				when 4 then goods_id end) \'aliExpress\',
			COUNT( case channel_id 
				when 7 then goods_id end) \'Joom\',
			COUNT( case channel_id 
				when 8 then goods_id end ) \'Pandao\',
			COUNT( case channel_id 
				when 9 then goods_id end) \'Shopee\',
			COUNT( case channel_id 
				when 17 then goods_id end) \'Shoppo\',
             COUNT(goods_id) total_num')
            ->group('goods_id')
            ->buildSql();

        $list=Db::table($Goods->getTable())->alias('G')
            ->field('id,publish_time,spu,developer_id,total_num,ebay,amazon,wish,aliExpress,Joom,Pandao,Shopee,Shoppo')
            ->join([$subsql=>'R'],'G.id=R.goods_id')
            ->where($condition)
            //->group($group['group'])
            ->order($order_str)
            ->page($page, $pageSize)
            ->select();
        //echo $sql= Db::getLastSql();


        $returnArr = [];

        if($isExport){ //导出
            foreach ($list as $data) {
                $one = $data;
                //$one['developer'] = $data->developer;
                $user = Cache::store('user')->getOneUser($data['developer_id']);
                $one['developer']=$user['realname'] ?? '';
//                $statistics = $this->getStatistics($data['id'],$isExport);
//                foreach ($statistics as $k=>$v){
//                    $one['channel_'.$k] = $v['num'];
//                }

                $one['channel_1'] =intval($one['ebay']);
                $one['channel_2'] =intval($one['amazon']);
                $one['channel_3'] =intval($one['wish']);
                $one['channel_4'] =intval($one['aliExpress']);
                $one['channel_7'] =intval($one['Joom']);
                $one['channel_8'] =intval($one['Pandao']);
                $one['channel_9'] =intval($one['Shopee']);
                $one['channel_17'] =intval($one['Shoppo']);



                unset($one['ebay']);
                unset($one['amazon']);
                unset($one['wish']);
                unset($one['aliExpress']);
                unset($one['Joom']);
                unset($one['Pandao']);
                unset($one['Shopee']);
                unset($one['Shoppo']);



                $returnArr[] =$one;
            }
        }else{
            foreach ($list as $data) {
                $one = $data;
                //$one['developer'] = $data->developer;
                $user = Cache::store('user')->getOneUser($data['developer_id']);
                $one['developer']=$user['realname'] ?? '';

//                $statisticsa = [];
//                $statistics = $this->getStatistics($data['id'],$isExport);
//                $tmp_total=0;
//                foreach ($statistics as $k=>$v){
//                    $statisticsa[] = $v;
//                }
//                $one['statistics'] = $statisticsa;
                $one['statistics'][]=[
                    'channel_id'=>1,
                    'name'=>'eBay平台',
                    'num'=>intval($one['ebay']),
                ];
                $one['statistics'][]=[
                    'channel_id'=>2,
                    'name'=>'亚马逊平台',
                    'num'=>intval($one['amazon']),
                ];
                $one['statistics'][]=[
                    'channel_id'=>3,
                    'name'=>'Wish平台',
                    'num'=>intval($one['wish']),
                ];
                $one['statistics'][]=[
                    'channel_id'=>4,
                    'name'=>'速卖通平台',
                    'num'=>intval($one['aliExpress']),
                ];
                $one['statistics'][]=[
                    'channel_id'=>7,
                    'name'=>'Joom平台',
                    'num'=>intval($one['Joom']),
                ];
                $one['statistics'][]=[
                    'channel_id'=>8,
                    'name'=>'MyMall平台',
                    'num'=>intval($one['Pandao']),
                ];
                $one['statistics'][]=[
                    'channel_id'=>9,
                    'name'=>'Shopee平台',
                    'num'=>intval($one['Shopee']),
                ];
                $one['statistics'][]=[
                    'channel_id'=>17,
                    'name'=>'shoppo平台',
                    'num'=>intval($one['Shoppo']),
                ];

                unset($one['ebay']);
                unset($one['amazon']);
                unset($one['wish']);
                unset($one['aliExpress']);
                unset($one['Joom']);
                unset($one['Pandao']);
                unset($one['Shopee']);
                unset($one['Shoppo']);




                $returnArr[] =$one;
            }
        }
        unset($list);
        return $returnArr;
    }




    protected function getStatistics($goodsId,$isExport = false)
    {
        $list = (new StatisticShelf())->getTimesByGoodsId($goodsId);
        $all = $this->getChannel();
        foreach ($list as $item){
            $all[$item['channel_id']]['num'] = $item['num'];
        }
        return $all;
    }

/*
 * select * from goods g
left join (select sum(times) as num , goods_id FROM `report_statistic_publish_by_shelf` GROUP BY goods_id) r
on g.id=r.goods_id
where (num<50 and num>10)
order by num desc limit 20
*/

    /**
     * 查询总数
     * @param array $condition
     * @param array $join
     * @return int|string
     */
    protected function doCount(array $condition = [],$group ='')
    {
        if($group['group']){
            $total =  $this->gooodsModel->where($condition)->group($group['group'])->count();
        }else{
            $total =  $this->gooodsModel->where($condition)->count();
        }

        return $total;
    }



    protected  function doCount2($where)
    {
        $Goods = new Goods();
        $Report= new ReportStatisticPublishByShelf();

//        $subsql=Db::table($Report->getTable())
//            ->where([])->field('sum(times) as total_num , goods_id,times')
//            ->group('goods_id')
//            ->buildSql();


        $subsql=Db::table('report_statistic_publish_by_channel') //$Report->getTable()
            ->where([])
            ->field('goods_id ,COUNT( case channel_id 
				when 1 then goods_id end) \'ebay\',
			COUNT( case channel_id 
				when 2 then goods_id end) \'amazon\',
			COUNT( case channel_id 
				when 3 then goods_id end) \'wish\',
			COUNT( case channel_id 
				when 4 then goods_id end) \'aliExpress\',
			COUNT( case channel_id 
				when 7 then goods_id end) \'Joom\',
			COUNT( case channel_id 
				when 8 then goods_id end ) \'Pandao\',
			COUNT( case channel_id 
				when 9 then goods_id end) \'Shopee\',
			COUNT( case channel_id 
				when 17 then goods_id end) \'Shoppo\',
             COUNT(goods_id) total_num')
            ->group('goods_id')
            ->buildSql();



        $countData=Db::table($Goods->getTable())->alias('G')->join([$subsql=>'R'],'G.id=R.goods_id')->where($where)->count();
        //echo Db::getLastSql();

        return $countData;

    }

}