<?php

namespace app\goods\service;

use app\common\model\GoodsGalleryPhash;
use app\common\model\GoodsImgRequirement;
use app\common\validate\GoodsImgRequirement as ValidateGoodsImgRequirement;
use think\Exception;
use think\Db;
use app\common\model\Goods;
use app\common\cache\Cache;
use app\common\model\GoodsGallery;
use app\common\validate\GoodsGallery as ValidateGoodsGallery;
use app\goods\service\GoodsHelp;
use org\Curl;
use think\Image;
use app\common\model\GoodsSku as goodsSkuModel;
use think\Config;
use app\goods\service\GoodsSkuMapService;

/**
 * Class GoodsImage
 * @package app\goods\service
 */
class GoodsImage
{
    /**
     * 保存新建的资源
     * @param int $goods_id
     * @param array $images
     * @throws Exception
     */
    public function save($goods_id, &$images, $add = false)
    {
        $goods = Goods::field('id,thumb,spu,channel_id')->where(['id' => $goods_id])->find();
        if (!$goods) {
            throw new Exception('产品不存在');
        }
        $delList = [];
        $fistId = 0;
        foreach ($images as $image) {
            if (!empty($image['id'])) {
                if (!$fistId) {
                    if ($image['is_default'] == 1) {
                        $fistId = $image['id'];
                    }
                }
            }
        }
        if ($add === false) {
            $this->check($goods_id, $images, $delList);
        }
        // 事务启动
        Db::startTrans();
        try {
            // 保存图片
            foreach ($images as $image) {
                if (empty($image['id'])) { // 添加图片
                    $flag = $this->handle($goods, $image);
                    if ($flag !== false) {
                        $image['id'] = $fistId;
                    }
                } else {
                    $this->updatePic($image);
                }
                if (!$fistId) {
                    if ($image['is_default'] == 1) {
                        $fistId = $image['id'];
                    }
                }
            }
            if ($delList) { // 删除图片
                foreach ($delList as $list) {
                    $this->deletePic($list);
                }
            }
            if ($fistId) {
                $fstImg = GoodsGallery::where('id', $fistId)->find();
                if ($fstImg) {
                    Goods::where(['id' => $goods_id])->update(['thumb' => $fstImg->path]);
                }
            }
            // Db 提交
            Db::commit();
            $goodsInfo = Goods::where(['id' => $goods_id])->field('thumb')->find();
            $thumb = $goodsInfo ? self::getThumbPath($goodsInfo['thumb'], 200, 200) : '';

            return ['thumb' => $thumb];
        } catch (Exception $ex) {
            Db::rollBack();
            throw new Exception($ex->getMessage());
        }
    }


    /**
     * 处理图片
     * @param string $spu
     * @param array $goodsInfo
     * @param array $image
     */
    private function handle($goodsInfo, &$image)
    {
        $goods_id = $goodsInfo['id'];
        $channel_id = $goodsInfo['channel_id'];
        if (isset($image['name'])) { // 处理名称
            $info = pathinfo($image['name']);
            $name = $info['basename'];
            $ext = $info['extension'];
        } else {
            $name = uniqid();
            $ext = 'jpg';
        }
        if (!isset($image['sku_id'])) {
            $image['sku_id'] = 0;
        }
        if (!isset($image['is_default'])) {
            $is_default = 2;
        } else {
            $is_default = $image['is_default'];
        }
        if (!isset($image['sort'])) {
            $image['sort'] = 999;
        }
        $unique_code = md5($image['image']);
        $checkInfo = $this->exists($goods_id, $image['sku_id'], $unique_code, $channel_id);
        if (!$checkInfo) {
            return false;
        }
        if ($checkInfo === true) {
            $filename = $this->savePic($goods_id, $image['image'], $unique_code, strtolower($ext));
        } else {
            $filename = $checkInfo['path'];
        }
        $goodsGallery = new GoodsGallery();
        $data = [
            'goods_id' => $goods_id,
            'attribute_id' => 0,
            'value_id' => 0,
            'sku_id' => $image['sku_id'],
            'path' => $filename,
            'sort' => $image['sort'],
            'channel_id' => $channel_id,
            'unique_code' => $unique_code,
            'original_path' => $name . '.' . $ext,
            'is_default' => $is_default
        ];
        $goodsGallery->allowField(true)->save($data);
        return $goodsGallery->id;
    }

