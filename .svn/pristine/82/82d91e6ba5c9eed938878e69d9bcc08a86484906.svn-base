<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-6-13
 * Time: 下午5:34
 */

namespace app\publish\controller;


use app\common\controller\Base;
use app\common\exception\JsonErrorException;
use app\publish\service\ProductDownloadService;
use think\Exception;
use think\Request;
/**
 * @module 刊登系统
 * @title 刊登报表导出
 * Class Export
 * packing app\publish\Export
 */
class Export extends Base
{

    /**
     * @title SPU在各个平台的已刊登数量报表导出
     * @url /publish-time-statistic-export
     * @access public
     * @method post
     * @param array $request
     * @output think\Response
     */
    public function statisticPublishTimeExport(Request $request){
        try{
            $params = $request->param();
            $params['flag']='joy88';
            $response = (new ProductDownloadService)->downloadByQueue($params);
            return json($response);
        }catch (Exception $exp){
            throw new JsonErrorException($exp->getMessage());
        }
    }

    /**
     * @title SPU在各个平台的已刊登数量
     * @url /publish-time-statistic
     * @access public
     * @method GET
     * @param array $request
     * @output think\Response
     */
    public function statisticPublishTime(Request $request){
        $params = $request->param();
        $page = $request->param('page',1);
        $pageSize = $request->param('pageSize',30);
        $result = ProductDownloadService::publishTime($params,$page,$pageSize);
        return json($result);
    }
    /**
     * @title 刊登报表导出
     * @url /publish-statistic-export
     * @access public
     * @method post
     * @param array $request
     * @output think\Response
     */
    public function statisticExport(Request $request){
        try{
            $params = $request->param();
            if(!isset($params['channel_id']) || empty($params['channel_id'])){
                return json_error('渠道id必须');
            }
            $params['flag']='joy';
            $response = (new ProductDownloadService)->downloadByQueue($params);
            return json($response);
        }catch (Exception $exp){
            throw new JsonErrorException($exp->getMessage());
        }
    }

    /**
     * @title spu刊登统计
     * @url /publish-statistic
     * @access public
     * @method GET
     * @param array $request
     * @output think\Response
     */
    public function statistic(Request $request){
        $params = $request->param();
        $channel = $request->param('channel_id','');
        if(empty($channel)){
            return json_error('请选择平台');
        }
        $page = $request->param('page',1);
        $pageSize = $request->param('pageSize',30);
        $service = new ProductDownloadService();
        $result = $service->statistic($params,$page,$pageSize);
        return json($result);
    }
    /**
     * @title 刊登部分导出
     * @url /publish-export
     * @access public
     * @method post
     * @param array $request
     * @output think\Response
     */
    public function download(Request $request){
        $params = $request->param();

        if(!isset($params['channel_id'])){
            return json(['message' => '渠道id必须'], 400);
        }
        $ids = $request->param('ids', 0);
        if (isset($request->header()['X-Result-Fields'])) {
            $field = $request->header()['X-Result-Fields'];
        }else{
            $field = [];
        }

        if(empty($field)){
            $field = $request->param('fields', '');
        }

        $type = $request->param('export_type', 0);
        if (empty($ids) && empty($type)) {
            return json(['message' => '请先选择一条记录'], 400);
        }
        if(!empty($field)){
            $field = json_decode($field,true);
        }
        if(!empty($type)){
            $ids = [];
        }
        $service = new ProductDownloadService();
        $result = $service->export($ids, $field,$params);
        return json($result);
    }
    /**
     * @title 刊登全部导出
     * @url /publish-export-all
     * @access public
     * @method post
     * @param array $request
     * @output think\Response
     */
    public function downloadAll(Request $request){
        try{
            $params = $request->param();

            $fields = $request->param('fields','');
            if(empty($fields)){
                return json_error('请选择你要导出的字段');
            }
            if(!isset($params['channel_id'])){
                return json_error('渠道id必须');
            }
            $response = (new ProductDownloadService)->downloadByQueue($params);
            return json($response);
        }catch (Exception $exp){
            throw new JsonErrorException($exp->getMessage());
        }
    }
    /**
     * @title 刊登报表导出字段
     * @date 2018-06-13
     * @url /publish-export-fields
     * @method get
     */
    public function fields(Request $request)
    {
        try {
            $channel_id = $request->param('channel_id',0);
            if( empty($channel_id)){
                return json_error('渠道id必须');
            }
            $response = ProductDownloadService::getDownloadFields($channel_id);
            return json($response);
        } catch (Exception $exp) {
            throw new JsonErrorException($exp->getMessage());
        }
    }

}