<?php

namespace app\report\service;

use app\common\model\Carrier;
use app\common\model\Channel;
use app\common\model\Order;
use app\common\model\OrderAddress;
use app\common\model\OrderPackageDeclare;
use app\common\service\ChannelAccountConst;
use app\order\service\AuditOrderService;
use app\warehouse\service\PackageCollection;
use think\Db;
use think\Exception;
use think\Loader;
use app\common\model\ShippingMethod;
use app\warehouse\service\ShippingMethod as ShippingMethodService;
use app\common\model\OrderPackage;
use app\common\cache\Cache;
use app\common\exception\JsonErrorException;
use app\report\model\ReportExportFiles;
use app\common\service\Common;
use app\common\service\CommonQueuer;
use app\report\queue\ExpressConfirmExportQueue;
use app\report\queue\ExpressConfirmExportCollectQueue;
use app\report\validate\FileExportValidate;

use app\common\model\joom\JoomShop;
use app\common\traits\Export;

Loader::import('phpExcel.PHPExcel', VENDOR_PATH);

/**
 * Created by PhpStorm.
 * Date: 2018/7/20
 * Time: 14:37
 */
class ExpressConfirmService
{
    use Export;
    protected $colMaps=[
        'order' => [
            'title' => [
                'A' => ['title' => '序号.', 'width' => 10],
                'B' => ['title' => '物流商', 'width' => 10],
                'C' => ['title' => '邮寄方式', 'width' => 25],
                'D' => ['title' => '结算方式', 'width' => 25],
                'E' => ['title' => '揽收商', 'width' => 10],
                'F' => ['title' => '发货包裹数量', 'width' => 20],
                'G' => ['title' => '运费', 'width' => 15],
                'H' => ['title' => '重量', 'width' => 10],

    ],
            'data' => [
                'id' => ['col' => 'A', 'type' => 'int'],
                'carrierCompany' => ['col' => 'B', 'type' => 'str'],
                'shortname' => ['col' => 'C', 'type' => 'str'],
                'settlement_method' => ['col' => 'D', 'type' => 'str'],
                'collector_name' => ['col' => 'E', 'type' => 'str'],
                'parcels_num' => ['col' => 'F', 'type' => 'str'],
                'shipping_fee' => ['col' => 'G', 'type' => 'time'],
                'package_weight' => ['col' => 'H', 'type' => 'str'],
                ]
            ]
    ];

    /**
     * 标题
     */
    public function titles()
    {
        $title = [
            'id' => [
                'title' => 'id',
                'remark' => '序号',
                'is_show' => 1
            ],
            'carrierCompany' => [
                'title' => 'carrierCompany',
                'remark' => '物流商',
                'is_show' => 1
            ],
            'shortname' => [
                'title' => 'shortname',
                'remark' => '邮寄方式',
                'is_show' => 1
            ],
            'settlement_method' => [
                'title' => 'settlement_method',
                'remark' => '结算方式',
                'is_show' => 1
            ],
            'collector_name' => [
                'title' => 'collector_name',
                'remark' => '揽收商',
                'is_show' => 1
            ],
            'parcels_num' => [
                'title' => 'parcels_num',
                'remark' => '发货包裹数量',
                'is_show' => 1
            ],
            'shipping_fee' => [
                'title' => 'shipping_fee',
                'remark' => '运费',
                'is_show' => 1
            ],
            'package_weight' => [
                'title' => 'package_weight',
                'remark' => '合计重量KG',
                'is_show' => 1
            ],
        ];
        return $title;
    }

    protected $colMap = [
        'order' => [
            'title' => [
                'A' => ['title' => '序号.', 'width' => 10],
                'B' => ['title' => '包裹发货时间', 'width' => 25],
                'C' => ['title' => '执行发货人', 'width' => 20],
                'D' => ['title' => '付款日期', 'width' => 15],
                'E' => ['title' => '平台', 'width' => 10],
                'F' => ['title' => '账号', 'width' => 10],
                'G' => ['title' => '买家姓名', 'width' => 10],
                'H' => ['title' => '买家电话', 'width' => 10],
                'I' => ['title' => '国家代码', 'width' => 10],
                'J' => ['title' => '国家英文名', 'width' => 10],
                'K' => ['title' => '国家中文名', 'width' => 10],
                'L' => ['title' => '省/州', 'width' => 10],
                'M' => ['title' => '城市', 'width' => 10],
                'N' => ['title' => '邮编', 'width' => 10],
                'O' => ['title' => '详细地址', 'width' => 10],
                'P' => ['title' => '邮寄方式', 'width' => 10],
                'Q' => ['title' => '揽收商', 'width' => 10],
                'R' => ['title' => '包裹号', 'width' => 10],
                'S' => ['title' => '跟踪号', 'width' => 10],
                'T' => ['title' => '系统重量(g)', 'width' => 10],
                'U' => ['title' => '系统运费(人民币)', 'width' => 10],
                'V' => ['title' => '物流商重量(g)', 'width' => 10],
                'W' => ['title' => '物流商运费(人民币)', 'width' => 10],
                'X' => ['title' => '货品名称1', 'width' => 10],
                'Y' => ['title' => '中文报关名1', 'width' => 10],
                'Z' => ['title' => '英文报关名1', 'width' => 10],
                'AA' => ['title' => '申报数量1', 'width' => 10],
                'AB' => ['title' => '申报价值1', 'width' => 10],
                'AC' => ['title' => '币种1', 'width' => 10],
                'AD' => ['title' => '结算方式', 'width' => 10],
            ],
            'data' => [
                'id' => ['col' => 'A', 'type' => 'int'],
                'shipping_time' => ['col' => 'B', 'type' => 'time'],
                'sendpeople' => ['col' => 'C', 'type' => 'str'],
                'pay_time' => ['col' => 'D', 'type' => 'time'],
                'channel_name' => ['col' => 'E', 'type' => 'str'],
                'channel_account_id' => ['col' => 'F', 'type' => 'str'],
                'buyer_name' => ['col' => 'G', 'type' => 'str'],
                'buyer_phone' => ['col' => 'H', 'type' => 'str'],
                'country_code' => ['col' => 'I', 'type' => 'str'],
                'country_en' => ['col' => 'J', 'type' => 'str'],
                'country_cn' => ['col' => 'K', 'type' => 'str'],
                'province' => ['col' => 'L', 'type' => 'str'],
                'city_id' => ['col' => 'M', 'type' => 'str'],
                'zipcode' => ['col' => 'N', 'type' => 'str'],
                'address' => ['col' => 'O', 'type' => 'str'],
                'shortname' => ['col' => 'P', 'type' => 'str'],
                'collector_name' => ['col' => 'Q', 'type' => 'str'],
                'number' => ['col' => 'R', 'type' => 'str'],
                'shipping_number' => ['col' => 'S', 'type' => 'str'],
                'package_weight' => ['col' => 'T', 'type' => 'str'],
                'shipping_fee' => ['col' => 'U', 'type' => 'str'],
                'providers_weight' => ['col' => 'V', 'type' => 'str'],
                'providers_fee' => ['col' => 'W', 'type' => 'str'],
                'goods_name' => ['col' => 'X', 'type' => 'str'],
                'goods_name_cn' => ['col' => 'Y', 'type' => 'str'],
                'goods_name_en' => ['col' => 'Z', 'type' => 'str'],
                'quantity' => ['col' => 'AA', 'type' => 'str'],
                'unit_price' => ['col' => 'AB', 'type' => 'str'],
                'declare_currency' => ['col' => 'AC', 'type' => 'str'],
                'settlement_method' => ['col' => 'AD', 'type' => 'str'],
            ]
        ],
    ];

