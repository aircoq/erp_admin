<?php


namespace app\index\service;


use app\common\model\shopee\ShopeeAccount;
use app\common\service\ChannelAccountConst;
use think\Exception;
use app\common\validate\ShopeeAccount as ValidateShopeeAccount;
use app\common\cache\Cache;

class ShopeeAccountService
{

    const tokenRule = [
        'shop_id' => 'require|unique:ShopeeAccount',
        'partner_id' => 'require',
        'key' => 'require',
    ];

    const  msg = [
        'shop_id.require' => 'shopid不能为空',
        'shop_id.unique' => 'shopid已存在',
        'partner_id.require' => 'partnerid不能为空',
        'key.require' => 'secretKey不能为空'
    ];
    public function index(int $page = 1, int $pageSize = 50, array $param = [])
    {
        $result = ['list' => []];
        $result['page'] = $page;
        $result['page_size'] = $pageSize;
        $result['count'] = $this->getWhere($param)->count();
        if ($result['count'] == 0) {
            return $result;
        }
        $o = $this->getWhere($param);
        $ret = $this->getSort($o, $param)->page($page, $pageSize)->select();

        if ($ret) {
            $result['list'] = $this->indexData($ret);
        }
        return $result;
    }

    private function getSort($o, $param)
    {
        if (isset($param['order_field']) && $param['order_field'] && isset($param['order_val']) && $param['order_val']) {
            $o = $o->order($param['order_field'] . ' ' . $param['order_val']);
        } else {
            $o = $o->order('id desc');
        }
        return $o;
    }

    private function indexData($ret)
    {
        $result = [];
        foreach ($ret as $v) {
            $row = [];
            $row['id'] = $v['id'];
            $row['code'] = $v['code'];
            $row['name'] = $v['name'];
            $row['shop_id'] = $v['shop_id'];
            $row['status'] = $v['status'];
            $row['site'] = $v['site'];
            $row['site_id'] = $v->site_id;
            $row['platform_status'] = $v['platform_status'];
            $row['platform_status_txt'] = $v['platform_status_txt'];
            $row['download_listing'] = $v['download_listing'];
            $row['sync_delivery'] = $v['sync_delivery'];
            $row['download_order'] = $v['download_order'];
            $row['download_return'] = $v['download_return'];
            $row['third_party_delivery'] = $v['third_party_delivery'];
            $row['is_authorization'] = $v['is_authorization'];
            // $row['third_party_delivery_txt'] = $v->is_TH_txt;
            $row['third_party_delivery_txt'] = $v->is_third_txt;
            $result[] = $row;
        }
        return $result;
    }

    /**
     * @title 注释..
     * @param $param
     * @return $this|ShopeeAccount
     * @author starzhan <397041849@qq.com>
     */
    public function getWhere($param)
    {
        $o = new ShopeeAccount();
        if (isset($param['sn_type']) && isset($param['sn_text']) && !empty($param['sn_text'])) {
            $val = trim($param['sn_text']);
            switch ($param['sn_type']) {
                case 'name':
                    $o = $o->where('name', 'like', "%{$val}%");
                    break;
                case 'code':
                    $o = $o->where('code', 'like', "%{$val}%");
                    break;
            }
        }
        if (isset($param['download_type']) && !empty($param['download_value'])) {
            $val = trim($param['download_value']);
            $execMap = [
                0 => '=',
                1 => '>',
                2 => '<'
            ];
            $doExec = $execMap[$param['download_exec']];
            switch ($param['download_type']) {
                case 'download_order':
                    $o = $o->where('download_order', $doExec, $val);
                    break;
                case 'download_listing':
                    $o = $o->where('download_listing', $doExec, $val);
                    break;
                case 'sync_delivery':
                    $o = $o->where('sync_delivery', $doExec, $val);
                    break;
            }
        }
        if (isset($param['status'])) {
            $o = $o->where('status', '=', trim($param['status']));
        }
        if (isset($param['platform_status'])) {
            $o = $o->where('platform_status', '=', trim($param['platform_status']));
        }
        if(isset($param['site'])&&$param['site']){
            $o = $o->where('site', '=', trim($param['site']));
        }

        if (isset($param['is_authorization']) && is_numeric($param['is_authorization'])) {
            $o = $o->where('is_authorization', '=', $param['is_authorization']);
        }
        return $o;
    }

    /**
     * @title 注释..
     * @param $id
     * @return array
     * @throws Exception
     * @author starzhan <397041849@qq.com>
     */
    public function getId($id)
    {
        $data = Cache::store('ShopeeAccount')->getId($id);
        if (isset($data['-1'])) {
            unset($data['-1']);
        }
        return $data;
    }

    private function buildData($data)
    {
        $info = [];
        isset($data['name']) && $info['name'] = $data['name'];
        isset($data['code']) && $info['code'] = $data['code'];
        isset($data['site']) && $info['site'] = $data['site'];
        /** 单独拉出来授权 linpeng 2019-1-17 15：00 */
         isset($data['shop_id']) && $info['shop_id'] = $data['shop_id'];
         isset($data['partner_id']) && $info['partner_id'] = $data['partner_id'];
         isset($data['key']) && $info['key'] = $data['key'];
        isset($data['download_order']) && $info['download_order'] = $data['download_order'];
        isset($data['sync_delivery']) && $info['sync_delivery'] = $data['sync_delivery'];
        isset($data['download_listing']) && $info['download_listing'] = $data['download_listing'];
        isset($data['third_party_delivery']) && $info['third_party_delivery'] = $data['third_party_delivery'];
        isset($data['base_account_id']) && $info['base_account_id'] = $data['base_account_id'];
        //判断授权；
        if (!empty($info['shop_id']) && !empty($info['partner_id']) && !empty($info['key'])) {
            $info['is_authorization'] = 1;
        } else {
            $info['is_authorization'] = 0;
        }
        return $info;
    }

