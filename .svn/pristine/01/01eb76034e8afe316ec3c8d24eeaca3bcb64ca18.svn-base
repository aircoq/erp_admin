<?php
/**
 * Created by PhpStorm.
 * User: zhangdongdong
 * Date: 2018/6/9
 * Time: 16:01
 */

namespace app\publish\service;


use app\common\cache\Cache;
use app\common\model\amazon\AmazonAccount;
use app\common\model\amazon\AmazonGoodsTag;
use app\common\model\amazon\AmazonPublishProduct;
use app\common\model\amazon\AmazonPublishTask as AmazonPublishTaskModel;
use app\common\model\ChannelProportion;
use app\common\model\ChannelUserAccountMap;
use app\common\model\GoodsLang;
use app\common\model\User;
use app\common\service\ChannelAccountConst;
use app\goods\service\CategoryHelp;
use app\goods\service\GoodsHelp;
use app\index\service\ChannelDistribution;
use app\index\service\ChannelService;
use app\index\service\Department;
use app\index\service\DepartmentUserMapService;
use think\Exception;
use \app\common\traits\User as UserTraits;

class AmazonPublishTaskService
{

    use UserTraits;

    protected $lang = 'zh';

    protected $model = null;

    protected $tagModel = null;

    protected $accountModel = null;

    public function __construct()
    {
        $this->model = new AmazonPublishTaskModel();
        $this->tagModel = new AmazonGoodsTag();
        $this->accountModel = new AmazonAccount();
    }

    /**
     * 设置刊登语言
     * @param $lang
     */
    public function setLang($lang)
    {
        $this->lang = $lang;
    }


    /**
     * 获取刊登语言
     * @return string
     */
    public function getLang()
    {
        return $this->lang ?? 'zh';
    }


    public function lists($params)
    {
        $page = $params['page'] ?? 1;
        $pageSize = $params['pageSize'] ?? 20;
        $where = $this->condition($params, $join);
        $count = $this->model->alias('t')->join($join)->where($where)->field('id')->count();
        $lists = $this->model->alias('t')
            ->join($join)
            ->where($where)
            ->field('g.spu,g.thumb image_url,g.category_id,g.name zn_name,g.pre_sale,t.id,t.goods_id,t.account_id,t.product_id,t.seller_id,t.type,t.profit,t.task_time,t.status,t.create_time')
            ->order('g.pre_sale desc,t.id desc')
            ->page($page, $pageSize)
            ->select();

        //图片的base_url;
        $baseUrl = Cache::store('configParams')->getConfig('innerPicUrl')['value'] . DS;
        $returnData = [
            'count' => $count,
            'page' => $page,
            'pageSize' => $pageSize,
            'data' => []
        ];

        $newList = [];
        $account_ids = [0];
        $seller_ids = [0];
        $product_ids = [0];
        $goodsIds = [0];
        foreach ($lists as $val) {
            $tmp = $val->toArray();
            $account_ids[] = $tmp['account_id'];
            $seller_ids[] = $val['seller_id'];
            $product_ids[] = $val['product_id'];
            $goodsIds[] = $val['goods_id'];
            $tmp['base_url'] = $baseUrl;
            $newList[] = $tmp;
        }
        //$publishStatusArr = ['待上传', '上传中', '已上传', '上传失败'];
        //$statusArr = ['未开始', '进行中', '已完成'];
        $accounts = $this->accountModel->where(['id' => ['in', $account_ids]])->column('code', 'id');
        $users = User::where(['id' => ['in', $seller_ids]])->column('realname', 'id');

        $publishs = AmazonPublishProduct::where(['id' => ['in', $product_ids]])->column('publish_status', 'id');
        $langs = GoodsLang::where(['goods_id' => ['in', $goodsIds], 'lang_id' => 2])->column('title', 'goods_id');

        $tags = $this->getTagsByGoodsIds($goodsIds);

        $toUpdateIds = [];
        $today = strtotime(date('Y-m-d'));
        $help = new CategoryHelp();
        foreach ($newList as &$val) {
            $val['en_name'] = $langs[$val['goods_id']] ?? '';
            $val['code'] = $accounts[$val['account_id']] ?? '-';
            $val['seller_name'] = $users[$val['seller_id']] ?? '-';

            if ($val['task_time'] < $today && $val['status'] == 0) {
                $val['status'] = 3;
                $val['status_text'] = '已延期';
            }

            if ($val['type'] == 1) {
                $val['tag'] = $tags[$val['goods_id']] ?? '-';
            } else {
                $val['tag'] = '外部随机分配';
            }

            $val['profit'] = $val['profit']. '%';
            //刊登状态；
            $val['publish_status'] = '';
            if (isset($publishs[$val['product_id']])) {
                $val['publish_status'] = $publishs[$val['product_id']];
                if ($val['publish_status'] == 2 && $val['status'] != 2) {
                    $val['publish_status'] = 1;
                    $toUpdateIds[] = $val['id'];
                }
            }

            $val['category_name'] = $help->getCategoryNameById($val['category_id'], ($this->lang == 'zh' ? 1 : 2));
        }
        unset($val);
        $returnData['data'] = $newList;
        if (!empty($toUpdateIds)) {
            $this->model->update(['status' => 2], ['id' => ['in', $toUpdateIds]]);
        }
        return $returnData;
    }


