<?php
namespace app\common\service;
use think\Loader;
use app\common\exception\JsonErrorException;
use think\Exception;

/**
 * Class ImportXsd
 * @package app\common\service
 */
class ImportXsd
{
    /**
     * 上传文件
     * @param Request $request
     * @param type $pathName
     * @param type $fileName
     * @return string
     * @throws Exception
     */
    public function uploadFile($baseData, $pathName)
    {

        if (!$baseData) {
            throw new JsonErrorException('未检测到文件');
        }
        $dir = date('Y-m-d');
        $base_path = ROOT_PATH . 'public' . DS . 'upload' . DS . $pathName . DS . $dir;

        if (!is_dir($base_path) && !mkdir($base_path, 0777, true)) {
            throw new JsonErrorException('目录创建失败');
        }

        try {
            $fileName = $pathName . date('YmdHis') . '.xsd';
            $start = strpos($baseData, ',');
            $content = substr($baseData, $start + 1);
            file_put_contents($base_path . DS . $fileName, base64_decode(str_replace(" ", "+", $content)));
            return $base_path . DS . $fileName;
        } catch (Exception $ex) {
            throw new JsonErrorException($ex->getMessage());
        }
    }


    /**解析得到XSD对应的所有节点信息
     * @param $filePath
     * @return mixed
     */
    public function getXsdNodes($filePath){
        $docObj = new \DOMDocument();
        $docObj->preserveWhiteSpace = true;
        $docObj->load($filePath);
        $xmlPath = str_replace("xsd","xml",$filePath);
        $docObj->save($xmlPath);
        $xmlfile = file_get_contents($xmlPath);
        $content = str_replace($docObj->lastChild->prefix.':',"",$xmlfile);
        $xmlNodes = simplexml_load_string($content);
        $jsonData  = json_encode($xmlNodes);
        return json_decode($jsonData, true);
    }

}