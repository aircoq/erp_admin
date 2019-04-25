<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\service\ChannelAccountConst;
use app\goods\service\GoodsBrandsLink;
use \think\Loader;
use think\Db;
use app\common\model\ebay\EbaySite;
use app\common\model\aliexpress\AliexpressAccount;
use app\common\model\amazon\AmazonAccount;
use app\common\model\cd\CdAccount;
use app\common\model\ebay\EbayAccount;
use app\common\model\fummart\FummartAccount;
use app\common\model\joom\JoomAccount;
use app\common\model\jumia\JumiaAccount;
use app\common\model\lazada\LazadaAccount;
use app\common\model\newegg\NeweggAccount;
use app\common\model\oberlo\OberloAccount;
use app\common\model\pandao\PandaoAccount;
use app\common\model\paypal\PaypalAccount;
use app\common\model\paytm\PaytmAccount;
use app\common\model\pdd\PddAccount;
use app\common\model\shoppo\ShoppoAccount;
use app\common\model\souq\SouqAccount;
use app\common\model\daraz\DarazAccount;
use app\common\model\pm\PmAccount;
use app\common\model\noon\NoonAccount;

/** 获取不同平台的分类
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/11/3
 * Time: 11:59
 */
class Channel extends Cache
{
    /** 获取站点
     * @param $channel
     * @param bool|false $cate
     * @return array
     */
    public function getSite($channel,$cate = false)
    {
        if ($this->redis->exists('cache:channelSite')) {
            $site = json_decode($this->redis->get('cache:channelSite'), true);
        } else {
            $ebaySiteModel = new EbaySite();
            $ebayList = $ebaySiteModel->field('siteid as id,name,country as code')->select();
            $amazonList = Cache::store('account')->amazonSite();
            $walmartList = Cache::store('account')->walmartSite();
            $darazList = Cache::store('account')->darazSite();
            $lazadaList = Cache::store('account')->lazadaSite();
            $noonList = Cache::store('account')->noonSite();
            $jumiaList = Cache::store('account')->jumiaSite();
            $shopeeList = Cache::store('ShopeeAccount')->getSite();
            $id = 0;
            $lazada_new = [];
            foreach($lazadaList as $k => $v){
                $temp['id'] = $id;
                $temp['name'] = $v['name'];
                $temp['code'] = $v['county_id'];
                $lazada_new[$v['county_id']] = $temp;
                $id ++;
            }

            $id = 0;
            $walmart_new = [];
            foreach($walmartList as $k => $v){
                $temp['id'] = $id;
                $temp['name'] = $v['name'];
                $temp['code'] = $v['site'];
                $walmart_new[$v['site']] = $temp;
                $id ++;
            }
            $id = 0;
            $daraz_new = [];
            foreach($darazList as $k => $v){
                $temp['id'] = $id;
                $temp['name'] = $v['name'];
                $temp['code'] = $v['site'];
                $daraz_new[$v['site']] = $temp;
                $id ++;
            }
            $id = 0;
            $amazon_new = [];
            foreach($amazonList as $k => $v){
                $temp['id'] = $id;
                $temp['name'] = $v['name'];
                $temp['code'] = $v['site'];
                $amazon_new[$v['site']] = $temp;
                $id ++;
            }
            $ebay_new = [];
            foreach($ebayList as $k => $v){
                $ebay_new[$v['code']] = $v;
            }
            $shopee_new = [];
            foreach($shopeeList as $k => $v){
                $shopee_new[$v['code']] = $v;
            }
            $id = 0;
            $noonList_new = [];
            foreach ($noonList as $k => $v) {
                $temp['id'] = $id;
                $temp['name'] = $v['name'];
                $temp['code'] = $v['site'];
                $noonList_new[$v['site']] = $temp;
                $id ++;
            }
            $id = 0;
            $jumiaList_new = [];
            foreach ($jumiaList as $v) {
                $jumiaList_new[$v['site']] = [
                    'id' => $id,
                    'name' => $v['name'],
                    'code' => $v['site'],
                ];
                ++$id;
            }
            $site = [
                'ebay' => $ebay_new,
                'amazon' => $amazon_new,
                'walmart' => $walmart_new,
                'lazada' => $lazada_new,
                'shopee'=>$shopee_new,
                'daraz'=>$daraz_new,
                'noon' => $noonList_new,
                'jumia' => $jumiaList_new,
            ];
            $this->redis->set('cache:channelSite', json_encode($site));
        }
        if (isset($site) && !empty($site) && isset($site[$channel])) {
            return $site[$channel];
        }
        //没有站点
        if($cate){
            return $this->getCate($channel);
        }else{
            return [];
        }
    }

