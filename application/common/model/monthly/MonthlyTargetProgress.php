<?php
namespace app\common\model\monthly;

use app\common\service\Common;
use think\Model;
use think\Db;

class MonthlyTargetProgress extends Model
{

    /**
     * 初始化
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }

    public function isHas($where)
    {
       return $this->where($where)->find();
    }

    public function add($data,$time = 0)
    {
        if(!$time){
            $time = strtotime(date('Y-m-d'));
        }
        $where = [
            'user_id' => $data['user_id'],
            'time' => $time,
            'mode' => $data['mode'],
            'type' => $data['type'],
        ];
        $old = $this->isHas($where);
        if($old){
            $save = [
                'progress' => $data['progress'],
                'ranking' => $data['ranking'],
            ];
            return $this->save($save,['id' => $old['id']]);
        }
        $where['progress'] = $data['progress'];
        $where['ranking'] = $data['ranking'];
        return $this->allowField(true)->isUpdate(false)->save($where);
    }

    public function getProgress($userId,$type = 0, $mode = 0, $time = 0)
    {
        if(!$time){
            $time = strtotime(date('Y-m-d'));
        }
        $times = $time - 86400;
        $where = [
            'user_id' => $userId,
            'time' => ['in',[$times,$time] ],
            'type' => $type,
            'mode' => $mode,
        ];
       return $this->where($where)->order('time desc')->select();
    }

}