    public function getTagsByGoodsIds(array $goodsIds) : array
    {
        if (empty($goodsIds)) {
            return [];
        }
        $tags = AmazonGoodsTag::alias('gt')
            ->join(['department' => 'd'], 'd.id=gt.tag_id')
            ->where(['goods_id' => ['in', $goodsIds]])
            ->column('d.name', 'gt.goods_id');
        return $tags;
    }


    public function condition($params, &$join)
    {
        $where = [];

        $join[] = ['goods g', 'g.id=t.goods_id'];
        if (!empty($params['account_id'])) {
            $where['t.account_id'] = $params['account_id'];
        }
        $where['t.status'] = ['in', [0, 1]];
        if (isset($params['status']) && in_array($params['status'], ['0', '1', '2', '3', '4'])) {
            switch ($params['status']) {
                case 0:
                    $where['t.status'] = 0;
                    $where['t.task_time'] = ['>=', strtotime(date('Y-m-d'))];
                    break;
                case 1:
                    $where['t.status'] = 1;
                    break;
                case 2:
                    $where['t.status'] = 2;
                    break;
                case 3:
                    $where['t.status'] = 0;
                    $where['t.task_time'] = ['<', strtotime(date('Y-m-d'))];
                    break;
                case 4:
                    $where['t.status'] = 4;
                    break;
                default:
                    $where['t.id'] = 0;
            }
        }
        //SPU搜索，条数不能超过50条;
        if (!empty($params['spus'])) {
            $spuArr = explode(',', trim($params['spus']));
            if (count($spuArr) > 500) {
                if ($this->getLang() == 'zh') {
                    throw new Exception('批量搜索最多只支持500条数据');
                } else {
                    throw new Exception('Batch search supports up to 500 pieces of data');
                }
            }
            $spuTmp = [];
            foreach ($spuArr as $spu) {
                $spu = trim($spu);
                if ($spu) {
                    $spuTmp[$spu] = 1;
                }
            }
            $spuArr = array_keys($spuTmp);
            if ($spuArr) {
                $where['g.spu'] = ['in', $spuArr];
            }
        }
        if (!empty($params['department_id'])) {
            //如果有这个数据，那么它就是tag_id，直接连表搜tag_id就好了；
            $sellerIds = $this->getUserByDepartmentId($params['department_id']);
            if ($sellerIds) {
                $where['t.seller_id'] = ['in', $sellerIds];
            } else {
                $where['t.id'] = 0;
            }
        }
        if (isset($params['tag_id']) && $params['tag_id'] != '') {
            if ($params['tag_id'] == '0') {
                $where['t.type'] = 2;
            } else {
                $join[] = ['amazon_goods_tag gt', 'gt.goods_id=t.goods_id'];
                $where['gt.tag_id'] = $params['tag_id'];
                $where['t.type'] = 1;
            }
        }

        $startTime = empty($params['start_time'])? 0 : strtotime($params['start_time']);
        $endTime = empty($params['end_time'])? 0 : (strtotime($params['end_time']) + 86399);
        if ($startTime == 0 && $endTime > 0) {
            $where['t.task_time'] = ['<', $endTime];
        }
        if ($startTime > 0 && $endTime == 0) {
            $where['t.task_time'] = ['>=', $startTime];
        }
        if ($startTime > 0 && $endTime > 0) {
            $where['t.task_time'] = ['between', [$startTime, $endTime]];
        }
        if (!empty($params['seller_id'])) {
            $where['t.seller_id'] = $params['seller_id'];
        }

        return $where;
    }


