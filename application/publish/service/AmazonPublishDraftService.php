<?php
/**
 * Created by PhpStorm.
 * User: zhangdongdong
 * Date: 2018/6/9
 * Time: 16:01
 */

namespace app\publish\service;


use app\common\cache\Cache;
use app\common\model\amazon\AmazonPublishProductDraft;
use app\common\model\User;
use app\common\service\Common;
use app\goods\service\GoodsHelp;
use think\Exception;
use \app\common\traits\User as UserTraits;

class AmazonPublishDraftService
{

    use UserTraits;

    protected $lang = 'zh';

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
        $where = $this->condition($params);

        $userInfo = Common::getUserInfo();
        $user_id = $userInfo['user_id'];
        //不是管理员，则加上人员限制；
        if (!$this->isAdmin($user_id)) {
            $where['uid'] = $user_id;
        }

        $draftModel = new AmazonPublishProductDraft();
        $count = $draftModel->where($where)->count();

        $returnData = [
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
            'lists' => []
        ];

        $lists = $draftModel->where($where)
            ->field('id,spu,title,account_codes,main_url,uid,create_time,update_time')
            ->page($page, $pageSize)->order('update_time', 'desc')
            ->select();

        if (empty($lists)) {
            return $returnData;
        }
        $uids = [];
        $newLists = [];
        foreach ($lists as $val) {
            $tmp = $val->toArray();
            $uids[] = $tmp['uid'];
            $codes = $tmp['account_codes'];
            $tmp['codes'] = $codes;
            $tmp['create_time'] = date('Y-m-d H:i:s', $tmp['create_time']);
            $tmp['update_time'] = date('Y-m-d H:i:s', $tmp['update_time']);
            $newLists[] = $tmp;
        }

        $baseUrl = Cache::store('configParams')->getConfig('innerPicUrl')['value'] . DS;
        $users = User::where(['id' => ['in', $uids]])->column('realname', 'id');
        foreach ($newLists as &$val) {
            $val['user_name'] = $users[$val['uid']] ?? '';
            $val['base_url'] = $baseUrl;
        }

        $returnData['lists'] = $newLists;

