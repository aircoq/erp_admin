<?php
namespace app\customerservice\service;

use app\common\model\ebay\EbayAccount;
use app\common\model\MsgRuleSet as MsgRuleSetModel;
use app\common\exception\JsonErrorException;
use app\common\model\MsgRuleSetItem as MsgRuleSetItemModel;
use think\Db;
use think\Exception;
use think\Validate;

/**
 * User: denghaibo
 * Date: 2019/02/28
 * Time: 13:24
 */

class KeywordManageService
{

    /**
     * @title 关键词列表条件
     * @param $params
     * @return array
     * @throws Exception
     */
    public function index_where($params)
    {
        $where = [];

        //关键词类型
        if (!empty(param($params, 'type'))) {
            $where['type'] = $params['type'];
        }

        //状态
        $status = param($params, 'status');
        if ($status === '0' || !empty($status)) {
            $where['status'] = $status;
        }

        //渠道
        if (!empty(param($params, 'channel_id'))) {
            $where[]  = array('exp',$params['channel_id'] . ' = suit_channel_id&' . $params['channel_id'] );
        }

        //关键词
        if (!empty(param($params, 'keyword'))) {
            $keyword = trim($params['keyword']);
            $keyword = '%' . $keyword . '%';
            $where['keyword'] = ['like', $keyword];
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
            $where['create_time'] = ['BETWEEN', [$b_time, $e_time]];
        } elseif ($b_time) {
            $where['create_time'] = ['EGT', $b_time];
        } elseif ($e_time) {
            $where['create_time'] = ['ELT', $e_time];
        }
        return $where;
    }

    public function getKeywords($channel_id)
    {
        $sql = 'SELECT id,keyword from message_keyword where suit_channel_id&' . $channel_id . ' = ' . $channel_id . ' and status = 1';
        $channel_keyword = Db::query($sql);

        return $channel_keyword;
    }


    /**
     * @return array
     */
    public function allType()
    {
        $type = [
            ['label' => '质量问题', 'value' => 1],
            ['label' => '包裹问题', 'value' => 2],
            ['label' => '物流问题', 'value' => 3],
            ['label' => '发货时间', 'value' => 4],
            ['label' => '仓库错发漏发', 'value' => 5],
            ['label' => '买家不满意', 'value' => 6],
            ['label' => '与描述不符', 'value' => 7],
            ['label' => '发票', 'value' => 8],
            ['label' => '其它', 'value' => 9],
        ];
        return $type;
    }

}