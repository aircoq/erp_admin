<?php


namespace app\index\service;

use app\common\model\SoftwareVersion as ModelSoftwareVersion;
use app\common\model\Software as ModelSoftware;
use app\index\validate\SoftwareVersion as ValidateSoftVersion;
use app\common\service\Common;
use think\Exception;
use think\Db;

class SoftwareVersion
{

    public function sendVersion($id, $param)
    {
        if (!isset($param['file']) || !$param['file']) {
            throw new Exception('附件不能为空!');
        }
        if (!isset($param['version']) || !$param['version']) {
            throw new Exception('版本号不能为空!');
        }
        unset($param['id']);
        $param['version'] = trim($param['version']);
        $this->checkVersion($id, $param['version']);
        $dir = 'install';
        if (!is_dir($dir)) {
            mkdir($dir, true);
        }
        $base_dir = ROOT_PATH . 'public' . DS;
        $filename = $dir . "/" . $param['version']."(".date('ymdhis').rand(0,99).")" . ".zip";
        $savePath = $base_dir.$filename;

        $file = json_decode($param['file'],true);
        $file = $file[0];
        $start = strpos($file['file'], ',');
        $content = substr($file['file'], $start + 1);
        $ModelSoftware = new ModelSoftware();
        $old = $ModelSoftware->where('id',$id)
            ->field('id as software_id,software_type,version,remark,status,upgrade_address,md5,creator_id,create_time')
            ->find();
        if(!$old){
            throw new Exception('当前软件信息不存在');
        }
        try {
            file_put_contents($savePath, base64_decode(str_replace(" ", "+", $content)));
            $userInfo = Common::getUserInfo();
            $data = [
                'software_id' => $id,
                'version' => $param['version'],
                'creator_id' => $userInfo['user_id'],
                'create_time' => time(),
                'upgrade_address' => $filename
            ];
            $data = array_merge($data, $param);
            $validate = new ValidateSoftVersion();
            $flag = $validate->scene('insert')->check($data);
            if ($flag === false) {
                throw new Exception($validate->getError());
            }
            Db::startTrans();
            try {
                $ModelSoftwareVersion = new ModelSoftwareVersion();
                $ModelSoftwareVersion->allowField(true)->isUpdate(false)->save($old->toArray());
                unset($data['creator_id']);
                unset($data['create_time']);
                unset($data['software_id']);
                $ModelSoftware->allowField(true)->isUpdate(true)->save($data, ['id' => $id]);
                Db::commit();
                return ['message' => '添加成功'];
            } catch (Exception $e) {
                Db::rollback();
                throw $e;
            }
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    private function checkVersion($softwareId, $version)
    {
        $SoftwareService = new SoftwareService();
        $info = $SoftwareService->read($softwareId);
        if ($info['version'] == $version) {
            throw new Exception('该软件版本号已存在!');
        }
        $ModelSoftwareVersion = new ModelSoftwareVersion();
        $count = $ModelSoftwareVersion->where('software_id', $softwareId)->where('version', $version)->count();
        if ($count) {
            throw new Exception('该软件版本号已存在!');
        }

    }

    public function getVersion($softwareId)
    {
        $ModelSoftwareVersion = new ModelSoftwareVersion();
        $ret = $ModelSoftwareVersion
            ->where('software_id', $softwareId)
            ->field('id,software_id,version,md5,remark,status,upgrade_address,create_time,creator_id')
            ->order('id desc')
            ->select();
        return $this->lists($ret);
    }

    public function lists($ret)
    {
        $result = [];
        foreach ($ret as $list) {
            $row = $this->row($list);
            $result[] = $row;
        }
        return $result;
    }

    public function row($list)
    {
        $result = [];
        $result['id'] = $list['id'];
        $result['version'] = $list['version'];
        $result['software_type'] = $list->software->software_type;
        $result['upgrade_address'] = $list['upgrade_address'];
        $result['creator_txt'] = $list['creator_txt'];
        $result['creator_id'] = $list['creator_id'];
        $result['create_time'] = $list['create_time'];
        $result['create_time_txt'] = $list['create_time_txt'];
        $result['department_name'] = $list['department_name'];
        $result['md5'] = $list['md5'];
        $result['remark'] = $list['remark'];
        $result['status'] = $list['status'];
        return $result;
    }
}