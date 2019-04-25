<?php
/**
 * Created by PhpStorm.
 * User: rondaful_user
 * Date: 2019/4/19
 * Time: 11:39
 */

namespace app\index\service;


use app\common\model\ebay\EbayAccount;
use app\common\model\ebay\EbayAccountPerformance;
use app\common\model\ebay\EbayDefectShip;
use app\common\model\ebay\EbayEdsShippingPolicy;
use app\common\model\ebay\EbayEpacketShippingPolicy;
use app\common\model\ebay\EbayLtnp;
use app\common\model\ebay\EbayNonshippingDefect;
use app\common\model\ebay\EbayPgcTracking;
use app\common\model\ebay\EbayQclist;
use app\common\model\ebay\EbaySellerInr;
use app\common\model\ebay\EbayShipPerformance;
use app\common\model\ebay\EbaySpakList;
use app\common\model\ebay\EbaySpakMisuse;
use app\common\model\ebay\EbayTci;
use app\common\model\ebay\EbayWarehousePerformance;
use app\common\model\internalLetter;
use app\common\service\UniqueQueuer;
use app\index\queue\EbayAccountPerformanceSyncQueue;
use app\publish\service\CommonService;
use service\ebay\EbayRestApi;
use think\Exception;

class EbayAccountPerformanceService
{
    //站点对应卖家等级文本
    private $siteSellerLevelTxt = ['--','Standard','AboveStandard','TopRated','BelowStandard'];
    //综合表现，货运表现，物流标准，海外仓标准，SpeedPAK物流等状态文本
    private $ltsSsEsWhsTxt = ['正常','超标','警告','限制',];
    //非货运表现状态文本
    private $nonShippingStatusTxt = [1=>'正常',2=>'超标',3=>'警告',4=>'限制'];
    //商业计划追踪状态文本
    private $pgcTrackingStatusTxt = ['正常','警告','限制'];
    //综合表现政策整体状态，不良交易率表现状态，纠纷表现状态文本
    private $ltnpStatusTxt = ['正常','超标','警告','限制','不考核'];
    //非货运表现状态文本
    private $tciTxt = [1=>'正常',2=>'警告',3=>'超标',4=>'限制'];


