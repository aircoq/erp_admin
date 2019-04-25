<?php
namespace app\index\controller;

use think\Request;
use think\Loader;
use app\common\controller\Base;
use app\common\model\Category;
use app\common\model\Goods;
use app\common\model\GoodsSku;
use app\common\model\User as UserModel;
use app\common\model\GoodsSkuAlias;
use app\common\model\Attribute;
use app\common\model\AttributeValue;
use app\common\model\Supplier;
use app\common\model\SupplierGoodsOffer;
use app\common\model\Packing;
use app\common\model\CategoryAttribute;
use app\common\model\AttributeGroup;
use app\common\model\GoodsAttribute;
use app\common\model\GoodsLang;
use app\goods\service\GoodsHelp;
use app\common\service\ImportExport;
use app\index\service\ImportData as ImportService;
use app\common\model\GoodsSkuMap;
use app\common\model\WarehouseGoods;
use app\common\model\GoodsSkuAlias as SkuAlias;
use app\common\cache\Cache;
use think\Db;
Loader::import('phpExcel.PHPExcel', VENDOR_PATH);

/**
 * @module 系统设置
 * @title 产品资料导入
 * @url /import
 */
class ImportData extends Base
{
    private $_headers = ["SKU", "SPU", "产品名称", "产品分类", "英文配货名称", "中文配货名称", "中文报关名称", "英文报关名称", "SKU别名", "属性名1", "属性值1"
, "属性名2", "属性值2", "属性名3", "属性值3", "中文关键字", "中文产品描述", "英文关键字", "英文产品描述", "英文名称", "销售状态", "产品重量", "产品净重", 
"长", "宽", "深度", "体积重", "是否含包装", "包装袋", "头程费", "是否存在于多仓库", "默认仓库", "物流属性", "成本价", "零售价", "市场价"];
    // 属性code => attribute_id
    private $attr_info = [
        'color' => 1,
        'size'  => 2,
        'plug'  => 4,
        'type'  => 5
    ];
    
    private $platform_sales = [
        '必须上架' => 1,
        '可上架'   => 1,
        '可选上架'  => 1,
        '不可上架'  => 2,
        '不上架'    => 2
    ];
    
    private $color_value = [
        'Oranger' => 'Orange',
        'Grey'    => 'Gray',
        'DarkBlue' => 'Dark Blue',
        'Darkblue' => 'Dark Blue',
        'Dark blue'=> 'Dark Blue',
        'Deep blue'=> 'Dark Blue',
        'Deep Blue'=> 'Dark Blue',
        'Dark bule'=> 'Dark Blue',
        'Deepblue' => 'Dark Blue',
        'Silvery'  => 'Silver',
        'Sliver'   => 'Silver',
        'Siliver'  => 'Silver',
        'Nude'       => 'Nude Color',
        'nude'       => 'Nude Color',
        'Rose'       => 'Hot pink',
        'SkyBlue'    => 'Sky Blue',
        'Lakeblue'   => 'Lake Blue',
        'Lackblue'   => 'Lake Blue',
        'LakeBlue'   => 'Lake Blue',
        'Lake blue'  => 'Lake Blue',
        'Waterblue'  => 'Aqua Blue',
        'Light blue' => 'Light Blue',
        'Lightblue'  => 'Light Blue',
        'LightBlue'  => 'Light Blue',
        'Lingtblue'  => 'Light Blue',
        'Wathet'     => 'Light Blue',
        'Wathet blue'=> 'Light Blue',
        'Light blue' => 'Light Blue',
        'Lirhtblue'  => 'Light Blue',
        'Light bule' => 'Light Blue',
        'DarkGray'   => 'Dark Gray',
        'Dark grey'  => 'Dark Gray',
        'Deep Gray'  => 'Dark Gray',
        'Deep grey'  => 'Dark Gray',
        'Dary grey'  => 'Dark Gray',
        'Darygrey'   => 'Dark Gray',
        'Light Grey' => 'Light Gray',
        'Lightgray'  => 'Light Gray',
        'LightGray'  => 'Light Gray',
        'Light grey' => 'Light Gray',
        'light gray' => 'Light Gray',
        'Light gray' => 'Light Gray',
        'Lightgreen' => 'Light Green',
        'Light Green' => 'Light Green',
        'LightGreen'  => 'Light Green',
        'light green' => 'Light Green',
        'Light green' => 'Light Green',
        'Navygreen'  => 'Army Green',
        'NavyGreen'  => 'Army Green',
        'Skyblue'    => 'Sky Blue',
        'Sky'        => 'Sky Blue',
        'sky blue'   => 'Sky Blue',
        'Sky blue'   => 'Sky Blue',
        'Glod'       => 'Gold',
        'Sand'       => 'Sand Color',
        'SandColour' => 'Sand Color',
        'Cream'      => 'Sand Color',
        'Pale Mauv'  => 'Cameobrown',
        'Purpel'     => 'Purple',
        'Gary'       => 'Gray',
        'Browm'      => 'Brown',
        'Rocy'       => 'Hot pink',
        'Natural color' => 'Wood Color',
        'Woodcolor'     => 'Wood Color',
        'Wooden'     => 'Wood Color',
        'Wood'       => 'Wood Color',
        'Burlywood'  => 'Wood Color',
        'RoseGold'   => 'Rose Gold',
        'Rosegold'   => 'Rose Gold',
        'Rose gold'  => 'Rose Gold',
        'Rose Red'   => 'Hot pink',
        'RoseRed'    => 'Hot pink',
        'Rosered'    => 'Hot pink',
        'Rose red'   => 'Hot pink',
        'Grenen'     => 'Green',
        'Armygreen'  => 'Army Green',
        'ArmyGreen'  => 'Army Green',
        'Army green' => 'Army Green',
        'army green' => 'Army Green',
        'Darkgray'   => 'Dark Gray',
        'Silvel'     => 'Silver',
        'Darkgreen'  => 'Dark Green',
        'DarkGreen'  => 'Dark Green',
        'Deepgreen'  => 'Dark Green',
        'Dark green'  => 'Dark Green',
        'dark green'  => 'Dark Green',
        'Darkpurple' => 'Dark Purple',
        'Modena'     => 'Dark Purple',
        'DarkPurple' => 'Dark Purple',
        'Dark purple' => 'Dark Purple',
        'Orangr'     => 'Orange',
        'Sapphirre'  => 'Sapphire',
        'Royalblue'  => 'Sapphire',
        'RoyalBlue'  => 'Sapphire',
        'Royal blue' => 'Sapphire',
        'Royablue'   => 'Sapphire',
        'SapphireBlue' => 'Sapphire',
        'Luxury Gol' => 'Gold',
        'Luxury Gold'=> 'Gold',
        'Luxury gold'=> 'Gold',
        'LuxuryGold' => 'Gold',
        'Luxurygold' => 'Gold',
        'Golden'     => 'Gold',
        'WineRed'    => 'Wine Red',
        'Winered'    => 'Wine Red',
        'Wine'       => 'Wine Red',
        'Wine red'   => 'Wine Red',
        'RedWine'    => 'Wine Red',
        'Red wine'   => 'Wine Red',
        'Redwine'    => 'Wine Red',
        'Army'       => 'Army Green',
        'Navy'       => 'Navy Blue',
        'Navyblue'   => 'Navy Blue',
        'NavyBlue'   => 'Navy Blue',
        'Navy blue'  => 'Navy Blue',
        'Bule'       => 'Blue',
        'Balck'      => 'Black',
        'Lightpink'  => 'Light Pink',
        'LightPink'  => 'Light Pink',
        'Light pink' => 'Light Pink',
        'Transparent color' => 'Transparent',
        'Trans parent'      => 'Transparent',
        'Deep Pink'  => 'Dark Pink',
        'Darkpink'   => 'Dark Pink',
        'DarkPink'   => 'Dark Pink',
        'Gerry'      => 'Green',
        'Pure'       => 'Purple',
        'Bronzy'     => 'Bronze',
        'Red Bronzy' => 'Red Bronze',
        'Redbronze'  => 'Red Bronze',
        'Red bronze' => 'Red Bronze',
        'Green bronze' => 'Green Bronze',
        'Red copper' => 'Copper',
        'Color'      => 'Colorful',
        'Colour'     => 'Colorful',
        'Colours'    => 'Colorful',
        'Lawender'   => 'Lavender',
        'Light purple' => 'Lavender',
        'Light Purple' => 'Lavender',
        'Lightpurple' => 'Lavender',
        'Lilac'      => 'Lavender',
        'Linghtpurple' => 'Lavender',
        'LightPurple' => 'Lavender',
        'West Red'   => 'Red',
        'Sunsetcolor'=> 'Sunset Color',
        'SunsetColor'=> 'Sunset Color',
        'Darkred'    => 'Burgundy',
        'DarkCoffee' => 'Dark Coffee',
        'PeachRed'   => 'Peach Red',
        'Peachred'   => 'Peach Red',
        'Peach'      => 'Peach Red',
        'Peach red'  => 'Peach Red',
        'peach red'  => 'Peach Red',
        'Watermelon red' => 'Watermelon',
        'Watermelonred'  => 'Watermelon',
        'Coffe'      => 'Coffee',
        'DeepCoffee' => 'Dark Coffee',
        'Deepcoffee' => 'Dark Coffee',
        'Darkcoffee' => 'Dark Coffee',
        'Dark coffe' => 'Dark Coffee',
        'Dark coffee' => 'Dark Coffee',
        'Lightcoffee'=> 'Light Coffee',
        'LightCoffee'=> 'Light Coffee',
        'Light coffee' => 'Light Coffee',
        'Iron gray'  => 'Iron',
        'Irongray'   => 'Iron',
        'ShrimpRed'  => 'Dark Red',
        'Shrimp'     => 'Dark Red',
        'RedShrimp'  => 'Dark Red',
        'DarkRed'    => 'Dark Red',
        'Dark red'   => 'Dark Red',
        'IceBlue'    => 'Ice Blue',
        'Hot pink'   => 'Hot pink',
        'HotPink'    => 'Hot pink',
        'Hotpink'    => 'Hot pink',
        'Rosy'       => 'Hot pink',
        'ROSY'       => 'Hot pink',
        'Skin'       => 'Skin Color',
        'Skin colour'=> 'Skin Color',
        'Milkwhite'  => 'Creamy',
        'SilverGray' => 'Silver Gray',
        'Silvergray' => 'Silver Gray',
        'Silver gray' => 'Silver Gray',
        'WhiteGold'  => 'Platinum',
        'White Gold' => 'Platinum',
        'Powder'     => 'Pink', 
        'Lightskin'  => 'Light Beige',
        'IightBeige' => 'Light Beige',
        'Pinkblue'   => 'Powder Blue', // 粉蓝
        'PinkBlue'   => 'Pink Blue',
        'Pinkblue'   => 'Pink Blue',
        'Lightyellow'=> 'Light Yellow',
        'LightYellow'=> 'Light Yellow',
        'Titanium gray' => 'Titanium',
        // 'ChampagneGold' => 'Champagne',
        'Muticolor'  => 'Colorful',
        'Cyan'       => 'Cyan Color',
        'Friutgreen' => 'Light Green',
        'FriutGreen' => 'Light Green',
        'Fruitgreen' => 'Light Green',
        'Glassgreen' => 'Light Green',
        'Glass green'=> 'Light Green',
        'Darkbrown'  => 'Dark Brown',
        'DarkBrown'  => 'Dark Brown',
        'Lightbrown' => 'Light Brown',
        'LightBrown' => 'Light Brown',
        'Mint green' => 'Mint Green',
        'mint green' => 'Mint Green',
        'light brown' => 'Light Brown',
        'Light brown' => 'Light Brown',
        'Malachite green' => 'Malachite Green',
        'Mixed color' => 'Mixed Color',
        'White pink'  => 'White Pink',
    ];
    
