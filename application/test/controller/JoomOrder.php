<?php

namespace app\test\controller;

// use think\Controller;
use app\api\service\Automation;
use app\api\service\Server;
use app\carrier\service\PackageLabelFileService;
use app\common\cache\driver\PandaoAccountCache;
use app\common\model\Account;
use app\common\model\AccountCompanyLog;
use app\common\model\AccountLog;
use app\common\model\AccountSite;
use app\common\model\BrowserCustomer;
use app\common\model\cd\CdCarrier;
use app\common\model\ChannelSuperSite;
use app\common\model\ChannelUserAccountMapLog;
use app\common\model\DepartmentUserMap;
use app\common\model\monthly\MonthlyTargetAmount;
use app\common\model\Order;
use app\common\model\OrderPackage;
use app\common\model\OrderRuleLog;
use app\common\model\pandao\PandaoAccount;
use app\common\model\paytm\PaytmShippingCarriers;
use app\common\model\ReportUnpackedByDate;
use app\common\model\ServerLog;
use app\common\model\ServerUserAgent;
use app\common\model\ServerUserMap;
use app\common\model\VirtualOrderMission;
use app\common\model\VirtualOrderRefundApply;
use app\common\model\walmart\WalmartCarrier;
use app\common\model\walmart\WalmartOrderDetail;
use app\common\model\yandex\YandexOrder;
use app\common\service\ChannelAccountConst;
use app\common\service\Common;
use app\common\service\Encryption;
use app\common\service\Report;
use app\common\service\Twitter;
use app\common\service\UniqueQueuer;
use app\customerservice\queue\DistributionStockInCallBack;
use app\customerservice\service\MsgRuleHelp;
use app\goods\service\GoodsSku;
use app\index\controller\AccountCompany;
use app\index\controller\AmazonAccountHealth;
use app\index\controller\Software;
use app\index\controller\VirtualUser;
use app\index\controller\WishShippingRate;
use app\index\controller\YandexAccount;
use app\index\queue\AccountUserMapBatchQueue;
use app\index\queue\AccountUserMapDelQueue;
use app\index\queue\AccountUserMapNewQueue;
use app\index\queue\AliexpressAccountHealthSendQueue;
use app\index\queue\AmazonAccountHealthReceiveQueue;
use app\index\queue\AmazonAccountHealthSendQueue;
use app\index\queue\DepartmentUserMapBatchQueue;
use app\index\queue\EbayAccountHealthExportQueue;
use app\index\queue\ServerExportQueue;
use app\index\queue\ServerUserReceive;
use app\index\service\AccountCompanyService;
use app\index\service\AccountUserMapService;
use app\index\service\AliexpressAccountHealthService;
use app\index\service\AmazonAccountHealthService;
use app\index\service\AutomaticSetService;
use app\index\service\BasicAccountService;
use app\index\service\CdAccountService;
use app\index\service\ChannelConfig;
use app\index\service\ChannelNodeService;
use app\index\service\Currency;
use app\index\service\Dashboard;
use app\index\service\Department;
use app\index\service\DepartmentUserMapService;
use app\index\service\EbayAccountHealthService;
use app\index\service\ManagerServer;
use app\index\service\MemberShipService;
use app\index\service\SoftwareService;
use app\index\service\User;
use app\index\service\VirtualUserService;
use app\index\service\WishAccountHealthService;
use app\index\service\WishShippingRateService;
use app\index\task\AmazonAccountHealthTask;
use app\index\task\ServerReportTimePastTask;
use app\internalletter\service\InternalLetterService;
use app\order\controller\ManualOrder;
use app\order\controller\StockOrder;
use app\order\controller\VirtualOrder;
use app\order\controller\VirtualRefund;
use app\order\interfaces\PaytmSynchronous;
use app\order\interfaces\WalmartSynchronous;
use app\order\queue\CdOrderQueue;
use app\order\queue\DistributionNotice;
use app\order\queue\JoomOrderByOrderIdQueue;
use app\order\queue\JumiaOrderByOrderIdQueue;
use app\order\queue\JumiaOrderItemQueue;
use app\order\queue\JumiaOrderQueue;
use app\order\queue\OrderExportQueue;
use app\order\queue\OrderPackageUpdateQueue;
use app\order\queue\OrderUpdateQueue;
use app\order\queue\PandaoOrderQueue;
use app\order\queue\PaytmOrderByOrderIdQueue;
use app\order\queue\VirtualOrderAutomationTaskerQueue;
use app\order\queue\WalmartOrderAckQueue;
use app\order\queue\WriteBackCdOrder;
use app\order\queue\WriteBackDistributionOrder;
use app\order\queue\YandexOrderQueue;
use app\order\queue\ZoodmallOrderQueue;
use app\order\service\CdOrderService;
use app\order\service\InvoiceRecordService;
use app\order\service\JoomOrderService;
use app\order\service\JumiaOrderService;
use app\order\service\ManualOrderService;
use app\order\service\OrderExportService;
use app\order\service\OrderHelp;
use app\order\service\OrderRuleCheckService;
use app\order\service\PaytmOrderService;
use app\order\service\Resource;
use app\order\service\ShippingTrackingService;
use app\order\service\VirtualOrderHelp;
use app\order\service\VirtualOrderHoldService;
use app\order\service\VirtualRuleCheckService;
use app\order\service\ZoodmallOrderService;
use app\order\task\CdToLocalOrder;
use app\order\task\DuplicateOrder;
use app\order\task\JoomOrderSynchronous;
use app\order\task\JoomToLocalOrder;
use app\order\task\JumiaToLocalOrder;
use app\order\task\OrderShortSeller;
use app\order\task\PandaoOrder;
use app\order\task\PandaoOrderSynchronous;
use app\order\task\PandaoToLocalOrder;
use app\order\task\PaytmOrder;
use app\order\task\PaytmToLocalOrder;
use app\order\task\ShippingAgainByOrder;
use app\order\task\VirtualTaskTimeOut;
use app\order\task\YandexToLocalOrder;
use app\order\task\ZoodmallToLocalOrder;
use app\publish\service\PricingRuleService;
use app\publish\task\AliexpressProductOnSelling;
use app\purchase\service\SupplierService;
use app\purchase\task\GetUnpackParcelsCount;
use app\report\filter\MonthlyTargetAmountFilter;
use app\report\queue\CustomerMessageExportQueue;
use app\report\queue\OrderLackExportQueue;
use app\report\queue\PublishbyPickingExportQueue;
use app\report\queue\PublishbyShelfExportQueue;
use app\report\queue\PublishbyTimeExportQueue;
use app\report\queue\StatisticByGoodsExportQueue;
use app\report\service\MonthlyTargetAmountService;
use app\report\service\MonthlyTargetDepartmentService;
use app\report\service\MonthlyTargetDepartmentUserMapService;
use app\report\service\OrderLackService;
use app\report\service\StatisticByGoods;
use app\report\service\StatisticGoods;
use app\report\service\StatisticPicking;
use app\report\service\WarehousePackageService;
use app\report\task\AmazonAccountMonitor;
use app\report\task\MessageStatisticReport;
use app\report\task\WriteBackSkuSizeTask;
use app\report\task\WriteBackSkuWeightTask;
use app\report\task\WriteBackWarehouseShippedTask;
use app\report\task\WriteBackWarehouseShortageTask;
use app\report\task\WriteBackWarehouseUnShippedTask;
use app\warehouse\controller\PackageCarrier;
use app\warehouse\controller\StockLack;
use app\warehouse\service\ReturnWaitShelfService;
use app\warehouse\service\ReturnWaitShelvesService;
use app\warehouse\service\ShippingMethod;
use cd\CdBaseApi;
use cd\CdOrderApi;
use function GuzzleHttp\Psr7\try_fopen;
use jumia\JumiaOrderApi;
use pandao\PandaoOrdersApi;
use paytm\PaytmOrderApi;
use recognition\RecognitionApi;
use service\shipping\operation\JoomOnLine;
use service\shipping\operation\JumiaOnLine;
use superbrowser\SuperBrowserBaseApi;
use think\Exception;
use think\Request;
use app\common\controller\Base;
use think\Loader;
use app\common\cache\Cache;
use app\common\model\Country as CountryModel;
use app\order\service\OrderService;
use joom\JoomOrdersApi;
use think\Db;
use app\order\task\JoomOrder as JoomOrderTask;

use app\order\task\JoomToLocalOrder as JTLOrder;
use paytm\PaytmAccountApi as PAA;
use paytm\PaytmCatalogApi as PCA;
use paytm\PaytmOrderApi as POA;
use pandao\PandaoOrdersApi as PO;
use app\order\task\PaytmOrder as POTask;
use app\index\controller\PaytmAccount as CPA;
use app\order\task\PandaoToLocalOrder as TPTLO;

use app\common\service\Twitter as ServiceTwitter;

use app\order\queue\PaytmOrderQueue as QPOQ;

use app\order\task\PaytmToLocalOrder as TPYTLO;

use app\index\service\PaytmAccountService;
use service\shipping\ShippingApi;
use app\order\queue\PandaoOrderQueue as TPO;

use app\order\queue\AmazonOrderUploadNewQueue as QAOUN;

use walmart\WalmartBaseApi;
use walmart\WalmartOrderApi;
use app\order\service\WalmartOrderService;
use app\order\task\WalmartOrder;
use  app\order\queue\WalmartOrderQueue;
use app\order\task\WalmartToLocalOrder;

use app\customerservice\queue\EbayMessageQueue;
use app\customerservice\queue\EbayMyMessageQueue;
use app\customerservice\queue\EbayOutboxMessageQueue;

use app\index\service\AccountService;
use app\order\queue\JoomOrderQueue;
use app\common\model\joom\JoomCarrier;
use zoodmall\ZoodmallBaseApi;
use zoodmall\ZoodmallOrderApi;
use app\order\service\PackageService;

/**
 * @module 订单系统
 * @title joom订单
 * @url /joom-orders
 * @author RondaFul
 *
 */
class JoomOrder extends Base
{

    use \app\common\traits\User;

    public function saveImg($img)
    {
        $suplierService = new SupplierService();
        $fileResult = $suplierService->base64DecImg(base64_encode($img), 'upload/baseaccount/' . date('Y-m-d'), time());
        return $fileResult;
    }

    public function testVirtual()
    {
        $order_ids = ['123241', 'green', 'blue', 'orange', 'blue', '1', 3, 1, 242, 11, 11];
        $order_ids = array_flip($order_ids);
        $list = array_keys($order_ids);

//        $virtualUser = new VirtualUserService();
//        $list = $virtualUser->getAllMessage([]);
//        $list = (new VirtualUserService())->getSingleTaskList();
        var_dump($list);
        die;

//        $list = (new VirtualOrderRefundApply())->column('refund_status','mission_id');
//        foreach ($list as $k => $v){
//            $save['refund_status'] = $v;
//            $saveWhere['id'] = $k;
//            (new VirtualOrderMission())->save($save,$saveWhere);
//        }
        echo 'ok';
        die;

        for ($i = 1; $i < 1000; $i++) {
            $re = Cache::store('JoomAccount')->getAccountTimes($i % 200);
            var_dump($re);
        }
        $re = Cache::store('JoomAccount')->getAccountTimes(400);
        var_dump($re);
        die;
//        return (new VirtualRefund())->getTask(1);
        return (new VirtualRefund())->read(2);

//        $data = (new VirtualRefund())->read(140);

        echo json_encode($data);
        die;

        $delaut['account_id'] = '1';
        $delaut['account_name'] = '4564';
        $delaut['keyword'] = '1111';
        $delaut['product_location'] = '111';
        $delaut['product_link'] = '111';
        $delaut['remark'] = '测试';
        $delaut['type'] = 1;
        $delaut['sku_id'] = '100000';
        $delaut['sku'] = 'BL9989501';

        $task['task_time'] = '2018-5-18';
        $task['quantity'] = 5;
        $task['seller_cost'] = 50;
        $task['estimate_cost'] = 80;
        $task['order_currency'] = 'USD';
        $task['msg_time'] = '2018-5-18';
        $delaut['time_quantity'][] = $task;

        $task['task_time'] = '2018-6-18';
        $task['quantity'] = 3;
        $task['seller_cost'] = 5;
        $task['estimate_cost'] = 16;
        $task['order_currency'] = 'USD';
        $task['msg_time'] = '2018-6-18';
        $delaut['time_quantity'][] = $task;


        echo json_encode([$delaut]);
        die;
        $data = Cache::store('Currency')->getCurrency();
        var_dump($data);
        die;
        $api = new VirtualOrder();
        return $api->missionType();
    }

    public function synchronousTest()
    {


//        $i = 9000;
//        foreach ($data as $datum) {
//            $one = [
//                'carrier_id' => $i ++,
//                'shipping_carrier' => $datum,
//                'description' => $datum,
//                'created_time' => time(),
//                'update_time' => time(),
//            ];
//            (new CdCarrier())->insert($one);
//        }
//        echo 'ok';die;

        // hjunscd-1810101805O868C
        //hjunscd-1810101650O96ZI
        //hjunscd-1810101543OA4J5

        $data = [];
        $data['account_id'] = 5;
        $data['order_id'] = '1810101805O868C';
        $data['shipped_time'] = date('Y-m-d H:i:s', time());

        $shipment_detail = [
            'tracking_number' => 'PBAI004598970',
            'shipping_carrier' => 'La Poste-Courrier'
        ];
        $data['shipment_detail'] = $shipment_detail;

        $cd = new CdOrderService();
        $response = $cd->completeShipped($data);
        var_dump($response);
    }

    public function addOrderByid()
    {


        $api = new \app\order\task\WalmartToLocalOrder();
        $re = $api->execute();
        die;


        $accountid = 275;
        $shop = Cache::store('JoomShop')->getTableRecord($accountid);
        $shop['access_token'] = 'SEV0001MTUzNzY4NTk0M3xMYjlFLVpfNnpob2l4VGVDc0JVTDVEVEFpRXdHaUJVS2hFM1hhRG1LV3E4TTlicThELWJydlRDQ0U3STdPSGtLR2hGQzJGVXBaTVUxQWdFeGtrUHNKTktYc21Cem1LZjJ4THV1RlFrMWpYRWpwbUZMMWtKVkFKSDg4cXBGLVJRLUlVdmFEdGhGWExoenY1RVAycWdhN2ZVV2VDazVDTG5iV2xEalE1ZTROWDc1QlZjPXxSNr2ulUXlwIB6Wf9v85xpqN5L_BdJzhbXeQXKaAJcXA==';
        $id = 'XVMX6PL';

//        $shop['client_id'] = '908852b156fcd72f';
//        $shop['client_secret'] = 'db7c22435a4cc8b35c72bb7f89d7fcdc';
        $apiServer = new JoomOrderService();
//        var_dump($shop);die;
        $re = $apiServer->downOrderByOrderIds($accountid, $shop, $id);
        var_dump('ok', $re);
        die;
    }

