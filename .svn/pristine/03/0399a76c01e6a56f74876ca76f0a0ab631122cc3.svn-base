<?php
/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2017/6/26
 * Time: 15:20
 */

namespace app\publish\service;


use app\common\cache\Cache;
use app\common\exception\JsonErrorException;
use erp\AbsServer;
use think\Db;
use think\Exception;
use app\goods\service\GoodsHelp;
use app\common\model\Goods as goodsModel;
use app\index\service\ChannelDistribution;
use app\common\service\UniqueQueuer;
use app\publish\queue\AliexpressGoodsDistributeQueue;
use app\common\model\ChannelUserAccountMap;
use app\common\model\aliexpress\AliexpressPublishTask;
use app\common\model\Goods;
use app\common\model\DepartmentLog as DepartmentLogModel;
use app\common\model\GoodsLang;
use app\index\service\DownloadFileService;


class AliexpressPublishTaskHelper extends AbsServer
{

    public function __construct()
    {
        parent::__construct();
    }


    /**
     *每日刊登自动分配
     *
     */
    public function publishDistribute()
    {
        try{

            $day = date('2019-3-7');
            //每天晚点定时跑的时候,将未刊登成功的都设置为自动延期.
           /* $day = date('Y-m-d', strtotime('-1 day'));
            $star_time = strtotime($day);

            $end_time = strtotime(date('Y-m-d 23:59:59', strtotime('-1 day')));

            $publishTaskModel = new AliexpressPublishTask();
            $publishTaskList = $publishTaskModel->field('id')->whereIn('status', [0,2])->whereBetween('create_time',[$star_time, $end_time])->select();


            //设置昨天未刊登成功的设置为延期
            if($publishTaskList) {
                $taskIds = array_column($publishTaskList, 'id');

                $publishTaskModel->update(['status' => 3], ['id' => ['in', $taskIds]]);
            }*/

            //1.goods表根据开发时间查询当天开发的产品总数;
            $goodsHelpServer = new GoodsHelp;
            $goodsList = $goodsHelpServer->getAssignGoods($day, 4);

            if(is_array($goodsList) && count($goodsList)) {

                //2.根据分类category_id获取分类的产品总数
                $goodsIds = array_keys($goodsList);
                $goodsModel = new goodsModel();
                $goodsCategories = $goodsModel->field('g.id,g.spu as goods_spu, c.pid')->alias('g')->join('category c','g.category_id = c.id','left')->whereIn('g.id', $goodsIds)->select();
                if(empty($goodsCategories)) {
                    return;
                }

                $goodsCategories = json_decode(json_encode($goodsCategories),true);

                $goodsCategoryList = [];
                foreach ($goodsCategories as $key => $val) {
                    $goodsCategoryList[$val['pid']][] = [
                        'id' => $val['id'],
                        'goods_spu' => $val['goods_spu'],
                    ];
                }


                //获取分类id
                $goodsCategoryIds = array_keys($goodsCategoryList);


                //4.速卖通账号与产品分类的关联关系
                $channelDistr = new ChannelDistribution();
                $distributes = $channelDistr->read(4);

                //刊登覆盖率
                $coverage_rate_min =  $distributes['coverage_rate_min'];
                if(!isset($distributes['category_account']) || empty($distributes['category_account'])) {
                    return;
                }


                $categoryAccount = $distributes['category_account'];
                //5.过滤产品中未有的分类
                $category_account = [];
                foreach ($categoryAccount as $key => $val) {

                    foreach ($val['account_id'] as $accountK => $accountV) {

                        if(in_array($val['category_id'], $goodsCategoryIds)) {
                            $category_account[] = [
                                'account_id' => $accountV,
                                'category_id' => $val['category_id']
                            ];
                        }
                    }
                }

                //根据账号查询账号关联销售人员信息
                $newAccountIds = array_count_values(array_column($category_account,'account_id'));
                $accountMapModel = new ChannelUserAccountMap();
                //销售员总数
                $accountMapsTotal = $accountMapModel->field('*')->whereIn('account_id', array_keys($newAccountIds))->where('channel_id','=',4)->where('seller_id','>',0)->count();
                //产品总数
                $goodsTotal = count($goodsList)*$coverage_rate_min;


                $many_task_goods = [];
                $task_goods  = [];
                //优先分配一个分类的账号
                foreach ($category_account as $key => $val) {
                    if(array_key_exists($val['account_id'], $newAccountIds)) {
                        if($newAccountIds[$val['account_id']] == 1) {
                            $task_goods[$val['category_id']][] = $val;
                        }else{
                            $many_task_goods[] = $val;
                        }
                    }
                }


                //每个spu分配6个账号.如果该spu分配了账号,则减少分配
                $dist_new_task_goods = [];
                $new_task_goods = [];
                foreach ($task_goods as $key => $val) {
                    if(array_key_exists($key, $goodsCategoryList)) {
                        //将每个spu,分配对应分类的账号
                        foreach ($goodsCategoryList[$key] as $goodsK => $goodsV) {
                            foreach ($val as $newK => $newV) {

                                if(floor($newK/$coverage_rate_min) == $goodsK) {

                                    $newV['goods_id'] = $goodsV['id'];
                                    $newV['goods_spu'] = $goodsV['goods_spu'];

                                    $new_task_goods[] =$newV;
                                }
                            }
                        }
                    }
                }
            }


            //产品总数大于销售员人数
            if($goodsTotal > $accountMapsTotal) {

                //计算每个销售员的平均任务数
                $average_number = round($goodsTotal/$accountMapsTotal);

                //平均任务数大于1
                if($average_number > 1) {

                    //如果平均任务数大于1,则优先再次分配一个分类的账号达到平均任务数
                    if($new_task_goods) {

                        foreach ($new_task_goods as $key => $val) {
                            foreach ($goodsCategoryList as $k => $v) {

                                foreach ($v as $goodsK => $goodsV) {

                                    if($val['goods_id'] != $goodsV['id']) {
                                        $goods = [
                                            'account_id' => $val['account_id'],
                                            'category_id' => $val['category_id'],
                                            'goods_id' => $goodsV['id'],
                                            'goods_spu' => $goodsV['goods_spu']
                                        ];
                                        array_push($new_task_goods, $goods);


                                        //如果自动分配达到均值,则跳出
                                        if(count($new_task_goods) == $average_number) {
                                            break;
                                        }
                                    }

                                }
                            }
                        }


                        //统计已经分配的spu个数.如果spu个数已经达到6个,则不在分配.未达到6个的。则继续分配.
                        $goodsIds = array_count_values(array_column($new_task_goods,'goods_id'));

                        foreach ($goodsIds as $key => $val) {
                            if($val > $coverage_rate_min) {
                                unset($goodsIds[$key]);
                            }
                        }

                        $new_many_task_goods = [];
                        $i = 0;
                        foreach ($goodsCategories as $key => $val) {

                            foreach ($many_task_goods as $k => $v) {
                                if(array_key_exists($val['id'], $goodsIds)) {

                                    $many_task_goods[$k]['goods_id'] = $val['id'];
                                    $many_task_goods[$k]['goods_spu'] = $val['goods_spu'];
                                }
                            }


                            $rate_min = $coverage_rate_min - $goodsIds[$val['id']];
                            $new_many_task_goods[$val['id']] = array_slice($many_task_goods,$i, $rate_min);
                            $i = $i+$rate_min;
                        }
                $many_task_goods = [];
                foreach ($new_many_task_goods as $key => $val) {
                    foreach ($val as $k => $v) {
                        $many_task_goods[] = $v;
                    }
                }

                $task_goods = array_merge($many_task_goods,$new_task_goods);

                //写入每日刊登队列
                $queueObj = new UniqueQueuer(AliexpressGoodsDistributeQueue::class);

                foreach ($task_goods as $val) {
                    $queueObj->push($val);
                }

            }
         }
        }

        }catch(JsonErrorException $exception){
            throw new JsonErrorException($exception->getMessage());
        }
    }