    /**
     * 标题
     */
    public function title()
    {
        $title = [
            'id' => [
                'title' => 'id',
                'remark' => '序号',
                'is_show' => 1
            ],
            'shipping_time' => [
                'title' => 'shipping_time',
                'remark' => '包裹发货时间',
                'is_show' => 1
            ],
            'sendpeople' => [
                'title' => 'sendpeople',
                'remark' => '执行发货人',
                'is_show' => 1
            ],
            'pay_time' => [
                'title' => 'pay_time',
                'remark' => '付款日期',
                'is_show' => 1
            ],
            'channel_name' => [
                'title' => 'channel_name',
                'remark' => '平台',
                'is_show' => 1
            ],
            'channel_account_id' => [
                'title' => 'channel_account_id',
                'remark' => '账号',
                'is_show' => 1
            ],
            'buyer_name' => [
                'title' => 'buyer_name',
                'remark' => '买家姓名',
                'is_show' => 1
            ],
            'buyer_phone' => [
                'title' => 'buyer_phone',
                'remark' => '买家电话',
                'is_show' => 1
            ],
            'country_code' => [
                'title' => 'country_code',
                'remark' => '国家代码',
                'is_show' => 1
            ],
            'country_en' => [
                'title' => 'country_en',
                'remark' => '国家英文名',
                'is_show' => 1
            ],
            'country_cn' => [
                'title' => 'country_cn',
                'remark' => '国家中文名',
                'is_show' => 1
            ],
            'province' => [
                'title' => 'province',
                'remark' => '省/州',
                'is_show' => 1
            ],
            'city_id' => [
                'title' => 'city_id',
                'remark' => '城市',
                'is_show' => 1
            ],
            'zipcode' => [
                'title' => 'zipcode',
                'remark' => '邮编',
                'is_show' => 1
            ],
            'address' => [
                'title' => 'address',
                'remark' => '详细地址',
                'is_show' => 1
            ],
            'shortname' => [
                'title' => 'shortname',
                'remark' => '邮寄方式',
                'is_show' => 1
            ],
            'collector_name' => [
                'title' => 'collector_name',
                'remark' => '揽收商',
                'is_show' => 1
            ],
            'number' => [
                'title' => 'number',
                'remark' => '包裹号',
                'is_show' => 1
            ],
            'shipping_number' => [
                'title' => 'shipping_number',
                'remark' => '跟踪号',
                'is_show' => 1
            ],
            'package_weight' => [
                'title' => 'package_weight',
                'remark' => '系统重量(g)',
                'is_show' => 1
            ],
            'shipping_fee' => [
                'title' => 'shipping_fee',
                'remark' => '系统运费(人民币)',
                'is_show' => 1
            ],
            'providers_weight' => [
                'title' => 'providers_weight',
                'remark' => '物流商重量(g)',
                'is_show' => 1
            ],
            'providers_fee' => [
                'title' => 'providers_fee',
                'remark' => '物流商运费(人民币)',
                'is_show' => 1
            ],
            'goods_name' => [
                'title' => 'goods_name',
                'remark' => '货品名称1',
                'is_show' => 1
            ],
            'goods_name_cn' => [
                'title' => 'goods_name_cn',
                'remark' => '中文报关名1',
                'is_show' => 1
            ],
            'goods_name_en' => [
                'title' => 'goods_name_en',
                'remark' => '英文报关名1',
                'is_show' => 1
            ],
            'quantity' => [
                'title' => 'quantity',
                'remark' => '申报数量1',
                'is_show' => 1
            ],
            'unit_price' => [
                'title' => 'unit_price',
                'remark' => '申报价值1',
                'is_show' => 1
            ],
            'declare_currency' => [
                'title' => 'declare_currency',
                'remark' => '币种1',
                'is_show' => 1
            ],
            'settlement_method' => [
                'title' => 'settlement_method',
                'remark' => '结算方式',
                'is_show' => 1
            ]
        ];
        return $title;
    }

    /**
     * @title 获取快递确认单列表
     * @param $page
     * @param $pageSize
     * @param $params
     * @return array
     * @throws Exception
     */
    public function getExpressForm($page, $pageSize, $params)
    {
        $where = [];
        $this->where($params, $where);
        $field = $this->field();
        $join = $this->join();
        $count = $this->doCount($field, $where, $join);
        $data = $this->assemblyData($this->doSearch($field, $where, $join, $page, $pageSize), $params);
        $arr = $this->packageWeight($where, $join);
        $sum = '';
        if (!empty($data)) {
            $sum = [
                'parcels_num' => $arr['parcelsNum'],
                'package_weight' => $arr['packageWeight'],
                'shipping_fee' => $arr['shippingFee'],
            ];
            $sum = json_encode($sum);
        }
        $result = [
            'data' => $data,
            'count' => $count,
            'page' => $page,
            'pageSize' => $pageSize,
            'sum' => $sum,
        ];
        return $result;
    }

    /**
     * 查询条件
     * @param $param
     * @param $where
     * @return \think\response\Json
     */
    private function where($params, &$where)
    {
        //平台id
        if (isset($params['channel_id']) && !empty($params['channel_id'])) {
            $where['p.channel_id'] = ['eq', $params['channel_id']];
        }

        if (isset($params['account_id']) && !empty($params['account_id'])) {
            $where['p.channel_account_id'] = ['eq', $params['account_id']];
            if ($params['channel_id'] == ChannelAccountConst::channel_Joom) {
                //joom平台特殊操作
                if ($shop_id = param($params, 'shop_id')) {
                    $where['p.channel_account_id'] = ['=', $shop_id];
                } else {
                    $joomShopModel = new JoomShop();
                    $account_ids = $joomShopModel->field('id')->where(['joom_account_id' => $params['account_id']])->select();
                    $account_ids = array_column($account_ids, 'id');
                    $where['p.channel_account_id'] = ['in', $account_ids];
                }
            }
        }

        //仓库id
        if (isset($params['warehouse_id']) && !empty($params['warehouse_id'])) {
            $where['p.warehouse_id'] = ['eq', $params['warehouse_id']];
        }

        //承运公司id
        if ($carrier_id = param($params, 'carrier_id')) {
            if (is_numeric($carrier_id)) {
                $where['s.carrier_id'] = ['eq',$params['carrier_id']];
            }
        }

        //揽收商
        if (isset($params['collector_id']) && !empty($params['collector_id'])) {
            $where['s.collector_id'] = ['eq', $params['collector_id']];
        }

        //目的地国家
        if (isset($params['country_code']) && !empty($params['country_code'])) {
            $where['o.country_code'] = ['eq', $params['country_code']];
        }

        //邮寄方式id
        if (isset($params['shipping_ids']) && !empty($params['shipping_ids'])) {
            $shipping_ids = json_decode($params['shipping_ids'], true);
            $where['s.id'] = ['in', $shipping_ids];
        }

        //查询日期
        $params['date_b'] = isset($params['date_b']) ? trim($params['date_b']) : 0;
        $params['date_e'] = isset($params['date_e']) ? trim($params['date_e']) : 0;
        $timeCondition = $this->getTimeWhere($params['date_b'], $params['date_e']);
        if (!is_array($timeCondition)) {
            return json(['message' => '日期格式错误'], 400);
        }
        if (!empty($timeCondition)) {
            $where['p.shipping_time'] = $timeCondition;
        }
    }

    /**
     * 获取字段信息
     * @return string
     */
    public function field()
    {
        $field = 's.id,' .    //邮寄方式id
            's.shortname,' .    //邮寄方式
            's.collector_id,' .    //揽收商id
            's.carrier_id,' .    //承运方式
            's.settlement_method,' .    //结算方式
            'count(p.id) as parcels_num,'. //统计包裹数量
            'sum(p.shipping_fee) as shipping_fee,' . //运费
            'sum(p.package_weight) as package_weight' //合计重量
        ;
        return $field;
    }

    /**
     * 关联数据
     * @return array
     */
    public function join()
    {
        $join[] = ['order_package p', 'p.shipping_id = s.id', 'left'];
        $join[] = ['order o', 'o.id = p.order_id', 'left'];
        return $join;
    }

    /**
     * 查询总数
     * @param $field
     * @param array $condition
     * @param array $join
     * @return int|string
     */
    protected function doCount($field, array $condition = [], array $join = [])
    {
        $shipping_method = new ShippingMethod();
        $whereTwo['p.shipping_time'] = ['gt', 0];
        return $shipping_method->group("s.id")->alias('s')->field($field)->join($join)->where($condition)->where($whereTwo)->order('s.id asc')->count();
    }

    /**
     * 查询数据
     * @param $field
     * @param array $condition
     * @param array $join
     * @param int $page
     * @param int $pageSize
     * @return int|string
     * @throws Exception
     */
    public function doSearch($field, array $condition = [], array $join = [], $page = 1, $pageSize = 20)
    {
        set_time_limit(0);
        $shipping_method = new ShippingMethod();
        $whereTwo['p.shipping_time'] = ['gt', 0];
        $data=$shipping_method->group("s.id")->alias('s')->field($field)->join($join)->where($condition)->where($whereTwo)->order('s.id asc')->page($page,
                $pageSize)->select();
        return $data;
    }

