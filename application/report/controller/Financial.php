<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-1-17
 * Time: 下午1:52
 *
 * @doc 财务报表
 */

namespace app\report\controller;


use app\common\controller\Base;
use app\common\model\Order;
use app\common\service\ChannelAccountConst;
use app\common\service\ImportExport;
use app\report\service\DateTime;
use think\Db;
use think\db\Query;
use think\Request;

const report_statistic_by_deeps = 'report_statistic_by_deeps';


class Financial extends Base
{
    public function index()
    {
        echo "sss";
    }



    public function search()
    {
        $page = input('get.page');
        $pageSize = input('get.pageSize');
        $filter = input('get.filter');
        $sort = input('get.sort');
        $keyword = input('get.keyword');

    }

    public function ten_sort()
    {
        $ymd = input('get.ymd');

        $channel_id = input('channel_id');
        $time = new DateTime($ymd);
        $begin = $time->middleStart();
        $end = $time->middleEnd();
        $db = Db::table(report_statistic_by_deeps);
        $db->where('dateline','>=',$begin);
        $db->where('dateline','<=',$end);
        if($channel_id){
            $db->where('channel_id', '=', $channel_id);
            $db->group('deeps.channel_id');
        }
        $db->alias('deeps');
        $db->field(['user.realname','deeps.channel_id','deeps.user_id','sum(sale_amount) sum_amount']);
        $db->join('user','user.id = deeps.user_id');
        $db->group('deeps.user_id');
        $ten = $db->select();
        $result = [];
        $amount_increase_ranks = [];
        $amount_increase_rate_ranks = [];
        foreach($ten as $value){
            $value['target_amount'] = $this->target_amount($value['user_id'], $ymd);
            $value['amount_increase'] = $value['sum_amount'] - $value['target_amount'];
            $value['amount_increase_rate'] = $value['sum_amount'] / max($value['target_amount'], 1);
            $amount_increase_ranks[] = ['uid'=>$value['user_id'], 'FinanceRate' =>$value['amount_increase']];
            $amount_increase_rate_ranks[] = ['uid'=>$value['user_id'], 'FinanceRate' =>$value['amount_increase_rate']];
            $result[$value['user_id']] = $value;
        }
        $sort = function($a,$b){
            return $a['FinanceRate'] < $b['FinanceRate'];
        };
        usort($amount_increase_ranks, $sort);
        usort($amount_increase_rate_ranks, $sort);
        foreach($amount_increase_ranks as $rank => $rankdata){
            $result[$rankdata['uid']]['amount_increase_rank'] = $rank+1;
        }
        foreach($amount_increase_rate_ranks as $rank => $rankdata){
            $result[$rankdata['uid']]['amount_increase_rate_rank'] = $rank+1;
        }

        foreach ($result as $key => $data){
            if($data['channel_id'] === channel_wish){
                $data['overall_rank'] = $data['amount_increase_rank'] * 0.5 + $data['amount_increase_rate_rank'] * 0.5;
            }else{
                $data['overall_rank'] = $data['amount_increase_rank'] * 0.55 + $data['amount_increase_rate_rank'] * 0.45;
            }
            $result[$key] = $data;
        }

        usort($result, function($a,$b){
            return $a['overall_rank'] > $b['overall_rank'];
        });
        $result2 = [];
        foreach($result as $rank => $rankdata){
            $result[$rank]['rank'] = $rank+1;
            $result2[] = $result[$rank];
        }

        unset($result);
        unset($amount_increase_ranks);
        unset($amount_increase_rate_ranks);
        return json(['data'=>$result2, 'begin'=>$begin,'end'=>$end]);
    }

    private function target_amount($user_id, $ymd){
        $time = new DateTime($ymd);
        $time->lastMiddle();
        $db = Db::table(report_statistic_by_deeps);
        $db->where('dateline','>=',$time->middleStart());
        $db->where('dateline','<=',$time->middleEnd());
        $db->where('user_id', '=', $user_id);
        $middle = $db->sum('sale_amount');
        $middle = $middle ? $middle : 0;

        $time = new DateTime($ymd);
        $time->lastMonth();
        $db = Db::table(report_statistic_by_deeps);
        $db->where('dateline','>=',$time->monthStart());
        $db->where('dateline','<=',$time->monthEnd());
        $db->where('user_id', '=', $user_id);
        $month = $db->sum('sale_amount');
        $month = $month ? $month : 0;
        $avgMonth = $month / 3;
        $result = ($avgMonth + $middle) / 2;
        return $result;
    }

    public function channel()
    {
        $channel = input('get.channel_id');
        $ymd = input('get.ymd');
        $db = Db::table(report_statistic_by_deeps);
        $db->alias('deeps');
        $db->where('channel_id', $channel);
        $db->join('user', 'user.id = deeps.user_id');
        $this->joinAccount($db, $channel, 'deeps.account_id');

        $db->field(['user.realname,account.code,deeps.sale_amount as amount']);
        $result = $db->select();
        return json($result, 200);
//        return json([['code'=>'aaa','month'=>'12','month_avg_ten'=>'11','realname'=>'aaaa','groupname'=>'group','last_ten'=>11,'target_amount'=>11,'amount'=>12]]);
    }

