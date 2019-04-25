<?php


namespace app\index\service;

use app\common\model\channel\ChannelDistributionSetting as ModelChannelDistribution;
use app\common\model\amazon\AmazonAccount;
use app\common\cache\Cache;
use app\common\model\ebay\EbaySite;
use app\order\service\OrderService;
use app\common\service\ChannelAccountConst;
use app\goods\service\GoodsImport;
use app\common\service\Excel;
use think\Exception;
use app\common\traits\ConfigCommon;

class ChannelDistribution
{
    public function getStatus()
    {
        $statusArr = ModelChannelDistribution::STATUS_TXT;
        $result = [];
        foreach ($statusArr as $k => $statusInfo) {
            $row = [
                'value' => $k,
                'label' => $statusInfo,
            ];
            $result[] = $row;
        }
        return $result;
    }

    public function getFirstCategories()
    {
        $result = [];
        $category_list = Cache::store('category')->getCategoryTree();
        foreach ($category_list['child_ids'] as $id) {
            $row = [];
            $row['value'] = $id;
            $row['label'] = $category_list[$id]['title'];
            $result[] = $row;
        }
        return $result;
    }

    /**
     * @title 获取站点
     * @param $channel_id
     * @return array
     * @author starzhan <397041849@qq.com>
     */
    public function getSites($channel_id)
    {
        $channelInfo = (new ChannelService())->getInfoById($channel_id);
        if (!$channelInfo) {
            return [];
        }
        $result = [];
        switch ($channel_id) {
            case 2:
                $siteArray = Cache::store('account')->amazonSite();
                foreach ($siteArray as $siteInfo) {
                    $row = [];
                    $row['value'] = $siteInfo['site'];
                    $row['label'] = $siteInfo['site'];
                    $result[] = $row;
                }
                break;
            case 1:
                $tmp = Cache::store('ebaySite')->getAllSites();
                foreach ($tmp as $v) {
                    $info = json_decode($v, true);
                    $row['label'] = $info['abbreviation'];
                    $row['value'] = $info['siteid'];
                    $result[] = $row;
                }
                break;
            default:
                $result = [];
                break;

        }
        return $result;
    }

    public function getAccounts($channelId, $param = [])
    {
        $account = [];
        $result = [];
        switch ($channelId) {
            case ChannelAccountConst::channel_ebay:
                $tem = Cache::store('EbayAccount')->getAllAccounts();
                foreach ($tem as $v) {
                    if (isset($param['site']) && $param['site'] !== '') {
                        $in_array_site = json_decode($param['site'], true);
                        $v_site_id = json_decode($v['site_id'], true);
                        if (!array_intersect($v_site_id, $in_array_site)) {
                            continue;
                        }
                    }
                    if($v['is_invalid']!=1){
                        continue;
                    }
                    if($v['is_enable']!=0){
                        continue;
                    }
                    $account[] = $v;
                }
                break;
            case ChannelAccountConst::channel_amazon:
                $tem = Cache::store('AmazonAccount')->getTableRecord();
                foreach ($tem as $v) {
                    if (isset($param['site']) && $param['site'] !== '') {
                        $in_array_site = json_decode($param['site'], true);
                        if (!in_array($v['site'], $in_array_site)) {
                            continue;
                        }
                    }
                    if($v['is_invalid']!=1){
                        continue;
                    }
                    if($v['status']!=1){
                        continue;
                    }
                    $account[] = $v;
                }
                break;
            case  ChannelAccountConst::channel_aliExpress:
                $tem = Cache::store('AliexpressAccount')->getAccounts();
                foreach ($tem as $v) {
                    if($v['is_invalid']!=1){
                        continue;
                    }
                    if($v['aliexpress_enabled']!=1){
                        continue;
                    }
                    $account[] = $v;
                }
                break;
            case ChannelAccountConst::channel_wish:
                $tem = Cache::store('WishAccount')->getAccounts();
                foreach ($tem as $v) {
                    $account[] = $v;
                }
                break;

        }
        foreach ($account as $accountInfo) {
            $row = [];
            $row['value'] = $accountInfo['id'];
            $row['label'] = $accountInfo['code'];
            $result[] = $row;
        }
        return $result;
    }

