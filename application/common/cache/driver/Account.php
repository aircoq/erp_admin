<?php

namespace app\common\cache\driver;


use app\common\model\MerchantDistributionAccount;
use app\common\service\ChannelAccountConst;
use app\common\service\DataToObjArr;
use think\Db;
use think\Loader;
use app\common\cache\Cache;
use app\common\model\wish\WishAccount;
use app\common\model\aliexpress\AliexpressAccount;
use app\common\model\paypal\PaypalAccount;
use think\Model;
use app\common\model\ebay\EbayAccount as EbayAccountModel;
use app\common\model\amazon\AmazonAccount as AmazonAccountModel;
use app\common\model\lazada\LazadaAccount as LazadaAccountModel;

/**
 *  所有账号表的信息
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2016/10/29
 * Time: 14:14
 */
class Account extends Cache
{
    const CacheEbayAccount = "cache:ebayAccount";

    /** ebay
     * @param int $id
     * @return array|mixed
     */
    public function ebayAccount($id = null)
    {
        //缓存不是最新数据时，清了一下Redis;
        //$this->redis->del('cache:ebayAccount');
        if ($this->redis->exists('cache:ebayAccount')) {
            if (!empty($id)) {
                $result = json_decode($this->redis->get('cache:ebayAccount'), true);
                return isset($result[$id]) ? $result[$id] : [];
            }
            return json_decode($this->redis->get('cache:ebayAccount'), true);
        }

        //$account_list = Loader::model('ebay.EbayAccount')->order('id asc')->select();
        $account_list = EbayAccountModel::field('*')->order('id asc')->select();
        $new_list = [];
        foreach ($account_list as $v) {
            $new_list[$v['id']] = $v->toArray();
        }
        $this->redis->set('cache:ebayAccount', json_encode($new_list));
        if (!empty($id)) {
            return isset($new_list[$id]) ? $new_list[$id] : [];
        }
        return $new_list;
    }

    /**
     *
     * @param array $account
     */
    public function updateEbayAccount($account = [])
    {
        $this->redis->set('cache:ebayAccount', json_encode($account));
    }


    /** amazon账号信息
     * @param int $id
     * @return array|mixed
     */
    public function amazonAccount($id = 0)
    {
        if ($this->redis->exists('cache:amazonAccount')) {
            if (!empty($id)) {
                $result = json_decode($this->redis->get('cache:amazonAccount'), true);
                return isset($result[$id]) ? $result[$id] : [];
            }
            return json_decode($this->redis->get('cache:amazonAccount'), true);
        }
        $account_list = (new AmazonAccountModel())->field(true)->order('id asc')->select();
        $new_list = [];
        foreach ($account_list as $v) {
            $new_list[$v['id']] = $v;
        }
        $this->redis->set('cache:amazonAccount', json_encode($new_list));
        if (!empty($id)) {
            return isset($new_list[$id]) ? $new_list[$id] : [];
        }
        return $new_list;
    }

    /**
     *  更新账号信息
     * @param array $account
     */
    public function updateAmazonAccount($account = [])
    {
        return $this->redis->set('cache:amazonAccount', json_encode($account));
    }

    /** paypal账号信息
     * @param int $id
     * @return array|mixed
     */
    public function paypalAccount($id = 0)
    {
        if ($this->redis->exists('cache:paypalAccount')) {
            if (!empty($id)) {
                $result = json_decode($this->redis->get('cache:paypalAccount'), true);
                return isset($result[$id]) ? $result[$id] : [];
            }
            return json_decode($this->redis->get('cache:paypalAccount'), true);
        }
        $account_list = PaypalAccount::field(true)->order('id asc')->select();
        $new_list = [];
        foreach ($account_list as $v) {
            $new_list[$v['id']] = $v;
        }
        $this->redis->set('cache:paypalAccount', json_encode($new_list));
        if (!empty($id)) {
            return isset($new_list[$id]) ? $new_list[$id] : [];
        }
        return $new_list;
    }

    /**
     *  更新账号信息
     * @param array $account
     */
    public function updatePaypalAccount($account = [])
    {
        $this->redis->set('cache:paypalAccount', json_encode($account));
    }

