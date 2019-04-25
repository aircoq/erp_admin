<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 17-12-21
 * Time: 上午10:55
 */

namespace app\publish\service;

use app\common\service\UniqueQueuer;
use app\publish\queue\AliexpressHotwordsQueue;
use app\common\model\aliexpress\AliexpressHotWord;
use app\common\model\aliexpress\AliexpressBcar;

use \QL\QueryList;
use \GuzzleHttp\Cookie\CookieJar;
use app\common\cache\Cache;
use think\Exception;

use app\common\service\CommonQueuer;

class Wangxiaowang
{
    private $userName='WCYfive';
    private $password='smt-2188';
    private $cookie = null;
    private const LOGINURL="https://www.wxwerp.com/do/get.ashx";
    private $domain = 'www.wxwerp.com';
    protected $loginStatus=null;

    //热词链接
    private $hotUrl="http://www.wxwerp.com/m/ProductSearchOptimization/hotkeyword.aspx";

    //直通力链接
    private $bpcarUrl="http://www.wxwerp.com/m/ProductSearchOptimization/BpcarKeyword.aspx";

    //语言分类
    private $langArr = [
        'en' => '英语',
        'ru' => '俄语',
        'sp' => '西班牙语',
        'pt' => '葡萄牙语',
        'fa' => '法语',
        'it' => '意大刘语',
        'de' => '德语',
        'nl' => '荷兰语',
        'kr' => '韩语',
        'jp' => '日语',
    ];

    public function __construct($account = [])
    {
        $this->ql = QueryList::getInstance();

        //如果初始化时传进了帐号，则设置；
        if(isset($account['username']) && isset($account['username'])) {
            $this->userName = $account['username'];
            $this->password = $account['password'];
        }
        //设置cookie,原来这两接口根本不需要登录，先放在这里;
        $this->setCookie();
    }

    /**
     * @param int $category $category：单页100000335， 多页200003307；
     * @param int $page
     * @param string $lang:语言选项en||ru
     * @param string $sort:按搜索人气排序(down||up)
     * @param int $reSearch
     * @return array
     */
    public function hotQuery($category = '', $page = 1, $lang = 'en', $sort = 'down', $reSearch = 1)
    {
        if(!isset($this->langArr[$lang])) {
            throw new Exception('语言分类不存在');
        }
        $time = time();
        $postData = [
            'page' => $page,
            'id' => '',
            'lang' => $lang,
            'sort' => 'searchs',
            'sort_to' => $sort,
            'categoryid' => $category,
            's' => 16507,
        ];

        //post数据过去，结果信存在$this->ql里面；
        $this->ql->post($this->hotUrl,  $postData, [
            'cookies' => $this->cookie
        ]);

        //post数据过去后，先量出页数；
        $pageMsg = $this->getPageMsg('hot');
        $list = $this->getDataList();

        //数组为空，重试开启，没有登录，则重新执行此函数并返回；
        if (empty($list) && $reSearch > 0 && !($this->checklogin())) {
            $reSearch--;
            //重新登录一次；
            $this->setCookie();
            return $this->hotQuery($category, $page, $lang, $sort, $reSearch);
        }

        //数据为空，则不往下执行；
        if (!$list) {
            return ['list' => [], 'max' => [], 'pageMsg' => $pageMsg];
        }

        $cidArr = [];
        foreach($list as &$val) {
            $val['category_id'] = $category;

            $val['lang'] = $lang;
            $val['word'] = preg_replace('/\s+/', ' ', trim($val['word']));
            //排重数组；
            $cidArr[] = $val['word'];
            $val['zh_word'] = preg_replace('/\s+/', ' ', trim($val['zh_word']));

            $val['search_popularity'] = (int)trim($val['search_popularity']);
            $val['search_index'] = (int)trim($val['search_index']);
            $val['click_rate'] = (float)str_replace('%', '', trim($val['click_rate']));
            $val['translate_rate'] = (float)str_replace('%', '', trim($val['translate_rate']));
            $val['competition'] = (int)trim($val['competition']);
            $val['country'] = preg_replace('/\s+/', ' ', trim($val['country']));
            $val['update_time'] = $time;
        }
        unset($val);

        $ahModel = new AliexpressHotWord();
        //找出当前关键字当前站点已存在数据库的分类ID；
        $oldList = $ahModel->where([
            'category_id' => $category,
            'word' => ['in', $cidArr],
        ])->column('id', 'word');

        //存在则更新，不存在则插入；
        foreach($list as &$val) {
            if(isset($oldList[$val['word']])) {
                $val['id'] = $oldList[$val['word']];
            }
        }

        unset($val);
        $ahModel->saveData($list);

        $maxArr = [
            'search_popularity' => max(array_column($list, 'search_popularity')),
            'search_index' => max(array_column($list, 'search_index')),
            'click_rate' => max(array_column($list, 'click_rate')),
            'translate_rate' => max(array_column($list, 'translate_rate')),
            'competition' => max(array_column($list, 'competition'))
        ];
        //(new CommonQueuer(EbayCategoryBackSave::class))->push($list);
        return ['list' => $list, 'max' => $maxArr, 'pageMsg' => $pageMsg];
    }

