<?php
namespace app\publish\service;

use app\common\model\Currency;
use app\common\model\Goods;
use app\common\cache\Cache;
use app\common\model\GoodsSku;
use app\common\model\GoodsSourceUrl;
use app\common\model\Channel;
use app\common\model\GoodsAttribute;
use app\common\model\GoodsLang;
use app\common\model\Category;
use app\goods\service\Goodsdev;
use think\Exception;
use think\Db;
use app\common\model\GoodsDevelopLog;
use app\common\model\GoodsSkuAlias;
use app\purchase\service\SupplierService;
use app\common\model\Attribute;
use app\goods\service\GoodsImage as GoodsImageService;
use app\common\model\GoodsGallery;
 
/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/12/6
 * Time: 18:14
 */

class GoodsHelp
{   
    // 产品sku属性规则
    private $sku_rules = [
        'color'         => ['default' => 'ZZ', 'length' => 2],
        'size'          => ['default' => 0, 'length' => 1],
        'style'         => ['default' => 'Z', 'length' => 1],
        'specification' => ['default' => 'Z', 'length' =>1]
    ];
    
    // sku状态
    private $sku_status = [
        0 => '未上架',
        1 => '上架',
        2 => '下架'        
    ];
    
    // 平台销售状态
    private $platform_sale_status = [
        0 => '可选上架',
        1 => '必须上架',
        2 => '禁止上架',
        3 => '下架'      
    ];
    
    // 出售状态
    private $sales_status = [
            0 => '未出售', 
            1 => '出售',
            2 => '停售'
    ];
    
    // 物流属性
    private $transport_properties = [
        'general'           => ['name' => '普货',      'field' => 'general',           'value' => 0x1, 'exclusion' => 0xfffffffe],
        'builtinbattery'    => ['name' => '带内置电池', 'field' => 'builtinbattery',    'value' => 0x2, 'exclusion' => 0xfd],
        'externalbattery'   => ['name' => '带外置电池', 'field' => 'externalbattery',   'value' => 0x4, 'exclusion' => 0xfb],
        'detachablebattery' => ['name' => '可拆卸电池', 'field' => 'detachablebattery', 'value' => 0x8, 'exclusion' => 0xf7],
        'mobilepower'       => ['name' => '移动电源',   'field' => 'mobilepower',       'value' => 0x10, 'exclusion' => 0xef],
        'buttonbattery'     => ['name' => '带纽扣电池', 'field' => 'buttonbattery',     'value' => 0x20, 'exclusion' => 0xdf],
        'highpowerbattery'  => ['name' => '大功率电池', 'field' => 'highpowerbattery',  'value' => 0x40, 'exclusion' => 0xbf],
        'purebattery'       => ['name' => '纯电池',    'field' => 'purebattery',       'value' => 0x80, 'exclusion' => 0x7f],
        'withmagnetic'      => ['name' => '带磁',      'field' => 'withmagnetic',      'value' => 0x100, 'exclusion' => 0x1],
        'liquid'            => ['name' => '液体',      'field' => 'liquid',            'value' => 0x200, 'exclusion' => 0x401 ],
        'pastesolid'        => ['name' => '膏状固体',   'field' => 'pastesolid',        'value' => 0x400, 'exclusion' => 0x201],
        'sharp'             => ['name' => '尖锐物品',   'field' => 'sharp',             'value' => 0x800, 'exclusion' => 0x1]
    ];
    
    /** 获取产品信息
     * @param array $condition
     * @return array
     */
    public static function getGoods($condition)
    {
        $where = '1=1';
        $page = isset($condition['page']) ? $condition['page'] : 1;
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 10;
        $where = [];
        if(isset($condition['category_id']) && !empty($condition['category_id'])){
            $category_list = Cache::store('category')->getCategoryTree();
            if($category_list[$condition['category_id']]){
                $child_ids = $category_list[$condition['category_id']]['child_ids'];
                if(count($child_ids) > 1){
                    $where['a.category_id'] = ['in', $child_ids];
                }else{
                    $where['a.category_id'] = ['=', $condition['category_id']];
                }
            }
        }
        if(isset($condition['snType']) && isset($condition['snText']) && !empty($condition['snText'])){
            $snType = trim($condition['snType']);
            switch ($snType) {
                case 'sku':
                    $where['b.sku'] = ['like', '%' . trim($condition['snText']) . '%'];
                    break;
                case 'title':
                    $where['b.spu_name'] = ['like', '%' . trim($condition['snText']) . '%'];
                    break;
                default:
                    break;
            }
        }
        if(isset($condition['category_id']) && !empty($condition['category_id'])){
            $count = (new Goods())->alias('a')->field('a.category_id,b.id,b.goods_id,b.thumb,b.spu_name,b.sku, b.name,b.market_price,b.cost_price,b.sku_attributes')->join('goods_sku b','a.id = b.goods_id','right')->where($where)->count();
            $goodsList = (new Goods())->alias('a')->field('a.category_id,b.id,b.goods_id,b.thumb,b.spu_name,b.sku, b.name,b.market_price,b.cost_price,b.sku_attributes')->join('goods_sku b','a.id = b.goods_id','right')->where($where)->page($page,$pageSize)->select();
        }else{
            $count = (new GoodsSku())->alias('b')->field('b.id,b.goods_id,b.thumb,b.spu_name,b.sku, b.name,b.market_price,b.cost_price,b.sku_attributes')->where($where)->count();
            $goodsList = (new GoodsSku())->alias('b')->field('b.id,b.goods_id,b.thumb,b.spu_name,b.sku, b.name,b.market_price,b.cost_price,b.sku_attributes')->where($where)->page($page,$pageSize)->select();
        }
        $new_array = [];
        $goodsAttributesModel = new GoodsAttribute();
        $attributesModel = new Attribute();
        foreach($goodsList as $k => $v){
            $temp['id'] = $v['id'];
            $temp['goods_id'] = $v['goods_id'];
            $temp['spu_name'] = $v['spu_name'].' '.$v['name'];
            $temp['thumb'] = $v['thumb'];
            $temp['sku'] = $v['sku'];
            $temp['market_price'] = $v['market_price'];
            $temp['cost_price'] = $v['cost_price'];
            //转义
            $attributes = json_decode($v['sku_attributes'],true);
            foreach($attributes as $a => $aa){
                $goodsAttributes = $goodsAttributesModel->where(['value_id' => $aa])->find();
                if(!empty($goodsAttributes)){
                    $attributesInfo = $attributesModel->where(['id' => $goodsAttributes['attribute_id']])->find();
                    if(!empty($attributesInfo)){
                        $temp[$attributesInfo['code']] = $goodsAttributes['data'];
                    }
                }
            }
            array_push($new_array,$temp);
        }
        $result = [
            'data' => $new_array,
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
        ];
        return $result;
    }
    
    /**
     * 产生SPU
     * @param int $category_id 产品分类Id
     * @return string
     */
    public function generateSpu($category_id)
    {
        $category_lists = Cache::store('category')->getCategoryTree();
        if (!isset($category_lists[$category_id])) {
            return '';
        }
        $spu = $category_lists[$category_id]['code'];
        if (!($pid = $category_lists[$category_id]['pid']) || !isset($category_lists[$pid])) {
            return '';
        }
        $spu = $category_lists[$pid]['code'] . $spu;
        $category = Category::where(['id' => $category_id])->field('sequence')->find();
        if (!$category) {
            return '';
        }
        $sequence = $category['sequence'];
        $spu.= substr('0000', 0, 4-count(str_split(++$sequence))) . $sequence;
        return $spu;
    }
    
    /**
     * sku生成
     * @param string $spu 产品SPU
     * @param array $attributes 物品属性
     * @return string
     */
    public function generateSku($spu, $attributes, $number = 0)
    {
        $sku = $spu;
        foreach($this->sku_rules as $code => $rule) {
            $str = $rule['default'];
            foreach($attributes as $attribute) {
                if ($code == $attribute['code']) {
                    isset($attribute['value_code']) ? $str = $attribute['value_code'] : '';
                    break;
                }
            }
            $sku .= $str;
        }
        $sku .= substr('000', 0, 3 - strlen(strval(++$number))) . $number;
        return $sku;
    }
    