        return $returnData;
    }

    public function condition($params)
    {
        $where = [];

        if (!empty($params['account_id'])) {
            $account = Cache::store('AmazonAccount')->getAccount($params['account_id']);
            $where['account_codes'] = ['like', '%' . $account['code'] . '%'];
        }
        if (!empty($params['time_start']) && empty($params['time_end'])) {
            $params['time_start'] = trim($params['time_start'], ' "');
            $where['create_time'] = ['>', strtotime($params['time_start'])];
        }
        if (empty($params['time_start']) && !empty($params['time_end'])) {
            $params['time_end'] = trim($params['time_end'], ' "');
            $where['create_time'] = ['<=', strtotime($params['time_end']) + 86400];
        }
        if (!empty($params['time_start']) && !empty($params['time_end'])) {
            $params['time_start'] = trim($params['time_start'], ' "');
            $params['time_end'] = trim($params['time_end'], ' "');
            $where['create_time'] = ['between', [strtotime($params['time_start']), strtotime($params['time_end']) + 86400]];
        }

        if (!empty($params['type']) && !empty($params['content'])) {
            if (in_array($params['type'], ['spu', 'title', 'upc', 'sku', 'publish_sku'])) {
                $tempArr = explode(',', $params['content']);
                $key = '';
                switch ($params['type']) {
                    case 'spu':
                        $key = 'spu';
                        $where[$key] = ['in', $tempArr];
                        break;
                    case 'title':
                        $key = 'title';
                        $where[$key] = ['like', '%' . $params['content'] . '%'];
                        break;
                    case 'upc':
                        $key = 'upcs';
                        $where[$key] = ['like', '%' . $params['content'] . '%'];
                        break;
                    case 'sku':
                        $key = 'skus';
                        $where[$key] = ['like', '%' . $params['content'] . '%'];
                        break;
                    case 'publish_sku':
                        $key = 'publish_skus';
                        $where[$key] = ['like', '%' . $params['content'] . '%'];
                        break;
                }
            }
        }

        return $where;
    }

    public function get($id)
    {
        $draft = AmazonPublishProductDraft::get($id);
        if (empty($draft)) {
            if ($this->lang == 'zh') {
                throw new Exception('草稿记录不存在');
            } else {
                throw new Exception('Draft record does not exist');
            }
        }

        $userInfo = Common::getUserInfo();
        $user_id = $userInfo['user_id'];
        //不是管理员，则加上人员限制；
        if (!$this->isAdmin($user_id) && $draft['uid'] != $user_id) {
            if ($this->lang == 'zh') {
                throw new Exception('只能编辑自已的草稿');
            } else {
                throw new Exception('You can only edit your own drafts');
            }
        }

        $result['draft_id'] = intval($id);
        $result['data'] = json_decode($draft['draft_json'], true);

        $goodsHelperModel = new GoodsHelp();
        $goodsInfo = $goodsHelperModel->getGoodsAndSkuAttrBySpu($draft['spu']);

        //头部信息；
        $result['header'] = array(
            'spu' => $draft['spu'],
            'goods_id' => $goodsInfo['goods_id'],
            'goods_name' => $goodsInfo['goods_name'],
            'category_name' => $goodsInfo['category_name'],
            'brand' => $goodsInfo['brand'] ? $goodsInfo['brand'] : '未知品牌',
        );
        return $result;
    }

    public function save($data)
    {
        empty($data['draft_id']) && $data['draft_id'] = 0;
        return $this->saveDraft($data['draft_id'], $data['data']);
    }

    public function update($data)
    {
        if (empty($data['draft_id'])) {
            if ($this->lang == 'zh') {
                throw new Exception('草稿ID不存在或不是个正值');
            } else {
                throw new Exception('Draft ID does not exist or is incorrect');
            }
        }
        return $this->saveDraft($data['draft_id'], $data['data']);
    }

    public function saveDraft($id, $list)
    {
        $draftModel = new AmazonPublishProductDraft();
        //用户ID
        $userInfo = Common::getUserInfo();
        $uid = $userInfo['user_id'];

        //找出草稿
        if (!empty($id)) {
            $draft = $draftModel->field('id,uid')->where(['id' => $id])->find();
            if (empty($draft)) {
                if ($this->lang == 'zh') {
                    throw new Exception('草稿记录不存在');
                } else {
                    throw new Exception('Draft record does not exist');
                }
            }
            //不是管理员，则加上人员限制；
            if (!$this->isAdmin($uid) && $draft['uid'] != $uid) {
                if ($this->lang == 'zh') {
                    throw new Exception('只能编辑自已的草稿');
                } else {
                    throw new Exception('You can only edit your own drafts');
                }
            }
        }

        $list = json_decode($list, true);

        if (!is_array($list)) {
            if ($this->lang == 'zh') {
                throw new Exception('data参数的值格式错误');
            } else {
                throw new Exception('The value format of the parameter is incorrect');
            }
        }

        //检查最基本的顺序，即使草稿也不能保存错误的数据；
        $this->checkSort($list);

        $data = [];
        $data['account_codes'] = '';
        $data['spu'] = '';
        $data['skus'] = '';
        $data['publish_skus'] = '';
        $data['upcs'] = '';
        $data['title'] = '';
        $data['main_url'] = '';
        $data['uid'] = $uid;
        $data['draft_json'] = json_encode($list, JSON_UNESCAPED_UNICODE);
        $data['update_time'] = time();
        empty($id) && $data['create_time'] = $data['update_time'];

        foreach ($list as $key => $val) {
            if ($key == 0) {
                $data['spu'] = $val['spu'];
                $data['main_url'] = empty($val['img']['SpuImage'][0]['path']) ? '' : $val['img']['SpuImage'][0]['path'];
                $data['title'] = empty($val['descript'][0]['Title']) ? '' : $val['descript'][0]['Title'];
                $data['uid'] = $uid;
            }
            if (!empty($val['sku'])) {
                foreach ($val['sku'] as $skuval) {
                    $data['skus'] .= $skuval['SKU'] ?? '';
                    $data['skus'] .= ',';
                    $data['publish_skus'] .= $skuval['PublishSKU'] ?? '';
                    $data['publish_skus'] .= ',';
                    $data['upcs'] .= $skuval['ProductIdValue'] ?? '';
                    $data['upcs'] .= ',';
                }
            }
            $data['account_codes'] .= $val['code'] . ',';
        }

        $data['upcs'] = trim($data['upcs'], ',');
        $data['publish_skus'] = trim($data['publish_skus'], ',');
        $data['account_codes'] = trim($data['account_codes'], ',');
        if ($id) {
            $draftModel->update($data, ['id' => $id]);
        } else {
            $id = $draftModel->insertGetId($data);
        }

        return $id;
    }

    /**
     * 检测刊登草稿数据；
     * @param $list
     * @return bool
     * @throws Exception
     */
    public function checkSort($list)
    {
        $noteNo = count($list) == 1 ? false : true;
        $before = '';
        foreach ($list as $key => $data) {
            //验证保存的登刊登参数；
            try {
                if ($this->lang == 'zh') {
                    $before = $noteNo ? '第' . ($key + 1) . '个帐号,' : '';
                    //验测第一个元素是不是父产品；
                    if ($data['basic']['Spu'] != $data['descript'][0]['SKU']) {
                        throw new Exception('标题描述，第一个对象应该是父产品信息');
                    }

                    //验证顺序
                    foreach ($data['descript'] as $key2 => $val) {
                        if ($key2 == 0) {
                            continue;
                        }
                        if ($val['SKU'] != $data['sku'][$key2 - 1]['SKU']) {
                            throw new Exception('标题描述和SKU部分段落顺序对应不上');
                        }
                    }
                } else {
                    $before = $noteNo ? 'No.' . ($key + 1) . ' accounts, ' : '';
                    //验测第一个元素是不是父产品；
                    if ($data['basic']['Spu'] != $data['descript'][0]['SKU']) {
                        throw new Exception('Title description, the first object should be the main product information');
                    }

                    //验证顺序
                    foreach ($data['descript'] as $key2 => $val) {
                        if ($key2 == 0) {
                            continue;
                        }
                        if ($val['SKU'] != $data['sku'][$key2 - 1]['SKU']) {
                            throw new Exception('The title description does not correspond to the SKU part paragraph order');
                        }
                    }
                }
            } catch (Exception $e) {
                throw new Exception($before . $e->getMessage());
            }
        }
        return true;
    }
}