    public function toOrder()
    {
        $apiServer = new \app\order\task\JoomToLocalOrder();
        $re = $apiServer->execute();
        var_dump('ok', $re);
        die;
    }

    public function testamazonAccountHealth()
    {



        $serv = new AmazonAccountHealthService();
        $re = $serv->sendAccount2Spider(3386, ChannelAccountConst::channel_amazon);

        $re = (new WarehousePackageService())->logUnfilled();
        var_dump($re);
        die;

        $data = [
            'order' => [
                'ss',
            ],
            'order_detail' => [
                'sss'
            ],
        ];

        echo json_encode($data);
        die;
        //发送
//        $api = (new AmazonAccountHealthSendQueue(60))->execute();

        echo 'ok';
        die;

//        $data = json_decode('{ "channel_id": "3", "account_id": "14", "user_ids": [ "332", 157 ], "user": { "user_id": 2228, "realname": "李佰敏", "username": "13535050984" } }',true);
        $data = json_decode('{ "channel_id": "1", "account_id": "76", "addIds": [ "1053" ], "delIds": [ 332 ], "user": { "user_id": 2228, "realname": "李佰敏", "username": "13535050984" } }', true);
        $re = (new AccountUserMapNewQueue($data))->execute();
        var_dump($re);

//        $data = json_decode('{"time":"2018-08-24 11:49:22","data":{"HealthData":"{\"Site\":\"UK\",\"AccountId\":\"4\",\"Odrsc\":\"0\",\"TransferAmountBD\":\"21 Aug 2018*\",\"AccountNumber\":\"huananzheng\",\"TransferAmountAD\":\"4 Sep 2018*\",\"TransferAmountA\":\"\u00a374.20\",\"TransferAmountB\":\"\u00a350.52\",\"GrabbingTime\":\"2018-08-24\",\"Currency\":\"\u8d27\u5e01\u7b26\u53f7\u5f85\u5904\u7406\",\"Odrac\":\"0\",\"LoginStatus\":\"true\",\"HintInformation\":\"You have 2 undispatched orders for which you need to confirm dispatch in order to be paid.\",\"Lpa\":\"\u6700\u8fd1\u4ed8\u6b3e\u91d1\u989d\u5f85\u5904\u7406\",\"Balance\":\"250.54\"}","account":"ludasn@outlook.com","status":"true","message":""}}',true);
        var_dump($data);
        die;

        $serv = new AmazonAccountHealthService();
        $re = $serv->sendAccount2Spider(3386, ChannelAccountConst::channel_amazon);
//       $re = $serv->saveHealthData($data['data']);
//        $api = new AmazonAccountHealthReceiveQueue($data['data']);
//        $re = $api->execute();
//        var_dump($re);die;
//        $api = new AmazonAccountHealthService();
//        $re = $api->openAmazonHealth(4,60);


        //接收
//        $data = '4:' . http_build_query($str);
//        $api = new AmazonAccountHealthReceiveQueue($data);
//        $re = $api->execute();

//      $re = $api->readGoal(4);

        var_dump($re);
        die;
    }

    public function testReportByMessage()
    {
        // [buyer_qauntity,message_quantity,dispute_quantity] 回复买家数,站内信处理数,纠纷处理数

        $re = \app\common\service\Report::statisticMessage(1, 2, time(), [
            'buyer_qauntity' => 1,   //回复买家数
            'message_quantity' => 2, //站内信处理数
            'dispute_quantity' => 4,  //纠纷处理数
        ]);
//        $re = Report::statisticMessage(2, 2, time(), [
//            'buyer_qauntity' => 1,
//            'quantity' => 6,
//        ]);
//        $re = Report::statisticMessage(3, 3, time(), [
//            'buyer_qauntity' => 1,
//            'quantity' => 7,
//        ]);
//        $re = Report::statisticMessage(4, 3, time(), [
//            'buyer_qauntity' => 1,
//            'quantity' => 8,
//        ]);
//

        $api = new \app\report\task\MessageStatisticReport();
        $re = $api->execute();
        var_dump($re);
        echo 'ok';
        die;
    }

    public function testRuleLog()
    {

        $data = json_decode('[{"item_id":1,"item_source":"source","item_value":[{"operator":{"sel":""},"key":"1","value":true,"other":"","group":"channel","child":[{"operator":{"sel":""},"key":"50","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"57","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"102","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"111","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"113","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"117","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"131","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"134","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"136","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"138","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"140","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"142","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"143","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"147","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"149","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"155","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"156","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"167","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"175","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"181","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"243","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"245","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"248","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"267","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"277","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"316","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"317","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"336","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"337","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"349","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"350","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"351","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"376","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"428","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"473","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"474","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"475","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"477","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"479","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"482","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"491","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"517","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"518","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"521","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"594","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"752","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"753","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"776","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"797","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"798","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"799","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"800","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"801","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"832","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"851","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"910","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"912","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"913","value":true,"other":"","group":"account","child":[],"election":0},{"operator":{"sel":""},"key":"922","value":true,"other":"","group":"account","child":[],"election":0}],"election":0}]},{"item_id":2,"item_source":"warehouse","item_value":[{"operator":{"sel":""},"key":"2","value":true,"other":"","group":"","child":[],"election":0}]},{"item_id":3,"item_source":"transport","item_value":[{"operator":{"sel":""},"key":"1","value":true,"other":"","group":"","child":[{"operator":{"sel":""},"key":"UK_OtherCourier24","value":true,"other":"","group":"","child":[],"election":0},{"operator":{"sel":""},"key":"UK_OtherCourier48","value":true,"other":"","group":"","child":[],"election":0},{"operator":{"sel":""},"key":"UK_OtherCourier3Days","value":true,"other":"","group":"","child":[],"election":0},{"operator":{"sel":""},"key":"UK_OtherCourier5Days","value":true,"other":"","group":"","child":[],"election":0},{"operator":{"sel":""},"key":"UK_OtherCourier","value":true,"other":"","group":"","child":[],"election":0},{"operator":{"sel":""},"key":"UK_SellersStandardRate","value":true,"other":"","group":"","child":[],"election":0}],"election":0}]},{"item_id":41,"item_source":"containsLogisticsAttributes","item_value":[{"operator":{"sel":""},"key":"1","value":true,"other":"","group":"","child":[],"election":0},{"operator":{"sel":""},"key":"256","value":true,"other":"","group":"","child":[],"election":0},{"operator":{"sel":""},"key":"1024","value":true,"other":"","group":"","child":[],"election":0},{"operator":{"sel":""},"key":"2048","value":true,"other":"","group":"","child":[],"election":0},{"operator":{"sel":""},"key":"4096","value":true,"other":"","group":"","child":[],"election":0},{"operator":{"sel":""},"key":"8192","value":true,"other":"","group":"","child":[],"election":0},{"operator":{"sel":""},"key":"16384","value":true,"other":"","group":"","child":[],"election":0},{"operator":{"sel":""},"key":"131072","value":true,"other":"","group":"","child":[],"election":0}]},{"item_id":23,"item_source":"weight","item_value":[{"operator":"<=","key":"weiT","value":"3000","other":"","group":"","child":[],"election":0},{"operator":">","key":"weiO","value":"260","other":"","group":"","child":[],"election":0}]},{"item_id":5,"item_source":"target","item_value":[{"operator":{"sel":""},"key":"GB","value":true,"other":"","group":"","child":[],"election":0}]},{"item_id":14,"item_source":"totalAmount","item_value":[{"operator":"GBP","key":"totO","value":true,"other":"","group":"","child":[],"election":0},{"operator":"<=","key":"totH","value":"20","other":"","group":"","child":[],"election":0}]}]', true);
//        var_dump($data);die;
        $re = (new Resource())->getValue($data[0]);
//        $re = json_decode(null,true);
        var_dump($re);

//        var_dump(empty('0'));die;
//        var_dump(Cache::store('Shipping')->getFullShippingName(0));
        die;
        $old = '{"title":"瑞典-销售审核是否发货","action_type":"3","action_value":"","channel_id":"0","status":"0","start_time":"1491819600","end_time":0,"create_time":1523948207,"update_time":1523948207,"sort":9999,"operator":"古丽欢","operator_id":431,"rule":[{"item_id":1,"item_source":"source","item_value":[{"operator":{"sel":""},"key":"1","value":true,"other":"","group":"channel","child":[]},{"operator":{"sel":""},"key":"2","value":true,"other":"","group":"channel","child":[]},{"operator":{"sel":""},"key":"4","value":true,"other":"","group":"channel","child":[]}]},{"item_id":2,"item_source":"warehouse","item_value":[{"operator":{"sel":""},"key":"2","value":true,"other":"","group":"","child":[]}]},{"item_id":5,"item_source":"target","item_value":[{"operator":{"sel":""},"key":"Europe","value":true,"other":"","group":"","child":[{"operator":{"sel":""},"key":"SE","value":true,"other":"","group":"","child":[]}]}]}]}';
        $new = '{"title":"瑞典-销售审核是否发货","action_type":"3","action_value":"","channel_id":"0","status":"0","start_time":"1491819600","end_time":0,"update_time":1535598526,"operator":"李佰敏","operator_id":2228,"rule":[{"item_id":1,"item_source":"source","item_value":[{"operator":{"sel":""},"key":"1","value":true,"other":"","group":"channel","child":[{"operator":{"sel":""},"key":"Ireland","value":true,"other":"","group":"site","child":[],"election":0},{"operator":{"sel":""},"key":"Malaysia","value":true,"other":"","group":"site","child":[],"election":0}],"election":1},{"operator":{"sel":""},"key":"2","value":true,"other":"","group":"channel","child":[],"election":0},{"operator":{"sel":""},"key":"4","value":true,"other":"","group":"channel","child":[],"election":0}]},{"item_id":2,"item_source":"warehouse","item_value":[{"operator":{"sel":""},"key":"2","value":true,"other":"","group":"","child":[],"election":0}]},{"item_id":6,"item_source":"province","item_value":[{"operator":{"sel":""},"key":"pro","value":"3333","other":"","group":"","child":[],"election":0}]},{"item_id":5,"item_source":"target","item_value":[{"operator":{"sel":""},"key":"Europe","value":true,"other":"","group":"","child":[{"operator":{"sel":""},"key":"SE","value":true,"other":"","group":"","child":[],"election":0}],"election":0}]},{"item_id":3,"item_source":"transport","item_value":[{"operator":{"sel":""},"key":"1","value":true,"other":"","group":"","child":[{"operator":{"sel":""},"key":"CA_EconomyShipping","value":true,"other":"","group":"","child":[],"election":0},{"operator":{"sel":""},"key":"CA_PostLettermail","value":true,"other":"","group":"","child":[],"election":0}],"election":0},{"operator":{"sel":""},"key":"3","value":true,"other":"","group":"","child":[{"operator":{"sel":""},"key":"PostNLInternational3S","value":true,"other":"","group":"","child":[],"election":0},{"operator":{"sel":""},"key":"RussianPost","value":true,"other":"","group":"","child":[],"election":0}],"election":0},{"operator":{"sel":""},"key":"4","value":true,"other":"","group":"","child":[],"election":0}]},{"item_id":23,"item_source":"weight","item_value":[{"operator":"=","key":"weiO","value":"50","other":"","group":"","child":[],"election":0}]}]}';

//        $old='[{"id":1029,"sort":0,"update_time":1535687454},{"id":1032,"sort":1,"update_time":1535687454},{"id":388,"sort":2,"update_time":1535687454}]';
//        $new='[{"id":388,"sort":0,"update_time":1535687454},{"id":1032,"sort":1,"update_time":1535687454},{"id":1029,"sort":2,"update_time":1535687454}]';

        $old = json_decode($old, true);
        $new = json_decode($new, true);

//        $userInfo = Common::getUserInfo();
//        $temp['rule_id'] = 0;
//        $temp['type'] = 2;
//        $temp['operator_id'] = $userInfo['user_id'] ?? 0;
//        $temp['operator'] = $userInfo['realname'] ?? '';
//        $re = (new OrderRuleLog())->arrayAllDiff($new, $old);
//        $re = (new OrderRuleLog())->getUpdateSortMessage($new,$old,$temp);


//        $re = (new OrderRuleLog())->getUpdateStatus(1,0);

        $data = json_decode('{"item_id":8,"item_source":"street","item_value":[{"operator":{"sel":""},"key":"str","value":"我的世界","other":"","group":"","child":[],"election":0}]}', true);
//        var_dump($data);die;
        $re = (new Resource())->getValue($data);
        var_dump($re);
        die;
    }