    /** aliexpress账号信息
     * @param int $id
     * @return array|mixed
     */
    public function aliexpressAccount($id = 0)
    {
        if ($id) {
            if ($this->redis->hexists('hash:AliexpressAccount', $id)) {
                $result = json_decode($this->redis->hGet('hash:AliexpressAccount', $id), true);
                return !empty($result) ? $result : [];
            }
            $account = AliexpressAccount::get($id);
            $this->redis->hSet('hash:AliexpressAccount', $id, json_encode($account));
            return !empty($account) ? $account : [];
        } else {
            if ($this->redis->exists('hash:AliexpressAccount')) {
                $allAccount = $this->redis->hGetAll('hash:AliexpressAccount');
                foreach ($allAccount as &$account) {
                    $account = json_decode($account, true);
                }
                return !empty($allAccount) ? $allAccount : [];
            }
            $aliexpressModel = new AliexpressAccount();
            $allAccount = $aliexpressModel->order('id asc')->select();
            $accounts = [];
            if (!empty($allAccount)) {
                foreach ($allAccount as $account) {
                    $accounts[$account['id']] = $account;
                    $this->redis->hSet('hash:AliexpressAccount', $account['id'], json_encode($account));
                }
            }
            return $accounts;
        }
    }
    /** lazada账号信息
     * @param int $id
     * @return array|mixed
     */
    public function lazadaAccount($id = 0)
    {
        if ($this->redis->exists('cache:lazadaAccount')) {
            if (!empty($id)) {
                $result = json_decode($this->redis->get('cache:lazadaAccount'), true);
                return isset($result[$id]) ? $result[$id] : [];
            }
            return json_decode($this->redis->get('cache:lazadaAccount'), true);
        }
        $account_list =  (new LazadaAccountModel())->field(true)->order('id asc')->select();
        $new_list = [];
        foreach ($account_list as $v) {
            $new_list[$v['id']] = $v;
        }
        $this->redis->set('cache:lazadaAccount', json_encode($new_list));
        if (!empty($id)) {
            return isset($new_list[$id]) ? $new_list[$id] : [];
        }
        return $new_list;
    }

    /**
     *  更新账号信息
     * @param array $account
     */
    public function updateLzadaAccount($account = [])
    {
        $this->redis->set('cache:lazadaAccount', json_encode($account));
    }
    /**
     *  更新账号信息
     * @param array $account
     */
    public function updateAliexpressAccount($account = [])
    {
        if (isset($account['id']) && $account['id']) {
            $this->redis->hSet('hash:AliexpressAccount', $account['id'], json_encode($account));
        }
    }

    /**
     * 速卖通账号上次更新时间
     * @param $id
     * @param array $data
     *
     * @return array|bool|mixed|object
     */
    public function aliexpressLastUpdateTime($id, $data = [])
    {
        $key = 'hash:aliexpressAccountTime';
        $result = json_decode($this->redis->hget($key, $id), true);
        if ($data) {
            foreach ($data as $field => $value) {
                $result[$field] = $value;
            }
            $this->redis->hset($key, $id, json_encode($result));
            return true;
        }

        return $result;
    }

