<?php
namespace app\publish\task;
/**
 * rocky
 * 17-4-17
 * ebay获取账号自定义类目
*/

use app\index\service\AbsTasker;
use app\common\model\ebay\EbayAccount;
use app\common\model\ebay\EbayCustomCategory;
use service\ebay\EbayApi;
use think\Db;
use think\Exception;

class EbayGetStore extends AbsTasker
{
    private $token;
    public function getName()
    {
        return "ebay获取账号自定义类目";
    }
    
    public function getDesc()
    {
        return "ebay获取账号自定义类目";
    }
    
    public function getCreator()
    {
        return "曾绍辉";
    }
    
    public function getParamRule()
    {
        return [];
    }
    
    
    public function execute()
    {
        self::getStore();
    }

    public function getStore(){
        set_time_limit(0); 
        $verb = "GetStore";
        $ebayAccount = new EbayAccount();#获取token
        $cusCa = new EbayCustomCategory();
        $acInfo = $ebayAccount->getAccountUpcus();
        $config = $this->createConfig($acInfo,0,$verb);
        $ebayApi = new EbayApi($config);
        $xml = $this->createXml($this->token);
        $resText = $ebayApi->createHeaders()->__set("requesBody",$xml)->sendHttpRequest2();
        // echo "<pre>";
        // print_r($resText);

        if(isset($resText['GetStoreResponse'])){
            $response = $resText['GetStoreResponse'];
            if($response['Ack']=="Success" && isset($response['Store']['CustomCategories']['CustomCategory'])){
//                $stores=isset($response['Store']['CustomCategories']['CustomCategory'][0])?$response['Store']['CustomCategories']['CustomCategory']:array($response['Store']['CustomCategories']['CustomCategory']);
                $categories = isset($response['Store']['CustomCategories']['CustomCategory'][0])?$response['Store']['CustomCategories']['CustomCategory']:array($response['Store']['CustomCategories']['CustomCategory']);
                $updateCategories = [];
                $categoryIds = [];
                $this->packCategories($categories,0,$updateCategories,$categoryIds,$acInfo['id']);
                $oldCategoryIds = EbayCustomCategory::where(['account_id'=>$acInfo['id']])->column('category_id');
                $needInsertCategoryIds = array_diff($categoryIds,$oldCategoryIds);//需要插入的
                $needDelCategoryIds = array_diff($oldCategoryIds,$categoryIds);//需要删除的
                $needUpdateCategoryIds = array_diff($categoryIds,$needInsertCategoryIds);//需要更新的
                try {
                    Db::startTrans();
                    //删除
                    if (!empty($needDelCategoryIds)) {
                        EbayCustomCategory::destroy(['account_id' => $acInfo['id'], 'category_id' => ['in', $needDelCategoryIds]]);
                    }
                    //插入
                    if (!empty($needInsertCategoryIds)) {
                        $insertCategories = [];
                        $tmpItems = $updateCategories;
                        foreach ($tmpItems as $k => $tmpItem) {
                            if (in_array($tmpItem['category_id'], $needInsertCategoryIds)) {
                                $insertCategories[] = $tmpItem;
                                unset($updateCategories[$k]);//释放掉新增的，剩下的都是需要更新的
                                unset($categoryIds[$k]);//同时释放掉id,与上面的保持索引一致
                            }
                        }
                        (new EbayCustomCategory())->saveAll($insertCategories, false);
                    }

                    //更新
                    if (!empty($needUpdateCategoryIds)) {
                        //获取旧信息
                        $wh['account_id'] = $acInfo['id'];
                        $wh['category_id'] = ['in', $needUpdateCategoryIds];
                        $updateField = 'id,category_id';
                        $needUpdateItems = EbayCustomCategory::field($updateField)->where($wh)->select();
                        $newItemIds = array_flip($categoryIds);
                        //将主键id组装到更新信息中，以便批量更新
                        foreach ($needUpdateItems as $needUpdateItem) {
                            $index = $newItemIds[$needUpdateItem['category_id']];
                            $updateCategories[$index]['id'] = $needUpdateItem['id'];
                        }
                        $updateItems = array_values($updateCategories);
                        (new EbayCustomCategory())->saveAll($updateItems);
                    }
                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                    throw new Exception($e->getMessage());
                }
                //                foreach($stores as $k => $v){
//                    $this->syncCustomCategories($v,$acInfo->id,0,$cusCa);
//                }
            }
        }
    }

