<?php

namespace app\index\service;

use app\common\exception\JsonErrorException;
use app\common\model\AccountApply;
use app\common\model\Server;
use app\common\model\Software;
use app\common\cache\Cache;
use app\common\model\SoftwareLog;
use app\common\service\Common;
use app\common\service\UniqueQueuer;
use app\index\controller\ServerSoftware;
use app\index\queue\ServerSoftwareBatchQueue;
use think\Exception;
use think\Request;
use think\Db;

/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/11/21
 * Time: 17:46
 */
class SoftwareService
{
    protected $software;

    public function __construct()
    {
        if (is_null($this->software)) {
            $this->software = new Software();
        }
    }

    /**
     * 软件列表
     * @param $params
     * @param int $page
     * @param int $pageSize
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function lists($params, $page = 1, $pageSize = 10)
    {
        $where = $this->getWhere($params);
        $field = '*';
        $orderBy = fieldSort($params);
        $orderBy .= 'id desc';
        $count = $this->software->field($field)->where($where)->count();
        $ret = $this->software->field($field)
            ->where($where)
            ->page($page, $pageSize)
            ->order($orderBy)
            ->select();
        $list = $this->getLists($ret);
        $result = [
            'data' => $list,
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
        ];
        return $result;
    }

    private function getLists($ret)
    {
        $result = [];
        foreach ($ret as $v){
            $row = $this->row($v);
            $result[] = $row;
        }
        return $result;
    }

    private function row($v)
    {
        $row = [];
        $row['id'] = $v->id;
        $row['software_type'] = $v->software_type;
        $row['version'] = $v->version;
        $row['remark'] = $v->remark;
        $row['status'] = $v->status;
        $row['upgrade_address'] = $v->upgrade_address;
        $row['md5'] = $v->md5;
        $row['creator_id'] = $v->creator_id;
        $row['creator_txt'] = $v->creator_txt;
        $row['create_time'] = $v->create_time;
        $row['department_name'] = $v->department_name;
        return $row;
    }

    /**
     * 服务器软件列表
     * @param $params
     * @param int $page
     * @param int $pageSize
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function serverLists($params, $page = 1, $pageSize = 10)
    {
        $model = new \app\common\model\ServerSoftware();
        $where = $this->getServerWhere($params);
        $join[] = ['server c', 'c.id = a.server_id', 'left'];
        $field = 'a.*,server.name';
        $orderBy = fieldSort($params);
        $orderBy .= 'upgrade_time desc';
        $count = $model->alias('a')->join($join)->where($where)->count();
        $list = $model->field($field)->alias('a')->join($join)
            ->where($where)
            ->page($page, $pageSize)
            ->order($orderBy)
            ->select();
        $result = [
            'data' => $list,
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
        ];
        return $result;
    }

    public function getServerWhere($params)
    {
        $where = [];
        if (isset($params['name']) && $params['name'] !== '') {
            $where['c.name'] = ['like', $params['name'] . '%'];
        }

        if (isset($params['corporation']) && $params['corporation'] !== '') {
            $where['corporation'] = ['like', $params['corporation'] . '%'];
        }

        if (isset($params['software_type']) && $params['software_type'] !== '') {
            $where['a.software_type'] = $params['software_type'];
        }

        if (isset($params['version']) && $params['version'] !== '') {
            $where['a.version'] = $params['version'];
        }

        if (isset($params['status']) && $params['status'] !== '') {
            $where['a.status'] = $params['status'];
        }

        $params['snDate'] = 'upgrade_time';
        //时间刷选
        if (isset($params['snDate'])) {
            $b_time = !empty($params['time_start']) ? strtotime($params['time_start'] . ' 00:00:00') : '';
            $e_time = !empty($params['time_end']) ? strtotime($params['time_end'] . ' 23:59:59') : '';
            if ($b_time && $e_time) {
                $where[$params['snDate']] = ['BETWEEN', [$b_time, $e_time]];

            } elseif ($b_time) {
                $where[$params['snDate']] = ['EGT', $b_time];
            } elseif ($e_time) {
                $where[$params['snDate']] = ['ELT', $e_time];
            }
        }

        return $where;
    }

    public function getWhere($params)
    {
        $where = [];
        if (isset($params['software_type']) && $params['software_type'] !== '') {
            $where['software_type'] = $params['software_type'];
        }
        if (isset($params['status']) && $params['status'] !== '') {
            $where['status'] = $params['status'];
        }
        $params['snDate'] = 'create_time';
        //时间刷选
        if (isset($params['snDate'])) {
            $b_time = !empty($params['time_start']) ? strtotime($params['time_start'] . ' 00:00:00') : '';
            $e_time = !empty($params['time_end']) ? strtotime($params['time_end'] . ' 23:59:59') : '';
            if ($b_time && $e_time) {
                $where[$params['snDate']] = ['BETWEEN', [$b_time, $e_time]];

            } elseif ($b_time) {
                $where[$params['snDate']] = ['EGT', $b_time];
            } elseif ($e_time) {
                $where[$params['snDate']] = ['ELT', $e_time];
            }
        }
        return $where;
    }


    /**
     * 保存信息
     * @param $data
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function save($data)
    {
        Db::startTrans();
        try {
            $this->software->allowField(true)->isUpdate(false)->save($data);
            //获取最新的数据返回
            $new_id = $this->software->id;
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw new Exception($e->getMessage());
        }
        return $this->read($new_id);
    }

    /**
     * 保存文件
     * @param $filename
     * @param $doc
     * @return mixed
     * @throws Exception
     */
    public function saveFile($filename, $doc)
    {
        if (empty($doc['content'])) {
            throw new Exception('添加的内容不能为空');
        }
        $start = strpos($doc['content'], ',');
        $content = substr($doc['content'], $start + 1);
        file_put_contents($filename, base64_decode(str_replace(" ", "+", $content)));
        return $filename;
    }

