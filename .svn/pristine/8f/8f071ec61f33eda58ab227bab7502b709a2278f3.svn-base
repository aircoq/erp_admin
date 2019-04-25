<?php

namespace app\common\model;

use erp\ErpModel;
use think\db\Query;
use app\common\cache\Cache;
use think\Request;
use think\Db;
use think\Config;
use Odan\Jwt\JsonWebToken;
use traits\model\SoftDelete;


/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/10/28
 * Time: 9:13
 */
class User extends ErpModel
{

    use SoftDelete;
    protected $deleteTime = 'delete_time';
    const DefaultPassword = 'Rondaful&@123#';

    public function getIdAttr($value)
    {
        return $value;
    }

    /** 用户与角色
     * @return mixed
     */
    public function roleUser()
    {
        return $this->hasOne(RoleUser::class, 'user_id', 'id', ['user' => 'u', 'role_user' => 'r'])->field('role_id');
    }

    /**
     * 用户登录
     * @param  string $username 用户名
     * @param  string $password 密码
     * @param  integer $mode 登录方式
     * @param  integer $user_type 用户类型
     * @return array|false
     */
    public function login($username, $password, $mode = 0, $user_type = null)
    {
        $condition = [];
        switch ($mode) {
            case 0:
                $condition['username|email|mobile'] = $username;
                break;
            case 1:
                $condition['username'] = $username;
                break;
            case 2:
                $condition['email'] = $username;
                break;
            case 3:
                $condition['mobile'] = $username;
                break;
            case 4:
                $condition['job_number'] = intval($username);
                break;
            case 5: // 短信登录
                $condition['mobile'] = $username;
                break;
            default:
                $this->error = '参数错误';
                return false;
        }
        $names = array('0' => '账号', '1' => '用户名', '2' => '邮箱名', '3' => '手机号', '4' => '工号');
        if (empty($username)) {
            $error = $names[$mode] . '不能为空';
            return ['state' => false, 'message' => $error];
        }
        if ((5 != $mode || 4 != $mode) && empty($password)) {
            $error = '密码不能为空';
            return ['state' => false, 'message' => $error];
        }
        $user = $this->where($condition)->find();
        if (empty($user)) {
            $error = $names[$mode] . '或者密码错误，登录失败！';
            $msg = $names[$mode] . '错误:' . $username;
            return ['state' => false, 'message' => $error];
        } else {
            if ($user['status'] == 0) {
                $error = '账号被禁用，登录失败！';
                $msg = '账号被禁用:' . $username;
                $user = false;
            } elseif ((5 != $mode || 4 != $mode) && $user['password'] != self::getHashPassword($password,
                    $user['salt'])
            ) {
                $error = $names[$mode] . '或者密码错误，登录失败！';
                $msg = '密码错误:' . $password;
                $user = false;
            } elseif (!empty($user_type)) { // 要求用户类型匹配
                $msg = '无登录权限';
                $error = '无登录权限';
                $user = false;
            } else {
                // 写入最新的登录时间
                $this->updateLoginTime($user['id']);
                $msg = '';
            }
        }
        $token = '';
        $ip = Request::instance()->ip();
        if ($user) {
            // 写入登录记录
            self::addLog($user['id'], $username, $msg, $ip);
            self::blacklist(true, $ip);
            //生产key
            $token = $this->createToken($user);
        } else {
            // 写入登录记录
            self::addLog(0, $username, $msg, $ip);
            self::blacklist(false, $ip);
            return ['state' => false, 'message' => $error];
        }
        (new \app\index\service\User())->tokenCache($user['id'], $token, 24 * 3600);
        return ['state' => $user, 'token' => $token];
    }

    public function quit()
    {
        $request = Request::instance();
        $key = Config::get('jwt_key');
        if (!$auth = $request->header('authorization')) {
            echo json_encode(['message' => '请先登录']);
            httpCode(401);
            exit;
        }
        $jwt = new JsonWebToken();
        $payload = $jwt->decode($auth, $key);

    }

