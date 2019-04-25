<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-4-19
 * Time: 下午2:12
 */

namespace app\publish\service;

use app\common\cache\Cache;
use app\common\exception\JsonErrorException;
use app\common\model\AttributeValue;
use app\common\model\DepartmentUserMap;
use app\common\model\Goods;
use app\common\model\GoodsAttribute;
use app\common\model\GoodsPublishMap;
use app\common\model\GoodsSku;
use app\common\model\GoodsTortDescription;
use app\common\model\joom\JoomAttributeValue;
use app\common\model\pandao\PandaoProduct;
use app\common\model\pandao\PandaoAccount;
use app\common\model\pandao\PandaoActionLog;
use app\common\model\pandao\PandaoProductInfo;
use app\common\model\pandao\PandaoVariant;
use app\common\model\User as UserModel;
use app\common\service\Common;
use app\common\service\CommonQueuer;
use app\common\service\UniqueQueuer;
use app\goods\service\GoodsPublishMapService;
use app\goods\service\GoodsSkuMapService;
use app\index\service\Role;
use app\listing\service\WishListingHelper;
use app\publish\queue\PandaoExportListingQueue;
use app\publish\queue\PandaoListingUpdateQueue;
use app\publish\queue\PandaoQueueJob;
use app\index\validate\PandaoValidate;
use app\report\model\ReportExportFiles;
use erp\AbsServer;
use erp\ErpRbac;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use app\common\traits\User;
use  app\index\service\DownloadFileService;

class PandaoService extends AbsServer
{
    use User;
    private $channel_id=8;
    const TYPE=[
        'updateProduct'=>1,
        'updateVariant'=>2,
        'enableProduct'=>3,
        'disableProduct'=>4,
        'enableVariant'=>5,
        'disableVariant'=>6,
        'updateInventory'=>7,
        'updateShipping'=>8,
        'updateMultiShipping'=>9,
        'updateQipaMultiShipping'=>10,
        'rsyncProduct'=>11,
        'online'=>12,
    ];
    public function pushQueue($ids){
        $ids = explode(',',$ids);
        $model = new PandaoVariant();
        $queueDriver  = new UniqueQueuer(PandaoQueueJob::class);
        $total=0;
        foreach ($ids as $id){
            //还存在没有刊登成功的，则加入队列
            $variant= $model->with(['product'=>function($query){
                $query->field('id,cron_time');
            }])->field('vid,pid')->where('pid',$id)->where('status','<>',1)->limit(1)->find();
            if($variant){
                if($queueDriver->push($id,$variant['product']['cron_time']))
                    ++$total;
            }
        }
        return ['message'=>"成功加入队列[{$total}]"];
    }
    public function getLogs($param, $page=1, $pageSize=30, $fields="*"){
        $where= [
            'product_id'=>['=',$param['product_id']],
        ];

        $const =[
            'name'=>'刊登标题',
            'description'=>'详情描述',
            'tags'=>'tags',
            'brand'=>'品牌',
            'landing_page_url'=>'展示页面',
            'upc'=>'upc',
            'main_image'=>'主图',
            'extra_images'=>'辅图',
            'max_quantity'=>'最大库存',
            'inventory'=>'库存',
            'price'=>'售价',
            'shipping'=>'运费',
            'enabled'=>'是否有效',
            'size'=>'size',
            'color'=>'颜色',
            'msrp'=>'msrp',
            'shipping_time'=>'发货日期',
            'warehouse_name'=>'仓库',
            'combine_sku'=>'捆绑sku'
        ];

        $model = new PandaoActionLog();
        $count = $model->where($where)->count();

        $data = $model->order('create_time Desc')->with(['user'=>function($query){$query->field('id,realname');}])->where($where)->page($page,$pageSize)->select();

        if($data)
        {
            foreach ($data as &$d)
            {
                if(is_array($d['new_data']))
                {
                    $log='';

                    foreach ($d['new_data'] as $name=>$v)
                    {
                        if(is_numeric($name))
                        {
                            $log='Wish Express';
                        }else{
                            $log=$log.$const[$name].':由['.$d['old_data'][$name].']改为['.$d['new_data'][$name].']'.'<br />';
                        }
                    }
                }else{
                    $log=$d['new_data'];
                }
                $d['log']=$log;
            }
        }

        return ['data'=>$data,'count'=>$count,'page'=>$page,'pageSize'=>$pageSize];
    }

    /**
     * 同步产品
     * @param $ids
     */
    public function rsyncProduct($ids){

    }

    /**
     * 批量操作
     * @param $ids
     */
    public function batchAction($ids,$uid,$type,$message){
        $products = PandaoProduct::whereIn('id',$ids)->field('id,product_id')->select();
        if($products){
            $total = 0;
            foreach ($products as $product){
                $product_id = $product['product_id'];
                $where=[
                    'product_id'=>['=',$product_id],
                    'create_id'=>['=',$uid],
                    'type'=>['=',self::TYPE[$type]],
                    'status'=>['=',0]
                ];
                $log=[
                    'product_id'=>$product_id,
                    'type'=>self::TYPE[$type],
                    'create_id'=>$uid,
                    'new_data'=>$message,
                    'old_data'=>'',
                    'create_time'=>time(),
                ];
                if($this->ActionLog($log,$where)){
                    ++$total;
                }
            }
            return ['message'=>$message.'['.$total.']条'];
        }else{
            throw new JsonErrorException("没有相关数据");
        }
    }
    /**
     * 更新未刊登成功的商品 信息
     * @param $params
     * @return array
     */
    private function updateUnpublishedProduct($post){
        try{
            if (isset($post['vars'])) {
                $vars = json_decode($post['vars'], true); //每个账号信息
            }else{
                throw new JsonErrorException("数据格式非法");
            }

            $products = []; //产品
            if (is_array($vars))
            {
                foreach ($vars as $k => $var)
                {
                    $products[$k]['id'] = $post['id'];
                    $products[$k]['goods_id'] = $post['goods_id'];  //商品id
                    $products[$k]['account_id'] = $var['account_id']; //账号id
                    $products[$k]['name'] = $var['name']; //刊登标题
                    $products[$k]['main_image'] = $var['images'][0]; //商品主图
                    $products[$k]['description'] = $var['description'];//详情描述
                    $products[$k]['tags'] = $var['tags']; //Tags
                    $products[$k]['parent_sku'] = $post['parent_sku']; //SPU
                    $products[$k]['brand'] = $post['brand']; //品牌
                    $products[$k]['upc'] = $post['upc']; //UPC
                    if (preg_match('/^http(s)?:\\/\\/.+/', $post['landing_page_url'])) {
                        $products[$k]['landing_page_url'] = $post['landing_page_url'];
                    } else {
                        $products[$k]['landing_page_url'] = ''; //商品展示页面
                    }
                    $products[$k]['extra_images'] = implode('|', array_slice($var['images'], 1)); //商品相册a.jpg|b.jpg
                    $products[$k]['original_images'] = implode('|', $var['images']); //商品相册a.jpg|b.jpg
                    $products[$k]['cron_time'] = strtotime($var['cron_time']); //定时刊登
                    $products[$k]['warehouse'] = @$post['warehouse'];
                    $variants = [];
                    //更新时，每个商品的variant都已经存在，不存在variant为空的情况
                    if (isset($var['variant']) && !empty($var['variant']))
                    {
                        $variants = $var['variant'];
                        foreach ($variants as &$v) {
                            $v['add_time'] = time();
                            $v['status'] = 0;
                        }
                    }
                    $products[$k]['variants'] = $variants;
                }
            }else{
                throw new JsonErrorException("数据格式不合法");
            }

            if ($products)
            {
                if (is_array($products))
                {
                    foreach ($products as $p)
                    {

                        Db::startTrans();
                        try{
                            $queue = (string)$p['id'];
                            $variants = $p['variants'];
                            unset($p['variants']);
                            (new PandaoProduct())->isUpdate(true)->allowField(true)->save($p,['id'=>$p['id']]);
                            (new PandaoProductInfo())->allowField(true)->isUpdate(true)->save($p,['id'=>$p['id']]);

                            foreach ($variants as $variant)
                            {
                                if(isset($variant['vid']) && $variant['vid'])
                                {
                                    $map['vid'] = ['=', $variant['vid']]; //没有sku时，根据pid更新
                                    //$map['pid'] = ['=', $variant['pid']];
                                    (new PandaoVariant())->isUpdate(true)->allowField(true)->save($variant,$map);
                                }else{
                                    (new PandaoVariant())->isUpdate(false)->allowField(true)->save($variant);
                                }
                            }

                            $findWhere = [
                                'pid' => ['=', $queue],
                                'status' => ['<>', 1],
                            ];

                            //如果存在没有刊登成功的加入队列
                            if (PandaoVariant::where($findWhere)->find())
                            {
                                PandaoVariant::where($findWhere)->update(['status' => 0, 'message' => '', 'run_time' => '']);
                            } else {
                                //设置状态为已更新
                                (new PandaoProduct())->update(['lock_product' => 1, 'lock_update' => 1], ['id' => $queue]);
                                (new PandaoVariant())->update(['lock_variant' => 1], ['pid' => $queue]);
                            }

                            Db::commit();
                            if ($p['cron_time'] <= time()) {
                                (new UniqueQueuer(PandaoQueueJob::class))->push($queue);
                            } else {
                                (new UniqueQueuer(PandaoQueueJob::class))->push($queue,$p['cron_time']);
                            }
                            $return = ['result' => true, 'message' => '更新成功'];
                        }catch (PDOException $exp){
                            Db::rollback();
                            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
                        }catch (DbException $exp){
                            Db::rollback();
                            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
                        }catch (\Exception $exp){
                            Db::rollback();
                            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
                        }
                    }
                }else{
                    throw new JsonErrorException("数据格式非法");
                }
            }else{
                throw new JsonErrorException("数据为空");
            }
            return $return;
        }catch (Exception $exp){
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");

        }
    }