    /**
     * 获取整体状态
     * @param $params
     * @return array
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function globalStatusList($params)
    {
        $wh = [];
        $whOr = [];
        if (!empty($params['accountIds'])) {
            $accountIds = explode(',', $params['accountIds']);
            $wh['account_id'] = ['in',$accountIds];
        }
        if (!in_array($params['status'],['','0','1','2'])) {
            throw new Exception('账号表现过滤状态设置有误');
        } elseif ($params['status'] == 1) {//正常状态，所有的都要正常
            $tmpWh = [
                'global' => ['<',4],
                'us' => ['<',4],
                'uk_ie' => ['<',4],
                'de_ch_at' => ['<',4],
                'long_term_status' => ['not in',[1,2,3]],//综合表现
                'non_shipping_status' => ['not in',[2,3,4]],//非货运表现
                'shipping_status' => ['not in',[1,2,3]],//货运表现
                'edshipping_status' => ['not in',[1,2,3]],//物流标准
                'pgc_tracking_status' => ['not in',[1,2]],//商业计划追踪和评估
                'warehouse_status' => ['not in',[1,2,3]],//海外仓标准
            ];
            $wh = array_merge($wh,$tmpWh);
        } elseif ($params['status'] == 2) {//至少有一个不正常
            $whOr = function ($query) {
                $query->whereOr('global',4)
                    ->whereOr('us',4)
                    ->whereOr('uk_ie',4)
                    ->whereOr('de_ch_at',4)
                    ->whereOr('long_term_status','in',[1,2,3])
                    ->whereOr('non_shipping_status','in',[2,3,4])
                    ->whereOr('shipping_status','in',[1,2,3])
                    ->whereOr('edshipping_status','in',[1,2,3])
                    ->whereOr('pgc_tracking_status','in',[1,2])
                    ->whereOr('warehouse_status','in',[1,2,3]);
            };
        }
        if (empty($params['page']) || !intval($params['page'])) {
            $page = 1;
        } else {
            $page = (int)$params['page'];
        }
        if (empty($params['pageSize']) || !intval($params['pageSize'])) {
            $pageSize = 50;
        } else {
            $pageSize = (int)$params['pageSize'];
        }
        //确保每个账号只取最新的一条
        $list = EbayAccountPerformance::where($wh)->where($whOr)->page($page,$pageSize)->group('account_id')
            ->order('create_time desc')->select();
        if (!$list) {
            return [
                'data' => [],
                'count' => 0,
                'page' => $page,
                'pageSize' => $pageSize,
            ];
        }
        $count = EbayAccountPerformance::where($wh)->where($whOr)->group('account_id')->count();

        $list = collection($list)->toArray();
        //处理账号
        $accountIds = array_column($list,'account_id');
        $accountName = EbayAccount::whereIn('id',$accountIds)->column('account_name','id');
        foreach ($list as &$lt) {
            $lt['account_name'] = $accountName[$lt['account_id']]??'';
            $lt['global_txt'] = $this->siteSellerLevelTxt[$lt['global']];
            $lt['us_txt'] = $this->siteSellerLevelTxt[$lt['us']];
            $lt['uk_ie_txt'] = $this->siteSellerLevelTxt[$lt['uk_ie']];
            $lt['de_ch_at_txt'] = $this->siteSellerLevelTxt[$lt['de_ch_at']];
            $lt['long_term_status_txt'] = $this->ltsSsEsWhsTxt[$lt['long_term_status']]??'';
            $lt['shipping_status_txt'] = $this->ltsSsEsWhsTxt[$lt['shipping_status']]??'';//货运表现
            $lt['edshipping_status_txt'] = $this->ltsSsEsWhsTxt[$lt['edshipping_status']]??'';//物流标准
            $lt['warehouse_status_txt'] = $this->ltsSsEsWhsTxt[$lt['warehouse_status']]??'';//海外仓标准
            $lt['non_shipping_status_txt'] = $this->nonShippingStatusTxt[$lt['non_shipping_status']]??'';//非货运表现
            $lt['pgc_tracking_status_txt'] = $this->pgcTrackingStatusTxt[$lt['pgc_tracking_status']]??'';//商业计划追踪
            $lt['update_time'] = date('Y-m-d H:i:s',$lt['update_time']);
        }
        return [
            'data' => $list,
            'count' => $count,
            'page' => $page,
            'pageSize' => $pageSize,
        ];
    }


    /**
     * 批量同步
     * @param array $accountIds
     * @param string $policyType
     */
    public function sync($accountIds=[], $policyType='')
    {
        if (!$accountIds) {
            $wh = [
                'is_invalid' => 1,
                'account_status' => 1,
                'ot_invalid_time' => ['>',time()],//token有效
            ];
            $accountIds = EbayAccount::where($wh)->column('id');
        } elseif (!is_array($accountIds)) {
            $accountIds = [$accountIds];
        }
        foreach ($accountIds as $accountId) {
            $param = ['account_id'=>$accountId,'policy_type'=>$policyType];
            (new UniqueQueuer(EbayAccountPerformanceSyncQueue::class))->push($param);
        }
    }