    /**
     *速卖通每日刊登
     *
     */
    public function everydayPublish($params)
    {

        try{
            $page = isset($params['page']) && $params['page'] ? $params['page'] : 1;
            $pageSize = isset($params['pageSize']) && $params['pageSize'] ? $params['pageSize'] : 50;

            $where = [];

            //账号简称
            if(isset($params['account_id']) && $params['account_id']) {
                $where['t.account_id'] = $params['account_id'];
            }

            //任务状态
            //'':全部;0:未开始;1:进行中;2:已完成;3:已延期;
            if(isset($params['status']) && is_numeric($params['status'])) {
                $where['t.status'] = $params['status'];
            }

            //任务计划开始时间-截止时间
            if(isset($params['star_time']) && isset($params['end_time'])) {

                if($params['star_time'] && $params['end_time']) {

                    $star_time = strtotime($params['star_time']);
                    $end_time = strtotime($params['end_time']);

                    $where['t.pre_publish_time'] = ['between', [$star_time, $end_time]];

                }elseif($params['star_time'] && empty($params['end_time'])) {

                    $star_time = strtotime($params['star_time']);

                    $where['t.pre_publish_time'] = ['>', $star_time];
                }elseif(empty($params['star_time']) && $params['end_time']) {

                    $end_time = strtotime($params['end_time']);
                    $where['t.pre_publish_time'] = ['<', $end_time];
                }
            }

            //销售员id
            if(isset($params['sales_id'])) {
                $where['t.sales_id'] = $params['sales_id'];
            }

            //$where['p.product_id'] = ['=',0];

            $publishTaskModel = new AliexpressPublishTask();

            $data = $publishTaskModel->alias('t')->field('g.thumb,t.id, a.code, t.spu as goods_spu, g. name, g.packing_en_name,  u.realname, g.create_time, t.status, t.sales_id as operator_id, t.goods_id, g.category_id, p.status as publish_status, t.pre_publish_time, t.ali_product_id, t.account_id')
                ->join('aliexpress_account a','t.account_id = a.id','left')
                ->join('user u','u.id = t.sales_id','left')
                ->join('goods g','t.goods_id = g.id','left')
                ->join('aliexpress_product p','t.ali_product_id = p.id','left')
                ->where($where)
                ->order('t.id desc')
                ->page($page, $pageSize)
                ->select();

            if($data) {

                $goodsModel = new Goods();
                $depLogModel = new DepartmentLogModel();
                foreach ($data as $key => $val) {

                    $data[$key]['ali_product_id'] = (string)$val['ali_product_id'];

                    //英文标题
                    $lang = GoodsLang::where(['goods_id' => $val['goods_id'], 'lang_id' => 2])->field('title')->find();
                    if ($lang) {
                        $data[$key]['packing_en_name'] = $lang['title'];
                    }

                    //分类
                    $category = $goodsModel->getCategoryAttr("", $val);
                    $data[$key]['category']  = $category ? $category : '';

                    //产品标签
                    $depName = $depLogModel->getDepartmentNameAttr('',$val);
                    $data[$key]['dep_name'] = $depName ? $depName : '';

                    //publish_status:0未开始,5,待上传,3.上传中,4.上传失败,2.上传成功
                    $data[$key]['publish_status'] = empty($val['publish_status']) ? ($val['publish_status'] == null ? 5 : 0) : $val['publish_status'];
                }
            }

            $count = $publishTaskModel->alias('t')->where($where)->count();

            $base_url = Cache::store('configParams')->getConfig('innerPicUrl')['value'] . DS;

            return ['base_url' => $base_url,'data' => $data, 'count' => $count, 'page' => $page, 'totalpage' => $pageSize];
            }catch(JsonErrorException $exception){
                throw new JsonErrorException($exception->getMessage());
            }
    }



