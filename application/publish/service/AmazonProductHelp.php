<?php
namespace app\publish\service;

use think\Loader;
use think\Exception;
use think\Db;
use think\Validate;
use app\goods\service\GoodsHelp as GoodsHelpService;
use app\goods\service\GoodsImage as GoodsImageService;
use app\common\cache\Cache;
use app\common\service\Common;
use app\common\service\ChannelAccountConst;
use app\common\model\GoodsSku;
use app\common\model\GoodsSkuAlias;
use app\common\model\GoodsGallery;
use app\goods\service\GoodsSkuMapService;
use app\index\service\DownloadFileService;
use app\common\model\amazon\AmazonProductExport;
use app\common\model\amazon\AmazonProductExportDownload;

Loader::import('phpExcel.PHPExcel', VENDOR_PATH);

class AmazonProductHelp
{   

    const SEARCH_TYPE_SKU = 1;
    const SEARCH_TYPE_SPU = 2;
    const SEARCH_TYPE_SELLER_KU = 3;
    private $exportModel;
    private $exportDownloadModel;
    private $listCondition = [];
    private $baseUrl;
    private $goodsinfo;
    private $parentseller_sku;
    private $seller_sku;

    public function __construct()
    {
        $this->exportModel = new AmazonProductExport();
        $this->baseUrl = Cache::store('configParams')->getConfig('innerPicUrl')['value'] . '/';
    }

    /**
     * 获得产品基本信息
     * goodsBaseinfo
     * @param $goods_id
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public function goodsBaseinfo($goods_id)
    {
        $fields = 'g.spu,g.id as goods_id,gl.title as name,gl.description';
        $join = [['goods_lang gl','g.id = gl.goods_id and gl.lang_id = 2']];
        return Db::name('goods')->alias('g')->join($join)->where(['g.id'=>$goods_id])->field($fields)->find();
    }

    /**
     * 组合SQL条件
     * combineWhere
     * @param array $param
     * @return array
     */
    private function combineWhere(array $param)
    {
        if (!empty($this->listCondition)) {
            return $this->listCondition;
        }

        $where_type = [];
        $where_account = [];
        $where_status = [];
        $where_create_user_id = [];
        $where_create_time = [];
        $join_type = [];
        $join_account = [];

        $sku_search = [];
        if(isset($param['sku_search']))
            $sku_search = json_decode($param['sku_search']);
        if(!empty($sku_search) && is_numeric($sku_search[0]) && !empty($sku_search[1])){
            $sku_type = $sku_search[0];
            $sku_value = $sku_search[1];
            switch ($sku_type){
                case self::SEARCH_TYPE_SKU:
                    $join_type = ["goods_sku gs","e.goods_id=gs.goods_id and gs.sku = '{$sku_value}'"];
                    break;
                case self::SEARCH_TYPE_SPU:
                    $where_type = ['e.spu'=>$sku_value];
                    break;
                case self::SEARCH_TYPE_SELLER_KU:
                    $join_type = ["goods_sku_map sm","e.goods_id=sm.goods_id and sm.channel_sku = '{$sku_value}'"];
                    break;
            }
        }
        if(isset($param['account_id']) && $param['account_id'] > 0){
            $account_id = $param['account_id'];
            $join_account = ["amazon_product_export_download ed","e.goods_id=ed.goods_id","LEFT"];
            $where_account = ['ed.account_id'=>$account_id];
        }

        if(isset($param['status']) && is_numeric($param['status'])){
            $where_status = ['e.status'=>$param['status']];
        }
        if(isset($param['create_user_id']) && is_numeric($param['create_user_id'])){
            $where_create_user_id = ['e.create_user_id'=>$param['create_user_id']];
        }
        $create_time = [];
        if(isset($param['create_time']))
            $create_time = json_decode($param['create_time']);
        if(!empty($create_time)){
            $start_time = $create_time[0];
            $end_time = date('Y-m-d H:i:s',strtotime("$create_time[1] +1 day"));
            if(Validate::dateFormat($start_time,'Y-m-d H:i:s') && Validate::dateFormat($end_time,'Y-m-d H:i:s')){
                $where_create_time = ['e.create_time'=>['between time',[$start_time,$end_time]]];
            }elseif(Validate::dateFormat($start_time,'Y-m-d H:i:s')){
                $where_create_time = ['e.create_time'=>['>= time',$start_time]];
            }elseif(Validate::dateFormat($end_time,'Y-m-d H:i:s')){
                $where_create_time = ['e.create_time'=>['<= time',$end_time]];
            }
        }

        $where = array_merge(
            $where_type,
            $where_account,
            $where_status,
            $where_create_user_id,
            $where_create_time
            );
        $join_user = ["user u","e.create_user_id=u.id"];
        $join = [
            $join_type,$join_account,$join_user
        ];
        $this->listCondition = ['where'=>$where,'join'=>$join];
    }

