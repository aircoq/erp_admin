<?php
namespace app\common\model\ebay;

use think\Model;
use think\Loader;
use think\Db;

class EbayMessageMedia extends Model
{

    /**
     * 初始化
     * 
     * @return [type] [description]
     */
    protected function initialize()
    {
        // 需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }

    /**
     * 新增消息
     * 
     * @param array $data
     *            [description]
     */
    public function add($data)
    {
        if (empty($data)) {
            return false;
        }
        if (! empty($data)) {
            
            foreach ($data as $key => $message) {
                
                if (isset($message['message_id'])) {
                    try {
                        $rs = $this->insert($message);
                    } catch (\Exception $e) {
                        \think\Log::write('ebay站内信media信息添加异常 ' . $e->getMessage());
                    }
                }
            }
        }
        return true;
    }

    /**
     * 批量新增
     * 
     * @param array $data
     *            [description]
     */
    public function addAll(array $data)
    {
        foreach ($data as $key => $value) {
            $this->add($value);
        }
    }

    /**
     * 修改数据
     * 
     * @param array $data
     *            [description]
     * @return [type] [description]
     */
    public function edit(array $data, array $where)
    {
        return $this->allowField(true)->save($data, $where);
    }

    /**
     * 批量修改
     * 
     * @param array $data
     *            [description]
     * @return [type] [description]
     */
    public function editAll(array $data)
    {
        return $this->save($data);
    }

    /**
     * 检测数据是否存在
     * 
     * @param int $id            
     * @return bool
     */
    public function isHas($id = 0)
    {
        $result = $this->where([
            'id' => $id
        ])->find();
        if (empty($result)) { // 不存在
            return false;
        }
        return true;
    }
}