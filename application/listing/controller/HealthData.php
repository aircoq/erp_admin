<?php

/**
 * Description of HealthData
 * @datetime 2017-7-10  19:50:25
 * @author joy
 */

namespace app\listing\controller;
use app\common\controller\Base;
use app\listing\service\HealthDataHelper;
use app\common\exception\JsonErrorException;
use app\common\service\Common;
/**
 * @module listing系统
 * @title wish健康数据
 * Class HealthData
 * @package app\listing\controller
 */

class HealthData extends Base{
    private $helper;
    
    protected function init() 
    { 
       $this->helper = new HealthDataHelper;
    }
    /**
     * @title 获取wish店铺数据监控
     * @url /get-monitor-data
     * @access public
     * @author joy
     * @param int $account_id 
     * @apiRelate app\publish\controller\Wish::getSellers
     * @method get
     * @return string
     */
    
    public function getMonitorData()
    {
       try{
           $account_id = $this->request->instance()->param('account_id',0);
            
           $response = $this->helper->getMonitorData($account_id);
           return json(['data'=>$response]);
       } catch (JsonErrorException $exp){
           throw  new JsonErrorException($exp->getMessage());
       }   
        
    }
     /**
     * @title wish店铺数据监控
     * @url /wish-shop-monitor
     * @access public
     * @author joy
     * @method post
     * @apiRelate app\publish\controller\Wish::getSellers
     * @param \think\Request $request
     * @return string
     */
    
    public function monitor()
    {
        $param = $this->request->instance()->param();
        
        $response = $this->helper->monitor($param);
        
        if($response['result'])
        {
            return json(['message'=>$response['message']]);
        }else{
            return json(['message'=>$response['message']],500);
        }
    }
    /**
     * @title wish店铺短信授权验证
     * @url /wish-shop-auth
     * @author joy
     * @method post
     * @access public
     * @param \think\Request $request
     * @apiRelate app\publish\controller\Wish::getSellers
     * @return string
     */
    
    public function auth()
    {
        $param = $this->request->instance()->param();
        
        $response = $this->helper->authorize($param);
        
        if($response['result'])
        {
            return json(['message'=>$response['message']]);
        }else{
            return json(['message'=>$response['message']],500);
        }
        
    }
    
    /**
     * @title 获取wish手机验证码
     * @url /get-wish-mobile-code
     * @method post
     * @access public
     * @param \think\Request $request
     * @return string  
     */
    public  function getCode()
    {
        $username = $this->request->instance()->param('username');
        
        $response = $this->helper->getCode($username);
        
        if($response['result'])
        {
            return json(['message'=>$response['message']]);
        }else{
            return json(['message'=>$response['message']],500);
        }
    }
    /**
     * @title wish健康数据列表
     * @method get
     * @access public
     * @url /wish-health-data-list
     * @apiRelate app\publish\controller\Wish::getSellers
     * @param \think\Request $request
     * @return array
     */
    public  function lists()
    {
        try{
            $param = $this->request->param();
        
            $page = $this->request->param('page',1);

            $pageSize = $this->request->param('pageSize',30);
            
            $uid=Common::getUserInfo($this->request) ? Common::getUserInfo($this->request)['user_id'] : 1;
            
            $param['uid']=$uid;
            
            $response = $this->helper->healthDataList($param, $page, $pageSize);
            
            return json($response);
            
        }catch(JsonErrorException $exp){
            throw  new JsonErrorException($exp->getMessage());
        } 
    }
    
    /**
     * @title wish历史健康数据列表
     * @method get
     * @url /wish-history-health-data
     * @param \think\Request $request
     * @return array
     */
    public  function history()
    {
        try{
            $param = $this->request->param();
        
            $page = $this->request->param('page',1);

            $pageSize = $this->request->param('pageSize',30);
            
            $response = $this->helper->healthHistoryDataList($param, $page, $pageSize);
            
            return json($response);
            
        }catch(JsonErrorException $exp){
            throw  new JsonErrorException($exp->getMessage());
        } 
    }
    
   
}
