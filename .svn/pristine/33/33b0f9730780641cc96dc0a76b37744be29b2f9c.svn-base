<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 17-10-16
 * Time: 上午10:04
 */

namespace app\listing\controller;


use app\common\cache\Cache;
use app\common\controller\Base;
use app\common\model\report\ReportStatisticPublishByChannel;
use app\common\model\wish\WishAccount;
use app\common\model\wish\WishWaitUploadProduct;
use app\common\model\wish\WishWaitUploadProductInfo;
use app\goods\service\GoodsPublishMapService;
use app\index\service\Department;
use app\listing\queue\AliexpressCombineSkuQueue;
use app\listing\queue\AliexpressListingUpdateQueue;
use app\listing\queue\AliexpressRsyncProductQueue;
use app\listing\queue\WishCombineSkuQueue;
use app\listing\queue\WishExpressQueue;
use app\listing\queue\WishListingUpdateQueue;
use app\listing\queue\WishListingInsertDb;
use app\listing\queue\WishRsyncListing;
use app\listing\task\WishExpress;
use app\publish\filter\WishFilter;
use app\publish\queue\AliexpressGrapListingQueue;
use app\publish\queue\AliexpressQueueJob;
use app\publish\queue\GoodsPublishMapQueue;
use app\publish\queue\JoomQueueJob;
use app\publish\queue\WishQueueJob;
use app\publish\service\AliexpressTaskHelper;
use app\publish\service\Wangxiaowang;
use app\publish\service\WishHelper;
use app\publish\task\AliexpressAttibutes;
use app\publish\task\AliexpressGetCategory;
use app\publish\task\AliexpressGrapListing;
use app\publish\task\AliexpressSaleMap;
use app\publish\task\AliexpressSkuMap;

use app\publish\task\WishPublish;
use service\wish\WishApi;
use think\db\Query;
use think\Exception;
use think\Request;
use think\Db;
use service\aliexpress\AliexpressApi;

use app\publish\task\WishPublishSPUStatistics;
use app\publish\task\EbayPublishSPUStatistics;
use app\publish\task\AmazonPublishSPUStatistics;
use app\publish\task\JoomPublishSPUStatistics;
use app\publish\task\ShopeePublishSPUStatistics;
use app\publish\task\AliexpressProductOnSelling;
use app\publish\queue\WishInfringeEnd;
use app\report\queue\StatisticByPublishSpuQueue;
use app\report\service\StatisticShelf;
use app\publish\queue\PandaoQueueJob;
use app\publish\queue\PublishProductDownloadQueue;
use app\report\queue\PublishbyTimeExportQueue;



use app\common\model\ChannelUserAccountMap;
use app\common\model\report\ReportStatisticPublishByShelf;

use app\common\service\Encryption;



/**
 * @module listing系统
 * @title listing管理
 * Class Test
 * @package app\listing\controller
 */