    /**
     * 查询产品总数
     * @param array $wheres
     * @return int
     */
    public function getCount(array $param)
    {
        $this->combineWhere($param);
        $where = $this->listCondition['where'];
        $join = $this->listCondition['join'];
        $count = $this->exportModel->alias('e')->field('e.*')->join($join)->where($where)->count('distinct e.goods_id');
        return $count;
    }

    /**
     * 获取导出列表信息
     * getList
     * @param array $param
     * @param $page
     * @param $pageSize
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getList(array $param, $page,$pageSize)
    {
        $this->combineWhere($param);
        $where =  $this->listCondition['where'];
        $join =  $this->listCondition['join'];
        $lists = $this->exportModel->getList('e.*,u.realname', $join, $where, 'e.goods_id', $order = 'create_time desc', $page, $pageSize);
        return $lists;
    }

    /**
     * 获取指定产品的信息
     * getDetail
     * @param $id
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public function getDetail($goods_id)
    {
        $result = $this->exportModel->getDetail($goods_id);
        return $result;
    }

    /**
     * 添加产品到预刊登列表
     * add
     * @param array $param
     * @return int|string
     */
    public function add(array $param)
    {
        $userinfo = Common::getUserInfo();
        $data = array();
        $data['spu'] = $param['spu'];
        $data['goods_id'] = $param['goods_id'];
        $data['name'] = $param['name'];
        $data['bullet_point'] = $param['bullet_point'];
        $data['search_terms'] = $param['search_terms'];
        $data['attributes_images'] = $param['attributes_images'];
        $data['extend_info'] = $param['extend_info'];
        $data['description'] = $param['description'];
        $data["create_user_id"] = $userinfo['user_id'];
        $data['create_time'] = date("Y-m-d H:i:s",time());
        $data['update_user_id'] = '0';
        $data['update_time'] = NULL;
        $id = $this->exportModel->add($data);
        return $id;
    }

    /**
     * 更新指定产品信息
     * update
     * @param array $param
     * @param int $goods_id
     * @return $this
     */
    public function update(array $param, $goods_id = 0)
    {
        $userinfo = Common::getUserInfo();

        $data = array();
        $data['name'] = $param['name'];
        $data['bullet_point'] = $param['bullet_point'];
        $data['search_terms'] = $param['search_terms'];
        $data['attributes_images'] = $param['attributes_images'];
        $data['extend_info'] = $param['extend_info'];
        $data['description'] = $param['description'];
        $data["update_user_id"] = $userinfo['user_id'];
        $data['update_time'] = date("Y-m-d H:i:s",time());

        $where = ['goods_id'=>$goods_id];
        $result = $this->exportModel->edit($data, $where);
        return $result;
    }

    /**
     * 删除指定产品
     * delete
     * @param string $goods_ids
     * @return $this
     */
    public function delete($goods_ids = '')
    {
        $where['goods_id']=['IN',$goods_ids];
        $result = $this->exportModel->where($where)->update(['status'=>2]);
        return $result;
    }

