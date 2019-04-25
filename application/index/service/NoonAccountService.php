<?php
namespace app\index\service;

use app\common\cache\Cache;
use app\common\exception\JsonErrorException;
use app\common\model\noon\NoonAccount as noonAccountModel;
use app\common\service\ChannelAccountConst;
use app\common\service\Common as CommonService;
use think\Db;
use think\Exception;

/**
 * @desc Noon账号管理
 */
class NoonAccountService
{
    public function save(array $data = [])
    {
        $ret = [
            'msg' => '',
            'code' => '',
        ];
        $noonAccount = new noonAccountModel();
        $re = $noonAccount->where(['code' => trim($data['code'])])->find();
        if ($re) {
            $ret['msg'] = '账户名重复';
            $ret['code'] = 400;
            return $ret;
        }
        \app\index\service\BasicAccountService::isHasCode(ChannelAccountConst::channel_Noon, $data['code'], $data['site']);

        //启动事务
        Db::startTrans();
        try {
            $data['create_time'] = time();
            //获取操作人信息
            $noonAccount->allowField(true)->isUpdate(false)->save($data);

            Db::commit();
            //新增缓存
            Cache::store('NoonAccount')->setTableRecord($noonAccount->id);
            $ret = [
                'msg' => '新增成功',
                'code' => 200,
                'id' => $noonAccount->id,
            ];
            return $ret;
        } catch (\Exception $e) {
            Db::rollback();
            $ret = [
                'msg' => '新增失败',
                'code' => 500,
            ];
            return $ret;
        }
    }
}
