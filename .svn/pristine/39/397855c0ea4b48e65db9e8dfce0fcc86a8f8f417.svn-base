<?php
/**
 * Created by PhpStorm.
 * User: zhangdongdong
 * Date: 2018/6/9
 * Time: 16:01
 */

namespace app\publish\service;


use app\common\cache\Cache;
use app\common\model\amazon\AmazonCategory;
use app\common\model\amazon\AmazonPublishDoc;
use app\common\model\amazon\AmazonPublishDocDetail;
use app\common\model\amazon\AmazonXsdTemplate;
use app\common\model\Goods;
use app\common\model\GoodsLang;
use app\common\model\GoodsPublishMap;
use app\common\model\GoodsSku;
use app\common\model\User;
use app\common\service\ChannelAccountConst;
use app\goods\service\GoodsHelp;
use app\goods\service\GoodsImage;
use think\Db;
use think\Exception;
use \app\publish\service\AmazonXsdTemplate as AmazonXsdTemplateService;

class AmazonPublishDocService
{

    const DOC_ACCOUNT = '0';

    private $site2lang = [];

    private $docModel = null;

    private $docDetailModel = null;

    public function __construct()
    {
        empty($this->docModel) && $this->docModel = new AmazonPublishDoc();
        empty($this->docDetailModel) && $this->docDetailModel = new AmazonPublishDocDetail();
    }

    /**
     * @title 已写范本列表；
     * @param $params
     * @return array
     */
    public function lists($params)
    {
        $where = $this->getCondition($params);

        $page = $params['page'] ?? 1;
        $pageSize = $params['pageSize'] ?? 50;

        $return = [
            'count' => 0,
            'page' => $page,
            'pageSize' => $pageSize,
            'data' => [],
        ];

        $field = 'd.id,d.site,d.spu,d.goods_id,d.category_template_id,d.use_total,d.creator_id,d.create_time';
        $return['count'] = $this->getListCount($where);
        $lists = $this->getListData($where, $field, $page, $pageSize);
        if (empty($lists)) {
            return $return;
        }

        $templateIds = [];
        $docIds = [];
        $userIds = [];
        foreach ($lists as $val) {
            $docIds[] = $val['id'];
            $userIds[] = $val['creator_id'];
            $templateIds[] = $val['category_template_id'];
        }

        $baseUrl = Cache::store('configParams')->getConfig('innerPicUrl')['value'] . DS;
        //用户
        $userList = User::where(['id' => ['in', $userIds]])->column('realname', 'id');

        //模板；
        $templateList = AmazonXsdTemplate::where(['id' => ['in', $templateIds]])->column('name', 'id');
        //详情；
        $detailList = AmazonPublishDocDetail::where(['doc_id' => ['in', $docIds]])
            ->order('id', 'asc')->column('id,doc_id,type,sku,title,standard_price,quantity,variant_info,main_image', 'id');

        $newlist = [];
        foreach ($lists as $val) {
            $tmp = $val->toArray();
            $tmp['title'] = '';
            $tmp['site_text'] = AmazonCategoryXsdConfig::getSiteByNum($val['site']);
            $tmp['main_image'] = '';
            $tmp['category_template_name'] = $templateList[$val['category_template_id']] ?? '未知';
            $tmp['creator'] = $userList[$tmp['creator_id']] ?? '未知';
            $tmp['create_time'] = date('Y-m-d H:i:s', $val['create_time']);
            $tmp['base_url'] = $baseUrl;
            $tmp['detail'] = [];
            foreach ($detailList as $ddid => $detail) {
                if ($val['id'] == $detail['doc_id']) {
                    if ($detail['type'] == 0) {
                        $tmp['title'] = $detail['title'];
                        $tmp['main_image'] = $detail['main_image'];
                    } else {
                        $detail['variant_info'] = json_decode($detail['variant_info'], true);
                        $tmp['detail'][] = $detail;
                    }
                    unset($userList[$ddid]);
                }
            }
            $newlist[] = $tmp;
        }

        $return['data'] = $newlist;
        return $return;
    }


    /**
     * 列表数量；
     * @param $where
     * @return $count int
     */
    public function getListCount($where)
    {
        if (isset($where['g.developer_id']) || isset($where['g.name'])) {
            $count = $this->docModel->alias('d')
                ->join(['goods' => 'g'], 'd.goods_id=g.id')
                ->where($where)
                ->count('d.id');
        } else {
            $count = $this->docModel->alias('d')
                ->where($where)
                ->count('d.id');
        }

        return $count;
    }


    /**
     * 列表数量；
     * @param $where
     * @return $count int
     */
    public function getListData($where, $field, $page, $pageSize)
    {
        if (isset($where['g.developer_id']) || isset($where['g.name'])) {
            $lists = $this->docModel->alias('d')
                ->join(['goods' => 'g'], 'd.goods_id=g.id')
                ->where($where)
                ->field($field)
                ->page($page, $pageSize)
                ->select();
        } else {
            $lists = $this->docModel->alias('d')
                ->where($where)
                ->field($field)
                ->page($page, $pageSize)
                ->select();
        }

        return $lists;
    }

    private function getCondition($params)
    {
        $where = [];
        if (!empty($params['developer_id'])) {
            $where['g.developer_id'] = $params['developer_id'];
        }
        if (!empty($params['creator_id'])) {
            $where['d.creator_id'] = $params['creator_id'];
        }

        if (!empty($params['snType']) && !empty($params['snText'])) {
            $text = json_decode($params['snText'], true);
            if (!empty($text)) {
                $condition = ['in', array_filter(array_unique($text))];
            } else {
                $condition = $params['snText'];
            }

            switch ($params['snType']) {
                case 'spu':
                    $where['d.spu'] = $condition;
                    break;
                case 'sku':
                    $tmp['sku'] = $condition;
                    $doc_ids = $this->docDetailModel->where($tmp)->column('doc_id');
                    $doc_ids[] = 0;
                    $where['d.id'] = ['in', $doc_ids];
                    break;
                case 'name':
                    $where['g.name'] = $condition;
                    break;
                default:
                    throw new Exception('未知搜索参数类别');
            }
        }
        return $where;
    }


