<?php

namespace app\common\model\monthly;

use think\Model;
use think\Db;

class MonthlyTargetAmount extends Model
{
    /**
     * 初始化
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }

    public function addTarget($data)
    {
        $map = $this->isHas($data);
        if ($map) {
            $save['update_time'] = $data['update_time'];
            $save['target_amount'] = $map['target_amount'] + $data['target_amount'];
            $total_details = explode(',', $map['total_details']);
            $new_total_details = explode(',', $data['total_details']);
            $save['total_details'] = ($total_details[0] + $new_total_details[0]) . ',' . ($total_details[1] + $new_total_details[1]);
            return $this->save($save, ['id' => $map['id']]);
        } else {
            return $this->insert($data);
        }
    }

    public function isHas($data)
    {
        $where = [
            'year' => $data['year'],
            'monthly' => $data['monthly'],
            'type' => $data['type'],
            'relation_id' => $data['relation_id'],
            'mode' => $data['mode'],
        ];
        return $this->where($where)->find();
    }

}
