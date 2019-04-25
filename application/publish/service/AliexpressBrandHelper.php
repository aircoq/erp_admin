<?php
/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2017/6/26
 * Time: 15:20
 */

namespace app\publish\service;


use app\common\cache\Cache;
use app\common\exception\JsonErrorException;
use app\common\model\aliexpress\AliexpressAccountBrand;
use app\common\model\aliexpress\AliexpressCategoryAttr;
use app\common\model\aliexpress\AliexpressCategoryAttrVal;
use app\common\model\aliexpress\AliexpressProductGroup;
use app\common\validate\AliexpressAccount;
use app\publish\validate\AliBrandValidate;
use erp\AbsServer;
use think\Db;
use think\Exception;
use app\common\model\aliexpress\AliexpressAccount as AliexpressAccountModel;

class AliexpressBrandHelper extends AbsServer
{
    private $_validate;
    public function __construct()
    {
        parent::__construct();
        $this->_validate = new AliBrandValidate();
    }

    /**
     * 获取分类下所有品牌
     * @param $params
     * @return mixed
     */
    public function brands($params)
    {

        /*$this->checkParams($params,'barand');
        $brandModel = new AliexpressCategoryAttr();
        $where['category_id'] = $params['category_id'];
        $where['id'] = 2;

        $brands = $brandModel->where($where)->find();

        if(empty($brands)||empty($brands['list_val'])){
            return [];
        }
        //获取当前账号已有品牌
        $accountBrandModel = new AliexpressAccountBrand();
        $ownedBrand = $accountBrandModel->field('attr_value_id')
            ->where(['account_id'=>$params['account_id'],'category_id'=>$params['category_id']])
            ->select();

        $arrOwned = [];
        if(!empty($ownedBrand)){
            $ownedBrand = collection($ownedBrand)->toArray();
            $arrOwned = array_column($ownedBrand,'attr_value_id');
        }

        $brands_list = [];
        foreach($brands['list_val'] as $k=>$brand){
            $brands_list[$k] = $brand;
            if(in_array($brand['id'],$arrOwned)){
                $brands_list[$k]['checked'] = true;
            }else{
                $brands_list[$k]['checked'] = false;
            }
        }
        return $brands_list;*/

        //获取当前账号已有品牌
        $accountBrandModel = new AliexpressAccountBrand();
        $brands_list = $accountBrandModel->alias('a')->field('t.*')
            ->join('aliexpress_category_attr_val t','t.id = a.attr_value_id','INNER')
            ->where(['a.account_id'=>$params['account_id'],'a.category_id'=>$params['category_id']])
            ->select();

        return $brands_list;
    }

    /**
     * 保存品牌设置
     * @param $params
     * @return bool
     */
    public function saveAccountBrands($params)
    {
        //$this->checkParams($params,'save');
        $brands = explode(',',$params['brands']);
        $categories = explode(',',$params['category_id']);
        $is_delete = isset($params['is_delete'])?$params['is_delete']:0;
        foreach ($categories as $category)
        {
            Db::startTrans();
            try{
                if($is_delete)
                {
                    AliexpressAccountBrand::destroy(['account_id'=>$params['account_id'],'category_id'=>$category]);
                }
                $model = new AliexpressAccountBrand();
                $data=[];
                foreach($brands as $brand)
                {
                    $where=[
                        'account_id'=>['=',$params['account_id']],
                        'category_id'=>['=',$category],
                        'attr_value_id'=>['=',$brand]
                    ];

                    $auth_brand = [
                        'account_id'=>$params['account_id'],
                        'category_id'=>$category,
                        'attr_value_id'=>$brand
                    ];

                    if($res = $model->where($where)->find())
                    {
                        $auth_brand['id'] = $res['id'];
                    }

                    $data []=$auth_brand;
                }
                $model->saveAll($data,true);
                Db::commit();
            }catch (Exception $exp){
                Db::rollback();
            }
        }
        return '设置成功';
    }

    /**
     * 更新品牌数据
     * @param $params
     * @return bool
     */
    public function synBrands($params)
    {
        try{

            $accountInfo = (new AliexpressAccountModel())->where(['id' => $params['account_id']])->find();

            $accountInfo = $accountInfo->toArray();
            $result = (new AliexpressTaskHelper())->getAeBrandAttribute($accountInfo,['category_id' => $params['category_id']]);
            return $result;
        }catch(Exception $exception){
            return false;
        }
    }

    /**
     * 参数验证
     * @param $params
     * @param $scene
     */
    private function checkParams($params,$scene)
    {
        $result = $this->_validate->scene($scene)->check($params);
        if (true !== $result) {
            // 验证失败 输出错误信息
            throw new JsonErrorException('参数验证失败：' . $this->_validate->getError());
        }
    }

    /**
     * 更新产品分组
     * @param $params
     * @return bool
     */
    public function rsyncGroups($params)
    {
        try{
            $this->checkParams($params,'group');
            $service = $this->invokeServer(AliexpressTaskHelper::class);
            $account = Cache::store('AliexpressAccount')->getAccountById($params['account_id']);
            $model = new AliexpressProductGroup();
            $result = $service->getAeGroups($account);
            return $result;
        }catch(JsonErrorException $exception){
            throw new JsonErrorException($exception->getMessage());
        }
    }

    /**
     * 同步运费模板
     * @param $params
     * @return mixed
     */
    public function rsyncTransport($params)
    {
        try{
            $this->checkParams($params,'transport');
            $service = $this->invokeServer(AliexpressTaskHelper::class);
            $account = Cache::store('AliexpressAccount')->getAccountById($params['account_id']);
            $result = $service->getAeTransport($account);
            return $result;
        }catch(JsonErrorException $exception){
            throw new JsonErrorException($exception->getMessage());
        }
    }

    /*
     * 同步服务模板
     * @param $params
     * @return mixed
     */
    public function rsyncPromise($params)
    {
        try{
            $this->checkParams($params,'promise');
            $service = $this->invokeServer(AliexpressTaskHelper::class);
            $account = Cache::store('AliexpressAccount')->getAccountById($params['account_id']);
            $result = $service->getAePromise($account);
            return $result;
        }catch(JsonErrorException $exception){
            throw new JsonErrorException($exception->getMessage());
        }
    }
}