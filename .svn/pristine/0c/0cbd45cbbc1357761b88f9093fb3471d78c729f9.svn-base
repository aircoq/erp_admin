<?php


namespace app\common\model;


use think\Model;
use app\common\cache\Cache;

class GoodsDeveloper extends Model
{
    public function getDeveloperAttr($value, $data)
    {
        $user = Cache::store('user')->getOneUser($data['developer_id']);
        return isset($user['realname']) && $user['realname'] ? ($user['realname'] . '[' . $user['job_number'] . ']') : '';
    }

    public function getDesignerMasterTxtAttr($value, $data)
    {
        $user = Cache::store('user')->getOneUser($data['designer_master']);
        return isset($user['realname']) && $user['realname'] ? ($user['realname'] . '[' . $user['job_number'] . ']') : '';
    }

    public function getGrapherTxtAttr($value, $data)
    {
        $user = Cache::store('user')->getOneUser($data['grapher']);
        return isset($user['realname']) && $user['realname'] ? ($user['realname'] . '[' . $user['job_number'] . ']') : '';
    }

    public function getTranslatorTxtAttr($value, $data)
    {
        $aTranslator = json_decode($data['translator'], true);
        $uid = [];
        foreach ($aTranslator as $row){
            $uid[] = $row['translator'];
        }
        $uid = array_unique($uid);
        $result = [];
        foreach ($uid as $id) {
            $user = Cache::store('user')->getOneUser($id);
            $name = isset($user['realname']) && $user['realname'] ? ($user['realname'] . '[' . $user['job_number'] . ']') : '';
            if ($name) {
                $result[] = $name;
            }
        }
        return implode(',', $result);
    }
}