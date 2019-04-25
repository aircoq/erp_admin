<?php

namespace app\publish\service;

use app\common\cache\Cache;
use app\common\exception\JsonConfirmException;
use app\common\exception\JsonErrorException;
use app\common\model\ebay\EbayShipping;
use app\common\model\walmart\WalmartCarrier;
use app\common\model\wish\WishPlatformShippingCarriers;
use app\common\model\ebay\EbayShippingService;
use app\common\model\aliexpress\AliexpressShippingMethod;
use app\common\model\Carrier;
use app\common\model\ShippingMethod;
use app\common\service\ChannelAccountConst;
use app\goods\service\GoodsHelp;
use app\index\service\User;
use app\index\service\Department as DepartmentService;
use app\purchase\service\SupplierBalanceType;
use think\Exception;
use app\warehouse\service\StockRuleExecuteService;
use think\Db;
use think\Request;
use app\common\model\User as UserModel;
use app\order\service\OrderRuleCheckService;

/** 订单规则来源信息
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2017/2/27
 * Time: 13:53
 */
class Resource
{
    /** 读取资源
     * @param $code
     * @return array
     */
    public function read($code)
    {
        $result = [];
        if (method_exists($this, $code)) {
            $result = $this->$code();
        }
        if (!isset($result['title'])) {
            $result['title'] = "";
        }
        if (!isset($result['profile'])) {
            $result['profile'] = "";
        }
        return $result;
    }

    /**
     * @title 读取设置值【解析后的值】
     * @param $data
     * @return array
     */
    public function getValue($data)
    {
        $code = $data['item_source'];
        $all = $this->read($code);
        $message = '';
        switch ($code) {
            case 'goodsCategory':
                foreach ($data['item_value'] as $k => $v) {
                    foreach ($v['value'] as $k2 => $v2) {
                        $message .= Cache::store('Category')->getFullNameById($v2, '') . ',';
                    }
                }
                break;
            case 'status':
                $alls = $this->arrayChangeKey($all['data']);
                foreach ($data['item_value'] as $k => $v) {
                    $message .= $alls['sta0']['condition'][$v['operator']];
                }
                break;
            case 'relationship':
                $alls = $this->arrayChangeKey($all['data']);
                foreach ($data['item_value'] as $k => $v) {
                    $message .= $alls[$v['key']]['title'];
                }
                break;
            default:
                $alls = $this->arrayChangeKey($all['data']);
                foreach ($data['item_value'] as $k => $v) {
                    $child = '';
                    if (isset($v['election']) && $v['election'] == 1) {
                        $message .= '[反选]';
                    }
                    if (isset($v['operator']) && !isset($v['operator']['sel']) && $v['operator']) {
                        if (!is_array($v['value'])) {
                            $message .= $v['operator'] . $v['value'];
                        }
                    } else if ($all['type'] == 3) {
                        $child .= $v['value'];
                    } else if ($v['key'] == 'str') {
                        $child .= $v['value'];
                    }

                    if ($v['child']) {
                        $child = ':';
                        $childs = $this->arrayChangeKey($alls[$v['key']]['child']);
                        foreach ($v['child'] as $k2 => $v2) {
                            if (isset($v2['election']) && $v2['election'] == 1) {
                                $child .= '反选';
                            }
                            if($code == 'source'){
                                try{
                                    $child .= $childs[$v2['key']]['title'] . ',';
                                }catch (\Exception $e){
                                    $accountName = Cache::store('Channel')->getAccountIdByWhere( $v['key'],['id'=>$v2['key']])[0]['code'] ?? '';
//                                    $msg = $alls[$v['key']]['title'].'平台账号'.$v2['key'].$accountName.'等已经停用，是否继续执行保存?';
                                    $msg = '该规则存在已停用账号，是否继续执行保存？';
                                    $request = Request::instance();
                                    $reconfirm = $request->param('reconfirm',0);
                                    if($reconfirm == 1){
                                        $child .= $accountName.',';
                                    }else{
                                        throw  new JsonErrorException($msg);
                                    }
                                }
                            }else{
                                $child .= $childs[$v2['key']]['title'] . ',';
                            }

                        }
                        $child = trim($child, ',');
                    }
                    $message .= $alls[$v['key']]['title'] . $child . ',';
                    if ($message == ',') {
                        if (is_array($v['value'])) {
                            foreach ($v['value'] as $k2 => $v2) {
                                $message .= $v2 . ',';
                            }
                        } else {
                            $message .= $v['value'] . ',';
                        }
                    }
                }

        }

        return trim($message, ',');
    }

    /**
     * 更换二维数组的key值
     * @param $old
     * @param string $keys
     * @return array
     */
    public function arrayChangeKey($old, $keys = 'key')
    {
        $new = [];
        foreach ($old as $key => $vol) {
            $new[$vol[$keys]] = $vol;
        }
        return $new;
    }

    /*
     *获取ebay订单来源
     */
    private function source_ebay(){
        return $this->source_part(ChannelAccountConst::channel_ebay);
    }

    /*
     *获取aliexpress订单来源
     */
    private function source_aliexpress(){
        return $this->source_part(ChannelAccountConst::channel_aliExpress);
    }

    /*
     *获取amazon订单来源
     */
    private function source_amazon(){
        return $this->source_part(ChannelAccountConst::channel_amazon);
    }

    /** 获取订单来源
     * @return mixed
     * @throws \think\Exception
     */
    private function source_part($channel_id)
    {
        $result['type'] = 1;
//        $channelList = Cache::store('channel')->getChannel();
        $channelList = Db::table('channel')->field('id, name')->where(['status' => 0, 'id' => $channel_id])->find();
//        $where[] = ['status', '==', 0];
//        $channelList = Cache::filter($channelList, $where, "id,name");
        $new_array = [];
        $accountLists = Cache::store('account')->getAccountsByBase(true,[$channelList['id']]);

//        foreach ($channelList as $k => $v) {
            $temp = [
                'type' => '',    //表示用父类的
                'key' => "" . $channelList['id'],
                'title' => $channelList['name'],
                'condition' => [],
                'value' => [],
                'unit' => '',
                'url' => '',
                'group' => 'channel',
                'child' => []
            ];
            $siteList = Cache::store('channel')->getSite($channelList['name']);
            foreach ($siteList as $s => $si) {
                $site = [
                    'type' => '',    //表示用父类的
                    'key' => $si['code'],
                    'title' => $si['name'],
                    'condition' => [],
                    'value' => [],
                    'unit' => '',
                    'url' => '',
                    'group' => 'site',
                    'child' => []
                ];
                array_push($temp['child'], $site);
            }

            $accountList = isset($accountLists[$channelList['name']]) ? $accountLists[$channelList['name']] : [];
            foreach ($accountList as $a => $ac) {
                if (!empty($ac)) {
                    $account = [
                        'type' => '',    //表示用父类的
                        'key' => "" . $ac['id'],
                        'title' => $ac['code'],
                        'condition' => [],
                        'value' => [],
                        'unit' => '',
                        'url' => '',
                        'group' => 'account',
                        'child' => []
                    ];
                    array_push($temp['child'], $account);
                }
            }
            array_push($new_array, $temp);
//        }
        $result['data'] = $new_array;
        $result['election'] = 0;
        $result['election_title'] = '勾选此处，表示当前规则不适用于下方所选账号（站点不参与此反选设置）';
        return $result;
    }

    /** 获取订单来源
     * @return mixed
     * @throws \think\Exception
     */
    private function source()
    {
        $result['type'] = 1;
        $channelList = Cache::store('channel')->getChannel();
        $where[] = ['status', '==', 0];
        $channelList = Cache::filter($channelList, $where, "id,name");
        $channel = array_column($channelList, 'id');
        $new_array = [];
        $accountLists = Cache::store('account')->getAccountsByBase(true,$channel);
        foreach ($channelList as $k => $v) {
            $temp = [
                'type' => '',    //表示用父类的
                'key' => "" . $v['id'],
                'title' => $v['name'],
                'condition' => [],
                'value' => [],
                'unit' => '',
                'url' => '',
                'group' => 'channel',
                'child' => []
            ];
            $siteList = Cache::store('channel')->getSite($v['name']);
            foreach ($siteList as $s => $si) {
                $site = [
                    'type' => '',    //表示用父类的
                    'key' => $si['code'],
                    'title' => $si['name'],
                    'condition' => [],
                    'value' => [],
                    'unit' => '',
                    'url' => '',
                    'group' => 'site',
                    'child' => []
                ];
                array_push($temp['child'], $site);
            }

            $accountList = isset($accountLists[$v['name']]) ? $accountLists[$v['name']] : [];
            foreach ($accountList as $a => $ac) {
                if (!empty($ac)) {
                    $account = [
                        'type' => '',    //表示用父类的
                        'key' => "" . $ac['id'],
                        'title' => $ac['code'],
                        'condition' => [],
                        'value' => [],
                        'unit' => '',
                        'url' => '',
                        'group' => 'account',
                        'child' => []
                    ];
                    array_push($temp['child'], $account);
                }
            }
            array_push($new_array, $temp);
        }
        $result['data'] = $new_array;
        $result['election'] = '勾选此处，表示当前规则不适用于下方所选账号（站点不参与此反选设置）';
        return $result;
    }


    /** 获取发货仓库
     * @return mixed
     * @throws \think\Exception
     */
    private function warehouse()
    {
        $result['type'] = 1;
        $wareHouseList = Cache::store('warehouse')->getWarehouse();
        $wareHouseList = Cache::filter($wareHouseList, [], 'id,name');
        $new_array = [];
        foreach ($wareHouseList as $k => $v) {
            $temp = [
                'type' => '',
                'key' => "" . $v['id'],
                'title' => $v['name'],
                'condition' => [],
                'value' => [],
                'unit' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ];
            array_push($new_array, $temp);
        }
        $result['data'] = $new_array;
        return $result;
    }

    /** 获取活动
     * @return mixed
     * @throws \think\Exception
     */
    private function activity()
    {
        $result['type'] = 1;
        $new_array = [];

        $temp = [
            'type' => '',
            'key' => "1",
            'title' => '预售商品部分付款',
            'condition' => [],
            'value' => [],
            'unit' => '',
            'url' => '',
            'group' => '',
            'child' => []
        ];
        array_push($new_array, $temp);

        $result['data'] = $new_array;
        return $result;

    }

