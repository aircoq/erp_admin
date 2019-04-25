<?php


namespace app\goods\service;

use app\common\model\GoodsNodeProcess as ModelGoodsNodeProcess;

class GoodsNodeProcess
{
    /**
     * @title 更新老流程，的状态，防止数据太多 用户id
     * @author starzhan <397041849@qq.com>
     */
    public function updateStatusOldCurrByUserId($process_id, $user_id)
    {
        $ModelGoodsNodeProcess = new ModelGoodsNodeProcess();
        if(is_array($user_id)){
            return $ModelGoodsNodeProcess->isUpdate(true)
                ->allowField(true)
                ->save(['status' => ModelGoodsNodeProcess::STATUS_WAIT], [
                    'status' => ModelGoodsNodeProcess::STATUS_ED,
                    'current_user_id' =>['in',$user_id],
                    'process_id' => $process_id,
                    'current_work_id' => 0,
                    'current_job_id' => 0
                ]);
        }else{
            return $ModelGoodsNodeProcess->isUpdate(true)
                ->allowField(true)
                ->save(['status' => ModelGoodsNodeProcess::STATUS_WAIT], [
                    'status' => ModelGoodsNodeProcess::STATUS_ED,
                    'current_user_id' => $user_id,
                    'process_id' => $process_id,
                    'current_work_id' => 0,
                    'current_job_id' => 0
                ]);
        }

    }

    /**
     * @title 更新老流程，的状态，防止数据太多
     * @param $process_id
     * @param $user_id
     * @author starzhan <397041849@qq.com>
     */
    public function updateStatusOldCurrByJobId($process_id, $job_id)
    {
        $ModelGoodsNodeProcess = new ModelGoodsNodeProcess();
        return $ModelGoodsNodeProcess->isUpdate(true)
            ->allowField(true)
            ->save(['status' => ModelGoodsNodeProcess::STATUS_WAIT], [
                'status' => ModelGoodsNodeProcess::STATUS_ED,
                'current_job_id' => $job_id,
                'process_id' => $process_id,
                'current_work_id' => 0
            ]);
    }

    /**
     * @title 更新老流程，的状态，防止数据太多
     * @param $process_id
     * @param $user_id
     * @author starzhan <397041849@qq.com>
     */
    public function updateStatusOldCurrByWorkId($process_id, $job_id, $work_id)
    {
        $ModelGoodsNodeProcess = new ModelGoodsNodeProcess();
        return $ModelGoodsNodeProcess->isUpdate(true)
            ->allowField(true)
            ->save(['status' => ModelGoodsNodeProcess::STATUS_WAIT], [
                'status' => ModelGoodsNodeProcess::STATUS_ED,
                'current_job_id' => $job_id,
                'current_work_id' => $work_id,
                'process_id' => $process_id
            ]);
    }


    /**
     * @title 新增流程
     * @param $data
     * @return false|int
     * @author starzhan <397041849@qq.com>
     */
    public function insert($data)
    {
        $infoData = [];
        $infoData['goods_id'] = $data['goods_id'];
        $infoData['process_id'] = $data['process_id'];
        $infoData['current_user_id'] = $data['current_user_id'];
        $infoData['intro'] = $data['intro'];
        $infoData['create_time'] = time();
        $infoData['current_job_id'] = $data['current_job_id'];
        $infoData['current_work_id'] = $data['current_work_id'];
        $ModelGoodsNodeProcess = new ModelGoodsNodeProcess();
        return $ModelGoodsNodeProcess->allowField(true)->isUpdate(false)->save($infoData);
    }

    /**
     * @title 完成当前流程节点
     * @param $process_id
     * @param $current_user_id
     * @param  $current_type
     * @author starzhan <397041849@qq.com>
     */
    public function finishThisProcess($goods_id, $process_id, $user_id, $intro = '')
    {
        $ModelGoodsNodeProcess = new ModelGoodsNodeProcess();
        $data = [
            'status' => ModelGoodsNodeProcess::STATUS_ED,
            'handle_id' => $user_id,
            'handle_time' => time()
        ];
        $where = [
            'goods_id' => $goods_id,
            'process_id' => $process_id
        ];
        if ($intro) {
            $where['intro'] = $intro;
        }
        return $ModelGoodsNodeProcess->allowField(true)
            ->isUpdate(true)
            ->save($data, $where);

    }

    /**
     * @title 统计未完成数
     * @param $goods_id
     * @param $process_id
     * @return int|string
     * @author starzhan <397041849@qq.com>
     */
    public function countForUnFinishByProcessId($goods_id, $process_id)
    {
        $ModelGoodsNodeProcess = new ModelGoodsNodeProcess();
        return $ModelGoodsNodeProcess->where('goods_id', $goods_id)
            ->where('process_id', $process_id)
            ->where('status', ModelGoodsNodeProcess::STATUS_WAIT)
            ->count();
    }

    public function backToTranslator($goods_id, $aLang)
    {
        $ModelGoodsNodeProcess = new ModelGoodsNodeProcess();
        return $ModelGoodsNodeProcess->where('goods_id', $goods_id)
            ->where('process_id', 8195)
            ->where('status', ModelGoodsNodeProcess::STATUS_ED)
            ->where('intro', 'in', $aLang)
            ->update(['status' => ModelGoodsNodeProcess::STATUS_WAIT, 'handle_id' => 0, 'handle_time' => 0]);
    }

    public function searchByCurrentNode($aCurrentNode)
    {
        $ModelGoodsNodeProcess = new ModelGoodsNodeProcess();
        $result = [];
        $ret = $ModelGoodsNodeProcess->field('goods_id')->where('status', ModelGoodsNodeProcess::STATUS_WAIT)
            ->where('current_node', 'in', $aCurrentNode)
            ->select();
        foreach ($ret as $v) {
            $result[] = $v['goods_id'];
        }
        return $result;
    }


}