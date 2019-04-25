<?php
namespace app\publish\service;

use app\common\exception\JsonErrorException;
use think\Exception;
use think\Db;
use app\common\model\Goods;
use app\common\cache\Cache;
use app\common\model\GoodsGallery;
use app\publish\service\GoodsHelp as GoodsHelpServer;
use think\File;
use think\Image;
use app\common\model\GoodsSku;
use app\goods\service\GoodsImage as GoodsImageService;

/**
 * Class GoodsImage
 * @package app\goods\service
 */
class GoodsImage
{   
    public function saveNetImages($goods_id, $images)
    {
        $goods  = Goods::field('thumb,spu')->where(['id' => $goods_id])->find();
        if (!$goods) {
            throw new Exception('产品不存在');
        }
        // 事务启动
        Db::startTrans();
        try {           
            // 保存图片
            $return=[];
            
            foreach($images as $k=>$image) 
            {
               $return[$k]= $this->handleNetImage($goods['spu'], $goods_id, $image); 
            }
            // Db 提交
            Db::commit();
        } catch (Exception $ex) {
            Db::rollBack();
            throw new Exception($ex->getMessage());
        }   
       return $return;
        
    }
    
    /**
     * 处理图片
     * @param string $spu
     * @param int $goods_id
     * @param array $image
     */
    private function handleNetImage($spu, $goods_id, &$image)
    {
        
        $name = uniqid(); 
        $ext = 'jpg';
        
        $filename = $this->saveNetPic($spu, $image, $name, $ext);
         
//        if(stripos($filename, 'http') ==false)
//        {
//            $filename=$_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].'/upload/'.$filename;
//        }
        
        $goodsGallery = new GoodsGallery();
        $data = [
            'goods_id'     => $goods_id, 
            'attribute_id' => 0, 
            'value_id'     => 0, 
            'path'         => $filename,
            'sort'         => '',
            'is_default'   => 0
        ];
           
        if($goodsGallery->allowField(true)->save($data))
        {
            return GoodsImageService::getThumbPath($filename,0);
        }
    }
    
    /**
     * 保存图片
     * @param string $spu
     * @param stirng $image
     * @param stirng $name
     * @param string $ext
     * @return string
     * @throws Exception
     */
    private function saveNetPic($spu, $image, $name, $ext)
    {
        $base_path = ROOT_PATH.'/public/upload';
          
//        if (8 != strlen($spu)) {
//            throw new Exception('spu的格式不对');
//        }
          
        $dir = substr($spu, 0, 4) . '/' . substr($spu, 4, 2) . '/' . substr($spu, 6);
        if (!is_dir($base_path . '/' . $dir) && !mkdir($base_path . '/' . $dir, 0777, true)) {
            throw new Exception('目录创建不成功');
        }
 
        if (!in_array($ext, ['jpg', 'gif', 'png', 'jpeg'])) {
            throw new Exception('图片格式不对');
        }        
        
        $data = file_get_contents($image);
        
        if (strpos('<?php', $data)) {
            throw new Exception('上传内容有敏感信息');
        }
        $fileName = $name . '.' . $ext;
        file_put_contents($base_path . '/' . $dir . '/' . $fileName, $data);
        $this->thumb($base_path . '/' . $dir . '/' . $fileName, 100, 100);
        return $dir . '/' .$fileName;
    }
    
    /**
     * 保存新建的资源
     *
     * @param int $goods_id
     * @param array $images
     * @throws Exception
     */
    public function save($goods_id, &$images)
    {
        $goods  = Goods::field('thumb,spu')->where(['id' => $goods_id])->find();
        if (!$goods) {
            throw new Exception('产品不存在');
        }
        $delList = [];
        //$this->check($goods_id, $images, $delList);
        // 事务启动
        Db::startTrans();
        try {           
            // 保存图片
            $return=[];
            foreach($images as $k=>$image) 
            {
                if (empty($image['id'])) { // 添加图片
                    $return[$k]= $this->handle($goods['spu'], $goods_id, $image);
                } else {
                    $this->updatePic($image);
                }
            }
            if ($delList) { // 删除图片
                foreach($delList as $list) {
                   $this->deletePic($list);
                }
            }
            // Db 提交
            Db::commit();
        } catch (Exception $ex) {
            Db::rollBack();
            throw new Exception($ex->getMessage());
        }   
       return $return;
    }
    
