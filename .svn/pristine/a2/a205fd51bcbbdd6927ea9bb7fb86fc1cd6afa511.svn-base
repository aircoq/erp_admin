<?php
namespace app\index\controller;

use app\common\exception\JsonErrorException;
use app\common\service\Common;
use think\Request;
use app\common\cache\Cache;
use think\Db;
use think\Exception;
use app\common\model\Currency as  currencyModel;
use app\common\model\CurrencyRate ;
use app\index\service\Currency as CurrencyService;

/**
 * @module 基础设置
 * @title 汇率管理
 * @author RondaFul
 */
class Currency 
{   
    /**
     * @title 查看币种列表
     * @url /currency
     * @method get
     * @param Request $request
     * @return \think\Response
     */
    public function index(Request $request)
    {   
        $page          = $request->get('page', 1);
        $pageSize      = $request->get('pageSize', 100);
        $params        = $request->param();
        $currencyModel = new currencyModel();
        $where         = [];
        
        if (isset($params['status'])) {           
            $where['status'] = $params['status'];
            $count = $currencyModel->where($where)->count();            
        } else {
            $count = $currencyModel->where($where)->count();
        }
        $currencyList = $currencyModel->where($where)->page($page,$pageSize)->field(true)->order('sort asc')->select();
        foreach($currencyList as &$list) {
            $list['official_rate'] = number_format($list['official_rate'], 6, '.', '');
        }        
        $result = [
            'data'     => $currencyList,
            'page'     => $page,
            'pageSize' => $pageSize,
            'count'    => $count,
        ];
        return json($result, 200);
    }
    
    /**
     * @title 查看汇率历史记录
     * @url /currency/:id(\d+)
     * @method get
     * @param Request $request
     * @param int $id
     * @return \think\Response
     */
    public function read(Request $request, $id)
    {
        $page             = $request->get('page', 1);
        $pageSize         = $request->get('pageSize', 100);  
        $currencyRateModel= new CurrencyRate();
        $count = $currencyRateModel->where(array('cur_id' => $id))->count();
        $lists = $currencyRateModel->where(array('cur_id' => $id))->page($page,$pageSize)->order('id desc')->select();  
        $data  = [];
        foreach ($lists as $list) {
            $row = [
                'official_rate' => '',
                'system_rate' => '',
                'create_time' => date('Y-m-d H:i:s', $list['update_time'])
            ];
            if($list['type'] ==1) {
                $row['system_rate'] = $list['rate'];
            }else {               
                $row['official_rate'] = $list['rate'];
            }
            $data[] = $row;
        }
        $result = [
            'data'     => $data,
            'page'     => $page,
            'pageSize' => $pageSize,
            'count'    => $count
        ];
        return json($result, 200);
    }    
    
    /**
     * @title 创建币种
     * @method post
     * @url /currency
     * @param Request $request
     * @return \think\Response
     * @throws JsonErrorException
     */
    public function save(Request $request)
    {   
        $params = $request->param();
        if (empty($params) ) {
            return json(['message' => '名称不能为空'], 400);
        }
        if (empty($params['code'])) {
            return json(['message' => '代码不能为空'], 400);
        }
        if (empty($params['symbol'])) {
            return json(['message' => '符号不能为空'], 400);
        }
        $currencyModel    = new currencyModel();
        $currency         = $currencyModel->where(array('code'=> $params['code']))->find();
        if ($currency) {
            throw new JsonErrorException("已经存在");
        }
        $time = time();
        $userinfo = Common::getUserInfo($request);
        $userid = param($userinfo, 'user_id', 0);
        Db::startTrans();
        try {
            $params['create_time'] = $time;
            $params['update_time'] = $time;
            $params['create_id'] = $userid;
            $params['update_id'] = $userid;
            $currencyModel->allowField(true)->isUpdate(false)->save($params);
            Db::commit();
            return json(['message' => '添加成功'], 200);
        } catch(Exception $e) {
            Db::rollback();
            throw new JsonErrorException("添加失败");
        }
    }
    
    
    

    /**
     * @title 编辑币种
     * @url /currency/:id(\d+)/edit
     * @method get
     * @param  int $id
     * @return \think\Response
     */
    public function edit($id)
    {
        if ($id == 0) {
            return json(['message' => '请求参数错误'], 400);
        }
        $currencyModel = new currencyModel();
        $currency = $currencyModel->field('id, name,code')->find($id);
        $currency = $currency ? : [];
        return json($currency, 200);
    }

