<?php
/**
 * Created by PhpStorm.
 * User: panguofu
 * Date: 2018/11/22
 * Time: 下午2:17
 */

namespace app\common\model\report;

use think\Model;
use app\common\cache\Cache;


class ReportStatisticPublishByChannel extends Model
{
    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }


    public function add($data)
    {

        $map = [
            'dateline' => $data['dateline'],
            'channel_id' => $data['channel_id'],
            'goods_id' => $data['goods_id'],
        ];
        $old = $this->isHas($map);
        if ($old) {
            $save = [
                'times' => $data['times'] + $old['times'],
                'update_time' => time()
            ];

            $rlt= $this->where($map)->update($save);
            //echo $this->getLastSql();
            return $rlt;
        } else {
            $data['update_time'] = time();
            return $this->allowField(true)->isUpdate(false)->save($data);
        }
    }

    public function isHas($map)
    {
        return $this->where($map)->find();
    }


}