    /**
     * 末写范本列表；
     * @param $params
     * @return array
     * @throws Exception
     */
    public function unlists($params)
    {
        //页码；
        $page = $params['page'] ?? 1;
        $pageSize = $params['pageSize'] ?? 50;

        $where = [];
        $where['channel'] = ['eq', ChannelAccountConst::channel_amazon];
        //$where['l.lang_id'] = ['in', [1, 2]];
        $where['m.platform_sale'] = ['IN', array(0, 1)];
        $where['g.sales_status'] = ['<>', 2];   //在售产品；

        if (!empty($params['developer_id'])) {
            $where['g.developer_id'] = $params['developer_id'];
        }
        if (!empty($params['time_start']) && empty($params['time_end'])) {
            $where['g.publish_time'] = ['>',];
        }

        //分类
        $categoryCache = Cache::store('category');
        $category_tree = $categoryCache->getCategoryTree();
        if (!empty($params['category_id'])) {
            $category_Arr[] = $params['category_id'];
            //查找他的子分类
            if (!empty($category_tree[$params['category_id']]['child_ids'])) {
                $category_Arr = array_merge($category_Arr, $category_tree[$params['category_id']]['child_ids']);
            }
            $where['m.category_id'] = ['in', $category_Arr];
        }

        if (!empty($params['snType']) && !empty($params['snText'])) {
            switch ($params['snType']) {
                case 'spu':
                    $where['m.spu'] = array('like', '%' . $params['snText'] . '%');
                    break;
                case 'sku':
                    $goods_id = GoodsSku::where(['sku' => ['like', '%' . $params['snText'] . '%']])->value('goods_id');
                    $where['g.id'] = ['eq', $goods_id];
                    break;
                case 'name':
                    $where['name'] = array('like', '%' . $params['snText'] . '%');
                    break;
                default:
                    throw new Exception('未知搜索参数类别');
            }
        }
        $baseUrl = Cache::store('configParams')->getConfig('innerPicUrl')['value'] . DS;

        $map = '(`publish_status` IS NULL OR NOT JSON_CONTAINS(publish_status, \'' . json_encode([self::DOC_ACCOUNT]) . '\'))';

        $model = new GoodsPublishMap();

        $count = $model->where($map)->alias('m')
            ->join('goods g', 'm.goods_id=g.id', 'LEFT')
            //->join('goods_lang l', 'g.id=l.goods_id')
            ->where($where)
            ->count('m.id');

        $data = $model->where($map)->alias('m')
            ->order('publish_time desc')
            ->field('g.id,g.category_id,m.spu,thumb,name,g.sales_status,publish_time')
            ->join('goods g', 'm.goods_id=g.id', 'LEFT')
            //->join('goods_lang l', 'g.id=l.goods_id')
            ->where($where)
            ->page($page, $pageSize)
            ->select();

        $saleStatus = [1 => '在售', 2 => '停售', 3 => '待发布', 4 => '卖完下架', 5 => '缺货'];
        $ids = [];
        foreach ($data as $k => &$d) {
            $ids[] = $d['id'];
            $d['goods_id'] = $d['id'];
            $d['title'] = '';
            $d['base_url'] = $baseUrl;
            $d['thumb'] = GoodsImage::getThumbPath($d['thumb'], 200, 200);
            $d['sales_status_text'] = $saleStatus[$d['sales_status']] ?? '-';
            $d['category_name'] = $categoryCache->getFullNameById($d['category_id'], $category_tree);
            if (!empty($d['publish_time'])) {
                $d['publish_time'] = date('Y-m-d H:i:s', $d['publish_time']);
            } else {
                $d['publish_time'] = '-';
            }
        }
        unset($d);
        $langList = GoodsLang::where(['goods_id' => ['in', $ids], 'lang_id' => 2])->column('title', 'goods_id');

        foreach ($data as &$d) {
            $d['title'] = $langList[$d['id']] ?? '';
        }
        unset($d);
        return ['count' => $count, 'page' => $page, 'pageSize' => $pageSize, 'data' => $data];
    }


    /**
     * 范本的创建人；
     * @return array
     */
    public function creator()
    {
        $this->docModel = new  AmazonPublishDoc();
        $creator_ids = $this->docModel->group('creator_id')->field('creator_id')->column('creator_id');
        $data = [];
        if (empty($creator_ids)) {
            return [];
        }
        $users = User::where(['id' => ['in', $creator_ids]])->field('realname label,id value')->select();
        foreach ($users as $val) {
            $data[] = $val->toArray();
        }
        return $data;
    }

    /**
     * 删除范本记录;
     * @param $ids
     * @return bool
     * @throws Exception
     */
    public function del($ids)
    {
        if (empty($ids)) {
            throw new Exception('删除参数ids为空');
        }
        $idArr = explode(',', $ids);
        $idArr = array_unique(array_filter($idArr));

        try {
            Db::startTrans();
            AmazonPublishDoc::where(['id' => ['in', $idArr]])->delete();
            AmazonPublishDocDetail::where(['doc_id' => ['in', $idArr]])->delete();
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw new Exception($e->getMessage());
        }
        return true;
    }


    /**
     * 拿取范本新增所需的数据；
     * @param $params
     */
    public function getDocField($params)
    {
        $data = [];
        if (empty($params['goods_id'])) {
            throw new Exception('请传递商品参数goods_id');
        }
        if (empty($params['site'])) {
            throw new Exception('请传递站点参数site');
        }

        $goodModel = new Goods();
        $goodsInfo = $goodModel->where(['id' => $params['goods_id']])->field('id,name,spu,category_id,brand_id')->find();

        if (empty($goodsInfo)) {
            throw new Exception('未找到需要编辑的商品，参数goods_id错误');
        }

        $langs = $this->getLangBySite($params['goods_id'], $params['site']);
        $aSku = GoodsSku::where(['goods_id' => $params['goods_id'], 'status' => ['<>', 2]])->select();

        $sku_list = [];
        $variant_option = [];
        foreach ($aSku as $v) {
            $attr = json_decode($v['sku_attributes'], true);
            $aAttr = GoodsHelp::getAttrbuteInfoBySkuAttributes($attr, $params['goods_id']);
            $row = [];
            $row['sku_id'] = $v['id'];
            $row['sku'] = $v['sku'];
            $row['attr'] = $aAttr;
            $sku_list[] = $row;
        }

        //基础部分
        $data['basic'] = $this->getBasicField();

        //详情描述部分；
        $descField = $this->getDescriptField();
        $spuDesc  = $descField;
        $spuDesc[0]['value'] = $goodsInfo['spu'];
        $spuDesc[1]['value'] = $langs['title'];
        $langs['description']  = str_replace("\r\n", '<br />', $langs['description']);
        $spuDesc[4]['value'] = str_replace(["\r", "\n"], '<br />', $langs['description']);
        $data['descript'][] = ['skuName' => $goodsInfo['spu'], 'field' => $spuDesc];

        //sku部分；
        $data_sku = [];
        $skuField = $this->getSkuField();
        foreach ($skuField as $val) {
            /*因为同时刊登几个帐号需求，这里不再显示货币，由前端自动添加
             * if (strpos($val['name'], 'Price') !== false) {
                $val['name'] = $val['name'] . '[' . $currency . ']';
            }*/
            $data_sku[$val['name']] = $val;
        }

        //图片部分
        $imgField = $this->getImgField();
        //图片信息；
        $imageModel = new GoodsImage();
        $images = $imageModel->getLists($params['goods_id'], '', array('channel_id' => 2));

        foreach ($sku_list as $sku) {
            //描述部分
            $skuDesc = $descField;
            $skuDesc[0]['value'] = $sku['sku'];
            $skuDesc[1]['value'] = $langs['title'];
            $skuDesc[4]['value'] = $langs['description'];
            $data['descript'][] = ['skuName' => $sku['sku'], 'field' => $skuDesc];

            //sku部分；
            $tmpSku = $data_sku;
            $tmpSku['平台SKU']['value'] = $sku['sku'];
            $tmpSku['Quantity']['value'] = 1000; //库库；
            $tmpSku['SKUID']['value'] = $sku['sku_id']; //库库；
            $data['sku'][] = $tmpSku;
        }

        //找出主图；
        $spuImg = $imgField[0];
        $spuImg['data'] = [];
        foreach ($images as $val) {
            if ($val['name'] == '主图') {
                $spuImg['data'] = $val;
                $data['img'][] = $spuImg;
            }
        }
        //找出SKU图片
        foreach ($sku_list as $sku) {
            $tmp_swatch = $imgField[1];
            $tmp_swatch['name'] = '平台SKU ' . $sku['sku'] . ' 刊登图片';
            $tmp_swatch['data'] = [];
            foreach ($images as $img) {
                if ($sku['sku'] == $img['name']) {
                    $tmp_swatch['data'] = $img;
                }
            }
            $data['img'][] = $tmp_swatch;
        }

        //找出产品原属性
        $math = [];
        $variant = [];
        if (!empty($sku_list)) {
            foreach ($sku_list[0]['attr'] as $attr) {
                $math[] = ['label' => '参考_' . $attr['name'], 'value' => $attr['name']];
            }

            foreach ($sku_list as $val) {
                foreach ($val['attr'] as $attr) {
                    $variant[$val['sku']]['参考_' . $attr['name']] = $attr['value'];
                }
            }
        }

        $newSkuData = [];
        foreach ($data['sku'] as $skudata) {
            $tmp = [];
            foreach ($skudata as $key => $skuval) {
                $tmp[$key] = $skuval;

                //在自定义SKU后面加上参考字段；
                if ($key == '平台SKU') {
                    foreach ($math as $val) {
                        $tmp[$val['label']]['name'] = $val['label'];
                        $tmp[$val['label']]['value'] = '';
                        $tmp[$val['label']]['type'] = 'text';
                        if (isset($variant[$skudata['平台SKU']['value']][$val['label']])) {
                            $tmp[$val['label']]['value'] = $variant[$skudata['平台SKU']['value']][$val['label']];
                        }
                    }
                }
            }
            $newSkuData[] = $tmp;
        }

        $data['sku'] = $newSkuData;
        $data['variant_option'] = $math;

        return $data;
    }