    /**
     * 检测是否图片已存在或者已添加
     * @param int $goods_id
     * @param int $sku_id
     * @param int $unique_code
     * @return boolean | object
     */
    private function exists($goods_id, $sku_id, $unique_code, $channel_id)
    {
        $lists = GoodsGallery::where(['goods_id' => $goods_id, 'unique_code' => $unique_code, 'channel_id' => $channel_id])->field(true)->select();
        if (!$lists) {
            return true;
        }
        $flag = true;
        foreach ($lists as $list) {
            if ($list['sku_id'] == $sku_id) {
                $flag = false;
                break;
            }
        }

        return $flag ? $lists[0] : $flag;
    }

    /**
     * 保存图片
     * @param int $goods_id
     * @param stirng $image
     * @param stirng $name
     * @param string $ext
     * @return string
     * @throws Exception
     */
    public function savePic($goods_id, $image, $name, $ext, $path = '', $decode = false)
    {
        if (6 != strlen($goods_id)) {
            throw new Exception('spu的格式不对');
        }
        $dir = substr($goods_id, 0, 3) . '/' . substr($goods_id, 3);
        if (!in_array($ext, ['jpg', 'gif', 'png', 'jpeg'])) {
            throw new Exception('图片格式不对');
        }
        if (strpos('<?php', $image)) {
            throw new Exception('上传内容有敏感信息');
        }
        if (!$decode) {
            $start = strpos($image, ',');
            $img = str_replace(' ', '+', substr($image, $start + 1));
        } else {
            $img = base64_encode($image);
        }
        $path = ($path ? $path . '/' : '') . $dir;
        $info = [
            'path' => $path,
            'name' => $name,
            'content' => $img,
            'file_ext' => $ext
        ];
        $url = Config::get('picture_upload_url') . '/upload.php';
        $strJson = Curl::curlPost($url, $info);
        $request = json_decode($strJson, true);
        if ($request && $request['status'] == 1) {
            return $path . '/' . $name . '.' . $ext;
        }
        throw new Exception($request ? $request['error_message'] : '');
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
        foreach ($searchLists as $list) {
            $list = $list->toArray();
            $lists[$list['id']] = $list;
        }
        foreach ($images as $k => &$image) {
            $ValidateGoodsGallery = new ValidateGoodsGallery();
            $flag = $ValidateGoodsGallery->scene('check')->check($image);
            if ($flag === false) {
                throw new Exception($ValidateGoodsGallery->getError());
            }
            if (!empty($image['id']) && isset($lists[$image['id']])) {
                if ($image['is_default'] == $lists[$image['id']]['is_default'] && $image['sort'] == $lists[$image['id']]['sort']) {
                    unset($images[$k]);
                }
                $image['old_is_default'] = $lists[$image['id']]['is_default'];
                $image['path'] = $lists[$image['id']]['path'];
                $image['goods_id'] = $lists[$image['id']]['goods_id'];
                $image['sku_id'] = $lists[$image['id']]['sku_id'];
                unset($lists[$image['id']]);
            }
        }
        $delList = $lists;
    }

