<?php
/**
 * Created by PhpStorm.
 * User: wlw2533
 * Date: 2018/7/4
 * Time: 9:29
 */

namespace app\common\service;



use app\common\cache\Cache;
use app\common\model\GoogleTranslateLog;
use org\Curl;
use \Stichoza\GoogleTranslate\GoogleTranslate as TranslateApi;

use \think\Exception;
class GoogleTranslate
{

    const LANGNAMECODE = [
        '阿法尔语' =>  'aa',
        '法语' =>  'fr',
        '林堡语' =>  'li',
        '北萨米语' =>  'se',
        '阿布哈兹语' =>  'ab',
        '弗里西亚语' =>  'fy',
        '林加拉语' =>  'ln',
        '桑戈语' =>  'sg',
        '阿维斯陀语' =>  'ae',
        '爱尔兰语' =>  'ga',
        '老挝语' =>  'lo',
        '塞尔维亚-克罗地亚语' =>  'sh',
        '南非语' =>  'af',
        '苏格兰盖尔语' =>  'gd',
        '立陶宛语' =>  'lt',
        '僧加罗语' =>  'si',
        '阿坎语' =>  'ak',
        '加利西亚语' =>  'gl',
        '卢巴语' =>  'lu',
        '斯洛伐克语' =>  'sk',
        '阿姆哈拉语' =>  'am',
        '瓜拉尼语' =>  'gn',
        '拉脱维亚语' =>  'lv',
        '斯洛文尼亚语' =>  'sl',
        '阿拉贡语' =>  'an',
        '古吉拉特语' =>  'gu',
        '马达加斯加语' =>  'mg',
        '萨摩亚语' =>  'sm',
        '阿拉伯语' =>  'ar',
        '马恩岛语' =>  'gv',
        '马绍尔语' =>  'mh',
        '绍纳语' =>  'sn',
        '阿萨姆语' =>  'as',
        '豪萨语' =>  'ha',
        '毛利语' =>  'mi',
        '索马里语' =>  'so',
        '阿瓦尔语' =>  'av',
        '希伯来语' =>  'he',
        '马其顿语' =>  'mk',
        '阿尔巴尼亚语' =>  'sq',
        '艾马拉语' =>  'ay',
        '印地语' =>  'hi',
        '马拉亚拉姆语' =>  'ml',
        '塞尔维亚语' =>  'sr',
        '阿塞拜疆语' =>  'az',
        '希里莫图语' =>  'ho',
        '蒙古语' =>  'mn',
        '斯瓦特语' =>  'ss',
        '巴什基尔语' =>  'ba',
        '克罗地亚语' =>  'hr',
        '摩尔达维亚语' =>  'mo',
        '南索托语' =>  'st',
        '白俄罗斯语' =>  'be',
        '海地克里奥尔语' =>  'ht',
        '马拉提语' =>  'mr',
        '巽他语' =>  'su',
        '保加利亚语' =>  'bg',
        '匈牙利语' =>  'hu',
        '马来语' =>  'ms',
        '瑞典语' =>  'sv',
        '比哈尔语' =>  'bh',
        '亚美尼亚语' =>  'hy',
        '马耳他语' =>  'mt',
        '斯瓦希里语' =>  'sw',
        '比斯拉马语' =>  'bi',
        '赫雷罗语' =>  'hz',
        '缅甸语' =>  'my',
        '泰米尔语' =>  'ta',
        '班巴拉语' =>  'bm',
        '国际语A' =>  'ia',
        '瑙鲁语' =>  'na',
        '泰卢固语' =>  'te',
        '孟加拉语' =>  'bn',
        '印尼语' =>  'id',
        '书面挪威语' =>  'nb',
        '塔吉克斯坦语' =>  'tg',
        '藏语' =>  'bo',
        '国际语E' =>  'ie',
        '北恩德贝勒语' =>  'nd',
        '泰语' =>  'th',
        '布列塔尼语' =>  'br',
        '伊博语' =>  'ig',
        '尼泊尔语' =>  'ne',
        '提格里尼亚语' =>  'ti',
        '波斯尼亚语' =>  'bs',
        '四川彝语（诺苏语）' =>  'ii',
        '恩敦加语' =>  'ng',
        '土库曼语' =>  'tk',
        '加泰隆语' =>  'ca',
        '依努庇克语' =>  'ik',
        '荷兰语' =>  'nl',
        '他加禄语' =>  'tl',
        '车臣语' =>  'ce',
        '伊多语' =>  'io',
        '新挪威语' =>  'nn',
        '塞茨瓦纳语' =>  'tn',
        '查莫罗语' =>  'ch',
        '冰岛语' =>  'is',
        '挪威语' =>  'no',
        '汤加语' =>  'to',
        '科西嘉语' =>  'co',
        '意大利语' =>  'it',
        '南恩德贝勒语' =>  'nr',
        '土耳其语' =>  'tr',
        '克里语' =>  'cr',
        '因纽特语' =>  'iu',
        '纳瓦霍语' =>  'nv',
        '宗加语' =>  'ts',
        '捷克语' =>  'cs',
        '日语' =>  'ja',
        '尼扬贾语' =>  'ny',
        '塔塔尔语' =>  'tt',
        '古教会斯拉夫语' =>  'cu',
        '爪哇语' =>  'jv',
        '奥克语' =>  'oc',
        '特威语' =>  'tw',
        '楚瓦什语' =>  'cv',
        '格鲁吉亚语' =>  'ka',
        '奥吉布瓦语' =>  'oj',
        '塔希提语' =>  'ty',
        '威尔士语' =>  'cy',
        '刚果语' =>  'kg',
        '奥洛莫语' =>  'om',
        '维吾尔语' =>  'ug',
        '丹麦语' =>  'da',
        '基库尤语' =>  'ki',
        '奥利亚语' =>  'or',
        '乌克兰语' =>  'uk',
        '德语' =>  'de',
        '宽亚玛语' =>  'kj',
        '奥塞梯语' =>  'os',
        '乌尔都语' =>  'ur',
        '迪维希语' =>  'dv',
        '哈萨克语' =>  'kk',
        '旁遮普语' =>  'pa',
        '乌兹别克语' =>  'uz',
        '不丹语' =>  'dz',
        '格陵兰语' =>  'kl',
        '巴利语' =>  'pi',
        '文达语' =>  've',
        '埃维语' =>  'ee',
        '高棉语' =>  'km',
        '波兰语' =>  'pl',
        '越南语' =>  'vi',
        '现代希腊语' =>  'el',
        '卡纳达语' =>  'kn',
        '普什图语' =>  'ps',
        '沃拉普克语' =>  'vo',
        '英语' =>  'en',
        '朝鲜语' =>  'ko',
        '韩语' =>  'ko',
        '葡萄牙语' =>  'pt',
        '沃伦语' =>  'wa',
        '世界语' =>  'eo',
        '卡努里语' =>  'kr',
        '凯楚亚语' =>  'qu',
        '沃洛夫语' =>  'wo',
        '西班牙语' =>  'es',
        '克什米尔语' =>  'ks',
        '罗曼什语' =>  'rm',
        '科萨语' =>  'xh',
        '爱沙尼亚语' =>  'et',
        '库尔德语' =>  'ku',
        '基隆迪语' =>  'rn',
        '依地语' =>  'yi',
        '巴斯克语' =>  'eu',
        '科米语' =>  'kv',
        '罗马尼亚语' =>  'ro',
        '约鲁巴语' =>  'yo',
        '波斯语' =>  'fa',
        '康沃尔语' =>  'kw',
        '俄语' =>  'ru',
        '壮语' =>  'za',
        '富拉语' =>  'ff',
        '吉尔吉斯语' =>  'ky',
        '卢旺达语' =>  'rw',
        '中文' =>  'zh',
        '汉语' =>  'zh',
        '芬兰语' =>  'fi',
        '拉丁语' =>  'la',
        '梵语' =>  'sa',
        '祖鲁语' =>  'zu',
        '斐济语' =>  'fj',
        '卢森堡语' =>  'lb',
        '萨丁尼亚语' =>  'sc',
        '法罗语' =>  'fo',
        '卢干达语' =>  'lg',
        '信德语' =>  'sd'

    ];
    const PATTERN_REPLACE = [
        [
            'pattern' => '/^[^<>]*/',//匹配开头
            'replace' => 'replaceMatchBeforeTranslatePattern0_',
            'index' => 0,
        ],
        [
            'pattern' => '/>[^<>]*/',//匹配中间
            'replace' => 'replaceMatchBeforeTranslatePattern1_',
            'index' => 0,
        ],
        [
            'pattern' => '/>[^<>]*$/',//匹配结尾
            'replace' => 'replaceMatchBeforeTranslatePattern2_',
            'index' => 0,
        ]
    ];
    const IP_HOST_KEY = 'google:translate:proxy:url';//请求获取代理IP url缓存键名
    const IP_POOL_KEY = 'google:translate:ip:list';//IP池键名
    const IP_POOL_MAX_LEN_KEY = 'google:translate:ip:list_max';//IP池最大容量键名
    const PROXY_USER_PWD_KEY = 'google:translate:proxy:token';//请求代理服务器时的认证信息

