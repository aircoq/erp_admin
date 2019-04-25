<?php
/**
 * Created by PhpStorm.
 * User: rondaful_user
 * Date: 2019/2/26
 * Time: 14:09
 */

namespace app\publish\service;


use app\common\model\ebay\EbayAccount;
use app\common\model\ebay\EbayBestOffer;
use app\common\model\ebay\EbayListing;
use app\common\model\ebay\EbaySite;
use app\common\model\User;
use app\common\service\Common;
use app\common\service\CommonQueuer;
use app\publish\helper\ebay\EbayPublish;
use app\publish\queue\EbaySyncBestOfferQueue;
use think\Exception;
use ebay\EbaySDK;

class EbayBestOfferService
{
    private $userId;
    const BEST_OFFER_TYPE = [
        'BuyerBestOffer' => 1,//买家第一次发起
        'BuyerCounterOffer' => 2,//卖家报价后，同一买家再次发起
        'SellerCounterOffer' => 3,//
    ];
    const BEST_OFFER_STATUS = [
        "Accepted" => 1,
        "Active" => 2,
        "AdminEnded" => 3,
        "Countered" => 4,
        "Declined" => 5,
        "Expired" => 6,
        "Pending" => 7,
        "PendingBuyerConfirmation" => 8,
        "PendingBuerPayment" => 9,
        "Retracted" => 10,
    ];


    public function __construct()
    {
        $userInfo = Common::getUserInfo();
        $this->userId = $userInfo['user_id']??0;
    }
    