    /**
     * 删除图片记录
     * @param array $list
     * @return boolean
     */
    private function deletePic($list)
    {
        $galleryInfo = GoodsGallery::where(['id' => $list['id']])->field(true)->find();
        $count = $this->countByUniqueCode($galleryInfo['goods_id'], $galleryInfo['unique_code']);
        if ($count > 1) {
            return true;
        }
        $url = Config::get('picture_upload_url') . '/delete.php';
        $strJson = Curl::curlPost($url, ['iamge_path' => $galleryInfo->path]);
        $response = json_decode($strJson, true);
        $galleryInfo->delete();
        if ($response && $response['status'] == 1) {
            return true;
        }
        return false;
    }

    /**
     * 更新图片信息
     * @param array $list
     * @return boolean
     */
    private function updatePic($list)
    {
        $gallery = new GoodsGallery();
        $gallery->where(['id' => $list['id']])->update(['is_default' => $list['is_default'], 'sort' => $list['sort'], 'channel_id' => $list['channel_id']]);
        return true;
    }

    /**
     * 更新产品 sku主图
     * @param int $goods_id
     * @param int $attribute_id
     * @param int $value_id
     * @param string $thumb
     */
    private function updateMainPic($goods_id, $sku_id, $thumb)
    {
        if ($sku_id) {
            goodsSkuModel::where(['id' => $sku_id])->update(['thumb' => $thumb]);
            Cache::handler()->hdel('cache:Sku', $sku_id);
        } else {
            Goods::where(['id' => $goods_id])->update(['thumb' => $thumb]);
            Cache::handler()->hdel('cache:Goods', $goods_id);
        }
    }

    /**
     * 获取总数按照unique_code
     * @param int $goods_id
     * @param string $unique_code
     * @return int
     */
    private function countByUniqueCode($goods_id, $unique_code)
    {
        return GoodsGallery::where(['goods_id' => $goods_id, 'unique_code' => $unique_code])->count();
    }

    /**
     * 生成图片缩略图
     * @param string $file
     * @param \app\goods\controller\width $length
     */
    private function thumb($file, $width, $length)
    {
        $image = Image::open($file);
        $arr_ext = explode('.', $file);
        $image->thumb($width, $length)->save($arr_ext[0] . '_' . $width . 'x' . $length . '.' . $arr_ext[1]);
        return true;
    }