    public function do($field, array $condition = [], array $join = [],$page = 1, $pageSize = 20)
    {
        $shipping_method = new ShippingMethod();
        $whereTwo['p.shipping_time'] = ['gt', 0];
        $data=$shipping_method->group("s.id")
            ->alias('s')->field($field)
            ->join($join)->where($condition)
            ->where($whereTwo)->order('s.id asc')->select();
        return $data;

    }

    /**
     * 导出申请汇总
     *@param $params
     * @param $page
     * @param $pageSize
     * @param $field
     * @return bool|array
     * @throws \Exception
     */
    public function exportApplys($page,$pageSize,$params='',$field)
    {
        $userId = Common::getUserInfo()->toArray()['user_id'];
        $cache = Cache::handler();
        try {
            $lastApplyTime = $cache->hget('hash:export_detail_apply', $userId);
            if ($lastApplyTime && time() - $lastApplyTime < 5) {
                throw new JsonErrorException('请求过于频繁', 400);
            } else {
                $cache->hset('hash:export_apply', $userId, time());
            }
            $fileName = $this->createFileNames($params);
            $downLoadDir = '/download/express_exports1/';
            $saveDir = ROOT_PATH . 'public' . $downLoadDir;
            if (!is_dir($saveDir) && !mkdir($saveDir, 0777, true)) {
                throw new Exception('导出目录创建失败');
            }
            $fullName = $saveDir . $fileName . '_' . $userId . '_' . date('Y-m-d', time()) . '.xlsx';
            $titleMap = $this->colMaps['order']['title'];
            $title = [];
            $col = [];
            $titleData = $this->colMaps['order']['data'];
            if (!empty($field)) {
                foreach ($field as $k => $v) {
                    if (isset($titleData[$v])) {
                        array_push($title, $v);
                        $col[$titleData[$v]['col']] = $titleData[$v]['col'];
                    }
                }
                $titleOrderData = [];
                foreach ($titleMap as $t => $tt) {
                    if (isset($col[$t])) {
                        $titleOrderData[$tt['title']] = 'string';
                    }
                }
            } else {
                foreach ($titleData as $k => $v) {
                    array_push($title, $k);
                }
                $titleOrderData = [];
                foreach ($titleMap as $t => $tt) {
                    $titleOrderData[$tt['title']] = 'string';
                }
            }
            $where = [];
            $params['channel_id']=trim($params['channel_id']);
            $params['warehouse_id']=trim($params['warehouse_id']);
            $params['carrier_id']=trim($params['carrier_id']);
            $params['dateRange']=trim($params['dateRange']);
            $params['date_b']=trim($params['date_b']);
            $params['date_e']=trim($params['date_e']);
            $params['shipping_ids']=trim($params['shipping_ids']);
            $params['page']=trim($params['page']);
            $params['pageSize']=trim($params['pageSize']);
            $this->where($params,$where);
            $fields = $this->field();
            $join = $this->join();
            $count =$this->doCount($fields, $where, $join);
            if ($count > 500) {
                $params['field'] = $field;
                //队列导出
                Db::startTrans();
                try {
                    $model = new ReportExportFiles();
                    $data['applicant_id'] = $userId;
                    $data['apply_time'] = time();
                    $data['export_file_name'] = $this->createFileNames($params) . '.xlsx';
                    $data['status'] = 0;
                    $data['applicant_id'] = $userId;
                    $model->allowField(true)->isUpdate(false)->save($data);
                    $params['file_name'] = $this->createFileNames($params) . '_' . $userId . '_' . date('Y-m-d', time()) . '.xlsx';
                    $params['apply_id'] = $model->id;
                   //$da=new ExpressConfirmExportCollectQueue();
                    //$da->execute($params);
                    (new CommonQueuer(ExpressConfirmExportCollectQueue::class))->push($params);
                    Db::commit();
                   return ['join_queue' => 1, 'message' => '已加入导出队列'];
                } catch (\Exception $ex) {
                    Db::rollback();
                    throw new JsonErrorException('申请导出失败');
                }

            } else {
                set_time_limit(0);
               $data = $this->exportData($this->do($fields, $where, $join, $page, $pageSize), $title);
                $data=json_encode($data);
                $data=json_decode($data,true);
                foreach($data as $k=>$v){
                    unset($data[$k]['collector_id']);
                    unset($data[$k]['carrier_id']);
                }

                $this->excelSave($titleOrderData, $fullName, $data);
                $auditOrderService = new AuditOrderService();
                $result = $auditOrderService->record($fileName, $fullName);
                return $result;
            }

        } catch (Exception $e) {
            throw new JsonErrorException($e->getMessage().$e->getFile().$e->getLine());


        }
    }

