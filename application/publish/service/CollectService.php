<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-3-5
 * Time: 下午5:14
 */

namespace app\publish\service;
use app\common\cache\Cache;
use app\common\exception\JsonErrorException;
use app\common\model\aliexpress\AliexpressCategory;
use app\common\model\aliexpress\AliexpressProduct;
use app\common\model\GoodsSku;
use app\common\model\PublishCollect;
use app\common\service\Twitter;
use app\publish\validate\CollectValidate;
use QL\QueryList;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\Validate;

class CollectService
{

    const CHANNEL=[
        '1'=>'ebay',
        '2'=>'amazon',
        '3'=>'wish',
        '4'=>'aliExpress',
        '5'=>'CD',
        '6'=>'Lazada',
        '7'=>'joom',
    ];

    public function __construct()
    {
        require_once ROOT_PATH. '/extend/QueryList/vendor/autoload.php';
    }
    public function delete($id)
    {
        Db::startTrans();
        try{
            PublishCollect::whereIn('id',$id)->delete();
            Db::commit();
            return ['message'=>'删除成功'];
        }catch (PDOException $exp){
            Db::commit();
            throw new JsonErrorException($exp->getMessage());
        }
    }
    public function bind($id,$goods_id)
    {
        $goods = Cache::store('Goods')->getGoodsInfo($goods_id);
        if(empty($goods))
        {
            throw new JsonErrorException("本地商品缓存不存在");
        }
        $spu = $goods['spu'];
        $data['goods_id']=$goods_id;
        $data['spu']=$spu;
        Db::startTrans();
        try{
            $model = new PublishCollect();
            $model->isUpdate(true)->save($data,['id'=>$id]);
            Db::commit();
            return ['message'=>'绑定成功'];
        }catch (PDOException $exp){
            Db::rollback();
            throw new JsonErrorException($exp->getMessage());
        }catch (Exception $exp){
            Db::rollback();
            throw new JsonErrorException($exp->getMessage());
        }
    }
    /**
     * @title 采集列表
     * @param $params
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function lists($params,$page=1,$pageSize=50)
    {
        $where=[];
        if(isset($params['product_id']) && $params['product_id'])
        {
            $where['product_id'] = ['=',$params['product_id']];
        }

        if(isset($params['uid']) && $params['uid'])
        {
            $where['create_id'] = ['=',$params['uid']];
        }

        //认领
        if(isset($params['claim_channel']) && $params['claim_channel']=='1')
        {
            $map = "JSON_SEARCH(claim_channel,'one', 'aliExpress') IS NOT NULL ";
        }elseif(isset($params['claim_channel']) && $params['claim_channel']=='0'){
            $map = "JSON_SEARCH(claim_channel,'one', 'aliExpress') IS  NULL ";
        }else{
            $map=[];
        }

        $fields = "a.*,b.realname";
        $total = PublishCollect::where($where)->where($map)->alias('a')->join('user b ','a.create_id=b.id')->count('*');
        $data = PublishCollect::where($where)->order('create_time DESC')->field($fields)->where($map)->alias('a')->join('user b','a.create_id=b.id')->page($page,$pageSize)->select();
        $return=[
            'total'=>$total,'data'=>$data,'page'=>$page,'pageSize'=>$pageSize
        ];
        return $return;
    }

    public function claim($params,$uid=0)
    {
        if($error = (new CollectValidate())->claim($params))
        {
            throw new JsonErrorException($error);
        }
        $id = $params['id'];
        $coolectInfo = PublishCollect::where('id',$id)->find();
        if(empty($coolectInfo))
        {
            throw new JsonErrorException("采集商品信息不存在");
        }
        $goodsInfo = Cache::store('Goods')->getGoodsInfo($params['goods_id']);

        if(empty($goodsInfo))
        {
            throw new JsonErrorException("本地商品信息不存在");
        }
        $skus = GoodsSku::where('goods_id',$params['goods_id'])->select();
        $accounts = explode(";",$params['account_id']);
        $channel_id = $params['channel_id'];

        switch ($channel_id)
        {
            case 1:
                break;
            case 2:
                break;
            case 3:
                break;
            case 4:
                $response = $this->claimAliexpress($accounts,$coolectInfo,$goodsInfo,$uid,$skus);
                break;
            case 5:
                break;
            case 6:
                break;
            case 7:
                break;
            default:
                break;
        }
        if($response['result'])
        {
            $this->updateClaimChannel($id,$channel_id);
        }
        return $response;
    }
    private function updateClaimChannel($id,$channel_id)
    {
        $info = PublishCollect::where('id',$id)->find();
        $claim_channel = $info->getData('claim_channel');
        if(empty($claim_channel))
        {
            $claim_channel = [];
        }else{
            $claim_channel = json_decode($claim_channel,true);
        }

        $channel = self::CHANNEL[$channel_id];
        if(!in_array($channel,$claim_channel))
        {
            array_push($claim_channel,"{$channel}");
        }

        Db::startTrans();
        try{
            PublishCollect::where('id',$id)->setField('claim_channel',json_encode($claim_channel));
            Db::commit();
        }catch (PDOException $exp){
            Db::rollback();
            throw new JsonErrorException($exp->getMessage());
        }
    }
    private function claimAliexpress($accounts,$coolectInfo,$goodsInfo,$uid,$skus)
    {
        $total=0;
        foreach ($accounts as $account_id)
        {
            $productModel = new AliexpressProduct();
            $id = Twitter::instance()->nextId(4,$account_id);
            $arrProductData['account_id'] = $account_id;
            $arrProductData['id'] = $id;
            $arrProductData['subject'] = $coolectInfo['title'];
            $arrProductData['goods_id'] = $coolectInfo['goods_id'];
            $arrProductData['goods_spu'] = $goodsInfo['spu'];
            $arrProductData['category_id'] = $coolectInfo['category_id'];
            $arrProductData['application'] = 'rondaful';
            $arrProductData['group_id']='[]';
            $arrProductData['package_length']=$goodsInfo['depth']/10;
            $arrProductData['package_width']=$goodsInfo['width']/10;
            $arrProductData['package_height']=$goodsInfo['height']/10;
            $arrProductData['gross_weight']=$goodsInfo['weight']/1000;
            $arrProductData['reduce_strategy']=2;
            $arrProductData['publisher_id']=$uid;
            $arrProductData['product_unit']=100000015;
            $arrProductInfoData = [
                'detail'=>$coolectInfo['description'],
                'mobile_detail'=>'[]',
                'product_attr'=>'[]',
            ];
            foreach ($skus as $sku)
            {
                $arrProductSkuData[] = [
                    'sku_price'=>$sku['retail_price'],
                    'sku_code'=>$sku['sku'],
                    'combine_sku'=>$sku['sku'].'*1',
                    'sku_stock'=>0,
                    'ipm_sku_stock'=>0,
                    'currency_code'=>'USD',
                    'sku_attr_relation'=>[],
                    'sku_attr'=>json_encode([]),
                    'goods_sku_id'=>$sku['id'],
                ];
            }

            $where['goods_id']=['=',$arrProductData['goods_id']];
            $where['account_id']=['=',$arrProductData['account_id']];

            if(empty($productModel->where($where)->find()))
            {
                $result = $productModel->addProduct($arrProductData,$arrProductInfoData,$arrProductSkuData);
                if(isset($result['status']) && $result['status'])
                {
                    ++$total;
                }
            }
        }
        if($total==0)
        {
            return ['message'=>"认领成功失败",'result'=>false];
        }else{
            return ['message'=>"认领成功[{$total}]个账号，已经添加至草稿箱",'result'=>true];
        }

    }
    public function common($urls,$uid)
    {
        $urls = explode(';',$urls);
        $num=0;
        foreach ($urls as $url)
        {
            $validate = new Validate(['url'=>'require|url']);
            if (!$validate->check(['url'=>$url]))
            {
                throw new JsonErrorException("采集地址不是有效的链接");
            }
            if(strpos($url, 'ebay.com'))
            {
                $data = $this->ebay($url);
                $channel_id = array_flip(self::CHANNEL)['ebay'];
            }elseif(strpos($url, 'amazon.com'))
            {
                $data =  $this->amazon($url);
                $channel_id = array_flip(self::CHANNEL)['amazon'];
            }elseif (strpos($url, 'aliexpress.com')) {
                $data =  $this->aliexpress($url);
                $channel_id = array_flip(self::CHANNEL)['aliExpress'];
            }elseif(strpos($url, 'tmall.com')){
                $data= $this->tmall($url);
            }elseif(strpos($url, 'gw.api.alibaba.com')){
                $data = $this->alibaba($url);
            }else{
                throw new JsonErrorException("采集地址暂时不支持，请检查");
            }
            $data['link']=$url;
            $data['create_id']=$uid;
            $data['images']=json_encode($data['images']);
            $data['channel_id']=$channel_id;
            $result  = $this->saveCollectData($data);
            if($result)
            {
                ++$num;
            }
        }
        return ['message'=>"成功采集[{$num}]个商品"];
    }

    /**
     * @title 保存采集的数据
     * @param $data
     * @return string
     * @throws JsonErrorException
     */
    public function saveCollectData($data)
    {
        Db::startTrans();
        try{
            $model = new PublishCollect();
            $where['channel_id']=['=',$data['channel_id']];
            $where['product_id']=['=',$data['product_id']];
            $where['create_id']=['=',$data['create_id']];
            if($has = $model->where($where)->field('id')->find())
            {
                $id = $has['id'];
                $model->allowField(true)->save($data,['id'=>$has['id']]);
            }else{
                $model->allowField(true)->save($data);
                $id = $model->id;
            }
            Db::commit();
            return $id;
        }catch (PDOException $exp){
            Db::rollback();
            throw new JsonErrorException($exp->getMessage());
        }catch (Exception $exp){
            Db::rollback();
            throw new JsonErrorException($exp->getMessage());
        }
    }
    /**
     * 处理速卖通采集
     * @param type $url
     * @return array $responseData
     */
    public function aliexpress($url)
    {
        set_time_limit(0);
        $pos = stripos($url, '?');

        if(false !== $pos)
        {
            $url = substr($url,0,$pos);

            preg_match('/\d{11,}/', $url,$product);

            $product_id = $product[0];

        }elseif(preg_match('/\d{11,}/', $url,$product)){
            $product_id = $product[0];
        }

        //采集规则
        $rules = [
            'title' => ['div.detail-wrap>h1.product-name','text'],
            'description'=>['div#j-product-desc>div.product-property-main>div.ui-box-body','html'],
            'symbol'=>['div.p-price-content>span.p-symbol','text'],
            'price'=>['#j-sku-price','text'],
            'category_name'=>['.ui-breadcrumb>.container>h2>a','title'],
        ];

        $data = QueryList::Query($url, $rules)->getData(function($items){
            return $items;
        });

        //描述详情页面
        $desc_url="https://www.aliexpress.com/getDescModuleAjax.htm?productId=".$product_id;

        $desc_data = file_get_contents($desc_url);

        preg_match_all('/<img.*?src="(.*?)".*?>/is',$desc_data,$des_images);

        $gallery_url = str_replace('https://www.aliexpress.com/item', 'https://www.aliexpress.com/item-img', $url);

        $gallery_rules =[
            'gallery'=>['div#change-600-layout>div.image>ul>li','html']
        ];

        $gallery = QueryList::Query($gallery_url,$gallery_rules)->getData(function($item){
            $str = $item['gallery'];
            preg_match('/<img.*?src="(.*?)".*?>/is',$str,$arr);
            return $arr[1];
        });

        $price = explode('-',$data[0]['price']);
        if(count($price)>1)
        {
            $min_price = $price[0];
            $max_price = $price[1];
        }else{
            $min_price = $max_price = $price[0];
        }

        $category_name = $data[0]['category_name'];


        $category = AliexpressCategory::whereLike('category_name',"%{$category_name}%")->find();

        $category_id=$category?$category['category_id']:0;
        $responseData = [
            'title'=>$data[0]['title'],
            'description'=>$data[0]['description'],
            'images'=> array_merge($gallery,$des_images[1]),
            'symbol'=>$data[0]['symbol'],
            'min_price'=>strpos($min_price,',') !== false ? str_replace(',','.',$min_price) : $min_price,
            'max_price'=>strpos($max_price, ',') !== false ? str_replace(',','.',$max_price) : $max_price,
            'product_id'=>$product_id,
            'category_id'=>$category_id
        ];
        return $responseData;
    }

