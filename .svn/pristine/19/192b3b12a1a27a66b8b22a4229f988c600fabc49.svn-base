<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-6-13
 * Time: 下午2:09
 */

namespace app\publish\queue;


use app\common\cache\Cache;
use app\common\service\SwooleQueueJob;
use app\publish\service\ProductDownloadService;
use think\Exception;

class PublishProductDownloadQueue extends SwooleQueueJob
{
    private $cacheDriver=null;
    const PRIORITY_HEIGHT = 10;
    public static function swooleTaskMaxNumber():int
    {
        return 3;
    }

    public function getName(): string
    {
        return '刊登商品全部导出队列';
    }

    public function getDesc(): string
    {
        return '刊登商品全部导出队列';
    }

    public function getAuthor(): string
    {
        return 'joy';
    }
    public function init()
    {
        $this->cacheDriver = Cache::store('PublishProductDownload');
    }

    public function execute()
    {
        set_time_limit(0);
        try{
            $params = $this->params;

            if($params){
                $channel_id = $id = $fields = $file_name = null;
                if(isset($params['channel_id']) && $params['channel_id']){
                    $channel_id = $params['channel_id'];
                }
                if(isset($params['apply_id']) && $params['apply_id']){
                    $id = $params['apply_id'];
                }

                if(isset($params['fields']) && $params['fields']){
                    $fields = $params['fields'];
                    $fields = json_decode($fields,true);
                }
                if(isset($params['file_name']) && $params['file_name']){
                    $file_name = $params['file_name'];
                }

                if(isset($params['flag'])){
                    if($params['flag']=='joy'){
                        ProductDownloadService::publishDownload($params,$id,$file_name);
                    }elseif($params['flag']=='joy88'){
                        ProductDownloadService::publishDownloadByTime($params,$id,$file_name);
                    }

                }else{
                    if($channel_id && $id && $fields){
                        switch ($channel_id){
                            case 1:
                                ProductDownloadService::ebayDownload($id,$fields);
                                break;
                            case 2:
                                ProductDownloadService::amazonDownload($id,$fields);
                                break;
                            case 3:
                                ProductDownloadService::wishDownload($params,$channel_id,$id,$fields,$file_name);
                                break;
                            case 4:
                                ProductDownloadService::aliexpressDownload($id,$fields);
                                break;
                            default:
                                break;
                        }
                    }
                }
            }
        }catch (Exception $exp){
            throw new Exception("{$exp->getFile()};{$exp->getLine()};{$exp->getMessage()}");
        }
    }
    private function getFields($name){
        return $this->cacheDriver->getCacheData($name);
    }

}