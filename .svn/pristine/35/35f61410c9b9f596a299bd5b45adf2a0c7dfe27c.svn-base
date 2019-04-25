<?php
/**
 * Created by PhpStorm.
 * User: rondaful_user
 * Date: 2019/3/22
 * Time: 17:40
 */

namespace app\publish\service;


use app\common\model\Category;
use app\common\model\ebay\EbayDailyPublish;
use app\common\model\Goods;
use app\common\model\GoodsLang;
use app\common\model\GoodsSku;
use app\common\model\User;
use app\common\service\Common;
use app\common\service\CommonQueuer;
use app\common\service\ImportExport;
use app\index\service\Department;
use app\publish\queue\EbayListingExportQueue;
use app\report\model\ReportExportFiles;
use think\Exception;

class EbayDailyPublishService
{
    private $userId;
    public function __construct()
    {
        $userInfo = Common::getUserInfo();
        $this->userId = $userInfo['user_id']??0;
    }

    /**
     * 获取每日刊登列表
     * @param $params
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function lists($params)
    {
        $wh = $this->packWh($params);

        $page = $params['page']??1;
        $pageSize = $params['pageSize']??50;
        $count = $this->totalCount($wh);
        if (!$count) {
            return [
                'data' => [],
                'count' => 0,
                'page' => $page,
                'pageSize' => $pageSize,
            ];
        }
        $data = $this->getData($wh,$page,$pageSize);

        return [
            'data' => $data,
            'count' => $count,
            'page' => $page,
            'pageSize' => $pageSize,
        ];
    }

    /**
     * 打包搜索条件
     * @param $params
     * @return array
     * @throws \think\Exception
     */
    public function packWh($params)
    {
        $wh = [];
        foreach ($params as $pn => $pv) {
            if ($pv == '') {
                continue;
            }
            switch ($pn) {
                case 'spu':
                    $spu = explode(',',$pv);
                    $goodsIds = Goods::whereIn('spu',$spu)->column('id');
                    if ($goodsIds) {
                        $wh['dp.goods_id'] = ['in',$goodsIds];
                    } else {
                        $wh['dp.goods_id'] = 0;
                    }
                    break;
                case 'sku':
                    $sku = explode(',',$pv);
                    $goodsIds = GoodsSku::whereIn('sku',$sku)->column('goods_id');
                    if ($goodsIds) {
                        $wh['dp.goods_id'] = ['in',$goodsIds];
                    } else {
                        $wh['dp.goods_id'] = 0;
                    }
                    break;
                case 'categoryId':
                    $categoryIds = (new Category())->getAllChilds($pv);
                    if ($categoryIds) {
                        $wh['g.category_id'] = ['in',$categoryIds];
                    } else {
                        $wh['g.category_id'] = 0;
                    }
                    break;
                case 'status':
                    if ($pv == 3) {//已延期
                        $wh['dp.status'] = ['<>',2];//排除已完成的
                        $wh['dp.expire_time'] = ['<',time()];//过期时间小于当前时间
                    } elseif ($pv == 2) {//已完成
                        $wh['dp.status'] = 2;
                    } else {//未开始或进行中
                        $wh['dp.status'] = $pv;
                        $wh['dp.expire_time'] = ['>',time()];//确保未过期
                    }
                    break;
                case 'sellerId'://销售员
                    $wh['dp.seller_id'] = $pv;
                    break;
                case 'departmentId'://部门id
                    $sellerIds = (new Department())->getDepartmentUser($pv,'sales');
                    $sids = [];
                    foreach ($sellerIds as $sellerId) {
                        $sids = array_merge($sids,$sellerId);
                    }
                    $wh['dp.seller_id'] = ['in',$sids];
                    break;
            }
        }
        if (!empty($params['startDate']) || !empty($params['endDate'])) {
            $startTime = $params['startDate'] ? strtotime($params['startDate'])+86400 : 0;
            $endTime = $params['endDate'] ? strtotime($params[''])+86400 : time()+86400;
            $wh['dp.expire_time'] = ['between',[$startTime,$endTime]];
        }
        return $wh;
    }

    /**
     * 统计总数
     * @param $wh
     * @return int|string
     */
    public function totalCount($wh)
    {
        return EbayDailyPublish::alias('dp')->where($wh)
            ->join('goods g','g.id=dp.goods_id','left')->count();
    }