    /**
     * 拿取范本新增所需的数据；
     * @param $params
     */
    public function editDocField($doc_id, $copy = 0)
    {
        //范本主记录；
        $docData = AmazonPublishDoc::where(['id' => $doc_id])->find();
        if (empty($docData)) {
            throw new Exception('范本ID错误，范本不存在');
        }

        //范本详情；
        $docDetails = AmazonPublishDocDetail::where(['doc_id' => $doc_id])->order('id', 'asc')->select();
        $site = AmazonCategoryXsdConfig::getSiteByNum($docData['site']);

        //装整体的数据；
        $data = [];
        $data['id'] = !$copy ? $docData['id'] : 0;
        $data['spu'] = $docData['spu'];
        $data['site'] = $docData['site'];
        $data['goods_id'] = $docData['goods_id'];
        $data['product_template_id'] = $docData['product_template_id'];
        $data['category_template_id'] = $docData['category_template_id'];
        $data['amazon_category_name'] = '';
        $data['amazon_category_name2'] = '';

        $categoryModel = new AmazonCategory();

        $goodsSkuModel = new GoodsSku();
        $goodModel = new Goods();
        $goodsInfo = $goodModel->where(['id' => $docData['goods_id']])->field('id,name,spu,category_id,brand_id')->find();

        if (empty($goodsInfo)) {
            throw new Exception('未找到需要编辑的商品，参数goods_id错误');
        }

        $langs = $this->getLangBySite($docData['goods_id'], $docData['site']);
        $aSku = GoodsSku::where(['goods_id' => $docData['goods_id'], 'status' => ['<>', 2]])->select();

        $sku_list = [];
        $variant_option = [];
        foreach ($aSku as $v) {
            $attr = json_decode($v['sku_attributes'], true);
            $aAttr = GoodsHelp::getAttrbuteInfoBySkuAttributes($attr, $docData['goods_id']);
            $row = [];
            $row['sku_id'] = $v['id'];
            $row['sku'] = $v['sku'];
            $row['attr'] = $aAttr;
            $sku_list[] = $row;
        }

        //其础信息；
        $data['basic'] = [];
        $el_basic = $this->getBasicField(false);
        $el_basic['ItemType']['value'] = $docData['item_type'];
        $el_basic['RecommendedBrowseNode']['value'] = '';

        if (!empty($docData['recommend_node'])) {
            $nodeArr = explode(',', $docData['recommend_node']);
            $newNodeArr = [];
            if (!empty($nodeArr[0])) {
                $category_name = $this->getAmazonCategoryName($nodeArr[0], $site, $categoryModel);
                if ($category_name) {
                    $newNodeArr[] = $nodeArr[0];
                    $data['amazon_category_name'] = $nodeArr[0]. ' ->> '. $category_name;
                }
            }
            if (!empty($nodeArr[1])) {
                $category_name = $this->getAmazonCategoryName($nodeArr[1], $site, $categoryModel);
                if ($category_name) {
                    $newNodeArr[] = $nodeArr[1];
                    $data['amazon_category_name2'] = $nodeArr[1]. ' ->> '. $category_name;
                }
            }
            if (!empty($newNodeArr)) {
                $el_basic['RecommendedBrowseNode']['value'] = implode(',', $newNodeArr);
            }
        }
        unset($categoryModel);

        $el_basic['Department']['value'] = $docData['department'];

        $el_basic['VariationTheme']['value'] = $docData['theme_name'];
        foreach ($el_basic as $key => $val) {
            $val['key'] = $key;
            $data['basic'][] = $val;
        }


        //descript部分；
        $data['descript'] = [];
        $data_descript = $this->getDescriptField(false);

        //sku部分；
        $data['sku'] = [];
        $data_sku = $this->getSkuField(false);

        //图片信息；
        $imageModel = new GoodsImage();
        $images = $imageModel->getLists($docData['goods_id'], '', array('channel_id' => 2));

        //图片部分
        $data_img = $this->getImgField();
        //图片的base_url;
        $baseUrl = Cache::store('configParams')->getConfig('innerPicUrl')['value'] . DS;

        $variant_info = [];
        $variant_info_arr = [];
        foreach ($docDetails as $key=>$detail) {
            $tmp_descript = $data_descript;

            $bullet_point = json_decode($detail['bullet_point'], true);

            $tmp_descript['SKU']['value'] = $detail['sku'];
            $tmp_descript['Title']['value'] = $detail['title'];
            $tmp_descript['SearchTerms']['value'] = $detail['search_Terms'];
            $tmp_descript['BulletPoint']['value'] = [
                $bullet_point[0]?? '', $bullet_point[1]?? '', $bullet_point[2]?? '', $bullet_point[3]?? '', $bullet_point[4]?? ''
            ];
            $tmp_descript['Description']['value'] = $detail['description'];
            $new_tmp_descript = [];
            foreach ($tmp_descript as $keyd => $val) {
                $val['key'] = $keyd;
                $new_tmp_descript[] = $val;
            }

            $data['descript'][] = ['skuName'=> $detail['sku'], 'detail_id' => ($copy == 0? $detail['id'] : 0), 'field' => $new_tmp_descript];

            //sku部分；
            if ($key > 0) {
                $tmp_sku = $data_sku;
                $tmp_sku['SKU']['value'] = $detail['sku'];
                $tmp_sku['StandardPrice']['value'] = $detail['standard_price'];
                $tmp_sku['Quantity']['value'] = $detail['quantity'];
                $tmp_sku['SKUID']['value'] = $detail['sku_id'];
                $new_tmp_sku = [];
                foreach ($tmp_sku as $sku_key => $val) {
                    $val['key'] = $sku_key;
                    $new_tmp_sku[$val['name']] = $val;
                }
                $data['sku'][] = $new_tmp_sku;

                $variant_info[$detail['sku']] = empty($detail['variant_info']) ? [] : json_decode($detail['variant_info'], true);
                $temp_variant_info['skuName'] = $detail['sku'];
                $temp_variant_info['field'] = empty($detail['variant_info']) ? [] : json_decode($detail['variant_info'], true);
                $variant_info_arr[] = $temp_variant_info;
            }


            //取出图片部分；
            if ($key == 0) {
                $main_img = [
                    ['base_url' => $baseUrl, 'path' => $detail['main_image']]
                ];
                $other_image = json_decode($detail['other_image'], true);
                foreach ($other_image as $val) {
                    $main_img[] = ['base_url' => $baseUrl, 'path' => $val];
                }

                //找出主副图的数据；
                $main = $data_img[0];
                $main['data'] = [];
                foreach ($images as $val) {
                    if ($val['name'] == '主图') {
                        $main['data'] = $val;
                        $main['value'] = $main_img;
                        continue;
                    }
                }
                $data['img'][] = $main;

            } else {
                $variant_img = [];

                if ($detail['main_image'] != '') {
                    if ($detail['main_image'] != $detail['swatch_image']) {
                        $variant_img[] = ['base_url' => $baseUrl, 'path' => $detail['main_image'], 'is_default' => 1, 'is_swatch' => false];
                        if ($detail['swatch_image'] != '') {
                            $variant_img[] = ['base_url' => $baseUrl, 'path' => $detail['swatch_image'], 'is_default' => 0, 'is_swatch' => true];
                        }
                    } else {
                        $variant_img[] = ['base_url' => $baseUrl, 'path' => $detail['swatch_image'], 'is_default' => 1, 'is_swatch' => true];
                    }
                } else {
                    if ($detail['swatch_image'] != '') {
                        $variant_img[] = ['base_url' => $baseUrl, 'path' => $detail['swatch_image'], 'is_default' => 0, 'is_swatch' => true];
                    }
                }
                $other_image = json_decode($detail['other_image'], true);
                if (!empty($other_image) && is_array($other_image)) {
                    foreach ($other_image as $val) {
                        $variant_img[] = ['base_url' => $baseUrl, 'path' => $val, 'is_default' => 0, 'is_swatch' => false];
                    }
                }

                $tmp_swatch = $data_img[1];
                $tmp_swatch['name'] = '平台SKU ' . $detail['sku'] . ' 刊登图片';
                $tmp_swatch['value'] = $variant_img;
                $tmp_swatch['data'] = [];
                foreach ($images as $img) {
                    if ($detail['sku'] == $img['name']) {
                        $tmp_swatch['data'] = $img;
                        continue;
                    }
                }

                //此处判断为空，则为另外SPU的产品，另外逻辑来处理；
                if (empty($tmp_swatch['data'])) {
                    $tmp_swatch['data']['name'] = $detail['sku'];
                    $tmp_swatch['data']['baseUrl'] = $baseUrl;
                    $tmp_swatch['data']['attribute_id'] = 0;
                    $tmp_swatch['data']['value_id'] = 0;
                    $tmp_swatch['data']['sku_id'] = 0;
                    $tmp_swatch['data']['goods_id'] = 0;
                    $tmp_swatch['data']['images'] = [];

                    $goodsSkuData = $goodsSkuModel->where(['sku' => $detail['sku']])->find();
                    if (!empty($goodsSkuData)) {
                        $tmp_swatch['data']['sku_id'] = $goodsSkuData['id'];
                        $tmp_swatch['data']['goods_id'] = $goodsSkuData['goods_id'];
                    }
                }

                $data['img'][] = $tmp_swatch;
            }
        }

        //找出产品原属性
        $math = [];
        $variant = [];
        if (!empty($sku_list)) {
            foreach ($sku_list[0]['attr'] as $attr) {
                $math[] = ['label' => '参考_' . $attr['name'], 'value' => $attr['name']];
            }

            foreach ($sku_list as $val) {
                foreach ($val['attr'] as $attr) {
                    $variant[$val['sku']]['参考_' . $attr['name']] = $attr['value'];
                }
            }
        }

        $newSkuData = [];
        foreach ($data['sku'] as $skudata) {
            $tmp = [];
            foreach ($skudata as $key => $skuval) {
                $tmp[$key] = $skuval;

                //在自定义SKU后面加上参考字段；
                if ($key == '平台SKU') {
                    foreach ($math as $val) {
                        $tmp[$val['label']]['name'] = $val['label'];
                        $tmp[$val['label']]['value'] = '';
                        $tmp[$val['label']]['type'] = 'text';
                        if (isset($variant[$skudata['平台SKU']['value']][$val['label']])) {
                            $tmp[$val['label']]['value'] = $variant[$skudata['平台SKU']['value']][$val['label']];
                        }
                    }
                }
            }
            $newSkuData[] = $tmp;
        }

        $data['sku'] = $newSkuData;
        $data['variant_option'] = $math;
        $data['category_template_info'] = is_string($docData['category_info']) ? json_decode($docData['category_info'], true) : $docData['category_info'];
        $data['product_template_info'] = is_string($docData['product_info']) ? json_decode($docData['product_info'], true) : $docData['product_info'];
        $data['variant_info'] = $variant_info;
        $data['variant_info_arr'] = $variant_info_arr;

        return $data;
    }