    public function index($params)
    {
        $statusTxt = [
            1 => '接受议价',
            4 => '与买家进行议价，您的价格为',
            5 => '拒绝议价',
            6 => '已失效',
            7 => '等待处理',
            8 => '等待买家确认',
            9 => '等待买家付款',
        ];
        try {
            $wh = [];
            foreach ($params as $key => $value) {
                $value = trim($value);
                if ($value == '0') {

                } elseif (!$value) {
                    continue;
                }
                switch ($key) {
                    case 'bestOfferId':
                        $value = explode(',',$value);
                        $wh['best_offer_id'] = ['in',$value];
                        break;
                    case 'dateType':
                        if ($params['dateStart']??'' || $params['dateEnd']??'') {
                            $startTime = $params['dateStart'] ?? '' ? strtotime($params['dateStart'] . ' 00:00:00') : 0;
                            $endTime = $params['dateEnd'] ?? '' ? strtotime($params['dateEnd'] . ' 23:59:59') : time();
                            $wh['expire_time'] = ['between', [$startTime, $endTime]];
                        }
                        break;
                    case 'itemId':
                        $value = explode(',',$value);
                        $wh['item_id'] = ['in',$value];
                        break;
                    case 'status':
                        if ($value == 0 || $value == 1) {
                            $wh['local_status'] = $value;
                        } elseif ($value == 2) {
                            $wh['expire_time'] = ['<',time()];
                        }
                        break;
                    case 'accountId':
                        $wh['account_id'] = $value;
                        break;
                    case 'number':
                        $wh['number'] = $value;
                        break;
                }
            }
//            if (!isset($wh['expire_time'])) {
//                $wh['expire_time'] = ['>',time()];
//            }
            $page = $params['page']??0 ? $params['page'] : 1;
            $pageSize = $params['pageSize']??0 ? $params['pageSize'] : 50;
            $field = 'id,account_id,best_offer_id,expire_time,img,title,buyer_id,buyer_email,buyer_feedback_score,
                currency_code,item_id,item_price,best_offer_price,number,local_status,status,seller_offer_price,
                operator_id';
            $data = EbayBestOffer::where($wh)->field($field)->page($page,$pageSize)->order('id desc')->select();
            if (!$data) {
                return [
                    'data' => [],
                    'count' => 0,
                    'page' => 1,
                    'pageSize' => 50,
                ];
            }
            $data = collection($data)->toArray();
            $count = EbayBestOffer::where($wh)->count();

            $userIds = array_column($data,'operator_id');
            $realNames = User::whereIn('id',array_unique($userIds))->column('realname','id');

            $itemIds = array_unique(array_column($data,'item_id'));
            $itemSiteIds = EbayListing::whereIn('item_id',$itemIds)->column('site','item_id');
            $siteAbbr = EbaySite::whereIn('siteid',array_unique(array_values($itemSiteIds)))->column('abbreviation','siteid');

            foreach ($data as &$dt) {
                if (isset($itemSiteIds[$dt['item_id']]) && isset($siteAbbr[$itemSiteIds[$dt['item_id']]])) {
                    $siteAbb = $siteAbbr[$itemSiteIds[$dt['item_id']]];
                } else {
                    $siteAbb = 'unknown';
                }
                $expireTime = $dt['expire_time'];
                $dt['expire_time'] = date('Y-m-d H:i:s',$dt['expire_time']);
                $dt['buyer_info'] = preg_replace('/@.*$/','',$dt['buyer_email']).
                    '('.$dt['buyer_feedback_score'].')'.'【'.$siteAbb.'】'."\n".$dt['buyer_email'];
                $dt['item_price'] = $dt['currency_code'].$dt['item_price'];
                $dt['local_status'] = ($expireTime < time()) ? '已失效' : ($dt['local_status'] ? '已处理' : '未处理');
                $dt['site_id'] = $itemSiteIds[$dt['item_id']]??0;
                if ($expireTime < time()) {
                    $dt['status'] = '';
                } elseif ($dt['status'] == 4) {
                    $dt['status'] = $dt['seller_offer_price'] ? $statusTxt[$dt['status']].$dt['seller_offer_price'] :
                        '已在其他平台处理';
                } elseif ($dt['status'] == 11) {
                    $dt['status'] = '已在其他平台处理';
                } else {
                    $dt['status'] = $statusTxt[$dt['status']] ?? '';
                }
                $dt['operator_name'] = $realNames[$dt['operator_id']] ?? '';
                if (!$dt['seller_offer_price']) {
                    $dt['seller_offer_price'] = '';
                }
            }

            return  [
                'data' => $data,
                'count' => $count,
                'page' => $page,
                'pageSize' => $pageSize
            ];
        } catch (\Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }

    /**
     * 同步best offer
     * @param $params
     * @return array
     * @throws Exception
     */
    public function sync($params)
    {
        try {
            if ($params['bestOfferIds']??'') {
                //部分同步
                $bestOfferIds = explode(',', $params['bestOfferIds']);
                foreach ($bestOfferIds as $bestOfferId) {
                    (new CommonQueuer(EbaySyncBestOfferQueue::class))->push(['bestOfferId'=>$bestOfferId]);
                }
            } else {//全部同步
                //获取名下账号id
                $accountIds = (new EbayPublish())->getUnderlineSalesAccountIds($this->userId);
                foreach ($accountIds as $accountId) {
                    (new CommonQueuer(EbaySyncBestOfferQueue::class))->push(['accountId'=>$accountId]);
                }
            }
            return ['message'=>'成功加入同步队列,请稍后刷新查看结果'];
        } catch (\Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }

    /**
     * 执行同步
     * @param $params
     * @throws Exception
     */
    public function doSync($params)
    {
        try {
            if ($params['bestOfferId']??'') {
                $info = EbayBestOffer::where('best_offer_id',$params['bestOfferId'])->field('account_id,item_id')->find();
                $accountId = $info['account_id'];
                $data['BestOfferID'] = $params['bestOfferId'];
                $data['ItemID'] = $info['item_id'];//api文档说BestOfferID存在的情况下，ItemID的值会被忽略，而且本身是可选的，然而不设置会报错
            } else {
                $accountId = $params['accountId'];
            }
            $account = EbayAccount::field(EbayPublish::ACCOUNT_FIELD_TOKEN)->find(['id'=>$accountId]);
            if (!$account) {
                throw new Exception('账号信息获取失败');
            }
            $config = $account->toArray();
            $data['DetailLevel'] = ['ReturnAll'];
            $pageNum = 1;
            while (1) {//循环页
                $data['Pagination']['EntriesPerPage'] = 200;
                $data['Pagination']['PageNumber'] = $pageNum;

                $response = EbaySDK::sendRequest('Trading', $config, 'getBestOffers', $data);
                if ($response['result'] === false) {
                    throw new Exception($response['message']);
                }
                //处理数据
                $response = $response['data'];
                $totalPage = $response['PaginationResult']['TotalNumberOfPages']??0;//总页数

                $itemBestOffers = [];
                if ($response['ItemBestOffersArray']??'') {
                    //按账号请求返回
                    $itemBestOffers = $response['ItemBestOffersArray']['ItemBestOffers'] ?? [];
                } elseif ($response['BestOfferArray'] ?? '' && $response['Item'] ?? '') {
                    //设置了best offer id
                    $itemBestOffers[0] = [
                        'BestOfferArray' => $response['BestOfferArray'],
                        'Item' => $response['Item'],
                    ];
                }
                foreach ($itemBestOffers as $kItem => $itemBestOffer) {//以item为单位循环
                    $item = $itemBestOffer['Item'];
                    $img = EbayListing::where('item_id', $item['ItemID'])->value('img');
                    $bestOffers = $itemBestOffer['BestOfferArray']['BestOffer'];
                    $rows = [];
                    $bestOfferIds = [];
                    foreach ($bestOffers as $bestOffer) {
                        $rows[$bestOffer['BestOfferID']] = [
                            'account_id' => $accountId,
                            'best_offer_id' => $bestOffer['BestOfferID'],
                            'best_offer_type' => self::BEST_OFFER_TYPE[$bestOffer['BestOfferCodeType']],
                            'expire_time' => strtotime($bestOffer['ExpirationTime']),
                            'img' => $img??'',
                            'title' => $item['Title'] ?? '',
                            'buyer_id' => $bestOffer['Buyer']['UserID'],
                            'buyer_email' => $bestOffer['Buyer']['Email'],
                            'buyer_feedback_score' => $bestOffer['Buyer']['FeedbackScore']??0,
                            'item_id' => $item['ItemID'],
                            'item_price' => $item['BuyItNowPrice']['value'] ?? 0.00,
                            'best_offer_price' => (self::BEST_OFFER_TYPE[$bestOffer['BestOfferCodeType']] > 2) ? 0.00 : $bestOffer['Price']['value'],
                            'seller_offer_price' => (self::BEST_OFFER_TYPE[$bestOffer['BestOfferCodeType']] > 2) ? $bestOffer['Price']['value'] : 0.00,
                            'currency_code' => $bestOffer['Price']['currencyID'],
                            'number' => $bestOffer['Quantity'],
//                            'local_status' => 0,
                            'status' => self::BEST_OFFER_STATUS[$bestOffer['Status']],
                            'buyer_message' => $bestOffer['BuyerMessage'] ?? '',
                            'seller_message' => $bestOffer['SellerMessage'] ?? '',
                        ];
                        $rows[$bestOffer['BestOfferID']]['local_status'] = in_array($bestOffer['Status'],[1,4,5]) ? 1 : 0;
                        $bestOfferIds[] = $bestOffer['BestOfferID'];
                    }
                    //查询数据库中已存在的
                    $wh = [
                        'item_id' => $item['ItemID'],//获取该item id下的所有best offer
                    ];
                    $ids = EbayBestOffer::where($wh)->column('id,status,local_status,seller_offer_price', 'best_offer_id');
                    $usedBestOfferIds = [];
                    foreach ($rows as $boId => &$row) {
                        if ($ids[$boId] ?? '') {
                            $row['id'] = $ids[$boId]['id'];
                            $usedBestOfferIds[] = $boId;
                        }
                    }
                    if (!($params['bestOfferId']??'') && ($unReturnBOIds = array_diff(array_keys($ids),$usedBestOfferIds))) {//没有指定best offer id,是按账号同步的
                        //处理未返回的
                        $unReturnBestOffers = [];
                        foreach ($unReturnBOIds as $unReturnBOId) {
                            if ($ids[$unReturnBOId]['local_status'] == 0) {//同步本地未处理的
                                $ids[$unReturnBOId]['status'] = 11;//本地未处理，其他平台处理了
                                $ids[$unReturnBOId]['local_status'] = 1;//标记为已处理
                                $unReturnBestOffers[] = $ids[$unReturnBOId];
                            }
                        }
                        (new EbayBestOffer())->saveAll($unReturnBestOffers);
                    }
                    (new EbayBestOffer())->saveAll($rows);//批量更新
                }
                if ($pageNum >= $totalPage) {
                    break;
                }
                $pageNum++;
            }
        } catch (\Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }

    /**
     * 删除
     * @param $ids
     */
    public function del($ids)
    {
        EbayBestOffer::destroy($ids);
    }

    /**
     * 处理议价
     * @param $params
     * @return array
     * @throws Exception
     */
    public function deal($params)
    {
        try {
            $bestOfferIds = explode(',',$params['bestOfferIds']);
            $field = 'id,best_offer_id,item_id,account_id,currency_code';
            $bestOffers = EbayBestOffer::whereIn('best_offer_id',$bestOfferIds)->column($field,'best_offer_id');
            if (!$bestOffers) {
                throw new Exception('获取best offer信息失败');
            }
            $bestOffer = array_values($bestOffers)[0];
            $accout = EbayAccount::where('id',$bestOffer['account_id'])->field(EbayPublish::ACCOUNT_FIELD_TOKEN)
                ->find();
            if (!$accout) {
                throw new Exception('账号信息获取失败');
            }
            $config = $accout->toArray();
            $data['BestOfferID'] = $bestOfferIds;
            $data['ItemID'] = $bestOffer['item_id'];
            $data['Action'] = ucfirst($params['action']);
            if ($params['action'] == 'counter') {
                $data['CounterOfferPrice'] = [
                    'value' => floatval($params['counterOfferPrice']),
                    'currencyID' => $bestOffer['currency_code'],
                ];
                $data['CounterOfferQuantity'] = (int)$params['counterOfferQuantity'];
            }
            if ($params['message']??'') {
                $data['SellerResponse'] = $params['message'];
            }
            $response = EbaySDK::sendRequest('Trading',$config,'respondToBestOffer',$data);
            if ($response['result'] === false) {
                if (strpos($response['message'],'20142') !== false) {
                    EbayBestOffer::update(['local_status'=>1],['best_offer_id'=>['in',$bestOfferIds]]);
                    throw new Exception('此条best offer已处理过或已失效');
                }
                throw new Exception($response['message']);
            }
            $res = $response['data'];
            $bestOfferStatus = $res['RespondToBestOffer']['BestOffer'];
            foreach ($bestOfferStatus as $k => $bos) {
                if ($bos['CallStatus'] == 'Success') {//请求成功
                    if ($params['action'] == 'accept') {
                        $bestOffers[$bestOfferIds[$k]]['status'] = self::BEST_OFFER_STATUS['Accepted'];//接受议价
                    } elseif ($params['action'] == 'counter') {//议价
                        $bestOffers[$bestOfferIds[$k]]['seller_offer_price'] = $params['counterOfferPrice'];
                        $bestOffers[$bestOfferIds[$k]]['status'] = self::BEST_OFFER_STATUS['Countered'];
                    } elseif ($params['action'] == 'decline') {//拒绝
                        $bestOffers[$bestOfferIds[$k]]['status'] = self::BEST_OFFER_STATUS['Declined'];
                    }
                }
                $bestOffers[$bestOfferIds[$k]]['local_status'] = 1;
            }
            $update = array_values($bestOffers);
            (new EbayBestOffer())->saveAll($update);
            return ['message'=>'提交成功，请及时同步最新状态'];
        } catch (\Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }

}