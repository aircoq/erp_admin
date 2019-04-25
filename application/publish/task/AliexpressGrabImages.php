<?php 
namespace app\publish\task;
/**
 * Created by ZendStudio
 * User: HotZr
 * Date: 17-3-31
 * Time: 11:00
 * Doc: 速卖通商品分组抓取任务
 */
 
use app\common\cache\Cache;
use app\index\service\AbsTasker;
use app\common\model\aliexpress\AliexpressAccount;
use service\aliexpress\AliexpressApi;
use think\Db;
use app\common\model\aliexpress\AliexpressImagesBank;
use app\publish\service\FieldAdjustHelper;
class AliexpressGrabImages extends AbsTasker
{
    
    public function getName()
    {
        return "速卖通抓取图片银行数据";
    }
    
    public function getDesc()
    {
         return "速卖通抓取图片银行数据";
    }
    
    public function getCreator()
    {
        return "曾锐";
    }
    
    public function getParamRule()
    {
        return [];
    }
    
    public function execute()
    {
        set_time_limit(0);
        $result = Cache::store('AliexpressAccount')->getAccounts();
        $AliexpressImagesBank = new AliexpressImagesBank();
        foreach($result as $user)
        {
            if(!$user['is_invalid'] || !$user['is_authorization']){
                continue;
            }
            $config = [
                'id'            => $user['id'],
                'client_id'            => $user['client_id'],
                'client_secret'     => $user['client_secret'],
                'accessToken'    => $user['access_token'],
                'refreshtoken'      =>  $user['refresh_token'],
            ];
            $Images = AliexpressApi::instance($config)->loader('Images');
            //$Images->setConfig($arrTemp);
            $intPage = 1;
            while(true)
            {
                $arrData = ['currentPage'=>$intPage,'pageSize'=>50];
                $result = $Images->listImagePagination($arrData);
                if(isset($result['images']))
                {
                   foreach ($result['images'] as $value)
                   {
                       $value = FieldAdjustHelper::adjust($value, '','HTU');
                       $value['account_id'] = $user['id'];
                       $value['md5_sign'] = @md5_file($value['url']);
                       $AliexpressImagesBank->db()->insert($value,true);
                   }
                }
                else 
                {
                    break;
                }
                if($intPage >= $result['totalPage'])break;
                $intPage++;
            }
        }
    }
}