    /**
     * 组装前端要的category_name
     * @param $category_id
     */
    private function getAmazonCategoryName($category_id, $site, $model)
    {
        $category = $model->where(['category_id' => $category_id, 'site' => $site])->field('category_id,name,path')->find();
        return (!empty($category['path']))? str_replace(',','>>',$category['path']) : '';
    }


    /**
     * 根据 goods_id和site获取对应站点title 和description
     * @param $goods_id
     * @param $site
     * @return array
     */
    private function getLangBySite($goods_id, $site)
    {
        $lang_id = $this->getLangIdBySite($site);

        $result['title'] = '';
        $result['description'] = '';

        //找到对应站点的语言，如果找不到，则找英文；
        $langs = GoodsLang::where(['goods_id' => $goods_id])->field('lang_id,title,description')->column('*', 'lang_id');
        if (empty($langs)) {
            return $result;
        }
        $result = [];
        if (isset($langs[$lang_id])) {
            $result['title'] = $langs[$lang_id]['title'];
            $result['description'] = $langs[$lang_id]['description'];
        } else if ($lang_id != 2 && isset($langs[2])) {
            $result['title'] = $langs[2]['title'];
            $result['description'] = $langs[2]['description'];
        }
        if (isset($langs[1])) {
            $result['title'] = empty($result['title'])? $langs[1]['title'] : $result['title'];
            $result['description'] = empty($result['description'])? $langs[1]['description'] : $result['description'];
        }
        return $result;
    }


    /**
     * 找出站点对应的语言ID
     * @param $site
     * @return int|mixed
     */
    private function getLangIdBySite($site)
    {
        //站点语言；
        $lang = AmazonCategoryXsdConfig::getLangCodeBySite($site);
        //先找出lang_ids
        $langs = Cache::store('Lang')->getLang();
        //先找出语言选项；
        $lang_id = 2;
        foreach ($langs as $val) {
            if (strcasecmp($val['code'], $lang) === 0) {
                $lang_id = $val['id'];
                break;
            }
        }
        return $lang_id;
    }


    /**
     * 获取产品品牌
     * @param int $id
     * @return string
     */
    private function getBrandById($id)
    {
        $lists = Cache::store('brand')->getBrand();
        foreach ($lists as $list) {
            if ($list['id'] == $id) {
                return $list['name'];
            }
        }
        return '';
    }


    /**
     * 通过goods_id拿头部部分；
     * @param $goods_id
     * @return array
     * @throws Exception
     */
    public function getBaseField($goods_id)
    {
        $goodsHelperModel = new GoodsHelp();
        $result = [];
        $goodModel = new Goods();
        $goodsInfo = $goodModel->where(['id' => $goods_id])->field('id,name,spu,category_id,brand_id')->find();

        if (empty($goodsInfo)) {
            throw new Exception('参数goods_id错误，产品不存在');
        }

        $result['goods_id'] = $goodsInfo->id;
        $result['goods_name'] = $goodsInfo->name;
        $result['spu'] = $goodsInfo->spu;
        $result['category_id'] = $goodsInfo->category_id;
        $result['category_name'] = $goodsHelperModel->mapCategory($goodsInfo->category_id);
        $result['brand'] = $this->getBrandById($goodsInfo->brand_id);

        //语言
        //$langs = GoodsLang::where(['goods_id' => $goods_id])->field('lang_id,title,description')->column('*', 'lang_id');
        //$result['title'] = empty($langs[2]) ? $langs[1]['title'] : $langs[2]['title'];
        //$result['description'] = empty($langs[2]) ? $langs[1]['description'] : $langs[2]['description'];

        $sites = AmazonCategoryXsdConfig::getSiteList();
        $existsSite = AmazonPublishDoc::where(['goods_id' => $goods_id])->column('site');
        $existsSite = empty($existsSite) ? [] : $existsSite;
        foreach ($sites as &$val) {
            $val['used'] = 0;
            if (in_array($val['value'], $existsSite)) {
                $val['used'] = 1;
            }
            $val['currency'] = AmazonCategoryXsdConfig::getCurrencyBySite($val['label']);
        }

        $result['sites'] = $sites;

        return $result;
    }

