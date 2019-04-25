<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-6-13
 * Time: 下午2:12
 */

namespace app\publish\service;


use app\common\cache\Cache;
use app\common\exception\JsonErrorException;
use app\common\model\aliexpress\AliexpressProduct;
use app\common\model\amazon\AmazonPublishProduct;
use app\common\model\ebay\EbayListing;
use app\common\model\Goods;
use app\common\model\GoodsPublishMap;
use app\common\model\GoodsSku;
use app\common\model\GoodsSkuAlias;
use app\common\model\joom\JoomProduct;
use app\common\model\LogExportDownloadFiles;
use app\common\model\pandao\PandaoProduct;
use app\common\model\RoleUser;
use app\common\model\wish\WishDownloadField;
use app\common\model\wish\WishWaitUploadProduct;
use app\common\model\wish\WishWaitUploadProductVariant;
use app\common\service\Common;
use app\common\service\CommonQueuer;
use app\goods\service\GoodsPublishMapService;
use app\index\service\DownloadFileService;
use app\index\service\Role;
use app\index\service\User;
use app\publish\queue\PublishProductDownloadQueue;
use app\report\model\ReportExportFiles;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use Box\Spout\Reader\ReaderFactory;
use Box\Spout\Writer\WriterFactory;
use Box\Spout\Common\Type;
use Box\Spout\Writer\Style\StyleBuilder;
use think\Model;
use app\goods\service\GoodsHelp;
class ProductDownloadService
{
    private static $channel_id=0;
    private static $cacheName;
    private static $wishHeader = [
        'parent_sku'=>['title' => '平台spu', 'key' => 'parent_sku', 'width' => 10],
        'product_id'=>['title' => '产品id', 'key' => 'product_id', 'width' => 10],
        'name'=>['title' => '刊登标题', 'key' => 'name', 'width' => 35],
        'description'=>['title' => '描述详情', 'key' => 'description', 'width' => 15],
        'tags'=>['title' => 'Tags', 'key' => 'tags', 'width' => 25],
        'sku'=>['title' => 'sku', 'key' => 'sku', 'width' => 20],
        'color'=>['title' => 'Color', 'key' => 'color', 'width' => 20],
        'size'=>['title' => 'Size', 'key' => 'size', 'width' => 25],
        'inventory'=>['title' => '可售量', 'key' => 'inventory', 'width' => 20],
        'price'=>['title' => '*Price', 'key' => 'price', 'width' => 40],
        'msrp'=>['title' => 'MSRP', 'key' => 'msrp', 'width' => 20],
        'shipping'=>['title' => '*Shipping', 'key' => 'shipping', 'width' => 20],
        'shipping_time'=>['title' => 'Shipping Time(enter without " ", just the estimated days )', 'key' => 'shipping_time', 'width' => 20],
        'date_uploaded'=>['title' => '上传时间', 'key' => 'date_uploaded', 'width' => 20],
        'last_updated'=>['title' => '更新时间', 'key' => 'last_updated', 'width' => 20],
        'main_image'=>['title' => '*Product Main Image URL', 'key' => 'main_image', 'width' => 20],
        'sku_image'=>['title' => 'Variant Main Image URL', 'key' => 'sku_image', 'width' => 20],
        'thumb0'=>['title' => 'Extra Image URL', 'key' => 'thumb0', 'width' => 20],
        'thumb1'=>['title' => 'Extra Image URL 1', 'key' => 'thumb1', 'width' => 20],
        'thumb2'=>['title' => 'Extra Image URL 2', 'key' => 'thumb2', 'width' => 20],
        'thumb3'=>['title' => 'Extra Image URL 3', 'key' => 'thumb3', 'width' => 20],
        'thumb4'=>['title' => 'Extra Image URL 4', 'key' => 'thumb4', 'width' => 20],
        'thumb5'=>['title' => 'Extra Image URL 5', 'key' => 'thumb5', 'width' => 20],
        'thumb6'=>['title' => 'Extra Image URL 6', 'key' => 'thumb6', 'width' => 20],
        'thumb7'=>['title' => 'Extra Image URL 7', 'key' => 'thumb7', 'width' => 20],
        'thumb8'=>['title' => 'Extra Image URL 8', 'key' => 'thumb8', 'width' => 20],
        'thumb9'=>['title' => 'Extra Image URL 9', 'key' => 'thumb9', 'width' => 20],
        'thumb10'=>['title' => 'Extra Image URL 10', 'key' => 'thumb10', 'width' => 20],
        'review_status'=>['title' => '审核状态', 'key' => 'review_status', 'width' => 20],
        'application'=>['title' => '刊登工具', 'key' => 'application', 'width' => 20],
        'code'=>['title' => '账号简称', 'key' => 'code', 'width' => 20],
        'is_promoted'=>['title' => '是否促销', 'key' => 'is_promoted', 'width' => 20],
        'number_sold'=>['title' => '已售量', 'key' => 'number_sold', 'width' => 20],
        'number_saves'=>['title' => '已收藏', 'key' => 'number_saves', 'width' => 20],
        'upc'=>['title' => 'upc', 'key' => 'upc', 'width' => 20],
        'brand'=>['title' => '品牌', 'key' => 'brand', 'width' => 20],
    ];