    public function getTags()
    {
        $departmentServ = new Department();
        $data = $departmentServ->getDepartmentByChannelId(ChannelAccountConst::channel_amazon);
        $list = [];
        foreach ($data as $val) {
            $list[] = [
                'value' => $val['id'],
                'label' => $val['name']
            ];
        }
        return $list;
    }


    /**
     * 标记为取消
     * @param $idArr
     * @return bool
     */
    public function cancelTask($id) {
        $task = $this->model->where(['id' => $id])->field('id,status')->find();
        if (empty($task)) {
            if ($this->getLang() == 'zh') {
                throw new Exception('任务不存在');
            } else {
                throw new Exception('Data does not exist');
            }
        }
        if ($task['status'] != 0) {
            if ($this->getLang() == 'zh') {
                throw new Exception('只能取消未开始和已延期的任务');
            } else {
                throw new Exception('Only unstarted tasks can be cancelled');
            }
        }
        $this->model->update(['status' => 4, 'update_time' => time()], ['id' => $id]);
        return true;
    }


    /**
     * 批量标记为取消
     * @param $idArr
     * @return bool
     */
    public function batchCancel($idArr) {
        $count = $this->model->where(['id' => ['in', $idArr], 'status' => 0])->field('id')->count();
        if ($count < count($idArr)) {
            if ($this->getLang() == 'zh') {
                throw new Exception('只能取消未开始和已延期的任务');
            } else {
                throw new Exception('Only unstarted tasks can be cancelled');
            }
        }
        $this->model->update(['status' => 4, 'update_time' => time()], ['id' => ['in', $idArr]]);
        return true;
    }


    /**
     * 根据商品ID和帐号ID拿取任务
     * @param $goodsId
     * @param $accountId
     * @param string $field
     * @return array
     */
    public function taskDetail($goodsId, $accountId, $field = '*') : array
    {
        $detail = $this->model->where(['goods_id' => $goodsId, 'account_id' => $accountId])->field($field)->find();
        if (empty($detail)) {
            return [];
        }
        return $detail->toArray();
    }