class Test extends Base
{
    /**
     * @title 刊登提交测试
     * @url /wish-test
     * @author joy
     * @method get
     * @param Request $request
     * @return string
     */
    public function index(Request $request)
    {

        set_time_limit(0);


        $sql='SELECT * from report_statistic_publish_by_shelf where 1';
        $shelfModel = new ReportStatisticPublishByChannel();

        $rows = Db::query($sql);
        if ($rows) {
            foreach ($rows as $val) {
                $shelfModel->add($val);

            }
        }
        exit('------OK-----');


//     echo    (new Encryption())->decrypt('nKuTal+qea5hhamV06Q=');
//     exit;

//        echo strtotime('2018-11-12').'|'.strtotime('2018-11-13');
//echo date('Y-m-d G:i:s',(1542297600) );
//
//exit;


//        set_time_limit(0);
//
//
//        $sql='SELECT * from report_statistic_publish_by_shelf where channel_id=4  and quantity=0';
//        $shelfModel = new ReportStatisticPublishByShelf();
//
//        $rows = Db::query($sql);
//        if ($rows) {
//            foreach ($rows as $val) {
//                $sql2="select count(*) sku_count from aliexpress_product p
//left JOIN aliexpress_product_sku v on v.product_id=p.product_id where p.account_id=".$val['account_id']." and p.goods_id=".$val['goods_id']."
//and salesperson_id=".$val['shelf_id']." and publish_time BETWEEN ".$val['dateline']." and ".($val['dateline']+24*3600);
//                $count = Db::query($sql2);
//                if ($count)
//                {
//                    $sku_count=$count[0]['sku_count'];
//                    $whe = [
//                        'dateline' => $val['dateline'],
//                        'channel_id' => $val['channel_id'],
//                        'account_id' => $val['account_id'],
//                        'shelf_id' => $val['shelf_id'],
//                        'goods_id' => $val['goods_id'],
//                    ];
//                    $rlt=$shelfModel->where($whe)->update(['quantity' => $sku_count]);
//
//                }
//
//            }
//        }
//
//exit('-------------------------------');



//        set_time_limit(0);
//
//
//        $sql='SELECT * from report_statistic_publish_by_shelf where channel_id=2  and quantity=0';
//        $shelfModel = new ReportStatisticPublishByShelf();
//
//        $rows = Db::query($sql);
//        if ($rows) {
//            foreach ($rows as $val) {
//                $sql2="select count(*) sku_count from amazon_publish_product p
//left JOIN amazon_publish_product_detail v on v.product_id=p.id where p.account_id=".$val['account_id']." and p.goods_id=".$val['goods_id']."
//and creator_id=".$val['shelf_id']." and update_time BETWEEN ".$val['dateline']." and ".($val['dateline']+24*3600);
//                $count = Db::query($sql2);
//                if ($count)
//                {
//                    $sku_count=$count[0]['sku_count'];
//                    $whe = [
//                        'dateline' => $val['dateline'],
//                        'channel_id' => $val['channel_id'],
//                        'account_id' => $val['account_id'],
//                        'shelf_id' => $val['shelf_id'],
//                        'goods_id' => $val['goods_id'],
//                    ];
//                    $rlt=$shelfModel->where($whe)->update(['quantity' => $sku_count]);
//
//                }
//
//            }
//        }
//
//exit('-------------------------------');



//        $sql='SELECT * from report_statistic_publish_by_shelf where channel_id=1 ';
//        $shelfModel = new ReportStatisticPublishByShelf();
//
//        $rows = Db::query($sql);
//        if ($rows) {
//            foreach ($rows as $val) {
//                $sql2="select count(*) sku_count from ebay_listing p
//left JOIN ebay_listing_variation v on v.listing_id=p.id where p.account_id=".$val['account_id']." and p.goods_id=".$val['goods_id']."
//and application=1 and start_date BETWEEN ".$val['dateline']." and ".($val['dateline']+24*3600);
//                $count = Db::query($sql2);
//                if ($count)
//                {
//                    $sku_count=$count[0]['sku_count'];
//                    $whe = [
//                        'dateline' => $val['dateline'],
//                        'channel_id' => $val['channel_id'],
//                        'account_id' => $val['account_id'],
//                        'shelf_id' => $val['shelf_id'],
//                        'goods_id' => $val['goods_id'],
//                    ];
//                    $rlt=$shelfModel->where($whe)->update(['quantity' => $sku_count]);
//
//                }
//
//            }
//        }



//        set_time_limit(0);
//
//        $sql='SELECT * from report_statistic_publish_by_shelf where channel_id=3 ';
//        $shelfModel = new ReportStatisticPublishByShelf();
//
//        $rows = Db::query($sql);
//        if ($rows) {
//            foreach ($rows as $val) {
//                $sql2="select count(*) sku_count from wish_wait_upload_product p left JOIN wish_wait_upload_product_variant v on v.pid=p.id where accountid=".$val['account_id']." and uid=".$val['shelf_id']." and goods_id=".$val['goods_id'];
//                $count = Db::query($sql2);
//                if ($count)
//                {
//                    $sku_count=$count[0]['sku_count'];
//                    $whe = [
//                        'dateline' => $val['dateline'],
//                        'channel_id' => $val['channel_id'],
//                        'account_id' => $val['account_id'],
//                        'shelf_id' => $val['shelf_id'],
//                        'goods_id' => $val['goods_id'],
//                    ];
//                    $rlt=$shelfModel->where($whe)->update(['quantity' => $sku_count]);
//
//                }
//
//            }
//        }

        //exit('-------------------------------');




//        \app\report\service\StatisticShelf::addReportShelfNow(
//            2, 12, 1279, 1017971, 1, 1508169600
//        );
//        exit;


//
//        $params=[
//           'channel_id'=> 3,
//            'account_id'=>  629,
//            'shelf_id'=>  4443,
//            'goods_id'=> 285100,
//            'times'=>1,
//            'quantity'=> 1,
//            'dateline'=> 1541692800
//        ];
////        StatisticShelf::addReportShelf( $params['channel_id'],
////            $params['account_id'],
////            $params['shelf_id'],
////            $params['goods_id'],
////            $params['times'],
////            $params['quantity'],
////            $params['dateline']);
//        $task= new StatisticByPublishSpuQueue($params);

      //  $id = $request->param('id');
       // $task = new WishQueueJob('1171130932795937408');//1069563429863688480
        // $task = new WishExpressQueue(['account_id'=>711,'product_id'=>"5bd6ca04705d9516ff52c8be"]);

        //$id='5bd04f95aeb26f33af1151a7';
        //$task = new WishRsyncListing($id);
        // $task = new WishExpressQueue(['account_id'=>711,'product_id'=>"5be1547440c9e45d86516b9e"]);

        // $aGoods = Cache::store('goods')->getGoodsInfo(277533);
        //  print_r($aGoods);

       // $task = new WishListingUpdateQueue(1943648); //540
        //$task=new  WishPublishSPUStatistics();
        //$task=new EbayPublishSPUStatistics();
       // $task=new AmazonPublishSPUStatistics();
       // $task=new JoomPublishSPUStatistics();
        //$task=new AliexpressProductOnSelling();
        //$task=new ShopeePublishSPUStatistics();

//        $task = new  WishInfringeEnd([
//            'tort_id' => 1,//侵权id
//            'goods_id' => 142071,//商品id
//            'ban_shop_id' => '14',//不用下架的店铺id
//            'notice_channel' => '1,2,3',//需要通知的渠道id
//            'reason' => '原因',//原因
//            'channel_id'=>3
//        ]);

       // $task = new PandaoQueueJob(1147557702433505352);


//        $params['file_name']='spu上架时间统计_3764_2018_11_19_11_26_34.xlsx';
//        $params['date_b']="2018-11-15";
//        $params['date_e']="2018-11-19";
//        $params['apply_id']=23413;
//
//        $task=new PublishbyTimeExportQueue($params);


        $params['channel_id']=3;
        $params['start_time']='2018-11-19';
        $params['end_time']='2018-11-19';

        $params['file_name'] = '软件工程师-后端_潘国富_2018_11_19_14_02_09报表.xlsx';
        $params['apply_id'] = 23468;
        $params['flag']='joy';
        $params['spu']='["LA03785","HK00579"]';

        $task=new PublishProductDownloadQueue($params);


        $task->beforeExec();
        $task->execute();
        $task->afterExec();
        exit('--------执行完成----------------');
//------------------------------------------

        $id = $request->param('id', 0);
        $task = new WishListingUpdateQueue($id);
        $task->beforeExec();
        $task->execute();
        $task->afterExec();
        die;

        $id = $request->param('id', 0);
        $task = new AliexpressAttibutes($id);
        $task->beforeExec();
        $task->execute();
        $task->afterExec();
        die;


        $id = $request->param('id');
        $task = new JoomQueueJob($id);
        $task->beforeExec();
        $task->execute();
        $task->afterExec();
        die;


        $task = new AliexpressCombineSkuQueue(["vid" => "839560", "combine_sku" => "BI0003201*3|JE0067800*1"]);
        $task->beforeExec();
        $task->execute();
        $task->afterExec();
        die;
        $data = (new WishWaitUploadProductInfo())->with(['product' => function ($query) {
            $query->field('id,accountid');
        }])->where('product_id', '=', '5a61ac8e5cc9a66b0949ad4a')->find();

        $account = WishAccount::where('id', '=', $data['product']['accountid'])->find();

        $config['access_token'] = $account['access_token'];

        $service = WishApi::instance($config)->loader('Product');

        $post['id'] = $data['product_id'];
        $post['access_token'] = $config['access_token'];
        $post['description'] = str_replace(chr(10), "\n", nl2br($data['description']));

        $response = $service->updateProduct($post);

        dump($response);
        die;

        $id = $request->param('id');
        $task = new WishListingUpdateQueue($id);
        $task->beforeExec();
        $task->execute();
        $task->afterExec();
        die;

        echo date('Y-m-d H:i:s', 1515317540);
        die;


        $data = [
            'id' => 141169,
            'category_id' => 162,
            'spu' => 'QD00067',
            'platform_sale' => '{"ebay": "1", "wish": "2", "amazon": "1", "aliExpress": "1"}',
            'sales_status' => 1,
        ];
        $task = new GoodsPublishMapQueue($data);
        $task->beforeExec();
        $task->execute();
        $task->afterExec();
        die;


        $id = $request->param('id');
        $task = new WishQueueJob($id);
        $task->beforeExec();
        $task->execute();
        $task->afterExec();
        die;


        $id = $request->param('id');
        $task = new WishExpressQueue(['account_id' => 396, 'product_id' => "5a3cc12489889925fbedb7e1"]);
        $task->beforeExec();
        $task->execute();
        $task->afterExec();
        die;


        $id = $request->param('id');
        $task = new WishListingUpdateQueue($id);
        $task->beforeExec();
        $task->execute();
        $task->afterExec();
        die;


        $id = $request->param('id');
        $task = new AliexpressGrapListing($id);
        $task->beforeExec();
        $task->execute();
        $task->afterExec();
        die;

        $subject = "1440pcs/pack Nail Art Flat Bottom Rhinestones DIY Non Hotfix Rhinestones Colorful Multi-size Manicure Nail Art Decoration";
        $wangxiaowang = (new Wangxiaowang());
        $wangxiaowang->BpcarKeyword(200001151, $subject);

        die;


        $id = $request->param('id');
        $task = new AliexpressQueueJob($id);
        $task->beforeExec();
        $task->execute();
        $task->afterExec();
        die;

        $id = $request->param('id');
        $task = new WishRsyncListing($id);
        $task->beforeExec();
        $task->execute();
        $task->afterExec();
        die;


        $id = $request->param('id');
        $config = Cache::store('Account')->aliexpressAccount(34);

        $config['accessToken'] = $config['access_token'];

        $data = (new AliexpressTaskHelper())->getAliexpressProductDetail($config, $id);

        dump($data);
        die;


        $id = $request->param('id');
        $config = Cache::store('Account')->aliexpressAccount(34);

        $config['accessToken'] = $config['access_token'];

        $data = (new AliexpressTaskHelper())->getAliexpressProductDetail($config, $id);

        $sku = '[{"currencyCode":"USD","ipmSkuStock":998,"skuPrice":"16.48","skuStock":true,"aeopSKUProperty":[],"skuCode":"IH0012900"}]';
        $post = [
            'subject' => substr($data['subject'], 0, 128), //标题
            'detail' => $data['detail'], //详情描述
            'aeopAeProductSKUs' => $sku, //sku
            'aeopAeProductPropertys' => json_encode($data['aeopAeProductPropertys']),//产品属性，以json格式进行封装后提交
            'categoryId' => $data['categoryId'], //分类id
            'imageURLs' => $data['imageURLs'], //产品的主图URL列表
            'mobileDetail' => $data['mobileDetail'],
            'currencyCode' => 'USD'
        ];

        print_r($post);

        $api = AliexpressApi::instance($config);

        $server = $api->loader('Product');

        $response = $server->postAeProduct($data);

        return json($response);
        die;


        $id = $request->param('id');
        $task = new AliexpressRsyncProductQueue($id);
        $task->beforeExec();
        $task->execute();
        $task->afterExec();
        die;

        $id = $request->param('id');
        $task = new WishRsyncListing($id);
        $task->beforeExec();
        $task->execute();
        $task->afterExec();
        die;
        $task = new WishQueueJob($id);
        $task->beforeExec();
        $task->execute();
        $task->afterExec();
        die;


        $task = new AliexpressSkuMap($id);
        $task->beforeExec();
        $task->execute();
        $task->afterExec();
        die;


        $task = new WishRsyncListing($id);
        $task->beforeExec();
        $task->execute();
        $task->afterExec();
        die;


        $task = new AliexpressListingUpdateQueue($id);
        $task->beforeExec();
        $task->execute();
        $task->afterExec();


        die;


        $model = new \app\common\model\Test();

        $model->where()->select();


        die;


        $task = new AliexpressAttibutes();
        $task->beforeExec();
        $task->execute();
        $task->afterExec();


        die;
        $task = new AliexpressRsyncProductQueue($id);
        $task->beforeExec();
        $task->execute();
        $task->afterExec();

        die;

        $task = new AliexpressQueueJob($id);
        $task->beforeExec();
        $task->execute();
        $task->afterExec();

        die;


        $task = new WishQueueJob($id);
        $task->beforeExec();
        $task->execute();
        $task->afterExec();


        $id = $request->param('id');

        $task = new AliexpressListingUpdateQueue($id);
        $task->beforeExec();
        $task->execute();
        $task->afterExec();
        die;

        (new WishFilter([]))->generate();
        exit();

        $query = new Query();

        (new WishWaitUploadProduct())->scopeListing($query, [198, 174, 167, 54]);

        die;


        $id = $request->param('id');

        $task = new AliexpressListingUpdateQueue($id);
        $task->beforeExec();
        $task->execute();
        $task->afterExec();
        die;
        $task = new AliexpressQueueJob(1037058947049549952);
        $task->beforeExec();
        $task->execute();
        $task->afterExec();
        die;
        $task = new WishListingInsertDb("48_20171028");
        $task->beforeExec();
        $task->execute();
        $task->afterExec();
        die;
        dump(time_format(1508421725));

        dump(time_format('1508421696'));
        die;

        GoodsPublishMapService::update(4, 'BF99138', 20171019, 0, 0);
        die;
    }
}