    /**
     * 获取全部的账号信息
     * @param bool $bool
     * @return array
     * @throws \think\Exception
     */
    public function getAccounts($bool = false)
    {
        $channel = Cache::store('channel')->getChannel();
        $result = [
            'wish' => Cache::filter(Cache::store('wishAccount')->getAccount(), [], 'id,code,account_name,is_invalid,is_authorization'),
            'ebay' => Cache::filter(Cache::store('EbayAccount')->getAllAccounts(), [], 'id,code,account_name,is_invalid,site_id,token_valid_status'),
            'amazon' => Cache::filter(Cache::store('AmazonAccount')->getAccount(), [], 'id,code,account_name,is_invalid,site,status,is_authorization'),
            'aliExpress' => Cache::filter(Cache::store('AliexpressAccount')->getAccounts(), [], 'id,code,account_name,is_invalid'),
            'joom' => Cache::filter(Cache::store('JoomAccount')->getAllAccounts(), [], 'id,code,account_name,is_invalid'),
            'shopee'=>Cache::filter(Cache::store('ShopeeAccount')->getAllCount(), [], 'id,code,name,status,platform_status'),
            'paytm' => Cache::filter(Cache::store('PaytmAccount')->getAllAccounts(), [], 'id,code,account_name,is_invalid'),
            'pandao' => Cache::filter(Cache::store('PandaoAccountCache')->getAllAccounts(), [], 'id,code,account_name,is_invalid'),
            'walmart' => Cache::filter(Cache::store('WalmartAccount')->getAllAccounts(), [], 'id,code,account_name,is_invalid'),
            'jumia' => Cache::filter(Cache::store('JumiaAccount')->getAllAccounts(), [], 'id,code,account_name,is_invalid'),
            'cd' => Cache::filter(Cache::store('CdAccount')->getAllAccounts(), [], 'id,code,account_name,is_invalid'),
            'vova' => Cache::filter(Cache::store('VovaAccount')->getTableRecord(), [], 'id,code,account_name,is_invalid'),
            'umka' => Cache::filter(Cache::store('UmkaAccount')->getTableRecord(), [], 'id,code,name,is_invalid'),
            'lazada' => Cache::filter(Cache::store('LazadaAccount')->getTableRecord(), [], 'id,code,name,is_invalid'),
            'newegg' => Cache::filter(Cache::store('NeweggAccount')->getAllAccounts(), [], 'id,code,account_name,is_invalid'),
            'oberlo' => Cache::filter(Cache::store('OberloAccount')->getTableRecord(), [], 'id,code,name,is_invalid'),
            'shoppo' => Cache::filter(Cache::store('ShoppoAccount')->getTableRecord(), [], 'id,code,name,is_invalid'),
            'pdd' => Cache::filter(Cache::store('PddAccount')->getTableRecord(), [], 'id,code,name,is_invalid'),
            'fummart' => Cache::filter(Cache::store('FummartAccount')->getTableRecord(), [], 'id,code,account_name,is_invalid'),
            'souq' => Cache::filter(Cache::store('SouqAccount')->getTableRecord(), [], 'id,code,account_name,is_invalid'),
            'noon' => Cache::filter(Cache::store('NoonAccount')->getAllAccounts(), [], 'id,code,account_name,status'),
        ];
        if ($bool) {
            return $result;
        }
        $new_list = [];
        foreach ($channel as $v) {
            foreach ($result as $key => $value) {
                if ($v['name'] == $key) {
                    $new_list[$v['id']] = $value;
                }
            }
        }
        return $new_list;
    }

