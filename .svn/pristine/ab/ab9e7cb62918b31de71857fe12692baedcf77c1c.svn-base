<?php
/**
 * Created by PhpStorm.
 * User: libaimoin
 * Date: 18-10-31
 * Time: 上午10:28
 */

namespace app\report\service;

use app\common\cache\Cache;
use app\common\exception\JsonErrorException;
use \app\common\model\monthly\MonthlyTargetDepartment as DepartmentModel;
use app\common\model\monthly\MonthlyTargetDepartment;
use app\common\model\monthly\MonthlyTargetDepartmentUserMap as DepartmentUserMap;
use app\common\model\monthly\MonthlyTargetAmount;
use app\common\model\monthly\MonthlyTargetDepartmentUserMap;
use app\common\model\monthly\MonthlyTargetLog;
use app\common\model\monthly\MonthlyTargetProgress;
use app\common\service\ChannelAccountConst;
use app\common\service\ChannelConst;
use app\common\service\MonthlyModeConst;
use app\index\service\MemberShipService;
use think\Db;
use think\Exception;
use think\Loader;
use app\order\service\AuditOrderService;
use app\common\traits\Export;
use app\common\service\Common;
use app\common\service\Excel;
use app\common\service\Filter;
use app\report\filter\MonthlyTargetAmountDepartmentFilter;
use app\report\filter\MonthlyTargetAmountFilter;

Loader::import('phpExcel.PHPExcel', VENDOR_PATH);

class MonthlyTargetAmountService
{

    use Export;
    protected $amountModel = null;
    protected $userMapModel = null;

    public function __construct()
    {
        if (is_null($this->amountModel)) {
            $this->amountModel = new MonthlyTargetAmount();
        }
        if (is_null($this->userMapModel)) {
            $this->userMapModel = new DepartmentUserMap();
        }
    }

    /**
     * 标题
     */
    public function title($data = [])
    {
        $title = [
            'department_id' => [
                'title' => 'department_id',
                'remark' => '部门ID',
                'is_show' => 1
            ],
            'department' => [
                'title' => 'department',
                'remark' => '部门名称',
                'is_show' => 1
            ],
            'user_id' => [
                'title' => 'user_id',
                'remark' => '用户ID',
                'is_show' => 1
            ],
            'realname' => [
                'title' => 'realname',
                'remark' => '姓名',
                'is_show' => 1
            ],
            'job_number' => [
                'title' => 'job_number',
                'remark' => '工号',
                'is_show' => 1
            ],
            'target_amount' => [
                'title' => 'target_amount',
                'remark' => '考核目标金额($)',
                'is_show' => 1
            ],
        ];
        if(isset($data['mode']) && $data['mode'] == MonthlyModeConst::mode_development){
            $title['target_amount']['remark'] = '考核目标个数';
        }
        return $title;
    }

    /**
     * 月度标题【销售】
     */
    public function monthlyTitle()
    {
        $title = [
            'name' => [
                'title' => 'name',
                'remark' => '部门',
                'is_show' => 1
            ],
            'leader_name' => [
                'title' => 'leader_name',
                'remark' => '负责人',
                'is_show' => 1
            ],
            'target_amount' => [
                'title' => 'target_amount',
                'remark' => '销售额目标',
                'is_show' => 1
            ],
            'actual_amount' => [
                'title' => 'actual_amount',
                'remark' => '已完成',
                'is_show' => 1
            ],
            'rate' => [
                'title' => 'rate',
                'remark' => '进度',
                'is_show' => 1
            ],
            'order_qty_e' => [
                'title' => 'order_qty_e',
                'remark' => '日均订单数',
                'is_show' => 1
            ],
            'actual_amount_e' => [
                'title' => 'actual_amount_e',
                'remark' => '日均销售额',
                'is_show' => 1
            ],
            'man' => [
                'title' => 'man',
                'remark' => '部门人数',
                'is_show' => 1
            ],
            'man_e' => [
                'title' => 'man_e',
                'remark' => '部门人均',
                'is_show' => 1
            ],
            'accounts' => [
                'title' => 'accounts',
                'remark' => '平台账号数',
                'is_show' => 1
            ],
            'accounts_e' => [
                'title' => 'accounts_e',
                'remark' => '账号平均',
                'is_show' => 1
            ],
            'local_warehouse_amount' => [
                'title' => 'local_warehouse_amount',
                'remark' => '本地仓销售额',
                'is_show' => 1
            ],
            'oversea_warehouse_amount' => [
                'title' => 'oversea_warehouse_amount',
                'remark' => '海外仓销售额',
                'is_show' => 1
            ],
            'fba_warehouse_amount' => [
                'title' => 'fba_warehouse_amount',
                'remark' => 'FBA销售额',
                'is_show' => 1
            ],
            'fba_warehouse_orders' => [
                'title' => 'fba_warehouse_orders',
                'remark' => 'FBA订单数',
                'is_show' => 1
            ],
        ];
        return $title;
    }

    /**
     * 月度标题【开发】
     */
    public function monthlyTitleDevelop()
    {
        $title = [
            'name' => [
                'title' => 'name',
                'remark' => '部门',
                'is_show' => 1
            ],
            'leader_name' => [
                'title' => 'leader_name',
                'remark' => '负责人',
                'is_show' => 1
            ],
            'target_amount' => [
                'title' => 'target_amount',
                'remark' => '目标',
                'is_show' => 1
            ],
            'actual_amount' => [
                'title' => 'actual_amount',
                'remark' => '已完成',
                'is_show' => 1
            ],
            'rate' => [
                'title' => 'rate',
                'remark' => '进度',
                'is_show' => 1
            ],
            'man' => [
                'title' => 'man',
                'remark' => '部门人数',
                'is_show' => 1
            ],
            'man_e' => [
                'title' => 'man_e',
                'remark' => '部门人均',
                'is_show' => 1
            ],
        ];
        return $title;
    }