    /**
     * 获取图片列表
     * @param int $id
     * @param string $domain
     * @return array
     */
    public function getLists($id, $domain, $param = array())
    {

        $goodsHelp = new GoodsHelp();
        $skus = $goodsHelp->getGoodsSkus($id);
        $width = 0;
        $height = 0;
        $GoodsGallery = new GoodsGallery();
        if (isset($param['width']) && $param['width']) {
            $width = $param['width'];
        }
        if (isset($param['height']) && $param['height']) {
            $height = $param['height'];
        }
        if (isset($param['is_default']) && $param['is_default']) {
            $is_default = json_decode($param['is_default'], true);
            $addDefault = array_sum($is_default);
            $GoodsGallery = $GoodsGallery->where('is_default|' . $addDefault . "=" . $addDefault);
        }
        if (isset($param['channel_id']) && $param['channel_id']) {
            $GoodsGallery = $GoodsGallery->where('channel_id', $param['channel_id']);
        }
        $unique = true;
        if (isset($param['unique'])) {
            $unique = $param['unique'];
        }
        $field = 'id, goods_id, sku_id, path, sort, is_default,channel_id,alt,cont_sign,unique_code';
        if ($unique) {
            $search_images = $GoodsGallery->where('goods_id', $id)
                ->field($field)
                ->order('is_default desc, sort asc')
                ->select();
        } else {
            $search_images = $GoodsGallery->where('goods_id', $id)
                ->field($field)
                ->order('is_default desc, sort asc')
                ->select();
        }
        $images = [];
        foreach ($search_images as $image) {
            // $image['thumb'] =  self::getThumbPath($image['path'], 100, 100);
            $image['path'] = self::getThumbPath($image['path'], $width, $height, '', false, true);
            $image['channel'] = $image->channel;
            $image['is_default_txt'] = $image->is_default_txt;
            $image['sku'] = $image->sku;
            $image['is_pull_baidu'] = $image['cont_sign']?1:0;
            $images[$image['sku_id']][] = $image;
        }
        if (empty($images)) {
            //GoodsI
            $goodsImage = Db::name('goods_images')->field('id,path')->where(['goods_id' => $id])->select();
            $goodsInfo = Cache::store('goods')->getGoodsInfo($id);
            if (isset($goodsInfo['status']) && $goodsInfo['status'] == 0) {
                foreach ($goodsImage as $k => $img) {
                    // $image['thumb'] = '' ;
                    $image['path'] = $img['path'];
                    $image['channel_id'] = $goodsInfo['channel_id'];
                    $image['channel'] = Cache::store('channel')->getChannelName($goodsInfo['channel_id']);
                    $image['is_default_txt'] = '主图';
                    $image['is_default'] = 2;
                    $image['sku'] = '';
                    $image['id'] = 'add' . uniqid() . rand(0, 100);
                    $pathArr = explode('/', $img['path']);
                    $image['alt'] = end($pathArr);
                    $image['sort'] = $k;
                    $image['unique_code'] = $img['unique_code'];
                    $image['is_pull_baidu'] = 0;
                    $images[0][] = $image;
                }
            }
        }
        $host = Cache::store('configParams')->getConfig('innerPicUrl')['value'] . "/";
        $lists[] = [
            'name' => '主图',
            'attribute_id' => 0,
            'baseUrl' => $host,
            'value_id' => 0,
            'sku_id' => 0,
            'images' => isset($images[0]) ? $images[0] : [],
            'goods_id' => $id,
        ];

        foreach ($skus as $skuInfo) {
            $list = [
                'name' => $skuInfo['sku'],
                'baseUrl' => $host,
                'attribute_id' => 0,
                'value_id' => 0,
                'sku_id' => $skuInfo['id'],
                'goods_id' => $skuInfo['goods_id'],
                'images' => isset($images[$skuInfo['id']]) ? $images[$skuInfo['id']] : [],
            ];
            $lists[] = $list;
        }
        return $lists;
    }

    private function getThumbListWhere($aGoodsId = [], $aSkuId = [])
    {
        $o = new GoodsGallery();
        if ($aGoodsId) {
            $o = $o->whereOr('goods_id', 'in', $aGoodsId);
        }
        if ($aSkuId) {
            $o = $o->whereOr('sku_id', 'in', $aSkuId);
        }
        return $o;
    }

    public function getThumbList($aGoodsId = [], $aSkuId = [])
    {
        $result = [];
        $tmp = $this->getThumbListWhere($aGoodsId, $aSkuId)
            ->field('id, goods_id, sku_id, path, sort, is_default,channel_id,alt,unique_code')
            ->order('is_default desc, sort asc')
            ->select();
        $host = Cache::store('configParams')->getConfig('innerPicUrl')['value'] . "/";
        foreach ($tmp as $image) {
            $row['thumb'] = self::getThumbPath($image['path'], 100, 100);
            $row['path'] = self::getThumbPath($image['path'], 0, 0, '', false, true);
            $row['channel'] = $image->channel;
            $row['channel_id'] = $image->channel_id;
            $row['is_default'] = $image->is_default;
            $row['sort'] = $image->sort;
            $row['alt'] = $image->alt;
            $row['baseUrl'] = $host;
            $row['is_default_txt'] = $image->is_default_txt;
            $row['sku'] = $image->sku;
            $row['goods_id'] = $image->goods_id;
            $row['unique_code'] = $image->unique_code;
            $result[] = $row;
        }
        return $result;
    }

    /**
     * 获取产品listing图片总数
     * @param int $goods_id
     * @param int $sku_id
     * @return int
     */
    public function countListingImage($goods_id, $sku_id)
    {
        $where['goods_id'] = $goods_id;
        $sku_id ? $where['sku_id'] = $sku_id : '';
        return GoodsGallery::where($where)->count();
    }