    public function getBasicField($getArr = true)
    {
        $field = [
            'ItemType' => [
                'name' => 'Item Type Keyword',
                'tree' => '/Product/DescriptionData/ItemType',
                "select" => 0,
                "require" => 0,
                "maxLength" => 500,
                "minLength" => 1,
                "pattern" => "",
                "option" => "",
                "totalDigits" => "",
                'value' => '',
            ],
            'RecommendedBrowseNode' => [
                'name' => 'RecommendedBrowseNode',
                'tree' => 'Product/DescriptionData/RecommendedBrowseNode',
                "select" => 0,
                "require" => 0,
                "maxLength" => 100,
                "minLength" => 0,
                "pattern" => "",
                "option" => "",
                "totalDigits" => "",
                'value' => '',
            ],
            'Department' => [
                'name' => 'Department',
                'tree' => '/Product/ProductData/category/Department',
                "select" => 0,
                "require" => 0,
                "maxLength" => 50,
                "minLength" => 1,
                "pattern" => "",
                "option" => "",
                "totalDigits" => "",
                'value' => '',
            ],
            'VariationTheme' => [
                'name' => 'VariationTheme',
                'tree' => '/Product/ProductData/category/VariationTheme',
                "select" => 1,
                "require" => 0,
                "maxLength" => 50,
                "minLength" => 1,
                "pattern" => "",
                "option" => "",
                "totalDigits" => "",
                'value' => '',
            ]
        ];
        if ($getArr) {
            $list = [];
            foreach ($field as $key => $val) {
                $val['key'] = $key;
                $list[] = $val;
            }
            return $list;
        }
        return $field;
    }

    public function getDescriptField($getArr = true)
    {
        $field = [
            'SKU' => [
                'name' => '平台SKU',
                'tree' => '/Product/SKU',
                "select" => 0,
                "require" => 1,
                "maxLength" => 200,
                "minLength" => 1,
                "pattern" => "",
                "option" => "",
                "totalDigits" => "",
                'value' => '',
            ],
            'Title' => [
                'name' => '刊登标题',
                'tree' => '/Product/DescriptionData/Title',
                "select" => 0,
                "require" => 1,
                "maxLength" => 200,
                "minLength" => 1,
                "pattern" => "",
                "option" => "",
                "totalDigits" => "",
                'value' => '',
            ],
            'SearchTerms' => [
                'name' => 'Search Terms',
                'tree' => '/Product/DescriptionData/SearchTerms',
                "select" => 0,
                "require" => 0,
                "maxLength" => 200,
                "minLength" => 1,
                "pattern" => "",
                "option" => "",
                "totalDigits" => "",
                'value' => '',
            ],
            'BulletPoint' => [
                'name' => 'Bullet-Point',
                'tree' => '/Product/DescriptionData/BulletPoint',
                "select" => 0,
                "require" => 0,
                "maxLength" => 500,
                "minLength" => 1,
                "pattern" => "",
                "option" => "",
                "totalDigits" => "",
                'value' => ['', '', '', '', ''],
            ],
            'Description' => [
                'name' => 'Description',
                'tree' => '/Product/DescriptionData/Description',
                "select" => 0,
                "require" => 0,
                "maxLength" => 2000,
                "minLength" => 1,
                "pattern" => "",
                "option" => "",
                "totalDigits" => "",
                'value' => '',
            ]
        ];
        if ($getArr) {
            $list = [];
            foreach ($field as $key => $val) {
                $val['key'] = $key;
                $list[] = $val;
            }
            return $list;
        }
        return $field;
    }

    public function getSkuField($getArr = true)
    {
        $field = [
            'SKU' => [
                'name' => '平台SKU',
                'tree' => '/Product/SKU',
                'type' => 'text',
                'is_batch' => false,
                "select" => 0,
                "require" => 1,
                "maxLength" => 200,
                "minLength" => 1,
                "pattern" => "",
                "option" => "",
                "totalDigits" => "",
                'value' => '',
            ],
            'StandardPrice' => [
                'name' => 'Standard Price',
                'tree' => '/Price/StandardPrice',
                'type' => 'input',
                'is_batch' => true,
                "select" => 0,
                "require" => 0,
                "maxLength" => '',
                "minLength" => '',
                "pattern" => "",
                "option" => '',
                "totalDigits" => "",
                'value' => '',
            ],
            'Quantity' => [
                'name' => 'Quantity',
                'tree' => '/Inventory/Quantity',
                'type' => 'input',
                'is_batch' => true,
                "select" => 0,
                "require" => 1,
                "maxLength" => '',
                "minLength" => '',
                "pattern" => "",
                "option" => '',
                "totalDigits" => "",
                'value' => '1000',
            ],
            'SKUID' => [
                'name' => 'SKUID',
                'tree' => '',
                'type' => 'text',
                'is_batch' => true,
                "select" => 0,
                "require" => 0,
                "maxLength" => '',
                "minLength" => '',
                "pattern" => "",
                "option" => '',
                "totalDigits" => "",
                'value' => '0',
            ],
        ];
        if ($getArr) {
            $list = [];
            foreach ($field as $key => $val) {
                $val['key'] = $key;
                $list[] = $val;
            }
            return $list;
        }
        return $field;
    }

    public function getImgField($getArr = true)
    {
        $field = [
            'SpuImage' => [
                'name' => '平台父SKU 刊登图片',
                'tree' => '',
                "select" => 0,
                "require" => 1,
                "maxLength" => 200,
                "minLength" => 1,
                "pattern" => "@^(?:/.{1,199})|(?:http://.{1,193})|(?:https://.{1,192})$@",
                "option" => "",
                "totalDigits" => "",
                'value' => [],
            ],
            'SkuImage' => [
                'name' => '平台SKU 刊登图片',
                'tree' => '/ProductImage/ImageLocation',
                "select" => 0,
                "require" => 0,
                "maxLength" => 2000,
                "minLength" => 1,
                "pattern" => "@^(?:/.{1,199})|(?:http://.{1,193})|(?:https://.{1,192})$@",
                "option" => "",
                "totalDigits" => "",
                'value' => [],
            ],
        ];
        if ($getArr) {
            $list = [];
            foreach ($field as $key => $val) {
                $val['key'] = $key;
                $list[] = $val;
            }
            return $list;
        }
        return $field;
    }


    /**
     * 获取刊登的字段；
     * @return mixed
     */
    public function getPublishElement($key = true)
    {
        $data['basic'] = $this->getBasicField($key);
        $data['descript'] = $this->getDescriptField($key);
        $data['sku'] = $this->getSkuField($key);
        $data['img'] = $this->getImgField($key);
        return $data;
    }


    /** @var array 用来存放保存详情页，变体的字段 */
    private $variantKey = [];
    private $docUnset = [];

