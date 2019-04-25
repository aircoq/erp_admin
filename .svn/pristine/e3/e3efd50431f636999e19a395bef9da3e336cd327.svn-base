<?php


namespace app\goods\service;

use service\baidu\baiduApi;
use app\common\model\GoodsGallery;
use think\Db;
use think\db\Query;
use PDO;
use think\Exception;
use app\common\cache\Cache;


class GoodsGalleryHash
{
    const CFG = [
        'api_key' => 'rHxrimkmo6EpMuwDdMI9E1CF',
        'secret_key' => 'M3oKE0EvI60VXwPPoBRbbmeNf6zQkBdd',
        'app_id' => 14763349
    ];
    const MAX_PUSH_TIMES = 9500;//一天最多推多少条
    const SCORE = 0.5;//分数大于这个值 表示相似

    public function times($set = false)
    {
        $key = "cache:goods:goods_gallery_push_baidu_times:" . date('Ymd');
        if ($set) {
            $result = Cache::handler()->incr($key);
            Cache::handler()->expireAt($key, strtotime('tomorrow'));
            return $result;
        }
        return Cache::handler()->get($key);
    }

    public function entry($goods_id, $goods_gallery_id, $file)
    {
        $api = baiduApi::instance(self::CFG)->loader('imageSearch');
        return $api->entry($goods_id, $goods_gallery_id, $file);
    }

    public function search($file)
    {
        $result = [];
        $file = preg_replace('/^(data:\s*image\/(\w+);base64,)/','',$file,1);
        $api = baiduApi::instance(self::CFG)->loader('imageSearch');
        $ret = $api->search($file);
        if(isset($ret['result'])){
            foreach ($ret['result'] as $row){
                if($row['score']>=self::SCORE){
                    $brief = json_decode($row['brief'],true);
                    if(isset($brief['goods_id'])){
                        $result[] = $brief['goods_id'];
                    }
                }
            }
            $result = array_unique($result);
        }
        return implode(',',$result);

    }

    public function pushBaidu($_id = 100000)
    {
        set_time_limit(0);
        $limit = 1000;
        $max = 9888888;
        $goods_id = $_id;
        $opts = [
            'http' => [
                "method" => "GET",
                "timeout" => 30
            ]
        ];
        $context = stream_context_create($opts);
        while ($goods_id <= $max) {
            try {
                $sCategory = '45,46,47,48,49,50,51,52,53,54,55,56,57,58,59,60,190,44';
                $sCategory .= ',32,33,34,35,36,37,38,39,40,41,42,43';
                $sCategory .= ',82,83,84,85,86,87,88,89,90,91,82,93,94';
                $sql = "SELECT i.* FROM goods_gallery i LEFT JOIN goods g ON g.id = i.goods_id LEFT JOIN category c ON g.category_id = c.id WHERE i.is_default = 1 AND  i.cont_sign = '' AND i.goods_id > {$goods_id} AND c.id in ({$sCategory}) GROUP BY i.goods_id,i.sku_id LIMIT " . $limit;
                $Q = new Query();
                $a = $Q->query($sql, [], true, true);
                $num = 0;
                while ($row = $a->fetch(PDO::FETCH_ASSOC)) {
                    $num++;
                    $goods_id = $row['goods_id'];
                    $times = $this->times();
                    if ($times > self::MAX_PUSH_TIMES) {
                        break(2);
                    }
                    try {
                        $fileName = GoodsImage::getThumbPath($row['path'], 800, 800);
                        $file_content = file_get_contents($fileName, false, $context);
                        if (!$file_content) {
                            throw new Exception('图片获取失败');
                        }
                        $result = $this->entry($row['goods_id'], $row['id'], $file_content);
                        if (isset($result['cont_sign']) && $result['cont_sign']) {
                            $updateData = [
                                'cont_sign' => $result['cont_sign'],
                                'log_id' => $result['log_id']
                            ];
                            $model = new GoodsGallery();
                            $model->isUpdate(true)->save($updateData,['id'=>$row['id']]);
                            $this->times(true);
                        }
                    } catch (\Exception $ex) {
                        $logFile = LOG_PATH . "goods/baidu(".date('Ymd').").log";
                        file_put_contents($logFile, $row['id'] . ":" . $ex->getMessage() . "\n", FILE_APPEND);
                        continue;
                    }
                }
                if(!$num){
                    break;
                }
            } catch (\Exception $e) {
                $logFile = LOG_PATH . "goods/baidu(".date('Ymd').").log";
                file_put_contents($logFile, $goods_id . ":" . $e->getMessage() . "\n", FILE_APPEND);
                continue;
            }
        }
    }
}
