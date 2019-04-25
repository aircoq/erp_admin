<?php
/**
 * Created by PhpStorm.
 * User: wlw2533
 * Date: 18-5-29
 * Time: 上午10:20
 */

namespace app\publish\task;


use app\common\model\shopee\ShopeeAccount;
use app\index\service\AbsTasker;
use app\publish\helper\shopee\ShopeeHelper;
use think\Exception;

class ShopeeLogisticsTask extends AbsTasker
{
    public function getName()
    {
        return 'shopee物流';
    }

    public function getDesc()
    {
        return 'shopee物流';
    }

    public function getCreator()
    {
        return 'wlw2533';
    }

    public function getParamRule()
    {
       return [];
    }

    public function execute()
    {
        set_time_limit(0);
        try{
            $field = 'id,partner_id,shop_id,key';
            $where=[
                'shop_id' => ['neq',0],
                'key' => ['neq', ''],
                'status' => 1
            ];
            $accounts = ShopeeAccount::field($field)->where($where)->select();
            if(empty($accounts)) {
                throw new Exception('获取账号信息失败');
            }
            foreach ($accounts as $config){
                $res = (new ShopeeHelper())->syncLogistics($config['id']);
                if ($res !== true) {
                    throw new Exception($res);
                }
            }
        }catch (Exception $exp){
            throw new Exception("{$exp->getMessage()}");
        }
    }


}