    /**
     * 验证刊登详情发来的参数是否正则；
     * @param $data
     * @return bool
     * @throws Exception
     */
    public function checkPublishData($data)
    {
        $publishServer = new AmazonPublishService();
        $templateHelp = new AmazonXsdTemplateService();
        //验证分类模板参数；
        $category_template_id = $data['category_template']['id'];
        $category_template_data = $templateHelp->getAttr($category_template_id, $data['site']);

        //验证分类模板元素；
        if (empty($category_template_data)) {
            throw new Exception('分类模板ID不存在');
        }

        //验证分类模板元素；
        if (!empty($category_template_data['message'])) {
            throw new Exception($category_template_data['message']);
        }

        //用来装变体字段，保存变体用；
        $variantfield = [];
        //变体rule，下面验证SKU时，用来验证变体字段;
        $variantRule = [];
        //下面验证SKU数据；先找出变体字段是哪几个字段；
        $variantList = $category_template_data['variant'];
        //用来验证变体重复；
        $theme = '';

        //不存在，设为0；
        !isset($data['basic']['VariationTheme']) && $data['basic']['VariationTheme'] = 0;

        if (!empty($variantList) && empty($data['basic']['VariationTheme'])) {
            throw new Exception('分类模板有变体存在时，必须选择一个变体');
        }

        if (!empty($variantList) && !empty($data['basic']['VariationTheme'])) {
            foreach ($variantList as $variant) {
                if ($variant['id'] == $data['basic']['VariationTheme']) {
                    $theme = $variant['name'];
                    $variantfield = $variant['relation_field'];
                    break;
                }
            }
            if (!empty($variantfield)) {
                $variantfield = is_string($variantfield) ? json_decode($variantfield, true) : $variantfield;
            }

            foreach ($category_template_data['attrs'] as $val) {
                if (in_array($val['name'], $variantfield)) {
                    $variantRule[$val['name']] = $val;

                    //变体元素的属性，要加进 variantfield 数组；
                    if (!empty($val['attribute']['name'])) {
                        $variantfield[] = $val['name'] . '@' . $val['attribute']['name'];
                    }
                }
            }
        }
        //保变体键存存一下，保存Sku时用来取出键；
        $this->variantKey[$data['site']] = $variantfield;

        foreach ($data['category_template'] as $key => $field) {
            if ($key == 'id') {
                continue;
            }
            if (strpos($key, '@') !== false) {
                $tmpArr = explode('@', $key);
                $tmpEle = $tmpArr[0];
                $tmpAttr = $tmpArr[1];
                $attrArr = [];
                foreach ($category_template_data['attrs'] as $element) {
                    if ($element['name'] == $tmpEle) {
                        if ($element['attribute']['name'] != $tmpAttr) {
                            throw new Exception('分类模板，' . $key . '参数属性名错误');
                        }
                        $attrArr = $element['attribute'];
                    }
                }
                //填写了前面的参数，后面的属性则为必填；
                if (empty($field) && !empty($data['category_template'][$tmpEle])) {
                    throw new Exception('分类模板，' . $key . '参数值不为空时，请填写或选择后面的单位值');
                }
                if (!empty($attrArr)) {
                    if (isset($attrArr[0]) && !in_array($field, $attrArr)) {
                        throw new Exception('分类模板' . $key . '参数属性值：' . $field . ' 错误');
                    } else {
                        $result = $publishServer->checkField($field, $attrArr);
                        if ($result !== true) {
                            throw new Exception('分类模板，' . $tmpEle . '参数属性值' . $result);
                        }
                    }
                }
                continue;
            }
            //找出具体对应的元素和规则；
            $rule = [];
            foreach ($category_template_data['attrs'] as $element) {
                ($element['name'] == $key) && $rule = $element;
            }
            if (empty($rule)) {
                throw new Exception('分类模板' . $key . '参数错误');
            }

            //转化看是不是json
            if (is_string($field) && json_decode($field)) {
                $field = json_decode($field, true);
            }

            //在变体里面填了的必填项，这里就不再要求必填了；
            if (in_array($key, $variantList) && isset($rule['require']) && $rule['require'] == 1) {
                $rule['require'] = 0;
            }

            if (is_array($field)) {
                foreach ($field as $f) {
                    $result = $publishServer->checkField($f, $rule);
                    if ($result !== true) {
                        throw new Exception('分类模板，' . $key . '参数值' . $result);
                    }
                }
            } else {
                $result = $publishServer->checkField($field, $rule);
                if ($result !== true) {
                    throw new Exception('分类模板，' . $key . ' 参数值' . $result);
                }
            }
            //必填元素如果有属性，也需要填；
            if ((!empty($rule['required']) || !empty($field)) && !empty($rule['attribute']['name'])) {
                $attrKey = $key. '@'. $rule['attribute']['name'];
                if (empty($data['category_template'][$attrKey])) {
                    throw new Exception('分类模板，' . $key . '参数值为必填或有值时，请填写或选择后面的单位值');
                }
            }
        }

        $doc_template_id = $data['product_template']['id'];
        $doc_template_data = $templateHelp->getProductAttr($doc_template_id, $data['site']);

        //验证产品模板元素；
        if (empty($doc_template_data)) {
            throw new Exception('分类模板ID不存在');
        }

        //验证产品模板元素；
        if (!empty($doc_template_data['message'])) {
            throw new Exception($doc_template_data['message']);
        }
        foreach ($data['product_template'] as $key => $field) {
            if ($key == 'id') {
                continue;
            }
            if (strpos($key, '@') !== false) {
                $tmpArr = explode('@', $key);
                $tmpEle = $tmpArr[0];
                $tmpAttr = $tmpArr[1];
                $attrArr = [];
                foreach ($doc_template_data['attrs'] as $element) {
                    if ($element['name'] == $tmpEle) {
                        if ($element['attribute']['name'] != $tmpAttr) {
                            throw new Exception('产品模板，' . $key . '参数属性名错误');
                        }
                        $attrArr = $element['attribute']['restriction'];
                    }
                }
                //填写了前面的参数，后面的属性则为必填；
                if (empty($field) && !empty($data['product_template'][$tmpEle])) {
                    throw new Exception('产品模板，' . $key . '参数值不为空时，请填写或选择后面的单位值');
                }
                //填写了前面的参数，后面的属性则为必填；
                if (empty($field) && !empty($data['product_template'][$tmpEle])) {
                    throw new Exception('产品模板，' . $key . '参数值不为空时，请填写或选择后面的单位值');
                }
                if (!empty($attrArr) && isset($attrArr[0]) && !in_array($field, $attrArr)) {
                    throw new Exception('产品模板' . $key . '参数属性值：' . $field . ' 错误');
                } else {
                    $result = $publishServer->checkField($field, $attrArr);
                    if ($result !== true) {
                        throw new Exception('产品模板，' . $tmpEle . '参数属性值' . $result);
                    }
                }
                continue;
            }
            //找出具体对应的元素和规则；
            $rule = [];
            foreach ($doc_template_data['attrs'] as $element) {
                ($element['name'] == $key) && $rule = $element;
            }

            if (empty($rule)) {
                $this->docUnset[] = $field;
                continue;
                //throw new Exception('产品模板，' . $key . '参数名错误');
            }

            //转化看是不是json
            if (is_string($field) && json_decode($field)) {
                $field = json_decode($field, true);
            }

            if (is_array($field)) {
                foreach ($field as $f) {
                    $result = $publishServer->checkField($f, $rule);
                    if ($result !== true) {
                        throw new Exception('产品模板，' . $key . '参数' . $result);
                    }
                }
            } else {
                $result = $publishServer->checkField($field, $rule);
                if ($result !== true) {
                    throw new Exception('产品模板，' . $key . ' 参数 ' . $result);
                }
            }
            //必填元素如果有属性，也需要填；
            if ((!empty($rule['required']) || !empty($field)) && !empty($rule['attribute']['name'])) {
                $attrKey = $key. '@'. $rule['attribute']['name'];
                if (empty($data['product_template'][$attrKey])) {
                    throw new Exception('产品模板，' . $key . '参数值为必填或有值时，请填写或选择后面的单位值');
                }
            }
        }

        //验证descript数据；
        $ruleArr = $this->getPublishElement(false);

        $key = 'basic';
        foreach ($data['basic'] as $k => $field) {
            if (!isset($ruleArr[$key][$k])) {
                throw new Exception('基础信息，' . $k . '参数名错误');
            }
            if (is_string($field) && json_decode($field)) {
                $field = json_decode($field, true);
            }

            $rule = $ruleArr[$key][$k];
            if (is_array($field)) {
                foreach ($field as $f) {
                    $result = $publishServer->checkField($f, $rule);
                    if ($result !== true) {
                        throw new Exception('基础信息，' . $k . '参数值' . $result);
                    }
                }
            } else {
                $result = $publishServer->checkField($field, $rule);
                if ($result !== true) {
                    throw new Exception('基础信息，' . $k . '参数值' . $result);
                }
            }
        }
        foreach ($data['descript'] as $linezero => $arr) {
            $line = $linezero + 1;
            foreach ($arr as $k => $field) {
                //这里的SKU只是用来和sku部分的SKU相对比来排序的，不进行验证;；
                if ($k == 'SKU') {
                    continue;
                }
                if (!isset($ruleArr['descript'][$k])) {
                    continue;
                    //throw new Exception('标题与描述' . 'SKU:' . $arr['SKU'] . '页面，' . $k . ' 参数名错误');
                }
                $rule = $ruleArr['descript'][$k];
                if (is_string($field) && json_decode($field)) {
                    $field = json_decode($field, true);
                }
                if (is_array($field)) {
                    foreach ($field as $f) {
                        $result = $publishServer->checkField($f, $rule);
                        if ($result !== true) {
                            throw new Exception('标题与描述' . 'SKU:' . $arr['SKU'] . '页面，' . $k . ' 参数值' . $result);
                        }
                    }
                } else {
                    $result = $publishServer->checkField($field, $rule);
                    if ($result !== true) {
                        throw new Exception('标题与描述' . 'SKU:' . $arr['SKU'] . '页面，' . $k . ' 参数值' . $result);
                    }
                    //标题与描述
                    if ($k === 'Description' && !empty($field)) {
                        $result = $publishServer->checkDescription($field);
                        if ($result !== true) {
                            throw new Exception('标题与描述' . 'SKU:' . $arr['SKU'] . '页面，' . $k . ' 参数值' . $result);
                        }
                    }
                    //搜索关键字
                    if ($k === 'SearchTerms' && !empty($field)) {
                        $result = $publishServer->checkSearchTerms($field);
                        if ($result !== true) {
                            throw new Exception('标题与描述' . 'SKU:' . $arr['SKU'] . '页面，' . $k . ' 参数值' . $result);
                        }
                    }
                }
            }
        }

        //比较变体值，不能一样了,否则上变体的时候，会连不上父子关系；
        $variantSkuValLineArr = [];
        $variantSkuValArr = [];
        $key = 'sku';
        foreach ($data['sku'] as $linezero => $arr) {

            $line = $linezero + 1;
            //验证固定属性；
            foreach ($arr as $k => $field) {
                //属于变体的值则跳过；
                if (isset($variantRule[$k]) || strpos($k, '@') !== false) {
                    continue;
                }
                //除去变体的值，如有找不到规则的，则于错参数错误
                if (!isset($ruleArr[$key][$k])) {
                    throw new Exception('SKU设置，第' . $line . '行，存在错误参数 ' . $k);
                }
                if (is_string($field) && json_decode($field)) {
                    $field = json_decode($field, true);
                }
                $rule = $ruleArr[$key][$k];
                if (is_array($field)) {
                    foreach ($field as $f) {
                        $result = $publishServer->checkField($f, $rule);
                        if ($result !== true) {
                            throw new Exception('SKU设置，第' . $line . '行，' . $k . ' 参数值' . $result);
                        }
                    }
                } else {
                    $result = $publishServer->checkField($field, $rule);
                    if ($result !== true) {
                        throw new Exception('SKU设置，第' . $line . '行，' . $k . ' 参数值' . $result);
                    }
                }

                if (in_array($k, ['StandardPrice'])) {
                    if (!is_numeric($field) || $field <= 0) {
                        throw new Exception('SKU设置，第' . $line . '行，' . $k . ' 价格参数值' . $field. '必须大于0');
                    }
                }
            }

            //验证变体属性；
            foreach ($variantRule as $variantKey => $rule) {
                if (!isset($arr[$variantKey])) {
                    throw new Exception('SKU设置，第' . $line . '行，缺少变体' . $variantKey . '的值');
                }
                //找出值；
                $field = $arr[$variantKey];
                $result = $publishServer->checkField($field, $rule);
                if ($result !== true) {
                    throw new Exception('SKU设置，第' . $line . '行，变体' . $variantKey . ' 参数值' . $result);
                }
                $variantSkuValLineArr[$variantKey][trim($field)][] = $line;
                $variantSkuValArr[$line][$variantKey] = trim($field);
            }
        }

        if (!empty($variantSkuValLineArr)) {
            $publishServer->checkVariantValue($theme, $variantSkuValLineArr, $variantSkuValArr);
        }

        //图片的验试省去，以上没抛出异常，则验证通过；
        return true;
    }