    /**
     * 更新刊登成功的商品信息
     * @param $params
     * @return array
     */
    private function updatePublishedProduct($post)
    {
        try{
            if(isset($post['vars']))
            {
                $vars = json_decode($post['vars'],true);
            }else{
                throw new JsonErrorException("数据格式非法");
            }

            $uid = $post['uid'];
            $products =[];
            if(is_array($vars))
            {
                foreach ($vars as $k=>$var)
                {
                    $products[$k]['id'] = $post['id'];
                    $products[$k]['goods_id'] = $post['goods_id'];  //商品id
                    $products[$k]['name'] = $var['name']; //刊登标题
                    $products[$k]['main_image'] = $var['images'][0]; //商品主图
                    $products[$k]['description'] = $var['description'];//详情描述
                    $products[$k]['tags'] = $var['tags']; //Tags
                    $products[$k]['parent_sku'] = $post['parent_sku']; //SPU
                    $products[$k]['brand'] = $post['brand']; //品牌
                    $products[$k]['upc'] = $post['upc']; //UPC
                    $products[$k]['landing_page_url'] = $post['landing_page_url']; //商品展示页面
                    $products[$k]['extra_images'] =  implode('|', array_slice($var['images'], 1)); //商品相册a.jpg|b.jpg
                    $products[$k]['original_images'] =  implode('|', $var['images']);
                    $products[$k]['cron_time'] = strtotime($var['cron_time']); //定时刊登
                    $products[$k]['warehouse']=$post['warehouse'];
                    $products[$k]['variants']=$var['variant'];
                }
            }else{
                throw new JsonErrorException("数据格式非法");
            }

            if($products)
            {
                if(is_array($products))
                {
                    foreach($products as $p)
                    {

                        if( $this->saveProductUpdateField($uid,$p))
                        {
                            PandaoProduct::where('id','=',$p['id'])->update(['lock_product'=>1,'lock_update'=>1]);
                        }
                        $variants = $p['variants'];
                        unset($p['variants']);
                        if(is_array($variants) && $variants)
                        {
                            foreach ($variants as $variant)
                            {

                                if(!empty($variant))
                                {
                                    //刊登成功之后添加的sku
                                    if (!isset($variant['vid']))
                                    {
                                        $variant['pid'] = $p['id'];
                                        (new PandaoVariant())->allowField(true)->isUpdate(false)->save($variant);
                                    }else{
                                        if($this->saveVariantUpdateField($uid,$variant))
                                        {
                                            PandaoVariant::where('id','=',$variant['id'])->update(['lock_variant'=>1]);
                                            PandaoProduct::where(['id'=>$variant['id']])->update(['lock_update'=>1]);
                                        }
                                    }
                                }
                            }
                        }
                        $return=['result'=>true,'message'=>'更新成功'];
                    }
                }else{
                    throw new JsonErrorException("数据格式非法");
                }
            }else{
                throw new JsonErrorException("数据格式非法");
            }
            return $return;
        }catch (Exception $exp){
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }
    /**
     * 保存发生了变化的数据，记录到action_log
     * @param $uid 用户id
     * @param $variant
     * @return bool
     */
    public function saveVariantUpdateField($uid,$variant)
    {
        try{
            $fields =['combine_sku','sku','inventory','price','shipping','enabled','size','color','msrp','shipping_time','main_image','warehouse_name'];
            $update=false;

            if(isset($variant['id']) && $variant['id'])
            {
                $object = PandaoVariant::where('id','=',$variant['id'])->limit(1)->find();

                if($object)
                {
                    $row = is_object($object)?$object->toArray():$object;

                    $row['combine_sku'] = $object->getData('combine_sku');
                    $new_data=$old_data=[];
                    foreach ($variant as $type=>$value)
                    {
                        //当新提交的数据和原始数据不一样，且在指定修改的字段中时

                        if(in_array($type,$fields) && $row[$type]!=$variant[$type])
                        {
                            $new_data[$type]=$variant[$type];
                            $old_data[$type]=$row[$type];
                        }
                    }

                    if($new_data && $old_data)
                    {

                        $keys =array_keys($new_data);

                        $where=[
                            'create_id'=>['=',$uid],
                            'new_data'=>['=',json_encode($new_data)],
                            'variant_id'=>['=',$row['variant_id']],
                            'status'=>['=',0],
                        ];
                        $log=[
                            'create_id'=>$uid,
                            'type'=>self::TYPE['updateVariant'],
                            'new_data'=>json_encode($new_data),
                            'old_data'=>json_encode($old_data),
                            'product_id'=>$row['product_id'],
                            'variant_id'=>$row['variant_id'],
                            'create_time'=>time(),
                        ];
                        //如果修改了捆绑销售
                        if(isset($new_data['combine_sku']))
                        {
                            //如果只是编辑了捆绑sku
                            if(count($keys)==1 && $keys[0]=='combine_sku')
                            {
                                $log['status']=1;
                            }

                            $queue=[
                                'id'=>$variant['id'],
                                'combine_sku'=>$variant['combine_sku']
                            ];

                            // (new CommonQueuer(JoomCombineSkuQueue::class))->push($queue);
                        }

                        if($this->ActionLog($log,$where))
                        {
                            $update=true;
                        }
                    }
                }
            }
            return $update;
        }catch (Exception $exp){
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }

    }
    /**
     * 保存发生了变化的数据，记录到action_log
     * @param $uid 用户id
     * @param $product
     * @return bool
     */
    public function saveProductUpdateField($uid,$product)
    {
        try{
            $fields =['name','tags','brand','upc','main_image','max_quantity'];
            $info=['description','landing_page_url','extra_images',];
            $update=false;
            if(isset($product['id']) && $product['id'])
            {
                foreach ($product as $type=>$value)
                {
                    if(in_array($type,$fields))
                    {
                        $row = PandaoProduct::where('id','=',$product['id'])->limit(1)->find();
                    }elseif(in_array($type,$info)){
                        $row = PandaoProductInfo::where('id','=',$product['id'])->limit(1)->find();
                    }else{
                        $row=[];
                    }

                    if($row)
                    {
                        $new_data=$old_data=[];
                        if($row[$type]!=$product[$type])
                        {
                            $new_data[$type]=$product[$type];
                            $old_data[$type]=$row[$type];
                        }
                        if($new_data && $old_data)
                        {
                            $where=[
                                'create_id'=>['=',$uid],
                                'new_data'=>['=',json_encode($new_data)],
                                'product_id'=>['=',$row['product_id']],
                                'status'=>['=',0],
                            ];
                            $log=[
                                'create_id'=>$uid,
                                'type'=>self::TYPE['updateProduct'],
                                'new_data'=>json_encode($new_data),
                                'old_data'=>json_encode($old_data),
                                'product_id'=>$row['product_id'],
                                'create_time'=>time(),
                            ];

                            if($this->ActionLog($log,$where))
                            {
                                $update=true;
                            }
                        }
                    }
                }
            }
            return $update;
        }catch (Exception $exp){
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }
    /**
     * 写入修改日志
     * @param array $log
     * @param array $where
     */
    public function ActionLog(array $log,$where=[])
    {
        try{
            $model = new PandaoActionLog();

            if($rs = $model->get($where)){
                $id = $rs['id'];
                $return = $model->allowField(true)->isUpdate(true)->save($log, ['id'=>$rs['id']]);
            }else{
                $return =  $model->allowField(true)->save($log);
                $id = $model->id;
            }

            $cron_time = isset($log['cron_time'])?strtotime($log['cron_time']):0;
            (new CommonQueuer(PandaoListingUpdateQueue::class))->push($id,$cron_time);
            return $return ;
        }catch (Exception $exp){
            throw new Exception("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }

    }
    /**
     * 获取商品是否全部刊登成功，true全部成功,false还有没有成功的
     * @param $id
     * @return bool
     */
    private function getPublishStatus($id)
    {
        $where=[
            'pid'=>['=',$id],
            'status'=>['<>',1],
        ];
        $variant = (new PandaoVariant())->where($where)->field('status')->find();
        if($variant)
        {
            return false;
        }else{
            return true;
        }
    }

    public function updateProductAndVariant($parmas){
        try{
            if(isset($parmas['id']) && $parmas['id'])
            {
                $id = $parmas['id'];
                $this->validateUpdate($parmas);
                //获取商品刊登状态
                if($this->getPublishStatus($id))
                {
                    $response = $this->updatePublishedProduct($parmas);
                }else{
                    $response = $this->updateUnpublishedProduct($parmas);
                }
                return $response;
            }else{
                throw new JsonErrorException("id非法,找不到对应数据，无法进行更新");
            }

        }catch (Exception $exp){
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }

    /**
     * 删除刊登失败数据
     * @param $id
     */
    public function delete($id){
        $varaints = PandaoVariant::whereIn('pid',$id)->where('status','<>',1)->select();
        if($varaints){
            Db::startTrans();
            try{
                PandaoProduct::whereIn('id',$id)->delete();
                PandaoVariant::whereIn('pid',$id)->delete();
                PandaoProductInfo::whereIn('id',$id)->delete();
                Db::commit();
                return ['message'=>'删除成功！'];
            }catch (Exception $exp){
                Db::rollback();
                throw new JsonErrorException($exp->getMessage());
            }
        }else{
            throw new JsonErrorException("没有相关数据");
        }
    }

    /**
     * @param $id
     * @param $status
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public function getProductAndVariant($id,$status)
    {
        try{
            //获取PandaoProduct产品信息（线上）
            $where['id'] = ['eq', $id];
            $model = new PandaoProduct();
            $field='id,create_id,product_id,name,goods_id,main_image,tags,account_id,cron_time,parent_sku,brand,upc,warehouse';
            $data = $model->field($field)->with(['info','variants'])->where($where)->find();

            $user=[];
            if(!empty($data))
            {
                $data = $data->toArray();
                $data['tort_flag'] = GoodsTortDescription::where('goods_id',$data['goods_id'])->value('id') ? 1 : 0;
                $data['original_images']=$data['info']['original_images'];
                $data['extra_images']=$data['info']['extra_images'];
                $data['description']=$data['info']['description'];
                $data['landing_page_url'] = $data['info']['landing_page_url'];
//                dump(json_decode(json_encode($data['tort_flag']),true));die();
                if(isset($data['account_id']))
                {
                    $accountInfo = (new PandaoAccount())->field('account_name')->where(['id'=>$data['account_id']])->find();
                    $accountName=$accountInfo['account_name']?$accountInfo['account_name']:'';
                }else{
                    $accountName='';
                }

                $skuImages=[];
                //如果是COPY模式
                if($status=='copy') {
                    if(isset($data['goods_id']) && $data['goods_id']){
                        $goods_id = $data['goods_id'];
                        $productImages = GoodsImage::getPublishImages($goods_id,3);
                        $images=$productImages['spuImages'];
                        $skuImages = $productImages['skuImages'];
                    }else{
                        $images=[];
                    }
                }else{
                    if(!empty($data['original_images']))   //原始图片不为空则优先采用原始图片
                    {
                        $images=WishListingHelper::dealImages($data['original_images']);
                    }elseif(!empty ($data['extra_images'])){
                        $images=WishListingHelper::dealImages($data['extra_images'],$data['main_image']);
                    }else{
                        $images=[];
                    }
                }

                if($data['tags'])
                {
                    $tags = explode(',',$data['tags']) ;
                    if(is_array($tags))
                    {
                        $newTags = [];
                        foreach ($tags as $key => $tag)
                        {
                            $newTags[$key]['name']=$tag;
                        }
                    }
                } else {
                    $newTags=[];
                }

                $variants =$data['variants'];
                if($variants && $status=='copy' && $skuImages)
                {
                    $variants = GoodsImage::replaceSkuImage($variants,$skuImages,$this->channel_id,'sku_id');
                    $variants = GoodsImage::ObjToArray($variants);
                }

                foreach ($variants as &$variant){
                    $variant['refer_color']=$variant['refer_size']='无';
                    if(isset($variant['sku_id']) && $variant['sku_id'])
                    {
                        $sku = $this->getSkuAttr($variant['sku_id']);
                        if(isset($sku['color']) && $sku['color']){
                            $variant['refer_color'] = $sku['color'];
                        }
                        if(isset($sku['size']) && $sku['size']){
                            $variant['refer_size'] = $sku['size'];
                        }
                    }
                }

                $goods = Cache::store('goods')->getGoodsInfo($data['goods_id']);

                $user = Cache::store('user')->getOneUser($data['create_id']);

                if($variants){
                    $price = max(array_column($variants,'price'));

                    $shipping = max(array_column($variants,'shipping'));

                    $shipping_time = (new JoomService())->getMaxShippingTime((array_column($variants,'shipping_time')));

                    $inventory = max(array_column($variants,'inventory'));

                    $msrp = max(array_column($variants,'msrp'));

                    $cost = max(array_column($variants,'cost'));

                    $weight = max(array_column($variants,'weight'));
                }else{
                    $price=$shipping=$inventory=$msrp=$cost=$weight=0;
                    $shipping_time='';
                }

                $data['zh_name']=isset($goods['name'])?$goods['name']:'';
                $data['spu']=isset($goods['spu'])?$goods['spu']:'';
                $data['cost']=$cost;
                $data['weight']=$weight;
                $data['base_url']=Cache::store('configParams')->getConfig('innerPicUrl')['value'].DS;
                $data['vars'] = array(
                    array(
                        'account_id'=>$data['account_id'] ? $data['account_id'] : 0,
                        'realname'=>isset($user['realname'])?$user['realname']:'',
                        'parent_spu'=>$data['parent_sku'],
                        'account_name'=>$accountName,
                        'realname'=>$user?$user['realname']:'',
                        'name'=>$data['name'],
                        'inventory'=>$inventory,
                        'msrp'=>$msrp,
                        'price'=>$price,
                        'tags'=>$newTags ,
                        'shipping'=>$shipping,
                        'shipping_time'=>$shipping_time,
                        'cron_time'=>$data['cron_time']?$data['cron_time']:'',
                        'description'=>$data['description'],
                        'images'=> $images,
                        'variant'=>$variants
                    ),
                );

                $data['transport_property']= isset($goods['transport_property']) ? (new \app\goods\service\GoodsHelp())->getProTransPropertiesTxt($goods['transport_property']) : '';

                unset($data['info']);
                unset($data['variants']);
                unset($data['name']);
                unset($data['is_promoted']);
                unset($data['number_saves']);
                unset($data['review_status']);
                unset($data['number_sold']);
                unset($data['last_updated']);
                unset($data['tags']);
                unset($data['cron_time']);
                unset($data['description']);
                unset($data['extra_images']);
                unset($data['main_image']);

                if($data['warehouse']=='null'){
                    $data['warehouse']='';
                }

            }else{
                $data=[];
            }

        }catch (Exception $exp){
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }catch (\Throwable $exp){
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");

        }
        return $data;
    }

    /**
     * 获取sku属性
     * @param $sku_id
     * @return mixed
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getSkuAttr($sku_id)
    {
        $sku = Cache::store('goods')->getSkuInfo($sku_id);

        $sku_attributes = json_decode($sku['sku_attributes'], true);

        $sku['size'] = '';

        foreach ($sku_attributes as $attribute_id => $attribute_value_id)
        {
            list($attr, $attr_id) = explode('_', $attribute_id);//$attr_id //属性名

            $attrKeyVal = (new AttributeValue())->field('a.value,a.code vcode,b.code')->alias('a')->join('attribute b', 'a.attribute_id=b.id', 'LEFT')
                ->where(['a.id' => $attribute_value_id, 'a.attribute_id' => $attr_id])->find();

            if ($attrKeyVal)
            {
                //如果类型是type获取style,则取goods_attribute表里的alias
                if($attrKeyVal['code']=='type' || $attrKeyVal['code']=='style')
                {
                    $where=[
                        'goods_id'=>['=',$sku['goods_id']],
                        'attribute_id'=>['=',$attr_id],
                        'value_id'=>['=',$attribute_value_id]
                    ];

                    $goodsAttribute = GoodsAttribute::where($where)->find();

                    if($goodsAttribute)
                    {
                        if(strlen($sku['size']))
                        {
                            $sku['size']= $sku['size'].' & '.$goodsAttribute['alias'];
                        }else{
                            $sku['size']= $sku['size'].$goodsAttribute['alias'];
                        }

                    }
                } elseif ($attrKeyVal['code'] == 'color') {

                    //匹配wish platform color values
                    $joomColorValue = (new JoomAttributeValue())->where(['code' => $attrKeyVal['value'],'joom_attribute_id'=>1])->find();
                    if ($joomColorValue) {
                        $sku['color'] = $joomColorValue['code'];
                    } else {
                        $sku['color'] = $attrKeyVal['vcode'];
                    }

                } else {
                    if (count($sku_attributes) > 2)
                    {
                        if(strlen($sku['size'])>0)
                        {
                            $sku['size'] = $sku['size'] . ' & ' . $attrKeyVal['value'];
                        }else{
                            $sku['size'] = $attrKeyVal['value'];
                        }

                    } else {
                        $sku['size'] = $attrKeyVal['value'];
                    }
                }
            }
        }

        return $sku;
    }
    public function validateUpdate($post = array())
    {
        $validate = new PandaoValidate();

        $error = $validate->checkData($post,'create');

        if ($error)
        {
            throw new JsonErrorException($error);
        }
        if (isset($post['vars']))
        {

            $vars = json_decode($post['vars'], true);
            if (is_array($vars) && !empty($vars))
            {
                $error = $validate->checkVars($vars, 'var');
                if ($error)
                {
                    throw new JsonErrorException($error);
                }
            }
        }
    }
    /**
     * @node 验证提交数据合法性
     * @access public
     * @param array $post
     * @return string
     */
    public function validatePost($post = array())
    {

        $validate = new PandaoValidate();

        $error = $validate->checkData($post,'create');

        if ($error)
        {
            throw new JsonErrorException($error);
        }

        if (isset($post['vars']))
        {

            $vars = json_decode($post['vars'], true);
            if (is_array($vars) && !empty($vars))
            {
                $error = $validate->checkVars($vars, 'var');
                if ($error)
                {
                    throw new JsonErrorException($error);
                }
            }
        }
    }

    public function create($post){

        try {
            $this->validatePost($post);
            $vars=[];
            if (isset($post['vars']))
            {
                $vars = json_decode($post['vars'], true); //每个账号信息
            }
            if (isset($post['warehouse']) && $post['warehouse'] == 'null') {
                $post['warehouse'] = '';
            }
            $goodsSkuMapModel = new GoodsSkuMapService();
            $spu = $post['parent_sku'];
            $timestamp = time();
            $products=[];
            if (is_array($vars))
            {
                foreach ($vars as $k => $var)
                {

                    $products[$k]['goods_id'] = $post['goods_id'];  //商品id
                    $products[$k]['account_id'] = $var['account_id']; //账号id
                    $products[$k]['create_id'] = $post['uid']; //登录用户id
                    $products[$k]['name'] = $var['name']; //刊登标题
                    $products[$k]['main_image'] = $var['images'][0]; //商品主图
                    $products[$k]['description'] = $var['description'];//详情描述
                    $products[$k]['tags'] = $var['tags']; //Tags
                    $products[$k]['spu'] = $spu; //SPU
                    $products[$k]['parent_sku'] = $goodsSkuMapModel->createSku($spu); //SPU
                    $products[$k]['brand'] = $post['brand']; //品牌
                    $products[$k]['upc'] = $post['upc']; //UPC
                    $products[$k]['landing_page_url'] = $post['landing_page_url'] ? @$post['landing_page_url'] : ''; //商品展示页面
                    $products[$k]['extra_images'] = implode('|', array_slice($var['images'], 1)); //商品相册a.jpg|b.jpg
                    $products[$k]['original_images'] = implode('|', $var['images']); //商品原始图片
                    $products[$k]['cron_time'] = strtotime($var['cron_time']); //定时刊登
                    $products[$k]['warehouse'] = $post['warehouse'];
                    $products[$k]['last_updated'] = $timestamp;
                    $products[$k]['application'] = 'rondaful';
                    if(isset($var['variant']) && !empty($var['variant']))
                    {
                        $variants = $var['variant'];

                        foreach ($variants as &$v)
                        {
                            $v['sku_id'] = $v['id'];
                            $v['status'] = 0;
                            $v['local_sku']=$v['sku'];

//                            $create_sku_code_response = $goodsSkuMapModel->createSku($v['sku']);
//                            if ($create_sku_code_response) {
//                                $v['sku'] = $create_sku_code_response;
//                            }else{
//                                throw new JsonErrorException("生成随机sku编码失败");
//                            }

                            if(isset($v['combine_sku']) && !empty($v['combine_sku']))
                            {
                                $create_sku_code_response = $goodsSkuMapModel->addSkuCodeWithQuantity(['combine_sku'=>$v['combine_sku'],'sku_code' => $v['sku'], 'channel_id' => $this->channel_id, 'account_id' => $var['account_id']], $post['uid']);
                            }else{
                                $create_sku_code_response = $goodsSkuMapModel->addSku(['sku_code' => $v['sku'], 'channel_id' => $this->channel_id, 'account_id' => $var['account_id']], $post['uid']);
                            }

                            if ($create_sku_code_response['result']) {
                                $v['sku'] = $create_sku_code_response['sku_code'];
                            }

                        }
                    }
                    $products[$k]['variants'] = $variants;
                }
            }
            $num = 0;
            if ($products)
            {
                if (is_array($products))
                {
                    foreach ($products as $k => $p)
                    {
                        Db::startTrans();
                        try{
                            $variants = $p['variants'];

                            $productModel  = new PandaoProduct();

                            $productModel->allowField(true)->save($p);

                            $pid = $productModel->getLastInsID();

                            $p['id']= $pid;

                            (new PandaoProductInfo())->allowField(true)->save($p);

                            foreach ($variants as &$variant)
                            {
                                $variant['pid']=$pid;
                            }
                            $variantModel = new PandaoVariant();
                            if (count($variants) == count($variants, 1))
                            {
                                $variantModel->isUpdate(false)->allowField(true)->save($variants);
                            } else {
                                $variantModel->isUpdate(false)->allowField(true)->saveAll($variants);
                            }

                            Db::commit();
                            $queue = (string)$pid;
                            //非定时刊登
                            if ($p['cron_time'] <= time())
                            {
                                (new UniqueQueuer(PandaoQueueJob::class))->push($queue);
                            } else {
                                (new UniqueQueuer(PandaoQueueJob::class))->push($queue, $p['cron_time']);
                            }

                            $num = $num + 1;
                            if ($pid)
                            {
                                $update = $this->ProductStat($variantModel, ['pid' => $pid]);
                                if ($update)
                                {
                                    $productModel->update($update, ['id' => $pid]);
                                }
                            }
                            GoodsPublishMapService::update($this->channel_id, $spu, $p['account_id'],1);
                        }catch (PDOException $exp){
                            Db::rollback();
                            throw new JsonErrorException("File:{$exp->getFile()};Line{$exp->getLine()};Message:{$exp->getMessage()}");
                        }catch (DbException $exp){
                            Db::rollback();
                            throw new JsonErrorException("File:{$exp->getFile()};Line{$exp->getLine()};Message:{$exp->getMessage()}");
                        }catch (Exception $exp){
                            Db::rollback();
                            throw new JsonErrorException("File:{$exp->getFile()};Line{$exp->getLine()};Message:{$exp->getMessage()}");
                        }
                    }
                }
            }
            if($num==0)
            {
                $num='添加失败';
            }
            return $num;

        } catch (Exception $exp) {
            throw new JsonErrorException("File:{$exp->getFile()};Line{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }
    public  function ProductStat($variantModel,$where=[])
    {
        if(empty($where))
        {
            return false;
        }else{
            $v=[];
        }

        if($variantModel->where($where)->find())
        {
            $min_price = $variantModel->where($where)->min('price');
            $max_price = $variantModel->where($where)->max('price');
            $min_shipping = $variantModel->where($where)->min('shipping');
            $max_shipping = $variantModel->where($where)->max('shipping');
            $v['inventory'] = $variantModel->where($where)->sum('inventory');
            $v['lowest_price']=$min_price;
            $v['highest_price']=$max_price;
            $v['lowest_shipping']=$min_shipping;
            $v['highest_shipping']=$max_shipping;
        }else{
            $v=[];
        }

        return $v;
    }
    private function createWhere($params){
        $where=[];
        //刊登工具
        if (isset($params['application']) && is_numeric($params['application'])){
            if($params['application']==1){
                $where['application']=['eq','rondaful'];
            }else{
                $where['application']=['neq','rondaful'];
            }
        }
        //销售人员
        if (isset($params['account_id']) && $params['account_id']){
            $where['p.account_id']=['=',$params['account_id']];
        }
        if (isset($params['review_status']) && is_numeric($params['review_status'])){
            $where['p.review_status']=['=',$params['review_status']];
        }

        //销售人员
        if (isset($params['create_id']) && $params['create_id']){
            $where['p.create_id']=['=',$params['create_id']];
        }
        //刊登状态
        if (isset($params['publish_status']) && is_numeric($params['publish_status'])){
            $where['p.publish_status']=['=',$params['publish_status']];
        }
        //搜索条件
        if (isset($params['nType']) && $params['nType'] && isset($params['nContent']) && $params['nContent']){
            if($params['nType']=='product_id' ){
                $where['p.'.$params['nType']]=['=',$params['nContent']];
            }elseif($params['nType']=='name'){
                $where['p.'.$params['nType']]=['like',"%{$params['nContent']}%"];
            } elseif($params['nType']=='local_sku' || $params['nType']=='sku'){
                $where['v.'.$params['nType']]=['like',$params['nContent'].'%'];
            }elseif($params['nType']=='spu'){
                $where['p.'.$params['nType']]=['like',"{$params['nContent']}%"];
            }
        }

        if(isset($params['nTime']) && $params['nTime']){
            if (isset ($params['start_time']) && isset ($params['end_time']) && $params['end_time'] && $params['start_time'])
            {
                $params['start_time'] = $params['start_time'].'00:00:00';
                $params['end_time'] = $params['end_time'].'23:59:59';
                $where['p.'.$params['nTime']] = ['between time', [strtotime($params['start_time']), strtotime($params['end_time'])]];
            } elseif (isset ($params['end_time']) && $params['end_time']) {
                $where['p.'.$params['nTime']] = array('<=', strtotime($params['end_time'] . '23:59:59'));
            } elseif (isset($params['start_time']) && $params['start_time']) {
                $where['p.'.$params['nTime']] = array('>=', strtotime($params['start_time'] . '00:00:00'));
            }
        }
        //本地状态

        if(isset($params['spu_status']) &&  in_array($params['spu_status'],['1','2','3','4','5','6'])) {
            $where['g.sales_status'] = $params['spu_status'];
        }
        return $where;
    }

    /***
     * 查询列表
     * @param $params 查询条件
     * @param int $page 页码
     * @param int $pageSize 页数
     * @param string $fields 字段
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function lists($params,$page=1,$pageSize=30,$fields="*"){
        $where=$this->createWhere($params);
        $map=[];
        if(isset($params['enabled']) && is_numeric($params['enabled'])){
            $map['enabled']=['=',$params['enabled']];
        }
        if(isset($params['field']) && $params['field'] &&  isset($params['order']) && $params['order']){
            $field=$params['field'];
            $order = $params['order'];
        }else{
            $field='p.create_time';
            $order='DESC';
        }

        $model = new PandaoProduct();
        $total = $model->alias('p')->where($where)->where($map)
            ->join('pandao_variant v','p.id=v.pid','LEFT')
            ->join('goods g','g.id=p.goods_id','LEFT')
//            ->select(false);
            ->count('DISTINCT(p.id)');

        $data  = $model->alias('p')->where($where)->where($map)
            ->join('goods g','g.id=p.goods_id','LEFT')
            ->join('pandao_variant v','p.id=v.pid','LEFT')->with(['variants'=>function($query)use($map){
                $query->where($map);
            },'account'=>function($query){
                $query->field('id,code,account_name');
            },'user'=>function($query){
                $query->field('id,realname');
            },'goods'=>function($query){
                $query->field('id,spu');
            }])->field($fields)->order($field,$order)->page($page,$pageSize)->select();

        return ['data'=>$data,'page'=>$page,'pageSize'=>$pageSize,'total'=>$total];
    }

    /**
     * 根据SKU获取刊登过该SKU的销售员
     * @param $skuId
     * @return array
     */
    public static function getSalesmenBySkuId($skuId)
    {
        try {
            //根据sku获取对应的goods id
            $goodsIds = GoodsSku::where('id',$skuId)->value('goods_id');
            //根据goods id获取已刊登listing的销售员
            $wh['goods_id'] = $goodsIds;
            $wh['publish_status'] = 1;
            $salesmenIds = PandaoProduct::distinct(true)->where($wh)->column('create_id');
            return $salesmenIds;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * 根据商品id获取刊登过该商品的销售员
     * @param $skuId
     * @return array
     */
    public static function getSalesmenByGoodsId($goodsId)
    {
        try {
            $wh['goods_id'] = $goodsId;
            $wh['publish_status'] = 1;
            $salesmenIds = PandaoProduct::distinct(true)->where($wh)->column('create_id');
            return $salesmenIds;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * 获取刊登账号
     * @param $params
     * @return array
     * @throws Exception
     */
    public function getPublishAccount($params)
    {
        $userInfo = Common::getUserInfo();
        $userId = $userInfo['user_id'];
        try {
            //pandao暂未设置过滤器
//            $serverIp = gethostbyname($_SERVER['SERVER_NAME']);
//            //测试服和正式服过滤节点id不同，区别对待，避免测试出现问题
//            $nodeFlag = strpos($serverIp,'172.18.8.241')!==false || strpos($serverIp,'172.19.23')!==false;
//            $nodeId = $nodeFlag ? 345353 : 336578;//节点id,与【listing管理列表】节点保持一致
            //解析条件
            $wh = [];
//            $query = $params['query'];
            //不再分页，一次全部返回，前端分页
//            $page = (!isset($params['page']) || !is_numeric($params['page'])) ? 1 : $params['page'];
//            $pageSize = (!isset($params['pageSize']) || !is_numeric($params['pageSize'])) ? 50 : $params['pageSize'];

            $admin = (new Role())->isAdmin($userId) ||
                UserModel::where('id',$userId)->value('job') == 'IT';
            if (!$admin) {//不是管理员或IT人员
                $underlineUserIds = $this->getUnderlingInfo($userId);
                $wh['c.seller_id'] = ['in',$underlineUserIds];
            }
            if (($params['query'] ?? []) && ($params['queryType'] ?? [])) {//有查询条件
                $queryField = [
                    'seller' => 'u.realname',
                    'accountName' => 'a.account_name',
                    'accountCode' => 'a.code'
                ];
                $wh[$queryField[trim($params['queryType'])]] = ['like',$params['query'].'%'];
            }

            //查过滤器
//            $role = ErpRbac::getRbac($userId);
//            $filters = $role->getFilters($nodeId);
//            if ($filters) {//过滤器存在且有设置
//                foreach ($filters as $name => $filter) {
//                    if ($name != 'app\\publish\\filter\\EbayListingFilter') {
//                        continue;
//                    }
//                    if ($filter == '') {//过滤器关闭了，带出所有的账号
//                        unset($wh['c.seller_id']);
//                    } else {//获取过滤器设置
//                        if (!is_array($filter)) {
//                            continue;
//                        }
//                        if (count($filter) == 1 && $filter[0] == 0) {
//                            //看自己不做处理
//                            continue;
//                        } else {
//                            if (($key = array_search(0,$filter)) !== false) {//有设置看自己
//                                unset($filter[$key]);
//                                $whOr['c.account_id'] = ['in',$filter];
//                            } else {//如果没有设置看自己，则仅看设置的账号
//                                $wh['c.account_id'] = ['in',$filter];
//                                unset($wh['c.seller_id']);//不能绑定人员
//                            }
//                        }
//                    }
//                }
//            }

            $wh['u.status'] = 1;
            $wh['u.job'] = 'sales';
            $wh['a.is_invalid'] = 1;
            $wh['a.enabled'] = 1;
            $wh['c.channel_id'] = 8;
            $field = 'a.id,a.account_name,a.code,u.realname';

            if (isset($whOr)) {
                $accounts = \app\common\model\ChannelUserAccountMap::alias('c')
                    ->where($wh)->whereOr($whOr)->field($field)
                    ->join('user u','u.id=c.seller_id','LEFT')
                    ->join('pandao_account a','a.id=c.account_id','LEFT')
                    ->group('c.account_id')->order('a.code')->select();
                $count = \app\common\model\ChannelUserAccountMap::alias('c')
                    ->where($wh)->whereOr($whOr)->field($field)
                    ->join('user u','u.id=c.seller_id','LEFT')
                    ->join('pandao_account a','a.id=c.account_id','LEFT')
                    ->group('c.account_id')->count();
            } else {
                $accounts = \app\common\model\ChannelUserAccountMap::alias('c')->where($wh)
                    ->field($field)->join('user u','u.id=c.seller_id','LEFT')
                    ->join('pandao_account a','a.id=c.account_id','LEFT')
                    ->order('a.code')->group('c.account_id')->select();
                $count = \app\common\model\ChannelUserAccountMap::alias('c')->where($wh)
                    ->field($field)->join('user u','u.id=c.seller_id','LEFT')
                    ->join('pandao_account a','a.id=c.account_id','LEFT')
                    ->group('c.account_id')->count();
            }
            $publishedAccountIds = [];
            //是否刊登过
            if ($params['spu']??[]) {
                $goodsId = Goods::where('spu',$params['spu'])->value('id');
                $tmpWh = [
                    'goods_id' => $goodsId,
                    'channel' => 8,
                ];
                $publishedAccountIds = GoodsPublishMap::where($tmpWh)->value('publish_status');
                $publishedAccountIds = json_decode($publishedAccountIds,true) ?? [];
            }
            foreach ($accounts as &$account) {
                $account['published'] = in_array($account['id'],$publishedAccountIds) ? 1 : 0;
            }

            return [
                'data' => $accounts ? $accounts : [],
                'count' => $count,
//                'page' => $page,
//                'pageSize' => $pageSize
            ];
        } catch (\Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }



    /**
     * 申请将下载排进队列（通常情况下超过500条调用）
     * @param $params
     */
    public function applyExportByQueue($params)
    {
        $user_info = Common::getUserInfo();
        $cache = Cache::handler();
        $lastApplyTime = $cache->hget('hash:export_stock_apply',$user_info['user_id']);
        if ($lastApplyTime && time() - $lastApplyTime < 5) {
            throw new JsonErrorException('请求过于频繁',400);
        } else {
            $cache->hset('hash:export_stock_apply',$user_info['user_id'],time());
        }
        if (isset($params['date_type'])) {//spu刊登统计
//            $count = $this->spuStatistic($params,1);
            $count = count($params['ids']);
        } else {
            //统计需要导出的数据行
            $where = [];
            $group = [];
            $having = '';
            $this->where($params, $having,$where, $group,true);
            $where = is_null($where) ? [] : $where;
            $count = $this->doCount($where, $having,$group);
        }
        $params['file_name'] = ($user_info['user_id'].$user_info['realname'].'MyMall在售统计'.date("Y_m_d_H_i_s"));
        $params['export_type'] = isset($params['date_type']) ? 'spuPublishStatistic' : 'accountSpuStatistic';
        $params['count'] = $count;
        //走队列
        $model = new ReportExportFiles();
        $data['applicant_id'] = $user_info['user_id'];
        $data['apply_time'] = time();
        $data['export_file_name'] = $params['file_name'];
        $data['status'] = 0;
        $data['applicant_id'] = $user_info['user_id'];
        $model->allowField(true)->isUpdate(false)->save($data);
        $params['apply_id'] = $model->id;
        (new CommonQueuer(PandaoQueueJob::class))->push($params);
        return ['message'=>'成功加入导出队列，请稍后到报表管理栏下载'];

        return $this->export($params);
    }
    /**
     * @param $ids
     * @return mixed
     */
    public function reportExport(array $params, $inqueue = 0, $filename = '')
    {
//        $model = new PandaoProduct();
//        $products = $model->with(['variants','info'])->whereIn('id',$ids)->select();
//
//        $rows = [];
//        foreach ($products as $product){
//            $variants = $product['variants'];
//            foreach ($variants as $variant){
//                $row['parent_sku']=$product['parent_sku'];
//                $row['sku']=$variant['sku'];
//                $row['name']=$product['name'];
//                $row['tags']=$product['tags'];
//                $row['msrp']=$variant['msrp'];
//                $row['price']=$variant['price'];
//                $row['weight']=$variant['weight'];
//                $row['shipping']=$variant['shipping'];
//                $row['size']=$variant['size'];
//                $row['color']=$variant['color'];
//                $row['description']=mb_convert_encoding($product['info']['description'],'gb2312','utf-8');
//                $row['main_image']=$product['main_image'];
//                $row['display_image']=$variant['main_image'];
//                $images = explode('|',$product['info']['extra_images']);
//                foreach ($images as $k=>$image){
//                    if($k<10){
//                        $index = $k+1;
//                        $row['thumb'.$index] = $image;
//                    }
//                }
//                $rows[$variant['id']]= $row;
//            }
//        }
//        $header = [
//            ['title' => '父SKU', 'key' => 'parent_sku', 'width' => 10],
//            ['title' => 'SKU', 'key' => 'sku', 'width' => 10],
//            ['title' => '标题', 'key' => 'name', 'width' => 35],
//            ['title' => '标签', 'key' => 'tags', 'width' => 35],
//            ['title' => '厂商建议价', 'key' => 'msrp', 'width' => 10],
//            ['title' => '价格', 'key' => 'price', 'width' => 10],
//            ['title' => '重量', 'key' => 'weight', 'width' => 10],
//            ['title' => '运费', 'key' => 'shipping', 'width' => 10],
//            ['title' => '尺寸', 'key' => 'size', 'width' => 10],
//            ['title' => '颜色', 'key' => 'color', 'width' => 10],
//            ['title' => '描述/详情', 'key' => 'description', 'width' => 40],
//            ['title' => '主图', 'key' => 'main_image', 'width' => 20],
//            ['title' => '展图', 'key' => 'display_image', 'width' => 20],
//            ['title' => 'Extra Image URL 1', 'key' => 'thumb1', 'width' => 20],
//            ['title' => 'Extra Image URL 2', 'key' => 'thumb2', 'width' => 20],
//            ['title' => 'Extra Image URL 3', 'key' => 'thumb3', 'width' => 20],
//            ['title' => 'Extra Image URL 4', 'key' => 'thumb4', 'width' => 20],
//            ['title' => 'Extra Image URL 5', 'key' => 'thumb5', 'width' => 20],
//            ['title' => 'Extra Image URL 6', 'key' => 'thumb6', 'width' => 20],
//            ['title' => 'Extra Image URL 7', 'key' => 'thumb7', 'width' => 20],
//            ['title' => 'Extra Image URL 8', 'key' => 'thumb8', 'width' => 20],
//            ['title' => 'Extra Image URL 9', 'key' => 'thumb9', 'width' => 20],
//            ['title' => 'Extra Image URL 10', 'key' => 'thumb10', 'width' => 20],
//        ];
//        $userId = Common::getUserInfo()->toArray()['user_id'];
//        $file = [
//            'name' => (new ProductDownloadService())->createExportFileName($userId,0),
//            'path' => 'goods'
//        ];
//        $ExcelExport = new DownloadFileService();
//        return $ExcelExport->exportCsv($rows, $header, $file);

        $where=$this->createWhere($params);
        $map=[];
        if(isset($params['enabled']) && is_numeric($params['enabled'])){
            $map['enabled']=['=',$params['enabled']];
        }
        if(isset($params['field']) && $params['field'] &&  isset($params['order']) && $params['order']){
            $field=$params['field'];
            $order = $params['order'];
        }else{
            $field='p.create_time';
            $order='DESC';
        }
        $fields = "DISTINCT(v.vid),p.*";
        $header = [
            ['title' => '父SKU', 'key' => 'parent_sku', 'width' => 10],
            ['title' => 'SKU', 'key' => 'sku', 'width' => 10],
            ['title' => '标题', 'key' => 'name', 'width' => 35],
            ['title' => '标签', 'key' => 'tags', 'width' => 35],
            ['title' => '厂商建议价', 'key' => 'msrp', 'width' => 10],
            ['title' => '价格', 'key' => 'price', 'width' => 10],
            ['title' => '重量', 'key' => 'weight', 'width' => 10],
            ['title' => '运费', 'key' => 'shipping', 'width' => 10],
            ['title' => '尺寸', 'key' => 'size', 'width' => 10],
            ['title' => '颜色', 'key' => 'color', 'width' => 10],
            ['title' => '描述/详情', 'key' => 'description', 'width' => 40],
            ['title' => '主图', 'key' => 'main_image', 'width' => 20],
            ['title' => '展图', 'key' => 'display_image', 'width' => 20],
            ['title' => 'Extra Image URL 1', 'key' => 'thumb1', 'width' => 20],
            ['title' => 'Extra Image URL 2', 'key' => 'thumb2', 'width' => 20],
            ['title' => 'Extra Image URL 3', 'key' => 'thumb3', 'width' => 20],
            ['title' => 'Extra Image URL 4', 'key' => 'thumb4', 'width' => 20],
            ['title' => 'Extra Image URL 5', 'key' => 'thumb5', 'width' => 20],
            ['title' => 'Extra Image URL 6', 'key' => 'thumb6', 'width' => 20],
            ['title' => 'Extra Image URL 7', 'key' => 'thumb7', 'width' => 20],
            ['title' => 'Extra Image URL 8', 'key' => 'thumb8', 'width' => 20],
        ];

        $fullName = 'MyMallListing'. date('YmdHi'). $this->buildExportName($params);

        $model = new PandaoProduct();
        $count = $model->alias('p')->where($where)->where($map)->join('pandao_variant v','p.id=v.pid','LEFT')->join('goods g','g.id=p.goods_id','LEFT')->count('DISTINCT(v.vid)');

        $file = [
            'name' => $fullName,
            'path' => 'MyMall',
            'add_time_ext' => false
        ];

        if ($count <= 500){//小于500条
//            $products = $model->with(['variants','info'])->whereIn('id',$ids)->select();
//            $products = $model->$model->alias('p')->where($where)->where($map)
//                ->join('goods g','g.id=p.goods_id','LEFT')
//                ->join('pandao_variant v','p.id=v.pid','LEFT')->with(['variants','info'])->order($field,$order)->select();

            $rows = $this->getExportData($where,$map,$fields,$order,$field, 1, $count);
            $userId = Common::getUserInfo()->toArray()['user_id'];
            $file = [
                'name' => (new ProductDownloadService())->createExportFileName($userId,0),
                'path' => 'goods'
            ];

            return DownloadFileService::exportCsv($rows, $header, $file,1);

        }else{//大于500条走队列
            if ($inqueue == 0) {
                $this->applyExportOrderToQueue($params, $fullName);
                return ['status' => 2, 'message' => '下载任务已加入执行队列'];
            } else {
                if (!empty($fileName)) {
                    $file['name'] = $fileName;
                }
                $modelCondition = [
                    'service' => \app\publish\service\PandaoService::class,
                    'method' => 'getExportData',
                    'count' => $count,
                    'field' => $fields,//查询的字段
                    'where' => $where,//查询条件
                    'map' => $map,
                    'sort' => ['order',$field => $order]//排序字段
                ];
                return DownloadFileService::exportCsvToZipByMethod($modelCondition, $header, $file);
            }
        }
    }

    /**
     * 获取导出文件名字
     * combineWhere
     * @param array $param
     * @param $params
     * @return string
     * @throws Exception
     */
    public function buildExportName($params)
    {
        $str = '';
        $snTypeArr = [
            'spu' => '本地SPU',
            'local_sku' => '本地SKU',
            'sku' => '平台SKU',
            'product_id' => '平台产品ID',
            'name' => '刊登标题',
        ];
        if (!empty($params['nType'])) {
            $str .= '('. ($snTypeArr[$params['nType']] ?? '-' ) . '_'. $params['nType']. ')';
        }

        if (!empty($params['account_id'])) {
            $account = Cache::store('PandaoAccountCache')->getAccount($params['account_id']);
            $str .= '(帐号简称_'. $account['code'] . ')';
        }

        return $str;
    }

    public function applyExportOrderToQueue($params, $fullname)
    {
        $exportFileName = mb_substr($fullname, 0, 70);
        $model = new ReportExportFiles();
        $user = Common::getUserInfo();
        $model->applicant_id     = $user['user_id'];
        $model->apply_time       = time();
        $model->export_file_name = $exportFileName.'.zip';

        $model->status =0;
        if (!$model->save()) {
            throw new Exception('导出请求创建失败');
        }
        $params['file_name'] = $exportFileName;
        $params['apply_id'] = $model->id;
        $queue = new UniqueQueuer(PandaoExportListingQueue::class);
        $queue->push($params);
    }

    public function allExport($params = [])
    {
        try {
            set_time_limit(0);
            ini_set('memory_limit','1024M');

            $result = $this->dowonload($params, 1, $params['file_name']);
            if(!$result['status']) {
                throw new Exception($result['message']);
            }
            if(is_file($result['file_path'])){
                $applyRecord = ReportExportFiles::get($params['apply_id']);
                $applyRecord->exported_time = time();
                $applyRecord->download_url = $result['download_url'];
                $applyRecord->status = 1;
                $applyRecord->isUpdate()->save();
            } else {
                throw new Exception('文件写入失败');
            }
        }catch (Exception $e){
            $applyRecord = ReportExportFiles::get($params['apply_id']);
            $applyRecord->status = 2;
            $applyRecord->error_message = $e->getMessage();
            $applyRecord->isUpdate()->save();
            throw new Exception($e->getMessage(). '|'. $e->getLine(). '|'. $e->getFile());
        }
    }

    /**
     * 导出时获取方法
     * @param $where
     * @param $fields
     * @param $page 状用page方法来分页，而不是limit
     * @param $pageSize
     */
    public function getExportData($where,$map,$fields,$order,$field, $page, $pageSize)
    {
        $products  = ( new PandaoProduct())->alias('p')->where($where)->where($map)
            ->join('goods g','g.id=p.goods_id','LEFT')
            ->join('pandao_variant v','p.id=v.pid','LEFT')->with([
                'variants'=>function($query)use($map){
                $query->where($map);
                },'info']
            )->field($fields)->order($field,$order)->page(1,$pageSize)->select();
        $rows = [];
        foreach ($products as $product){
            $variants = $product['variants'];
            foreach ($variants as $variant){
                $row['parent_sku']=$product['parent_sku'];
                $row['sku']=$variant['sku'];
                $row['name']=$product['name'];
                $row['tags']=$product['tags'];
                $row['msrp']=$variant['msrp'];
                $row['price']=$variant['price'];
                $row['weight']=$variant['weight'];
                $row['shipping']=$variant['shipping'];
                $row['size']=$variant['size'];
                $row['color']=$variant['color'];
                $row['description']= str_replace(PHP_EOL, '',mb_convert_encoding($product['info']['description'],'gb2312','utf-8'));
                $row['main_image']=$product['main_image'];
                $row['display_image']=$variant['main_image'];
                $images = explode('|',$product['info']['extra_images']);
                foreach ($images as $k=>$image){
                    if($k<8){
                        $index = $k+1;
                        $row['thumb'.$index] = $image;
                    }
                }
                $rows[$variant['vid']]= $row;
            }
        }
        return $rows;
    }

}