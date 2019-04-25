<?php
namespace app\common\model\ebay;

use think\Model;
use think\Loader;
use think\Db;

class EbayCustomCategory extends Model
{	
	/**
     * 同步
     * @param array $data [description]
    */
    public function syncCustomCategory(array $data)
    {
        if (isset($data['category_id'])) {
            //检查产品是否已存在
            if ($this->check(['category_id' => $data['category_id']])) {
                return $this->edit($data, ['category_id' => $data['category_id']]);
            }else{
            	return $this->insert($data);
            }
        }
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

    /**
    * 修改
    * @param  array $data [description]
    * @return [type]       [description]
    */
    public function edit(array $data, array $where)
    {
        return $this->allowField(true)->save($data, $where);
    }

}