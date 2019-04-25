<?php
namespace app\report\service;

use app\common\cache\Cache;
use app\common\model\Order;
use app\goods\service\GoodsSkuAlias;
use app\goods\service\CategoryHelp;
use app\common\model\report\ReportStatisticBySkuOrder;
use think\Db;
use think\Exception;

/**
 * Created by PhpStorm.
 * User: hecheng
 * Date: 2018/9/19
 * Time: 17:31
 */

class FirstOrderSkuListService
{
    protected $skuListService;

    public function __construct()
    {
        if (is_null($this->skuListService)) {
            $this->skuListService = new ReportStatisticBySkuOrder();
        }
    }

    /**
     * 列表数据
     * @param $params
     * @param $page
     * @param $pageSize
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index($params, $page, $pageSize)
    {
        $goodsSkuAliasService = new GoodsSkuAlias();
        $categoryHelpService = new CategoryHelp();
        $where = $this->where($params);
        $field = 'channel_id,sku,sku_id,goods_id,category_id,developer,shelf_time,order_time,issue_time';
        $count = $this->skuListService->field($field)->where($where)->count();
        $firstOrderList = $this->skuListService->field($field)->where($where)->page($page,$pageSize)->order('issue_time asc')->select();
        $data = [];
        foreach ($firstOrderList as $k => $v) {
            //获取SKU别名
            $alias = $goodsSkuAliasService->getAliasBySkuId($v['sku_id']);
            //获取商品名称
            $goodsInfo = Cache::store('goods')->getGoodsInfo($v['goods_id']);
            //获取商品分类
            $category = $categoryHelpService->getCategoryNameById($v['category_id']);
            //获取开发员
            $user = Cache::store('user')->getOneUser($v['developer'] ?? '') ?? '';
            $temp = [];
            $temp['channel_id'] = Cache::store('channel')->getChannelName($v['channel_id']) ?? '';
            $temp['sku'] = $v['sku'];
            $temp['sku_alias'] = $alias[0] ?? '';
            $temp['thumb'] = $goodsInfo['thumb'] ?? '';
            $temp['sku_id'] = $v['sku_id'] ?? '';
            $temp['goods_name'] = $goodsInfo['name'] ?? '';
            $temp['category'] = $category ?? '';
            $temp['developer'] = $user['realname'] ?? '';
            $temp['shelf_time'] = date('Y-m-d',$v['shelf_time']);
            $temp['order_time'] = date('Y-m-d',$v['order_time']);
            $temp['issue_time'] = $v['issue_time'];
            array_push($data,$temp);
        }
        $result = [
            'data' => $data,
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
        ];
        return $result;
    }

    /**
     * 查询条件
     * @param $params
     * @return array|\think\response\Json
     * @throws \Exception
     */
    public function where($params)
    {
        $where = [];
        //平台
        if (isset($params['channel_id']) && $params['channel_id'] != '') {
            $where['channel_id'] = ['eq',$params['channel_id']];
        }
        //开发员
        if (isset($params['developer']) && $params['developer'] != ''){
            $where['developer'] = ['eq', $params['developer']];
        }
        //sku
        if (isset($params['sku']) && is_json($params['sku'])) {
            $snText = json_decode($params['sku'], true);
            $skuData = (new \app\goods\service\GoodsSku())->getASkuIdByASkuOrAlias($snText);
            $sku_ids = array_values($skuData);
            if (!empty($sku_ids)) {
                $where['sku_id'] = ['in', $sku_ids];
            } else {
                search($params['sku'], $where, 'sku');
            }
        }

        //日期筛选
        if (isset($params['snDate'])) {
            $params['date_b'] = isset($params['date_b']) ? $params['date_b'] : 0;
            $params['date_e'] = isset($params['date_e']) ? $params['date_e'] : 0;
            switch ($params['snDate']) {
                case 'shelf_time':
                    $condition = timeCondition($params['date_b'], $params['date_e']);
                    if (!is_array($condition)) {
                        return json(['message' => '日期格式错误'], 400);
                    }
                    if (!empty($condition)) {
                        $where['shelf_time'] = $condition;
                    }
                    break;
                case 'order_time':
                    $condition = timeCondition($params['date_b'], $params['date_e']);
                    if (!is_array($condition)) {
                        return json(['message' => '日期格式错误'], 400);
                    }
                    if (!empty($condition)) {
                        $where['order_time'] = $condition;
                    }
                    break;
                default:
                    break;
            }
        }
        return $where;
    }