    /**
     * 处理图片
     * @param string $spu
     * @param int $goods_id
     * @param array $image
     */
    private function handle($spu, $goods_id, &$image)
    {
        if (isset($image['name'])) {
            //list($name, $ext) = explode('.', $image['name']);
            $name = pathinfo($image['name'],PATHINFO_FILENAME);
            $tmp = explode('.', $image['name']);
            $ext = strtolower(end($tmp));
        } else {
            $name = uniqid(); 
            $ext = 'jpg';
        }
        //throw new Exception('name:'.$name.'---ext:'.$ext);
       
        if (!isset($image['attribute_id'])) {
            $image['attribute_id'] = 0;
        }
        if (!isset($image['value_id'])) {
            $image['value_id'] = 0;
        }
        if (!isset($image['is_default'])) {
            $is_default = 0;
        } else {
            $is_default = $image['is_default'];
        }
        if (!isset($image['sort'])) {
            $image['sort'] = 99;
        }
        $filename = $this->savePic($spu, $image['image'], $name, $ext);
         
//        if(stripos($filename, 'http') ==false)
//        {
//            $filename=$_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].'/upload/'.$filename;
//        }
        
        $goodsGallery = new GoodsGallery();
        $data = [
            'goods_id'     => $goods_id, 
            'attribute_id' => $image['attribute_id'], 
            'value_id'     => $image['value_id'], 
            'path'         => $filename,
            'sort'         => $image['sort'],
            'is_default'   => $is_default
        ];
        
        if ($is_default) 
        {
            $this->updateMainPic($goods_id, $image['attribute_id'], $image['value_id'], $filename);
        }    
        
        if($goodsGallery->allowField(true)->save($data))
        {
            return GoodsImageService::getThumbPath($filename,0);
        }
    }
    
    /**
     * 保存图片
     * @param string $spu
     * @param stirng $image
     * @param stirng $name
     * @param string $ext
     * @return string
     * @throws Exception
     */
    private function savePic($spu, $image, $name, $ext)
    {
        $base_path = ROOT_PATH.'/public/upload';
         
//        if (8 != strlen($spu)) {
//            throw new Exception('spu的格式不对');
//        }
          
        $dir = substr($spu, 0, 4) . '/' . substr($spu, 4, 2) . '/' . substr($spu, 6);
        if (!is_dir($base_path . '/' . $dir) && !mkdir($base_path . '/' . $dir, 0777, true)) {
            throw new Exception('目录创建不成功');
        }
        
        if (!in_array($ext, ['jpg', 'gif', 'png', 'jpeg'])) {
            throw new Exception('图片格式不对');
        }        
        $start=strpos($image,',');
        $img= substr($image,$start+1);
        $img = str_replace(' ', '+', $img);
        $data = base64_decode($img);
        if (strpos('<?php', $data)) {
            throw new Exception('上传内容有敏感信息');
        }
        $fileName = $name . '.' . $ext;
        file_put_contents($base_path . '/' . $dir . '/' . $fileName, $data);
        $this->thumb($base_path . '/' . $dir . '/' . $fileName, 100, 100);
        return $dir . '/' .$fileName;
    }
    
    /**
     * 检测删除
     * @param int $goods_id
     * @param array $image
     * @param array $delList
     */
    private function check($goods_id, &$images, &$delList) 
    {
        $searchLists = GoodsGallery::where(['goods_id' => $goods_id])->select();
        
        $lists = [];
        
        foreach($searchLists as $list) {
            $list = $list->toArray();
            $lists[$list['id']] = $list;
        }
        
        foreach($images as $k => &$image) {
            if(!empty($image['id']) && isset($lists[$image['id']])) {
                if ($image['is_default'] == $list['is_default'] && $image['sort'] == $list['sort']) {
                    unset($images[$k]);
                }
                $image['old_is_default'] = $lists[$image['id']]['is_default'];
                $image['path'] = $lists[$image['id']]['path'];
                $image['goods_id'] = $lists[$image['id']]['goods_id'];
                unset($lists[$image['id']]);
            }
        }
        
        $delList = $lists;
    }
    
    /**
     * 删除图片记录
     * @param array $list
     */
    private function deletePic($list)
    {
        $base_path = ROOT_PATH.'public/upload/';
        $fileNameArr = explode('.', $list['path']);
        $thumb = $fileNameArr[0] . '_100x100.'. $fileNameArr[1];
        $gallery = new GoodsGallery();
        $gallery->where(['id' => $list['id']])->delete();
        @unlink($base_path . $list['path']);
        @unlink($base_path . $thumb);
    }
    
