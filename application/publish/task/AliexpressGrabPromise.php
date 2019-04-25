<?php 
namespace app\publish\task;
/**
 * Created by ZendStudio
 * User: HotZr
 * Date: 17-3-30
 * Time: 下午2:16
 * Doc: 速卖通商户服务模板抓取任务
 */
 
use app\common\cache\Cache;
use app\common\model\aliexpress\AliexpressPromiseTemplate;
use app\index\service\AbsTasker;
use app\common\model\aliexpress\AliexpressAccount;
use service\aliexpress\AliexpressApi;
use think\Db;
use think\Exception;
use app\common\exception\TaskException;
class AliexpressGrabPromise extends AbsTasker
{
    public function getName()
    {
        return "速卖通抓取服务模板";
    }
    
    public function getDesc()
    {
        return "速卖通抓取服务模板";
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
        try{
            set_time_limit(0);
            $result = Cache::store('AliexpressAccount')->getAccounts();
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
                $PostProduct = AliexpressApi::instance($config)->loader('PostProduct');
                /*
                //时时获取access_token，防止抓取失败，这段代码待服务器的access_token抓取任务正常后请删除可加快速度！
                $arrAccessToken = $PostProduct->getTokenByRefreshToken($arrTemp);
                $arrTemp['access_token'] = $arrAccessToken['access_token'];
                //时时获取access_token，防止抓取失败，这段代码待服务器的access_token抓取任务正常后请删除可加快速度！
                */
                //$PostProduct->setConfig($arrTemp);
                $arr = $PostProduct->queryPromiseTemplateById();
                if(is_array($arr) && isset($arr['templateList']))
                {
                    AliexpressPromiseTemplate::where(['account_id'=>$user['id']])->delete();
                    $arrData = [];
                    foreach ($arr['templateList'] as $v)
                    {
//                    $strSql = "('".$v['id']."','".$user['id']."','".$v['name']."')";
//                    $strSql = "REPLACE INTO aliexpress_promise_template (`template_id`,`account_id`,`template_name`) VALUES {$strSql};";
//                    Db::execute($strSql);
                        $data=[
                            'account_id'=>$user['id'],
                            'template_id'=>$v['id'],
                            'template_name'=>$v['name'],
                        ];
                        $where=[
                            'account_id'=>['=',$user['id']],
                            'template_id'=>['=',$v['id']],
                            'template_name'=>['=',$v['name']]
                        ];
                        if(AliexpressPromiseTemplate::where($where)->find())
                        {
                            AliexpressPromiseTemplate::where($where)->update($data);
                        }else{
                            AliexpressPromiseTemplate::insert($data);
                        }
                    }
                }
            }
        }catch (TaskException $exp){
            throw new TaskException($exp->getMessage());
        }

        
    }
    
    
}