    private $proxyIp;

    /**
     * 单字符串翻译
     * @param string $string 要翻译的字符串
     * @param array $options 翻译设置
     *              可设置值
     *                    target  目标语言简码，必须是可用的ISO 639-1语言简码。
     * @param int $userId  用户id,用于记录日志
     * @param int $channelId 平台id,用于记录日志
     * @return array 包含以下字段
     *               source 源语言简码
     *               input 输入的源语言字符串
     *               text 翻译后的目标语言
     *               model 翻译模式，可能为null
     * @throws Exception
     */
    public function translate(string $string, array $options, int $userId, int $channelId)
    {
        $res = $this->translateBatch([$string], $options, $userId, $channelId);
        return $res;
    }

    /**
     * 批量字符串翻译
     * @param array $strings 要翻译的字符串数组
     * @param array $options 翻译设置
     *              可设置值
     *                          target  目标语言简码，必须是可用的ISO 639-1语言简码。
     * @param int $userId  用户id,用于记录日志
     * @param int $channelId 平台id,用于记录日志
     * @return array 包含多个元素，每个元素均包含以下字段
     *               source 源语言简码
     *               input 输入的源语言字符串
     *               text 翻译后的目标语言
     *               model 翻译模式，可能为null
     *         格式类似于[[source,input,text,model], [source,input,text,model], ...]
     * @throws Exception
     */
    public function translateBatch(array $strings, array $options, int $userId, int $channelId)
    {
        if (!isset($options['target']) || !in_array($options['target'], array_values(self::LANGNAMECODE))) {
            throw new Exception('目标语言种类未设置或设置不正确');
        }
        $result = $this->dealBeforeTranslate($strings, $options['target']);

        if (!$result) {//没有需要翻译的，打包原样返回
            foreach ($strings as $string) {
                $res[] = [
                    'source' => 'en',
                    'input' => $string,
                    'text' => $string,
                    'model' => 'nmt'
                ];
            }
            return $res;
        }

        $sourceStrs = [
            'arrayStrs' => $result['arrayStrs']??[],
            'htmlStrs' => $result['htmlStrs']??[],
            'strings' => $strings,
        ];
        if (empty($result['strToTranslate']) ) {//记录中都存在
            return $this->packageTranslatedData($sourceStrs, $result['existLogs']);
        }



//        $strToTranslate = array_values($result['strToTranslate']);
        $subRes = $this->doTrans($result['strToTranslate'],$options['target']);
        $md5Res = $subRes;
        $allMd5Res = $result['existLogs']+$md5Res;
        return $this->packageTranslatedData($sourceStrs, $allMd5Res);
    }