    public static function publishTime($params,$page=1,$pageSize=50){
        $response=self::getPublishTimeData($params,$page,$pageSize);
        return $response;
    }
    private static function getPublishTimeData($params,$page=1,$pageSize=30){
        $where=[];
        if(isset($params['start_time']) && $params['start_time'] && isset($params['end_time']) && $params['end_time']){
            $where['publish_time']=['between time',[strtotime($params['start_time'].'00:00:00'),strtotime($params['end_time']."23:59:59")]];
        }elseif(isset($params['start_time']) && $params['start_time']){
            $where['publish_time']=['>=',strtotime($params['start_time'].'00:00:00')];
        }elseif(isset($params['end_time']) && $params['end_time']){
            $where['publish_time']=['<=',strtotime($params['end_time']."23:59:59")];
        }
        if(isset($params['spu']) && $params['spu']){
            $where['spu']=['IN',"{$params['spu']}"];
        }
        if(isset($params['developer']) && $params['developer']){
            $where['realname']=['LIKE',"{$params['developer']}%"];
        }
        $model = new Goods();
        $fields="a.id,a.spu,b.realname";
        $total=0;
        if(isset($params['flag']) && $params['flag']=='joy88'){
            $items = $model->field($fields)->where($where)->alias('a')->join('user b','a.developer_id=b.id','LEFT')->select();
        }else{
            $total = $model->where($where)->alias('a')->join('user b','a.developer_id=b.id','LEFT')->count();
            $items = $model->field($fields)->where($where)->alias('a')->join('user b','a.developer_id=b.id','LEFT')->page($page,$pageSize)->select();
        }
        $channels = GoodsPublishMapService::CHANNEL;
        foreach ($items as &$item){
            foreach ($channels as $channel_id=>$channel){
                if(!in_array($channel_id,[5,6,9])){
                    //$publish_num = self::everyChannelPublishStatistic(['channel_id'=>$channel_id,'goods_id'=>$item['id']]);
                    $publish_num = GoodsPublishMap::where(['goods_id'=>$item['id'],'channel'=>$channel_id])->field('publish_count')->find();
                    $publish_count = $publish_num?$publish_num['publish_count']:0;
                    $item[$channel]=$publish_count;
                }
            }
        }
        return ['total'=>$total,'data'=>$items,'page'=>$page,'pageSize'=>$pageSize];
    }
    private static function everyChannelPublishStatistic($params){
        $where=$join=[];
        if(isset($params['channel_id']) && $params['channel_id']){
            //$where['channel']=['=',$params['channel']];
            switch ($params['channel_id']){
                case 1:
                    $uploadTime = 'start_date';
                    $where['listing_status']=['IN',[3,5,6]];
                    $where['draft']=['=',0];
                    $model = new EbayListing();
                    break;
                case 2:
                    $uploadTime = 'a.create_time';
                    $where['publish_status']=['=',2];
                    $model = new AmazonPublishProduct();
                    break;
                case 3:
                    $uploadTime = 'date_uploaded';
                    $where['publish_status']=['=',1];
                    $where['b.enabled']=['=',1];
                    $where['review_status']=['IN',[1,3]];
                    $join[]=['wish_wait_upload_product_variant b','b.pid=a.id','LEFT'];
                    $model = new WishWaitUploadProduct();
                    break;
                case 4:
                    $uploadTime = 'gmt_create';
                    $where['product_id']=['<>',0];
                    $where['product_status_type']=['IN',[1,3]];
                    $model = new AliexpressProduct();
                    break;
                case 5:
                    break;
                case 6:
                    break;
                case 7:
                    $uploadTime = 'date_uploaded';
                    $where['a.product_id']=['<>',''];
                    $where['b.enabled']=['=',1];
                    $where['a.enabled']=['=',1];
                    $where['review_status']=['IN',[1,0]];
                    $join[]=['joom_variant b','a.id=b.joom_product_id','LEFT'];
                    $model = new JoomProduct();
                    break;
                case 8:
                    $uploadTime = 'date_uploaded';
                    $where['publish_status']=['=',1];
                    $where['b.enabled']=['=',1];
                    $where['review_status']=['IN',[1,3]];
                    $join[]=['pandao_variant b','a.id=b.pid','LEFT'];
                    $model = new PandaoProduct();
                    break;
                default:
                    break;
            }
        }
        $total = $model->alias('a')->where('goods_id',$params['goods_id'])->where($where)->join($join)->count('DISTINCT(a.id)');

        return $total;
    }
    public static function statistic($params,$page=1,$pageSize=50){
        $response=self::createWhere2($params,$page,$pageSize);
        return $response;
    }

