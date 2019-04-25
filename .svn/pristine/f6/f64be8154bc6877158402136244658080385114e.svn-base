<?php
namespace app\publish\queue;

use app\common\model\amazon\AmazonCategoryRefinement;
use think\Db;
use app\common\exception\QueueException;
use app\common\service\SwooleQueueJob;
use app\common\cache\Cache;
use app\common\model\amazon\AmazonCategory as AmazonCategoryModel;
use think\Exception;

class AmazonBrowseTreeSaveQueuer extends  SwooleQueueJob
{
    private $accountCache;
    private $accountInfo;
    private $marketplace;

    private $model = null;
    private $rmodel = null;
    private $categoryCache = null;

    public function getName(): string {
        return 'amazon读取分类树文件并插入到数据表(队列)';
    }

    public function getDesc(): string {
        return 'amazon读取分类树文件并插入到数据表(队列)';
    }

    public function getAuthor(): string {
        return '冬';
    }

    public function init()
    {
        $this->accountCache = Cache::store('AmazonAccount');
    }

    public function execute()
    {
        set_time_limit(0);
        try {
            $job = $this->params;
            if ($job) {
                $path = $job['path'];
                $this->accountInfo = $this->accountCache->getTableRecord($job['account_id']);
                if (!file_exists($path)) {
                    throw new Exception($path . ' File not existed!');
                }
                $this->marketplace = $this->accountInfo['site'];
                $this->analyzeContent($path);
                return true;
            }
        } catch (Exception $exp) {
            throw new Exception($exp->getMessage() . $exp->getFile() . $exp->getLine());
        }
    }


    public function analyzeContent($path)
    {
        $this->model = new AmazonCategoryModel();
        $this->rmodel = new AmazonCategoryRefinement();
        $this->categoryCache = Cache::store('AmazonCategory');

        $result = simplexml_load_file($path, 'SimpleXMLElement');
        foreach ($result as $key=>$val) {
            $cid = $this->saveNode($val);
            $this->saveRefinementsInformation($val, $cid);
        }
        $this->updateNode($this->marketplace);
    }


    /**
     * 之前保存数据，parent_id数据可能有没更新到位的，这里重新更新一下；
     */
    private function updateNode($site) {
        $ids = $this->model->where(['site' => $site, 'parent_id' => 0])->column('path_id', 'id');
        if (empty($ids)) {
            return;
        }
        $update_marke = false;
        foreach($ids as $id=>$path_id) {
            $path_id_arr = explode(',', $path_id);
            if (count($path_id_arr) > 2) {
                //扔掉最后一个；
                array_pop($path_id_arr);
                $parent_path_id = implode(',', $path_id_arr);
                $parent_id = $this->categoryCache->getIdByPathId($site, $parent_path_id);
                if ($parent_id > 0) {
                    $update_marke = true;
                    $this->model->update(['parent_id' => $parent_id], ['id' => $id]);
                }
            }
        }
        //只要这一次又重新更新了，则会再次梳理一遍，直到本次一个没有更新为止；
        if ($update_marke) {
            $this->updateNode($site);
        }
    }


    private function saveNode($node)
    {
        //站点
        try {
            $data['site'] = $this->marketplace;

            $data['category_id'] = (int)$node->browseNodeId;
            $data['name'] = (string)$node->browseNodeName;
            $data['context_name'] = (string)$node->browseNodeStoreContextName;

            $data['path_id'] = (string)$node->browsePathById;
            $id = $this->categoryCache->getIdByPathId($data['site'], $data['path_id']);
            //根据站点和path_id去查询旧数据，如果存在，则说明是已经缓了了数据的，可以直接跳过；
            if ($id > 0) {
                return $id;
            }

            $data['path'] = (string)$node->browsePathByName;
            $data['attributes'] = $this->getNodeAttributes($node);
            $data['parent_id'] = 0;

            //找出parent_id;
            //先确定有几个path_id的个数，如只有两个，则些为根分类，不用进去了；
            $path_id_arr = explode(',', $data['path_id']);
            if (count($path_id_arr) > 2) {
                //扔掉最后一个；
                array_pop($path_id_arr);
                $parent_path_id = implode(',', $path_id_arr);
                $data['parent_id'] = $this->categoryCache->getIdByPathId($data['site'], $parent_path_id);
            }

            //个数；
            $data['child_count'] = 0;
            $data['child_ids'] = '[]';
            if ($node->hasChildren == "true" && !empty($node->childNodes->id)) {
                $data['child_count'] = (int)$node->childNodes['count'];
                $data['child_ids'] = json_encode($this->childIdToNumberArr($node->childNodes->id));
            }

            $data['feed_product_type'] = (string)$node->productTypeDefinitions;
            $data['create_time'] = time();

            $id = $this->model->insertGetId($data);

            if ($id > 0) {
                $this->categoryCache->savePathId($data['site'], $data['path_id'], $id);
                return $id;
            } else {
                throw new Exception('保存未成功,数据为：'. json_encode($data));
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }


    private function saveRefinementsInformation($node, $id)
    {
        try {
            //如果没有推荐信息部分，直接结束
            if (!isset($node->refinementsInformation) || (int)$node->refinementsInformation['count'] == 0) {
                return;
            }
            //$rmodel = new AmazonCategorycRefinement();
            //先格式化入库数据，然后再统一新增入库（多属性表，多属性值表）
            $arr = array();

            //当只有一条记录时是对象，多条时是数组
            if (is_object($node->refinementsInformation->refinementName)){
                $arr[0] = $node->refinementsInformation->refinementName;
            }else{
                $arr = $node->refinementsInformation->refinementName;
            }

            $data = [];
            //格式化入库数据
            foreach ($arr as $val){
                $refinementField = [];
                $refinementField['cid'] = $id;
                $refinementField['name'] = (string)$val['name'];
                $refinementField['attribute'] = (string)$val->refinementField->refinementAttribute;
                $refinementField['value'] = (string)$val->refinementField->acceptedValues;
                $data[] = $refinementField;
            }
            $this->rmodel->insertAll($data);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(). '|'. json_encode($data, JSON_UNESCAPED_UNICODE));
        }
    }


    public function childIdToNumberArr($arr)
    {
        $new = [];
        foreach ($arr as $id) {
            $id = intval($id);
            $new[] = $id;
        }
        return $new;
    }


    private function getNodeAttributes($node)
    {
        if ((string)$node->browseNodeAttributes['count'] == 0) {
            return '[]';
        }
        $attrs = [];
        foreach ($node->browseNodeAttributes->attribute as $attrNode) {
            $attrs[(string)$attrNode['name']] = (string)$attrNode;
        }
        return json_encode($attrs);
    }


    protected function convertToUtf8($string = '')
    {
        $encode = mb_detect_encoding($string, array("ISO-8859-1","ASCII","UTF-8","GB2312","GBK","BIG5"));
        if ($encode){
            $string = iconv($encode,"UTF-8",$string);
        }else{
            $string = iconv("UTF-8","UTF-8//IGNORE",$string);	//识别不了的编码就截断输出
        }
        return $string;
    }
}