    /** 获取分类
     * @param string $channel
     * @param int $cate
     * @param null $site
     * @return array
     */
    public function getCate($channel, $cate = null, $site = null)
    {


        //获取站点的信息
        $channelSite = json_decode($this->redis->get('cache:channelSite'), true);
        if($channel=='amazon'){
            if (is_null($cate) && is_null($site)) {
                //获取第一级分类
                $where['parent_id'] = ['=', 0];
            }
            if (is_null($cate) && !is_null($site)) {
                //获取站点的第一级分类
                $where['parent_id'] = [ '=', 0];
                $where['site'] = ['=', $site];
            }
            if (!is_null($cate) && !is_null($site)) {
                //获取分类
                $where['parent_id'] = ['=', $cate];
                $where['category_id']        = ['<>', $cate];
                $where['site'] = ['=', $site];
            }
            if (!is_null($cate) && is_null($site)) {
                $where['parent_id'] = ['=', $cate];
            }
        }else{
            if (is_null($cate) && is_null($site)) {
                //获取第一级分类
                $where['category_level'] = ['=', 1];
            }
            if (is_null($cate) && !is_null($site)) {
                //获取站点的第一级分类
                $where['category_level'] = [ '=', 1];
                $where['site'] = ['=', $channelSite[$channel][$site]['id']];
            }
            if (!is_null($cate) && !is_null($site)) {
                //获取分类
                $where['category_parent_id'] = ['=', $cate];
                $where['category_id']        = ['<>', $cate];
                $where['site'] = ['=', $channelSite[$channel][$site]['id']];
            }
            if (!is_null($cate) && is_null($site)) {
                $where['category_pid'] = ['=', $cate];
            }
        }

        //获取所有渠道的分类
        $result = $this->readCate($channel, $where);
        //返回数据
        return $result;
    }

    /** 读取分类信息
     * @param $channel
     * @param $where
     * @return array
     */
    private function readCate($channel, $where)
    {
        // 区分平台
        switch($channel) {
            case 'ebay':
                $fields  = 'category_id,category_level,category_name,category_parent_id, leaf_category as is_leaf';
                $results = Loader::model('ebay.EbayCategory')->where($where)->field($fields)->select();
            break;
            case 'amazon':
                $fields  = 'category_id,name as category_name, parent_id as category_parent_id';
                $results = Loader::model('amazon.AmazonCategory')->field($fields)->where($where)->select();
            break;
            case 'aliExpress':
                $results = [];
                $fields  = 'category_id,category_level,category_name_zh,category_pid as category_parent_id, category_isleaf as is_leaf';
                $lists = Loader::model('aliexpress.AliexpressCategory')->where($where)->field($fields)->select();
                foreach($lists as $list){
                    $list = $list->toArray();
                    $list['category_name'] = $list['category_name_zh'];
                    unset($list['category_name_zh']);
                    $results[] = $list;
                }
            break;
            case 'distribution':
                $results = (new GoodsBrandsLink())->combineCategory($where);
            break;
            default:
                $results = [];
            break;
        }
        return $results;
    }

    /**
     * 获取平台信息
     * @return false|mixed|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getChannel()
    {
        if($this->redis->exists('cache:channel')){
            return json_decode($this->redis->get('cache:channel'),true);
        }
        $result = (new \app\common\model\Channel())->where(['status' => 0])->select();
        $list = [];
        foreach($result as $k => $v){
            $list[$v['name']] = $v;
        }
        $this->redis->set('cache:channel',json_encode($list));
        return $result;
    }

    /**
     * 获取部分渠道信息
     */
    public function getPartialChannel($channel)
    {
        if($this->redis->exists('cache:partial-channel'.json_encode($channel))){
            return json_decode($this->redis->get('cache:partial-channel'.json_encode($channel)),true);
        }

        $where['status'] = 0;
        $where['id'] = array('in',json_decode($channel, true));

        $result = \app\common\model\Channel::where($where)->select();
        $list = [];
        foreach($result as $k => $v){
            $list[$v['name']] = $v;
        }
        $this->redis->set('cache:partial-channel'.json_encode($channel),json_encode($list));
        return $result;
    }

    /** 获取渠道名称
     * @param int $channel_id
     * @return array
     */
    public function getChannelName($channel_id = 0)
    {
        $result = $this->getChannel();
        $new_list = [];
        foreach($result as $k => $v){
            $new_list[$v['id']] = $v['name'];
        }
        if(empty($channel_id) && is_null($channel_id)){
            return $new_list;
        }else{
            return isset($new_list[$channel_id]) ? $new_list[$channel_id] : '';
        }
    }

    /**
     * 获取渠道id
     * @param $name
     * @author starzhan <397041849@qq.com>
     */
    public function getChannelId($name){
        $result = $this->getChannel();

        $name = strtolower($name);
        foreach ($result as $k=>$v){
            $v['name'] = strtolower($v['name']);
            if($v['name']==$name){
                return $v['id'];
            }
        }
        return false;
    }

    /** 读取分类属性
     * @param null $channel
     * @param null $cate
     * @return array
     */
    public function readAttr($channel = null,$cate = null)
    {
        if($this->redis->exists('cache:channelCategoryAttribute')){
            $attribute = json_decode($this->redis->get('cache:channelCategoryAttribute'),true);
            if(isset($attribute[$channel][$cate])){
                return $attribute[$channel][$cate];
            }
            return false;
        }
        //查询所有平台的属性表
        $aliexpressAttr = Loader::model('aliexpress.AliexpressCategoryAttr')->select();
        $amazonAttr = Loader::model('')->select();
        $ebayAttr = Loader::model('')->select();

        $channelData = json_decode($this->redis->get('cache:channel'),true);
        return array();
    }