    public function testTest()
    {

        //        $this->testlog();
//        return $this->testRuleLog();
//        $url = "http://172.19.23.98";
//        $port = explode(':',$url);
//        $port = $port[2] ?? "";


//        set_time_limit(0);
//        $user_id = 1724;
//        $actual_amount = 5;
//        $year = '2018';
//        $monthly = '12';
//
        $users = [1540, 1554, 1797, 2227, 2281, 2578, 2579, 2616, 2617, 2676, 4245, 1533, 1543, 1793, 1803, 2537, 2584, 2613, 2660, 4518, 1288, 1289, 1353, 1406, 1407, 1505, 1555, 1556, 1557, 1818, 1869, 2559, 568, 814, 1790, 1820, 1842, 2219, 2447, 2463, 2997, 3985, 536, 559, 2589, 2590, 2592, 2682, 2794, 3113, 4059, 2795, 476, 1266, 1270, 1271, 1279, 2502, 2544, 2699, 2798, 4474, 1871, 1872, 2396, 2449, 2577, 2599, 3015, 3023, 3309, 3928, 4225, 3216, 1943, 3217, 3629, 3822, 4168, 4192, 4193, 4209, 4222, 4223, 60, 571, 1357, 1802, 1810, 1822, 2277, 2666, 3535, 3587, 2601, 2634, 2671, 2674, 2753, 2890, 3541, 4472, 2657, 2665, 2703, 2756, 3078, 3208, 3922, 4176, 4205, 2314, 2325, 2326, 2450, 2456, 2499, 2511, 2619, 3137, 3505, 3910, 3960, 4271, 2398, 2716, 3172, 3294, 3336, 4157, 4183, 4441, 3131, 2915, 2932, 1638, 2882, 4242, 3652, 2726, 577, 3056, 1877, 3434, 1753, 3124, 3440, 2926, 1741, 1043, 2509, 2399, 1636, 2375, 573, 222, 3118, 4434, 4150, 2787, 1400, 3206, 2208, 3245, 2964, 1679, 4156, 4486, 1667, 1634, 2683, 4047, 1096, 4181, 2363, 1093, 2362, 1092, 166, 1091, 1586, 2283, 1101, 4185, 215, 2568, 1875, 1641, 2445, 1639, 1681, 3019, 4471, 4243, 1635, 4458, 2975, 2583, 4302, 3919, 2981, 3911, 3204, 2737, 2929, 2788, 3080, 2949, 2985, 4105, 3684, 2921, 2988, 2883, 2862, 3110, 3504, 3329, 2761, 2912, 1099, 1620, 2585, 2803, 3065, 3068, 3098, 3161, 3181, 3242, 3291, 4461, 2751, 2740, 1870, 2377, 2535, 3126, 4153, 4438, 4485, 1649, 2233, 2234, 2508, 3036, 3340, 3506, 3658, 4475, 2604, 2825, 3400, 3518, 3722, 4151, 4439, 4440, 2171, 2711, 2907, 2956, 3063, 3097, 3145, 3182, 3290, 2458, 2649, 2873, 2944, 3039, 3064, 3324, 3364, 3891, 1514, 2709, 2854, 2866, 2983, 3189, 3406, 4056, 4508, 4050, 497, 626, 1515, 1664, 1683, 2221, 2496, 2656, 3114, 3724, 3834, 248, 252, 1192, 1479, 2220, 2222, 2275, 2889, 3087, 3168, 3225, 3531, 14, 153, 668, 1193, 1516, 1518, 1522, 1769, 2506, 2542, 2638, 666, 1520, 1525, 1529, 1930, 1931, 1932, 2214, 2864, 3408, 3827, 4252, 15, 217, 671, 1070, 1794, 2492, 2627, 2886, 2924, 1663, 669, 2754, 2766, 3263, 3409, 3423, 3543, 4066, 4172, 4452, 1507, 2928, 3149, 3190, 4184, 4251, 4281, 4300, 4507, 85, 156, 674, 1199, 1648, 1784, 1814, 2493, 2621, 3523, 672, 2945, 3089, 3405, 3407, 3661, 4180, 4253, 4301, 4506, 2989, 402, 693, 1761, 1817, 2877, 2982, 3924, 4055, 4437, 4484, 689, 680, 682, 1686, 1687, 1688, 2995, 3005, 3280, 3300, 3437, 1691, 1752, 2207, 2705, 2777, 3109, 3351, 4084, 4087, 2252, 2774, 2778, 2834, 2984, 3509, 3967, 4102, 4347, 4512, 1816, 2256, 2271, 2276, 2717, 2738, 3186, 3209, 3277, 4207, 4305, 2240, 3278, 3350, 3516, 3536, 3589, 3590, 3872, 4088, 4513, 1929, 2190, 2197, 2202, 2217, 2272, 2735, 3247, 3502, 3549, 2373, 2462, 2465, 2571, 2575, 2840, 3820, 3986, 3987, 4303, 2673, 2721, 2763, 2779, 2998, 3044, 3058, 3082, 3215, 2856, 2918, 2923, 2937, 3248, 3312, 3315, 3923, 4169, 4279, 4436, 414, 820, 1882, 2532, 583, 1023, 1878, 1879, 2947, 4167, 260, 276, 815, 1622, 4175, 247, 580, 581, 823, 825, 1883, 274, 1623, 1624, 1625, 1884, 2530, 2679, 1235, 1807, 1857, 1858, 1860, 1881, 2378, 3187, 4494, 305, 1808, 1861, 1863, 1864, 1866, 2582, 2891, 2952, 1651, 2935, 3016, 3295, 3452, 3662, 4170, 2570, 3022, 3436, 3496, 4173, 4228, 4502, 22, 1534, 1537, 2223, 2491, 2729, 2739, 2828, 3909, 4061, 4062, 1765, 2498, 2569, 3021, 3165, 3220, 4186, 4195, 4268, 4457, 178, 697, 2680, 2782, 2851, 2860, 2861, 2977, 3163, 3660, 4053, 56, 1573, 1574, 1700, 2244, 2258, 2832, 3303, 3723, 4149, 58, 1160, 1605, 2618, 2838, 2852, 2853, 3174, 3183, 3510, 1164, 2661, 2662, 2677, 2725, 2730, 2784, 2910, 3049, 4299, 4444, 3050, 3086, 3153, 3167, 3221, 3223, 3250, 3341, 3766, 4250, 4348, 4443, 831, 1017, 1940, 2701, 2780, 2939, 3444, 3540, 3594, 4218, 1627, 1843, 1845, 2370, 2636, 4440, 2974, 1750, 1844, 2369, 2759, 2979, 3680, 3749, 1629, 1127, 1849, 2367, 2457, 2690, 2936, 3275, 4226, 1846, 1847, 1850, 1852, 2744, 3410, 3654, 2165, 3347, 3363, 3366, 3429, 3572, 3787, 4493, 4527, 1373, 2172, 2285, 2529, 3271, 3419, 3853, 1913, 1916, 2643, 2644, 3196, 3483, 3488, 3179, 2299, 2301, 3446, 4178, 4431, 1618, 2566, 2631, 2698, 2710, 2993, 3487, 4264, 2567, 2513, 2514, 2515, 2629, 2630, 2632, 2815, 2857, 3289, 2697, 2550, 3982, 4045, 4046, 4462, 2817, 1901, 2270, 2297, 2560, 2595, 2596, 2816, 2818, 3646, 4277, 2265, 2540, 2693, 2694, 2898, 2969, 3101, 3102, 4071, 4432, 3178, 2266, 2290, 2696, 3011, 3462, 4121, 4122, 4141, 2162, 2166, 2287, 2541, 2695, 2713, 3287, 4043, 2345, 2807, 2808, 3013, 3105, 3638, 4203, 4204, 4528, 3899, 2167, 2327, 2329, 3285, 3645, 4044, 3761, 1378, 2307, 3705, 3784, 4080, 4120, 4278, 2955, 1888, 2291, 2313, 2455, 3482, 3786, 3981, 4146, 1219, 1893, 2308, 2309, 3463, 3481, 3760, 3949, 268, 406, 578, 1752, 565];
//
        foreach ($users as $user_id) {
            $actual_amount = rand(10, 20);
            $order = rand(1, 5);
            $re = (new \app\report\service\MonthlyTargetAmountService())->addAmount($user_id, $actual_amount, $order);
        }
        $new = [1, 2, 3];
        $old = [3, 4, 5, 1, 1];
//        $diff = array_diff($new,$old);
        $diff = array_diff($old, $new);
        var_dump($diff);
        echo 'ok';
        die;

//      $re = Cache::store('channel')->getSite();
//      $re = (new MonthlyTargetDepartmentUserMapService())->deleteUser(406,1);

        // 需要，从ERP---订单系统，导出 9月1号到12月1号三个月 & 全部发货，并按物流方式去重后，整理出一份表格
        $where = [
            'p.shipping_time' => ['between', [1535731200, 1543593600]],
            'p.shipping_number' => ['neq', ''],
        ];
        $join[] = ['carrier c', 'c.id = s.carrier_id', 'left'];
        $join[] = ['order_package p', 'p.shipping_id = s.id', 'left'];
        $feild = 's.code as "物流渠道商code",s.shortname as "物流渠道中文名",c.code as "物流商code",c.shortname as "物流商中文名",p.shipping_number as "跟踪号"';
        $re = (new \app\common\model\ShippingMethod())->alias('s')->join($join)->field($feild)->where($where)->group('p.shipping_id')->select(false);
        echo $re;
        die;
        var_dump($re);
        die;
    }

    public function cdTest(Request $request)
    {
        set_time_limit(0);

//        return $this->testamazonAccountHealth();
        return $this->testlog();
        return $this->testPricingRule();
        return $this->testmonthly();
        return $this->testSuperbrowser();
        return $this->testVirtualOrder();
        return $this->testExport();
//        $re = (new Automation())->getVisitIp();
//        echo $re;
//        die('ok');
//
//        return $this->testServerOn();
//        $re = (new Dashboard())->fbaNearby15(0);
//        return json($re);
//
//        return $this->testServerIn();
        return $this->testDistribution();

//        return $this->testShippingTracking();
//        return $this->testAccount();
//        return $this->testSuperbrowser();
//
//        return $this->testPandao();
//        return $this->fetch('index');
//        $re = (new \app\index\service\WishShippingRateService())->shippingChargeRunOne(100);
        return $this->testJoom();

//        return $this->testDingtalk();
//        return $this->testReturnShelf();
//        return $this->testTest();
//        return $this->testPackage();

//        return $this->testCompany();
//        return $this->testWishShiping();
//        return $this->testVirtual();
//        return $this->testSku();
//        return $this->testmonthly();
//        return $this->testGoods();
//        $this->testWalmarts();
        // return $this->testServerIn();
        // $this->testZoodMall();
//        $this->synchronousTest();

//        $this->testShippingMethod();
//        $this->addOrderByid();
        // return $this->testYandex();
//        $this->testwalmart();
//        $this->testReport();

//        $re = (new AmazonAccountHealthTask())->execute();
//        echo 'ok';die;

//        $re = $this->testwalmart();
//        $order_id = '1101266280771757088';
//        $service = new InvoiceRecordService();
//        $re = $service->generateInvoice($order_id);
//        var_dump(Cache::store('Invoice')->ruleSetInfo());die;

//        $re = (new VirtualOrderMission())->getTaskerName(1,16);

//        $this->testVirtualRuel();


        $api = new CdOrderQueue(5);
        $re = $api->execute();
        var_dump($re);
        die;

    }


    private function testVirtualOrder()
    {

        $re = (new \app\index\service\DepartmentUserMapService())->updateDepartmentUserMap(530);
//        return json($re);

        $re = '';

        $data = [               'channel_id' => 1, //平台
                                  'warehouse_id' => 2, // 仓库ID
                                   'shipping_id' => 703, // 物流方式ID
                                     'time_e' => 1551247038, // 发货时间 开始
                                        'time_s' => 1, // 发货时间 结束
    ];
//        $re = (new VirtualOrderHoldService())->getVirtualShippingNumber('1062771301481448512',$data);
//        $re = (new VirtualOrderHoldService())->saveVirtualShippingNumber('1062771301481448512',154111141,$re);

//        $re = (new VirtualOrderHoldService())->getList();
//        return json(['message'=>'生成成功','data'=>$re]);
//        $re = (new AccountUserMapService())->changeUserDepartment(51);
//        $re = (new User())->getSuperiorInfo(2228,false);
//        $re = (new ManagerServer())->setAuthorization(1,[],[2228,1,2,3,4,5,11],true);

//        $data = json_decode('{"server_id":"1", "userAd":[{"handle_Type":"1","local_password":"Server07107bf6", "local_username":"rondaful2860", "user_id":"2228","status":"1"}]}',true);
//        $re = (new ServerUserReceive($data))->execute();

//        $re = (new ChannelConfig(1))->getConfig('channel_duplication');
//        $re = (new \app\index\service\ChannelConfig(1))->automationChannelConfig();
//        $channelCofig = new ChannelConfig(ChannelAccountConst::channel_amazon);
//        $re = $channelCofig->getConfig('channel_list_num');
//        $re = (new AmazonAccountMonitor())->execute();
//        $re = Cache::store('Channel')->getChannel();
//        $re = $config = Cache::store('configParams')->getConfig('amazon_channel_cost');
//        $re = $config = (new ChannelConfig(ChannelAccountConst::channel_amazon))->getConfig('amazon_detail_version');
//        $data = json_decode(
//            '{ "type": "updateOrderDelivery", "data": { "order_id": 1216348951546888193, "shipping_time": 1552269390 } }',
//            true
//        );
//        $re = (new OrderUpdateQueue($data))->execute();
//        $re = (new BasicAccountService())->writeBackBackAccountId();


        $params = [
            'type' => 'updateOrderMoney',
            'data' => [
                'order_id' => '1216789133985382401',//系统订单ID, 传字符串
                'pay_fee'=>'30',//支付费用
                'paypal_fee'=>'31',//paypal手续费
                'channel_cost'=>'32',//渠道费用（手续费）
                'goods_amount'=>'33',//商品总价
                'currency_code'=>'',//货币种类
                'user'=> [],//更新人
            ],
        ];
        (new UniqueQueuer(OrderUpdateQueue::class))->push($params);

        $re = (new OrderService())->updateOrderMoney($params['data']);
//

        $channel = ChannelAccountConst::channel_amazon;
        $service = new \app\common\model\amazon\AmazonAccount();
//        $channel = ChannelAccountConst::channel_Lazada;
//        $service = new \app\common\model\lazada\LazadaAccount();
//        $channel = ChannelAccountConst::channel_Shopee;
//        $service = new \app\common\model\shopee\ShopeeAccount();
//        $channel = ChannelAccountConst::channel_Daraz;
//        $service = new \app\common\model\daraz\DarazAccount();
        $accountUserMapService = new \app\index\service\AccountUserMapService();
        $saveAll = [];
        $list = $service->where('base_account_id',0)->column('code','id');;
        foreach ($list as $id => $code){
            try{
                $accountInfo = $accountUserMapService->getAccountInfo($code, $channel);
                $saveAll[] = [
                    'id' => $id,
                    'base_account_id' => $accountInfo['id'],
                ];
            }catch (\Exception $e){

            }
        }
        $service->isUpdate(true)->saveAll($saveAll);





        die;
    }

