<?php
namespace app\index\service;

use app\common\exception\JsonErrorException;
use app\common\model\Channel;
use app\common\model\ChannelBuyer;
use app\common\model\ChannelBuyerAddress;
use app\common\service\Common;
use app\index\validate\BuyerValidate;
use app\order\service\OrderService;
use think\Db;
use think\Exception;
use app\common\cache\Cache;
use think\Request;
use think\Loader;

/** 买家管理
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/8/4
 * Time: 19:48
 */
class BuyerService
{
    protected $channelBuyerModel;
    protected $channelBuyerAddressModel;
    protected $validate;

    public function __construct()
    {
        if (is_null($this->channelBuyerModel)) {
            $this->channelBuyerModel = new ChannelBuyer();
        }
        if (is_null($this->channelBuyerAddressModel)) {
            $this->channelBuyerAddressModel = new ChannelBuyerAddress();
        }
        $this->validate = new BuyerValidate();
    }

    /** 获取列表
     * @param array $where
     * @param $page
     * @param $pageSize
     * @return array
     */
    public function buyerList(array $where, $page, $pageSize)
    {
        $orderService = new OrderService();
        $count = $this->channelBuyerModel->field('creator_id,updater_id',true)->where($where)->count();
        $serverList = $this->channelBuyerModel->field('creator_id,updater_id',true)->where($where)->order('create_time desc,update_time desc')->page($page, $pageSize)->select();
        foreach($serverList as $key => &$value){
            $value['account_id'] = $orderService->getAccountName($value['channel_id'], $value['account_id']);
            $value['channel_id'] = !empty($value['channel_id']) ? Cache::store('channel')->getChannelName($value['channel_id']) : '';
        }
        $result = [
            'data' => $serverList,
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
        ];
        return $result;
    }

    /** 获取信息
     * @param $id
     * @return mixed
     */
    public function info($id)
    {
        $orderService = new OrderService();
        $data['basic'] = $this->channelBuyerModel->field('creator_id,updater_id,create_time,update_time',true)->field(true)->where(['id' => $id])->find();
        $data['basic']['account_name'] =  $orderService->getAccountName($data['basic']['channel_id'], $data['basic']['account_id']);
        $data['basic']['channel_name'] = !empty($data['basic']['channel_id']) ? Cache::store('channel')->getChannelName($data['basic']['channel_id']) : '';
        return $data;
    }

    /** 更新
     * @param array $basic
     * @param $id
     * @throws \Exception
     */
    public function update(array $basic,$id)
    {
        if($bool = $this->channelBuyerModel->isHas($basic['channel_id'],$basic['buyer_id'],$id)){
            throw new JsonErrorException('该买家id已经存在');
        }
        if(!$this->validate->check($basic)){
            throw new JsonErrorException($this->validate->getError(),500);
        }
        Db::startTrans();
        try{
            $this->channelBuyerModel->where(['id' => $id])->update($basic);
            Db::commit();
        }catch (Exception $e){
            Db::rollback();
            throw new JsonErrorException($e->getMessage().$e->getFile().$e->getLine());
        }
    }

    /** 新增买家信息
     * @param array $basic
     * @return mixed
     */
    public function add(array $basic)
    {
        if($bool = $this->channelBuyerModel->isHas($basic['channel_id'],$basic['buyer_id'])){
            throw new JsonErrorException('该买家id已经存在');
        }
        if(!$this->validate->check($basic)){
            throw new JsonErrorException($this->validate->getError(),500);
        }
        Db::startTrans();
        try{
            $this->channelBuyerModel->allowField(true)->isUpdate(false)->save($basic);
            $buyer_id = $this->channelBuyerModel->id;
            Db::commit();
            return $buyer_id;
        }catch (Exception $e){
            Db::rollback();
            throw new JsonErrorException($e->getMessage().$e->getFile().$e->getLine());
        }
    }

    /** 删除
     * @param $id
     */
    public function delete($id)
    {
        $ids = [$id];
        $this->batch($ids);
    }

    /** 批量删除
     * @param array $ids
     */
    public function batch(array $ids)
    {
        Db::startTrans();
        try{
            $this->channelBuyerModel->where('id','in',$ids)->delete();
            $this->channelBuyerAddressModel->where('channel_buyer_id','in',$ids)->delete();
            Db::commit();
        }catch (Exception $e){
            Db::rollback();
        }
    }

    /** 导入买家信息
     * @param array $data
     * @param $channel_id
     * @return bool
     * @throws \Exception
     */
    public function import(array $data,$channel_id)
    {
        $buyer = [];
        foreach($data as $key => $value){
            $temp['channel_id'] = $channel_id;
            $temp['email'] = $value['ebay登录账号'];
            $temp['buyer_id'] = $value['ebayID'];
            $temp['name'] = $value['买家ID'];
            $temp['is_scalping'] = 1;
            $temp['create_time'] = time();
            array_push($buyer,$temp);
        }
        $this->channelBuyerModel->allowField(true)->isUpdate(false)->saveAll($buyer);
        return true;
    }

    /**
     * 批量修改买家数据
     * @param Request $request
     * @return array
     * @throws Exception
     */
    public function batchUpdate(Request $request)
    {
        set_time_limit(0);
        $data = $this->getImportData($request);
        $result = $this->saveImportData($data);
        $message = '成功修改'. $result['success']. '条数据';
        if (!empty($result['addNum'])) {
            $message .= '，成功添加'. $result['addNum']. '条数据';
        }
        $error = empty($result['error'])? '': '第'. implode(',', $result['error']). '行数据修改失败';
        return ['message' => $message, 'error' => $error];
    }

