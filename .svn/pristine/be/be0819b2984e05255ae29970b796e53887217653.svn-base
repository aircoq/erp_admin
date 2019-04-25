<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-2-21
 * Time: 下午3:54
 */

namespace app\goods\controller;


use app\common\controller\Base;
use app\common\service\Common;
use app\goods\service\GoodsHelp;
use app\goods\service\GoodsSku as Server;
use app\goods\service\GoodsImage;
use app\common\cache\Cache;
use think\Exception;
use think\Request;
use app\report\model\ReportExportFiles;
use app\index\queue\ExportDownQueue;
use app\common\service\CommonQueuer;

/**
 * @module 商品系统
 * @title 商品SKU
 */
class GoodsSku extends Base
{
    /**
     * @title 查询商品
     * @url /goods-sku/query
     */
    public function query(Request $request)
    {
        $param = $request->param();
        if (!isset($param['keyword'])) {
            return json_error('必需输入关键字');
        }
        $keyword = $param['keyword'];
        if ($keyword === '') {
            return json_error('必需输入关键字');
        }
        $size = isset($param['size']) ? ((int)$param['size']) + 1 : 16;
        $server = new Server();
        $result = $server->querySku($keyword, $size);
        return json($result);
    }

    /**
     * @title 根据id，sku，别名取得sku信息
     * @url /goods-sku/info
     * @noauth
     * @author starzhan <397041849@qq.com>
     */
    public function getSkuInfo()
    {
        $param = $this->request->param();
        try {
            if (!isset($param['sku']) || !$param['sku']) {
                throw new Exception('sku不能为空!');
            }

            $server = new Server();
            $param['sku'] = preg_replace("/(\s|\&nbsp\;|　|\xc2\xa0)/", "", $param['sku']);
            $result = $server->getSkuInfo($param['sku']);
            return json($result, 200);
        } catch (Exception $ex) {
            return json($ex->getMessage(), 400);
        }

    }

    /**
     * @title 根据sku返回详细信息
     * @url /goods-sku/api/:sku/info
     * @noauth
     * @return \think\response\Json
     * @author starzhan <397041849@qq.com>
     */
    public function apiSkuInfo($sku)
    {
        $param = $this->request->param();
        try {

            $server = new Server();
            $param['sku'] = preg_replace("/(\s|\&nbsp\;|　|\xc2\xa0)/", "", $sku);
            $result = $server->getSkuInfo($param['sku']);
            if (!$result) {
                throw new Exception('该sku不存在');
            }
            $sku_attributes = json_decode($result['sku_attributes'], true);
            $result['attr'] = GoodsHelp::getAttrbuteInfoBySkuAttributes($sku_attributes, $result['goods_id']);
            $GoodsImage = new GoodsImage();
            $result['img_list'] = $GoodsImage->getImgBySkuId($result['id']);
            return json($result, 200);
        } catch (Exception $ex) {
            return json([], 400);
        }
    }

    /**
     * @title 根据sku_id获取兄弟元素
     * @url /goods-sku/:id/siblings
     * @method get
     * @noauth
     * @author starzhan <397041849@qq.com>
     */
    public function getSkuSiblings($id)
    {
        try {
            $server = new Server();
            $result = $server->getSkuSiblings($id);
            return json($result, 200);
        } catch (Exception $ex) {
            return json($ex->getMessage(), 400);
        }
    }

    /**
     * @title 删除sku
     * @param $id
     * @url /goods-sku/batch/delete
     * @method post
     * @noauth
     * @author starzhan <397041849@qq.com>
     */
    public function batchDelete()
    {
        try {
            $server = new Server();
            $param = $this->request->param();
            if (!isset($param['ids']) || !$param['ids']) {
                throw new Exception('删除的skuId不能为空!');
            }
            $ids = json_decode($param['ids'], true);
            $result = $server->delete($ids);
            return json($result, 200);
        } catch (Exception $ex) {
            return json(['message' => $ex->getMessage()], 400);
        }
    }

