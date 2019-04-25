<?php
/**
 * Created by PhpStorm.
 * User: rondaful_user
 * Date: 2019/4/1
 * Time: 9:21
 */

namespace app\publish\task;


use app\common\model\Department;
use app\common\model\Goods;
use app\common\service\ChannelAccountConst;
use app\index\service\AbsTasker;
use app\goods\service\GoodsHelp;
use app\index\service\ChannelDistribution;
use app\index\service\DepartmentUserMapService;
use app\common\model\ebay\EbayDailyPublish as DailyPublishModel;


class EbayDailyPublish extends AbsTasker
{
    private $shenzhenDepartmentUsers;//记录部门用户对应关系，格式为[department_id=>[user_id1,user_id2],...]
    private $allDepartmentIds;//记录所有的ebay销售组部门id
    private $wuhanDepartmentIds;//记录武汉分公司的ebay销售部门id
    private $wuhanSellerCount = 0;//记录武汉分公司销售人数
    private $shenzhenSellerCount = 0;//记录深圳公司销售人数


    public function getName()
    {
        return "每日刊登分配任务";
    }

    public function getDesc()
    {
        return "每日刊登分配任务";
    }

    public function getCreator()
    {
        return "wlw2533";
    }

    public function getParamRule()
    {
        return [];
    }

    public function init()
    {
        $this->getDepartments();
        $this->getDepartmentUsers();
    }


    public function execute()
    {
        $this->init();
        //获取要分配的产品信息，格式为[id=>spu,...]
        $goodsIds = (new GoodsHelp())->getAssignGoods(date('Y-m-d',time()-86400),ChannelAccountConst::channel_ebay);

        //获取配置信息,包括每日刊登均值，小组与产品分类关联，不参与分配的职位
        $config = (new ChannelDistribution())->read(ChannelAccountConst::channel_ebay);

        $avgMin = $config['publish_value_min']??0;
        $avgMax = $config['publish_value_max']??0;
        if ($config['category_relation_type'] == 2) {//分类关联部门
            $categoryDepartment = $config['category_department'];
            $cateDpt = [];
            foreach ($categoryDepartment as $cd) {
                $cateDpt[$cd['category_id']] = $cd['department_id'];
            }
        }

        //获取深圳部应分到的数量
        $shenzhenSpuCnt = ceil(count($goodsIds)*$this->shenzhenSellerCount/($this->shenzhenSellerCount+$this->wuhanSellerCount));
        $shenzhenGoodsIds = array_rand($goodsIds,$shenzhenSpuCnt);
        !is_array($shenzhenGoodsIds) && $shenzhenGoodsIds = [$shenzhenGoodsIds];

        //获取商品信息
        $goods = Goods::alias('g')->whereIn('g.id',$shenzhenGoodsIds)
            ->join('category c','c.id=g.category_id','LEFT')->column('c.pid','g.id');
        //以分类分组
        $cateGoods = [];
        foreach ($goods as $gid => $cpid) {
            if (isset($cateGoods[$cpid])) {
                array_push($cateGoods[$cpid],$gid);
                continue;
            }
            $cateGoods[$cpid] = [$gid];
        }
        //每个销售员分配到的数目
        $avg = max($avgMin,$avgMax,ceil($shenzhenSpuCnt/$this->shenzhenSellerCount));

        $this->doAssign($cateGoods,$avg,$cateDpt);
        $wuhanGoodsIds = array_diff(array_values($goodsIds),$shenzhenGoodsIds);
        $data = [];
        foreach ($wuhanGoodsIds as $wuhanGoodsId) {
            $data[] = [
                'goods_id' => $wuhanGoodsId,
                'seller_location' => 1,
            ];
        }
        (new DailyPublishModel())->saveAll($data);

    }