    /**
     * 导出部门成员数据
     * @param string $params
     * @return array
     */
    public function applyExport($params = '')
    {

        set_time_limit(0);
        $userInfo = Common::getUserInfo();
//        try {
        //获取导出文件名
        $fileName = $this->newExportFileName($params);
        //判断是否存在筛选条件，更改导出名
        if (isset($fileName) && $fileName != '') {
            $setFileName = 1;
            $name = $fileName . (isset($params['name']) ? $params['name'] : $userInfo['realname']);
            $fileName = $name;
        } else {
            $setFileName = 0;
            $name = isset($params['name']) ? $params['name'] : $userInfo['realname'];
            $fileName = $name . date('YmdHis', time());
        }

        $downLoadDir = '/download/customer_message/';
        $saveDir = ROOT_PATH . 'public' . $downLoadDir;
        if (!is_dir($saveDir) && !mkdir($saveDir, 0777, true)) {
            throw new Exception('导出目录创建失败');
        }
        $fullName = $saveDir . $fileName;
        $titleData = $this->title($params);
        $deparmentServer = new MonthlyTargetDepartmentService();
        $allDeparment = $deparmentServer->getAllDepartmentTree();
        $remark = [];
        if (!empty($field)) {
            $title = [];
            foreach ($field as $k => $v) {
                if (isset($titleData[$v])) {
                    array_push($title, $v);
                    array_push($remark, $titleData[$v]['remark']);
                }
            }
        } else {
            $title = [];
            foreach ($titleData as $k => $v) {
                if ($v['is_show'] == 1) {
                    array_push($title, $k);
                    array_push($remark, $v['remark']);
                }
            }
        }
        $where = [];
        $this->where($params, $where);
        $count = $this->doCount($where, $params);

        if ($count > 10000) {
//                $params['field'] = $field;
//                $this->exportApply($params, StatisticByGoodsExportQueue::class, $name, $setFileName);
//                return ['join_queue' => 1, 'message' => '已加入导出队列'];
            return ['message' => '数据过大，无法导出'];
        } else {
            $data = $this->doSearch($where, $params);

            $titleOrderData = [];
            foreach ($remark as $t => $tt) {
                $titleOrderData[$tt] = 'string';
            }
            $data = $this->getMapAll($data,$allDeparment);
            $this->excelSave($titleOrderData, $fullName, $data);
            $auditOrderService = new AuditOrderService();
            $result = $auditOrderService->record($fileName, $saveDir . $fileName);
            return $result;
        }
//        } catch (Exception $e) {
//            throw new JsonErrorException($e->getMessage());
//        }
    }

    /**
     * @param $params
     * @param $where
     */
    public function where($params, &$where)
    {
        $where['status'] = 0;
        if(isset($params['mode']) ){
            $where['mode'] = $params['mode'];
        }
    }


    /**
     * 统计表总数
     * @param $where
     * @param $params
     * @return int|string
     */
    public function doCount($where, $params)
    {
        return $this->userMapModel->where($where)->count();
    }

    /**
     * 查询表
     * @param $where
     * @param $params
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function doSearch($where, $params)
    {
        return $this->userMapModel->where($where)->order('department_id')->select();
    }

    /**
     * 组装多个数据
     * @param $data
     * @return array
     * @throws Exception
     */
    public function getMapAll($data,$allDeparment=[])
    {
        $reData = [];
        foreach ($data as $v) {
            $reData[] = $this->getMapOne($v,$allDeparment);
        }
        return $reData;
    }

    /**
     * 组装一个数据 对象->数组
     * @param $data
     * @return array
     * @throws Exception
     */
    public function getMapOne($data,$allDeparment)
    {
        $userInfo = Cache::store('user')->getOneUser($data['user_id']);
        $deparment = $allDeparment[$data['department_id']];
        $one = [
            'department_id' => $data['department_id'],
            'department' => $deparment['name_path'],
            'user_id' => $data['user_id'],
            'realname' => $userInfo['realname'] ?? '',
            'job_number' => $userInfo['job_number'] ?? '',
            'target_amount' => 0,
        ];
        return $one;
    }

    /**
     * @param $params
     * @param $where
     */
    public function whereMonthly($params, &$where)
    {
        if(isset($params['year']) && $params['year']){
            $where['year'] = $params['year'];
        }else{
            $where['year'] = date('Y');
        }

        if(isset($params['monthly']) && $params['monthly']){
            $where['monthly'] = $params['monthly'];
        }else{
            $where['monthly'] = date('m');
        }

        if(isset($params['mode']) ){
            $where['mode'] = $params['mode'];
        }

    }


    /**
     * 统计月度表总数
     * @param $where
     * @param $params
     * @return int|string
     */
    public function doCountMonthly($where, $params)
    {
        return (new MonthlyTargetAmount())->where($where)->count();
    }

    /**
     * 查询月度表
     * @param $where
     * @param $params
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function doSearchMonthly($where, $params)
    {
        return (new MonthlyTargetAmount())->where($where)->order('department_id')->select();
    }

    /**
     * 组装多个数据月度表
     * @param $data
     * @param $day
     * @param $title
     * @return array
     * @throws Exception
     */
    public function getMapAllMonthly($data,$day,$title)
    {
        $reData = [];
        $deparmentServer = new MonthlyTargetDepartmentService();
        $allDeparment = $deparmentServer->getAllDepartmentTree();
        foreach ($data as $v) {
            if($v['type'] == 0){
                $deparmentMode = $allDeparment[$v['department_id']];
            }else{
                $deparmentMode = $allDeparment[$v['relation_id']];
            }
            $one = $this->getDataOneMonthly($v, $deparmentMode,$all);
            $this->getDataOneAdd($v, $one,$all,$day);
            if($v['type'] == 0){
                $userInfo = Cache::store('user')->getOneUser($v['relation_id']);
                $one['name'] .= '-'.$userInfo['realname'] ?? '';
            }
            $temp = [];
            foreach ($title as $k => $v) {
                $temp[$v] = $one[$v];
            }
            $reData[] = $temp;
        }
        return $reData;
    }

    /**
     * @title 生成导出表名
     * @param $params
     * @return string
     */
    public function newExportFileName($params)
    {
        $fileName = '部门与成员组成表';
        $times = date('Y-m-d_H_i_s');
        $fileName .= $times;
        return $fileName;
    }

    /**
     * 考核目标金额导入
     * @param $params
     * @return array
     */
    public function import($params)
    {
        set_time_limit(0);
        try {
            $title = $this->titleToConvert($params);
            $filename = 'upload/' . uniqid() . '.' . $params['extension'];
            $this->saveFile($filename, $params);
            $trackingData = Excel::readExcel($filename);
            $orderList = [];
            foreach ($trackingData as $key => $row) {
                $orderList[] = $this->convert($row, $title);
            }
            return $orderList;
        } catch (Exception $e) {
            throw new JsonErrorException($e->getMessage());
        }
    }

    /**
     * 保存文件
     * @param $filename
     * @param $params
     * @return mixed
     * @throws Exception
     */
    public function saveFile($filename, &$params)
    {
        if (empty($params['content'])) {
            throw new Exception('添加的内容不能为空');
        }
        $start = strpos($params['content'], ',');
        $content = substr($params['content'], $start + 1);
        file_put_contents($filename, base64_decode(str_replace(" ", "+", $content)));
        return $filename;
    }

    /**
     * 导入数据属性的转变
     * @return array
     */
    public function titleToConvert($params = [])
    {
        $re = [];
        $title = $this->title($params);
        foreach ($title as $v) {
            if ($v['is_show'] == 1) {
                $re[$v['remark']] = $v['title'];
            }
        }
        return $re;
    }

