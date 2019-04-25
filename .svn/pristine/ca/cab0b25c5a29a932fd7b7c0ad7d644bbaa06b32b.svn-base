<?php
namespace app\publish\service;



use app\common\exception\JsonErrorException;
use app\common\model\amazon\AmazonCategory;
use app\common\model\amazon\AmazonCategoryXsd;
use app\common\model\amazon\AmazonCategoryAttributeXsd;
use app\common\model\amazon\AmazonCategoryAttributeValueXsd;
use app\common\cache\driver\AmazonXsdCache;
use app\common\model\amazon\AmazonListing;
use app\common\service\ChannelAccountConst;
use app\common\service\GoogleTranslate;
use app\publish\validate\AmazonAtrributeValidate;
use app\common\model\amazon\AmazonCategoryExcelAttributeValue;
use app\common\model\amazon\AmazonXsdVariantMap;
use erp\AbsServer;
use think\Db;
use think\Exception;
use app\goods\service\GoodsHelp;
use app\goods\service\GoodsImage;
class AmazonCategoryHelper extends AbsServer
{

    private $_validate;

    private $categoryModel = null;

    public function __construct()
    {
        parent::__construct();
        $this->_validate = new AmazonAtrributeValidate();
    }


    public function translateToZh()
    {
        $this->categoryModel = new AmazonCategory();
        $num = 0;
        do {
            $hundred = $this->categoryModel->where(['zh_name' => ''])->limit(100)->order('id', 'asc')->column('name', 'id');
            if (!empty($hundred)) {
                $num++;
                $this->translate($hundred);
                $lastValue = end($hundred);
                $keys = array_keys($hundred, $lastValue, true);
                if ($keys) {
                    echo end($keys) . "\r\n";
                }
            }
        } while (count($hundred) == 100);
    }

