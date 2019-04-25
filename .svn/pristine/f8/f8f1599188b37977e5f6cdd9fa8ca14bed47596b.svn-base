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
use app\common\model\amazon\AmazonHeelSaleLog as AmazonHeelSaleLogModel;
use app\index\service\AbsTasker;
use app\publish\queue\AmazonHeelSaleQueuer;
use think\Exception;
use app\publish\service\AmazonHeelSaleLogService;

class AmazonAddHeelSale extends AbsTasker
{
    public function getName()
    {
        return "Amazon跟卖上传产品-停止使用";
    }

    public function getDesc()
    {
        return "Amazon跟卖上传产品-停止使用";
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
            $page = 1;
            $pageSize = 100;


            $where = [
                'status' => ['in', [0]],
                'type' => ['=', 1],
                'submission_id' => ['=', ''],
            ];

            do {

                $heelSale = (new AmazonHeelSaleLogModel())->field('id,rule_id')->where($where)->page($page, $pageSize)->select();

                if (empty($heelSale)) {
                    break;
                } else {

                    //重新加入获取结果队列
                    $this->queue($heelSale);
                    $page = $page + 1;
                }

            } while (count($heelSale) == $pageSize);

        } catch (Exception $exp) {
            throw new TaskException($exp->getMessage());
        }
    }


    public function queue($heelSale)
    {

        foreach ($heelSale as $key => $val) {

            $val = is_object($val) ? $val->toArray() : $val;

            (new UniqueQueuer(AmazonHeelSaleQueuer::class))->push($val['id'], 0);
           /* //无定时上下架
            if (empty($val['rule_id'])) {

                //直接提交跟卖
                (new UniqueQueuer(AmazonHeelSaleQueuer::class))->push($val['id'], 0);
            } else {

                $rule_id = $val['rule_id'];
                $service = new AmazonHeelSaleLogService;
                $rules = $rule_id ? $service->upLowerRuleInfo($rule_id) : [];

                if ($rules) {
                    //定时上下架
                    $service->heelSaleOpen($rules, $val['id']);
                } else {
                    //直接提交跟卖
                    (new UniqueQueuer(AmazonHeelSaleQueuer::class))->push($val['id'], 0);
                }
            }*/
        }
    }
}