    /**
     * 获取全部的账号信息--通过数据库
     * @param bool $bool
     * @return array
     * @throws \think\Exception
     */
    public function getAccountsByBase($bool = false,$channelFilter = [])
    {
        $channel = Cache::store('channel')->getChannel();
        $result = [
            'wish' => !empty($channelFilter) && in_array(ChannelAccountConst::channel_wish,$channelFilter) ? $this->getAccountByChannel(ChannelAccountConst::channel_wish) : [],
            'ebay' => !empty($channelFilter) && in_array(ChannelAccountConst::channel_ebay,$channelFilter) ? $this->getAccountByChannel(ChannelAccountConst::channel_ebay) : [],
            'amazon' => !empty($channelFilter) && in_array(ChannelAccountConst::channel_amazon,$channelFilter) ? $this->getAccountByChannel(ChannelAccountConst::channel_amazon) : [],
            'aliExpress' => !empty($channelFilter) && in_array(ChannelAccountConst::channel_aliExpress,$channelFilter) ? $this->getAccountByChannel(ChannelAccountConst::channel_aliExpress) :[],
            'joom' => !empty($channelFilter) && in_array(ChannelAccountConst::channel_Joom,$channelFilter) ? $this->getAccountByChannel(ChannelAccountConst::channel_Joom) : [],
            'shopee'=> !empty($channelFilter) && in_array(ChannelAccountConst::channel_Shopee,$channelFilter) ? $this->getAccountByChannel(ChannelAccountConst::channel_Shopee) : [],
            'paytm' => !empty($channelFilter) && in_array(ChannelAccountConst::channel_Paytm,$channelFilter) ? $this->getAccountByChannel(ChannelAccountConst::channel_Paytm) : [],
            'pandao' => !empty($channelFilter) && in_array(ChannelAccountConst::channel_Pandao,$channelFilter) ? $this->getAccountByChannel(ChannelAccountConst::channel_Pandao) : [],
            'walmart' => !empty($channelFilter) && in_array(ChannelAccountConst::channel_Walmart,$channelFilter) ? $this->getAccountByChannel(ChannelAccountConst::channel_Walmart) : [],
            'jumia' => !empty($channelFilter) && in_array(ChannelAccountConst::Channel_Jumia,$channelFilter) ? $this->getAccountByChannel(ChannelAccountConst::Channel_Jumia) : [],
            'cd' => !empty($channelFilter) && in_array(ChannelAccountConst::channel_CD,$channelFilter) ? $this->getAccountByChannel(ChannelAccountConst::channel_CD) : [],
            'vova' => !empty($channelFilter) && in_array(ChannelAccountConst::channel_Vova,$channelFilter) ? $this->getAccountByChannel(ChannelAccountConst::channel_Vova) : [],
            'umka' => !empty($channelFilter) && in_array(ChannelAccountConst::Channel_umka,$channelFilter) ? $this->getAccountByChannel(ChannelAccountConst::Channel_umka) : [],
            'lazada' => !empty($channelFilter) && in_array(ChannelAccountConst::channel_Lazada,$channelFilter) ? $this->getAccountByChannel(ChannelAccountConst::channel_Lazada) : [],
            'newegg' => !empty($channelFilter) && in_array(ChannelAccountConst::channel_Newegg,$channelFilter) ? $this->getAccountByChannel(ChannelAccountConst::channel_Newegg) : [],
            'oberlo' => !empty($channelFilter) && in_array(ChannelAccountConst::channel_Oberlo,$channelFilter) ? $this->getAccountByChannel(ChannelAccountConst::channel_Oberlo) : [],
            'shoppo' => !empty($channelFilter) && in_array(ChannelAccountConst::channel_Shoppo,$channelFilter) ? $this->getAccountByChannel(ChannelAccountConst::channel_Shoppo) : [],
            'pdd' => !empty($channelFilter) && in_array(ChannelAccountConst::channel_Pdd,$channelFilter) ? $this->getAccountByChannel(ChannelAccountConst::channel_Pdd) : [],
            'fummart' => !empty($channelFilter) && in_array(ChannelAccountConst::channel_Fummart,$channelFilter) ? $this->getAccountByChannel(ChannelAccountConst::channel_Fummart) : [],
            'souq' => !empty($channelFilter) && in_array(ChannelAccountConst::channel_Souq,$channelFilter) ? $this->getAccountByChannel(ChannelAccountConst::channel_Souq) : [],
            'merchant' => !empty($channelFilter) && in_array(ChannelAccountConst::channel_Merchant_Distribution,$channelFilter) ? $this->getAccountByChannel(ChannelAccountConst::channel_Merchant_Distribution) : [],
            'noon' => !empty($channelFilter) && in_array(ChannelAccountConst::channel_Noon,$channelFilter) ? $this->getAccountByChannel(ChannelAccountConst::channel_Noon) : [],
        ];
        
        if ($bool) {
            return $result;
        }
        $new_list = [];
        foreach ($channel as $v) {
            foreach ($result as $key => $value) {
                if ($v['name'] == $key) {
                    $new_list[$v['id']] = $value;
                }
            }
        }
        return $new_list;
    }

    private function getAccountWhere(&$mode, $page = 0, $pageSize = 0,$where = [])
    {
        if($page > 0 && $pageSize > 0){
            $mode->page($page,$pageSize);
        }
        if($where){
            $mode->where($where);
        }
    }

