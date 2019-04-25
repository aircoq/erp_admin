<?php

/**
 * Description of AliexpressProductTemplate
 * @datetime 2017-6-13  11:24:57
 * @author joy
 */

namespace app\publish\task;
use app\index\service\AbsTasker;
use app\common\cache\Cache;
use service\aliexpress\AliexpressApi;
use app\common\model\aliexpress\AliexpressProductTemplate as ProductTemplate;
class AliexpressProductTemplate extends AbsTasker{
   /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return "速卖通信息模板";
    }
    
    /**
     * 定义任务描述
     * @return string
     */
    public function getDesc()
    {
        return "速卖通信息模板";
    }
    /**
     * 定义任务作者
     * @return string
     */
    public function getCreator()
    {
        return "joy";
    }
     /**
     * 定义任务参数规则
     * @return array
     */
    public function getParamRule()
    {
        return [];
    }
    /**
     * 任务执行内容
     * @return void
     */
    
    public function execute()
    {
        set_time_limit(0);
        //$total = Cache::store('AliexpressAccount')->getAccounts();
        $page=1;
        $pageSize=30;
        $model= new ProductTemplate;
        $accountModel = new \app\common\model\aliexpress\AliexpressAccount;
        do{
            //$accounts = Cache::page($total, $page, $pageSize);
            $accounts = $accountModel->page($page, $pageSize)->select();
            if(empty($accounts))
            {
                break;
            }else{
                $page =$page+1;
                $this->findAeProductDetailModuleListByQurey($accounts,$model);
            }
        } while(count($accounts) == $pageSize);
    }
    
    private function findAeProductDetailModuleListByQurey($accounts,$model)
    {
        if(is_array($accounts))
        {
            foreach ($accounts as $key => $account) 
            {
               if($account['access_token']) 
               {
                   $account['accessToken'] = $account['access_token'];
                   $account['refreshtoken'] = $account['refresh_token'];
                   $this->findAeProductModuleById($account, 1,$model);
               }
            }
        }
    }
    
     public  function findAeProductModuleById($account,$page=1,$model)
    {
        $service = AliexpressApi::instance($account)->loader('ProductDetailModule');
        $queryResponse = $service->findAeProductDetailModuleListByQurey($page);
        
        if(isset($queryResponse['success']) && $queryResponse['success'])
        {
           $currentPage= $queryResponse['currentPage']  ;
           $totalPage = $queryResponse['totalPage']  ;
           $aeopDetailModuleList = $queryResponse['aeopDetailModuleList']  ;
           foreach($aeopDetailModuleList as $list)
           {
             $moduleInfo = $service->findAeProductModuleById($list['id']);
             if(isset($moduleInfo['success']) && $moduleInfo['success'])
             {
                 unset($moduleInfo['success']);
                 $moduleInfo['accountId'] = $account['id'];
                 $data = $this->manage_data($moduleInfo);
                 if($model->check(['id'=>$moduleInfo['id']]))
                 {
                     $model->update($data,['id'=>$moduleInfo['id']]);
                 }else{
                     $model->insert($data);
                 }
             }
           } 
           
           if($currentPage!=$totalPage)
           {
               $this->findAeProductModuleById($account, $currentPage+1);
           }
        }else{
            if(isset($queryResponse['error_code']))
            {
                throw new TaskException("帐号:[".$account['code']."]".$queryResponse['error_message']);
            }
        }
    }
    
    private function manage_data($data)
    {
        $return=[];
        foreach($data as $k=>$v)
        {
            $name = snake($k);
            $return[$name]=$v;
        }
        return $return;
    }
}