    //调整代码
    public static function createWhere2($params,$page=1,$pageSize=30,$export=false)
    {
        $where = $join = [];
        if (isset($params['channel_id']) && $params['channel_id']) {
            //$where['channel']=['=',$params['channel']];
            switch ($params['channel_id']) {
                case 1:
                    $uploadTime = 'start_date';
                    $where['listing_status'] = ['IN', [3, 5, 6]];
                    $where['draft'] = ['=', 0];
                    $model = new EbayListing();
                    break;
                case 2:
                    $uploadTime = 'a.create_time';
                    $where['publish_status'] = ['=', 2];
                    $model = new AmazonPublishProduct();
                    break;
                case 3:
                    $uploadTime = 'date_uploaded';
                    $where['publish_status'] = ['=', 1];
                    //$where['b.enabled']=['=',1]; //屏蔽-pan
                    $where['review_status'] = ['IN', [1, 3]];
                    //$join[]=['wish_wait_upload_product_variant b','b.pid=a.id','LEFT']; //屏蔽-pan
                    $model = new WishWaitUploadProduct();
                    break;
                case 4:
                    $uploadTime = 'gmt_create';
                    $where['product_id'] = ['<>', 0];
                    $where['product_status_type'] = ['IN', [1, 3]];
                    $model = new AliexpressProduct();
                    break;
                case 5:
                    break;
                case 6:
                    break;
                case 7:
                    $uploadTime = 'date_uploaded';
                    $where['a.product_id'] = ['<>', ''];
                    $where['b.enabled'] = ['=', 1];
                    $where['a.enabled'] = ['=', 1];
                    $where['review_status'] = ['IN', [1, 0]];
                    $join[] = ['joom_variant b', 'a.id=b.joom_product_id', 'LEFT'];
                    $model = new JoomProduct();
                    break;
                case 8:
                    $uploadTime = 'date_uploaded';
                    $where['publish_status'] = ['=', 1];
                    $where['b.enabled'] = ['=', 1];
                    $where['review_status'] = ['IN', [1, 3]];
                    $join[] = ['pandao_variant b', 'a.id=b.pid', 'LEFT'];
                    $model = new PandaoProduct();
                    break;
                default:
                    break;
            }
        }

        if (isset($params['start_time']) && $params['start_time'] && isset($params['end_time']) && $params['end_time']) {
            $where[$uploadTime] = ['between time', [strtotime($params['start_time'] . '00:00:00'), strtotime($params['end_time'] . "23:59:59")]];
        } elseif (isset($params['start_time']) && $params['start_time']) {
            $where[$uploadTime] = ['>=', strtotime($params['start_time'] . '00:00:00')];
        } elseif (isset($params['end_time']) && $params['end_time']) {
            $where[$uploadTime] = ['<=', strtotime($params['end_time'] . "23:59:59")];
        }

        if (isset($params['developer_id']) && !empty($params['developer_id'])) {
            $where['c.developer_id'] = ['=', $params['developer_id']];
        }

        if (isset($params['spu']) && !empty($params['spu'])) {
            if (is_json($params['spu'])) {
                $arr = json_decode($params['spu'], true);
                $where['c.spu'] = ['IN', $arr];
            } else {
                $where['c.spu'] = ['eq', $params['spu']];
            }

        }

        $order_str = 'c.id desc';
        if (isset($params['order_type']) && !empty($params['order_type'])) {
            $sort = $params['order_sort'] ? $params['order_sort'] : 'desc';
            switch ($params['order_type']) {
                case 'publish_count':
                    $order_str = 'publish_count ' . $sort;
                    break;
            }
        }

        $fields = "count(*) as publish_count,c.name,c.sales_status,c.id,c.developer_id,c.spu";

        if ($export) {
            $total = $model->alias('a')->where($where)->join($join)
                ->join('goods c', 'a.goods_id=c.id', 'RIGHT')
                ->group('goods_id')
                ->count();

            $items = $model->field($fields)->alias('a')->where($where)->join($join)
                ->join('goods c', 'a.goods_id=c.id', 'RIGHT')
                ->group('goods_id')
                ->select();

        } else {


            $total = $model->alias('a')->where($where)->join($join)
                ->join('goods c', 'a.goods_id=c.id', 'RIGHT')
                ->group('goods_id')
                ->count();

            $items = $model->field($fields)->alias('a')->where($where)->join($join)
                ->join('goods c', 'a.goods_id=c.id', 'RIGHT')->page($page, $pageSize)
                ->group('goods_id')
                ->order($order_str)
                ->select();
            //echo  $model::getLastSql();
        }


        $service = new  GoodsHelp();
        foreach ($items as &$item){
           // $total_num = $model->alias('a')->where('goods_id',$item['id'])->where($where)->join($join)->count('DISTINCT(a.id)');
            $item['status'] = isset($service->sales_status[$item['sales_status']])?$service->sales_status[$item['sales_status']]:'';
           // $item['publish_count'] = $total_num;
            $developer=Cache::store('user')->getOneUser($item['developer_id']);
            $item['developer']= isset($developer['realname'])?$developer['realname']:''; //添加开发者-pan
        }
        return ['total'=>$total,'data'=>$items,'page'=>$page,'pageSize'=>$pageSize];
    }