    /**
     * 根据语言名称返回语言简码
     * @param string $name
     * @return bool|mixed
     */
    public function getLangCodeByName(string $name)
    {
        return self::LANGNAMECODE[$name] ?? false;
    }

    /**
     * 返回语言简码列表
     * @return array
     */
    public function getLangCodeList()
    {

        return array_values(self::LANGNAMECODE);

    }

    /**
     * 获取语言名称列表
     * @return array
     * @throws Exception
     */
    public function getLangNameList()
    {
        return array_keys(self::LANGNAMECODE);
    }

//    /**
//     * 写入日志
//     * @param array $log
//     * @throws Exception
//     */
//    private function writeLog(array $data, string $target, int $userId, int $channelId)
//    {
//        try {
//            $log = [];
//            !isset($data[0]) && $data = [$data];
//            foreach ($data as $k => $datum) {
//                $log[$k]['source'] = $datum['source'];
//                $log[$k]['input'] = $datum['input'];
//                $log[$k]['input_length'] = strlen($datum['input']);
//                $log[$k]['input_md5'] = md5($datum['input']);
//                $log[$k]['target'] = $target;
//                $log[$k]['output'] = $datum['text'];
//                $log[$k]['output_length'] = strlen($datum['text']);
//                $log[$k]['cost'] = number_format((20/1000000)*strlen($datum['input']), 6);//估算的费用，单位$
//                $log[$k]['model'] = $datum['model'];
//                $log[$k]['channel_id'] = $channelId;
//                $log[$k]['user_id'] = $userId;
//                $log[$k]['type'] = 0;
//                $log[$k]['create_time'] = time();
//            }
//            (new GoogleTranslateLog())->saveAll($log);
//        } catch (Exception $e) {
//            throw new Exception($e->getMessage());
//        }
//    }