    /**
     * @param int $category $category：单页100000335， 多页200003307；
     * @param int $page
     * @param string $lang
     * @param string $sort:按热搜度排序(down||up)
     * @param int $reSearch
     * @return array
     */
    public function bcarQuery($category = '', $page = 1, $sort = 'down', $reSearch = 1)
    {
        $time = time();
        $postData = [
            'page' => $page,
            //'pagesize' => 50,//默认20传了不起作用
            'id' => '',
            'sort' => 'searchs',
            'sort_to' => $sort,
            'categoryid' => $category,
            's' => 16507,
        ];

        //post数据过去，结果信存在$this->ql里面；
        $this->ql->post($this->bpcarUrl,  $postData, [
            'cookies' => $this->cookie
        ]);

        //post数据过去后，先量出页数；
        $pageMsg = $this->getPageMsg('bpcar');
        $list = $this->getBcarDataList();
        //数组为空，重试开启，没有登录，则重新执行此函数并返回；
        if (empty($list) && $reSearch > 0 && !($this->checklogin())) {
            $reSearch--;
            //重新登录一次；
            $this->setCookie();
            return $this->hotQuery($category, $page, $sort, $reSearch);
        }

        //数据为空，则不往下执行；
        if (!$list) {
            return ['list' => [], 'max' => [], 'pageMsg' => $pageMsg];
        }

        $cidArr = [];
        foreach($list as &$val) {
            $val['category_id'] = $category;
            $val['zh_word'] = $this->ql->post('http://www.wxwerp.com/do/get.ashx', [
                'act' => 'translate',
                'text' => trim($val['word']),
                's' => '16507',
            ], [
                'cookies' => $this->cookie
            ])->getHtml();


            $val['word'] = preg_replace('/\s+/', ' ', trim($val['word']));
            //排重数组；
            $cidArr[] = $val['word'];
            $val['searchs'] = (int)trim($val['searchs']);
            $val['cate_relevance'] = (int)trim($val['cate_relevance']);
            $val['competition'] = (int)trim($val['competition']);
            $val['avg_price'] = preg_replace('/\s+/', ' ', trim($val['avg_price']));

            $val['high_flow'] = (int)trim($val['high_flow']);
            $val['high_order'] = (int)trim($val['high_order']);
            $val['recommend'] = (int)trim($val['recommend']);
            $val['high_transform_rate'] = (int)trim($val['high_transform_rate']);

            $val['update_time'] = $time;
        }
        unset($val);

        $ahModel = new AliexpressBcar();
        //找出当前关键字当前站点已存在数据库的分类ID；
        $oldList = $ahModel->where([
            'category_id' => $category,
            'word' => ['in', $cidArr],
        ])->column('id', 'word');
        //存在则更新，不存在则插入；
        foreach($list as &$val) {
            if(isset($oldList[$val['word']])) {
                $val['id'] = $oldList[$val['word']];
            }
        }
        unset($val);
        $ahModel->saveData($list);

        $maxArr = [
            'searchs' => max(array_column($list, 'searchs')),
            'cate_relevance' => max(array_column($list, 'cate_relevance')),
            'competition' => max(array_column($list, 'competition')),
        ];

        //(new CommonQueuer(EbayCategoryBackSave::class))->push($list);
        return ['list' => $list, 'max' => $maxArr, 'pageMsg' => $pageMsg];
    }

