<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 2017/8/24
 * Time: 9:31
 */

namespace app\publish\queue;
use app\common\exception\QueueException;
use app\publish\service\AliexpressTaskHelper;
use app\common\model\aliexpress\AliexpressAccount;
use app\common\service\SwooleQueueJob;
use app\common\model\aliexpress\AliexpressCategory;
use think\Exception;

class AliexpressAccountBrandQuque extends SwooleQueueJob {
    protected static $priority=self::PRIORITY_HEIGHT;

    protected $failExpire = 600;

    protected $maxFailPushCount = 3;

    public static function swooleTaskMaxNumber():int
    {
        return 30;
    }

	public function getName():string
	{
		return '速卖通商户授权品牌获取';
	}
	public function getDesc():string
	{
		return '速卖通商户授权品牌获取';
	}
	public function getAuthor():string
	{
		return 'hao';
	}

	public  function execute()
	{
		set_time_limit(0);
		try{
			$params = $this->params;
			if($params)
			{
			    $params = explode('|', $params);

			    $account_id = $params[0];
			    $auth_category_id = $params[1];

			    $accountModel = new AliexpressAccount();
			    $account = $accountModel->where('id','=',$account_id)->find();
			    if(!$account) {
			        return;
                }
                $account = $account->toArray();
                $categorys = self::getChildsByPid($auth_category_id);

                $categorys?$this->getBrandsByCategory($account,$categorys):'';
			}

			return true;
		}catch (Exception $exp){
			throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
		}catch (\Throwable $exp){
            throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
	}


    private function getBrandsByCategory($config,$categorys){
        try{
            $service = new AliexpressTaskHelper();
            foreach ($categorys as $category){
                $service->getAeBrandAttribute($config,$category);
            }
        }catch (\Throwable $exp){
            throw new TaskException($exp->getMessage());
        }
    }
    /**
     * 获取所有子分类
     * @param $cate
     * @param $pid
     * @return array
     */
    public static function getChildsByPid($pid,&$return=[],&$leafNodes=[])
    {
        $model = new AliexpressCategory;
        $categorys = $model->field('category_id,category_pid,category_isleaf,category_name_zh')->where('category_pid',$pid)->select();
        if($categorys){
            foreach ($categorys as $category){
                $category = $category->toArray();
                if($category['category_isleaf']){
                    $leafNodes[]=$category;
                }
                $return[] = $category;
                self::getChildsByPid($category['category_id'],$return,$leafNodes);
            }
        }
        return $leafNodes;
    }
}