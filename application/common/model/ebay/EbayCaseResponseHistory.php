<?php
namespace app\common\model\ebay;

use think\Model;
use think\Db;
use think\Exception;

class EbayCaseResponseHistory extends Model
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
    

    /**
     * 批量新增
     * @param array $data [description]
     */
    public function addAll(array $data)
    {
            Db::startTrans();
            try { 
                
                foreach ($data as $key => $value) {
                    $res = $this->add($value);
                                              
                }                 
                Db::commit(); 
                return true;
            } catch (Exception $ex) {
                Db::rollback();
                print_r($ex->getMessage());exit;
            }
  
        return false;
    }
    
    /**
     * 新增
     * @param array $data [description]
     */
    public function add(array $data)
    {
        if (!empty($data['case_id'])) {      
              $res = $this->insert($data);    
        }
        return $res;
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
