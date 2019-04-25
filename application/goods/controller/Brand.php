<?php
namespace app\goods\controller;

use think\Exception;
use think\Request;
use app\common\controller\Base;
use app\common\model\Brand as brandModel;
use app\common\model\Goods as GoodsModel;
use app\common\cache\Cache;

/**
 * Class Brand
 * @module 商品系统
 * @title 品牌管理
 * @author ZhaiBin
 * @package app\goods\controller
 */
class Brand extends Base
{
    /**
     * 显示资源列表
     * @title 品牌列表
     * @url /brand
     * @method get
     * @return \think\Response
     */
    public function index()
    {
        $request = Request::instance();
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 10);
        $name = $request->get('name', '');
        $model = new brandModel();        
        $where=[];
        if($name){
            $where['name']=['like','%'.$name.'%'];
        }
        $count = $model->where($where)->count();
        $brandList = $model->field('*')->where($where)->page($page, $pageSize)->select();
        foreach ($brandList as &$vo){
            $vo['logo'] = empty($vo['logo'])?'':$_SERVER['HTTP_HOST'].'/'.$vo['logo'];
        }
        $result = [
            'data' => $brandList,
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
        ];
        return json($result, 200);
        
    }

    /**
     * 保存新建的资源
     * @title 保存品牌
     * @method post
     * @url /brand
     * @param  \think\Request $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        $data['name'] = $request->post('name', '');
        $data['code'] = $request->post('code', '');
        $data['site_url'] = $request->post('site_url', '');
        $data['description'] = $request->post('description', '');
        $file = $request->post('file', '');
        $data['create_time'] = time();
        $data['update_time'] = time();

        //如果有图片上传图片
        if($file){
            $fileResult = $this->base64DecImg($file, 'upload/brand/' . date('Y-m-d'), time());

            $data['logo'] = param($fileResult, 'filePath');
        }
        $brandModel = new brandModel();
        $validateBrand = validate('Brand');
        if (!$validateBrand->check($data)) {
            return json(['message' => $validateBrand->getError()], 400);
        }
        $bool = $brandModel->allowField(true)->isUpdate(false)->save($data);
        $id = $brandModel->id;
        //删除缓存
        Cache::handler()->del('cache:brand');
        if ($bool) {
            return json(['message' => '新增成功','data'=>$data], 200);
        } else {
            return json(['message' => '新增失败'], 500);
        }
        
    }

    /**
     * 显示指定的资源
     * @title 编辑品牌
     * @url /brand/:id(\d+)/edit
     * @method get
     * @param  int $id
     * @return \think\Response
     */
    public function edit($id)
    {
        if (!is_numeric($id)) {
            return json(['message' => '参数错误'], 400);
        }
        $brandModel = new brandModel();
        $result = $brandModel->field('*')->where(['id' => $id])->find();
        $result['logo'] =  empty($result)?'':$_SERVER['HTTP_HOST'].'/'.$result['logo'];
        $result = empty($result) ? [] : $result;
        return json($result, 200);
    }

    /**
     * 保存更新的资源
     * @title 更新品牌
     * @url /brand/:id(\d+)
     * @method put
     * @param  \think\Request $request
     * @param  int $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        if (!is_numeric($id)) {
            return json(['message' => '参数错误'], 400);
        }
        $params = $request->param();
        $data['id']   = $id;
        $data['name'] = isset($params['name']) ? $params['name'] : '';
        $data['code'] = isset($params['code'])?$params['code']:'';
        $data['site_url'] = isset($params['site_url'])?$params['site_url']:'';
        $data['description'] = isset($params['description']) ? $params['description'] : '';
        $data['update_time'] = time();
        $brandModel = new brandModel();
        if (!$info = $brandModel->field('id,logo')->where(['id'=>$id])->find()) {
            return json(['message' => '该品牌不存在'], 500);
        }
        //判断名称是否重复
        $validateBrand = validate('Brand');
        if (!$validateBrand->check($data)) {
            return json(['message' => $validateBrand->getError()], 500);
        }
        
        // 更新logo图片
        if(param($params, 'file')){
            $file = $params['file'];
            $fileResult = $this->base64DecImg($file, 'upload/brand/' . date('Y-m-d'), time());
            $data['logo'] = $fileResult['filePath'];
            //删除原来的图片
            if(param($info, 'logo')){
                $filePath = ROOT_PATH . 'public' . DS .$info['logo'];
                @unlink($filePath);
            }             
        }
        
        $result = $brandModel->allowField(true)->save($data, ['id' => $id]);
        //删除缓存
        Cache::handler()->del('cache:brand');
        if ($result) {
            return json(['message' => '更新成功'], 200);
        } else {
            return json(['message' => '更新失败'], 500);
        }
    }

    /**
     * 删除指定资源
     * @title 删除品牌
     * @method delete
     * @url /brand/:id(\d+)
     * @param  int $id
     * @return \think\Response
     */
    public function delete($id)
    {
        $brandModel      = new brandModel();
        if (!is_numeric($id)) {
            return json(['message' => '参数错误'], 400);
        }
        if (GoodsModel::where(['brand_id' => $id])->count()) {
            return json(['message' => '品牌在产品使用中'], 400);
        }
        $result = $brandModel->where(['id' => $id])->delete();        
        if ($result) {
            //删除缓存
            Cache::handler()->del('cache:brand');
            return json(['message' => '删除成功'], 200);            
        } else {
            return json(['message' => '删除失败'], 500);
        }
    }
    
    /**
     * @title 获取品牌字段值
     * @url /brand/dictionary
     * @method get
     * @return \think\Response
     */
    public function dictionary()
    {
        $result = Cache::store('brand')->getBrand();
        
        return json($result, 200);
    }
    
    /**
     * @title 产品品牌风险字典
     * @method get
     * @url /tort/dictionary
     * @public
     * @return \think\Response
     */
    public function tortDictionary()
    {
        $result = Cache::store('brand')->getTort();
        
        return json($result, 200);
    }
    
    
    /**
     * @disable
     * @title 处理图片
     * 反编译data/base64数据流并创建图片文件
     *
     * @author Lonny ciwdream@gmail.com
     * @param string $baseData
     *            data/base64数据流
     * @param string $Dir
     *            存放图片文件目录
     * @param string $fileName
     *            图片文件名称(不含文件后缀)
     * @return mixed 返回新创建文件路径或布尔类型
     */
    private function base64DecImg($baseData, $Dir, $fileName)
    {
        $base_path = ROOT_PATH . '/public/';
        $imgPath = $base_path . '/' . $Dir;
        try {
            if (! is_dir($imgPath) && ! mkdir($imgPath, 0777, true)) {
                return false;
            }
            $expData = explode(';', $baseData);
            $postfix = explode('/', $expData[0]);
            if (strstr($postfix[0], 'image')) {
                $postfix = $postfix[1] == 'jpeg' ? 'jpg' : $postfix[1];
                $storageDir = $imgPath . '/' . $fileName . '.' . $postfix;
                $export = base64_decode(str_replace("{$expData[0]};base64,", '', $baseData));
                file_put_contents($storageDir, $export);
                return [
                        'fileName' => $fileName,
                        'filePath' => $Dir . '/' . $fileName . '.' . $postfix
                ];
    
              
            } else {
                return false;
            }
        } catch (Exception $e) {
            return false;
        }
    }
    
    
}