    /**
     * 翻译前处理数据
     * @param array $strings
     * @param string $target
     * @return array
     * @throws Exception
     */
    private function dealBeforeTranslate(array $strings, string $target)
    {

        $newStrings = [];//存储新的字符串
        $arrayStrings = [];//存储字符串数组
        $htmlStrings = [];//存储html格式的字符串

        foreach ($strings as $k => $string) {
            if (is_array($string)) {//数组转化成单个字符串
                $arrayStrings[$k] = $string;
                $newStrings = array_merge($newStrings, $string);
                continue;
            }
            //html格式的，去除html标签
            if (strpos($string,'<p') !== false
                || strpos($string,'<br') !== false
                || strpos($string,'<span') !== false) {//html格式的
                $text = [];//存储去除html标签后的文本
                $strs = explode("\n", $string);
                $patternReplace = self::PATTERN_REPLACE;
                foreach ($strs as &$s) {
                    $s = preg_replace_callback_array([
                        $patternReplace[0]['pattern'] => function ($matches) use (&$text, &$patternReplace) {
                            if (preg_replace(['/>/', '/</', '/\s/'], ['', '', ''], $matches[0])) {//不是空才做处理
                                $text[] = str_replace(['>', '<'], ['', ''], $matches[0]);
                                return $patternReplace[0]['replace'] . $patternReplace[0]['index']++;
                            }
                            return $matches[0];
                        },
                        $patternReplace[1]['pattern'] => function ($matches) use (&$text, &$patternReplace) {
                            if (preg_replace(['/>/', '/</', '/\s/'], ['', '', ''], $matches[0])) {//不是空才做处理
                                $text[] = str_replace(['>', '<'], ['', ''], $matches[0]);
                                return '>' . $patternReplace[1]['replace'] . $patternReplace[1]['index']++;
                            }
                            return $matches[0];
                        },
                        $patternReplace[2]['pattern'] => function ($matches) use (&$text, &$patternReplace) {
                            if (preg_replace(['/>/', '/</', '/\s/'], ['', '', ''], $matches[0])) {//不是空才做处理
                                $text[] = str_replace(['>', '<'], ['', ''], $matches[0]);
                                return '>' . $patternReplace[2]['replace'] . $patternReplace[2]['index']++;
                            }
                            return $matches[0];
                        },
                    ], $s);
                }
                //replacedStr与tmpStr的区别就是replacedStr保留了html实体，因为实体的替换是不可逆的，所以必须保留一份原来的实体

                $replacedStr = implode("\n", $strs);//去除html标签后的字符串
                $tmpStr = preg_replace('/\&nbsp;/', ' ', $text);//html实体替换为空格
                $tmpStr = implode("\n", $tmpStr);

                $htmlStrings[$k] = [
                    'replacedStr' => $replacedStr,
                    'tmpStr' => $tmpStr,
                ];
                array_push($newStrings, $tmpStr);
                continue;
            }

            array_push($newStrings, $string);
        }

        $uniqueStrs = array_unique($newStrings);//过滤重复的

        //过滤空白字符和不需要翻译的字符
        $filterStrings = array_filter($uniqueStrs, function ($a){
            if (preg_replace('/\s/','',$a)//不全是空白字符
                && !preg_match('/^[A-Z]{2}[0-9]{5,7}$/', $a)//spu,sku
                && !preg_match('/^[0-9]*$/', $a)//纯数字
            )
                return true;
        });

        if (empty($filterStrings)) {//都被过滤了，直接返回
            return [];
        }

        //查记录
        $md5s = [];
        foreach ($filterStrings as $filterString) {
            $md5s[] = md5($filterString);
        }
        $md5Strings = array_combine($md5s, $filterStrings);//组合数组
//
//
//        $map['input_md5'] = ['in', $md5s];
//        $map['target'] = $target;
//        $logs = (new GoogleTranslateLog())->field('source,input,input_md5,output,model')->where($map)->select();
//
//        $existLogs = [];
//        $existMd5 = [];
//        if (!empty($logs)) {
//            foreach ($logs as $log) {
//                $existLogs[$log['input_md5']] = $log['output'];
//                $existMd5[] = $log['input_md5'];
//            }
//        }
//        //重新打包未翻译过的字符串
//        $strToTranslate = [];
//        foreach ($md5Strings as $md5 => $string) {
//            !in_array($md5, $existMd5) && $strToTranslate[$md5] = $string;
//        }
        return [
            'existLogs'=>[],//$existLogs,
            'strToTranslate'=>$md5Strings,//$strToTranslate,
            'arrayStrs'=>$arrayStrings,
            'htmlStrs'=>$htmlStrings
        ];
    }

