<?php
namespace app\common\service;

use app\common\cache\Cache;
use think\Exception;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/11/2
 * Time: 17:44
 */
class Encryption
{
    protected $encryption_key = '';
    protected $_hash_type = 'sha1';
    protected $_mcrypt_exists = true;
    protected $_mcrypt_cipher;
    protected $_mcrypt_mode;
    protected $private_key_password = 'secret_ronda_ful';
    protected $encrypt_key = 'base64';
    static $lastTimestamp = -1;
    static $sequence = 0;

    const path = ROOT_PATH . DS . 'public' . DS . 'certs' . DS;    //证书路径


    public function init()
    {
        $this->_mcrypt_exists = (!function_exists('mcrypt_encrypt')) ? false : true;
    }

    /** 获取加密秘钥
     * @param string $key
     * @return string
     * @throws Exception
     */
    public function get_key($key = '')
    {
        if ($key == '') {
            if ($this->encryption_key != '') {
                return $this->encryption_key;
            }
            $key = "ENCRYPTION_KEY";
            if ($key == false || empty($key)) {
                throw new Exception("为了使用加密类需要设置一个加密密钥");
            }
        }
        return md5($key);
    }

    /** 设置加密秘钥
     * @param string $key
     */
    public function set_key($key = '')
    {
        $this->encryption_key = $key;
    }

    /** 执行加密
     * @param $string
     * @param string $key
     * @return string
     * @throws Exception
     */
    public function encode($string, $key = '')
    {
        $key = $this->get_key($key);
        $enc = null;
        if ($this->_mcrypt_exists === true) {
            $enc = $this->mcrypt_encode($string, $key);
        }
        return base64_encode($enc);
    }

    /** 执行解密
     * @param $string
     * @param string $key
     * @return bool|string
     * @throws Exception
     */
    public function decode($string, $key = '')
    {
        $key = $this->get_key($key);
        if (preg_match('/[^a-zA-Z0-9\/\+=]/', $string)) {
            return false;
        }
        $dec = base64_decode($string);
        if ($this->_mcrypt_exists === true) {
            if (($dec = $this->mcrypt_decode($dec, $key)) === false) {
                return false;
            }
        }
        return $dec;
    }

    /** 加密使用mcrypt
     * @param $data
     * @param $key
     * @return string
     */
    function mcrypt_encode($data, $key)
    {
        $init_size = mcrypt_get_iv_size($this->_get_cipher(), $this->_get_mode());
        $init_iv = mcrypt_create_iv($init_size, MCRYPT_RAND);
        return $this->_add_cipher_noise($init_iv . mcrypt_encrypt($this->_get_cipher(), $key, $data,
                $this->_get_mode(), $init_iv), $key);
    }

    /** 解密使用mcrypt
     * @param $data
     * @param $key
     * @return bool|string
     */
    function mcrypt_decode($data, $key)
    {
        $data = $this->_remove_cipher_noise($data, $key);
        $init_size = mcrypt_get_iv_size($this->_get_cipher(), $this->_get_mode());
        if ($init_size > strlen($data)) {
            return false;
        }
        $init_iv = substr($data, 0, $init_size);
        $data = substr($data, $init_size);
        return rtrim(mcrypt_decrypt($this->_get_cipher(), $key, $data, $this->_get_mode(), $init_iv), "\0");
    }

    /** Set the Mcrypt Cipher
     * @access    public
     * @param    constant
     * @return    string
     */
    function set_cipher($cipher)
    {
        $this->_mcrypt_cipher = $cipher;
    }

    /** Set the Mcrypt Mode
     * @access    public
     * @param    constant
     * @return    string
     */
    function set_mode($mode)
    {
        $this->_mcrypt_mode = $mode;
    }

    /** Get Mcrypt cipher Value
     * @access    private
     * @return    string
     */
    function _get_cipher()
    {
        if ($this->_mcrypt_cipher == '') {
            $this->_mcrypt_cipher = MCRYPT_RIJNDAEL_256;
        }
        return $this->_mcrypt_cipher;
    }

    /** Get Mcrypt Mode Value
     * @access    private
     * @return    string
     */
    function _get_mode()
    {
        if ($this->_mcrypt_mode == '') {
            $this->_mcrypt_mode = MCRYPT_MODE_CBC;
        }
        return $this->_mcrypt_mode;
    }

    /**      * @desc Adds permuted noise to the IV + encrypted data to protect
     *         against Man-in-the-middle attacks on CBC mode ciphers
     * @param $data
     * @param $key
     * @return string
     */
    function _add_cipher_noise($data, $key)
    {
        $keyHash = $this->hash($key);
        $keyLen = strlen($keyHash);
        $str = '';
        for ($i = 0, $j = 0, $len = strlen($data); $i < $len; ++$i, ++$j) {
            if ($j >= $keyLen) {
                $j = 0;
            }
            $str .= chr((ord($data[$i]) + ord($keyHash[$j])) % 256);
        }
        return $str;
    }

