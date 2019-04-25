<?php


namespace app\index\service;

use app\common\model\ServerUserAccountSite as ServerUserAccountSiteModel;

class AutomationService
{

    public function  getCookie($data, $user_id)
    {

        $ServerUserAccountSiteModel = new ServerUserAccountSiteModel();
        $where = [
            'account_id' => $data['id'],
            'relation_module' => 0,
            'site' => $data['site'],
            'is_account'=>1,
        ];
        $cookie = $ServerUserAccountSiteModel->where($where)->order('update_time desc')->value('cookie');
        if ($cookie) {
            return json_decode($cookie, true);
        }

        $where = [
            'account_id' => $data['id'],
            'relation_module' => 9,
            'site' => $data['site'],
        ];
        $ServerUserAccountSiteModel = new ServerUserAccountSiteModel();
        $cookie = $ServerUserAccountSiteModel->where($where)->order('id asc')->value('cookie');
        if ($cookie) {
            return json_decode($cookie, true);
        }

        $where = [
            'account_id' => $data['id'],
            'relation_module' => 0,
            'site' => $data['site'],
            'user_id' => $user_id
        ];
        $ServerUserAccountSiteModel = new ServerUserAccountSiteModel();
        $cookie = $ServerUserAccountSiteModel->where($where)->order('id asc')->value('cookie');
        if ($cookie) {
            return json_decode($cookie, true);
        }
        return [];
    }

    public  function cleanCookies($userId, $account_id, $site){

        $where = [
            'account_id' => $account_id,
            'site' => $site,
            'user_id' => $userId
        ];
        $ServerUserAccountSiteModel = new ServerUserAccountSiteModel();
        $ServerUserAccountSiteModel->where($where)->delete();
        return [];
    }
}