    /**
     * 导出条件
     * @param $params
     */
        public function wheres($params,&$where){
            //平台id
            if (isset($params['channel_id']) &&!empty($params['channel_id'])) {
                $where['p.channel_id'] = ['eq', $params['channel_id']];
            }
            if (isset($params['account_id']) && !empty($params['account_id'])) {
                $where['p.channel_account_id'] = ['eq', $params['account_id']];
                if ($params['channel_id'] == ChannelAccountConst::channel_Joom) {
                    //joom平台特殊操作
                    if ($shop_id = param($params, 'shop_id')) {
                        $where['p.channel_account_id'] = ['=', $shop_id];
                    } else {
                        $joomShopModel = new JoomShop();
                        $account_ids = $joomShopModel->field('id')->where(['joom_account_id' => $params['account_id']])->select();
                        $account_ids = array_column($account_ids, 'id');
                        $where['p.channel_account_id'] = ['in', $account_ids];
                    }
                }
            }

            //仓库id
            if (isset($params['warehouse_id']) && empty($params['warehouse_id'])) {
                $where['p.warehouse_id'] = ['eq', $params['warehouse_id']];
            }

            //承运公司id
            if ($carrier_id = param($params, 'carrier_id')) {
                if (is_numeric($carrier_id)) {
                    $where['s.carrier_id'] = ['eq',$params['carrier_id']];
                }
            }

            //揽收商
            if (isset($params['collector_id']) && !empty($params['collector_id'])) {
                $where['s.collector_id'] = ['eq', $params['collector_id']];
            }

            //邮寄方式id
            if (isset($params['shipping_ids']) && ($params['shipping_ids'])) {
                $shipping_ids = json_decode($params['shipping_ids'], true);
                $where['s.id'] = ['in', $shipping_ids];
            }

            //查询日期
            $params['date_b'] = isset($params['date_b']) ? trim($params['date_b']) : 0;
            $params['date_e'] = isset($params['date_e']) ? trim($params['date_e']) : 0;
            $timeCondition = $this->getTimeWhere($params['date_b'], $params['date_e']);
            if (!is_array($timeCondition)) {
                return json(['message' => '日期格式错误'], 400);
            }
            if (!empty($timeCondition)) {
                $where['p.shipping_time'] = $timeCondition;
            }
        }
    /**
     * 导出申请
     * @param $params
     * @param $ids
     * @param $field
     * @return bool|array
     * @throws \Exception
     */
    public function exportApply($params, $ids, $field)
    {
        $userId = Common::getUserInfo()->toArray()['user_id'];
        $cache = Cache::handler();
        $lastApplyTime = $cache->hget('hash:export_detail_apply', $userId);
        if ($lastApplyTime && time() - $lastApplyTime < 5) {
            throw new JsonErrorException('请求过于频繁', 400);
        } else {
            $cache->hset('hash:export_apply', $userId, time());
        }
        $fileName = $this->createExportFileName($params);
        $downLoadDir = '/download/express_confirm/';
        $saveDir = ROOT_PATH . 'public' . $downLoadDir;
        if (!is_dir($saveDir) && !mkdir($saveDir, 0777, true)) {
            throw new Exception('导出目录创建失败');
        }
        $fullName = $saveDir . $fileName . '_' . $userId . '_' . date('YmdHis', time()) . '.xlsx';
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
        //统计需要导出的数据行
        $params['ids'] = $ids;
        //统计导出数量
        $count = $this->getCount($params);
        if ($count > 500) {
            $params['field'] = $field;
            //队列导出
            Db::startTrans();
            try {
                $model = new ReportExportFiles();
                $data['applicant_id'] = $userId;
                $data['apply_time'] = time();
                $data['export_file_name'] = $this->createExportFileName($params) . '.xlsx';
                $data['status'] = 0;
                $data['applicant_id'] = $userId;
                $model->allowField(true)->isUpdate(false)->save($data);
                $params['file_name'] = $this->createExportFileName($params) . '_' . $userId . '_' . date('Y-m-d H:i:s', time()) . '.xlsx';
                $params['apply_id'] = $model->id;
                $params['ids'] = $ids;  //选中部分id
                (new CommonQueuer(ExpressConfirmExportQueue::class))->push($params);
                Db::commit();
                return ['join_queue' => 1, 'message' => '已加入导出队列'];
            } catch (\Exception $ex) {
                Db::rollback();
                throw new JsonErrorException('申请导出失败');
            }
        } else {
            //页面导出
            $writer = new \XLSXWriter();
            $countryList = Cache::store('country')->getCountry();
            $shippingArr = $this->getShippingMethodArr($params);
            foreach ($shippingArr as $key => $shippingData) {
                if (strlen($key) > 31) {
                    $key = mb_substr($key, 0, 31, "utf-8");
                }
                $oldchar=array(" ","　","-");
                $newchar=array("","","");
                $key = str_replace($oldchar,$newchar,$key);
                $writer->writeSheetHeader($key, $titleOrderData);
                $data = $this->getExportData(1, 20, $key, $title, $shippingData, $countryList,1);
                foreach ($data as $a => $r){
                    $writer->writeSheetRow($key, $r);
                }
            }
            $writer->writeToFile($fullName);
            $auditOrderService = new AuditOrderService();
            $result = $auditOrderService->record($fileName, $fullName);
            return $result;
        }
    }
    /**
     * 创建导出文件名
     * @param $params
     * @return string
     */
    protected function createFileNames($params)
    {

        if (!empty($params['date_b']) && !empty($params['date_e'])) {
            $date_b = strtotime($params['date_b']);
            $date_b = date('Y-m-d ', $date_b);
            $data_e = strtotime($params['date_e']);
            $data_e = date('Y-m-d ', $data_e);
            $fileName = '快递确认单汇总表（' . $date_b . '-' . $data_e . ')';
        } else {
            $fileName = '快递确认单汇总表';
        }
        return $fileName;
    }
    /**
     * 创建导出文件名
     * @param $params
     * @return string
     */
    protected function createExportFileName($params)
    {
        if (!empty($params['date_b']) && !empty($params['date_e']) && $params['export_type'] == 1) {
            $date_b = strtotime($params['date_b']);
            $date_b = gmdate('Y-m-d H:i:s',$date_b);
            $data_e = strtotime($params['date_e']);
            $data_e = date('Y-m-d H:i:s',$data_e);
            $fileName = '快递确认单报表（' . $date_b . '-' .$data_e . ')';
        } else {
            $fileName = '快递确认单报表';
        }
        return $fileName;
    }
    /**
     * 导出数据至excel文件
     * @param array $params
     * @return bool
     * @throws Exception
     */
    public function collectExport(array $params)
    {

        set_time_limit(0);
        try {
             ini_set('memory_limit', '4096M');
            if (!isset($params['apply_id']) || empty($params['apply_id'])) {
                throw new Exception('导出申请id获取失败');
            }
            if (!isset($params['file_name']) || empty($params['file_name'])) {
                throw new Exception('导出文件名未设置');
            }
            $fileName = $params['file_name'];
            $downLoadDir = '/download/express_confirm/';
            $saveDir = ROOT_PATH . 'public' . $downLoadDir;

            if (!is_dir($saveDir) && !mkdir($saveDir, 0777, true)) {
                throw new Exception('导出目录创建失败');
            }

            $fullName = $saveDir . $fileName;
            $where = [];
            $this->where($params, $where);
            $fields = $this->field();
            $join = $this->join();
            $count =$this->doCount($fields, $where, $join);
            $pageSize = 1000;
            $loop = ceil($count / $pageSize);
            //创建excel对象
            $writer = new \XLSXWriter();
            $col = [];
            $title = [];
            $titleMap = $this->colMaps['order']['title'];
            $titleData = $this->colMaps['order']['data'];
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
                $data = $this->exportData($this->doSearch($fields, $where, $join, $i+1, $pageSize),$title);
                foreach($data as $k=>$v){
                    unset($data[$k]['collector_id']);
                    unset($data[$k]['carrier_id']);
                    $writer->writeSheetRow('Sheet1', $v);
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
            $applyRecord['status'] = 2;
            $applyRecord['error_message'] = $ex->getMessage();
            (new ReportExportFiles())->where(['id' => $params['apply_id']])->update($applyRecord);
            Cache::handler()->hset(
                'hash:report_export',
                $params['apply_id'] .'_' . time(),
                '申请id: ' . $params['apply_id'] . ',导出失败:' . $ex->getMessage() . ',错误行数：' . $ex->getLine());
        }
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
            if (!isset($params['apply_id']) || empty($params['apply_id'])) {
                throw new Exception('导出申请id获取失败');
            }
            if (!isset($params['file_name']) || empty($params['file_name'])) {
                throw new Exception('导出文件名未设置');
            }
            $fileName = $params['file_name'];
            $downLoadDir = '/download/express_confirm/';
            $saveDir = ROOT_PATH . 'public' . $downLoadDir;
            if (!is_dir($saveDir) && !mkdir($saveDir, 0777, true)) {
                throw new Exception('导出目录创建失败');
            }
            $fullName = $saveDir . $fileName;
            //统计需要导出的数据行
            $count = $this->getCount($params);
            $pageSize = 10000;
            $loop = ceil($count / $pageSize);
            $shippingArr = $this->getShippingMethodArr($params);
//            $num = 0;
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
           // $excel = new \PHPExcel();
            foreach ($shippingArr as $key => $shippingData) {
//                if (!empty($num)) {
//                    $excel->createSheet();
//                }
//                $excel->setActiveSheetIndex($num);
//                $sheet = $excel->getActiveSheet();
                if (strlen($key) > 31) {
                    $key = mb_substr($key, 0, 31, "utf-8");
                }
                $oldchar=array(" ","　","-");
                $newchar=array("","","");
                $key = str_replace($oldchar,$newchar,$key);
                $writer->writeSheetHeader($key, $titleOrderData);
//                $sheet->setTitle($key);
//                $excel->setActiveSheetIndexByName($key);
//                $titleRowIndex = 1;
//                $dataRowStartIndex = 2;
//                $titleMap = $this->colMap['order']['title'];
//                $lastCol = 'AA';
//                $dataMap = $this->colMap['order']['data'];
                //设置表头和表头样式
//                foreach ($titleMap as $col => $set) {
//                    $sheet->getColumnDimension($col)->setWidth($set['width']);
//                    $sheet->getCell($col . $titleRowIndex)->setValue($set['title']);
//                    $sheet->getStyle($col . $titleRowIndex)
//                        ->getFill()
//                        ->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)
//                        ->getStartColor()->setRGB('E8811C');
//                    $sheet->getStyle($col . $titleRowIndex)
//                        ->getBorders()
//                        ->getAllBorders()
//                        ->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
//                }
//                $sheet->setAutoFilter('A1:' . $lastCol . '1');
                //国家信息
                $countryList = Cache::store('country')->getCountry();
                //分批导出
                for ($i = 0; $i < $loop; $i++) {
                    $data = $this->getExportData($i + 1, $pageSize, $key, $title, $shippingData, $countryList);
                    foreach ($data as $r) {
//                        foreach ($dataMap as $field => $set) {
//                            $cell = $sheet->getCell($set['col'] . $dataRowStartIndex);
//                            switch ($set['type']) {
//                                case 'time':
//                                    if (empty($r[$field])) {
//                                        $cell->setValue('');
//                                    } else {
//                                        $cell->setValue(date('Y-m-d H:i:s', $r[$field]));
//                                    }
//                                    break;
//                                case 'numeric':
//                                    $cell->setDataType(\PHPExcel_Cell_DataType::TYPE_NUMERIC);
//                                    if (empty($r[$field])) {
//                                        $cell->setValue(0);
//                                    } else {
//                                        $cell->setValue($r[$field]);
//                                    }
//                                    break;
//                                default:
//                                    $cell->setDataType(\PHPExcel_Cell_DataType::TYPE_STRING);
//                                    if (!isset($r[$field]) || is_null($r[$field])) {
//                                        $r[$field] = '';
//                                    }
//                                    $cell->setValueExplicit($r[$field], \PHPExcel_Cell_DataType::TYPE_STRING);
//                            }
//                        }
//                        $dataRowStartIndex++;
                        $writer->writeSheetRow($key, $r);
                    }
                    unset($data);
                }
//                $num++;
            }
            $writer->writeToFile($fullName);
//            $writer = \PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
//            $writer->save($fullName);
            if (is_file($fullName)) {
                $applyRecord['exported_time'] = time();
                $applyRecord['download_url'] = $downLoadDir . $fileName;
                $applyRecord['status'] = 1;
                (new ReportExportFiles())->where(['id' => $params['apply_id']])->update($applyRecord);
            } else {
                throw new Exception('文件写入失败');
            }
        } catch (\Exception $ex) {
            $applyRecord['status'] = 2;
            $applyRecord['error_message'] = $ex->getMessage();
            (new ReportExportFiles())->where(['id' => $params['apply_id']])->update($applyRecord);
            Cache::handler()->hset(
                'hash:report_export',
                $params['apply_id'].'_'.time(),
                '申请id: ' . $params['apply_id'] . ',导出失败:' . $ex->getMessage() . ',错误行数：' . $ex->getLine());
        }
    }