    /* --------------------------------- 以下分配任务 --------------------------------- */
    /**
     * 主执行方法；
     * @return bool
     */
    public function assign($days, $return = false)
    {
        //商品总数；
        $goodsHelp = new GoodsHelp();
        $days = (array)$days;
        $goods = [];
        foreach ($days as $day) {
            $tmp = $goodsHelp->getAssignGoods($day, ChannelAccountConst::channel_amazon);
            //$tmp = $this->getLineData([
            //        'name' => 'app\goods\service\GoodsHelp',
            //        'method' => 'getAssignGoods',
            //        'result' => '2',
            //        'p1' => $day
            //        'p2' => 2
            //]);
            if (is_array($tmp)) {
                foreach ($tmp as $k=>$v) {
                    $goods[$k] = $v;
                }
            }
        }

        if (empty($goods)) {
            return false;
        }

        //商品ID
        $goodsIds = array_keys($goods);
        $goodsTotal = count($goodsIds);

        //部门；
        $departmentServ = new ChannelService();
        $departments = $departmentServ->getProportionByChannelId(ChannelAccountConst::channel_amazon);
        //$departments = $this->getLineData([
        //        'name' => 'app\index\service\ChannelService',
        //        'method' => 'getProportionByChannelId',
        //        'result' => '2',
        //        'p1' => ChannelAccountConst::channel_amazon
        //]);
        if (empty($departments)) {
            return false;
        }

        $departments = array_combine(array_column($departments, 'department_id'), $departments);
        $departmentCount = count($departments);

        $num = 0;
        $start = 0;
        $remainder = 0;
        $departmentGoods = [];
        foreach ($departments as &$val) {
            $num++;
            if ($num < $departmentCount) {

                $len = $goodsTotal * $val['product_proportion'] / 100;
                $round = round($len);
                ////补偿值
                $remainder = $remainder + $len - $round;
                if ($remainder >= 1) {
                    $round = $round + 1;
                    $remainder = $remainder - 1;
                } else if ($remainder <= -1 && $round >= 1) {
                    $round = $round - 1;
                    $remainder = $remainder + 1;
                }

                if ($round == 0 || $start >= $goodsTotal) {
                    $tmpGoods = [];
                } else {
                    $tmpGoods = array_slice($goodsIds, $start, $round);
                }
                $start += $round;
            } else {
                if ($start > $goodsTotal) {
                    $tmpGoods = [];
                } else {
                    $tmpGoods = array_slice($goodsIds, $start);
                }
            }

            $departmentGoods[$val['department_id']] = $tmpGoods;
        }

        //给每组分配的商品；
        $departmentGroupGoods = [];
        //抽取商品
        $departmentRondomGoods = [];

        $dumServ = new DepartmentUserMapService();
        foreach ($departmentGoods as $department_id=>$department_goods_ids) {
            //每个部门底下的分组；
            $departmentGroups = $dumServ->getPublishUserByDepartmentId($department_id);
            //$departmentGroups = $this->getLineData([
            //    'name' => 'app\index\service\DepartmentUserMapService',
            //    'method' => 'getPublishUserByDepartmentId',
            //    'result' => '2',
            //    'p1' => $department_id
            //    'p2' => 2
            //]);
            //当前部门下的分组ID对应的组员；
            $departmentGroupUsers = $this->getDepartmentGroupUsers($departmentGroups);
            $departments[$department_id]['groups'] = $departmentGroupUsers;

            //每个分组ID对应的商品ID；
            $groupGoods = $this->groupAssignGoods($departmentGroupUsers, $department_goods_ids);
            $departmentGroupGoods[$department_id] = $groupGoods;

            //商品group
            $rondom_number = $departments[$department_id]['product_count'];
            if ($rondom_number > 0) {
                $userRondomGoods = $this->userRondomGoods($departmentGroupUsers, $departmentGoods, $department_id, $rondom_number);
                foreach ($userRondomGoods as $uid=>$rondomGood) {
                    $departmentRondomGoods[$uid] = $rondomGood;
                }
            }
        }

        //去除帐号 和 站点
        $this->getRemoveAccountAndSite();
        $result = $this->saveTasks($departmentGroupGoods, $departmentRondomGoods, $departments, $goods, $return);
        return $result;
    }


    /**
     * 返回每个分组ID对应的用户；
     * @param $departmentGroups
     * @return array
     */
    public function getDepartmentGroupUsers($departmentGroups)
    {
        if (empty($departmentGroups['child'])) {
            return [];
        }
        $groupUsers = [];
        foreach ($departmentGroups['child'] as $val) {
            if ($val['type'] == 1) {
                $groupUsers[$val['id']] = $val['users'];
            } else {
                if (!empty($val['child'])) {
                    $subGroupUsers = $this->getDepartmentGroupUsers($val);
                    if (!empty($subGroupUsers)) {
                        foreach ($subGroupUsers as $k=>$v) {
                            $groupUsers[$k] = $v;
                        }
                    }
                }
            }
        }
        return $groupUsers;
    }


