<?php
namespace app\goods\controller;

use app\common\controller\Base;
use think\Request;
use app\goods\service\UploadHelp;

class Upload extends Base
{
    /**
     * @node 产品图片上传（ZIP包）
     * @author hot-zr
     * @throws \Exception
     */
    public function goodImageByZip()
    {
        try 
        {
            $strUrls = Request::instance()->post('urls');
            if(count($_FILES)==0 && empty($strUrls)) throw new \Exception ('最少上传一个ZIP文件！');
            //urls to upload
            $arrUrls = explode(';',$strUrls);
            $arrUrls = array_unique($arrUrls);
            foreach ($arrUrls as $v)
            {
                $strTempName =  basename($v);
                if(!empty($v) && !empty($strTempName))
                {
                    $_FILES[] = ['name'=>$strTempName,'tmp_name'=>$v,'is_urls'=>1];
                }
            }
            $UploadHelp = new UploadHelp();
            return json($UploadHelp->goodImageByZip($_FILES));
        }
        catch (\Exception $e)
        {
            return json($e->getMessage(),400);
        }
    }
    
    
}