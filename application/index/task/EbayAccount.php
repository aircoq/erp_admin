<?php
namespace app\index\task;

use app\index\service\AbsTasker;
use app\common\cache\Cache;
use service\ebay\EbayAccountApi;
use app\common\model\ebay\EbayAccount as EbayAccountModel;
use think\Exception;
use app\common\exception\TaskException;

class EbayAccount extends AbsTasker
{
    public function getName()
    {
        return "Ebay 账号/店铺评分";
    }

    public function getDesc()
    {
        return "Ebay 账号/店铺评分";
    }

    public function getCreator()
    {
        return "TanBin";
    }

    public function getParamRule()
    {
        return [
            'account_id|账号id'=>'',
        ];
    }

    public function execute()
    {
        $account_id = $this->getData('account_id','');
        $accountList = Cache::store('EbayAccount')->getTableRecord($account_id);
        if(!isNumericArray($accountList)){
            $accountList = [$accountList];
        }
        foreach ($accountList as $k => $v) {
            if($v['is_invalid'] != 1){
                continue;
            }
            if (empty($v['dev_id']) || empty($v['app_id']) || empty($v['cert_id']) || empty($v['token'])) {
                continue;
            }
            $data = [
                'account_id' => $v['id'],
                'account_name' => $v['account_name'],
                'userToken' => $v['token'],
                'devID'=>$v['dev_id'],
                'appID'=>$v['app_id'],
                'certID'=>$v['cert_id'],
            ] ;
            $res = $this->downAccountInfo($data);
            sleep(3);
        }
        return true;
    }
    
    /**
     * 下载账号信息
     * @param array $data
     * @throws TaskException
     */
    function downAccountInfo($data = []){
        try {
            $ebay = new EbayAccountApi($data);
            $result = $ebay->getUser($data['account_name']);
            if($result){
                $update = [
                    'email' => param($result, 'Email'),
                    'feedback_score' => param($result, 'FeedbackScore' ,0),
                    'positive_feedback_percent' => param($result, 'PositiveFeedbackPercent' ,0),
                    'feedback_rating_star' => param($result, 'FeedbackRatingStar'),
                    'register_time' => strtotime(param($result, 'RegistrationDate',0)),
                ];
                $res = EbayAccountModel::update($update,['id'=>$data['account_id']]);     
            }
        } catch (Exception $ex) {
            throw new TaskException($ex->getMessage());
        }
    }
    
}