    /**
     * 检测刊登数据；
     * @param $list
     * @return bool
     * @throws Exception
     */
    public function checkPublishList(&$list)
    {
        $noteNo = count($list) == 1 ? false : true;
        $before = '';
        foreach ($list as $key => $data) {
            //验证保存的登刊登参数；
            try {
                $before = $noteNo ? '第' . ($key + 1) . '个站点,' : '';
                //验测第一个元素是不是父产品；
                if ($data['spu'] != $data['descript'][0]['SKU']) {
                    throw new Exception('标题描述，第一个对象应该是父产品信息');
                }

                //验证顺序
                foreach ($data['descript'] as $key2 => $val) {
                    $this->replaceTitle($list[$key]['descript'][$key2]['Title']);
                    if ($key2 == 0) {
                        continue;
                    }
                    if ($val['SKU'] != $data['sku'][$key2 - 1]['SKU']) {
                        throw new Exception('标题描述和SKU部分段落顺序对应不上');
                    }
                }
                $this->checkPublishData($data);
            } catch (Exception $e) {
                throw new Exception($before . $e->getMessage());
            }
        }
        return true;
    }


    private $saveReplace = false;


    public function getSaveReplace()
    {
        return $this->saveReplace;
    }


    public function replaceTitle(&$title)
    {
        $length = mb_strlen($title, 'utf-8');
        $check = ['⑧', '!', '*', '￡', '?', '%', 'Lightning', 'Terrific Item', 'Best Seller', 'Sale', 'Free Delivery', 'Great Gift', 'Hot Sale', 'Christmas Sale', 'Available in different colors', 'Brand new', 'Best Seller', 'Seen on TV', 'Popular Best', 'Top Seller', 'Offer of the day', 'Custom size', 'Best Gift Ever', '100% Quantity', 'free worldwide shipping', 'sexy', 'FREE DELIVERY', 'Wholesale', '2-3 days shipping', 'Buy 2 Get 1 Free', 'Guaranteed or Monday Back', 'Bestseller', 'NEW COLORS AVAIABLE', 'new arrival', '1 Best Rated', 'Money Back', 'Limited edition', 'Not to miss', 'Perfect Fit', 'Great price', 'prime day deals', 'High Quality', 'ALL Other Sizes and Styles on Request', 'The package will arrive before Christmas', '[100%]Satisfaction Guaranteed'];
        $title = str_replace($check, '', $title);
        if (mb_strlen($title, 'utf-8') != $length) {
            $this->saveReplace = true;
        }
    }


