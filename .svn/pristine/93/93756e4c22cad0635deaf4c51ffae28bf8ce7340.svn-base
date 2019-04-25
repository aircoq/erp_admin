<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-8-2
 * Time: 上午11:38
 */
namespace app\progress\service;
use app\common\exception\JsonErrorException;
use app\common\model\Progress;
use app\common\service\Common;
use app\index\service\User;
use erp\AbsServer;
use think\Db;
use think\exception\PDOException;

class ProgressService extends AbsServer
{
    protected $model = Progress::class;
    public static function permision($request){
        $user = Common::getUserInfo($request);
        $user_id= $user? $user['user_id'] : 0;
        $userInfo = (new User())->info($user_id);
        $roleList = isset($userInfo['roleList'])?$userInfo['roleList']:[];
        foreach ($roleList as $role){
            //角色id写死了，软件工程师（IT部）也能对所有数据编辑和删除
            if($role['id'] == 1 || $role['id'] == 211){
                return true;
            }
        }
        return false;
    }
    private function createWhere($params){
        $where=[];
        if(isset($params['leading_id']) && is_numeric($params['leading_id'])){
            $where['leading_id']=$params['leading_id'];
        }
        if(isset($params['module']) && is_numeric($params['module'])){
            $where['module']=$params['module'];
        }
        if(isset($params['status']) && is_numeric($params['status'])){
            $where['status']=$params['status'];
        }
        if(isset($params['type']) && is_numeric($params['type'])){
            $where['type']=$params['type'];
        }
        if(isset($params['priority']) && is_numeric($params['priority'])){
            $where['priority']=$params['priority'];
        }

        if(isset($params['raiser_id']) && is_numeric($params['raiser_id'])){
            $where['raiser_id']=$params['raiser_id'];
        }

        if(isset($params['sn_type']) && isset($params['sn_text']) && $params['sn_type'] && $params['sn_text']){
            $where[$params['sn_type']]=$params['sn_text'];
        }

        if(isset($params['time_type']) && $params['time_type']=='listing_time'){
            if(isset($params['date_begin']) && isset($params['date_end'])){
                if($params['date_begin'] == $params['date_end']){
                    $params['date_begin'] = $params['date_begin'].'00:00:00';
                    $params['date_end'] = $params['date_end'].'23:59:59';
                    $where['listing_time'] = ['between time', [strtotime($params['date_begin']), strtotime($params['date_end'])]];
                }
            }elseif (isset($params['date_begin']) && $params['date_begin']){
                $params['date_begin'] = $params['date_begin'].'00:00:00';
                $where['listing_time']=['>=',$params['date_begin']];
            }elseif (isset($params['date_end']) && $params['date_end']){
                $params['date_end'] = $params['date_end'].'23:59:59';
                $where['listing_time']=['<=',$params['date_end']];
            }
        }

        if(isset($params['time_type']) && $params['time_type']=='raise_time'){
            if(isset($params['date_begin']) && isset($params['date_end'])){
                if($params['date_begin'] == $params['date_end']){
                    $params['date_begin'] = $params['date_begin'].'00:00:00';
                    $params['date_end'] = $params['date_end'].'23:59:59';
                    $where['raise_time'] = ['between time', [strtotime($params['date_begin']), strtotime($params['date_end'])]];
                }
            }elseif (isset($params['date_begin']) && $params['date_begin']){
                $params['date_begin'] = $params['date_begin'].'00:00:00';
                $where['raise_time']=['>=',$params['date_begin']];
            }elseif (isset($params['date_end']) && $params['date_end']){
                $params['date_end'] = $params['date_end'].'23:59:59';
                $where['raise_time']=['<=',$params['date_end']];
            }
        }

        return $where;
    }
    public  function lists($params,$page=1,$pageSize=30){
        $where=$this->createWhere($params);
        if (isset($params['order_by']) && $params['order_by']) {
            $order = $params['order_by'];
        }else{
            $order = ' create_time ';
        }

        if (isset($params['order']) && $params['order']) {
            $sort = $params['order'];
        } else {
            $sort = 'DESC';
        }


        $user = Common::getUserInfo(request());
        $user_id = $user?$user->user_id:0;
        $total =  $this->model->where($where)->count();
        $items = $this->model->where($where)->order($order,$sort)->page($page,$pageSize)->select();
        foreach ($items as &$item){
            $item->raiser = $this->model->username($item->raiser_id);
            $item->leading = $this->model->username($item->leading_id);
            $item->developer = $this->model->username($item->developer_id);
            $item->tester = $this->model->username($item->test_id);
            $item->creater = $this->model->username($item->create_id);
            $item->fronter = $this->model->username($item->fronter_id);
            if($item->create_id == $user_id || self::permision(request())){
                $item->can_delete = 1;
            }else{
                $item->can_delete = 0;
            }
        }
        return ['data'=>$items,'total'=>$total];
    }

