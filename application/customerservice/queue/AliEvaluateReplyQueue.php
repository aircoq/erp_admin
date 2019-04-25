<?php
namespace app\customerservice\queue;

use app\common\model\aliexpress\AliexpressOnlineOrder;
use app\customerservice\service\aliexpress\AliEvaluateHelp;
use Exception;
use app\common\cache\Cache;
use \service\alinew\AliexpressApi;
use app\common\service\SwooleQueueJob;
use app\common\model\aliexpress\AliexpressEvaluate;

class AliEvaluateReplyQueue extends SwooleQueueJob
{

    private $evaluate_id;
    private $score;
    private $content;
    /**
     * @desc 作者
     * @author Reece
     * @date 2018-08-17 14:43:11
     * @return string
     */
    public function getAuthor(): string
    {
        return 'Reece';
    }

    /**
     * @desc 描述
     * @author Reece
     * @date 2018-08-17 14:42:11
     * @return string
     */
    public function getDesc(): string
    {
        return '速卖通评价批量回评';
    }

    /**
     * @desc 获取队列名称
     * @author Reece
     * @date 2018-08-17 14:41:11
     * @return string
     */
    public function getName(): string
    {
        return '速卖通评价批量回评';
    }

    public static function swooleTaskMaxNumber():int
    {
        return 5;
    }

    /**
     * @desc 执行
     * @author Reece
     * @date 2018-08-17 14:40:11
     */
    public function execute()
    {
        try {
            //获取执行的参数信息
            $this->getParams();
            $evaluate = AliexpressEvaluate::find($this->evaluate_id);
            $service = new AliEvaluateHelp();
            $service->evaluate($evaluate, $this->score, $this->content);
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }


    /**
     * @desc 处理响应数据
     * @param string $data 执行api请求返回的订单数据json字符串
     * @return array 结果集
     * @author Jimmy <554511322@qq.com>
     * @date 2018-03-19 15:20:11
     */
    private function dealResponse($data)
    {
        //已经报错了,抛出异常信息
        if (isset($data->error_response) && $data->error_response) {
            throw new Exception($data->sub_msg, $data->code);
        }
        //如果没有result
        if (!isset($data->result)) {
            throw new Exception(json_encode($data));
        }
        return json_decode($data->result, true);
    }

    /**
     * @desc 获取账号的配置信息
     * @param int $id 账号对应的数据库表ID
     * @return array $config 账号配置信息
     * @author Jimmy <554511322@qq.com>
     * @date 2018-03-13 15:03:11
     */
    private function getConfig($id)
    {
        $info = Cache::store('AliexpressAccount')->getTableRecord($id);
        if (!$info || !isset($info['id'])) {
            throw new Exception('账号信息缺失');
        }
        if (!param($info, 'client_id')) {
            throw new Exception('账号ID缺失,请先授权!');
        }
        if (!param($info, 'client_secret')) {
            throw new Exception('账号秘钥缺失,请先授权!');
        }
        if (!param($info, 'access_token')) {
            throw new Exception('access token缺失,请先授权!');
        }
        $config['id'] = $info['id'];
        $config['client_id'] = $info['client_id'];
        $config['client_secret'] = $info['client_secret'];
        $config['token'] = $info['access_token'];
        return $config;
    }

    /**
     * @desc 获取任务执行的参数,过滤参数信息
     * @return array $data 检验后的数据信息
     * @author Jimmy <554511322@qq.com>
     * @date 2018-03-13 14:53:11
     */
    private function getParams()
    {
        //获取任务参数
        $data = json_decode($this->params, true);
        if (!param($data, 'evaluate_id')) {
            throw new Exception('评价ID不能为空!');
        }
        if (!isset($data['score'])) {
            throw new Exception('评价分数不能为空!');
        }
        if(!isset($data['content'])){
            throw new Exception('评价内容不能为空!');
        }
        $this->evaluate_id = $data['evaluate_id'];
        $this->score  = $data['score'];
        $this->content = $data['content'];
    }
    
}

