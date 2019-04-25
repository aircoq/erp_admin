<?php
namespace app\publish\controller;
use app\common\exception\JsonErrorException;
use app\common\model\amazon\AmazonCategory;
use app\publish\service\AmazonCategoryHelper;
use think\Request;
use think\Response;
use think\Cache;
use think\Db;
use think\Exception;
use think\Validate;
use app\common\controller\Base;
use app\goods\service\GoodsHelp;
use app\common\service\Common;
use app\publish\service\AmazonXsdToXmlService;
use app\publish\service\AmazonPublishHelper;
use app\goods\service\GoodsImage;

/**
 * @module 亚马逊刊登产品
 * @title Amazon刊登任务
 * @author hzy
 * @url /publish/amazon-task
 * Class AmazonTask
 * @package app\publish\controller
 */

class AmazonTask extends Base{


    /**
     * @title 上传产品信息
     * @method get
     * @url /publish/amazon-task/upload-product
     * @return 返回一个JSON数组
     */
    public function uploadProduct(Request $request){
        try{
            $account_id = $request->param('account_id');
            if(!$account_id){
                throw new Exception("请输入一个帐号");
            }
            $params = ['account_id'=>$account_id,'type' => '_POST_PRODUCT_DATA_'];
            return (new \app\publish\queue\AmazonPublishQueuer($params))->execute();
        }catch (\Exception $e){
            return json($e->getMessage(),400);
        }

    }


    /**
     * @title 上传关系
     * @method get
     * @url /publish/amazon-task/upload-relation
     * @return 返回一个JSON数组
     */
    public function uploadProductRelation(Request $request){
        try{
            $account_id = $request->param('account_id');
            if(!$account_id){
                throw new Exception("请输入一个帐号");
            }
            $params = ['account_id'=>$account_id,'type' => '_POST_PRODUCT_RELATIONSHIP_DATA_'];


            return (new \app\publish\queue\AmazonPublishQueuer($params))->execute();
        }catch (\Exception $e){
            return json($e->getMessage(),400);
        }
    }


    /**
     * @title 上传产品价格
     * @method get
     * @url /publish/amazon-task/upload-price
     * @return 返回一个JSON数组
     */
    public function uploadPrice(Request $request){
        try{
            $account_id = $request->param('account_id');
            if(!$account_id){
                throw new Exception("请输入一个帐号");
            }
            $params = ['account_id'=>$account_id,'type' => '_POST_PRODUCT_PRICING_DATA_'];
            return (new \app\publish\queue\AmazonPublishQueuer($params))->execute();
        }catch (\Exception $e){
            return json($e->getMessage(),400);
        }

    }


    /**
     * @title 上传产品数量
     * @method get
     * @url /publish/amazon-task/upload-quantity
     * @return 返回一个JSON数组
     */
    public function uploadQuantity(Request $request){
        try{
            $account_id = $request->param('account_id');
            if(!$account_id){
                throw new Exception("请输入一个帐号");
            }
            $params = ['account_id'=>$account_id,'type' => '_POST_INVENTORY_AVAILABILITY_DATA_'];
            return (new \app\publish\queue\AmazonPublishQueuer($params))->execute();
        }catch (\Exception $e){
            return json($e->getMessage(),400);
        }
    }


    /**
     * @title 上传产品图片
     * @method get
     * @url /publish/amazon-task/upload-images
     * @return 返回一个JSON数组
     */
    public function uploadImages(Request $request){
        try{
            $account_id = $request->param('account_id');
            if(!$account_id){
                throw new Exception("请输入一个帐号");
            }

            $params = ['account_id'=>$account_id,'type' => '_POST_PRODUCT_IMAGE_DATA_'];
            return (new \app\publish\queue\AmazonPublishQueuer($params))->execute();

        }catch (\Exception $e){
            return json($e->getMessage(),400);
        }
    }


    /**
     * @title 获取上传结果
     * @method get
     * @url /publish/amazon-task/get-submission
     * @return 返回一个JSON数组
     */
    public function getSubmissionResult(Request $request){
        try{
            $account_id = $request->param('account_id');
            $submissionId = $request->param('submission_id','');
            $id = $request->param('id');
            $type = 'submission';
            $subType = $request->param('sub_type');

            if(!$account_id){
                throw new Exception("请输入一个帐号");
            }
            $params = ['account_id'=>$account_id,'type' => $type,'submission_id' => $submissionId,'id' => $id,'sub_type' => $subType];
            return (new \app\publish\queue\AmazonPublishQueuer($params))->execute();
        }catch (\Exception $e){
            return json($e->getMessage(),400);
        }
    }

}