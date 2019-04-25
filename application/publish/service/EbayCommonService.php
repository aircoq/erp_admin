<?php
namespace app\publish\service;

use app\common\model\ebay\EbayAccount;
use think\Db;
use think\Exception;
use app\common\model\User;
use app\common\model\ebay\EbayCommonChoice;
use app\common\model\ebay\EbayCommonExclude;
use app\common\model\ebay\EbayCommonBargaining;
use app\common\model\ebay\EbayCommonCate;
use app\common\model\ebay\EbayCommonCounter;
use app\common\model\ebay\EbayCommonGallery;
use app\common\model\ebay\EbayCommonIndividual;
use app\common\model\ebay\EbayCommonLocation;
use app\common\model\ebay\EbayCommonPickup;
use app\common\model\ebay\EbayCommonQuantity;
use app\common\model\ebay\EbayCommonReceivables;
use app\common\model\ebay\EbayCommonRefuseBuyer;
use app\common\model\ebay\EbayCommonReturn;
use app\common\model\ebay\EbayCommonTemplate;
use app\common\model\ebay\EbayCommonTrans;
use app\common\model\ebay\EbayCommonTransDetail;
use app\common\model\ebay\EbayModelComb;
use app\common\model\ebay\EbayModelPromotion;
use app\common\model\ebay\EbayModelSale;
use app\common\model\ebay\EbayModelStyle;
use app\common\model\ebay\EbayModTrans;
use app\common\model\ebay\EbayListing;

/** Ebay刊登范本
 * User: zengshaohui
 * Date: 2017/6/3
 */

class EbayCommonService 
{
    private $listMod = [
        'comb' => '\\app\\common\\model\\ebay\\EbayModelComb',
        'sale' => '\\app\\common\\model\\ebay\\EbayModelSale',
        'bargaining' => '\\app\\common\\model\\ebay\\EbayCommonBargaining',
        'choice' => '\\app\\common\\model\\ebay\\EbayCommonChoice',
        'exclude' => '\\app\\common\\model\\ebay\\EbayCommonExclude',
        'gallery' => '\\app\\common\\model\\ebay\\EbayCommonGallery',
        'pickup' => '\\app\\common\\model\\ebay\\EbayCommonPickup',
        'location' => '\\app\\common\\model\\ebay\\EbayCommonLocation',
        'individual' => '\\app\\common\\model\\ebay\\EbayCommonIndividual',
        'quantity' => '\\app\\common\\model\\ebay\\EbayCommonQuantity',
        'receivables' => '\\app\\common\\model\\ebay\\EbayCommonReceivables',
        'refuse' => '\\app\\common\\model\\ebay\\EbayCommonRefuseBuyer',
        'promotion' => '\\app\\common\\model\\ebay\\EbayModelPromotion',
        'style' => '\\app\\common\\model\\ebay\\EbayModelStyle',
        'cate' => '\\app\\common\\model\\ebay\\EbayCommonCate',
        'trans' => '\\app\\common\\model\\ebay\\EbayCommonTrans',
        'returngoods' => '\\app\\common\\model\\ebay\\EbayCommonReturn',
    ];

    /**
     * @title 获取公共模块列表
     * @param $table  【模块类型】
     * @param $page     【页数】
     * @param $size     【尺码】
     */
    public function getModelList($params)
    {
        $wh = [];
        foreach ($params as $key => $value) {
            if ($value == '') {
                continue;
            }
            switch ($key) {
                case 'accountId':
                    $accountIds = explode(',',$value);
                    $wh['ebay_account'] = ['in',$accountIds];
                    break;
                case 'moduleName':
                    $wh['model_name'] = ['like',$value.'%'];
                    break;
                case 'creatorName':
                    $creatorIds = User::where('realname','like',$value.'%')->column('id');
                    $wh['creator_id'] = ['in',$creatorIds];
                    break;
                case 'siteId':
                    $wh['site'] = $value;
                    break;
                case 'page':
                    $page = $params['page'];
                    break;
                case 'pageSize':
                    $pageSize = $params['pageSize'];
                    break;
            }
        }

        $field = 'id,model_type,site,model_name,creator_id,updator_id,create_time,update_time';
        $accountFlag = in_array($params['type'],['style','comb','promotion']);
        $paginationFlag = $page??0 && $pageSize??0;
        if ($accountFlag) {
            $field .= ',ebay_account';
        }
        if ($params['type'] == 'promotion') {
            $field .= ',start_date,end_date,promotion_discount,promotion_trans,status';
        } elseif ($params['type'] == 'cate') {
            $field .= ',type';
        }
        $data = $this->listMod[$params['type']]::where($wh)->field($field);
        if ($paginationFlag) {
            $data = $data->page($page,$pageSize);
        }
        $data = $data->order('id desc')->select();

        $rd = [
            'data' => [],
            'count' => 0,
        ];
        if ($paginationFlag) {
            $rd['page'] = $page;
            $rd['pageSize'] = $pageSize;
        }
        if (!$data) {
            return $rd;
        }

        $count = $this->listMod[$params['type']]::where($wh)->field($field)->count();
        $data = collection($data)->toArray();
        $userIds = array_merge(array_column($data,'creator_id'),array_column($data,'updator_id'));
        $userIdNames = User::whereIn('id',$userIds)->column('realname','id');
        if ($accountFlag) {
            $accountIds = array_column($data,'ebay_account');
            $accountIdCodes = EbayAccount::whereIn('id',$accountIds)->column('code','id');
        }
        foreach ($data as &$dt) {
            $dt['create_time'] = $dt['create_time'] ? date('Y-m-d H:i:s',$dt['create_time']) : '';
            $dt['update_time'] = $dt['update_time'] ? date('Y-m-d H:i:s',$dt['update_time']) : '';
            $dt['create_name'] = $userIdNames[$dt['creator_id']]??'';
            $dt['update_name'] = $userIdNames[$dt['updator_id']]??'';
            if ($accountFlag) {
                $dt['account_code'] = $accountIdCodes[$dt['ebay_account']]??'通用';
            }
        }
        $rd['data'] = $data;
        $rd['count'] = $count;
        return $rd;

    }