    /**
     * 获取预刊登列表里的商品信息
     * getGoodsInfo
     * @param int $goods_id
     * @return array
     */
    public function getGoodsInfo($goods_id)
    {
        if($goodsinfo = $this->exportModel->getDetail($goods_id)){
            return $goodsinfo;
        }
        $baseinfo = $this->goodsBaseinfo($goods_id);
        $images = $this->imageLists($goods_id);
        $attribute = $this->getSkuInfo($goods_id);

        //规格属性处理
        $attr_head = [];
        foreach ($attribute['headers'] as $header){
            $attribute_id = $header['attribute_id'];
            $attr_head[$attribute_id] = $header['name'];
        }
        $attr = [];
        foreach ($attribute["lists"] as $data){
            $sku_id = $data['id'];
            $attr[$sku_id] = array();
            if(isset($data["attributes"]) && is_array($data["attributes"])) {
                foreach ($data["attributes"] as $attr_id_value) {
                    $attr_id = $attr_id_value["attribute_id"];
                    $attr_name = $attr_head[$attr_id];
                    $attr_value = $attr_id_value["attribute_value"];
                    array_push($attr[$sku_id], [$attr_name => $attr_value]);
                }
            }
        }
        //图片处理并且将属性加入图片数组中
        $image_attr = array();
        foreach ($images as $image){
            $sku_id = $image['sku_id'];
            $sku = $image["name"];
            if($sku_id > 0){
                $image_attr[$sku] = [
                    "attribute"=>$attr[$sku_id],
                    "sku_id"=>$sku_id,
                    "sku"=>$sku,
                    "images"=>$image["images"]
                ];
            }else{
                $image_attr[$sku_id] = [
                    "images"=>$image["images"]
                ];
            }
        }

        $bullet_point = ['bullet_point'=>[]];
        $search_terms = ['search_terms'=>[]];
        $image_host = ['baseUrl'=>$this->baseUrl];
        $goodsinfo = array_merge($baseinfo,$bullet_point,$search_terms,$image_host,['attributes_images'=>$image_attr]);
        return $goodsinfo;
    }

    /**
     * 获取产品sku信息列表
     * @param int $goods_id
     * @return array
     */
    public function getSkuInfo($goods_id)
    {
        try {
            if (!$goods_id) {
                throw new Exception('缺少查询必要信息');
            }
            $goods = new GoodsHelpService();
            $result = $goods->getSkuInfo($goods_id);
            return $result;
        } catch (Exception $e) {
            throw new Exception('获取SKU属性失败');
        }
    }

    /**
     * @title 获取刊登图片
     * @param Request $request
     * @return \think\response
     * @throws Exception
     */
    public function imageLists($goods_id = 0, $sku_id = '')
    {
        $service = new GoodsImageService();
        try {
            if (!$goods_id) {
                throw new Exception('缺少查询必要信息');
            }
            //$lists = $this->geImageLists($goods_id,'', []);
            $lists = $service->getLists($goods_id,'', []);
            return $lists;
        } catch (Exception $e) {
            throw new Exception('获取图片失败');
        }
    }

    /**
     * stripTagsDesc
     * @param string $string
     * @return string
     */
    public function stripTagsDesc($string = '')
    {
        return strip_tags($string,'<br> <b>');
    }

    /**
     * 记录导出动作，生成EXCEL并下载
     * download
     * @param array $param
     * @throws \Exception
     */
    public function download(array $param)
    {
        $goods_ids = $param['goods_id'];
        $account = $param['account'];
        $account_id = $account[0];
        $result = $this->logDownloadRecord($account_id, $goods_ids);
        if($result){
            //generate excel
            $this->generateExcel($goods_ids,$account);

            //update status to downloaded
            $data_status = ['status'=>1];
            $data_where = ['goods_id'=>['IN', $goods_ids]];
            $this->exportModel->edit($data_status,$data_where);
            exit;
        }
    }