    /**
     * 执行同步
     * @param $accountId
     * @param string $policyType
     */
    public function doSync($accountId, $policyType='')
    {
        /**
         * 各个接口返回的数据格式略有不同，大致分为两类
         * 第一类
         * {
         *   ...
         *   data:{
         *      //键值对
         *   }
         * }
         * 第二类
         * {
         *   ...
         *   data:{
         *     key1:value1,//这部分属于listings里面的元素共有的部分
         *     key2:value2,
         *     listings:[
         *          {},
         *          {},
         *     ]
         * }
         * 对于第一类只需要将键名从驼峰转换为下划线即可直接保存，
         * 对于第二类需要将共有的部分重新分配给listings的元素，然后再批量保存
         * $actions数组就是根据这种逻辑定义的
         * 键名：接口名称
         * model:对应的模型
         * updateFlag:判断是否进行更新的字段依据
         * shareData:共有的字段名
         * arrayData:数组字段名
         */
        $actions = [
            'accountOverview' => ['model' => EbayAccountPerformance::class,],
            'ltnp' => [
                'model' => EbayLtnp::class,
                'updateFlag' => ['refreshedDate'],
            ],
            'tci' => [
                'model' => EbayTci::class,
                'updateFlag' => ['refreshedDate']
            ],
            'nonShipping' => [
                'model' => EbayNonshippingDefect::class,
                'updateFlag' => ['reviewStartDate','reviewEndDate'],
                'shareData' => ['reviewStartDate','reviewEndDate'],
                'arrayData' => 'listings',
            ],
            'ship1to8' => [
                'model' => EbayShipPerformance::class,
                'updateFlag' => ['refreshedDate'],
            ],
            'ship5to12' => [
                'model' => EbayShipPerformance::class,
                'updateFlag' => ['refreshedDate'],
            ],
            'defectListingsShip1to8' => [
                'model' => EbayDefectShip::class,
                'updateFlag' => ['reviewStartDate','reviewEndDate'],
                'shareData' => ['reviewStartDate','reviewEndDate'],
                'arrayData' => 'listings',
            ],
            'defectListingsShip5to12' => [
                'model' => EbayDefectShip::class,
                'updateFlag' => ['reviewStartDate','reviewEndDate'],
                'shareData' => ['reviewStartDate','reviewEndDate'],
                'arrayData' => 'listings',
            ],
            'epacketShippingPolicy' => [
                'model' => EbayEpacketShippingPolicy::class,
                'updateFlag' => ['refreshedDate'],
            ],
            'edsShippingPolicy' => [
                'model' => EbayEdsShippingPolicy::class,
                'updateFlag' => ['refreshedDate'],
            ],
            'SPAKlistData' => [
                'model' => EbaySpakList::class,
                'updateFlag' => ['createPst'],
            ],
            'SPAKmisuseData' => [
                'model' => EbaySpakMisuse::class,
                'updateFlag' => ['createPst'],
                ],
            'acctList' => [
                'model' => EbayWarehousePerformance::class,
                'updateFlag' => ['createPst'],
                ],
            'pgcTracking' => [
                'model' => EbayPgcTracking::class,
                'updateFlag' => ['refreshedDate'],
                ],
            'qclist' => [
                'model' => EbayQclist::class,
                'updateFlag' => ['refreshedDate'],
                'shareData' => ['refreshedDate'],
                'arrayData' => 'qclist',
            ],
            'sellerInr' => [
                'model' => EbaySellerInr::class,
                'updateFlag' => ['refreshedDate'],
                'shareData' => ['refreshedDate'],
                'arrayData' => 'sellerINR',
                ],
        ];
    try {
        foreach ($actions as $ack => $acv) {
            if (in_array($policyType, array_keys($actions)) && $ack != $policyType) {
                continue;
            }
            $res = (new EbayRestApi(['account_id' => $accountId], 'gccbt', $ack))->sendRequest();
            if (empty($res) || !isset($res['ackValue']) || $res['ackValue'] != 'SUCCESS') {
                //错误信息记录日志
                $log = [
                    'accountId' => $accountId,
                    'errMsg' => $res['errorMessage'],
                    'remark' => '同步账号表现状态数据失败,请求方法：' . $ack,
                ];
                mongo('ebay_log')->insertOne($log);
                continue;
            }

            $data = $res['data'];
            if (!$data) {
                continue;
            }


            $wh = [];
            //查询是否有更新过
            if (isset($acv['updateFlag'])) {
                $wh['account_id'] = $accountId;
                foreach ($acv['updateFlag'] as $acuName) {
                    $wh[parseName($acuName)] = $data[$acuName];
                }
                if ($acv['model']::where($wh)->value('id')) {
                    continue;//已更新过
                }
            }
            $field = [];
            if (isset($acv['shareData'])) {//对象包含数组的形式,共享的数据一般是审核日期
                $lists = $data[$acv['arrayData']];
                foreach ($lists as $list) {
                    foreach ($acv['shareData'] as $acvSd) {
                        $list[$acvSd] = $data[$acvSd];
                    }
                    $tmp = [];
                    foreach ($list as $ltk => $ltv) {
                        if ($ltv === 'Y') {
                            $ltv = 1;
                        } elseif ($ltv === 'N') {
                            $ltv = 0;
                        } elseif (is_null($ltv)) {
                            continue;
                        }
                        $tmp[parseName($ltk)] = $ltv;
                    }
                    $tmp['account_id'] = $accountId;
                    $field[] = $tmp;
                }
            } else {
                foreach ($data as $dk => $dv) {
                    if ($dv === 'Y') {
                        $dv = 1;
                    } elseif ($dv === 'N') {
                        $dv = 0;
                    } elseif (is_null($dv)) {
                        continue;
                    }
                    $field[parseName($dk)] = $dv;
                }
                $field['account_id'] = $accountId;
                $field = [$field];
            }
            (new $acv['model'])->allowField(true)->saveAll($field);
        }
    } catch (\Throwable $e) {
        throw new Exception($e->getMessage().'.ack is '.$ack.',account id is '.$accountId);
    }
    }


