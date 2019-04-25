<?php
namespace app\common\model\aliexpress;
use think\Model;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2016/10/28
 * Time: 9:13
 */
class AliexpressShippingMethod extends Model
{
    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }
    
    /**
     * 查询记录是否存在
     * @param number $accountId
     * @param string $company
     */
    public function isHas($accountId = 0, $company = '')
    {
        return self::get(['account_id' => $accountId, 'company' => $company]);
    }
    
    /**
     * 添加或更新运输方式
     */
    public function saveShippingMethod($data = [], $id = 0)
    {
        if ($id) {
            return $this->update($data, ['id' => $id]);
        }
        return $this->insert($data);
        
    }
    
}