    /**
     * @title 注释..
     * @param $channelId
     * @author starzhan <397041849@qq.com>
     */
    public function getDepartments($channelId)
    {
        $department = new Department();
        $departmentTree = $department->getDepartmentTreeByChannelId($channelId);
        return $departmentTree;
    }

    /**
     * @title 获取职位
     * @author starzhan <397041849@qq.com>
     */
    public function getPositions()
    {
        $job = new JobService();
        $result = [];
        $aJob = $job->selectByType();
        foreach ($aJob as $jobInfo) {
            $row = [];
            $row['value'] = $jobInfo['id'];
            $row['label'] = $jobInfo['name'];
            $result[] = $row;
        }
        return $result;
    }

    /**
     * @title 注释..
     * @param $id
     * @param $param
     * @param $userInfo
     * @author starzhan <397041849@qq.com>
     */
    public function update($id, $param, $userInfo)
    {
        unset($param['id']);
        $param['channel_id'] = $id;
        $ModelChannelDistribution = new ModelChannelDistribution();
        $old = $ModelChannelDistribution->where('channel_id', $id)->find();
        $infoData = $this->checkParamAndBuildData($param);
        if ($old) {
            $infoData['updater_id'] = $userInfo['user_id'];
            $infoData['update_time'] = time();
            $old->allowField(true)->isUpdate(true)->save($infoData);
        } else {
            $infoData['creator_id'] = $userInfo['user_id'];
            $infoData['create_time'] = time();
            $ModelChannelDistribution = new ModelChannelDistribution();
            $ModelChannelDistribution
                ->allowField(true)
                ->isUpdate(false)
                ->save($infoData);
        }
        return ['message' => '保存成功'];
    }

    public function checkParamAndBuildData($param)
    {
        $result = [];
        if (empty($param['item'])) {
            throw new Exception('勾选的项目不能为空');
        }
        if (empty($param['channel_id'])) {

            throw new Exception('渠道id不能为空');
        }
        $result['channel_id'] = $param['channel_id'];
        $result['item'] = json_decode($param['item'], true);
        $result['product_status'] = [];
        $result['ban_category'] = [];
        $result['ban_site'] = [];
        $result['ban_account_id'] = [];
        $result['coverage_rate_min'] = 0.00;
        $result['category_relation_type'] = 0;
        $result['category_department'] = [];
        $result['category_account'] = [];
        if (in_array(1, $result['item'])) {
            if (!empty($param['product_status'])) {
                $result['product_status'] = json_decode($param['product_status'], true);
            }

        }
        if (in_array(2, $result['item'])) {
            if (!empty($param['ban_category'])) {
                $result['ban_category'] = json_decode($param['ban_category'], true);
            }
        }

        if (in_array(3, $result['item'])) {
            if (!empty($param['ban_site'])) {
                $result['ban_site'] = json_decode($param['ban_site'], true);
            }
            if (!empty($param['ban_account_id'])) {
                $result['ban_account_id'] = json_decode($param['ban_account_id'], true);
            }
        }

        if (in_array(4, $result['item'])) {
            if (!empty($param['coverage_rate_min'])) {
                $result['coverage_rate_min'] = floatval($param['coverage_rate_min']);
            }
        }
        if (in_array(5, $result['item'])) {
            if (empty($param['category_relation_type'])) {
                throw new Exception('分类关联类型不能为空');
            }
            $result['category_relation_type'] = $param['category_relation_type'];
            switch ($result['category_relation_type']) {
                case 1:
                    if (!empty($param['category_account'])) {
                        $result['category_account'] = json_decode($param['category_account'], true);
                    }
                    break;
                case 2:
                    if (!empty($param['category_department'])) {
                        $result['category_department'] = json_decode($param['category_department'], true);
                    }
                    break;
            }
        }
        $result['publish_value'] = [];
        if (in_array(6, $result['item'])) {
            if (!empty($param['publish_value_min'])) {
                $result['publish_value'][] = $param['publish_value_min'];
            }
            if (!empty($param['publish_value_est'])) {
                $result['publish_value'][] = $param['publish_value_est'];
            }
        }

        $result['allow_position'] = [];
        if (in_array(7, $result['item'])) {
            if (!empty($param['allow_position'])) {
                $result['allow_position'] = json_decode($param['allow_position'], true);
            }
        }
        $result['proportion_type'] = 0;
        if (in_array(8, $result['item'])) {
            if (!empty($param['proportion_type'])) {
                $result['proportion_type'] = $param['proportion_type'];
            }
        }
        return $result;

    }

