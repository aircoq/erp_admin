<?php

namespace app\report\task;

use app\common\model\amazon\AmazonAccount;
use app\common\model\report\ReportListingByAccount;
use app\common\service\ChannelAccountConst;
use app\index\service\ChannelConfig;
use app\listing\service\AmazonListingHelper;
use app\index\service\AbsTasker;
use app\common\cache\Cache;
use app\report\service\AmazonAccountMonitorService;
use think\Exception;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2018/5/11
 * Time: 17:01
 */
class AmazonAccountMonitor extends AbsTasker
{
    /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return '亚马逊账号统计写入数据库';
    }

    /**
     * 定义任务描述
     * @return string
     */
    public function getDesc()
    {
        return '';
    }

    /**
     * 定义任务作者
     * @return string
     */
    public function getCreator()
    {
        return '翟雪莉';
    }

    /**
     * 定义任务参数规则
     * @return array
     */
    public function getParamRule()
    {
        return [];
    }

    /**
     * 执行方法
     */
    public function execute()
    {
        $this->write();
    }

    /**
     *  统计写入数据库数据
     */
    public function write()
    {
        $this->writeInPackage();
    }


    /**
     * 包裹统计信息写入数据库
     * @return bool
     * @throws Exception
     */
    private function writeInPackage()
    {
        try {
            $reportListingByAccount = new ReportListingByAccount;
            $amazonInfo = (new AmazonAccountMonitorService())->accountList();
            $serv = new AmazonListingHelper();
            if (!empty($amazonInfo)) {
                $channelCofig = new ChannelConfig(ChannelAccountConst::channel_amazon);
                $list_num = $channelCofig->getConfig('channel_list_num');
                $examination_cycle = $channelCofig->getConfig('channel_examination_cycle');
                foreach ($amazonInfo as $k => $v) {
                    $current_time = time();
                    //查询时间数组；
                    $update_time= $v['update_time']+86400;
                    $dayArr = [date('Y-m-d H:i:s',$update_time), date('Y-m-d H:i:s',$current_time)];
                    $days = intval(Round(($current_time - $v['update_time']) / 86400));
                    $temp['account_id'] = $v['account_id'];
                    $temp['channel_id'] = $v['id'];
                    $temp['site'] = $v['site'];
                    $temp['seller_id'] = $v['seller_id'] ?? '';
                    $temp['activation_time'] = $v['update_time'];
                    $temp['estimated_quantity'] = intval($list_num) *$days ;

                    //查询这段时间内，本帐号listing创建的个数
                    $data = $serv->getPublishListingTotalByTime($dayArr, [$v['account_id']]);

                    $temp['actual_quantity'] = $data[$v['account_id']] ?? 0;

                    $newData = $this->checkData($temp);
                    $where['account_id'] = array('eq', $v['account_id']);
                    $result = $reportListingByAccount->where($where)->find();

                    if ($days > $examination_cycle && $result) {
                        $tt['assessment_of_usage'] = 1;
                        $tt['updated_time'] = time();
                        $tt['id'] = $result['account_id'];
                        (new AmazonAccount)->where(['id' => $result['account_id']])->update($tt);
                        //更新缓存
                        $cache = Cache::store('AmazonAccount');
                        foreach ($tt as $key => $val) {
                            $cache->updateTableRecord($result['account_id'], $key, $val);
                        }
                    } else {
                        if ($result['id']) {
                            $newData['update_time'] = time();
                            $reportListingByAccount->update($newData, ['id' => $result['id']]);
                            $id = $result['id'];
                        } else {
                            $id = $reportListingByAccount->insertGetId($newData);
                        }
                        $record = [];
                        $record['id'] = $id;
                        $record['day'] = $dayArr;
                        $record['account_id'] = $v['account_id'];
                        $record['search_data'] = $data;
                        $cacheData = json_encode($record);
                        Cache::handler()->hset('task:report:amazon_listing', $id, $cacheData);

                    }
                }
            }
        } catch (\Exception $e) {
            throw new Exception($e->getMessage() . $e->getFile() . $e->getLine());
        }

        return true;
    }

    /**
     * 检查数据
     * @param array $data
     * @return array
     */
    private function checkData(array $data)
    {
        $newData = [];
        foreach ($data as $k => $v) {
            if (is_numeric($v)) {
                if ($v < 0) {
                    $newData[$k] = 0;
                } else {
                    $newData[$k] = $v;
                }
            } else {
                $newData[$k] = $v;
            }
        }
        return $newData;
    }
}