    public function AliexpressApiException($controllers,$file='',$comment)
    {
        $error = [];
        $file = APP_PATH.'publish/exception/'.$file;

        if(!file_exists($file)){
            $open = fopen($file, 'w');
        }else{
            $open = fopen($file, 'w');
        }

        fwrite($open, "<?php".PHP_EOL);
        fwrite($open, "//".$comment.PHP_EOL);
        fwrite($open, "return [".PHP_EOL);
        try{
            foreach ($controllers as $controller){
                try{

                    //'520520'=>['description'=>'','solution'=>'']
                    $str = "\t'".$controller['code']."'=>["."'description'=>'".$controller['description']."','solution'=>'".$controller['solution']."'],".PHP_EOL;
                    fwrite($open, $str);
                }catch (JsonErrorException $exception){
                    $error[] = $exception->getMessage();
                }
            }
        }catch (JsonErrorException $exception){
            fclose($open);
            throw new JsonErrorException($exception->getMessage());
        }
        fwrite($open, "];");
        fclose($open);
        return $error;
    }

    public  function alibaba($url)
    {
        set_time_limit(0);
        //采集规则
        $rules = [
            'v'=>['div.api-detail-bd>div:eq(4)>table>tbody>tr>td','text'],
        ];

        $data = QueryList::Query($url, $rules)->getData(function($item){
            return $item;
        });
        $model = new \app\common\model\aliexpress\AliexpressApiException;

        if($data && is_array($data))
        {
            $len = count($data);
            for($i=0;$i<$len;$i=$i+3)
            {
                $codes = explode('/', $data[$i]['v']);
                $description = $data[$i+1]['v'];
                $solution=$data[$i+2]['v'];
                foreach($codes as $code)
                {
                    $exp[$code]['code']=$code;
                    $exp[$code]['description']=$description;
                    $exp[$code]['solution']=$solution;
                }
            }
            $comment="速卖通修改编辑商品信息异常";
            $response = $this->AliexpressApiException($exp,'AliexpressEditProductException.php',$comment);
        }


    }
    public function tmall( )
    {
        require_once ROOT_PATH. '/extend/QueryList/vendor/autoload.php';

        $url="https://list.tmall.com/search_product.htm?cat=50025135";
        //采集规则
        $rules = [
            'list' => ['div#mallPage>div#content>div.main>div#J_ItemList','html'],
        ];

        $data = QueryList::Query($url, $rules)->getData(function($item){
            return $item;
        });
        dump($data);

    }
    public function  amazon($url)
    {



    }