    /**
     * 执行分配
     * @param $cateGoods
     * @param $avg
     * @param $cateDpt
     */
    private function doAssign($cateGoods, $avg, $cateDpt)
    {
        //开始分配
        $userGoods = [];
        $unAssigned = [];
        $dptCnt = [];
        foreach ($cateGoods as $cateId => $cateGood) {//循环产品
            if (isset($cateDpt[$cateId])) {//有绑定
                $dptIds = $cateDpt[$cateId];//绑定的部门id
                $assignOver = 0;
                shuffle($dptIds);
                foreach ($dptIds as $dptId) {//循环分类部门
                    $tmpGgCnt = 0;
                    foreach ($this->shenzhenDepartmentUsers[$dptId] as $sid) {//循环销售员
                        if (count($cateGood) < $avg) {//剩余的不够一个销售员的
                            $userGoods[$sid] = [
                                'seller_id' => $sid,
                                'goods_ids' => array_values($cateGood),
                                'complete' => 0,//分配未完成
                                'department_id' => $dptId,
                            ];
                            $assignOver = 1;
                            $tmpGgCnt += count($cateGood);
                            break;
                        } else {
                            $tmpKeys = array_rand($cateGood,$avg);//随机取
                            !is_array($tmpKeys) && $tmpKeys = [$tmpKeys];
                            $tmpCateGoods = [];
                            foreach ($tmpKeys as $tmpKey) {
                                $tmpCateGoods[] = $cateGood[$tmpKey];
                                unset($cateGood[$tmpKey]);
                            }
                            $userGoods[$sid] = [
                                'user_id' => $sid,
                                'goods_id' => $tmpCateGoods,
                                'complete' => 1,
                                'department_id' => $dptId,
                            ];
                            $tmpGgCnt += $avg;
                            if (!$cateGood) {
                                $assignOver = 1;
                                break;
                            }
                        }
                    }
                    //记录部门分配到的产品数量
                    $dptCnt[$dptId] = $tmpGgCnt;
                    if ($assignOver) {
                        break;
                    }
                }


            }
            if ($cateGood) {//如果还有剩余，记录下来
                $unAssigned = array_merge($unAssigned,array_values($cateGood));
            }
        }
        //绑定分类的分配完毕后，分配未绑定分类的,按部门总数量从少到多分

        asort($dptCnt);
        $assignDptIds = array_keys($dptCnt);
        $shenzhenDepartmentIds = array_diff($this->allDepartmentIds,$this->wuhanDepartmentIds);
        if (count($assignDptIds)<count($shenzhenDepartmentIds)) {//有些部门没有分配到
            $shenzhenDepartmentIds = array_values($shenzhenDepartmentIds);
            $unassignDptIds = array_values(array_diff($shenzhenDepartmentIds,$assignDptIds));
            $assignDptIds = array_merge($unassignDptIds,$assignDptIds);
        }

        $completeFlag = 0;
        foreach ($assignDptIds as $assignDptId) {
            $uIds = $this->shenzhenDepartmentUsers[$assignDptId];
            foreach ($uIds as $uId) {
                if ($userGoods[$uId]['complete'] ?? 0) {
                    continue;
                }
                $tmpCnt = empty($userGoods[$uId]['goods_ids']) ? 0 : count($userGoods[$uId]['goods_ids']);
                $left = count($unAssigned) - ($avg - $tmpCnt);
                $tcg = [];
                if ($left > 0) {//随机取
                    $tks = array_rand($unAssigned, $avg - $tmpCnt);//随机取
                    !is_array($tks) && $tks = [$tks];
                    foreach ($tks as $tk) {
                        $tcg[] = $unAssigned[$tk];
                        unset($unAssigned[$tk]);
                    }
                } else {
                    $tcg = array_values($unAssigned);
                    $completeFlag = 1;
                }
                $userGoods[$uId] = [
                    'user_id' => $uId,
                    'goods_ids' => array_merge($userGoods[$uId]['goods_ids'] ?? [], $tcg),
                    'department_id' => $assignDptId,
                ];
                if ($completeFlag) {
                    break;
                }
            }
            if ($completeFlag) {
                break;
            }
        }

        //分配完毕，组装写入数据库
        $data = [];
        foreach ($userGoods as $userGood) {
            if (empty($userGood['goods_ids'])) {
                continue;
            }
            foreach ($userGood['goods_ids'] as $goods_id) {
                $data[] = [
                    'goods_id' => $goods_id,
                    'seller_id' => $userGood['user_id'],
                    'department_id' => $userGood['department_id'],
                    'expire_time' => strtotime(date('Y-m-d'))+2*86400,
                ];
            }

        }
        (new DailyPublishModel())->allowField(true)->saveAll($data);

    }


    /**
     * 获取销售部门
     * @return array
     */
    private function getDepartments()
    {
        //1.获取ebay所有销售部门，只查到组
        $wh = [
            'channel_id' => 1,
            'job' => 'sales',
            'type' => 1,//部门类别为组
        ];
        $allDptIds = Department::where($wh)->column('id');

        //2.获取武汉分部ebay销售部门
        $wuhanCoId = Department::where('name','武汉分公司')->value('id');
        $subDptIds = [$wuhanCoId];
        unset($wh['type']);

        $wuhanDptIds = [];
        do {
            $wh['pid'] = ['in',$subDptIds];
            $tmpDptIds = Department::where($wh)->column('id');
            if ($tmpDptIds) {
                $wuhanDptIds = $tmpDptIds;
                $subDptIds = $tmpDptIds;
            }
        } while ($tmpDptIds);

        $this->allDepartmentIds = $allDptIds;
        $this->wuhanDepartmentIds = $wuhanDptIds;
    }


    /**
     * 获取部门与销售对应关系
     */
    private function getDepartmentUsers()
    {
        $allDptIds = $this->allDepartmentIds;
        $wuhanDptIds = $this->wuhanDepartmentIds;

        foreach ($allDptIds as $allDptId) {
            $tmpSellers = (new DepartmentUserMapService())->getPublishUserByDepartmentId($allDptId,ChannelAccountConst::channel_ebay);
            if (empty($tmpSellers) || empty($tmpSellers['users'])) {
                continue;
            }
            if (in_array($allDptId,$wuhanDptIds)) {//属于武汉分公司
                $this->wuhanSellerCount += count($tmpSellers['users']);
                continue;
            }
            $this->shenzhenDepartmentUsers[$allDptId] = $tmpSellers['users'];
            $this->shenzhenSellerCount += count($tmpSellers['users']);
        }
    }



}