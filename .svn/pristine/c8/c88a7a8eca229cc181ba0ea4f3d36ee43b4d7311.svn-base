<?php


namespace app\goods\service;

use think\Exception;
use app\common\model\GoodsDevelopLog;

class GoodsDevLog
{

    const LIMIT_SIZE = 5;
    protected $LogData = [];
    /**
     * @title 编辑基本信息
     * @return $this
     * @author starzhan <397041849@qq.com>
     */
    public function editBaseInfo()
    {
        $list = [];
        $list['type'] = '基本信息';
        $list['val'] = '';
        $list['data'] = [];
        $list['exec'] = 'edit';
        $this->LogData[] = $list;
        return $this;
    }

    /**
     * @title 编辑了平台分类
     * @author starzhan <397041849@qq.com>
     */
    public function editPlatform()
    {
        $list = [];
        $list['type'] = '平台分类';
        $list['val'] = '';
        $list['data'] = [];
        $list['exec'] = 'edit';
        $this->LogData[] = $list;
        return $this;
    }

    public function editSupplier()
    {
        $list = [];
        $list['type'] = '供应商信息';
        $list['val'] = '';
        $list['data'] = [];
        $list['exec'] = 'edit';
        $this->LogData[] = $list;
        return $this;
    }

    public function editSpecification()
    {
        $list = [];
        $list['type'] = '规格参数';
        $list['val'] = '';
        $list['data'] = [];
        $list['exec'] = 'edit';
        $this->LogData[] = $list;
        return $this;
    }

    public function editAttribute()
    {
        $list = [];
        $list['type'] = '产品属性';
        $list['val'] = '';
        $list['data'] = [];
        $list['exec'] = 'edit';
        $this->LogData[] = $list;
        return $this;
    }

    public function editImgRequirement()
    {
        $list = [];
        $list['type'] = '修图要求';
        $list['val'] = '';
        $list['data'] = [];
        $list['exec'] = 'edit';
        $this->LogData[] = $list;
        return $this;
    }

    public function editDeclare()
    {
        $list = [];
        $list['type'] = '报关信息';
        $list['val'] = '';
        $list['data'] = [];
        $list['exec'] = 'edit';
        $this->LogData[] = $list;
        return $this;
    }

    public function createSku(){
        $list = [];
        $list['type'] = 'sku编码';
        $list['val'] = '';
        $list['data'] = [];
        $list['exec'] = 'create';
        $this->LogData[] = $list;
        return $this;
    }

    public function submit()
    {
        $list = [];
        $list['type'] = '审核';
        $list['val'] = '';
        $list['data'] = [];
        $list['exec'] = 'submit';
        $this->LogData[] = $list;
        return $this;
    }

    public function cancel()
    {
        $list = [];
        $list['type'] = '作废';
        $list['val'] = '';
        $list['data'] = [];
        $list['exec'] = 'submit';
        $this->LogData[] = $list;
        return $this;
    }

    public function disagree($text = '')
    {
        $list = [];
        $list['type'] = '';
        $list['val'] = '未通过原因：' . $text;
        $list['data'] = [];
        $list['exec'] = 'disagree';
        $this->LogData[] = $list;
        return $this;
    }

    public function receive()
    {
        $list = [];
        $list['type'] = '';
        $list['val'] = '';
        $list['data'] = [];
        $list['exec'] = 'receive';
        $this->LogData[] = $list;
        return $this;
    }

    public function start($val)
    {
        $list = [];
        $list['type'] = $val;
        $list['val'] = '';
        $list['data'] = [];
        $list['exec'] = 'start';
        $this->LogData[] = $list;
        return $this;
    }

    public function Assign($val)
    {
        $list = [];
        $list['type'] = '';
        $list['val'] = $val;
        $list['data'] = [];
        $list['exec'] = 'assign';
        $this->LogData[] = $list;
        return $this;
    }

    public function back($remark, $val)
    {
        $list = [];
        $list['type'] = '';
        $list['val'] = '指定退回节点：' . $val . ";未通过原因：" . $remark;
        $list['data'] = [];
        $list['exec'] = 'disagree';
        $this->LogData[] = $list;
        return $this;
    }

    public function publish()
    {
        $list = [];
        $list['type'] = '';
        $list['val'] = '产品';
        $list['data'] = [];
        $list['exec'] = 'publish';
        $this->LogData[] = $list;
        return $this;
    }


    protected function getText()
    {
        $ret = [];
        foreach ($this->LogData as $list) {
            $total = count($list['data']);
            if ($total > self::LIMIT_SIZE) {
                $page_size = self::LIMIT_SIZE;
                $total_page = ceil($total / $page_size);
                for ($page = 1; $page < $total_page; $page++) {
                    $offset = ($page - 1) * $page_size;
                    $tmp1 = $list;
                    $tmp1['data'] = array_slice($list['data'], $offset, $page_size);
                    $ret[] = $tmp1;
                }
            } else {
                $ret[] = $list;
            }
        }
        $tmp = [];
        foreach ($ret as $list) {
            $result = '';
             if ($list['exec'] == 'del') {
                $exec = '删除';
                $result .= $exec . $list['type'] . ":{$list['val']};";
            } else if ($list['exec'] == 'create') {
                $exec = '创建';
                $result .= $exec . $list['type'] . "{$list['val']}";
            } else if ($list['exec'] == 'agree') {
                $exec = '审批通过' . $list['val'];
                $result .= $exec;
            } else if ($list['exec'] == 'disagree') {
                $exec = '审批未通过,' . $list['val'];
                $result .= $exec;
            } else if ($list['exec'] == 'submit') {
                $exec = '提交' . $list['type'];
                $result .= $exec;
            } else if ($list['exec'] == 'start') {
                $exec = '开始';
                $result .= $exec . $list['type'];
            } else if ($list['exec'] == 'receive') {
                $exec = '收到样品';
                $result .= $exec;
            } else if ($list['exec'] == 'edit') {
                $exec = '编辑了';
                $result .= $exec . $list['type'];
            } else if ($list['exec'] == 'create') {
                $exec = '生成';
                $result .= $exec . $list['type'];
            } else if ($list['exec'] == 'assign') {
                $exec = '指派给';
                $result .= $exec . $list['val'];
            } else if ($list['exec'] == 'publish') {
                $exec = '发布';
                $result .= $exec . $list['val'];
            } else {
                throw new Exception('无此操作' . $list['exec']);
            }
            $tmp[] = $result;
        }

        return $tmp;
    }


    public function save($user_id, $goods_id, $process_id, $resource = '')
    {

        $this->baseSave($user_id, $goods_id, $resource, 0, $process_id);
    }

    public function baseSave($user_id, $goods_id = 0, $resource = '', $type = 2, $process_id = 0, $pre_goods_id = 0)
    {

        $this->goods_id = $goods_id;
        $texts = $this->getText();
        if ($texts) {
            foreach ($texts as $text) {
                $data = [];
                $data['goods_id'] = $goods_id;
                $data['pre_goods_id'] = $pre_goods_id;
                $data['operator_id'] = $user_id;
                $data['process_id'] = $process_id;
                $data['remark'] = $resource . $text;
                $data['type'] = $type;
                $data['create_time'] = time();
                $GoodsDevelopLog = new GoodsDevelopLog();
                $GoodsDevelopLog->allowField(true)->isUpdate(false)->save($data);
            }
        }
        $this->LogData = [];
    }
}