    private function testExport()
    {

//        $departments = Cache::store('department')->tree();
//        var_dump($departments);die;
            // 导出测试
//        $data = json_decode(
//            '{ "warehouse_id": "2", "developer_id": "", "purchaser_id": "", "snType": "sku", "snText": "", "goodsStatus": "1", "page": "1", "pageSize": "50", "channel_id": "0", "file_name": "仓库中山仓|商品状态在售|库存管理_缺货列表", "apply_id": "3952" }'
//            ,true
//        );
//        $re = (new OrderLackExportQueue($data))->execute();

        //更换负责人测试
//        $data = json_decode(
//            '{ "departmentId": "141", "leader": [ { "user_id": 2840, "job_id": 18 }, { "user_id": 5426, "job_id": 19 } ], "oldLeader": [ 2840 ], "user": { "realname": "[更换负责人]张华杰", "user_id": 107 } }'
//            ,true
//        );
//        $re = (new DepartmentUserMapBatchQueue($data))->execute();


//        $re = (new ManagerServer())->userServer('chenlinf','1234');
//        var_dump((new ChannelNodeService())->nodeTpye());
//        $code = (new OrderService())->getAccountName(6,31);
//        var_dump($code);die;
//        var_dump((new AccountUserMapService())->getAccountInfo($code,6));
//        var_dump((new Dashboard())->fbaNearby15(2,1545840000));
//        var_dump((new Dashboard())->getStaticOrderInfo(1,'2019-1-28'));
//        $api = new Department();
        // 查找部门下所有用户关联的账号资料ID
//        $departmentUser = (new \app\index\service\Department())->getDepartmentUser(524, true);
//        $re = (new OrderLackService())->getShortageEarliestTime(323092,1);
//        $data = json_decode('{ "channel_order_number": "TK151515721SWWrXE3v", "status": false, "message": "--账号id信息为空11113" }',true);
//        $re = (new WriteBackDistributionOrder($data))->execute();
//        $re = (new ManagerServer())->getExtranetType();

        //重返待上架入库
//        $re = (new ReturnWaitShelvesService())->addReturnWaitShelves(2,100001,50,'155003648063',1);
        //上架重返待上架回写状态
//        $re = (new ReturnWaitShelvesService())->writeBackStatus(2,100021,27,2228);
        //盘点回写
//        $re = (new ReturnWaitShelvesService())->writeBackCheck(2,100021,27,2228);

//        $re = (new ReturnWaitShelvesService())->detail(1);
//        $re = (new DepartmentUserMapService())->getLeader(85);

//                $day = 1546272000;
//        $re = $day - date('H',$day) * 3600;
//        return json($re);
        $re  = (new UniqueQueuer(\app\index\queue\AccountUserMapDelQueue::class))->push(476);
        $re = (new DepartmentUserMapService())->updateDepartmentUserMap(355);
        $server_id = 1; // 服务器ID
        $addUser = [1,2,3]; // 添加的用户ID 无就传空数组
        $delUser = [4,5,6]; // 删除的用户ID 无就传空数组
        $user = [
            'user_id' => 3,
            'realname' => '[paypal绑定]'.'李佰敏',
            'username' => '李佰敏',
        ]; // 操作人
        (new \app\index\service\ManagerServer())->setAuthorizationAll($server_id, $addUser,$delUser ,$user);

        return json($re);
//        $re = (new AmazonAccountMonitor())->execute();
//        $day = 1546275600;
//        $re = (floor($day - 28800) % 86400);
//        return json($re);
//        $params = [
//            'type' => 'updateUploadedDeadline',
//            'data' => [
//                'channelId' => 31,
//                'channelOrderNumber' => 'TK183012393qpeRI64l',
//                'uploadedDeadline' => 1553011200,
//            ],
//        ];
//        $re = (new UniqueQueuer(\app\order\queue\OrderUpdateQueue::class))->push($params);
//
//        (new OrderService())->updateUploadedDeadline(31,'TK183012393qpeRI64l',1553011200);

//        $re = (new Dashboard())->nearby15(31);
//        $re = (new Dashboard())->nearby15(31);
        $url = 'https://wg.brandslink.com:8105/cms/erpToBrandsLink/erpPostAfterSalesOrder/TK154112289VI8cwcb7';
        $re = (new \callBack\operation\Order(['call_back'=>'']))->httpReader($url,'POST');
//        $data = json_decode('{ "channel_order_number": "TK154112289VI8cwcb7" }',true);
//        $re = (new DistributionStockInCallBack($data))->execute();
       return json($re);
    }

    private function tests($channels)
    {
        $place = 0;
        if (is_array($channels)) {
            foreach ($channels as $channel) {
                $place += (1 << ($channel - 1));
            }
        }
        return $place;
    }

    private function tests2($place, $max = 63)
    {
        if (!$place) {
            return 0;
        }
        $channels = [];
        for ($i = 0; $i < $max; $i++) {
            if ($place & (1 << $i )) {
                $channels[] = $i + 1;
            }
        }
        return $channels;
    }

    private function testDistribution()
    {
//               $re = Twitter::instance()->nextId(1);
//       var_dump($re);die;

//        $model = new WriteBackWarehouseShortageTask();
//
//
//        $mode = get_class($model);
//        $mode = basename(str_replace('\\', '/', $mode));
//        var_dump($mode);die;
//        $userInfo = Cache::store('user')->getOneUser(2228);
//        var_dump($userInfo);die;

//        $number = '154846725920';

//        $shippingInfo = Cache::store('shipping')->getShipping(1053);
//        $shippingInfo = (new ShippingTrackingService())->addNew($number);
//        var_dump($shippingInfo);die;


        $mes = [
            '分配库存异常，库存不足',
            '物流下单异常，物流商出错',
            '包裹包装异常，无法拣货',
            '发货异常，面单打印失败',
        ];

        $mes1 = [
            'distribution',
            'package_upload_status',
            'package_confirm_status',
            'shipping_time',
        ];
        $order_ids = [
            'SH20190225151837f3da',
            'SH201902251627231a21',
            'SH201902251627231a21',
            'SH201902251627231a21',
        ];

        $order_id = 'TK170357460E9EMgvBA';

        foreach ($order_ids as $k => $order_id) {
            if ($k == 0) {
                $order_id = (new \app\common\model\Order())->where('channel_order_number', $order_id)->value('id');

                if (!$order_id) {
                    throw new Exception('不存在订单');
                }
                $package_id = (new OrderPackage())->where('order_id', $order_id)->value('id');
                if (!$package_id) {
                    throw new Exception('不存在包裹');
                }
//        $package_id = '1201179391447400544';
                $speed = $mes1[$k];
                $time = time();
//
//


                $re = (new OrderHelp())->distribution($package_id, $speed, $time, $mes[$k]);
            }

        }
//        $re = (new ShippingAgainByOrder())->execute();
        var_dump($re);
        die;


//        $data = json_decode('{ "departmentId": "377", "leader": [ { "user_id": 189, "job_id": 16 }, { "user_id": 207, "job_id": 17 }, { "user_id": 208, "job_id": 17 }, { "user_id": 1718, "job_id": 17 }, { "user_id": 1835, "job_id": 16 }, { "user_id": 3342, "job_id": 19 }, { "user_id": 4505, "job_id": 17 }, { "user_id": 4628, "job_id": 17 }, { "user_id": 295, "job_id": 19 } ], "user": { "user_id": 107, "realname": "张华杰", "username": "zhanghuajie" } }',
//            true);
//        $data['user'] = Common::getUserInfo();
//        $re = (new DepartmentUserMapBatchQueue($data))->execute();
//        $re = Cache::store('MonthlyDepartment')->getMonthlyDepartmentTree();
//        $re = (new WarehousePackageService())->logUnfilled();
//        $re = (new WarehousePackageService())->logUnfilledDetails(2,'2019-1-9');
//        $re = \app\report\model\ReportUnpackedByDate::add(2,6);

        $warehouseId = 2;//仓库ID
        $qty = 123;//统计的包裹数
        $re = \app\report\model\ReportUnpackedByDate::add($warehouseId, $qty);

//        $where = [
//            'shipping_time' => 0,
//        ];
//
//        $day = date('Y-m-d');
//        $b_day = strtotime($day);
//        $e_day = $b_day + 86399;
//        $where['shipping_time'] = ['between',[$b_day,$e_day]];
//        var_dump($where);die;
//
//        $re = (new WarehousePackageService())->getPackageCountGroupWarehouseId($where);
        $re = (new WriteBackWarehouseUnShippedTask())->execute();
        return json($re);
        die;
    }

    private function testShippingTracking()
    {
//        $number = '151565962303';
//        $re = (new ShippingTrackingService())->add($number);
//        $re = (new PackageService())->getLogisticsReceipt($number);
//        $re = YandexAccount::get($_data['channel_account_id']);
        $save = [
            'providers_shipping_time' => 1515832488,
            'accept_time' => 1515832489,
        ];
        $re = (new PackageService())->updateOrderPackageTime($save, 'LP00088615280676');
        var_dump($re);
        die;
    }

    private function testAccount()
    {
        $re = (new BasicAccountService())->saveAllDir();
//        $str = '1123,';
//        $re = explode(',',$str);
//        $re = Cache::store('User')->getOneUser(1228);
//        $re = Cache::store('User')->getOneUserRealname(2228);
        var_dump($re);
        die;

        $re = Cache::store('User')->getOneUser(1228, 'id,realname');
        var_dump($re);
        die;
    }

    private function testSuperbrowser()
    {
//        $userId = 2228;
//        $re = (new ManagerServer())->getShopListByUserId($userId);
//        (new UniqueQueuer(AmazonAccountHealthSendQueue::class))->push($id);

//        $re = (new AmazonAccountHealthSendQueue(7))->execute();
//        $re = (new Dashboard())->fbaNearby15(0);


        $api = new SuperBrowserBaseApi();
//        $re = $api->getSecret();
//        $re = $api->getAuthorization();

//        $re = $api->showPublicKey();
//        $re = $api->getIpList();
        $re = (new ManagerServer())->updageSuperServer();
        var_dump($re);die;

//        $re = $api->getIpList();
//        var_dump($re);die;
//        $re = $api->getIpBuyList();
//        $re = $api->getContainerBasicData();
//        $add = []; //
//        foreach ($re['site'] as $v){
//            $add[] = [
//                'update_time' => time(),
//                'create_time' => time(),
//                'channel_id' => 1,
//                'site_id' => $v['site_id'],
//                'site_name' => $v['site_name'],
//            ];
//        }
//        (new ChannelSuperSite())->saveAll($add);

//        for( $i = 1;$i<3;$i++){
//            $day = 28;
//            if($i%2 == 1){
//                $day = 31;
//            }
//            $str = '2019-'.$i.'-1 0:0:0';
//            $strend = '2019-'.$i.'-'.$day.' 23:59:59';
//            echo strtotime($str),' and ',strtotime($strend),'<br/>';
//        }
//
//        die;
//        echo 123;die;
//        $re = $api->getWalletBalance();
//        $re = $api->getWalletPayRecord();
//        $re = $api->newBuyIp(3,3);
//        $re = (new VirtualOrderHoldService())->getOrderSynchronize('1119422910994460384');
        $data = [
            'status' => '1',
            'snType' => 'department_id',
            'snText' => '[255, 256, 329, 445]',
        ];
//        $re = (new \app\index\service\User())->getUserList($data,true);
//        return json($re);
//            $api->getAuthorization();

//        $re = $api->decode();

        $size = [
                'length' => 600,//长度(mm)删除资料服务器
                       'width' => 400,//宽度(mm)
                         'height' => 500,//高度(mm)
        ];
        $weight = 91; //重量
//        $re = (new \app\order\service\OrderPackage())->writeBackPackageSize('1062771321979013472',$size,$weight);

//        $re = (new AccountService())->accountInfos(1,'Malaysia');

        $data = json_decode(
            '{ "channel_order_number": "TK102449473oGweQlwf", "speed": "shipping_time", "time": 1553486381, "other": { "order_id": "1221406002027364568", "shipping_number": "564239585", "process_code": "54215375429", "shipping_fee": "65.0000" } }'
            ,true
        );
//        $re = (new DistributionNotice($data))->execute();
        var_dump($re);die;
//        $re = (new OrderPackageUpdateQueue($data))->execute();
//        $re = (new WriteBackSkuSizeTask())->execute();
//        $re = (new Department())->info(0);
//        $request = Request::instance();
//        $re = (new PackageCarrier())->index($request);
        return $re;

//        $re = $api->getWalletPayRecord();
//        var_dump($re);
        die;
    }

    public function testPandao()
    {

        $data = '{"orderDetail":[{"transaction_id":"B282AFF2-0001-11E9-8615-A0E6984D4F49","channel_item_id":"668898f5-639f-4e36-887a-ec7d987cd012","channel_sku":"DJ0012202","channel_sku_title":"HOMTOM S99 Face ID 6200mAh 4GB 64GB Smartphone 5.5-Inch Bezel-less 21+2MP Dual Rear Cameras Android 8.0 Fingerprint Mobile Phone","channel_sku_price":"145.00","sku_quantity":1,"channel_item_link":"http:\/\/img.rondaful.com\/240\/150\/37d951f3e80e122b394773c83759b996_800x800.jpg","channel_currency_code":"USD"}],"order":{"order_number":"homtom-45d8-9e28-6775196a655c","buyer":"Dmitriy Valentinovich Nagornyy","buyer_id":"9940b092-8f96-4313-a8e2-2dea0cf5d96e","seller":"homtom123","seller_id":11,"consignee":"Dmitriy Valentinovich Nagornyy","country_code":"RU","province":"Primorskiy","city":"Vladivostok","area_info":"","address":"Ulitsa Nekrasovskaya, d. 72, kv. 89","address2":"","zipcode":"690014","tel":"+79146760318","mobile":"+79146760318","email":"","type":"","shipping_id":"","pay_id":"","pay_fee":"145.00","order_amount":"145.00","create_time":1544818089,"pay_time":1544818089,"channel_shipping_free":"0.00","message":"","channel_id":8,"channel_account_id":11,"channel_account_code":"homtom","channel_order_id":356498,"channel_order_number":"af06c0b9-d3ab-45d8-9e28-6775196a655c","related_order_id":"","currency_code":"USD","order_time":1544818089,"transaction_id":"B282AFF2-0001-11E9-8615-A0E6984D4F49","site_code":"","uploaded_deadline":1545682089,"channel_cost":15.949999999999999,"discount":0,"goods_amount":145}}';
        $data = json_decode($data, true);
        (new \app\common\service\Order())->add([$data]);
        var_dump('ok');
        die;

        $accountId = 6;
//        $orders = 'e3aff269-8f3a-4699-a29f-f064bb6dbd06';
//        (new \app\common\service\UniqueQueuer(\app\order\queue\PaytmOrderByOrderIdQueue::class))->push(['account_id' => $accountId, 'orderIds' => $orders]);
//        (new \app\order\queue\PaytmOrderByOrderIdQueue(['account_id' => $accountId, 'orderIds' => $orders]))->execute();
        (new PandaoOrderQueue($accountId))->execute();
        echo 'ok';
        die;
    }

    private function testDingtalk()
    {
        $accessToken = InternalLetterService::getAccessTokenText();
        var_dump($accessToken);
        die;
    }

    private function testReturnShelf()
    {
//        $re = (new ReturnWaitShelfService())->addReturnWaitShelf(2,100002,2,151565964);

//        $re = (new \app\warehouse\service\WarehouseCargoGoods)->getCargoAreaInfo(2, 100003 );
        $re = (new ReturnWaitShelfService())->writeBackStatus('69813');
        var_dump($re);
        die;
    }

    private function testCompany()
    {
//        $place = 10;
//        $re = (new AccountCompanyService())->placeToChannel($place);

        $place = [
            1, 23
        ];
        $re = (new AccountCompanyService())->channelToplace($place);
        $re = (new AccountCompanyService())->placeToChannel($re);
//        $re = Cache::store('WalmartAccount')->getTableRecord(20);
//        $re = Cache::store('Channel')->getChannelName(null);
//        $re = (new AccountCompanyLog())->getValue('channel',6316142);
//        $re = Cache::store('JoomShop')->getAccountId(55);
        var_dump($re);
        die;
        $place['channel_id'] = 4;
        $re = (new AccountCompanyService())->getCompany($place);


//        $place = '101';


//        $id = 1;
//        $data = [
//            'company' => '百度1'
//        ];
//        $re = (new AccountCompanyService())->update($id,$data);

//        $re = AccountCompanyLog::getValue('channel','1000001');

        var_dump($re);
        die;
    }

    private function testPackage()
    {

        $user_id = 1724;
        $actual_amount = 5;
        $year = '2018';
        $monthly = '12';
        $re = (new \app\report\service\MonthlyTargetAmountService())->addDevelopment($user_id, $actual_amount, $year, $monthly);
        var_dump($re);
        echo 123;
//        $a1=array(1=>"red",2=>"green",3=>'blue');
//        $a2=array(1=>"blue",2=>"yellow");
//        print_r(array_merge($a1,$a2));
        die;
        $packageIds = [
            '151627791001',
            '151623831103',
            '151627791001',
        ];
        $re = \app\order\service\PackageHelp::getPackageIdByNumber($packageIds);
//        $re = [
//            '151623831103' => 123214234,
//            '151623831104'  => 123214234,
//        ];
//
//        $re = array_diff($packageIds , array_keys($re));
        var_dump($re);
        die;
    }

