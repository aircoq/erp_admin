<?php 
namespace app\publish\task;
/**
 *amazon被跟卖-账号名称抓取
 * Description of AmazonSellerNameHeelSale
 * @datetime 2019-1-31
 * @author hao
 */
 
use app\common\cache\Cache;
use app\common\exception\TaskException;
use app\publish\queue\AmazonUSSellerNameHeelSaleQueuer;
use app\publish\queue\AmazonCASellerNameHeelSaleQueuer;
use app\publish\queue\AmazonMXSellerNameHeelSaleQueuer;
use app\publish\queue\AmazonDESellerNameHeelSaleQueuer;
use app\publish\queue\AmazonESSellerNameHeelSaleQueuer;
use app\publish\queue\AmazonFRSellerNameHeelSaleQueuer;
use app\publish\queue\AmazonITSellerNameHeelSaleQueuer;
use app\publish\queue\AmazonUKSellerNameHeelSaleQueuer;
use app\publish\queue\AmazonJPSellerNameHeelSaleQueuer;
use app\common\service\UniqueQueuer;
use app\index\service\AbsTasker;
use app\common\model\amazon\AmazonSellerHeelSale as AmazonSellerHeelSaleModel;

class AmazonSellerNameHeelSale extends AbsTasker
{

    public function getName()
    {
        return "amazon被跟卖-账号名称抓取";
    }
    
    public function getDesc()
    {
        return "amazon被跟卖-账号名称抓取";
    }
    
    public function getCreator()
    {
        return "hao";
    }
    
    public function getParamRule()
    {
        return [];
    }


    public function execute()
    {
         set_time_limit(0);
         try{

            $page = 1;
            $pageSize = 100;

            do {

                $model = new AmazonSellerHeelSaleModel;
                $data = $model->alias('a')->field('a.id, a.seller_id, h.asin, m.site')->where('a.seller_name','=','')->join('amazon_heel_sale_complain h','a.heel_sale_complain_id = h.id', 'LEFT')->join('amazon_account m','m.id = h.account_id','LEFT')->page($page++,$pageSize)->select();

                if(!$data) {
                    return;
                }


                foreach ($data as $key => $val) {

                    $heelSaleInfo = $val->toArray();

                    if($heelSaleInfo && $heelSaleInfo['site']) {
                        $this->queuer($heelSaleInfo);
                    }
                }

            }while(count($data) == $pageSize);

            return true;
         }catch (\Throwable $exp){
             throw new TaskException($exp->getMessage());
         }
    }


    /**
     *加入队列
     *
     */
    public function queuer(array $heelSaleInfo) {


        //分站点进行推送消息
        switch($heelSaleInfo['site']) {
            case 'US':
                //加入获取店铺名称队列
                (new UniqueQueuer(AmazonUSSellerNameHeelSaleQueuer::class))->push($heelSaleInfo);
                break;
            case 'CA':
                //加入获取店铺名称队列
                (new UniqueQueuer(AmazonCASellerNameHeelSaleQueuer::class))->push($heelSaleInfo);
                break;
            case 'MX':
                //加入获取店铺名称队列
                (new UniqueQueuer(AmazonMXSellerNameHeelSaleQueuer::class))->push($heelSaleInfo);
                break;
            case 'DE':
                //加入获取店铺名称队列
                (new UniqueQueuer(AmazonDESellerNameHeelSaleQueuer::class))->push($heelSaleInfo);
                break;
            case 'ES':
                //加入获取店铺名称队列
                (new UniqueQueuer(AmazonESSellerNameHeelSaleQueuer::class))->push($heelSaleInfo);
                break;
            case 'FR':
                //加入获取店铺名称队列
                (new UniqueQueuer(AmazonFRSellerNameHeelSaleQueuer::class))->push($heelSaleInfo);
                break;
            case 'IT':
                //加入获取店铺名称队列
                (new UniqueQueuer(AmazonITSellerNameHeelSaleQueuer::class))->push($heelSaleInfo);
                break;
            case 'UK':
                //加入获取店铺名称队列
                (new UniqueQueuer(AmazonUKSellerNameHeelSaleQueuer::class))->push($heelSaleInfo);
                break;
            case 'JP':
                //加入获取店铺名称队列
                (new UniqueQueuer(AmazonJPSellerNameHeelSaleQueuer::class))->push($heelSaleInfo);
                break;
        }

    }
}