    /**
     * 记录下载日志
     * logDownloadRecord
     * @param $account_id
     * @param $goods_ids
     * @return int|string
     * @throws \Exception
     */
    public function logDownloadRecord($account_id, $goods_ids)
    {
        $userinfo = Common::getUserInfo();
        $user_id = $userinfo['user_id'];

        //generate parent seller sku and seller sku
        //generate data: $data =['sku_code'=>'sku编码','channel_id'=>'平台id','account_id'=>'账号id']
        $param_sku = [
            'channel_id'=> ChannelAccountConst::channel_amazon,
            'account_id'=>$account_id
        ];

        //seller sku
        $skuModel = new GoodsSku();
        $skuList = $skuModel->where('goods_id','IN', $goods_ids)->select();
        if ($skuList) {
            $goodsSkuMapService = new GoodsSkuMapService();
            foreach ($skuList as $skuinfo){
                $param_sku["sku_code"] = $skuinfo['sku'];
                //seller sku
                $seller_sku = $this->existChannelSku($param_sku);
                if(!$seller_sku){
                    $seller_sku_tmp = $goodsSkuMapService->addSku($param_sku, $user_id);
                    if($seller_sku_tmp["result"])
                        $seller_sku = $seller_sku_tmp["sku_code"];
                    else
                        throw new \Exception('生成SellerSku失败');
                }

                $goods_id = $skuinfo['goods_id'];
                $sku_id = $skuinfo['id'];
                $sku = $skuinfo['sku'];
                $seller_sku_arr[$goods_id][$sku] = $seller_sku;
            }
        }

        //parent seller sku
        $this->goodsinfo = $this->exportModel->getList('*',[],['goods_id'=>['IN',$goods_ids]],'',0,0);
        $goods_spu_arr = [];
        foreach ($this->goodsinfo as $spuinfo){
            $goods_id = $spuinfo['goods_id'];
            $spu = $spuinfo['spu'];
            $goods_spu_arr[$goods_id] = $spu;
            $parentseller_sku[$goods_id] = $spu.'|'.$goods_id;
        }

        $data = [];
        foreach ($goods_ids as $goods_id){
            $data[] = [
                'goods_id'=>$goods_id,
                'spu'=>$goods_spu_arr[$goods_id],
                'account_id'=>$account_id,
                'parent_seller_sku'=>$parentseller_sku[$goods_id],
                'seller_sku'=>json_encode($seller_sku_arr[$goods_id]),
                'download_user'=>$user_id,
                'download_time'=>date("Y-m-d H:i:s",time())
            ];
        }
        //write data
        $this->exportDownloadModel = new AmazonProductExportDownload();
        $result = $this->exportDownloadModel->insertAll($data);
        if($result) {
            $this->parentseller_sku = $parentseller_sku;
            $this->seller_sku = $seller_sku_arr;
        }

        return $result;
    }

    /**
     * existChannelSku
     * 平台SellerSku是否已经存在
     * @param $param
     * @return bool|mixed
     */
    public function existChannelSku($param)
    {
        $where = [
          'sku_code'=>$param['sku_code'],
          'channel_id'=>$param['channel_id'],
          'account_id'=>$param['account_id'],
        ];
        $result = Db::name('goods_sku_map')->where($where)->find();
        if($result)
            return $result["channel_sku"];
        else
            return false;
    }