    /**
     * 标题转换
     * @param $row
     * @return array
     * @throws Exception
     */
    public function convert($row, $title = [])
    {

        $data = [];
        foreach ($title as $key => $value) {
            $key = trim($key);
            if (isset($row[$key])) {
                $data[$value] = $row[$key];
            }
        }
        return $data;
    }

    /**
     * 保存导入的成员考核目标
     * @param array $import
     * @param array $importData
     * @return array
     * @throws Exception
     */
    public function saveImport(array $import, array $importData)
    {
        $error = ['message'=>'导入成功'];
        $shipServer = new MemberShipService();
        $departmentServer = new MonthlyTargetDepartmentService();
        $time = time();
        $departmentAmount = [];
        $departmentAccount = [];
        $departmentMan = [];
        $thisTime = $import['year'] . '-' . $import['monthly'];

        $addTarget = [];
        $addLog = [];

        //人员操作
        foreach ($importData as $key => $value) {
            try {
                $department_id = $value['department_id'];
                if(!$department_id || !$value['user_id'] || $value['target_amount'] == 0){
                    array_push($error, $value['user_id'].'跳过没有'.$department_id);
                    continue;
                }
                $one = [
                    'year' => $import['year'],
                    'monthly' => $import['monthly'],
                    'mode' => $import['mode'],
                    'department_id' => $department_id,
                    'type' => 0,
                    'relation_id' => $value['user_id'],
                    'target_amount' => $value['target_amount'],
                    'create_time' => $time,
                    'update_time' => $time,
                ];
                if (isset($departmentAmount[$department_id])) {
                    $departmentAmount[$department_id] += $one['target_amount'];
                } else {
                    $departmentAmount[$department_id] = $one['target_amount'];
                }

                //账号统计数
                $account = $shipServer->getAccountBySellerUserId($value['user_id']);
                //部门人员-
                $man = 1;

                $old = $this->amountModel->isHas($one);
                if($old){
                    $man = 0;
                    $old_details = explode(',', $old['total_details']);
                    if($old_details[1] != $account){
                        $account = $account - $old_details[1];
                    }
                }

                if (isset($departmentAccount[$department_id])) {
                    $departmentAccount[$department_id] += $account;
                } else {
                    $departmentAccount[$department_id] = $account;
                }

                if (isset($departmentMan[$department_id])) {
                    $departmentMan[$department_id] += $man;
                } else {
                    $departmentMan[$department_id] = $man;
                }

                $one['total_details'] = $man . ',' . $account;


                $addTarget[] = $one;
                $addLog[] = [
                    'type' => MonthlyTargetLog::user,
                    'department_id' =>$department_id,
                    'user_id' => $value['user_id'],
                    'remark' => $thisTime . '增加目标：' . $one['target_amount'],
                ];
            } catch (Exception $e) {
                array_push($error, $e->getMessage() . $e->getFile() . $e->getLine());
            }
        }

        //部门操作
        $tree = $departmentServer->getAllDepartmentTree();
        foreach ($departmentAmount as $department => $target_amount) {
            $work = $tree[$department];
            if (!$work || $target_amount == 0) {
                continue;
            }
            $work['parents'][] = $department;
            foreach ($work['parents'] as $department_id) {


                $one = [
                    'year' => $import['year'],
                    'monthly' => $import['monthly'],
                    'mode' => $import['mode'],
                    'department_id' => 0,
                    'type' => 1,
                    'relation_id' => $department_id,
                    'target_amount' => $target_amount,
                    'create_time' => $time,
                    'update_time' => $time,
                ];
                $one['total_details'] = $departmentMan[$department] . ',' . $departmentAccount[$department];


                $addTarget[] = $one;
                $addLog[] = [
                    'type' => MonthlyTargetLog::department,
                    'department_id' =>$department_id,
                    'user_id' => 0,
                    'remark' => $thisTime . '增加目标：' . $one['target_amount'],
                ];

            }
        }

        Db::startTrans();
        try {
            foreach ($addLog as $log){
                MonthlyTargetLog::AddLog($log['type'], $log['department_id'], $log['user_id'], $log['remark']);
            }
            foreach ($addTarget as $v){
                $amountModel = new MonthlyTargetAmount();
                $amountModel->addTarget($v);
            }
            Cache::store('MonthlyDepartment')->deleteAll();
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            array_push($error, $e->getMessage() . $e->getFile() . $e->getLine());
        }
        return $error;
    }

