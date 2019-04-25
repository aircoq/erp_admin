<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-6-12
 * Time: 上午11:51
 */

namespace app\publish\task;


use app\common\cache\Cache;
use app\common\model\aliexpress\AliexpressAccount;
use app\index\service\AbsTasker;
use app\publish\service\AliexpressTaskHelper;
use think\Db;
use think\Exception;
use app\common\model\aliexpress\AliexpressAccountPhotobank as AliexpressAccountPhotobankModel;
class AliexpressAccountPhotobank extends AbsTasker {
    public function getName()
    {
        return '速卖通账号图片银行信息';
    }

    public function getDesc()
    {
        return '速卖通账号图片银行信息';
    }

    public function getCreator()
    {
        return 'joy';
    }

    public function getParamRule()
    {
       return [];
    }

    public function execute()
    {
        set_time_limit(0);
        try {
            //$accounts = Cache::store('AliexpressAccount')->getAccounts();
            $accounts = AliexpressAccount::all();
            if ($accounts) {
                foreach ($accounts as $account) {
                    if ($account['is_invalid'] && $account['is_authorization']){
                        $this->getphotobankinfo($account);
                    }
                }
            }
        } catch (Exception $exp) {
            throw new Exception($exp->getMessage());
        }
    }

    private function getphotobankinfo($account){
        $account = is_object($account)?$account->toArray():$account;
        $response = AliexpressTaskHelper::getphotobankinfo($account);
        if(isset($response['capicity']) && isset($response['useage'])){
            $model = new  AliexpressAccountPhotobankModel();
            $response['left'] = $response['capicity'] - $response['useage'];
            $response['account_id']=$account['id'];
            $where['account_id']=['=',$account['id']];
            Db::startTrans();
            try{
                dump($account['id']);
                if($has = $model->where($where)->find()){
                    $model->allowField(true)->save($response,['id'=>$has['id']]);
                }else{
                    $model->allowField(true)->save($response);
                }
                Db::commit();
            }catch (Exception $exp){
                Db::rollback();
                throw new Exception($exp->getMessage());
            }
        }
    }
}