    /**
     * 打包翻译后的数据
     * @param $sourceStrs
     * @param $translateStrs
     * @return array
     */
    private function packageTranslatedData($sourceStrs, $translateStrs)
    {

        $patternReplace = self::PATTERN_REPLACE;
        $strings = $sourceStrs['strings'];
        $arrayIndex = array_keys($sourceStrs['arrayStrs']);
        $htmlIndex = array_keys($sourceStrs['htmlStrs']);
        foreach ($strings as $k => $string) {
            if (in_array($k,$arrayIndex)) {
                $translatedStr = [];
                foreach ($sourceStrs['arrayStrs'][$k] as $str) {
                    $translatedStr[] = $translateStrs[md5($str)] ?? $str;
                }
            } elseif (in_array($k,$htmlIndex)) {
                $htmlTred = $translateStrs[md5($sourceStrs['htmlStrs'][$k]['tmpStr'])] ?? $string;
                $translatedStr = explode("\n",$htmlTred);
                $tmpTransStr = preg_replace_callback_array([
                    '/'.$patternReplace[0]['replace'].'\d+/' => function ($matches) use (&$translatedStr,$patternReplace) {
                        return $translatedStr[(int)substr($matches[0],strlen($patternReplace[0]['replace']))] ?? ' ';
                    },
                    '/'.$patternReplace[1]['replace'].'\d+/' => function ($matches) use (&$translatedStr,$patternReplace) {
                        return $translatedStr[(int)substr($matches[0],strlen($patternReplace[1]['replace']))] ?? ' ';
                    },
                    '/'.$patternReplace[2]['replace'].'\d+/' => function ($matches) use (&$translatedStr,$patternReplace) {
                        return $translatedStr[(int)substr($matches[0],strlen($patternReplace[2]['replace']))] ?? ' ';
                    },
                ],$sourceStrs['htmlStrs'][$k]['replacedStr']);
                $translatedStr = $tmpTransStr;
            } else {
                $translatedStr = $translateStrs[md5($string)] ?? $string;
            }
            $res[] = [
                'source' => 'en',
                'input' => $string,
                'text' => $translatedStr,
                'model' => 'nmt'
            ];
        }
        return $res;
    }