    public function ebay($url)
    {

    }


    public function test()
    {
        require_once __DIR__. '/QueryList/vendor/autoload.php';

        $url="https://www.aliexpress.com/item/2017-Babyonline-Sexy-Black-O-Neck-Lace-Mermaid-Evening-Dresses-With-Long-Sleeve-Formal-Dress-Prom/32791468991.html?spm=a2g01.8286187.3.2.AyhpV1&scm=1007.14594.81236.0&pvid=a1104403-db71-4738-9f3e-22665d849da2";


        //多线程扩展
        $curl = QueryList::run('Multi',[
            //待采集链接集合
            'list' => [
                "https://www.aliexpress.com/item/2017-Babyonline-Sexy-Black-O-Neck-Lace-Mermaid-Evening-Dresses-With-Long-Sleeve-Formal-Dress-Prom/32791468991.html?spm=a2g01.8286187.3.2.AyhpV1&scm=1007.14594.81236.0&pvid=a1104403-db71-4738-9f3e-22665d849da2",
            ],
            'curl' => [
                'opt' => array(
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_AUTOREFERER => true,
                ),
                //设置线程数
                'maxThread' => 100,
                //设置最大尝试数
                'maxTry' => 3
            ],
            //不自动开始线程，默认自动开始
            'start' => false,
            'success' => function($html,$info){
                dump($html);                dump($info);die;
            },
            'error' => function(){
                //出错处理
            }
        ]);

        $curl->start();
    }
}