    /**
     * @title 包裹重量差异列表
     * @url /goods-sku/diff-weight
     * @return \think\response\Json
     * @author starzhan <397041849@qq.com>
     */
    public function diffWeight()
    {
        try {
            $server = new Server();
            $param = $this->request->param();
            $page = isset($param['page']) ? $param['page'] : 1;
            $pageSize = isset($param['page_size']) ? $param['page_size'] : 50;
            $result = $server->diffWeight($page, $pageSize, $param);
            return json($result, 200);
        } catch (Exception $ex) {
            return json(['message' => $ex->getMessage(), 'file' => $ex->getFile(), 'line' => $ex->getLine()], 400);
        }
    }


    /**
     * @title 包裹重量差异列表导出
     * @url /goods-sku/diff-weight-export
     * @method post
     * @return \think\response\Json
     * @author starzhan <397041849@qq.com>
     */
    public function diffWeightExport()
    {
        try {
            $userInfo = Common::getUserInfo();
            $server = new Server();
            $param = $this->request->param();
            $ids = isset($param['ids']) ? json_decode($param['ids'], true) : [];
            if ($ids) {
                $result = $server->diffWeightExport($param);
                return json($result, 200);
            }
            $count = $server->getDiffWeightExportCount($param);
            if (!$count) {
                throw new Exception('导出的记录数为0');
            }
            $param['count'] = $count;
            if ($count > 1000) {
                $cache = Cache::handler();
                $key = 'GoodsSku:diffWeightExport:lastExportTime:' . $userInfo['user_id'];
                $lastApplyTime = $cache->get($key);
                if ($lastApplyTime && time() - $lastApplyTime < 5 * 60) {
                    throw new Exception('5分钟内只能请求一次', 400);
                } else {
                    $cache->set($key, time());
                    $cache->expire($key, 3600);
                }
                $fileName = 'SKU重量差异' . date('Y-m-d_H-i-s') . ".csv";
                $model = new ReportExportFiles();
                $data['applicant_id'] = $userInfo['user_id'];
                $data['apply_time'] = time();
                $data['export_file_name'] = $fileName;
                $data['export_file_name'] = str_replace('.csv', '.zip', $data['export_file_name']);
                $data['status'] = 0;
                $data['applicant_id'] = $userInfo['user_id'];
                $model->allowField(true)->isUpdate(false)->save($data);
                $param['file_name'] = $fileName;
                $param['apply_id'] = $model->id;
                $param['class'] = '\app\\\\goods\\\\service\\\\GoodsSku';
                $param['fun'] = 'diffWeightExport';
                (new CommonQueuer(ExportDownQueue::class))->push($param);
                return json(['status' => 0, 'message' => '导出数据太多，已加入导出队列，稍后请自行下载'], 200);
            } else {
                $result = $server->diffWeightExport($param);
                return json($result, 200);
            }
        } catch (Exception $ex) {
            return json(['message' => $ex->getMessage(), 'file' => $ex->getFile(), 'line' => $ex->getLine()], 400);
        }
    }

    /**
     * @title 批量设置停售sku
     * @url /goods-sku/batch/stopped
     * @method post
     * @author starzhan <397041849@qq.com>
     */
    public function stopped()
    {
        $param = $this->request->param();
        $userInfo = Common::getUserInfo($this->request);
        try {
            $server = new Server();
            $result = $server->stopped($param, $userInfo);
            return json($result, 200);
        } catch (Exception $ex) {
            $err = [];
            $err['file'] = $ex->getFile();
            $err['line'] = $ex->getLine();
            $err['message'] = $ex->getMessage();
            return json($err, 400);
        }
    }

    /**
     * @title 停售sku渠道
     * @url /goods-sku/stopped-channel
     * @method get
     * @author starzhan <397041849@qq.com>
     */
    public function stoppedChannel()
    {
        return json((new Server)->stoppedChannel(),200);
    }

}