    private function getDataList()
    {
        $list = $this->ql->find('.hotkeyword table tr')->map(function($item) {
            return [
                'word' => $item->children('td')->eq(0)->children('a')->text(),
                'zh_word' => $item->children('td')->eq(0)->children('span')->text(),
                'search_popularity' => $item->children('td')->eq(1)->children('span.val')->text(),
                'search_index' => $item->children('td')->eq(2)->children('span.val')->text(),
                'click_rate' => $item->children('td')->eq(3)->children('span.val')->text(),
                'translate_rate' => $item->children('td')->eq(4)->children('span.val')->text(),
                'competition' => $item->children('td')->eq(5)->children('span.val')->text(),
                'country' => $item->children('td')->eq(6)->children('span')->text(),
            ];
        })->toArray();

        //为空，返回空数组
        if(empty($list)) {
            return [];
        } else { //不为空unset掉第一行标题栏；
            unset($list[0]);
            $list = array_merge($list);
            return $list;
        }
    }

    private function getBcarDataList()
    {
        $list = $this->ql->find('.bpcarkeyword table tr')->map(function($item) {
            return [
                'word' => $item->children('td')->eq(0)->children('a')->text(),
                'zh_word' => $item->children('td')->eq(0)->children('span')->text(),
                'searchs' => $item->children('td')->eq(1)->children('span.val')->text(),
                'cate_relevance' => $item->children('td')->eq(2)->children('span.val')->text(),
                'competition' => $item->children('td')->eq(3)->children('span.val')->text(),
                'avg_price' => $item->children('td')->eq(4)->children('span.val')->text(),

                'high_flow' => $item->children('td')->eq(5)->find('i.icon-ok')->size(),
                'high_order' => $item->children('td')->eq(6)->find('i.icon-ok')->size(),
                'recommend' => $item->children('td')->eq(7)->find('i.icon-ok')->size(),
                'high_transform_rate' => $item->children('td')->eq(8)->find('i.icon-ok')->size(),
            ];
        })->toArray();

        //为空，返回空数组
        if(empty($list)) {
            return [];
        } else { //不为空unset掉第一行标题栏；
            unset($list[0]);
            $list = array_merge($list);
            return $list;
        }
    }

    /**
     * @param string $type 'hot'||'bpcar'
     * @return array
     */
    private function getPageMsg($type = 'hot')
    {
        $html = $this->ql->find('script')->text();

        $data = [];//换两种情况来匹配
        if ($type == 'bpcar') {
            preg_match('/bpcarkeyword.page.Bind\(([^\)]*)\)/', $html, $data);
        } else {
            preg_match('/hotkeyword.page.Bind\(([^\)]*)\)/', $html, $data);
        }
        if(!empty($data[1])) {
            $arr = explode(',', $data[1]);
            $pageMsg =  [
                'page' => (int)trim(($arr[0] ?? 0), '" '),
                'pageTotal' => (int)trim(($arr[1] ?? 0), '" '),
                'total' => (int)trim(($arr[2] ?? 0), '" '),
            ];
            return $pageMsg;
        }
        return ['page' => 0, 'pageTotal' => 0, 'total' => 0];
    }

    public function getTitle() {
        $html = $this->ql->get('http://www.wxwerp.com/m/ProductSearchOptimization/?id=5a3ccbdeacb38d1944adf034&s=16507&m=1', [], [
            'cookies' => $this->cookie
        ])->getHtml();
        var_dump($html);
    }

    /** 注：此检测只能在post或get一次后使用，否则如果cookie有缓存时，再检测是否登录可能会不准 */
    private function checklogin()
    {
        $text = $this->ql->find('div.login')->text();
        //如果登录失败，清除缓存内的cookie,后面尝试重新登录；
        if(strpos($text, '账号') !== false && strpos($text, '密码')  !== false) {
            Cache::handler()->hDel('QlCookie:'. $this->domain, $this->userName);
            return false;
        } else {
            return true;
        }
    }