    /**
     * @param $ids
     * @return array
     * @throws Exception
     * 每日刊登导出
     */
    public function everyDayPublishExport($ids)
    {
        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }
        $ids = array_filter(array_unique($ids));

        $where = [];
        //$where['p.product_id'] = ['=',0];

        $publishTaskModel = new AliexpressPublishTask();

        $data = $publishTaskModel->alias('t')->field('a.code, t.spu as goods_spu, g. name, g.packing_en_name,  u.realname, g.create_time, t.status, t.sales_id as operator_id, t.goods_id, g.category_id, p.status as publish_status, t.pre_publish_time')
            ->join('aliexpress_account a','t.account_id = a.id','left')
            ->join('user u','u.id = t.sales_id','left')
            ->join('goods g','t.goods_id = g.id','left')
            ->join('aliexpress_product p','t.ali_product_id = p.id','left')
            ->whereIn('t.id', $ids)
            ->where($where)
            ->select();

        if (empty($data)) {
            throw new Exception('导出参数对应的记录为空');
        }

        if($data) {

            $goodsModel = new Goods();
            $depLogModel = new DepartmentLogModel();

            //任务状态
            $status = ['未开始','进行中','已完成','已延期'];
            //刊登状态
            $publishStatus = ['待上传','定时刊登','上传成功','上传中','上传失败'];
            foreach ($data as $key => $val) {

                //英文标题
                $lang = GoodsLang::where(['goods_id' => $val['goods_id'], 'lang_id' => 2])->field('title')->find();
                if ($lang) {
                    $data[$key]['packing_en_name'] = $lang['title'];
                }

                //分类
                $category = $goodsModel->getCategoryAttr("", $val);
                $data[$key]['category']  = $category ? $category : '';

                //产品标签
                $depName = $depLogModel->getDepartmentNameAttr('',$val);
                $data[$key]['dep_name'] = $depName ? $depName : '';

                $publish_status = empty($val['publish_status']) ? 0 : $val['publish_status'];
                $data[$key]['publish_status'] = $publishStatus[$publish_status];

                $data[$key]['status'] = $status[$val['status']];

                $data[$key]['create_time'] = $val['create_time'] ? date('Y-m-d H:i:s',$val['create_time']) : '';
                $data[$key]['pre_publish_time'] = $val['pre_publish_time'] ? date('Y-m-d H:i:s', $val['pre_publish_time']) : '';
            }
        }

        try {
            $header = [
                ['title' => '账号简称', 'key' => 'code', 'width' => 10],
                ['title' => '本地SPU', 'key' => 'goods_spu', 'width' => 10],
                ['title' => '产品中文名称', 'key' => 'name', 'width' => 70],
                ['title' => '产品英文名称', 'key' => 'packing_en_name', 'width' => 70],
                ['title' => '本地分类', 'key' => 'category', 'width' => 70],
                ['title' => '创建时间', 'key' => 'create_time', 'width' => 20],
                ['title' => '销售员', 'key' => 'realname', 'width' => 20],
                ['title' => '刊登状态', 'key' => 'publish_status', 'width' => 5],
                ['title' => '产品标签', 'key' => 'dep_name', 'width' => 20],
                ['title' => '任务时间', 'key' => 'pre_publish_time', 'width' => 10],
                ['title' => '任务状态', 'key' => 'status', 'width' => 5],
            ];

            $file = [
                'name' => '速卖通每日刊登导出',
                'path' => 'aliexpress'
            ];
            $ExcelExport = new DownloadFileService();
            $result = $ExcelExport->export($data, $header, $file);
            return $result;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

}