<?php
namespace app\common\model\amazon;

use think\Model;
use think\Loader;
use think\Db;

class AmazonProductExportDownload extends Model
{
    protected $type = [
    ];
    /**
     * 初始化
     * @return [type] [description]
     */
    protected function initialize()
    {   
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }
    
    /**
     * 新增
     * @param array $data [description]
     */
    public function add(array $data)
    {
        if (isset($data['id'])) {
            //检查是否已存在
            if ($this->check(['id' => $data['id']])) {
                return $this->edit($data, ['id' => $data['id']]);
            }
        }
        return $this->insert($data);
    }

    /**
     * 批量新增
     * @param array $data [description]
     */
    public function addAll(array $data)
    {
        foreach ($data as $key => $value) {
            $this->add($value);
        }
    }

    /**
     * 修改
     * @param  array $data [description]
     * @return [type]       [description]
     */
    public function edit(array $data, array $where)
    {
        return $this->allowField(true)->save($data, $where);
    }

    /**
     * 批量修改
     * @param  array $data [description]
     * @return [type]       [description]
     */
    public function editAll(array $data)
    {
        return $this->save($data);
    }
    
    /**
     * 检查是否存在
     * @return [type] [description]
     */
    protected function check(array $data)
    {   
        $result = $this->get($data);
        if (!empty($result)) {
            return true;
        }
        return false;
    }
}
