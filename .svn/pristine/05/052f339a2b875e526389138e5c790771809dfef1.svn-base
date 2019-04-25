<?php


namespace app\finance\controller;


use app\common\controller\Base;
use app\common\model\Bank;
use app\common\service\Common;
use think\Exception;
use app\finance\service\BankAccount as ServiceBankAccount;
use app\common\cache\Cache;
use app\common\service\UniqueQueuer;
use app\common\model\City;
use app\common\model\Province;
use app\report\model\ReportExportFiles;
use app\index\queue\ExportDownQueue;

/**
 * @title 银行账户管理
 * @module 财务管理
 * @url bank-account
 * @author starzhan <397041849@qq.com>
 */
class BankAccount extends Base
{
    /**
     * @title 新增银行账户
     * @author starzhan <397041849@qq.com>
     */
    public function save()
    {
        $param = $this->request->param();
        $userInfo = Common::getUserInfo();
        try {
            $service = new ServiceBankAccount();
            $result = $service->save($param, $userInfo);
            return json($result, 200);
        } catch (Exception $ex) {
            $arr = [
                'file' => $ex->getFile(),
                'line' => $ex->getLine(),
                'message' => $ex->getMessage()
            ];
            return json($arr, 400);
        }
    }

    /**
     * @title 银行账户列表
     * @apiRelate app\index\controller\User::staffs
     * @apiRelate app\index\controller\Currency::index
     * @author starzhan <397041849@qq.com>
     */
    public function index()
    {
        $param = $this->request->param();
        $page = $param['page'] ?? 1;
        $pageSize = $param['pageSize'] ?? 50;
        $service = new ServiceBankAccount();
        $result = $service->index($page, $pageSize, $param);
        return json($result, 200);
    }

    /**
     * @title 银行账户信息
     * @param $id
     * @return \think\response\Json
     * @author starzhan <397041849@qq.com>
     */
    public function read($id)
    {
        $service = new ServiceBankAccount();
        $result = $service->read($id);
        return json($result, 200);
    }

    /**
     * @title 更新银行记录
     * @param $id
     * @author starzhan <397041849@qq.com>
     */
    public function update($id)
    {
        $param = $this->request->param();
        $userInfo = Common::getUserInfo();
        try {
            $service = new ServiceBankAccount();
            $result = $service->update($id, $param, $userInfo);
            return json($result, 200);
        } catch (Exception $ex) {
            $arr = [
                'file' => $ex->getFile(),
                'line' => $ex->getLine(),
                'message' => $ex->getMessage()
            ];
            return json($arr, 400);
        }
    }

    /**
     * @title 导出csv
     * @method post
     * @url export
     * @author starzhan <397041849@qq.com>
     */
    public function export()
    {
        try {
            $userInfo = Common::getUserInfo();
            $server = new ServiceBankAccount();
            $param = $this->request->param();
            $ids = isset($param['ids']) ? json_decode($param['ids'], true) : [];
            if ($ids) {
                $result = $server->export($param);
                return json($result, 200);
            }
            $count = $server->exportCount($param);
            if (!$count) {
                throw new Exception('导出的记录数为0');
            }
            $param['count'] = $count;
            if ($count > 1000) {
                $cache = Cache::handler();
                $key = 'BankAccount:export:lastExportTime:' . $userInfo['user_id'];
                $lastApplyTime = $cache->get($key);
                if ($lastApplyTime && time() - $lastApplyTime < 5 * 60) {
                    throw new Exception('5分钟内只能请求一次', 400);
                } else {
                    $cache->set($key, time());
                    $cache->expire($key, 3600);
                }
                $fileName = '出纳银行账户管理' . date('Y-m-d_H-i-s') . ".csv";
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
                $param['class'] = '\app\\finance\\service\\BankAccount';
                $param['fun'] = 'export';
                (new UniqueQueuer(ExportDownQueue::class))->push($param);
                return json(['status' => 0, 'message' => '导出数据太多，已加入导出队列，稍后请自行下载'], 200);
            } else {
                $result = $server->export($param);
                return json($result, 200);
            }
        } catch (Exception $ex) {
            return json(['message' => $ex->getMessage(), 'file' => $ex->getFile(), 'line' => $ex->getLine()], 400);
        }
    }

    /**
     * @title 获取银行信息
     * @method get
     * @url bank
     * @author starzhan <397041849@qq.com>
     */
    public function bank()
    {
        $ModelBank = new Bank();
        return json($ModelBank->select(), 200);
    }

    /**
     * @title 城市信息列表
     * @method get
     * @url cities
     * @author starzhan <397041849@qq.com>
     */
    public function city()
    {
        $ModelCity = new City();
        return json($ModelCity->select(), 200);
    }

    /**
     * @title 省份信息列表
     * @method get
     * @url provinces
     * @author starzhan <397041849@qq.com>
     */
    public function province()
    {
        $ModelProvince = new Province();
        return json($ModelProvince->select(), 200);
    }

}