    public static function createWhere($params,$page=1,$pageSize=30){
        $where=$join=[];
        if(isset($params['channel_id']) && $params['channel_id']){
            //$where['channel']=['=',$params['channel']];
            switch ($params['channel_id']){
                case 1:
                    $uploadTime = 'start_date';
                    $where['listing_status']=['IN',[3,5,6]];
                    $where['draft']=['=',0];
                    $model = new EbayListing();
                    break;
                case 2:
                    $uploadTime = 'a.create_time';
                    $where['publish_status']=['=',2];
                    $model = new AmazonPublishProduct();
                    break;
                case 3:
                    $uploadTime = 'date_uploaded';
                    $where['publish_status']=['=',1];
                    $where['b.enabled']=['=',1];
                    $where['review_status']=['IN',[1,3]];
                    $join[]=['wish_wait_upload_product_variant b','b.pid=a.id','LEFT'];
                    $model = new WishWaitUploadProduct();
                    break;
                case 4:
                    $uploadTime = 'a.create_time';
                    $where['product_id']=['<>',0];
                    $where['product_status_type']=['IN',[1,3]];
                    $model = new AliexpressProduct();
                    break;
                case 5:
                    break;
                case 6:
                    break;
                case 7:
                    $uploadTime = 'date_uploaded';
                    $where['a.product_id']=['<>',''];
                    $where['b.enabled']=['=',1];
                    $where['a.enabled']=['=',1];
                    $where['review_status']=['IN',[1,0]];
                    $join[]=['joom_variant b','a.id=b.joom_product_id','LEFT'];
                    $model = new JoomProduct();
                    break;
                case 8:
                    $uploadTime = 'date_uploaded';
                    $where['publish_status']=['=',1];
                    $where['b.enabled']=['=',1];
                    $where['review_status']=['IN',[1,3]];
                    $join[]=['pandao_variant b','a.id=b.pid','LEFT'];
                    $model = new PandaoProduct();
                    break;
                default:
                    break;
            }
        }

        if(isset($params['start_time']) && $params['start_time'] && isset($params['end_time']) && $params['end_time']){
            $where[$uploadTime]=['between time',[strtotime($params['start_time'].'00:00:00'),strtotime($params['end_time']."23:59:59")]];
        }elseif(isset($params['start_time']) && $params['start_time']){
            $where[$uploadTime]=['>=',strtotime($params['start_time'].'00:00:00')];
        }elseif(isset($params['end_time']) && $params['end_time']){
            $where[$uploadTime]=['<=',strtotime($params['end_time']."23:59:59")];
        }

        $fields="DISTINCT(c.spu),c.name,c.status,c.id,c.developer_id";
        if(isset($params['flag']) && $params['flag']=='joy88'){
            $total = $model->alias('a')->where($where)->join($join)
                ->join('goods c','a.goods_id=c.id','RIGHT')
                ->count('DISTINCT(c.id)');
            $items = $model->field($fields)->alias('a')->where($where)->join($join)
                ->join('goods c','a.goods_id=c.id','RIGHT')
                ->select();
        }else{
            $total = $model->alias('a')->where($where)->join($join)
                ->join('goods c','a.goods_id=c.id','RIGHT')
                ->count('DISTINCT(c.id)');
            $items = $model->field($fields)->alias('a')->where($where)->join($join)
                ->join('goods c','a.goods_id=c.id','RIGHT')->page($page,$pageSize)
                ->select();
        }

        $service = new  GoodsHelp();
        foreach ($items as &$item){
            $total_num = $model->alias('a')->where('goods_id',$item['id'])->where($where)->join($join)->count('DISTINCT(a.id)');
            $item['status'] = isset($service->sales_status[$item['status']])?$service->sales_status[$item['status']]:'';
            $item['publish_count'] = $total_num;
            $developer=Cache::store('user')->getOneUser($item['developer_id']);

            $item['developer']= isset($developer['realname'])?$developer['realname']:''; //添加开发者-pan
        }
        return ['total'=>$total,'data'=>$items,'page'=>$page,'pageSize'=>$pageSize];
    }


