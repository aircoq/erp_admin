<?php 
namespace app\publish\task;
 
use app\common\cache\Cache;
use app\common\model\aliexpress\AliexpressProductGroup;
use app\index\service\AbsTasker;
use app\common\model\aliexpress\AliexpressAccount;
use service\aliexpress\AliexpressApi;
use think\Db;
use app\common\exception\TaskException;
class AliexpressGrabProductGroup extends AbsTasker
{
    protected $strSqlData=[];
    
    public function getName()
    {
        return "速卖通抓取品分组";
    }
    
    public function getDesc()
    {
        return "速卖通抓取品分组";
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
            $accounts = Cache::store('AliexpressAccount')->getAccounts();
            if($accounts)
            {
                foreach($accounts as $user)
                {
                    if($user['is_invalid'] &&  $user['is_authorization'] )
                    {
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
                        $arr=[];

                        $arr = $PostProduct->getProductGroupList();

                        if(is_array($arr) && isset($arr['target']))
                        {
                            AliexpressProductGroup::where(['account_id'=>$user['id']])->delete();
                            $groups = $arr['target'];
                            if($groups)
                            {
                                foreach ($groups as $group)
                                {
                                    if(isset($group['childGroup']) && $group['childGroup'])
                                    {
                                        $childGroups = $group['childGroup'];
                                        foreach ($childGroups as $child)
                                        {
                                            $data=[
                                                'account_id'=>$user['id'],
                                                'group_id'=>$child['groupId'],
                                                'group_pid'=>$group['groupId'],
                                                'group_name'=>$child['groupName'],
                                            ];
                                            $where=[
                                                'account_id'=>['=',$user['id']],
                                                'group_id'=>['=',$child['groupId']]
                                            ];
                                            if(AliexpressProductGroup::where($where)->find())
                                            {
                                                AliexpressProductGroup::where($where)->update($data);
                                            }else{
                                                AliexpressProductGroup::insert($data);
                                            }
                                        }
                                    }

                                    $map=[
                                        'account_id'=>['=',$user['id']],
                                        'group_id'=>['=',$group['groupId']]
                                    ];
                                    $data=[];
                                    $data=[
                                        'account_id'=>$user['id'],
                                        'group_id'=>$group['groupId'],
                                        'group_pid'=>0,
                                        'group_name'=>$group['groupName'],
                                    ];


                                    if(AliexpressProductGroup::where($map)->find())
                                    {
                                        AliexpressProductGroup::where($map)->update($data);
                                    }else{
                                        (new AliexpressProductGroup())
                                            ->allowField(true)->isUpdate(false)
                                            ->save($data);
                                    }
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

    public function old_func($user,$arr)
    {
        $this->strSqlData = [];
        $arrData = self::formartData($arr['target']);

        $strSql ='';
        $arr_group_id = array_column($arrData,'groupId');

        foreach ($arrData as $v)
        {

            $strSql .= "('".$user['id']."','".$v['groupId']."','".$v['pid']."','".addcslashes($v['groupName'],"'")."'),";
        }
        if(!empty($strSql))
        {
            $strSql = rtrim($strSql,',');
            $strSql = "REPLACE INTO `aliexpress_product_group` (`account_id`,`group_id`,`group_pid`,`group_name`) VALUES {$strSql};";
            Db::execute($strSql);
        }
        $str_group_id = implode(',',$arr_group_id);
        $delSql = "DELETE FROM `aliexpress_product_group` WHERE `account_id`={$user['id']} and `group_id` NOT IN ({$str_group_id})";
        Db::execute($delSql);
        $arrData=[];
    }
    
    public function formartData($arrData,$pid=0)
    {
        foreach($arrData as $key=>&$value)
        {
           $value['pid'] = isset($value['pid'])?$value['pid']:$pid;
            if(isset($value['childGroup']))
            {
                $this->formartData($value['childGroup'],$value['groupId']);
                unset($value['childGroup']);
            }
            $this->strSqlData[] = $value;
        }
        return $this->strSqlData;
    }
}