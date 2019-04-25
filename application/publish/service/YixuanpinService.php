<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 17-12-13
 * Time: 下午1:54
 */

namespace app\publish\service;

use app\common\exception\JsonErrorException;
use app\common\exception\QueueException;
use app\common\model\wish\WishTagsRecommand;
use app\common\service\UniqueQueuer;
use app\publish\queue\WishTagsQueue;
use \QL\QueryList;
use \GuzzleHttp\Cookie\CookieJar;
use think\Exception;

use app\common\cache\Cache;

class YixuanpinService
{
    protected $username = 'hbchan';
    protected $password = 'hbchan19';
    //登录url地址
    protected const LOGINURL = "https://www.yixuanpin.cn/api/users/login";
    //验证码地址
    protected const CODEURL = "https://www.yixuanpin.cn/api/validate?type=loginCode";

    protected const TAGSURL = "https://www.yixuanpin.cn/api/tag/search";

    private $domain = 'www.yixuanpin.cn';

    private $cookie = null;

    private $ql = null;

    public function __construct($username = null, $password = null)
    {
        $this->ql = QueryList::getInstance();
        if ($username) {
            $this->username = $username;
        }

        if ($password) {
            $this->password = $password;
        }
    }

    /**
     * 保存tags
     * @param array $tags
     */
    public function saveTags(array $tags, $query = '')
    {
        try {
            if (!empty($tags)) {
                foreach ($tags as $tag) {
                    $data = [
                        'query' => $query,
                        'tag' => $tag['tagName'],
                        'goods_num' => $tag['goodsNum'],
                        'sale_num' => $tag['sale'],
                        'comment_num' => $tag['commentNum'],
                        'focus_num' => $tag['focusNum'],
                        'sale_ratio' => round($tag['saleRatio'], 2),
                        'update_time' => time(),
                    ];
                    $model = (new WishTagsRecommand());
                    $where['query'] = ['=', $query];
                    $where['tag'] = ['=', $tag['tagName']];
                    if ($row = $model->where($where)->find()) {
                        $data['id'] = $row['id'];
                        $model->isUpdate(true)->allowField(true)->save($data);
                    } else {
                        $data['create_time'] = time();
                        $model->isUpdate(false)->allowField(true)->save($data);
                    }
                }
            }
        } catch (Exception $exp) {
            throw new QueueException($exp->getMessage());
        }

    }

    /**
     * 获取tags
     * @param string $keyword
     * @return mixed
     */
    public function getTags($keyword = '', $l = 0)
    {

        try {
            if (empty($keyword)) {
                return [];
            }
            //去重过滤，重新索引数字键；
            $keyword = array_merge(array_unique(array_filter(explode(',', $keyword))));
            if (empty($keyword) || empty($keyword[0])) {
                return [];
            }

            //如果是本地查找
            if ($l) {
                return $this->getLocalTags($keyword);
            }

            $this->setCookie();

            //用来装返回的数据；
            $data = [];
            foreach ($keyword as $val) {
                $param = [
                    't' => '1513144354954',
                    'granularity' => 'd30',
                    'isBatch' => 0,
                    'isNew' => 0,
                    'keyword' => $val,
                    'pageNum' => 1,
                    'pageSize' => 30,
                    'sortBy' => 'sale'
                ];

                $this->ql->get(self::TAGSURL, $param, [
                    'cookies' => $this->cookie
                ]);

                $content = $this->ql->getHtml();
                if ($content) {
                    $response = json_decode($content, true);
                    if (isset($response['code']) && $response['code'] = 1 && isset($response['data']['results'])) {
                        $response['data']['query'] = $val;
                        $tags = $response['data']['results'];
                        foreach ($tags as $tag) {
                            array_push($data, $tag['tagId']);
                        }

                        (new UniqueQueuer(WishTagsQueue::class))->push($response['data']);
                        //(new WishTagsQueue($response['data']))->execute();
                    }
                } else {
                    throw new JsonErrorException("tags获取失败");
                }
            }
            return $data;
        } catch (Exception $exp) {
            throw new JsonErrorException($exp->getMessage());
        }
    }


    /**
     * 拿本地关键字；
     * @param $keyword
     * @return array
     */
    public function getLocalTags($keyword)
    {
        if (empty($keyword)) {
            return [];
        }
        if (count($keyword) == 1) {
            return WishTagsRecommand::where(['query' => ['like', $keyword[0]. '%']])->order('sale_num', 'desc')->limit(100)->column('tag');
        } else {
            return WishTagsRecommand::where(['query' => ['in', $keyword]])->order('sale_num', 'desc')->limit(100)->column('tag');
        }
    }