    public function testWishShiping()
    {
//        $date_e = '2018-8';
//        $date_e = date('Y-m-t',strtotime($date_e));
//        $date_e .= ' 23:59:59';
//        var_dump($date_e);die;
//        $skuList = [
//            'N0338801',
//            'EN0338802',
//            'EN0338804',
//        ];
        $service = new StatisticGoods();
        $re = $service->updatePackageSkuAverageWeight('', '', ['628572']);
//        $re = (new WishShippingRateService())->orderRate('2018-1-1','2018-11-12');
//        $re = (new WishShippingRateService())->lists([]);
        $re = (new WishShippingRateService())->shippingCharge();
//        $re =  Cache::store('Shipping')->getShipping(1087);;
        var_dump($re);
        die;
        return (new WishShippingRate())->orderRate();
    }

    private function testSku()
    {
//        $re = (new \app\index\service\MemberShipService())->getAllMemberShip();
//        $re = (new \app\index\service\MemberShipService())->saveAllDir();
        $re = (new WriteBackSkuWeightTask())->execute();
        var_dump($re);
        die;
    }

    public function testmonthly()
    {
//        var_dump(substr(date('Y-m-t'),-2));die;
//        Cache::store('MonthlyDepartment')->deleteAll();
//        var_dump(Cache::store('MonthlyDepartment')->tree());die;
//        $re = Cache::store('MonthlyDepartment')->getMonthlyDepartment();
//        $re = Cache::store('MonthlyDepartment')->tree();
//{"local_warehouse_amount":0,"oversea_warehouse_amount":0,"fba_warehouse_amount":0,"fba_warehouse_orders":0}
//        $re = (new MonthlyTargetDepartmentService())->getAllDepartmentTree(3);
//        var_dump($re['parents']);die;
//        var_dump(strtotime(date('Y-m')));die;
//        $re = (new MonthlyTargetDepartmentUserMapService())->getUserByDepartmentId(9);
//        $re = (new \app\report\service\MonthlyTargetAmountService())->addAmount(672, 60, 2, ['local_warehouse_amount' => 60, 'fba_warehouse_orders' => 1]);
//        $re = (new \app\report\service\MonthlyTargetAmountService())->recalculateManAccount(2018, 11);
//        $re = (new \app\report\service\MonthlyTargetAmountService())->getAllDeparment(true,'','',9);
//        $re = (new MonthlyTargetAmountService())->import([]);
//        $re = (new MonthlyTargetAmountService())->getDay('2018',2);
//        $re = (new StatisticByGoods())->exportOnLine();

//        $re = MonthlyTargetAmountFilter::config();

//        $re = (new MonthlyTargetAmountService())->getUserRanking(3277);
//        $re = (new MonthlyTargetAmountService())->progressTarget();
//        $id = '1062771349300709728';
//        $param = [
//                'channel_id' => 1, //平台
//                                  'warehouse_id' => 2, // 仓库ID
//                                  'shipping_id' => 2, // 物流方式ID
//                                    'date_s' => 1514736000, // 发货时间 开始
//                                      'date_e' => 1553572617, // 发货时间 结束
//        ];
//        $re = (new VirtualOrderHoldService())->getVirtualShippingNumber($id, $param);

        $re = (new OrderHelp())->subsidyVerification(1552269352,7,634619,1,30.6);

        var_dump($re);

//        return json($re);
        die;
    }

    private function testGoods()
    {

//        $re = (new WalmartOrderQueue(21))->execute();
//        $re = (new PandaoOrderQueue(1))->execute();
//        $orderid = 'd5412e43-7856-4d91-8635-7eda69d6b956';
//        $account = Cache::store('PandaoAccountCache')->getTableRecord(1);
//        $account = (new PandaoOrdersApi($account))->getOrdersById($orderid);
//        var_dump($account);die;
        die;
        $account = Cache::store('WalmartAccount')->getTableRecord(20);
        $data = [
            'shipment_detail' => [
                'tracking_number' => '9400110895343147405914',
                'shipping_carrier' => 'USPS',
            ],

        ];
        $data['account_id'] = 20;
        $data['order_id'] = '4783014246851';
        $data['shipped_time'] = time();

        $walmart = new WalmartOrderService();
        $re = $walmart->completeShipped($data, $account);


//        $params = [
//            'receive_ids'=>[2228],
//            'title'=>'测试1232111',
//            'content'=>'这是一条测试信息9992',
//            'type'=>2,
//            'dingtalk'=>1,
//            'create_id'=> 1
//        ];
//        $re = InternalLetterService::sendLetter($params);

//        $re = (new CdAccountService())->check(['id'=>10]);
//        $re = 'ok';//(new Server())->sendMessage(['2228'],'FFFF','测试');
        var_dump($re);
        die;

        $server = new StatisticByGoods();
        $params = [
//            'rate_min' => 1.5,
//            'rate_max' => 3,
            '7d_min' => 9,

        ];
//        $data = $server->lists(1,5,$params);
        $data = $server->exportOnLine(['100379', '101332'], [], $params);


//        $params['file_name'] = 'SKU销量动态表2018-10-17--2018-10-24test.xlsx';
//        $params['apply_id'] = '2371';
//        $api = new StatisticByGoodsExportQueue($params);
//
//        $re = $api->execute();
//        var_dump($re);

        return json($data);
//        var_dump($data);
        die;
    }

    private function testWalmarts()
    {
        echo 123, 'wdd', 'ddd', PHP_EOL;
        die;
//        $orderIds = '3786951022983';
//        $shop = Cache::store('WalmartAccount')->getTableRecord(18);
//        $api = new WalmartOrderApi($shop);
//        $re = $api->getOrderById($orderIds);
//        $re = $api->acknowledgeOrders($orderIds);
//        $data = [
//            'accountId' => 22,
//            'orderId' => '1786611262382',
//        ];
//        $re = (new WalmartOrderAckQueue($data))->execute();

        $re = (new WalmartOrderQueue(18))->execute();
        var_dump($re);
        die;
    }

    private function testServerIn()
    {

//        $re = (new GetUnpackParcelsCount())->execute();

//        $re = (new WriteBackWarehouseShippedTask())->execute();
//        $re = (new WriteBackWarehouseUnShippedTask())->execute();
//        $re = (new WriteBackWarehouseShortageTask())->execute();

        $re = (new Automation())->getRand(6);
//        $re = (new ManagerServer())->getAgencyShopListByUserId(2228);
//        $re = (new ManagerServer())->getAgencyShopDetailByIds(1,2228);
//
        var_dump($re);
        die;

//        $url = 'https://172.19.23.15:10088/user_management';
//        $data = [
//            'user_Name' => 'administrator',
//            'user_Password' => '123456',
//            'userAd' => [
//                [
//                    'handle_Type' => 1, //handle_Type 1（新增或修改），0（删除）
//                    'local_password' => 'a1234567',
//                    'local_username' => 'min',
//                ],
//            ],
//        ];
//        $result = (new \app\index\service\ManagerServer())->sendCommand($url, $data, false);
//        $result = (new ManualOrderService())->excelTimeToErpTime('43433.5133217593');
//        $result = (new ManagerServer())->serverList([],1,10);
//        $result = (new ManagerServer())->useInfo(1437);
//        $result = (new SoftwareService())->typeInfo();
//        $ip = '172.21.90.45';
//        $type = 1;

//        $result =  (new SoftwareService())->receptionUpdate( $ip,$type,'2.212');

//        $result = (new MemberShipService())->downloadZip();

        $new = [
            ['user_id' => 432],
            ['user_id' => 42],
            ['user_id' => 43],
        ];
        $old = [
            ['user_id' => 432],
            ['user_id' => 42],
            ['user_id' => 43],
            ['user_id' => 41],
        ];
        $result = ServerLog::getRemarkUser([], $old);
        var_dump($result);
        $result = ServerLog::toUrlencode($result);
        var_dump($result);
        die;
        return json($result);
        die;
    }

    private function testShippingMethod()
    {

        $api = new AliexpressProductOnSelling();
        $res = $api->execute();


        $res = '';
        //warehouse_id, country_code, weight, volume, property
        $data = [
            'warehouse_id' => 2, //仓库ID
            'country_code' => 'AA',
            'weight' => '100',
            'volume' => 1,
            'property' => '',
        ];
        $res = (new ShippingMethod())->trial($data);
        var_dump($res);
        die;
    }

    function splitFloat($d)
    {

        $reData['ints'] = intval($d); //整数部分
        $reData['decimals'] = $d - $reData['ints']; // 小数部分
        return $reData;
    }

    private function getSum(&$shippingFee, &$salePrice)
    {
        if ($shippingFee >= 1) {
            $decimals = $shippingFee - intval($shippingFee);
            $salePrice += $decimals;
            $shippingFee = intval($shippingFee);
        } else {
            $salePrice += $shippingFee;
            $shippingFee = 0;
        }
        return true;
    }

    private function testPricingRule()
    {
//        $shippingFee = '1,123';
//        (new MonthlyTargetAmountService())->restore($shippingFee);
//        $shippingFee = 0.756546545;
//        $salePrice = 65.3;
//        $this->getSum($shippingFee,$salePrice);
//        var_dump(Cache::store('WarehouseArea')->getWarehouseArea());die;

        //定时清理过期的刷单任务
//        $res  = (new VirtualTaskTimeOut())->execute();
//        echo 'ok';die;

//        $allUserData=(new \app\index\service\User())->getGoupByDepartmentUsers();
//        var_dump($allUserData[38]['data']);die;

//        $detail = [634599,634600,634601,634602,634603,634604,634605,634606,634607,634608];
//        $detail = ['494439', '494437'];
//        $detail = ['214875','214876']; // 672865,672866
//        $publishInfo['channel_id'] = ChannelAccountConst::channel_amazon;
//        $publishInfo['channel_account_id'] = 0;
//        $publishInfo['warehouse_id'] = 2;
//        $publishInfo['site_code'] = 'JP';
//        $publishInfo['category_id'] = 41;

//        $publishInfo['channel_id'] = ChannelAccountConst::channel_ebay;
//        $publishInfo['channel_account_id'] = 4;
//        $publishInfo['warehouse_id'] = 2;
//        $publishInfo['site_code'] = 'US';
//        $publishInfo['category_id'] = 41;


//        $publishInfo['channel_id'] = ChannelAccountConst::channel_aliExpress;
//        $publishInfo['channel_account_id'] = 2;
//        $publishInfo['warehouse_id'] = 2;
//        $publishInfo['site_code'] = 'US';

        $detail = [379678,379679];
        $publishInfo['channel_id'] = ChannelAccountConst::channel_ebay;
        $publishInfo['channel_account_id'] = 17;
        $publishInfo['warehouse_id'] = 2;
        $publishInfo['site_code'] = 'US';
//        echo 123;die;
        $result = (new PricingRuleService())->calculate($publishInfo, $detail);

//        $result = (new OrderShortSeller())->execute();
//        $result = Cache::store('Shipping')->getShipping(28);
//        $result = Cache::store('Account')->getAccountByChannel(2,1,50,'pueries');
//        $result = Cache::store('Order')->orderSkuQuantity(100000,2);
        var_dump($result);
        die;
    }

    private function testYandex()
    {


//        $msgRuleHelp = new MsgRuleHelp();
//        $msgRuleHelp->add_send_message($channel_id, $account_id, $item_id,  $receiver, $content);

        $user = Cache::store('User')->getOneUser(7);
        $token = (new \app\common\model\User())->createToken($user);
        echo $token;
        die;
//        $re = (new \app\order\task\YandexOrder())->execute();

//        $a=array("Name"=>['id'=>11]);
//        print_r(array_values($a));die;
//        $re = (new ManagerServer())->domainServer('pyy4325','rondaful.local');
//        $where = [
//            "status" => 'PROCESSING',
//            'push_status' => 0,
//            'create_time' => ['<', time()],
//            'buyer_id' => ['<>', ''],
//        ];
//
//        $re = (new YandexOrder())->where($where)->select(false);
//
//        echo $re;die;
        $re = \app\index\service\BasicAccountService::isHasCode(ChannelAccountConst::channel_wish,'123456');
//        $id = '3895875';
////
//        $data = Cache::store('YandexAccount')->getTableRecord(1);
//        $data['codes'] = 'AQAAAAAo4o5JAAVRKH0ilYQ9NkW0jscojDcQxqA';
//        $api = new \yandex\YandexOrderApi($data);
//        $re = $api->getAccountToken();
//        $re = $api->getOrderLists();
//        $re = $api->getOrdersById($id);
        $re = (new YandexOrderQueue(1))->execute();
//        $re = (new YandexToLocalOrder())->execute();
//        $re = $api->fulfillOne($id);
//        $re = $api->fulfillTwo($id);

//        $re = (new YandexToLocalOrder())->execute();
        var_dump($re);
        die;
    }

    private function testReport()
    {


        //下架
        $re = \app\report\service\StatisticPicking::addReportPicking(1, 2, 16, 100005, 1, 2);
        //上架
        $re = \app\report\service\StatisticShelf::addReportShelf(1, 2, 16, 100005, 1, 2);

//        $re = (new \app\index\service\User())->getGoupByDepartmentUsers();

//        $ids = [33];
//        $re = (new BasicAccountService())->createChannelAccount($ids);

        $params['file_name'] = 'spu上架时间统计_0_2018_09_27_12_02_28.xlsx';
        $params['apply_id'] = '2143';
        $api = new PublishbyTimeExportQueue($params);

        $re = $api->execute();
        var_dump($re);
        die;
    }

    private function testwalmart()
    {

        $re = '';
//        $api = new WalmartOrder();
//        $code = '1132aukfewuk';
//        $re = strpos($code, 'uk',1);
//        if(substr($code, -2 , 2) == 'uk'){
////            $re = 'ok';
//            $code = substr($code,0,strlen($code)-2);
//        }
//        $api = new \app\order\queue\WalmartOrderQueue(23);
        $params['file_name'] = '下架刊登spu统计_0_2018_09_18_18_06_19.xlsx';
        $params['apply_id'] = '1970';
        $params['file_name'] = '上架刊登spu统计_0_2018_09_19_11_39_20.xlsx';
        $params['apply_id'] = '1991';
        $api = new PublishbyShelfExportQueue($params);
//        $api = new PublishbyPickingExportQueue($params);
        $re = $api->execute();
        var_dump($re);
        die;
    }