    public function getImportData(Request $request)
    {
        $file = $request->post();
        if (empty($file['name'])) {
            throw new Exception('上传文件name参数为空');
        }
        if (empty($file['content'])) {
            throw new Exception('上传文件content参数为空');
        }
        if (empty($file['extension'])) {
            throw new Exception('上传文件extension参数为空');
        }

        $extArr = ['xlsx', 'xls', 'csv'];
        if (!in_array($file['extension'], $extArr)) {
            throw new Exception('只能上传后辍为：'. implode(',', $extArr). '的文件');
        }
        $path = ROOT_PATH. 'public/upload/buyers_import';
        $fileName = $path. DS. $file['name'];

        if (strpos($file['content'], 'base64,') === false) {
            throw new Exception('上传文件content文件编码错误');
        }
        $content = substr($file['content'], strrpos($file['content'], 'base64,') + 7);
        $content = base64_decode($content);
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        $fsource = fopen($fileName, 'w');
        fwrite($fsource, $content);
        fclose($fsource);

        $ext = pathinfo($fileName, PATHINFO_EXTENSION);
        $data = $this->readExcel($fileName, $ext);
        unlink($fileName);

        return $data;
    }

    /**
     * 保存数据；
     * @param $datas
     * @return array
     * @throws Exception
     */
    public function saveImportData($datas)
    {
        $total = 0;
        $addNum = 0;
        $error = [];
        $userInfo = Common::getUserInfo();
        try {
            $channel = new Channel();
            foreach ($datas as $key => $val) {
                if ($key == 0 && $val['A'] == '平台') {
                    continue;
                }
                //通过平台标题获取平台id
                if (!empty($val['A'])) {
                    $val['A'] = $channel->where('name' ,$val['A'])->value('id');
                    if (!is_numeric($val['A'])) {
                        //'平台【'. $val['A']. '】不正确';
                        $error[] = $key + 1;
                        continue;
                    }
                }
                //通过账号简称获取账号id
                if (empty($val['B'])) {
                    //'账号简称【'. $val['B']. '】不正确';
                    $error[] = $key + 1;
                    continue;
                }

                //查询当前账号是否存在
                $presence = $this->channelBuyerModel->where(['channel_id' => $val['A'] ,'buyer_id' => $val['B']])->find();
                if ($presence) {
                    $this->channelBuyerModel->where(['channel_id' => $val['A'] ,'buyer_id' => $val['B']])->update(['is_scalping' => 1]);
                    $total++;
                } else {
                    $updata['channel_id'] = $val['A'];
                    $updata['buyer_id'] = $val['B'];
                    $updata['is_scalping'] = 1;
                    $updata['creator_id'] = $userInfo['user_id'];
                    $updata['create_time'] = time();
                    $updata['update_time'] = time();
                    $updata['updater_id'] = $userInfo['user_id'];
                    $this->channelBuyerModel->insert($updata);
                    $addNum ++;
                }
            }
            return ['success' => $total, 'addNum' => $addNum, 'error' => $error];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function readExcel($path, $ext = 'xlsx') {
        Loader::import('phpExcel.PHPExcel', VENDOR_PATH);

        switch ($ext) {
            case 'xlsx':
                $reader = \PHPExcel_IOFactory::createReader('Excel2007'); //设置以Excel5格式(Excel97-2003工作簿)
                break;
            case 'xls':
                $reader = \PHPExcel_IOFactory::createReader('Excel5'); //设置以Excel5格式(Excel97-2003工作簿)
                break;
            case 'csv':
                $reader = \PHPExcel_IOFactory::createReader('csv'); //设置以Excel5格式(Excel97-2003工作簿)
                break;
            default:
                $reader = \PHPExcel_IOFactory::createReader('Excel2007'); //设置以Excel5格式(Excel97-2003工作簿)
                break;
        }

        $PHPExcel = $reader->load($path); // 载入excel文件
        $sheet = $PHPExcel->getSheet(0); // 读取第一個工作表
        $highestRow = $sheet->getHighestRow(); // 取得总行数
        $highestColumm = $sheet->getHighestColumn(); // 取得总列数

        $data = [];
        /** 循环读取每个单元格的数据 */
        for ($row = 1; $row <= $highestRow; $row++)    //行号从1开始
        {
            $dataset = [];
            for ($column = 'A'; $column <= $highestColumm; $column++)  //列数是以A列开始
            {
                $dataset[$column] = (string)$sheet->getCell($column.$row)->getValue();
            }
            $data[] = $dataset;
        }
        return $data;
    }

    public function updateTemplate(Request $request)
    {
        $ids = $request->get('ids');
        //默认数据；
        $data = [
            [
                'channel' => 'aliExpress',
                'buyer_id' => 'cn1274346783jyhf',
            ]
        ];
        if (!empty($ids)) {
            $ids = explode(',', $ids);
            $ids = array_merge(array_unique($ids));
            $channelBuyer = new ChannelBuyer();
            $lists = $channelBuyer->field('channel_id,buyer_id')->where(['id' => ['in', $ids]])->select();
            if (!empty($lists)) {
                $data = [];
                $channel = new Channel();
                foreach ($lists as $k => $v) {
                    $name = $channel->where('id' ,$v['channel_id'])->value('name');
                    $tmp['channel'] = $name;
                    $tmp['buyer_id'] = $v['buyer_id'];
                    $data[] = $tmp;
                }
            }
        }
        $header = [
            ['title' => '平台', 'key' => 'channel', 'width' => 30],
            ['title' => '买家ID', 'key' => 'buyer_id', 'width' => 30],
        ];

        $file = [
            'name' => '买家管理导入模板',
            'path' => 'order'
        ];
        $ExcelExport = new DownloadFileService();
        return $ExcelExport->export($data, $header, $file);
    }
}