    /**
     * 综合表现状态
     * @param $accountId
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function ltnp($accountId)
    {
        $list = EbayLtnp::where('account_id',$accountId)->order('id desc')->find();
        if (!$list) {
            return ['data' => []];
        }
        $list['program_status_lst_eval_txt'] = $this->ltnpStatusTxt[$list['program_status_lst_eval']]??'';//综合表现政策整体状态
        $list['status_lst_eval_txt'] = $this->ltnpStatusTxt[$list['status_lst_eval']]??'';//不良交易率表现状态
        $list['status_lt10_lst_eval_txt'] = $this->ltnpStatusTxt[$list['status_lt10_lst_eval']]??'';//小于等于10美金12月不良交易率状态
        $list['status_gt10_lst_eval_txt'] = $this->ltnpStatusTxt[$list['status_gt10_lst_eval']]??'';//大于10美金12月不良交易率状态
        $list['status_adj_lst_eval_txt'] = $this->ltnpStatusTxt[$list['status_adj_lst_eval']]??'';//综合12月不良交易率状态
        $list['status_wk_eval_txt'] = $this->ltnpStatusTxt[$list['status_wk_eval']]??'';//预期评价状态
        $list['status_lt10_wk_eval_txt'] = $this->ltnpStatusTxt[$list['status_lt10_wk_eval']]??'';//预期-小于等于10美金12月不良交易率状态
        $list['status_gt10_wk_eval_txt'] = $this->ltnpStatusTxt[$list['status_gt10_wk_eval']]??'';//预期-大于10美金12月不良交易率状态
        $list['status_adj_wk_eval_txt'] = $this->ltnpStatusTxt[$list['status_adj_wk_eval']]??'';//预期-综合12月不良交易率状态
        $list['snad_status_lst_eval_txt'] = $this->ltnpStatusTxt[$list['snad_status_lst_eval']]??'';//纠纷表现状态
        $list['snad_status_wk_eval_txt'] = $this->ltnpStatusTxt[$list['snad_status_wk_eval']]??'';//预期评价状态

        return ['data' => $list];
    }

    /**
     * 非货运表现
     * @param $accountId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function tci($accountId)
    {
        $list = EbayTci::where('account_id',$accountId)->order('id desc')->find();
        if (!$list) {
            return ['data' => []];
        }

        $list['result_txt'] = $this->tciTxt[$list['result']]??'';

        return ['data' => $list];
    }


    /**
     * 导致非货运表现问题刊登列表
     * @param $accountId
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public function nonShippingDefect($params)
    {
        $accountId = $params['account_id'];
        if (empty($params['page']) || !intval($params['page'])) {
            $page = 1;
        } else {
            $page = intval($params['page']);
        }
        if (empty($params['pageSize']) || !intval($params['pageSize'])) {
            $pageSize = 50;
        } else {
            $pageSize = intval($params['pageSize']);
        }

        //获取最新的日期
        $wh['account_id'] = $accountId;
        $lastReviewDate = EbayNonshippingDefect::where($wh)->order('id desc')
            ->field('review_start_date,review_end_date')->find();

        if ($lastReviewDate) {
            $wh['review_start_date'] = $lastReviewDate['review_start_date'];
            $wh['review_end_date'] = $lastReviewDate['review_end_date'];
        }

        $list = EbayNonshippingDefect::where($wh)->page($page,$pageSize)->select();
        if (!$list) {
            return [
                'data' => [],
                'count' => 0,
                'page' => 1,
                'pageSize' => 50,
            ];
        }
        $count = EbayNonshippingDefect::where($wh)->count();
        return [
            'data' => $list,
            'count' => $count,
            'page' => 1,
            'pageSize' => 50,
        ];
    }


    /**
     * 获取货运表现状态
     * @param $accountId
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public function ship($accountId)
    {
        $wh['account_id'] = $accountId;
        $wh['date_range'] = 0;
        $ship1to8 = EbayShipPerformance::where($wh)->order('id desc')->find();
        $wh['date_range'] = 1;
        $ship5to12 = EbayShipPerformance::where($wh)->order('id desc')->find();
        $list = [];
        if ($ship1to8) {
            $list[] = $ship1to8;
        }
        if ($ship5to12) {
            $list[] = $ship5to12;
        }

        if (!$list) {
            return ['data' => []];
        }
        foreach ($list as &$lt) {
            $lt['result_txt'] = $this->ltsSsEsWhsTxt[$lt['result']]??'';
        }
        return ['data' => $list];
    }


    /**
     * 获取货运问题刊登列表（1-8周）
     * @param $accountId
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public function shipDefect1to8($params)
    {

        $accountId = $params['account_id'];
        if (empty($params['page']) || !intval($params['page'])) {
            $page = 1;
        } else {
            $page = intval($params['page']);
        }
        if (empty($params['pageSize']) || !intval($params['pageSize'])) {
            $pageSize = 50;
        } else {
            $pageSize = intval($params['pageSize']);
        }
        $wh['account_id'] = $accountId;
        $wh['date_range'] = 0;
        //获取最新的日期
        $lastReviewDate = EbayDefectShip::where($wh)->order('id desc')
            ->field('review_start_date,review_end_date')->find();

        if ($lastReviewDate) {
            $wh['review_start_date'] = $lastReviewDate['review_start_date'];
            $wh['review_end_date'] = $lastReviewDate['review_end_date'];
        }

        $list = EbayDefectShip::where($wh)->page($page,$pageSize)->select();
        if (!$list) {
            return [
                'data' => [],
                'count' => 0,
                'page' => 1,
                'pageSize' => 50,
            ];
        }
        $count = EbayDefectShip::where($wh)->count();
        return [
            'data' => $list,
            'count' => $count,
            'page' => 1,
            'pageSize' => 50,
        ];
    }

    /**
     * 获取货运问题刊登列表（5-12周）
     * @param $accountId
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public function shipDefect5to12($params)
    {

        $accountId = $params['account_id'];
        if (empty($params['page']) || !intval($params['page'])) {
            $page = 1;
        } else {
            $page = intval($params['page']);
        }
        if (empty($params['pageSize']) || !intval($params['pageSize'])) {
            $pageSize = 50;
        } else {
            $pageSize = intval($params['pageSize']);
        }
        $wh['account_id'] = $accountId;
        $wh['date_range'] = 1;
        //获取最新的日期
        $lastReviewDate = EbayDefectShip::where($wh)->order('id desc')
            ->field('review_start_date,review_end_date')->find();

        if ($lastReviewDate) {
            $wh['review_start_date'] = $lastReviewDate['review_start_date'];
            $wh['review_end_date'] = $lastReviewDate['review_end_date'];
        }

        $list = EbayDefectShip::where($wh)->page($page,$pageSize)->select();
        if (!$list) {
            return [
                'data' => [],
                'count' => 0,
                'page' => 1,
                'pageSize' => 50,
            ];
        }
        $count = EbayDefectShip::where($wh)->count();
        return [
            'data' => $list,
            'count' => $count,
            'page' => 1,
            'pageSize' => 50,
        ];
    }


    /**
     * 获取物流标准政策表现
     * @param $accountId
     */
    public function shippingPolicy($accountId)
    {
        $epacketShipping = EbayEpacketShippingPolicy::where('account_id',$accountId)->order('id desc')->find();
        if ($epacketShipping) {
            $epacketShipping['e_packet_status_txt'] = $this->ltsSsEsWhsTxt[$epacketShipping['e_packet_status']] ?? '';
        }
        $edsShipping = EbayEdsShippingPolicy::where('account_id',$accountId)->order('id desc')->find();
        if ($edsShipping) {
            $edsShipping['eds_status_txt'] = $this->ltsSsEsWhsTxt[$edsShipping['eds_status']]??'';
        }
        return [
            'epacket_shipping'=>$epacketShipping ?: [],
            'eds_shipping' => $edsShipping ?: [],
        ];
    }