    private function testlog()
    {
        set_time_limit(0);
        $data = json_decode('{ "page": "1", "pageSize": "50", "snDate": "transaction_date", "snUser": "seller_id", "delivery_type": "", "snType": "channel_order_number", "belong_type": "", "channel_id": "1", "account_id": "[1,16,17,18,19,20,21,22,23]", "site_code": "US", "date_b": "", "date_e": "2019-04-11", "export_type": "1", "type": 0, "user_id": 2228, "field": [ "order_number", "channel_order_number", "buyer_id", "email", "properties", "channel_account_id", "channel_id", "shipping_id", "warehouse_id", "currency_code", "rate", "rate_usd", "is_pay", "pay_time", "pay_fee", "channel_shipping_free", "order_amount", "goods_amount", "order_note", "seller", "sku_status", "consignee", "country_code", "process_state", "updated_time", "status", "shipping_status", "country_cn_name", "country_en_name", "province", "city", "zipcode", "mobile", "sku_id", "sku", "quantity", "synchronize_tracking_number", "shipping_time", "shipping_number", "number", "channel_item_id", "shipping_en_name", "offer_price", "purchase_price", "offer_price_amount", "purchase_price_amount", "package_fee", "channel_sku", "create_time", "buyer_selected_logistics", "pay_id", "supplier", "purchase", "address", "address2", "refund_amount", "reason_for_audit", "weight", "create_new_order_reason", "channel_sku_title", "channel_sku_cn_title", "reviews_id", "site_code", "goods_category_name", "goods_category_en_name", "price", "collection_account", "uploaded_deadline", "discount", "goods_name", "customer", "order_error", "rmb_profit", "profit_margin", "package_weight", "reason_for_invalid" ], "file_name": "订单数据.xlsx", "apply_id": "5746" }',true
        );
        $re = (new OrderExportService())->exportNew($data);

        $datas = [1,2];
        $save = [];
        foreach ($datas as $v) {
            $file_name = 'bulangyeuk@outlook.comfrcookies.txt';
            $cookie = '[{"path":"/","domain":".amazon.co.uk","name":"session-id-time","httpOnly":false,"secure":true,"storeId":"0","value":"2185347417l","url":"https://sellercentral.amazon.co.uk/home","expirationDate":2185347362000},{"path":"/","domain":".amazon.co.uk","name":"at-acbuk","httpOnly":true,"secure":true,"storeId":"0","value":"Atza|IwEBIIzC7S90VqbohmiV50ODwnMAGgasgwmuS-UMfGStlKZuAnEd7937zoW615nTrauY9NCWYqKzbrh7xllZ2SkNW44qzOjtv-vTvv-nYJEpDjA_A1yIBTc-luGomf_7IzvcTpF4r3KwuMBlksEhfVPfQ03rhMCginxCXo8ggmmqnuEsjZbkgUnkcuk76dl9Rj637f2tsvpfYMeqosTfaoe-oNPk7EfDhI74peMYajjYgFxHCNPZPwPfvHLDTGTjnOIpHnPgpUh8imPUl9HR4NODm3AqIa5mrFapbKd8OQZmmKg4MDvoH5aB2u6ErKS3XS3gMbHgJvhyC3lJ1CGNUpxyneM5eZ0VcLZEFOLGJ6r_LQJju64ZjdUOhdXu8FpIxVFA9x9yKmXWleEN5KmrwLsjrsKOJ2-AToH20aFHPMch35boNw","url":"https://sellercentral.amazon.co.uk/home","expirationDate":2185694002000},{"path":"/","domain":".amazon.co.uk","name":"sst-acbuk","httpOnly":true,"secure":true,"storeId":"0","value":"Sst1|PQEc5yAoZAlAMllJMhaLY6nOC3iyzqKQGi30abF0EuKJmGpaP6ukHvx9iMymZCRtqayvPiSiB6OlW2qx8VkcJLqXfNCdi7CsLWKgGe2aLFVIH-CrUreZ5MzVhSzdawHf6d09I25v8BciQEoHRVAzJEgnsj95_pFh3v59gvbRvXosTeYETHVKmcHtCsys1KkBVYU0kDedSJSqf3TtfEjf8UEdn77jaT28aYyY-nzGZYdBcUtsrxn_nNDrTcV1vKDnHh48o_2cJrXFRY_Z9MtrCq4Gmcqr-mtwtF1Ta014dJvVI3stccTKD3GYkfRnWW8rGskuYBUrTVCp70gsz8wwbd0DFQ","url":"https://sellercentral.amazon.co.uk/home","expirationDate":2185694002000},{"path":"/","domain":".amazon.co.uk","name":"x-acbuk","httpOnly":false,"secure":false,"storeId":"0","value":"\"odh23ByznMFgr?u?2qy9ueJ2kLvcWSlSzt7EKpKY683GNhXJmDKDBqnglq9MAhQt\"","url":"https://sellercentral.amazon.co.uk/home","expirationDate":2185694006000},{"path":"/","domain":".amazon.co.uk","name":"ubid-acbuk","httpOnly":false,"secure":false,"storeId":"0","value":"260-3407856-3993448","url":"https://sellercentral.amazon.co.uk/home","expirationDate":2185694002000},{"path":"/","domain":".amazon.co.uk","name":"session-token","httpOnly":false,"secure":false,"storeId":"0","value":"\"VQHGswrLBJpHvuaPQ5QNrR81R80q2/Pal4pFnpKq8WPPVO/8iUWrVt0NCILIFNuWLilgT+RLvCv9kPGq8RMC7+Tid+LcGjnu8ClZ9YZEhTvqDPcAUq/uTDTsLKViqv+7e0TOyEsYVeXTfj2Byl0qIWkrky2+636CfxQSexG0+TEfqvU9ZU6YMkYVo949XWkNf9pj125K0qbf88+o/XfF3g0an+vogEIOJFYC5BOjjj8=\"","url":"https://sellercentral.amazon.co.uk/home","expirationDate":2185694002000},{"path":"/","domain":".amazon.co.uk","name":"session-id","httpOnly":false,"secure":true,"storeId":"0","value":"257-8204917-3841710","url":"https://sellercentral.amazon.co.uk/home","expirationDate":2185347362000},{"path":"/","domain":"sellercentral.amazon.co.uk","name":"csm-hit","httpOnly":false,"secure":false,"storeId":"0","value":"tb:s-SQJTBK1G50K76WVTC2WM|1554974003352&t:1554974004693&adb:adblk_no","url":"https://sellercentral.amazon.co.uk/home","expirationDate":1615454004000},{"path":"/","domain":".amazon.co.uk","name":"sid","httpOnly":true,"secure":true,"storeId":"0","value":"\"UaFREbZ9RNuQTw5T3a//Mg==|dCiyZzGyYGnRNGqK/aBKWDHyiEGRMT77dyx/ONkY0uA=\"","url":"https://sellercentral.amazon.co.uk/home","expirationDate":2176532061000},{"path":"/","domain":".amazon.co.uk","name":"sess-at-acbuk","httpOnly":true,"secure":true,"storeId":"0","value":"\"pEGpnO77i+MYWE8wGBmDeEzw50yGnJL3Ihd4Ngs2SrQ=\"","url":"https://sellercentral.amazon.co.uk/home","expirationDate":2185694002000}]';

            $cookie = json_decode($cookie, true);
            $str = '.com';
            $name = explode($str, $file_name);
            $where = [];
            if (isset($name[1])) {
                $where = [
                    'account_name' => $name[0] . $str,
                ];
            }
            if (!$where) {
                $str = '.com';
                $name = explode($str, $file_name);
                if (isset($name[1])) {
                    $where = [
                        'account_name' => $name[0] . $str,
                    ];
                }
            }

            if ($where) {
                $where['channel_id'] = 2;
                $id = (new Account())->where($where)->value('id');
                if (!$id) {
                    continue;
                }
                $site = strtoupper(substr($name[1],0, 2));
                $url = '';
                foreach ($cookie as &$c){
                    $c['url'] = $url;
                }
                $save[] = [
                    'account_id' => $id,
                    'cookie' => json_encode($cookie),
                    'site' => json_encode($site),
                    'relation_module' => 9,
                ];

            }
            var_dump($save);
        }
        $re = 'ok';

        var_dump($re);die;


//        (new ReturnWaitShelfService())->writeBackByWarehouse();
//        $re = (new OrderService())->getWarehouseType('701-6585093-0761856');
//        $re = (new EbayAccountHealthService())->sendRequest(863);
        for($i = 422 ;$i <428;$i++){
            $re = (new AliexpressAccountHealthService())->sendAccount2Spider($i);
        }echo 'ok';die;

        $re = (new WishAccountHealthService())->sendAccount2Spider(526);
        $re = (new AmazonAccountHealthService())->sendAccount2Spider(3386);
//        $departments = Cache::store('department')->tree();
//        $re = $departments[187];
//        $re = $this->getUnderlingInfo(1322);
        var_dump($re);die;
//        echo 'ok';die;


//        $order_ids = [1064914536647492704,1064914536244839520];
//        $orders = (new Order())->field('id,channel_id,channel_account_id as account_id,buyer_id,transaction_id,channel_order_number')->where([
//            'id' => ['in', $order_ids]
//        ])->select();
//        $msgRuleHelp = new MsgRuleHelp();
//        if($orders){
//            $channelIds = array_column($orders,'channel_id');
//            $channelIds = array_unique($channelIds);
//            $count = count($channelIds);
//            if($count > 1){
//                throw new JsonErrorException('仅限同一平台的订单');
//            }
//            $channelIds = $channelIds[0];
//            $allOK = [1,2,4];
//            if(!in_array($channelIds,$allOK)){
//                throw new JsonErrorException('仅限支持ebay,速卖通，亚马逊这三个平台');
//            }
//        }
//        die('ok');

        $str = ['r52688',
            'test:task# 添加了测试',
            '---------------------',
            'r52687',
            'test:task#6786 将MyMall平台的英文名改为MyMall显示(先还原) ',
            '---------------------',
            'r52682',
            'add:task#6786 将MyMall平台的英文名改为MyMall显示 ',
            '---------------------',
            'r52679',
            'add:task#6842 Fummart订单，推到系统订单时，不需要合并包裹； ',
            '---------------------',
            'r52618',
            'mdf:refactor# 修复了一些问题',
            '---------------------',
            'r52613',
            'mdf:refactor# 修复了无法平台账号简称无数据的问题 ',
            '---------------------',
            'r52605',
            'mdf:refactor# 添加了自动登录的大小账号问题 ',
            '---------------------',
            'r52556',
            'mdf:refactor# 添加了自动登录主登录 ',
            '---------------------',
            'r52498',
            'mdf:refactor# 修复了一些问题 ',
            '---------------------',
            'r52487',
            'mdf:refactor# 移除自动登录1.1 接口 ',
            '---------------------',
            'r52478',
            'mdf:refactor# 修改了退回待上架的数据不对的问题 ',
            '---------------------',
            'r52466',
            'mdf:refactor# 修改了无法查站点的问题',
            '---------------------',
            'r52447',
            'mdf:refactor# 修改了退回待上架的数据不对的问题',
            '---------------------',
            'r52382',
            'mdf:refactor# 修改了服务器用户登录提示语',
            '---------------------',
            'r52337',
            'mdf:refactor# 修复了重新推送用户bug',
            '---------------------',
            'r52325',
            'mdf:refactor# 优化了重新推送用户',
            '---------------------',
            'r52295',
            'mdf:refactor# 添加重新推送用户',
            '---------------------',
            'r52272',
            'mdf:refactor# 添加了过滤处理中的用户服务器列表',
            '---------------------',
            'r52266',
            'test:refactor# 添加了测试文件',
            '---------------------',
            'r52232',
            'mdf:refactor# 添加了Daraz平台不合并包裹的处理',
            '---------------------',
            'r52231',
            'mdf:refactor# 移除掉价处理',
            '---------------------',
            'r52229',
            'mdf:refactor# 修复无法退回待上架数据不对的问题',
            '---------------------',
            'r52226',
            'mdf:refactor# 修复无法退回待上架数据不对的问题',
            '---------------------',
            'r52205',
            'add:task#6595 软件管理表的 更新地址需要统一去除 域名前缀， app的更新方法需要调整查表software',
            '---------------------',
            'r52184',
            'mdf:refactor# 修复无法保存的问题',
            '---------------------',
            'r52171',
            'mdf:refactor# 修复退回待上架数据不对的问题',
            '---------------------',
            'r52164',
            'mdf:refactor# 修复了品连的问题',
            '---------------------',
            'r52162',
            'mdf:refactor# 修复了没有更新数据也推送到队列的问题',
            '---------------------',
            'r52151',
            'mdf:refactor# 移除 重返待上架数据不对的问题',
            '---------------------',
            'r52116',
            'mdf:refactor# 修复了不该删除服务器用户的问题',
            '---------------------',
            'r52114',
            'mdf:refactor# 移除 重返待上架数据不对的问题',
            '---------------------',
            'r52093',
            'mdf:refactor# 修复退回待上架、重返待上架数据不对的问题',
            '---------------------',
            '',];
        sort($str);
        foreach ($str as $v) {
            if (strpos($v, 'add') !== false || strpos($v, 'mdf') !== false) {
                echo $v . '<br/>';
            }
        }
        die;
    }

    private function testJoom()
    {

        $backInfo['channel_order_number'] = '1111111232154';
        $re = (new DistributionStockInCallBack($backInfo))->execute();
        echo 'ok';
        die;

//        $accountid = 191;
        $accountid = 1;

//        $shop = Cache::store('JoomShop')->delTableRecord($accountid);
//        var_dump($shop);die;
        $shop['access_token'] = 'SEV0001MTU0NTc5MDA1MnxfaDAxcXdVSnpHR09lVXFTTzVpYWVmdUtfdmZ5MldBMjlRYVBIZ0o0X0Exdkg2MWpCWTZhMjJERkdJa1JuU0otcml2bTNHb2lrNTFKLUZlekFQREo5Sko1Tkp4R1lJaHVyR3VkY0plVkxYNGVkMGhFV0N4aUw0NXp6Z0lDelhJYzhZcXV6RWR6djFQcF9fMmw4ck9vRW9yTHRKVktfQ3h3Qk9WOHAybXJBZ3NEZzV6Y3pUMEZrUzN0dEpFMkY4cHRRQzlofIb4Vdemtr3wjiL31nKVJNdBNuORtOhl0Z5WgajLPSST';
        $shop = Cache::store('JoomShop')->getTableRecord($accountid);
        $shop['refresh_token'] = 'SEV0001MTUxNjYwOTEwN3xHVTBoMEZEdVFUVndIZGx2Ty1qLTgyTV8tcHRPTU5iRkEtY29hNnpneGRCS3BGX3ozWmE5TGs3OUFJNjd4a29uVUhxc2RmSzVwX0RBX2NXVld0OHpIMFJfeXYyYlQwTlAzRDc2ZmVQbFhCYmloQ1loOHhLMnNGVHZuSV9URW9uaHlBdzFfWEdVYkcxenFnOWU5enBjQ3RwZ254amVfd29lVmtyU3FIaTN4YnJzSFM2NXhRMDloTm1POGFkck8zdW9Udz09fGz4c7bDejijTjh-DoLWNxX1IA-sv7Xf46-Rt1rGunBW';
//        $re = (new \app\order\queue\JoomOrderQueue(191))->execute();
        $orderId = 'O28287NW';
//        var_dump($shop);die;
        $re = (new JoomOrdersApi($shop))->getOrdersById($orderId);
//            $re = (new OrderHelp())->saveAllDir();

//        $re = (new \app\order\task\JoomOrder())->execute();
        var_dump($re);
        echo 'ok';

        die;
    }