    /** 生产jwt
     * @param $user
     * @return string
     */
    public function createToken($user)
    {
        $key = Config::get('jwt_key');
        $payload = [
            'iss' => 1,
            'exp' => strtotime(date('Y-m-d H:i:s') . ' + 1 day'),
            'aud' => '',
            'nbf' => time(),
            'iat' => time(),
            'jti' => uniqid('', true),
            'user_id' => $user['id'],
            'realname' => $user['realname'],
            'username' => $user['username']
        ];
        $jwt = new JsonWebToken();
        $token = $jwt->encode($payload, $key);
        return $token;
    }

    /**
     * 更新用户最后登录时间
     * @param $user_id
     */
    public function updateLoginTime($user_id)
    {
        $data = [
            'last_login_time' => self::getNowTime(),
            'last_login_ip' => Request::instance()->ip(),
        ];
        $this->where(array('id' => $user_id))->update($data);
    }

    /**
     * 检查ip黑名单
     * @param  integer $num 错误次数
     * @param  integer $minutes 禁止登录的时长
     * @return boolean
     */
    public static function checkBlacklist($num = 5, $minutes = 15)
    {
        $blacklist = Cache::get('Blacklist_ip');
        if ($blacklist) {
            $blacklist = json_decode($blacklist, true);
        }
        $ip = Request::instance()->ip();
        if ($blacklist && $blackInfo = $blacklist[$ip]) {
            if ($blackInfo['num'] >= $num && ((int)$blackInfo['time'] + 60 * $minutes) > self::getNowTime()) {
                return true;
            }
        }
        return false;
    }

    /**
     * 登录ip黑名单处理
     * @param  boolean $flag 登录是否成功
     * @param  string $ip ip地址
     * @return void
     */
    private static function blacklist($flag, $ip = '')
    {
        if (empty($ip)) {
            $ip = Request::instance()->ip();
        } //登录者IP
        $blacklist = Cache::get('Blacklist_ip');
        if ($blacklist) {
            $blacklist = json_decode($blacklist, true);
        }
        if ($flag) {
            if (isset($blacklist[$ip])) {
                unset($blacklist[$ip]);
                Cache::set('Blacklist_ip', json_encode($blacklist));
            }
        } else {
            if (!$blacklist) {
                $blacklist = array();
            }
            $num = isset($blacklist[$ip]) ? $blacklist[$ip]['num'] + 1 : 1;
            $blacklist[$ip] = array(
                'time' => self::getNowTime(),
                'num' => $num,
            );
            Cache::set('Blacklist_ip', json_encode($blacklist));
        }
    }

    /** 记录时间
     * @return mixed
     */
    public static function getNowTime()
    {
        return $_SERVER['REQUEST_TIME'];
    }

    /**
     * 记录用户登录情况
     * @param  integer $uid 用户ID
     * @param  string $username 用户名
     * @param  string $info 提示的信息
     * @param  string $ip 登录的ip
     * @return void
     */
    public static function addLog($uid, $username, $info, $ip = '')
    {
        if (empty($ip)) {
            $ip = Request::instance()->ip();
        }
        $request = Request::instance();
        $data = array(
            'user_id' => $uid,
            'username' => $username,
            'info' => $info,
            'create_time' => self::getNowTime(),
            'status' => !empty($uid) ? 1 : 0,
            'ip' => $ip,
            'type' => 0,
            'action' => $request->module() . '/' . $request->controller() . '/' . $request->action(),
            'params' => '',
            'referrer' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '',
        );
        Db::name('system_log')->insert($data); // 记录本次操作
    }

    /**
     * 获取hash后的密码
     * @param  string $value 要进和hash的内容
     * @param  string $key 密钥
     * @param  string $algo 使用的哈希算法
     * @return string
     */
    public static function getHashPassword($value, $key, $algo = 'sha1')
    {
        return hash_hmac($algo, $value, $key);
    }

    /** 生成随机秘钥
     * @param int $len
     * @param int $source
     * @return string
     */
    public static function getSalt($len = 24, $source = MCRYPT_DEV_URANDOM)
    {
        //return base64_encode(mcrypt_create_iv($len, $source));
        return base64_encode(random_bytes($len));
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user', 'user_id', 'user_id');
    }

    public static function scopeValid(Query $query)
    {
        $query->where('status', 1);
    }


    public function department()
    {
        return $this->hasOne(Department::class, 'id', 'department_id', 'left');
    }

}