    /**
     * 获取speedPAK物流表现
     * @param $accountId
     */
    public function speedPak($accountId)
    {
        $listData = EbaySpakList::where('account_id',$accountId)->order('id desc')->find();
        if ($listData) {
            $listData['account_status_txt'] = $this->ltsSsEsWhsTxt[$listData['account_status']]??'';
        }
        $misuseData = EbaySpakMisuse::where('account_id',$accountId)->order('id desc')->find();
        if ($misuseData) {
            $misuseData['account_status_txt'] = $this->ltsSsEsWhsTxt[$misuseData['account_status']]??'';
        }
        return [
            'list_data'=> $listData ?: [],
            'misuse_data' => $misuseData ?: [],
        ];
    }

    /**
     * 下载SpeedPAK 物流管理方案及其他符合政策要求的物流服务使用状态相关交易
     * @param $accountId
     */
    public function speedPakListDownload($accountId)
    {
        $res = (new EbayRestApi(['account_id' => $accountId], 'gccbt', 'SPAKlistDownload'))->sendRequest();
        if (empty($res) || !isset($res['ackValue']) || $res['ackValue'] != 'SUCCESS') {
            throw new Exception($res['errorMessage']??'未知错误，请重试');
        }
        $data = $res['data'];
        if (!$data) {
           return ['messsage' => '没有需要下载的数据'];
        }
        $header = [
            '数据创建时间' => 'string',
            '交易号' => 'string',
            '刊登号' => 'string',
            '买家付款时间' => 'string',
            '买家路向' => 'string',
            '单价' => 'string',
            '单价货币币种' => 'string',
            '物品所在地' => 'string',
            '买家选择物流选项' => 'string',
            '买家选择物流类型' => 'string',
            '卖家上传的跟踪号' => 'string',
            '卖家填写的物流供应商' => 'string',
            '卖家使用的物流服务' => 'string',
            '揽收扫描时间' => 'string',
            '卖家承诺订单处理时间' => 'string',
            '买家是否选择SpeedPAK物流选项' => 'string',
            '是否使用SpeedPAK及以上服务' => 'string',
            '揽收扫描是否及时' => 'string',
            '卖家使用物流服务是否与买家选择相匹配' => 'string',
            '交易是否合格' => 'string',
        ];
        $fileInfo = [
            'file_name' => 'SpeedPAK 物流管理方案及其他符合政策要求的物流服务使用状态相关交易',
            'file_extension' => 'xlsx',
            'file_code' => date('YmdHis').rand(100000,999999),
            'path' => 'ebay',
            'type' => 'ebay_account_performance',
        ];
        $result = CommonService::xlsxwriterExport($header,$data,$fileInfo,0);
        if ($result === true) {
            return [
                'status' => 1,
                'message' => 'OK',
                'file_code' => $fileInfo['file_code'],
                'file_name' => $fileInfo['file_name'].'.'.$fileInfo['file_extension'],
            ];
        } else {
            throw new Exception($result);
        }
    }

