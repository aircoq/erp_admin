<?php
namespace app\publish\service;


use app\common\cache\Cache;
use app\common\model\amazon\AmazonPublishProductDetail;
use app\common\model\amazon\AmazonUpcParam;
use app\common\service\Common as CommonServer;
use think\Exception;

class AmazonUpcService
{

    private $upcCache = null;

    public function __construct()
    {
        empty($this->upcCache) && $this->upcCache = Cache::store('AmazonUpc');
    }

    /**
     * 输送给前端的UPC；
     * @param $num
     */
    public function getUpc($num)
    {
        //$upcs = $this->upcCache->getUpcFromPool($num);
        //if (count($upcs) < $num) {
        //    $upcs2 = $this->getUpcByWeb($num - count($upcs), $upcs);
        //    $upcs = array_merge($upcs, $upcs2);
        //}
        $user = CommonServer::getUserInfo();

        if ($num > 1) {
            if (!$this->upcCache->getUpcLock($user['user_id'])) {
                throw new Exception('每5秒才能获取一次UPC');
            }
        }

        //不排重用原UPC；
        $upcs = $this->getAllUpc($num);
        if (!is_array($upcs)) {
            throw new Exception('调用获取UPC接口出错');
        }
        return $upcs;
    }


    public function getAllUpc($num) {
        $start = time();
        $upcs = [];
        for ($i = 0; $i < 10; $i++) {
            if (count($upcs) >= $num) {
                break;
            }
            $tmps = $this->getUpcByNum($num - count($upcs));
            $upcs = array_merge(array_unique(array_merge($upcs, $tmps)));
            if (time() - $start >= 20) {
                break;
            }
        }

        return $upcs;
    }


    /**
     * 拿取相对数量的UPC，并且去除重复，去掉存在erp里面的UPC；
     * 去重步骤：1.去除24-48小时内已经获取过的UPC，2.去除存在于数据库的UPC；
     * @param $num 需要获取的对应的数量；
     * @param array $old 已找出来的，用于排重
     * @return array
     */
    public function getUpcByWeb($num, $old = [])
    {
        //存放有效的UPC；
        $have = [];

        //获取指定数量的个数；
        $upcs = $this->getUpcByNum($num);
        if (!is_array($upcs)) {
            throw new Exception('调用获取UPC接口出错');
        }
        //Cache::handler()->hset('task:amazon:getUpcByWeb', date('Y-m-d H:i:s'), json_encode($upcs));

        //先查看在不在缓存；
        foreach ($upcs as $key=>$upc) {
            if ($this->upcCache->checkUpcRecord($upc) || in_array($upc, $old)) {
                unset($upcs[$key]);
            }
        }

        //通过缓存验证的UPC，如果还有剩下的，再经过数据库验证；
        if (!empty($upcs)) {
            if (empty($this->dmodel)) {
                $this->dmodel = new AmazonPublishProductDetail();
            }
            $exists = $this->dmodel->where(['product_id_value' => ['in', $upcs]])->group('product_id_value')->column('product_id_value');

            //找着的UPC
            if (!empty($exists)) {
                foreach ($upcs as $upc) {
                    if (!in_array($upc, $exists)) {
                        $have[] = $upc;
                    }
                }
            } else {
                $have = $upcs;
            }

            //记录本次可用的UPC；
            if (!empty($have)) {
                $this->upcCache->upcRecord($have);
            }
        }

        if (count($have) < $num) {
            $old = array_merge($have, $old);
            $have = array_merge($have, $this->getUpcByWeb($num - count($have), $old));
        }

        return $have;
    }