    /**
     * 组装查询返回数据
     * @param $data
     * @param $params
     * @return array
     */
    protected function assemblyData($data, $params)
    {
        //平台id
        if (isset($params['channel_id']) && !empty($params['channel_id'])) {
            $where['channel_id'] = ['eq', $params['channel_id']];
        }

        if (isset($params['account_id']) && !empty($params['account_id'])) {
            $where['channel_account_id'] = ['eq', $params['account_id']];
            if ($params['channel_id'] == ChannelAccountConst::channel_Joom) {
                //joom平台特殊操作
                if ($shop_id = param($params, 'shop_id')) {
                    $where['channel_account_id'] = ['=', $shop_id];
                } else {
                    $joomShopModel = new JoomShop();
                    $account_ids = $joomShopModel->field('id')->where(['joom_account_id' => $params['account_id']])->select();
                    $account_ids = array_column($account_ids, 'id');
                    $where['channel_account_id'] = ['in', $account_ids];
                }
            }
        }

        //仓库id
        if (isset($params['warehouse_id']) && !empty($params['warehouse_id'])) {
            $where['warehouse_id'] = ['eq', $params['warehouse_id']];
        }

        $params['date_b'] = isset($params['date_b']) ? trim($params['date_b']) : 0;
        $params['date_e'] = isset($params['date_e']) ? trim($params['date_e']) : 0;
        $condition = $this->getTimeWhere($params['date_b'], $params['date_e']);
        if (!empty($condition)) {
            $where['shipping_time'] = $condition;
        }

        $whereTwo['shipping_time'] = ['gt', 0];
        //统计订单数量
        foreach ($data as $key => $v) {
            $v['shipping_fee']=sprintf('%01.2f',$v['shipping_fee']);
            if (!empty($v['settlement_method'])) {
                $v['settlement_method'] = (new ShippingMethodService())->getSettlementMethod()[$v['settlement_method']]['name'];
            } else {
                $v['settlement_method'] = '';
            }
            $carrierInfo = Cache::store('carrier')->getCarrier($v['carrier_id']);
            $v['carrier_company'] = '';
            if (isset($carrierInfo['fullname']) && !empty($carrierInfo['fullname'])) {
                $v['carrier_company'] = $carrierInfo['fullname'];
            }
            $v['collector_name'] = '';
            if (!empty($v['collector_id'])) {
                $collectorInfo = Cache::store('Collector')->getCollector($v['collector_id']);
                $v['collector_name'] = $collectorInfo['name'] ?? '';
            }
            //重量装换g 转成 kg
            $v['package_weight'] = sprintf('%01.2f',$v['package_weight'] / 1000);
        }
        return $data;
    }
    /**
     * 组装查询返回数据
     * @param $data
     *  @param $title
     * @return array
     */
    protected function exportData($data,$title)
    {
        //统计订单数量
        set_time_limit(0);
        $info=[];
        foreach ($data as $key =>$v) {
            $v['shipping_fee']=sprintf('%01.2f',$v['shipping_fee']);
            //重量装换g 转成 kg
            $v['package_weight'] = sprintf('%01.2f',$v['package_weight'] / 1000);
            if (!empty($v['settlement_method'])) {
                $v['settlement_method'] = (new ShippingMethodService())->getSettlementMethod()[$v['settlement_method']]['name'];
            } else {
                $v['settlement_method'] = '';
            }
            $newData=$v;
            if(!empty($v['carrier_id'])){
                $carrierInfo = Cache::store('carrier')->getCarrier($v['carrier_id']);
                $newData['carrierCompany'] = $carrierInfo['fullname'] ?? '';
            }else{
                $newData['carrierCompany']='' ;
            }
            $v['collector_name'] = '';
            if (!empty($v['collector_id'])) {
                $collectorInfo = Cache::store('Collector')->getCollector($v['collector_id']);
                $newData['collector_name'] = $collectorInfo['name']??'';
            }else{
                $newData['collector_name'] = '';
            }
            $temp=[];
            foreach($title as $value){
                $temp[$value] =$newData[$value];

            }
            array_push($info,$temp);
        }
        return $info;
    }


    /**
     * 获取导出查询条件
     * @param $params
     * @param $condition
     * @return \think\response\Json
     */
    public function getExportWhere($params, &$condition)
    {
        //平台id
        if (isset($params['channel_id']) && !empty($params['channel_id'])) {
            $condition['p.channel_id'] = ['eq', $params['channel_id']];
        }
        if (isset($params['account_id']) && !empty($params['account_id'])) {
            $condition['p.channel_account_id'] = ['eq', $params['account_id']];
            if ($params['channel_id'] == ChannelAccountConst::channel_Joom) {
                //joom平台特殊操作
                if ($shop_id = param($params, 'shop_id')) {
                    $condition['p.channel_account_id'] = ['=', $shop_id];
                } else {
                    $joomShopModel = new JoomShop();
                    $account_ids = $joomShopModel->field('id')->where(['joom_account_id' => $params['account_id']])->select();
                    $account_ids = array_column($account_ids, 'id');
                    $condition['p.channel_account_id'] = ['in', $account_ids];
                }
            }
        }

        //仓库id
        if (isset($params['warehouse_id']) && !empty($params['warehouse_id'])) {
            $condition['p.warehouse_id'] = ['eq', $params['warehouse_id']];
        }

        //揽收商
//        if (isset($params['collector_id']) && !empty($params['collector_id'])) {
//            $condition['s.collector_id'] = ['eq', $params['collector_id']];
//        }

        //目的地国家
        if (isset($params['country_code']) && !empty($params['country_code'])) {
            $condition['o.country_code'] = ['eq', $params['country_code']];
        }

//        //邮寄方式id
//        if (isset($params['shipping_ids']) && !empty($params['shipping_ids'])) {
//            $shipping_ids = json_decode($params['shipping_ids'], true);
//            $condition['s.id'] = ['in', $shipping_ids];
//        }

        //查询日期
        $params['date_b'] = isset($params['date_b']) ? trim($params['date_b']) : 0;
        $params['date_e'] = isset($params['date_e']) ? trim($params['date_e']) : 0;
        $time_where = $this->getTimeWhere($params['date_b'], $params['date_e']);
        if (!is_array($time_where)) {
            return json(['message' => '日期格式错误'], 400);
        }
        if (!empty($time_where)) {
            $condition['p.shipping_time'] = $time_where;
        }
    }

    /**
     * 获取时间筛选条件
     * @param $start
     * @param $end
     * @return array|bool
     */
    public function getTimeWhere($start, $end)
    {
        date_default_timezone_set("PRC");
        $condition = [];
        if (!empty($start) && !empty($end)) {
            $is_date = strtotime($start) ? strtotime($start) : false;
            if (!$is_date) {
                return false;
            }
            $start = strtotime($start);
            $is_date = strtotime($end) ? strtotime($end) : false;
            if (!$is_date) {
                return false;
            }
            $end = strtotime($end);
            $condition = ['between', [$start, $end]];
        } else {
            if (!empty($start)) {
                $is_date = strtotime($start) ? strtotime($start) : false;
                if (!$is_date) {
                    return false;
                }
                $start = strtotime($start);
                $condition = ['>=', $start];
            } else {
                if (!empty($end)) {
                    $is_date = strtotime($end) ? strtotime($end) : false;
                    if (!$is_date) {
                        return false;
                    }
                    $end = strtotime($end);
                    $condition = ['<=', $end];
                }
            }
        }
        return $condition;
    }

