<?php


namespace app\goods\service;

use app\common\cache\Cache;
use \think\Exception;
use app\common\model\GoodsGalleryPhash as ModelGoodsGalleryPhash;
use app\common\model\GoodsGallery;
use think\db\Query;
use think\Db;
use PDO;
use think\Log;

class GoodsGalleryPhash
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

    public function file2Phash($fileCode)
    {
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $fileCode, $result)) {
            $type = $result[2];
            if (in_array($type, ['jpg', 'jpeg', 'png', 'gif'])) {
                $filename = 'upload/' . uniqid() . '.' . $type;
                self::saveFile($filename, $fileCode);
                $phash = $this->getphash($filename);
                unlink($filename);
                return $phash;
            }
        }
        throw new Exception('文件类型不正确');
    }

    /**
     * @title 切割 变为 数组
     * @param $phash
     * @author starzhan <397041849@qq.com>
     */
    private function cuttingPhash($pHash)
    {
        $aPHash = [
            0 => '',
            1 => ''
        ];
        for ($i = 0; $i < 64; $i++) {
            $m = floor($i / 32);
            $aPHash[$m] .= $pHash[$i];
        }
        $aPHash[0] = base_convert($aPHash[0], 2, 10);
        $aPHash[1] = base_convert($aPHash[1], 2, 10);
        return $aPHash;
    }

    public function searchGoodsByPhash($phash)
    {
        $aHash = $this->cuttingPhash($phash);
        $aGoods = [];
        $sql = "select DISTINCT goods_id,(BIT_COUNT(phash1^{$aHash[0]})+BIT_COUNT(phash2^{$aHash[1]})) as num1 from  goods_gallery_phash where (BIT_COUNT(phash1^{$aHash[0]})+BIT_COUNT(phash2^{$aHash[1]}))<10 and is_default=1 order by num1 asc ";
        $Q = new Query();
        $a = $Q->query($sql, [], true, true);
        while ($row = $a->fetch(PDO::FETCH_ASSOC)) {
            $aGoods[] = $row['goods_id'];
        }
        return $aGoods;
    }

    public function runWritePhashAndCreateCache($_id = 0)
    {
        set_time_limit(0);
        $limit = 1000;
        $max = 9888888;
        $id = $_id;
        while ($id <= $max) {
            try {
                $sql = 'select * from goods_gallery where id not in  (select id from goods_gallery_phash) and id> ' . $id . ' order by id asc limit ' . $limit;
                $Q = new Query();
                $a = $Q->query($sql, [], true, true);
                $data = [];
                while ($row = $a->fetch(PDO::FETCH_ASSOC)) {
                    $id = $row['id'];
                    try {
                        $fileName = GoodsImage::getThumbPath($row['path']);
                        $pHash = $this->getphash($fileName);
                        if (!$pHash) {
                            continue;
                        }
                        $aPHash = $this->cuttingPhash($pHash);
                        $infoData = [];
                        $infoData['id'] = $row['id'];
                        $infoData['goods_id'] = $row['goods_id'];
                        $infoData['sku_id'] = $row['sku_id'];
                        $infoData['phash1'] = $aPHash[0];
                        $infoData['phash2'] = $aPHash[1];
                        $infoData['is_default'] = $row['is_default'];
                        $data[] = $infoData;

                    } catch (\Exception $ex) {
                        $logFile = LOG_PATH."goods/phash.log";
                        file_put_contents($logFile, $row['id'] . ":" . $ex->getMessage() . "\n", FILE_APPEND);
                        continue;
                    }
                }
                if ($data) {
                    $model = new ModelGoodsGalleryPhash();
                    $model->insertAll($data);
                }
            } catch (\Exception $e) {
                $logFile = LOG_PATH."goods/phash.log";
                file_put_contents($logFile, $id . ":" . $e->getMessage() . "\n", FILE_APPEND);
                continue;
            }
        }
        echo '最后' . $id;
    }

    /**
     * @title 重置这个商品id下的所有phash
     * @param $goods_id
     * @author starzhan <397041849@qq.com>
     */
    public function resetPhashByGoodsId($goods_id)
    {
        $aGoodsGallery = GoodsGallery::where('goods_id', $goods_id)->select();
        ModelGoodsGalleryPhash::where('goods_id', $goods_id)->delete();
        if ($aGoodsGallery) {
            foreach ($aGoodsGallery as $imgInfo) {
                $fileName = GoodsImage::getThumbPath($imgInfo['path']);
                $pHash = $this->getphash($fileName);
                if (!$pHash) {
                    continue;
                }
                $aPHash = $this->cuttingPhash($pHash);
                $data = [];
                $data['id'] = $imgInfo['id'];
                $data['phash1'] = $aPHash[0];
                $data['phash2'] = $aPHash[1];
                $data['goods_id'] = $imgInfo['goods_id'];
                $data['sku_id'] = $imgInfo['sku_id'];
                $data['is_default'] = $imgInfo['is_default'];
                $ModelGoodsGalleryPhash = new ModelGoodsGalleryPhash();
                $ModelGoodsGalleryPhash
                    ->isUpdate(false)
                    ->allowField(true)
                    ->save($data);

            }
        }
    }

    public function getImage($file)
    {
        $extname = pathinfo($file, PATHINFO_EXTENSION);
        $extname = strtolower($extname);
        if (!in_array($extname, ['jpg', 'jpeg', 'png', 'gif'])) {
            throw new Exception('该图片格式不正确');
        }
        $img = call_user_func('imagecreatefrom' . ($extname == 'jpg' ? 'jpeg' : $extname), $file);
        if (!$img) {
            throw new Exception('无法打开远程图片' . $file);
        }
        return $img;
    }

    public function getphash($file)
    {
        $w = 8;
        $h = 8;
        $img = imagecreatetruecolor($w, $h);
        list($src_w, $src_h) = getimagesize($file);
        $src = $this->getImage($file);
        imagecopyresampled($img, $src, 0, 0, 0, 0, $w, $h, $src_w, $src_h);
        imagedestroy($src);
        $total = 0;
        $array = array();
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $gray = (imagecolorat($img, $x, $y) >> 8) & 0xFF;
                if (!isset($array[$y])) $array[$y] = array();
                $array[$y][$x] = $gray;
                $total += $gray;
            }
        }
        imagedestroy($img);
        $average = intval($total / ($w * $h * 2));
        $hash = '';
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $hash .= ($array[$y][$x] >= $average) ? '1' : '0';
            }
        }
        return $hash;
    }


}