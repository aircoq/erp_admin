<?php


namespace app\finance\service;

use app\common\model\BankAccount as ModelBankAccount;
use app\finance\validate\BankAccount as ValidateBankAccount;
use think\Exception;
use app\common\service\Common;
use app\index\service\DownloadFileService;
use PDO;
use think\db\Query;
use phpzip\PHPZip;
use app\report\model\ReportExportFiles;

class BankAccount
{

    public function save($param, $userInfo)
    {
        try {
            $param['create_time'] = time();
            $param['creator_id'] = $userInfo['user_id'];
            $ValidateBankAccount = new ValidateBankAccount();
            $flag = $ValidateBankAccount->scene('insert')->check($param);
            $param['initial_money'] = $param['money'];
            if ($flag === false) {
                throw new Exception($ValidateBankAccount->getError());
            }
            try {
                $ModelBankAccount = new ModelBankAccount();
                unset($param['id']);
                $ModelBankAccount->allowField(true)->isUpdate(false)->save($param);
                return ['message' => '添加成功'];
            } catch (Exception $e) {
                throw $e;
            }
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    public function update($id, $param, $userInfo)
    {
        $ModelBankAccount = new ModelBankAccount();
        $old = $ModelBankAccount->where('id', $id)->find();
        if (!$old) {
            throw new Exception('当前帐号不存在');
        }
        $param['update_time'] = time();
        $param['updater_id'] = $userInfo['user_id'];
        if (isset($param['status'])) {
            if ($old['status'] != $param['status']) {
                $param['enable_time'] = 0;
                $param['forbidden_time'] = 0;
                if ($param['status'] == ModelBankAccount::STATUS_FORBIDDEN) {
                    $param['forbidden_time'] = time();
                } else if ($param['status'] == ModelBankAccount::STATUS_ENABLE) {
                    $param['enable_time'] = time();
                }
            }
        }
        $ValidateBankAccount = new ValidateBankAccount();
        $flag = $ValidateBankAccount->scene('update')->check($param);
        if ($flag === false) {
            throw new Exception($ValidateBankAccount->getError());
        }
        unset($param['id']);
        $old->allowField(true)->save($param);
        return ['message' => '保存成功'];
    }

    public function read($id)
    {
        $ModelBankAccount = new ModelBankAccount();
        $row = $ModelBankAccount
            ->field('id,account_name,bank_id,bank_code,money,currency_code,province_id,city_id,deposit_bank,bank_key,mobile,cashier_id,enable_time,forbidden_time,status')
            ->where('id', $id)
            ->find();
        if (!$row) {
            throw new Exception('当前帐号不存在');
        }
        $result = $this->row($row);
        return $result;
    }

    public function getWhere($param)
    {
        $ModelBankAccount = new ModelBankAccount();
        if (isset($param['status']) && $param['status'] !== '') {
            $ModelBankAccount = $ModelBankAccount->where('a.status', intval($param['status']));
        }
        if (isset($param['account_name']) && $param['account_name']) {
            $ModelBankAccount = $ModelBankAccount->where('a.account_name', 'like', "{$param['account_name']}%");
        }
        if (isset($param['cashier_id']) && $param['cashier_id']) {
            $ModelBankAccount = $ModelBankAccount->where('a.cashier_id', intval($param['cashier_id']));
        }
        if (isset($param['bank_codes']) && $param['bank_codes']) {
            $aBankCodes = json_decode($param['bank_codes'], true);
            $ModelBankAccount = $ModelBankAccount->where('a.bank_code', 'in', $aBankCodes);
        }
        if (isset($param['currency_code']) && $param['currency_code']) {
            $ModelBankAccount = $ModelBankAccount->where('a.currency_code', '=', $param['currency_code']);
        }
        if (isset($param['bank_id']) && $param['bank_id']) {
            $ModelBankAccount = $ModelBankAccount->where('a.bank_id', '=', $param['bank_id']);
        }
        return $ModelBankAccount;
    }

    private function getOrder()
    {
        return 'id desc';
    }

    public function index($page, $pageSize, $param)
    {
        $result = ['data' => []];
        $result['page'] = $page;
        $result['pageSize'] = $pageSize;
        $result['count'] = $this->getWhere($param)->alias('a')->count();
        if ($result['count'] == 0) {
            return $result;
        }
        $o = $this->getWhere($param);
        $sort = $this->getOrder();
        $ret = $o->page($page, $pageSize)
            ->field("a.id,a.account_name,a.bank_code,a.currency_code,a.money,a.mobile,a.bank_id,a.deposit_bank,a.cashier_id,a.status")
            ->alias('a')
            ->order($sort)->select();
        if ($ret) {
            $result['data'] = $this->lists($ret);
        }
        return $result;
    }

    public function lists($ret)
    {
        $result = [];
        foreach ($ret as $v) {
            $row = $this->row($v);
            $result[] = $row;
        }
        return $result;
    }

    public function row($v)
    {
        $result = [];
        $result['id'] = $v['id'];
        isset($v['account_name']) && $result['account_name'] = $v['account_name'];
        isset($v['bank_code']) && $result['bank_code'] = $v['bank_code'];
        isset($v['currency_code']) && $result['currency_code'] = $v['currency_code'];
        isset($v['money']) && $result['money'] = $v['money'];
        isset($v['money']) && $result['local_money'] = $v['local_money'];
        isset($v['mobile']) && $result['mobile'] = $v['mobile'];
        isset($v['bank_name']) && $result['bank_name'] = $v['bank_name'];
        isset($v['bank_key']) && $result['bank_key'] = $v['bank_key'];
        isset($v['deposit_bank']) && $result['deposit_bank'] = $v['deposit_bank'];
        if (isset($v['cashier_id'])) {
            $result['cashier_id'] = $v['cashier_id'];
            $result['cashier_txt'] = $v['cashier_txt'];
        }
        if (isset($v['bank_id'])) {
            $result['bank_name'] = $v->bank->bank_name;
            $result['bank_id'] = $v['bank_id'];
        }
        isset($v['status']) && $result['status'] = $v['status'];
        isset($v['status']) && $result['status_txt'] = $v['status_txt'];
        if (isset($v['city_id'])) {
            $result['city_name'] = $v->city->city_name??'';
            $result['city_id'] = $v['city_id'];
        }
        if (isset($v['province_id'])) {
            $result['province_name'] = $v->province->province_name??'';
            $result['province_id'] = $v['city_id'];
        }
        if (isset($v['enable_time'])) {
            $result['enable_time'] = $v->enable_time;
            $result['enable_time_txt'] = $v['enable_time_txt'];
        }
        if (isset($v['forbidden_time'])) {
            $result['forbidden_time'] = $v->forbidden_time;
            $result['forbidden_time_txt'] = $v['forbidden_time_txt'];
        }
        return $result;
    }
    private function exportLists($ret){
        $result = [];
        foreach ($ret as $v) {
            $row = $this->exportRow($v);
            $result[] = $row;
        }
        return $result;
    }

    private function exportRow($v)
    {
        $model = new ModelBankAccount();
        $result = [];
        $result['account_name'] = $v['account_name'];
        $result['bank_code'] = $v['bank_code']." ";
        $result['currency_code'] = $v['currency_code'];
        $result['money'] = $v['money']." ";
        $result['local_money'] = $model->getLocalMoneyAttr(null,$v);
        $result['mobile'] = $v['mobile']." ";
        $result['bank_name'] = $v['bank_name'];
        $result['province_name'] = $v['province_name'];
        $result['city_name'] = $v['city_name'];
        $result['bank_key'] = $v['bank_key'];
        $result['deposit_bank'] = $v['deposit_bank'];
        $result['status_txt'] = $model->getStatusTxtAttr(null,$v);
        $result['cashier_txt'] = $model->getCashierTxtAttr(null,$v);
        $result['enable_time_txt'] = $model->getEnableTimeTxtAttr(null,$v);
        $result['forbidden_time_txt'] = $model->getForbiddenTimeTxtAttr(null,$v);
        return $result;
    }

    public function exportField()
    {
        $header = [
            ['title' => '账户名称', 'key' => 'account_name', 'width' => 10],
            ['title' => '银行卡号', 'key' => 'bank_code', 'width' => 35],
            ['title' => '币种', 'key' => 'currency_code', 'width' => 15],
            ['title' => '余额', 'key' => 'money', 'width' => 25],
            ['title' => '余额(CNY)', 'key' => 'money', 'width' => 20],
            ['title' => '银行预留手机号', 'key' => 'mobile', 'width' => 20],
            ['title' => '银行名称', 'key' => 'bank_name', 'width' => 25],
            ['title' => '开户省份', 'key' => 'province_name', 'width' => 20],
            ['title' => '开户城市', 'key' => 'city_name', 'width' => 40],
            ['title' => '联行号/swift code', 'key' => 'bank_key', 'width' => 20],
            ['title' => '开户行', 'key' => 'deposit_bank', 'width' => 20],
            ['title' => '状态', 'key' => 'status_txt', 'width' => 20],
            ['title' => '出纳员', 'key' => 'cashier_txt', 'width' => 20],
            ['title' => '启用时间', 'key' => 'enable_time_txt', 'width' => 20],
            ['title' => '停用时间', 'key' => 'forbidden_time_txt', 'width' => 20],
        ];
        return $header;

    }

    public function export($param = [])
    {
        set_time_limit(0);
        ini_set('memory_limit', '128M');
        $header = $this->exportField();
        $userInfo = Common::getUserInfo();
        $downFileName = isset($param['file_name']) ? $param['file_name'] : '出纳银行账户管理(' . date('Y-m-d_H-i-s') . ')';
        $downFileName .= "({$userInfo['realname']}).csv";
        $ids = isset($param['ids']) ? json_decode($param['ids'], true) : [];
        $field = 'a.id,a.account_name,a.bank_code,a.currency_code,a.mobile,a.money,a.bank_id,a.province_id,a.city_id
                ,a.bank_key,a.deposit_bank,a.status,a.cashier_id,a.enable_time,a.forbidden_time,c.city_name,p.province_name,b.bank_name';
        if ($ids) {
            $ModelBankAccount = new ModelBankAccount();
            $result = $ModelBankAccount->alias('a')->where('a.id', 'in', $ids)
                ->join('city c','c.id=a.city_id','left')
                ->join('province p','p.id=a.province_id','left')
                ->join('bank b','b.id=a.bank_id','left')
                ->field($field)
                ->select();
            if ($result) {
                $result = $this->exportLists($result);
                $file = [
                    'name' => '出纳银行账户管理',
                    'path' => 'finance'
                ];
                $ExcelExport = new DownloadFileService();
                return $ExcelExport->export($result, $header, $file);
            }
            throw new Exception('所选id导出数据为空!');
        }
        if ($param['count'] < 1000) {
            $ret = $this->getWhere($param)
                ->alias('a')
                ->join('city c','c.id=a.city_id','left')
                ->join('province p','p.id=a.province_id','left')
                ->join('bank b','b.id=a.bank_id','left')
                ->field($field)
                ->select();
            if ($ret) {
                $result = $this->exportLists($ret);
                $file = [
                    'name' => '出纳银行账户管理',
                    'path' => 'finance'
                ];
                $ExcelExport = new DownloadFileService();
                return $ExcelExport->export($result, $header, $file);
            }
            throw new Exception('导出数据为空!');
        }
        $sql = $this->getWhere($param)->alias('a')
            ->order('a.id', 'desc')
            ->join('city c','c.id=a.city_id','left')
            ->join('province p','p.id=a.province_id','left')
            ->join('bank b','b.id=a.bank_id','left')
            ->field($field)
            ->select(false);
        $page = 1;
        $page_size = 10000;
        $page_total = ceil($param['count'] / $page_size);
        $fileName = str_replace('.csv', '', $downFileName);
        $file = ROOT_PATH . 'public' . DS . 'download' . DS . 'finance';
        $filePath = $file . DS . $downFileName;
        $aHeader = [];
        foreach ($header as $v) {
            $aHeader[] = $v['title'];
        }
        $fp = fopen($filePath, 'w+');
        fwrite($fp, "\xEF\xBB\xBF");
        fputcsv($fp, $aHeader);
        fclose($fp);
        do {
            $offset = ($page - 1) * $page_size;
            $dosql = $sql . " limit  {$offset},{$page_size}";
            $Q = new Query();
            $a = $Q->query($dosql, [], true, true);
            $fp = fopen($filePath, 'a');
            while ($v = $a->fetch(PDO::FETCH_ASSOC)) {
                $row = $this->exportRow($v);
                $rowContent = [];
                foreach ($header as $h) {
                    $field = $h['key'];
                    $value = isset($row[$field]) ? $row[$field] : '';
                    $rowContent[] = $value;
                }
                fputcsv($fp, $rowContent);
            }
            unset($a);
            unset($Q);
            fclose($fp);
            $page++;
        } while ($page <= $page_total);
        $zipPath = $file . DS . $fileName . ".zip";
        $PHPZip = new PHPZip();
        $zipData = [
            [
                'name' => $fileName,
                'path' => $filePath
            ]
        ];
        $PHPZip->saveZip($zipData, $zipPath);
        @unlink($filePath);
        $applyRecord = ReportExportFiles::get($param['apply_id']);
        $applyRecord['exported_time'] = time();
        $applyRecord['download_url'] = '/download/finance/' . $fileName . ".zip";
        $applyRecord['status'] = 1;
        $applyRecord->isUpdate()->save();
    }

    public function exportCount($param)
    {
        return $this->getWhere($param)->count();
    }
}