    /**
     * 重新计算部门人数和平台账号数
     * @param string $year
     * @param string $monthly
     * @param int $mode
     * @return array
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function recalculateManAccount($year = '' , $monthly = '' ,$mode = 0)
    {
        if(!$year){
            $year = date('Y');
        }
        if(!$monthly){
            $monthly = date('m');
        }
        $where['mode'] = $mode;
        $where['status'] = 0;
        $userList = (new MonthlyTargetDepartmentUserMap())->where($where)->select();

        $shipServer = new MemberShipService();
        $departmentServer = new MonthlyTargetDepartmentService();
        $time = time();
        $departmentAccount = [];
        $departmentMan = [];
        $thisTime = $year . '-' . $monthly;

        $updateTarget = [];
        $addLog = [];

        //人员操作
        foreach ($userList as $key => $value) {
            $department_id = $value['department_id'];
            $updateWhere = [
                'year' => $year,
                'monthly' => $monthly,
                'mode' => $mode,
                'type' => 0,
                'relation_id' => $value['user_id'],
            ];
            //账号统计数
            $account = $shipServer->getAccountBySellerUserId($value['user_id']);
            //部门人员-
            $man = 1;
            if (isset($departmentAccount[$department_id])) {
                $departmentAccount[$department_id] += $account;
            } else {
                $departmentAccount[$department_id] = $account;
            }

            if (isset($departmentMan[$department_id])) {
                $departmentMan[$department_id] += $man;
            } else {
                $departmentMan[$department_id] = $man;
            }

            $one['total_details'] = $man . ',' . $account;
            $one['update_time'] = $time;
            $updateTarget[] = [
                'save' => $one,
                'where' => $updateWhere,
            ];
            $addLog[] = [
                'type' => MonthlyTargetLog::user,
                'department_id' =>$department_id,
                'user_id' => $value['user_id'],
                'remark' => $thisTime . '更新部门人员:'.$man.',账号：' . $account,
            ];
        }
        $addTarget = [];
        //部门操作
        $tree = $departmentServer->getAllDepartmentTree();
        foreach ($departmentMan as $department => $man) {
            $work = $tree[$department] ?? '';
            if (!$work || $man == 0) {
                continue;
            }
            $work['parents'][] = $department;
            foreach ($work['parents'] as $department_id) {

                $one = [
                    'year' => $year,
                    'monthly' => $monthly,
                    'mode' => $mode,
                    'type' => 1,
                    'relation_id' => $department_id,
                    'target_amount' => 0,
                ];
                $account = $departmentAccount[$department];
                $one['total_details'] = $man . ',' . $account;
                $one['update_time'] = $time;
                $addTarget[] = $one;
                $addLog[] = [
                    'type' => MonthlyTargetLog::department,
                    'department_id' =>$department_id,
                    'user_id' => 0,
                    'remark' => $thisTime . '更新部门人员:'.$man.',账号：' . $account,
                ];

            }
        }
        $error = [];
        Db::startTrans();
        try {
            //先更新部门的 部门人数 和 平台账号为 0
            $updateWhere = [
                'year' => $year,
                'monthly' => $monthly,
                'mode' => $mode,
                'type' => 1,
            ];
            $save['total_details'] = '0,0';
            $amountModel = new MonthlyTargetAmount();
            $amountModel->save($save,$updateWhere);
            foreach ($addLog as $log){
                MonthlyTargetLog::AddLog($log['type'], $log['department_id'], $log['user_id'], $log['remark']);
            }
            foreach ($updateTarget as $v){
                $amountModel = new MonthlyTargetAmount();
                $amountModel->save($v['save'],$v['where']);
            }
            foreach ($addTarget as $v){
                $amountModel = new MonthlyTargetAmount();
                $amountModel->addTarget($v);
            }
            Cache::store('MonthlyDepartment')->deleteAll();
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            array_push($error, $e->getMessage() . $e->getFile() . $e->getLine());
        }
        return $error;
    }

    /**
     * 添加统计数据 回写 开发人员
     * @param $user_id 用户id
     * @param int $actual_amount 完成的开发数量
     * @param string $year 年 2018
     * @param string $monthly 月 11
     * @return array|bool
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function addDevelopment($user_id, $actual_amount = 0, $year = '', $monthly = '')
    {
        return $this->addAmount($user_id,$actual_amount,0,[],$year,$monthly,MonthlyModeConst::mode_development);
    }

    /**
     * 添加统计数据 回写
     * @param $user_id
     * @param int $actual_amount
     * @param int $order_qty
     * @param array $distribution_details $distribution = [ 'local_warehouse_amount'=>0, //本地仓金额
     *                                                      'oversea_warehouse_amount' => 0,//海外仓金额
     *                                                      'fba_warehouse_amount' => 0, //fba金额
     *                                                      'fba_warehouse_orders' => 0,//fba订单数
     *                                                      ];
     * @param string $year
     * @param string $monthly
     * @return array|bool
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function addAmount($user_id, $actual_amount = 0, $order_qty = 0, $distribution_details = [], $year = '', $monthly = '',$mode = 0)
    {

        $userMap = (new DepartmentUserMap())->where('user_id', $user_id)->find();

        if (!$userMap || !$userMap['department_id']) {
            return ['status' => false, 'message' => '该用户没有绑定小组'];
        }

        if ($userMap['status'] == 1 && $userMap['update_time'] < strtotime(date('Y-m'))) {
            return ['status' => false, 'message' => '该用户已经过期'];
        }

        $dataUpdate = [];
        $dataAdd = [];
        $dataLog = [];

        if (!$year) {
            $year = date('Y');
        }
        if (!$monthly) {
            $monthly = date('m');
        }

        //个人的处理
        $time = time();
        $one = [
            'year' => $year,
            'monthly' => $monthly,
            'mode' => $mode,
            'type' => 0,
            'relation_id' => $user_id,
        ];
        $old = $this->amountModel->isHas($one);
        if ($old) {
            $thisDayTime = strtotime(date('y-m-d'));
            if($old['update_time'] > $thisDayTime){
//                return ['status' => false, 'message' => '今天已经推送过了'];
            }
            $save = [
                'update_time' => $time,
            ];
            $this->getDistributionDetails($actual_amount, $order_qty, $distribution_details, $old, $save);
            $dataUpdate[] = [
                'where' => ['id' => $old['id']],
                'save' => $save,
            ];

        } else {
            $one = [
                'year' => $year,
                'monthly' => $monthly,
                'department_id' => $userMap['department_id'],
                'type' => 0,
                'relation_id' => $user_id,
                'target_amount' => 0,
                'create_time' => $time,
                'update_time' => $time,
                'order_qty' => $order_qty,
                'actual_amount' => $actual_amount,
                'mode' => $mode,
            ];
            $shipServer = new MemberShipService();
            $account = $shipServer->getAccountBySellerUserId($user_id);
            $one['total_details'] = '1,' . $account;
            $distribution = [
                'local_warehouse_amount' => $distribution_details['local_warehouse_amount'] ?? 0,
                'oversea_warehouse_amount' => $distribution_details['oversea_warehouse_amount'] ?? 0,
                'fba_warehouse_amount' => $distribution_details['fba_warehouse_amount'] ?? 0,
                'fba_warehouse_orders' => $distribution_details['fba_warehouse_orders'] ?? 0,
            ];
            $one['distribution_details'] = json_encode($distribution);
            $dataAdd[] = $one;
        }

        $message = $year . '-' . $monthly . '金额变动,累计总金额' . $actual_amount . ',累计订单数' . $order_qty . ',其他数据:' . json_encode($distribution_details);

        //组的处理
        $work = (new MonthlyTargetDepartmentService())->getAllDepartmentTree($userMap['department_id']);
        $work['parents'][] = $userMap['department_id'];
        foreach ($work['parents'] as $department_id) {
            $one = [
                'year' => $year,
                'monthly' => $monthly,
                'mode' => $mode,
                'type' => 1,
                'relation_id' => $department_id,
            ];
            $old = $this->amountModel->isHas($one);
            if ($old) {
                $save = [
                    'update_time' => $time,
                ];
                $this->getDistributionDetails($actual_amount, $order_qty, $distribution_details, $old, $save);
                $dataUpdate[] = [
                    'where' => ['id' => $old['id']],
                    'save' => $save,
                ];
            } else {
                $one = [
                    'year' => $year,
                    'monthly' => $monthly,
                    'department_id' => 0,
                    'type' => 1,
                    'relation_id' => $department_id,
                    'target_amount' => 0,
                    'create_time' => $time,
                    'update_time' => $time,
                    'order_qty' => $order_qty,
                    'actual_amount' => $actual_amount,
                    'mode' => $mode,
                ];
                $shipServer = new MemberShipService();
                $account = $shipServer->getAccountBySellerUserId($user_id);
                $one['total_details'] = '1,' . $account;
                $distribution = [
                    'local_warehouse_amount' => $distribution_details['local_warehouse_amount'] ?? 0,
                    'oversea_warehouse_amount' => $distribution_details['oversea_warehouse_amount'] ?? 0,
                    'fba_warehouse_amount' => $distribution_details['fba_warehouse_amount'] ?? 0,
                    'fba_warehouse_orders' => $distribution_details['fba_warehouse_orders'] ?? 0,
                ];
                $one['distribution_details'] = json_encode($distribution);
                $dataAdd[] = $one;

            }
            $dataLog[] = [
                'type' => MonthlyTargetLog::amount,
                'department_id' => $department_id,
                'user_id' => $user_id,
                'message' => $message,
            ];
        }
        Db::startTrans();
        try {
            foreach ($dataAdd as $v) {
                $amountModel = new MonthlyTargetAmount();
                $amountModel->addTarget($v);
            }
            foreach ($dataUpdate as $v) {
                $amountModel = new MonthlyTargetAmount();
                $amountModel->save($v['save'], $v['where']);
            }
            foreach ($dataLog as $v) {
                MonthlyTargetLog::AddLog($v['type'], $v['department_id'], $v['user_id'], $v['message']);
            }
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            return ['status' => false, 'message' => $e->getMessage() . $e->getFile() . $e->getLine()];
        }
        return true;
    }

    /**
     * 累计数据的处理
     * @param int $actual_amount
     * @param int $order_qty
     * @param array $distribution_details
     * @param $old
     * @param $save
     */
    public function getDistributionDetails($actual_amount = 0, $order_qty = 0, $distribution_details = [], $old, &$save)
    {

        if ($order_qty > 0) {
            $save['order_qty'] = $old['order_qty'] + $order_qty;
        }
        if ($actual_amount > 0) {
            $save['actual_amount'] = $old['actual_amount'] + $actual_amount;
        }

        $oldDD = json_decode($old['distribution_details'], true);


        $distributionNew = [
            'local_warehouse_amount' => ($distribution_details['local_warehouse_amount'] ?? 0) + ($oldDD['local_warehouse_amount'] ?? 0),
            'oversea_warehouse_amount' => ($distribution_details['oversea_warehouse_amount'] ?? 0) + ($oldDD['oversea_warehouse_amount'] ?? 0),
            'fba_warehouse_amount' => ($distribution_details['fba_warehouse_amount'] ?? 0) + ($oldDD['fba_warehouse_amount'] ?? 0),
            'fba_warehouse_orders' => ($distribution_details['fba_warehouse_orders'] ?? 0) + ($oldDD['fba_warehouse_orders'] ?? 0),
        ];


        $save['distribution_details'] = json_encode($distributionNew);
    }