    /**
     * 产品添加
     * @param array $params
     * @param int $user_id
     * @throws Exception
     * @return int
     */
    public function add($params, $user_id)
    {
        // 产品标签
        if (isset($params['tags'])) {
            $tags = json_decode($params['tags'], true);
            $params['tags'] = '';
            foreach($tags as $tag) {
               $params['tags'].= ($params['tags'] ? ',' : '') . $tag['id'];
            }
        }
        if (!isset($params['description'])) {
            $params['description'] = '';
        }
        $params['type']         = 0;
        $params['status']       = 1;
        $params['sales_status'] = 0;
        // 资源链接
        if (isset($params['source_url'])) {
            $source_urls = json_decode($params['source_url'], true);
        }
        // 平台销售状态
        if (isset($params['platform_sale'])) {
            $lists = json_decode($params['platform_sale'], true);
            $platform_sales = [];
            foreach($lists as $list) {
                $platform_sales[$list['name']] = $list['value_id'];
            }
            $params['platform_sale'] = json_encode($platform_sales);
        } else{
            $params['platform_sale'] = json_encode([]);
        }
        // 产品物流属性
        if (isset($params['properties'])) {
            $properties = json_decode($params['properties'], true);
            $params['transport_property'] = $this->formatTransportProperty($properties);
            $this->checkTransportProperty($params['transport_property']);
        }
        // 产品验证
        $goodsValidate = Validate('goods');
        if (!$goodsValidate->check($params)) {
            throw new Exception($goodsValidate->getError());
        }
        $params['spu'] = $this->generateSpu($params['category_id']);
        // 开启事务
        Db::startTrans();
        try {
            $goods = new Goods();            
            $goods->allowField(true)->isUpdate(false)->save($params);
            if (isset($source_urls)) {
                $this->saveSourceUrls($goods->id, $source_urls, $user_id);
            }
            if ($params['spu']) {
                $sequence = intval(substr($params['spu'], -4));
                Category::where(['id' => $params['category_id']])->update(['sequence' => $sequence]);
            }
            // 提交事务
            Db::commit();
            return $goods->id;
        } catch (Exception $ex) {
            Db::rollBack();
            throw new Exception('添加失败 ' . $ex->getMessage());
        }       
    }
    
    /**
     * 产品搜索条件
     * @param array $params
     * @return array
     */
    public function getWhere($params)
    {         
        $where = ' g.status =1 ';
        $join  = [];
        if (isset($params['status']) && !empty($params['status'])) {
            $where.= ' and g.sales_status = ' . $params['status'];

        }else{
            // $where.= ' and g.sales_status in (1,2)';
        }
        
        if (isset($params['snType']) && isset($params['snText']) && !empty($params['snText'])) {
            switch ($params['snType']) {
                case 'name':
                    $where .= " and g.name like '%" . $params['snText'] . "%'";
                    break;
                case 'declareName':
                    $where .= " and g.declare_name like '%" . $params['snText'] . "%'";
                    break;
                case 'declareEnName':
                    $where .= " and g.declare_en_name like '%" . $params['snText'] . "%'";
                    break;
                case 'packingName':
                    $where .= " and g.packing_name like '%" . $params['snText'] . "%'";
                    break;
                case 'packingEnName':
                    $where .= " and g.packing_en_name like '%" . $params['snText'] . "%'";
                    break;
                case 'sku':
                    $join[] = [
                        'goods_sku gs', 'gs.goods_id=g.id'
                    ];
                    $where .=" and gs.sku like '%" .$params['snText'] ."%'";
                    break;
                case 'spu':
                    $where .= " and g.spu like '%" . $params['snText'] . "%'";
                    break;
                case 'alias':
                    $where .= " and g.alias like '%" . $params['snText'] . "%'";
                    break;
                default:
                    break;
            }
        }
        
        if (isset($params['sellTime']) && !empty($params['sellTime'])) {
            $is_date = strtotime($params['sellTime']) ? strtotime($params['sellTime']) : false;
            if (!$is_date) {
                return json(['message' => '日期格式错误'], 400);
            }
            $start = strtotime($params['sellTime']);
            $end = strtotime($params['sellTime'] . " 23:59:59");
            $where .= ' and g.publish_time <= ' . $end . ' and g.publish_time > ' . $start;
        }
       
        if (isset($params['stopTime']) && !empty($params['stopTime'])) {
            $is_date = strtotime($params['stopTime']) ? strtotime($params['stopTime']) : false;
            if (!$is_date) {
                return json(['message' => '日期格式错误'], 400);
            }
            $start = strtotime($params['stopTime']);
            $end = strtotime($params['stopTime'] . " 23:59:59");
            $where .= ' and g.stop_selling_time <= ' . $end . ' and g.stop_selling_time > ' . $start;
        }
        
        $wheres['where'] = $where;
        $wheres['join']  = $join;
        return $wheres;
    }
    
    /**
     * 查询产品总数
     * @param array $wheres
     * @return int
     */
    public function getCount($wheres)
    {   
        return !empty($wheres['join']) ? Goods::alias('g')->join($wheres['join'])->where($wheres['where'])->count('distinct(g.id)') : Goods::alias('g')->where($wheres['where'])->count();
    }
    
    /**
     * 查询产品列表
     * @param array $wheres
     * @param string $fields
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function getList($wheres, $fields = '*', $page = 1, $pageSize = 20, $domain = '')
    {   
        $goodsModel = new Goods();
        if (empty($wheres['join'])) {
            $goods_data = $goodsModel->alias('g')->order(['g.id' => 'desc'])->field($fields)->where($wheres['where'])->page($page,$pageSize)->select();
        } else {
            $goods_data = $goodsModel->alias('g')->order(['g.id' => 'desc'])->join($wheres['join'])->field($fields)->where($wheres['where'])->page($page,$pageSize)->select();    
        }
        $new_array = [];
        foreach ($goods_data as $k => $v) {
            $new_array[$k] = $v;
            $new_array[$k]['thumb'] = empty($v['thumb']) ? '' : $domain . '/' . $v['thumb'];
            $new_array[$k]['category'] = $this->mapCategory($v['category_id']);
            $new_array[$k]['status'] = $v['sales_status'];
            $new_array[$k]['sales'] = $goodsModel->getSalesInfo($v['type']);
            // $new_array[$k]['publish_time'] = $v['publish_time'] ? date('Y-m-d H:i:s', $new_array[$k]['publish_time']) : '';
            // $new_array[$k]['stop_selling_time'] = $v['stop_selling_time'] ? date('Y-m-d H:i:s', $new_array[$k]['stop_selling_time']) : '';
            unset($new_array[$k]['sales_status']);
            unset($new_array[$k]['type']);
            unset($new_array[$k]['category_id']);
        }
        
        return $new_array;
    }
    
    /**
     * 获取产品sku列表
     * @param int $goods_id
     * @return array
     */
    public function getGoodsSkus($goods_id)
    {
        $sku_data = GoodsSku::field("sku,retail_price,market_price,sku_attributes,status,thumb")->where(['goods_id' => $goods_id])->select();
        $new_sku = [];
        foreach ($sku_data as $key => $value) {
            $new_sku[$key] = $value;
            $new_sku[$key]['thumb'] = "";
            $new_sku[$key]['status'] = isset($this->sku_status[$value['status']]) ? $this->sku_status[$value['status']] : '';
        }
        return $new_sku;
    }
    
    /**
     * 匹配多级分类名称
     * @param int $category_id
     * @return string
     */
    public function mapCategory($category_id)
    {
        static $result = [];        
        if (!isset($result[$category_id])) {
            $category_list = Cache::store('category')->getCategoryTree();
            $name_path = "";
            $loop_category_id = $category_id;
            while($loop_category_id) {
                if (!isset($category_list[$loop_category_id])) {
                    break;
                }
                $name_path = $name_path ? $category_list[$loop_category_id]['title'] . '>' . $name_path : $category_list[$loop_category_id]['title'];
                $parent = $category_list[$loop_category_id]['parents'];
                $loop_category_id = empty($parent) ? 0 : $parent[0];
            }
            $result[$category_id] = $name_path;    
        }
        
        return $result[$category_id];
    }
    