    /**
     * 软件信息
     * @param $id
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function read($id)
    {
        $accountInfo = $this->software->field(true)->where(['id' => $id])->find();
        if (empty($accountInfo)) {
            throw new JsonErrorException('软件不存在', 500);
        }
        return $accountInfo;
    }


    /**
     * 更新
     * @param $id
     * @param $data
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function update($id, $data)
    {
        $oldData = $this->software->isHas(['id' => $id]);
        if (!$oldData) {
            throw new JsonErrorException('软件不存在', 500);
        }
        Db::startTrans();
        try {
            $this->software->allowField(true)->save($data, ['id' => $id]);
            Db::commit();
        } catch (Exception $e) {
            Db::rollBack();
            throw new Exception($e->getMessage());
        }
        return $this->read($id);
    }


    /**
     * 服务器软件更新
     * @param $id
     * @param $data
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function serverUpdate($id, $data)
    {
        Db::startTrans();
        try {
            (new \app\common\model\ServerSoftware())->allowField(true)->save($data, ['id' => $id]);
            Db::commit();
        } catch (Exception $e) {
            Db::rollBack();
            throw new Exception($e->getMessage());
        }
        return $this->read($id);
    }

    /**
     * 状态
     * @param $ids
     * @param $data
     * @param $type
     * @return bool
     */
    public function batch($ids, $data, $type)
    {
        try {
            switch ($type) {
                case 'update': //更新客户端版本
                    $where['id'] = ['in', $ids];
                    $where['status'] = 0;
                    $serverIds = (new \app\common\model\ServerSoftware())->where($where)->column('server_id');
                    foreach ($serverIds as $id) {
                        (new UniqueQueuer(ServerSoftwareBatchQueue::class))->push($id);
                    }
                    break;
            }
            return true;
        } catch (\Exception $e) {
            throw new JsonErrorException($e->getMessage() . $e->getFile() . $e->getLine(), 400);
        }
    }

