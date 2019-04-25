<?php
// +----------------------------------------------------------------------
// | 
// +----------------------------------------------------------------------
// | File  : AmazonEmailHelper.php
// +----------------------------------------------------------------------
// | Author: LiuLianSen <3024046831@qq.com>
// +----------------------------------------------------------------------
// | Date  : 2017-07-27
// +----------------------------------------------------------------------

namespace app\customerservice\service;


class AmazonEmailHelper
{
    /**
     * @param $data
     * @param string $fileName
     * @return string
     */
    static public function saveFile($data,$fileName)
    {
        $headLength = strpos($data,',');
        $headInfo   = substring($data,0,$headLength);
        $fileData   = base64_decode(substring($data,$headLength+1));
        if(strpos($headInfo,'plain')){
            $encodeList = array('GBK','GB2312','UTF-8');
            $encode = mb_detect_encoding($fileData,$encodeList );
            if($encode != 'UTF-8'){
                if(!in_array($encode,$encodeList)){
                    $encode = 'GBK';
                }
                $fileData = mb_convert_encoding($fileData,'UTF-8',$encode);
            }
        }
//
//        preg_match('/data:(?P<type>.*);/',$headInfo,$match);
//        switch ($match['type']){
//            case 'text/plain':
//                $suffix = '.txt';
//                break;
//            case 'image/jpeg':
//                $suffix = '.jpeg';
//                break;
//            case 'application/pdf':
//                $suffix = '.pdf';
//                break;
//            case 'application/vnd.ms-excel':
//            case 'application/x-xls':
//                $suffix = '.xls';
//                break;
//            case 'application/msword':
//                $suffix = '.doc';
//                break;
//            case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
//                $suffix = '.xlsx';
//                break;
//            default: $suffix = '';
//        }
//        if(substring($saveFileDir,strlen($saveFileDir)-1)== DIRECTORY_SEPARATOR){
//            $fileName = $saveFileDir.$baseFileName.''.$suffix;
//        }else{
//            $fileName = $saveFileDir.$baseFileName.DIRECTORY_SEPARATOR.$suffix;
//        }
        file_put_contents($fileName,$fileData);
        return true;
    }

}