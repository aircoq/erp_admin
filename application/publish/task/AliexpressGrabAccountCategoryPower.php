<?php 
namespace app\publish\task;
/**
 * Created by ZendStudio
 * User: HotZr
 * Date: 17-5-2
 * Time: 中午10：34
 * Doc: 速卖通商户授权分类分析
 */
 
use app\index\service\AbsTasker;
use app\common\model\aliexpress\AliexpressProduct;
use app\common\model\aliexpress\AliexpressAccountCategoryPower;
use app\common\model\aliexpress\AliexpressCategory;
class AliexpressGrabAccountCategoryPower extends AbsTasker
{
    public function getName()
    {
        return "速卖通商户授权分类分析";
    }
    
    public function getDesc()
    {
        return "速卖通商户授权分类分析";
    }
    
    public function getCreator()
    {
        return "曾锐";
    }
    
    public function getParamRule()
    {
        return [];
    }
    
    /**
     * @info 速卖通商户授权分类分析
     */
    public function execute()
    {
        set_time_limit(0);
        $intTime = time()-24*3600;
        $intCount = 5000;//每次分析条数
        $AliexpressProduct = new AliexpressProduct();
        $Result = $AliexpressProduct
        ->field('account_id,category_id')
        ->where('product_id','NEQ',0)
        ->where('pull_time','<',$intTime)
        ->where('category_id','NEQ',0)
        ->limit($intCount)
        ->select();
        $AliexpressCategory = new AliexpressCategory();
        $AliexpressAccountCategoryPower = new AliexpressAccountCategoryPower();
        foreach ($Result as $Product)
        {
            $objParentCid = $AliexpressCategory->getTheMostPareantCategory($Product->category_id);
            $objCategoryPowerResult = $AliexpressAccountCategoryPower
            ->where('account_id',$Product->account_id)
            ->where('category_id',$objParentCid->category_id)
            ->find();
            if(is_null($objCategoryPowerResult))
            {
                $AliexpressAccountCategoryPower->data(['account_id'=>$Product->account_id,'category_id'=>$objParentCid->category_id],true)
                ->isUpdate(false)
                ->save();
            }
        }
    }

}