    /**
     * 发起更新服务器软件请求
     * @param $serverId
     * @return array
     * @throws \Exception
     */
    public function sendUpdate($serverId)
    {
        $reData = [
            'status' => true,
            'message' => '发起更新成功'
        ];
        $ip = (new Server())->where('id', $serverId)->value('ip');
        if (!$ip) {
            $reData['status'] = false;
            $reData['message'] = '服务器信息错误！';
            return $reData;

        }
        $url = 'https://' . $ip . ':10089/update';
        $re = $this->httpReader($url);
        Cache::handler()->hset(
            'hash:server_software',
            'get_' . time(),
            $re);
        return $reData;

    }

    /**
     * HTTP读取
     * @param string $url 目标URL
     * @param string $method 请求方式
     * @param array|string $bodyData 请求BODY正文
     * @param array $responseHeader 传变量获取请求回应头
     * @param int $code 传变量获取请求回应状态码
     * @param string $protocol 传变量获取请求回应协议文本
     * @param string $statusText 传变量获取请求回应状态文本
     * @param array $extra 扩展参数,可传以下值,不传则使用默认值
     * header array 头
     * host string 主机名
     * port int 端口号
     * timeout int 超时(秒)
     * proxyType int 代理类型; 0 HTTP, 4 SOCKS4, 5 SOCKS5, 6 SOCK4A, 7 SOCKS5_HOSTNAME
     * proxyAdd string 代理地址
     * proxyPort int 代理端口
     * proxyUser string 代理用户
     * proxyPass string 代理密码
     * caFile string 服务器端验证证书文件名
     * sslCertType string 安全连接证书类型
     * sslCert string 安全连接证书文件名
     * sslKeyType string 安全连接证书密匙类型
     * sslKey string 安全连接证书密匙文件名
     * @return string|array 请求结果;成功返回请求内容;失败返回错误信息数组
     * error string 失败原因简单描述
     * debugInfo array 调试信息
     */
    public function httpReader($url, $method = 'GET', $bodyData = [], $extra = [], &$responseHeader = null, &$code = 0, &$protocol = '', &$statusText = '')
    {
        $ci = curl_init();

        if (isset($extra['timeout'])) {
            curl_setopt($ci, CURLOPT_TIMEOUT, $extra['timeout']);
        }
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ci, CURLOPT_HEADER, true);
        curl_setopt($ci, CURLOPT_AUTOREFERER, true);
        curl_setopt($ci, CURLOPT_FOLLOWLOCATION, true);

        if (isset($extra['proxyType'])) {
            curl_setopt($ci, CURLOPT_PROXYTYPE, $extra['proxyType']);

            if (isset($extra['proxyAdd'])) {
                curl_setopt($ci, CURLOPT_PROXY, $extra['proxyAdd']);
            }

            if (isset($extra['proxyPort'])) {
                curl_setopt($ci, CURLOPT_PROXYPORT, $extra['proxyPort']);
            }

            if (isset($extra['proxyUser'])) {
                curl_setopt($ci, CURLOPT_PROXYUSERNAME, $extra['proxyUser']);
            }

            if (isset($extra['proxyPass'])) {
                curl_setopt($ci, CURLOPT_PROXYPASSWORD, $extra['proxyPass']);
            }
        }