    public static function getDownloadFields($channel_id){
        $fields = [];
        switch ($channel_id){
            case 1:
                break;
            case 2:
                break;
            case 3:
                $fields = WishDownloadField::where('is_show',1)->select();
                break;
            case 4:
                break;
            default:
                break;
        }
        return $fields;
    }
    /**
     * 创建导出文件名
     * @param $userId
     * @return string
     */
    public function createExportFileName($userId,$extension=1)
    {
        $userInfo = Common::getUserInfo()->toArray();
        $user['role'] = (new Role())->getRoleNameByUserId($userId);
        if($extension){
            $fileName = "{$user['role']}_".$userInfo['realname'].'_'.date("Y_m_d_H_i_s").'报表.xlsx';
        }else{
            $fileName = "{$user['role']}_{$userInfo['realname']}_";
        }

        return $fileName;
    }


    /**
     * 新的命名规则，根据搜索条件-pan
     * @param $userId
     * @param int $extension
     * @return string
     */
    public function createExportFileNameNew($params)
    {

        $fileName = '';
        if (isset($params['channel_id']) && $params['channel_id']) {
            $channel = cache::store('channel')->getChannel();
            foreach ($channel as $v) {
                if ($params['channel_id'] == $v['id']) {
                    $fileName .= $v['title'] . '|';
                }
            }

        }
        if (isset($params['developer_id']) && !empty($params['developer_id'])) {
            $developer = Cache::store('user')->getOneUser($params['developer_id']);

            $fileName .= '开发员：';
            $fileName.=isset($developer['realname']) ? $developer['realname'] : '---';
            $fileName .='|';

        }

        if (isset($params['start_time']) && $params['start_time'] && isset($params['end_time']) && $params['end_time']) {

            $fileName .= '刊登时间:' . $params['start_time'] . '~' . $params['end_time'] . '|';
        } elseif (isset($params['start_time']) && $params['start_time']) {
            $fileName .= '刊登时间:' . $params['start_time'] . '~' . '---' . '|';

        } elseif (isset($params['end_time']) && $params['end_time']) {

            $fileName .= '刊登时间:' . '---' . '~' . $params['end_time'] . '|';
        }


//
//        if (isset($params['spu']) && !empty($params['spu'])) {
//            if (is_json($params['spu'])) {
//                $arr = json_decode($params['spu'], true);
//
//            } else {
//
//            }
//
//        }

        return $fileName . 'SPU刊登时间统计.xlsx';
    }


    public function downloadByQueue($params){
        $userId = Common::getUserInfo()->toArray()['user_id'];
        $cache = Cache::handler();
        $key = 'hash:download:product';
        $lastApplyTime = $cache->hget($key,$userId);
        if($lastApplyTime && time() - $lastApplyTime < 5){
            throw new JsonErrorException('请求过于频繁',400);
        }else{
            $cache->hset($key,$userId,time());
        }
        Db::startTrans();
        try{
            $model = new ReportExportFiles();
            $data['applicant_id'] = $userId;
            $data['apply_time'] = time();
            $data['export_file_name'] = $this->createExportFileNameNew($params);
            $data['status'] = 0;
            $data['applicant_id'] = $userId;
            $model->allowField(true)->isUpdate(false)->save($data);
            $params['file_name'] = $data['export_file_name'];
            $params['apply_id'] = $model->id;
            (new CommonQueuer(PublishProductDownloadQueue::class))->push($params);


            Db::commit();
            return ['message'=>'生成成功'];
        }catch (\Exception $ex){
            Db::rollback();
            throw new JsonErrorException('申请导出失败');
        }
    }

