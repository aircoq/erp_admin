<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 2017/8/24
 * Time: 9:31
 */

namespace app\publish\queue;

use app\common\exception\QueueException;
use app\common\service\SwooleQueueJob;
use think\Exception;
use app\common\model\aliexpress\AliexpressProduct;
use app\common\cache\Cache;
use app\common\model\aliexpress\AliexpressAccount;
use app\publish\service\AliexpressService;
use app\common\model\aliexpress\AliexpressRenewexpireLog;
use app\common\exception\TaskException;


class AliexpressDelayWsValidNumQueue extends SwooleQueueJob {
    protected static $priority=self::PRIORITY_HEIGHT;

    protected $failExpire = 600;

    protected $maxFailPushCount = 3;

    public static function swooleTaskMaxNumber():int
    {
        return 40;
    }

	public function getName():string
	{
		return '速卖通延长商品有效天数';
	}
	public function getDesc():string
	{
		return '速卖通延长商品有效天数';
	}
	public function getAuthor():string
	{
		return 'hao';
	}

	public  function execute()
	{
		set_time_limit(0);
		try{
			$id = $this->params;
			if(!$id) {
                return;
            }


            $model = new AliexpressProduct();

			$product = $model->where(['id' => $id])->field('product_id, account_id,ws_valid_num')->find();

            if(!$product) {
                return;
            }

            $productId = $product['product_id'];

            if(!$productId) {
                return;
            }

            $product = $product->toArray();
            $accounts = (new AliexpressAccount)->field('id, code, refresh_token, access_token, client_secret, client_id')->where(['id' => $product['account_id']])->find();
			if(!$accounts) {
			    return;
            }

            $logModel = new AliexpressRenewexpireLog();
            $log = $logModel->field('id')->where('product_id','=',$productId)->find();

            $time = time();

            if(!$log) {

                $data = [
                    'product_id' => $productId,
                    'create_time' => $time
                ];

                $logId = $logModel->insertGetId($data);
            }

            $logId = $log ? $log['id'] : $logId;

            $account = $accounts->toArray();

			$post = [
                'module' => 'product',
                'class' => 'Product',
                'action' => 'renewexpire',
                'productId' => $productId
            ];

            $params = array_merge($account ,$post);
            $response = AliexpressService::execute(snakeArray($params));

            $message = '';
            if(isset($response['modifyCount']) && $response['modifyCount']) {


                $ws_offline_date = $time+86400*$product['ws_valid_num'];

                $expire_day = $product['ws_valid_num']-1;
                $model->update(['ws_offline_date' => $ws_offline_date, 'expire_day' => $expire_day], ['id' => $id]);

                $status = 1;

            }else{
                $status= 2;
                if(isset($response['error_code'])) {
                    $message = json_encode($response);
                }
                if(isset($response['errorDetails'])) {
                    $message = json_encode(['error_message'=>'','error_code'=>$response['errorDetails']['json'][0]]);
                    $status=-1;
                }
            }

            $logModel->update(['status' => $status, 'message' => $message, 'run_time' => $time], ['id' => $logId]);


            return true;
		}catch(Exception $exp){
            throw new TaskException("File:{$exp->getFile()}Line:{$exp->getLine()}Message:{$exp->getMessage()}");
        }catch (\Throwable $exp){
            throw new TaskException("File:{$exp->getFile()}Line:{$exp->getLine()}Message:{$exp->getMessage()}");
        }
	}
}