    public function read($id)
    {
        $ModelChannelDistribution = new ModelChannelDistribution();
        $old = $ModelChannelDistribution->where('channel_id', $id)->find();
        if (!$old) {
            throw  new Exception('当前信息为空');
        }
        return $this->row($old);

    }

    public function row($row)
    {
        $result = [];
        $v = $row->toArray();
        isset($v['channel_id']) && $result['channel_id'] = $row['channel_id'];
        isset($v['item']) && $result['item'] = $row['item'];
        isset($v['product_status']) && $result['product_status'] = $row['product_status'];
        isset($v['ban_category']) && $result['ban_category'] = $row['ban_category'];
        isset($v['category_relation_type']) && $result['category_relation_type'] = $row['category_relation_type'];
        isset($v['ban_site']) && $result['ban_site'] = $row['ban_site'];
        isset($v['ban_account_id']) && $result['ban_account_id'] = $row['ban_account_id'];
        isset($v['coverage_rate_min']) && $result['coverage_rate_min'] = floatval($row['coverage_rate_min']);
        isset($v['category_account']) && $result['category_account'] = $row['category_account'];
        isset($v['category_department']) && $result['category_department'] = $row['category_department'];
        isset($v['publish_value']) && $result['publish_value_min'] = $row['publish_value_min'];
        isset($v['publish_value']) && $result['publish_value_est'] = $row['publish_value_est'];
        isset($v['allow_position']) && $result['allow_position'] = $row['allow_position'];
        isset($v['proportion_type']) && $result['proportion_type'] = $row['proportion_type'];
        return $result;
    }


    public function import($channelId, $param)
    {
        $filename = 'upload/' . uniqid() . '.' . $param['extension'];
        GoodsImport::saveFile($filename, $param);
        try {
            $data = Excel::readExcel($filename);
            @unlink($filename);
            $this->checkHeader($data);
            $codes = [];
            foreach ($data as $v) {
                $code = trim($v['账号']);
                $code = preg_replace(["/^(\s|\&nbsp\;|　|\xc2\xa0)/", "/(\s|\&nbsp\;|　|\xc2\xa0)$/"], "", $code);
                $codes[] = $code;
            }
            $result = [];
            switch ($channelId) {
                case 1:

                    break;
                case 2:
                    if ($codes) {
                        $AmazonAccount = new AmazonAccount();
                        $result = $AmazonAccount->field('id')->where('code', 'in', $codes)->column('id');
                    }

                    break;
            }
            return $result;
        } catch (Exception $ex) {
            @unlink($filename);
            throw new Exception($ex->getMessage());
        }
    }

    protected function checkHeader($result)
    {
        if (!$result) {
            throw new Exception("未收到该文件的数据");
        }
        $headers = ["账号"];
        $row = reset($result);
        $aRowFiles = array_keys($row);
        $aDiffRowField = array_diff($headers, $aRowFiles);
        if (!empty($aDiffRowField)) {
            throw new Exception("缺少列名[" . implode(';', $aDiffRowField) . "]");
        }
    }

}