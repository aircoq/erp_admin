<?php

namespace app\report\service;

use app\common\cache\Cache;
use app\common\exception\JsonErrorException;
use app\common\service\ChannelAccountConst;
use app\common\service\Common;
use app\common\service\CommonQueuer;
use app\index\service\ChannelConfig;
use app\report\model\ReportExportFiles;
use app\report\queue\AmazonAccountMonitorExportQueue;
use think\Db;
use think\Exception;
use app\index\service\Department as DepartmentServer;
use app\index\service\DepartmentUserMapService;
use think\Loader;
use app\index\service\User;
use app\common\model\ChannelUserAccountMap as ChannelUserAccountMapModel;
use app\common\model\Channel as ChannelModel;
use app\common\model\report\ReportListingByAccount;
use app\common\model\amazon\AmazonAccount;
use app\report\validate\FileExportValidate;
use app\common\traits\Export;

Loader::import('phpExcel.PHPExcel', VENDOR_PATH);

/**
 * Created by PhpStorm.
 * User: zhaixueli
 * Date: 2019/2/15
 * Time: 10:09
 */
class AmazonAccountMonitorService
{
    use Export;

    private $amazonAccountModel;
    private $reportListingByAccount;
    private $channelModel;

    protected $colMap = [
        'title' => [
            'A' => ['title' => '平台', 'width' => 20],
            'B' => ['title' => '账号', 'width' => 10],
            'C' => ['title' => '站点', 'width' => 15],
            'D' => ['title' => '销售员', 'width' => 10],
            'E' => ['title' => '部门', 'width' => 20],
            'F' => ['title' => 'listing目标数量', 'width' => 10],
            'G' => ['title' => '激活时间', 'width' => 15],
            'H' => ['title' => '预估数量', 'width' => 15],
            'I' => ['title' => '实际数量', 'width' => 10],

        ],
        'data' => [
            'name' => ['col' => 'A', 'type' => 'time'],
            'account_name' => ['col' => 'B', 'type' => 'str'],
            'site' => ['col' => 'C', 'type' => 'str'],
            'user_name' => ['col' => 'D', 'type' => 'int'],
            'department' => ['col' => 'E', 'type' => 'str'],
            'list_num' => ['col' => 'F', 'type' => 'str'],
            'create_time' => ['col' => 'G', 'type' => 'str'],
            'estimate_num' => ['col' => 'H', 'type' => 'str'],
            'real_quantity' => ['col' => 'I', 'type' => 'str'],
        ]

    ];

    public function __construct()
    {
        if (is_null($this->amazonAccountModel)) {
            $this->amazonAccountModel = new AmazonAccount();
        }
        if (is_null($this->reportListingByAccount)) {
            $this->reportListingByAccount = new ReportListingByAccount();
        }
        if (is_null($this->channelModel)) {
            $this->channelModel = new ChannelModel();
        }

    }


    /**
     * 导出申请
     * @param $params
     * @return bool
     * @throws Exception
     * @throws \Exception
     */
    public function applyExport($params)
    {
        $userId = Common::getUserInfo()->toArray()['user_id'];
        $cache = Cache::handler();
        $lastApplyTime = $cache->hget('hash:export_goods_apply', $userId);
        if ($lastApplyTime && time() - $lastApplyTime < 5) {
            throw new JsonErrorException('请求过于频繁', 400);
        } else {
            $cache->hset('hash:export_account_monitor', $userId, time());
        }
        try {
            $model = new ReportExportFiles();
            $data['applicant_id'] = $userId;
            $data['apply_time'] = time();
            $data['export_file_name'] = $this->createExportFileName();
            $data['status'] = 0;
            $data['applicant_id'] = $userId;
            $model->allowField(true)->isUpdate(false)->save($data);
            $params['file_name'] = $data['export_file_name'];
            $params['apply_id'] = $model->id;
            (new CommonQueuer(AmazonAccountMonitorExportQueue::class))->push($params);
            return true;
        } catch (\Exception $ex) {
            throw new JsonErrorException('申请导出失败');
        }
    }

    /**
     * 创建导出文件名
     *
     * @param array $params
     * @param [string] $date_b
     * @param [string] $date_e
     * @return string
     */
    protected function createExportFileName()
    {
        $fileName = '亚马逊账号监控报表' . '(' . date('Y-m-d', time()) . ')' . '.xlsx';
        return $fileName;
    }