    /** Removes permuted noise from the IV + encrypted data, reversing
     * @param $data
     * @param $key
     * @return string
     */
    function _remove_cipher_noise($data, $key)
    {
        $keyHash = $this->hash($key);
        $keyLen = strlen($keyHash);
        $str = '';
        for ($i = 0, $j = 0, $len = strlen($data); $i < $len; ++$i, ++$j) {
            if ($j >= $keyLen) {
                $j = 0;
            }
            $temp = ord($data[$i]) - ord($keyHash[$j]);
            if ($temp < 0) {
                $temp = $temp + 256;
            }
            $str .= chr($temp);
        }
        return $str;
    }

    /** Hash encode a string
     * @access    public
     * @param    string
     * @return    string
     */
    function hash($str)
    {
        return ($this->_hash_type == 'sha1') ? $this->sha1($str) : md5($str);
    }

    /** Generate an SHA1 Hash
     * @access    public
     * @param    string
     * @return    string
     */
    function sha1($str)
    {
        if (!function_exists('sha1')) {
            if (!function_exists('mhash')) {
                $SH = new Sha1();
                return $SH->generate($str);
            } else {
                return bin2hex(mhash(MHASH_SHA1, $str));
            }
        } else {
            return sha1($str);
        }
    }

    /** key 加密类
     * @param array $data
     * @return string
     */
    public static function encryption(array $data)
    {
        $key = md5($data['names'] . serialize($data['time']) . serialize($data['args']));
        return $key;
    }

    /** 生成编号
     * @param int $code
     * @return string
     */
    public static function number($code = 0)
    {
        $yCode = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
        if (empty($code)) {
            $prefix = $yCode[intval(date('Y')) - 2016];
        } else {
            $prefix = $code;
        }
        //$sn = $prefix . strtoupper(dechex(date('m'))) . date('d') . substr(time(),-5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
        $sn = $prefix . substr(date('Y'), 2,
                strlen(date('Y'))) . strtoupper(dechex(date('m'))) . date('d') . substr(floor(microtime(true) * 1000),
                -9) . sprintf('%02d', rand(0, 99));
        return $sn;
    }

    /** 通过仓库ID和skuID生产唯一key
     * @param $warehouseId
     * @param $skuId
     * @return string
     */
    public static function keyByWarehouseIdSkuId($warehouseId, $skuId)
    {
        return "$warehouseId|$skuId";
    }

    /**
     * 生成证书
     * @param string $certificate
     * @param string $secret
     */
    public function certs($certificate = 'rondaful', $secret = 'rondaful')
    {
        $certificate_filename = self::path . $certificate . '.cer';
        $secret_filename = self::path . $secret . '.pfx';
        if (file_exists($certificate_filename) && file_exists($secret_filename)) {
            return;
        }
        //创建公钥和私钥
        $res = openssl_pkey_new(array('private_key_bits' => 512)); #此处512必须不能包含引号。
        //提取私钥
        openssl_pkey_export($res, $private_key);
        //生成公钥
        $public_key = openssl_pkey_get_details($res);
        $public_key = $public_key["key"];
        //生成证书文件
        $fp = fopen($certificate_filename, "w");
        fwrite($fp, $public_key);
        fclose($fp);
        //生成密钥文件
        $fp = fopen($secret_filename, "w");
        fwrite($fp, $private_key);
        fclose($fp);
    }

    /**
     * 生成证书
     * @param string $certificate
     * @param string $secret
     */
    public function certsByPassword($certificate = 'rondaful', $secret = 'rondaful')
    {
        $certificate_filename = self::path . $certificate . '.cer';  //生成证书路径
        $secret_filename = self::path . $secret . '.pfx';  //密钥文件路径
        if (file_exists($certificate_filename) && file_exists($secret_filename)) {
            return;
        }
        //设置加密信息
        $dn = [];
        $number_of_days = 365;     //有效时长
        //生成证书
        $private_key = openssl_pkey_new();
        $csr = openssl_csr_new($dn, $private_key);
        $secret = openssl_csr_sign($csr, null, $private_key, $number_of_days);
        openssl_x509_export($secret, $certificate_key); //导出证书
        openssl_pkcs12_export($secret, $secret_key, $private_key, $this->private_key_password); //导出密钥
        //生成证书文件
        $fp = fopen($certificate_filename, "w");
        fwrite($fp, $certificate_key);
        fclose($fp);
        //生成密钥文件
        $fp = fopen($secret_filename, "w");
        fwrite($fp, $secret_key);
        fclose($fp);
    }

