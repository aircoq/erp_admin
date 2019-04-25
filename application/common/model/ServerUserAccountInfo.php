<?php
namespace app\common\model;

use function MongoDB\BSON\fromJSON;
use think\Db;
use think\Exception;
use think\Model;
/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2017/8/22
 * Time: 17:46
 */
class ServerUserAccountInfo extends Model
{

    /**
     * 服务器渠道账号人员关系表
     */
    protected function initialize()
    {
        parent::initialize();
    }

    /**
     * @param $where
     * @return array|bool|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function isHas($where)
    {
        $result = $this->where($where)->find();
        if (empty($result)) {   //不存在
            return false;
        }
        return $result;
    }

    private function getSession($cookie)
    {
        foreach ($cookie as $v){
            if(isset($v['name']) && $v['name'] == 'session-id'){
                return $v['value'];
            }
        }
        return '';
    }

    /**
     *
     * @param $data
     * @return bool|false|int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add($data,$other = [])
    {
        $where = [
            'user_id' => $data['user_id'],
            'account_id' => $data['account_id'],
            'relation_module' => $data['relation_module'],
        ];
        $other['session_id'] = '';
        if(is_array($data['cookie'])){
            $other['session_id'] = $this->getSession($data['cookie']);
            $data['cookie'] = json_encode($data['cookie'],JSON_UNESCAPED_UNICODE);
        }
        if(is_array($data['profile'])){
            $data['profile'] = json_encode($data['profile'],JSON_UNESCAPED_UNICODE);
        }
        $time = time();
        $old = $this->isHas($where);
        Db::startTrans();
        try{
            if($old){
                $saveData['update_time'] = $time;
                $saveData['cookie'] = $data['cookie'];
                $saveData['profile'] = $data['profile'];
                $status = (new ServerUserAccountInfo())
                    ->save($saveData,['id'=>$old['id']]);
            }else{
                $data['update_time'] = $time;
                $data['create_time'] = $time;
                $status = (new ServerUserAccountInfo())
                    ->allowField(true)
                    ->isUpdate(false)
                    ->save($data);
            }
            if(isset($other['site'])){
                if($other['is_first']  !== false){
                    $data['site'] = $other['site'];
                    isset($other['session_id']) && $data['session_id'] = $other['session_id'];
                    isset($other['is_account']) && $data['is_account'] = $other['is_account'];
                    (new ServerUserAccountSite())->add($data);
                }
            }

            Db::commit();
        }catch (\Exception $ex){
            Db::rollback();
            throw new Exception('保存数据');
        }

        return $status;
    }


    public function addRedis($data)
    {
        $save = [];
        foreach ($data as $v){
            $file_name = $v['file_name'];
            $cookie = $v['cookie'] ?? '';
            if(!$file_name || !$cookie){
                continue;
            }
            $str = '.com';
            $name = explode($str, $file_name);
            $where = [];
            if (isset($name[1])) {
                $where = [
                    'account_name' => $name[0] . $str,
                ];
            }
            if (!$where) {
                $str = '.COM';
                $name = explode($str, $file_name);
                if (isset($name[1])) {
                    $where = [
                        'account_name' => $name[0] . $str,
                    ];
                }
            }
            if ($where) {
                $where['channel_id'] = 2;
                $id = (new Account())->where($where)->value('id');
                if (!$id) {
                    continue;
                }
                $site = strtolower(substr($name[1],0, 2));
                $url = $this->getUrl($site);
                foreach ($cookie as &$c){
                    $c['url'] = $url;
                }
                $save[] = [
                    'account_id' => $id,
                    'cookie' => json_encode($cookie),
                    'site' => $site,
                    'relation_module' => 9,
                    'create_time' => time(),
                ];

            }
        }
        if($save){
            return (new ServerUserAccountSite())->saveAll($save);
        }
        return false;


    }

    private function getUrl($site)
    {
        $url = 'https://sellercentral.amazon.co.uk/home';
        $one = ['jp'];
        $two = ['us','ca','mx'];
        $two_3 = ['in'];
        $two_4 = ['au'];
        if(in_array($site,$one)){
            $url = 'https://sellercentral-japan.amazon.com/home';
        }elseif(in_array($site,$two)){
            $url = 'https://sellercentral.amazon.com/home';
        }elseif(in_array($site,$two_3)){
            $url = 'https://sellercentral.amazon.in/home';
        }elseif(in_array($site,$two_4)){
            $url = 'https://sellercentral.amazon.com.au/home';
        }
        return $url;
    }

}