    public function getStoreImmediately($accountId)
    {
        set_time_limit(0); 
        $verb = "GetStore";
        $ebayAccount = new EbayAccount();#获取token
        $cusCa = new EbayCustomCategory();
        $acInfo = $ebayAccount->get($accountId);
        $config = $this->createConfig($acInfo,0,$verb);
        $ebayApi = new EbayApi($config);
        $xml = $this->createXml($this->token);
        $resText = $ebayApi->createHeaders()->__set("requesBody",$xml)->sendHttpRequest2();
        // echo "<pre>";
        // print_r($resText);#die;
        if(isset($resText['GetStoreResponse'])){
            $response = $resText['GetStoreResponse'];
            if($response['Ack']=="Success" && isset($response['Store']['CustomCategories']['CustomCategory'])){
                $categories = isset($response['Store']['CustomCategories']['CustomCategory'][0])?$response['Store']['CustomCategories']['CustomCategory']:array($response['Store']['CustomCategories']['CustomCategory']);
                $updateCategories = [];
                $categoryIds = [];
                $this->packCategories($categories,0,$updateCategories,$categoryIds,$accountId);
                $oldCategoryIds = EbayCustomCategory::where(['account_id'=>$accountId])->column('category_id');
                $needInsertCategoryIds = array_diff($categoryIds,$oldCategoryIds);//需要插入的
                $needDelCategoryIds = array_diff($oldCategoryIds,$categoryIds);//需要删除的
                $needUpdateCategoryIds = array_diff($categoryIds,$needInsertCategoryIds);//需要更新的
                try {
                    Db::startTrans();
                    //删除
                    if (!empty($needDelCategoryIds)) {
                        EbayCustomCategory::destroy(['account_id' => $accountId, 'category_id' => ['in', $needDelCategoryIds]]);
                    }
                    //插入
                    if (!empty($needInsertCategoryIds)) {
                        $insertCategories = [];
                        $tmpItems = $updateCategories;
                        foreach ($tmpItems as $k => $tmpItem) {
                            if (in_array($tmpItem['category_id'], $needInsertCategoryIds)) {
                                $insertCategories[] = $tmpItem;
                                unset($updateCategories[$k]);//释放掉新增的，剩下的都是需要更新的
                                unset($categoryIds[$k]);//同时释放掉id,与上面的保持索引一致
                            }
                        }
                        (new EbayCustomCategory())->saveAll($insertCategories, false);
                    }

                    //更新
                    if (!empty($needUpdateCategoryIds)) {
                        //获取旧信息
                        $wh['account_id'] = $accountId;
                        $wh['category_id'] = ['in', $needUpdateCategoryIds];
                        $updateField = 'id,category_id';
                        $needUpdateItems = EbayCustomCategory::field($updateField)->where($wh)->select();
                        $newItemIds = array_flip($categoryIds);
                        //将主键id组装到更新信息中，以便批量更新
                        foreach ($needUpdateItems as $needUpdateItem) {
                            $index = $newItemIds[$needUpdateItem['category_id']];
                            $updateCategories[$index]['id'] = $needUpdateItem['id'];
                        }
                        $updateItems = array_values($updateCategories);
                        (new EbayCustomCategory())->saveAll($updateItems);
                    }
                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                    return ['result'=>false,'message'=>$e->getMessage()];
                }
//                foreach ($categories as $k => $category) {
//                    $updateCategories[$i]['category_id'] = $category['CategoryID'];
//                    $updateCategories[$i]['name'] = $category['Name'];
//                    $updateCategories[$i]['order'] = empty($category['Order']) ? 0 : (int)$category['Order'];
//                    if (isset($category['ChildCategory'])) {
//
//                    }
//
//                }
                //                foreach($stores as $k => $v){
//                    $this->syncCustomCategories($v,$acInfo->id,0,$cusCa);
//                }
            }
            return ['result'=>true,'message'=>'同步成功！'];
        }else{
            return ['result'=>false,'message'=>'同步失败！'];
        }

    }

    private function packCategories($categories, $parentId=0, &$package=[],&$categoryIds=[],$accountId)
    {
        try {
            $tmp = $categories;
            foreach ($tmp as $t) {
                $tmpCategory = [
                    'category_id' => $t['CategoryID'],
                    'name' => $t['Name'],
                    'order' => empty($t['Order']) ? 0 : (int)$t['Order'],
                    'parent_id' => $parentId,
                    'account_id' => $accountId
                ];
                array_push($categoryIds, $t['CategoryID']);
                array_push($package, $tmpCategory);
                if (isset($t['ChildCategory'])) {
                    !isset($t['ChildCategory'][0]) && $t['ChildCategory'] = [$t['ChildCategory']];
                    $this->packCategories($t['ChildCategory'], $t['CategoryID'],$package,$categoryIds, $accountId);
                }
            }
        } catch (Exception $e) {
            return ['result'=>false, 'message'=>$e->getFile().'|'.$e->getLine().'|'.$e->getMessage()];
        }
    }

    public function createConfig($acInfo,$site,&$verb)
    {
        $tokenArr = json_decode($acInfo['token'],true);
        $this->token = trim($tokenArr[0])?$tokenArr[0]:$acInfo['token'];
        $config['devID']=$acInfo['dev_id'];
        $config['appID']=$acInfo['app_id'];
        $config['certID']=$acInfo['cert_id'];
        $config['userToken']=$this->token;
        $config['compatLevel']=957;
        $config['siteID']=$site;
        $config['appMode']=0;
        $config['account_id']=$acInfo['id'];
        $config['verb'] = $verb;
        return $config;
    }

    public function syncCustomCategories($data,$account_id,$parent_id,$cusCa){#同步店铺类目
        $rows['category_id'] = $data['CategoryID'];
        $rows['name'] = isset($data['Name'])?$data['Name']:"";
        $rows['order'] = trim($data['Order'])?$data['Order']:0;
        $rows['parent_id'] = $parent_id;
        $rows['account_id'] = $account_id;
        $cusCa->syncCustomCategory($rows);
        
        if(isset($data['ChildCategory'])){
            if(is_array($data['ChildCategory'])){
                $childs=isset($data['ChildCategory'][0])?$data['ChildCategory']:array($data['ChildCategory']);
                foreach($childs as $k => $v){
                    $this->syncCustomCategories($v,$account_id,$data['CategoryID'],$cusCa);
                }
            }
        }
    }

    #创建请求xml
    public function createXml($token){
        $requesBody ='<?xml version="1.0" encoding="utf-8"?>';
        $requesBody.='<GetStoreRequest xmlns="urn:ebay:apis:eBLBaseComponents">';
        $requesBody.='<RequesterCredentials>';
        $requesBody.='<eBayAuthToken>'.$token.'</eBayAuthToken>';
        $requesBody.='</RequesterCredentials>';
        $requesBody.='</GetStoreRequest>';
        return $requesBody;
    }

}