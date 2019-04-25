<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 2017/8/30
 * Time: 14:26
 */

namespace app\listing\queue;
use app\common\service\SwooleQueueJob;
use app\listing\service\AliexpressListingHelper;
use app\publish\queue\WishQueue;
use app\common\exception\QueueException;
use think\Exception;
use think\exception\ErrorException;
use think\exception\PDOException;
class AliexpressOfflineProductQueue extends  SwooleQueueJob {
	public function getName(): string {
		return '速卖通商品下架(队列)';
	}

	public function getDesc(): string {
		return '速卖通商品下架(队列)';
	}

	public function getAuthor(): string {
		return 'joy';
	}

	public function execute() {
		try {
			$queue = $this->params;
			if ( $queue ) {
				( new AliexpressListingHelper )->offlineAeProduct( $queue );
			}
		} catch ( QueueException $exp ) {
			throw  new QueueException( $exp->getFile() . $exp->getLine() . $exp->getMessage() );
		} catch ( ErrorException $exp ) {
			throw  new ErrorException( $exp->getFile() . $exp->getLine() . $exp->getMessage() );
		} catch ( \PDOException $exp ) {
			throw  new PDOException( $exp->getFile() . $exp->getLine() . $exp->getMessage() );
		} catch ( Exception $exp ) {
			throw  new Exception( $exp->getFile() . $exp->getLine() . $exp->getMessage() );
		}
	}
}