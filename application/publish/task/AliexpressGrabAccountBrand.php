<?php 
namespace app\publish\task;
/**
 *速卖通属性
 * Description of AliexpressGrabAccountBrand
 * @datetime 2018-5-2
 * @author joy
 */
 
use app\common\cache\Cache;
use app\common\exception\TaskException;
use app\common\model\aliexpress\AliexpressAccountCategoryPower;
use app\common\service\UniqueQueuer;
use app\index\service\AbsTasker;
use app\publish\queue\AliexpressBrandDistributeQuque;

class AliexpressGrabAccountBrand extends AbsTasker
{

    public function getName()
    {
        return "速卖通商户授权品牌获取";
    }
    
    public function getDesc()
    {
        return "速卖通商户授权品牌获取";
    }
    
    public function getCreator()
    {
        return "joy";
    }
    
    public function getParamRule()
    {
        return [];
    }


    public function execute()
    {
         set_time_limit(0);
         try{
             $accounts = Cache::store('AliexpressAccount')->getAccounts();
             if ($accounts) {
                 foreach ($accounts as $account) {
                     if (isset($account['is_invalid']) && $account['is_invalid'] && isset($account['is_authorization']) && $account['is_authorization'] ) {
                          $authCategory = AliexpressAccountCategoryPower::where('account_id',$account['id'])->field('category_id')->group('category_id')->limit(1)->find();
                          $auth_category_id = $authCategory?$authCategory['category_id']:0;
                          if($auth_category_id){
                              $queue = $account['id'].'|'.$auth_category_id;
                              (new UniqueQueuer(AliexpressBrandDistributeQuque::class))->push($queue);
                          }
                     }
                 }
             }
         }catch (\Throwable $exp){
             throw new TaskException($exp->getMessage());
         }
    }
}