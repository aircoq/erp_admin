<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 17-12-15
 * Time: 下午2:30
 */

namespace app\publish\task;

use app\common\service\UniqueQueuer;
use app\common\exception\TaskException;
use app\index\service\AbsTasker;
use app\common\model\aliexpress\AliexpressRenewexpireLog;
use app\internalletter\service\InternalLetterService;
use app\common\model\aliexpress\AliexpressAccount;

class AliexpressNotDelayWsValidNum extends AbsTasker
{
    public function getName()
    {
        return "速卖通活动中未被延期发钉钉";
    }

    public function getDesc()
    {
        return "速卖通活动中未被延期发钉钉";
    }

    public function getCreator()
    {
        return "hao";
    }

    public function getParamRule()
    {
        return [];
    }


    public function execute()
    {
        set_time_limit(0);
        try {
            $page=1;
            $pageSize=100;

            $start_time = strtotime(date('Y-m-d'));
            $end_time = strtotime(date('Y-m-d 23:59:59'));

            $where=[
                'a.status' => ['in',[-1,2]],
                'a.run_time' => ['between',[$start_time, $end_time]],
            ];

            $data = [];
            $accountIds = [];
            do {

                $model = new AliexpressRenewexpireLog();

                $list = $model->alias('a')->field('a.product_id, p.account_id, p.salesperson_id')->join('aliexpress_product p','a.product_id = p.product_id','left')->where($where)->page($page++, $pageSize)->select();

                if(empty($list)) {
                    return;
                }

                foreach ($list as $key => $val) {
                    $val = $val->toArray();
                    //以用户id为分组
                    $data[$val['salesperson_id']][$val['account_id']][] = [];
                    array_push( $data[$val['salesperson_id']][$val['account_id']], $val);

                    //获取所有的账号
                    array_push($accountIds,$val['account_id']);
                }

            }while(count($list) == $pageSize);

            //发送钉钉
            if($data && $accountIds) {

                $accountIds = array_unique($accountIds);
                $accounts = (new AliexpressAccount())->field('code,id')->whereIn('id', $accountIds)->select();

                $accountList = [];
                foreach ($accounts as $val) {
                    $accountList[$val['id']] = $val['code'];
                }

                $this->sendLetter($data, $accountList);
            }
            return true;
        }catch (Exception $exp){
            throw new TaskException($exp->getMessage());
        }
    }


    /**
     * @param $data
     * 发送钉钉
     */
    protected function sendLetter($data, $accountList)
    {

        foreach ($data as $key => $val) {

            if($key && $val && is_array($val)) {

                $letterInfo = '';
                foreach ($val as $k =>$v) {

                    $productIds = array_column($v, 'product_id');
                    $productIds = implode('、', $productIds);

                    $letterInfo.= '账号简称:'.$accountList[$k].' 活动未被延期的产品ID：'.$productIds.';';
                }


                if($letterInfo) {

                    $dingParams = [
                        'receive_ids'=> $key,
                        'title'=> '活动未被延期的产品系统通知',
                        'content'=> rtrim($letterInfo,';').'，请前往平台后台手动处理',
                        'type'=> 32,
                        'dingtalk'=> 1,
                        'create_id' => 1
                    ];

                    InternalLetterService::sendLetter($dingParams);
                }
            }
        }

        return;
    }
}