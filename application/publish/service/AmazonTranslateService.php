<?php
/**
 * Created by PhpStorm.
 * User: zhangdongdong
 * Date: 2018/7/7
 * Time: 14:26
 */

namespace app\publish\service;


use app\common\service\ChannelAccountConst;
use app\common\service\Common;
use app\common\service\GoogleTranslate;
use think\Exception;

class AmazonTranslateService
{

    private $siteLangMap = [
        'US' => 'en',
        'UK' => 'en',
        'DE' => 'de',
        'CA' => 'en',
        'FR' => 'fr',
        'IT' => 'it',
        'JP' => 'ja',
        'ES' => 'es',
        'AU' => 'en',
        'MX' => 'es',
        'IN' => 'en',
    ];

    private $gooleTranslate = null;

    public function translate($data)
    {
        try {
            $data = $this->checkParams($data);
            $this->gooleTranslate = new GoogleTranslate();
            $newData = [];
            foreach ($data as $val) {
                $val['data'] = $this->handleTranslate($val['data'], $val['site']);
                $newData[] = $val;
            }
            return $newData;
        } catch (Exception $e) {
            throw new Exception('file:'. $e->getFile(). '，line:'. $e->getLine(). '，msg:'. $e->getMessage());
        }
    }

    /**
     * 检测data格式
     * @param $data
     * @return mixed
     * @throws Exception
     */
    public function checkParams($data)
    {
        try {
            $data = json_decode($data, true);
            if ($data === false) {
                throw new Exception('参数错误，不是正确的 json字符串 格式');
            }

            if (empty($data)) {
                throw new Exception('json字符串 参数为空');
            }

            if (!is_array($data)) {
                throw new Exception('json字符串 参数内容格式错误');
            }

            $line = count($data);
            foreach ($data as $key=>$val) {
                $pre = '';
                if ($line > 1) {
                    $pre = '第'. ($key + 1). '个翻译页面，';
                }
                if (empty($val['account_id'])) {
                    throw new Exception($pre. '帐号ID参数 为空');
                }
                if (empty($val['site'])) {
                    throw new Exception($pre. '站点参数site 为空');
                }
                $site = $val['site'];
                if (is_numeric($site)) {
                    $site = AmazonCategoryXsdConfig::getSiteByNum($site);
                }
                if (empty($this->siteLangMap[$site])) {
                    throw new Exception($pre. '站点参数site值[ '. $val['site']. ' ]不在预设翻译语言内');
                }
                if (empty($val['data'])) {
                    throw new Exception($pre. '翻译内空为空');
                }
            }
        } catch (Exception $e) {
            throw new Exception('数据检测：'. $e->getMessage());
        }

        return $data;
    }

    /**
     * 组合成条件；
     * @param $data
     * @param $site
     * @return array
     */
    public function handleTranslate($data, $site)
    {
        if (is_numeric($site)) {
            $site = AmazonCategoryXsdConfig::getSiteByNum($site);
        }
        $lang = $this->siteLangMap[$site];

        $options = [
            'source' => '',
            'target' => $lang,
            'format' => 'html',
            'model' => 'nmt',
        ];

        $data = $this->recursionTranslate($data, $options);

        return $data;
    }

    /**
     * 开始翻译；
     * @param $data
     * @param $options
     * @return array
     * @throws Exception
     */
    public function recursionTranslate($data, $options) {
        $gooleTranslate = new GoogleTranslate();

        //组成平面数组；
        $flatdata = $this->getFlatData($data);
        //把值拿去翻译
        $toTranslateArr = array_values($flatdata);

        $user = Common::getUserInfo();
        $uid = $user['user_id'];
        //翻译回来的数据；
        $translateArr = $gooleTranslate->translateBatch($toTranslateArr, $options, $uid, ChannelAccountConst::channel_amazon);
        $translateArr = $this->checkTransalateArr($translateArr);
        //对比一下长度；
        if (count($flatdata) != count($translateArr)) {
            throw new Exception('翻译回来的数据出错，个数不对，请联系研发人员');
        }
        //装上脚部；
        $flatTranslateArr = array_combine(array_keys($flatdata), $translateArr);

        //把平面数组，装回去；
        $soliddata = $this->setFlatData($data, $pre = '', $flatTranslateArr);

        return $soliddata;
    }


    //转换一下本地的数据；
    public function checkTransalateArr($arr)
    {
        if (empty($arr)) {
            return [];
        }
        $new = [];
        foreach ($arr as $val) {
            $new[] = str_replace('&#39;', '\'', $val);
        }
        return $new;
    }


    /**
     * 把多维数据组合成平面数组；
     * @param $data
     * @param string $pre
     * @return array
     */
    public function getFlatData($data, $pre = '') {
        $new = [];
        foreach ($data as $key=>$val) {
            $newKey = trim($pre. '-'. $key, '-');
            if (is_string($val)) {
                $new[$newKey] = $val;
            } else if (is_array($val)) {
                $down = $this->getFlatData($val, $newKey);
                $new = array_merge($new, $down);
            }
        }

        return $new;
    }

    /**
     * 把平面数组再组合回原来的多维数组；
     * @param $data
     * @param string $pre
     * @param $translate
     * @return array
     */
    public function setFlatData($data, $pre = '', $translate) {
        $new = [];
        foreach ($data as $key=>$val) {
            $newKey = trim($pre. '-'. $key, '-');
            if (is_string($val)) {
                $new[$key] = $translate[$newKey]['text'];
            } else if (is_array($val)) {
                $new[$key] = $this->setFlatData($val, $newKey, $translate);
            }
        }
        return $new;
    }
}