    /**
     * 获取产品基础信息
     * @param int $goods_id 产品id
     * @return array
     */
    public function getBaseInfo($goods_id) 
    {
        $fields = 'channel_id,source_url,id,category_id,name,spu,declare_name,declare_en_name,retail_price,cost_price,weight,width,height,depth,sales_status,volume_weight,packing_id,unit_id,thumb,alias,hs_code,process_id,is_packing,brand_id,tort_id,tags,warehouse_id,is_multi_warehouse,platform_sale,transport_property';
        $result = Goods::where(['id' => $goods_id])->field($fields)->find();
        if ($result)
        {
            $currency=(new Currency())->where('code','=','USD')->find();
            $result['category'] = $this->mapCategory($result['category_id']);
            $result['package']  = $result['packing_id'] ? $this->getPackageById($result['packing_id']) : '';
            $result['unit']     = $result['unit_id'] ? $this->getUnitById($result['unit_id']) : '';
            $result['tort']     = $result['tort_id'] ? $this->getTortById($result['tort_id']) : '';
            $result['brand']    = $result['brand_id'] ? $this->getBrandById($result['brand_id']) : '';
            //$result['tags']     = $result['tags'] ? $this->getTags($result['tags']) : [];
            $result['tags'] = $this->getEnglishTags($goods_id);
            $result['warehouse']= $result['warehouse_id'] ? $this->getWarehouseById($result['warehouse_id']) : '';
            $result['warehouse_type'] = $result['warehouse_id'] ? $this->getWarehouseTypeById($result['warehouse_id']) : '';
            $result['platform_sale'] = $this->resolvePlatformSale($result['platform_sale']);

            $result['source_url']    = strpos($result['source_url'],'http')?$result['source_url']:'';
            $result['properties'] = $this->getProTransProperties($result['transport_property']);

            $result['transport_property']=(new \app\goods\service\GoodsHelp())->getProTransPropertiesTxt($result['transport_property']);//物流属性转文本-pan
        }       
        return $result;
    }


    public function getEnglishTags($goods_id)
    {
        $result = [];
        $aLang = GoodsLang::where('goods_id', $goods_id)
            ->where('lang_id', 2)
            ->find();
        if($aLang){
            $result =  $aLang['tags']?explode('\n',$aLang['tags']):[];
        }
        return $result;
    }
    
    
    /**
     * 更新产品基础信息
     * @param int $goods_id
     * @param array $data
     * @param int $user_id
     * @throws Exception
     * @return int
     */
    public function updateBaseInfo($goods_id, $data, $user_id)
    {   
        $fields = 'category_id, spu, name, packing_en_name, packing_name, declare_name, declare_en_name, thumb, tags, sort, alias, unit_id'
                .', weight,width, height,depth,volume_weight,packing_id,cost_price, retail_price, hs_code, is_packing,tags,brand_id,tort_id,warehouse_id,is_multi_warehouse,platform_sale,transport_property';
        $goods_info = Goods::where(['id' => $goods_id])->field($fields)->find()->toArray();
        if (!$goods_info) {
            throw new Exception('产品不存在', 101);
        }
        if (isset($data['tags'])) {
            $tags = json_decode($data['tags'], true);
            $tag_string = '';
            foreach($tags as $tag) {
                $tag_string .= ($tag_string ? ',' : '') . $tag['id'];
            }
            $data['tags'] = $tag_string;
        }
        
        if (isset($data['platform_sale'])) {
            $lists = json_decode($data['platform_sale'], true);
            $platform_sales = [];
            foreach($lists as $list) {
                $platform_sales[$list['name']] = $list['value_id'];
            }
            $data['platform_sale'] = json_encode($platform_sales);
        }
        
        if (isset($data['source_url'])) {
            $data['source_url'] = json_decode($data['source_url'], true);
            $original_url = $this->getSourceUrls($goods_id);
            if (!array_diff($data['source_url'],$original_url) && !array_diff($original_url, $data['source_url'])) {
                unset($data['source_url']);
            }
        }
        
        if (isset($data['properties'])) {
            $properties = json_decode($data['properties'], true);
            $data['transport_property'] = $this->formatTransportProperty($properties);
            $this->checkTransportProperty($data['transport_property']);
        }
        
        $diff = array_intersect(array_keys($goods_info), array_keys($data));
        $update = [];
        foreach($diff as $key) {
            if ($goods_info[$key] != $data[$key]) {
                $update[$key] = $data[$key];
            }
        }
        if (empty($update)&&!isset($data['source_url'])) {
            throw new Exception('没有更新的字段', 102);
        }
        
        // 开启事务
        Db::startTrans();
        try {
            $update['update_time'] = time();
            Goods::where('id', $goods_id)->update($update);
            if (isset($data['source_url'])) {
                $this->saveSourceUrls($goods_id, $data['source_url'], $user_id);
            }
            // 提交事务
            Db::commit();
            Cache::handler()->hdel('cache:Goods', $goods_id);
        } catch (Exception $ex) {
            Db::rollBack();
            throw new Exception('更新失败');
        }
        
        return 0;
    }
    
    /**
     * 获取仓库名称
     * @param int $id
     * @return string
     */
    public function getWarehouseById($id)
    {
        $info = Cache::store('warehouse')->getWarehouse($id);
        return isset($info['name']) ? $info['name'] : '';
    }
    
    /**
     * 获取仓库名称
     * @param int $id
     * @return string
     */
    public function getWarehouseTypeById($id)
    {
        $info = Cache::store('warehouse')->getWarehouse($id);
        return isset($info['type']) ? $info['type'] : '';
    }
    
    /**
     * 解析平台销售状态
     * @param string $platform_sale
     * @return array
     */
    public function resolvePlatformSale($platform_sale)
    {   
        $platform_sales = json_decode($platform_sale, true);
        $lists = Channel::where(['status' => 0])->field('id,name,title')->select();
        foreach($lists as &$list) {
            $list['value_id'] = isset($platform_sales[$list['name']]) ? $platform_sales[$list['name']] : 0;
            $list['value']    = isset($this->platform_sale_status[$list['value_id']]) ? $this->platform_sale_status[$list['value_id']] : '';
        }
        
        return $lists;
    }
    
    /**
     * 获取平台状态列表
     * @return array
     */
    public function getPlatformSaleStatus()
    {
        $lists = [];
        foreach($this->platform_sale_status as $k => $list) {
            $lists[] = [
                'id'   => $k,
                'name' => $list
            ];
        }
        
        return $lists;
    }
    
    /**
     * 获取产品参考地址
     * @param int $goods_id
     * @return array
     */
    private function getSourceUrls($goods_id)
    {
        $results = [];
        $lists = GoodsSourceUrl::where(['goods_id' => $goods_id])->select();
        foreach($lists as $list) {
            $results[] = $list['source_url'];
        }
        return $results;
    }
    
    /**
     * 保存产品参考地址
     * @param int $goods_id
     * @param array $urls
     * @param int $user_id
     */
    public function saveSourceUrls($goods_id, $urls, $user_id)
    {
        $goodsSourceUrl = new GoodsSourceUrl();
        $goodsSourceUrl->where(['goods_id' => $goods_id])->delete();
        $results = [];
        foreach($urls as $source_url) {
            $results[] = [
                'goods_id'    => $goods_id,
                'source_url'  => $source_url,
                'create_time' => time(),
                'create_id'   => $user_id
            ];
        }
        $results ? $goodsSourceUrl->allowField(true)->saveAll($results) : '';
    }
    
    /**
     * 获取产品规格参数
     * @param int $goods_id 产品ID
     * @param int $filter_sku 若为0 未取用产品属性，1为规格参数，2 全部属性
     * @param int $edit 是否为编辑属性
     * @return array
     */
    public function getAttributeInfo($goods_id, $filter_sku = 2, $edit = 1)
    {   
        $result = [];
        do {
            $goods = Goods::where(['id' => $goods_id])->field('category_id')->find();
            if (empty($goods) || !$goods->category_id) {
                break;
            }
            // 获取产品属性
            $goodsAttributes = GoodsAttribute::where(['goods_id' => $goods_id])->select();
            $goods_attributes = [];
            foreach($goodsAttributes as $attribute) {
                $goods_attributes[$attribute['attribute_id']]['attribute_value'][] = $attribute->toArray();
            }
            unset($goodsAttributes);
            $result = $this->matchCateAttribute($goods->category_id, $filter_sku, $edit, $goods_attributes);
        } while(false);
        return $result;
    }
    
