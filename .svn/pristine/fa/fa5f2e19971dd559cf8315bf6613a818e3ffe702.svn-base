<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\publish\service\AmazonElement;
use think\Exception;

class AmazonTemplate extends Cache
{
    /** @var string xsd缓存key */
    private $hashPrefix = 'task:amazon:template-sequence:';

    /** @var string xml缓存KEY */
    private $xmlHashPrefix = 'task:amazon:xml:';

    /** @var string xml缓存KEY */
    private $templateHashPrefix = 'task:amazon:template-attribute';

    /**
     * 产品模板元素json存储；
     */
    public function getProductSequence()
    {
        $key = $this->hashPrefix. 'product';
        $data = $this->redis->get($key);
        //缓存存在则直接反回；
        if ($data) {
            return json_decode($data, true);
        }

        $templateModel = new AmazonElement();
        $data = $templateModel->getProductSequnce();
        if($this->redis->set($key, json_encode($data, JSON_UNESCAPED_UNICODE))) {
            return $data;
        }

        return false;
    }

    /**
     * 分类模板元素json存储；
     * @param $class_type_id
     */
    public function getCategorySequence($site, $class_type_id)
    {
        $typeArr = explode(',', $class_type_id);
        //pid为大分类，cid为小分类；
        if(count($typeArr) == 2) {
            $pid = trim($typeArr[0]);
            $cid = trim($typeArr[1]);
            $cid = is_numeric($cid)? $cid : 0;
        } else {
            $pid = trim($typeArr[0]);
            $cid = 0;
        }
        $key = $this->hashPrefix. 'site_'. $site;
        $hashkey = 'class_type_id_'. $pid. '_'. $cid;

        $data = $this->redis->hget($key, $hashkey);
        //缓存存在则直接反回；
        if ($data) {
            return json_decode($data, true);
        }

        $templateModel = new AmazonElement();
        $data = $templateModel->getCategorySequence($pid, $cid);

        if($this->redis->hSet($key, $hashkey, json_encode($data, JSON_UNESCAPED_UNICODE))) {
            return $data;
        }

        return false;
    }

    /**
     * 刊登时保存帐号-类别的xml
     * @param $product_id 刊登记录ID
     * @param $code 帐号简称
     * @param $type xml类别
     * @param $xml 刊登的XML
     * @return bool
     */
    public function savePublishXml($sub_id, $xml)
    {
        $this->redis->hSet($this->xmlHashPrefix. floor($sub_id/10000), $sub_id, $xml);
        $this->redis->expire($this->xmlHashPrefix. floor($sub_id/10000), time() + 60 * 60 * 48);
        return true;
    }


    /**
     * 保存模板数据；
     * @param $template_id
     * @param array $data
     * @throws Exception
     */
    public function setTemplateAttr($template_id, Array $data = [])
    {
        if (empty($template_id)) {
            throw new Exception('模板ID为空');
        }
        //空数组则删除；
        if (empty($data)) {
            $this->redis->hDel($this->templateHashPrefix, $template_id);
        } else {
            $json = json_encode($data, JSON_UNESCAPED_UNICODE);
            $this->redis->hSet($this->templateHashPrefix, $template_id, $json);
        }

        return true;
    }


    /**
     * 拿取模板数据；
     * @param $template_id
     * @param array $data
     * @throws Exception
     */
    public function getTemplateAttr($template_id)
    {
        if (empty($template_id)) {
            throw new Exception('模板ID为空');
        }
        $json = $this->redis->hGet($this->templateHashPrefix, $template_id);
        $data = json_decode($json, true);
        if (is_array($data)) {
            return $data;
        }
        return [];
    }
    
}
