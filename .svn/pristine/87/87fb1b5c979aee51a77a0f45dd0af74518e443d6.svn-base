<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\InvoiceRule;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2017/4/18
 * Time: 14:17
 */
class Invoice extends Cache
{
    /** 获取发票规则
     * @return array|mixed
     */
    public function ruleSetInfo()
    {
        if ($this->persistRedis->exists('cache:InvoiceRuleSet')) {
            return json_decode($this->persistRedis->get('cache:InvoiceRuleSet'),true);
        }
        $invoiceRuleModel = new InvoiceRule();
        $result = $invoiceRuleModel->with('item')->where(['status' => 0])->order('sort desc')->select();
        $this->persistRedis->set('cache:InvoiceRuleSet', json_encode($result));
        return $result;
    }

    /** 获取发票规则
     * @return array|mixed
     */
    public function delRuleInfo()
    {
        if ($this->persistRedis->exists('cache:InvoiceRuleSet')) {
            $this->persistRedis->del('cache:InvoiceRuleSet');
        }
    }
}