    /**
     * 查询月度目标统计数据
     * @param bool $isDetails
     * @param string $year
     * @param string $monthly
     * @param int $department_id
     * @param int $user_id
     * @param int $mode
     * @param int $types
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAllDeparment($isDetails = false, $year = '', $monthly = '',$department_id = 0,$user_id = 0,$mode = 0 ,$types = 0)
    {
        $reData = [];
        $deparmentServer = new MonthlyTargetDepartmentService();
        $allDeparment = $deparmentServer->getAllDepartmentTree();



        if (!$year) {
            $year = date('Y');
        }
        if (!$monthly) {
            $monthly = date('m');
        }
        $where = [
            'mode' => $mode,
            'year' => $year,
            'monthly' => $monthly,
            'type' => 1,
        ];


        if($department_id){
            if(is_array($department_id)){
                $where['relation_id'] = ['in', $department_id];
            }else {
                $allDeparmentIds = $allDeparment[$department_id]['child_ids'];
                if ($allDeparment[$department_id]['is_bottom']) {
                    $where['type'] = 0;
                    $allDeparmentIds = $this->userMapModel->where('department_id', $department_id)->column('user_id');
                    $where['relation_id'] = ['in', $allDeparmentIds];
                } else {
                    $where['relation_id'] = ['in', $allDeparmentIds];
                }
            }
        }else{
            $allDeparmentIds = $allDeparment['child_ids'];
            $where['relation_id'] = ['in',$allDeparmentIds];
        }

        if($user_id){
            if(is_array($user_id)){
                $where['relation_id'] = ['in',$user_id];
            }else{
                $where['relation_id'] = $user_id;
            }
            $where['type'] = 0;
        }

        $day = $this->getDay($year,$monthly);

        if($where['type'] == 1){
            $where['a.type'] = 1;
            $where['a.mode'] = $mode;
            unset($where['type']);
            unset($where['mode']);
            $join[] = ['monthly_target_department d','d.id=a.relation_id','left'];
            $field = 'a.*,d.sort';
            $list = $this->amountModel->alias('a')->join($join)->field($field)->where($where)->order('sort desc')->select();
        }else{
            $list = $this->amountModel->where($where)->select();
        }

        if($mode == MonthlyModeConst::mode_sales){
            $reData = $this->getMonthlySales($isDetails,$list,$allDeparment,$day,$types);
        }elseif($mode == MonthlyModeConst::mode_development){
            $reData = $this->getMonthlyDevelopment($isDetails,$list,$allDeparment,$day,$types);
        }


        foreach ($reData as &$v){
            if($v['target_amount'] == 0){
                $v['rate'] = 0;
            }
        }


        return $reData;
    }

    /**
     * 组装返回开发的进度数据
     * @param $isDetails
     * @param $list
     * @param $allDeparment
     * @param $day
     * @param $types
     * @return array
     */
    private function getMonthlyDevelopment($isDetails,$list,$allDeparment,$day,$types=0)
    {
        $reData = [];
        $all = [
            'is_bottom' => 0,
            'department_id' => '',
            'name' => '汇总',
            'leader_id' => '',
            'leader_name' => '',
            'target_amount' => 0,
            'actual_amount' => 0,
        ];
        if ($isDetails) {
            $all['man'] = 0;
            $all['accounts'] = 0;
            $all['local_warehouse_amount'] = 0;
            $all['oversea_warehouse_amount'] = 0;
            $all['fba_warehouse_amount'] = 0;
            $all['fba_warehouse_orders'] = 0;
            $all['order_qty'] = 0;
            if($types == 1){
                $all['man_e_y'] = 0;
                $all['actual_amount_e_y'] = 0;
                $all['accounts_e_y'] = 0;
            }
            foreach ($list as $v) {
                if($v['type'] == 0){
                    $deparmentMode = $allDeparment[$v['department_id']];
                }else{
                    $deparmentMode = $allDeparment[$v['relation_id']];
                }
                $one = $this->getDataOne($v, $deparmentMode,$all);
                $this->getDataOneAdd($v, $one,$all,$day);
                $this->formattingDevelop($one);
                $reData[] = $one;
            }
            $all['actual_amount_e'] = $this->rateE($all['actual_amount'] , $day);
            $all['man_e'] = $this->rateE($all['actual_amount'] , $all['man']);
            $all['man'] = number_format($all['man']);
        } else {
            foreach ($list as $v) {
                if($v['type'] == 0){
                    $deparmentMode = $allDeparment[$v['department_id']];
                }else{
                    $deparmentMode = $allDeparment[$v['relation_id']];
                }
                $one = $this->getDataOne($v, $deparmentMode,$all);
                $this->formattingDevelop($one);
                $reData[] = $one;
            }
        }
        $all['rate'] = $this->rateE($all['actual_amount'] * 100 , $all['target_amount'] ,100);
        $all['actual_amount'] = number_format($all['actual_amount']);
        $all['target_amount'] = number_format($all['target_amount']);

        $reData[] = $all;




        return $reData;
    }

