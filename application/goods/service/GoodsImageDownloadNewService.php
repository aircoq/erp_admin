<?php
/**
 * Created by PhpStorm.
 * User: TOM
 * Date: 2017/8/28
 * Time: 13:59
 */

namespace app\goods\service;


use app\common\cache\Cache;
use app\common\model\Category;
use app\common\model\Goods;
use app\common\model\GoodsGallery;
use erp\AbsServer;
use org\Curl;
use org\Ftp;
use think\Config;
use think\Exception;
use app\common\model\GoodsSku;
use think\Db;

class GoodsImageDownloadNewService extends AbsServer
{
    private $sftp_service;
    private $ftp_service;
    /**
     * 文件大小判断
     */
    const ALLOW_SIZE = 5120000;
    /**
     * 区别主图还是细节图
     */
    const SPIT = '-detail';
    const OTHER_SPIT = '-other';
    const MARKET_SPIT = '-marketing';

    public function __construct()
    {
        parent::__construct();
        $host = config('image_ftp_host');
        $user = config('image_ftp_user');
        $pwd = config('image_ftp_pwd');
        $this->ftp_service = new Ftp($host, 21, $user, $pwd);
    }


    /**
     * 新版撸图
     * @param int $goodsId
     * @param string $path
     * @author starzhan <397041849@qq.com>
     */
    public function syncImg(int $goodsId, string $path = '')
    {
        //获取产品信息
        $goods_info = $this->getGoodsInfo($goodsId);
        if (empty($path)) {
            //获取产品图片远程目录
            $image_path = $goods_info['path'];

        } else {
            $image_path = $path;
        }
        if (!$image_path) {
            return '目录不存在';
        }
        $image_path = str_replace('skupic', '', $image_path);
        $image_path = str_replace('picsku', '', $image_path);
        $image_path = str_replace('//', '/', $image_path);
        $tmp = $this->pathLists($image_path, $goods_info);
        $image_paths = $tmp['path'];
        $goods_info = $tmp['goods_info'];
        try {
            $errMsg = [];
            foreach ($image_paths as $channelId => $runPath) {
                try {
                    $this->downloadRemoteImages($runPath, $goods_info, $channelId);
                } catch (Exception $ex) {
                    $errMsg[] = $ex->getMessage();
                }
            }
            if ($errMsg) {
                return implode(';', $errMsg);
            }
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage() . $exception->getFile() . $exception->getLine());
        }
    }

    public function pathLists($path, $goods_info)
    {
        ##实现思路：
        # 1.传入的path 可能有两种，一种spu路径，一种是平台路径
        # 2.若输入值是平台路径，首先将传入的路径的按照“/“分割，最后一个元素（即：平台名称）为小写,定位到正确的平台，ftp上目标目录也有可能是错误的。
        # 也需要转为小写，如果发现匹配就将这个目录返回
        # 3.若输入的值是 spu路径，则将 ftp目标 所有当下目录转成小写，进行匹配，将正确的目录返回
        ##
        $targetDirs = [
            'eBay' => 1,
            'Amazon' => 2,
            'Wish' => 3,
            'AliExpress' => 4,
            'New' => 0
        ];
        $lowercaseMapTargetDirs = [
            'ebay' => 'eBay',
            'amazon' => 'Amazon',
            'wish' => 'Wish',
            'aliexpress' => 'AliExpress',
            'new' => 'New'
        ];
        $path = str_replace('/' . config('image_ftp_host'), '', $path);
        Cache::store('LogisticsLog')->setLogisticsLog('Image-Path', $path);
        $arr_path = explode('/', $path);
        $endPath = end($arr_path);
        $lowercaseEndPath = strtolower($endPath);
        if (isset($lowercaseMapTargetDirs[$lowercaseEndPath])) {
            $trueChannel = $lowercaseMapTargetDirs[$lowercaseEndPath];
            array_pop($arr_path);
            $spuPath = implode('/', $arr_path);
            $catalog = $this->ftp_service->getCatelog($spuPath);
            $result = [];
            $allTarget = [];
            foreach ($catalog as $cd) {
                $cdArr = explode('/', $cd);
                $ltCd = strtolower(end($cdArr));
                if (isset($lowercaseMapTargetDirs[$ltCd])) {
                    $allTarget[] = $ltCd;
                    if ($ltCd == $lowercaseEndPath) {
                        $channelId = $targetDirs[$trueChannel];
                        $result[$channelId] = $cd;
                    }
                }
            }
            $hasAChannel = array_keys($result);
            if (!in_array($goods_info['channelId'], $hasAChannel)) { //如果当前渠道没有图片，则取有图片的渠道
                $goods_info['channelId'] = reset($hasAChannel);
            }
            if (!$result) {
                throw new Exception("目录{$path}不存在");
            }
            return ['path' => $result, 'goods_info' => $goods_info];
        } else {
            $catalog = $this->ftp_service->getCatelog($path);
            $result = [];
            $allTarget = [];
            foreach ($catalog as $cd) {
                $cdArr = explode('/', $cd);
                $ltCd = strtolower(end($cdArr));
                if (isset($lowercaseMapTargetDirs[$ltCd])) {
                    $allTarget[] = $ltCd;
                    $trueChannel = $lowercaseMapTargetDirs[$ltCd];
                    $channelId = $targetDirs[$trueChannel];
                    $result[$channelId] = $cd;
                }
            }
            if (!$result) {
                throw new Exception("没有可用的目录");
            }
            $hasAChannel = array_keys($result);
            if (!in_array($goods_info['channelId'], $hasAChannel)) { //如果当前渠道没有图片，则取有图片的渠道
                $goods_info['channelId'] = reset($hasAChannel);
            }
            return ['path' => $result, 'goods_info' => $goods_info];
        }

    }

    /**
     * 下载远程图片
     * @param string $path
     * @param $goods_info
     * @param int $channelId
     * @throws Exception
     * @author starzhan <397041849@qq.com>
     */
    private function downloadRemoteImages(string $path, $goods_info, int $channelId)
    {

        try {
            //读取目录下面所有文件
            $files = $this->ftp_service->getFiles($path);
            if (!empty($files)) {
                $new_files = [];
                $isSingleThumb = false;//是否有spu图片
                $skuI = [];
                $spuI = 0;
                foreach ($files as $file) {
                    if (!in_array(strtolower($file['file_ext']), ['jpg', 'gif', 'png', 'jpeg'])) {
                        continue;
                    }
                    $file['file'] = mb_convert_encoding($file['file'], 'utf8', 'gb2312');
                    $file['file_name'] = mb_convert_encoding($file['file_name'], 'utf8', 'gb2312');
                    preg_match("/^[A-Za-z\d_]*/", $file['file_name'], $arr_name);
                    $code = $arr_name[0];
                    $file['is_default'] = 1;
                    $file['defaultSkuThumb'] = false;
                    $file['defaultSpuThumb'] = false;
                    if (strpos($file['file_name'], self::SPIT) !== false) {
                        $file['is_default'] = 2;
                    }
                    if (strpos($file['file_name'], self::OTHER_SPIT) !== false) {
                        $file['is_default'] = 4;
                    }
                    if (strpos($file['file_name'], self::MARKET_SPIT) !== false) {
                        $file['is_default'] = 8;
                    }
                    if ($code == $goods_info['spu']) {
                        $file['is_spu'] = true;
                        $isSingleThumb = true;
                        if ($spuI == 0 && $file['is_default'] == 1) {
                            $file['defaultSpuThumb'] = true;
                            $spuI++;
                        }
                    } else {
                        $file['is_spu'] = false;
                        $skuI[$code] = isset($skuI[$code]) ? $skuI[$code] : 0;
                        if ($skuI[$code] == 0 && $file['is_default'] == 1) {
                            $file['defaultSkuThumb'] = true;
                            $skuI[$code]++;
                        }
                    }
                    $file['code'] = $code;
                    $new_files[] = $file;
                };
                $errorMsg = [];
                $insertData = [];
                foreach ($new_files as $item) {
                    try {
                        $insertData[] = $this->saveImage($goods_info, $item, $isSingleThumb, $channelId);
                    } catch (Exception $ex) {
                        $errorMsg[] = $item['code'] . "=>" . $ex->getMessage();
                        continue;
                    }
                }
                Db::startTrans();
                try {
                    GoodsGallery::where(['goods_id' => $goods_info['goods_id'], 'channel_id' => $channelId])->delete();
                    if ($insertData) {
                        $GoodsGallery = new GoodsGallery();
                        $GoodsGallery->insertAll($insertData);
                    }
                    Db::commit();
                } catch (Exception $ex) {
                    Db::rollback();
                    throw $ex;
                }
                if ($errorMsg) {
                    throw new Exception(implode('====', $errorMsg));
                }
            } else {
                return false;
            }
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    /**
     * 获取产品信息
     * @param int $goodsId
     * @return array [
     *       'goods_id'=>'产品ID',
     *       'spu'=>'产品spu',
     *       'first_category'=>'产品一级分类Code',
     *       'second_category'=>'产品二级分类Code'
     * ]
     * @throws Exception
     */
    private function getGoodsInfo(int $goodsId)
    {
        $data = [];
        $goods_info = Cache::store('goods')->getGoodsInfo($goodsId);
        if (empty($goods_info) || empty($goods_info['spu'])) {
            throw new Exception('产品不存在获取spu为空！');
        }
        if (empty($goods_info['thumb_path'])) {
            //获取分类信息
            $second_category = Category::where(['id' => $goods_info['category_id']])->field('code,pid')->find();
            if (empty($second_category) || empty($second_category['code'])) {
                throw new Exception('产品二级分类不存在或code为空！');
            }
            $first_category = Category::where(['id' => $second_category['pid']])->field('code,pid')->find();
            if (empty($first_category) || empty($first_category['code'])) {
                throw new Exception('产品一级分类不存在或code为空！');
            }
            $data['path'] = "/{$first_category['code']}/{$second_category['code']}/{$goods_info['spu']}";
        } else {
            $data['path'] = $goods_info['thumb_path'];
        }
        $aSku = GoodsSku::where('goods_id', $goodsId)->field('id,sku')->select();
        $data['sku'] = [];
        foreach ($aSku as $v) {
            $data['sku'][] = $v->sku;
            $data['skuMap'][$v->sku] = $v->id;
        }
        $data['goods_id'] = $goodsId;
        $data['spu'] = $goods_info['spu'];
        $data['channelId'] = isset($goods_info['channel_id']) ? $goods_info['channel_id'] : 0;
        if (!$data['channelId']) {
            //若没有channelId 则取ebay
            $data['channelId'] = GoodsImport::$DEVELOPER_DEPARTMENT_CHANNELID_MAP[$goods_info['dev_platform_id']] ?? 1;
        }
        $data['dev_platform_id'] = $goods_info['dev_platform_id'];
        return $data;
    }


    /**
     * 保存产品图片
     * @param array $goodsInfo
     * @param array $file
     * @param string $content
     * @param array $goodsInfo
     * @param bool $isSingleThumb 是否是单属性图片
     * @return array
     * @throws Exception
     */
    public function saveImage($goodsInfo, array $file, $isSingleThumb = false, int $channelId)
    {

        $goodsId = $goodsInfo['goods_id'];
        $fileSize = $this->ftp_service->fileSize($file['file']);
        if ($fileSize > self::ALLOW_SIZE) {
            throw new Exception('文件大小超出了限制！');
        }
        $content = $this->ftp_service->getFileStream($file['file']);
        $file['file'] = mb_convert_encoding($file['file'], 'utf8', 'gb2312');
        $unique_code = md5($content);
        $path = substr($goodsId, 0, 3) . '/' . substr($goodsId, 3);
        $image_path = $path . '/' . $unique_code . '.' . $file['file_ext'];
        //上线的时候注释打开
         $this->uploadFile($goodsId, $content, $file['file_ext'], $unique_code);
        $sku_id = isset($goodsInfo['skuMap'][$file['code']]) ? $goodsInfo['skuMap'][$file['code']] : 0;
        $data = [
            'goods_id' => $goodsId,
            'attribute_id' => 0,
            'value_id' => 0,
            'sku_id' => $sku_id,
            'path' => $image_path,
            'sort' => 98,
            'unique_code' => $unique_code,
            'original_path' => $file['file'],
            'is_default' => $file['is_default'],
            'channel_id' => $channelId,
            'alt' => $file['file_name'] . "." . $file['file_ext']
        ];
        //更新SKU主图信息
        if ($sku_id && $channelId === $goodsInfo['channelId'] && $file['defaultSkuThumb']) {
            \app\common\model\GoodsSku::update(['id' => $sku_id, 'thumb' => $image_path]);
            Cache::store('goods')->delSkuInfo($sku_id);
            if (!$isSingleThumb) {//如果产品没图，则更新 sku的主图
                Goods::update(['id' => $goodsId, 'thumb' => $image_path]);
                Cache::store('goods')->delGoodsInfo($goodsId);
            }
        }
        //更新产品主图
        if ($file['defaultSpuThumb'] === true && $channelId === $goodsInfo['channelId']) {
            Goods::update(['id' => $goodsId, 'thumb' => $image_path]);
            Cache::store('goods')->delGoodsInfo($goodsId);
        }
        return $data;
    }

    /**
     * 上传图片
     * @param $goodsId
     * @param $fileContent
     * @param $ext
     * @param $fileName
     * @return string
     * @throws Exception
     */
    public function uploadFile($goodsId, $fileContent, $ext, $fileName)
    {
        if (empty($fileName)) {
            throw new Exception('文件名不能为空');
        }
        if (!in_array($ext, ['jpg', 'gif', 'png', 'jpeg'])) {
            throw new Exception('文件格式不支持');
        }
        if (empty($fileContent)) {
            throw new Exception('文件内容不能为空');
        }
        if (6 != strlen($goodsId)) {
            throw new Exception('goodsId的格式不对');
        }
        $path = substr($goodsId, 0, 3) . '/' . substr($goodsId, 3);
        $info = [
            'path' => $path,
            'name' => $fileName,
            'content' => base64_encode($fileContent),
            'file_ext' => $ext
        ];
        $url = Config::get('picture_upload_url') . '/upload.php';
        $strJson = Curl::curlPost($url, $info);
        $request = json_decode($strJson, true);
        if ($request && $request['status'] == 1) {
            return $path . '/' . $fileName . '.' . $ext;
        }
        throw new Exception($request ? $request['error_message'] : '文件上传失败');
    }

}