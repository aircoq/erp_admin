<?php
namespace app\report\service;

use app\common\cache\Cache;
use app\common\model\aliexpress\AliexpressAccount;
use app\common\model\amazon\AmazonAccount;
use app\common\model\Category;
use app\common\model\ebay\EbayAccount;
use app\common\model\Goods;
use app\common\model\joom\JoomAccount;
use app\common\model\Message;
use app\common\model\pandao\PandaoAccount;
use app\common\model\report\ReportStatisticPublishByPicking;
use app\common\model\report\ReportStatisticPublishByShelf;
use app\common\model\report\ReportStatisticPublishByChannel;
use app\common\model\report\ReportStatisticPublishByAccount;
use app\common\model\shopee\ShopeeAccount;
use app\common\model\User;
use app\common\model\wish\WishAccount;
use app\common\service\ChannelConst;
use app\common\service\Common;
use app\common\service\ImportExport;
use app\common\service\MessageStatusConst;
use app\common\service\Report;
use app\goods\service\GoodsHelp;
use app\order\service\OrderService;
use app\report\queue\PublishbyShelfExportQueue;
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
use app\common\model\Channel;


Loader::import('phpExcel.PHPExcel', VENDOR_PATH);
/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/08/24
 * Time: 15:24
 *
 */
class StatisticShelf
{
    protected $colMap = [
        'title' => [
//            'A' => ['title' => '平台', 'width' => 15],
//            'B' => ['title' => '账号', 'width' => 30],
//            'C' => ['title' => '日期', 'width' => 30],
//            'D' => ['title' => '人员', 'width' => 15],
//            'E' => ['title' => '分类', 'width' => 15],
//            'F' => ['title' => 'GOODS', 'width' => 20],
//            'G' => ['title' => '次数', 'width' => 20],
//            'H' => ['title' => 'sku 累加', 'width' => 20],
//            'I' => ['title' => '部门', 'width' => 20],
            '平台' => 'string',
            '账号' => 'string',
            '日期' => 'string',
            '人员' => 'string',
            '分类' => 'string',
            'GOODS' => 'string',
            '次数' => 'string',
            'sku 累加' => 'string',
            '部门' => 'string',
        ],
        'data' => [
            'channel' =>                     ['col' => 'A', 'type' => 'str'],
            'account' =>                   ['col' => 'B', 'type' => 'str'],
            'dateline' =>                  ['col' => 'C', 'type' => 'time_stamp'],
            'shelf_name' =>               ['col' => 'D', 'type' => 'str'],
            'catetory' =>             ['col' => 'E', 'type' => 'str'],
            'goods' =>             ['col' => 'F', 'type' => 'str'],
            'goodsC' =>             ['col' => 'G', 'type' => 'str'],
            'quantity' =>             ['col' => 'H', 'type' => 'str'],
            'department' =>             ['col' => 'I', 'type' => 'str'],
        ]
    ];
    protected $spuPublishStatistic = [
        'title' => [
//            'A' => ['title' => 'SPU', 'width' => 15],
//            'B' => ['title' => '上架时间', 'width' => 30],
//            'C' => ['title' => '所属平台', 'width' => 30],
//            'D' => ['title' => '分类', 'width' => 15],
//            'E' => ['title' => '开发员', 'width' => 15],
//            'F' => ['title' => '产品状态', 'width' => 20],
//            'G' => ['title' => '刊登总数', 'width' => 20],
//            'H' => ['title' => 'eBay平台', 'width' => 20],
//            'I' => ['title' => '亚马逊平台', 'width' => 20],
//            'J' => ['title' => 'Wish平台', 'width' => 20],
//            'K' => ['title' => '速卖通平台', 'width' => 20],
//            'L' => ['title' => 'Joom平台', 'width' => 20],
//            'M' => ['title' => 'MyMall平台', 'width' => 20],
//            'N' => ['title' => 'Shopee平台', 'width' => 20],
            'SPU' =>'string',
            '上架时间' =>'string',
            '所属平台' =>'string',
            '分类' =>'string',
            '开发员' =>'string',
            '产品状态' =>'string',
            '刊登总数' =>'string',
            'eBay平台' =>'string',
            '亚马逊平台' =>'string',
            'Wish平台' =>'string',
            '速卖通平台' =>'string',
            'Joom平台' =>'string',
            'MyMall平台' =>'string',
            'Shopee平台' =>'string',
        ],
        'data' => [
            'spu' =>                     ['col' => 'A', 'type' => 'str'],
            'publish_time' =>                   ['col' => 'B', 'type' => 'str'],
            'channel_txt' =>                  ['col' => 'C', 'type' => 'str'],
            'category_chain' =>               ['col' => 'D', 'type' => 'str'],
            'developer_name' =>             ['col' => 'E', 'type' => 'str'],
            'sales_status_txt' =>             ['col' => 'F', 'type' => 'str'],
            'total_cnt' =>             ['col' => 'G', 'type' => 'str'],
            'ebay_cnt' =>             ['col' => 'H', 'type' => 'str'],
            'amazon_cnt' =>             ['col' => 'I', 'type' => 'str'],
            'wish_cnt' =>             ['col' => 'J', 'type' => 'str'],
            'aliexpress_cnt' =>             ['col' => 'K', 'type' => 'str'],
            'joom_cnt' =>             ['col' => 'L', 'type' => 'str'],
            'mymall_cnt' =>             ['col' => 'M', 'type' => 'str'],
            'shopee_cnt' =>             ['col' => 'N', 'type' => 'str'],
        ]
    ];