    /**
     * @title 更新币种汇率
     * @url /currency/:id(\d+)
     * @method put 
     * @param Request $request
     * @param int $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {   
        if ($id == 0) {
            return json(['message' => '请求参数错误'], 400);
        }
        $params = $request->param();
        if (empty($params) || (empty($params['system_rate']))) {
            return json(['message' => '请求参数不能为空'], 400);
        }               
        $currencyModel = new currencyModel();
        $currency         = $currencyModel->field('id,code,main')->find($id);
        if (!$currency) { 
            return json(['message' => '改记录不存在'], 500);
        }        
        if ($currency['code']=='CNY') {
            return json(['message' => '主汇率不能更改.'], 400);
        }
        if (!empty($params['system_rate']) ) {
            $system_rate = $params['system_rate'];           
        }
        $data = [
            'system_rate'  => $system_rate,
            'update_time'  => time(),
        ];
        try {
            Db::startTrans();           
            $params['update_id'] = Common::getUserInfo($request) ? Common::getUserInfo($request)['user_id'] : 0;
            $currencyModel->allowField(true)->save($data, ['id' => $id]);
            unset($data);           
            $data['code']         = $currency['code'];            
            $data['cur_id']       = $id;
            $data['rate']         = $params['system_rate'];
            $data['type']         = 1;
            $data['create_time']  = time();
            $data['update_time']  = time();
            $currencyRateModel    = new CurrencyRate();
            $currencyRateModel->save($data);
            Cache::handler()->del('currency');
            Cache::handler()->del('cache:Currency');
            Db::commit();
            return json(['message' =>  '更新成功'], 200);
        } catch (Exception $e) {
            Db::rollback();
            return json(['message' => '更新失败'], 400);
        }
    }
    
    /**
     * @title 更新官方汇率
     * @method post
     * @url /currency/updateOfficialRate
     * Appkey  22397

       Secret  fa77b72ecdf8deb1dc7921db4d5f6593

       Sign   ef6f250e5f7db2dee0df5b317b6317b3
     * @param Request $request
     * @param unknown $id
     * 
     */
    public function updateOfficialRate()
    {                   
        try{
            $CurrencyService = new CurrencyService();
            return json($CurrencyService->updateOfficialRate(true), 200);
        }catch (Exception $ex){
            $err = [];
            $err['message'] = $ex->getMessage();
            $err['file'] = $ex->getFile();
            $err['line'] = $ex->getLine();
            return json($err,400);
        }
        
    }
    
    /**
     * @title 查询官方汇率(新增币种)
     * @url /currency/selectOfficialRate
     * @method post
     * @return \think\response\Json
     */
    public function selectOfficialRate(Request $request)
    {  
        $params = $request->param();
        if (strlen($params['code']) == 3) {
            $code = $params['code'];
            $nowapi_parm['apiurl'] = 'http://api.k780.com:88';
            $nowapi_parm['app']    = 'finance.rate';
            $nowapi_parm['scur']   = $code;
            $nowapi_parm['tcur']   = 'CNY';
            $nowapi_parm['appkey'] ='22397';
            $nowapi_parm['sign']   ='ef6f250e5f7db2dee0df5b317b6317b3';
            $nowapi_parm['format'] ='json';
            $result                = $this->nowapi_call($nowapi_parm);
            if (isset($result['rate'])) {
                return json(['message'=> $result['rate']], 200);
            }else{
                return json(['message' =>  '查询失败.'], 400);
            }
        } else {
            return json(['message' =>  '错误的代码.'], 400);
        }
    }
    
    /**
     * @title 修改币种排序
     * @method put
     * @url /currency/sorts
     * @param  \think\Request $request
     * @return \think\Response
     */
    public function sorts(Request $request)
    {
        $sorts = json_decode($request->param('sorts'), true);
        if (empty($sorts)) {
            throw new JsonErrorException('请求参数不能为空');
        }
        Db::startTrans();
        try {
            foreach($sorts as $sort) {
                $currency = new CurrencyModel();
                $currency->save(['sort' => $sort['sort']], ['id' => $sort['id']]);
            }
            Db::commit();
            Cache::handler()->del('currency');
            return json(['message' => '操作成功'], 200);
        } catch (Exception $ex) {
            Db::rollback();
            return json(['message' => '操作失败'], 400);
        }
    }      

   private function nowapi_call(array $a_parm){
        // 组装数据
        $a_parm['format'] = empty($a_parm['format']) ? 'json' : $a_parm['format'];
        $apiurl = empty($a_parm['apiurl']) ? 'http://api.k780.com:88/?' : $a_parm['apiurl'].'/?';
        unset($a_parm['apiurl']);
        foreach($a_parm as $k=>$v){
            $apiurl.=$k.'='.$v.'&';
        }
        $apiurl = substr($apiurl,0,-1);
        if(!$callapi=file_get_contents($apiurl)){
            return false;
        }
        //format
        if($a_parm['format']=='base64'){
            $a_cdata=unserialize(base64_decode($callapi));
        }elseif($a_parm['format']=='json'){
            if(!$a_cdata=json_decode($callapi,true)){
                return false;
            }
        }else{
            return false;
        }
        //array
        if($a_cdata['success']!='1'){
            return false;
        }
        return $a_cdata['result'];
    }

    /**
     * @title 币种字段
     * @url /currency/dictionary
     * @method get
     */
    public function dictionary()
    {
        $result = [];
        $lists = Cache::store('currency')->getCurrency();
        foreach($lists as $list)
        {
            $result[] = [
                'code'        => $list['code'],
                'name'        => $list['name'],
                'system_rate' => $list['system_rate'],
                'symbol'      => $list['symbol'],
            ];
        }
        
        return json($result);
    }
    
  }