    /**
     * 保存数据；
     * @param $data
     */
    public function save($list, $uid)
    {
        //data检查如果是string就解码下；
        $list = is_string($list) ? json_decode($list, true) : $list;
        if (empty($list) || !is_array($list)) {
            throw new Exception('范本保存参数data错误，值必须是一个json');
        }
        //先进行检测；
        $this->checkPublishList($list);

        $publishDocModel = new AmazonPublishDoc();

        //找出旧数据；
        $oldProductList = [];
        foreach ($list as $data) {
            //先查看是编辑还是新增，复制属于新增；
            if (empty($data['id'])) {
                $total = $publishDocModel->where(['goods_id' => $data['goods_id'], 'site' => $data['site']])->count();
                if ($total > 0) {
                    throw new Exception('站点：' . AmazonCategoryXsdConfig::getSiteByNum($data['site']) . '数据已存在范本数据，请编辑范本');
                }
            } else {
                $oldProduct = $publishDocModel->where(['id' => $data['id']])->count();
                if (empty($oldProduct)) {
                    throw new Exception('站点：' . AmazonCategoryXsdConfig::getSiteByNum($data['site']) . '编辑数据传入未知ID');
                }
            }
        }

        try {
            $time = time();

            foreach ($list as $data) {
                try {
                    //先查看是编辑还是新增，复制属于新增；
                    $doc['id'] = $data['id'] ?? 0;
                    //先保存amazon_publish_product数据
                    $doc['site'] = $data['site'];
                    $doc['category_id'] = $data['category_id'];
                    //是否已翻译；
                    //$doc['is_translate'] = $data['is_translate'] ?? 0;
                    $doc['goods_id'] = $data['goods_id'];

                    $doc['spu'] = $data['spu'];
                    $doc['item_type'] = $data['basic']['ItemType'];
                    //可能会有两个元素,逗号分隔；
                    if (!empty($data['basic']['RecommendedBrowseNode'])) {
                        $nodeArr = explode(',', $data['basic']['RecommendedBrowseNode']);
                        $nodeArr = array_map(function ($str) {
                            return trim($str);
                        }, $nodeArr);
                        //如果有两个以上，也最多只保留两个；
                        $doc['recommend_node'] = implode(',', array_slice($nodeArr, 0, 2));
                    }
                    $doc['recommend_node'] = $data['basic']['RecommendedBrowseNode'];
                    $doc['department'] = $data['basic']['Department'];
                    $doc['theme_name'] = $data['basic']['VariationTheme'] ?? 0;

                    $doc['category_template_id'] = $data['category_template']['id'];
                    $doc['category_info'] = json_encode($data['category_template'], JSON_UNESCAPED_UNICODE);

                    $doc['product_template_id'] = $data['product_template']['id'];
                    //删除产品模板不存在的数据；
                    foreach ($this->docUnset as $unval) {
                        if (isset($data['product_template'][$unval])) {
                            unset($data['product_template'][$unval]);
                        }
                    }
                    $doc['product_info'] = json_encode($data['product_template'], JSON_UNESCAPED_UNICODE);

                    //只要编辑了，就把刊登状态变更为待刊登；
                    $doc['update_id'] = $uid;
                    $doc['update_time'] = $time;

                    if (empty($doc['id'])) {
                        $doc['creator_id'] = $uid;
                        $doc['create_time'] = $time;
                    }

                    $detailList = [];
                    //保存amazon_publish_product_detail表数据；
                    foreach ($data['descript'] as $key => $descript) {
                        $detail = [];

                        $detail['id'] = $doc['detail_id'] ?? 0;
                        $detail['doc_id'] = $doc['id'];
                        //第一个数组数据是父体；
                        $detail['type'] = $key == 0 ? 0 : 1;

                        //先保存descript里面的数据------------------；
                        $detail['title'] = $descript['Title'];
                        $detail['search_Terms'] = $descript['SearchTerms'];
                        $detail['bullet_point'] = is_string($descript['BulletPoint']) ? $descript['BulletPoint'] : json_encode($descript['BulletPoint'], JSON_UNESCAPED_UNICODE);
                        $detail['description'] = $descript['Description'];

                        //保存sku里面的数据------------------------；
                        //以下数据key错对一位；
                        //sku第一个数组可能是空数组,所以需要判断保存；
                        $detail['sku'] = $detail['type'] ? $data['sku'][$key - 1]['SKU'] : $data['spu'];
                        //标准价格
                        $detail['standard_price'] = $data['sku'][$key - 1]['StandardPrice'] ?? 0;

                        $detail['quantity'] = $data['sku'][$key - 1]['Quantity'] ?? 0;

                        //装变体数据；
                        $variant_info = [];
                        foreach ($this->variantKey[$data['site']] as $vkey) {
                            if ($key > 0) {
                                if (!isset($data['sku'][$key - 1][$vkey])) {
                                    throw new Exception('SKU部分，第' . $key . '条参少变体参数' . $vkey);
                                }
                                $variant_info[$vkey] = $data['sku'][$key - 1][$vkey];
                            }
                        }
                        $detail['variant_info'] = json_encode($variant_info, JSON_UNESCAPED_UNICODE);

                        //解析图片
                        $detail['main_image'] = '';
                        $detail['swatch_image'] = '';
                        $detail['other_image'] = [];
                        $other_number = 0;
                        //保存图片；
                        if ($key == 0) {
                            if (!empty($data['img']['SpuImage'])) {
                                foreach ($data['img']['SpuImage'] as $keyi => $val) {
                                    if ($keyi == 0) {
                                        $detail['main_image'] = $val['path'];
                                        break;
                                    }
                                }
                            }
                        } else {
                            //sku对应的图片列表；
                            $imgList = [];
                            //如果SkuImage 是以数组形势存在的，则是新的数据，否则是旧的数据；
                            if (isset($data['img']['SkuImage'][0])) {
                                if (isset($data['img']['SkuImage'][$key - 1]['data']) && is_array($data['img']['SkuImage'][$key - 1]['data'])) {
                                    $imgList = $data['img']['SkuImage'][$key - 1]['data'];
                                }
                            } else {
                                $imgList = $data['img']['SkuImage'][$descript['SKU']] ?? [];
                            }
                            foreach ($imgList as $ikey=>$val) {
                                if (isset($val['is_default']) && $val['is_default'] == 1 && empty($detail['main_image'])) {
                                    $detail['main_image'] = $val['path'];
                                    unset($imgList[$ikey]);
                                }
                                if (isset($val['is_swatch']) && $val['is_swatch'] == true && empty($detail['swatch_image'])) {
                                    $detail['swatch_image'] = $val['path'];
                                    unset($imgList[$ikey]);
                                }
                            }
                            //以上没有标记主图的，把第一张给主图，如果有主图，则其余的应该全部都是其它图片
                            foreach ($imgList as $ikey=>$val) {
                                if (empty($ikey)&& empty($detail['main_image'])) {
                                    $detail['main_image'] = $val['path'];
                                } else {
                                    if ($other_number < 7) {
                                        $detail['other_image'][] = $val['path'];
                                        $other_number++;
                                    } else {
                                        break;
                                    }
                                }
                            }
                        }
                        //把other_image转成数组；
                        $detail['other_image'] = json_encode($detail['other_image'], JSON_UNESCAPED_UNICODE);
                        $detailList[] = $detail;
                    }

                    $doc_id = $this->saveProductDetailData(['doc' => $doc, 'detailList' => $detailList]);

                } catch (\Exception $e) {
                    throw new Exception($e->getMessage(). $e->getLine());
                }

                // Cache::handler()->hSet('task:Amazon:detail', $doc_id, json_encode($data, JSON_UNESCAPED_UNICODE));
            }

            return true;
        } catch (Exception $e) {
            throw new Exception($e->getMessage() . ' File:' . $e->getFile() . '; Line:' . $e->getLine() . ';');
        }
    }

    /**
     * 保存数据；
     * @param $data
     * @return string
     * @throws Exception
     */
    public function saveProductDetailData($data) : string
    {
        $doc = $data['doc'];
        $detailList = $data['detailList'];

        $publishDocModel = new AmazonPublishDoc();
        $publishDetailModel = new AmazonPublishDocDetail();

        //需要返回的数据；
        $doc_id = 0;

        //分成两种保存方式1.新增保存；
        if ($doc['id'] == 0) {
            Db::startTrans();
            try {
                unset($doc['id']);
                $doc_id = $publishDocModel->insertGetId($doc);
                if (empty($doc_id)) {
                    throw new Exception('保存失败');
                }

                foreach ($detailList as $detail) {
                    $detail['doc_id'] = $doc_id;
                    $publishDetailModel->insert($detail);
                }
                Db::commit();
            } catch (Exception $e) {
                Db::rollback();
                throw new Exception($e->getMessage());
            }

            return $doc_id;
        }

        //2.修改保存以下是更新数据；先查出详情的数据；
        $parent_id = $publishDetailModel->where(['doc_id' => $doc['id'], 'type' => 0])->value('id');
        $oldDetailList = $publishDetailModel->where(['doc_id' => $doc['id'], 'type' => 1])->column('id', 'sku');

        Db::startTrans();
        try {
            $doc_id = $doc['id'];
            unset($doc['id']);
            $publishDocModel->update($doc, ['id' => $doc_id]);

            foreach ($detailList as $detail) {
                $detail['doc_id'] = $doc_id;
                if ($detail['type'] === 0) {
                    if (empty($parent_id)) {
                        $publishDetailModel->insert($detail);
                    } else {
                        //父元素在修改保存时没有带过publish_sku，把spu当做的publish_sku,所以在这里要unset掉防止把已有的数据修改了；
                        $publishDetailModel->update($detail, ['id' => $parent_id]);
                    }
                } else {
                    if (empty($oldDetailList[$detail['sku']])) {
                        $publishDetailModel->insert($detail);
                    } else {
                        $publishDetailModel->update($detail, ['id' => $oldDetailList[$detail['sku']]]);
                        unset($oldDetailList[$detail['sku']]);
                    }
                }
            }

            //删掉多余的；
            if (!empty($oldDetailList)) {
                $publishDetailModel->where(['id' => ['in', array_values($oldDetailList)]])->delete();
            }

            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw new Exception($e->getMessage(). $e->getFile(). $e->getLine());
        }

        return $doc_id;
    }
}