    /**
     * 开发的数据格式化
     * @param $one
     */
    private function formattingDevelop(&$one)
    {
        $one['actual_amount'] = str_replace('.00','',$one['actual_amount'] );
        $one['target_amount'] = str_replace('.00','',$one['target_amount']);
    }

    /**
     * 组装返回销售的进度数据
     * @param $isDetails
     * @param $list
     * @param $allDeparment
     * @param $day
     * @param $types
     * @return array
     */
    private function getMonthlySales($isDetails,$list,$allDeparment,$day,$types = 0)
    {
        $reData = [];
        $all = [
            'is_bottom' => 0,
            'department_id' => '',
            'name' => '汇总',
            'leader_id' => '',
            'leader_name' => '',
            'target_amount' => 0,
            'actual_amount' => 0,
        ];
        if ($isDetails) {
            $all['man'] = 0;
            $all['accounts'] = 0;
            $all['local_warehouse_amount'] = 0;
            $all['oversea_warehouse_amount'] = 0;
            $all['fba_warehouse_amount'] = 0;
            $all['fba_warehouse_orders'] = 0;
            $all['order_qty'] = 0;
            if($types == 1){
                $all['man_e_y'] = 0;
                $all['actual_amount_e_y'] = 0;
                $all['accounts_e_y'] = 0;
            }
            foreach ($list as $v) {
                if($v['type'] == 0){
                    $deparmentMode = $allDeparment[$v['department_id']];
                }else{
                    $deparmentMode = $allDeparment[$v['relation_id']];
                }
                $one = $this->getDataOne($v, $deparmentMode,$all);
                $this->getDataOneAdd($v, $one,$all,$day,$types);
                $reData[] = $one;
            }
            $all['order_qty_e'] = $this->rateE($all['order_qty'] ,$day);
            $all['actual_amount_e'] = $this->rateE($all['actual_amount'] , $day);
            $all['man_e'] = $this->rateE($all['actual_amount'] , $all['man']);
            $all['accounts_e'] = $this->rateE($all['actual_amount'] , $all['accounts']);
            $all['local_warehouse_amount'] = number_format($all['local_warehouse_amount']);
            $all['oversea_warehouse_amount'] = number_format($all['oversea_warehouse_amount']);
            $all['fba_warehouse_amount'] = number_format($all['fba_warehouse_amount']);
            $all['fba_warehouse_orders'] = number_format($all['fba_warehouse_orders']);
            $all['man'] = number_format($all['man']);
            $all['accounts'] = number_format($all['accounts']);

        } else {
            foreach ($list as $v) {
                if($v['type'] == 0){
                    $deparmentMode = $allDeparment[$v['department_id']];
                }else{
                    $deparmentMode = $allDeparment[$v['relation_id']];
                }
                $one = $this->getDataOne($v, $deparmentMode,$all);

                $reData[] = $one;
            }
        }
        $all['rate'] = $this->rateE($all['actual_amount'] * 100 , $all['target_amount'] , 100);
        $all['actual_amount'] = number_format($all['actual_amount']);
        $all['target_amount'] = number_format($all['target_amount']);

        $reData[] = $all;
        return $reData;
    }

    public function restore($num)
    {
        $num = intval(str_replace(',','',$num));
        return $num;
    }

    private function getDataOne($v, $deparmentMode,&$all = [])
    {
        if($all){
            $all['target_amount'] += $v['target_amount'];
            $all['actual_amount'] += $v['actual_amount'];
        }
        $one = [
            'is_bottom' => $deparmentMode['is_bottom'],
            'department_id' => $v['relation_id'],
            'name' => $deparmentMode['name'],
            'leader_id' => $deparmentMode['leader_id'],
            'leader_name' => $deparmentMode['leader_name'],
            'target_amount' => $v['target_amount'],
            'actual_amount' => $v['actual_amount'],
            'rate' => $this->rateE($v['actual_amount'] * 100 , $v['target_amount'] ,100),
        ];
        if($v['type'] == 0){
            $userInfo = Cache::store('user')->getOneUser($v['relation_id']);
            $one['name'] = $userInfo['realname'] ?? '';
            $one['is_bottom'] =  2;
        }
        $one['actual_amount'] = number_format($one['actual_amount']);
        $one['target_amount'] = number_format($one['target_amount']);
        return $one;
    }

    private function getDataOneMonthly($v, $deparmentMode,&$all = [])
    {
        if($all){
            $all['target_amount'] += $v['target_amount'];
            $all['actual_amount'] += $v['actual_amount'];
        }
        return [
            'name' => $deparmentMode['name_path'],
            'leader_name' => implode(",",$deparmentMode['leader_name']),
            'target_amount' => $v['target_amount'],
            'actual_amount' => $v['actual_amount'],
            'rate' => $this->rateE($v['actual_amount'] * 100 , $v['target_amount'] , 100 ),
        ];
    }

    private function getDataOneAdd($v, &$one,&$all = [],$day,$types = 0)
    {

        $one['order_qty_e'] = $this->rateE($v['order_qty'] ,$day);
        $one['actual_amount_e'] = $this->rateE($v['actual_amount'] , $day);
        $total_details = explode(',', $v['total_details']);
        $one['man'] = $total_details[0];
        $one['man_e'] = $this->rateE($v['actual_amount'] , $one['man']);
        $one['accounts'] = $total_details[1];
        $one['accounts_e'] = $this->rateE($v['actual_amount'] , $one['accounts']);
        $oldDD = json_decode($v['distribution_details'], true);
        $one['local_warehouse_amount'] = $oldDD['local_warehouse_amount'] ?? 0;
        $one['oversea_warehouse_amount'] = $oldDD['oversea_warehouse_amount'] ?? 0;
        $one['fba_warehouse_amount'] = $oldDD['fba_warehouse_amount'] ?? 0;
        $one['fba_warehouse_orders'] = $oldDD['fba_warehouse_orders'] ?? 0;


        if($types == 1){
            $one['man_e_y'] = $this->restore($one['man_e']);
            $one['actual_amount_e_y'] = $this->restore($one['actual_amount_e']);
            $one['accounts_e_y'] = $this->restore($one['accounts_e']);
            $all['man_e_y'] += $one['man_e_y'];
            $all['actual_amount_e_y'] += $one['actual_amount_e_y'];
            $all['accounts_e_y'] += $one['accounts_e_y'];
        }

        if($all){
            $all['order_qty'] += $v['order_qty'];
            $all['man'] += $one['man'];
            $all['accounts'] += $one['accounts'];
            $all['local_warehouse_amount'] += $one['local_warehouse_amount'];
            $all['oversea_warehouse_amount'] += $one['oversea_warehouse_amount'];
            $all['fba_warehouse_amount'] += $one['fba_warehouse_amount'];
            $all['fba_warehouse_orders'] += $one['fba_warehouse_orders'];
        }
        $one['local_warehouse_amount'] = number_format($one['local_warehouse_amount']);
        $one['oversea_warehouse_amount'] = number_format($one['oversea_warehouse_amount']);
        $one['fba_warehouse_amount'] = number_format($one['fba_warehouse_amount']);
        $one['fba_warehouse_orders'] = number_format($one['fba_warehouse_orders']);
    }

