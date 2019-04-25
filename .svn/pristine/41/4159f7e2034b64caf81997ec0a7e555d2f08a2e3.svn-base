<?php
namespace app\publish\service;

use app\common\model\amazon\AmazonProductXsd;
use app\common\model\amazon\AmazonType;
use app\common\service\Common;
use app\common\traits\User;
use PhpOffice\PhpSpreadsheet\IOFactory;
use think\Exception;
use think\Db;
use app\common\cache\Cache;
use app\common\model\User as UserModel;
use app\common\model\amazon\AmazonXsdTemplate as AmazonXsdTemplateModel;
use app\common\model\amazon\AmazonXsdTemplateDetail;
use app\common\model\amazon\AmazonXsdTemplateVariant;
use think\Loader;

class AmazonXsdTemplate
{
    use User;

    private $error = '默认错误';
    private $model;
    private $config;

    protected $lang = 'zh';

    public function __construct()
    {
        $this->model = new AmazonXsdTemplateModel();
        $this->config = new AmazonCategoryXsdConfig();
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


    /**
     * 获取模板列表;
     * @param $param 筛选条件；
     * @param int $page 页码；
     * @param int $pageSize 每页条数；
     * @return array
     */
    public function getList($param, $page = 1, $pageSize = 20)
    {
        $where = $this->getWhere($param);
        $order = $param['order'] ?? 'id';
        $sort = $param['sort'] ?? 'desc';
        $count = $this->model->where($where)->count();
        $data = [
            'count' => $count,
            'page' => $page,
            'pageSize' => $pageSize,
            'order' => $order,
            'sort' => $sort,
            'list' => [],
        ];
        $lists = $this->model->where($where)->order($order, $sort)->page($page, $pageSize)->select();
        if (empty($count)) {
            return $data;
        }
        $userIdArr = [0];
        foreach ($lists as $val) {
            $userIdArr[] = $val['create_id'];
            $userIdArr[] = $val['update_id'];
        }
        $userList = UserModel::where(['id' => ['in', $userIdArr]])->column('realname', 'id');

        $typeArr = [1 => '分类模板', '产品模板'];
        $newData = [];
        foreach ($lists as $val) {
            $val = $val->toArray();
            $val['create_time'] = date('Y-m-d H:i:s', $val['create_time']);
            $val['update_time'] = date('Y-m-d H:i:s', $val['update_time']);
            $val['create_name'] = $userList[$val['create_id']] ?? '-';
            $val['type_text'] = $typeArr[$val['type']]?? '-';
            if ($val['site'] == 0) {
                $val['site_text'] = 'COMMON';
            } else {
                $val['site_text'] = $this->config->getSiteByNum($val['site']);
            }
            $newData[] = $val;
        }
        $data['list'] = $newData;
        return $data;
    }

    private function getWhere($param)
    {
        $where = [];
        if (isset($param['type']) && in_array($param['type'], [1, 2])) {
            $where['type'] = $param['type'];
        }
        if (isset($param['site']) && $param['site'] !== '') {
            $where['site'] = $param['site'];
        }
        if (isset($param['status']) && $param['status'] !== '') {
            $where['status'] = $param['status'];
        }
        if (!empty($param['create_id'])) {
            $where['create_id'] = $param['create_id'];
        }
        if (!empty($param['name'])) {
            $where['name'] = ['like', '%' . $param['name'] . '%'];
        }
        return $where;
    }

    /**
     * 拿取详情
     * @param $id
     * @return array
     */
    public function getInfo($id)
    {
        $data = $this->model->where(['id' => $id])->field('id,name,type,class_name,class_type_id,site,default,status')->find();
        if (empty($data)) {
            $this->error = 'id不存在';
            return false;
        }

        $typeArr = [1 => '分类模板', '产品模板'];

        $val = $data->toArray();

        $infoList = AmazonXsdTemplateDetail::where(['amazon_xsd_template_id' => $id])->order('sort asc')->select();
        $info = [];
        foreach ($infoList as $val2) {
            $data = $val2->toArray();
            $tmp = explode(',', $data['node_tree']);
            unset($data['create_time'], $data['create_id']);
            $data['element_name'] = end($tmp);
            $info[] = $data;
        }
        $val['detail'] = $info;

        if ($val['type'] == 1) {
            $variantList = AmazonXsdTemplateVariant::where(['amazon_xsd_template_id' => $id])->order('id asc')->select();
            $variant = [];
            foreach ($variantList as $val2) {
                $data = $val2->toArray();
                unset($data['create_time'], $data['create_id'], $data['node_tree'], $data['amazon_element_relation_id']);
                $variant[] = $data;
            }
            $val['variant'] = $variant;
        }

        return [
            'data' => $val
        ];
    }

    public function save($data, $uid)
    {
        try {
            $time = time();
            $detailList = [];
            $variantList = [];

            //如果是产品模板，则把class_name标示为Product;
            if ($data['type'] == 2) {
                $data['class_name'] = 'product';
                $data['class_type_id'] = '';
            }

            if ($data['type'] == 1) {
                if (empty($data['class_type_id']))
                    throw new Exception('缺少class_type_id参数');
            }

            //暂时多余的上传数据，需要的时候再改表保存；
            if (isset($data['child_name'])) {
                unset($data['child_name']);
            }

            //创建人、时间；
            $data['create_id'] = $data['update_id'] = $uid;
            $data['create_time'] = $data['update_time'] = $time;

            //找出元素详情和变体的数据；
            $detailList = json_decode($data['detail'], true);
            unset($data['detail']);
            if ($data['type'] == 1 && isset($data['variant'])) {
                $variantList = empty($data['variant']) ? [] : json_decode($data['variant'], true);
                unset($data['variant']);
            }

            //查看默认是否存在，所有产品模板只允详有一个默认模板，每个分类只允许有一个默认默板；
            if ($data['type'] == 2) {
                if ($data['default'] == 1) {
                    $count = $this->model->where(['type' => 2, 'default' => 1, 'site' => $data['site']])->count();
                    if ($count > 0) {
                        throw new Exception('已存在一个产品默认模板，先取消再设置默认');
                    }
                }
            } else {
                if ($data['default'] == 1) {
                    $count = $this->model->where(['type' => 1, 'default' => 1, 'site' => $data['site']])->count();
                    if ($count > 0) {
                        throw new Exception('已存在一个分类默认模板，先取消再设置默认');
                    }
                }
            }

            Db::startTrans();
            $template_id = $this->model->allowField(true)->insert($data, false, true);
            if (!is_array($detailList) || empty($detailList)) {
                throw new Exception('新增时没有添加产品元素直接保存');
            }
            $nameArr = array_column($detailList, 'name');
            if (count($detailList) != count($uniqueArr = array_unique($nameArr))) {
                $tmpArr = array_diff_assoc($nameArr, $uniqueArr);
                throw new Exception('产品元素名称有重复:' . implode('、', $tmpArr));
            }
            $detailModel = new AmazonXsdTemplateDetail();
            $variantModel = new AmazonXsdTemplateVariant();
            foreach ($detailList as $val) {
                $detail = [];
                $detail['amazon_xsd_template_id'] = $template_id;
                $detail['name'] = $val['name'];
                $detail['node_tree'] = $val['node_tree'];
                $detail['amazon_element_relation_id'] = $val['amazon_element_relation_id'];
                $detail['select'] = $val['select'];
                $detail['required'] = $val['required'];
                $detail['show'] = $val['show'];
                $detail['sort'] = $val['sort'];
                //只type是1，也就是分类模板才会有变体sku这个属性,产品是没有这个属性,默认为0
                $detail['sku'] = ($data['type'] == 2) ? '0' : $val['sku'];
                $detail['create_id'] = $uid;
                $detail['create_time'] = time();
                $relation_id = $detailModel->allowField(true)->insert($detail, false, true);
            }

            if (!empty($variantList)) {
                foreach ($variantList as $key => $variant) {
                    //变体字段为空时，则不添加或更新
                    if ($key > 0 && ($variant['relation_field'] == [] || $variant['relation_field'] == '[]')) {
                        continue;
                    }
                    if (is_array($variant['relation_field'])) {
                        $relation_field = json_encode($variant['relation_field']);
                    } else {
                        $relation_field = $variant['relation_field'];
                    }
                    if (!is_json($relation_field)) {
                        throw new Exception('variant下面的relation_field 字段必须是JSON或数组');
                    }
                    $variantModel->insert([
                        'amazon_xsd_template_id' => $template_id,
                        'name' => $variant['name'],
                        'relation_field' => $relation_field,
                        'create_time' => $time,
                        'create_id' => $uid
                    ]);
                }
            }

            Db::commit();

            //把插入的列表数据返回；
            $data['id'] = $template_id;
            return $this->makeReturnData($data);
        } catch (Exception $e) {
            Db::rollback();
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 更新模板数据和详情数据；
     */
    public function update($data, $uid)
    {
        try {
            $template = $this->model->where(['id' => $data['id']])->find();
            if (empty($template)) {
                throw new Exception('本条数据ID不存在');
            }

            //如果是产品模板，则把class_name标示为Product;
            if ($data['type'] == 2) {
                $data['class_name'] = 'product';
                $data['class_type_id'] = '';
            }

            if ($data['type'] == 1) {
                if (empty($data['class_type_id']))
                    throw new Exception('缺少class_type_id参数');
            }

            //暂时多余的上传数据，需要的时候再改表保存；
            if (isset($data['child_name'])) {
                unset($data['child_name']);
            }

            $detailList = [];
            $variantList = [];
            $time = time();
            $data['update_id'] = $uid;
            $data['update_time'] = $time;

            $detailList = json_decode($data['detail'], true);
            unset($data['detail']);
            if ($data['type'] == 1 && isset($data['variant'])) {
                $variantList = empty($data['variant']) ? [] : json_decode($data['variant'], true);
                unset($data['variant']);
            }

            //查看默认是否存在，所有产品模板只允详有一个默认模板，每个分类只允许有一个默认默板；
            if ($data['type'] == 2) {
                if ($data['default'] == 1) {
                    $ids = $this->model->where(['type' => 2, 'default' => 1, 'site' => $data['site']])->column('id');
                    if (count($ids) > 2 || (count($ids) == 1 && !in_array($data['id'], $ids))) {
                        throw new Exception('已存在一个产品默认模板，先取消再设置默认');
                    }
                }
            } else {
                if ($data['default'] == 1) {
                    $ids = $this->model->where(['type' => 1, 'default' => 1, 'site' => $data['site']])->column('id');
                    if (count($ids) > 2 || (count($ids) == 1 && !in_array($data['id'], $ids))) {
                        throw new Exception('已存在一个产品默认模板，先取消再设置默认');
                    }
                }
            }

            Db::startTrans();
            $this->model->allowField(true)->update($data, ['id' => $data['id']]);
            $data['create_time'] = $template['create_time'];
            $data['create_id'] = $template['create_id'];
            if (!is_array($detailList) || empty($detailList)) {
                throw new Exception('没有添加产品元素直接保存');
            }

            $nameArr = array_column($detailList, 'name');
            if (count($detailList) != count($uniqueArr = array_unique($nameArr))) {
                $tmpArr = array_diff_assoc($nameArr, $uniqueArr);
                throw new Exception('产品元素名称有重复:' . implode('、', $tmpArr));
            }

            $detailModel = new AmazonXsdTemplateDetail();
            $variantModel = new AmazonXsdTemplateVariant();
            //详情列表
            $list = $detailModel->where('amazon_xsd_template_id', $data['id'])->column('amazon_xsd_template_id', 'id');
            foreach ($detailList as $val) {
                $detail = [];
                $detail['amazon_xsd_template_id'] = $data['id'];
                $detail['name'] = $val['name'];
                $detail['node_tree'] = $val['node_tree'];
                $detail['amazon_element_relation_id'] = $val['amazon_element_relation_id'];
                $detail['select'] = $val['select'];
                $detail['required'] = $val['required'];
                $detail['show'] = $val['show'];
                $detail['sort'] = $val['sort'];
                $detail['sku'] = $val['sku'];
                if (empty($val['id'])) {
                    $detail['create_id'] = $uid;
                    $detail['create_time'] = time();
                    $detailModel->allowField(true)->insert($detail);
                } else {
                    if (!empty($list[$val['id']])) {
                        $detailModel->allowField(true)->update($detail, ['id' => $val['id']]);
                        unset($list[$val['id']]);
                    } else {
                        throw new Exception('错误的detail列表ID');
                    }
                }
            }
            //下面是删掉不存在的详情数据；
            foreach ($list as $key => $val) {
                $detailModel->where(['id' => $key])->delete();
            }

            //变体列表
            $list = $variantModel->where('amazon_xsd_template_id', $data['id'])->column('amazon_xsd_template_id', 'id');
            foreach ($variantList as $key => $val) {
                //变体字段为空时，则不添加或更新
                if ($key > 0 && ($val['relation_field'] == [] || $val['relation_field'] == '[]')) {
                    continue;
                }
                if (is_array($val['relation_field'])) {
                    $relation_field = json_encode($val['relation_field']);
                } else {
                    $relation_field = $val['relation_field'];
                }
                if (!is_json($relation_field)) {
                    throw new Exception('variant下面的relation_field 字段必须是JSON或数组');
                }
                $variant = [];
                $variant['amazon_xsd_template_id'] = $data['id'];
                $variant['name'] = $val['name'];
                $variant['relation_field'] = $relation_field;
                if (empty($val['id'])) {
                    $variant['create_id'] = $uid;
                    $variant['create_time'] = time();
                    $variantModel->allowField(true)->insert($variant);
                } else {
                    if (!empty($list[$val['id']])) {
                        $variantModel->allowField(true)->update($variant, ['id' => $val['id']]);
                        unset($list[$val['id']]);
                    } else {
                        throw new Exception('错误的variant列表ID');
                    }
                }
            }
            //下面是删掉不存在的变体数据；
            foreach ($list as $key => $val) {
                $variantModel->where(['id' => $key])->delete();
            }

            Db::commit();

            Cache::store('AmazonTemplate')->setTemplateAttr($data['id'], []);
            return $this->makeReturnData($data);
        } catch (Exception $e) {
            Db::rollback();
            throw new Exception('MSG:' . $e->getMessage() . '; FILE: ' . $e->getFile() . '; LINE:' . $e->getLine() . ';');
        }
    }

    private function makeReturnData($data)
    {
        $typeArr = [1 => '分类模板', '产品模板'];
        $data['id'] = intval($data['id']);
        $data['type'] = intval($data['type']);
        $data['site'] = intval($data['site']);
        $data['status'] = intval($data['status']);
        $data['default'] = intval($data['default']);
        $data['type_text'] = $typeArr[$data['type']];
        $data['site_text'] = $this->config->getSiteByNum($data['site']);
        $data['create_time'] = date('Y-m-d H:i:s', $data['create_time']);
        $data['update_time'] = date('Y-m-d H:i:s', $data['update_time']);
        $data['create_name'] = UserModel::where(['id' => $data['create_id']])->value('realname');
        $data['success_total'] = 0;
        $data['error_total'] = 0;
        return $data;
    }

    /**
     * 按模板自增id进行删除;
     * @param $id
     * @return bool
     * @throws Exception
     */
    public function delete($idArr)
    {
        try {
            Db::startTrans();
            $this->model->where(['id' => ['in', $idArr]])->delete();
            AmazonXsdTemplateDetail::where(['amazon_xsd_template_id' => ['in', $idArr]])->delete();
            Db::commit();
            //删除缓存；
            foreach ($idArr as $template_id) {
                Cache::store('AmazonTemplate')->setTemplateAttr($template_id, []);
            }
            return true;
        } catch (Exception $e) {
            Db::rollback();
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 禁用或启用；
     * @param $id
     * @param $enable
     * @return bool
     * @throws Exception
     */
    public function status($id, $enable)
    {
        $data = $this->model->where(['id' => $id])->find();
        if (empty($data)) {
            throw new Exception('模板不存在');
        }
        $data->status = $enable;
        $data->save();
        Cache::store('AmazonTemplate')->setTemplateAttr($id, []);
        return true;
    }

    /**
     * 获取创建人和站点
     */
    public function getCreator($type = 1)
    {
        $site = $this->config->getSiteList();
        $userId = [0];
        $createId = $this->model->where(['type' => $type])->group('create_id')->field('create_id')->select();
        foreach ($createId as $val) {
            $userId[] = $val['create_id'];
        }
        $userList = UserModel::where(['id' => ['in', $userId]])->field('id value,realname label')->select();
        return [
            'site' => $site,
            'creator' => ($userList ? $userList : [])
        ];
    }

    public function getError()
    {
        return $this->error;
    }

    public function getFilterField()
    {
        static $filter = [];
        if (!empty($filter)) {
            return $filter;
        }
        $filter = (new AmazonPublishService())->getFilter();
        $filter = array_merge($filter, ['VariationTheme', 'Parentage']);
        return $filter;
    }

    /**
     * 拿取亚马逊刊登时的模板信息；
     * @return array
     */
    public function getAttr($template_id, $site)
    {
        //先从缓存找；
        $template = Cache::store('AmazonTemplate')->getTemplateAttr($template_id);
        if (!empty($template)) {
            return $template;
        }

        $template = $this->model->where(['id' => $template_id, 'type' => 1, 'status' => 1])
            ->where('site=0'. ' OR site='. $site)
            ->find();

        if (empty($template)) {
            if ($this->lang == 'zh') {
                throw new Exception('分类模板不存在或未启用，也没有默认模板');
            } else {
                throw new Exception('Category templates do not exist or are not enabled, and there is no default template');
            }
        }

        $template = $template->toArray();
        $variantModel = new AmazonXsdTemplateVariant();
        $templateVariant = $variantModel->where(['amazon_xsd_template_id' => $template_id])->field('id,name,relation_field')->select();
        $variantList = [];
        foreach ($templateVariant as $variant) {
            $variant = $variant->toArray();
            $variant['relation_field'] = json_decode($variant['relation_field'], true);
            $variantList[] = $variant;
        }

        $detailModel = new AmazonXsdTemplateDetail();
        $templateDetail = $detailModel->alias('d')
            ->join(['amazon_product_xsd' => 'x'], 'd.amazon_element_relation_id=x.id')
            ->field('d.id,d.name,d.node_tree,d.select,d.required,d.show,d.sort,d.sku,x.type_name,x.attribute,x.max max_occurs,x.min min_occurs,x.doc,x.restriction,x.validata')
            ->where(['amazon_xsd_template_id' => $template_id])
            ->order('d.sort', 'asc')
            ->select();
        $attrList = [];

        $filterArr = $this->getFilterField();
        foreach ($templateDetail as $detail) {
            if (in_array($detail['name'], $filterArr)) {
                continue;
            }
            $detail = $detail->toArray();
            $attrList[] = $this->buildAttrRestriction($detail);
        }


        $template['variant'] = $variantList;
        $template['attrs'] = $attrList;

        //查出来的先放缓存里面去；
        Cache::store('AmazonTemplate')->setTemplateAttr($template_id, $template);

        return $template;
    }

    /**
     * 把约束条件大至解析出来，方便前端验证；
     * @param $attr
     * @return array
     */
    public function buildAttrRestriction($attr)
    {
        $data['maxLength'] = '';
        $data['minLength'] = '';
        $data['pattern'] = '';
        $data['option'] = '';
        $data['totalDigits'] = '';

        $data['attribute'] = json_decode($attr['attribute'], true);
        if (!empty($data['attribute'][0])) {
            $data['attribute'] = $data['attribute'][0];
            if (isset($data['attribute']['restriction']['enumeration'])) {
                $data['attribute']['restriction'] = $data['attribute']['restriction']['enumeration'];
            }
        }

        $data['restriction'] = json_decode($attr['restriction'], true);

        if (isset($data['restriction']['enumeration'])) {
            $data['option'] = $data['restriction']['enumeration'];
            $data['select'] = 1;
            $data['restriction']['enumeration'] = [];
        }
        if ($attr['type_name'] == 'boolean') {
            $data['option'] = ['TRUE', 'FALSE'];
        }
        if (isset($data['restriction']['length'])) {
            $data['option'] = $data['restriction'];
            $data['maxLength'] = $data['restriction']['length'];
            $data['minLength'] = $data['restriction']['length'];
        }
        $data['maxLength'] = $data['restriction']['maxLength'] ?? '';
        $data['minLength'] = $data['restriction']['minLength'] ?? '';
        $data['pattern'] = $data['restriction']['pattern'] ?? '';
        $data['totalDigits'] = $data['restriction']['totalDigits'] ?? '';
        $attr['validata'] = $this->buildValidata($attr['validata']);

        $attr = array_merge($attr, $data);
        return $attr;
    }


    public function buildValidata($data) : array
    {
        $validata = [];
        if (empty($data)) {
            return $validata;
        }

        $validata = json_decode($data, true);
        if (empty($validata)) {
            return $validata;
        }

        if (count($validata) > 100) {
            $validata = array_slice($validata, 0, 100);
        }
        return $validata;
    }

    /**
     * 拿亚马逊刊登时的产品属性
     * @param $template_id
     */
    public function getProductAttr($template_id, $site)
    {
        if ($template_id == 0) {
            $template = $this->model->where(['default' => 1, 'site' => $site, 'type' => 2, 'status' => 1])->find();
        } else {
            //有模板ID先从缓存找；
            $templateCache = Cache::store('AmazonTemplate')->getTemplateAttr($template_id);
            if (!empty($templateCache)) {
                return $templateCache;
            }

            $template = $this->model->where(['id' => $template_id, 'site' => $site, 'type' => 2, 'status' => 1])->find();
        }

        if (empty($template)) {
            if ($this->lang == 'zh') {
                throw new Exception('产品模板不存在或未启用，也没有默认模板');
            } else {
                throw new Exception('The product template does not exist or is not enabled, and there is no default template');
            }
        }
        //先从缓存找；
        $templateCache = Cache::store('AmazonTemplate')->getTemplateAttr($template['id']);
        if (!empty($templateCache)) {
            return $templateCache;
        }

        $template = $template->toArray();
        $template_id = $template['id'];
        $detailModel = new AmazonXsdTemplateDetail();
        $templateDetail = $detailModel->alias('d')
            ->join(['amazon_product_xsd' => 'x'], 'd.amazon_element_relation_id=x.id')
            ->field('d.id,d.name,d.node_tree,d.select,d.required,d.show,d.sort,d.sku,x.type_name,x.attribute,x.max max_occurs,x.min min_occurs,x.doc,x.restriction,x.validata')
            ->where(['amazon_xsd_template_id' => $template_id])
            ->order('d.sort', 'asc')
            ->select();
        $attrList = [];

        $filterArr = $this->getFilterField();
        foreach ($templateDetail as $detail) {
            if (in_array($detail['name'], $filterArr)) {
                continue;
            }
            $detail = $detail->toArray();
            $attrList[] = $this->buildAttrRestriction($detail);
        }

        $template['attrs'] = $attrList;

        //查出来的先放缓存里面去；
        Cache::store('AmazonTemplate')->setTemplateAttr($template_id, $template);

        return $template;
    }

    /**
     * 拿取简单的模板列表
     */
    public function getSimpleList($type, $site, $keyword = '', $page, $pageSize = 20)
    {
        $where['status'] = 1;
        $where['type'] = $type;

        if (!empty($keyword)) {
            $where['name'] = ['like', '%' . $keyword . '%'];
        }


        //type == 2 产品模板
        if ($type == 2) {
            $list = $this->model->where($where)->where(['site' => $site])->field('id value,site,name label,default')->order('name', 'asc')->page($page, $pageSize)->select();
            return empty($list) ? [] : collect($list)->toArray();
        }

        $siteWhere = 'site='. $site. ' OR site=0';
        //分类模板；
        $list = $this->model->where($where)
            ->where($siteWhere)
            ->field('id value,name label,site,default,success_total,error_total')
            ->order('name', 'asc')
            ->page($page, $pageSize)
            ->select();
        if (empty($list)) {
            return [];
        }
        $ids = [];
        foreach ($list as $val) {
            $ids[] = $val['value'];
        }
        //查找变体个数，来区分分类模板是否有变体，方便标出来
        $variantTotal = AmazonXsdTemplateVariant::where(['amazon_xsd_template_id' => ['in', $ids]])
            ->group('amazon_xsd_template_id')
            ->field('count(id) total,amazon_xsd_template_id')
            ->select();
        $newVariantTotal = [];
        foreach ($variantTotal as $val) {
            $newVariantTotal[$val['amazon_xsd_template_id']] = $val['total'];
        }
        $newList = [];
        foreach ($list as $val) {
            $tmp = [];
            $tmp['value'] = $val['value'];
            $total = $val['success_total'] + $val['error_total'];
            $tmp['label'] = $val['label'] . '【';
            if ($val['site'] == 0) {
                $tmp['label'] = $tmp['label']. 'COMMON：';
            } else {
                $tmp['label'] = $tmp['label']. AmazonCategoryXsdConfig::getSiteByNum($val['site']). '：';
            }
            $variantTag = isset($newVariantTotal[$val['value']]) ? '变体' : '单体';
            $tmp['label'] = $tmp['label']. $val['success_total'] . '/' . $total . '】-'. $variantTag;
            $tmp['site'] = $val['site'];
            $newList[] = $tmp;
        }
        return $newList;
    }


    /**
     * 更新模板数据，把旧数握更新到新数据来
     * @param $id
     * @return bool
     */
    public function updateOldDataAll()
    {
        $user = Common::getUserInfo();
        if (!$this->isAdmin($user['user_id'])) {
            throw new Exception('无权限使用');
        }
        $this->updateOldDataSwoole(true);
    }


    public function updateOldDataSwoole($write = false)
    {
        $page = 1;
        $pageSize = 100;
        do {
            $ids = $this->model->page($page++, $pageSize)->column('id');
            if (empty($ids)) {
                break;
            }

            foreach ($ids as $id) {
                $this->updateOldData($id);
                if ($write) {
                    echo '更新模板ID：' . $id . "\r\n";
                }
            }

            if (count($ids) < $pageSize) {
                break;
            }

        } while (true);
    }


    /**
     * 更新模板数据，把旧数握更新到新数据来
     * @param $id
     * @return bool
     */
    public function updateOldData($id)
    {
        $template = $this->model->where(['id' => $id])->find();
        if (empty($template)) {
            return false;
        }
        $time = time();
        //1.更新模板的type_id数据；
        if ($template['type'] == 1) {
            $type_id = $this->getNewTypeId($template['class_type_id'], $template['class_name']);
            $this->model->update(['class_type_id' => $type_id, 'update_time' => $time], ['id' => $id]);
        }

        //2.更新详情数据；
        $details = $this->updateOldTemplateDetail($id);

        //3.更新变体名称
        //if ($template['type'] == 1) {
        //    $this->updateOldVariant($id, $details, $template['class_type_id']);
        //}
    }


    /**
     * 把旧的type_id更换出来，找出新的type_id
     * @param $class_type_id
     * @param $class_name
     * @return string
     */
    public function getNewTypeId($class_type_id, $class_name): string
    {
        if (empty($class_type_id)) {
            return '';
        }
        $typeArr = explode(',', $class_type_id);
        $typeModel = new AmazonType();
        $xsdModel = new AmazonProductXsd();

        //先检查
        if (!empty($typeArr[0]) && is_numeric($typeArr[0])) {
            $element_name = $xsdModel->where(['id' => $typeArr[0]])->value('name');
            if ($element_name == $class_name) {
                return $class_type_id;
            }
        }
        $typeId1 = '';
        if (!empty($typeArr[0]) && is_numeric($typeArr[0])) {
            $type1 = $typeModel->where(['id' => $typeArr[0]])->value('name');
            $typeId1 = $xsdModel->where(['name' => $type1])->value('id');
        }
        $typeId2 = '';
        if (!empty($typeArr[1]) && is_numeric($typeArr[1])) {
            $type2 = $typeModel->where(['id' => $typeArr[1]])->value('name');
            $typeId2 = $xsdModel->where(['name' => $type2])->value('id');
        }
        $typeId = trim($typeId1 . ',' . $typeId2, ',');
        return $typeId;
    }


    /**
     * 更样的模板详情ID
     * @param $id
     * @return array 返回的更新后的详情名和ID对；
     */
    public function updateOldTemplateDetail($id)
    {
        $detailModel = new AmazonXsdTemplateDetail();
        $details = $detailModel->where(['amazon_xsd_template_id' => $id])->column('id', 'node_tree');

        $xsdModel = new AmazonProductXsd();
        $newDetails = $xsdModel->where(['path' => ['in', array_keys($details)]])->column('id', 'path');

        foreach ($details as $key => $id) {
            if (empty($newDetails[$key])) {
                $detailModel->where(['id' => $id])->delete();
            } else {
                $new_id = $newDetails[$key];
                $detailModel->update(['amazon_element_relation_id' => $new_id], ['id' => $id]);
            }
        }

        return $newDetails;
    }


    public function updateOldVariant($id, $details, $class_type_id)
    {
        $nameArr = [];
        foreach ($details as $path=>$vid) {
            $pathArr = explode(',', $path);
            $nameArr[end($pathArr)] = $vid;
        }

        $variantModel = new AmazonXsdTemplateVariant();
        $variantLists = $variantModel->where(['amazon_xsd_template_id' => $id])->column('id', 'name');
        $restriction = $this->getVariantByClassTypeId($class_type_id);
    }


    public function getVariantByClassTypeId($class_type_id, $sub_id = 0)
    {
        if (empty($class_type_id)) {
            return [];
        }

        $typeIdArr = explode(',', $class_type_id);
        if ($sub_id == 0 && !empty($typeIdArr[1]) && is_numeric($typeIdArr[1])) {
            $class_type_id = $typeIdArr[0];
            $sub_id = $typeIdArr[1];
        }

        $xsdModel = new AmazonProductXsd();
        $eles = $xsdModel->where(['pid' => $class_type_id])->column('name,restriction,has_child', 'id');

        foreach ($eles as $val) {
            if ($val['name'] == 'VariationTheme') {
                return json_decode($val['restriction'], true);
            } else if ($val['name'] == 'ProductType' && $val['has_child'] == 1) {
                return $this->getVariantByClassTypeId($sub_id, 0);
            } else if ($val['name'] == 'VariationData' && $val['has_child'] == 1) {
                    return $this->getVariantByClassTypeId($val['id']);
            } else if ($val['has_child'] == 1) {
                $result = $this->getVariantByClassTypeId($val['id']);
                if (!empty($result)) {
                    return $result;
                }
            }
        }

        return '';
    }


    public function autoAddTemplate()
    {
        set_time_limit(0);
        ini_set('memory_limit', '4096M');

        $excels = [
            'AutoAccessory' => 'Flat.File.AutoAccessory.ca.xlsm',
            'Baby' => 'Flat.File.Baby.ca.xlsm',
            'Beauty' => 'Flat.File.Beauty.ca.xlsm',
            /*'BookLoader' => 'Flat.File.BookLoader.xlsm',*/
            'CameraPhoto' => 'Flat.File.CameraAndPhoto.xlsm',
            'Clothing' => 'Flat.File.Clothing.ca.xlsm',
            'Coins' => 'Flat.File.Coins.xlsm',
            'Computers' => 'Flat.File.Computers.xlsm',
            /*'ConsumerElectronics' => 'Flat.File.ConsumerElectronics.xlsm',*/
            'EntertainmentCollectibles' => 'Flat.File.EntertainmentCollectibles.xlsm',
            'FoodAndBeverages' => 'Flat.File.FoodAndBeverages.xlsm',
            /*'GiftCards' => 'Flat.File.GiftCards.xlsm',*/
            'Health' => 'Flat.File.Health.xlsm',
            'Home' => 'Flat.File.Home.ca.xlsm',
            'HomeImprovement' => 'Flat.File.HomeImprovement.xlsm',
            'Industrial' => 'Flat.File.Industrial.xlsm',
            'Jewelry' => 'Flat.File.Jewelry.xlsm',
            'LabSupplies' => 'Flat.File.LabSupplies.xlsm',
            'Lighting' => 'Flat.File.Lighting.xlsm',
            'MechanicalFasteners' => 'Flat.File.MechanicalFasteners.xlsm',
            'Music' => 'Flat.File.Music.xlsm',
            'MusicalInstruments' => 'Flat.File.MusicalInstruments.xlsm',
            'Office' => 'Flat.File.Office.xlsm',
            'Outdoors' => 'Flat.File.Outdoors.xlsm',
            'PetSupplies' => 'Flat.File.PetSupplies.xlsm',
            'PowerTransmission' => 'Flat.File.PowerTransmission.xlsm',
            'RawMaterials' => 'Flat.File.RawMaterials.xlsm',
            'Shoes' => 'Flat.File.Shoes.xlsm',
            'SoftwareVideoGames' => 'Flat.File.SoftwareVideoGames.xlsm',
            'Sports' => 'Flat.File.Sports.xlsm',
            'SportsMemorabilia' => 'Flat.File.SportsMemorabilia.xlsm',
            'Toys' => 'Flat.File.Toys.xlsm',
            /*'TradingCards' => 'Flat.File.TradingCards.xlsm',*/
            'Video' => 'Flat.File.Video.xlsm',
            /*'Watches' => 'Flat.File.Watches.xlsm',*/
            'Wireless' => 'Flat.File.Wireless.xlsm',
        ];
        $basePath = ROOT_PATH. 'public/download/amazon/';

        foreach ($excels as $key=>$path) {
            $this->getXsdElement($key, $basePath. $path);

            echo $key. '自动更新完成'. "\r\n";
        }
    }


    /**
     * @param string $name
     * @throws Exception
     */
    public function getXsdElement($name, $path)
    {
        try {
            $xsdModel = new AmazonProductXsd();
            $parentEle = [];
            $eles = $xsdModel->where(['name' => $name])->select();
            if (empty($eles)) {
                throw new Exception('元素:'. $name. '不存在');
            }
            $pid = $xsdModel->where(['name' => 'ProductData'])->value('id');
            foreach ($eles as $val) {
                if ($pid == $val['pid']) {
                    $parentEle = $val;
                }
            }
            if (empty($parentEle)) {
                throw new Exception('元素:'. $name. '不是一个分类名称');
            }

            $lists = $xsdModel->where(['data_type' => $parentEle['data_type']])->select();
            //变体；
            $this->variants = [];
            $tree = $this->buildTree($lists, $parentEle['pid']);
            if (empty($tree)|| empty($tree[$name])) {
                throw new Exception('分类为空');
            }
            $tree = $tree[$name];
            $classTree = $this->buildClassTree($tree);

            $excelEles = ['require' => [], 'valid' => []];
            $excelEles = $this->readTemplateExcel($path);

            //包含的数据；
            $includeData = ['UnitCount'];

            foreach ($classTree as $tree) {
                //获取变体字段
                $themes = $this->theme($tree);
                //筛选出常用的字段；
                $themes = $this->chooseTemes($themes);

                $this->autoAddEle($tree, $excelEles['require'], $includeData, $themes, $excelEles['valid']);
            }

            return true;
        } catch (Exception $e) {
            throw new Exception($e->getMessage(). '|'. $e->getLine(). '|'. $e->getFile());
        }
    }

    /**
     * 拿到变体主题；
     * @param $tree
     * @return array|bool
     */
    public function theme($tree)
    {
        $list = $tree['childs'];
        while ($list) {
            $newList = [];
            foreach ($list as $val) {
                if ($val['has_child'] == 0) {
                    if ($val['name'] == 'VariationTheme') {
                        $arr = json_decode($val['restriction'], true);
                        if (!empty($arr['enumeration'])) {
                            return (array)$arr['enumeration'];
                        } else {
                            return [];
                        }
                    }
                } else {
                    $newList = array_merge($newList, $val['childs']);
                }
            }
            $list = $newList;
        }
        return false;
    }


    /**
     * 选择常用的变体主题；
     * @param $themes
     * @return array
     */
    public function chooseTemes($themes)
    {
        if (empty($themes)) {
            return [];
        }
        $new = [];
        //这几个是优先的；
        $priorityVariants = ['Color', 'Size', 'Color-Size', 'ColorSize'];// 'Size-Color', 'SizeColor'
        foreach ($themes as $val) {
            if (in_array($val, $priorityVariants)) {
                $new[] = $val;
            }
        }
        if (count($new) >= 3) {
            return $new;
        }

        //没有Color-Size,看看有Size-Color没有；
        if (in_array('Color', $new) && in_array('Size', $new) && count($new) == 2) {
            if (in_array('Size-Color', $themes)) {
                $new[] = 'Size-Color';
                return $new;
            }
            if (in_array('SizeColor', $themes)) {
                $new[] = 'SizeColor';
                return $new;
            }
        }

        $total = count($themes);
        for ($i = 0; $i < $total; $i++) {
            if (count($new) >= 4) {
                break;
            }
            if (!in_array($themes[$i], $new)) {
                $new[] = $themes[$i];
            }
        }
        return $new;
    }


    public function chooseVariant($themes)
    {
        if (empty($themes)) {
            return [];
        }
        $variants = [];
        foreach ($themes as $val) {
            $val = str_replace('-', '', $val);
            $tmp = preg_replace('@([A-Z])@', ' $1', $val);
            $arr = explode(' ', trim($tmp));
            foreach ($arr as $v) {
                $ustr = ucwords($v);
                $variants[] = $ustr;
                $variants[] = $ustr. 'Name';
                $variants[] = $ustr. 'Map';
            }
        }
        $variants = array_unique($variants);
        return $variants;
    }


    public function autoAddEle($tree, $requireData, $includeData, $themes, $validData)
    {
        $requireData = array_merge($requireData, ['Color', 'ColorName', 'ColorMap']);
        $time = time();
        //这是添加分类模板的基础信息，如果有小分类，要加上小分类，如果没有小分类，则可以直接添加；

        try {
            $templateName = $tree['name'];
            $classTypeId = $tree['id'];
            //有子分类；
            if (!empty($tree['childs']['ProductType']['childs'])) {
                foreach ($tree['childs']['ProductType']['childs'] as $sub) {
                    $templateName .= '-'. $sub['name'];
                    $classTypeId .= ','. $sub['id'];
                }
            }

            $template = [
                'name' => $templateName,
                'type' => 1,
                'class_type_id' => $classTypeId,
                'class_name' => $tree['name'],
                'site' => 0,
                'create_time' => $time,
                'update_time' => $time,
                'create_id' => 1,
                'update_id' => 1,
                'status' => 1
            ];

            $xsdTemplateModel = new AmazonXsdTemplateModel();
            $id = $xsdTemplateModel->where(['name' => $templateName, 'type' => 1, 'class_type_id' => $classTypeId, 'create_id' => $template['create_id']])->value('id');
            if (empty($id)) {
                $id = $xsdTemplateModel->insertGetId($template);
            }

            //通过变体字段，标出变体；
            $variants = $this->chooseVariant($themes);

            $details = $this->getElementDetail($tree, $requireData, $includeData, $variants, $validData);
            $detail_names = $this->saveDetails($details, $id);
            $this->saveVariants($themes, $detail_names, $id);

            //var_dump($details);exit;
        } catch (Exception $e) {
            throw new Exception($e->getMessage(). '|'. $e->getLine(). '|'. $e->getFile());
        }
    }


    public function getElementDetail($tree, $requireData, $includeData, $variants, $validData, $min = 1)
    {
        if (empty($tree['childs'])) {
            return;
        }
        $eles = [];
        foreach ($tree['childs'] as $val) {
            if (empty($val['childs']) && $val['has_child'] == 0) {
                //先用必选
                if (in_array($val['name'], $requireData) || ($val['min'] > 0 && $min > 0)) {
                    $eles[] = [
                        'name' => $val['name'],
                        'node_tree' => $val['path'],
                        'amazon_element_relation_id' => $val['id'],
                        'required' => 1,
                        'create_time' => time(),
                        'create_id' => 1,
                        'min' => $val['min'],
                        'sku' => (int)in_array($val['name'], $variants)
                    ];
                } else if (in_array($val['name'], $includeData)) {
                    $eles[] = [
                        'name' => $val['name'],
                        'node_tree' => $val['path'],
                        'amazon_element_relation_id' => $val['id'],
                        'required' => 0,
                        'create_time' => time(),
                        'create_id' => 1,
                        'min' => $val['min'],
                        'sku' => (int)in_array($val['name'], $variants)
                    ];
                } else if (in_array($val['name'], $variants)) {
                    $eles[] = [
                        'name' => $val['name'],
                        'node_tree' => $val['path'],
                        'amazon_element_relation_id' => $val['id'],
                        'required' => 0,
                        'create_time' => time(),
                        'create_id' => 1,
                        'min' => $val['min'],
                        'sku' => (int)in_array($val['name'], $variants)
                    ];
                }
                //当有有效值的时候；
                if (!empty($validData[$val['name']])) {
                    $validataJson = json_encode(array_merge($validData[$val['name']]), JSON_UNESCAPED_UNICODE);
                    //先验证有效值；
                    if ($val['validata'] == $validataJson) {
                        continue;
                    }
                    $restriction = json_decode($val['restriction'], true);
                    //没有枚选值，则填充有效值；
                    if (empty($restriction['enumeration'])) {
                        AmazonProductXsd::update(['validata' => $validataJson], ['id' => $val['id']]);
                    }
                }
            } else {
                $min2 = $min > 0 ? $val['min'] : $min;
                $tmp = $this->getElementDetail($val, $requireData, $includeData, $variants, $validData, $min2);
                $eles = array_merge($eles, $tmp);
            }
        }
        return $eles;
    }


    public function saveDetails($details, $template_id)
    {
        $detailModel = new AmazonXsdTemplateDetail();
        $oldIds = $detailModel->where(['amazon_xsd_template_id' => $template_id])->column('id');

        $news = [];
        foreach ($details as $detail) {
            $news[$detail['name']][] = $detail;
        }
        $insertDetails = [];
        foreach ($news as $key=>$val) {
            if (count($val) > 1) {
                foreach ($val as $v) {
                    if (strpos($v['node_tree'], 'ProductType') !== false) {
                        $insertDetails[$key] = $v;
                    }
                }
                if (empty($insertDetails[$key])) {
                    $insertDetails[$key] = $val[0];
                }
            } else {
                $insertDetails[$key] = $val[0];
            }
        }
        $detail_names = [];
        ksort($insertDetails);
        $sort = 1;
        foreach ($insertDetails as $detail) {
            $ids = $detailModel->where([
                'amazon_xsd_template_id' => $template_id,
                'node_tree' => $detail['node_tree'],
            ])->column('id');

            $detail_names[] = $detail['name'];

            unset($detail['min']);
            $detail['amazon_xsd_template_id'] = $template_id;
            $detail['sort'] = $sort++;

            if (empty($ids)) {
                $detailModel->insertGetId($detail);
            } else {
                $id = $ids[0];
                if (in_array($id, $oldIds)) {
                    unset($oldIds[array_search($id, $oldIds)]);
                }
                $detailModel->update($detail, ['id' => $id]);
            }
        }

        //删除旧的元素；
        if (!empty($oldIds)) {
            //$detailModel->where(['id' => ['in', $oldIds]])->delete();
        }

        return $detail_names;
    }


    public function saveVariants($themes, $detail_names, $template_id)
    {
        $variantModel = new AmazonXsdTemplateVariant();
        $oldIds = $variantModel->where(['amazon_xsd_template_id' => $template_id])->column('id');
        foreach ($themes as $val) {
            $tmpVariant = [
                'amazon_xsd_template_id' => $template_id,
                'name' => $val,
                'create_id' => 1
            ];

            $id = $variantModel->where($tmpVariant)->value('id');
            $relation_field = [];
            $val = str_replace('-', '', $val);
            $tmp = preg_replace('@([A-Z])@', ' $1', $val);
            $arr = explode(' ', trim($tmp));
            foreach ($arr as $v) {
                $vtmp = ucwords($v);
                if (in_array($vtmp, $detail_names)) {
                    $relation_field[] = $vtmp;
                }
                $vtmp2 = $vtmp. 'Name';
                if (in_array($vtmp2, $detail_names)) {
                    $relation_field[] = $vtmp2;
                }
                $vtmp3 = $vtmp. 'Map';
                if (in_array($vtmp3, $detail_names)) {
                    $relation_field[] = $vtmp3;
                }
            }
            $tmpVariant['relation_field'] = json_encode($relation_field);
            if (empty($id)) {
                $tmpVariant['create_time'] = time();
                $variantModel->insert($tmpVariant);
            } else {
                $variantModel->update($tmpVariant, ['id' => $id]);
                unset($oldIds[array_search($id, $oldIds)]);
            }
        }
        if (!empty($oldIds)) {
            //$variantModel->where(['id' => ['in', $oldIds]])->delete();
        }
    }


    public function buildTree($eles, $pid)
    {
        $datas = [];
        $sort = [];
        foreach ($eles as $key=>$val) {
            if ($pid == $val['pid']) {
                unset($eles[$key]);
                $datas[$val['name']] = $val->toArray();
                $sort[$val['name']] = $val['no'];
            }
        }

        asort($sort);
        $newDatas = [];
        foreach ($sort as $key=>$no) {
            $newDatas[$key] = $datas[$key];
        }

        foreach ($newDatas as $key=>$val) {
            $newDatas[$key]['childs'] = $this->buildTree($eles, $val['id']);
        }
        return $newDatas;
    }


    public function buildClassTree($tree)
    {
        $datas = [];
        $has_sub_class = true;
        if (!isset($tree['childs']['ProductType']) || empty($tree['childs']['ProductType']['childs'])) {
            return [$tree];
        }

        $new = $tree;
        $new['childs']['ProductType']['childs'] = [];
        foreach ($tree['childs']['ProductType']['childs'] as $key=>$val) {
            $tmp = $new;
            $tmp['childs']['ProductType']['childs'][$key] = $val;
            $datas[] = $tmp;
        }
        return $datas;
    }


    public function buildExcelClomn()
    {
        $base = range('A', 'Z');
        $datas = [];
        for ($a = -1; $a <= 0; $a++) {
            $av = $base[$a] ?? '';
            $d = ($a == -1)? -1 : 0;
            for ($b = $d; $b <= 25; $b++) {
                $bv = $base[$b] ?? '';
                for ($c = 0; $c <= 25; $c++) {
                    $cv = $base[$c] ?? '';
                    array_push($datas, $av. $bv. $cv);
                }
            }
        }
        return $datas;
    }


    public function readTemplateExcel($path)
    {
        $columns = $this->buildExcelClomn();
        $reader = IOFactory::load($path);
        $sheetCount = $reader->getSheetCount();
        $requireData = [];
        $validData = [];
        for ($i = 0; $i < $sheetCount; $i++) {
            $sheet = $reader->getSheet($i);
            $title = $sheet->getTitle();
            if ($title == 'Data Definitions') {
                $requireData = $this->getRequireData($sheet, $columns);
            } else if ($title == 'Valid Values') {
                $validData = $this->getValidData($sheet, $columns);
            }
        }
        unset($reader);

        return ['require' => $requireData, 'valid' => $validData];
    }

    public function getRequireData($sheet, $columns)
    {
        $clength = count($columns);
        $name = $sheet->getTitle();
        if (trim($name) !== 'Data Definitions') {
            throw new Exception('卡片title对应不上：'. $name);
        }
        $highestRow = $sheet->getHighestRow(); // 取得总行数
        $highestColumm = $sheet->getHighestColumn(); // 取得总列数

        $requireData = [];
        $fieldClomn = '';
        $labelClomn = '';
        $requireClomn = '';
        /** 循环读取每个单元格的数据 */
        for ($row = 1; $row <= 2; $row++)    //行号从1开始
        {
            for ($i = 0; $i < $clength; $i++)  //列数是以A列开始
            {
                $column = $columns[$i];
                $val = $sheet->getCell($column.$row)->getValue();
                if (trim($val) == 'Field Name') {
                    $fieldClomn = $column;
                }
                if (trim($val) == 'Local Label Name') {
                    $labelClomn = $column;
                }
                if (trim($val) == 'Required?') {
                    $requireClomn = $column;
                }
                if ($column == $highestColumm) {
                    break;
                }
            }
        }

        if (empty($fieldClomn) || empty($labelClomn) || empty($requireClomn)) {
            $requireData = [];
        } else {
            /** 循环读取每个单元格的数据 */
            for ($row = 3; $row <= $highestRow; $row++)    //行号从1开始
            {
                $fieldVal = $sheet->getCell($fieldClomn.$row)->getValue();
                $labelVal = $sheet->getCell($labelClomn.$row)->getValue();
                $requireVal = $sheet->getCell($requireClomn.$row)->getValue();
                if (empty($fieldVal) || empty($labelVal) || empty($requireVal)) {
                    continue;
                }

                //必须；
                if ($requireVal == 'Required') {
                    $requireData[] = $fieldVal;
                    $requireData[] = $labelVal;
                }

                //只取require模块，别的模块不要；
                if ($fieldVal == 'main_image_url' || $labelVal == 'Main Image URL') {
                    break;
                }
            }
        }

        $newData = [];
        foreach ($requireData as $val) {
            $newData[] = $this->ucwordsField($val);
        }
        $newData = array_unique($newData);
        return $newData;
    }


    public function ucwordsField($val) {
        $tmp = '';
        $len = strlen($val);
        for ($i = 0; $i < $len; $i++) {
            $is = $val{$i};
            if (in_array($is, ['0', '1', '2', '3', '4', '5', '6', '7' , '8', '9', '-', '('])) {
                break;
            }
            $tmp .= $is;
        }
        $tmp = str_replace('_', ' ', $tmp);
        $tmp = ucwords(strtolower($tmp));
        $tmp = str_replace(' ', '', $tmp);
        return $tmp;
    }


    public function getValidData($sheet, $columns)
    {
        $clength = count($columns);
        $name = $sheet->getTitle();
        if (trim($name) !== 'Valid Values') {
            throw new Exception('卡片title对应不上：'. $name);
        }
        $highestRow = $sheet->getHighestRow(); // 取得总行数
        $highestColumm = $sheet->getHighestColumn(); // 取得总列数

        $title = [];
        $title2 = [];
        $data = [];
        /** 循环读取每个单元格的数据 */
        for ($row = 1; $row <= $highestRow; $row++)    //行号从1开始
        {
            for ($i = 0; $i < $clength; $i++)  //列数是以A列开始
            {
                $column = $columns[$i];
                $val = $sheet->getCell($column.$row)->getValue();
                if ($row == 1) {
                    $title[$column] = $this->ucwordsField($val);
                } else if ($row == 2) {
                    $title2[$column] = $this->ucwordsField($val);
                } else {
                    if (!empty($val)) {
                        $data[$title[$column]][] = $val;
                        $data[$title2[$column]][] = $val;
                    }
                }
                if ($column == $highestColumm) {
                    break;
                }
            }
        }
        foreach ($data as &$val) {
            $val = array_unique($val);
        }
        unset($val);
        return $data;
    }
}