    /**
     * 通过渠道查找账号信息
     * @param $channel_id
     * @param $page
     * @param $pageSize
     * @param $code
     * @return array
     * @throws \think\Exception
     */
    public function getAccountByChannel($channel_id, $page = 0, $pageSize = 0,$code = '',$site = 0)
    {
        $result = [];
        $filter = [];
        $getWhere = [];
        $table = '';
        $field = '';
        if($site){
            $getWhere['site'] = $site;
        }
        if($code){
            $getWhere['code'] = [ 'like', '%' . $code . '%'];
        }
        switch ($channel_id) {
            case ChannelAccountConst::channel_wish:
                $filter['is_invalid'] = ['eq', 1];
                $filter['is_authorization'] = ['eq', 1];
                $table = 'wish_account';
                $field = 'id,code,account_name,is_invalid,is_authorization,base_account_id';
                break;
            case ChannelAccountConst::channel_ebay:
                $filter['is_invalid'] = ['eq', 1];
                $filter['token_valid_status'] = ['eq', 1];
                $table = 'ebay_account';
                $field = 'id,code,account_name,site_id,is_invalid,account_status,base_account_id';
                if($site){
                    unset($getWhere['site']);
                    $getWhere['site_id'] = [ 'like', '%' . $site . '%'];
                }
                break;
            case ChannelAccountConst::channel_amazon:
                $filter['status'] = ['eq', 1];
                $filter['is_authorization'] = ['eq', 1];
                $table = 'amazon_account';
                $field = 'id,code,account_name,is_invalid,site,status,is_authorization,base_account_id';
                break;
            case ChannelAccountConst::channel_aliExpress:
                $filter['is_invalid'] = ['eq', 1];
                $table = 'aliexpress_account';
                $field = 'id,code,account_name,is_invalid,base_account_id';
                break;
            case ChannelAccountConst::channel_Joom:
                $filter['is_invalid'] = ['eq', 1];
                $filter['platform_status'] = ['eq', 1];
                $table = 'joom_account';
                $field = 'id,code,account_name,is_invalid,base_account_id';
                break;
            case ChannelAccountConst::channel_Joom_Shop:
                $filter['is_invalid'] = ['eq', 1];
                $table = 'joom_shop';
                $field = 'id,code,shop_name,is_invalid,base_account_id';
                break;
            case ChannelAccountConst::channel_Shopee:
//                $filter['status'] = ['eq', 1];
                $filter['platform_status'] = ['eq', 1];
                $table = 'shopee_account';
                $field = 'id,code,name,status,site,base_account_id';
                break;
            case ChannelAccountConst::channel_Lazada:
                $filter['status'] = ['eq', 1];
                $table = 'lazada_account';
                $field = 'id,code,name,platform_status,site,base_account_id';
                break;
            case ChannelAccountConst::channel_Pandao:
                $filter['is_invalid'] = ['eq', 1];
                $table = 'pandao_account';
                $field = 'id,code,account_name,is_invalid,base_account_id';
                break;
            case ChannelAccountConst::channel_Paytm:
                $filter['is_invalid'] = ['eq', 1];
                $table = 'paytm_account';
                $field = 'id,code,account_name,is_invalid,base_account_id';
                break;
            case ChannelAccountConst::channel_Walmart:
                $filter['is_invalid'] = ['eq', 1];
                $table = 'walmart_account';
                $field = 'id,code,account_name,is_invalid,site,base_account_id';
                break;
            case ChannelAccountConst::channel_Vova:
                $filter['is_invalid'] = ['eq', 1];
                $table = 'vova_account';
                $field = 'id,code,account_name,is_invalid,base_account_id';
                break;
            case ChannelAccountConst::Channel_Jumia:
                $filter['status'] = ['eq', 1];
                $table = 'jumia_account';
                $field = 'id,code,account_name,status,site,base_account_id';
                break;
            case ChannelAccountConst::Channel_umka:
                $filter['is_invalid'] = ['eq', 1];
                $table = 'umka_account';
                $field = 'id,code,name,is_invalid,base_account_id';
                break;
            case ChannelAccountConst::channel_CD:
                $filter['is_invalid'] = ['eq', 1];
                $filter['status'] = ['eq', 1];
                $table = 'cd_account';
                $field = 'id,code,account_name,status,base_account_id';
                break;
            case ChannelAccountConst::channel_Newegg:
                $filter['is_invalid'] = ['eq', 1];
                $table = 'newegg_account';
                $field = 'id,code,account_name,is_invalid,base_account_id';
                break;
            case ChannelAccountConst::channel_Oberlo:
                $filter['status'] = ['eq', 1];
                $table = 'oberlo_account';
                $field = 'id,code,name,status,base_account_id';
                break;
            case ChannelAccountConst::channel_Shoppo:
                $filter['status'] = ['eq', 1];
                $table = 'shoppo_account';
                $field = 'id,code,name,status,base_account_id';
                break;
            case ChannelAccountConst::channel_Pdd:
                $filter['is_invalid'] = ['eq', 1];
                $table = 'pdd_account';
                $field = 'id,code,name,status,base_account_id';
                break;
            case ChannelAccountConst::channel_Zoodmall:
                $filter['is_invalid'] = ['eq', 1];
                $table = 'zoodmall_account';
                $field = 'id,code,account_name,is_invalid,base_account_id';
                break;
            case ChannelAccountConst::channel_Yandex:
                $filter['is_invalid'] = ['eq', 1];
                $table = 'yandex_account';
                $field = 'id,code,account_name,is_invalid,base_account_id';
                break;
            case ChannelAccountConst::channel_Fummart:
                $filter['is_invalid'] = ['eq', 1];
                $table = 'fummart_account';
                $field = 'id,code,account_name,is_invalid,base_account_id';
                break;
            case ChannelAccountConst::channel_Souq:
                $filter['is_invalid'] = ['eq', 1];
                $table = 'souq_account';
                $field = 'id,code,account_name,is_invalid';
                break;
            case ChannelAccountConst::channel_Merchant_Distribution:
                $filter['is_invalid'] = ['eq', 1];
                $table = 'merchant_distribution_account';
                $field = 'id,code,account_name,is_invalid';
                break;
            case ChannelAccountConst::channel_Daraz:
                $filter['status'] = ['eq', 1];
                $table = 'daraz_account';
                $field = 'id,code,name,platform_status,site,base_account_id';
                break;
            case ChannelAccountConst::channel_PM:
                $filter['status'] = ['eq', 1];
                $table = 'pm_account';
                $field = 'id,code,name,platform_status,base_account_id';
                break;
            case ChannelAccountConst::channel_Noon:
                $filter['status'] = ['eq', 1];
                $table = 'noon_account';
                $field = 'id,code,account_name,site,base_account_id';
                break;
        }
        if($table){
            $dataList = Db::table($table)->where($filter)->field($field);
            $this->getAccountWhere($dataList, $page, $pageSize,$getWhere);
            $dataList = $dataList->select();
            foreach ($dataList as $k => $value){
                $result[$value['id']] = $value;
            }
            if($page > 0){
                return [
                    'data' => $result,
                    'count' => Db::table($table)->where($filter)->where($getWhere)->count(),
                ];
            }
        }
        return $result;
    }

