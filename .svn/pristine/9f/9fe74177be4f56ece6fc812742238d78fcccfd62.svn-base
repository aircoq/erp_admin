<?php

/**
 * Description of HealthDataHelper
 * @datetime 2017-7-10  19:52:38
 * @author joy
 */

namespace app\listing\service;
use app\common\model\wish\WishHealthData;
use app\common\model\wish\WishHistoryHealthData;
use app\listing\validate\HealthDataValidate;
use app\common\exception\JsonErrorException;
use snoopy\Snoopy;
use QL\QueryList;
use app\common\model\wish\WishMonitor;
use Facebook\WebDriver\Interactions\Internal\WebDriverClickAndHoldAction;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\Cookie;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverPoint;
use Facebook\WebDriver\JavaScriptExecutor;
class HealthDataHelper {
    protected $healthDataModel;
    protected $validate;
    protected $snoopy;
    protected $historyModel;
    protected $agents;
    protected $monitorModel;
    public function __construct() 
    {
        $this->healthDataModel = new WishHealthData;
        $this->validate = new HealthDataValidate;
        $this->snoopy = new Snoopy;
        $this->historyModel = new WishHistoryHealthData;
        $this->monitorModel = new WishMonitor;
        $this->agents=[
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/59.0.3071.115 Safari/537.36',
                'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36',
                'Mozilla/5.0 (Windows NT 10.0; WOW64; rv:54.0) Gecko/20100101 Firefox/54.0',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.6; rv,2.0.1) Gecko/20100101 Firefox/4.0.1',
                'Mozilla/5.0 (Windows NT 6.1; rv,2.0.1) Gecko/20100101 Firefox/4.0.1',
                'Opera/9.80 (Macintosh; Intel Mac OS X 10.6.8; U; en) Presto/2.8.131 Version/11.11',
                'Opera/9.80 (Windows NT 6.1; U; en) Presto/2.8.131 Version/11.11',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_0) AppleWebKit/535.11 (KHTML, like Gecko) Chrome/17.0.963.56 Safari/535.11',
                'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; Maxthon 2.0)',
                'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; TencentTraveler 4.0)',
                'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; Trident/4.0; SE 2.X MetaSr 1.0; SE 2.X MetaSr 1.0; .NET CLR 2.0.50727; SE 2.X MetaSr 1.0)',
                'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/59.0.3071.115 Safari/537.36',
                'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/53.0.2785.104 Safari/537.36 Core/1.53.3103.400 QQBrowser/9.6.11372.400'
        ];
    }

    /**
     * ebay selenium模拟登陆
     * @param string $username
     * @param string $password
     */
    public function ebayLogin($username,$password)
    {

        require_once(ROOT_PATH.'vendor/autoload.php');
        // start Firefox with 5 second timeout
        $host = 'http://localhost:4444/wd/hub'; // this is the default

        $capabilities = DesiredCapabilities::chrome();

        $driver = RemoteWebDriver::create($host, $capabilities, 5000);

        $driver->manage()->window()->maximize();

        $cookie = new Cookie('ebay', 'ebay_cookie');

        $driver->get('https://signin.ebay.com/ws/eBayISAPI.dll');

        $signin = $driver->findElement(WebDriverBy::id('pri_signin'));

        $divs = $signin->findElements(WebDriverBy::tagName('div'));

        foreach ($divs as $k=>$div)
        {
            if($k==3)
            {
                $div->findElement(WebDriverBy::tagName('input'))->sendKeys('joy_qhs@163.com');
            }

            if($k==4)
            {
                $div->findElement(WebDriverBy::tagName('input'))->sendKeys('?!*qiaohengshan8');
            }
        }

        $driver->findElement(WebDriverBy::id('sgnBt'))->click();

        $driver->manage()->addCookie();

        $cookies = $driver->manage()->getCookies();

        dump($cookies);

    }
    
    public function getMonitorData($account_id)
    {
        $data = $this->monitorModel->where(['account_id'=>$account_id])->find();
        if(empty($data))
        {
            $data = $this->monitorModel->where(['account_id'=>0])->find();
        }
        return is_object($data)?$data->toArray():$data;
    }
    /**
     * 设置监控值
     * @param type $param
     * @return string
     * @throws JsonErrorException
     */
    public  function monitor($param)
    {
//        if($error= $this->validate->checkData($param,'monitor'))
//        {
//            return ['result'=>FALSE,'message'=>$error];
//        }
        try{
            
            if($this->monitorModel->where(['account_id'=>$param['account_id']])->find())
            {
                $res = $this->monitorModel->allowField(true)->save($param,['account_id'=>$param['account_id']]); 
                
                if($res === false){
                    $message='更新失败';
                    $response = ['result'=>false,'message'=>$message.':'.$this->monitorModel->getError()];
                }else{
                    $message='更新成功';
                    $response = ['result'=>true,'message'=>$message];
                }
                
            }else{
                $res =  $this->monitorModel->allowField(true)->save($param); 
                if($res)
                {
                    $message='添加成功';
                    $response = ['result'=>true,'message'=>$message];
                }else{
                    $message='添加失败';
                    $response = ['result'=>false,'message'=>$message.':'.$this->monitorModel->getError()];
                }  
            }
            return $response;
        } catch (JsonErrorException $exp){
           throw new JsonErrorException($exp->getMessage());
        } 
        
    }
    
    /**
     * 授权登录
     * @param type $param
     * @return string
     * @throws JsonErrorException
     */
    public  function authorize($param)
    {
        if($error= $this->validate->checkData($param,'auth'))
        {
            return ['result'=>FALSE,'message'=>$error];
        }
        try{
            
            if($this->healthDataModel->where(['account_id'=>$param['account_id']])->find())
            {
                $res = $this->healthDataModel->allowField(true)->isUpdate(true)->save($param,['account_id'=>$param['account_id']]); 
                
                if($res === false){
                    $message='更新失败';
                    $response = ['result'=>false,'message'=>$message.':'.$this->healthDataModel->getError()];
                }else{
                    
                    $message='更新成功';
                    $response = ['result'=>true,'message'=>$message];
                }
                
            }else{
                $res =  $this->healthDataModel->allowField(true)->save($param); 
                if($res)
                {
                    $message='添加成功';
                    $response = ['result'=>true,'message'=>$message];
                }else{
                    $message='添加失败';
                    $response = ['result'=>false,'message'=>$message.':'.$this->healthDataModel->getError()];
                }  
            }
            $username = $param['username'];
            $password = $param['password'];
            $proxy_ip = $param['proxy_ip'];
            $proxy_port = $param['proxy_port'];
            $proxy_user = $param['proxy_user'];
            $proxy_passwd = $param['proxy_passwd'];
            $proxy_protocol = $param['proxy_protocol'];
            $tfa_token = $param['tfa_token'];
            
            $login_res = $this->simulatedLoginByQueryList($username, $password, $proxy_ip, $proxy_port, $proxy_user, $proxy_passwd, $proxy_protocol, $tfa_token);
           
            if($login_res['result'] && $response['result'])
            {
                
                $this->healthDataModel->where(['account_id'=>$param['account_id']])->update(array('auth'=>1)); 
                
                $return  = ['result'=>true,'message'=>$response['message'].$login_res['message']];
            }else{
                $return = ['result'=>false,'message'=>$response['message'].'<---->'.$login_res['message']];
            }
            return $return;
            
        } catch (JsonErrorException $exp){
           throw new JsonErrorException($exp->getMessage());
        } 
        
    }
    
    /**
     * 获取手机验证码
     * @param type $username
     * @return type
     */
    public  function getCode($username)
    {
        if(empty($username))
        {
            return ['result'=>false,'data'=>'','message'=>'用户名不能为空'];
        }else{
              
            $token_params['username']= $username;
        
            $token_url ='https://china-merchant.wish.com/api/gen_tfa_token';

            $this->snoopy->submit($token_url, $token_params); 

            $token = json_decode($this->snoopy->results,TRUE);
 
            if($token['code']==0)
            {
                return ['result'=>TRUE,'message'=>'短信验证码获取成功，请到手机号码['.$token['data']['phone_number'].']查看'];
            }else{
                return ['result'=>FALSE,'message'=>$token['msg']];
            } 
        }
    }
    /**
     * 模拟登陆
     */
    public  function simulatedLogin($username,$password,$tfa_token='')
    {
        $login_url = "https://china-merchant.wish.com/api/login";
        
        $post["username"]     = $username;
        $post["password"]     = $password;
        $post["remember_me"]  = "true";
        if($tfa_token)
        {
            $post["tfa_token"]  = $tfa_token;
        }
        
        $this->snoopy->scheme='https';
        $this->snoopy->host='china-merchant.wish.com';
        $this->snoopy->agent='Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/59.0.3071.115 Safari/537.36';
        $this->snoopy->referer='china-merchant.wish.com';      
        $this->snoopy->submit($login_url, $post); 
        
        $response = json_decode($this->snoopy->results,true);
        
        if($response['code']==0 && $response['msg']=='')
        {
            return ['result'=>true,'message'=>$response['msg']];
        }else{
            return ['result'=>false,'message'=>$response['msg']];
        }
        
    }
    
    /**
     * 模拟登陆
     */
    public  function simulatedLoginByQueryList($username,$password,$proxy_ip,$proxy_port,$proxy_user,$proxy_passwd,$proxy_protocol,$tfa_token='')
    {  
        set_time_limit(0);
        try{
            
            $login = $this->simulatedWishLogin($username, $password, $proxy_ip, $proxy_port, $proxy_user, $proxy_passwd, $proxy_protocol, $tfa_token);
            if(isset($login->html) && $login->html)
            {
                $response = json_decode($login->html,true);
            
                if($response['code']==0 && $response['msg']=='')
                {
                    return ['result'=>true,'message'=>$response['msg']];
                }else{
                    return ['result'=>false,'message'=>$response['msg']];
                }
            }else{
                return ['result'=>false,'message'=>'代理服务错误'];
            }    
             
        }catch(JsonErrorException $exp){
            throw new Exception($exp->getMessage());
        }
        
        
    }
    
    
    /**
     * 模拟登陆
     */
    public  function simulatedWishLogin($username,$password,$proxy_ip='',$proxy_port='',$proxy_user='',$proxy_passwd='',$proxy_protocol='',$tfa_token='')
    {  
        set_time_limit(0);
        try{
            if(empty($password)  || empty($username) )
            {
                return '';
            }
            
            $post['username'] = $username;
            $post['password'] = $password;
            if($tfa_token)
            {
                $post['tfa_token'] = $tfa_token;
            }
            $post['remember_me'] = "true";
            
            $proxy=$proxy_ip.':'.$proxy_port;
            
            $proxy_user_pwd=$proxy_user.':'.$proxy_passwd;
            
            $proxy_type = $proxy_protocol;
            
            require_once ROOT_PATH. '/extend/QueryList/vendor/autoload.php';
            $login = QueryList::run('Login',[
               'scheme'=>'https',
               'referrer'=>'https://china-merchant.wish.com',
               'target' => 'https://china-merchant.wish.com/api/login',
               'method' => 'post',
               'proxy'=>$proxy,
               'timeout'=>120,
               'proxy_user_pwd'=>$proxy_user_pwd,
               'proxy_type'=>$proxy_type,
               'params' => $post,//登陆表单需要提交的数据
               'user_agent'=> array_rand($this->agents),
               'cookiePath' => './wish_cookie.txt'
            ]);
            
            return $login;
        }catch(JsonErrorException $exp){
            throw new Exception($exp->getMessage());
        }  
    }
    
    /**
     * 模拟速卖通登录
     * @param type $username
     * @param type $password
     * @param type $proxy_ip
     * @param type $proxy_port
     * @param type $proxy_user
     * @param type $proxy_passwd
     * @param type $proxy_protocol
     * @param type $tfa_token
     * @return string
     * @throws Exception
     */
    public  function simulatedAliexpressLogin($username,$password)
    {  
        set_time_limit(0);
        try{
            if(empty($password)  || empty($username) )
            {
                return '';
            }
            
            $post['loginId'] = $username;
            $post['password2'] = $password;
          
            require_once ROOT_PATH. '/extend/QueryList/vendor/autoload.php';
            $login = QueryList::run('Login',[
               'scheme'=>'https',
               'referrer'=>'https://passport.aliexpress.com',
               'target' => 'https://passport.aliexpress.com/newlogin/login.do?fromSite=4&appName=aebuyer',
               'method' => 'post',
               'timeout'=>120,
               'params' => $post,//登陆表单需要提交的数据
               'user_agent'=> array_rand($this->agents),
               'cookiePath' => './aliexpress_cookie.txt'
            ]);
            if($login->html)
            {
                $res = json_decode($login->html,true);
                if(100 == $res['data']['resultCode'] )
                {
                    
                }
            }
            return $login;
        }catch(JsonErrorException $exp){
            throw new Exception($exp->getMessage());
        }  
    }
    
    
    /**
     * 获取wish健康数据
     * @param string $username 用户名
     * @param string $password 密码
     * @param string $tfa_token 手机验证码
     * @return void|
     * @throws Exception
     */
    public  function getStatisticsData($username,$password,$proxy_ip,$proxy_port,$proxy_user,$proxy_passwd,$proxy_protocol,$tfa_token='')
    {
        set_time_limit(0);
        try{
             
            
//            $proxy='172.21.90.90:1080';
//            $proxy_user_pwd='43.251.16.113:123456';
            
            $login = $this->simulatedWishLogin($username, $password, $proxy_ip, $proxy_port, $proxy_user, $proxy_passwd, $proxy_protocol,$tfa_token);
           
            if(isset($login->html) && $login->html)
            {
                $login_res = json_decode($login->html,true);
                //登陆成功后，就可以调用get和post两个方法来抓取登陆后才能抓的页面
                //$ql = $login->get('页面地址'[,'处理页面的回调函数','传给回调的参数']);

                //登录成功
                if($login_res['code'] == 0 && $login_res['msg']=='')
                {
                    $q2 = $login->get('https://china-merchant.wish.com');

                    $html2 = $q2->setQuery([''])->html;
                     
                    $data2 = QueryList::Query($html2,array(
                        'valid_money' =>array("div#loading-content>div.section:first>div.row-fluid>ul.data-list>a>li:eq(0)>div:eq(0)","html"),
                        'valid_money_time' =>array("div#loading-content>div.section:first>div.row-fluid>ul.data-list>a>li:eq(0)>div:eq(1)","html"),
                        'unvalid_money' =>array("div#loading-content>div.section:first>div.row-fluid>ul.data-list>a>li:eq(1)>div.data-pt","html"),
                    ))->getData(function($item){

                        foreach ($item as $name => &$v) {
                            if($name=='valid_money_time')
                            {
                                preg_match('/\d{4}-\d{2}-\d{2}/', $v,$match);

                                if($match)
                                {
                                    $v = $match[0];
                                }else{
                                    $v=$v;
                                }

                            }else{
                                $v = str_replace(',', '', $v);
                                $v = str_replace('$', '', $v);
                                $v = str_replace('USD', '', $v);
                                $v = trim($v);
                            }
                        }
                        return $item;
                    });

                    $ql = $login->get('https://china-merchant.wish.com/trusted-store');

                    $html = $ql->setQuery([])->html;

                    $data1 = QueryList::Query($html,array(
                        'js' =>array("script:eq(1)","html"),
                    ))->getData(function($item){
                        preg_match('/"data":(.*?)},/', $item['js'],$match);

                        if(count($match) >1)
                        {
                            $res = json_decode($match[1]."}",true);
                            $health=[];
                            foreach ($res as $k => $v) 
                            {
                                if($k==1)
                                {
                                    $health['valid_tracking_rate']=$v;
                                }elseif($k==2){
                                    $health['refund_rate']=$v;
                                }elseif($k==3){
                                    $health['average_rating']=$v;
                                }elseif($k==4){
                                    $health['late_confirmed_fulfillment_rate']=$v;
                                }else{
                                    $health['counterfeit_rate']=$v;
                                }
                            }
                        }
                        return $health;
                    });
                    $data = array_merge($data2[0],$data1[0]);
                    $data['code']=$login_res['code'];
                    $data['msg']=$login_res['msg'];
                }else{
                    $data=[
                        'code'=>$login_res['code'],
                        'msg'=>$login_res['msg'],
                    ];
                } 
            }else{
                $data=[];
            } 
            
           return $data;
            
        }catch(JsonErrorException $exp){
            throw new Exception($exp->getMessage());
        }
    }
    
    /**
     * 
     * @param type $param
     * @param type $page
     * @param type $pageSize
     */
    public  function healthDataList($param,$page,$pageSize)
    {
        $where=[];
        
        if(isset($param['account_id']) && $param['account_id'])
        {
           $where['account_id'] = ['eq',$param['account_id']];
        } 
        
        if(isset($param['auth']) && is_numeric($param['auth']))
        {
           $where['auth'] = ['eq',$param['auth']];
        } 
        
        
        if(isset($param['start_time']) && $param['start_time'] && isset($param['end_time']) && $param['end_time']){
            
             if($param['end_time'] == $param['start_time']) //同一个时间
            {
                $param['start_time'] = $param['start_time'].' 00:00:00';
                $param['end_time']   = $param['end_time'].' 23:59:59';
            }
            $where['create_time']=['between time',[strtotime($param['start_time']),strtotime($param['end_time'])]];
        }elseif(isset($param['start_time']) && $param['start_time'])
        {
           $where['create_time'] = ['>=', strtotime($param['start_time'])];
        } elseif(isset($param['end_time']) && $param['end_time']){
            $where['create_time'] = ['<=', strtotime($param['start_time'])];
        }
        
        
        $count = $this->healthDataModel->where($where)->count();
        
        $data = $this->healthDataModel->with(['account'=>function($query){$query->field('id,code');},'monitor'])->where($where)->page($page,$pageSize)->order('id ASC')->select();
         
        if($data && is_array($data))
        {
            foreach ($data as $key => &$d) 
            {
                if(empty($d['monitor']))
                {
                    $commonMinitorData = $this->monitorModel->get(['account_id'=>0]);
                    if($commonMinitorData && is_object($commonMinitorData))
                    {
                        $commonMinitorData = $commonMinitorData->toArray ();
                        $d['monitor'] = $commonMinitorData;
                    }
                }
            }
        }
        
        return ['data'=>$data,'page'=>$page,'pageSize'=>$pageSize,'count'=>$count];
         
//        if(isset($param['uid']) && $param['uid'] != 1)
//        {
//            $hasWhere['seller_id'] = ['=',$param['uid']];
//        } 
        
        //$hasWhere['channel_id'] = ['=',3];
        //$count = $this->healthDataModel->hasWhere('account',$hasWhere)->with(['account'=>function($query){$query->with(['user']);}])->where($where)->count();
        
        //$data = $this->healthDataModel->hasWhere('account',['channel_id'=>3])->with(['account'=>function($query){$query->with(['user']);}])->where($where)->page($page,$pageSize)->order('id ASC')->select();
        
        
        
        
    }
    
    /**
     * 
     * @param type $param
     * @param type $page
     * @param type $pageSize
     */
    public  function healthHistoryDataList($param,$page,$pageSize)
    {
         
       $where=[];
        
        if(isset($param['account_id']) && $param['account_id'])
        {
           $where['account_id'] = ['eq',$param['account_id']];
        } 
        
        if(isset($param['start_time']) && $param['start_time'] && isset($param['end_time']) && $param['end_time']){
            
             if($param['end_time'] == $param['start_time']) //同一个时间
            {
                $param['start_time'] = $param['start_time'].' 00:00:00';
                $param['end_time']   = $param['end_time'].' 23:59:59';
            }
            $where['create_time']=['between time',[strtotime($param['start_time']),strtotime($param['end_time'])]];
        }elseif(isset($param['start_time']) && $param['start_time'])
        {
           $where['create_time'] = ['>=', strtotime($param['start_time'])];
        } elseif(isset($param['end_time']) && $param['end_time']){
            $where['create_time'] = ['<=', strtotime($param['start_time'])];
        }
        
        
        $count = $this->historyModel->where($where)->count();
        
        $data = $this->historyModel->where($where)->page($page,$pageSize)->order('id ASC')->select();
         
        
        return ['data'=>$data,'page'=>$page,'pageSize'=>$pageSize,'count'=>$count];
        
    }
    
    public  function getUserAgents($url)
    {
        $html = file_get_contents($url);
        
        preg_match('/(.*?)var pcj = (.*?)]];/',$html,$match);
        
        $res = json_decode($match[2].']]',true);
        
        dump($res);die;
       
        $data = QueryList::Query($html,['js'=>['script','html']])->getData(function($item){
            return $item;
        });
        dump($data);
    }
}