    /**
     * 通过证书加密
     * @param $data
     * @param bool|false $is_password
     * @param string $type
     * @param string $certificate
     * @param string $secret
     * @return string
     */
    public function encryptByCerts($data, $is_password = false, $type = 'public', $certificate = 'rondaful', $secret = 'rondaful')
    {
        $encrypted = '';
        if ($is_password) {
            $this->certsByPassword($certificate, $secret);
            switch ($type) {
                case 'public':
                    $secret_filename = self::path . $secret . '.pfx';
                    $certificate = file_get_contents($secret_filename); //获取公钥
                    //公钥加密
                    openssl_pkcs12_read($certificate, $certs, $this->private_key_password); //读取公钥
                    $public_key = $certs['cert']; //公钥
                    //公钥加密后的数据
                    openssl_public_encrypt($data, $encrypted, $public_key);
                    //openssl_private_decrypt(base64_decode($encrypted), $decrypted, $private_key);//私钥解密
                    break;
                case 'private':
                    $secret_filename = self::path . $secret . '.pfx';
                    $secret = file_get_contents($secret_filename); //获取密钥文件内容
                    //私钥加密
                    openssl_pkcs12_read($secret, $certs, $this->private_key_password); //读取私钥
                    $private_key = $certs['pkey']; //私钥
                    //私钥加密后的数据
                    openssl_private_encrypt($data, $encrypted, $private_key);
                    //openssl_public_decrypt(base64_decode($encrypted), $decrypted, $private_key);//公钥解密
                    break;
            }
        } else {
            $this->certs($certificate, $secret);
            switch ($type) {
                case 'public':
                    $certificate_filename = self::path . $certificate . '.cer';
                    $public_key = file_get_contents($certificate_filename); //获取公钥
                    //公钥加密后的数据
                    openssl_public_encrypt($data, $encrypted, $public_key);
                    //openssl_private_decrypt(base64_decode($encrypted), $decrypted, $private_key);//私钥解密
                    break;
                case 'private':
                    $secret_filename = self::path . $secret . '.pfx';
                    $private_key = file_get_contents($secret_filename); //获取密钥文件内容
                    //私钥加密后的数据
                    openssl_private_encrypt($data, $encrypted, $private_key);
                    //openssl_public_decrypt(base64_decode($encrypted), $decrypted, $private_key);//公钥解密
                    break;
            }
        }
        //加密后的内容通常含有特殊字符，需要base64编码转换下
        $encrypted = base64_encode($encrypted);
        return $encrypted;
    }

    /**
     * 加密
     * @param $data
     * @return string
     */
    public function encrypt($data)
    {
        $key = md5($this->encrypt_key);
        $x = 0;
        $len = strlen($data);
        $keyLength = strlen($key);
        $char = '';
        $str = '';
        for ($i = 0; $i < $len; $i++) {
            if ($x == $keyLength) {
                $x = 0;
            }
            $char .= $key[$x];
            $x++;
        }
        for ($i = 0; $i < $len; $i++) {
            $str .= chr(ord($data[$i]) + (ord($char[$i])) % 256);
        }
        return base64_encode($str);
    }