    /**
     * 获取分类属性及值
     * type 0 单选 1 多选 2 输入文本
     * @param int   $category_id 分类id
     * @param int   $filter_sku 过滤sku, 当值为2 全部属性, 1 选择参与sku规则制定属性, 0 不选择参与sku规则制定属性
     * @return array
     */
    public function getCategoryAttribute($category_id, $filter_sku = 2)
    {
        $cate_attributes = Cache::store('category')->getAttribute($category_id);
        $result = [];
        if (empty($cate_attributes)) {
            return $result;
        }
        foreach($cate_attributes['group'] as $group) {
            foreach($group['attributes'] as &$attribute) {
                // 属性详情解析
                $attribute_info = Cache::store('attribute')->getAttribute($attribute['attribute_id']);
                if (2 != $filter_sku && $filter_sku != $attribute['sku']) {
                    continue;
                }
                $attribute['type']       = $attribute_info['type'];
                $attribute['name']       = $attribute_info['name'];
                $attribute['code']       = $attribute_info['code'];
                $attribute['group_id']   = $group['group_id'];
                $attribute['group_sort'] = $group['sort'];
                $attribute['group_name'] = $group['name'];
                if ( 2 == $attribute['type']) {
                    $attribute['attribute_value'] = '';
                } elseif (empty($attribute['attribute_value'])) {
                    foreach($attribute_info['value'] as $v) {
                        $v['selected'] = false;
                        $attribute['attribute_value'][$v['id']] = $v;
                    }                 
                } else {
                    $values = [];
                    foreach($attribute['attribute_value'] as $attribute_value_id) {
                        if (isset($attribute_info['value'][$attribute_value_id])) {
                            $values[$attribute_value_id] = $attribute_info['value'][$attribute_value_id];
                            $values[$attribute_value_id]['selected'] = false;
                        }
                    }
                    $attribute['attribute_value'] = $values;
                }
                $result[] = $attribute;
            }
        }
        return $result;
    }
    
    /**
     * 匹配分类属性
     * @param int $category_id
     * @param int $filter_sku
     * @param int $edit
     * @param array $goods_attributes
     * @return array
     */
    public function matchCateAttribute($category_id, $filter_sku, $edit, $goods_attributes)
    {
        // 获取分类属性
        $category_attributes = $this->getCategoryAttribute($category_id, $filter_sku);    
        // 分类属性是否为已选
        $result = [];
        foreach($category_attributes as $k => $attribute) {
            if ($attribute['type'] == 2) {
                $value = $this->getAttributeValueByIdAndValueId($goods_attributes, $attribute['attribute_id'], 0);
                //  $value === 0 说明不存在此属性
                if ($value === 0 && $edit == 0) {
                    unset($category_attributes[$k]);
                } elseif ($value === 0 && $edit == 1) {
                    $attribute['attribute_value'] = '';
                    $attribute['enabled'] = false;
                } else {
                    $attribute['attribute_value'] = $value;
                    $attribute['enabled'] = true;
                }
                $result[] = $attribute;
                continue;
            }

            $attribute['enabled'] = true;
            foreach($attribute['attribute_value'] as $ke => &$attribute_value) {
                if ($this->getAttributeValueByIdAndValueId($goods_attributes, $attribute['attribute_id'], $attribute_value['id']) !== 0) {
                    $attribute_value['selected'] = true;                 
                } else {
                    if ($edit == 1) {
                        $attribute_value['selected'] = false;
                    } else {
                        unset($attribute['attribute_value'][$ke]);
                    }
                }
            }

            if ($edit == 1 && !isset($goods_attributes[$attribute['attribute_id']])) {
                $attribute['enabled'] = false;
            } else if ($edit == 0 && !isset($goods_attributes[$attribute['attribute_id']])){
                continue;
            }
            $attribute['attribute_value'] = array_values($attribute['attribute_value']);
            $result[] = $attribute;
        }
        unset($category_attributes);    
        if (0 == $filter_sku) {
            $groups = [];
            foreach($result as $list) {
                if (in_array($list['group_id'], $groups)) {
                    $groups[$list['group_id']]['attributes'][] = $list;
                } else {
                    $groups[$list['group_id']]['group_name'] = $list['group_name'];
                    $groups[$list['group_id']]['group_sort'] = $list['group_sort'];
                    $groups[$list['group_id']]['group_id'] = $list['group_id'];
                    $groups[$list['group_id']]['attributes'][] = $list;
                }
            }
            $result = array_values($groups);
        }
        
        return $result;
    }
    
    /**
     * 更新产品规格参数
     * @param int $goods_id
     * @param array $attributes
     * @param int $filter_sku
     * @return int
     * @throws Exception
     */
    public function modifyAttribute($goods_id, $attributes, $filter_sku = 2)
    {
        $goods_info = Goods::where(['id' => $goods_id])->field('category_id')->find();
        if (empty($goods_info) || empty($goods_info['category_id'])) {
            throw new Exception('产品不存在或产品分类不存在', 101);
        }
        
        //启动事务
        Db::startTrans();
        try {
            $results = [];
            $this->checkGoodsAttributes($filter_sku, $goods_id, $attributes, $results, $goods_info['category_id']);
            foreach($attributes as $attribute) {
                $goodsAttrModel = new GoodsAttribute();
                if ($attribute['type'] == 2) {
                    if ($goodsAttrModel->check(['attribute_id' => $attribute['attribute_id'], 'goods_id' => $goods_id])) {
                        $goodsAttrModel->allowField(true)->isUpdate(true)->where(['attribute_id' => $attribute['attribute_id'], 'goods_id' => $goods_id])->update(['data' => $attribute['attribute_value']]);
                    } else {
                        $goodsAttrModel->allowField(true)->save(['attribute_id' => $attribute['attribute_id'], 'goods_id' => $goods_id, 'value_id' => 0, 'data' => $attribute['attribute_value']]);
                    }
                } else {
                    $goodsAttrModel->where(['attribute_id' => $attribute['attribute_id'], 'goods_id' => $goods_id])->delete();
                    $infos = [];
                    foreach($attribute['attribute_value'] as $value) {
                        $is_qc = isset($results[$attribute['attribute_id']]) ? $results[$attribute['attribute_id']]['is_qc'] : 0;
                        $infos[] = ['attribute_id' => $attribute['attribute_id'], 'goods_id' => $goods_id, 'value_id' => $value, 'data' => '', 'is_qc' => $is_qc];
                    }                       
                    $infos ? $goodsAttrModel->allowField(true)->saveAll($infos) : '';
                }
                unset($results[$attribute['attribute_id']]);
            }
            // 产品属性更新时删除操作
            if (0 == $filter_sku && !empty($results)) {
                foreach($results as $info) {
                    $goodsAttrModel = new GoodsAttribute();
                    $goodsAttrModel->where(['attribute_id' => $info['attribute_id'], 'goods_id' => $goods_id])->delete();
                }
            }
            Db::commit();
            return ['message' =>  '修改成功'];
        } catch ( Exception $e) {
            Db::rollback();
            throw new Exception('修改失败' . $e->getMessage(), 103);
        }
        
        return 0;
    }
    
    
    /**
     * 获取属性值根据值
     * @param array $goods_attributes  产品属性数组
     * @param int $attribute_id        属性Id
     * @param int $attribute_value_id  属性值Id
     * @return int|string
     */
    public function getAttributeValueByIdAndValueId(&$goods_attributes, $attribute_id, $attribute_value_id) {
        /*foreach($goods_attributes as $attribute) {
            if ($attribute['attribute_id'] == $attribute_id && $attribute_value_id == $attribute['value_id']) {
                return $attribute_value_id ?: $attribute['data'];
            }
        }*/
        if (!isset($goods_attributes[$attribute_id])) {
            return 0;
        }
        foreach($goods_attributes[$attribute_id]['attribute_value'] as $value) {
            if ($attribute_value_id == $value['value_id']) {
                return $attribute_value_id ?: $value['data'];
            }
        }
        return 0;
    }
    
