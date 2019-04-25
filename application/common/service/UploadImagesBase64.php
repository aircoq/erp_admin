<?php
/**
 * Created by PhpStorm.
 * User: Reece
 * Date: 2019/1/9
 * Time: 17:30
 */
namespace app\common\service;
use think\Exception;

class UploadImagesBase64
{
    private $path;
    private $sizeLimit;
    private $extLimit;
    private $maxQty;
    private $oldImages;
    private $images;

    public function __construct(string $path, array $images, array $oldImages)
    {
        $this->path = $path;
        $this->maxQty = 5;
        $this->images = $images;
        $this->oldImages = $oldImages;
        $this->extLimit = ['jpg', 'jpeg', 'png'];
        $this->sizeLimit = 2*1024*1024;
    }

    public function setSizeLimit(int $size)
    {
        $this->sizeLimit = $size;
    }

    public function setExtLimit(array $ext)
    {
        $this->extLimit = $ext;
    }

    public function setMaxQty(int $qty)
    {
        $this->maxQty = $qty;
    }

    public function saveImages()
    {
        if($this->oldImages){
            foreach($this->oldImages as $k=>$v){
                if(!in_array($v, $this->images)){
                    @unlink(ROOT_PATH . 'public/'.$v);
                    unset($this->oldImages[$k]);
                }
            }
            $newImages = array_diff($this->images, $this->oldImages);
            if(empty($newImages)) return $this->oldImages;
            if(count($newImages) + count($this->oldImages) > $this->maxQty) throw new Exception('最多可上传'.$this->maxQty.'张图片');
        }else{
            if(count($this->images) > $this->maxQty) throw new Exception('最多可上传'.$this->maxQty.'张图片');
            $newImages = $this->images;
        }
        $checkImg = ['size'=>$this->sizeLimit, 'ext'=>$this->extLimit];
        $imagesPath = [];
        try{
            foreach($newImages as $image){
                if(empty($image)) continue;
                $fileName = md5(uniqid(rand(1000,9999)));
                $saveImage = Common::base64DecImg($image, $this->path, $fileName, $checkImg);
                $imagesPath[] =  $saveImage['imgPath'];
            }
            if($this->oldImages) $imagesPath = array_merge($this->oldImages, $imagesPath);
            return $imagesPath;
        }catch (Exception $ex){
            if($imagesPath){
                foreach($imagesPath as $v){
                    @unlink(ROOT_PATH . 'public/'.$v);
                }
            }
            throw new Exception($ex->getMessage());
        }
    }
}