    private function testZoodMall()
    {

        //测试更新sku重量接口
//        $data = [
//            '494421' => 288,
//        ];
//        $re = (new GoodsSku())->batchUpdateWeightByASkuId($data);

        //
//        $re = (new \app\report\service\StatisticGoods())->getPackageSkuAverageWeight();


//        商家编号149
//商家密钥0a5056e0c99aa6ed9d08f4b92d3971a4
//        $data['client_id'] = 149;
//        $data['client_secret'] = '0a5056e0c99aa6ed9d08f4b92d3971a4';
//        var_dump($data);die;
//        $data['access_token'] = '4jt33ll4llluh0cl931etobv861';
//        $api = new ZoodmallBaseApi($re);
//        $re = $api->refresh_access_token();
//            ChannelAccountConst::channel_Zoodmall;

//        $re = $api->fulfillOne('Z1825500005683','G885641111',['GF0142503']);
//        $re =$api->getOrdersById('T1825500005683'); //无效
//        $re = $api->getOrderLists();

//        $re = Cache::store('ZoodmallAccount')->getTableRecord(1);
//        $api = new ZoodmallOrderApi($re);
//        $orderId = 'Z1832900027516';
//        $orderId = 'Z1834300038549';
//        $re = (new ZoodmallOrderService())->downOrderByOrderIds(1,'',$orderId);

//        $data = [
//             'account_id' => 1,
//             'orderIds' => 'Z1832500023197,Z1833100029597,Z1832900027516',
//          ];
//        $re = (new \app\order\queue\ZoodmallOrderByOrderIdQueue($data))->execute();
//        $re = $api->getOrdersById($orderId);
//        $re = $api->refresh_access_token();

//        $re = (new \app\order\queue\ZoodmallOrderQueue(1))->execute();

//        $re = (new ZoodmallToLocalOrder())->execute();


//        $re = [ "server_ids"=> "[1]", "file_name"=> "服务器成员_2228_2018_12_12_13_57_43.xlsx", "apply_id"=> "2638"];
//        $re  = (new ServerExportQueue($re))->execute();
        $re = (new AccountCompanyService())->addCompanyByAccount();
        var_dump($re);
        die;
    }

    private function testVirtualRuel()
    {


//        var_dump(Cache::store('Order')->delVirtualRuleSet());die;
//        $testdata = json_decode('{ "taskOrder": { "id": 101, "virtual_order_apply_detail_id": 81, "task_number": "VT1891057448477659", "order_number": "", "task_id": 0, "functionary_id": 44, "quantity": 1, "seller_cost": "99.0000", "estimate_cost": "109.0000", "order_cost": "0.0000", "order_id": "", "order_currency": "RUB", "task_currency": "", "order_time": 0, "task_time": 1536595200, "msg_time": 0, "msg_true_time": 0, "create_time": 0, "finish_time": 0, "status": 2, "remark": "", "account_id": 281, "sku_id": 100043, "sku": "BL9988906", "asin": "", "account_name": "fagfagf", "keyword": "adgshfs", "product_location": "fjgoesgj", "product_link": "http://agagaggag", "type": 3, "thumb": "", "channel_id": 4, "seller_id": 15, "site": "", "type_id": 3 }, "userInfo": { "user_id": 2228, "realname": "李佰敏", "username": "13535050984" }, "time": 1536638006 }',true);
//        $re = new VirtualOrderAutomationTaskerQueue($testdata);
//        $re = $re->execute();
//        var_dump($re,$testdata);die;

//        $log = [];
//        $taskOrder = (new VirtualOrderHelp())->getMissionInfo(81);
//        $re = VirtualRuleCheckService::checkRule($testdata['taskOrder'],$log);

        $re = (new VirtualOrderHelp)->automationMissionAllocationBuyer();
        var_dump($re);
        die;
    }

    //部门负责人更换，账号基础资料成员信息 需要自动变更
    public function testUserDep()
    {

        $re = (new User())->changeStatus('0123');
        var_dump($re);
        die;

        $api = new ManagerServer();
        $re = $api->serverAccount('MAC-MIN', '192.168.1.1', '00-1B-21-09-00-51', 8, true);
//        $re = $api->getVisitIp();
        var_dump($re);
        die;


        $leader = json_decode('[{"user_id":66,"job_id":19},{"user_id":394,"job_id":16}]', true);
        $departmentId = 219;
//        $api = new DepartmentUserMapService();
//        $leader = $api->updateLeaderServer($departmentId);
//        $leader = $api->getLeader($departmentId);
//        $leader = $api->updateLeaderServer($departmentId,[],[2229]);
        $temp = [
            'departmentId' => $departmentId,
            'leader' => $leader,
        ];
        $re = (new DepartmentUserMapBatchQueue($temp))->execute();

        var_dump($re);
        die;


    }

    public function fanyi()
    {
        set_time_limit(0);
        $encryption = new Encryption();
        $model = new ServerUserMap();
        $list = $model->where('password_code', 'NOT LIKE', 'S%')->select();
//        var_dump($list);die;
        $i = 0;
        foreach ($list as $k => $v) {

            $pwd = $encryption->decrypt($v['password']);
            $i += $model->save(['password_code' => $pwd], ['user_id' => $v['user_id'], 'server_id' => $v['server_id']]);

        }
        echo '成功解析了：' . $i . '个<hr>';
        echo 'ok';
        die;
    }


    private function testImg()
    {
//        $api = RecognitionApi::instance()->loader('BaiduImage');
//        $api->setApi('11560951','EroOGyCuRLTifkddgOchiEPV','uo1xoXaLSt4AbaWGOIFZA3HVXO2QW4EI');
//        $re = $api->advanced('images/test/21.jpg','basicGeneral');
//        $re = $api->advanced('images/test/zz.jpg','basicAccurate');
//        $re = $api->advanced('images/test/ssz.jpg','basicAccurate');
//        var_dump($re['data']);die;
        $api = new BasicAccountService();

        $re = $api->automatic('images/test/ssz.jpg', 1);
        var_dump($re);
        $re = $api->automatic('images/test/zz.jpg', 2);
        var_dump($re);
        die;
    }

    public function getImageValue($data, $name = '', $num = null)
    {
        $list = $data['words_result'];
        foreach ($list as $key => $v) {
            $value = $v['words'];
            $pos = strpos($value, $name);
            if ($pos !== false) {
                $pos += strlen($name);
                var_dump($value, $pos, $num);
                if ($num) {
                    return substr($value, $pos, $num);
                }
                return substr($value, $pos);
            }
        }
    }

    private function label1()
    {
        $app = new \app\carrier\service\Shipping();
        $result = $app->getSelfControlLabel('1083796927046747200', 703);
        if ($result['success']) {
            echo $result['file'];
            exit;
        } else {
            echo $result['msg'];
            exit;
        }
    }

    public function jumiaTest()
    {


        $params['account_id'] = 1;
        $params['orderIds'] = ['5080709'];
        $api = new JumiaOrderByOrderIdQueue($params);
        $re = $api->execute();
        var_dump($re);
        die;
//        $pages = json_decode('{"pack":{"number":153195317170,"order_number":153195317170,"customer_number":"","package_id":"1131111423545314816","currency":"USD","order_id":"1131111423490788864","channel_id":13,"channel_account_id":1,"warehouse_id":6,"shipping_id":6174,"estimated_weight":"100.000","with_electricity":0,"name":"Ekaterina Saladkova","zip":"143910","phone":"+79151488058","country_code":"RU","province":"Moskovskaya obl","city":"Balashikha","country_name":"Russian Federation","street":"ul Sverdlova, d 1, kv 186","street2":"","email":"","tel":"+79151488058","street_address":"ul Sverdlova, d 1, kv 186","declared_amount":"3.0000","declared_currency":"USD","declared_weight":"100.0000","declared_info":{"197532":{"sku":"EI9931701","declared_name_en":"The curtain","declared_name_cn":"\u7a97\u5e18","qty":2,"declared_value":"1.5000","declared_value_currency":"USD","declared_weight":50,"hs_code":"3925300000","url":""}},"product":{"197532":{"title_en":"The curtain","title_cn":"\u7a97\u5e18","weight":50,"price":"0.0000","hs_code":"3925300000","goods_id":126469,"battery":"","sku":"EI9931701","order_source_detail_id":1131111423503371776,"height":1,"width":100,"length":100,"qty":2}},"shipping_method_code":"Joom Logistics","shortname":"Joom Logistics RM","delivery_mode":"0","sender":{"sender_code":"","sender_name":"Mao Jinjun","sender_country":"China","sender_state":"guangdognsheng","sender_city":"zhongshanshi","sender_district":"sanjiaozhen","sender_street":"No. 39, East of Jinsan Avenue, Triangle Town, Zhongshan City, Guangdong Province (opposite to Chengtai)","sender_zipcode":"528445","sender_phone":"18098940709","sender_mobile":"18098940709","sender_company":"Rondaful","sender_email":"salena@rondaful.com"},"pickup":{"pickup_name":"Mao Jinjun","pickup_country":"China","pickup_state":"guangdognsheng","pickup_city":"zhongshanshi","pickup_district":"sanjiaozhen","pickup_street":"No. 39, East of Jinsan Avenue, Triangle Town, Zhongshan City, Guangdong Province (opposite to Chengtai)","pickup_zipcode":"528445","pickup_email":"salena@rondaful.com","pickup_mobile":"18098940709","pickup_company":"Rondaful"},"refund":{"refund_name":"","refund_country":"","refund_province":"","refund_city":"","refund_district":"","refund_street":"","refund_zipcode":"","refund_email":"","refund_mobile":"","refund_company":""},"length":100,"width":100,"height":1,"is_need_return":"0"},"request":"0PJML428","response":"{\"code\":0,\"data\":{\"trackingNumber\":\"RY006416148HK\",\"shippingOrderNumber\":\"\"}}"}',true);
//        $page = $pages['pack'];
//        $page['channel_id'] = 13;
//        $api = new JumiaOnLine();

        $data = [];
        $data['account_id'] = 1;
        $data['order_id'] = '339416786';
        $data['shipped_time'] = date('Y-m-d H:i:s', time());

        $shipment_detail = [
            'tracking_number' => 'MPDS-339416786-7389',
            'shipping_carrier' => '',//$orderInfo['synchronize_carrier']
        ];


        $data['shipment_detail'] = $shipment_detail;

        $jumia = new JumiaOrderService();
        $response = $jumia->completeShipped($data);

        //缓存日志
        $logs = [
            'time' => date('Y-m-d H:i:s'),
            'type' => '同步跟踪号',
            'data' => $data,
            'response' => $response,
        ];
        Cache::store('JumiaOrder')->addSynchronousLogs($data['order_id'], $logs);
//        $re = $api->createOrder([],$page);
//        var_dump($re);die;
//        $data[] = '[10573223]';
//        $data[] =  '4974433';
//        $data[] =  '4824227';
//        $data =  '332984686,384669286';

        $cache = Cache::store('JumiaAccount')->getTableRecord(7);

//        var_dump($cache);die;

        $api = new JumiaOrderApi($cache);
//        $re = $api->getOrdersItem($data);
//        $re = $api->getDocument($data[0]); //打印面单
//        $re = $api->getOrderShipper();
//        $orderItemIds = [10573223];
//        $re = $api->getDocument($orderItemIds); //打印面单
        $shippingProvider = 'NG-JG-Seko-HongKong-Seko-Station';
//        $re = $api->setStatusToPackedByMarketplace($orderItemIds, $shippingProvider); //标记为打包
//        $re = $api->setStatusToReadyToShip($orderItemIds, $shippingProvider); //标记为准备发货
//        $re = $api->fulfillOne($orderItemIds, $shippingProvider); //发货
//        $re = $api->getOrderById('4987139'); //标记为准备发货
//        $re = $api->getOrderById('4967119'); //标记为准备发货
//        $re = $api->setStatusToShipped('4987139'); //标记为准备发货
        $re = $api->getOrderLists();

        $re = $re === false ? $api->getError() : $re;

        var_dump($re);
    }

    public function ebayTest()
    {

    }

    public function walmartTest()
    {
        $data = [

            'code' => 'test',
            'channel_type' => '0f3e4dd4-0514-4346-b39d-af0e00ea066d',
            'client_id' => '993c293c-7b00-42c6-a329-f302660b73d5',
            'client_secret' => 'MIICeAIBADANBgkqhkiG9w0BAQEFAASCAmIwggJeAgEAAoGBALLfwp/GzzUWs0e9gNu7D9L0GOhDFCxoFm1d2YOmtD/grPCv7U+IVYpinr4M5bcWY1heHmYR9uwUdGmhrVlhsmNv6zugCrEl8XoWLwg3QAEwuW343PZpJEaVojd+qN1hQm2m93yhB5IaTZ4c0k+xj2JPJl+jedsIXxY8QjXGVb1LAgMBAAECgYEAmO0Q2cUBFeRIdkcfybwN9U7XlIr0zlpXAj3AXvvBEvOlJ0lhXCO07lEOYnFoW7V6Tex5lM47Cu8Z781YBRivh0IZ5LKioJst8c/j9nivSpLBEIYpEOu6dqltFkJYGZZFzeCXhGn8zNv1WA5vH6e81o3X/cYYNjf6W4Kf/vBdSrECQQDXKaXaN+j2IeEm93fOfrQAcQmcA3TUJf/KDcL/DE+umlw8s6u78Kqdlxw4k8EGGPZauP43NNbGxJyjMB5LaRzdAkEA1NLqhmGvVRdfHosoEamJfWP7m8/jWsTcZwbkJhEneptm8dH4fe9CiMrZMlmrUo6eINN9aYA4VSfxLpRBPS/sRwJAAb8gzYiup3DW9w4DNvXoWCiSv2V3yVEVpno+HvvmmbA/F28N8dSeTfEwFXV9l6MUPOBLj/8pzytBakG1vT75MQJBAKB53b+wnu2xrtawJWmUBglXv9yQWCYUdQD20Efn/XXVAj3rjs0fAXN2SWpO9QFOauvjrRhFR7TmZlUyEzNZFHcCQQCVQW4IZ1U4TmhHHXrtNiGBUbJyvuibh9G897EYF6kUQ7k/6jUPiPGzinQY7AaAS2jq6pj9s6GnY6ykxMjnYhyg',
        ];

//        $cache = Cache::store("WalmartAccount");
//        $re = $cache->delAccount(1);
//        $re = $cache->getAccount(1);
        //任务测试
//        $task = new WalmartOrder();
//        $re = $task->execute();
        //队列测试
        $queue = new WalmartOrderQueue(1);
        $re = $queue->execute();
        //转化订单测试
//        $task = new WalmartToLocalOrder();
//        $re = $task->execute();

//        $api = new WalmartOrderApi($data);
//        $re = $api->getOrderById('2781585334082');
        $service = new WalmartOrderService();
//        $re = $service->orderFormatting(1,$re);
//        $api = new WalmartOrderApi($data);
//        $re = $api->getOrderLists('2018-05-01','2018-06-07',200);
        /*        $re =  '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';*/
//        $api = new WalmartOrderApi($data);
//        $trackingInfo = [
//            'carrier' => 'FedEx',
//            'methodCode' => 'Standard',
//            'trackingNumber' => '12333634122',
//            'shipDateTime' => time(),
//            'trackingURL' => 'http://www.baidu.com/test',
//        ];
//        $re = $api->fulfillOne('1785295844321',1,$trackingInfo);
//        if($re === false){
//            var_dump($api->getError());die;
//        }

//        var_dump($re);die;
//        dump($re);die;
//        $service = new WalmartOrderService();
//        $re = $service->downOrder(1);
//        $tiem = $api->getTimestamp();
//        $url = 'https://marketplace.walmartapis.com/v3/feeds';
//        $re = $api->getAuthSignature($url,'GET',$tiem);
//        $re = $api->getTimestamp();


        dump($re);
    }