    /** 获取关键词
     * @return mixed
     * @throws \think\Exception
     */
    private function keywords()
    {
        $result['type'] = '';
        $result['title'] = "多个关键词之间的关系(不选默认为或)：";
        $result['data'] = [];
        $temp = [
            0 => [
                'type' => 2,
                'key' => '&',
                'title' => '与',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            1 => [
                'type' => 2,
                'key' => '||',
                'title' => '或',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            2 => [
                'type' => 3,
                'key' => 'keywords',
                'title' => '',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '请输入关键词(多个关键词用英文逗号隔空)',
                'url' => '',
                'group' => '',
                'child' => []
            ]
        ];
        $result['data'] = $temp;
        return $result;
    }

    /** 运输方式
     * @return mixed
     * @throws \think\Exception
     */
    private function transport()
    {
        $result['type'] = 1;
        $channelList = Cache::store('channel')->getChannel();
        $new_array = [];
        foreach ($channelList as $k => $v) {
            $temp = [
                'type' => '',    //表示用父类的
                'key' => "" . $v['id'],
                'title' => $v['title'],
                'condition' => [],
                'value' => [],
                'unit' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ];
            switch ($v['id']) {
                case ChannelAccountConst::channel_ebay:
                    $ebayShipping = new EbayShipping();
                    $ebayShippingList = $ebayShipping->field('shippingservice,description')->select();
                    foreach ($ebayShippingList as $w => $ww) {
                        $shipping = [
                            'type' => '',    //表示用父类的
                            'key' => $ww['shippingservice'],
                            'title' => $ww['description'],
                            'condition' => [],
                            'value' => [],
                            'unit' => '',
                            'url' => '',
                            'group' => '',
                            'child' => []
                        ];
                        array_push($temp['child'], $shipping);
                    }
                    break;
                case ChannelAccountConst::channel_amazon:
                    $shipping = [
                        0 => [
                            'type' => '',
                            'key' => 'Standard',
                            'title' => 'Standard',
                            'condition' => [],
                            'value' => [],
                            'unit' => '',
                            'url' => '',
                            'group' => '',
                            'child' => []
                        ],
                        1 => [
                            'type' => '',
                            'key' => 'Expedited',
                            'title' => 'Expedited',
                            'condition' => [],
                            'value' => [],
                            'unit' => '',
                            'url' => '',
                            'group' => '',
                            'child' => []
                        ],
                        2 => [
                            'type' => '',
                            'key' => '其他',
                            'title' => '其他',
                            'condition' => [],
                            'value' => [],
                            'unit' => '',
                            'url' => '',
                            'group' => '',
                            'child' => []
                        ]
                    ];
                    $temp['child'] = $shipping;
                    break;
                case ChannelAccountConst::channel_wish:
                    $wishShipping = new WishPlatformShippingCarriers();
                    $wishShippingList = $wishShipping->field('shipping_carriers')->select();
                    foreach ($wishShippingList as $w => $ww) {
                        $shipping = [
                            'type' => '',    //表示用父类的
                            'key' => $ww['shipping_carriers'],
                            'title' => $ww['shipping_carriers'],
                            'condition' => [],
                            'value' => [],
                            'unit' => '',
                            'url' => '',
                            'group' => '',
                            'child' => []
                        ];
                        array_push($temp['child'], $shipping);
                    }
                    break;
                case ChannelAccountConst::channel_aliExpress:
                    $aliExpressShipping = new AliexpressShippingMethod();
                    $aliExpressShippingList = $aliExpressShipping->field('shipping_name')->select();
                    foreach ($aliExpressShippingList as $w => $ww) {
                        $shipping = [
                            'type' => '',    //表示用父类的
                            'key' => $ww['shipping_name'],
                            'title' => $ww['shipping_name'],
                            'condition' => [],
                            'value' => [],
                            'unit' => '',
                            'url' => '',
                            'group' => '',
                            'child' => []
                        ];
                        array_push($temp['child'], $shipping);
                    }
                    break;
                case ChannelAccountConst::channel_CD:
                    break;
                case ChannelAccountConst::channel_Lazada:
                    break;
                case ChannelAccountConst::channel_Shopee:
                    $list = ShippingMethod::where('carrier_id', 124)->where('status', 1)->group('code')->select();
                    foreach ($list as $v) {
                        $shipping = [
                            'type' => '',    //表示用父类的
                            'key' => $v['code'],
                            'title' => $v['code'],
                            'condition' => [],
                            'value' => [],
                            'unit' => '',
                            'url' => '',
                            'group' => '',
                            'child' => []
                        ];
                        array_push($temp['child'], $shipping);
                    }
                    break;
                case ChannelAccountConst::channel_Distribution:
                    $list = (new \app\warehouse\service\ShippingMethod())->getInfoForDistribution();
                    foreach ($list as $v) {
                        $shipping = [
                            'type' => '',    //表示用父类的
                            'key' => $v['code'],
                            'title' => $v['shortname'],
                            'condition' => [],
                            'value' => [],
                            'unit' => '',
                            'url' => '',
                            'group' => '',
                            'child' => []
                        ];
                        array_push($temp['child'], $shipping);
                    }
                    break;
                case ChannelAccountConst::channel_Walmart:
                    $walmartShipping = new WalmartCarrier();
                    $walmartShippingList = $walmartShipping->field('shipping_carrier,description')->select();
                    foreach ($walmartShippingList as $w => $ww) {
                        $shipping = [
                            'type' => '',    //表示用父类的
                            'key' => $ww['shipping_carrier'],
                            'title' => $ww['description'],
                            'condition' => [],
                            'value' => [],
                            'unit' => '',
                            'url' => '',
                            'group' => '',
                            'child' => []
                        ];
                        array_push($temp['child'], $shipping);
                    }
                    break;
            }
            array_push($new_array, $temp);
        }
        $result['data'] = $new_array;
        return $result;
    }

    /**
     * 运输方式为
     * @return mixed
     * @throws \think\Exception
     */
    private function appointShipping()
    {
        $result['type'] = 11;
        $result['title'] = '搜索并指定运输方式';
        $service = new \app\warehouse\service\ShippingMethod();
        $lists = $service->getListForOrder(0);
        $condition = [];
        foreach ($lists as $k => $v) {
            $temp = [];
            $temp['value'] = $v['shipping_method_id'];
            $temp['label'] = $v['carrier_name'] . '>>' . $v['shortname'];
            array_push($condition, $temp);
        }
        $temp = [
            0 => [
                'type' => '',    //表示用父类的
                'key' => "",
                'title' => '',
                'condition' => $condition,
                'value' => [],
                'unit' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ]
        ];
        $result['data'] = $temp;
        $result['election'] = '勾选此处，表示当前规则不适用于下方所选运输方式';
        return $result;
    }

    /** 关系
     * @return mixed
     */
    private function relationship()
    {
        $result['type'] = 2;
        $result['data'] = [];
        $temp = [
            0 => [
                'type' => '',
                'key' => 'relO',
                'title' => '订单包含多个交易（且运输类型完全相同 ） 或者 订单仅包含一个交易',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            1 => [
                'type' => '',
                'key' => 'relT',
                'title' => '订单包含多个交易（且运输类型不完全相同 ）',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ]
        ];
        $result['data'] = $temp;
        return $result;
    }

    /** 目的地
     * @return mixed
     * @throws \think\Exception
     */
    private function target()
    {
        $result['type'] = 1;
        $countryList = Cache::store('country')->getCountry();
        $new_array = [];
        $result['data'] = [];
        foreach ($countryList as $k => $v) {
            $temp['country_code'] = $v['country_code'];
            $temp['country_cn_name'] = $v['country_cn_name'];
            if (!isset($new_array[$v['zone_code']])) {
                $new_array[$v['zone_code']] = [];
            }
            array_push($new_array[$v['zone_code']], $temp);
        }
        $code = '';
        foreach ($new_array as $k => $v) {
            $temp = [
                'type' => '',
                'key' => "" . $k,
                'title' => $k,
                'condition' => [],
                'value' => [],
                'unit' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ];
            foreach ($v as $kk => $vv) {
                if (in_array($vv['country_code'], ['UK', 'GB'])) {
                    $code = $vv['country_code'];
                }
                $target = [
                    'type' => '',
                    'key' => $vv['country_code'],
                    'title' => empty($code) ? $vv['country_cn_name'] : $vv['country_cn_name'] . '(' . $code . ')',
                    'condition' => [],
                    'value' => [],
                    'unit' => '',
                    'url' => '',
                    'group' => '',
                    'child' => []
                ];
                array_push($temp['child'], $target);
            }
            array_push($result['data'], $temp);
        }
        return $result;
    }

    /** 省
     * @return mixed
     */
    private function province()
    {
        $result['type'] = 3;
        $result['data'] = [];
        $temp = [
            'type' => '',
            'key' => 'pro',
            'title' => '指定要在地址(仅省/州名称)中查找的字符：',
            'condition' => [],
            'value' => [],
            'unit' => '',
            'desc' => '',
            'url' => '',
            'group' => '',
            'child' => []
        ];
        array_push($result['data'], $temp);
        return $result;
    }

    /** 市
     * @return mixed
     */
    private function city()
    {
        $result['type'] = 3;
        $result['data'] = [];
        $temp = [
            'type' => '',
            'key' => 'city',
            'title' => '指定要在地址(仅城市名称)中查找的字符：',
            'condition' => [],
            'value' => [],
            'unit' => '',
            'desc' => '',
            'url' => '',
            'group' => '',
            'child' => []
        ];
        array_push($result['data'], $temp);
        return $result;
    }

    /** 订单发货地址街道
     * @return mixed
     */
    private function street()
    {
        $result['type'] = 3;
        $result['data'] = [];
        $temp = [
            'type' => '',
            'key' => 'str',
            'title' => '指定要在地址(仅街道1+街道2，不含国家省市信息)中查找的字符：',
            'condition' => [],
            'value' => [],
            'unit' => '',
            'desc' => '',
            'url' => '',
            'group' => '',
            'child' => []
        ];
        array_push($result['data'], $temp);
        return $result;
    }

    /** 订单发货地址街道 长度 判断
     * @return mixed
     */
    private function streetLength()
    {
        $result['type'] = 3;
        $result['data'] = [];
        $temp = [
            'type' => '',
            'key' => 'strL',
            'title' => '订单地址信息字符长度：',
            'condition' => [
                '<' => '<'
            ],
            'value' => [],
            'unit' => '',
            'desc' => '*此处地址信息由街道1与街道2合并组成，不包含国家、省州、城市信息。',
            'url' => '',
            'group' => '',
            'child' => []
        ];
        array_push($result['data'], $temp);
        return $result;
    }

    /**
     * 订单发货地址街道1中前4位是否包含数值
     * @return mixed
     */
    private function streetLengthIncludeNumber()
    {
        $result['type'] = 2;
        $result['title'] = "订单发货地址街道1中前4位是否包含数值";
        $temp = [
            0 => [
                'type' => '',
                'key' => 'strLINO',
                'title' => '是',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            1 => [
                'type' => '',
                'key' => 'strLINT',
                'title' => '否',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ]
        ];
        $result['data'] = $temp;
        return $result;
    }

    /** 邮编
     * @return mixed
     */
    private function zipCode()
    {
        $result['type'] = 1;
        $result['title'] = '订单邮编至少符合以下选中的条件中的任意一个条件（多个以 英文逗号 隔开）：';
        $temp = [
            0 => [
                'type' => 5,
                'key' => 'zipO',
                'title' => '以**开头',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            1 => [
                'type' => 5,
                'key' => 'zipT',
                'title' => '包含',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ]
        ];
        $result['data'] = $temp;
        $result['profile'] = '';
        return $result;
    }

    /**
     *  邮编地址长度
     */
    private function zipCodeLength()
    {
        $result['type'] = 1;
        $result['title'] = '订单收货邮编字符长度满足以下选中的条件：';
        $temp = [
            0 => [
                'type' => 5,  //表示复选框+文本框输入
                'key' => 'zipLO',
                'title' => '',
                'condition' => [
                    '>=' => '≥',
                    '>' => '>',
                    '=' => '='
                ],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            1 => [
                'type' => 5,
                'key' => 'zipLT',
                'title' => '',
                'condition' => [
                    '<' => '<',
                    '<=' => '≤',
                ],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ]
        ];
        $result['data'] = $temp;
        $result['profile'] = '';
        return $result;
    }

    /** 电话
     * @return mixed
     */
    private function mobile()
    {
        $result['type'] = 1;
        $result['title'] = '订单收件人电话符合以下选中的条件：';
        $temp = [
            0 => [
                'type' => 5,
                'key' => 'mobO',
                'title' => '移动电话可读字符长度',
                'condition' => [
                    '<=' => '≤'
                ],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            1 => [
                'type' => 5,
                'key' => 'mobT',
                'title' => '固定电话可读字符长度',
                'condition' => [
                    '<=' => '≤'
                ],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ]
        ];
        $result['data'] = $temp;
        $result['profile'] = '';
        return $result;
    }

    /** 订单收件人姓名/地址  是否存在异常
     * @return mixed
     */
    private function abnormal()
    {
        $result['type'] = 1;
        $result['title'] = '本条件用于筛选异常状况，以下条件符合任何一项，即认为符合本条件。';
        $temp = [
            0 => [
                'type' => 5,
                'key' => 'abnO',
                'title' => '姓名字符中空格数',
                'condition' => [
                    '<' => '<'
                ],
                'value' => [],
                'unit' => '',
                'desc' => '俄罗斯邮政要求收件人地址为全名，此处可输入2 ',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            1 => [
                'type' => 5,
                'key' => 'abnT',
                'title' => '姓名字符数',
                'condition' => [
                    '<' => '<'
                ],
                'value' => [],
                'unit' => '',
                'desc' => '输入1时，相当于收件人姓名为空',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            2 => [
                'type' => 5,
                'key' => 'abnR',
                'title' => '地址字符数',
                'condition' => [
                    '<' => '<'
                ],
                'value' => [],
                'unit' => '',
                'desc' => '地址1+地址2的总字符长度 ',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            3 => [
                'type' => 5,
                'key' => 'abnH',
                'title' => '城市名字字符数',
                'condition' => [
                    '<' => '<'
                ],
                'value' => [],
                'unit' => '',
                'desc' => '输入1时，相当于城市名称为空  ',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            4 => [
                'type' => 5,
                'key' => 'abnF',
                'title' => '省/州名字字符数',
                'condition' => [
                    '<' => '<'
                ],
                'value' => [],
                'unit' => '',
                'desc' => '输入1时，相当于省州名称为空  ',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            5 => [
                'type' => 5,
                'key' => 'abnS',
                'title' => '邮编字符数',
                'condition' => [
                    '<' => '<'
                ],
                'value' => [],
                'unit' => '',
                'desc' => '输入1时，相当于邮编为空 ',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            6 => [
                'type' => 5,
                'key' => 'abnE',
                'title' => '电话号码数字字符个数',
                'condition' => [
                    '<' => '<'
                ],
                'value' => [],
                'unit' => '',
                'desc' => '电话、手机两个号码必须都小于该值才认为该条件成立 ',
                'url' => '',
                'group' => '',
                'child' => []
            ]
        ];
        $result['data'] = $temp;
        $result['profile'] = '';
        return $result;
    }

    /** 订单总金额  范围
     * @return mixed
     */
    private function totalAmount()
    {
        $result['type'] = 1;
        $result['title'] = '总金额满足以下条件：';
        $temp = [
            0 => [
                'type' => 1,
                'key' => 'totO',
                'title' => '',
                'value' => [],
                'unit' => '',
                'desc' => '默认转换币种为人民币',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            1 => [
                'type' => 5,
                'key' => 'totT',
                'title' => '',
                'condition' => [
                    '>=' => '≥',
                    '>' => '>',
                    '=' => '='
                ],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            2 => [
                'type' => 5,
                'key' => 'totH',
                'title' => '',
                'condition' => [
                    '<' => '<',
                    '<=' => '≤',
                ],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ]
        ];
        $temp[0]['condition'] = OrderRuleCheckService::setCurrency();
        $result['data'] = $temp;
        return $result;
    }

    /** 订单利润
     * @return mixed
     */
    private function profits()
    {
        $result['type'] = 1;
        $result['title'] = '订单利润满足以下条件：';
        $temp = [
            0 => [
                'type' => 1,
                'key' => 'proO',
                'title' => '',
                'value' => [],
                'unit' => '',
                'desc' => '默认转换币种为人民币',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            1 => [
                'type' => 5,
                'key' => 'proT',
                'title' => '',
                'condition' => [
                    '>=' => '≥',
                    '>' => '>',
                    '=' => '='
                ],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            2 => [
                'type' => 5,
                'key' => 'proH',
                'title' => '',
                'condition' => [
                    '<' => '<',
                    '<=' => '≤'
                ],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            3 => [
                'type' => 7,
                'key' => 'proF',
                'title' => '首重',
                'condition' => [
                    '<' => '<',
                ],
                'value' => [],
                'unit' => '克',
                'desc' => '收取',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            4 => [
                'type' => 7,
                'key' => 'proS',
                'title' => '续重每',
                'condition' => [
                    '=' => '=',
                ],
                'value' => [],
                'unit' => '克',
                'desc' => '收取',
                'url' => '',
                'group' => '',
                'child' => []
            ],
        ];
        $temp[0]['condition'] = OrderRuleCheckService::setCurrency();
        $result['data'] = $temp;
        $result['profile'] = '(匹配邮寄方式之前估算运费，实际运费计算以邮寄方式为准)';
        return $result;
    }

    /** 订单利润百分比
     * @return mixed
     */
    private function profitsPercentage()
    {
        $result['title'] = '订单利润百分比之后满足以下全部条件：';
        $temp = [
            0 => [
                'type' => 5,
                'key' => 'proO',
                'title' => '',
                'condition' => [
                    '>=' => '≥',
                    '>' => '>',
                    '=' => '='
                ],
                'value' => [],
                'unit' => '',
                'desc' => '（%）注意输入框后已有百分号',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            1 => [
                'type' => 5,
                'key' => 'proT',
                'title' => '',
                'condition' => [
                    '<' => '<',
                    '<=' => '≤'
                ],
                'value' => [],
                'unit' => '',
                'desc' => '（%）注意输入框后已有百分号',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            2 => [
                'type' => 7,
                'key' => 'proH',
                'title' => '首重',
                'condition' => [
                    '<' => '<',
                ],
                'value' => [],
                'unit' => '克',
                'desc' => '收取',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            3 => [
                'type' => 7,
                'key' => 'proF',
                'title' => '续重每',
                'condition' => [
                    '=' => '=',
                ],
                'value' => [],
                'unit' => '克',
                'desc' => '收取',
                'url' => '',
                'group' => '',
                'child' => []
            ],
        ];
        $result['data'] = $temp;
        $result['profile'] = '(匹配邮寄方式之前估算运费，实际运费计算以邮寄方式为准)';
        return $result;
    }

    /** 订单单笔 交易额
     * @return mixed
     */
    private function volume()
    {
        $result['type'] = 1;
        $result['title'] = '订单单笔交易额满足以下条件：';
        $temp = [
            0 => [
                'type' => 1,
                'key' => 'volO',
                'title' => '',
                'value' => [],
                'unit' => '',
                'desc' => '默认转换币种为人民币',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            1 => [
                'type' => 5,
                'key' => 'volT',
                'title' => '',
                'condition' => [
                    '>=' => '≥',
                    '>' => '>',
                    '=' => '='
                ],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            2 => [
                'type' => 5,
                'key' => 'volH',
                'title' => '',
                'condition' => [
                    '<' => '<',
                    '<=' => '≤',
                ],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ]
        ];
        $temp[0]['condition'] = OrderRuleCheckService::setCurrency();
        $result['data'] = $temp;
        return $result;
    }

    /** 买家支付的运费费用  指定范围
     * @return mixed
     */
    private function freight()
    {
        $result['type'] = 1;
        $result['title'] = '买家支付的运费费用满足以下全部条件：';
        $temp = [
            0 => [
                'type' => 1,
                'key' => 'freO',
                'title' => '',
                'value' => [],
                'unit' => '',
                'desc' => '默认转换币种为人民币',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            1 => [
                'type' => 5,
                'key' => 'freT',
                'title' => '',
                'condition' => [
                    '>=' => '≥',
                    '>' => '>',
                    '=' => '='
                ],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            2 => [
                'type' => 5,
                'key' => 'freH',
                'title' => '',
                'condition' => [
                    '<' => '<',
                    '<=' => '≤',
                ],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ]
        ];
        $temp[0]['condition'] = OrderRuleCheckService::setCurrency();
        $result['data'] = $temp;
        return $result;
    }

    /** 产品包含
     * @return mixed
     */
    private function goods()
    {
        $result['type'] = 4;
        $result['title'] = '搜索并指定货品sku';
        $temp = [
            0 => [
                'type' => 4,
                'key' => 'gooO',
                'title' => '',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => 'sku-map/query',
                'group' => '',
                'child' => []
            ]
        ];
        $result['data'] = $temp;
        return $result;
    }

    /** 产品统计
     * @return mixed
     */
    private function goodsTotal()
    {
        $result['type'] = 1;
        $result['title'] = '订单货品总数量满足以下选中的条件：';
        $temp = [
            0 => [
                'type' => 5,
                'key' => 'gotO',
                'title' => '',
                'condition' => [
                    '>=' => '≥',
                    '>' => '>',
                    '=' => '='
                ],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            1 => [
                'type' => 5,
                'key' => 'gotT',
                'title' => '',
                'condition' => [
                    '<' => '<',
                    '<=' => '≤',
                ],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ]
        ];
        $result['data'] = $temp;
        return $result;
    }

    /** 订单货品包含  指定货品且数量指定范围
     * @return mixed
     */
    private function goodsNumber()
    {
        $result['type'] = 1;
        $result['title'] = '指定货品的数量总和满足以下选中的所有条件：';
        $temp = [
            0 => [
                'type' => 4,
                'key' => 'gonO',
                'title' => '',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '请输入指定货品的sku',
                'url' => '/sku-map/query',
                'group' => '',
                'child' => []
            ],
            1 => [
                'type' => 5,
                'key' => 'gonT',
                'title' => '',
                'condition' => [
                    '>=' => '≥',
                    '>' => '>',
                    '=' => '='
                ],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            2 => [
                'type' => 5,
                'key' => 'gonH',
                'title' => '',
                'condition' => [
                    '<' => '<',
                    '<=' => '≤',
                ],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ]
        ];
        $result['data'] = $temp;
        return $result;
    }

    /** 订单货品属于 指定分类
     * @return mixed
     */
    private function goodsCategory()
    {
        $result['type'] = 8;
        $result['title'] = '';
        $temp = [
            0 => [
                'type' => 8,
                'key' => 'gooO',
                'title' => '',
                'condition' => [],
                'value' => [],
                'unit' => 'g',
                'desc' => '',
                'url' => 'categories',
                'group' => '',
                'child' => []
            ]
        ];
        $result['data'] = $temp;
        return $result;
    }

    /** 商品标签
     * @return mixed
     * @throws \think\Exception
     */
    private function goodsTag()
    {
        $result['type'] = 1;
        $result['title'] = '货品中包含带有以下任一标签的货品：';
        $tagList = Cache::store('tag')->getTag();
        $tagList = Cache::filter($tagList, [], 'id,name');
        $new_array = [];
        foreach ($tagList as $k => $v) {
            $temp = [
                'type' => '',
                'key' => "" . $v['id'],
                'title' => $v['name'],
                'condition' => [],
                'value' => [],
                'unit' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ];
            array_push($new_array, $temp);
        }
        $result['data'] = $new_array;
        return $result;
    }

    /** 订单重量 指定范围
     * @return mixed
     */
    private function weight()
    {
        $result['type'] = 1;
        $result['title'] = '重量满足以下全部条件：';
        $temp = [
            0 => [
                'type' => 5,
                'key' => 'weiO',
                'title' => '',
                'condition' => [
                    '>=' => '≥',
                    '>' => '>',
                    '=' => '='
                ],
                'value' => [],
                'unit' => 'g',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            1 => [
                'type' => 5,
                'key' => 'weiT',
                'title' => '',
                'condition' => [
                    '<' => '<',
                    '<=' => '≤',
                ],
                'value' => [],
                'unit' => 'g',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ]
        ];
        $result['data'] = $temp;
        return $result;
    }

    /** 订单货品长度  指定范围
     * @return mixed
     */
    private function length()
    {
        $result['type'] = 1;
        $result['title'] = '订单货品长度满足以下全部条件：';
        $temp = [
            0 => [
                'type' => 5,
                'key' => 'lenO',
                'title' => '',
                'condition' => [
                    '>=' => '≥',
                    '>' => '>',
                    '=' => '='
                ],
                'value' => [],
                'unit' => 'cm',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            1 => [
                'type' => 5,
                'key' => 'lenT',
                'title' => '',
                'condition' => [
                    '<' => '<',
                    '<=' => '≤',
                ],
                'value' => [],
                'unit' => 'cm',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ]
        ];
        $result['data'] = $temp;
        $result['profile'] = '提示：订单货品长度=订单中所有商品的长度的总和';
        return $result;
    }

    /** 订单货品宽度 指定范围
     * @return mixed
     */
    private function width()
    {
        $result['type'] = 1;
        $result['title'] = '订单货品宽度满足以下全部条件：';
        $temp = [
            0 => [
                'type' => 5,
                'key' => 'widO',
                'title' => '',
                'condition' => [
                    '>=' => '≥',
                    '>' => '>',
                    '=' => '='
                ],
                'value' => [],
                'unit' => 'cm',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            1 => [
                'type' => 5,
                'key' => 'widT',
                'title' => '',
                'condition' => [
                    '<' => '<',
                    '<=' => '≤',
                ],
                'value' => [],
                'unit' => 'cm',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ]
        ];
        $result['data'] = $temp;
        $result['profile'] = '提示：订单货品宽度=订单中所有商品的宽度的总和';
        return $result;
    }

    /** 订单货品高度 指定范围
     * @return mixed
     */
    private function height()
    {
        $result['type'] = 1;
        $result['title'] = '订单货品高度满足以下全部条件：';
        $temp = [
            0 => [
                'type' => 5,
                'key' => 'heiO',
                'title' => '',
                'condition' => [
                    '>=' => '≥',
                    '>' => '>',
                    '=' => '='
                ],
                'value' => [],
                'unit' => 'cm',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            1 => [
                'type' => 5,
                'key' => 'heiT',
                'title' => '',
                'condition' => [
                    '<' => '<',
                    '<=' => '≤',
                ],
                'value' => [],
                'unit' => 'cm',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ]
        ];
        $result['data'] = $temp;
        $result['profile'] = '提示：订单货品高度=订单中所有商品的高度的总和';
        return $result;
    }

    /** 订单货品体积 指定范围
     * @return mixed
     */
    private function capacity()
    {
        $result['type'] = 1;
        $result['title'] = '订单货品体积满足以下全部条件：';
        $temp = [
            0 => [
                'type' => 5,
                'key' => 'capO',
                'title' => '',
                'condition' => [
                    '>=' => '≥',
                    '>' => '>',
                    '=' => '='
                ],
                'value' => [],
                'unit' => 'cm³',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            1 => [
                'type' => 5,
                'key' => 'capT',
                'title' => '',
                'condition' => [
                    '<' => '<',
                    '<=' => '≤',
                ],
                'value' => [],
                'unit' => 'cm³',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ]
        ];
        $result['data'] = $temp;
        $result['profile'] = '提示：订单货品体积=订单中所有商品的体积的总和';
        return $result;
    }

    /** 订单货品状态包含  指定的商品状态
     * @return mixed
     */
    private function status()
    {
        $result['type'] = 1;
        $result['title'] = '订单货品体积满足以下全部条件：';
        $temp = [
            0 => [
                'type' => '',
                'key' => 'sta0',
                'title' => '',
                'condition' => [
                    '00' => '未出售',
                    '01' => '出售',
                    '02' => '停售'
                ],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ]
        ];
        $result['data'] = $temp;
        return $result;
    }

    /**
     * 买家曾经给过我差评
     */
    private function badReview()
    {
        $result['type'] = 2;
        $result['title'] = "买家曾经给过我差评：";
        $result['data'] = [];
        $temp = [
            0 => [
                'type' => '',
                'key' => 'bRO',
                'title' => '是',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            1 => [
                'type' => '',
                'key' => 'bRT',
                'title' => '否',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ]
        ];
        $result['data'] = $temp;
        return $result;
    }

    /**
     * 买家曾经发起纠纷
     */
    private function disputes()
    {
        $result['type'] = 2;
        $result['title'] = "买家曾经发起纠纷：";
        $result['data'] = [];
        $temp = [
            0 => [
                'type' => '',
                'key' => 'dO',
                'title' => '是',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            1 => [
                'type' => '',
                'key' => 'dT',
                'title' => '否',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ]
        ];
        $result['data'] = $temp;
        return $result;
    }

    /**
     * 买家曾经有过退款记录
     */
    private function refundRecord()
    {
        $result['type'] = 2;
        $result['title'] = "买家曾经有过退款记录：";
        $result['data'] = [];
        $temp = [
            0 => [
                'type' => '',
                'key' => 'rRO',
                'title' => '是',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            1 => [
                'type' => '',
                'key' => 'rRT',
                'title' => '否',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ]
        ];
        $result['data'] = $temp;
        return $result;
    }

    /**
     * 买家曾经有过补发记录
     */
    private function replaceRecord()
    {
        $result['type'] = 2;
        $result['title'] = "买家曾经有过补发记录：";
        $result['data'] = [];
        $temp = [
            0 => [
                'type' => '',
                'key' => 'rRO',
                'title' => '是',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            1 => [
                'type' => '',
                'key' => 'rRT',
                'title' => '否',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ]
        ];
        $result['data'] = $temp;
        return $result;
    }

    /**
     * 产品
     */
    private function goodsPut()
    {
        $result['data'] = [];
        return $result;
    }

    /** 买家ID
     * @return mixed
     */
    private function buyerId()
    {
        $result['type'] = 3;
        $result['title'] = '指定要在买家ID中查找的字符(多个ID用英文逗号隔空)';
        $temp = [
            0 => [
                'type' => 3,
                'key' => 'biO',
                'title' => '',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ]
        ];
        $result['data'] = $temp;
        return $result;
    }

    /** 买家姓名
     * @return mixed
     */
    private function buyerName()
    {
        $result['type'] = 3;
        $result['title'] = '指定要在买家姓名中查找的字符(多个姓名用英文逗号隔空)';
        $temp = [
            0 => [
                'type' => 3,
                'key' => 'bnO',
                'title' => '',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ]
        ];
        $result['data'] = $temp;
        return $result;
    }

    /** 订单付款方式
     * @return mixed
     */
    private function orderPayment()
    {
        $result['type'] = 2;
        $result['title'] = "付款方式是否为货到付款：";
        $result['data'] = [];
        $temp = [
            0 => [
                'type' => '',
                'key' => 'ordO',
                'title' => '是',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            1 => [
                'type' => '',
                'key' => 'ordT',
                'title' => '否',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ]
        ];
        $result['data'] = $temp;
        return $result;
    }


    /** 订单付款方式
     * @return mixed
     */
    private function childOrder()
    {
        $result['type'] = 2;
        $result['title'] = "是否为子订单：";
        $result['data'] = [];
        $temp = [
            0 => [
                'type' => '',
                'key' => 'cordO',
                'title' => '是',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            1 => [
                'type' => '',
                'key' => 'cordT',
                'title' => '否',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ]
        ];
        $result['data'] = $temp;
        return $result;
    }


    /** 是否由LGS当地仓库发货
     * @return mixed
     */
    private function isLocalDeliver()
    {
        $result['type'] = 2;
        $result['title'] = "是否由LGS当地仓库发货：";
        $result['data'] = [];
        $temp = [
            0 => [
                'type' => '',
                'key' => 'isLO',
                'title' => '是',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            1 => [
                'type' => '',
                'key' => 'isLT',
                'title' => '否',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ]
        ];
        $result['data'] = $temp;
        return $result;
    }

    /** 是否wish
     * @return mixed
     */
    private function isWishExpress()
    {
        $result['type'] = 2;
        $result['title'] = "是否是Wish Express：";
        $result['data'] = [];
        $temp = [
            0 => [
                'type' => '',
                'key' => 'isWO',
                'title' => '是',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            1 => [
                'type' => '',
                'key' => 'isWT',
                'title' => '否',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ]
        ];
        $result['data'] = $temp;
        return $result;
    }

    /** wish确认妥投政策
     * @return mixed
     */
    private function delivered()
    {
        $result['type'] = 2;
        $result['title'] = "符合“确认妥投政策”";
        $result['data'] = [];
        $temp = [
            0 => [
                'type' => '',
                'key' => 'dO',
                'title' => '是',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            1 => [
                'type' => '',
                'key' => 'dT',
                'title' => '否',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ]
        ];
        $result['data'] = $temp;
        return $result;
    }

    /** wishEPC订单
     * @return mixed
     */
    private function wishEpc()
    {
        $result['type'] = 2;
        $result['title'] = "是否是EPC订单";
        $result['data'] = [];
        $temp = [
            0 => [
                'type' => '',
                'key' => 'wEO',
                'title' => '是',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            1 => [
                'type' => '',
                'key' => 'wET',
                'title' => '否',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ]
        ];
        $result['data'] = $temp;
        return $result;
    }

    /** 订单货品包含停售
     * @return mixed
     */
    private function goodsHaltSales()
    {
        $result['type'] = 2;
        $result['title'] = "订单货品包含停售：";
        $result['data'] = [];
        $temp = [
            0 => [
                'type' => '',
                'key' => 'ghsO',
                'title' => '是',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            1 => [
                'type' => '',
                'key' => 'ghsT',
                'title' => '否',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ]
        ];
        $result['data'] = $temp;
        return $result;
    }

    /** 采购总金额  范围
     * @return mixed
     */
    private function purchaseTotalAmount()
    {
        $result['type'] = 1;
        $result['title'] = '采购总金额满足以下条件：';
        $temp = [
            0 => [
                'type' => 1,
                'key' => 'pTotO',
                'title' => '',
                'value' => [],
                'unit' => '',
                'desc' => '默认转换币种为人民币',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            1 => [
                'type' => 5,
                'key' => 'pTotT',
                'title' => '',
                'condition' => [
                    '>=' => '≥',
                    '>' => '>',
                    '=' => '='
                ],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            2 => [
                'type' => 5,
                'key' => 'pTotH',
                'title' => '',
                'condition' => [
                    '<' => '<',
                    '<=' => '≤',
                ],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ]
        ];
        $temp[0]['condition'] = OrderRuleCheckService::setCurrency();
        $result['data'] = $temp;
        return $result;
    }

    /** 采购单价  范围
     * @return mixed
     */
    private function unitPrice()
    {
        $result['type'] = 1;
        $result['title'] = '采购单价满足以下条件：';
        $temp = [
            0 => [
                'type' => 1,
                'key' => 'upO',
                'title' => '',
                'value' => [],
                'unit' => '',
                'desc' => '默认转换币种为人民币',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            1 => [
                'type' => 5,
                'key' => 'upT',
                'title' => '',
                'condition' => [
                    '>=' => '≥',
                    '>' => '>',
                    '=' => '='
                ],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            2 => [
                'type' => 5,
                'key' => 'upH',
                'title' => '',
                'condition' => [
                    '<' => '<',
                    '<=' => '≤',
                ],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ]
        ];
        $temp[0]['condition'] = OrderRuleCheckService::setCurrency();
        $result['data'] = $temp;
        return $result;
    }

    /** 采购单价比较
     * @return mixed
     */
    private function unitPriceCompare()
    {
        $result['type'] = 1;
        //$result['title'] = '采购单所有的商品采购单价满足以下条件:';
        $result['title'] = '采购单中只要有一个商品的采购单价满足以下任一条件:';
        $temp = [
            0 => [
                'type' => 1,
                'key' => 'upcO',
                'title' => '上次采购单价',
                'condition' => [
                    '>=' => '≥',
                    '>' => '>',
                    '=' => '='
                    //'<' => '<',
                    //'<=' => '≤'
                ],
                'value' => [],
                'unit' => '',
                'desc' => '本次采购单价',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            1 => [
                'type' => 1,
                'key' => 'upcT',
                'title' => '上次采购单价',
                'condition' => [
                    //'>=' => '≥',
                    //'>' => '>',
                    '<=' => '≤',
                    '<' => '<'
                ],
                'value' => [],
                'unit' => '',
                'desc' => '本次采购单价',
                'url' => '',
                'group' => '',
                'child' => []
            ]
        ];
        $result['data'] = $temp;
        return $result;
    }

    /** 建议数量比较
     * @return mixed
     */
    private function adviceQuantity()
    {
        $result['type'] = 1;
        $result['title'] = '采购单所有的商品建议数量满足以下条件:';
        $temp = [
            0 => [
                'type' => 1,
                'key' => 'aqO',
                'title' => '采购建议数量',
                'condition' => [
                    '>=' => '≥',
                    '>' => '>',
                    '=' => '=',
                    '<' => '<',
                    '<=' => '≤'
                ],
                'value' => [],
                'unit' => '',
                'desc' => '实际采购数量',
                'url' => '',
                'group' => '',
                'child' => []
            ]
        ];
        $result['data'] = $temp;
        return $result;
    }

    /** 采购数量
     * @return mixed
     */
    private function quantity()
    {
        $result['type'] = 1;
        $result['title'] = '采购数量满足以下选中的条件：';
        $temp = [
            0 => [
                'type' => 5,
                'key' => 'qO',
                'title' => '',
                'condition' => [
                    '>=' => '≥',
                    '>' => '>',
                    '=' => '='
                ],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            1 => [
                'type' => 5,
                'key' => 'qT',
                'title' => '',
                'condition' => [
                    '<' => '<',
                    '<=' => '≤',
                ],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ]
        ];
        $result['data'] = $temp;
        return $result;
    }

    /** 申报的sku品种数
     * @return mixed
     */
    private function declareSkuVariety()
    {
        $result['type'] = 2;
        $result['title'] = '申报的SKU品种数';
        $result['code'] = 'declareSkuVariety';
        $temp = [
            0 => [
                'type' => '2',
                'key' => 'dsv0',
                'title' => '全部申报',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            1 => [
                'type' => '6',
                'key' => 'dsvT',
                'title' => '限制最多申报品种数',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'url' => '',
                'group' => '',
                'child' => [],
                'value_type' => 'integer'
            ]
        ];
        $result['data'] = $temp;
        return $result;
    }

    /** 每个申报的sku申报的数量
     * @return mixed
     */
    private function eachDeclareSkuQuantity()
    {
        $result['type'] = 2;
        $result['title'] = '每个申报的SKU品种申报的数量';
        $result['code'] = 'eachDeclareSkuQuantity';
        $temp = [
            0 => [
                'type' => '2',
                'key' => 'dsqO',
                'title' => '实际数量',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            1 => [
                'type' => '6',
                'key' => 'dsqT',
                'title' => '限制最多申报数量',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'url' => '',
                'group' => '',
                'child' => [],
                'value_type' => 'integer'
            ]
        ];
        $result['data'] = $temp;
        return $result;
    }

    /** 包裹包含多sku时选择申报的货品信息
     * @return mixed
     */
    private function declareGoodsInfo()
    {
        $result['type'] = 1;
        $result['title'] = '包裹包含多SKU时选择申报的货品信息';
        $result['code'] = 'declareGoodsInfo';
        $temp = [
            0 => [
                'type' => '1',
                'key' => 'dgi',
                'title' => '',
                'condition' => [
                    'dgiO' => '按照货品成本单价由高到低顺序选择',
                    'dgiT' => '按照货品成本单价X数量由高到低顺序选择',
                    'dgiH' => '按照货品单品体积由大到小顺序选择',
                    'dgiF' => '按照货品单品体积X数量由大到小顺序选择',
                    'dgiFi' => '按照货品单品重量由大到小顺序选择',
                    'dgiS' => '按照货品单品重量X数量由大到小顺序选择'
                ],
                'value' => [],
                'unit' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
        ];
        $result['data'] = $temp;
        return $result;
    }

    /** 申报重量的计算方法
     * @return mixed
     */
    private function declareGoodsWeight()
    {
        $result['type'] = 2;
        $result['title'] = '申报重量的计算方法';
        $result['code'] = 'declareGoodsWeight';
        $temp = [
            0 => [
                'type' => '6',
                'key' => 'dgwO',
                'title' => '使用固定的总重量申报，固定包裹总重量为',
                'condition' => [],
                'value' => [],
                'unit' => 'g',
                'url' => '',
                'group' => '',
                'desc' => '如果申报时各品种需要详细的申报重量，系统将自动按照申报的品种的真实货品重量加权平均计算',
                'child' => [],
                'value_type' => 'integer'
            ],
            1 => [
                'type' => '6',
                'key' => 'dgwT',
                'title' => '使用货品真实重量申报，设置包裹封顶重量为',
                'condition' => [],
                'value' => [],
                'unit' => 'g',
                'url' => '',
                'group' => '',
                'desc' => '填写0表示不设置封顶重量',
                'child' => [],
                'value_type' => 'integer'
            ]
        ];
        $result['data'] = $temp;
        return $result;
    }

    /** 申报价格的计算方法
     * @return mixed
     */
    private function declarePrice()
    {
        $result['type'] = 2;
        $result['title'] = '申报价格的计算方法';
        $result['code'] = 'declarePrice';
        $temp = [
            0 => [
                'type' => 1,
                'key' => 'dpO',
                'title' => '申报币种',
                'value' => [],
                'condition' => OrderRuleCheckService::setCurrency(),
                'unit' => '',
                'desc' => '默认转换币种为人民币',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            1 => [
                'type' => 6,
                'key' => 'dpT',
                'title' => '使用固定价格申报，固定申报价格',
                'value' => [],
                'condition' => [],
                'unit' => '',
                'desc' => '如果申报时各品种需要详细的申报重量，系统将自动按照申报的品种的真实货品重量加权平均计算',
                'url' => '',
                'group' => '',
                'child' => [],
                'value_type' => 'float'
            ],
            2 => [
                'type' => 6,
                'key' => 'dpH',
                'title' => '使用价格比例申报，使用包裹',
                'value' => [],
                'condition' => [
                    'totalAmount' => '总金额',
                    'totalCost' => '总成本'
                ],
                'unit' => '%',
                'desc' => '设置为本选项后，系统将根据申报币种自动做汇率转换',
                'url' => '',
                'group' => '',
                'child' => [
                    0 => [
                        'type' => 5,
                        'key' => 'dpHO',
                        'title' => '设置最低价格',
                        'value' => [],
                        'condition' => [],
                        'unit' => '',
                        'desc' => '',
                        'url' => '',
                        'group' => '',
                        'child' => [],
                        'value_type' => 'float'
                    ],
                    1 => [
                        'type' => 5,
                        'key' => 'dpHT',
                        'title' => '设置封顶价格',
                        'value' => [],
                        'condition' => [],
                        'unit' => '',
                        'desc' => '',
                        'url' => '',
                        'group' => '',
                        'child' => [],
                        'value_type' => 'float'
                    ]
                ],
                'value_type' => 'float'
            ]
        ];
        $result['data'] = $temp;
        return $result;
    }

    /** 包裹尺寸
     * @return mixed
     */
    private function size()
    {
        $result['type'] = 1;
        $result['title'] = '所有货品的包装材料最短边相加后：';
        $temp = [
            0 => $this->options(5, 'sizeO', '', '', ['>=' => '≥', '>' => '>', '=' => '='], '', '', 'cm'),
            1 => $this->options(5, 'sizeT', '', '', ['<' => '<', '<=' => '≤'], '', '', 'cm')
        ];
        $result['data'] = $temp;
        $result['profile'] = '';
        return $result;
    }

    /** 货品尺寸
     * @return mixed
     */
    private function goodsSize()
    {
        $result['type'] = 1;
        $result['title'] = '订单商品满足以下所有条件：';
        $temp = [
            0 => $this->options(5, 'gzO', '长+宽+高≤', '', [], '', '', 'cm'),
            1 => $this->options(5, 'gzT', '最长边≤', '', [], '', '', 'cm')
        ];
        $result['data'] = $temp;
        $result['profile'] = '';
        return $result;
    }

    /** 邮寄方式
     * @return mixed
     */
    private function shipping()
    {
        $result['type'] = 1;
        $result['title'] = '选择指定邮寄方式：';
        $carrierModel = new Carrier();
        $carrierList = $carrierModel->field('id, shortname, type as type_cn')->select();
        $condition = [];
        foreach ($carrierList as $v) {
            if ($v['type_cn'] == 0) {
                $v['type'] = 0;
            } else {
                $v['type'] = 1;
            }
            array_push($condition, $v);
        }
        $temp = [
            0 => $this->options(1, 'shipO', '', '', $condition, '', '', 'cm', '', '',
                [$this->options(1, 'shipOO', '', '', [], '', '', 'cm', '/warehouse/getShip')])
        ];
        $result['data'] = $temp;
        $result['profile'] = '';
        return $result;
    }

    /** 采购员
     * @return mixed
     * @throws \think\Exception
     */
    private function purchase()
    {
        $result['type'] = 1;
        $userService = new User();
        $userList = $userService->staff('purchase', '', 0, 1000);
        $new_array = [];
        foreach ($userList as $k => $v) {
            $temp = $this->options('', "" . $v['id'], $v['realname']);
            array_push($new_array, $temp);
        }
        $result['data'] = $new_array;
        return $result;
    }

    /** 追款金额
     * @return mixed
     */
    private function chaseMoney()
    {
        $result['type'] = 1;
        $result['title'] = '已付款金额以下选中的条件：';
        $temp = [
            0 => [
                'type' => 5,
                'key' => 'cmO',
                'title' => '',
                'condition' => [
                    '>=' => '≥',
                    '>' => '>',
                    '=' => '='
                ],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            1 => [
                'type' => 5,
                'key' => 'cmT',
                'title' => '',
                'condition' => [
                    '<' => '<',
                    '<=' => '≤',
                ],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ]
        ];
        $result['data'] = $temp;
        return $result;
    }

    /** 不等待剩余sku数量
     * @return mixed
     */
    private function surplusSkuQuantity()
    {
        $result['type'] = 1;
        $result['title'] = '不等待剩余sku数量满足以下选中的条件：';
        $temp = [
            0 => [
                'type' => 5,
                'key' => 'ssqO',
                'title' => '',
                'condition' => [
                    '>=' => '≥',
                    '>' => '>',
                    '=' => '='
                ],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            1 => [
                'type' => 5,
                'key' => 'ssqT',
                'title' => '',
                'condition' => [
                    '<' => '<',
                    '<=' => '≤',
                ],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ]
        ];
        $result['data'] = $temp;
        return $result;
    }

    /** 物流运输费
     * @return mixed
     */
    private function pricingTransportationCosts()
    {
        $result['type'] = 2;
        $result['title'] = '物流运输费';
        $temp = [
            0 => $this->options(9, 'ptcO', '首重：每', '收取', [], '', '', 'g'),
            1 => $this->options(9, 'ptcT', '续重：每', '收取', [], '', '', 'g')
        ];
        $result['data'] = $temp;
        $result['profile'] = '';
        return $result;
    }

    /** 发挂号时的物流运输费
     * @return mixed
     */
    private function pricingTransportationCostsTime()
    {
        $result['type'] = 2;
        $result['title'] = '发挂号时的物流运输费';
        $temp = [
            0 => $this->options(5, 'ptctO', '如果：平邮实际销售价', '', ['≥' => '>=', '>' => '>'], '', '', '（单位：站点币种）则：物流运输费改为以下'),
            1 => $this->options(9, 'ptctT', '首重：每', '收取', [], '', '', 'g'),
            2 => $this->options(9, 'ptctF', '续重：每', '收取', [], '', '', 'g')
        ];
        $result['data'] = $temp;
        $result['profile'] = '';
        return $result;
    }


    /** 平台佣金率
     * @return mixed
     */
    private function pricingPlatformCommission()
    {
        $result['type'] = 2;
        $result['title'] = '平台佣金率为(币种为站点币种)';
        $temp = [
            0 => $this->options(3, 'ppcO', '平台佣金率为：', '', [], '', '', '%'),
            1 => $this->options(9, 'ppcT', '当平台佣金低于', '则按固定金额收取', [], '', '', '时,'),
        ];
        $result['data'] = $temp;
        $result['profile'] = '';
        return $result;
    }

    /** 销售毛利润
     * @return mixed
     */
    private function pricingGrossProfit()
    {
        $result['type'] = 2;
        $result['title'] = '销售毛利润率为';
        $temp = [
            0 => $this->options(3, 'pgpO', '毛利润率为：', '', [], '', '', '%')
        ];
        $result['data'] = $temp;
        $result['profile'] = '';
        return $result;
    }

    /** 运输方式选择
     * @return mixed
     */
    private function pricingShippingCountry()
    {
        $result['type'] = 1;
        $result['title'] = '运输方式选择';
        $temp = [
            0 => [
                'type' => 1,
                'key' => 'volO',
                'title' => '发送国家：',
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            1 => [
                'type' => 1,
                'key' => 'vol1',
                'title' => '运输方式：',
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],

        ];
        $temp[0]['condition'] = OrderRuleCheckService::setCountry();
        $temp[1]['condition'] = OrderRuleCheckService::setShipping();
        $result['data'] = $temp;
        return $result;
    }


    /** EUB运输方式选择（US站点专用）
     * @return mixed
     */
    private function pricingShippingEUB()
    {
        $result['type'] = 1;
        $result['title'] = '运输方式选择';
        $temp = [
            0 => [
                'type' => 1,
                'key' => 'volO',
                'title' => '运输方式：',
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],

        ];
        $temp[0]['condition'] = OrderRuleCheckService::setShipping();
        $result['data'] = $temp;
        return $result;
    }

    /** 售价金额取整
     * @return mixed
     */
    private function pricingSellingPriceFixed()
    {
        $result['type'] = 2;
        $result['title'] = '售价金额取整';
        $temp = [
            0 => $this->options(3, 'pgpO', '售价金额小数点后第二位数自动改为：', '', [], '', '', '')
        ];
        $result['data'] = $temp;
        $result['profile'] = '';
        return $result;
    }

    /** 其他成本
     * @return mixed
     */
    private function pricingOtherCosts()
    {
        $result['type'] = 2;
        $result['title'] = '其他成本为';
        $temp = [
            0 => $this->options(3, 'pocO', '其他固定成本为：', '', [], '', '', 'RMB'),
            1 => $this->options(3, 'pocT', '其他动态成本为：', '', [], '', '', 'RMB/g')
        ];
        $result['data'] = $temp;
        $result['profile'] = '';
        return $result;
    }

    /** 换算汇率
     * @return mixed
     */
    private function pricingConvertedRate()
    {
        $result['type'] = 2;
        $result['title'] = '换算汇率';
        $temp = [
            0 => $this->options(1, 'pcrO', '请选择站点币种：'),
            1 => $this->options(2, 'pcrT', '自动获取系统最新汇率', '', [], '', '', '', '', 'rate'),
            2 => $this->options(6, 'pcrH', '自定义汇率为', '', [], '', '', '', '', 'rate')
        ];
        $temp[0]['condition'] = OrderRuleCheckService::setCurrency();
        $result['data'] = $temp;
        $result['profile'] = '';
        return $result;
    }

    /** 平台营销活动的促销折扣率
     * @return mixed
     */
    private function pricingDiscountRate()
    {
        $result['type'] = 2;
        $result['title'] = '促销折扣率';
        $temp[0] = $this->options(3, 'pdrO', '促销折扣率为:', '', [], '', '', '%');
        $result['data'] = $temp;
        $result['profile'] = '';
        return $result;
    }

    /** [只用于ebay] Paypal-大额账号交易费
     * @return mixed
     */
    private function pricingLargeAccountTransactionFee()
    {
        $result['type'] = 2;
        $result['title'] = 'Paypal-大额账号交易费';
        $temp = [
            0 => $this->options(3, 'platO', '大额收款账号费率为:', '', [], '', '', '%'),
            1 => $this->options(3, 'platT', '站点固定金额为:', '（单位：站点币种）')
        ];
        $result['data'] = $temp;
        $result['profile'] = '';
        return $result;
    }

    /** [只用于ebay] Paypal-小额账号交易费
     * @return mixed
     */
    private function pricingSmallAccountTransactionFee()
    {
        $result['type'] = 2;
        $result['title'] = 'Paypal-小额账号交易费';
        $temp = [
            0 => $this->options(3, 'psatO', '小额收款账号费率为:', '', [], '', '', '%'),
            1 => $this->options(3, 'psatT', '站点固定金额为:', '（单位：站点币种）')
        ];
        $result['data'] = $temp;
        $result['profile'] = '';
        return $result;
    }

    /** 货币转换费率（英国/澳洲/加拿大/德国专用）
     * @return mixed
     */
    private function currencyConversionRate()
    {
        $result['type'] = 2;
        $result['title'] = '货币转换费率（英国/澳洲/加拿大/德国专用）';
        $temp = [
            0 => $this->options(3, 'ccrO', '货币转化费率：', '', [], '', '', '%'),
        ];
        $result['data'] = $temp;
        $result['profile'] = '';
        return $result;
    }

    /** 物流附加费设置
     * @return mixed
     * @throws \think\Exception
     */
    private function pricingLogisticsSurchargeSetting()
    {
        $result['type'] = 2;
        $result['title'] = '物流附加费设置';
        $goodsHelp = new GoodsHelp();
        $propertyList = $goodsHelp->getTransportProperies();
        $new_array = [];
        $new_array[0] = $this->options(3, 'plssO', '产品包含以下属性，需增加物流运输附加费：', '', [], '', '', 'RMB');
        foreach ($propertyList as $k => $v) {
            $temp = $this->options(1, "" . $v['value'], $v['name']);
            array_push($new_array, $temp);
        }
        $result['data'] = $new_array;
        return $result;
    }

    /** 物流挂号费设置
     * @return mixed
     * @throws \think\Exception
     */
    private function pricingLogisticsRegistrationFeeSetting()
    {
        $result['type'] = 2;
        $result['title'] = '物流挂号费设置';
        $temp = [
            0 => $this->options(3, 'plrfsO', '当满足以下所有条件的情况下，需收物流挂号费：', '', [], '', '', 'RMB'),
            1 => $this->options(5, 'plrfsT', '平邮销售价', '', ['≥' => '>=', '>' => '>'], '', '', '（单位：站点币种）'),
            2 => $this->options(5, 'plrfsF', '重量', '', ['≥' => '>=', '>' => '>'], '', '', 'g'),
            3 => $this->options(5, 'plrfsH', '平邮销售价', '', ['<' => '<', '≤' => '<='], '', '', '（单位：站点币种）'),
            4 => $this->options(5, 'plrfsFi', '重量', '', ['<' => '<', '≤' => '<='], '', '', 'g')
        ];
        $result['data'] = $temp;
        return $result;
    }

    /** 【只用于Wish】销售价与运费的拆分比例设置（计算结果四舍五入，保留小数点后2位数。单位：站点币种）
     * @return mixed
     * @throws \think\Exception
     */
    private function pricingSplitScaleSettings()
    {
        $result['type'] = 2;
        $result['title'] = '销售价与运费的拆分比例设置（计算结果四舍五入，保留小数点后2位数。单位：站点币种）';
        $temp = [
            0 => $this->options(5, 'psss0', '总销售价 ≤', '', [], '', '', '', '', '',
                [$this->options(9, 'psss0O', '销售价：运费=')]),
            1 => $this->options(10, 'psssT', '< 总销售价 ≤', '', [], '', '', '', '', '',
                [$this->options(9, 'psssTO', '销售价：运费=')]),
            2 => $this->options(5, 'psssH', '总销售价 >', '', [], '', '', '', '', '',
                [$this->options(9, 'psssHO', '销售价：运费=')])
        ];
        $result['data'] = $temp;
        return $result;
    }

    /** 允许销售员降价幅度（计算结果四舍五入，保留小数点后2位数。单位：站点币种）
     * @return mixed
     * @throws \think\Exception
     */
    private function pricingCutPercentage()
    {
        $result['type'] = 2;
        $result['title'] = '允许销售员降价幅度（计算结果四舍五入，保留小数点后2位数。单位：站点币种）';
        $temp = [
            0 => $this->options(5, 'pcp0', '总销售价 ≤', '', [], '', '', '', '', '',
                [$this->options(5, 'pcp0O', '允许降价幅度为:', '', [], '', '', '%')]),
            1 => $this->options(10, 'pcpT', '< 总销售价 ≤', '', [], '', '', '', '', '',
                [$this->options(5, 'pcpTO', '允许降价幅度为:', '', [], '', '', '%')]),
            2 => $this->options(5, 'pcpH', '总销售价 >', '', [], '', '', '', '', '',
                [$this->options(5, 'pcpHO', '允许降价幅度为:', '', [], '', '', '%')])
        ];
        $result['data'] = $temp;
        return $result;
    }

    /** 最终平邮售价取值（US站专用，币种为站点币种）
     * @return mixed
     * @throws \think\Exception
     */
    private function pricingPriceOfSurfaceMail()
    {
        $result['type'] = 2;
        $result['title'] = '最终平邮售价取值（US站专用，币种为站点币种）';
        $temp = [
            0 => $this->options(10, 'pcpO', '< 平邮小额售价 ≤', '', [], '', '', '', '', '',
                [$this->options(5, 'pcpOO', '则:平邮小额售价', '', [], '', '', '')]),
            1 => $this->options(3, 'pcpT', '如果:平邮小额售价<', '，则：最终平邮售价 =平邮小额售价', [], '', '', ''),
            2 => $this->options(3, 'pcpW', '否则，如果:平邮小额售价<', ',则：最终平邮售价 =（平邮小额售价+Edis销售价）/ 2', [], '', '', ''),
            3 => [
                'type' => 1,
                'key' => 'pcpX',
                'title' => '否则，最终平邮售价 = Edis销售价',
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            ];
        $result['data'] = $temp;
        return $result;
    }

    /** EUB运费价格(US站点专用，币种为站点币种)
     * @return mixed
     * @throws \think\Exception
     */
    private function pricingShippingPriceEUB()
    {
        $result['type'] = 2;
        $result['title'] = 'EUB运费价格(US站点专用，币种为站点币种)';
        $temp = [
            0 => $this->options(10, 'pcpO', '< 平邮小额售价 ≤', '', [], '', '', '', '', ''),
            1 => [
                'type' => 1,
                'key' => 'pcpT',
                'title' => '则：“国内运费”= “Edis销售价” 减去 “平邮小额售价”，否则：“国内运费”=0',
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            2 => [
                'type' => 1,
                'key' => 'pcpT',
                'title' => '当有国内运费时，刊登详情国内第一运送方式默认选中：Standard Shipping from outside US (5-10 days)',
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
        ];
        $result['data'] = $temp;
        return $result;
    }

    /** 最终平邮售价取值（非US站专用，币种为站点币种）
     * @return mixed
     * @throws \think\Exception
     */
    private function pricingPriceMail()
    {
        $result['type'] = 2;
        $result['title'] = '最终平邮售价取值（非US站专用，币种为站点币种）';
        $temp = [
            0 => $this->options(10, 'pcpO', '< 平邮小额售价 ≤', '', [], '', '', '', '', '',
                [$this->options(5, 'pcpOO', '则:平邮小额售价', '', [], '', '', '')]),
            1 => $this->options(3, 'pcpT', '如果:平邮小额售价<', '，则：最终平邮售价 =平邮小额售价', [], '', '', ''),
            2 => [
                'type' => 1,
                'key' => 'pcpX',
                'title' => '否则，最终平邮售价 = Edis销售价',
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
        ];;
        $result['data'] = $temp;
        return $result;
    }

    /** 国际运费价格(非US站点专用，币种为站点币种）
     * @return mixed
     * @throws \think\Exception
     */
    private function pricingPriceInternationalShipping()
    {
        $result['type'] = 1;
        $result['title'] = '国际运费价格(非US站点专用，币种为站点币种）';
        $temp = [
            0 => $this->options(5, 'riqO', '如果：SKU毛重重量(g)', '',[
                '>=' => '>=',
                '>' => '>',
                '=' => '='
            ], '', '', ''),
            1 => $this->options(5, 'pcpH', '或者 “最终销售价”乘以“站点汇率”>', '', [], '', '', 'RMB', '', '',
                []),
            2 => [
                'type' => 1,
                'key' => 'pcpT',
                'title' => '则：“国际运费”= “Edis大额售价” 减去 “最终销售价”，否则：“国际运费”=0',
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
        ];
        $result['data'] = $temp;
        return $result;
    }

    /** 售价金额范围
     * @return mixed
     * @throws \think\Exception
     */
    private function pricingPriceRange()
    {
        $result['type'] = 1;
        $result['title'] = '售价金额范围';
        $temp = [
            0 => $this->options(5, 'pcp0', '售价金额 ≤', '', [], '', '', '', '', 'rate'),
            1 => $this->options(10, 'pcpT', '< 售价金额 ≤', '', [], '', '', '', '', 'rate'),
            2 => $this->options(5, 'pcpH', '售价金额 >', '', [], '', '', '', '', 'rate')
        ];
        $result['data'] = $temp;
        return $result;
    }

    /** 最终平邮售价金额随机（币种为站点币种）
     * @return mixed
     * @throws \think\Exception
     */
    private function pricingPriceRand()
    {
        $result['type'] = 2;
        $result['title'] = '最终平邮售价金额随机幅度';
        $temp = [
            0 => $this->options(5, 'weiO', '最终平邮售价金额随机幅度', '', [
                '上下浮动' => '1',
                '往上浮动' => '2',
                '往下浮动' => '3'
            ], '', '', '')
        ];
        $result['data'] = $temp;
        return $result;
    }

    /** 【只用于Wish】吊牌价设置（四舍五入，取整数。此处“总销售价”已减去“允许降价金额”。单位：站点币种）
     * @return mixed
     * @throws \think\Exception
     */
    private function pricingTagPriceSetting()
    {
        $result['type'] = 2;
        $result['title'] = '吊牌价设置（四舍五入，取整数。此处“总销售价”已减去“允许降价金额”。单位：站点币种）';
        $temp = [
            0 => $this->options(3, 'ptpsO', '吊牌价 = 总销售价 * ', '倍')
        ];
        $result['data'] = $temp;
        return $result;
    }

    /** 商品不能包含物流属性
     * @return mixed
     * @throws \think\Exception
     */
    private function containsNoLogisticsAttributes()
    {
        $result['type'] = 1;
        $new_array = [];
        $propertyList = $this->logisticsAttributes();
        foreach ($propertyList as $k => $v) {
            $temp = [
                'type' => '',
                'key' => "" . $v['value'],
                'title' => $v['name'],
                'condition' => [],
                'value' => [],
                'unit' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ];
            array_push($new_array, $temp);
        }
        $result['data'] = $new_array;
        return $result;
    }

    /** 商品必须包含物流属性
     * @return mixed
     * @throws \think\Exception
     */
    private function containsLogisticsAttributes()
    {
        $result['type'] = 1;
        $new_array = [];
        $propertyList = $this->logisticsAttributes();
        foreach ($propertyList as $k => $v) {
            $temp = [
                'type' => '',
                'key' => "" . $v['value'],
                'title' => $v['name'],
                'condition' => [],
                'value' => [],
                'unit' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ];
            array_push($new_array, $temp);
        }
        $result['data'] = $new_array;
        return $result;
    }

    /**商品必须包含物流属性[定价规则]
     * @return mixed
     * @throws Exception
     */
    private function containsLogisticsAttributesPrice()
    {
        return $this->containsLogisticsAttributes();
    }

    /**
     * 物流属性
     */
    private function logisticsAttributes()
    {
        $goodsHelp = new GoodsHelp();
        $propertyList = $goodsHelp->getTransportProperies();
        return $propertyList;
    }

    /**
     * 刊登速卖通指定分类
     * @return mixed
     */
    private function smtCategory()
    {
        $result['type'] = 8;
        $result['title'] = '';
        $temp = [
            0 => [
                'type' => 8,
                'key' => 'smtO',
                'title' => '',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => 'aliexpress-category-tree',
                'group' => '',
                'child' => []
            ]
        ];
        $result['data'] = $temp;
        return $result;
    }

    /** 资源值
     * @param $type 【类型】
     * @param $key 【key】
     * @param $title 【标题】
     * @param $desc 【描述】
     * @param array $condition
     * @param string $value
     * @param string $other
     * @param string $unit
     * @param string $url
     * @param string $group
     * @param array $child
     * @return array
     */
    private function options(
        $type,
        $key,
        $title,
        $desc = '',
        $condition = [],
        $value = '',
        $other = '',
        $unit = '',
        $url = '',
        $group = '',
        $child = []
    )
    {
        $options = [
            'type' => $type,
            'key' => $key,
            'title' => $title,
            'desc' => $desc,
            'condition' => $condition,
            'value' => $value,
            'other' => $other,
            'unit' => $unit,
            'url' => $url,
            'group' => $group
        ];
        $newChild = [];
        if (!empty($child)) {
            foreach ($child as $key => $value) {
                array_push($newChild, $value);
            }
        }
        $options['child'] = $newChild;
        return $options;
    }

    //min start

    /** 是否 Prime 会员
     * @return mixed
     */
    private function isPrimeVip()
    {
        $result['type'] = 2;
        $result['title'] = "是否 Prime 会员：";
        $result['data'] = [];
        $temp = [
            0 => [
                'type' => '',
                'key' => 'yes',
                'title' => '是',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            1 => [
                'type' => '',
                'key' => 'no',
                'title' => '否',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ]
        ];
        $result['data'] = $temp;
        return $result;
    }

    /** 是否允许分配给相同居住“城市”的买手
     * @return mixed
     */
    private function isInCity()
    {
        $result['type'] = 2;
        $result['title'] = "同一天内分配的相同SKU，是否允许分配给相同居住“城市”的买手：";
        $result['data'] = [];
        $temp = [
            0 => [
                'type' => '',
                'key' => 'yes',
                'title' => '是',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            1 => [
                'type' => '',
                'key' => 'no',
                'title' => '否',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ]
        ];
        $result['data'] = $temp;
        return $result;
    }

    /** 允许分配的刷单类型为
     * @return mixed
     */
    private function taskType()
    {
        //1-内部刷单 2-外包刷单 3-国外刷单

        $result['title'] = '允许分配的刷单类型为';
        $result['type'] = 2;
        $result['data'] = [];
        $temp = [
            0 => [
                'type' => '',
                'key' => 1,
                'title' => '内部刷单',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            1 => [
                'type' => '',
                'key' => 2,
                'title' => '外包刷单',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            2 => [
                'type' => '',
                'key' => 3,
                'title' => '国外刷单',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],

        ];
        $result['data'] = $temp;
        return $result;
    }

    /** SKU优先分配的买手所在国家
     * @return mixed
     */
    private function buyerCountry()
    {
        $result['title'] = 'SKU优先分配的买手所在国家';
        $result['type'] = 1;
        $channelList = Cache::store('VirtualRule')->getChannel();
        $allCountry = Cache::store('VirtualRule')->getChannelCountry();
        $new_array = [];
        foreach ($channelList as $k => $v) {
            $temp = [
                'type' => '',    //表示用父类的
                'key' => "" . $v['id'],
                'title' => $v['name'],
                'condition' => [],
                'value' => [],
                'unit' => '',
                'url' => '',
                'group' => 'channel',
                'child' => []
            ];
            $siteList = $allCountry[$v['name']];
            foreach ($siteList as $s => $si) {
                $site = [
                    'type' => '',    //表示用父类的
                    'key' => $si['code'],
                    'title' => $si['name'],
                    'condition' => [],
                    'value' => [],
                    'unit' => '',
                    'url' => '',
                    'group' => 'country',
                    'child' => []
                ];
                array_push($temp['child'], $site);
            }
            array_push($new_array, $temp);
        }
        $result['data'] = $new_array;
        $result['election'] = '勾选此处，表示当前规则不适用于下方所选账号（站点不参与此反选设置）';
        return $result;
    }


    /** 买手允许被分配SKU的频率（天）
     * @return mixed
     */
    private function buyerUseDay()
    {
        $result['type'] = 2;
        $result['title'] = '买手允许被分配SKU的频率（天）';
        $result['data'] = [];
        $temp = [
            0 => [
                'type' => 5,
                'key' => 'str',
                'title' => '大于',
                'value' => [],
                'condition' => [],
                'unit' => '天',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => [],
            ],

        ];
        $result['data'] = $temp;
        return $result;
    }

    /** 同一个账号简称（店铺账号），在X天内不允许被分配给相同的买手
     * @return mixed
     */
    private function channelBuyerDay()
    {
        $result['title'] = '同一个账号简称（店铺账号），在X天内不允许被分配给相同的买手';
        $result['type'] = 2;
        $result['data'] = [];
        $temp = [
            0 => [
                'type' => 5,
                'key' => 'str',
                'title' => 'X为：',
                'value' => [],
                'condition' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => [],
            ],

        ];
        $result['data'] = $temp;
        return $result;
    }
    //min end

    //指定备货仓库
    private function readyInventoryWarehouse()
    {
        $result['type'] = 1;
        $result['title'] = '指定备货仓库：';
        $warehouse_all = Cache::store('warehouse')->getWarehouse();
        $warehouse_tmp = array_filter($warehouse_all, function ($v) {
            if (6 != $v['type']) {
                return true;
            }
        });
        $warehouse_use = array_column($warehouse_tmp, 'name', 'id');
        foreach ($warehouse_use as $k => $v) {
            $result['data'][] = [
                'type' => 1,
                'key' => "" . $k,
                'title' => $v,
                'condition' => [],
                'value' => [],
                'unit' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ];
        }
        return $result;
    }

    //指定备货数量
    private function readyInventoryGoodsQty()
    {
        $result['type'] = 1;
        $result['title'] = '备货数量满足以下条件：';
        $temp = [
            0 => [
                'type' => 5,
                'key' => 'riqO',
                'title' => '',
                'condition' => [
                    '>=' => '≥',
                    '>' => '>',
                    '=' => '='
                ],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            1 => [
                'type' => 5,
                'key' => 'riqT',
                'title' => '',
                'condition' => [
                    '<' => '<',
                    '<=' => '≤',
                ],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ]
        ];
        $result['data'] = $temp;
        return $result;
    }

    //指定备货销售人员
    public function readyInventorySeller()
    {
        $result['type'] = 1;
        $result['title'] = '部门筛选：';

        $obj = new DepartmentService();
        $nodes = $obj->nodes();
        $treeNodes = $obj->tree([4,223], $nodes);
        $users = (new User())->getUsersFromSalesDepartment();
        $result['data'] = $this->parseNodes($treeNodes, $users, 'b-');
        return $result;
    }

    private function parseNodes($nodes, $users, $prefix = '')
    {
        $data = [];
        foreach ($nodes as $k => $v) {
            $tmp = [
                'type' => 1,
                'key' => $prefix . $v['id'],
                'title' => $v['name'],
            ];
            if (!empty($v['nodes'])) {
                $tmp['child'] = $this->parseNodes($v['nodes'], $users, 'g-');
            }
            if (empty($tmp['child'])) {
                $tmp['child'] = [];
            }
            //部门分组下的成员
            if (isset($users[$v['id']])) {
                foreach ($users[$v['id']] as $kk => $vv) {
                    array_unshift($tmp['child'], [
                        'type' => 1,
                        'key' => $kk,
                        'title' => $vv,
                        'user' => true,
                        'child' => []
                    ]);
                }
            }

            $data[] = $tmp;
        }
        return $data;
    }

    //指定备货上级审批人员
    private function readyInventoryApprover()
    {
        return $this->readyInventorySeller();
    }

    //指定备货上级审批人员
    private function readyInventoryApprover2()
    {
        return $this->readyInventorySeller();
    }


    /**
     * 毛重重量 指定范围
     * @return mixed
     */
    private function goodsWeight()
    {
        $result['type'] = 1;
        $result['title'] = '重量满足以下全部条件：';
        $temp = [
            0 => [
                'type' => 5,
                'key' => 'weiO',
                'title' => '',
                'condition' => [
                    '>=' => '≥',
                    '>' => '>',
                    '=' => '='
                ],
                'value' => [],
                'unit' => 'g',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            1 => [
                'type' => 5,
                'key' => 'weiT',
                'title' => '',
                'condition' => [
                    '<' => '<',
                    '<=' => '≤',
                ],
                'value' => [],
                'unit' => 'g',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ]
        ];
        $result['data'] = $temp;
        return $result;
    }

    /**
     * 采购一级审批人
     * @return mixed
     */
    private function firstApprove()
    {
        $result['type'] = 1;
        $result['title'] = '部门筛选：';

        $obj = new DepartmentService();
        $nodes = $obj->nodes('purchase');
        $treeNodes = $obj->tree([4,255,143,223], $nodes);
        $department_ids = (new \app\index\service\Department)->getDepartmentIds('purchase');
        $users = (new User())->getUserByDepartmentIds($department_ids);
        $result['data'] = $this->parseNodes($treeNodes, $users, 'b-');
        return $result;
    }

    /**
     * @desc 用户组织架构
     * @param array $nodes
     * @param array $users
     * @param string $prefix
     * @return array获取指定
     */
    private function userParseNodes($nodes, $users, $prefix = '')
    {
        $data = [];
        foreach ($nodes as $k => $v) {
            $tmp = [
                'key'=>$prefix .$k,
                'child'=>[],
                'other'=>'',
                'value'=>true,
                'operator'=>'',
            ];
            if (!empty($v['nodes'])) {
                $tmp['child'] = $this->userParseNodes($v['nodes'], $users, 'g-');
            }
            if (empty($tmp['child'])) {
                $tmp['child'] = [];
            }
            //部门分组下的成员
            if (isset($users[$v['id']]) && $users[$v['id']]) {
                foreach ($users[$v['id']] as $kk => $vv) {
                    array_unshift($tmp['child'], [
                        'key'=>$kk,
                        'user'=>true,
                        'child'=>[],
                        'other'=>'',
                        'value'=>true,
                        'operator'=>'',
                    ]);
                }
            }
            if(!$tmp['child']){
                continue;
            }
            $data[] = $tmp;
        }
        return $data;
    }

    /**
     * @desc 解析显示的销售人员
     * @author wangwei
     * @date 2018-11-9 17:41:14
     */
    public function parseReadyInventorySeller($item_value)
    {
        $return = [];
        $isRe = (new StockRuleExecuteService(null, null, null))->parseInventorySeller($item_value);
        if($isRe['user']){
            $res = (new UserModel())->alias('u')
                ->join('department_user_map du','u.id=du.user_id')
                ->where('u.id','IN',$isRe['user'])
                ->column('u.id,u.realname,du.department_id');
            $ret = [];
            foreach ($res as $v){
                $ret[$v['department_id']][$v['id']] = $v['realname'];
            }
            if ($ret) {
                $obj = new DepartmentService();
                $treeNodes = $obj->tree([4,23], $obj->nodes());
                $return = $this->userParseNodes($treeNodes, $ret, 'b-');
            }
        }
        return $return;
    }

    /**
     * @desc 解析显示的销售人员
     * @author wangwei
     * @date 2018-11-9 17:41:14
     */
    public function parseReadyInventorySellerOld($item_value){
        $return = [];
        $isRe = (new StockRuleExecuteService(null, null, null))->parseInventorySeller($item_value);
        if($isRe['user'] && $res = \app\common\model\User::alias('u')->join('department_user_map du','u.id=du.user_id')->where('u.id','IN',$isRe['user'])->column('u.id,u.realname,du.department_id')){
            $ret = [];
            foreach ($res as $v){
                $ret[$v['department_id']][$v['id']] = $v['realname'];
            }
            $obj = new DepartmentService();
            $treeNodes = $obj->tree([4,223], $obj->nodes());
            foreach ($treeNodes as $b_id=>$b_v){
                $b_tmp = [
                    'key'=>'b-' .$b_id,
                    'child'=>[],
                    'other'=>'',
                    'value'=>true,
                    'operator'=>'',
                ];
                if(isset($ret[$b_id])){
                    foreach ($ret[$b_id] as $u_id=>$b_name){
                        $b_tmp['child'][] = [
                            'key'=>$u_id,
                            'user'=>true,
                            'child'=>[],
                            'other'=>'',
                            'value'=>true,
                            'operator'=>'',
                        ];
                    }
                }
                foreach ($b_v['nodes'] as $g_id=>$g_v){
                    $g_tmp = [
                        'key'=>'g-' .$g_id,
                        'child'=>[],
                        'other'=>'',
                        'value'=>true,
                        'operator'=>'',
                    ];
                    if(isset($ret[$g_id])){
                        foreach ($ret[$g_id] as $u_id=>$u_name){
                            $g_tmp['child'][] = [
                                'key'=>$u_id,
                                'user'=>true,
                                'child'=>[],
                                'other'=>'',
                                'value'=>true,
                                'operator'=>'',
                            ];
                        }
                    }
                    if(!empty($g_tmp['child'])){
                        $b_tmp['child'][] = $g_tmp;
                    }
                }
                if(!empty($b_tmp['child'])){
                    $return[] = $b_tmp;
                }
            }
        }
        return $return;
    }

    /** wish物流运输费
     * @return mixed
     */
    private function wishShippingRate()
    {
        $result['type'] = 2;
        $result['title'] = 'wish物流运输费';
        $temp = [
            0 => $this->options(1, 'pcrO', '获取物流运费类型：'),
        ];
        $temp[0]['condition'] = [
            0 => '普货运费',
            1 => '特性运费'
        ];
        $result['data'] = $temp;
        $result['profile'] = '';
        return $result;
    }

    /** 发挂号时的物流运输费
     * @return mixed
     * @throws \think\Exception
     */
    private function wishShippingFee()
    {
        $result['type'] = 2;
        $result['title'] = '发挂号时的物流运输费（币种为站点币种）';
        $temp = [
            0 => [
                'type' => 5,
                'key' => 'riqT',
                'title' => '如果“平邮实际售价”',
                'condition' => [
                    '>=' => '>=',
                    '>' => '>',
                    '=' => '='
                ],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            1 => [
                'type' => 1,
                'key' => 'pcpT',
                'title' => '则：物流运输费改为：挂号运费',
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
        ];
        $result['data'] = $temp;
        return $result;
    }

    /**
     * 售后订单来源
     * @return mixed
     * @throws \think\Exception
     */
    private function afterSaleSource()
    {
        $result['type'] = 1;
        $channelList = Cache::store('channel')->getChannel();
        $where[] = ['status', '==', 0];
        $where[] = ['id', 'in' , [1,4,9]];
        $channelList = Cache::filter($channelList, $where, "id,name");
        $new_array = [];
        $accountLists = Cache::store('account')->getAccounts(true);
        foreach ($channelList as $k => $v) {
            $temp = [
                'type' => '',    //表示用父类的
                'key' => "" . $v['id'],
                'title' => $v['name'],
                'condition' => [],
                'value' => [],
                'unit' => '',
                'url' => '',
                'group' => 'channel',
                'child' => []
            ];
            $siteList = Cache::store('channel')->getSite($v['name']);
            foreach ($siteList as $s => $si) {
                $site = [
                    'type' => '',    //表示用父类的
                    'key' => $si['code'],
                    'title' => $si['name'],
                    'condition' => [],
                    'value' => [],
                    'unit' => '',
                    'url' => '',
                    'group' => 'site',
                    'child' => []
                ];
                array_push($temp['child'], $site);
            }

            $accountList = isset($accountLists[$v['name']]) ? $accountLists[$v['name']] : [];
            foreach ($accountList as $a => $ac) {
                if (!empty($ac)) {
                    $account = [
                        'type' => '',    //表示用父类的
                        'key' => "" . $ac['id'],
                        'title' => $ac['code'],
                        'condition' => [],
                        'value' => [],
                        'unit' => '',
                        'url' => '',
                        'group' => 'account',
                        'child' => []
                    ];
                    array_push($temp['child'], $account);
                }
            }
            array_push($new_array, $temp);
        }
        $result['data'] = $new_array;
        $result['election'] = '勾选此处，表示当前规则不适用于下方所选账号（站点不参与此反选设置）';
        return $result;
    }

    /** 纠纷退款金额  范围
     * @return mixed
     */
    private function disputeRefundAmount()
    {
        $result['type'] = 1;
        $result['title'] = '退款金额满足以下条件：';
        $temp = [
            0 => [
                'type' => 1,
                'key' => 'totO',
                'title' => '',
                'value' => [],
                'unit' => '',
                'desc' => '默认转换币种为人民币',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            1 => [
                'type' => 5,
                'key' => 'totT',
                'title' => '',
                'condition' => [
                    '>=' => '≥',
                    '>' => '>',
                    '=' => '='
                ],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            2 => [
                'type' => 5,
                'key' => 'totH',
                'title' => '',
                'condition' => [
                    '<' => '<',
                    '<=' => '≤',
                ],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ]
        ];
        $temp[0]['condition'] = OrderRuleCheckService::setCurrency();
        $result['data'] = $temp;
        return $result;
    }

    /**
     * 客服员列表
     * @return mixed
     */
    private function customerService()
    {
        $result['type'] = 1;
        $customerList = (new User())->staff('customer',0,0,0);
        $arr = [
            'type' => '',    //表示用父类的
            'key' => "",
            'title' => '客服员',
            'condition' => [],
            'value' => [],
            'unit' => '',
            'url' => '',
            'group' => 'customer',
            'child' => []
        ];
        foreach ($customerList as $k => $v) {
            $temp = [
                'type' => '',
                'key' => "" . $v['id'],
                'title' => $v['realname'],
                'condition' => [],
                'value' => [],
                'unit' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ];
            array_push($arr['child'], $temp);
        }
        $result['data'] = [];
        array_push($result['data'],  $arr);
        $result['election'] = '反选';
        return $result;
    }

    /**
     * @desc 采购数量比较
     */
    private function proposalQtyCompare()
    {
        $result['type'] = 1;
        $result['title'] = '采购计划中的任意商品采购数量满足以下选中的条件:';
        $temp = [
            0 => [
                'type' => 1,
                'key' => 'qC',
                'title' => '采购数量',
                'condition' => [
                    '>' => '>',
                    '>=' => '≥',
                    '=' => '=',
                    '<' => '<',
                    '<=' => '≤',
                    '!=' => '≠'
                ],
                'value' => [],
                'unit' => '',
                'desc' => '建议采购',
                'url' => '',
                'group' => '',
                'child' => []
            ]
        ];
        $result['data'] = $temp;
        return $result;
    }

    /**
     * @desc 采购类型
     */
    private function purchaseType()
    {
        $result['type'] = 1;
        $result['title'] = '';
        $temp = [
            0 => [
                'type' => '',
                'key' => '1',
                'title' => '正常采购',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            1 => [
                'type' => '',
                'key' => '2',
                'title' => '供应商多送',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            2 => [
                'type' => '',
                'key' => '3',
                'title' => '样品',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ]
        ];
        $result['data'] = $temp;
        return $result;
    }

    /**
     * 系统自动抓取订单拆包时
     * @return mixed
     */
    private function autoUnpack()
    {
        $result['type'] = 2;
        $result['title'] = "系统自动抓取订单拆包时：";
        $result['data'] = [];
        $temp = [
            0 => [
                'type' => '',
                'key' => 'auO',
                'title' => '是',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            1 => [
                'type' => '',
                'key' => 'auT',
                'title' => '否',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ]
        ];
        $result['data'] = $temp;
        return $result;
    }

    /**
     * 是否虚拟仓发货
     * @return mixed
     */
    private function virtualWarehouseDelivery()
    {
        $result['type'] = 2;
        $result['title'] = "是否虚拟仓发货：";
        $result['data'] = [];
        $temp = [
            0 => [
                'type' => '',
                'key' => 'vtO',
                'title' => '是',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            1 => [
                'type' => '',
                'key' => 'vtT',
                'title' => '否',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ]
        ];
        $result['data'] = $temp;
        return $result;
    }

    /**
     * @desc 采购阶段
     */
    private function purchasePhase()
    {
        $result['type'] = 1;
        $result['title'] = '';
        $temp = [
            0 => [
                'type' => '',
                'key' => '1',
                'title' => '采购计划',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            1 => [
                'type' => '',
                'key' => '2',
                'title' => '采购单不等待剩余',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            2 => [
                'type' => '',
                'key' => '3',
                'title' => '采购单作废',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ]
        ];
        $result['data'] = $temp;
        return $result;
    }

    /**
     * @desc 结算方式
     */
    private function balanceType()
    {
        $result['type'] = 1;
        $result['title'] = '';
        $balanceType = SupplierBalanceType::TYPE_TEXT;
        unset($balanceType[SupplierBalanceType::UNDEFIND]);
        $temp = [];
        foreach($balanceType as $k => $v){
            $temp[] = [
                'type' => '',
                'key' => (string)$k,
                'title' => $v,
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ];
        }
        $result['data'] = $temp;
        return $result;
    }

    /**
     * @desc 付款状态
     */
    private function paymentStatus()
    {
        $result['type'] = 1;
        $result['title'] = '';
        $temp = [
            0 => [
                'type' => '',
                'key' => '8',
                'title' => '已付款',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            1 => [
                'type' => '',
                'key' => '7',
                'title' => '未付款',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ],
            2 => [
                'type' => '',
                'key' => '9',
                'title' => '部分付款',
                'condition' => [],
                'value' => [],
                'unit' => '',
                'desc' => '',
                'url' => '',
                'group' => '',
                'child' => []
            ]
        ];
        $result['data'] = $temp;
        return $result;
    }



    /**
     *促销返点
     *
     */
    public function promotionRebate()
    {
        $result['type'] = 2;
        $result['title'] = '促销返点';
        $temp = [
            0 => $this->options(3, 'pgpO', '促销返点：', '', [], '1', '', '(默认值为1)')
        ];
        $result['data'] = $temp;
        $result['profile'] = '';
        return $result;
    }


    /**
     *退款
     *
     */
    public function refund()
    {
        $result['type'] = 2;
        $result['title'] = '退款为';
        $temp = [
            0 => $this->options(3, 'pgpO', '退款：', '', [], '5', '', '(默认值为5)')
        ];
        $result['data'] = $temp;
        $result['profile'] = '';
        return $result;
    }
}