    private function rateE($divisor,$dividend,$num = 0,$decimals = 0)
    {
        if($dividend > 0){
            return number_format($divisor / $dividend,$decimals);
        }
        return $num;
    }

    /**
     * 导出月度考核数据
     * @param string $params
     * @return array
     */
    public function applyExportMonthly($params = '')
    {

        set_time_limit(0);
        $userInfo = Common::getUserInfo();
        try {
            //获取导出文件名
            $fileName = $this->exportFileNameMonthly($params);
            //判断是否存在筛选条件，更改导出名
            if (isset($fileName) && $fileName != '') {
                $setFileName = 1;
                $name = $fileName . (isset($params['name']) ? $params['name'] : $userInfo['realname']);
                $fileName = $name;
            } else {
                $setFileName = 0;
                $name = isset($params['name']) ? $params['name'] : $userInfo['realname'];
                $fileName = $name . date('YmdHis', time());
            }

            $downLoadDir = '/download/customer_message/';
            $saveDir = ROOT_PATH . 'public' . $downLoadDir;
            if (!is_dir($saveDir) && !mkdir($saveDir, 0777, true)) {
                throw new Exception('导出目录创建失败');
            }
            $fullName = $saveDir . $fileName;
            if($params['mode'] == MonthlyModeConst::mode_development){
                $titleData = $this->monthlyTitleDevelop();
            }else{
                $titleData = $this->monthlyTitle();
            }


            $remark = [];
            if (!empty($field)) {
                $title = [];
                foreach ($field as $k => $v) {
                    if (isset($titleData[$v])) {
                        array_push($title, $v);
                        array_push($remark, $titleData[$v]['remark']);
                    }
                }
            } else {
                $title = [];
                foreach ($titleData as $k => $v) {
                    if ($v['is_show'] == 1) {
                        array_push($title, $k);
                        array_push($remark, $v['remark']);
                    }
                }
            }
            $where = [];
            $this->whereMonthly($params, $where);
            $count = $this->doCountMonthly($where, $params);
            if ($count > 10000) {
                return ['message' => '数据过大，无法导出'];
            } else {
                $data = $this->doSearchMonthly($where, $params);
                $day = $this->getDay($where['year'], $where['monthly']);
                $titleOrderData = [];
                foreach ($remark as $t => $tt) {
                    $titleOrderData[$tt] = 'string';
                }
                $data = $this->getMapAllMonthly($data, $day,$title);
                $this->excelSave($titleOrderData, $fullName, $data);
                $auditOrderService = new AuditOrderService();
                $result = $auditOrderService->record($fileName, $saveDir . $fileName);
                return $result;
            }
        } catch (Exception $e) {
            throw new JsonErrorException($e->getMessage());
        }
    }


    /**
     * @title 生成导出表名月度表
     * @param $params
     * @return string
     */
    public function exportFileNameMonthly($params)
    {
        $fileName = '月度目标考核表';
        if($params['mode'] == MonthlyModeConst::mode_sales){
            $fileName .= '销售';
        }elseif ($params['mode'] == MonthlyModeConst::mode_development){
            $fileName .= '开发';
        }
        $times = date('Y-m-d_H_i_s');
        $fileName .= $times;
        return $fileName;
    }


    /**
     * 查询考核目标数据
     * @param $relation_ids
     * @param int $type
     * @param string $monthly
     * @param string $year
     * @return array
     */
    public function getTarget($relation_ids, $type = 0, $monthly = '', $year = '',$mode=null)
    {
        if (!$monthly) {
            $monthly = date('m');
        }
        if (!$year) {
            $year = date('Y');
        }
        $where = [
            'monthly' => $monthly,
            'year' => $year,
            'type' => $type,
        ];

        if(!is_null($mode)){
            $where['mode'] = $mode;
        }

        if (is_array($relation_ids)) {
            $where['relation_id'] = ['in', $relation_ids];
        } else {
            $where['relation_id'] = $relation_ids;
        }
        $list = (new MonthlyTargetAmount())->where($where)->column('target_amount', 'relation_id');
        return $list;

    }

    /**
     * 某个月的最后一天
     * @param string $year
     * @param int $monthly
     * @return false|string
     */
    public function getDay($year = '',$monthly = 1)
    {
        if($year == date('Y') && $monthly == date('m')){
            $day = date('d') - 1;
            $day = $day < 1 ? 1 : $day;
        }else{
            $day = date('t',strtotime($year.'-'.$monthly.'-01'));
        }
        return $day;
    }