    /**
     * 更新图片信息
     * @param array $list
     */
    private function updatePic($list)
    {
        $gallery = new GoodsGallery();
        $gallery->where(['id' => $list['id']])->update(['is_default' => $list['is_default'], 'sort' => $list['sort']]);
        if ($list['is_default'] == 1 && $list['old_is_default'] == 0) {
            $this->updateMainPic($list['goods_id'], $list['attribute_id'], $list['value_id'], $list['path']);
        }
    }
    
    /**
     * 上传图片保存
     * @param string $spu
     * @param File $file
     * @return string
     * @throws Exception
     */
    private function uploadPic($spu, File $file)
    {
        $base_path = ROOT_PATH.'/public/upload';
        if (8 != strlen($spu)) {
            throw new Exception('spu的格式不对');
        }
        $dir = substr($spu, 0, 4) . '/' . substr($spu, 4, 2) . '/' . substr($spu, 6);
        if (!is_dir($base_path . '/' . $dir) && !mkdir($base_path . '/' . $dir, 0666, true)) {
            throw new Exception('目录创建不成功');
        }
        $info     = $file->validate(['ext' => 'jpg,gif,png'])->move($base_path . '/' . $dir , $file->getInfo('name'), false);
        if (!$info) {
            throw new Exception($file->getError());
        }
        $this->thumb($base_path . '/' . $dir . '/' . $file->getInfo('name'), 100, 100);
        return $dir . '/' . $info->getFilename();
    }
    
    /**
     * 更新产品 sku主图
     * @param int $goods_id
     * @param int $attribute_id
     * @param int $value_id
     * @param string $thumb
     */
    private function updateMainPic($goods_id, $attribute_id, $value_id, $thumb)
    {
        if ($attribute_id) {
            $where = 'goods_id = '. $goods_id . ' AND sku_attributes->"$.attr_'. $attribute_id. '" = '. $value_id;
        } else {
            $where = 'goods_id = '. $goods_id . ' AND JSON_LENGTH(sku_attributes) = 0';
        }
        $lists = GoodsSku::where($where)->select();
        foreach($lists as $list) {
            GoodsSku::where(['id' => $list['id']])->update(['thumb' => $thumb]);
            Cache::handler()->hdel('cache:Sku', $list['id']);
        }
        if (!$attribute_id) {
            Goods::where(['id' => $goods_id])->update(['thumb' => $thumb]);
            Cache::handler()->hdel('cache:Goods', $goods_id);
        }
    }
    
    /**
     * 生成图片缩略图
     * @param string $file
     * @param \app\goods\controller\width $length
     */
    private function thumb($file, $width, $length)
    {
        $image = Image::open($file);
        $arr_ext = explode('.',$file);
        $image->thumb($width, $length)->save($arr_ext[0].'_'. $width .'x'. $length . '.' . $arr_ext[1]);
        return true;
    }
    
    /**
     * 获取图片列表
     * @param int $id
     * @param string $domain
     * @return array
     */
    public function getLists($id, $domain)
    {
        $base_path = 'upload';
        $goodsHelp = new GoodsHelpServer();
        $attributes = $goodsHelp->getAttributeInfo($id, 2);
        $search_images = GoodsGallery::where(['goods_id' => $id])->order('is_default desc, sort asc')->select();
        $images = [];
        foreach($search_images as $image) {
            $image['thumb'] = $domain .'/' . $base_path . '/' . $this->getThumbName($image['path'], 100, 100);
            $image['path'] = $domain.'/'. $base_path . '/' . $image['path'];            
            $images[$image['attribute_id']. '-' . $image['value_id']][] = $image;
        }
        $lists[] = [
            'name' => '主图',
            'attribute_id' => 0,
            'value_id' => 0,
            'images' => isset($images['0-0']) ? $images['0-0'] : []
        ];
        foreach($attributes as $attribute) {
            if ($attribute['gallery']==0) {
                continue;
            }
            foreach($attribute['attribute_value'] as $value) {
                if ($value['selected'] == false) {
                    continue;
                }
                $list = [
                    'name' => $attribute['name'] . ' '. $value['value'],
                    'attribute_id' => $attribute['attribute_id'],
                    'value_id' => $value['id'],
                    'images'   => isset($images[$attribute['attribute_id']. '-'. $value['id']]) ? $images[$attribute['attribute_id']. '-'. $value['id']] : []
                ];
                $lists[] = $list;
            }
        }
        
        return $lists;
    }
    
    /**
     * 获取缩略图路径
     * @param string $filename
     * @param int $width
     * @param int $height
     * @return string
     */
    private function getThumbName($filename, $width, $height) 
    {
        $filenameArr = explode('.', $filename);
        if (count($filenameArr) != 2) {
            return $filename;
        }
        return $filenameArr[0] . '_'.$width . 'x' . $height . '.' .$filenameArr[1];
    }
    