    /**
     * 导分类
     * @param Request $request
     */
    public function index(Request $request)
    {
        /*$phpExcel  = new \PHPExcel();
        $file = $request->file('category');
        if ($file) {
            $info = $file->move(ROOT_PATH . 'public' . DS . 'upload');
            $ext = $info->getExtension();
            $path = $info->getPathname();
    		$filename= $info->getFilename();
//     		$type = ['分数', '日期'];
            $excelData = self::readExcel($path, 0, 0, $ext);
            $categoryModel = new Category();
            foreach ($excelData as $k=>$v) {
                foreach ($v as $kk=>$vv) {
                    $ids = $categoryModel->field('id')->where('code', $kk)->find();
                    $pid= $ids['id'];
                    $idata[] = ['name' => $vv[0], 'code' => $vv[1], 'pid' => intval($pid)];
                }
            }
            $categoryModel->saveAll($idata);
//     		@unlink($filename);
            echo 1;
        }*/
        $config  = [
            'client_id'     => '28223432',
            'client_secret' => 'Nb88wk9aRY',
            'accessToken'   => '1f022c53-5767-435a-9dca-5485d19f8649',
            'refreshtoken'  => 'e2e540df-41e1-4be4-abbd-569a6a98f529'
        ];
        $service = new \service\aliexpress\operation\Message($config);
        print_r($service->queryMsgDetailList(44025349802));exit;
        // print_r($service->queryMsgRelationList());exit;
    }
    
    /**
     * 导分类映射
     * 
     * @param Request $request
     */
    private function categoryMap(Request $request)
    {
        $phpExcel  = new \PHPExcel();
        $file = $request->file('category_map');
        if ($file) {
            $info = $file->move(ROOT_PATH . 'public' . DS . 'upload');
            $ext = $info->getExtension();
            $path = $info->getPathname();
            $filename= $info->getFilename();
            $excelData = self::readExcel($path, 0, 0, $ext);
            $categoryModel = new Category();
            foreach ($excelData as $k=>$v) {
                $data = [];
                $name = $v['new_two'][0];
                $two = $v['two'][0];
                if (isset($v['three'])) {
                    $three = $v['three'][0];
                    $two .= ',' .$three;
                }
                if (isset($v['four'])) {
                    $four = $v['four'][0];
                    $two .= ',' .$four;
                }
                $category = $categoryModel->where('name', 'like', $name.'%')->find();
                if ($category['name_map']) {
                    $two .= ',' .$category['name_map'];
                }
                $data['name_map'] = $two;
                $categoryModel->isUpdate(true)->save($data, ['id' => $category['id']]);
            }
            echo 'ok';
        }
    }
    
    /**
     * 导入商品(通途数据)
     * @title 产品导入
     * @url /import/goods
     * @method get
     */
    public function goods()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $filename = ROOT_PATH . 'public' . DS . 'upload/goods.csv';
        $file = @fopen($filename, 'r');
        $service        = new ImportService();
        $title      = [];
        $i          = 0;
        $spu        = '';
        $goods_id   = 0;
        $goods_name = '';
        $supplierId = 0;
        $min_quantity = 0;
        $this->properties();
        
