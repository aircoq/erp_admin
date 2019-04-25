<?php

namespace app\goods\service;

use think\Exception;
use think\Db;
use app\common\model\Goods;
use app\common\model\GoodsSelfGallery as GoodsSelfGalleryModel;
use app\goods\service\GoodsImage;

/**
 * Class GoodsSelfGallery
 * @package app\goods\service
 */
class GoodsSelfGallery
{
    private $where = [];

    /**
     * 保存新建的资源
     * @param int $goods_id
     * @param array $images
     * @throws Exception
     */
    public function save($goodsId, &$images, $channelId, $userId)
    {
        $goods = Goods::field('thumb,spu')->where(['id' => $goodsId])->find();
        if (!$goods) {
            throw new Exception('产品不存在');
        }
        // 事务启动
        Db::startTrans();
        try {
            $aPath = [];
            // 保存图片
            foreach ($images as $image) {
                $decode = false;
                if (isset($image['url']) && $image['url'] && $this->validateUrl($image['url'])) {
                    $this->getPicContent($image);
                    $decode = true;
                }
                $aPath[] = $this->handle($goodsId, $image, $userId, $channelId, $decode);
            }
            // Db 提交
            Db::commit();
            return $aPath;
        } catch (Exception $ex) {
            Db::rollBack();
            throw new Exception($ex->getMessage());
        }
    }

    /**
     * 处理图片
     * @param int $goodsId
     * @param array $image
     * @param int $userId
     */
    private function handle($goodsId, &$image, $userId, $channelId, $decode = false)
    {
        if (isset($image['name'])) { // 处理名称
            $info = pathinfo($image['name']);
            $name = $info['basename'];
            $ext = $info['extension'];
        } else {
            $name = uniqid();
            $ext = 'jpg';
        }

        $unique_code = md5($image['image']);
        $checkInfo = $this->exists($goodsId, $userId, $unique_code);
        if ($checkInfo['status'] == 1) {
            return  $checkInfo['result']['path'];
        }
        if ($checkInfo['status'] == 0) {
            $goodsImage = new GoodsImage();
            $filename = $goodsImage->savePic($goodsId, $image['image'], $unique_code, strtolower($ext), 'self', $decode);
        } else {
            $filename = $checkInfo['result']['path'];
        }
        $goodsGallery = new GoodsSelfGalleryModel();
        $data = [
            'goods_id' => $goodsId,
            'create_id' => $userId,
            'path' => $filename,
            'sort' => 0,
            'unique_code' => $unique_code,
            'create_time' => time(),
            'channel_id' => $channelId ? $channelId : 0
        ];
        $goodsGallery->allowField(true)->save($data);
        return $filename;
    }


    /**
     * 检测是否图片已存在或者已添加
     * @param int $goodsId
     * @param int $userId
     * @param int $unique_code
     * @return boolean | array
     */
    private function exists($goodsId, $userId, $unique_code)
    {
        $result = [
            'status' => 0,
            'result' => []
        ];
        $lists = GoodsSelfGalleryModel::where(['goods_id' => $goodsId, 'unique_code' => $unique_code])->field(true)->select();
        if (!$lists) {
            return $result;
        }
        $result['status'] = 2;
        foreach ($lists as $list) {
            if ($list['create_id'] == $userId) {
                $result['status'] = 1;
                break;
            }
        }
        $result['result'] = $lists[0];
        return $result;
    }

    /**
     * 获取自定义图片列表
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function getList($page = 1, $pageSize = 20)
    {
        $search_images = GoodsSelfGalleryModel::where($this->where)->field('id, goods_id, create_id, path, sort')->order('sort asc')->page($page, $pageSize)->select();
        $images = [];
        foreach ($search_images as $image) {
            $image['thumb'] = GoodsImage::getThumbPath($image['path'], 100, 100);
            $image['original_path'] = $image['path'];
            $image['path'] = GoodsImage::getThumbPath($image['path'], 0, 0);
            $images[] = $image;
        }

        return $images;
    }

    public function setWhere($params)
    {
        if (isset($params['goods_id']) && $params['goods_id']) {

            $params['goods_id'] = json_decode($params['goods_id'], true);
            if (!is_array($params['goods_id'])) {
                $params['goods_id'] = [$params['goods_id']];
            }
            $this->where['goods_id'] = ['in', $params['goods_id']];
        }
        if (isset($params['channel_id']) && $params['channel_id']) {
            $this->where['channel_id'] = $params['channel_id'];
        }
        if (isset($params['user_id']) && $params['user_id']) {
            $this->where['user_id'] = $params['user_id'];
        }
        return $this;
    }

    public function getWhere()
    {
        return $this->where;
    }

    public function resetWhere()
    {
        $this->where = [];
        return $this;
    }

    public function getCount()
    {
        return GoodsSelfGalleryModel::where($this->where)->count();
    }

    private function getPicContent(&$image)
    {
        $lastPos = strrpos($image['url'], '/');
        $image['name'] = substr($image['url'], $lastPos);
        $arrContextOptions = array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
            )
        );
        $image['image'] = @file_get_contents($image['url'], false, stream_context_create($arrContextOptions));
    }

    private function validateUrl($url)
    {
        $regex = "/^https?:\/\/(.*)[jpg|gif|png|bmp|jpeg]$/i";
        if (preg_match($regex, $url)) {
            return true;
        }
        throw new Exception('网络图片格式不正确');
    }
}