    protected $reportStatisticByMessageModel = null;

    public function __construct()
    {
        if (is_null($this->reportStatisticByMessageModel)) {
            $this->reportStatisticByMessageModel = new ReportStatisticPublishByShelf();
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
        $pickingServer = new ReportStatisticPublishByPicking();
        $returnArr = [];
        foreach ($list as $data) {
            $one = $data;
            $one['goods'] =  $data->goods;
            $goods = $pickingServer->getSpuAndName($data['goods_id']);
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
        $this->where($params,$having, $where,$group,true);
        $count = $this->doCount($where,$having,$group);
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
        if (isset($data['channel_id'])&& !empty($data['channel_id'])) {
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
            $accountIds=$channelAccountTable[$channelId]::where('code','like',$accountCode.'%')->column('id');
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
            $groupBy['field'] = 'dateline,channel_id,account_id,shelf_id,count(goods_id) as goodsC,department_id,catetory_id,goods_id,SUM(quantity) AS sku_total';
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
        if (isset($params['date_type'])) {//spu刊登统计
            $count = $this->spuStatistic($params,1);
        } else {
            //统计需要导出的数据行
            $where = [];
            $group = [];
            $having = '';
            $this->where($params, $having,$where, $group,true);
            $where = is_null($where) ? [] : $where;
            $count = $this->doCount($where, $having,$group);
        }
        $params['file_name'] = $this->createExportFileName($userId,isset($params['date_type']) ? 'SPU刊登' : '账号刊登上架SPU');
        $params['export_type'] = isset($params['date_type']) ? 'spuPublishStatistic' : 'accountSpuStatistic';
        $params['count'] = $count;
        if ($count > 500) {//走队列
            $model = new ReportExportFiles();
            $data['applicant_id'] = $userId;
            $data['apply_time'] = time();
            $data['export_file_name'] = $params['file_name'];
            $data['status'] = 0;
            $data['applicant_id'] = $userId;
            $model->allowField(true)->isUpdate(false)->save($data);
            $params['apply_id'] = $model->id;
            (new CommonQueuer(PublishbyShelfExportQueue::class))->push($params);
            return ['message'=>'成功加入导出队列，请稍后到报表管理栏下载'];
        }
        return $this->export($params);
    }

    /**
     * 创建导出文件名
     * @param int $user_id
     * @return string
     */
    protected function createExportFileName($user_id,$prefix='')
    {
        $fileName = $prefix.'统计 ('.date("Y_m_d_H_i_s").')';
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
//            ini_set('memory_limit', '4096M');
//            //验证时申请id和文件名不能为空
//            $validate = new FileExportValidate();
//            if (!$validate->scene('export')->check($params)) {
//                throw new Exception($validate->getError());
//            }
            $statisticType = $params['export_type'];
            $count = $params['count']??50;
            $fileName = $params['file_name'];
            $applyId = $params['apply_id']??0;
            unset($params['count']);
            unset($params['export_type']);
            unset($params['file_name']);
            unset($params['apply_id']);


            if ($statisticType == 'spuPublishStatistic') {
                $pageSize = 500;
                $loop = ceil($count / $pageSize);
                $lists = [];
                //分批导出
                for ($i=0;$i<$loop;$i++) {
                    $params['page'] = $i+1;
                    $params['pageSize'] = $pageSize;
                    $res = $this->spuStatistic($params, 2);
                    if ($res['result']===false) {
                        throw new Exception($res['message']);
                    }
                    $tmpList = $res['data']['lists'];
                    $lists = array_merge($lists,$tmpList);
                }
                //排序
                foreach ($lists as &$list) {
                    $tmp = [];
                    $tmp[] = $list['spu'];
                    $tmp[] = $list['publish_time'];
                    $tmp[] = $list['channel_txt'];
                    $tmp[] = $list['category_chain'];
                    $tmp[] = $list['developer_name'];
                    $tmp[] = $list['sales_status_txt'];
                    $tmp[] = $list['total_cnt'];
                    $tmp[] = $list['ebay_cnt'];
                    $tmp[] = $list['amazon_cnt'];
                    $tmp[] = $list['wish_cnt'];
                    $tmp[] = $list['aliexpress_cnt'];
                    $tmp[] = $list['joom_cnt'];
                    $tmp[] = $list['mymall_cnt'];
                    $tmp[] = $list['shopee_cnt'];
                    $list = $tmp;
                }
                $title = $this->spuPublishStatistic['title'];
            } else {
                //统计需要导出的数据行
                $where = [];
                $group = [];
                $having = '';
                $this->where($params, $having,$where, $group,true);
                $where = is_null($where) ? [] : $where;
                $pageSize = 1000;
                $loop = ceil($count / $pageSize);

                $lists = [];
                //分批导出
                for ($i = 0; $i < $loop; $i++) {
                    $data = $this->doSearch($where, $i + 1, $pageSize, $group,'',$having);
                    if (!$data) {
                        continue;
                    }
                    $lists = array_merge($lists,$data);
                }
                //排序
                foreach ($lists as &$list) {
                    $tmp['channel'] = $list['channel'];
                    $tmp['account'] = $list['account'];
                    $tmp['dateling'] = $list['dateling'];
                    $tmp['shelf_name'] = $list['shelf_name'];
                    $tmp['category'] = $list['category'];
                    $tmp['goods'] = $list['goods'];
                    $tmp['goodsC'] = $list['goodsC'];
                    $tmp['sku_total'] = $list['sku_total'];
                    $tmp['department'] = $list['department'];
                    $list = $tmp;
                }
                $title = $this->colMap['title'];

            }
            $file = [
                'name' => $fileName,
                'path' => 'customer_message',
            ];
            $res = ImportExport::xlsxwriterExport($title,$lists,$file,$applyId);
            return $res;

        } catch (\Throwable $e) {
            throw new Exception('文件生成失败：'.$e->getMessage());
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
    protected function doSearch(array $condition = [], $page = 1, $pageSize = 10,$group ='',$params='',$having)
    {
        $order = $this->getOrder($params);
        $list = $this->reportStatisticByMessageModel
            ->where($condition)
            ->field($group['field'])
            ->group($group['group'])
            ->having($having)
            ->order($order)
            ->page($page, $pageSize)
            ->select();

        //echo $this->reportStatisticByMessageModel->getLastSql();
        $returnArr = [];
        $departmentServer = new DepartmentServer();
        $departmentUserMapService = new DepartmentUserMapService();
        $orderServer = new OrderService();
        foreach ($list as $data) {
            $one = $data;
            $one['quantity']=$one['sku_total'];
            $one['shelf_name'] = $one['shelf_name'];
            $one['channel'] =  $data['channel'];
            $one['catetory'] =  $data->catetory;
            $one['account'] = $orderServer->getAccountName($data->channel_id,$data->account_id);
            $one['department'] =  $departmentServer->getDepartmentNames($data->department_id);
            $one['goods'] =  $data->goods;
            $one['dateline'] = date('Y-m-d',$one['dateline']);
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
    protected function doCount(array $condition = [],$having='',$group ='',$type='')
    {
        if ($type == 'spu') {
            $total = ReportStatisticPublishByShelf::alias('r')->
            join('goods g', 'r.goods_id=g.id', 'LEFT')->where($condition)->group('goods_id')->
            having($having)->count();
        } else {
            if ($group['group']) {
                $total = $this->reportStatisticByMessageModel->where($condition)->group($group['group'])->having($having)->count();
            } else {
                $total = $this->reportStatisticByMessageModel->where($condition)->count();
            }
        }

        return $total;
    }

    /**
     * 添加上架刊登spu统计
     * @param $channel_id
     * @param $account_id
     * @param $shelf_id
     * @param $goods_id
     * @param int $times
     * @param int $quantity
     * @param string $dateline
     * @return false|int|string
     */
    public static function addReportShelf($channel_id, $account_id, $shelf_id, $goods_id, $times = 0, $quantity = 0, $dateline = '')
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
        $shelfModel = new ReportStatisticPublishByShelf();
        $add = [
            'dateline' => $dateline,
            'channel_id' => $channel_id,
            'account_id' => $account_id,
            'shelf_id' => $shelf_id,
            'goods_id' => $goods_id,
            //'times' => $times,
            //'quantity' => $quantity,
        ];


        $report=$shelfModel->where($add)->find();
        if(!empty($report)){
            if ($report['times']!=$times)
            {
                $rlt=$shelfModel->where($add)->update(['times' => $times]);
            } else {
                $rlt=false;
            }
        //    $rlt=$shelfModel->where($add)->setInc('times',1); //一条一条同步的时使用这个

        } else {
            $add['times']=$times;
            $add['quantity']=$quantity;
            $rlt=$shelfModel->add($add);
        }
        return $rlt;

    }


    /**
     * 【实时】刊登入统计数据表
     * @param $channel_id
     * @param $account_id
     * @param $shelf_id
     * @param $goods_id
     * @param int $times
     * @param int $quantity
     * @param string $dateline
     */
    public static function addReportShelfNow($channel_id, $account_id, $shelf_id, $goods_id, $times = 0, $quantity = 0, $dateline = '')
    {
        if (!$channel_id) {
            return ['result' => false, 'message' => '缺少必要参数channel_id'];
        }
        if (!$account_id) {
            return ['result' => false, 'message' => '缺少必要参数account_id'];
        }
        if (!$shelf_id) {
            return ['result' => false, 'message' => '缺少必要参数shelf_id'];
        }
        if (!$goods_id) {
            return ['result' => false, 'message' => '缺少必要参数goods_id'];
        }
        if (!$channel_id) {
            return ['result' => false, 'message' => '缺少必要参数channel_id'];
        }
        if (!$dateline) {
            $dateline = time();
        }
        $dateline = strtotime(date('Y-m-d', $dateline));


        $add = [
            'dateline' => $dateline,
            'channel_id' => $channel_id,
            'account_id' => $account_id,
            'shelf_id' => $shelf_id,
            'goods_id' => $goods_id,
            'times' => $times,
            'quantity' => $quantity,
        ];

        Db::startTrans();
        try {

            $rlt = (new ReportStatisticPublishByShelf())->add($add);

            $rlt = (new ReportStatisticPublishByChannel())->add($add); //回写暂时屏蔽
            $rlt = (new ReportStatisticPublishByAccount())->add($add);  //回写暂时屏蔽

            Db::commit();

            return $rlt;

        } catch (\Exception $e) {
            Db::rollback();
            throw new JsonErrorException($e->getMessage() . $e->getFile() . $e->getLine(), 500);
        }






    }



    public function getTimesByGoodsId($goodsId)
    {
        $field = 'channel_id,sum(times) as num';
        $list = $this->reportStatisticByMessageModel->field($field)->where('goods_id',$goodsId)->group('channel_id')->select();
        return $list;
    }

    public function getAccountByGoodsIdChannelId($goodsId,$channelId)
    {
        $where['goods_id'] = $goodsId;
        $where['channel_id'] = $channelId;
        $field = 'account_id,sum(times) as num';
        $list = $this->reportStatisticByMessageModel->field($field)->where($where)->group('account_id')->select();
        return $list;
    }

    /**
     * SPU刊登统计
     * @param $params
     * @return array
     */
    public function spuStatistic($params,$export=0)
    {
        try {
            $wh = [];
            $having = '';
            $res = is_numeric($params['min_num']);
            if (!empty($params['date_type']) && (!empty($params['start_date']) || !empty($params['end_date']))) {
                $startTime = empty($params['start_date']) ? 0 : strtotime($params['start_date'].' 00:00:00');
                $endTime = empty($params['end_date']) ? time() : strtotime($params['end_date'].' 23:59:59');
                if ($params['date_type'] == 'shelf') {//上架时间
                    $wh['g.publish_time'] = ['between',[$startTime,$endTime]];
                } else {//刊登时间
                    $wh['r.dateline'] = ['between',[$startTime,$endTime]];
                }
            }
            if ($params['channel_num'] !='' && (is_numeric($params['min_num']) || is_numeric($params['max_num']))) {//平台刊登数量
                $channelCnt = $params['channel_num'] === 0 ? 'total_cnt' : $params['channel_num'].'_cnt';
                $minNum = $params['min_num'];
                $maxNum = $params['max_num'];
                if (!is_numeric($minNum)) {
                    $having = $channelCnt.'<='.$maxNum;
                } else if (!is_numeric($maxNum)) {
                    $having = $channelCnt.'>='.$minNum;
                } else {
                    $having = $channelCnt.'>='.$minNum.' and '.$channelCnt.'<='.$maxNum;
                }
            }
            if (!empty($params['channel_id'])) {//产品开发平台
                $wh['g.channel_id'] = $params['channel_id'];
            }
            if (!empty($params['spu'])) {//本地SPU
                $spus = json_decode($params['spu'],true);
                if (!empty($spus)) {
                    $goodsIds = Goods::whereIn('spu',$spus)->column('id');
                    $wh['r.goods_id'] = ['in',$goodsIds];
                }
            }
            if (!empty($params['local_category'])) {//本地分类
                $localCategories = json_decode($params['local_category'],true);
                if (is_null($localCategories)) {//不是json格式，按字符串处理
                    $localCategories = [$params['local_category']];
                }
                $categoryIds = [];
                foreach ($localCategories as $localCategory) {
                    $psCategories = explode('>',$localCategory);
                    if (count($psCategories)==1) {//不是分类链的形式
                        $tmpCategoryIds = Category::where('name','like',trim($localCategory).'%')->column('id');
                    } else if (count($psCategories) == 2) {//分类链的形式
                        $parentCategoryName = trim($psCategories[0]);
                        $childCategoryName = trim($psCategories[1]);
                        $parentCategoryId = Category::where('title',$parentCategoryName)->value('id');
                        $tmpCategoryIds = Category::where(['pid'=>$parentCategoryId,'title'=>['like',$childCategoryName.'%']])->column('id');
                    }
                    $categoryIds = array_merge($categoryIds,$tmpCategoryIds);
                }
//                if (!empty($categoryIds)) {
                    $childredCategories = [];
                    foreach ($categoryIds as $categoryId) {
                        $subCategories = (new Category())->getAllChilds($categoryId);
                        $childredCategories = array_merge($childredCategories,$subCategories);
                    }
                    $wh['g.category_id'] = ['in',array_unique($childredCategories)];
//                }
            }
            if (!empty($params['developer_id'])) {//开发者id
                $wh['g.developer_id'] = $params['developer_id'];
            }
            $page = empty($params['page']) ? 1 : $params['page'];
            $pageSize = empty($params['pageSize']) ? 50 : $params['pageSize'];
            //排序
            $order = '';
            if (!empty($params['order_shelf_time'])) {
                $order .= 'g.publish_time '.$params['order_shelf_time'].',';
            }
            if (!empty($params['order_total_cnt'])) {
                $order .= 'total_cnt '.$params['order_total_cnt'].',';
            }
            !empty($order) && $order = substr($order,0,'-1');

            $field = 'r.goods_id,g.spu,g.publish_time,g.category_id,g.developer_id,g.sales_status,g.channel_id,
                sum(case r.channel_id when 1 then r.times else 0 end) ebay_cnt,
                sum(case r.channel_id when 2 then r.times else 0 end) amazon_cnt,
                sum(case r.channel_id when 3 then r.times else 0 end) wish_cnt,
                sum(case r.channel_id when 4 then r.times else 0 end) aliexpress_cnt,
                sum(case r.channel_id when 7 then r.times else 0 end) joom_cnt,
                sum(case r.channel_id when 8 then r.times else 0 end) mymall_cnt,
                sum(case r.channel_id when 9 then r.times else 0 end) shopee_cnt,
                (sum(case r.channel_id when 1 then r.times else 0 end)+
                sum(case r.channel_id when 2 then r.times else 0 end)+
                sum(case r.channel_id when 3 then r.times else 0 end)+
                sum(case r.channel_id when 4 then r.times else 0 end)+
                sum(case r.channel_id when 7 then r.times else 0 end)+
                sum(case r.channel_id when 8 then r.times else 0 end)+
                sum(case r.channel_id when 9 then r.times else 0 end)) total_cnt';
            if ($export == 0) {//正常搜索展示
                $lists = ReportStatisticPublishByShelf::alias('r')->field($field)->
                join('goods g', 'r.goods_id=g.id', 'LEFT')->where($wh)->order($order)->
                group('goods_id')->having($having)->page($page, $pageSize)->select();

                $count = ReportStatisticPublishByShelf::alias('r')->field($field)->
                join('goods g', 'r.goods_id=g.id', 'LEFT')->where($wh)->group('goods_id')->
                having($having)->count();
            } elseif ($export == 1) {//导出获取总数
                $count = $this->doCount($wh,'',$having,'spu');
                return $count;
            } elseif ($export == 2) {//导出获取数据
                $lists = ReportStatisticPublishByShelf::alias('r')->field($field)->
                join('goods g', 'r.goods_id=g.id', 'LEFT')->where($wh)->order($order)->
                group('goods_id')->having($having)->page($page, $pageSize)->select();
            }
            if (!$lists) {
                return ['result'=>true,'data'=>[]];
            } else {
                $lists = collection($lists)->toArray();
            }

            //处理数据
            $developerIds = array_column($lists,'developer_id');
            $developers = User::whereIn('id',$developerIds)->column('realname','id');

            $goodsIds = array_column($lists,'goods_id');
            $platformStatus = Goods::whereIn('id',$goodsIds)->column('platform','id');

            $goodsPlatformStatus = [];
            $platform = [
                1 => 'ebay',
                2 => 'amazon',
                3 => 'wish',
                4 => 'aliexpress',
                7 => 'joom',
                8 => 'mymall',
                9 => 'shopee'
            ];
            foreach ($platformStatus as $goodsId => $ps) {
                foreach ($platform as $channelId => $p) {
                    $value = (new GoodsHelp())->getPlatformValueByChannelId($channelId);
                    $goodsPlatformStatus[$goodsId][$channelId] = (($ps&$value) == $value);
                }
            }
            foreach ($lists as &$list) {
                $list['publish_time'] = empty($list['publish_time']) ? '' : date('Y-m-d H:i:s',$list['publish_time']);
                $list['category_chain'] = (new GoodsHelp())->mapCategory($list['category_id']);
                $list['developer_name'] = isset($developers[$list['developer_id']]) ? $developers[$list['developer_id']] : '';
                $salesStatus = (new GoodsHelp())->sales_status;
                $list['sales_status_txt'] = $salesStatus[$list['sales_status']]??'未知';
                foreach ($platform as $channelId => $p) {
                    if (empty($goodsPlatformStatus[$list['goods_id']][$channelId]) && $list[$p.'_cnt']==0) {//该平台禁售且数量为0
                        $list['total_cnt'] -= $list[$p.'_cnt'];
                        $list[$p.'_cnt'] = '--';
                    }
                }
                $list['channel_txt'] = $platform[$list['channel_id']]??'';
                foreach ($platform as $k => $pl) {
                    $list['statistics'][] = [
                        'channel' => $k,
                        'name' => $pl.'平台',
                        'num' => $list[$pl.'_cnt']
                    ];
                }

            }
            return ['result'=>true,'data'=>['lists'=>$lists,'count'=>$count??0,'page'=>$page,'pageSize'=>$pageSize]];
        } catch (Exception $e) {
            return ['result'=>false, 'message'=>$e->getFile().'|'.$e->getLine().'|'.$e->getMessage()];
        }
    }


    /**
     * @param $params
     * @return array
     */
    public function getAccountDetail($params)
    {
        $channelAccountTable = [
            1 => EbayAccount::class,
            2 => AmazonAccount::class,
            3 => WishAccount::class,
            4 => AliexpressAccount::class,
            7 => JoomAccount::class,
            8 => PandaoAccount::class,
            9 => ShopeeAccount::class
        ];
        try {
            if (!empty($params['date_type']) && (!empty($params['start_date']) || !empty($params['end_date']))) {
                $startTime = empty($params['start_date']) ? 0 : strtotime($params['start_date'].' 00:00:00');
                $endTime = empty($params['end_date']) ? time() : strtotime($params['end_date'].' 23:59:59');
                if ($params['date_type'] == 'shelf') {//上架时间
                    $wh['g.publish_time'] = ['between',[$startTime,$endTime]];
                } else {//刊登时间
                    $wh['r.dateline'] = ['between',[$startTime,$endTime]];
                }
            }
            $wh['r.goods_id'] = $params['goods_id'];
            $wh['r.channel_id'] = $params['channel_id'];
            $accountCounts = ReportStatisticPublishByShelf::alias('r')->join('goods g', 'r.goods_id=g.id', 'LEFT')
                ->where($wh)->group('account_id')->column('sum(times)','account_id');
            $accountIds = array_keys($accountCounts);
            $accountCodes = $channelAccountTable[$params['channel_id']]::whereIn('id',$accountIds)->column('code','id');
            $data = [];
            foreach ($accountCounts as $k => $accountCount) {
                $data[$accountCodes[$k]??($k.'-'.rand(0,999))] = $accountCount;
            }
            return ['result'=>true,'data'=>$data];
        } catch (\Exception $e) {
            return ['result'=>false, 'message'=>$e->getFile().'|'.$e->getLine().'|'.$e->getMessage()];
        }
    }


    /**
     * @param $id 商品id
     * @return \think\response\Json
     * 根据商品id获取刊登统计
     */
    public function statistics($id)
    {
        if (empty($id)) {
            return json_error('产品ID不能为空');
        }

        try {

            //1.eBay平台,2.亚马逊平台,3.Wish平台,4.速卖通平台,5.CD平台,6.Lazada平台,7.Joom平台,8.MyMall平台,9.Shopee
           $channel_config = [1,2,3,4,5,6,7,8,9];

            $channelModel = new Channel();
            $channelList = $channelModel->field('id, id as channel, name, status, is_site, config, title')->whereIn('id', $channel_config)->select();

            //2.根据配置查询
            $where['goods_id'] = ['=', $id];
            $data = ReportStatisticPublishByShelf::where($where)->alias('a')->field('SUM(a.times) as publish_count,a.channel_id ')->group('a.channel_id')->select();

            if($data && $channelList){
                foreach ($data as $val) {

                    foreach ($channelList as $key => $channelVal) {

                        if($val['channel_id'] == $channelVal['id']){
                            $channelList[$key]['publish_count'] = $val['publish_count'];
                        }
                    }
                }
            }

            if($channelList){
                foreach ($channelList as $key => $val) {
                    if(!isset($val['publish_count'])){
                        $channelList[$key]['publish_count'] = 0;
                    }
                }
            }

            return $channelList;
        } catch (Exception $ex) {
            throw new JsonErrorException($ex->getMessage());
        }
    }
}