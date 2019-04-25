<?php
/**
 * Created by ZendStudio.
 * User: hot-zr
 * Date: 2017年5月6日 
 * Time: 下午11:33:03
 */

namespace app\goods\service;
use app\common\model\Goods;
use app\common\model\GoodsGallery;
use org\Curl;
use app\common\model\AttributeValue;


class UploadHelp
{
    public function goodImageByZip($arrData)
    {
        //Compliance type
        $arrImageType = ['.jpg','.png','.gif','.png','.jpeg'];
        //success file
        $arrSuccess = [];
        //error file
        $arrError = [];
        $GoodsGallery = new GoodsGallery();
        foreach($arrData as $arrFile)
        {
            $strName = $arrFile['name'];
            $strType = strrchr($strName, '.');
            if(empty($strName) ||  strtolower($strType) !='.zip')
            {
                $arrError[] = $strName;
                continue;
            }
            $strSpu = strtok($strName, '.');
            $Goods = Goods::get(['spu'=>$strSpu]);
            if($Goods==NULL)
            {
                $arrError[] = $strName;
                continue;
            }
            //create file name
            $strSpuFile = implode('/',str_split($Goods->id,3)).'/';
            $strFile = ROOT_PATH.'public'.DS.'upload'.DS.$strSpuFile;
        
            @mkdir($strFile,0777,true);
            if(!file_exists($strFile))
            {
                $arrError[] = $strName;
                continue;
            }
            //mv
            $strTemp = 'temp/tmp'.md5($strName.time());
            $strTempFile = $strTemp.DS.$strSpu.$strType;
            if(!mkdir($strTemp,0777,true))
            {
                @removeDir($strTemp);
                $arrError[] = $strName;
                continue;
            }
            if(isset($arrFile['is_urls']))
            {
                $boolResult = Curl::downFileToFlow($arrFile['tmp_name'], $strTempFile);
            }
            else
            {
                $boolResult = move_uploaded_file($arrFile['tmp_name'], $strTempFile);
        
            }
            if(!$boolResult)
            {
                @removeDir($strTemp);
                $arrError[] = $strName;
                continue;
            }
            //unzip
            $strUnzipTemp = 'temp/tmpunzip'.md5($strName.time());
            if(!mkdir($strUnzipTemp,0777,true) || !$this->unZip($strTempFile,$strUnzipTemp))
            {
                @removeDir($strTemp);
                @removeDir($strUnzipTemp);
                $arrError[] = $strName;
                continue;
            }

            //insert
            $arrImages = scandir($strUnzipTemp);
            foreach ($arrImages as $strAttrValue)
            {
                if(is_numeric(strpos($strAttrValue, '.')))continue;
                $objAttributeValue = AttributeValue::get(function($query) use($strAttrValue){
                    $query->field('id as value_id,attribute_id')->where('value',$strAttrValue)->fetchSql(false);
                });
                if($objAttributeValue == NUll)continue;
                $arrAttributeValue = $objAttributeValue->toArray();
                $arrGalleryData = [];
                $arrAttrImages = scandir($strUnzipTemp.DS.$strAttrValue);
                foreach ($arrAttrImages as $value)
                {
                    $strImageSrc = $strUnzipTemp.DS.$strAttrValue.DS.$value;
                    if(!in_array(strtolower(strrchr($value, '.')),$arrImageType))continue;
                    // md5(md5_file . good_id) is unqiue
                    $strMd5 = md5_file($strImageSrc).$Goods->id;
                    $strMd5 = md5($strMd5);
                
                    $strImageFile = create_uuid($strMd5).strrchr($value, '.');
                    if(!file_exists($strFile.$strImageFile))
                    {
                        rename($strImageSrc,$strFile.$strImageFile);
                    }
                    $arrGalleryData= [
                        'goods_id'     => $Goods->id,
                        'path'         => $strSpuFile.$strImageFile,
                    ];
                    $arrGalleryData = array_merge($arrAttributeValue,$arrGalleryData);
                    $GoodsGallery->where($arrGalleryData)->find() || $GoodsGallery->isUpdate(false)->insert($arrGalleryData);
                }
            }
            @removeDir($strTemp);
            @removeDir($strUnzipTemp);
            if(!empty($arrGalleryData)) $arrSuccess[] = $strName;
        }
        return count($arrSuccess);
    }
    
    public function unZip($strTempFile,$strUnzipTemp)
    {
        $Zip = new \ZipArchive();
    	if(!$Zip->open($strTempFile))
    	{
    		$Zip->close();
    		return false;
    	}
// On Windows select this code     	
//     	$docnum = $Zip->numFiles;
//     	for($i = 0; $i < $docnum; $i++) 
//     	{
//     		$statInfo = $Zip->statIndex($i);
//     		if($statInfo['crc'] == 0) 
//     		{
//     			//新建目录
//     			@mkdir($strUnzipTemp.DS.substr($statInfo['name'], 0,-1));
//     		}
//     		else 
//     		{
//     			//拷贝文件
//     			copy('zip://'.$strTempFile.'#'.$statInfo['name'], $strUnzipTemp.DS.$statInfo['name']);
//     		}
//     	}
//   	
// On Linux select this code 
    	$Zip->extractTo($strUnzipTemp);
    	
        $Zip->close();
        return true;
    }
}