    /**
     * @title 获取可用ip
     */
    public function getAvailableIp()
    {
        //先从缓存获取
        $ipPoolLen = Cache::handler()->lLen(self::IP_POOL_KEY);
        $maxLen = Cache::handler()->get(self::IP_POOL_MAX_LEN_KEY) ?: 30;
        if ($ipPoolLen < $maxLen) {//没有获取到或数量过低，重新获取
            $ips = $this->getProxyIpList();
            foreach ($ips as $ip) {
                Cache::handler()->lPush(self::IP_POOL_KEY,$ip);
            }
        }
        //取出一个有效IP
        while (1) {
            $ip = Cache::handler()->rPop(self::IP_POOL_KEY);
            if (!$ip) {//被取空了，重新获取
                $this->getAvailableIp();
                break;
            }
            //ip格式为ip:port,expire_time,get_time
            $ipAr = explode(',', $ip);
            if (count($ipAr) != 3 || time()-$ipAr[2]>$ipAr[1]) {
                continue;
            }
            $this->proxyIp = $ipAr[0];
            Cache::handler()->lPush(self::IP_POOL_KEY,$ip);
            break;
        }

    }

    /**
     * @title 获取代理IP列表
     * @return mixed
     */
    public function getProxyIpList()
    {
        $proxyUrl = Cache::handler()->get(self::IP_HOST_KEY);
        $ips = Curl::curlGet($proxyUrl);
        if (!$ips) {
            throw new Exception('获取代理IP失败，请重试');
        }
        $ips = json_decode($ips,true);
        if (empty($ips) || !isset($ips['data']['proxy_list'])) {
            throw new Exception('获取代理IP失败，请重试');
        }
        $ipList = $ips['data']['proxy_list'];
        foreach ($ipList as &$ip) {
            $ip .= ','.time();//记录获取时间，以便判断是否过期,拼接后ip格式为ip:port,expire_time,get_time
        }
        return $ipList;
    }


    public function doTrans($strings,$target,$retryTimes=10)
    {
        try {
            $this->getAvailableIp();
            $curlOp = [
                'curl' =>  [
                    CURLOPT_PROXY => substr($this->proxyIp,0,strpos($this->proxyIp,':')),
                    CURLOPT_PROXYPORT => substr($this->proxyIp,strpos($this->proxyIp,':')+1),
                    CURLOPT_PROXYUSERPWD => Cache::handler()->get(self::PROXY_USER_PWD_KEY),
                ],
            ];
            $tr = (new TranslateApi($target,null,$curlOp));
            $tr->setUrl('https://translate.google.cn/translate_a/single');
            //避免多次提交，先进行合并
            $mergedStrs = [];
            $originStr = [];
            $i = 0;
            $translatedStrs = [];
            foreach ($strings as $md5 => $str) {
                $tmpTr = $tr->translate($str);
                $translatedStrs[$md5] = $tmpTr;

//                if (strpos($str,"\n") !== false) {//本身包含\n的不合并，因为要用\n做分隔符
//                    $originStr[$md5] = $str;
//                    continue;
//                }
//                if (mb_strlen($mergedStrs[$i]['string']??'') + mb_strlen($str) <5000) {
//                    $mergedStrs[$i] = [
//                        'string' => (empty($mergedStrs[$i]['string']) ? '' : $mergedStrs[$i]['string']."\n").$str,
//                        'md5' => (empty($mergedStrs[$i]['md5']) ? '' : $mergedStrs[$i]['md5'].',').$md5,
//                    ];
//                } else {
//                    $mergedStrs[++$i] = [
//                        'string' => $str,
//                        'md5' => $md5,
//                    ];
//                }
            }
//            $translatedStrs = [];
//            if ($mergedStrs) {
//                foreach ($mergedStrs as $mergedStr) {
//                    $tmpTr = $tr->translate($mergedStr['string']);
//                    $tmpTr = explode("\n",$tmpTr);
//                    $md5K = explode(',',$mergedStr['md5']);
//                    $cmTr = array_combine($md5K,$tmpTr);
//                    $translatedStrs = array_merge($translatedStrs,$cmTr);
//                }
//            }
//            if ($originStr) {
//                foreach ($originStr as $orMd5 => $orStr) {
//                    $tmpOrStr = $tr->translate($orStr);
//                    $translatedStrs = array_merge($translatedStrs,[$orMd5 => $tmpOrStr]);
//                }
//            }
            return $translatedStrs;
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if (strpos($msg,'cURL error') !== false && $retryTimes > 0) {
                Cache::handler()->rPop(self::IP_POOL_KEY);//不可用的从队列删除
                return $this->doTrans($strings,$target,$retryTimes-1);
            } else {
                throw new Exception($msg);
            }
        }
    }


}