    public function channelAccount(Request $request)
    {
        $channel = $request->get('channel');
        if(!$channel){
            return json(['message'=>'必需指定平台'], 400);
        }
        $begin = $request->get('begin');
        if(!$begin){
            return json(['message'=>'必需指定开始时间'], 400);
        }
        $begin = new \DateTime($begin);
        $begin = $begin->getTimestamp();
        $end = $request->get('end');
        if(!$end){
            return json(['message'=>'必需指定结束时间'], 400);
        }
        $end = new \DateTime($end);
        $end = $end->getTimestamp();
        $db = Db::table(report_statistic_by_deeps);
        $db->alias('deeps');
        $wheres = [];
        $wheres['deeps.channel_id'] = $channel;
        $wheres['dateline'] = ['<=',$begin];
        $wheres['dateline'] = ['>=',$end];
        $db->where($wheres);
        $db->group('deeps.account_id');
        $result = $db->select();
        $db = Db::table(report_statistic_by_deeps);
        $db->where($wheres);
        $db->group('deeps.account_id');
        $db->alias('deeps');
        $count = $db->count();
        return json(['data'=>$result, 'count'=>$count]);
    }

    public function channelProfit()
    {
        $channel = input('get.channel_id');
        $pageSize = input('get.pageSize', 20);
        $page = input('get.page', 1);
        if(!$channel){
            return json(['message'=>'请指定平台'],400);
        }

        $fvcommonKey = input('get.fvcommonKey');
        $fvcommonVal = input('get.fvcommonVal');

        $db = Db::table('order');
        $db->alias('o');
        $wheres = ['channel_id'=>$channel];
        if($fvcommonKey && $fvcommonVal){
            switch ($fvcommonKey){
                case 1://仓库类型
                    break;
                case 2://销售员
                    break;
                case 3://销售组长
                    break;
                case 4://发货仓库
                    break;
                case 5://邮寄方式
                    break;
            }
        }
//        $wheres['status'] = ['in',[983044,983048,983056]];
        $this->joinAccount($db, $channel, 'o.channel_account_id');
        $db->where($wheres)->page($page, $pageSize);
        $data = $db->select();
        foreach ($data as $key => $val){
            $db2 = Db::table('order_package');
            $db2->where('order_id',$val['id']);
            $package = $db2->select();
            $data[$key]['package'] = $package;
        }
        $db->table('order');
        $count= $db->where($wheres)->count();
        return json(['data'=>$data, 'count'=>$count], 200);
    }

    private function joinAccount(Query $db, $channel, $relation)
    {
        switch ($channel){
            case ChannelAccountConst::channel_CD:
                $db->join('cd_account account', 'account.id='.$relation);
                break;
            case ChannelAccountConst::channel_amazon:
                $db->join('amazon_account account', 'account.id='.$relation);
                break;
            case ChannelAccountConst::channel_ebay:
                $db->join('ebay_account account', 'account.id='.$relation);
                break;
            case ChannelAccountConst::channel_wish:
                $db->join('wish_account account', 'account.id='.$relation);
                break;
            case ChannelAccountConst::channel_aliExpress:
                $db->join('aliexpress_account account', 'account.id='.$relation);
                break;
            case ChannelAccountConst::channel_Lazada:
                $db->join('lazada_account account', 'account.id='.$relation);
                break;
        }
    }

    public function channelProfitExport()
    {
        $titles = [
            '订单号',
            '账号简称',
            '销售员',
            '销售组长',
            '站点',
            '国家',
            '编码',
            '付款日期',
            '发货日期',
            '仓库类型',
            '发货仓库',
            '邮寄方式',
            '包裹号(4738)',
            '跟踪号',
            '总售价',
            '销售额原币',
            '交易费原币',
            'P卡费用原币'
        ];
        $field = [
            'o.id',
            'o.order_number',
            'account.code',
            'o.site_code',
        ];
        $channel = input('get.channel_id');
        if(!$channel){
            return json(['message'=>'请指定平台'],400);
        }

        $fvcommonKey = input('get.fvcommonKey');
        $fvcommonVal = input('get.fvcommonVal');

        $db = Db::table('order');
        $db->alias('o');
        $wheres = ['channel_id'=>$channel];
        $wheres['status'] = ['in',[983044,983048,983056]];
        if($fvcommonKey && $fvcommonVal){

        }
        $this->joinAccount($db, $channel, 'o.channel_account_id');
        $db->where($wheres);
        $data = $db->field($field)->select();
        $result = [];
        foreach ($data as $key => $val){
            $db2 = Db::table('order_package');
            $db2->where('order_id',$val['id']);
            $package = $db2->field('shipping_name')->select();
            $shippingName = arrays_get_vals_by_key($package, 'shipping_name');
            $result[] = [
                $val['order_number'],
                $val['code'],
                join(",",$shippingName),
                "",
                $val['site_code'],
            ];
        }
        return ImportExport::excelExport($result,$titles,'');
    }

    private function db_debug()
    {
        Db::listen(function($sql, $time, $explain){
            // 记录SQL
            echo $sql. ' ['.$time.'s]';
            // 查看性能分析结果
            var_dump($explain);
        });
    }

}