    /**
     * 导出数据至excel文件
     * @param array $params
     * @return bool
     * @throws Exception
     */
    public function export(array $params)
    {
        set_time_limit(0);
        try {
            ini_set('memory_limit', '4096M');
            $validate = new FileExportValidate();
            if (!$validate->scene('export')->check($params)) {
                throw new Exception($validate->getError());
            }
            $fileName = $params['file_name'];
            $downLoadDir = '/download/report_amazon/';
            $saveDir = ROOT_PATH . 'public' . $downLoadDir;
            if (!is_dir($saveDir) && !mkdir($saveDir, 0777, true)) {
                throw new Exception('导出目录创建失败');
            }
            $fullName = $saveDir . $fileName;
            //创建excel对象
            $writer = new \XLSXWriter();
            $titleMap = $this->colMap['title'];
            $title = [];
            $titleData = $this->colMap['data'];
            foreach ($titleData as $k => $v) {
                array_push($title, $k);
            }
            $titleOrderData = [];
            foreach ($titleMap as $t => $tt) {
                $titleOrderData[$tt['title']] = 'string';
            }
            //统计需要导出的数据行
            $where = [];
            $this->where($params, $where);
            $count = $this->doCount($where);
            $pageSize = 10000;
            $loop = ceil($count / $pageSize);
            $writer->writeSheetHeader('Sheet1', $titleOrderData);
            //分批导出
            for ($i = 0; $i < $loop; $i++) {
                $data = $this->doSearch($where, $i + 1, $pageSize, $title);

                foreach ($data as $r) {
                    $writer->writeSheetRow('Sheet1', $r);
                }
                unset($data);
            }
            $writer->writeToFile($fullName);

            if (is_file($fullName)) {
                $applyRecord = ReportExportFiles::get($params['apply_id']);
                $applyRecord['exported_time'] = time();
                $applyRecord['download_url'] = $downLoadDir . $fileName;
                $applyRecord['status'] = 1;
                $applyRecord->isUpdate()->save();
            } else {
                throw new Exception('文件写入失败');
            }
        } catch (\Exception $ex) {
            $applyRecord = ReportExportFiles::get($params['apply_id']);
            $applyRecord['status'] = 2;
            $applyRecord['error_message'] = $ex->getMessage();
            $applyRecord->isUpdate()->save();
            Cache::handler()->hset(
                'hash:report_export',
                $params['apply_id'].'_' . time(),
                '申请id: ' . $params['apply_id'] . ',导出失败:' . $ex->getMessage());
        }
    }

    /**
     * 列表详情
     * @param int $page
     * @param int $pageSize
     * @param array $params
     * @return array
     */
    public function lists($page, $pageSize, $params)
    {
        $where = [];
        $this->where($params, $where);
        $count = $this->doCount($where);
        $data = $this->doSearch($where, $page, $pageSize);
        $result = [
            'data' => $data,
            'count' => $count,
            'page' => $page,
            'pageSize' => $pageSize
        ];
        return $result;
    }

    /**
     * 查询条件
     * @param array $params
     * @param array $where
     * @return \think\response\Json
     */
    private function where($params, &$where)
    {
        if (isset($params['channel_id']) && !empty($params['channel_id'])) {
            $where['a.channel_id'] = ['eq', $params['channel_id']];
        }
        if (isset($params['site_code']) && !empty($params['site_code'])) {
            $where['a.site'] = ['eq', $params['site_code']];
        }
        if (isset($params['account_name']) && !empty($params['account_name'])) {
            $account_id = $this->amazonAccountModel
                ->where(['account_name' => ['EQ', $params['account_name']]])
                ->value('id');
            $where['a.account_id'] = ['eq', $account_id];
        }
        if (isset($params['seller_id']) && !empty($params['seller_id'])) {
            $where['a.seller_id'] = ['eq', $params['seller_id']];
        }
    }