    /**
     * 下载卖家设置SpeedPAK物流选项与实际使用物流服务不符表现相关交易
     * @param $accountId
     */
    public function speedPakMisuseDownload($accountId)
    {
        $res = (new EbayRestApi(['account_id' => $accountId], 'gccbt', 'SPAKmisuseDownload'))->sendRequest();
        if (empty($res) || !isset($res['ackValue']) || $res['ackValue'] != 'SUCCESS') {
            throw new Exception($res['errorMessage']??'未知错误，请重试');
        }
        $data = $res['data'];
        if (!$data) {
            return ['messsage' => '没有需要下载的数据'];
        }
        $header = [
            '数据创建时间' => 'string',
            '交易号' => 'string',
            '刊登号' => 'string',
            '买家付款时间' => 'string',
            '卖家上传的跟踪号' => 'string',
            '买家选择物流选项' => 'string',
            '卖家使用SpeedPAK服务类型' => 'string',
        ];
        $fileInfo = [
            'file_name' => '卖家设置SpeedPAK物流选项与实际使用物流服务不符表现相关交易',
            'file_extension' => 'xlsx',
            'file_code' => date('YmdHis').rand(100000,999999),
            'path' => 'ebay',
            'type' => 'ebay_account_performance',
        ];
        $result = CommonService::xlsxwriterExport($header,$data,$fileInfo,0);
        if ($result === true) {
            return [
                'status' => 1,
                'message' => 'OK',
                'file_code' => $fileInfo['file_code'],
                'file_name' => $fileInfo['file_name'].'.'.$fileInfo['file_extension'],
            ];
        } else {
            throw new Exception($result);
        }
    }