    /** 获取站点对应的币种
     * @param string $channel
     * @return array
     */
    public function getSiteCurrency($channel=''){
        if($this->redis->exists('cache:siteCurrency')){
            $result = json_decode($this->redis->get('cache:siteCurrency'),true);
            if($channel)    return isset($result[$channel])?$result[$channel]:[];
            else        return [];
        }
        //查表
        $result = [];
        if($channel=='ebay'){
            
            $ebaySiteModel = new EbaySite();
            $res = $ebaySiteModel->order('country,currency')->select();
    
            foreach ($res as $key=>$vo){
                $result[$channel][$vo['country']] = $vo['currency'];
            }
    
        }

        $this->redis->set('cache:siteCurrency',json_encode($result));
        if($channel)    return isset($result[$channel])?$result[$channel]:[];
        else        return [];
    }
    

    /** 获取站点/账号
     * @param $channel
     * @return \think\response\Json
     * @throws \app\common\cache\Exception
     */
    public function getSiteAccount($channel)
    {
        $site = $this->getSite($channel,false);
        $account = Cache::store('account')->getAccounts(true);
        $result['site'] = $site;
        $result['account'] = isset($account[$channel]) ? $account[$channel] : [];
        return $result;
    }

    /** 获取站点
     * @return false|mixed|\PDOStatement|string|\think\Collection
     */
    public function getEbayCountry()
    {
        if ($this->redis->exists('cache:ebayCountry')) {
            return  json_decode($this->redis->get('cache:ebayCountry'), true);
        } else {
            $db = Db::table('ebay_country');
            $result  = $db->field('countrySn as code,countryNameEn as name,countryNameCn as name_cn')->select();
            $this->redis->set('cache:ebayCountry', json_encode($result));
            return $result;
        }       
    }

    public function getAccountIdByWhere($channel_id,$where,$filed = 'id,code')
    {
        $mode = '';
        switch ($channel_id){
            case ChannelAccountConst::channel_ebay:
                $mode = new EbayAccount();
                break;
            case ChannelAccountConst::channel_amazon:
                $mode = new AmazonAccount();
                break;
            case  ChannelAccountConst::channel_wish:
                $mode = new \app\common\model\wish\WishAccount();
                break;
            case ChannelAccountConst::channel_aliExpress:
                $mode = new AliexpressAccount();
                break;
            case ChannelAccountConst::channel_CD:
                $mode = new CdAccount();
                break;
            case ChannelAccountConst::channel_Lazada:
                $mode = new LazadaAccount();
                break;
            case ChannelAccountConst::channel_Joom:
                $mode = new JoomAccount();
                break;
            case ChannelAccountConst::channel_Pandao:
                $mode = new PandaoAccount();
                break;
            case ChannelAccountConst::channel_Shopee:
                $mode = new ShoppoAccount();
                break;
            case ChannelAccountConst::channel_Paytm:
                $mode = new PaytmAccount();
                break;
            case  ChannelAccountConst::channel_Walmart:
                $mode = new WalmartAccount();
                break;
            case ChannelAccountConst::channel_Vova:
                $mode = new VovaAccount();
                break;
            case ChannelAccountConst::Channel_Jumia:
                $mode = new JumiaAccount();
                break;
            case ChannelAccountConst::Channel_umka:
                $mode = new UmkaAccount();
                break;
            case ChannelAccountConst::channel_Newegg:
                $mode = new NeweggAccount();
                break;
            case ChannelAccountConst::channel_Oberlo:
                $mode = new OberloAccount();
                break;
            case ChannelAccountConst::channel_Shoppo:
                $mode = new ShoppoAccount();
                break;
            case ChannelAccountConst::channel_Zoodmall:
                $mode = new ZoodmallAccount();
                break;
            case ChannelAccountConst::channel_Pdd:
                $mode = new PddAccount();
                break;
            case ChannelAccountConst::channel_Yandex:
                $mode = new \app\common\model\yandex\YandexAccount();
                break;
            case ChannelAccountConst::channel_Paypal:
                $mode = new PaypalAccount();
                break;
            case ChannelAccountConst::channel_Fummart:
                $mode = new FummartAccount();
                break;
            case ChannelAccountConst::channel_Souq:
                $mode = new SouqAccount();
            case ChannelAccountConst::channel_Daraz:
                $mode = new DarazAccount();
                break;
            case ChannelAccountConst::channel_PM:
                $mode = new PmAccount();
                break;
            case ChannelAccountConst::channel_Noon:
                $mode = new NoonAccount();
                break;
        }
        if($mode){
            return $mode->where($where)->field($filed)->select();
        }
        return [];
    }

    /**
     * @ttile 获取渠道名称
     * @param int $channel_id 渠道id
     * @return string|array
     */
    public function getChannelTitle($channel_id)
    {
        $result = $this->getChannel();
        $new_list = [];
        foreach($result as $k => $v){
            $new_list[$v['id']] = $v['title'];
        }
        return isset($new_list[$channel_id]) ? $new_list[$channel_id] : '';
    }
}