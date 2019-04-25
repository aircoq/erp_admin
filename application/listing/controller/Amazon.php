<?php

namespace app\listing\controller;

use app\common\controller\Base;
use app\common\exception\JsonErrorException;
use think\Request;
use think\Exception;
use app\listing\service\AmazonActionLogsHelper;
use think\Cache;
use app\common\service\Common;
use app\publish\service\AmazonListingService;

/**
 * @module listing系统
 * @title amazon listing管理
 * @url listing/amazon
 * Class Amazon
 * @package app\listing\controller
 */
class Amazon extends Base
{

    protected $lang = 'zh';

    private $helper;
    private $uid = 0;

    protected function init()
    {
    }

    public function __construct(Request $request = null)
    {
        parent::__construct($request);

        $this->lang = $request->header('Lang', 'zh');
        $this->helper = new AmazonActionLogsHelper;
        $this->helper->setLang($this->lang);


        $user = Common::getUserInfo();
        if (!empty($user['user_id'])) {
            $this->uid = $user['user_id'];
        }
    }

    /**
     * @title 亚马逊在线listing修改日志
     * @url action-logs
     * @method get
     * @param Request $request
     * @return string
     */
    public function actionLogs()
    {
        try {
            $request = Request::instance();
            $page = $request->get('page', 1);
            $pageSize = $request->get('pageSize', 50);

            //搜索条件
            $param = $request->param();

            if (!isset($param['amazon_listing_id'])) {
                if ($this->lang == 'zh') {
                    return json(['message' => '缺少参数amazon_listing_id'], 400);
                } else {
                    return json(['message' => 'Params error'], 400);
                }
            }

            $data = $this->helper->getList($param, $page, $pageSize);

            return json($data);

        } catch (Exception $exp) {
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }

    /**
     * @title 修改Listing日志
     * @url edit-listing
     * @method post
     * @param Request $request
     * @return \think\response\Json
     */
    public function editListing(Request $request)
    {
        try {
            //接受的参数；
            $params = $request->post();
            $result = $this->validate($params, [
                'type|更改类型' => 'require',
                'data|修改参数' => 'require',
                'remark|备注' => '',
                'cron_time|定时执行' => ''
            ]);

            if ($result !== true) {
                if ($this->lang == 'zh') {
                    throw new Exception($result);
                } else {
                    throw new Exception('Params Error');
                }
            }

            if (!key_exists($params['type'], AmazonActionLogsHelper::$scence_type)) {
                if ($this->lang == 'zh') {
                    throw new Exception('type参数错误');
                } else {
                    throw new Exception('Params Error');
                }
            }

            $res = $this->helper->editListingData($params['data'], $params['type'], $this->uid, $params['remark'] ?? '', $params['cron_time'] ?? 0);

            if ($res['result']) {
                return json(['message' => $res['message']]);
            } else {
                return json(['message' => $res['message']], 400);
            }
        } catch (JsonErrorException $exp) {
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }

    //amazonListing-step-1: request
    //url: post /listing/amazon/testListing?test&account_id=999
    public function testListing(Request $request)
    {
        $account_id = $request->param('account_id');
        $params = [
            'account_id' => $account_id
        ];
        return (new \app\listing\task\AmazonRsyncListing($params))->execute();
    }
    //amazonListing-step-2: getreport and saveReport
    //url: post /listing/amazon/testListingReport?test&account_id=999&report_request_id=50598017529
    public function testListingReport(Request $request)
    {
        $account_id = $request->param('account_id');
        $report_request_id = $request->param('report_request_id');
        $params = [
            'account_id' => $account_id,
            'reportRequestId' => $report_request_id
        ];
        return (new \app\listing\queue\AmazonRsyncListing($params))->execute();
    }
    //amazonListing-step-3: parse report and insert listing to db
    //url: post /listing/amazon/testListingReportUpdate?test&account_id=999&path=listing_2017-12-14-14-41-35.xls
    public function testListingReportUpdate(Request $request)
    {
        $account_id = $request->param('account_id');
        $path = ROOT_PATH . 'public/amazon/' . $request->param('path');
        $params = [
            'account_id' => $account_id,
            'path' => $path
        ];
        return (new \app\listing\queue\AmazonUpdateListing($params))->execute();
    }
    //amazonListing-step-4: skumap queue
    //url: post /listing/amazon/testListingSkuMap?test&listing_id=18027&seller_sku=EC0000901*9|Bloomma-es&channel=2&account_id=999
    public function testListingSkuMap(Request $request)
    {
        $listing_id = $request->param('listing_id');
        $seller_sku = $request->param('seller_sku');
        $channel = $request->param('channel');
        $account_id = $request->param('account_id');
        $params = [
            'listing_id' => $listing_id,
            'seller_sku' => $seller_sku,
            'channel' => $channel,
            'account_id' => $account_id,
        ];
        return (new \app\listing\queue\AmazonUpdateListingSkuMap($params))->execute();
    }

    public function testPublishListingUpdate(Request $request)
    {
        $action_log_id = $request->param('action_log_id');
        return (new \app\listing\task\AmazonPublishListingUpdate($action_log_id))->execute();
    }

    //url: modify listing queue1: /listing/amazon/testListingUpdate
    public function testListingUpdate(Request $request)
    {
        $action_log_id = $request->param('action_log_id');
        return (new \app\listing\queue\AmazonActionLogQueue($action_log_id))->execute();
    }

    //url: modify listing queue2: /listing/amazon/testPublishListingUpdateResult?test&feed_id=50582017527&id=57
    public function testPublishListingUpdateResult(Request $request)
    {
        $feed_id = $request->param('feed_id');
        $id = $request->param('id');
        $params = [
            'account_id' => 999,
            'feedSubmissionId' => $feed_id,
            'publishType' => 'action_log',
            'id' => $id
        ];
        return (new \app\publish\queue\AmazonPublishResultQueuer($params))->execute();
    }


    //amazonBrowseTree-step-1: request
    //url: post /listing/amazon/testBrowseTree?test&account_id=999
    public function testBrowseTree(Request $request)
    {
        $account_id = $request->param('account_id');
        $params = [
            'account_id' => $account_id
        ];
        return (new \app\publish\task\AmazonBrowseTree($params))->execute();
    }
    //amazonBrowseTree-step-2: getreport and saveReport
    //url: post /listing/amazon/testBrowseTreeReport?test&account_id=999&report_request_id=50618017534
    public function testBrowseTreeReport(Request $request)
    {
        $account_id = $request->param('account_id');
        $report_request_id = $request->param('report_request_id');
        $params = [
            'account_id' => $account_id,
            'reportRequestId' => $report_request_id
        ];
        return (new \app\publish\queue\AmazonBrowseTreeQueuer($params))->execute();
    }
    //amazonBrowseTree-step-3: parse report and insert browseTree to db
    //url: post /listing/amazon/testBrowseTreeReportUpdate?test&account_id=999&path=browserTree_2018-01-03-15-31-21.xml
    public function testBrowseTreeReportUpdate(Request $request)
    {
        $account_id = $request->param('account_id');
        $path = ROOT_PATH . 'public/amazon/' . $request->param('path');
        $params = [
            'account_id' => $account_id,
            'path' => $path
        ];
        return (new \app\publish\queue\AmazonBrowseTreeSaveQueuer($params))->execute();
    }


    /**
     * @title 亚马逊批量修改销售价
     * @url /listing/amazon/batch-edit-price
     * @method post
     * @param Request $request
     * @return \think\response\Json
     *
     */
    public function batchEditPrice(Request $request)
    {
        $ids = $request->post('ids');

        if (!$ids) {
            if ($this->lang == 'zh') {
                return json(['message' => '请选择listing'], 400);
            } else {
                return json(['message' => 'Please select a listing.'], 400);
            }
        }

        $service = new AmazonListingService;
        $result = $service->batchEditPrice($ids);

        return json($result, 200);
    }
}
