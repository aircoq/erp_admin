<?php
namespace app\api\service;

use app\goods\service\GoodsDeclare;
use app\goods\service\GoodsHelp;
use think\Exception;

class Goods extends Base 
{
    
    /**
     * 获取产品ID
     */
    public function getGoodsId()
    {
        if(!isset($this->requestData['data']) || empty($this->requestData['data'])) {
            $this->retData['message'] = '请求查询spu不能为空';
            $this->retData['status']  = 0;
            return $this->retData;
        }
        $spus = explode(',', $this->requestData['data']);
        $where = [];
        try {
            $goods = new GoodsHelp();
            $where['where']['g.spu'] = ['IN', $spus];
            $this->retData['data'] = $goods->getList($where, 'g.id, g.spu');
        } catch (Exception $ex) {
            $this->retData['status']  = 0;
            $this->retData['error'] = '执行错误';
        }       
        return $this->retData;
    }

    /** 获取服务器渠道账号信息
     * @return array
     * @throws \think\Exception
     */
    public function declareInfo()
    {
        $page = $this->requestData['page'] ?? 1;;
        $pageSize = $this->requestData['pageSize'] ?? 8;
        try{
            $goodsDeclareServer = new GoodsDeclare();
            $infoList = $goodsDeclareServer->lists($page,$pageSize);
            $this->retData['lists'] = $infoList;
        }catch (Exception $e){
            throw new Exception($e->getMessage());
        }
        return $this->retData;
    }

    /**
     * 查看详情
     * @return array
     * @throws Exception
     */
    public function detail()
    {
        $sku_id = $this->requestData['sku_id'] ?? 0;
        try{
            $goodsDeclareServer = new GoodsDeclare();
            $infoList = $goodsDeclareServer->info($sku_id,true);
            $this->retData['detail'] = $infoList;
        }catch (Exception $e){
            throw new Exception($e->getMessage());
        }
        return $this->retData;
    }
}

