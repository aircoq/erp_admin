<?php
/**
 * Created by PhpStorm.
 * User: panguofu
 * Date: 2018/11/22
 * Time: 下午2:18
 */

namespace app\common\model\report;

use app\index\service\DepartmentUserMapService;
use think\Model;
use app\common\cache\Cache;


class ReportStatisticPublishByAccount extends Model
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
            'account_id' => $data['account_id'],
            'shelf_id' => $data['shelf_id'],
        ];
        $old = $this->isHas($map);
        if ($old) {
            $save = [
                'times' => $data['times'] + $old['times'],
                'quantity' => $data['quantity'] + $old['quantity'],
            ];
            //return $this->save($save, $map);
            $rlt= $this->where($map)->update($save);
            return $rlt;

        } else {

            $departmentIds = (new DepartmentUserMapService())->getDepartmentByUserId($data['shelf_id']);

            $data['department_id'] = count($departmentIds) > 0 ? $departmentIds[rand(0, count($departmentIds) - 1)] : 0;
            return $this->allowField(true)->isUpdate(false)->save($data);
        }
    }

    public function isHas($map)
    {
        return $this->where($map)->find();
    }


}