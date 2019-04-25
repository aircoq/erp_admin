<?php

/**
 * 速卖通商品橱窗信息
 * Description of AliexpressWindowProducts
 * @datetime 2017-7-5  14:59:41
 * @author joy
 */

namespace app\listing\task;
use app\common\cache\Cache;
use app\index\service\AbsTasker;
use app\publish\service\AliexpressApiService;
use app\publish\service\AliexpressService;
use service\aliexpress\AliexpressApi;
use app\common\model\aliexpress\AliexpressAccount;
use app\common\model\aliexpress\AliexpressWindow;
use app\common\exception\TaskException;
use think\Db;
use think\Exception;
use think\exception\DbException;
use think\exception\PDOException;

class AliexpressWindowProducts extends AbsTasker{
    /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return "速卖通获取卖家橱窗商品目前使用情况详情";
    }
    /**
     * 定义任务描述
     * @return string
     */
    public function getDesc()
    {
        return "速卖通获取卖家橱窗商品目前使用情况详情";
    }
    /**
     * 定义任务作者
     * @return string
     */
    public function getCreator()
    {
        return "joy";
    }
    /**
     * 定义任务参数规则
     * @return array
     */
    public function getParamRule()
    {
        return [];
    }
    /**
     * 任务执行内容
     * @return void
     */
    public  function execute()
    {
        set_time_limit(0);
        $window =new AliexpressWindow;
        $accounts = Cache::store('AliexpressAccount')->getAccounts();
        if ($accounts) {
            foreach ($accounts as $account) {

                if (isset($account['is_invalid']) &&  isset($account['is_authorization']) && $account['is_invalid'] && $account['is_authorization']) {
                    $this->getWindowProducts($account,$window);
                }
            }
        }

    }
    private  function getWindowProducts(array $account,$window)
    {
        if(!($window instanceof AliexpressWindow))
        {
            return ;
        }
        $param['module']='product';
        $param['class']='product';
        $param['action']='getwindowproducts';
        $params = array_merge($account,$param);
        $response = AliexpressService::execute($params);
        if(isset($response['success']) && $response['success'])
        {
            $data=array();
            $data=[
                'account_id'=>$account['id'],
                'window_count'=>$response['windowCount']?:0,
                'used_count'=>$response['usedCount']?:0,
                'window_products'=> json_encode($response['windowProducts']),
                'product_list'=> json_encode($response['productList']),
            ];

            Db::startTrans();
            try{

                if($row = $window->get(['account_id'=>$account['id']]))
                {
                    $data['update_time']= time();
                    AliexpressWindow::where('id','=',$row['id'])->update($data);
                    //$window->isUpdate(true)->save($data,['id'=>$row['id']]);
                }else{
                    $data['create_time']= time();
                    (new AliexpressWindow())->isUpdate(false)->save($data);
                }
                Db::commit();
            } catch (PDOException $exp){
                Db::rollback();
                throw new TaskException($exp->getFile().$exp->getLine().$exp->getMessage());
            }catch (DbException $exp){
                Db::rollback();
                throw new TaskException($exp->getFile().$exp->getLine().$exp->getMessage());
            }catch (Exception $exp){
                Db::rollback();
                throw new TaskException($exp->getFile().$exp->getLine().$exp->getMessage());
            }
        }
    }
}
