<?php

namespace app\common\model;

use app\common\service\Common;
use app\index\service\AccountApplyService;
use think\Cache;
use think\Model;

/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/12/18
 * Time: 11:47
 */
class ChannelUserAccountMapLog extends Model
{
    const add = 0;
    const update = 1;
    const delete = 2;
    const user = 3;


    const allMeg = [
        'seller_id' => '销售人员',
        'warehouse_type' =>  '仓库类型',
        'customer_id' =>      '客服人员',
    ];

    public function isHas($data)
    {
        $where = [
            'channel_id' => $data['channel_id'],
            'account_id' => $data['account_id'],
        ];
        return $this->where($where)->order('id desc')->find();
    }

    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }

    public static function getLog($channelId,$accountId)
    {
        $where['channel_id'] = $channelId;
        $where['account_id'] = $accountId;
        $list = (new ChannelUserAccountMapLog())->where($where)->order('id desc')->select();
        foreach ($list as &$v) {
            $v['remark'] = json_decode($v['remark'], true);
        }
        return $list;
    }

    /**
     * 新增日志
     * @param $account_id
     * @param $type
     * @param $newData
     * @param  $oldData
     * @param  $msg
     * @return false|int
     */
    public static function addLog($type,$data, $infoList, $msg = '')
    {

        $remark = '';
        $oldData = (new ChannelUserAccountMapLog())->isHas($data);
        if($oldData){
            $oldData = json_decode($oldData['data'],true);
        }
        $newData = [
            'customer_id' => $data['customer_id'],
        ];
        $newData['info'] = $infoList;
        switch ($type) {
            case self::add:
                $remark = self::getRemark($newData);
                break;
            case self::update:
                $remark = self::getRemark($newData, $oldData);
                break;
            case self::delete:
                $remark[] = '删除资料';
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
        $userInfo = Common::getUserInfo();
        $temp['account_id'] = $data['account_id'];
        $temp['channel_id'] = $data['channel_id'];
        $temp['type'] = $type;
        $temp['operator_id'] = $userInfo['user_id'] ?? 0;
        $temp['operator'] = $userInfo['realname'] ?? '';

        $temp['create_time'] = time();
        return (new ChannelUserAccountMapLog())->allowField(true)->isUpdate(false)->save($temp);
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

    public static function getRemark($newData, $oldData=[])
    {
        $remarks = [];
        foreach ($newData as $key => $new) {
            if($key == 'info'){
                self::getInfoRemark($newData[$key],($oldData[$key] ?? []),$remarks);
            }else {
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
        }
        return $remarks;
    }

    public static function getInfoRemark($newData, $oldData,&$remark)
    {
        $oldUserId = $oldData ? array_column($oldData,'seller_id') : [];
        $newUserId = $newData ?  array_column($newData,'seller_id') : [];
        $oldUser = self::objToArray($oldData);
        //删除
        foreach ($oldData as $k=>$v){
            if(!in_array($v['seller_id'],$newUserId)) {
                $remark[] = '[删除销售]'.self::getUserName($v['seller_id']).'[仓库类型]:' . self::getValue('warehouse_type',$v['warehouse_type']);
            }
        }
        //修改或者新增
        foreach ($newData as $k=>$v){
            if(in_array($v['seller_id'],$oldUserId)){
                if($v['warehouse_type'] != $oldUser[$v['seller_id']]['warehouse_type']){
                    $remark[] = self::getUserName($v['seller_id']).'[修改仓库类型]:' .self::getValue('warehouse_type',$oldUser[$v['seller_id']]['warehouse_type']) . '-->' . self::getValue('warehouse_type',$v['warehouse_type']);
                }
            }else{
                $remark[] = '[新增销售]'.self::getUserName($v['seller_id']).'[仓库类型]:' . self::getValue('warehouse_type',$v['warehouse_type']);
            }
        }
    }

    public static function objToArray($data, $key='seller_id')
    {
        $reData = [];
        foreach ($data as $k=>$v){
            $reData[$v[$key]] = $v;
        }
        return $reData;
    }

    public static function getValue($key, $vlave)
    {
        switch ($key) {
            case 'warehouse_type':
                $allTpe = ['全部','本地','海外'];
                $msg = $allTpe[$vlave] ?? '全部';
                break;
            case 'seller_id':
                $msg = self::getUserName($vlave);
                break;
            case 'customer_id':
                $msg = self::getUserName($vlave);
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


    
}