    /**
     * 获取产品listing图片
     * @param int $goods_id
     * @param int $sku_id
     * @return array
     */
    public function getListingImage($goods_id, $sku_id, $page, $pageSize)
    {
        $where['goods_id'] = $goods_id;
        $sku_id ? $where['sku_id'] = $sku_id : '';
        $field = 'id, goods_id, sku_id, path, sort, unique_code, is_default';
        $lists = GoodsGallery::where($where)->field($field)->order('sort asc')->select();
        $result = [];
        $uniqueCodes = [];
        foreach ($lists as $list) {
            if (in_array($list['unique_code'], $uniqueCodes)) {
                continue;
            } else {
                array_push($uniqueCodes, $list['unique_code']);
            }
            $list['thumb'] = self::getThumbPath($list['path']);
            $list['original_path'] = $list['path'];
            $list['path'] = self::getThumbPath($list['path'], 0, 0);
            $result[] = $list;
        }

        return $result;
    }

    /**
     * 获取map的goods_id
     * @param string $sku
     * @param string $channel_id
     * @param string $account_id
     * @return int
     */
    public function getGoodsId($sku, $channel_id, $account_id)
    {
        $skuMapService = new GoodsSkuMapService();
        $sku_id = $skuMapService->getSkuInfo($sku, $channel_id, $account_id);
        if (!$sku_id) {
            throw new Exception('sku map中找不到关联的记录');
        }
        $skuInfo = Cache::store('goods')->getSkuInfo($sku_id);
        return isset($skuInfo['goods_id']) ? $skuInfo['goods_id'] : 0;
    }

    /**
     * 获取缩略图路径
     * @param string $fileName
     * @param int $width
     * @param int $height
     * @param string 账号简称
     * @param outer 外部访问地址
     * @param $noHost 带不带 域名
     * @return string
     */
    public static function getThumbPath($fileName, $width = 60, $height = 60, $storeShortName = '', $outer = false, $noHost = false)
    {
        if (strpos($fileName, '.') === false) {
            return '';
        }
        $host = $outer ? Cache::store('configParams')->getConfig('outerPicUrl')['value'] : Cache::store('configParams')->getConfig('innerPicUrl')['value'];
        $filename = str_replace($host, '', $fileName);
        $pathInfo = pathinfo($filename);
        if ($storeShortName) {
            if (strpos($pathInfo['dirname'], 'self') === 0) {
                $storeShortName = 'self' . $storeShortName;
                $pathInfo['dirname'] = substr($pathInfo['dirname'], 5);
            }
            $path = self::encode($storeShortName, str_replace('/', '', $pathInfo['dirname']));
            $path .= '/' . self::encode($storeShortName, $pathInfo['filename']);
        } else {
            $path = $pathInfo['dirname'] . '/' . $pathInfo['filename'];
        }
        if ($noHost) {
            return $path . ($width == 0 ? '' : '_' . $width . 'x' . $height) . '.' . $pathInfo['extension'];
        }
        return $host . '/' . $path . ($width == 0 ? '' : '_' . $width . 'x' . $height) . '.' . $pathInfo['extension'];
    }

    private static function encode($path, $name)
    {
        $strResult = '';
        $strlen1 = strlen($path);
        $strlen2 = strlen($name);
        $min = min($strlen1, $strlen2);
        for ($i = 0; $i < $min; $i++) {
            $strResult .= $path[$i] . $name[$i];
        }
        $strResult .= ($min == $strlen1) ? substr($name, $min) : substr($path, $min);

        return $strResult;
    }

