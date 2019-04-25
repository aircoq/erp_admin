<?php

namespace app\index\service;

use app\common\model\ServerNetworkIp;
use app\common\model\Server;
use app\common\model\ExtranetType;
use think\Db;
use app\common\exception\JsonErrorException;
use think\Exception;


/**
 * Created by PhpStorm.
 * User: zhaixueli
 * Date: 2018/11/29
 * Time: 17:37
 */
class ServerNetwork
{
    protected $serverNetworkIpModel;
    protected $server;
    protected $error = '';
    protected $taskCondition = '';
    protected $taskNum = '';

    public function __construct()
    {
        if (is_null($this->serverNetworkIpModel)) {
            $this->serverNetworkIpModel = new ServerNetworkIp ();
        }
        if (is_null($this->server)) {
            $this->server = new Server ();
        }

    }


    public function getError()
    {
        return $this->error;
    }

    /** 服务器记录列表
     * @param array $where
     * @param $page
     * @param $pageSize
     * @return array
     */
    public function list(array $where, $page, $pageSize)
    {
        try{
            $join['s'] = ['server s', 'o.server_id=s.id', 'left'];
            $ip=$this->serverNetworkIpModel
                ->alias('o')
                ->field('o.ip')
                ->group('o.ip')
                ->having('count(o.ip)'.$this->taskCondition. $this->taskNum)
                ->select();
            $ips=[];
            foreach($ip as $k=>$v){
                $ips[]=$v['ip'];
            }
            $count = $this->serverNetworkIpModel->alias('o')
                ->join($join)->field('o.id')
                ->where($where['where'])
                ->where($where['ips'])
                ->where('o.ip','in',$ips)
                ->count();

            $list = $this->serverNetworkIpModel->alias('o')
                ->join($join)
                ->field('name,o.id,o.server_id,o.create_time,o.ip,s.type,s.ip_type')
                ->where($where['where'])
                ->where($where['ips'])
                ->where('o.ip','in',$ips)
                ->page($page, $pageSize)
                ->select();
            $ip_type=array_column($list,'ip_type');
            $wheres['id']=array('in',$ip_type);
            $name= (new ExtranetType)->where($wheres)->column('id,name');
            foreach ($list as &$v) {
                switch ($v['type'] )
                {
                    case 0:
                        $v['type']= '虚拟机'.'('.($name[$v->ip_type] ?? '').')';
                        break;
                    case 1:
                        $v['type']='云服器';
                        break;
                    case 2:
                        $v['type']='超级浏览器';
                        break;
                    case 3:
                        $v['type']='代理';
                        break;
                }
                $v['server_id']=$v['name'];
                $v['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
                $v['ip']= $this->convertIpToString($v['ip']);
            }

            $result = [
                'data' => $list,
                'page' => $page,
                'pageSize' => $pageSize,
                'count' => $count,
            ];
            return $result;
        } catch (Exception $e) {
        }

    }

    /**
     * 代替long2ip函数
     * @param $ip
     * @return string
     */
    function convertIpToString($ip)
    {
       $long= intval($long = 4294967295 - ($ip - 1));
        return long2ip(-$long);
    }


    /** 组装where 条件
     * @param object $request
     * @return array $where
     */
    public function getWhere($request)
    {
        $where = [];
        $ips = [];
        $params = $request->param();
        if (isset($params['snType']) && isset($params['snText']) && !empty($params['snText'])) {
            $text1 = $params['snText'];
            switch (trim($params['snType'])) {
                //服务器名
                case 'name':
                    $condition['name'] = ['like', '%' . $text1 . '%'];
                    $ids = $this->server->where($condition)->field('id')->select();
                    $orderIdArr = [];
                    foreach ($ids as $item) {
                        $orderIdArr[] = $item->id;
                    }
                    $where['server_id'] = ['IN', $orderIdArr];
                    break;
                //ip
                case 'ip':
                    $temp = bindec(decbin(ip2long($text1)));
                    $ips = [
                        "o.ip" => ['IN', $temp],
                    ];
                    break;
            }
        }
        if ( isset($params['taskCondition'])  && isset($params['taskNum'])  &&  !empty($params['taskNum'])) {
            $this->taskCondition=$params['taskCondition'];
            $this->taskNum=$params['taskNum'];
        }
        $data = [
            'ips' => $ips,
            'where' => $where,

        ];
        return $data;
    }

    /**
     * 新增
     * @param $data
     * @return bool
     */
    public function add($data)
    {
        try {
            $id = $data['id'] ?? 0;
            $time = time();
            $save_data['ip'] = sprintf('%u', ip2long($data['ip']));
            if ($this->serverNetworkIpModel->check(['server_id' => $data['server_id'],'ip'=>$save_data['ip']])) {
                $this->error = '服务器已经存在无法重复添加';
                return false;
            }
            $save_data['create_time'] = $time;
            $save_data['server_id'] = $data['server_id'];
            $save_data['id'] = $id;
            $this->serverNetworkIpModel->add($save_data);
            return true;
        } catch (Exception $e) {
            throw new JsonErrorException($e->getMessage());
        }


    }


    /** 获取账号信息
     * @param $id
     * @return array
     */
    public function getOne($id)
    {
        $field = 'id,server_id,ip,create_time';
        if ($id == 0) {
            return $this->serverNetworkIpModel->field($field)->order('id desc')->find();
        }
        return $this->serverNetworkIpModel->where('id', $id)->field($field)->find();
    }
}