    public static function getAllImages($where=array(),$fields="*",$limit=15)
    {
        $GoodsGallery = new GoodsGallery();
        if($limit)
        {
            $gallerys = $GoodsGallery->field($fields)->where($where)->group('path')->limit($limit)->select();
        }else{
            $gallerys =  $GoodsGallery->field($fields)->where($where)->group('path')->select();
        }
        return $gallerys;
    }

    /**
     * 获取商品相册
     */
    public function getGoodsGallery($goods_id=0,$fields="*")
    {
        $gallerys = self::getAllImages(['goods_id'=>$goods_id],$fields,0);

        if($gallerys)
        {
            foreach ($gallerys as &$gallery)
            {
                $gallery['path']=GoodsImageService::getThumbPath($gallery['path'], 200, 200);
            }
            return $gallerys;
        }else{
            return [];
        }

    }

    /**
     * 获取刊登图片
     * @param $goods_id 商品id
     * @param $channel_id 平台id
     */
    public static function getPublishImages($goods_id,$channel_id)
    {
        //sku图片

        $goodsInfomation = (new Goods())->where('id',$goods_id)->find();

        $develop_id = $goodsInfomation['developer_id'];

        $skus = self::getSkuImages($goods_id,$channel_id,$develop_id);

        $skuImages = [];

        if($skus)
        {
            foreach ($skus as $sku)
            {
                $sku = is_object($sku)?$sku->toArray():$sku;
                $skuImages[] = $sku;
            }
        }

//        $imagePath = self::getSkuImagePath($skuImages);

        //商品主图
        $mainImage = self::getMainImage($goods_id,$channel_id,$develop_id);

//        if($mainImage && $imagePath)
//        {
//            array_push($imagePath,$mainImage['path']);
//        }


//        $extraImages = self::getExtraImages($goods_id,$channel_id,$develop_id,$imagePath);
        $extraImages = [];
        $spuImages=[];

//        $extraImages= self::ObjToArray($extraImages);

        if($mainImage && $extraImages)
        {
            $firstImage[]= self::ObjToArray($mainImage);
            $spuImages=array_merge($firstImage,$extraImages);
        }elseif ($mainImage){
            $firstImage = self::ObjToArray($mainImage);
            $spuImages=$firstImage;
        }elseif($extraImages){
            $spuImages=$extraImages;
        }

        if (!empty($spuImages))
        shuffle($spuImages);

        return ['skuImages'=>$skuImages,'spuImages'=>$spuImages];

    }

    /**
     * @info 对象转换成数组
     * @param unknown $obj
     */
    public static function ObjToArray($obj)
    {
        return json_decode(json_encode($obj),true);
    }

    /**
     * 获取附图
     * @param $goods_id
     * @param $channel_id
     * @param $develop_id
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public static function getExtraImages($goods_id,$channel_id,$develop_id,$imagePath)
    {
        //商品主图
        $where=[
            'goods_id'=>['=',$goods_id],
            'channel_id'=>['=',$channel_id],
            'is_default'=>['IN',[1,2]],
            'path'=>['NOT IN',$imagePath]
        ];

        $extraImages = (new GoodsGallery())->field('*')->where($where)->select();

        if(empty($extraImages))
        {
            $where=[
                'goods_id'=>['=',$goods_id],
                'channel_id'=>['=',$develop_id],
                'is_default'=>['IN',[1,2]],
                'path'=>['NOT IN',$imagePath]
            ];
            //商品主图
            $extraImages = (new GoodsGallery())->field('*')->where($where)->select();
        }

        return $extraImages;
    }

    /**
     * 根据sku_id获取所有的sku图片
     * @param $sku_id
     * @return array
     */
    public static function getSkuImagesBySkuId($sku_id,$channel_id,$develop_id)
    {
        //商品主图
        $where=[
            'sku_id'=>['=',$sku_id],
            'channel_id'=>['=',$channel_id],
            'is_default'=>['=',1],
        ];

        $images = (new GoodsGallery())->field('*')->where($where)->select();

        if(empty($images))
        {
            $where=[
                'sku_id'=>['=',$sku_id],
                'channel_id'=>['=',$develop_id],
                'is_default'=>['=',1],
            ];
            //商品主图
            $images = (new GoodsGallery())->field('*')->where($where)->select();
        }

        return $images;
    }
    /**
     * 获取sku图片
     */
    public static function getSkuImages($goods_id,$channel_id,$develop_id)
    {
        //先查通用图片和对应平台图片
        $skus = (new GoodsSku())->with(['image'=>function($query)use($channel_id){$query->field('*')->where('channel_id','in',[0,$channel_id]);}])->order('rand()')->where(['goods_sku.goods_id'=>$goods_id])->select();

        if(empty($skus))
        {
            //查所有平台图片
            $skus = (new GoodsSku())->with(['image'=>function($query)use($develop_id){$query->field('*');}])->order('rand()')->where(['goods_sku.goods_id'=>$goods_id])->select();
        }

        if(empty($skus))
        {
            $skus = (new GoodsSku())->field('*,thumb path')->where('goods_id',$goods_id)->select();
        }else{
            $skus = self::ObjToArray($skus);

            foreach ($skus as &$sku)
            {
                if($sku['image'])
                {
                    $sku['path'] = $sku['image']['path'];
                }else{
                    $sku['path'] = $sku['thumb'];
                }
            }
        }

        return $skus;

    }