    private function checkWhere(&$where,$old,$new,$value = '')
    {
        $where[$new] = $value ? $value : $where[$old];
        unset($where[$old]);
    }

    /**
     * amazon账号最后下载订单的时间
     * @param array $time
     * @return mixed
     */
    public function amazonLastUpdateTime($id, $time = [])
    {
        $key = 'hash:amazonAccountTime';
        $result = json_decode($this->redis->hget($key, $id), true);
        if ($time) {
            foreach ($time as $field => $value) {
                $result[$field] = $value;
            }
            $this->redis->hset($key, $id, json_encode($result));
            return true;
        }

        return $result;
    }


    /**
     * paypal账号订单同步最后更新的时间
     * @param array $time
     * @return mixed
     */
    public function paypalLastUpdateTime($account_id, $time = [])
    {
        $key = 'hash:paypalAccountTime';
        $result = json_decode($this->redis->hget($key, $account_id), true);
        if ($time) {
            foreach ($time as $field => $value) {
                $result[$field] = $value;
            }
            $this->redis->hset($key, $account_id, json_encode($result));
            return true;
        }

        return $result;
    }

    /**
     * amazon 站点数据
     * @param string $site 站点
     */
    public function amazonSite($site = null)
    {
        $result = [
            'US' => [
                'site' => 'US',
                'region' => 'NA',
                'name' => '美国站点',
                'endpoint' => 'https://mws.amazonservices.com',
                'marketpalceId' => 'ATVPDKIKX0DER'
            ],
            'CA' => [
                'site' => 'CA',
                'region' => 'NA',
                'name' => '加拿大站点',
                'endpoint' => 'https://mws.amazonservices.com',
                'marketpalceId' => 'A2EUQ1WTGCTBG2'
            ],
            'MX' => [
                'site' => 'MX',
                'region' => 'NA',
                'name' => '墨西哥站点',
                'endpoint' => 'https://mws.amazonservices.com',
                'marketpalceId' => 'A1AM78C64UM0Y8'
            ],
            'DE' => [
                'site' => 'DE',
                'region' => 'EU',
                'name' => '德国站点',
                'endpoint' => 'https://mws-eu.amazonservices.com',
                'marketpalceId' => 'A1PA6795UKMFR9'
            ],
            'ES' => [
                'site' => 'ES',
                'region' => 'EU',
                'name' => '西班牙站点',
                'endpoint' => 'https://mws-eu.amazonservices.com',
                'marketpalceId' => 'A1RKKUPIHCS9HS'
            ],
            'FR' => [
                'site' => 'FR',
                'region' => 'EU',
                'name' => '法国站点',
                'endpoint' => 'https://mws-eu.amazonservices.com',
                'marketpalceId' => 'A13V1IB3VIYZZH'
            ],
            'IT' => [
                'site' => 'IT',
                'region' => 'EU',
                'name' => '意大利站点',
                'endpoint' => 'https://mws-eu.amazonservices.com',
                'marketpalceId' => 'APJ6JRA9NG5V4'
            ],
            'UK' => [
                'site' => 'UK',
                'region' => 'EU',
                'name' => '英国站点',
                'endpoint' => 'https://mws-eu.amazonservices.com',
                'marketpalceId' => 'A1F83G8C2ARO7P'
            ],
            'JP' => [
                'site' => 'JP',
                'region' => 'JP',
                'name' => '日本站点',
                'endpoint' => 'https://mws.amazonservices.jp',
                'marketpalceId' => 'A1VC38T7YXB528'
            ],
            'CN' => [
                'site' => 'CN',
                'region' => 'CN',
                'name' => '中国站点',
                'endpoint' => 'https://https://mws.amazonservices.com.cn',
                'marketpalceId' => 'AAHKV2X7AFYLW'
            ],
            'BR' => [
                'site' => 'BR',
                'region' => 'BR',
                'name' => '巴西站点',
                'endpoint' => 'https://mws.amazonservices.com',
                'marketpalceId' => '	A2Q3Y263D00KWC'
            ],
            'IN' => [
                'site' => 'IN',
                'region' => 'IN',
                'name' => '印度站点',
                'endpoint' => 'https://mws.amazonservices.in',
                'marketpalceId' => 'A21TJRUUN4KGV'
            ],
            'AU' => [
                'site' => 'AU',
                'region' => 'AU',
                'name' => '澳大利亚站点',
                'endpoint' => 'https://mws.amazonservices.com.au',
                'marketpalceId' => 'A39IBJ37TRP1C6'
            ],
        ];

        if (null === $site) {
            return $result;
        } else {
            return isset($result[$site]) ? $result[$site] : [];
        }
    }