    private function createFullPath($fileName){
        $downLoadDir = '/download/publish/';
        $saveDir = ROOT_PATH . 'public' . $downLoadDir;
        if (!is_dir($saveDir) && !mkdir($saveDir, 0777, true)) {
            throw new Exception('导出目录创建失败');
        }
        if(strpos($fileName,".xlsx")!==false){
            $fullName = $saveDir . $fileName;
        }else{
            $fullName = $saveDir . $fileName.'.xlsx';
        }

        return $fullName;
    }
    /**
     * 导出
     * @param array $order_ids
     * @param array $field
     * @param array $params
     * @return array
     */
    public function export($ids,$field, $params){
        try {
            $cacheDriver = Cache::store('PublishProductDownload');
            if(empty($ids)){
                if ($cacheDriver->isExport('export')) {
                    throw new JsonErrorException('请使用部分导出');
                }
                $cacheDriver->setExport('export');
            }
            $userId = Common::getUserInfo()->toArray()['user_id'];
            //记录操作时间
            $fileName = $this->createExportFileName($userId);
            $fullName = $this->createFullPath($fileName);
            $condition = [];
            if (!empty($ids)) {
                $condition['id'] = ['in', $ids];
            }
            if (isset ($params['start_time']) && isset ($params['end_time']) && $params['end_time'] && $params['start_time'])
            {
                $params['start_time'] = $params['start_time'].'00:00:00';
                $params['end_time'] = $params['end_time'].'23:59:59';
                $where['date_uploaded'] = ['between time', [strtotime($params['start_time']), strtotime($params['end_time'])]];
            } elseif (isset ($params['end_time']) && $params['end_time']) {
                $where['date_uploaded'] = array('<=', strtotime($params['end_time'] . '23:59:59'));
            } elseif (isset($params['start_time']) && $params['start_time']) {
                $where['date_uploaded'] = array('>=', strtotime($params['start_time'] . '00:00:00'));
            }


            $records = [];
            switch ($params['channel_id']){
                case 1:
                    break;
                case 2:
                    break;
                case 3:
                    $records = $this->getWishPublishProduct($condition, $params);
                    break;
                case 4:
                    break;
                case 5:
                    break;
                default:
                    break;
            }
            $titleAndRemark = $this->createTitleRemark($params['channel_id'],$field);
            $title  = $titleAndRemark['title'];
            $remark = $titleAndRemark['remark'];
            $data = $this->assemblyData($records, $title);
            $this->createExcelFile($data, $remark, $fullName,$fileName);
            $result = $this->record($fileName, $fullName);
            $cacheDriver->delExport('export');
            return $result;
        } catch (Exception $e) {
            $cacheDriver->delExport('export');
            throw new JsonErrorException($e->getMessage() . $e->getFile() . $e->getLine());
        }
    }
    public function createTitleRemark($channel_id,$field){
        switch ($channel_id){
            case 3:
                $titleData = self::$wishHeader;
                break;
            default:
                break;
        }

        $remark = [];
        if (!empty($field)) {
            $title = [];
            foreach ($field as $k => $v) {
                if (isset($titleData[$v['key']])) {
                    array_push($title, $v['key']);
                    array_push($remark,  $v['title']);
                }
            }
        } else {
            $title = [];
            foreach ($titleData as $k => $v) {
                array_push($title, $k);
                array_push($remark, $v['title']);
            }
        }
        return ['remark'=>$remark,'title'=>$title];
    }
    public function createExcelFile($data,$remark,$file,$filename){
        $downLoadDir = '/download/publish/';
       try{
           include EXTEND_PATH.'Box/Spout/Autoloader/autoload.php';
           ini_set('memory_limit', '4096M');
           $writer = WriterFactory::create(Type::XLSX);
           $style= new StyleBuilder();
           //$style->setBorder();
           //$style->setShouldWrapText();
           $style = $style->build();
           $writer->openToFile($file);
           $writer->addRowWithStyle($remark,$style);
           $writer->addRowsWithStyle($data,$style);
           $writer->close();
           $return = ['result'=>true,'error_message'=>''];
       }catch (Exception $exp){
           $return = ['result'=>false,'error_message'=>$exp->getMessage()];
       }

       if($return['result']){
           $return['status']=1;
           $return['exported_time']=time();
           $return['download_url']=$downLoadDir.$filename;
       }

       return $return;
    }
    public function record($filename,$path)
    {
        Db::startTrans();
        try{
            $model = new LogExportDownloadFiles();
            $temp['file_code'] = date('YmdHis');
            $temp['created_time'] = time();
            $temp['download_file_name'] = $filename;
            $temp['type'] = 'publish_export';
            $temp['file_extionsion'] = 'xlsx';
            $temp['saved_path'] = $path;
            $model->allowField(true)->isUpdate(false)->save($temp);
            Db::commit();
            return ['file_code' => $temp['file_code'],'file_name' => $temp['download_file_name']];
        }catch (Exception $exp){
            Db::rollback();
            throw new JsonErrorException($exp->getMessage());
        }
    }
    private function assemblyData($records,$title){
        $exportData=[];

        foreach ($records as $record){
            $record = is_object($record)?$record->toArray():$record;
            $temp=[];
            foreach ($title as $name){
                array_push($temp,$record[$name]);
            }
            array_push($exportData, $temp);
        }
        return $exportData;
    }
    private function getWishPublishProduct($condition=[],$params){
        $condition['publish_status']=['=',1];
        $products = WishWaitUploadProduct::where($condition)->field('a.*,b.*,b.main_image sku_image')->with(['info','account'])->alias('a')->join('wish_wait_upload_product_variant b','a.id=b.pid')->select();
        foreach ($products as &$product){
            $info = json_decode(json_encode($product->info),true);
            $account = $product->account;
            $product = $product->toArray();
            $product = array_merge($product,$info);
            $extra_images = $this->extraImagesTranslate($product['extra_images'],'|','thumb');
            $product = array_merge($product,$extra_images);
            $product['code']=$account['code'];
        }
        return $products;
    }

