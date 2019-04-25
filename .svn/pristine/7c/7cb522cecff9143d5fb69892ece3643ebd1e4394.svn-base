<?php
/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2017/5/10
 * Time: 14:10
 */

namespace app\publish\task;

use app\common\exception\TaskException;
use app\common\model\aliexpress\AliexpressCategory;
use app\common\model\aliexpress\AliexpressNeedSizeCategory;
use app\common\model\aliexpress\AliexpressSizeTemplate;
use app\index\service\AbsTasker;
use app\publish\service\AliexpressTaskHelper;
use app\common\cache\Cache;
use service\aliexpress\AliexpressApi;
use think\Exception;

class AliexpressCategoryHasSize extends AbsTasker
{
    public  function getName()
    {
        return 'Aliexpress-检测分类是否需要尺寸模板';
    }

    public  function getDesc()
    {
        return 'Aliexpress-检测分类是否需要尺寸模板';
    }

    public  function getCreator()
    {
        return 'joy';
    }

    public  function getParamRule()
    {
        return [];
    }

    public  function execute()
    {
        set_time_limit(0);
        try{
            //获取所有授权并已启用账号
            $page=1;
            $pageSize=100;
            $model = new AliexpressCategory();
            do{
                $arrCategory = $model->where('category_isleaf=1')->field('*')->page($page,$pageSize)->select();
                if(empty($arrCategory))
                {
                    break;
                }else{
                    $config = Cache::store('AliexpressAccount')->getAccountById(34);
                    self::grabData($config,$arrCategory);
                    $page = $page + 1;
                }
            }while(count($arrCategory) == $pageSize);
        }catch (Exception $ex){
            throw new TaskException($ex->getMessage());
        }
    }
    public static function grabData($config,$items)
    {

        $helpServer = new AliexpressTaskHelper();
        foreach($items as $item)
        {
            $response = $helpServer->sizeModelIsRequiredForPostCat($config,$item['category_id']);
            if(is_bool($response) && $response===true){
                $item->required_size_model = 1;
                $item->save();
            }
        }
    }
}