    /**
     * lazada 站点数据
     * @param string $site 站点
     */
    public function lazadaSite()
    {
        $result = [
            'ID' => [
                'county_id' => 'ID',
                'name' => '印度尼西亚',
                'originalUrl' => 'https://api.lazada.co.id/rest',
                'endpoint' => 'http://api.lazada.co.id/rest',
            ],
            'PH' => [
                'county_id' => 'PH',
                'name' => '菲律宾',
                'originalUrl' => 'https://api.lazada.com.ph/rest',
                'endpoint' => 'http://api.lazada.com.ph/rest',
            ],
            'VN' => [
                'county_id' => 'VN',
                'name' => '越南',
                'originalUrl' => 'https://api.lazada.vn/rest',
                'endpoint' => 'http://api.lazada.vn/rest',
            ],
            'SG' => [
                'county_id' => 'SG',
                'name' => '新加坡',
                'originalUrl' => 'https://api.lazada.sg/rest',
                'endpoint' => 'http://api.lazada.sg/rest',
            ],
            'MY' => [
                'county_id' => 'MY',
                'name' => '马来西亚',
                'originalUrl' => 'https://api.lazada.com.my/rest',
                'endpoint' => 'http://api.lazada.com.my/rest',
            ],
            'TH' => [
                'county_id' => 'TH',
                'name' => '泰国',
                'originalUrl' => 'https://api.lazada.co.th/rest',
                'endpoint' => 'http://api.lazada.co.th/rest',
            ],

        ];


        return $result;

    }