    /**
     * 格式化属性数组
     * @param array $attributes
     * @return array
     */
    public function formatAttribute($attributes)
    {   
        $lists = [];
        $attributes = json_decode($attributes, true);
        foreach($attributes as $attribute) {
            $list['attribute_id'] = $attribute['attribute_id'];
            $list['type']         = $attribute['type'];
            isset($attribute['required']) ? $list['required'] = $attribute['required'] : '';
            isset($attribute['sku']) ? $list['sku'] = $attribute['sku'] : '';
            isset($attribute['gallery']) ? $list['gallery'] = $attribute['gallery'] : '';
            if (2 == $list['type']) {
                $list['attribute_value'] = $attribute['attribute_value'];
            } else {
                $list['attribute_value'] = [];
                foreach($attribute['attribute_value'] as $value) {
                    $list['attribute_value'][] = $value;
                }
            }
            $lists[] = $list;
            $list = null;
        }
        return $lists;
    }
    
    /**
     * 检测产品属性能否删除
     * @param int $filter_sku
     * @param int $goods_id
     * @param array $attributes
     * @throws Exception
     */
    private function checkGoodsAttributes($filter_sku, $goods_id, &$attributes, &$results, $category_id) 
    {
        $message = ''; 
        // 分类属性
        $categoryAttributes = $this->getCategoryAttribute($category_id, $filter_sku);
        $base_attributes = [];
        foreach($categoryAttributes as $val) {
            $base_attributes[$val['attribute_id']] = $val;
        }
        $base_info = $base_attributes;
        // 检查属性及属性值
        foreach($attributes as $attribute) {
            if (!isset($base_attributes[$attribute['attribute_id']])) {
                $message .= ' 属性Id为' . $attribute['attribute_id'] . '不存在分类中 ';
                break;
            }
            if ($attribute['type'] != $base_attributes[$attribute['attribute_id']]['type']) {
                $message .= ' 属性'. $base_attributes[$attribute['attribute_id']]['name'] . '类型不对 ';
                break;
            }
            if (empty($attribute['attribute_value'])) {
                $message .= ' 属性'. $base_attributes[$attribute['attribute_id']]['name'] . '值不能为空 ';
            }
            // 属性为单选且不参与sku计算 进行单选
            if($attribute['type'] == 0 && $base_attributes[$attribute['attribute_id']]['sku'] == 0 && count($attribute['attribute_value']) > 1) {
                $message .=  ' '.$base_attributes[$attribute['attribute_id']]['name'] . '为单选项不能多选';
            }
            if ($attribute['type'] != 2) {
                foreach($attribute['attribute_value'] as $value) {
                    !isset($base_attributes[$attribute['attribute_id']]['attribute_value'][$value]) ? ($message .= $base_attributes[$attribute['attribute_id']]['name'] . '属性值Id' . $value . '不存在 ') : '';
                }
            }
            
            if ($message) {
                break;
            }            
            unset($base_attributes[$attribute['attribute_id']]);
            unset($attribute);
        }
        // 检测属性必选项
        if (!$message && !empty($base_attributes)) {
            foreach($base_attributes as $attribute) {
                $attribute['required'] == 1 ? $message .= ' 属性为'.$attribute['name'] . '是必选项 ' : '';
                unset($attribute);
            }
        }
        
        if ($message) {
            throw new Exception($message, 102);
        }
        
        
        $lists = GoodsAttribute::where(['goods_id' => $goods_id])->select();
        foreach($lists as $list) {
            if (!in_array($list['attribute_id'], array_keys($base_info))) {
                continue;
            }
            if (isset($results[$list['attribute_id']])) {
                $results[$list['attribute_id']]['attribute_value'][] = $list['value_id'];
            } else {
                $results[$list['attribute_id']] = [
                    'attribute_id' => $list['attribute_id'],
                    'is_qc'        => $list['is_qc']
                ];
                $list['value_id'] != 0 ? ($results[$list['attribute_id']]['attribute_value'][] = $list['value_id']) : '';
            }
        }
        
        if (1 == $filter_sku) {
            foreach($attributes as $attribute) {
                $attribute_info = Cache::store('attribute')->getAttribute($attribute['attribute_id']);
                if (!isset($results[$attribute['attribute_id']]) || !isset($results[$attribute['attribute_id']]['attribute_value'])) {
                    continue;
                }
                $diff = array_diff($results[$attribute['attribute_id']]['attribute_value'], $attribute['attribute_value']);
                if (!$diff) {
                    continue;
                }
                
                foreach($diff as $value_id) {
                    $where = ' `goods_id` = '. $goods_id . '  AND sku_attributes->"$.attr_'. $attribute['attribute_id'] . '" = '. $value_id;
                    $count = GoodsSku::where($where)->count();
                    if (empty($count)) {
                        continue;
                    }                        
                    $message .= isset($attribute_info['name']) ? ' ' . $attribute_info['name'] . '的值'.$attribute_info['value'][$value_id]['value'] . '正在使用中' : '此属性不存在';
                }
            }
        }
        
        if ($message) {
            throw new Exception($message);
        }
    }
    
    /**
     * 获取产品关联供应商信息
     * @param  int $goods_id
     * @return array
     */
    public function getSupplierInfo($goods_id)
    {
        $supplierService = new SupplierService();
        $lists = $supplierService->supplierInfo($goods_id);
        return $lists;
    }
    
    /**
     * 获取产品描述信息
     * @praam int $goods_id 产品Id
     * @param int $lang_id  语言Id
     * @return array
     */
    public function getProductDescription($goods_id, $lang_id = 0)
    {   
        $where = 'goods_id =' . $goods_id;
        if (0 == $lang_id) {

        } elseif (1 == $lang_id) {
            $where .= ' AND lang_id = ' . $lang_id;
        } else {
            $where .= ' AND lang_id in (1, '. $lang_id . ')';
        }
        $lists = GoodsLang::where($where)->select();
        
        foreach($lists as &$list) {
            $list['lang_name'] = $this->getLangName($list['lang_id']);           
        }
        if (!$lists) {
            $lists[] = [
                'lang_id'   => 1,
                'lang_name' => '中文',
                'description'   => '',
                'goods_id'  => $goods_id
            ];
        }
        return $lists;
    }
    
    /**
     * 获取语言名字
     * @param int $lang_id
     * @return array
     */
    private function getLangName($lang_id)
    {
        $lists = Cache::store('lang')->getLang();
        $name = '';
        foreach($lists as $list) {
            if ($list['id'] == $lang_id) {
                $name = $list['name'];
                break;
            }
        }
        
        return $name;
    }
    
    /**
     * 更新产品描述
     * @param int $goods_id
     * @param array $data
     * @throws Exception
     * @return array
     */
    public function modifyProductDescription($goods_id, $data)
    {           
        // 开始事务
        Db::startTrans();
        try {
            foreach($data as $list) {
                $goodsLang = new GoodsLang();
                if ($goodsLang->check(['goods_id' => $goods_id, 'lang_id' => $list['lang_id']])) {
                   $goodsLang->allowField(true)->where(['goods_id' => $goods_id, 'lang_id' => $list['lang_id']])->update(['description' => $list['description']]);
                } else {
                   $goodsLang->allowField(true)->save(['goods_id' => $goods_id, 'lang_id' => $list['lang_id'], 'description' => $list['description']]);
                }
            }
            Db::commit();
            return ['message' => '更新成功'];
        } catch(Exception $e) {
           Db::rollBack();
           throw new Exception('更新失败');exit;
        }
    }
    
    /**
     * 格式化产品描述
     * @param array $descriptions
     * @return array
     */
    public function formatDescription($descriptions)
    {
        $results = [];
        $descriptions = json_decode($descriptions);
        foreach($descriptions as $description) {
            $list['lang_id'] = $description->lang_id;
            $list['description'] = $description->description;
            $results[] = $list;
        }
        return $results;
    }
    
    /**
     * 获取产品包装名称
     * @param int $id
     * @return string
     */
    private function getPackageById($id)
    {
        $lists = Cache::store('packing')->getPacking();
        foreach($lists as $list) {
            if ($list['id'] == $id) {
                return $list['name'];
            }
        }
        return '';
    }
    
    /**
     * 获取产品包装名称
     * @param int $id
     * @return string
     */
    private function getUnitById($id)
    {
        $lists = Cache::store('unit')->getUnit();
        foreach($lists as $list) {
            if ($list['id'] == $id) {
                return $list['name'];
            }
        }
        return '';
    }
    