    //    public function getTags($keyword='')
    //    {
    //        try{
    //            if(empty($keyword))
    //            {
    //                return [];
    //            }
    //            $login = $this->login();
    //
    //            $param=[
    //                't'=>'1513144354954',
    //                'granularity'=>'d30',
    //                'isBatch'=>0,
    //                'isNew'=>0,
    //                'keyword'=>$keyword,
    //                'pageNum'=>1,
    //                'pageSize'=>30,
    //                'sortBy'=>'sale'
    //            ];
    //
    //            $ql = $login->get(self::TAGSURL.'?'.http_build_query($param),[]);
    //
    //            $content = $ql->setQuery([])->html;
    //
    //            $response = json_decode($content,true);
    //
    //            $data=[];
    //            if(isset($response['code']) && $response['code']=1 && isset($response['data']['results']))
    //            {
    //                $response['data']['query']=$keyword;
    //                $tags = $response['data']['results'];
    //                foreach ($tags as $tag)
    //                {
    //                    array_push($data,$tag['tagId']);
    //                }
    //
    //                (new UniqueQueuer(WishTagsQueue::class))->push($response['data']);
    //            }
    //            return $data;
    //        }catch (Exception $exp){
    //            throw new JsonErrorException($exp->getMessage());
    //        }
    //
    //
    //    }

    /**
     * 模拟登录
     * @return mixed
     */
    private function login()
    {
        try {
            require_once ROOT_PATH . '/extend/QueryList/vendor/autoload.php';
            $file = ROOT_PATH . 'public' . DS . 'wish_cookie.txt';

            if (!file_exists($file)) {

                $fp = fopen($file, "a") or die("Unable to open file!");
                if ($fp) {
                    fclose($fp);
                }
            }

            $login = QueryList::run('Login', [
                'target' => self::LOGINURL,
                'method' => 'post',
                //登陆表单需要提交的数据
                'params' => [
                    'username' => $this->username,
                    'password' => $this->password
                ],
                'cookiePath' => $file
            ]);
            return $login;
        } catch (Exception $exp) {
            throw new JsonErrorException($exp->getMessage());
        }


    }

    /**
     * 登录设置cookie;
     */
    private function setCookie()
    {
        $time = time();
        $account = [
            'username' => $this->username,
            'password' => $this->password
        ];

        $key = 'yixuanpin:cookie:' . $this->domain;

        //Cache::handler()->hDel('QlCookie:'. $this->domain, $this->userName);
        //拿缓存，缓存存在则判断时间, 在有效期内则不用重新登录；
        $result = Cache::handler()->hget($key, $this->username);

        if ($result) {
            $result = json_decode($result, true);
            //在有效期内
            if (isset($result[0]['Expires']) && $result[0]['Expires'] >= $time) {
                $this->cookie = CookieJar::fromArray($this->makeCookie($result), $this->domain);
                return true;
            }
        }

        $lock = Cache::store('Lock');
        //缓存不存在，或存在但并不在有效期内，则加锁拿cookie,如有同时后来者，则在锁外面等别人拿到cookie写进缓存后，再进去缓存拿；
        //给key程序加锁,因为有等待时间，加锁成功后，分两种两情况1.同时没有程序在更新cookie; 2.等待加锁的同时有程序已经加锁后在取cookie,此次加锁进去，cookie缓存已经设置；
        if ($lock->lock($key . $this->username, 30)) {
            //加锁成功，可能是在别人解锁成功后才加锁成功的，所以进来必须先查缓存；
            $result = Cache::handler()->hget($key, $this->username);
            if ($result) {
                $result = json_decode($result, true);
                if (isset($result[0]['Expires']) && $result[0]['Expires'] >= $time) {
                    $this->cookie = CookieJar::fromArray($this->makeCookie($result), $this->domain);
                    //加锁成功，必须解锁，否则会造成死锁，导致加锁失败
                    $lock->unlock($key . $this->username);
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
            if (empty($tmpArr)) {
                Cache::handler()->hset($key, $this->username, json_encode($tmpArr));
                throw new JsonErrorException('用户名' . $this->username . '登录获取cookie失败，请检查帐号是否错误');
            }
            $tmpArr[0]['Expires'] = $time + 600;

            Cache::handler()->hset($key, $this->username, json_encode($tmpArr));
            //加锁成功，必须解锁，否则会造成死锁，导致加锁失败
            $lock->unlock($key . $this->username);
            return true;
        } else {
            throw new JsonErrorException($this->username . '加锁时可能造成了死锁');
        }
    }


    /** 组成需要的cookie数组 */
    private function makeCookie(Array $result)
    {
        $data = [];
        foreach ($result as $row) {
            $data[$row['Name']] = $row['Value'];
        }
        return $data;
    }
}