    /**
     * 获取原始图片路径
     * @param string $filename
     * @param int $width
     * @param int $height
     * @param string 账号简称
     * @return string
     */
    public static function getImagePath($filename, $storeShortName = '')
    {
        if ($storeShortName) {
            $pathInfo = pathinfo($filename);
            $filename = $storeShortName . '/' . $pathInfo['dirname'] . '/' . $storeShortName . '-' . $pathInfo['basename'];
        }
        return Config::get('picture_base_url') . '/' . $filename;
    }

    public static function getAllImages($where = array(), $limit = 15)
    {
        $GoodsGallery = new GoodsGallery();
        return $GoodsGallery->where($where)->limit($limit)->select();
    }

    /**
     * @title 修改修图要求的信息
     * @param int $goods_id
     * @param int $is_photo
     * @param int $photo_remark
     * @param int $undisposed_img_url
     * @param int $ps_requirement
     * @return array
     * @author starzhan <397041849@qq.com>
     */
    public function updateGoodsImgRequirement(
        int $goods_id,
        array $data
    ): array
    {
        $GoodsImgRequirement = new GoodsImgRequirement();
        $aImgRequirement = $GoodsImgRequirement->where('goods_id', $goods_id)->find();
        if ($aImgRequirement) {
            $data['update_time'] = time();
            $aImgRequirement->allowField(true)->isUpdate(true)->save($data);
            return $data;
        } else {
            $data['create_time'] = time();
            $ValidateGoodsImgRequirement = new ValidateGoodsImgRequirement();
            $flag = $ValidateGoodsImgRequirement->check($data);
            if ($flag === false) {
                throw new Exception($ValidateGoodsImgRequirement->getError());
            }
            $GoodsImgRequirement->allowField(true)->isUpdate(false)->save($data);
            return $data;
        }
    }

    public function getImgBySkuId($sku_id, $fullPath = false)
    {
        $ret = GoodsGallery::where('sku_id', $sku_id)
            ->order('is_default asc')
            ->order('sort asc')
            ->select();
        $result = [];
        foreach ($ret as $v) {
            if ($fullPath) {
                $result[] = GoodsImage::getThumbPath($v['path'], 0, 0);
            } else {
                $result[] = $v['path'];
            }
        }
        return $result;
    }

    public function getImgByGoodsId($goods_id, $fullPath = false)
    {
        $ret = GoodsGallery::where('goods_id', $goods_id)
            ->where('sku_id', 0)
            ->order('is_default asc')
            ->order('sort asc')
            ->select();
        $result = [];
        foreach ($ret as $v) {
            if ($fullPath) {
                $result[] = GoodsImage::getThumbPath($v['path'], 0, 0);
            } else {
                $result[] = $v['path'];
            }
        }
        return $result;
    }

    public function getImgByChannelIdAndGoodsId($goods_id = 0, $channel_id)
    {
        $defule = [0, 1, 4, 3, 2];
        array_unshift($defule, $channel_id);
        $defule = array_unique($defule);
        $ret = GoodsGallery::where('goods_id', $goods_id)
            ->where('sku_id', 0)
            ->order("find_in_set(channel_id,'" . implode(',', $defule) . "' )")
            ->order('is_default asc')
            ->order('sort asc')
            ->limit(10)
            ->select();
        $result = [];
        foreach ($ret as $v) {
            $result[] = GoodsImage::getThumbPath($v['path'], 0, 0);
        }
        return $result;
    }

    public function getImgByChannelIdAndSkuId($sku_id = 0, $channel_id)
    {
        $defule = [0, 1, 4, 3, 2];
        array_unshift($defule, $channel_id);
        $defule = array_unique($defule);
        $ret = $ret = GoodsGallery::where('sku_id', $sku_id)
            ->where('sku_id', 0)
            ->order("find_in_set(channel_id,'" . implode(',', $defule) . "' )")
            ->order('is_default asc')
            ->order('sort asc')
            ->limit(10)
            ->select();
        $result = [];
        foreach ($ret as $v) {
            $result[] = GoodsImage::getThumbPath($v['path'], 0, 0);
        }
        return $result;
    }


}