    /**
     * 获取某个部门的完成额度排名
     * @param $departmentId
     * @param int $mode
     * @param int $year
     * @param int $monthly
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getTodayRanking($departmentId, $isDepartment = false, $mode = 0, $year = 0, $monthly = 0)
    {
        if(!$departmentId){
            return [];
        }
        if (!$year) {
            $year = date('Y');
        }
        if (!$monthly) {
            $monthly = date('m');
        }

        $where = [
            'mode' => $mode,
            'year' => $year,
            'monthly' => $monthly,
            'type' => 0,
        ];
        if($isDepartment){
            $where['relation_id'] = ['in', $departmentId];
            $where['type'] = 1;
        }else{
            $allDeparmentIds = $this->userMapModel->where('department_id', 'in', $departmentId)->column('user_id');
            $where['relation_id'] = ['in', $allDeparmentIds];
        }

        $field = 'id,relation_id,target_amount,actual_amount';
        $list = $this->amountModel->where($where)->field($field)->order('actual_amount desc,order_qty desc')->select();
        if($list) {
            foreach ($list as &$v) {
                $v['progress'] = $this->rateE($v['actual_amount'], $v['target_amount'], 0, 4) * 100;
            }
            $sort = array_column($list, 'progress');
            array_multisort($sort, SORT_DESC, $list);
        }
        return $list;
    }

    /**
     * 回写员工销售额度排名记录
     * @param int $mode
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function progressTarget($mode = 0)
    {
        $time = strtotime(date('Y-m-d'));
        $departmentIds = (new MonthlyTargetDepartmentService())->getDepartmentTree($mode);
        foreach ($departmentIds as $ids){
            $list = $this->getTodayRanking($ids);
            $ranking = 1;
            foreach ($list as $v){
                $one = [
                    'user_id' => $v['relation_id'],
                    'progress' => $v['progress'],
                    'ranking' => $ranking++,
                    'type' => 0,
                    'mode' => $mode,
                ];
                (new MonthlyTargetProgress())->add($one, $time);
            }
        }
        return true;
    }


    /**
     * 回写部门销售额度排名记录
     * @param int $mode
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function progressDepartmentTarget($mode = 0)
    {
        $time = strtotime(date('Y-m-d'));
        $departmentIds = (new MonthlyTargetDepartmentService())->getDepartmentTree($mode);
        foreach ($departmentIds as $ids){
            $list = $this->getTodayRanking($ids, true);
            $ranking = 1;
            foreach ($list as $v){
                $one = [
                    'user_id' => $v['relation_id'],
                    'progress' => $v['progress'],
                    'ranking' => $ranking++,
                    'type' => 1,
                    'mode' => $mode,
                ];
                (new MonthlyTargetProgress())->add($one, $time);
            }
        }
        return true;
    }


    /**
     * 员工销售额完成进度弹框
     * @param int $user_id
     * @param int $mode
     * @return array
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserRanking($user_id = 0, $mode = 0)
    {
        if(!$user_id){
            $user = Common::getUserInfo();
            $user_id = $user['user_id'];
        }
        $isHasCahe = Cache::store('MonthlyDepartment')->getUserProgress($user_id);
        if($isHasCahe){
           return [];
        }
        Cache::store('MonthlyDepartment')->setUserProgress($user_id);

        //1.判断是否为负责人
        $whereDepartment = [
            'leader_id' => '[' . $user_id . ']',
            'is_bottom' => 1,
            'status' => 0,
        ];
        $leaderId = (new MonthlyTargetDepartment())->where($whereDepartment)->value('id');
        $type = 0;
        $where = [
            'mode' => $mode,
            'year' => date('Y'),
            'monthly' => date('m'),
            'type' => 0,
            'relation_id' => $user_id,
        ];
        if($leaderId){
            $type = 1;
            $where['type'] = 1;
            $where['relation_id'] = $leaderId;
        }

        $field = 'id,department_id,relation_id,target_amount,actual_amount';
        $info = $this->amountModel->where($where)->field($field)->find();
        if(!$info){
            return [];
        }
        if($leaderId){
            $info['department_id'] = $where['relation_id'];
        }

        $one  = [
            'department' => (new MonthlyTargetDepartmentService())->getPidDepartmentName($info['department_id']),
            'user_id' => Cache::store('User')->getOneUserRealname($user_id),
            'target_amount' => $info['target_amount'],
            'actual_amount' => $info['actual_amount'],
            'rate' => $this->rateE($info['actual_amount'], $info['target_amount'], 0, 4) * 100,
            'rate_yesterday' => 0,
            'ranking' => 0,
            'ranking_yesterday' => 0,
            'is_promote' => 0, //是否提升 0 持平 ,1 上升 ，2 下降
            'message' => '',
            'type' => $type,
        ];
        $oldData = (new MonthlyTargetProgress())->getProgress($info['relation_id'],$type ,$mode);
        if($oldData){
            $one['ranking'] = $oldData[0]['ranking'];
            if(isset($oldData[1])){
                $one['ranking_yesterday'] = abs($oldData[1]['ranking'] - $one['ranking']);
                if($oldData[1]['ranking'] > $one['ranking']){
                    $one['is_promote'] = 1;
                }elseif($oldData[1]['ranking'] < $one['ranking']){
                    $one['is_promote'] = 2;
                }
                $one['rate_yesterday'] = $oldData[1]['progress'];
            }
        }
        $one['message'] = $this->getMessage($one['is_promote']);
        return $one;
    }

    /**
     * 提示语
     * @param $isPromote
     * @return mixed
     */
    public function getMessage($isPromote)
    {
        $allMessage = [
            [ //持平
                '停下休息的时候，不要忘记别人还在奔跑。',
                '希望每天叫醒你的不是闹钟，而是梦想。',
                '有志者，事竟成。',
                '比起不去试试，还是试了无悔比较好。愿我们都有这样的力量。',
                '当你的才华撑不起你的野心时，你就该静下心来学习。',
            ],
            [ //上升
                '今日排名较昨日上升，每天进步一点点，加油！',
                '排名上升，继续保持，世界会向那些有目标和远见的人让路。',
                '心有多大，舞台就有多大。',
                '你比自己想象中的更强大。继续加油！',
                '胜人者有力,自强者胜。',
                '撸起袖子加油干！',
            ],
            [ //下降
                '排名稍有下降哦，加油',
                '假如你真的踏实努力过，那么你人生最坏的结果，只不过是大器晚成而已。',
                '今天很残酷，明天更残酷，后天很美好。但是大多数人倒在了明天的晚上，看不到后天的黎明。',
                '挫折其实就是迈向成功所应缴的学费。 ',
                '人一旦堕落，哪怕短暂的几年，上帝就会以更快的速度收走你的天赋与力量。',
                '只要决心成功，失败永远不会把你击倒。',
            ],
        ];
        $key = array_rand($allMessage[$isPromote]);
        return $allMessage[$isPromote][$key];

    }


    public function permissionsSettings($is_pid, &$userId, &$department_id)
    {

        $isShowAll = true;

        if($is_pid != 1){ // 如果是查找下级数据则不用过滤
            //过滤器
            $targetFillter = new Filter(MonthlyTargetAmountFilter::class,true);
            if($targetFillter->filterIsEffective() && $userId){
                //判断是否为负责人
                $info = Cache::store('MonthlyDepartment')->getMonthlyDepartmentLeader($userId);
                if($info){
                    $department_id = $info;
                }else{
                    $userId = $targetFillter->getFilterContent();
                    $isShowAll = false;
                }
            }

            //顶级部门过滤器
            $targetFillter = new Filter(MonthlyTargetAmountDepartmentFilter::class,true);
            if($targetFillter->filterIsEffective()) {
                $department_id = $targetFillter->getFilterContent();
                $isShowAll = true;
            }
        }



        if($isShowAll){
            $userId = 0;
        }

    }
}