    /**
     * 获取海外仓标准表现状态
     * @param $accountId
     */
    public function acctList($accountId)
    {
        $list = EbayWarehousePerformance::where('account_id',$accountId)->order('id desc')->find();
        if ($list) {
            $list['action_status_txt'] = $this->ltsSsEsWhsTxt[$list['action_status']] ?? '';
        }
        return ['data' => $list ?: []];
    }

    /**
     * 下载海外仓服务标准政策相关交易
     * @param $accountId
     */
    public function warehouseDownload($accountId)
    {
        $res = (new EbayRestApi(['account_id' => $accountId], 'gccbt', 'transactionDownload'))->sendRequest();
        if (empty($res) || !isset($res['ackValue']) || $res['ackValue'] != 'SUCCESS') {
            throw new Exception($res['errorMessage']??'未知错误，请重试');
        }
        $data = $res['data'];
        if (!$data) {
            return ['messsage' => '没有需要下载的数据'];
        }
        $header = [
            '评估日期' => 'string',
            '刊登ID' => 'string',
            '交易ID' => 'string',
            '刊登物品所在地' => 'string',
            '物流不良交易' => 'string',
            '及时发货不达标' => 'string',
            '物品及时送达不达标' => 'string',
        ];
        $fileInfo = [
            'file_name' => '海外仓服务标准政策相关交易',
            'file_extension' => 'xlsx',
            'file_code' => date('YmdHis').rand(100000,999999),
            'path' => 'ebay',
            'type' => 'ebay_account_performance',
        ];
        $result = CommonService::xlsxwriterExport($header,$data,$fileInfo,0);
        if ($result === true) {
            return [
                'status' => 1,
                'message' => 'OK',
                'file_code' => $fileInfo['file_code'],
                'file_name' => $fileInfo['file_name'].'.'.$fileInfo['file_extension'],
            ];
        } else {
            throw new Exception($result);
        }
    }

    /**
     * 获取商业追踪计划表现状态
     * @param $accountId
     */
    public function pgcTracking($accountId)
    {
        $list = EbayPgcTracking::where('account_id',$accountId)->order('id desc')->find();
        if ($list) {
            $list['pgc_status_txt'] = $this->pgcTrackingStatusTxt[$list['pgc_status']]??'';
        }
        return ['data' => $list ?: []];
    }

    /**
     * 获取待处理刊登接口
     * @param $accountId
     */
    public function qclist($params)
    {
        if (empty($params['page']) || !intval($params['page'])) {
            $page = 1;
        } else {
            $page = intval($params['page']);
        }
        if (empty($params['pageSize']) || !intval($params['pageSize'])) {
            $pageSize = 50;
        } else {
            $pageSize = intval($params['pageSize']);
        }
        $wh['account_id'] = $params['account_id'];
        $lastRefreshedDate = EbayQclist::where($wh)->order('id desc')->value('refreshed_date');
        $wh['refreshed_date'] = $lastRefreshedDate;

        $lists = EbayQclist::where($wh)->page($page,$pageSize)->select();
        if (!$lists) {
            return [
                'data' => [],
                'count' => 0,
                'page' => $page,
                'pageSize' => $pageSize,
            ];
        }
        $count = EbayQclist::where($wh)->count();
        return [
            'data' => [],
            'count' => $count,
            'page' => $page,
            'pageSize' => $pageSize,
        ];
    }

    /**
     * 获取买家未收到物品提醒列表
     * @param $accountId
     */
    public function sellerInr($params)
    {
        if (empty($params['page']) || !intval($params['page'])) {
            $page = 1;
        } else {
            $page = intval($params['page']);
        }
        if (empty($params['pageSize']) || !intval($params['pageSize'])) {
            $pageSize = 50;
        } else {
            $pageSize = intval($params['pageSize']);
        }
        $wh['account_id'] = $params['account_id'];
        $lastRefreshedDate = EbaySellerInr::where($wh)->order('id desc')->value('refreshed_date');
        $wh['refreshed_date'] = $lastRefreshedDate;

        $lists = EbaySellerInr::where($wh)->page($page,$pageSize)->select();
        if (!$lists) {
            return [
                'data' => [],
                'count' => 0,
                'page' => $page,
                'pageSize' => $pageSize,
            ];
        }
        $count = EbaySellerInr::where($wh)->count();
        return [
            'data' => [],
            'count' => $count,
            'page' => $page,
            'pageSize' => $pageSize,
        ];
    }


}