    /**
     * 获取标签
     * @param int $tag
     * @return array
     */
    private function getTags($tag)
    {   
        $result = [];
        $tags = explode(',', $tag);
        $lists = Cache::store('tag')->getTag();
        foreach($tags as $tag_id) {
            foreach($lists as $list) {
                if ($list['id'] == $tag_id) {
                    $result[] = $list;
                }
            }
        }
        return $result;
    }
    
    /**
     * 获取产品品牌
     * @param int $id
     * @return string
     */
    public function getBrandById($id)
    {
        $lists = Cache::store('brand')->getBrand();
        foreach($lists as $list) {
            if ($list['id'] == $id) {
                return $list['name'];
            }
        }
        return '';
    }
    
    /**
     * 获取产品品牌风险
     * @param int $id
     * @return string
     */
    private function getTortById($id)
    {
        $lists = Cache::store('brand')->getTort();
        foreach($lists as $list) {
            if ($list['id'] == $id) {
                return $list['name'];
            }
        }
        return '';
    }
    
    /**
     * 获取出售信息
     * @return array
     */
    public function getSalesStatus()
    {
        $lists = [];
        foreach($this->sales_status as $k => $list) {
            $lists[] = [
                'id'   => $k,
                'name' => $list
            ];
        }
        
        return $lists;
    }
    
    /**
     * 获取产品物流属性
     * @param int $property
     * @return array
     */
    public function getProTransProperties($property)
    {
        $results = $this->getTransportProperies();
        foreach($results as &$list) {
            $list['enabled'] = $list['value'] & $property ? true : false;
        }
        
        return $results;
    }
    
    /**
     * 格式化物流属性
     * @param array $properties
     * @return int 
     */
    public function formatTransportProperty($properties)
    {
        $transport_property = 0;
        foreach($properties as $property) {
            $transport_property += isset($this->transport_properties[$property['field']]) ? $this->transport_properties[$property['field']]['value'] : 0;
        }  
        
        return $transport_property;
    }
    
    /**
     * 检查产品物流属性
     * @param int $transport_property
     */
    public function checkTransportProperty($transport_property)
    {
        foreach($this->transport_properties as $property) {
            if (($transport_property & $property['value']) && ($property['exclusion'] & $transport_property)) {
                throw new Exception('存在与' . $property['name'] . '相排斥的物流属性');
            }
            if ($transport_property == 1) {
                break;
            }
        }
    }
    
    /**
     * 获取物流属性列表
     * @return array
     */
    public function getTransportProperies()
    {
        $results = [];
        foreach($this->transport_properties as $property) {
            $property['enabled'] = false;
            $results[] = $property;
        }
        
        return $results;
    }
    
    /**
     * 获取用户名 根据id 
     * @param int $id
     * @return string
     */
    private function getUserNameById($id)
    {
        if (!$id) {
            return '';
        }
        static $users = [];
        if (!isset($users[$id])) {
            $userInfo = Cache::store('user')->getOneUser($id);
            if (!$userInfo) {
                $users[$id] = '';
            }
            $users[$id] = $userInfo['realname'];
        }
        
        return $users[$id];
    }
    
    /**
     * 获取产品日志列表
     * @param type $goods_id 产品Id
     * @return array
     */
    public function getLog($goods_id)
    {   
        $lists = GoodsDevelopLog::where(['goods_id' => $goods_id])->select();
        $goodsdev = new Goodsdev();
        foreach($lists as &$list) {
            // $list['create_time'] = $list['create_time'] ? date('Y-m-d H:i:s', $list['create_time']) : '';
            $list['operator']    = $this->getUserNameById($list['operator_id']);
            $list['process']     = $goodsdev->getProcessBtnNameById($list['process_id']);
            unset($list['operator_id'], $list['process_id'], $list['id']);
        }
        return $lists;
    }
    
    /**
     * 添加日志
     * @param int $goods_id
     * @param string $remark
     */
    public function addLog($goods_id, $remark)
    {
        $log['remark']      = $remark;
        $log['goods_id']    = $goods_id;
        $log['create_time'] = time();
        $log['process_id']  = 0;
        $log['operator_id'] = 1;
        $goodsDevLog = new GoodsDevelopLog();
        $goodsDevLog->allowField(true)->save($log);
    }
    
    /**
     * 获取产品sku信息列表
     * @param int $goods_id
     * @return array
     */
    public function getSkuInfo($goods_id,$develop_id)
    {   
        
        $skus = GoodsSku::where(['goods_id'=>$goods_id])->whereIn('status',[1,4])->order('s.id ASC')->alias('s')->field('s.*,s.cost_price cost,s.id sku_id,s.status sell_status,s.thumb main_image')->select();
        //$currency=(new Currency())->where('code','=','USD')->find();
        if($skus)
        {
            foreach ($skus as &$sku)
            {
                if($sku['main_image'])
                {
                    $sku['main_image']=GoodsImageService::getThumbPath($sku['main_image'], 200, 200);
                }

                $sku['combine_sku'] = $sku['sku'].'*1';

                $sku['d_imgs'] = GoodsImage::getSkuImagesBySkuId($sku['id'],3,$develop_id);
                $sku['cost']  = round($sku['cost_price'],2);

//                if($currency)
//                {
//                    $sku['cost']    = round($sku['cost']/$currency['system_rate'], 2);
//                }else{
//                   $sku['cost']  = round($sku['retail_price'], 2);
//                }

            }
            $result['lists']=$skus;
        }else{
            $result=[];
        }
        return $result; 
        /*
        $result  = [];
        $headers = [];
        $message = '';
                
        do {           
            $goods_info = Goods::where(['id' => $goods_id])->field('category_id,weight')->find();
                
            if (empty($goods_info)) {
                $message = '产品不存在';
                break;
            }
            $sku_lists = GoodsSku::where(['goods_id' => $goods_id])->field('id,sku,thumb,goods_id,name,cost_price,retail_price,sku_attributes,weight')->select();
            if (empty($sku_lists)) {
                break;
            }
                
            $goods_attributes = $this->getAttributeInfo($goods_id, 1);
            
            foreach($sku_lists as &$sku) 
            {
                $sku['weight'] == 0 ? $sku['weight'] = $goods_info['weight'] : '';
                $sku['sku_alias'] = $this->getSkuAlias($sku['id']);
                $sku['main_image'] = $sku['thumb'];
                $sku_attributes = json_decode($sku['sku_attributes'], true);
                $attribute_name = '';
                foreach($sku_attributes as $attribute_id => $attribute_value_id)
                {
                    list($attr, $attribute_id)  = explode('_', $attribute_id);
                    
                    $sku[$attribute_id]     = $this->getAttributeValue($goods_attributes, $attribute_id, $attribute_value_id, $attribute_name);
                    $headers[$attribute_id] = [
                        'attribute_id' => $attribute_id,
                        'name'         => $attribute_name
                    ];
                    unset($attribute_name);
                }
                //unset($sku['sku_attributes']);
            }
            $result['lists']   = $sku_lists;
            $result['headers'] = array_values($headers);
        } while(false);
        return $result; */
    }

    public static function getAllImages($where=array(),$fields="*",$limit=15)
    {
        $GoodsGallery = new GoodsGallery();
        if($limit)
        {
            $gallery = $GoodsGallery->field($fields)->where($where)->group('path')->limit($limit)->select();
        }else{
            $gallery =  $GoodsGallery->field($fields)->where($where)->group('path')->select();
        }
        return $gallery;
    }

    /**
     * 获取商品相册
     */
    public function getGoodsGallery($goods_id=0,$fields="*")
    {
        $gallerys = self::getAllImages(['goods_id'=>$goods_id],$fields,0);

        if($gallerys)
        {
            foreach ($gallerys as &$gallery)
            {
                $gallery['path']=GoodsImageService::getThumbPath($gallery['path'], 200, 200);
            }
            return $gallerys;
        }else{
            return [];
        }

    }
    /**
     * 获取产品编辑sku
     * @param int $goods_id
     * @return array
     */
    public function getSkuLists($goods_id)
    {   
        $lists = [];
        $headers = [];
        $goods_attributes = $this->getAttributeInfo($goods_id, 1);
        $sku_lists        = GoodsSku::where(['goods_id' => $goods_id])->field('id,sku,thumb,name,sku_attributes,name,cost_price,retail_price,weight,status')->select();
        $goods_info       = Goods::where(['id' => $goods_id])->field('weight')->find();
        foreach($goods_attributes as $attribute) {
            foreach($attribute['attribute_value'] as $k => &$list) {
                if (empty($list['selected'])) {
                    unset($attribute['attribute_value'][$k]);
                    continue;
                }
                $list['attribute_id'] = $attribute['attribute_id'];
                $list['attribute_code'] = $attribute['code'];
            }
            $headers[] = [
                'attribute_id' => $attribute['attribute_id'],
                'name'         => $attribute['name']
            ];
           
            $lists[] = $attribute['attribute_value'];
        }
        
        $new_lists = $this->getBaseSkuLists($lists, $sku_lists, $headers, $goods_info['weight']);
        
        return ['lists' => $new_lists, 'headers' => $headers];
    }
    