    /**
     * 登录设置cookie;
     */
    private function setCookie()
    {
        $time = time();
        $account = [
            'cmd' => '',
            'token_key' => '',
            'uname' => $this->userName,
            'upass' => $this->password,
            'rempass' => 'false',
            'act' => 'login_check'
        ];

        $key = 'QlCookie:'.$this->domain;

        //Cache::handler()->hDel('QlCookie:'. $this->domain, $this->userName);
        //拿缓存，缓存存在则判断时间, 在有效期内则不用重新登录；
        $result = Cache::handler()->hget($key, $this->userName);
        if($result) {
            $result = json_decode($result, true);
            //在有效期内
            if(isset($result[0]['Expires']) && $result[0]['Expires'] >= $time) {
                $this->cookie = CookieJar::fromArray($this->makeCookie($result), $this->domain);
                return true;
            }
        }

        //$this->unlock($key. $this->userName);
        //缓存不存在，或存在但并不在有效期内，则加锁拿cookie,如有同时后来者，则在锁外面等别人拿到cookie写进缓存后，再进去缓存拿；
        //给key程序加锁,因为有等待时间，加锁成功后，分两种两情况1.同时没有程序在更新cookie; 2.等待加锁的同时有程序已经加锁后在取cookie,此次加锁进去，cookie缓存已经设置；
        if ($this->lock($key. $this->userName)) {
            //加锁成功，可能是在别人解锁成功后才加锁成功的，所以进来必须先查缓存；
            $result = Cache::handler()->hget($key, $this->userName);
            if ($result) {
                $result = json_decode($result, true);
                if(isset($result[0]['Expires']) && $result[0]['Expires'] >= $time) {
                    $this->cookie = CookieJar::fromArray($this->makeCookie($result), $this->domain);
                    //加锁成功，必须解锁，否则会造成死锁，导致加锁失败
                    $this->unlock($key. $this->userName);
                    return true;
                }
            }

            //登录url
            $loginUrl = self::LOGINURL;
            //利用对象的引用传值带出cookie;
            $jar = new CookieJar();
            $this->ql->post($loginUrl, $account, [
                'cookies' => $jar
            ]);
            $this->cookie = $jar;

            //给cookie有效期设为10分钟；
            $tmpArr = $jar->toArray();
            if(empty($tmpArr)) {
                Cache::handler()->hset($key, $this->userName, json_encode($tmpArr));
                throw new Exception($key. '用户名'. $this->userName. '登录获取cookie失败，请检查帐号是否错误');
            }
            $tmpArr[0]['Expires'] = $time + 600;

            Cache::handler()->hset($key, $this->userName, json_encode($tmpArr));
            //加锁成功，必须解锁，否则会造成死锁，导致加锁失败
            $this->unlock($key. $this->userName);
            return true;
        } else {
            throw new Exception($key. $this->userName. '加锁时可能造成了死锁');
        }
    }

    /**
     * 给一个key加锁，失败则等待时间后重新尝试，最多尝试次数后，返回false防止造成偱环;同一个KEY加锁后必需要解锁；
     * @param $key 加要锁的KEY
     * @param int $maxTest 最大等待次数；
     * @return bool
     */
    private function lock($key, $maxTest = 10) {
        $bol = true;
        while(true) {
            $result = Cache::handler()->setnx($key, 1);
            if($result) {
                Cache::handler()->expire($key, 10);
                break;
            }
            $maxTest--;
            if($maxTest <= 0) {
                $bol = false;
                break;
            }
            usleep(100000);
        }
        return $bol;
    }

    /**
     * 加锁后必需解锁，否则下次同样key会换败
     * @param $key
     * @return bool
     */
    private function unlock($key) {
        Cache::handler()->delete($key);
        return true;
    }

    /** 组成需要的cookie数组 */
    private function makeCookie(Array $result) {
        $data = [];
        foreach($result as $row) {
            $data[$row['Name']] = $row['Value'];
        }
        return $data;
    }

}