    /**
     * 解密
     * @param $data
     * @return string
     */
    public function decrypt($data)
    {
        $key = md5($this->encrypt_key);
        $x = 0;
        $data = base64_decode($data);
        $len = strlen($data);
        $keyLength = strlen($key);
        $char = '';
        $str = '';
        for ($i = 0; $i < $len; $i++) {
            if ($x == $keyLength) {
                $x = 0;
            }
            $char .= substr($key, $x, 1);
            $x++;
        }
        for ($i = 0; $i < $len; $i++) {
            if (ord(substr($data, $i, 1)) < ord(substr($char, $i, 1))) {
                $str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
            } else {
                $str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));
            }
        }
        return $str;
    }

    /**
     * 创建包裹号
     * @param $time
     * @return string
     * @throws Exception
     */
    public function createNumber2($time)
    {
        $timestamp = $this->timeGen($time);
        $lastTimestamp = self::$lastTimestamp;
        //判断时钟是否正常
        if ($timestamp < $lastTimestamp) {
            throw new Exception("Clock moved backwards.  Refusing to generate id for %d milliseconds" . ($lastTimestamp - $timestamp));
        }
        //生成唯一序列
        if ($lastTimestamp == $timestamp) {
            if (self::$sequence == 0 || self::$sequence == 99) {
                $timestamp = $this->tilNextMillis($lastTimestamp);
                self::$sequence = 1;
            } else {
                self::$sequence++;
            }
        } else {
            self::$sequence = 1;
        }
        self::$lastTimestamp = $timestamp;
        $sequence = $this->fillSeats(self::$sequence);
        $number = $time . '' . $sequence;
        return intval($number);
    }

    /**
     * 创建包裹号(100并发处理)
     * @param $time
     * @return string
     * @throws Exception
     */
    public function createNumberOld($time)
    {
        $redis = Cache::handler();
        $timestamp = $this->timeGen($time);
        $key = 'cache:createNumber' . $timestamp;
        if (!$redis->exists($key)) {
            $redis->set($key, 0, 60);
        }
        $lastKey = 'cache:createNumberLastTimestamp';
        if (!$redis->exists($lastKey)) {
            $redis->set($lastKey, self::$lastTimestamp, 60);
        }
        //判断时钟是否正常
        if ($timestamp < $redis->get($lastKey)) {
            throw new Exception("Clock moved backwards.  Refusing to generate id for %d milliseconds" . ($redis->get($lastKey) - $timestamp));
        }
        //生成唯一序列
        if ($redis->get($lastKey) == $timestamp) {
            if ($redis->get($key) == 0 || $redis->get($key) == 99) {
                $redis->set($key, 1, 60);
                $timestamp = $this->tilNextMillis($redis->get($lastKey));
            } else {
                $redis->incr($key);
            }
        } else {
            $redis->set($key, 1, 60);
        }
        $redis->set($lastKey, $timestamp, 60);
        $sequence = $this->fillSeats($redis->get($key));
        $number = $time . '' . $sequence;
        return intval($number);
    }

    /**
     * 创建包裹号
     * @param $time
     * @return string
     */
    public function createNumber($time)
    {
        $redis = Cache::handler();
        $key = 'pool:number';
        $number = $redis->rPop($key);
        if($number){
            return $number;
        }else{
            $ok = $redis->setnx('pool:create',1);
            if($ok){
                for ($i = 1; $i < 100; $i++) {
                    $sequence = $this->fillSeats($i);
                    $number = time() . '' . $sequence;
                    $redis->lPush($key, $number);
                }
                $redis->del('pool:create');
            }
            return $redis->rPop($key);
        }
    }

    /** 获取当前时间秒
     * @param int $time
     * @return float
     */
    private function timeGen($time = 0)
    {
        if (empty($time)) {
            $time = time();
            return $time;
        }
        return $time;
    }

    /** 取下一秒
     * @param $lastTimestamp
     * @return float
     */
    private function tilNextMillis($lastTimestamp)
    {
        $timestamp = $this->timeGen();
        while ($timestamp <= $lastTimestamp) {
            $timestamp = $this->timeGen();
        }
        return $timestamp;
    }

    /**
     * 补位
     * @param $data
     * @return string
     */
    private function fillSeats($data)
    {
        if ($data < 10) {
            $data = '0' . $data;
        }
        return $data;
    }

    /**
     * 生成简单的密码
     * @param int $length
     * @return string
     */
    public function createPassword($length = 8)
    {
        $key = 'Server';
        $str = substr(md5(time()), 0, $length);
        return $key . $str;
    }

    /**
     * @desc des加密
     * @param  string $input
     * @param  string $key
     * @param  string $method
     * @param  string $iv 偏移量
     * @return string
     */
    public static function desEncrypt($input, $key, $method = 'DES-ECB', $iv = null)
    {
        $iv = $iv ? $iv : self::createIv();
        return base64_encode(openssl_encrypt($input, $method, $key, OPENSSL_RAW_DATA, $method == 'DES-ECB' ? '' : $iv));
    }

    /**
     * @desc des解密
     * @param  string $input
     * @param  string $key
     * @param  string $method
     * @param  string $iv
     * @return string
     */
    public static function desDecrypt($input, $key, $method = 'DES-ECB', $iv = null)
    {
        $iv = $iv ? $iv : self::createIv();
        return openssl_decrypt(base64_decode($input), $method, $key, OPENSSL_RAW_DATA, $method == 'DES-ECB' ? '' : $iv);
    }

    /**
     * 相当于java里的 byte[] iv = { 0, 0, 0, 0, 0,0, 0, 0 }
     * 也相当于C#里的
     * IV = new byte[8];
     * @return string
     */
    public static function createIv()
    {
        return self::hexToStr("0000000000000000");
    }

    public static function hexToStr($hex)
    {
        $string = '';
        for ($i = 0; $i < strlen($hex) - 1; $i += 2) {
            $string .= chr(hexdec($hex[$i] . $hex[$i + 1]));
        }
        return $string;
    }
}