    /**
     * 给小组分配商品
     * @param $departmentGroups
     * @param $goodsIds
     * @return array
     */
    public function groupAssignGoods($departmentGroups, $goodsIds)
    {
        //如果下面分组为空，则没必要计算了；直接返回空；
        $goodsTotal = count($goodsIds);
        if (!$departmentGroups) {
            return [];
        }

        $groupGoods = [];
        $total = 0;
        $newDepartmentGroups = [];
        foreach ($departmentGroups as $groupId=>$val) {
            $groupGoods[$groupId] = [];
            $tmpTotal = count($val);
            if ($tmpTotal > 0) {
                $total += $tmpTotal;
                $newDepartmentGroups[$groupId] = $val;
            }
        }

        $num = 0;
        $start = 0;
        $remainder = 0;
        $groupTotal = count($newDepartmentGroups);
        foreach ($newDepartmentGroups as $groupId=>$val) {
            $num++;
            if ($num < $groupTotal) {
                $sellerTotal = count($val);

                //实际数量；
                $len = $goodsTotal * count($val) / $total;
                $round = round($len);
                //补偿值
                $remainder = $remainder + $len - $round;
                if ($remainder >= 1) {
                    $round = $round + 1;
                    $remainder = $remainder - 1;
                } else if ($remainder <= -1 && $round >= 1) {
                    $round = $round - 1;
                    $remainder = $remainder + 1;
                }

                $tmpGoods = [];
                if ($sellerTotal > 0) {
                    $tmpGoods = array_slice($goodsIds, $start, $round);
                }
                $start = $start + $round;
            } else {
                $tmpGoods = array_slice($goodsIds, $start);
            }
            $groupGoods[$groupId] = $tmpGoods;
        }
        return $groupGoods;
    }


    /**
     * 返回用户ID为键值为随机商品ID；
     * @param $departmentGroupUsers
     * @param $goodsIds
     * @param $department_goods_ids
     * @param int $rondom_number
     * @return array
     */
    public function userRondomGoods($departmentGroupUsers, $departmentGoods, $department_id, $rondom_number = 3)
    {
        if (empty($departmentGroupUsers)) {
            return [];
        }
        //找出部门所有销售；
        $users = [];
        foreach ($departmentGroupUsers as $val) {
            $users = array_merge($users, $val);
        }
        $users = array_filter(array_unique($users));

        //找出非本部门的商用品
        $diffGoods = [];
        $goodsTags = [];
        foreach ($departmentGoods as $key=>$val) {
            if ($department_id != $key) {
                $diffGoods = array_merge($diffGoods, $val);
                foreach ($val as $goodsId) {
                    $goodsTags[$goodsId] = $key;
                }
            }
        }

        $rondoms = [];
        if (!empty($diffGoods)) {
            foreach ($users as $uid) {
                //随机KEY；
                $rondom_keys = (array)array_rand($diffGoods, $rondom_number);
                foreach ($rondom_keys as $key) {
                    $tmp = $diffGoods[$key];
                    $rondoms[$uid][] = [
                        'goods_id' => $tmp,
                        'tag_id' => $goodsTags[$tmp]
                    ];
                }
            }
        }
        return $rondoms;
    }


    public $removeSites = [];
    public $removeAccountIds = [];

    public function getRemoveAccountAndSite()
    {
        $distributionServ = new ChannelDistribution();
        $result = $distributionServ->read(ChannelAccountConst::channel_amazon);
        if (!empty($result['ban_site']) && is_array($result['ban_site'])) {
            $this->removeSites = array_values($result['ban_site']);
        }
        if (!empty($result['ban_account_id']) && is_array($result['ban_account_id'])) {
            $this->removeAccountIds = array_values($result['ban_account_id']);
        }
    }


