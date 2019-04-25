<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 17-12-28
 * Time: 下午1:50
 */

namespace app\listing\queue;


use app\common\cache\Cache;
use app\common\exception\QueueException;
use app\common\model\wish\WishWaitUploadProductInfo;
use app\common\service\SwooleQueueJob;
use service\wish\WishApi;
use think\Db;

class WishExpressQueue extends SwooleQueueJob
{
    protected static $priority = self::PRIORITY_MIDDLE;

    public static function swooleTaskMaxNumber():int
    {
        return 10;
    }

    public function getName(): string {
        return 'wish express队列';
    }

    public function getDesc(): string {
        return 'wish express队列';
    }

    public function getAuthor(): string {
        return 'joy';
    }

    public function execute()
    {
        $params = $this->params;
        if($params)
        {
            if(isset($params['account_id']) && isset($params['product_id']))
            {
                $account_id = $params['account_id'];
                $product_id =$params['product_id'];
                $config = Cache::store('WishAccount')->getAccount($account_id);

                if($config)
                {
                    $api = WishApi::instance($config)->loader('Product');
                    $response = $api->getAllShipping(['id'=>$product_id,'access_token'=>$config['access_token']]);
                    //print_r($response);

                    if($response['state']==true && $response['code']==0)
                    {
                        $shipping_prices = $response['data']['ProductCountryAllShipping']['shipping_prices'];
                        $data['all_country_shipping']= json_encode($shipping_prices);
                        $data['shipping_status']=1;
                        self::updateCountryAllShipping($data, ['product_id'=>$product_id]);
                    }
                }else{
                    throw new QueueException("wish帐号[".$account_id."]缓存不存在");
                }
            }else{
                throw new QueueException("数据不合法");
            }
        }
    }
    /**
     * 更新产品所有的国家物流设置
     * @param array $data
     * @param array $where
     */
    private static function updateCountryAllShipping($data,$where)
    {
        Db::startTrans();
        try {
            (new WishWaitUploadProductInfo())->isUpdate(true)->allowField(true)->save($data, $where);
            Db::commit();
        } catch (\Exception $exp) {
            Db::rollback();
            throw new QueueException($exp->getMessage());
        }
    }
}