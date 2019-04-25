<?php
namespace app\common\model\ebay;

use think\Model;
use think\Db;
use think\Exception;

class EbayCategoryKeyword extends Model
{
    /**
     * 初始化
     * @return [type] [description]
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }

    public function saveData($data)
    {
        if(!is_array($data)) {
            return false;
        }
        try {
            foreach($data as $val) {
                if(!empty($val['id'])) {
                    $this->update(['score' => $val['score']], ['id' => $val['id']]);
                } else {
                    $this->allowField(true)->insert($val);
                }
            }
            return true;
        } catch(Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}