    public function saveTasks($departmentGroupGoods, $departmentRondomGoods, $departments, $goods, $return = false)
    {
        $task_time = strtotime(date('Y-m-d'));
        $returnData = [];
        foreach ($departments as $tag_id=>$department) {
            foreach ($department['groups'] as $group_id=>$users) {
                if (empty($users)) {
                    continue;
                }

                $time = time();
                $goodsIds = empty($departmentGroupGoods[$tag_id][$group_id]) ? [] : $departmentGroupGoods[$tag_id][$group_id];
                if (!$return) {
                    foreach ($goodsIds as $goodsId) {
                        $id = $this->tagModel->where(['goods_id' => $goodsId])->value('goods_id');
                        $goodsData = ['goods_id' => $goodsId, 'tag_id' => $tag_id, 'update_time' => $time];
                        if ($id) {
                            $this->tagModel->update($goodsData, ['goods_id' => $goodsId]);
                        } else {
                            $goodsData['create_time'] = $time;
                            $this->tagModel->insert($goodsData);
                        }
                    }
                }

                foreach ($users as $uid) {
                    $accounts = $this->getSellerAccountIds($uid);
                    //$accounts = $this->getLineData([
                    //    'name' => 'app\publish\service\AmazonPublishTaskService',
                    //    'method' => 'getSellerAccountIds',
                    //    'result' => '2',
                    //    'p1' => $uid
                    //]);
                    if (empty($accounts)) {
                        continue;
                    }

                    //添加当前uid的分配商品；
                    foreach ($goodsIds as $goods_id) {
                        foreach ($accounts as $account) {
                            //多次声明，需要初始化；
                            $data = [];
                            $data['goods_id'] = $goods_id;
                            $data['account_id'] = $account['id'];
                            $data['seller_id'] = $uid;

                            $data['type'] = 1;
                            $data['task_time'] = $task_time;
                            $id = $this->model->where($data)->value('id');

                            $data['spu'] = $goods[$goods_id] ?? '';
                            $data['profit'] = $department['profit_in'];
                            $data['update_time'] = $time;

                            if ($return) {
                                $returnData[] = $data;
                            } else {
                                //存在则更新，不存在则保存;
                                if ($id) {
                                    $this->model->update($data, ['id' => $id]);
                                } else {
                                    $data['create_time'] = $time;
                                    $this->model->insert($data);
                                }
                            }
                        }
                    }

                    $userRondomGoods = $departmentRondomGoods[$uid] ?? [];

                    //下面按站点排重
                    $siteAccounts = [];
                    foreach ($accounts as $val) {
                        $siteAccounts[$val['site']][] = $val['id'];
                    }

                    $accountIds = [];
                    foreach ($siteAccounts as $site=>$ids) {
                        $count = count($ids);
                        if ($count == 1) {
                            $accountIds[] = $ids[0];
                        } else {
                            $accountIds[] = $ids[mt_rand(0, $count - 1)];
                        }
                    }

                    //添加当前uid的分配商品；
                    foreach ($userRondomGoods as $val) {
                        foreach ($accountIds as $accountId) {
                            //多次声明，需要初始化；
                            $data = [];
                            $data['goods_id'] = $val['goods_id'];
                            $data['account_id'] = $accountId;
                            $data['seller_id'] = $uid;

                            $data['type'] = 2;
                            $data['task_time'] = $task_time;
                            $id = $this->model->where($data)->value('id');

                            $data['spu'] = $goods[$val['goods_id']] ?? '';
                            $data['profit'] = $department['profit_out'];
                            $data['update_time'] = $time;

                            if ($return) {
                                $returnData[] = $data;
                            } else {
                                //存在则更新，不存在则保存;
                                if ($id) {
                                    $this->model->update($data, ['id' => $id]);
                                } else {
                                    $data['create_time'] = $time;
                                    $this->model->insert($data);
                                }
                            }

                        }
                    }
                }
            }
        }

        if (!$return) {
            return true;
        }
        return $returnData;
    }