    /**
     * @param $imageString 图片连接字符串
     * @param $depart 连接符
     * @param $subName 数组下标
     */
    private function extraImagesTranslate($imageString,$depart,$subName){
        $images = explode("{$depart}",$imageString);
        if($images)
        {
            foreach ($images as $index=>$image)
            {
                $row["{$subName}".$index] = $image;
            }
        }

        if($index<10){
            $start = $index+1;
            for ($i=$start;$i<=10;$i++){
                $row["{$subName}".$i] = '';
            }
        }
        return $row;
    }

    public static function createDownloadFile($headers,$data,$id){
        $channel = GoodsPublishMapService::CHANNEL[self::$channel_id];
        $file = [
            'name' => "导出{$channel}刊登商品",
            'path' => 'goods'
        ];
        $ExcelExport = new DownloadFileService();
        $result =  $ExcelExport->exportCsv($data, $headers, $file);
        $canDelete=false;
        if(isset($result['status']) && $result['status'] && isset($result['file_code']) && $result['file_code']){
            $logExportDownloadFiles = new LogExportDownloadFiles();
            $fileLog = $logExportDownloadFiles->where(['file_code' => $result['file_code']])->find();
            if($fileLog){
                $result['download_url']='download'.DS.'goods'.$fileLog['download_file_name'];
                $canDelete=true;
            }
        }
        $result['error_message']=$result['message'];
        Db::startTrans();
        try{
            $model = new ReportExportFiles();
            $model->allowField(true)->save($result,['id'=>$id]);
        }catch (PDOException $exp){
            Db::rollback();
            throw new Exception($exp->getMessage());
        }
        if($canDelete && self::$cacheName){
            Cache::store('PublishProductDownload')->deleteCacheData(self::$cacheName);
        }
    }
    public static function ebayDownload($id,$fields){

    }
    public static function amazonDownload($id,$fields){

    }
    private function updateReportFile($result){
        Db::startTrans();
        try{
            $model = new ReportExportFiles();
            $model->allowField(true)->isUpdate(true)->save($result,['id'=>$result['id']]);
            Db::commit();
        }catch (PDOException $exp){
            Db::rollback();
            throw new Exception($exp->getMessage());
        }
    }
    public static function publishDownloadByTime($params,$id,$file_name)
    {
        $response=self::publishTime($params);

        $records = $response['data'];
        $self = new self();
        $title  = [
            'spu'=>['title' => 'spu', 'key' => 'spu', 'width' => 10],
            'name'=>['title' => '产品名称', 'key' => 'name', 'width' => 10],
            'status'=>['title' => '产品状态', 'key' => 'status', 'width' => 10],
            'publish_count'=>['title' => '刊登数量', 'key' => 'publish_count', 'width' => 10],
        ];
        $remark = ['spu','开发员','Ebay平台','亚马逊平台','Wish平台','速卖通平台','Joom平台','MyMall平台'];
        $data = $self->assemblyData($records, $title);
        $fullName =$self->createFullPath($file_name);
        $result = $self->createExcelFile($data, $remark, $fullName,$file_name);
        $result['id']=$id;
        $self->updateReportFile($result);
    }


//    public static function publishDownload($params,$id,$file_name)
//    {
//        $response=self::createWhere($params);
//
//        $records = $response['data'];
//        $self = new self();
//        $title  = [
//            'spu'=>['title' => 'spu', 'key' => 'spu', 'width' => 10],
//            'name'=>['title' => '产品名称', 'key' => 'name', 'width' => 10],
//            'status'=>['title' => '产品状态', 'key' => 'status', 'width' => 10],
//            'publish_count'=>['title' => '刊登数量', 'key' => 'publish_count', 'width' => 10],
//        ];
//        $remark = ['spu','产品名称','产品状态','刊登数量'];
//        $data = $self->assemblyData($records, $title);
//        $fullName =$self->createFullPath($file_name);
//        $result = $self->createExcelFile($data, $remark, $fullName,$file_name);
//        $result['id']=$id;
//        $self->updateReportFile($result);
//    }

