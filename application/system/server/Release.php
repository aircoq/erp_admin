<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-8-14
 * Time: 下午2:12
 */

namespace app\system\server;

use app\common\cache\Cache;
use app\common\exception\JsonErrorException;
use app\common\traits\Role;
use app\index\service\User;
use app\system\model\Release as ReleaseModel;
use app\system\model\ReleaseItem;
use app\system\model\ReleaseUserRead;
use Carbon\Carbon;
use think\Db;
use think\Exception;

class Release
{
    use Role;
    const STATUS_RELEASE = 1;
    const STATUS_AUDITED = 2;
    const STATUS_NEWED = 5;

    /**
     * @var \app\system\cache\Release
     */
    private $cache ;

    public function __construct()
    {
        $this->cache = Cache::moduleStore('release');
    }


    public function getVersion()
    {
        return $this->cache->getVersion();
    }

    public function setVersion($verId)
    {

    }

    public function getReleases($params = [])
    {
        $model =ReleaseModel::with('items');
        $last = param($params, 'last');
        $review = param($params, 'review');
        $model->order('create_time','asc');
        if($last){
            $model->where('release_time', ">=", $last);
        }
        if($review){
            $model->where('status', static::STATUS_RELEASE);
        }
        $releases = $model->select();
        return $releases;
    }

    public function create($post)
    {
        $sameVersion = ReleaseModel::where('version', $post['version'])->find();
        if($sameVersion){
            throw new JsonErrorException("已存在版本号");
        }
        $release = [
            'title' => $post['title'],
            'author' => $post['author'],
            'type' => $post['type'],
            'version' => $post['version'],
            'status' => $post['status'],
            'release_time' => Carbon::parse($post['release_time'])->getTimestamp(),
            'create_time' => time(),
            'update_time' => time()
        ];
        try{
            $model = new ReleaseModel($release);
            $model->save();
            Db::startTrans();
            $items = json_decode($post['items'], true);
            foreach ($items as &$item){
                $releaseItem= new ReleaseItem();
                $releaseItem->desc = $item['desc'];
                $releaseItem->type = $item['type'];
                $releaseItem->at_roles = $item['at_roles'];
                $releaseItem->dev_authors = $item['dev_authors'];
                $releaseItem->release_id = $model->id;
                $releaseItem->save();
            }
            Db::commit();
        }catch (Exception $exception){
            Db::rollback();
            throw new JsonErrorException($exception->getMessage());
        }

    }

    public function remove($id)
    {
        $model = ReleaseModel::where('id', $id)->find();
        if($model){
            if(static::STATUS_AUDITED === $model->status){
                throw new JsonErrorException("已审核的版本，无法移除");
            }
            if(static::STATUS_RELEASE === $model->status){
                throw new JsonErrorException("已发布的版本，无法移除");
            }
            $model->delete();
        }else{
            throw new JsonErrorException("不存在的版本");
        }
    }

    public function getReads($userId = null)
    {
        $userId = $userId ?: User::getCurrent();
        if($readModel = ReleaseUserRead::get($userId)){
            return $readModel->reads;
        }else{
            return [];
        }
    }

    public function read($id)
    {
        $userId = User::getCurrent();
        if($readModel = ReleaseUserRead::get($userId)){
            if(in_array($id, $readModel->reads)){
                throw new JsonErrorException("已读");
            }
            $reads = $readModel->reads;
            array_push($reads, $id);
            $readModel->reads = $reads;
            $readModel->update_time = time();
            $readModel->save();
        }else{
            $readModel = new ReleaseUserRead();
            $readModel->id = $userId;
            $readModel->create_time = time();
            $readModel->update_time = time();
            $readModel->reads = [$id];
            $readModel->save();
        }
    }
}