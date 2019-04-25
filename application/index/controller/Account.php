<?php
namespace app\index\controller;

use app\common\controller\Base;
use think\Request;
use app\common\model\wish\WishAccount as WishAccountModel;
use app\common\model\aliexpress\AliexpressAccount as AliexpressAccountModel;
use app\common\model\amazon\AmazonAccount as AmazonAccountModel;
use app\common\model\ebay\EbayAccount as EbayAccountModel;
use app\common\model\ChannelUserAccountMap as DeveloperGroupDetailModel;

/**
 * @module 各个渠道账号管理
 * @title 渠道账号
 * @author phill
 * @url /channels
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2017/3/3
 * Time: 10:11
 */
class Account extends Base
{
    /**
     * @title 读取账号信息
     * @url channels/:channel(\w+)/accounts
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\Exception
     */
    public function accounts(Request $request)
    {
        $result = [];
        $params = $request->param();
        $channel = $params['channel'];
        $type = $request->get('type', '');
        $content = $request->get('content', '');
        $isEdit = $request->get('is_edit',0);
        if (!empty($channel)) {
            $model = NULL;
            $list = [];
            $channel_id = 0;
            $account = [];
            $where = [];
            switch ($channel) {
                case 'wish':
                    $channel_id = 3;
                    $model = new WishAccountModel();
                    break;
                case 'ebay':
                    $channel_id = 1;
                    $model = new EbayAccountModel();
                    break;
                case 'aliExpress':
                    $channel_id = 4;
                    $model = new AliexpressAccountModel();
                    break;
                case 'amazon':
                    $channel_id = 2;
                    $model = new AmazonAccountModel();
                    break;
                default:
                    break;
            }
            if(!empty($channel_id)){
                switch($type){
                    case 'developer':
                        $developerGroupDetailModel = new DeveloperGroupDetailModel();
                        $record = $developerGroupDetailModel->field('channel_id,account_id')->where(['channel_id' => $channel_id])->select();
                        foreach($record as $k => $v){
                            array_push($account,$v['account_id']);
                        }
                        break;
                }
            }
            if(!empty($account) && empty($isEdit)){
                $where['id'] = ['not in',$account];
            }
            if(!empty($content)){
                $where['account_name'] = ['like','%'.$content.'%'];
            }
            if(!is_null($model)){
                $list = $model->field('id,account_name')->where($where)->select();
            }
            foreach ($list as $k => $v) {
                $temp['id'] = $v['id'];
                $temp['account_name'] = $v['account_name'];
                array_push($result,$temp);
            }
        }
        return json($result, 200);
    }
}