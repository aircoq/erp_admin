<?php
namespace app\common\model\amazon;

use think\Exception;
use think\Model;

class AmazonUpcParam extends Model
{
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }

    public function getUpcParma()
    {
        $data = $this->where(['status' => 0])->order('id', 'asc')->find();
        if (!empty($data)) {
            return ['code' => $data['code'], 'header' => $data['header']];
        }
        return [];
    }

    public function loseParam($param)
    {
        $data = $this->where(['code' => $param['code'], 'header' => $param['header']])->find();
        if (empty($data)) {
            return false;
        }
        if (isset($data['lose_total']) && $data['lose_total'] < 10) {
            $data->save(['lose_total' => $data['lose_total'] + 1]);
        } else {
            $data->save(['status' => 1, 'lose_time' => time()]);
        }
        return true;
    }

    public function addParmas($data)
    {
        $data = $this->where(['code' => $data['code'], 'header' => $data['header']])->find();
        if (!empty($data)) {
            throw new Exception('UPC参数code和header添加失败，已存在数据据中');
        }
        $this->save([
            'code' => $data['code'],
            'header' => $data['header'],
            'create_time' => time(),
        ]);
    }

}