    /**
     * 获取基础sku 列表
     * @param array $lists
     * @param array $sku_lists
     * @param array $headers
     * @param int $weight
     * @return array
     */
    public function getBaseSkuLists(&$lists, &$sku_lists, &$headers, $weight = 0) 
    {
        $new_lists = !empty($lists) ? $this->getAttrSet($lists) : [];
        foreach($new_lists as &$list) {
            foreach($list as $v) {
                $new_list[$v['attribute_id']] = $v;                
            }
            $list = $new_list;
        }
        
        // 匹配sku
        foreach($new_lists as &$list) {            
            foreach($sku_lists as $k => $sku_info) {
                $sku_info['weight'] == 0 ? $sku_info['weight'] = $weight : '';
                $flag = true;
                $sku_attributes = json_decode($sku_info['sku_attributes'], true);
                foreach($list as $attribute_id => $value) {
                    if (isset($sku_attributes['attr_'.$attribute_id]) && $sku_attributes['attr_'.$attribute_id] == $value['id']) {
                        continue;
                    } else {
                        $flag = false;
                        break;
                    }
                }
                if ($flag) {
                    $list['thumb']        = $sku_info['thumb'];
                    $list['sku']          = $sku_info['sku'];
                    $list['alias_sku']    = $this->getSkuAlias($sku_info['id']);
                    $list['id']           = $sku_info['id'];
                    $list['name']         = $sku_info['name'];
                    $list['status']       = $sku_info['status'];
                    $list['cost_price']   = $sku_info['cost_price'];
                    $list['retail_price'] = $sku_info['retail_price'];
                    $list['weight']       = $sku_info['weight'];
                    $list['enabled']      = true;
                    unset($sku_lists[$k]);
                    break;
                }
            }
            
            if (!isset($list['sku'])) {
                $list['thumb']        = '';
                $list['sku']          = '';
                $list['alias_sku']    = [];
                $list['id']           = 0;
                $list['name']         = '';
                $list['status']       = 0;
                $list['cost_price']   = 0.00;
                $list['retail_price'] = 0.00;
                $list['weight']       = $weight;
                $list['enabled']      = false;
            }
            unset($list);
        }      
        if (!empty($sku_lists)) {
            foreach($sku_lists as $list) {
                $list['enabled'] = true;
                $list['alias_sku'] = $this->getSkuAlias($list['id']);
                foreach($headers as $header) {
                    $list[$header['attribute_id']] = [
                        'value' => ''
                    ];
                }
                unset($list['sku_attributes']);
                array_push($new_lists, $list);
            }
        }
        return $new_lists;
    }
    
    /**
     * 保存产品sku信息
     * @param int $goods_id
     * @param array $lists
     * @param boolean $is_generate_sku
     * @throws Exception
     */
    public function saveSkuInfo($goods_id, $lists, $is_generate_sku = true)
    {
        $goods_info = Goods::where(['id' => $goods_id])->field('spu, name, weight')->find();       
        if (empty($goods_info)) {
            throw new Exception('产品没找到');
        }
        $attributes = $this->getAttributeInfo($goods_id, 1);
        $goods_attributes = [];
        foreach($attributes as $attribute) {
            $values = [];
            foreach($attribute['attribute_value'] as $k => $list) {
                if (empty($list['selected'])) {
                    unset($attribute['attribute_value'][$k]);
                    continue;
                }
                $values[$list['id']] = $list;
            }
            $attribute['attribute_value'] = $values;
            $goods_attributes[$attribute['attribute_id']] = $attribute;
        }
        $add_lists    = [];
        $del_lists    = [];
        $modify_lists = [];                
        
        // 开始事务
        Db::startTrans();
        try {
            $this->formatSkuInfo($lists, $add_lists, $modify_lists, $del_lists, $goods_attributes, $goods_info['spu'], $goods_id, $is_generate_sku);
            if ($add_lists) {
                foreach($add_lists as $list) {
                    $goodsSku = new GoodsSku();
                    if (isset($list['id'])) {
                        unset($list['id']);
                    }
                    isset($list['weight']) && $list['weight'] == $goods_info['weight'] ? $list['weight'] = 0 : '';
                    $list['status'] = 0;
                    $list['spu_name'] = $goods_info['name'];
                    $list['goods_id'] = $goods_id;
                    $list['create_time'] = time();
                    $list['update_time'] = time();
                    $alias_sku = $list['alias_sku'];
                    unset($list['alias_sku']);
                    $goodsSku->allowField(true)->isUpdate(false)->save($list);
                    !empty($alias_sku) ? $this->saveSkuAlias($goodsSku->id, $list['sku'], $alias_sku) : '';
                }               
            }
        
            if ($modify_lists) {
                foreach($modify_lists as $list) {
                    $goodsSku = new GoodsSku();
                    $list['update_time'] = time();
                    if (isset($list['alias_sku'])) {
                        !empty($list['alias_sku']['add']) ? $this->saveSkuAlias($list['id'], $list['sku'], $list['alias_sku']['add']) : '';
                        !empty($list['alias_sku']['del']) ? $this->deleteSkuAlias($list['id'], $list['alias_sku']['del']) : '';
                        unset($list['alias_sku']);
                    }
                    if (isset($list['sku'])) {
                        unset($list['sku']);
                    }
                    isset($list['weight']) && $list['weight'] == $goods_info['weight'] ? $list['weight'] = 0 : '';
                    $goodsSku->allowField(true)->isUpdate(true)->save($list);
                }
            }
        
            if ($del_lists) {
                foreach($del_lists as $list) {
                    $goodsSku = new GoodsSku();
                    $goodsSku->where(['id' => $list['id'], 'goods_id' => $goods_id])->delete();
                    $this->deleteSkuAlias($list['id']);
                }
            }
            // 事务提交
            Db::commit();
        } catch( Exception $e) {
            Db::rollBack();
            throw new Exception($e->getMessage());
        }
    }
    
    /**
     * 获取sku别名列表
     * @param int $sku_id
     * @return array
     */
    private function getSkuAlias($sku_id)
    {
        $results = [];
        $lists = GoodsSkuAlias::where(['sku_id' => $sku_id])->select();
        foreach($lists as $list) {
            $results[] = $list['alias'];
        }
        
        return $results;
    }
    
    /***
     * 保存sku别名
     * @param int $sku_id
     * @param string $sku
     * @param array $lists
     */
    private function saveSkuAlias($sku_id, $sku, $lists)
    {
        $goodsSkuAlias = new GoodsSkuAlias();
        $results = [];
        foreach($lists as $alias) {
            $results[] = [
                'sku_id'    => $sku_id,
                'sku_code' => $sku,
                'alias'    => $alias,
                'create_time' => time()
            ];
            Cache::handler()->hdel('cache:Sku', $sku_id);
        }
        $goodsSkuAlias->allowField(true)->saveAll($results);
    }
    
    /**
     * 删除sku别名
     * @param int $sku_id
     * @param string $sku
     */
    private function deleteSkuAlias($sku_id, $sku = null)
    {
        if (null == $sku) {
            GoodsSkuAlias::where(['sku_id' => $sku_id])->delete();
        } else if (is_string($sku)) {
            GoodsSkuAlias::where(['sku_id' => $sku_id, 'alias' => $sku])->delete();
        } else {
            foreach($sku as $alias) {
                GoodsSkuAlias::where(['sku_id' => $sku_id, 'alias' => $alias])->delete();
            }
        }
        Cache::handler()->hdel('cache:Sku', $sku_id);
    }
    
