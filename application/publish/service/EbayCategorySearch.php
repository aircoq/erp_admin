<?php
/**
 * Created by PhpStorm.
 * User: zhangdongdong
 * Date: 2017/12/22
 * Time: 17:02
 */

namespace app\publish\service;

use think\Exception;
use \QL\QueryList;
use \GuzzleHttp\Cookie\CookieJar;
use app\common\cache\Cache;
use app\common\model\ebay\EbayCategoryKeyword;
use app\common\service\CommonQueuer;
use app\publish\queue\EbayCategoryBackSave;


class EbayCategorySearch
{
    public $userName = '';

    public $password = '';

    public $cookie = [];

    public $domain = '120.25.211.235';

    public $ql = null;

    public function __construct($account=[])
    {
        $this->ql = QueryList::getInstance();
        $this->userName = isset($account['username'])?$account['username']:'Chenhaibin';
        $this->password = isset($account['password'])?$account['password']:'onsale';
        //设置cookie;
        $this->setCookie();
    }

    /**
     * @param string $keyword 关键字
     * @param int $site 站点
     * @param int $reSearch 重试次数；
     * @return array
     */
    public function query($keyword = '', $site = 0, $reSearch = 1)
    {
        $time = time();
        $searchUrl = 'http://120.25.211.235/index.php/muban/getsuggestedcategories/siteid/'. $site. '/elementid/primarycategor';
        //找出table，遍历tr，找出数据；
        $list = $this->ql->post($searchUrl, ['query' => $keyword], [
            'cookies' => $this->cookie
        ])->find('table.r tr')->map(function($item)  {
            $data = [
                'category_id' => $item->children('th')->text(),
                'category_name' => $item->children('td')->eq(0)->text(),
                'score' => $item->children('td')->eq(1)->text(),
            ];
            return $data;
        })->toArray();
        //数组为空，重试开启，没有登录，则重新执行此函数并返回；
        if (empty($list) && $reSearch > 0 && !($this->checklogin())) {
            $reSearch--;
            //重新登录一次；
            $this->setCookie();
            return $this->query($keyword, $site, $reSearch);
        }

        //数据为空，则不往下执行；
        if (!$list) {
            return [];
        }
        $cidArr = [];
        foreach($list as &$val) {
            $val['category_id'] = (int)trim($val['category_id']);
            $cidArr[] = $val['category_id'];

            $val['category_name'] = preg_replace('/\s+/', ' ', trim($val['category_name']));
            $val['score'] = str_replace('%', '', trim($val['score']));
            $val['query'] = $keyword;
            $val['site_id'] = $site;
            $val['create_time'] = $time;
        }
        unset($val);

        $eckModel = new EbayCategoryKeyword();
        //找出当前关键字当前站点已存在数据库的分类ID；
        $cidArr = array_column($list, 'category_id');
        $oldList = $eckModel->where([
            'query' => $keyword,
            'site_id' => $site,
            'category_id' => ['in', $cidArr],
        ])->column('id', 'category_id');

        //存在则更新，不存在则插入；
        foreach($list as &$val) {
            if(isset($oldList[$val['category_id']])) {
                $val['id'] = $oldList[$val['category_id']];
            }
        }
        unset($val);

        (new CommonQueuer(EbayCategoryBackSave::class))->push($list);
        return $list;
    }

    /** 注：此检测只能在post或get一次后使用，否则如果cookie有缓存时，再检测是否登录可能会不准 */
    private function checklogin()
    {
        $text = $this->ql->find('form')->text();
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
        $account = $account = [
            'username' => $this->userName,
            'password' => $this->password,
        ];
        $key = 'QlCookie:'.$this->domain;

        //Cache::handler()->hDel('QlCookie:'. $this->domain, $this->userName);
        //拿缓存，缓存存在则判断时间, 在有效期内则不用重新登录；
        $result = Cache::handler()->hget($key, $this->userName);
        if($result) {
            $result = json_decode($result, true);
            //在有效期内
            if(!isset($result[0]['Expires']) || $result[0]['Expires'] >= $time) {
                $this->cookie = CookieJar::fromArray($this->makeCookie($result), $this->domain);
                return true;
            }
        }

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
            $loginUrl = 'http://120.25.211.235/index.php/myibay/login/redirect/%252Findex.php%252Fmyibay';
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
            $this->unlock($key);
            throw new Exception($key. $this->userName. '加锁时可能造成了死锁');
        }
    }

    /**
     * 给一个key加锁，失败则等待时间后重新尝试，最多尝试次数后，返回false防止造成偱环;同一个KEY加锁后必需要解锁；
     * @param $key 加要锁的KEY
     * @param int $maxTest 最大等待次数；
     * @return bool
     */
    private function lock($key, $maxTest = 50) {
        $bol = true;
        while(true) {
            $result = Cache::handler()->setnx($key, 1);
            if($result) {
                Cache::handler()->expire($key, 12);
                break;
            }
            $maxTest--;
            if($maxTest <= 0) {
                $bol = false;
                break;
            }
            usleep(200000);
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