    public  function add($params){
        Db::startTrans();
        try{
            $params['raise_time']=strtotime($params['raise_time']);
            $params['plan_listing_time']=strtotime($params['plan_listing_time']);
            $params['listing_time']=strtotime($params['listing_time']);

            $params['plan_complete_time']=strtotime($params['plan_complete_time']);
            $params['real_complete_time']=strtotime($params['real_complete_time']);

            $params['plan_developer_time']=strtotime($params['plan_developer_time']);
            $params['real_developer_time']=strtotime($params['real_developer_time']);

            $params['plan_test_time']=strtotime($params['plan_test_time']);
            $params['real_test_time']=strtotime($params['real_test_time']);

            if(isset($params['raiser_id']) && empty($params['raiser_id'])){
                $user = Common::getUserInfo(request());
                $user_id = $user?$user->user_id:0;
                $params['raiser_id']=$user_id;
            }

            $model=new  Progress();
            if(isset($params['id']) && $params['id']){
                $params['update_time']=time();
                $model->allowField(true)->save($params,['id'=>$params['id']]);
                $message="更新成功";
            }else{
                $params['create_time']=time();
                $params['update_time']=time();
                $model->allowField(true)->save($params);
                $message="添加成功";
            }

            Db::commit();
            return ['message'=>$message];
        }catch (PDOException $exp){
            Db::rollback();
            throw new JsonErrorException($exp->getMessage());
        }
    }
    public function update($ids,$status){
        Db::startTrans();
        $model = new Progress();
        try{
            $this->model->whereIn('id',$ids)->setField('status',$status);
            Db::commit();
            return ['message'=>'更新成功'];
        }catch (PDOException $exp){
            Db::rollback();
            throw new JsonErrorException($exp->getMessage());
        }
    }
    public function delete($ids){
        Db::startTrans();

        try{
            Progress::destroy($ids);
            Db::commit();
            return ['message'=>'删除成功'];
        }catch (PDOException $exp){
            Db::rollback();
            throw new JsonErrorException($exp->getMessage());
        }
    }

    public function principal(){
        $user_list = Db::table('department_user_map')->alias('d')->where(['d.department_id'=>232,'u.on_job'=>1,'u.status'=>1])->field('d.user_id,u.realname')
            ->join('user u','u.id=d.user_id','LEFT')->select();

        $all =[
            'user_id'=>'',
            'realname'=>'全部'
        ];

        array_unshift($user_list,$all);
        return $user_list;
    }

    public function module()
    {
        $menu_list = Db::table('menu')->where(['pid'=>0,'type'=>0])->field('id,title')->select();
        $all =[
            'id'=>'',
            'title'=>'全部'
        ];
        $other =[
            'id'=>'9999',
            'title'=>'其它'
        ];

        array_unshift($menu_list,$all);
        array_push($menu_list,$other);

        return $menu_list;
    }
}