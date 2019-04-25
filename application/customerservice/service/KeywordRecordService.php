<?php
namespace app\customerservice\service;

use think\Db;
use think\Exception;
use think\Validate;
use app\common\model\customerservice\MessageKeywordMatch as MessageKeywordMatchModel;

/**
 * User: denghaibo
 * Date: 2019/02/28
 * Time: 13:24
 */

class KeywordRecordService
{

    /**
     * @param $data
     * @return int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function save_message($data)
    {
        $messageKeywordMatchModel = new MessageKeywordMatchModel();
        $re = 0;

        $channel_id = param($data, 'channel_id',0);
        $message_keyword_id = param($data, 'message_keyword_id',0);
        $message_id = param($data, 'message_id',0);
        $account_id = param($data, 'account_id',0);
        $auto_reply = param($data, 'auto_reply',0);
        $message_type = param($data, 'message_type',0);
        $buyer_id = param($data, 'buyer_id','');
        $receive_time = param($data, 'receive_time',0);

        $where['channel_id'] = $channel_id;
        $where['message_keyword_id'] = $message_keyword_id;
        $where['message_id'] = $message_id;
        $where['account_id'] = $account_id;

        $message = $messageKeywordMatchModel->where($where)->find();

        if (!$message)
        {
            $data['channel_id'] = $channel_id;
            $data['message_keyword_id'] = $message_keyword_id;
            $data['message_id'] = $message_id;
            $data['account_id'] = $account_id;
            $data['auto_reply'] = $auto_reply;
            $data['message_type'] = $message_type;
            $data['buyer_id'] = $buyer_id;
            $data['receive_time'] = $receive_time;
            $data['create_time'] = time();
            $re = $messageKeywordMatchModel->save($data);
        }else{
            $re = 2;
        }

        if ($re === false){
            $re = 3;
        }
        return $re;
    }

    public function ebayAccount()
    {
        $ebay_account = Db::table('ebay_account')->field('id,code')->order('id asc')->select();
        return $ebay_account;
    }

    public function amazonAccount()
    {
        $amazon_account = Db::table('amazon_account')->field('id,code')->order('id asc')->select();
        return $amazon_account;
    }

    public function aliexpressAccount()
    {
        $aliexpress_account = Db::table('aliexpress_account')->field('id,code')->order('id asc')->select();
        return $aliexpress_account;
    }

    /**
     * 关键词抓取记录列表条件
     * @param $params
     * @return array
     * @throws Exception
     */
    public function index_where($params)
    {
        $where = [];

        //平台
        if (!empty(param($params, 'channel_id'))) {
            $where['m.channel_id'] = $params['channel_id'];
        }

        //平台
        if (!empty(param($params, 'type'))) {
            $where['k.type'] = $params['type'];
        }

        //账号简称
        if (!empty(param($params, 'account_id'))) {
            $where['m.account_id'] = $params['account_id'];
        }

        //关键词
        if (!empty(param($params, 'keyword'))) {
            $keyword = trim($params['keyword']);
            $keyword = '%' . $keyword . '%';
            $where['k.keyword'] = ['like', $keyword];
        }

        $b_time = !empty(param($params, 'start_date')) ? $params['start_date'] . ' 00:00:00' : '';
        $e_time = !empty(param($params, 'end_date')) ? $params['end_date'] . ' 23:59:59' : '';

        if ($b_time) {
            if (Validate::dateFormat($b_time, 'Y-m-d H:i:s')) {
                $b_time = strtotime($b_time);
            } else {
                throw new Exception('起始日期格式错误(格式如:2017-01-01)', 400);
            }
        }

        if ($e_time) {
            if (Validate::dateFormat($e_time, 'Y-m-d H:i:s')) {
                $e_time = strtotime($e_time);
            } else {
                throw new Exception('截止日期格式错误(格式如:2017-01-01)', 400);
            }
        }

        if ($b_time && $e_time) {
            $where['m.receive_time'] = ['BETWEEN', [$b_time, $e_time]];
        } elseif ($b_time) {
            $where['m.receive_time'] = ['EGT', $b_time];
        } elseif ($e_time) {
            $where['m.receive_time'] = ['ELT', $e_time];
        }
        return $where;
    }

}