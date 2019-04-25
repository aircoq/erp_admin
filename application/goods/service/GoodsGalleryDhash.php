<?php


namespace app\goods\service;

use think\Exception;
use app\common\model\GoodsGalleryDhash as ModelGoodsGalleryDhash;
use app\common\model\GoodsGallery;
use think\db\Query;
use think\Db;
use PDO;
use think\Log;

class GoodsGalleryDhash
{

    public static function saveFile($filename, $content)
    {
        if (!$content) {
            throw new Exception('添加的内容不能为空');
        }
        $start = strpos($content, ',');
        $content = substr($content, $start + 1);
        file_put_contents($filename, base64_decode(str_replace(" ", "+", $content)));
        return $filename;
    }
    public function dHash($src)
    {
        $extname = pathinfo($src, PATHINFO_EXTENSION);
        $extname = strtolower($extname);
        if (!in_array($extname, ['jpg', 'jpeg','png'])) {
            throw new Exception('图片格式不正确');
        }
        $info = getimagesize($src);
        if ($info === false) {
            throw new Exception('远程图片打开失败');
        }
        $opts = array(
            'http' => array(
                'method' => "GET",
                'timeout' => 30,//单位秒
            )
        );
        $file = file_get_contents($src, false, stream_context_create($opts));
        if (!$file) {
            throw new Exception('远程图片打开失败');
        }

        $w = 9;  // 采样宽度
        $h = 8;  // 采样高度
        $dst = imagecreatetruecolor($w, $h);
        $img = imagecreatefromstring(file_get_contents($src));
        // 缩放
        $img && imagecopyresized($dst, $img, 0, 0, 0, 0, $w, $h, $info[0], $info[1]);
        $hash = '';
        for ($y = 0; $y < $h; $y++) {
            $pix = $this->getGray(imagecolorat($dst, 0, $y));
            for ($x = 1; $x < $w; $x++) {
                $_pix = $this->getGray(imagecolorat($dst, $x, $y));
                $_pix > $pix ? $hash .= '1' : $hash .= '0';
                $pix = $_pix;
            }
        }
        // $hash = base_convert($hash, 2, 16);
        return $hash;
    }

    /**
     * 获取像素点的灰度值
     * @param $rgb
     * @return int
     */
    private function getGray($rgb)
    {
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        return intval(($r + $g + $b) / 3) & 0xFF;
    }

    /**
     * @title 切割 变为 数组
     * @param $phash
     * @author starzhan <397041849@qq.com>
     */
    private function cuttingDhash($dHash)
    {
        $aDHash = [
            0 => '',
            1 => ''
        ];
        for ($i = 0; $i < 64; $i++) {
            $m = floor($i / 32);
            $aDHash[$m] .= $dHash[$i];
        }
        $aDHash[0] = base_convert($aDHash[0], 2, 10);
        $aDHash[1] = base_convert($aDHash[1], 2, 10);
        return $aDHash;
    }

    public function file2Phash($fileCode)
    {
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $fileCode, $result)) {
            $type = $result[2];
            if (in_array($type, ['jpg', 'jpeg', 'png'])) {
                $filename = 'upload/' . uniqid() . '.' . $type;
                self::saveFile($filename, $fileCode);
                $phash = $this->dHash($filename);
                unlink($filename);
                return $phash;
            }
        }
        throw new Exception('文件类型不正确');
    }


    public function runWritePhashAndCreateCache($_id = 0)
    {
        set_time_limit(0);
        $limit = 1000;
        $max = 9888888;
        $id = $_id;
        while ($id <= $max) {
            try {
                $sql = 'select * from goods_gallery where id not in  (select id from goods_gallery_dhash) and id> ' . $id . ' and is_default=1 order by id asc limit ' . $limit;
                $Q = new Query();
                $a = $Q->query($sql, [], true, true);
                $data = [];
                while ($row = $a->fetch(PDO::FETCH_ASSOC)) {
                    $id = $row['id'];
                    try {
                        $fileName = GoodsImage::getThumbPath($row['path'],500,500);
                        $pHash = $this->dHash($fileName);
                        if (!$pHash) {
                            continue;
                        }
                        $aPHash = $this->cuttingDhash($pHash);
                        $infoData = [];
                        $infoData['id'] = $row['id'];
                        $infoData['goods_id'] = $row['goods_id'];
                        $infoData['sku_id'] = $row['sku_id'];
                        $infoData['phash1'] = $aPHash[0];
                        $infoData['phash2'] = $aPHash[1];
                        $infoData['is_default'] = $row['is_default'];
                        $data[] = $infoData;

                    } catch (\Exception $ex) {
                        $logFile = LOG_PATH . "goods/phash.log";
                        file_put_contents($logFile, $row['id'] . ":" . $ex->getMessage() . "\n", FILE_APPEND);
                        continue;
                    }
                }
                if ($data) {
                    $model = new ModelGoodsGalleryDhash();
                    $model->insertAll($data);
                }
            } catch (\Exception $e) {
                $logFile = LOG_PATH . "goods/phash.log";
                file_put_contents($logFile, $id . ":" . $e->getMessage() . "\n", FILE_APPEND);
                continue;
            }
        }

    }
    public function searchGoodsByPhash($phash)
    {
        $aHash = $this->cuttingDhash($phash);
        $aGoods = [];
        $sql = "select DISTINCT goods_id,(BIT_COUNT(phash1^{$aHash[0]})+BIT_COUNT(phash2^{$aHash[1]})) as num1 from  goods_gallery_dhash where (BIT_COUNT(phash1^{$aHash[0]})+BIT_COUNT(phash2^{$aHash[1]}))<10 and is_default=1 order by num1 asc ";
        $Q = new Query();
        $a = $Q->query($sql, [], true, true);
        while ($row = $a->fetch(PDO::FETCH_ASSOC)) {
            $aGoods[] = $row['goods_id'];
        }
        return $aGoods;
    }
    public function resetDhashByGoodsId($goods_id)
    {
        $aGoodsGallery = GoodsGallery::where('goods_id', $goods_id)->where('is_default',1)->select();
        ModelGoodsGalleryDhash::where('goods_id', $goods_id)->where('is_default',1)->delete();
        if ($aGoodsGallery) {
            foreach ($aGoodsGallery as $imgInfo) {
                $fileName = GoodsImage::getThumbPath($imgInfo['path'],500,500);
                $pHash = $this->dHash($fileName);
                if (!$pHash) {
                    continue;
                }
                $aPHash = $this->cuttingDhash($pHash);
                $data = [];
                $data['id'] = $imgInfo['id'];
                $data['phash1'] = $aPHash[0];
                $data['phash2'] = $aPHash[1];
                $data['goods_id'] = $imgInfo['goods_id'];
                $data['sku_id'] = $imgInfo['sku_id'];
                $data['is_default'] = $imgInfo['is_default'];
                $ModelGoodsGalleryPhash = new ModelGoodsGalleryDhash();
                $ModelGoodsGalleryPhash
                    ->isUpdate(false)
                    ->allowField(true)
                    ->save($data);

            }
        }
    }

}