    /**
     * @title 注释..
     * @param $data
     * @param $user_id
     * @return array
     * @throws Exception
     * @author starzhan <397041849@qq.com>
     */
    public function save($data, $user_id)
    {

        if (empty($data['id'])) {
            //必须要去账号基础资料里备案
            \app\index\service\BasicAccountService::isHasCode(ChannelAccountConst::channel_Shopee,$data['code'], $data['site']);
            $infoData = $this->buildData($data);

            $infoData['create_id'] = $user_id;
            $infoData['create_time'] = time();
            $validate = new ValidateShopeeAccount();

            $flag = $validate->check($infoData);
            if ($flag === false) {

                throw new Exception($validate->getError());
            }
            $model = new ShopeeAccount();
            $res = $model->where('code',$infoData['code'])->field('id')->find();
            if (count($res)) {
                return ['message' => '该账号简称已存在','data' => $infoData];
            }
            $model->allowField(true)->isUpdate(false)->save($infoData);
            $id = $model->id;
        } else {
            $model = new ShopeeAccount();
            $aData = $model->where('id', $data['id'])->find();
            if (!$aData) {
                throw new Exception('该记录不存在');
            }
            $updateData = $this->buildData($data);
            $updateData['update_id'] = $user_id;
            $updateData['update_time'] = time();
            $id = $data['id'];
            $aData->save($updateData);
        }
        Cache::store('ShopeeAccount')->clearCache($id);
        return ['message' => '保存成功', 'data' => $this->getId($id)];
    }

    public function saveToken($data, $user_id)
    {
        if (empty($data['id'])) {
            $infoData = $this->buildData($data);

            $infoData['create_id'] = $user_id;
            $infoData['create_time'] = time();
            $validate = new ValidateShopeeAccount();

            $flag = $validate->check($infoData);
            if ($flag === false) {

                throw new Exception($validate->getError());
            }
            $model = new ShopeeAccount();
            $model->allowField(true)->isUpdate(false)->save($infoData);
            $id = $model->id;
        } else {
            $model = new ShopeeAccount();
            $aData = $model->where('id', $data['id'])->find();
            if (!$aData) {
                throw new Exception('该记录不存在');
            }
            $infoData = $this->buildData($data);

            $validate = new ValidateShopeeAccount();
            $validate->rule(self::tokenRule);
            $validate->message(self::msg);

            $flag = $validate->check($infoData);

            if (!$flag) {
                throw new Exception($validate->getError());
            }

            $infoData['update_id'] = $user_id;
            $infoData['update_time'] = time();
            $id = $data['id'];
            $aData->save($infoData);
        }
        Cache::store('ShopeeAccount')->clearCache($id);
        return ['message' => '保存成功', 'data' => $this->getId($id)];
    }


    public function changeStatus($id, $platform_status, $user_id)
    {
        $model = new ShopeeAccount();
        $accountInfo = $model->where('id', $id)->find();
        if (!$accountInfo) {
            throw new Exception('该记录不存在');
        }
        if ($platform_status == 1) {
            if ($accountInfo->status == 0) {
                throw new Exception('平台状态为禁用，无法切换系统状态');
            }
        }
        $accountInfo->platform_status = $platform_status;
        $accountInfo->update_id = $user_id;
        $accountInfo->update_time = time();
        $accountInfo->save();
        Cache::store('ShopeeAccount')->clearCache();
        return ['message' => '修改成功'];
    }

    public function getSite()
    {
        $result = Cache::store('ShopeeAccount')->getSite();
        $ret = [];
        foreach ($result as $v) {
            $ret[] = $v;
        }
        return $ret;
    }

    public function getAccount($site_code = '')
    {
        $filter[] = ['status', '==', 1];
        $res = Cache::filter(Cache::store('ShopeeAccount')->getAllCount(), $filter, 'id,code,name,status,site');
        $new_list['account'] = [];
        foreach ($res as $k => $v){
            $temp['label'] = $v['code'];
            $temp['value'] = intval($v['id']);
            $temp['account_name'] = $v['account_name'] ?? $v['shop_name'] ?? $v['name'] ??'';
            if (!empty($site_code)) {
                if (isset($v['site_id'])) {
                    if (is_array($v['site_id'])) {
                        $siteArray = $v['site_id'];
                    } else if (is_string($v['site_id'])) {
                        $siteArray = json_decode($v['site_id'], true);
                    } else {
                        $siteArray = [];
                    }
                    if (is_array($siteArray)) {
                        if (in_array($site_code, $siteArray)) {
                            array_push($new_list['account'], $temp);
                        }
                    }
                }

                if (isset($v['site'])) {
                    if (strstr($v['site'], $site_code)) {
                        array_push($new_list['account'], $temp);
                    }
                }
            } else {
                array_push($new_list['account'], $temp);
            }
        }
        return $new_list;
    }
}