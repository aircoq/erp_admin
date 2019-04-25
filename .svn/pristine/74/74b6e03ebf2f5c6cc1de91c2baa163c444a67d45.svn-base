<?php 
namespace app\publish\task;

use app\common\cache\Cache;
use app\common\model\aliexpress\AliexpressFreightTemplate;
use app\index\service\AbsTasker;
use app\common\model\aliexpress\AliexpressAccount;
use service\aliexpress\AliexpressApi;
use think\Db;
use think\Exception;
use app\common\exception\TaskException;
class AliexpressGrabTransport extends AbsTasker
{
    public function getName()
    {
        return "速卖通抓取运费模板";
    }
    
    public function getDesc()
    {
        return "速卖通抓取运费模板";
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
        try{
            set_time_limit(0);
            $accounts =Cache::store('AliexpressAccount')->getAccounts();
            if($accounts)
            {
                foreach($accounts as $user)
                {
                    if($user['is_invalid'] && $user['is_authorization'])
                    {
                        $config = [
                            'id'            => $user['id'],
                            'client_id'            => $user['client_id'],
                            'client_secret'     => $user['client_secret'],
                            'accessToken'    => $user['access_token'],
                            'refreshtoken'      =>  $user['refresh_token'],
                        ];

                        $Freight = AliexpressApi::instance($config)->loader('Freight');
                        $arr=[];

                        $arr = $Freight->listFreightTemplate();

                        if(isset($arr['success']) && $arr['success'] && isset($arr['aeopFreightTemplateDTOList']) && $arr['aeopFreightTemplateDTOList'])
                        {

                            AliexpressFreightTemplate::where(['account_id'=>$user['id']])->delete();

                            foreach ($arr['aeopFreightTemplateDTOList'] as $v)
                            {

                                $where=[
                                    'template_id'=>['=',$v['templateId']],
                                    'account_id'=>['=',$user['id']],
                                ];
                                $data=[
                                    'template_id'=>$v['templateId'],
                                    'account_id'=>$user['id'],
                                    'template_name'=>$v['templateName'],
                                    'is_default'=>(int)$v['default']
                                ];

                                if($has=AliexpressFreightTemplate::where($where)->find())
                                {
                                    AliexpressFreightTemplate::where($where)->update($data);
                                }else{
                                    (new AliexpressFreightTemplate)->save($data);
                                }
                            }
                        }
                    }
                }
            }

        }catch (TaskException $exp){
            throw new TaskException("File:{$exp->getFile()};Line:{$exp->getLine()};{$exp->getMessage()}");
        }

    }
}