    /**
     * generateExcel
     * 生成并下载Excel
     * @param array $goods_ids
     * @param array $account
     */
    public function generateExcel(array $goods_ids, array $account)
    {
        $account_id = $account[0];
        $account_code = $account[1];

        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->getProperties()->setCreator("Rondaful");

        $num = 1;
        $header = [
            [ 'title'=>'Seller SKU', 'key'=>'item_sku', 'width'=>25 ],
            [ 'title'=>'Product Name', 'key'=>'item_name', 'width'=>25 ],
            [ 'title'=>'Product Description', 'key'=>'product_description', 'width'=>25 ],
            [ 'title'=>'Key Product Features1', 'key'=>'bullet_point1', 'width'=>25 ],
            [ 'title'=>'Key Product Features2', 'key'=>'bullet_point2', 'width'=>25 ],
            [ 'title'=>'Key Product Features3', 'key'=>'bullet_point3', 'width'=>25 ],
            [ 'title'=>'Key Product Features4', 'key'=>'bullet_point4', 'width'=>25 ],
            [ 'title'=>'Key Product Features5', 'key'=>'bullet_point5', 'width'=>25 ],
            [ 'title'=>'Search Terms1', 'key'=>'generic_keywords1', 'width'=>20 ],
            [ 'title'=>'Search Terms2', 'key'=>'generic_keywords2', 'width'=>20 ],
            [ 'title'=>'Search Terms3', 'key'=>'generic_keywords3', 'width'=>20 ],
            [ 'title'=>'Search Terms4', 'key'=>'generic_keywords4', 'width'=>20 ],
            [ 'title'=>'Search Terms5', 'key'=>'generic_keywords5', 'width'=>20 ],
            [ 'title'=>'Product ID Type', 'key'=>'external_product_id_type', 'width'=>20 ],
            [ 'title'=>'Product ID', 'key'=>'external_product_id', 'width'=>20 ],
            [ 'title'=>'Brand Name', 'key'=>'brand_name', 'width'=>20 ],
            [ 'title'=>'Standard Price', 'key'=>'standard_price', 'width'=>20 ],
            [ 'title'=>'Quantity', 'key'=>'quantity', 'width'=>20 ],
            [ 'title'=>'Condition Type', 'key'=>'condition_type', 'width'=>20 ],
            [ 'title'=>'Main Image URL', 'key'=>'main_image_url', 'width'=>20 ],
            [ 'title'=>'Swatch Image URL', 'key'=>'swatch_image_url', 'width'=>20 ],
            [ 'title'=>'Other Image URL1', 'key'=>'other_image_url1', 'width'=>25 ],
            [ 'title'=>'Other Image URL2', 'key'=>'other_image_url2', 'width'=>25 ],
            [ 'title'=>'Other Image URL3', 'key'=>'other_image_url3', 'width'=>25 ],
            [ 'title'=>'Other Image URL4', 'key'=>'other_image_url4', 'width'=>25 ],
            [ 'title'=>'Other Image URL5', 'key'=>'other_image_url5', 'width'=>25 ],
            [ 'title'=>'Other Image URL6', 'key'=>'other_image_url6', 'width'=>25 ],
            [ 'title'=>'Other Image URL7', 'key'=>'other_image_url7', 'width'=>25 ],
        ];

        $objPHPExcel->getProperties()->setCreator("Rondaful");
        $objPHPExcel->setActiveSheetIndex(0);
        $letter = 'A';
        for ($i = 0; $i < count($header); $i++) {
            $objPHPExcel->getActiveSheet()->setCellValue($letter . $num, $header[$i]['title']);
            $objPHPExcel->getActiveSheet()->setCellValue($letter . ($num+1), $header[$i]['key']);
            $objPHPExcel->getActiveSheet()->getColumnDimension($letter)->setWidth($header[$i]['width']);
            $objPHPExcel->getActiveSheet()->getStyle($letter)->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_TEXT);
            $letter++;
        }