    public function getSellerAccountIds($seller_id)
    {
        $umapModel = new ChannelUserAccountMap();
        $account_ids = $umapModel->where([
            'channel_id' => ChannelAccountConst::channel_amazon,
            'seller_id' => $seller_id,
        ])->column('account_id');

        $newAccountIds = [];
        foreach ($account_ids as $id) {
            if (!in_array($id, $this->removeAccountIds)) {
                $newAccountIds[] = $id;
            }
        }
        //没有帐号返回空数组；
        if (!$newAccountIds) {
            return [];
        }

        $where = [];
        $where['id'] = ['in', $newAccountIds];
        $where['status'] = 1;
        $where['is_invalid'] = 1;

        if (!empty($this->removeSites)) {
            $where['site'] = ['NOT IN', $this->removeSites];
        }

        $accounts = $this->accountModel->where($where)->field('id,site')->select();
        return $accounts;
    }


    public function getLineData($post)
    {
        $url = 'http://www.zrzsoft.com:8081/ebay-message/server';

        $post = http_build_query($post);
        $extra['header'] = [
            'Authorization' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOjEsImV4cCI6MTU1MDk3MTk2NCwiYXVkIjoiIiwibmJmIjoxNTUwODg1NTY0LCJpYXQiOjE1NTA4ODU1NjQsImp0aSI6IjVjNzBhMmJjMDg1NmI4LjE2NzY4OTI2IiwidXNlcl9pZCI6MTU4NSwicmVhbG5hbWUiOiJcdTVmMjBcdTUxYWNcdTUxYWMiLCJ1c2VybmFtZSI6IjE3NzI3NDUzMDU5In0.1d02150682ee3098e0451aa2fc2f4ca16ab29d409262d336cdd2cb26b08b7b24'
        ];
        $data = $this->httpReader($url, 'POST', $post, $extra);
        return json_decode($data, true);
    }
    /* --------------------------------- 以上分配任务 --------------------------------- */

    /**
     * HTTP读取
     * @param string $url 目标URL
     * @param string $method 请求方式
     * @param array|string $bodyData 请求BODY正文
     * @param array $responseHeader 传变量获取请求回应头
     * @param int $code 传变量获取请求回应状态码
     * @param string $protocol 传变量获取请求回应协议文本
     * @param string $statusText 传变量获取请求回应状态文本
     * @param array $extra 扩展参数,可传以下值,不传则使用默认值
     * header array 头
     * host string 主机名
     * port int 端口号
     * timeout int 超时(秒)
     * proxyType int 代理类型; 0 HTTP, 4 SOCKS4, 5 SOCKS5, 6 SOCK4A, 7 SOCKS5_HOSTNAME
     * proxyAdd string 代理地址
     * proxyPort int 代理端口
     * proxyUser string 代理用户
     * proxyPass string 代理密码
     * caFile string 服务器端验证证书文件名
     * sslCertType string 安全连接证书类型
     * sslCert string 安全连接证书文件名
     * sslKeyType string 安全连接证书密匙类型
     * sslKey string 安全连接证书密匙文件名
     * @return string|array 请求结果;成功返回请求内容;失败返回错误信息数组
     * error string 失败原因简单描述
     * debugInfo array 调试信息
     */
    public function httpReader($url, $method = 'GET', $bodyData = [], $extra = [], &$responseHeader = null, &$code = 0, &$protocol = '', &$statusText = '')
    {
        $ci = curl_init();

        if (isset($extra['timeout'])) {
            curl_setopt($ci, CURLOPT_TIMEOUT, $extra['timeout']);
        }
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ci, CURLOPT_HEADER, true);
        curl_setopt($ci, CURLOPT_AUTOREFERER, true);
        curl_setopt($ci, CURLOPT_FOLLOWLOCATION, true);

        if (isset($extra['userpwd'])) {
            curl_setopt($ci, CURLOPT_USERPWD, $extra['userpwd']);
        }