    /**
     * walmart 站点数据
     * @param string $site 站点
     */
    public function walmartSite($site = null)
    {
        $result = [
            'US' => [
                'site' => 'US',
                'name' => '美国站点',
                'endpoint' => '',
            ],
            'CA' => [
                'site' => 'CA',
                'name' => '加拿大站点',
                'endpoint' => '',
            ],

        ];
        if (null === $site) {
            return $result;
        } else {
            return isset($result[$site]) ? $result[$site] : [];
        }

    }
    
    /**
     * daraz 站点数据
     * @param string $site 站点
     */
    public function darazSite($site = null)
    {
        $result = [
            'PK' => [
                'site' => 'PK',
                'name' => '巴基斯坦',
                'endpoint' => '',
            ],
            'BD' => [
                'site' => 'BD',
                'name' => '孟加拉国',
                'endpoint' => '',
            ],
            'LK' => [
                'site' => 'LK',
                'name' => '斯里兰卡',
                'endpoint' => '',
            ],
            'NP' => [
                'site' => 'NP',
                'name' => '尼泊尔',
                'endpoint' => '',
            ],
            'MM' => [
                'site' => 'MM',
                'name' => '缅甸',
                'endpoint' => '',
            ],
        ];
        if (null === $site) {
            return $result;
        } else {
            return isset($result[$site]) ? $result[$site] : [];
        }
        
    }

    /**
     * noon 站点数据
     * @param string $site 站点
     */
    public function noonSite($site = null)
    {
        $result = [
            'SA' => [
                'site' => 'SA',
                'name' => '沙特',
                'endpoint' => '',
            ],
            'AE' => [
                'site' => 'AE',
                'name' => '阿联酋',
                'endpoint' => '',
            ],
            'EG' => [
                'site' => 'EG',
                'name' => '埃及',
                'endpoint' => '',
            ],
        ];
        if (null === $site) {
            return $result;
        } else {
            return $result[$site] ?? [];
        }
    }

    /**
     * jumia 站点数据
     * @param string $site 站点
     */
    public function jumiaSite($site = null)
    {
        $result = [
            'NG' => [
                'site' => 'NG',
                'name' => '尼日利亚',
                'endpoint' => '',
            ],
            'KE' => [
                'site' => 'KE',
                'name' => '肯尼亚',
                'endpoint' => '',
            ],
            'MA' => [
                'site' => 'MA',
                'name' => '摩洛哥',
                'endpoint' => '',
            ],
            'CI' => [
                'site' => 'CI',
                'name' => '科特迪瓦',
                'endpoint' => '',
            ],
            'EG' => [
                'site' => 'EG',
                'name' => '埃及',
                'endpoint' => '',
            ],
        ];
        if (null === $site) {
            return $result;
        } else {
            return $result[$site] ?? [];
        }
    }

}