        $num = 2;
        foreach ($this->goodsinfo as $k => $info){
            $num++;
            $goods_id = $info['goods_id'];

            //Spu Baseinfo
            $parentseller_sku = isset($this->parentseller_sku[$goods_id]) ? $this->parentseller_sku[$goods_id] : [];
            $seller_sku = isset($this->seller_sku[$goods_id]) ? $this->seller_sku[$goods_id] : [];

            $name = $info['name'];
            $description = $this->stripTagsDesc($info['description']);

            $bullet_point = $info['bullet_point'];
            $search_terms = $info['search_terms'];
            $extend_info = $info['extend_info'];
            $attributes_images = $info['attributes_images'];

            $images_spu = $attributes_images[0];
            $images_sku = array_slice($attributes_images,1);

            //Spu Images
            if (isset($images_spu['images'][0]['path']))
                $imgpath = GoodsImageService::getThumbPath($images_spu['images'][0]['path'],1001,1001, $account_code,true);
            else
                $imgpath = '';

            $spu_cell = [];
            $spu_cell[$header[0]['key']] = $parentseller_sku;
            $spu_cell[$header[1]['key']] = $name;
            $spu_cell[$header[2]['key']] = $description;
            $spu_cell[$header[3]['key']] = isset($bullet_point[0])?$bullet_point[0]:'';
            $spu_cell[$header[4]['key']] = isset($bullet_point[1])?$bullet_point[1]:'';
            $spu_cell[$header[5]['key']] = isset($bullet_point[2])?$bullet_point[2]:'';
            $spu_cell[$header[6]['key']] = isset($bullet_point[3])?$bullet_point[3]:'';
            $spu_cell[$header[7]['key']] = isset($bullet_point[4])?$bullet_point[4]:'';
            $spu_cell[$header[8]['key']] = isset($search_terms[0])?$search_terms[0]:'';
            $spu_cell[$header[9]['key']] = isset($search_terms[1])?$search_terms[1]:'';
            $spu_cell[$header[10]['key']] = isset($search_terms[2])?$search_terms[2]:'';
            $spu_cell[$header[11]['key']] = isset($search_terms[3])?$search_terms[3]:'';
            $spu_cell[$header[12]['key']] = isset($search_terms[4])?$search_terms[4]:'';
            $spu_cell[$header[19]['key']] = $imgpath;

            $letter = 'A';
            for ($i = 0; $i < count($header); $i++) {
                $header_key = $header[$i]['key'];
                $cell_value = isset($spu_cell[$header_key]) ? $spu_cell[$header_key] : '';
                $objPHPExcel->getActiveSheet()->setCellValue($letter . $num, $cell_value);
                if($header_key == 'main_image_url')
                    break;
                $letter++;
            }

            //Sku Baseinfo, Images and Attributes
            foreach ($images_sku as $sku=>$sku_img_attr){
                $num++;

                //Sku seller sku
                $spu_cell[$header[0]['key']] = $seller_sku[$sku];

                //Extend info
                $sku_exinfo_index = 13;
                $sku_exinfo_end = 17;
                if(isset($extend_info[$sku]) && is_array($extend_info[$sku])){
                    for($sku_exinfo_index;$sku_exinfo_index <= $sku_exinfo_end;$sku_exinfo_index++){
                        $sku_exinfo_key = $header[$sku_exinfo_index]['key'];
                        $spu_cell[$sku_exinfo_key] = $extend_info[$sku][$sku_exinfo_key];
                    }
                }

                $spu_cell[$header[18]['key']] = 'New';
                $spu_cell[$header[19]['key']] = '';
                $spu_cell[$header[20]['key']] = '';
                //sku images
                $sku_other_img_index = 21;
                foreach ($sku_img_attr["images"] as $ki=>$sku_imginfo){
                    if($sku_other_img_index >= count($header))
                        break;
                    $spu_cell[$header[$sku_other_img_index]['key']] = '';
                    $sku_imgpath = GoodsImageService::getThumbPath($sku_imginfo['path'],1001,1001, $account_code,true);
                    if($ki === 0) {
                        $spu_cell[$header[19]['key']] = $sku_imgpath;
                    }elseif(isset($sku_imginfo['isSwatch']) && $sku_imginfo['isSwatch']) {
                        $spu_cell[$header[20]['key']] = $sku_imgpath;
                    }else{
                        $spu_cell[$header[$sku_other_img_index]['key']] = $sku_imgpath;
                        $sku_other_img_index ++;
                    }
                }

                $letter = 'A';
                for ($i = 0; $i < count($header); $i++) {
                    $header_key = $header[$i]['key'];
                    $cell_value = isset($spu_cell[$header_key]) ? $spu_cell[$header_key] : '';
                    $objPHPExcel->getActiveSheet()->setCellValue($letter . $num, $cell_value);
                    $letter++;
                }

                //sku attributes
                if(isset($sku_img_attr['attribute'])){
                    foreach ($sku_img_attr['attribute'] as $akey=>$attrinfo){
                        $objPHPExcel->getActiveSheet()->getColumnDimension($letter)->setWidth(20);

                        //header
                        $objPHPExcel->getActiveSheet()
                            ->setCellValue($letter.'2', '属性'.($akey+1));

                        //value
                        $attr_name = strpos(key($attrinfo),'|') ? explode('|',key($attrinfo))[1] : key($attrinfo);
                        $attr_value = strpos(current($attrinfo),'|') ? explode('|',current($attrinfo))[1] : current($attrinfo);
                        $objPHPExcel->getActiveSheet()
                            ->setCellValue($letter . $num, $attr_name . ':' . $attr_value);
                        $letter++;
                    }
                }
            }
        }

