<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 2017/8/30
 * Time: 14:28
 */

namespace app\listing\queue;
use app\common\exception\TaskException;
use app\common\service\SwooleQueueJob;
use app\listing\service\AliexpressListingHelper;
use app\publish\queue\WishQueue;
use think\Db;
use app\common\model\aliexpress\AliexpressProduct;
use think\Exception;

class AliexpressRsyncEditListingQueue extends  SwooleQueueJob{
	public function getName():string
	{
		return '速卖通修改编辑商品信息(队列)';
	}
	public function getDesc():string
	{
		return '速卖通修改编辑商品信息(队列)';
	}
	public function getAuthor():string
	{
		return 'joy';
	}

	public  function execute()
	{
		try{
			$queue = $this->params;
			if($queue)
			{
				$response = (new AliexpressListingHelper())->editAeProduct($queue);

				if(isset($response['success']) && $response['success'])
				{
					$data['lock_update']=0; //更新成功
					$data['update_message']= json_encode($response);
				}else{
					$data['lock_update']=2; //更新失败
					$data['update_message']= json_encode($response);
				}
				$where['product_id']=['=',$queue];
				Db::startTrans();
				try{
					$model = new AliexpressProduct;
					$model->isUpdate(true)->save($data, $where);
					Db::commit();
				}catch(Exception $exp) {
					Db::rollback();
					throw new Exception($exp->getFile().$exp->getLine().$exp->getMessage());
				}
			}
		}catch (Exception $exp){
			throw  new Exception($exp->getMessage().$exp->getFile().$exp->getLine());
		}
	}
}