    public function translate($data)
    {
        $options = [
            'source' => '',
            'target' => 'zh',
            'format' => 'html',
            'model' => 'nmt',
        ];

        try {
            $transateArr = [];
            foreach ($data as $id => $name) {
                if (is_numeric($name)) {
                    $this->categoryModel->update(['zh_name' => $name], ['id' => $id]);
                    continue;
                }
                $transateArr[$name][] = $id;
            }

            $toTranslate = array_keys($transateArr);
            $gooleTranslate = new GoogleTranslate();
            //翻译回来的数据；
            $translateArr2 = $gooleTranslate->translateBatch($toTranslate, $options, 0, ChannelAccountConst::channel_amazon);

            foreach ($transateArr as $name => $idArr) {
                if (is_numeric($name)) {
                    continue;
                }
                foreach ($translateArr2 as $arr) {
                    if ($name === $arr['input']) {
                        $this->categoryModel->update(['zh_name' => $arr['text']], ['id' => ['in', $idArr]]);
                        break;
                    }
                }
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }


    /**
     * 根据父类ID获取模板分类
     * @param int $fid
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getAmazonXsdCategoryByFid($fid = 0)
    {
        $categoryModel = new AmazonCategoryXsd();
        return $categoryModel->field('id,category_name')->where(['f_id' => $fid])->order('id')->select();
    }


    /**
     * 得到基本信息
     */
    public function getBaseAttributeList($site, $categoryId)
    {
        $cache = new AmazonXsdCache();
        return $cache->getBaseAttributeListByCatId($site, $categoryId);
    }


    public function getCommonAttributeList($site, $categoryId)
    {
        $cache = new AmazonXsdCache();
        return $cache->getCommonAttributeListByCatId($site, $categoryId);
    }


    public function getVariantAttributeList($site, $categoryId)
    {
        $cache = new AmazonXsdCache();
        return $cache->getVariantAttributeListByCatId($site, $categoryId);
    }


    public function saveXsdAttributeByCatId($post)
    {
        $this->checkParams($post, 'search');
        //保存数据
        $attributeList = $post['attributes'];
        $baseAttributes = $attributeList['base'];
        $site = $post["site"];
        $categoryId = $post['category_id'];
        $xsdCategoryModel = new AmazonCategoryXsd();
        $xsdCategoryInfo = $xsdCategoryModel->where(['id' => $categoryId])->find();

        if ($xsdCategoryInfo) {
            $oldSites = $xsdCategoryInfo['sites'];
            if (!mb_substr_count($oldSites, $site)) {
                $oldSites = ltrim($oldSites . ',' . $site, ',');
                $xsdCategoryInfo->where(['id' => $categoryId])->update(array('sites' => $oldSites));
            }
        } else {
            throw new JsonErrorException("找不到对应的类目");
        }


        $excelValModel = new AmazonCategoryExcelAttributeValue();
        $this->saveXsdAttribute($baseAttributes, $site, $excelValModel, 'base');

        if (isset($attributeList['common']) && count($attributeList['common'])) {
            $commonAttributes = $attributeList['common'];
            $this->saveXsdAttribute($commonAttributes, $site, $excelValModel, 'common');
        }

        if (isset($attributeList["variant"]) && count($attributeList["variant"])) {
            $variantAttributes = $attributeList["variant"];

            $this->saveVariantAttribute($variantAttributes, $categoryId, $site);
        }

        $cache = new AmazonXsdCache();
        $cache->deleteAttributeCacheByCatId($site, $post['category_id']);

    }


    public function saveVariantAttribute($variants, $categoryId)
    {
        $variantMapModel = new AmazonXsdVariantMap();
        foreach ($variants as $val) {

            $selectedList = $val['selected'];
            // && $selectedList[0]['xsd_id']
            if (is_array($selectedList) && isset($selectedList[0])) {
                $name = $val['variant'];
                $id = $val['variant_id'];
                foreach ($selectedList as $list) {
                    if (isset($list['xsd_id']) && $list['xsd_id']) {
                        $tmp = array(
                            'category_id' => $categoryId,
                            'variant_id' => $id,
                            'variant_name' => $name,
                            'xsd_id' => $list['xsd_id'],
                            'xsd_name' => $list['xsd_name'],
                            'last_update_time' => time(),
                        );
                        $variantMapModel->saveVariantMap($tmp);
                    }
                }

            }
        }
    }


    private function saveXsdAttribute($data, $site, $model, $type)
    {
        if (is_array($data) && count($data)) {
            $attributeXsdModel = new AmazonCategoryAttributeXsd();
            foreach ($data as $val) {
                if (isset($val['values'])) {
                    $values = $val["values"];
                    if ($val["is_hand"] && $values) {
                        if (mb_substr_count($values, ',')) {
                            $values = explode(",", $values);
                        } else {
                            $values = array($values);
                        }

                        foreach ($values as $value) {
                            $valData = [
                                'excel_attr_id' => 0,
                                'xsd_attr_id' => $val['id'],
                                'site' => $site,
                                'name' => $value,
                                'last_update_time' => time(),
                            ];

                            $model->saveExcelAttributeValueByXsdAttrId($valData, $site);
                        }
                    }
                    unset($val["values"]);
                }


                if ($val['enable']) {
                    if (!mb_substr_count($val["sites"], $site)) {
                        $val["sites"] .= "," . $site;
                    }
                    $val["sites"] = trim($val["sites"], ',');
                    if ($type == 'base') {
                        $val['is_public'] = 1;
                    }
                } else {
                    if ($type == 'base') {
                        $val['is_public'] = 0;
                    }
                    $val['sites'] = str_replace(array($site, $site . ','), '', $val['sites']);
                }

                unset($val['enable']);
                $val = $this->formateAttributeData($val);

                $attributeXsdModel->where(['id' => $val['id']])->update($val);

            }
        } else {
            throw new JsonErrorException("修改属性配置,保存失败");
        }
    }


    private function formateAttributeData($data)
    {
        if (is_array($data) && count($data)) {
            foreach ($data as $item => $value) {
                if ($data[$item] === true) {
                    $data[$item] = 1;
                } elseif ($data[$item] === false || ($data[$item] === null && $item == 'is_sku')) {
                    $data[$item] = 0;
                }
            }
        }
        return $data;
    }


    /**
     * 参数验证
     * @param $params
     * @param $scene
     */
    public function checkParams($params, $scene)
    {
        $result = $this->_validate->scene($scene)->check($params);
        if (true !== $result) {
            // 验证失败 输出错误信息
            throw new JsonErrorException('参数验证失败：' . $this->_validate->getError());
        }
    }


    public function getCategoryTreeList()
    {
        $categoryXsdModel = new AmazonCategoryXsd();
        $categoryList = $categoryXsdModel->field("id,category_name")->where(["f_id" => 0])->select();
        $data = array();

        if ($categoryList) {
            foreach ($categoryList as $key => $val) {
                if ($val['category_name'] == "Product") {

                } else {
                    $val["children"] = $categoryXsdModel->field("id,category_name")->where(["f_id" => $val["id"]])->select();
                    $data[] = $val;
                }
            }
        }
        return $data;
    }


    //展示固定的基本属性信息
    public function getBaseAttribute()
    {
        $xsdValModel = new AmazonCategoryAttributeXsd();
        return $xsdValModel->field("name")->where(["is_public" => 1])->select();
    }


    //拉取类目属性
    public function getAttributeByCategoryId($categoryId, $site)
    {
        $xsdValModel = new AmazonCategoryAttributeXsd();
        $sql = "select * from amazon_category_attribute_xsd where category_id = $categoryId AND is_public =0 AND FIND_IN_SET('{$site}',sites) and is_has_children = 0 and is_sku =0 ";
        $list = Db::query($sql);
        if ($list) {
            $valueModel = new AmazonCategoryAttributeValueXsd();
            foreach ($list as $key => $val) {
                $maxNum = $val['max_occurs'];
                if ($maxNum > 1) {
                    $tmp = array();
                    for ($i = 0; $i < $maxNum; $i++) {
                        $tmp[] = array('v' => '');
                    }
                    $list[$key]['value'] = $tmp;
                } else {
                    $list[$key]['value'] = '';
                }

                if ($val['is_select']) {
                    $attrValueList = $valueModel->field('value')->where(['attribute_id' => $val['id']])->select();
                    $list[$key]['option'] = $attrValueList;
                }

            }
        }
        return $list;
    }


    /**
     * 获取亚马逊搜索分类
     * @param $params
     * @param int $page
     * @param int $pageSize
     */
    public function getSearchCategory($params, $site, $page = 1, $pageSize = 30)
    {
        $where = "";
        if ($params) {
            $params = addslashes(trim($params));
            $where = " (category_id ='{$params}' OR name like '{$params}%') AND site = '$site'";
        } else {
            $where = "site = '$site'";
        }
        $amazonCategory = new AmazonCategory();

        $where = 'site="' . $site . '"';
        if (!empty($params)) {
            if (is_numeric($params)) {
                $where .= ' AND ' . 'category_id=' . $params;
            } else {
                //$where .= ' AND MATCH(`name`) AGAINST ("'. $params. '")';
                $where .= ' AND `name` like "' . $params . '%"';
            }
        }
        $total = $amazonCategory->where(['child_count' => 0])->where($where)->count();
        $list = $amazonCategory->field('id,category_id,name,zh_name,attributes,path,parent_id')->where(['child_count' => 0])->where($where)->page($page, $pageSize)->select();
        $news = [];
        if ($list) {
            foreach ($list as $key => $val) {
                $data = $val->toArray();
                $data['en_name'] = $val['name'] . '（' . $val['zh_name'] . '）';

                //编辑一下path，把在最后面贴上中文翻译；
                if (!empty($data['path']) && !empty($data['zh_name'])) {
                    $data['path'] .= '（' . $val['zh_name'] . '）';
                }

                $data['category_path_name'] = str_replace(',', '>>', $data['path']);

                $fields = json_decode($data['attributes'], true);
                $data['item_type_keyword'] = $fields['item_type_keyword'] ?? '';
                $data['department_name'] = $fields['department_name'] ?? '';
                $data['recommended_browse_nodes'] = $fields['recommended_browse_nodes'] ?? '';

                $news[] = $data;
            }
        }
        return ['data' => $news, 'total' => $total, 'page' => $page, 'pageSize' => $pageSize];
    }


    public function getCategoriesByParentId($parentId = 0, $site = '')
    {
        $categoryModel = new AmazonCategory();
        $lists = $categoryModel->field('id,category_id,name,zh_name,attributes,parent_id')->where(['parent_id' => $parentId, 'site' => $site])->select();
        $news = [];
        foreach ($lists as $val) {
            $data = $val->toArray();
            $data['en_name'] = ($data['name'] ?? '') . '（' . ($val['zh_name'] ?? '') . '）';
            $fields = json_decode($data['attributes'], true);
            $data['item_type_keyword'] = $fields['item_type_keyword'] ?? '';
            $data['department_name'] = $fields['department_name'] ?? '';
            $data['recommended_browse_nodes'] = $fields['recommended_browse_nodes'] ?? '';

            $news[] = $data;
        }
        return $news;
    }


    //public function getCategoriesByParentId($parentId = 0,$site=''){
    //    $categoryModel = new AmazonCategory();
    //    $lists = $categoryModel->field('id,category_id,en_name,category_fields_required,parent_id')->where(['parent_id' => $parentId,'marketplace' => $site])->select();
    //    $news = [];
    //    foreach ($lists as $val) {
    //        $data = $val->toArray();
    //        $fields = $this->transitionField($data['category_fields_required']);
    //        $data['item_type_keyword'] = $fields['item_type_keyword'] ?? '';
    //        $data['department_name'] = $fields['department_name'] ?? '';
    //        $data['recommended_browse_nodes'] = $fields['recommended_browse_nodes'] ?? '';
    //
    //        $news[] = $data;
    //    }
    //    return $news;
    //}

    /**
     * 解析分类里面的category_fields_require
     * @param $field
     * @return array
     */
    private function transitionField($field)
    {
        $arr = explode('|', $field);
        $data = [];
        foreach ($arr as $val) {
            $fieldArr = explode(':', $val);
            if (count($fieldArr) == 2) {
                $data[trim($fieldArr[0])] = trim($fieldArr[1]);
            }
        }
        return $data;
    }

    public function getSiteName($site)
    {
        $sitePairs = $this->sitePairs();
        if (isset($sitePairs[$site]) && $sitePairs[$site]) {
            return $sitePairs[$site];
        } else {
            return '';
        }
    }


    public function getProductInfoBySpu($spu, $type = 1)
    {
        $goodsHelperModel = new GoodsHelp();
        $imageModel = new GoodsImage();
        $goodsInfo = $goodsHelperModel->getGoodsAndSkuAttrBySpu($spu);

        $title = array('check', '本地SKU');
        $data = array();
        $json = array(
            'goods_name' => $goodsInfo['goods_name'],
            'category_name' => $goodsInfo['category_name'],
            'brand' => $goodsInfo['brand'] ? $goodsInfo['brand'] : '未知品牌',
            'description' => $goodsInfo['description'],
        );

        if ($type == 1) {
            return $json;
        }

        $images = $imageModel->getLists($goodsInfo['goods_id'], '', array('channel_id' => 2));
        $json['images'] = $images;

        $matchAttribute = array();

        if ($goodsInfo && isset($goodsInfo['sku_list'])) {
            $skuList = $goodsInfo['sku_list'];
            if (isset($skuList[0]['attr']) && count($skuList[0]['attr'])) {
                $attrList = $skuList[0]['attr'];
                foreach ($attrList as $_attVal) {
                    $tmpAttrName = "参考" . mb_substr($_attVal['name'], 0, 2);
                    $title[] = $tmpAttrName;
                    $matchAttribute[] = array('label' => $_attVal['name'], 'value' => $tmpAttrName);
                }
            }
            $title[] = 'hidden';

            $title = array_merge($title, array('Product ID', 'Condition', 'Condition Note', 'Standard Price', 'Sale Price', 'Sale Start Date', 'Sale End Date', 'Quantity'));

            foreach ($goodsInfo['sku_list'] as $key => $_skuVal) {
                $tmpData = array(
                    'check' => array(
                        'value' => false,
                        'type' => 'check',
                    ),
                );

                $tmpData['本地SKU'] =
                    array(
                        'value' => $_skuVal['sku'],
                        'type' => 'text',
                        'node-tree' => 'Product-SKU',
                    );


                if (count($_skuVal['attr'])) {
                    foreach ($_skuVal['attr'] as $_attVal) {
                        $attrName = "参考" . mb_substr($_attVal['name'], 0, 2);
                        $tmpData[$attrName] = array(
                            'value' => $_attVal['value'],
                            'type' => 'text',
                        );
                    }
                }

                $tmpData['Product ID'] = array(
                    array(
                        'type' => 'select',
                        'option' => array('UPC', 'EAN', 'GCID', 'GTIN'),
                        'node-tree' => 'Product-StandardProductID-Type',
                        'value' => '',
                        'is_requried' => true,
                        'is_batch' => false,
                    ),
                    array(
                        'type' => 'input',
                        'node-tree' => 'Product-StandardProductID-Type',
                        'value' => '',
                        'is_requried' => true,
                        'is_batch' => false,
                    ),
                );

                $tmpData['Condition'] = array(
                    'type' => 'select',
                    'option' => array('New', 'UsedLikeNew', 'UsedVeryGood', 'UsedGood', 'UsedAcceptable', 'CollectibleLikeNew', 'CollectibleVeryGood', 'CollectibleGood', 'CollectibleAcceptable', 'Refurbished', 'Club'),
                    'node-tree' => 'Product-Condition-ConditionType',
                    'is_requried' => false,
                    'is_batch' => false,
                    'value' => '',
                );

                $tmpData['Condition Note'] = array(
                    'type' => 'input',
                    'node-tree' => 'Product-Condition-ConditionNote',
                    'value' => '',
                    'is_requried' => false,
                    'is_batch' => true,
                );

                $tmpData['Standard Price'] = array(
                    'type' => 'input',
                    'node-tree' => 'Standard-Price',
                    'value' => '',
                    'is_requried' => true,
                    'is_batch' => true,
                );

                $tmpData['Sale Price'] = array(
                    'type' => 'input',
                    'node-tree' => 'Sale-Price',
                    'value' => '',
                    'is_requried' => false,
                    'is_batch' => true,
                );

                $tmpData['Sale Start Date'] = array(
                    'type' => 'date',
                    'node-tree' => 'Sale-Start-Date',
                    'value' => '',
                    'is_requried' => false,
                );

                $tmpData['Sale End Date'] = array(
                    'type' => 'date',
                    'node-tree' => 'Sale-End-Date',
                    'value' => '',
                    'is_requried' => false,
                    'is_batch' => true,
                );

                $tmpData['Quantity'] = array(
                    'type' => 'input',
                    'node-tree' => 'quantity',
                    'value' => '',
                    'is_requried' => true,
                    'is_batch' => true,
                );
                $data[] = $tmpData;
            }

        }

        $json['variant'] = $data;
        $json['variant_title'] = $title;
        $json['variant_option'] = $matchAttribute;
        return $json;
    }


    public function sitePairs()
    {

        return
            array(
                'US' => '美国站',
                'UK' => '英国站',
                'DE' => '德国站',
                'FR' => '法国站',
                'IT' => '意大利站',
                'AU' => '澳大利亚站',
                'CA' => '加拿大站',
                'JP' => '日本站',
            );

    }

    /**
     * 拿站点的大分类；
     * @param string $site
     * @return array
     */
    public function getSiteParentCategorys(string $site) : array
    {
        $siteList = array_keys(AmazonCategoryXsdConfig::$sites);
        if (!in_array($site, $siteList)) {
            return [];
        }
        $categoryModel = new AmazonCategory();
        $categorys = $categoryModel->where(['site' => $site, 'parent_id' => 0])->field('category_id,name')->select();
        $new = [];
        foreach ($categorys as $val) {
            $new[] = $val->toArray();
        }
        return $new;
    }

    /**
     * 拿帐号平台SKU的大分类；
     * @param $accountId
     * @param $sellerSku
     * @return array
     */
    public function getSellerSkuCategoryS($accountId, $sellerSku) : array
    {
        $data = AmazonListing::alias('l')
            ->join(['amazon_publish_product_detail' => 'd'], 'l.publish_detail_id=d.id')
            ->join(['amazon_publish_product' => 'p'], 'p.id=d.product_id')
            ->where(['l.seller_sku' => $sellerSku, 'l.account_id' => $accountId, 'd.publish_sku' => $sellerSku])
            ->field('p.recommend_node product_recommend_node,d.recommend_node detail_recommend_node,l.publish_detail_id,d.id,d.product_id')
            ->find();
        $node = [];
        if (empty($data)) {
            return [];
        }
        if (empty($node) && !empty($data['detail_recommend_node'])) {
            $node = explode(',', trim($data['detail_recommend_node']));
        }
        if (empty($node) && !empty($data['product_recommend_node'])) {
            $node = explode(',', trim($data['product_recommend_node']));
        }
        if (empty($node) || empty($node[0])) {
            return [];
        }
        $categoryIds = [];
        $categorys = AmazonCategory::where(['category_id' => ['in', $node]])->field('path_id')->select();
        foreach ($categorys as $val) {
            $tmp = explode(',', $val['path_id']);
            if (!empty($tmp[1]) && !in_array($tmp[1], $categoryIds)) {
                $categoryIds[] = $tmp[1];
            }
        }
        return $categoryIds;
    }
}