    /**
     * @title 获取模块信息
     * @param $modelType  【模块类型】
     * @param $modelId     【Id】
     */
    public function getModeInfo($params)
    {
        $data = $this->listMod[$params['type']]::get($params['id']);
        if (!$data) {
            throw new Exception('要编辑的数据不存在');
        }
        $data = $data->toArray();
        foreach ($data as $key => &$value) {
            if ($params['type']=='comb' && $value === 0 && $key!='site' ) {
                $value = '';
            }
        }
        if ($params['type'] == 'trans') {
            $data['trans'] = EbayCommonTransDetail::where('trans_id',$data['id'])->select();
        }
        return $data;
    }

    /**
     * @title 保存公共模块
     * @param $modelType 【模块类型】
     * @param $detail  【详情】
     */
    public function saveModel($params)
    {
        $type = $params['type'];
        $params = json_decode($params['detail'],true);
        foreach ($params as &$param) {
            if (is_array($param)) {
                $param = json_encode($param);
            }
        }

        $params['model_type'] = $type;
        unset($params['create_time']);
        unset($params['update_time']);
        unset($params['creator_id']);
        unset($params['updator_id']);
        //检查是否重名
        $isUpdate = $params['id']??0;
        $wh['model_name'] = $params['model_name'];
        $isUpdate && $wh['id'] =  ['<>',$params['id']];
        if ($this->listMod[$type]::where($wh)->find()) {
            throw new Exception('【'.$params['model_name'].'】名称已存在');
        }
        //执行保存
        $model = new $this->listMod[$type];
        if ($type == 'trans') {
            $params['trans'] = json_decode($params['trans'],true);
            try {
                Db::startTrans();
                $model->allowField(true)->isUpdate((bool)$isUpdate)->save($params);
                if ($isUpdate) {
                    $id = $params['id'];
                } else {
                    $id = $model->getLastInsID();
                }
                EbayCommonTransDetail::destroy(['trans_id' => $id]);
                foreach ($params['trans'] as &$tran) {
                    unset($tran['id']);
                    $tran['trans_id'] = $id;
                    $tran['location'] = json_encode($tran['location']);
                }
                (new EbayCommonTransDetail())->allowField(true)->saveAll($params['trans']);
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                throw new Exception($e->getMessage());
            }
        } else {
            (new $this->listMod[$type])->allowField(true)->saveAll([$params]);
        }
    }

    /**
     * 删除模块
     * @param $params
     */
    public function deleteModule($params)
    {
        if (in_array($params['type'],['cate','comb'])) {//删除的是组合模块
            $this->listMod[$params['type']]::destroy($params['id']);
            return ['message' => '操作成功'];
        }

        if (empty($params['confirm'])) {
            //获取所有使用到此模块的组合模块信息
            $combNames = EbayModelComb::where($params['type'],$params['id'])->column('model_name');
            if (!$combNames) {
                $this->listMod[$params['type']]::destroy($params['id']);
                return ['message' => '操作成功'];
            }
            return ['comb_names' => $combNames];
        }

        //清掉引用了该模块的组合模块的对应信息
        EbayModelComb::update([$params['type']=>0],[$params['type']=>$params['id']]);
        //删除对应模块
        $this->listMod[$params['type']]::destroy($params['id']);
        return ['message'=>'操作成功'];

    }



    public function uploadStyleImgs(array $imgs)
    {
        try {
            $verb = 'UploadSiteHostedPictures';
            $accountInfo = EbayAccount::get(1)->toArray();
            $packApi = new EbayPackApi();
            $errorMsg = [];
            $api = $packApi->createApi($accountInfo, $verb);
            for ($i=0; $i<5; $i++) {//最多上传5次
                $xml = [];
                foreach ($imgs as $k => $img) {
                    if (strpos($img,'https') === false) {
                        $xml[$k] = $packApi->createXml($img);
                    }
                }
                $response = $api->createHeaders()->sendHttpRequestMulti($xml);
                foreach ($response as $key => $res) {
                    if (!is_array($res)) continue;
                    $r = (new EbayDealApiInformation())->dealWithApiResponse($verb, $res);
                    if ($r['result'] == true) {
                        $imgs[$key] = $r['data'];
                        if (isset($errorMsg[$key])) unset($errorMsg[$key]);
                    } else {
                        $errorMsg[$key] = json_encode($r['message']);
                    }
                }
            }
            if (!empty($errorMsg)) {
                $res = ['result'=>false, 'message'=>$errorMsg];
            } else {
                $res = ['result'=>true, 'data'=>$imgs];
            }
            return $res;
        } catch (Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }

    /**
     * @return array
     * @throws Exception
     */
//    public function getSellers()
//    {
//        try {
//            $ebaySellerIds = RoleUser::distinct(true)->where(['role_id'=>['in', [41,48,75,83,92,93]]])->column('user_id');//获取ebay销售角色
//            $sellerNames = User::where(['id'=>['in',$ebaySellerIds]])->column('realname');
//            return $sellerNames;
//        } catch(Exception $e) {
//            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
//        }
//    }

}