        if (isset($extra['proxyType'])) {
            curl_setopt($ci, CURLOPT_PROXYTYPE, $extra['proxyType']);

            if (isset($extra['proxyAdd'])) {
                curl_setopt($ci, CURLOPT_PROXY, $extra['proxyAdd']);
            }

            if (isset($extra['proxyPort'])) {
                curl_setopt($ci, CURLOPT_PROXYPORT, $extra['proxyPort']);
            }

            if (isset($extra['proxyUser'])) {
                curl_setopt($ci, CURLOPT_PROXYUSERNAME, $extra['proxyUser']);
            }

            if (isset($extra['proxyPass'])) {
                curl_setopt($ci, CURLOPT_PROXYPASSWORD, $extra['proxyPass']);
            }
        }

        if (isset($extra['caFile'])) {
            curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, 2); //SSL证书认证
            curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, true); //严格认证
            curl_setopt($ci, CURLOPT_CAINFO, $extra['caFile']); //证书
        } else {
            curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, false);
        }

        if (isset($extra['sslCertType']) && isset($extra['sslCert'])) {
            curl_setopt($ci, CURLOPT_SSLCERTTYPE, $extra['sslCertType']);
            curl_setopt($ci, CURLOPT_SSLCERT, $extra['sslCert']);
        }

        if (isset($extra['sslKeyType']) && isset($extra['sslKey'])) {
            curl_setopt($ci, CURLOPT_SSLKEYTYPE, $extra['sslKeyType']);
            curl_setopt($ci, CURLOPT_SSLKEY, $extra['sslKey']);
        }

        $method = strtoupper($method);
        switch ($method) {
            case 'GET':
                curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'GET');
                if (!empty($bodyData)) {
                    if (is_array($bodyData)) {
                        $url .= (stristr($url, '?') === false ? '?' : '&') . http_build_query($bodyData);
                    } else {
                        curl_setopt($ci, CURLOPT_POSTFIELDS, $bodyData);
                    }
                }
                break;
            case 'POST':
                curl_setopt($ci, CURLOPT_POST, true);
                if (!empty ($bodyData)) {
                    curl_setopt($ci, CURLOPT_POSTFIELDS, $bodyData);
                }
                break;
            case 'PUT':
                //                 curl_setopt ( $ci, CURLOPT_PUT, true );
                curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'PUT');
                if (!empty ($bodyData)) {
                    curl_setopt($ci, CURLOPT_POSTFIELDS, $bodyData);
                }
                break;
            case 'DELETE':
                curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
            case 'HEAD':
                curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'HEAD');
                break;
            default:
                throw new \Exception(json_encode(['error' => '未定义的HTTP方式']));
                return ['error' => '未定义的HTTP方式'];
        }

        if (!isset($extra['header']) || !isset($extra['header']['Host'])) {
            $urldata = parse_url($url);
            $extra['header']['Host'] = $urldata['host'];
            unset($urldata);
        }

        $header_array = array();
        foreach ($extra['header'] as $k => $v) {
            $header_array[] = $k . ': ' . $v;
        }

        curl_setopt($ci, CURLOPT_HTTPHEADER, $header_array);
        curl_setopt($ci, CURLINFO_HEADER_OUT, true);

        curl_setopt($ci, CURLOPT_URL, $url);

        $response = curl_exec($ci);

        if (false === $response) {
            $http_info = curl_getinfo($ci);
            throw new \Exception(json_encode(['error' => curl_error($ci), 'debugInfo' => $http_info]));
            return ['error' => curl_error($ci), 'debugInfo' => $http_info];
        }

        $responseHeader = [];
        $headerSize = curl_getinfo($ci, CURLINFO_HEADER_SIZE);
        $headerData = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        $responseHeaderList = explode("\r\n", $headerData);

        if (!empty($responseHeaderList)) {
            foreach ($responseHeaderList as $v) {
                if (false !== strpos($v, ':')) {
                    list($key, $value) = explode(':', $v, 2);
                    $responseHeader[$key] = ltrim($value);
                } else if (preg_match('/(.+?)\s(\d+)\s(.*)/', $v, $matches) > 0) {
                    $protocol = $matches[1];
                    $code = $matches[2];
                    $statusText = $matches[3];
                }
            }
        }

        curl_close($ci);
        return $body;
    }

}