        $excel_name = 'amazon_goods_export.xlsx';
        $objPHPExcel->getActiveSheet()->setTitle('Amazon Publish Data');

        header( "Accept-Ranges: bytes");
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition:attachment;filename=' . $excel_name . '.xls'); //文件名称
        header('Cache-Control: max-age=0');
        $excelWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $excelWriter->save('php://output');
    }


    /**
     * 获取图片列表
     * @param int $id
     * @param string $domain
     * @return array
     */
    public function geImageLists($id, $domain,$param=array())
    {

        $goodsHelp = new GoodsHelpService();
        $skus = $goodsHelp->getGoodsSkus($id);
        $width = 0;
        $height = 0;
        $GoodsGallery = new GoodsGallery();
        if(isset($param['width'])&&$param['width']){
            $width = $param['width'];
        }
        if(isset($param['height'])&&$param['height']){
            $height = $param['height'];
        }
        if(isset($param['is_default'])&&$param['is_default']){
            $is_default = json_decode($param['is_default'],true);
            $addDefault = array_sum($is_default);
            $GoodsGallery = $GoodsGallery->where('is_default|'.$addDefault."=".$addDefault);
        }
        if(isset($param['channel_id'])&&$param['channel_id']){
            $GoodsGallery = $GoodsGallery->where('channel_id',$param['channel_id']);
        }
        $search_images = $GoodsGallery->where('goods_id',$id)->field('id, goods_id, sku_id, path, sort, is_default,channel_id,alt')->order('is_default desc, sort asc')->select();
        $images = [];
        foreach($search_images as $image) {
            $image['path']  =  $image['path'];
            $image['channel'] = $image->channel;
            $image['is_default_txt'] = $image->is_default_txt;
            $image['sku'] = $image->sku;
            $images[$image['sku_id']][] = $image;
        }
        $lists[] = [
            'name' => '主图',
            'attribute_id' => 0,
            'value_id' => 0,
            'sku_id'   => 0,
            'images' => isset($images[0]) ? $images[0] : []
        ];

        foreach ($skus as $skuInfo) {
            $list = [
                'name' => $skuInfo['sku'],
                'attribute_id' => 0,
                'value_id' => 0,
                'sku_id' => $skuInfo['id'],
                'images' => isset($images[$skuInfo['id']]) ? $images[$skuInfo['id']] : []
            ];
            $lists[] = $list;
        }
        return $lists;
    }

}