    /**
     * 获取主图
     */
    public static function getMainImage($goods_id,$channel_id,$develop_id)
    {
        //商品主图
        $mainImage = (new GoodsGallery())->field('*')->order('rand()')
            ->where(['goods_id'=>$goods_id,'channel_id'=>['in',[0,$channel_id]]])->select();

        if(empty($mainImage) || $mainImage=='NULL')
        {
            //商品主图
            $mainImage = (new GoodsGallery())->field('*')->order('rand()')
                ->where(['goods_id'=>$goods_id])->select();
        }
        if(empty($mainImage) || $mainImage=='NULL')
        {
            $mainImage=[];
        }
        //去重
        $uniqueImgs = [];
        foreach ($mainImage as $img) {
            $uniqueImgs[$img['unique_code']] = $img;
        }
        return array_values($uniqueImgs);
    }
    /**
     * 获取sku图片的路径
     * @param $images
     */
    public static function getSkuImagePath($images)
    {
        $path=[];
        if(empty($images))
        {
            return $path;
        }
        foreach ($images as $image)
        {
            $path[]=$image['path'];
        }
        return $path;
    }

    /**
     * 给sku图片赋值
     * @param $attributes sku属性
     * @param $sku sku对象
     */
    public static function assignSkuImage($attributes,$sku,$value=null)
    {

        if($attributes)
        {
            foreach ($attributes  as $k=>$attrbute)
            {
                if(isset($attrbute['customized_pic']) && $attrbute['customized_pic']==1)
                {
                    $sku['sku_attributes'][$k]['thumb'] = $value?$sku['thumb']:'';
                    $sku['sku_attributes'][$k]['custom_pic'] = $value?$sku['thumb']:'';
                }
            }
        }
        return $sku;


    }
    /**
     * 替换sku中的图片
     * @param $skus
     * @param $skuImages
     */
    public static function replaceSkuImage($skus,$skuImages,$channel_id,$sku_id_name='id')
    {
        try{

            foreach ($skus as &$sku)
            {
                 if(isset($sku['variation_sku'])){
                     $sku['sku']=$sku['variation_sku'];
                 }
                $sku = self::whetherIsCustomedPic($skuImages,$sku,$channel_id,$sku_id_name);
            }
            return $skus;
        }catch (JsonErrorException $exp){
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }

    public static function whetherIsCustomedPic($skuImages,&$sku,$channel_id,$sku_id_name)
    {

        foreach ($skuImages as $image)
        {
            if($sku[$sku_id_name] == $image['id'] )
            {

                switch ($channel_id)
                {
                    case 3:
                        $sku['main_image'] =$image['path'];
                        break;
                    case 4:
                        $sku['thumb'] =$image['path'];
                        break;
                }

                if(isset($sku['sku_attributes']) && $sku['sku_attributes'])
                {
                    $attributes = $sku['sku_attributes'];
                    if(!is_array($attributes))
                    {
                        $attributes = json_decode($attributes,true);
                    }
                    $sku = self::assignSkuImage($attributes,$sku,true);
                }
                return $sku;
            }else{
                switch ($channel_id)
                {
                    case 3:
                        $sku['main_image'] ='';
                        break;
                    case 4:
                        $sku['thumb'] ='';
                        break;
                }
                if(isset($sku['sku_attributes']) && $sku['sku_attributes'])
                {
                    $attributes = $sku['sku_attributes'];
                    if(!is_array($attributes))
                    {
                        $attributes = json_decode($attributes,true);
                    }
                    $sku = self::assignSkuImage($attributes,$sku,false);
                }
            }
        }

        return $sku;
    }
}