    /**
     * 首次出单SKU列表数据回写方法
     * @throws Exception
     */
    public function searchDataInsert()
    {
        try {
            //实例化模型
            $skuListModel = new ReportStatisticBySkuOrder();
            $join[] = ['order o','o.id = d.order_id','left'];
            $field = 'min(d.create_time) as create_time,d.sku,d.sku_id,d.goods_id,o.id,o.channel_id';
            $start = strtotime(date("Y-m-d"),time());
            $end = strtotime(date("Y-m-d",strtotime("+1 day"))) - 1;
            $where['d.create_time'] = ['between', [$start, $end]];
            $detailList = Db::table('order_detail')->alias('d')->field($field)->where($where)->join($join)->group('sku_id')->select();
            //今日出单sku
            $orderSku = array_column($detailList,'sku_id');
            //已记录sku
            $num = ceil(count($orderSku) / 10000);
            $skuOrder = [];
            for ($i = 0; $i < $num; $i++) {
                $skuList = array_slice($orderSku, $i*10000, 10000);
                $hasSkuOrder = Db::table('report_statistic_by_sku_order')->where('sku_id','in',$skuList)->column('sku_id');
                $skuOrder = array_merge_recursive($skuOrder,$hasSkuOrder);
            }
            //获取差集
            $arr = array_diff($orderSku,$skuOrder);
            $orderArr = [];
            foreach ($detailList as $k => $detail){
                $orderArr[$detail['sku_id']] = $detail;
            }
            unset($orderSku);
            unset($skuOrder);
            $dataDetail = [];
            //循环差集
            if (!empty($arr)) {
                foreach ($arr as $k => $v) {
                    //获取商品信息
                    $goodsInfo = Cache::store('goods')->getGoodsInfo($orderArr[$v]['goods_id']);
                    if (!empty($goodsInfo['publish_time'])) {
                        $date = $this->getTime($goodsInfo['publish_time'], $orderArr[$v]['create_time']);
                        if (!empty($goodsInfo)) {
                            $dataDetail[] = array(
                                'shelf_time' => $goodsInfo['publish_time'],
                                'sku_id' => $orderArr[$v]['sku_id'],
                                'channel_id' => $orderArr[$v]['channel_id'],
                                'sku' => $orderArr[$v]['sku'],
                                'goods_id' => $orderArr[$v]['goods_id'],
                                'category_id' => $goodsInfo['category_id'],
                                'developer' => $goodsInfo['developer_id'],
                                'order_time' => $orderArr[$v]['create_time'],
                                'issue_time' => $date
                            );
                            //数量达到5000条插入一次
                            if (count($dataDetail) == 5000) {
                                $skuListModel->insertAll($dataDetail);
                                $dataDetail = [];
                            }
                        }
                    } else {
                        if (isset($goodsInfo['name']) && !empty($goodsInfo['name'])) {
                            $name = $goodsInfo['name'];
                        } else if (isset($goodsInfo['declare_name']) && !empty($goodsInfo['declare_name'])) {
                            $name = $goodsInfo['declare_name'];
                        } else {
                            continue;
                        }
                        $message = '已生成订单商品【' . $name . '】未上架，订单编号：' .$orderArr[$v]['id'];
                        $fileName = date('Y-m-d', time());
                        $logFile = LOG_PATH . "order/" . $fileName . "FirstOrderSku_failure.log";
                        file_put_contents($logFile, '-----' . $message . "\r\n", FILE_APPEND);
                    }
                }
            }
            unset($arr);
            if (!empty($dataDetail)) {
                $skuListModel->insertAll($dataDetail);
            }
            return true;
        } catch (Exception $e) {
            throw new Exception('查询首次出单SKU信息出错：' . $e->getMessage() . $e->getFile() . $e->getLine());
        }
    }

    /**
     * 获取
     * @param $start
     * @param $end
     * @return float|string
     */
    public function getTime($start,$end)
    {
        $past_time = $end - $start;
        $days = ceil($past_time / 86400);
        if ($days >= 1) {
            return $days;
        }else if($start > $end) {
            return 0;
        } else {
            return 1;
        }
    }
}