    /**
     * 搜索
     * @param array $condition
     * @param array $join
     * @param int $page
     * @param int $pageSize
     * @param $title
     * @return array
     */
    protected function doSearch(array $condition = [], $page = 1, $pageSize = 10,$title = [])
    {
        $field = ['a.channel_id', 'a.account_id', 'a.site', 'a.seller_id', 'a.activation_time', 'a.estimated_quantity', 'a.actual_quantity','cast(a.actual_quantity  as signed) - cast(a.estimated_quantity as signed) as margin'];
        $results = $this->reportListingByAccount
            ->alias('a')
            ->join('channel c','c.id=a.channel_id')
            ->join('amazon_account am','am.id=a.account_id')
            ->field($field)
            ->where($condition)
            ->where(['am.status'=>'1'])
            ->where(['am.assessment_of_usage'=>'0'])
            ->group('a.seller_id')
            ->order('margin asc')
            ->page($page, $pageSize)
            ->select();
        $departmentServer = new DepartmentServer();
        $departmentUserMapService = new DepartmentUserMapService();
        if (!empty($results)) {
            $channelConfig = new ChannelConfig(ChannelAccountConst::channel_amazon);
            $list_num = $channelConfig->getConfig('channel_list_num');

            foreach ($results as $k => &$v) {
                $result = Cache::store('AmazonAccount')->getAccount($v['account_id']);
                $temp['account_name'] = $result['code'];
                $temp['name'] = Cache::store('channel')->getchannelName($v['channel_id']);
                $temp['site'] = $v['site'];
                $temp['seller_id'] = $v['seller_id'];
                $name = Cache::store('user')->getOneUser($v['seller_id']);
                $temp['user_name'] = $name['realname'] ?? '';
                $department_ids = $departmentUserMapService->getDepartmentByUserId($v['seller_id']);
                $departmentInfo = '';
                foreach ($department_ids as $d => $department) {
                    if (!empty($department)) {
                        $departmentInfo .= $departmentServer->getDepartmentNames($department) . '   ,   ';
                    }
                }
                $departmentInfo = rtrim($departmentInfo, '   ,   ');
                $temp['department'] = $departmentInfo;
                $time = date('Y-m-d', $v['activation_time']);
                $temp['create_time'] = $time;
                $temp['list_num'] = $list_num.'条/天';
                $temp['estimate_num'] = $v['estimated_quantity'];
                $temp['real_quantity'] = $v['actual_quantity'];
                $temp['status'] = $v['margin'];
                if (!empty($temp['status']) && $temp['status'] < 0) {
                    $temp['status'] = 0;
                } else {
                    $temp['status'] = 1;
                }

                if (!empty($title)) {

                    $tt = [];
                    foreach ($title as $k => $value) {
                        $tt[$value] = $temp[$value];
                    }
                    $data[] = $tt;
                } else {
                    $data[] = $temp;
                }
            }

        } else {
            $data = [];
        }
        return $data;
    }


    /**
     * 查询总数
     * @param array $condition
     * @param array $join
     * @return int|string
     */
    protected function doCount(array $condition = [])
    {
        $data = $this->reportListingByAccount
            ->alias('a')
            ->join('channel c','c.id = a.channel_id')
            ->join('amazon_account am','am.id=a.account_id')
            ->where(['am.status'=>'1'])
            ->where(['am.assessment_of_usage'=>'0'])
            ->where($condition)
            ->group('a.seller_id')
            ->count();
        return $data;
    }


    /**
     *
     * 获取字段信息
     * @return string
     */
    protected function field()
    {
        $field = ['c.id', 'a.site', 'a.id as account_id', 'cu.seller_id',
            ' cu.update_time'];
        return $field;
    }

    /**
     * 关联数据
     * @return array
     */
    protected function join()
    {
        $join[] = ['channel_user_account_map cu', 'cu.account_id = a.id', 'left'];
        $join[] = [' channel c', 'c.id = cu.channel_id', 'left'];
        return $join;
    }

    /**
     * 查询账号数据
     * @return array
     */
    public function accountList()
    {
        $amazonAccountModel = new AmazonAccount();
        $where['c.id'] = array('eq', 2);
        $where['a.assessment_of_usage'] = array('eq',0);
        $where['cu.update_time']=array('gt',0);
        $where['a.status']=array('eq',1);
        $field = $this->field();
        $join = $this->join();
        $amazonInfo = $amazonAccountModel
            ->alias('a')
            ->field($field)
            ->join($join)
            ->where($where)
            ->group('cu.seller_id')
            ->order('cu.seller_id asc')
            ->select();
        return $amazonInfo;
    }


}