    /**
     * 获取数据
     * @param $wh
     * @param int $page
     * @param int $pageSize
     * @return array|false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getData($wh, $page=1, $pageSize=50)
    {
        $field = 'dp.id,dp.goods_id,g.spu,g.name,dp.seller_id,g.category_id,g.thumb,dp.expire_time,dp.status,dp.create_time';
        $lists = EbayDailyPublish::alias('dp')->field($field)->where($wh)
            ->join('goods g','g.id=dp.goods_id','left')->page($page,$pageSize)->select();
        $lists = collection($lists)->toArray();
        //获取商品英文标题
        $gdsIds = array_column($lists,'goods_id');
        $gLang = GoodsLang::whereIn('goods_id',$gdsIds)->where('lang_id',2)->column('title','goods_id');
        //获取销售员姓名
        $sIds = array_column($lists,'seller_id');
        $sName = User::whereIn('id',$sIds)->column('realname','id');
        //状态文本数组
        $statusTxt = ['未开始','进行中','已完成'];

        foreach ($lists as &$list) {
            $list['thumb'] = 'https://img.rondaful.com/'.$list['thumb'];
            $list['en_title'] = $gLang[$list['goods_id']] ?? '';
            $list['seller_name'] = $sName[$list['seller_id']] ?? '';
            $list['category_chain'] = (new Goods())->getCategoryAttr([],['category_id'=>$list['category_id']]);
            if ($list['expire_time'] < time()) {//已延期
                $list['status_txt'] = ($list['status'] == 2) ? '已完成' : '已延期';
            } else {
                $list['status_txt'] = $statusTxt[$list['status']];
            }
            $list['task_time'] = date('Y-m-d',$list['expire_time']-86400);
            $list['create_time'] = date('Y-m-d H:i:s',$list['create_time']);
        }
        return $lists;
    }

    /**
     * 批量设置转接
     * @param $data
     * @throws \Exception
     */
    public function setSeller($data)
    {
        $ids = array_column($data,'id');
        $field = 'dp.id,dp.status,dp.expire_time,g.spu';
        $lists = EbayDailyPublish::alias('dp')->whereIn('dp.id',$ids)->field($field)
            ->join('goods g','g.id=dp.goods_id','left')->select();
        //只能转接未开始，未过期的任务
        $msg = '';
        foreach ($lists as $list) {
            if ($list['status'] == 0 && $list['expire_time']>time()) {

            }
        }
        (new EbayDailyPublish())->saveAll($data);
    }

    /**
     * 导出
     * @param $params
     * @return array
     * @throws \think\Exception
     */
    public function export($params)
    {
        $wh = $this->packWh($params);
        $count = $this->totalCount($wh);
        if ($count === 0) {
            return ['message'=>'没有需要导出的数据'];
        }

        $fileName = '每日刊登导出' . date('YmdHis');
        $wh['file_name'] = $fileName;
        $wh['count'] = $count;

        if ($count > 500) {//页面进入且总数大于500走队列
            $model = new ReportExportFiles();
            $data['applicant_id'] = $this->userId;
            $data['apply_time'] = time();
            $data['export_file_name'] = $fileName;
            $data['status'] = 0;
            $data['applicant_id'] = $this->userId;
            $model->allowField(true)->isUpdate(false)->save($data);
            $wh['apply_id'] = $model->id;
            $wh['export_type'] = 3;
            (new CommonQueuer(EbayListingExportQueue::class))->push($wh);
            $message = '导出任务添加成功，请到报表导出管理处下载xlsx';
            return ['message'=>$message];
        }
        return $this->doExport($wh);
    }

    /**
     * 执行导出
     * @param $wh
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function doExport($wh)
    {
        $header = [
            '产品图片'=>'string',
            '本地SPU'=>'string',
            '产品中文名称'=>'string',
            '英文标题'=>'string',
            '销售员'=>'string',
            '本地分类'=>'string',
            '任务时间'=>'string',
            '任务状态'=>'string',
        ];
        $fileName = $wh['file_name'];
        unset($wh['file_name']);
        $applyId = $wh['apply_id']??0;
        unset($wh['apply_id']);
        unset($wh['export_type']);
        $count = $wh['count'];
        unset($wh['count']);

        $data = [];
        $page =  1;
        $pageSize = 500;
        $loop = ceil($count/$pageSize);
        for($i=0; $i<$loop; $i++) {
            $tmp = $this->getData($wh,$page++,$pageSize);
            $data = array_merge($data,$tmp);
        }
        //重新排序
        foreach ($data as &$dt) {
            $tmp[] = $dt['thumb'];
            $tmp[] = $dt['spu'];
            $tmp[] = $dt['name'];
            $tmp[] = $dt['en_title'];
            $tmp[] = $dt['seller_name'];
            $tmp[] = $dt['category_chain'];
            $tmp[] = $dt['task_time'];
            $tmp[] = $dt['status_txt'];
            $dt = $tmp;
        }
        $file = [
            'file_name' => $fileName,
            'file_extension' => 'xlsx',
            'file_code' => date('YmdHis').rand(100000,999999),
            'path' => 'ebay',
            'type' => 'ebay_publish_export',
        ];
        $res = CommonService::xlsxwriterExport($header, $data, $file,$loop>1?1:0,$applyId);
        if ($res === true) {
            return [
                'status' => 1,
                'message' => 'OK',
                'file_code' => $file['file_code'],
                'file_name' => $file['file_name'].'.'.$file['file_extension'],
            ];
        } elseif ($res !== true) {
            throw new Exception($res);
        }
    }

}