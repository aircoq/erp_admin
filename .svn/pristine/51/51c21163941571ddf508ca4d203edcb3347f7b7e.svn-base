<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\pm\PmAccount as PmAccountModel;
use app\common\traits\CacheTable;

/**
 * pm账号缓存
 * @author wangwei
 * @date 2019-2-27 11:00:03
 */
class PmAccount extends Cache
{
    const cachePrefix = 'table';
    private $taskPrefix = 'task:pm:';
    private $listingSyncTime = 'listing_sync_time';
    private $listintlistkey = 'listing_list';

    use CacheTable;

    public function __construct()
    {
        $this->model(PmAccountModel::class);
        parent::__construct();
    }

    /**
     * 获取全部账号信息
     * @param int $id
     * @return array|mixed
     */
    public function getAllAccounts()
    {
        return $this->getTableRecord();
    }

    /**
     * 获取账号信息通过id
     * @param int $id
     * @return array|mixed
     */
    public function getAccountById($id)
    {
        return $this->getTableRecord($id);
    }

    /**
     * 获取帐号信息传ID为，此ID的，不传ID，则为全部；
     * @param $id
     * @return array|mixed
     */
    public function getAccount($id = 0)
    {
        return $this->getTableRecord($id);
    }

    /**
     * 删除账号缓存信息
     * @param int $id
     */
    public function delAccount($id = 0)
    {
        $this->delTableRecord($id);
        return true;
    }
}