    /**
     * 根据数量获取相对应数据的UPC；
     * @param $num
     */
    private function getUpcByNum($num)
    {
        $params = ['count' => $num];
        $upcModel = new AmazonUpcParam();
        $headerParams = $upcModel->getUpcParma();
        
        //没有用完的，可以放进去用；
        if (!empty($headerParams)) {
            $params['code'] = $headerParams['code'];
            $params['header'] = $headerParams['header'];
        }

        $base_url = 'http://172.20.0.180:7001/api/erp';
        $url = $base_url. '?'. http_build_query($params);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        $response = curl_exec ( $ch );
        $response = json_decode($response, true);
        //获取了正确数量的UPC；
        if (is_array($response) && count($response) == $num) {
            return $response;
        }

        $errno = curl_errno($ch);
        $errstr = curl_error($ch);
        curl_close($ch);

        //能正常访问，UPC不对，可能UPC不足了，需要更换codehead；
        if ($errno === 0 && !empty($headerParams) && is_array($response))  {
            $returnCount = count($response);
            if ($num > 5 && $num <= 10 && $returnCount <= 1) {
                $upcModel->loseParam($headerParams);
            } else if ($num > 10 && $num <= 20 && $returnCount <= 2) {
                $upcModel->loseParam($headerParams);
            } else if ($num > 20 && $num <= 30 && $returnCount <= 3) {
                $upcModel->loseParam($headerParams);
            } else if ($num > 30 && $returnCount <= 4) {
                $upcModel->loseParam($headerParams);
            }
            return $response;
        }

        if ($errno != 0) {
            throw new Exception('访问UPC生成站点出错：'. $errstr);
        }
        return is_array($response)? $response : [];
    }

    public function autoGetUpcToPool($max = 0) {
        //每次拿取的个数；
        $limit = 50;
        if ($max == 0) {
            $max = $this->upcCache->getAutoNumber();
        } else {
            $this->upcCache->setAutoNumber($max);
        }

        //最大执行次数,暂时定为最大个数，防止无限执行下去；
        $maxOpera = $max;

        //UPC池原来的个数；
        $start = $this->upcCache->getPoolUpcTotal();

         //重复UPC记录；
        $key1 = 'task:amazon:upc-repetition:redis';
        $key2 = 'task:amazon:upc-repetition:mysql';

        $num = 1;
        $dmodel = new AmazonPublishProductDetail();
        while ($start < $max && $maxOpera > 0) {
            $time = date('Y-m-d/H:i:s');
            $have = [];
            //获取指定数量的个数；
            $upcs = $this->getUpcByNum($limit);
            //Cache::handler()->hset('task:amazon:getUpcByWeb', date('Y-m-d H:i:s'), json_encode($upcs));
            $num++;
            $repitileArr1 = [];
            $repitileArr2 = [];
            //先查看在不在缓存；
            foreach ($upcs as $key=>$upc) {
                if ($this->upcCache->checkUpcRecord($upc)) {
                    $repitileArr1[] = $upc;
                    unset($upcs[$key]);
                }
            }

            $exists = $dmodel->where(['product_id_value' => ['in', $upcs]])->group('product_id_value')->column('product_id_value');
            //每执行一次+1；
            $maxOpera++;

            foreach ($upcs as $upc) {
                if (!in_array($upc, $exists)) {
                    $have[] = $upc;
                } else {
                    $repitileArr2[] = $upc;
                }
            }

            $hashKey = $time. '-'. $num;
            Cache::handler()->hSet($key1, $hashKey, json_encode($repitileArr1));
            Cache::handler()->hSet($key2, $hashKey, json_encode($repitileArr2));

            if (!empty($have)) {
                $this->upcCache->addUpcToPool($have);
                $start = $this->upcCache->getPoolUpcTotal();
            }

            unset($upcs, $exists);
        }

        return true;
    }

    public function upcToDump($start, $len)
    {
        $dModel = new AmazonPublishProductDetail();
        $upcdatas = $dModel->where(['product_id_value' => ['<>', ''], 'id' => ['>=', $start]])
            ->limit($len)
            ->order('id', 'asc')
            ->column('product_id_value', 'id');

        if (empty($upcdatas)) {
            return false;
        }

        $upcs = ['upcs' => array_merge(array_unique($upcdatas))];
        $url = 'http://172.20.0.180:7001/api/readd';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($upcs));
        $response = curl_exec ($ch);
        $result = json_decode($response, true);

        if (isset($result['message']) && $result['message'] == '排重成功') {
            return true;
        } else {
            //失败的放进缓存；
            Cache::handler()->lpush('task:amazon:upc-dump', json_encode($start, $len));
            return false;
        }

    }
}