        while ($data = fgetcsv($file)) {
            $data = eval('return '.iconv('gbk', 'utf-8', var_export($data,true)).';');
            if ($i == 0) {
                $i++;
                foreach ($data as $k=>$v) {
                    $title[$v] = $k;
                }
                continue;
            }
           
            if ($data[$title['SKU']]) {
                $spu        = $data[$title['SKU']];
                $goods_name = $data[$title['产品名称']];
                $min_quantity = $data[$title['最小采购量(MOQ)']];
                $purchase_price = $data[$title['采购单价']];
                $goodsModel = new Goods();
                //供应商
                $supplierModel = new Supplier();
                $supplier = $supplierModel->field('id')->where('company_name', $data[$title['供应商名称']])->find();
                if (!$supplier) {
                    if ($data[$title['供应商名称']]) {
                        $supplier = [
                            'company_name' => $data[$title['供应商名称']],
                            'status'       => 1,
                            'create_time' => time()
                        ];
                        $supplierModel->data($supplier)->isUpdate(false)->save();
                        $supplierId = $supplierModel->id;
                    } else {
                        $supplierId = 0;
                    }
                } else {
                    $supplierId = $supplier['id'];
                }
                
                $strCategory = $data[$title['分类']];
                $categoryId = $service->addCategory($strCategory, false);
                //商品数组
                $volume = $data[$title['产品体积(长*宽*高)CM']];
                $height = $width = $depth = 0;
                if ($volume) {
                    $volumeArr = explode('*', $volume);
                    $height = $volumeArr[2];
                    $width = $volumeArr[1];
                    $depth = $volumeArr[0];
                }
                $platform_sale = '{"ebay": 1,"amazon": 1,"wish": 1,"aliExpress": 1}';               
                // 匹配物流属性
                $transport_property = $this->matchTransportProperty($data[$title['标签']]);
                $goods = [
                    'name'            => $data[$title['产品名称']],
                    'spu'             => $spu,
                    'height'          => $height,
                    'width'           => $width,
                    'depth'           => $depth,
                    'category_id'     => $categoryId,
                    'packing_en_name' => $data[$title['英文配货名称']],
                    'packing_name'    => $data[$title['中文配货名称']],
                    'declare_name'    => $data[$title['中文报关名']],
                    'declare_en_name' => $data[$title['英文报关名']],
                    'packing_id'      => 0,
                    'thumb'           => $data[$title['产品首图']],
                    'platform_sale'   => $platform_sale,
                    'status'          => 1,
                    'warehouse_id'    => 2,
                    'transport_property' => $transport_property,
                ];
                $goodsInfo = $goodsModel->where(['spu' => $spu])->find();
                if ($goodsInfo) {
                    $goods_id = $goodsInfo->id;
                } else {
                    $goodsModel->allowField(true)->save($goods);
                    $goods_id  = $goodsModel->id;
                    // 产品描述
                    $description = [
                        'title' => $data[$title['产品名称']],
                        'goods_id' => $goods_id,
                        'lang_id' => 1,
                        'description' => ''
                    ];
                    $goodsLang      = new GoodsLang();
                    $goodsLang->allowField(true)->save($description);
                }
            } else {
                $goodsSkuModel = new GoodsSku();
                $goodSku = [
                    'goods_id'       => $goods_id,
                    'sku'            => $data[$title['SKU属性编号']],
                    'spu_name'       => $goods_name,
                    'sku_attributes' => '{}',
                    'status'         => 1,
                    'cost_price'     => $purchase_price,
                    'weight'         => $data[$title['产品重量(g)']],
                ];
                $skuInfo = $goodsSkuModel->where(['goods_id' => $goods_id, 'sku' => $data[$title['SKU属性编号']]])->find();
                if ($skuInfo) {
                    continue;
                }
                $goodsSkuModel->data($goodSku, true)->isUpdate(false)->save();
                $goodsService = new Goods();
                $goodsService->where(['id' => $goods_id])->update(['weight' => $data[$title['产品重量(g)']], 'net_weight' =>  $data[$title['产品重量(g)']]]);
                //sku别名
                $strSkuAlias = $data[$title['SKU别名']];
                if ($strSkuAlias) {
                    $skuAliasModel = new SkuAlias();
                    $insertSkuAliasArr = [];
                    $skuAliasArr = explode(',', $strSkuAlias);
                    foreach ($skuAliasArr as $vv) {
                        $insertSkuAliasArr[] = [
                            'sku_id' => $goodsSkuModel->id,
                            'sku_code' => $data[$title['SKU']],
                            'alias' => $vv,
                            'create_time' => time()
                        ];
                    }
                    $skuAliasModel->saveAll($insertSkuAliasArr);
                }
                //商品sku对应的提供商
                $supplierGoodsSku = [
                    'goods_id'     => $goods_id,
                    'sku_id'       => $goodsSkuModel->id,
                    'supplier_id'  => $supplierId,
                    'min_quantity' => $min_quantity,
                    'price'        => $purchase_price,
                    'create_time'  => time(),
                ];
                $supplierOfferService = new \app\purchase\service\SupplierOfferService();
                $supplierOfferService->add($supplierGoodsSku);
            }
        }
    }
    
    /**
     * 导入单属性sku
     * @method get
     * @title 导入单属性SKU
     * @url /import/single-sku
     */
    public function singleSku()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $filename = ROOT_PATH . 'public' . DS . 'upload/goods1.csv';
        $file = @fopen($filename, 'r');
        $service        = new ImportService();
        $title      = [];
        $i          = 0;
        $spu        = '';
        $goods_id   = 0;
        $goods_name = '';
        $supplierId = 0;
        $min_quantity = 0;
        $this->properties();
        
        while ($data = fgetcsv($file)) {
            $data = eval('return '.iconv('gbk', 'utf-8', var_export($data,true)).';');
            if ($i == 0) {
                $i++;
                foreach ($data as $k=>$v) {
                    $title[$v] = $k;
                }
                continue;
            }
           
            $spu = $data[$title['SKU']];
            $goods_name = $data[$title['产品名称']];
            $min_quantity = $data[$title['最小采购量(MOQ)']];
            $purchase_price = $data[$title['采购单价']];
            $goodsModel = new Goods();
            

            $strCategory = $data[$title['分类']];
            $categoryId = $service->addCategory($strCategory, true);
            //商品数组
            $volume = $data[$title['产品体积(长*宽*高)CM']];
            $height = $width = $depth = 0;
            if ($volume) {
                $volumeArr = explode('*', $volume);
                $height = $volumeArr[2];
                $width = $volumeArr[1];
                $depth = $volumeArr[0];
            }
            $platform_sale = '{"ebay": 1,"amazon": 1,"wish": 1,"aliExpress": 1}';
            // 匹配物流属性
            $transport_property = $this->matchTransportProperty($data[$title['标签']]);
            $goods = [
                    'name'            => $data[$title['产品名称']],
                    'spu'             => $spu,
                    'height'          => $height,
                    'width'           => $width,
                    'depth'           => $depth,
                    'weight'          => $data[$title['产品重量(g)']], 
                    'net_weight'      => $data[$title['产品重量(g)']],
                    'category_id'     => $categoryId,
                    'packing_en_name' => $data[$title['英文配货名称']],
                    'packing_name'    => $data[$title['中文配货名称']],
                    'declare_name'    => isset($data[$title['中文报关名']]) ? $data[$title['中文报关名']] : '',
                    'declare_en_name' => isset($data[$title['英文报关名']]) ? $data[$title['英文报关名']] : '',
                    'packing_id'      => 0,
                    'thumb'           => $data[$title['产品首图']],
                    'platform_sale'   => $platform_sale,
                    'status'          => 1,
                    'transport_property' => $transport_property,
            ];
            $goodsInfo = $goodsModel->where(['spu' => $spu])->find();
            if ($goodsInfo) {
                $goods_id = $goodsInfo->id;
                continue;
            } else {
                $goodsModel->allowField(true)->save($goods);
                $goods_id = $goodsModel->id;
                // 产品描述
                $description = [
                    'title' => $data[$title['产品名称']],
                    'goods_id' => $goods_id,
                    'lang_id' => 1,
                    'description' => ''
                ];
                $goodsLang = new GoodsLang();
                $goodsLang->allowField(true)->save($description);
            }
            
            // 供应商
            $supplierModel = new Supplier();
            $supplier = $supplierModel->field('id')->where('company_name', $data[$title['供应商名称']])->find();
            if (!$supplier) {
                if ($data[$title['供应商名称']]) {
                    $supplier = [
                        'company_name' => $data[$title['供应商名称']],
                        'status' => 1,
                        'create_time' => time()
                    ];
                    $supplierModel->data($supplier)->isUpdate(false)->save();
                    $supplierId = $supplierModel->id;
                } else {
                    $supplierId = 0;
                }
            } else {
                $supplierId = $supplier['id'];
            }
            
            $goodsSkuModel = new GoodsSku();
            $goodSku = [
                'goods_id'       => $goods_id,
                'sku'            => $spu,
                'spu_name'       => $goods_name,
                'sku_attributes' => '{}',
                'status'         => 1,
                'cost_price'     => $purchase_price,
                'weight'         => $data[$title['产品重量(g)']],
            ];
            $skuInfo = $goodsSkuModel->where(['goods_id' => $goods_id, 'sku' => $spu])->find();
            if ($skuInfo) {
                continue;
            }
            $goodsSkuModel->data($goodSku, true)->isUpdate(false)->save();
            $goodsService = new Goods();
            $goodsService->where(['id' => $goods_id])->update([]);
            //sku别名
            $strSkuAlias = $data[$title['SKU别名']];
            if ($strSkuAlias) {
                $skuAliasModel = new SkuAlias();
                $insertSkuAliasArr = [];
                $skuAliasArr = explode(',', $strSkuAlias);
                foreach ($skuAliasArr as $vv) {
                    $insertSkuAliasArr[] = [
                        'sku_id' => $goodsSkuModel->id,
                        'sku_code' => $data[$title['SKU']],
                        'alias' => $vv,
                        'create_time' => time()
                    ];
                }
                $skuAliasModel->saveAll($insertSkuAliasArr);
            }
            //商品sku对应的提供商
            $supplierGoodsSku = [
                'goods_id' => $goods_id,
                'sku_id' => $goodsSkuModel->id,
                'supplier_id' => $supplierId,
                'min_quantity' => $min_quantity,
                'price' => $purchase_price,
                'create_time' => time(),
            ];
            $supplierOfferService = new \app\purchase\service\SupplierOfferService();
            $supplierOfferService->add($supplierGoodsSku);
        }
    }
    
    /**
     * @title 导入sku属性
     * @url /import/sku-attribute
     * @method get
     * 通途sku属性
     * 
     */
    public function attribute()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $filename = ROOT_PATH . 'public' . DS . 'upload/skuattr.csv';
        $attribute_names = ['规格1名称' => '规格1取值', '规格2名称' => '规格2取值'];
        $file = @fopen($filename, 'r');
        $attributes = [
            'Color'            => ['attribute_id' => 1, 'self' => 0],
            'Type'             => ['attribute_id' => 11, 'self' => 1, 'value_id' => 171],
            // 'Watt'             => ['attribute_id' => , 'self' => 0],
            'Current'          => ['attribute_id' => 8, 'self' => 0],
            'Model'            => ['attribute_id' => 9, 'self' => 0],
            'Storage Capacity' => ['attribute_id' => 10, 'self' => 0],
            'Plug'             => ['attribute_id' => 5, 'self' => 0],
            'Quantity'         => ['attribute_id' => 12, 'self' => 1, 'value_id' => 197],
            'Lumen'            => ['attribute_id' => 6, 'self' => 0],
            'Voltage'          => ['attribute_id' => 7, 'self' => 0],
            'Size(DIY)'        => ['attribute_id' => 13, 'self' => 1, 'value_id' => 223],
            'Size(Standard)'   => ['attribute_id' => 2, 'self' => 0],
        ];
        $i = 0;
        while ($data = fgetcsv($file)) {
            $data = eval('return '.iconv('gbk', 'utf-8', var_export($data,true)).';');
            if ($i == 0) {
                $i++;
                foreach ($data as $k=>$v) {
                    $title[$v] = $k;
                }
                continue;
            }
            $goodsSku = new GoodsSku();
            $goods = new Goods();
            $skuInfo   = $goodsSku->where(['sku' => $data[$title['SKU']]])->find();
            if (!$skuInfo) {
                continue;
            }
            $goodsInfo = $goods->where(['id' => $skuInfo->goods_id])->find();
            $info = [
                'goods_id' => $goodsInfo->id,
                'category_id' => $goodsInfo->category_id,
            ];
            $sku_attributes = '';
            
            $sku_name = '';
            foreach($attribute_names as $attribute_name => $attribute_value) {
                if (!isset($data[$title[$attribute_name]])) {
                    continue;
                }
                $attributeName = $data[$title[$attribute_name]];
                if (!isset($attributes[$attributeName])) {
                    continue;
                }
                
                $info['attribute_id'] = $attributes[$attributeName]['attribute_id'];
                if ($attributes[$attributeName]['self']) {
                    $info['value'] = $data[$title[$attribute_value]];
                    $info['value_id'] = $attributes[$attributeName]['value_id'];
                    $value_id = $this->addSelfAttribute($info);
                    $sku_name .= ' '. $data[$title[$attribute_value]];
                } else {                    
                    $values = $this->getValueId($info['attribute_id'], $data[$title[$attribute_value]]);
                    if($values['value_id'] == 0) {
                        Db::table('goods_attribute_log')->insert(['sku_id' => $skuInfo->id , 'sku' => $data[$title['SKU']], 'attribute_id' => $info['attribute_id'], 'value' => $data[$title[$attribute_value]]]);
                        continue;
                    }
                    $info['value_id'] = $values['value_id'];
                    $this->addAttribute($info);
                    $value_id = $values['value_id'];
                    $sku_name .= ' '.$values['value'];
                }
                
                $sku_attributes .= ($sku_attributes ? ',' : ''). "\"attr_{$info['attribute_id']}\"". ':' .$value_id;
            }
            $skuAttrValue = '{'. $sku_attributes .'}';
            // 更新sku
            
            $goodsSku->where(['id' => $skuInfo->id])->update(['sku_attributes' => $skuAttrValue, 'name' => trim($sku_name)]);
        }
    }
    
    /**
     * @title 导入sku属性fix
     * @url /import/handle-attribute
     * @method get
     * 通途sku属性
     * 
     */
    public function handleAttribute()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $filename = ROOT_PATH . 'public' . DS . 'upload/skuattr-fix-1.csv';
        $attribute_names = ['规格1名称' => '规格1取值', '规格2名称' => '规格2取值'];
        $file = @fopen($filename, 'r');
        $attributes = [
            'Color'            => ['attribute_id' => 1, 'self' => 0],
            'Type'             => ['attribute_id' => 11, 'self' => 1, 'value_id' => 171],
            // 'Watt'             => ['attribute_id' => , 'self' => 0],
            'Current'          => ['attribute_id' => 8, 'self' => 0],
            'Model'            => ['attribute_id' => 9, 'self' => 0],
            'Storage Capacity' => ['attribute_id' => 10, 'self' => 0],
            'Plug'             => ['attribute_id' => 5, 'self' => 0],
            'Quantity'         => ['attribute_id' => 12, 'self' => 1, 'value_id' => 197],
            'Lumen'            => ['attribute_id' => 6, 'self' => 0],
            'Voltage'          => ['attribute_id' => 7, 'self' => 0],
            'Size(DIY)'        => ['attribute_id' => 13, 'self' => 1, 'value_id' => 223],
            'Size(Standard)'   => ['attribute_id' => 2, 'self' => 0],
        ];
        $i = 0;
        while ($data = fgetcsv($file)) {
            $data = eval('return '.iconv('gbk', 'utf-8', var_export($data,true)).';');
            if ($i == 0) {
                $i++;
                foreach ($data as $k=>$v) {
                    $title[$v] = $k;
                }
                continue;
            }
            $goodsSku = new GoodsSku();
            $goods = new Goods();
            $skuInfo   = $goodsSku->where(['sku' => $data[$title['SKU']]])->find();
            if (!$skuInfo) {
                continue;
            }
            $goodsInfo = $goods->where(['id' => $skuInfo->goods_id])->find();
            $info = [
                'goods_id' => $goodsInfo->id,
                'category_id' => $goodsInfo->category_id,
            ];
            $sku_attributes = '';
            
            $sku_name = '';
            foreach($attribute_names as $attribute_name => $attribute_value) {
                if (!isset($data[$title[$attribute_name]])) {
                    continue;
                }
                $attributeName = $data[$title[$attribute_name]];
                if (!isset($attributes[$attributeName])) {
                    continue;
                }
                
                $info['attribute_id'] = $attributes[$attributeName]['attribute_id'];
                if ($attributes[$attributeName]['self']) {
                    $info['value'] = $data[$title[$attribute_value]];
                    $info['value_id'] = $attributes[$attributeName]['value_id'];
                    $value_id = $this->addSelfAttribute($info);
                    $sku_name .= ' '. $data[$title[$attribute_value]];
                } else {                    
                    $values = $this->getValueId($info['attribute_id'], $data[$title[$attribute_value]]);
                    if($values['value_id'] == 0) {
                        Db::table('goods_attribute_log')->insert(['sku_id' => $skuInfo->id , 'sku' => $data[$title['SKU']], 'attribute_id' => $info['attribute_id'], 'value' => $data[$title[$attribute_value]]]);
                        continue;
                    }
                    $info['value_id'] = $values['value_id'];
                    $this->addAttribute($info);
                    $value_id = $values['value_id'];
                    $sku_name .= ' '.$values['value'];
                }
                
                $sku_attributes .= ($sku_attributes ? ',' : ''). "\"attr_{$info['attribute_id']}\"". ':' .$value_id;
            }
            $skuAttrValue = '{'. $sku_attributes .'}';
            // 更新sku
            Db::table('goods_attribute_log')->where(['sku_id' => $skuInfo->id])->delete();
            $goodsSku->where(['id' => $skuInfo->id])->update(['sku_attributes' => $skuAttrValue, 'name' => trim($sku_name)]);
        }
    }
    
    /**
     * @title 导入sku属性数据库
     * @url /import/data-attribute
     * @method get
     * 通途sku属性
     * 
     */
    public function dataAttribute()
    {
        set_time_limit(0);
        $lists = Db::table('goods_attribute_log')->select();
        foreach($lists as $list) {
            $goodsSku = new GoodsSku();
            $goods = new Goods();
            $skuInfo   = $goodsSku->where(['id' => $list['sku_id']])->find();
            if (!$skuInfo) {
                continue;
            }
            $goodsInfo = $goods->where(['id' => $skuInfo->goods_id])->find();
            $info = [
                'goods_id' => $goodsInfo->id,
                'category_id' => $goodsInfo->category_id,
                'attribute_id' => $list['attribute_id'],
            ];
            $sku_attributes = '';
            if ($list['attribute_id'] == 10) {
                $list['value'] = str_replace('GB', 'G', $list['value']);
            }
            $values = $this->getValueId($list['attribute_id'], $list['value']);
            if ($values['value_id'] == 0) {
                $attributeValue = Db::table('attribute_value')->where(['attribute_id' => $list['attribute_id'], 'value' => ['like', $list['value'].'%']])->find();
                if (!$attributeValue) {
                    continue;
                }
                $values = [
                    'value' => $list['value'],
                    'value_id' => $attributeValue['id']
                ];
            }
            $info['value_id'] = $values['value_id'];
            $this->addAttribute($info);
            $value_id = $values['value_id'];
            $sku_name =  $values['value'];

            $sku_attributes .= ($sku_attributes ? ',' : '') . "\"attr_{$info['attribute_id']}\"" . ':' . $value_id;
            $skuAttrValue = '{' . $sku_attributes . '}';
            // 更新sku
            Db::table('goods_attribute_log')->where(['sku_id' => $skuInfo->id])->delete();
            $goodsSku->where(['id' => $skuInfo->id])->update(['sku_attributes' => $skuAttrValue, 'name' => trim($sku_name)]);
        }
    }
    
    private function addAttribute(array $info) 
    {
        //添加分类属性
        $categoryAttribute = new CategoryAttribute();
        $categoryAttr = $categoryAttribute->where('category_id', $info['category_id'])->where('attribute_id', $info['attribute_id'])->find();
        if (!$categoryAttr) {
            $categoryAttrArr = [
                'category_id' => $info['category_id'],
                'attribute_id' => $info['attribute_id'],
                'group_id' => 1,
                'sku' => 1,
                'gallery' => 1,
                'value_range' => '[' . $info['value_id'] . ']'
            ];
            $attributeGroup = new AttributeGroup();
            $groupInfo = $attributeGroup->where(['category_id' => $info['category_id']])->find();
            if (!$groupInfo) {
                $attributeGroup->allowField(true)->save(['category_id' => $info['category_id'], 'name' => '分组1']);
                $group_id = $attributeGroup->id;
            } else {
                $group_id = $groupInfo->id;
            }
            $categoryAttrArr['group_id'] = $group_id;
            $categoryAttribute->allowField(true)->save($categoryAttrArr);
        } else {
            $value_range = json_decode($categoryAttr->value_range);
            if (!in_array($info['value_id'], $value_range)) {
                $value_range[] = $info['value_id'];
                $categoryAttribute->where('category_id', $info['category_id'])->where('attribute_id', $info['attribute_id'])->update(['value_range' => json_encode($value_range), 'sku' => 1]);
            }
        }

        //添加商品属性
        $goodsAttrAttr = [
            'attribute_id' => $info['attribute_id'],
            'goods_id' => $info['goods_id'],
            'value_id' => $info['value_id'],
        ];
        $goodsAttribute = new GoodsAttribute();
        if (!$goodsAttribute->where($goodsAttrAttr)->count()) {
            $goodsAttribute->allowField(true)->save($goodsAttrAttr);
        }
    }
    
    private function addSelfAttribute(array $info) 
    {
        //添加分类属性
        $categoryAttribute = new CategoryAttribute();
        $categoryAttr = $categoryAttribute->where('category_id', $info['category_id'])->where('attribute_id', $info['attribute_id'])->find();
        if (!$categoryAttr) {
            $categoryAttrArr = [
                'category_id' => $info['category_id'],
                'attribute_id' => $info['attribute_id'],
                'group_id' => 1,
                'sku' => 1,
                'value_range' => '[]'
            ];
            $attributeGroup = new AttributeGroup();
            $groupInfo = $attributeGroup->where(['category_id' => $info['category_id']])->find();
            if (!$groupInfo) {
                $attributeGroup->allowField(true)->save(['category_id' => $info['category_id'], 'name' => '分组1']);
                $group_id = $attributeGroup->id;
            } else {
                $group_id = $groupInfo->id;
            }
            $categoryAttrArr['group_id'] = $group_id;
            $categoryAttribute->allowField(true)->save($categoryAttrArr);
        }
        //添加商品属性
        $goodsAttrAttr = [
            'attribute_id' => $info['attribute_id'],
            'goods_id' => $info['goods_id'],
            'alias'    => $info['value']
        ];
        $goodsAttribute = new GoodsAttribute();
        $goodsAttributeInfo = $goodsAttribute->where($goodsAttrAttr)->find();
        if ($goodsAttributeInfo) {
            $value_id = $goodsAttributeInfo->value_id;
        } else {
            $lastAttriubteInfo = $goodsAttribute->where(['attribute_id' => $info['attribute_id'], 'goods_id' => $info['goods_id']])->order('value_id desc')->find();
            if ($lastAttriubteInfo) {
                $value_id = $lastAttriubteInfo->value_id + 1;
            } else {
                $value_id = $info['value_id'];
            }
            $goodsAttrAttr['value_id'] = $value_id;
            $goodsAttribute->allowField(true)->save($goodsAttrAttr);
        }
        
        return $value_id;
    }
     
    /**
     * 获取产品属性（通途数据归类用，供业务人员使用的，使用完了后此代码可以删除）
     */
    private function extarcAttribute()
    {
        $PHPExcel = new \PHPExcel();
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $filename = ROOT_PATH . 'public' . DS . 'upload/goods2.csv';
        $file = @fopen($filename, 'r');
        $i = 0;
        $attr = [];
        $attr = ['属性名1' => '属性值1', '属性名2' => '属性值2', '属性名3' => '属性值3'];
        while ($data = fgetcsv($file)) {
            $data = eval('return '.iconv('gbk', 'utf-8', var_export($data,true)).';');
            if ($i == 0) {
                $i++;
                foreach ($data as $k=>$v) {
                    $title[$v] = $k;
                }
                continue;
            }
//             $prodcutName = $data[$title['产品名称']];
//             //处理商品名称
//             if ($prodcutName) {
//                 $partener = ['/\（/', '/\）/'];
//                 $str = preg_replace($partener, ['(', ')'], $prodcutName);
//                 preg_match_all("/\([^\(\)]*\)/i", $str, $result);
//                 if ($result && isset($result[0])) {
//                     $result = $result[0];
//                     foreach ($result as $k=>$v) {
//                         $val = trim($v, '()');
//                         $temp[$val] = $val;
//                     }
//                 }
//             }
            //处理商品分类
//             $categoryName = $data[$title['分类']];
//             if ($categoryName) {
//                 $nameArr = explode('/', $categoryName);
//                 $temp[] = $nameArr;
//             }
            //处理sku属性(属性值包含的sku)
            if ($data[$title['SKU']]) {
                $tsku = $data[$title['SKU']];
            }
            foreach ($attr as $m=>$n) {
                $attribute = $data[$title[$m]];
                $value = $data[$title[$n]];
                if ($attribute && $value) {
                    $temp[$value][] = [$attribute => $value, 'sku' => $tsku];
                }
            }
            $tableTitle = ['属性值', 'SKU'];
            foreach ($temp as $kk=>$vv) {
                $res[] = self::multUnique($vv);
            }
            $newResult = [];
            foreach ($res as $kkk=>$vvv) {
                foreach ($vvv as $l) {
                    $newResult[] = $l;
                }
            }
            //处理标签
//             $tagName = $data[$title['标签']];
//             if ($tagName) {
//                 $tagArr = explode(',', $tagName);
//                 foreach ($tagArr as $kk=>$vv) {
//                     $temp[$vv] = $vv;
//                 }
//             }
        }
//         foreach ($temp as $kk=>$vv) {
//             $temp1[] = [$vv];
//         }
//         $temp1[] = $temp;
//         $arr = self::multiUnique($temp);
//         foreach ($arr as $k=>$v) {
//             foreach ($v as $s=>$s_val) {
//                 $sku[] = [$s, $s_val];
//             }
//         }
        ImportExport::excelExport($newResult, $tableTitle);
//         $arr = self::multiUnique($temp);
//         ImportExport::excelExport($attr1, ['商品名称属性'], '商品名称属性');
//         ImportExport::excelExport($attr1, ['一级分类', '二级分类'], '分类');
//         ImportExport::excelExport($sku, ['sku属性', 'sku属性值'], 'sku属性');
//         ImportExport::excelExport($arr, ['一级分类', '二级分类', '三级分类', '四级分类'], '分类');
    }
    
    /**
     * 导入赛和的数据
     * 数据导入--翟彬接收
     * @method get
     * @url /import/saihe-data
     * @title 导入赛盒数据
     */
    public function importDataSaihe()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $filename = ROOT_PATH . 'public' . DS . 'upload'. DS . 'saihe.csv';
        $file = @fopen($filename, 'r');
        // $userModel = new UserModel();
        $skuAliasModel = new GoodsSkuAlias();
        $goodsHelpService = new GoodsHelp();
        $title = [];
        $i = 0;
        //属性数组
        $attr = ['属性名1' => '属性值1', '属性名2' => '属性值2', '属性名3' => '属性值3'];
        while ($data = fgetcsv($file)) {
            $data = eval('return '.iconv('gbk', 'utf-8', var_export($data,true)).';');
            if ($i == 0) {
                $i++;
                foreach ($data as $k=>$v) {
                    $title[$v] = $k;
                }
                continue;
            }
            $strSupplier = $data[$title['默认供应商']];
            $supplierId  = ImportService::addSupplier($strSupplier, true);
            $strCategory = $data[$title['完整中文类别']];
            $categoryId  = ImportService::addCategory($strCategory, true);
            //生成spu
            // $spu = $categoryId ? $goodsHelpService->generateSpu($categoryId) : '';
//             echo $spu;
            //商品数组
            $spu = empty($data[$title['母体ID']]) ? $data[$title['自定义SKU']] : $data[$title['母体ID']];
            $height = $data[$title['产品长']];
            $width  = $data[$title['产品宽']];
            $depth  = $data[$title['产品高']];
            $weight  = $data[$title['产品毛重']];
            $sellStatus  = $data[$title['上架状态']];
            if ($sellStatus == '未上架') {
                $sellStatus = 0;
            } elseif ($sellStatus == '已下架') {
                $sellStatus = 2;
            } else {
                $sellStatus = 1;
            }
            // $user   = $userModel->field('id')->where('username', $data[$title['开发产品人员']])->find();
            $goods = [
                'name'              => $data[$title['产品中文名']],
                'spu'               => $spu,
                'height'            => $height,
                'width'             => $width,
                'depth'             => $depth,
                'weight'            => $weight,
                'category_id'       => $categoryId,
                'packing_en_name'   => $data[$title['产品英文名']],
                'packing_name'      => $data[$title['产品中文名']],
                'declare_name'      => $data[$title['产品报关中文名']],
                'declare_en_name'   => $data[$title['产品报关英文名']],
//                 'packing_id'        => $data['packing_id'],
                'thumb'             => $data[$title['产品封面图']],
                'hs_code'           => $data[$title['海关编码']],
                'keywords'          => $data[$title['产品搜索关键词']],
               // 'description'       => $data[$title['产品详细描述文本']],
                'supplier_id'       => $supplierId,
                'cost_price'        => $data[$title['默认供货价']],
                'retail_price'      => $data[$title['B2C销售价']],
                'source_url'        => $data[$title['参考网址']],
                'volume_weight'     => ($height*$width*$depth)/6000,
                'status'            => 1,
                'platform_sale'     => '{}',
                'create_time'       => strtotime($data[$title['添加时间']]),        
                // 'developer'         => $data[$title['开发产品人员']],           
                // 'developer_id'      => $user ? $user['id'] : 0,           
                'sales_status'      => $sellStatus,       
            ];
            $goodsId = ImportService::addGoods($goods);
            // 添加描述
            $description = [
                'lang_id' => 1,
                'description' => $data[$title['产品详细描述文本']],
                'goods_id'    => $goodsId
            ];
            ImportService::addDescription($description);
            
            //属性值
            $color = $data[$title['产品颜色']];
            $size  = $data[$title['产品尺码']];
            $skuAttrValueArr = [];
            if ($color) {
                $colorValueId = ImportService::addAttributeValue($color, 1);
                $skuAttrValueArr = ['attr_1' => $colorValueId];
                //添加商品属性
                $goodsAttrArr1 = [
                    'attribute_id' => 1,
                    'goods_id'     => $goodsId,
                    'value_id'     => $colorValueId,
                    'data'         => $color
                ];
                ImportService::addGoodsAttr($goodsAttrArr1);
                //添加分类属性
                $categoryAttrArr1 = [
                    'category_id'    => $categoryId,
                    'attribute_id'   => 1,
                    'value_range'    => $colorValueId,
                    'group_id'       => 1,
                    'sku'            => 1,
                    'gallery'        => 1
                ];
                ImportService::addCategoryAttr($categoryAttrArr1);
            }
            if ($size) {
                $sizeValueId = ImportService::addAttributeValue($size, 2);
                $skuAttrValueArr['attr_2'] = $sizeValueId;
                //添加商品属性
                $goodsAttrArr2 = [
                    'attribute_id' => 2,
                    'goods_id'     => $goodsId,
                    'value_id'     => $sizeValueId,
                    'data'         => $size
                ];
                ImportService::addGoodsAttr($goodsAttrArr2);
                //添加分类属性
                $categoryAttrArr2 = [
                    'category_id'    => $categoryId,
                    'attribute_id'   => 2,
                    'value_range'    => $sizeValueId,
                    'group_id'       => 1,
                    'sku'            => 1,
                ];
                ImportService::addCategoryAttr($categoryAttrArr2);
            }
            $sku = $data[$title['自定义SKU']];
            //商品对应的sku数组
             $goodSku = [
                'goods_id'          => $goodsId,
                'sku'               => $sku,
                'thumb'             => $data[$title['产品图1']],
                'sku_attributes'    => $skuAttrValueArr ? json_encode($skuAttrValueArr) : '{}',
                'spu_name'          => $data[$title['产品中文名']],
                'weight'            => $weight,
                'status'            => $sellStatus,
                'cost_price'        => $data[$title['默认供货价']],
                'retail_price'      => $data[$title['B2C销售价']],
                'market_price'      => $data[$title['B2C市场价']],
                'create_time'       => time(),
            ];
            $skuId = ImportService::addGoodsSku($goodSku);
            //商品sku对应的提供商报价
            $supplierGoodsSku = [
                'goods_id'          => $goodsId,
                'sku_id'            => $skuId,
                'supplier_id'       => $supplierId,
                'link'              => $data[$title['网络采购链接']],
                'price'             => $data[$title['默认供货价']],
                'is_default'        => 1,
//                 'min_quantity'      => $data['min_quantity'],
                'create_time'       => time(),
            ];
            ImportService::addSupplierOfferGoodsSku($supplierGoodsSku);
            
            // 库存
            if(!empty($data[$title['中国本地仓库数量']])) {
                $list = [
                    'warehouse_id'       => 2,
                    'goods_id'           => $goodsId,
                    'sku_id'             => $skuId,
                    'sku'                => $sku,
                    'available_quantity' => $data[$title['中国本地仓库数量']],
                    'per_time'           => time(),
                    'per_cost'           => $data[$title['中国本地仓库金额']]/$data[$title['中国本地仓库数量']],
                    'created_time'       => time(),
                    'updated_time'       => time()
                ];
                $warehouseGoods = new WarehouseGoods();
                $warehouseGoods->allowField(true)->save($list);
            }
            if(!empty($data[$title['FBA仓库数量']])) {
                $list = [
                    'warehouse_id'       => 67,
                    'goods_id'           => $goodsId,
                    'sku_id'             => $skuId,
                    'sku'                => $sku,
                    'available_quantity' => $data[$title['FBA仓库数量']],
                    'per_time'           => time(),
                    'per_cost'           => $data[$title['FBA仓库金额']]/$data[$title['FBA仓库数量']],
                    'created_time'       => time(),
                    'updated_time'       => time()
                ];
                $warehouseGoods = new WarehouseGoods();
                $warehouseGoods->allowField(true)->save($list);
            }
        }
    }
    
    /**
     * 导入赛和的数据
     * 数据导入--翟彬接收 很大数据(春茂)
     * @method get
     * @url /import/saihe-goods
     * @title 导入赛盒数据
     */
    public function importGoodsSaihe()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $filename = ROOT_PATH . 'public' . DS . 'upload'. DS . 'saihe-data.csv';
        $file = @fopen($filename, 'r');
        $title = [];
        $i = 0;
        while ($data = fgetcsv($file)) {
            $data = eval('return '.iconv('gbk', 'utf-8', var_export($data,true)).';');
            if ($i == 0) {
                $i++;
                foreach ($data as $k=>$v) {
                    $title[$v] = $k;
                }
                continue;
            }

            $strCategory = $data[$title['OA女装']];
            $categoryId  = ImportService::addCategory($strCategory, false);
            $ebay = isset($this->platform_sales[$data[$title['亚马逊平台']]]) ? $this->platform_sales[$data[$title['亚马逊平台']]] : 1;
            $amazon = isset($this->platform_sales[$data[$title['速卖通平台']]]) ?$this->platform_sales[$data[$title['速卖通平台']]] : 1;
            $wish = isset($this->platform_sales[$data[$title['wish']]]) ? $this->platform_sales[$data[$title['wish']]] : 1;
            $aliExpress = isset($this->platform_sales[$data[$title['ebay']]]) ? $this->platform_sales[$data[$title['ebay']]] : 1;
            $platform_sale = '{"ebay":'. $ebay .',"amazon":' . $amazon .',"wish":'. $wish . ',"aliExpress":'. $aliExpress .'}'; 
            //商品数组
            $spu = empty($data[$title['母体ID']]) ? $data[$title['SKU']] : $data[$title['母体ID']];
            $weight = intval(str_replace('g', '', $data[$title['包装重量(g)']]));
            $goods = [
                'name'              => $data[$title['中文标题']],
                'spu'               => $spu,
                'height'            => $data[$title['包装尺寸(高cm)']]*10,
                'width'             => $data[$title['包装尺寸(宽cm)']]*10,
                'depth'             => $data[$title['包装尺寸(长cm)']]*10,
                'weight'            => $weight,
                'net_weight'        => $weight,
                'category_id'       => $categoryId,
                'declare_name'      => $data[$title['产品报关中文名']],
                'declare_en_name'   => $data[$title['产品报关英文名']],
                'hs_code'           => $data[$title['海关编码']],
                'cost_price'        => 0.00,
                'retail_price'      => $data[$title['参考售价(美金)']],
                'source_url'        => '', // $data[$title['参考链接']],
                'volume_weight'     => 6000,
                'status'            => 1,
                'transport_property' => 1,
                'platform_sale'     => $platform_sale,
                'create_time'       => 0,              
                'sales_status'      => 1,       
            ];
            
            $goodsId = ImportService::addGoods($goods);
            // 添加描述
            $description = [
                'lang_id'     => 1,
                'title'       => $data[$title['中文标题']],
                'description' => $data[$title['产品描述']],
                'goods_id'    => $goodsId
            ];
            ImportService::addDescription($description);
            // 英文描述
            $description = [
                'lang_id'     => 2,
                'title'       => $data[$title['英文标题']],
                'description' => '',
                'goods_id'    => $goodsId
            ];
            ImportService::addDescription($description);
            //属性值
            $color = $data[$title['颜色']];
            $size  = $data[$title['尺码属性']];
            $skuAttrValueArr = [];
            if ($color) {
                $values = $this->getValueId(1, $color);
                if($values['value_id'] == 0) {
                    Db::table('goods_attribute_log')->insert(['sku_id' => 0, 'sku' => $data[$title['SKU']], 'attribute_id' => 1, 'value' => $color]);
                } else {
                $skuAttrValueArr = ['attr_1' => $values['value_id']];
                // 属性
                $info = [
                    'attribute_id' => 1,
                    'goods_id'     => $goodsId,
                    'value_id'     => $values['value_id'],
                    'category_id'  => $categoryId
                ];
                $this->addAttribute($info);
                }
            }
            if ($size) {
                if ($size == 'One Size') {
                    $values['value_id'] = 105;
                    $attribute_id = 3;
                } else {
                    $values = $this->getValueId(2, $size);
                    $attribute_id = 2;
                }
                if($values['value_id'] == 0) {
                    Db::table('goods_attribute_log')->insert(['sku_id' => 0, 'sku' => $data[$title['SKU']], 'attribute_id' => 2, 'value' => $size]);
                } else {
                    $skuAttrValueArr['attr_2'] = $values['value_id'];
                    // 属性
                    $info = [
                        'attribute_id' => $attribute_id,
                        'goods_id' => $goodsId,
                        'value_id' => $values['value_id'],
                        'category_id' => $categoryId
                    ];
                    $this->addAttribute($info);
                }
            }
            
            //商品对应的sku数组
             $goodSku = [
                'goods_id'          => $goodsId,
                'sku'               => $data[$title['SKU']],
                'thumb'             => '',
                'sku_attributes'    => $skuAttrValueArr ? json_encode($skuAttrValueArr) : '{}',
                'spu_name'          => $data[$title['中文标题']],
                'weight'            => $weight,
                'status'            => 1,
                'cost_price'        => 0.00,
                'retail_price'      => $data[$title['参考售价(美金)']],
                'market_price'      => $data[$title['参考售价(美金)']],
                'create_time'       => time(),
            ];
            ImportService::addGoodsSku($goodSku);
        }
    }
    
    /**
     * 导入赛和的数据
     * 数据导入--翟彬接收
     * @method get
     * @url /import/saihe-stock
     * @title 导入赛盒库存
     */
    public function importStockSaihe()
    {  
        ini_set('memory_limit','512M');
        $data = self::readExcel(ROOT_PATH . 'public' . DS . 'upload/saihe.xlsx');var_dump($data);exit;
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $filename = ROOT_PATH . 'public' . DS . 'upload/saihe.csv';
        $file     = @fopen($filename, 'r');
        $title    = [];
        $i        = 0;
        while ($data = fgetcsv($file)) {
            $info = eval('return '.iconv('gbk', 'utf-8', var_export($data,true)).';');
            if ($i == 0) {
                $i++;
                foreach ($info as $k=>$v) {
                    $title[$v] = $k;
                }
                continue;
            }
            $sku     = $info[$title['自定义SKU']];
            $skuInfo = GoodsSku::where(['sku' => $sku])->find();
            if (empty($skuInfo)) {
                continue;
            }
            if(!empty($info[$title['中国本地仓库数量']])) {
                $list = [
                    'warehouse_id'       => 2,
                    'goods_id'           => $skuInfo['goods_id'],
                    'sku_id'             => $skuInfo['id'],
                    'sku'                => $sku,
                    'available_quantity' => $info[$title['中国本地仓库数量']],
                    'per_time'           => time(),
                    'per_cost'           => $info[$title['中国本地仓库金额']]/$info[$title['中国本地仓库数量']],
                    'created_time'       => time(),
                    'updated_time'       => time()
                ];
                $warehouseGoods = new WarehouseGoods();
                $warehouseGoods->allowField(true)->save($list);
            }
            if(!empty($info[$title['FBA仓库数量']])) {
                $list = [
                    'warehouse_id'       => 67,
                    'goods_id'           => $skuInfo['goods_id'],
                    'sku_id'             => $skuInfo['id'],
                    'sku'                => $sku,
                    'available_quantity' => $info[$title['FBA仓库数量']],
                    'per_time'           => time(),
                    'per_cost'           => $info[$title['FBA仓库金额']]/$info[$title['FBA仓库数量']],
                    'created_time'       => time(),
                    'updated_time'       => time()
                ];
                $warehouseGoods = new WarehouseGoods();
                $warehouseGoods->allowField(true)->save($list);
            }
        }
    }
    
    /**
     * 导入赛和的数据
     * 数据导入--翟彬接收
     * @url /import/saihe-purchase
     * @method get
     * @title 导入赛盒采购
     */
    public function importPurchaseSaihe()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $filename = ROOT_PATH . 'public' . DS . 'upload/purchase.csv';
        $file     = @fopen($filename, 'r');
        $title    = [];
        $i        = 0;
        while ($data = fgetcsv($file)) {
            $info = eval('return '.iconv('gbk', 'utf-8', var_export($data,true)).';');
            if ($i == 0) {
                $i++;
                foreach ($info as $k=>$v) {
                    $title[$v] = $k;
                }
                continue;
            }
            if ($info[$title['财务状态']] != '已付款') { // 未付款过滤
                continue;
            }
            $sku     = $info[$title['物料编码']];
            $skuInfo = GoodsSku::where(['sku' => $sku])->find();
            if (empty($skuInfo)) {
                continue;
            }
            $supplierInfo = [
                'company_name' => $info[$title['供应商名称']]
            ];
            $supplier = Supplier::where($supplierInfo)->find();
            if (!$supplier) {
                $supplier = new Supplier();
                $supplier->allowField(true)->save($supplierInfo);
            }
            $supplierGoodsSku = [
                'goods_id'          => $skuInfo->goods_id,
                'sku_id'            => $skuInfo->id,
                'supplier_id'       => $supplier->id,
                'link'              => '',
                'price'             => $info[$title['单价(RMB)']],
                'is_default'        => 1,
//                 'min_quantity'      => $data['min_quantity'],
                'create_time'       => strtotime($info[$title['下单时间']]),
            ];
            ImportService::addSupplierOfferGoodsSku($supplierGoodsSku);
        }
    }
    
    /**
     * @method get
     * @url /import/sku-map
     * @title 导入skuMap
     */
    public function importSkuMap()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $filename = ROOT_PATH . 'public' . DS . 'upload/skuMap.csv';
        $file = @fopen($filename, 'r');     
        $title = [];
        $i = 0;
        while ($data = fgetcsv($file)) {
            $data = eval('return '.iconv('gbk', 'utf-8', var_export($data,true)).';');
            if ($i == 0) {
                $i++;
                foreach ($data as $k=>$v) {
                    $title[$v] = $k;
                }
                continue;
            }
            // 过滤渠道SKU与自定义SKU
            if ($data[$title['渠道SKU']] == $data[$title['自定义SKU']]) {
                
                continue;
            }
            $row = [];
            $service = new ImportService();
            // 账号信息
            $row = $service->getAccount($data[$title['渠道来源']]);
            if (empty($row)) {
                \think\Log::write($data[$title['渠道来源']]);
                \think\Log::write($data[$title['渠道SKU']]);
                continue;
            }
            // SKU信息
            $row['sku_id'] = $service->getSkuIdBySku($data[$title['自定义SKU']]);
            if (empty($row['sku_id'])) {
                continue;
            }
            $row['sku_code'] = $data[$title['自定义SKU']];
            $row['channel_sku'] = $data[$title['渠道SKU']];
            $row['quantity'] = 1;
            $row['create_time'] = $row['update_time'] = time();
            $row['create_user_id'] = $row['update_user_id'] = 0;
            $goodsSkuMap = new GoodsSkuMap();
            if (!$goodsSkuMap::where(['sku_id' => $row['sku_id'], 'channel_id' => $row['channel_id'], 'account_id' => $row['account_id']])->count()) {
                $goodsSkuMap->allowField(true)->save($row);
            }
        }
    }
    
    /**
     * 多维数组去重
     * @param unknown $data 
     * @return multitype:unknown
     */
    private static function multiUnique($data = [])
    {
        $return = [];
        foreach($data as $k=>$v) {
            if (!in_array($v, $return)) {
                $return[$k]=$v;
            }
        }
        return $return;
    }
    /**
     * 按列读取excel
     * @param unknown_type $excelPath：excel路径
     * @param unknown_type $allColumn：读取的列数
     * @param unknown_type $sheet：读取的工作表
     * @param unknown_type $type：字段类型(指定字段为时间格式)
     * @return $data ：返回标题对应的值的二维数组
     */
    private static function readExcel($excelPath, $allColumn = 0, $sheet = 0, $extension = 'xlsx', $type = '')
    {
        ini_set('memory_limit', '1024M');
        $data = [];
        try {
        switch ($extension) {
            case 'xlsx':
                $phpReader = \PHPExcel_IOFactory::createReader('Excel2007');
                break;
            case 'xls':
                $phpReader = \PHPExcel_IOFactory::createReader('Excel5');
                break;
            default:
                return 'error file type';
        }
        } catch(Exception $e) {
            var_dump($e);
        }
        //载入excel文件
        $phpExcel  = new \PHPExcel();
        $phpExcel  = $phpReader->load($excelPath);
        //获取工作表总数
        $sheetCount = $phpExcel->getSheetCount();
        //判断是否超过工作表总数，取最小值
        $sheet = $sheet < $sheetCount ? $sheet : $sheetCount;
        //默认读取excel文件中的第一个工作表
        $currentSheet = $phpExcel->getSheet($sheet);
        if(empty($allColumn)) {
            //取得最大列号，这里输出的是大写的英文字母，ord()函数将字符转为十进制，65代表A
            //$allColumn = ord($currentSheet->getHighestColumn()) - 65 + 1;
            $allColumn = ord($currentSheet->getHighestColumn()) - 0 + 1;
        }
        //取得一共多少行
        $allRow = $currentSheet->getHighestRow();
    
        //从第二行开始输出，因为excel表中第一行为列名
        for($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
            for($currentColumn = 0; $currentColumn <= $allColumn - 1; $currentColumn++) {
//     			$val = \PHPExcel_Shared_Date::ExcelToPHP($currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getValue());
                $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getValue();
                $title = $currentSheet->getCellByColumnAndRow($currentColumn, 1)->getValue();
                /*if (empty($val) && !is_numeric($val)) {
                    continue;
                }
                if ($type) {
                    if (is_array($type)) {
                        foreach ($type as $v) {
                            if ($title[$currentColumn] == $v) {
                                $val=self::excelTime($val);
                            }
                        }
                    } else {
                        if ($title[$currentColumn] == $type) {
                            $val = self::excelTime($val);
                        }
                    }
                }
                if(is_object($val)) {
                    $val= $val->__toString();
                }*/
                // if(is_object($title[$currentColumn]))  $title[$currentColumn]= $title[$currentColumn]->__toString();
                $data[$currentRow - 2][$title] =  $val;
            }
        }
        //返回二维数组
        return $data;
    }
    
    /**
     * 将 Excel 时间转为标准的时间格式
     * @param $date
     * @param bool $time
     * @return array|int|string
     */
    private static function excelTime($date, $time = false)
    {
        if (function_exists('GregorianToJD')) {
            if (is_numeric( $date )) {
                $jd = GregorianToJD( 1, 1, 1970 );
                $gregorian = JDToGregorian( $jd + intval ( $date ) - 25569 );
                $date = explode( '/', $gregorian );
                $date_str = str_pad( $date [2], 4, '0', STR_PAD_LEFT )
                ."/". str_pad( $date [0], 2, '0', STR_PAD_LEFT )
                ."/". str_pad( $date [1], 2, '0', STR_PAD_LEFT )
                . ($time ? " 00:00:00" : '');
                return $date_str;
            }
        } else {
            $date=$date>25568 ? $date+1 : 25569;
            /*There was a bug if Converting date before 1-1-1970 (tstamp 0)*/
            $ofs=(70 * 365 + 17+2) * 86400;
            $date = date("Y-m-d",($date * 86400) - $ofs).($time ? " 00:00:00" : '');
        }
        return $date;
    }
    
    /**
     * @title 产品导入模板
     * @method get
     * @url /import/export
     */
    public function export()
    {
        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->getProperties()->setCreator("Rondaful")
                ->setLastModifiedBy("Rondaful")
                ->setTitle("产品模板")
                ->setSubject("产品模板")
                ->setDescription("产品模板")
                ->setKeywords("产品模板")
                ->setCategory("产品模板");
        // Add some data    
        $i = 65;
        foreach($this->_headers as $header) {
            $prefix = '';
            if ($i > 90) {
                $prefix = 'A';
                $j = $i - 26;
            } else {
                $j = $i;
            }
            $prefix .= chr($j) . "1";
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($prefix, $header);
            $i++;
        }
        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->setTitle('产品模板');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save(ROOT_PATH . 'public' . DS . 'upload/product.xlsx');       
    }
    
    private function match($value)
    {
        $match = '/([a-z\+ ]{3,})/i';
        $value = trim($value);
        $value = preg_replace('/([ ]{2,})/', ' ', $value);
        if (preg_match($match, $value, $match)) {
            $match = trim($match[1]);
            return preg_replace('/([ ]{2,})/', ' ', $match);
        }
        
        return '';
    }
    
    private function match5($value) 
    {
        $match = '/([A-Z]{2})/i';
        if (preg_match($match, $value, $matches)) {
            return $matches[1];
        }
        
        return '';
    }
    
    private function getValueId($attribute_id, $code)
    {
        if ($attribute_id == 1) {
            $code = $this->match($code);
            $code = firstUpper($code);
            if (isset($this->color_value[$code])) {
                $code = $this->color_value[$code];
            }
        }
        
        if ($attribute_id == 5) {
            $code = $this->match5($code);
        }
        
        $info = $this->getCacheAttribute($attribute_id.'_'.$code);
        $result = [
            'value_id' => 0,
            'value' => ''
        ];
        if ($info) {
            $result['value_id'] = $info['id'];
            list($result['value']) = explode('|', $info['value']);
        }
        
        return $result;
    }
    
    private function getCacheAttribute($key) 
    {
        static $redis = null;
        $hash_key = 'hash:attributeValue';
        if (is_null($redis)) {
            $redis = Cache::handler();
        }/*
        $attribute = new AttributeValue();
        $lists = $attribute->select();
        foreach($lists as $list) {
            $redis->hSet($hash_key, $list['attribute_id'] . '_'. $list['code'], json_encode($list));
        }*/
        if ($redis->hExists($hash_key, $key)) {
            $result = json_decode($redis->hGet($hash_key, $key), true);
        } else {
            $result = [];
        }
        return $result;
    }
    
    /**
     * @title 导入属性
     * @method get
     * @url /import/attribute
     * 导入属性数据
     * 数据导入--翟彬接收
     */
    public function importAttribute()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $filename = ROOT_PATH . 'public' . DS . 'upload/attribute.csv';
        $file     = @fopen($filename, 'r');
        $title    = [];
        $i        = 0;
        while ($data = fgetcsv($file)) {
            $info = eval('return '.iconv('gbk', 'utf-8', var_export($data,true)).';');
            if ($i == 0) {
                $i++;
                foreach ($info as $k=>$v) {
                    $title[$v] = $k;
                }
                continue;
            }
            $attribute = $info[$title['属性中英文名称']];
            $attributeInfo = Attribute::where(['name' => $attribute])->find();
            $value = $info[$title['属性详情']];
            $values = explode('|', $value);
            if (count($values) == 2) {
                $code = $values[1];
            } else {
                $code = $value;
            }
            
            $insertData = [
                'attribute_id' => $attributeInfo->id,
                'code'         => $code,
                'value'        => $value,
                'status'       => 1,
                'create_time'  => time()
            ];
            
            $attributeValue = new AttributeValue();
            $attributeValue->allowField(true)->save($insertData);          
        }
    }
    
    /**
     * @title 导入属性值
     * @url /import/attribute-value
     * @method get
     */
    public function importAttributeValue()
    {
        $i = 65;
        for($i = 65; $i <=90; $i++) {
            $value = chr($i);
            $insertData = [
                'attribute_id' => 13,
                'code'         => $value,
                'value'        => $value,
                'status'       => 1,
                'create_time'  => time()
            ];
            
            $attributeValue = new AttributeValue();
            $attributeValue->allowField(true)->save($insertData); 
        }
    }
    
    /**
     * 处理产品物流属性
     * @param type $str
     * @return int
     */
    private function matchTransportProperty($str)
    {
        $result = 0;
        $tags = explode(',', $str);
        foreach($tags as $tag) {
            $tag = trim($tag);
            if (isset($this->properties[$tag])) {
                $result += $this->properties[$tag]['value'];
            } else {
                
            }
        }
        
        if ($result == 0) {
            $result = 1;
        }
        
        return $result;
    }
    
    private function properties()
    {
        $goodsHelp = new GoodsHelp();
        $properties = $goodsHelp->getTransportProperies();
        foreach($properties as $property) {
            $this->properties[$property['name']] = $property;
        }
    }
}