    public function paytmTest(Request $request)
    {
        var_dump($request->domain());
        die;
        $cache = Cache::store('synchronous');

        $orders = $cache->setAmazonSynchronousOrder(331, time(), '1087440173756852576');
        $orders = $cache->setAmazonSynchronousOrder(331, time(), '1087490498467473760');
        $orders = $cache->setAmazonSynchronousOrder(331, time(), '1087550899892791648');

        $api = new QAOUN(331);
        $orders = $api->execute();
        dump(sys_get_temp_dir());

    }

    public function mmallTest()
    {
        $arr = array(
//            '172.20.0.11:8081/guanyi/test?test&action=joomOrder&ids=XVW8J9O4&account=30',
            'http://www.erp.com/cd-test?test',
        );
        foreach ($arr as $v) {
            $this->getHttp($v);
        }
        for ($i = 0; $i < 50; $i++) {
            $this->getHttp('http://www.erp.com/cd-test?test');
        }

    }

    public function joomOrder()
    {
//        $datass = json_decode('{"time":"2018-06-25 21:42:17","type":"libaimin","order_id":1120874429402054688,"orderInfo":{"id":"1120874429402054688","channel_account_id":1,"buyer_id":"647105301","channel_id":10,"order_number":"rfpaytm-5413858844","channel_order_number":"5413858844","channel_order_id":1973,"uploaded_deadline":0,"synchronize_shipping_id":5367,"synchronize_shipping_time":1529737628,"synchronize_tracking_number":"1000027736356","synchronize_carrier":"Gati_International","package_number":152951236955,"items":[{"transaction_id":"5790763572","channel_item_id":"110173623","channel_sku_quantity":1,"id":"1120874429418831904"}],"sendType":0},"account":{"expiry_time_cat":"1528287241","client_secret_cat":"F6dEuYbLDq528BmO50HuQ1cKhNkjCId8ogElNq4KKbY","expiry_time":"1530020181","updater_id":"310","update_time":"1529068976","account_name":"rondaful","is_authorization_cat":"1","lmd_enabled":"0","access_token":"b2d7d06e-e7ce-4ac9-83f2-8e70c4121d7b","id":"1","create_time":"1528200730","is_authorization":"1","creator_id":"2228","access_token_cat":"eyJhbGciOiJSUzI1NiIsImtpZCI6IlRhNzVFZmdzWEoyam41Ym01WlJwNWciLCJ0eXAiOiJKV1QifQ.eyJqdGkiOiIxNzUxOGNkNTc4NDI0NTFkOWMyNjY3MjdlYjNkYWI5ZCIsImV4dF9hdHRyIjp7fSwic3ViIjoicm9uZGFmdWwiLCJzY29wZSI6WyJjb20ucGF5dG1tYWxsLmMyaS5zY29wZXMuYXBpLmFzZ2FyZCJdLCJjbGllbnRfaWQiOiJyb25kYWZ1bCIsImNpZCI6InJvbmRhZnVsIiwiYXpwIjoicm9uZGFmdWwiLCJncmFudF90eXBlIjoiY2xpZW50X2NyZWRlbnRpYWxzIiwicmV2X3NpZyI6IjQ5Y2I5OWZiIiwiaWF0IjoxNTI4MjAwODQyLCJleHAiOjE1MjgyODcyNDIsImlzcyI6Imh0dHBzOi8vYzJpaW50ZXJuYXRpb25hbC5wYXl0bW1hbGwuY29tL29hdXRoL3Rva2VuIiwiemlkIjoidWFhIiwiYXVkIjpbInJvbmRhZnVsIiwiY29tLnBheXRtbWFsbC5jMmkuc2NvcGVzLmFwaSJdfQ.GXTZ4xvKvYl66OlakOLHhefUvQdqDigr6vd84LD-VpEI3zc8DzNkkHTkEx2ho7ghfpWNg885C5367O7L3Qh8at0VYS2WAX8bclDMV6ey2CERfFIlmJE1gbWm0AHnDsMu7I4DXAuVhbWiSRagFK3DwxptRcJOQR6OqA8qkHk0VcxX9i5cYLMqu36633G90a9oFuWr1j-39vjeKWSJKt0c020kzohWqlvYV5grtsLHfvLk8aWJfx1Wo-SLW1MgXJojenKeKiutl-a1mBoX5xiHag8nh_MtYsiIJmpGWXzjJIzmXdvN9jRuyaru56VRP1vNPbmpu6Fq2zvZf69truBEBw","refresh_token":"b2d7d06e-e7ce-4ac9-83f2-8e70c4121d7b","download_health":"0","client_id":"merchant-c2i_rondaful","download_listing":"0","is_invalid":"1","code":"rfpaytm","download_order":"60","email":"paytmchu@outlook.com","client_secret":"19b09ea7-4326-43eb-b9c7-c2ce1161a81e","redirect_uri":"","paytm_enabled":"1","status":"1","password":"paytmchu","sync_delivery":"1","merchant_id":"458950","client_id_cat":"rondaful"}}', true);
        $account = Db::name('joom_shop')->where('id=44')->find();
        $id = 'GEMYGJVD';
//        $itme_id = '3998984790';
//
        $api = new PaytmOrderApi($account);
//        $re = $api->downloadPackingLabel($itme_id);
//        $re = $api->markShipped($itme_id);
        $re = $api->getOrderById($id);
        var_dump($re);
        die;
//        $apiServer = new PaytmOrderService();
//        $re = $apiServer->downOrderByOrderIds(1,$account,$id);

        var_dump($re[0]['items']);
        die;

//        $api = new PaytmSynchronous();
//        $re = $api->handlerOrderId('111',11);

        $datas = json_decode('{"time":"2018-06-27 20:45:45","type":"\u540c\u6b65\u8ddf\u8e2a\u53f7","data":{"account_id":1,"order_id":"5436262369","shipped_time":"2018-06-26 16:55:41","shipment_detail":{"tracking_number":"670659350","shipping_carrier":"Gati_International","shipment_item":[{"item_id":"128955344","transaction_id":"5814949348","quantity":1,"description":"system"}]}},"retsult":{"state":0,"msg":"RequestError"}}', true);
        $data = $datas['data'];
        $datasss = json_decode('{"time":"2018-06-26 14:22:20","type":0,"order_id":1121607742190256160,"orderInfo":{"id":"1121607742190256160","channel_account_id":1,"buyer_id":"1008782734","channel_id":10,"order_number":"rfpaytm-5427993885","channel_order_number":"5427993885","channel_order_id":4617,"uploaded_deadline":0,"synchronize_shipping_id":5367,"synchronize_shipping_time":1529920916,"synchronize_tracking_number":"1000027839989","synchronize_carrier":"Gati_International","package_number":152968687259,"items":[{"transaction_id":"5805991424","channel_item_id":"91886542","channel_sku_quantity":1,"id":"1121607742202839072"}],"sendType":0},"account":{"expiry_time_cat":"1528287241","client_secret_cat":"F6dEuYbLDq528BmO50HuQ1cKhNkjCId8ogElNq4KKbY","expiry_time":"1530020181","updater_id":"2228","update_time":"1529934356","account_name":"rondaful","is_authorization_cat":"1","lmd_enabled":"0","access_token":"b2d7d06e-e7ce-4ac9-83f2-8e70c4121d7b","id":"1","create_time":"1528200730","is_authorization":"1","creator_id":"2228","access_token_cat":"eyJhbGciOiJSUzI1NiIsImtpZCI6IlRhNzVFZmdzWEoyam41Ym01WlJwNWciLCJ0eXAiOiJKV1QifQ.eyJqdGkiOiIxNzUxOGNkNTc4NDI0NTFkOWMyNjY3MjdlYjNkYWI5ZCIsImV4dF9hdHRyIjp7fSwic3ViIjoicm9uZGFmdWwiLCJzY29wZSI6WyJjb20ucGF5dG1tYWxsLmMyaS5zY29wZXMuYXBpLmFzZ2FyZCJdLCJjbGllbnRfaWQiOiJyb25kYWZ1bCIsImNpZCI6InJvbmRhZnVsIiwiYXpwIjoicm9uZGFmdWwiLCJncmFudF90eXBlIjoiY2xpZW50X2NyZWRlbnRpYWxzIiwicmV2X3NpZyI6IjQ5Y2I5OWZiIiwiaWF0IjoxNTI4MjAwODQyLCJleHAiOjE1MjgyODcyNDIsImlzcyI6Imh0dHBzOi8vYzJpaW50ZXJuYXRpb25hbC5wYXl0bW1hbGwuY29tL29hdXRoL3Rva2VuIiwiemlkIjoidWFhIiwiYXVkIjpbInJvbmRhZnVsIiwiY29tLnBheXRtbWFsbC5jMmkuc2NvcGVzLmFwaSJdfQ.GXTZ4xvKvYl66OlakOLHhefUvQdqDigr6vd84LD-VpEI3zc8DzNkkHTkEx2ho7ghfpWNg885C5367O7L3Qh8at0VYS2WAX8bclDMV6ey2CERfFIlmJE1gbWm0AHnDsMu7I4DXAuVhbWiSRagFK3DwxptRcJOQR6OqA8qkHk0VcxX9i5cYLMqu36633G90a9oFuWr1j-39vjeKWSJKt0c020kzohWqlvYV5grtsLHfvLk8aWJfx1Wo-SLW1MgXJojenKeKiutl-a1mBoX5xiHag8nh_MtYsiIJmpGWXzjJIzmXdvN9jRuyaru56VRP1vNPbmpu6Fq2zvZf69truBEBw","refresh_token":"b2d7d06e-e7ce-4ac9-83f2-8e70c4121d7b","download_health":"0","client_id":"merchant-c2i_rondaful","download_listing":"0","is_invalid":"1","code":"rfpaytm","download_order":"60","email":"paytmchu@outlook.com","client_secret":"19b09ea7-4326-43eb-b9c7-c2ce1161a81e","redirect_uri":"","paytm_enabled":"1","status":"1","password":"paytmchu","sync_delivery":"180","merchant_id":"458950","client_id_cat":"rondaful"}}', true);
        $account = $datasss['account'];


        $paytm = new \app\order\service\PaytmOrderService();
        $re = $paytm->completeShipped($data, $account);

        var_dump($re);
        echo 'OK';


    }

    public function task()
    {
        $task = new JoomOrderTask();
        $list = $task->execute();
        echo $list . 'taskOK';
    }

    public function queue()
    {
        set_time_limit(0);
        $joom_queue = new JoomOrderQueue(1);
        $list = $joom_queue->execute();
//        $data = Cache::store('JoomShop')->getAccountById(1);
//        var_dump($data);die;
//            $JoomOrderApi = new JoomOrdersApi($data);
//            $list =$JoomOrderApi->getOrdersById('JJ6JW24');
//            dump($list);
        echo $list . 'queueOK';
    }

    public function jtask()
    {
        $task = new JTLOrder();
        $list = $task->execute();
        echo $list . 'jtaskOK';
    }

    public function getAToken()
    {
//        $filter[] = ['is_invalid', '==', 1];
//        $result = Cache::filter(Cache::store('JoomShop')->getAllAccounts(), $filter, 'id,code,shop_name,is_invalid');
//        dump($result);die;
        set_time_limit(0);
//        $cache = Cache::store('PaytmAccount');
//        $data = db('paytm_account')->where('id',1)->find();
//        var_dump($data);
//        foreach ($data as $key => $val) {
//            $cache->updateTableRecord(1, $key, $val);
//        }

//        $data = db('paytm_account')->where('id',1)->find();

//        $data['password'] = 'paytmchu';
//        $data = Cache::store('PaytmAccount')->getTableRecord(1);
//        var_dump($data);die;
//        $paytm = new PAA($data);
//        $re = $paytm->refresh_access_token();
//        $paytm = new PCA($data);
//        $re = $paytm->getCurrencies();
//        $paytm = new POA($data);
//        $re = $paytm->getOrders();

//        $paytm = new POAT();
//        $re = $paytm->downOrder(1);

        $accountList = Cache::store('PandaoAccountCache');
        var_dump($accountList->getTableRecord());
        die;

        $task = new POTask();
        $re = $task->execute();
//        $order_id = '5202182575';
//        $re = $paytm->getOrderById($order_id);

        dump($re);
    }

    public function getHttp($url)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_PORT => "8081",
            CURLOPT_URL => "$url",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_POSTFIELDS => "ids=%5B%221120097991698547968%22%2C%221120097995838326016%22%5D&export_type=0",
            CURLOPT_HTTPHEADER => array(
                "Cache-Control: no-cache",
                "Postman-Token: 6877238a-53c6-45ae-99d5-2d30559fa17a"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            echo $response;
        }
    }

    private function testServerOn()
    {

        $re = (new ServerReportTimePastTask())->execute();

//        $re = ReportUnpackedByDate::add('2',110);
        var_dump($re);
        die;

        $data = json_decode();

//        $i = 0;
//        $time = time();
//        $id = 0;
//        foreach ($data['browsers'] as $v1){
//            $i ++ ;
//            $j = 0;
//            foreach ($v1 as $v){
//                $id ++;
//                $j ++;
//                $now = $time - 10000 + (100 * $j) + $j;
//                $one = [
//                    'id' => $id,
//                    'type' => $i,
//                    'content' => $v,
//                    'create_time' => $time,
//                    'update_time' => $now,
//                ];
//                (new BrowserCustomer())->insert($one);
//            }
//        }
//        for($i = 0 ;$i < 250;$i++){
        echo BrowserCustomer::getUA();
        echo '<hr>';
//        }
        echo 'ok';
        die;
    }

}
