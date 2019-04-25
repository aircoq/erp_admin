<?php

namespace app\common\model;

use app\common\service\Common;
use app\index\service\AccountApplyService;
use think\Cache;
use think\Model;

/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/12/14
 * Time: 11:47
 */
class ServerLog extends Model
{
    const add = 0;
    const update = 1;
    const delete = 2;
    const user = 3;
    const allMeg = [
        'name' => '服务器名称',
        'ip' => 'ip地址',
        'mac' => 'mac地址',
        'visit_ip' => '访问IP',
        'domain' => '域名',
        'type' => '类型',
        'admin' => '管理员',
        'password' => '密码',
        'creator_id' => '创建者',
        'updater_id' => '更新者',
        'create_time' => '创建时间',
        'update_time' => '更新时间',
        'reporting_time' => '上报时间',
        'reporting_cycle' => '上报周期（分钟）',
        'network_ip' => '外网ip',
        'ip_type' => '外网类型',
        'user_agent' => '用户代理信息',
        'status' => '状态',
        'proxy' => '代理',
        'proxy_agent' => '代理协议',
        'proxy_port' => '代理端口',
        'proxy_ip' => '代理IP',
        'proxy_user_name' => '代理用户名',
        'proxy_user_password' => '代理密码',
        'thread_quantity' => '线程数',
        'gateway' => '网关',
    ];


    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }

    public static function getLog($id, $page = 1, $pageSize = 50)
    {
        $list = (new ServerLog())->where('server_id', $id)->page($page, $pageSize)->order('id desc')->select();
        foreach ($list as &$v) {
            $v['remark'] = json_decode($v['remark'], true);
        }
        return $list;
    }

    public static function type($param)
    {
        $data = [
            1 => '软件回调',
            2 => '更换负责人',
            3 => '再次请求',
            4 => '更换服务器',
            5 => '平台账号绑定',
            6 => '系统矫正部门',
            7 => '批量操作',
            8 => '新增负责人',

        ];
        $type = array_search($param, $data);
        return $type;
    }


    /**
     * 新增日志
     * @param $server_id
     * @param $type
     * @param $newData
     * @param  $oldData
     * @param  $msg
     * @return false|int
     */
    public static function addLog($server_id, $type, $newData = [], $oldData = [], $msg = [], $userInfo = [])
    {

        if (!$userInfo) {
            $userInfo = Common::getUserInfo();
        }
        $temp['server_id'] = $server_id;
        $temp['type'] = $type;
        $temp['operator_id'] = $userInfo['user_id'] ?? 0;
        $str = $userInfo['realname'];
        if (preg_match_all("/(?:\[)(.*)(?:\])/i", $str, $operator_type)) {
            $result = explode(']', $str);
            $temp['operator'] = $result[1];
            $temp['operator_type'] = self::type($operator_type[1][0]);
        } else {
            $temp['operator'] = $userInfo['realname'];
            $temp['operator_type'] = 0;
        }
        $remark = [];
        unset($newData['update_time']);
        unset($newData['updater_id']);
        unset($newData['create_time']);
        unset($newData['creator_id']);
        switch ($type) {
            case self::add:
                $remark = self::getRemark($newData, $oldData);
                break;
            case self::update:
                $remark = self::getRemark($newData, $oldData);
                break;
            case self::delete:
                $remark[] = '';
                break;
            case self::user:
                $remark = self::getRemarkUser($newData, $oldData);
                break;
        }
        if ($msg) {
            $remark[] = $msg;
        }
        $temp['remark'] = json_encode($remark, JSON_UNESCAPED_UNICODE);
        $temp['data'] = json_encode($newData, JSON_UNESCAPED_UNICODE);
        $temp['create_time'] = time();
        return (new ServerLog())->allowField(true)->isUpdate(false)->save($temp);
    }


    public static function getRemarkUser($newIds = [], $oldIds = [])
    {
        $remarks = [];
        foreach ($newIds as $id) {
            if (!in_array($id, $oldIds)) {
                $remarks[] = '【新增成员】' . self::getUserName($id);
            }
        }
        foreach ($oldIds as $id) {
            if (!in_array($id, $newIds)) {
                $remarks[] = '【移除成员】' . self::getUserName($id);
            }
        }
        return $remarks;
    }

    public static function getUserName($userId)
    {
        $userInfo = \app\common\cache\Cache::store('User')->getOneUser($userId);
        return $userInfo['realname'] ?? '';
    }

    public static function getRemark($newData, $oldData)
    {
        $remarks = [];
        foreach ($newData as $key => $new) {
            $remark = '';
            if (isset($oldData[$key])) {
                if ($oldData[$key] != $newData[$key]) {
                    $remark .= '修改:' . self::getValue($key, $oldData[$key]) . '-->' . self::getValue($key, $newData[$key]);
                }
            } elseif ($new) {
                $remark .= '增加:' . self::getValue($key, $new);
            }
            if ($remark) {
                $remarks[] = "【" . self::allMeg[$key] . "】" . $remark;
            }

        }
        return $remarks;
    }

    public static function getValue($key, $vlave)
    {
        switch ($key) {
            case 'status':
                $allStatus = ['启用', '停用'];
                $msg = $allStatus[$vlave] ?? '';
                break;
            case 'ip_type':
                $msg = ExtranetType::getName($vlave);
                break;
            case 'type':
                $allStatus = ['虚拟机', '云服务', '超级浏览器'];
                $msg = $allStatus[$vlave] ?? '';
                break;
            case 'fulfill_time':
                $msg = self::showTime($vlave);
                break;
            case 'initiate_time':
                $msg = self::showTime($vlave);
                break;
            default:
                $msg = $vlave;
        }
        return $msg;
    }

    public static function showTime($time)
    {
        $msg = 0;
        if ($time) {
            $msg = date('Y-m-d H:i:s', $time);
        }
        return $msg;
    }

    /**
     * 将数组里面带有中文的字串用urlencode转换格式返回
     *
     * @param   array $arr 数组
     * @return  array
     */
    public static function toUrlencode($arr)
    {
        $temp = "[";
        foreach ($arr as $v) {
            $temp .= '"' . $v . '"' . ',';
        }
        $temp = trim($temp, ',');
        $temp .= "]";
        return $temp;
    }
}