        if (isset($extra['caFile'])) {
            curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, 2); //SSL证书认证
            curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, true); //严格认证
            curl_setopt($ci, CURLOPT_CAINFO, $extra['caFile']); //证书
        } else {
            curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, false);
        }

        if (isset($extra['sslCertType']) && isset($extra['sslCert'])) {
            curl_setopt($ci, CURLOPT_SSLCERTTYPE, $extra['sslCertType']);
            curl_setopt($ci, CURLOPT_SSLCERT, $extra['sslCert']);
        }

        if (isset($extra['sslKeyType']) && isset($extra['sslKey'])) {
            curl_setopt($ci, CURLOPT_SSLKEYTYPE, $extra['sslKeyType']);
            curl_setopt($ci, CURLOPT_SSLKEY, $extra['sslKey']);
        }

        $method = strtoupper($method);
        switch ($method) {
            case 'GET':
                curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'GET');
                if (!empty($bodyData)) {
                    if (is_array($bodyData)) {
                        $url .= (stristr($url, '?') === false ? '?' : '&') . http_build_query($bodyData);
                    } else {
                        curl_setopt($ci, CURLOPT_POSTFIELDS, $bodyData);
                    }
                }
                break;
            case 'POST':
                curl_setopt($ci, CURLOPT_POST, true);
                if (!empty ($bodyData)) {
                    curl_setopt($ci, CURLOPT_POSTFIELDS, $bodyData);
                }
                break;
            case 'PUT':
                //                 curl_setopt ( $ci, CURLOPT_PUT, true );
                curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'PUT');
                if (!empty ($bodyData)) {
                    curl_setopt($ci, CURLOPT_POSTFIELDS, $bodyData);
                }
                break;
            case 'DELETE':
                curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
            case 'HEAD':
                curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'HEAD');
                break;
            default:
                throw new \Exception(json_encode(['error' => '未定义的HTTP方式']));
                return ['error' => '未定义的HTTP方式'];
        }

        if (!isset($extra['header']) || !isset($extra['header']['Host'])) {
            $urldata = parse_url($url);
            $extra['header']['Host'] = $urldata['host'];
            unset($urldata);
        }

        $header_array = array();
        foreach ($extra['header'] as $k => $v) {
            $header_array[] = $k . ': ' . $v;
        }

        curl_setopt($ci, CURLOPT_HTTPHEADER, $header_array);
        curl_setopt($ci, CURLINFO_HEADER_OUT, true);

        curl_setopt($ci, CURLOPT_URL, $url);

        $response = curl_exec($ci);

        if (false === $response) {
            $http_info = curl_getinfo($ci);
            throw new \Exception(json_encode(['error' => curl_error($ci), 'debugInfo' => $http_info]));
            return ['error' => curl_error($ci), 'debugInfo' => $http_info];
        }

        $responseHeader = [];
        $headerSize = curl_getinfo($ci, CURLINFO_HEADER_SIZE);
        $headerData = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        $responseHeaderList = explode("\r\n", $headerData);

        if (!empty($responseHeaderList)) {
            foreach ($responseHeaderList as $v) {
                if (false !== strpos($v, ':')) {
                    list($key, $value) = explode(':', $v, 2);
                    $responseHeader[$key] = ltrim($value);
                } else if (preg_match('/(.+?)\s(\d+)\s(.*)/', $v, $matches) > 0) {
                    $protocol = $matches[1];
                    $code = $matches[2];
                    $statusText = $matches[3];
                }
            }
        }

        curl_close($ci);
        return $body;
    }


    /** 删除信息
     * @param $id
     */
    public function delete($id)
    {
        try {
            $this->software->where(['id' => $id])->delete();
        } catch (Exception $e) {
            throw new JsonErrorException($e->getMessage() . $e->getFile() . $e->getLine());
        }
    }

    /**
     * 接收更新版本信息
     * @param $ip
     * @param $type
     * @param $version
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function receptionUpdate($mac, $type, $version)
    {
        $reData = [
            'status' => true,
            'message' => '更新成功！',
        ];
        $serverId = (new Server())->where('mac', $mac)->value('id');
        if (!$serverId) {
            $reData['status'] = false;
            $reData['message'] = '不存在服务器信息！';
            return $reData;
        }
        $model = new \app\common\model\ServerSoftware();
        $where['software_type'] = $type;
        $where['server_id'] = $serverId;
        $old = $model->isHas($where);
        $save['version'] = $version;
        $save['upgrade_time'] = time();
        if ($old) {
            $model->save($save, ['id' => $old['id']]);
        } else {
            $save['create_time'] = $save['upgrade_time'];
            $save['software_type'] = $type;
            $save['server_id'] = $serverId;
            $save['server_id'] = $serverId;
            $save['status'] = 0;
            $model->allowField(true)->isUpdate(false)->save($save);
        }
        return $reData;
    }

    /**
     * 获取类型
     * @return array
     */
    public function typeInfo()
    {
        $reData = [];
        $allType = Software::TYPE;
        foreach ($allType as $k => $v) {
            $reData[] = [
                'value' => $k,
                'label' => $v,
            ];
        }
        return $reData;
    }
}