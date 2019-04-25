<?php
namespace app\api\help;

use app\common\exception\ApiException;
use think\Exception;
use think\Request;
use app\api\components\ApiPost;
use think\config;
use app\api\components\ApiVisit;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2017/5/12
 * Time: 11:21
 */
class ApiHelp
{
    /** 请求
     * @var
     */
    protected $request;

    /** 提交查询验证
     * @var bool
     */
    protected $enableCsfValidation = false;

    /** 用户ID
     * @var int
     */
    protected $uid = 0;

    /**
     * 初始化
     * @param $type
     */
    public function init($type)
    {
        $this->before($type);
    }

    /**
     * 行为之前
     * @param $type
     */
    public function before($type)
    {
        try {
            $this->request = Request::instance();
            ApiPost::requestCheck($this->request);
            Config::load(APP_PATH . 'api/config/ApiUrl.php');
            $config = Config::get();
            if (!isset($config['api_setting'])) {
                ApiPost::error("api config setting error");
            }
            $apiUrl = $config['api_setting'];
            if (!isset($apiUrl[$this->request->get('url')])) {
                ApiPost::error("api config method error");
            }
            $apiName = $this->request->get('url');
            $apiData = $apiUrl[$apiName];
            //记录访问次数
            if (isset($apiData['visit'])) {
                ApiVisit::checkApiVisit($apiName, $apiData['visit']);
            }
            $service = (string)ucfirst($apiData['service']);
            $action = (string)(isset($apiData['method']) ? $apiData['method'] : 'index');
            $retData = ApiPost::requestStart($apiData);
            $this->execute($retData, $apiData, $service, $action,$type);
        } catch (ApiException $e) {
            ApiPost::error("api error");
        } catch (Exception $e) {
            ApiPost::error($e->getMessage());
        }
    }

    /**
     * 处理
     * @param $retData
     * @param $apiData
     * @param $service
     * @param $action
     * @param $type
     */
    public function execute($retData, $apiData, $service, $action,$type)
    {
        try {
            //用户ID
            $this->uid = $retData['uid'];
            //控制器
            $className = (string)ucwords($service);
            $controller_file = APP_PATH . DS . 'api' . DS . 'service' . DS . $className . '.php';
            if (!file_exists($controller_file)) {
                ApiPost::error("api model file error");
            }
            $controller_obj = "\\app\\api\\service\\" . $className;
            $object = new $controller_obj($this);
            $class_methods = get_class_methods($object);
            if (!in_array($action, $class_methods)) {
                ApiPost::error("api model action error");
            }
            $object->object = $this;
            $object->apiData = $apiData;
            $object->retData = $retData;
            if($type == 'POST'){
                $content_type = $this->request->header()['content-type'] ?? '';
                $object->requestData = ApiPost::requestPostData($_POST);
                switch ($content_type){
                    case 'application/json':
                        $inputData = $this->request->getInput();
                        foreach (json_decode($inputData, true) as $kk => $vv) {
                            $object->requestData[$kk] = $vv;
                        }
                        break;
                    default:
                        if (!empty($_POST['param']) && is_array(json_decode($_POST['param'], true))) {
                            unset($object->requestData['param']);
                            foreach (json_decode($_POST['param'], true) as $kk => $vv) {
                                $object->requestData[$kk] = $vv;
                            }
                        }
                        break;
                }
            }else{
                unset($_GET['url']);
                $object->requestData = ApiPost::requestPostData($_GET);
                if (!empty($_GET)) {
                    foreach ($_GET as $kk => $vv) {
                        $object->requestData[$kk] = $vv;
                    }
                }
            }
            $this->after($object, $action);
        } catch (ApiException $e) {
            ApiPost::error("api error");
        } catch (Exception $e) {
            ApiPost::error($e->getMessage());
        }
    }

    /** 结束
     * @param $object
     * @param $action
     * @return \think\response\Json
     */
    public function after($object, $action)
    {
        ApiPost::requestEnd($object->requestEnd($object->$action()));
    }
}