    /**
     * sku信息格式化与检测
     * @param array $lists
     * @param array $add_lists
     * @param array $modify_lists
     * @param array $del_lists
     * @param array $goods_attributes
     * @param string $spu
     * @param int $goods_id
     * @param boolean $is_generate_sku
     * @throws Exception
     */
    public function formatSkuInfo(&$lists, &$add_lists, &$modify_lists, &$del_lists, &$goods_attributes, $spu, $goods_id, $is_generate_sku)
    {
        $message = '';
        $attribute_array = [];
        $sku_array = [];
        foreach($lists as $list) {
            switch($list['action']) {
                case 'add':
                    $attributes     = !empty($list['attributes']) ? $list['attributes'] : [];
                    $sku_attributes = [];
                    $rule           = [];
                    foreach($attributes as $attribute) { // 组织属性规格用于生产sku
                        $sku_attributes['attr_'.$attribute['attribute_id']] = $attribute['value_id'];
                        if (!isset($goods_attributes[$attribute['attribute_id']]) 
                            || !isset($goods_attributes[$attribute['attribute_id']]['attribute_value'][$attribute['value_id']])) {
                            $message .= '属性或者属性值不存在产品规格中';
                        }
                        $rule[] = [
                          'code'         => $goods_attributes[$attribute['attribute_id']]['code'],
                          'attribute_id' => $attribute['attribute_id'],
                          'value_code'   => $goods_attributes[$attribute['attribute_id']]['attribute_value'][$attribute['value_id']]['code'],
                          'value_id'     => $attribute['value_id']
                        ];
                    }
                    if ($message) {
                        break;
                    }
                    $list['sku_attributes'] = json_encode($sku_attributes);
                    if (in_array($list['sku_attributes'], $attribute_array) || $this->isSameAttribute($goods_id, $sku_attributes)) {
                        $message .= '存在相同的属性sku';
                        break;
                    }
                    $attribute_array[] = $list['sku_attributes'];
                    $list['sku'] = $is_generate_sku ? $this->createSku($spu, $rule, $goods_id, 0 , $sku_array) : '';
                    $sku_array[] = $list['sku'];
                    $list['alias_sku'] = isset($list['alias_sku']) ? $list['alias_sku'] : [];
                    // 验证sku_alias 别名问题
                    foreach($list['alias_sku'] as $alias) {
                        if (in_array($alias , $sku_array) || $this->isSameSku($goods_id, $alias)) {
                            $message .= '  '. $alias. '存在相同的sku';
                            continue;
                        }
                        $sku_array[] = $alias;
                    }
                    $add_lists[] = $list;
                break;
                case 'del':
                    if (!$this->isDeleteSku($list['id'])) {
                        $message .= (isset($list['sku']) ? $list['sku'] : '') . '不可以被删除';
                    }
                    $del_lists[] = $list;
                break;
                case 'modify':
                    if (isset($list['alias_sku'])) {
                        $sku_alias = $list['alias_sku'];
                        $search_lists = $this->getSkuAlias($list['id']);
                        $del_alias = array_diff($search_lists, $sku_alias);
                        $add_alias = array_diff($sku_alias, $search_lists);
                        foreach($add_alias as $alias) {
                            if (in_array($alias , $sku_array) || $this->isSameSku($goods_id, $alias)) {
                                $message .= '  '. $alias. '存在相同的sku';
                                continue;
                            }
                            $sku_array[] = $alias;
                        }
                    }
                    $list['alias_sku']['add'] = $add_alias;
                    $list['alias_sku']['del'] = $del_alias;
                    $modify_lists[] = $list;
                break;
            }
        }
        
        if ($message) {
            throw new Exception($message);
        }
    }
    
    /**
     * sku能否删除
     * @param int $sku_id
     * @return boolean
     */
    public function isDeleteSku($sku_id) {
        $sku_info = GoodsSku::where(['id' => $sku_id])->field('status')->find();
        if (!$sku_info || $sku_info['status'] == 0 ) {
            return true;
        }
        
        return false;
    }
    /**
     * 获取sku
     * @param string $spu
     * @param array $rule
     * @param int $goods_id
     * @param int $num
     * @param array $list_array
     * @return string
     */
    public function createSku($spu, $rule, $goods_id, $num = 0, $list_array = array()) {
        do {
           $sku = $this->generateSku($spu, $rule, $num++);
        } while($this->isSameSku($goods_id, $sku) || in_array($sku, $list_array));
        
        return $sku;
    }
    
    /**
     * 检测是否使用过相同属性
     * @param int $goods_id
     * @param array $sku_attributes
     * @return boolean
     */
    public function isSameAttribute($goods_id, $sku_attributes)
    {
        $where = ' goods_id =' . $goods_id;
        foreach($sku_attributes as $attribute => $value_id) {
            $where .= ' AND sku_attributes->"$.'. $attribute. '" = '. $value_id;
        }
        $count = GoodsSku::where($where)->count();
        if ($count) {
            return true;
        }
        
        return false;
    }
    
    /**
     * 检测是否使用过相同SKU
     * @param int $goods_id
     * @param string $sku
     * @return boolean
     */
    public function isSameSku($goods_id, $sku)
    {
        $count = GoodsSku::where(['goods_id' => $goods_id, 'sku' => $sku])->count();
        $alias_count = GoodsSkuAlias::where(['alias' => $sku])->count();
        if ($count || $alias_count) {
            return true;
        }
        
        return false;
    }
    /**
     * 获取属性值名称
     * @param array $goods_attributes
     * @param int $attribute_id
     * @param int $attribute_value_id
     * @return string
     */
    public function getAttributeValue(&$goods_attributes, $attribute_id, $attribute_value_id, &$attribute_name)
    {
        $attribute = '';
        $value = '';
        foreach($goods_attributes as $list) {
            if ($list['attribute_id'] == $attribute_id) {
                $attribute = $list;
                break;
            }
        }
        if (!$attribute) {
            return '';
        }
        $attribute_name = $attribute['name'];
        foreach($attribute['attribute_value'] as $value_list) {
            if ($value_list['id'] == $attribute_value_id) {
                $value = $value_list['value'];
                break;
            }
        }
        return $value;
    }
    
    /**
     * 数组交叉组合
     * @staticvar array $_total_arr
     * @staticvar int $_total_arr_index
     * @staticvar int $_total_count
     * @staticvar array $_temp_arr
     * @param array $arrs
     * @param int $_current_index
     * @return array
     */
    private function getAttrSet( array $arrs,$_current_index=-1)
    {     
        static $_total_arr;
        static $_total_arr_index;
        static $_total_count;
        static $_temp_arr;    
        if ($_current_index < 0) {
            $_total_arr       = array();
            $_total_arr_index = 0;
            $_temp_arr        = array();
            $_total_count     = count($arrs)-1;
            $this->getAttrSet($arrs, 0);
        } else {
            foreach ($arrs[$_current_index] as $v) {
                //如果当前的循环的数组少于输入数组长度
                if ($_current_index<$_total_count) {
                    //将当前数组循环出的值放入临时数组
                    $_temp_arr[$_current_index]=$v;
                    //继续循环下一个数组
                    $this->getAttrSet($arrs,$_current_index+1);
                } else if($_current_index==$_total_count) { //如果当前的循环的数组等于输入数组长度(这个数组就是最后的数组)
                //将当前数组循环出的值放入临时数组
                $_temp_arr[$_current_index]=$v;
                //将临时数组加入总数组
                $_total_arr[$_total_arr_index]=$_temp_arr;
                //总数组下标计数+1
                $_total_arr_index++;
               }
            }
        }
    
        return $_total_arr;
    }
    
    /**
     * 获取sku关联的仓库
     * @param int sku_id
     * @return array
     */
    public function getSkuWarehouses($sku_id)
    {
        $skuInfo = Cache::store('goods')->getSkuInfo($sku_id);
        if (empty($skuInfo)) {
            return [0];
        }
        $goodsInfo = Cache::store('goods')->getGoodsInfo($skuInfo['goods_id']);
        if (!$goodsInfo['is_multi_warehouse']) {
            return [$goodsInfo['warehouse_id']];
        }
        $warehouses = Cache::store('warehouse')->getWarehouse();
        return array_keys($warehouses);
    }
}