    /**
     * 获取承运公司数组
     * @param $params
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCarrierArr($params)
    {
        $where = [];
        //添加查询条件
        if (!empty($params['ids'])) {
            $where['s.id'] = ['in', $params['ids']];
        }

        //承运公司id
        if ($carrier_id = param($params, 'carrier_id')) {
            if (is_numeric($carrier_id)) {
                $where['c.id'] = ['eq',$carrier_id];
            }
        }

        //揽收商
        if (isset($params['collector_id']) && !empty($params['collector_id'])) {
            $where['s.collector_id'] = ['eq', $params['collector_id']];
        }

        $carrier = new Carrier();
        $join[] = ['shipping_method s', 's.carrier_id = c.id', 'left'];
        //查询承运公司对应的物流方式
        $carrierData = $carrier->alias('c')->field('c.id,c.fullname,s.id as sid,s.carrier_id')->join($join)->where($where)->select();
        //获取对应的物流公司id
        $carrierArr = [];
        foreach ($carrierData as $k => $v) {
            if (!isset($carrierArr[$v['fullname']])) {
                $carrierArr[$v['fullname']] = [];
            }
            array_push($carrierArr[$v['fullname']], $v['sid']);
        }
        return $carrierArr;
    }

    /**
     * 获取物流方式数组
     * @param $params
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getShippingMethodArr($params)
    {
        $where = [];
        $this->getExportWhere($params, $where);
        //添加查询条件
        if (!empty($params['ids'])) {
            $where['s.id'] = ['in', $params['ids']];
        }

        //承运公司id
        if ($carrier_id = param($params, 'carrier_id')) {
            if (is_numeric($carrier_id)) {
                $where['s.carrier_id'] = ['eq',$carrier_id];
            }
        }

        //邮寄方式id
        if (isset($params['shipping_ids']) && !empty($params['shipping_ids'])) {
            $shipping_ids = json_decode($params['shipping_ids'], true);
            $condition['s.id'] = ['in', $shipping_ids];
        }

        //揽收商
        if (isset($params['collector_id']) && !empty($params['collector_id'])) {
            $condition['s.collector_id'] = ['eq', $params['collector_id']];
        }

        $join[] = ['order_package p', 'p.shipping_id = s.id', 'left'];
        $join[] = ['order o', 'o.id = p.order_id', 'left'];
        //物流方式对应的包裹id
        $shippingData = Db::table('shipping_method')->alias('s')->field('s.id,s.shortname,s.collector_id,p.id as package_id')->join($join)->where($where)->select();
        //获取揽收商数据
        $collectorArr = array_column($shippingData,'collector_id');
        $collectorArr = array_unique($collectorArr);
        $collectorInfo = [];
        foreach ($collectorArr as $k => $v ) {
            if (!isset($collectorInfo[$v])) {
                $collectorInfo[$v] = [];
            }
            if (empty($collectorInfo[$v])) {
                $info = Cache::store('Collector')->getCollector($v);
                $name = $info['name'] ?? '';
                $collectorInfo[$v] = $name;
            }
        }
        //获取对应的物流公司id
        $shippingArr = [];
        $strArr = array('*', ':', '/', '\\', '?', '[', ']');
        foreach ($shippingData as $k => $v) {
            $v['shortname'] = str_replace($strArr,'-',$v['shortname']);
            if (!isset($shippingArr[$v['shortname']])) {
                $shippingArr[$v['shortname']] = [];
            }
            if (!isset($shippingArr[$v['shortname']]['package_ids'])) {
                $shippingArr[$v['shortname']]['package_ids'] = [];
            }
            if (!isset($shippingArr[$v['shortname']]['collector_name'])) {
                $shippingArr[$v['shortname']]['collector_name'] = [];
            }
            $collector_name = $collectorInfo[$v['collector_id']] ?? '';
            array_push($shippingArr[$v['shortname']]['package_ids'], $v['package_id']);
            array_push($shippingArr[$v['shortname']]['collector_name'], $collector_name);
        }
        return $shippingArr;
    }

    /**
     * 统计导出数量
     * @param $params
     * @return int|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCount($params)
    {
        $carrierArr = $this->getCarrierArr($params);
        //查询承运公司下的订单包裹数量
        $condition = [];
        $this->getExportWhere($params, $condition);
        $orderPackageModel = new OrderPackage();
        $countNum = 0;
        $join[] = ['order o', 'o.id = p.order_id', 'left']; //添加关联订单国家
        foreach ($carrierArr as $key => $carrier_ids) {
            //包裹物流方式过滤
            $condition['p.shipping_id'] = ['in', $carrier_ids];
            //查询包裹数据
            $countNum += $orderPackageModel->alias('p')->join($join)->where($condition)->count();
        }
        return $countNum;
    }

    /**
     * 查询整理导出数据
     * @param $params
     * @param int $page
     * @param int $pageSize
     * @param $key
     * @param $titleArr
     * @param $shippingData
     * @param $countryList
     * @param $pagination
     * @return array
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
//    public function getExportData($params, $page = 1, $pageSize = 20, $key, $titleArr, $shippingData, $countryList, $pagination = 0)
//    {
//        //查询承运公司下的订单包裹数量
//        $condition = [];
//        $conditionExp = [];
//        if (isset($params['date_b']) && isset($params['date_e'])) {
//            $conditionExp['p.shipping_time'] = ['gt', 0];
//        }
//        $orderPackageModel = new OrderPackage();
//        $orderPackageInfo = [];
//        //包裹物流方式过滤
//        if (isset($shippingData['package_ids']) && !empty($shippingData['package_ids'])) {
//            $condition['p.id'] = ['in', $shippingData['package_ids']];
//        }
//        //查询包裹数据
//        $field = 'p.id as package_id,p.order_id,p.channel_id,p.channel_account_id,p.shipping_number,p.shipping_time,p.pay_time,p.number,p.package_weight,p.shipping_fee,p.providers_weight,p.providers_fee';
//        if (empty($pagination)) {
//            $dataList = $orderPackageModel->alias('p')->field($field)->where($condition)->where($conditionExp)->order('p.id desc')->page($page, $pageSize)->select();
//        } else {
//            $dataList = $orderPackageModel->alias('p')->field($field)->where($condition)->where($conditionExp)->order('p.id desc')->select();
//        }
//        $i = 1;
//        //实例化模型
//        $orderModel = new Order();
//        $orderAddressModel = new OrderAddress();
//        $orderPackageDeclareModel = new OrderPackageDeclare();
//        $packageCollectionService = new PackageCollection();
//
//        //查询订单数据
//        $order_ids = array_map(function ($orderInfo) {
//            return $orderInfo->order_id;
//        }, $dataList);
//        //查询包裹订单数据
//        $orderInfo = [];
//        $orderData = $orderModel->field('id,buyer_id,country_code')->where(['id' => ['in',$order_ids]])->select();
//        foreach ($orderData as $k => $v) {
//            $info = $v->toArray();
//            if (!isset($orderInfo[$v['id']])) {
//                $orderInfo[$v['id']] = $info;
//            }
//        }
//        //获取订单orderAddress 表的用户姓名和电话号码
//        $orderAddressData = $orderAddressModel->field('order_id,province,city,zipcode,address,consignee,tel')->where(['order_id' => ['in', $order_ids]])->select();
//        //查询包裹订单地址数据
//        $orderAddressInfo = [];
//        foreach ($orderAddressData as $k => $v) {
//            $info = $v->toArray();
//            if (!isset($orderAddressInfo[$v['order_id']])) {
//                $orderAddressInfo[$v['order_id']] = $info;
//            }
//        }
//
//        $package_ids = array_map(function ($packageInfo) {
//            return $packageInfo->package_id;
//        }, $dataList);
//
//        $orderPackageDeclareInfo = [];
//        $orderPackageDeclareData = $orderPackageDeclareModel->field('package_id,sku_id,goods_name_cn,goods_name_en,quantity,unit_price,declare_currency')->where(['package_id' => ['in', $package_ids]])->select();
//        foreach ($orderPackageDeclareData as $k => $v){
//            $info = $v->toArray();
//            if (!isset($orderPackageDeclareInfo[$v['package_id']])) {
//                $orderPackageDeclareInfo[$v['package_id']] = [];
//                array_push($orderPackageDeclareInfo[$v['package_id']],$info);
//            }else{
//                array_push($orderPackageDeclareInfo[$v['package_id']],$info);
//            }
//        }
//        foreach ($dataList as $k => $v) {
//            $data['id'] = $i;
//            $data['carrierCompany'] = $key ? $key : '';
//            $data['shortname'] = $key ? $key : '';
//            $data['collector_name'] = $shippingData['collector_name'][0] ? $shippingData['collector_name'][0] : '';
//            //导出字段
//            $data['shipping_time'] = $v['shipping_time'] ? date('Y-m-d H:i:s',$v['shipping_time']) : '';
//            //获取执行发货人
//            $send_name = $packageCollectionService->getHandoverNameByPackageId($v['package_id']);
//            $data['sendpeople'] = $send_name;
//            $data['pay_time'] = $v['pay_time'] ? date('Y-m-d H:i:s',$v['pay_time']) : '';
//            //获取平台
//            $channel = new Channel();
//            $title = $channel->where('id' ,$v['channel_id'])->value('title');
//            $data['channel_name'] = !empty($title) ? $title : '';
//            //获取用户账号
//            $order_service = new \app\order\service\OrderService();
//            $accountName = $order_service->getAccountName($v['channel_id'], $v['channel_account_id']);
//            $data['channel_account_id'] = !empty($v['channel_account_id']) ? $accountName : '';
//            $data['shipping_number'] = !empty($v['shipping_number']) ? $v['shipping_number'] : '';
//
//            //order 表查询
//            if(isset($orderAddressInfo[$v['order_id']]) && !empty($orderAddressInfo[$v['order_id']])){
//                $data['country_code'] = $orderInfo[$v['order_id']]['country_code'] ? $orderInfo[$v['order_id']]['country_code'] : '';
//                $data['country_cn'] = isset($countryList[$orderInfo[$v['order_id']]['country_code']]['country_cn_name']) ? !empty($countryList[$orderInfo[$v['order_id']]['country_code']]['country_cn_name']) ? $countryList[$orderInfo[$v['order_id']]['country_code']]['country_cn_name'] : '' : '';
//                $data['country_en'] = isset($countryList[$orderInfo[$v['order_id']]['country_code']]['country_en_name']) ? !empty($countryList[$orderInfo[$v['order_id']]['country_code']]['country_en_name']) ? $countryList[$orderInfo[$v['order_id']]['country_code']]['country_en_name'] : '' : '';
//            }else{
//                $data['country_code'] ='';
//                $data['country_cn']='';
//                $data['country_en']='';
//            }
//
//            //通过country_code 获取国家中英文名
//            $data['number'] = $v['number'] ? $v['number'] : '';
//            $data['package_weight'] = $v['package_weight'] ? $v['package_weight'] : '';
//            $v['shipping_fee']=sprintf('%01.2f',$v['shipping_fee']);
//            $data['shipping_fee'] = $v['shipping_fee'] ? $v['shipping_fee'] : '';
//            $data['providers_weight'] = $v['providers_weight'] ? $v['providers_weight'] : '';
//            $data['providers_fee'] = $v['providers_fee'] ? $v['providers_fee'] : '';
//
//            //order_address 表查询
//            //获取订单orderAddress 表的用户姓名和电话号码|
//            if (isset($orderAddressInfo[$v['order_id']]) && !empty($orderAddressInfo[$v['order_id']])) {
//                $data['buyer_name'] = $orderAddressInfo[$v['order_id']]['consignee'] ? $orderAddressInfo[$v['order_id']]['consignee'] : '';
//                $data['buyer_phone'] = $orderAddressInfo[$v['order_id']]['tel'] ? $orderAddressInfo[$v['order_id']]['tel'] : '';
//                $data['province'] = $orderAddressInfo[$v['order_id']]['province'] ? $orderAddressInfo[$v['order_id']]['province'] : '';
//                $data['city_id'] = $orderAddressInfo[$v['order_id']]['city'] ? $orderAddressInfo[$v['order_id']]['city'] : '';
//                $data['zipcode'] = $orderAddressInfo[$v['order_id']]['zipcode'] ? $orderAddressInfo[$v['order_id']]['zipcode'] : '';
//                $data['address'] = $orderAddressInfo[$v['order_id']]['address'] ? $orderAddressInfo[$v['order_id']]['address'] : '';
//            } else {
//                $data['buyer_name'] = '';
//                $data['buyer_phone'] = '';
//                $data['province'] = '';
//                $data['city_id'] = '';
//                $data['zipcode'] = '';
//                $data['address'] = '';
//            }
//
//            //order_package_declare
//            $quantity = '';
//            $unit_price = '';
//            if (isset($orderPackageDeclareInfo[$v['package_id']]) && !empty($orderPackageDeclareInfo[$v['package_id']])) {
//                foreach ($orderPackageDeclareInfo[$v['package_id']] as $op => $opInfo) {
//                    $quantity .= ($quantity) ? ',' . $opInfo['quantity'] : $opInfo['quantity'];
//                    $unit_price .= ($unit_price) ? ',' . $opInfo['unit_price'] : $opInfo['unit_price'];
//                    $skuInfo = Cache::store('goods')->getSkuInfo($opInfo['sku_id']);
//                    $data['goods_name'] = isset($skuInfo['spu_name']) ? $skuInfo['spu_name'] : '';
//                    $data['goods_name_cn'] = $opInfo['goods_name_cn'] ? $opInfo['goods_name_cn'] : '';
//                    $data['goods_name_en'] = $opInfo['goods_name_en'] ? $opInfo['goods_name_en'] : '';
//                    $data['declare_currency'] = $opInfo['declare_currency'] ? $opInfo['declare_currency'] : '';
//                }
//            } else {
//                $data['goods_name'] = '';
//                $data['goods_name_cn'] = '';
//                $data['goods_name_en'] = '';
//                $data['declare_currency'] = '';
//            }
//            $data['quantity'] = $quantity ? $quantity : '';
//            $data['unit_price'] = $unit_price ? $unit_price : '';
//            $temp = [];
//            foreach ($titleArr as $k => $v) {
//                $temp[$v] = $data[$v];
//            }
//            array_push($orderPackageInfo, $temp);
//            $i++;
//        }
//        return $orderPackageInfo;
//    }
    public function getExportData($page = 1, $pageSize = 20, $key, $titleArr, $shippingData, $countryList, $pagination = 0)
    {
        //查询承运公司下的订单包裹数量
        $condition = [];
        $conditionExp['p.shipping_time'] = ['gt', 0];
        $orderPackageInfo = [];
        //包裹物流方式过滤
        if (isset($shippingData['package_ids']) && !empty($shippingData['package_ids'])) {
            $condition['p.id'] = ['in', $shippingData['package_ids']];
        }
        //查询包裹数据
        $field = 'p.id as package_id,p.order_id,p.channel_id,p.channel_account_id,p.shipping_number,p.shipping_time,p.pay_time,p.number,p.package_weight,p.shipping_fee,p.providers_weight,p.providers_fee,p.shipping_id';
        if (empty($pagination)) {
            $dataList = Db::table('order_package')->alias('p')->field($field)->where($condition)->where($conditionExp)->order('p.id desc')->page($page, $pageSize)->select();
        } else {
            $dataList = Db::table('order_package')->alias('p')->field($field)->where($condition)->where($conditionExp)->order('p.id desc')->select();
        }
        $i = 1;
        //实例化模型
        $packageCollectionService = new PackageCollection();
        //查询订单数据
        $order_ids = array_column($dataList, 'order_id');
        //查询包裹订单数据
        $orderInfo = [];
        $orderData = Db::table('order')->field('id,buyer_id,country_code')->where(['id' => ['in',$order_ids]])->select();
        foreach ($orderData as $k => $v) {
            if (!isset($orderInfo[$v['id']])) {
                $orderInfo[$v['id']] = $v;
            }
        }
        //获取订单orderAddress 表的用户姓名和电话号码
        $orderAddressData = Db::table('order_address')->field('order_id,province,city,zipcode,address,consignee,tel')->where(['order_id' => ['in', $order_ids]])->select();
        //查询包裹订单地址数据
        $orderAddressInfo = [];
        foreach ($orderAddressData as $k => $v) {
            if (!isset($orderAddressInfo[$v['order_id']])) {
                $orderAddressInfo[$v['order_id']] = $v;
            }
        }
        $package_ids = array_column($dataList, 'package_id');
        $orderPackageDeclareInfo = [];
        $orderPackageDeclareData = Db::table('order_package_declare')->field('package_id,sku_id,goods_name_cn,goods_name_en,quantity,unit_price,declare_currency')->where(['package_id' => ['in', $package_ids]])->select();
        foreach ($orderPackageDeclareData as $k => $v){
            if (!isset($orderPackageDeclareInfo[$v['package_id']])) {
                $orderPackageDeclareInfo[$v['package_id']] = [];
                array_push($orderPackageDeclareInfo[$v['package_id']],$v);
            }else{
                array_push($orderPackageDeclareInfo[$v['package_id']],$v);
            }
        }
        foreach ($dataList as $k => $v) {
            $data['id'] = $i;
            $data['carrierCompany'] = $key ? $key : '';
            $data['shortname'] = $key ? $key : '';
            $data['collector_name'] = $shippingData['collector_name'][0] ? $shippingData['collector_name'][0] : '';
            //导出字段
            $data['shipping_time'] = $v['shipping_time'] ? date('Y-m-d H:i:s',$v['shipping_time']) : '';
            //获取执行发货人
            $send_name = $packageCollectionService->getHandoverNameByPackageId($v['package_id']);
            $data['sendpeople'] = $send_name;
            $data['pay_time'] = $v['pay_time'] ? date('Y-m-d H:i:s',$v['pay_time']) : '';
            //获取平台
            $title = Db::table('channel')->where('id' ,$v['channel_id'])->value('title');
            $data['channel_name'] = !empty($title) ? $title : '';
            //获取用户账号
            $order_service = new \app\order\service\OrderService();
            $accountName = $order_service->getAccountName($v['channel_id'], $v['channel_account_id']);
            $data['channel_account_id'] = !empty($v['channel_account_id']) ? $accountName : '';
            $data['shipping_number'] = !empty($v['shipping_number']) ? $v['shipping_number'] : '';

            //order 表查询
            if(isset($orderAddressInfo[$v['order_id']]) && !empty($orderAddressInfo[$v['order_id']])){
                $data['country_code'] = $orderInfo[$v['order_id']]['country_code'] ? $orderInfo[$v['order_id']]['country_code'] : '';
                $data['country_cn'] = isset($countryList[$orderInfo[$v['order_id']]['country_code']]['country_cn_name']) ? !empty($countryList[$orderInfo[$v['order_id']]['country_code']]['country_cn_name']) ? $countryList[$orderInfo[$v['order_id']]['country_code']]['country_cn_name'] : '' : '';
                $data['country_en'] = isset($countryList[$orderInfo[$v['order_id']]['country_code']]['country_en_name']) ? !empty($countryList[$orderInfo[$v['order_id']]['country_code']]['country_en_name']) ? $countryList[$orderInfo[$v['order_id']]['country_code']]['country_en_name'] : '' : '';
            }else{
                $data['country_code'] ='';
                $data['country_cn']='';
                $data['country_en']='';
            }

            //通过country_code 获取国家中英文名
            $data['number'] = $v['number'] ? $v['number'] : '';
            $data['package_weight'] = $v['package_weight'] ? $v['package_weight'] : '';
            $v['shipping_fee']=sprintf('%01.2f',$v['shipping_fee']);
            $data['shipping_fee'] = $v['shipping_fee'] ? $v['shipping_fee'] : '';
            $data['providers_weight'] = $v['providers_weight'] ? $v['providers_weight'] : '';
            $data['providers_fee'] = $v['providers_fee'] ? $v['providers_fee'] : '';

            //order_address 表查询
            //获取订单orderAddress 表的用户姓名和电话号码|
            if (isset($orderAddressInfo[$v['order_id']]) && !empty($orderAddressInfo[$v['order_id']])) {
                $data['buyer_name'] = $orderAddressInfo[$v['order_id']]['consignee'] ? $orderAddressInfo[$v['order_id']]['consignee'] : '';
                $data['buyer_phone'] = $orderAddressInfo[$v['order_id']]['tel'] ? $orderAddressInfo[$v['order_id']]['tel'] : '';
                $data['province'] = $orderAddressInfo[$v['order_id']]['province'] ? $orderAddressInfo[$v['order_id']]['province'] : '';
                $data['city_id'] = $orderAddressInfo[$v['order_id']]['city'] ? $orderAddressInfo[$v['order_id']]['city'] : '';
                $data['zipcode'] = $orderAddressInfo[$v['order_id']]['zipcode'] ? $orderAddressInfo[$v['order_id']]['zipcode'] : '';
                $data['address'] = $orderAddressInfo[$v['order_id']]['address'] ? $orderAddressInfo[$v['order_id']]['address'] : '';
            } else {
                $data['buyer_name'] = '';
                $data['buyer_phone'] = '';
                $data['province'] = '';
                $data['city_id'] = '';
                $data['zipcode'] = '';
                $data['address'] = '';
            }

            //order_package_declare
            $quantity = '';
            $unit_price = '';
            if (isset($orderPackageDeclareInfo[$v['package_id']]) && !empty($orderPackageDeclareInfo[$v['package_id']])) {
                foreach ($orderPackageDeclareInfo[$v['package_id']] as $op => $opInfo) {
                    $quantity .= ($quantity) ? ',' . $opInfo['quantity'] : $opInfo['quantity'];
                    $unit_price .= ($unit_price) ? ',' . $opInfo['unit_price'] : $opInfo['unit_price'];
                    $skuInfo = Cache::store('goods')->getSkuInfo($opInfo['sku_id']);
                    $data['goods_name'] = isset($skuInfo['spu_name']) ? $skuInfo['spu_name'] : '';
                    $data['goods_name_cn'] = $opInfo['goods_name_cn'] ? $opInfo['goods_name_cn'] : '';
                    $data['goods_name_en'] = $opInfo['goods_name_en'] ? $opInfo['goods_name_en'] : '';
                    $data['declare_currency'] = $opInfo['declare_currency'] ? $opInfo['declare_currency'] : '';
                }
            } else {
                $data['goods_name'] = '';
                $data['goods_name_cn'] = '';
                $data['goods_name_en'] = '';
                $data['declare_currency'] = '';
            }
            $data['quantity'] = $quantity ? $quantity : '';
            $data['unit_price'] = $unit_price ? $unit_price : '';
            $settlement_method = Db::name('shipping_method')->where(['id' => $v['shipping_id']])->value('settlement_method');
            if (!empty($settlement_method)) {
                $data['settlement_method'] = (new ShippingMethodService())->getSettlementMethod()[$settlement_method]['name'];
            } else {
                $data['settlement_method'] = '';
            }
            $temp = [];
            foreach ($titleArr as $k => $v) {
                $temp[$v] = $data[$v];
            }
            array_push($orderPackageInfo, $temp);
            $i++;
        }
        return $orderPackageInfo;
    }

    /**
     * 获取快递确认单包裹总重
     * @param array $condition
     * @param array $join
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function packageWeight(array $condition = [], array $join = [])
    {
        $shipping_method = new ShippingMethod();
        $whereTwo['p.shipping_time'] = ['gt', 0];
        $shippingMethod = $shipping_method->group("s.id")->alias('s')->field('count(p.id) as parcels_num,sum(p.shipping_fee) as shipping_fee,ROUND(sum(p.package_weight) / 1000, 2) as package_weight')->join($join)->where($condition)->where($whereTwo)->order('s.id asc')->select();
        $packageWeight = array_column($shippingMethod, 'package_weight');
        $packageWeight = sprintf('%01.2f',array_sum($packageWeight));
        $shippingFee = array_column($shippingMethod,'shipping_fee');
        $shippingFee = sprintf('%01.2f',array_sum($shippingFee));
        $parcelsNum = array_column($shippingMethod,'parcels_num');
        $parcelsNum = array_sum($parcelsNum);
        return ['packageWeight' => $packageWeight,'shippingFee' => $shippingFee, 'parcelsNum' => $parcelsNum];
    }
}