    //对应SPU刊登时间统计-批量导出-pan
    public static function publishDownload($params,$id,$file_name)
    {
        $response=self::createWhere2($params,0,0,true); //导出

        $records = $response['data'];
        $self = new self();
        $title  = [
            'spu'=>['title' => 'spu', 'key' => 'spu', 'width' => 10],
            'developer'=>['title' => '开发员', 'key' => 'developer', 'width' => 10],
            'name'=>['title' => '产品名称', 'key' => 'name', 'width' => 10],
            'status'=>['title' => '产品状态', 'key' => 'status', 'width' => 10],
            'publish_count'=>['title' => '刊登数量', 'key' => 'publish_count', 'width' => 10],
        ];
        $remark = ['spu','开发员','产品名称','产品状态','已刊登总数'];
        $keys=array_keys($title);


        $data = $self->assemblyData($records, $keys);
        $fullName =$self->createFullPath($file_name);
        $result = $self->createExcelFile($data, $remark, $fullName,$file_name);
        $result['id']=$id;
        $self->updateReportFile($result);
    }




    public static function wishDownload($params,$channel_id,$id,$field,$file_name){
        try {
            self::$channel_id = $channel_id;
            $where = [];
            if (isset ($params['start_time']) && isset ($params['end_time']) && $params['end_time'] && $params['start_time'])
            {
                $params['start_time'] = $params['start_time'].'00:00:00';
                $params['end_time'] = $params['end_time'].'23:59:59';
                $where['date_uploaded'] = ['between time', [strtotime($params['start_time']), strtotime($params['end_time'])]];
            } elseif (isset ($params['end_time']) && $params['end_time']) {
                $where['date_uploaded'] = array('<=', strtotime($params['end_time'] . '23:59:59'));
            } elseif (isset($params['start_time']) && $params['start_time']) {
                $where['date_uploaded'] = array('>=', strtotime($params['start_time'] . '00:00:00'));
            }

            if(isset($params['ids']) && $params['ids']){
                $where['id']=['IN',$params['ids']];
            }
            $self = new self();
            $records= $self->getWishPublishProduct($where,$params);
            $titleAndRemark = $self->createTitleRemark($channel_id,$field);
            $title  = $titleAndRemark['title'];
            $remark = $titleAndRemark['remark'];
            $data = $self->assemblyData($records, $title);
            $fullName =$self->createFullPath($file_name);
            $result = $self->createExcelFile($data, $remark, $fullName,$file_name);
            $result['id']=$id;
            $self->updateReportFile($result);
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage().$ex->getFile().$ex->getLine());
        }
    }
    private static function createWishHeaders($fields){
        $headers=[];
        foreach ($fields as $field){
            if(in_array(self::$wishHeader[$field])){
                $header = self::$wishHeader[$field];
                $headers[]=$header;
            }
        }
        return $headers;

    }

    public static function joomDownload($fields){

    }
    public static function pandaoDownload($id,$fields){
        
    }

    public static function aliexpressDownload($id,$fields)
    {

    }
    
}