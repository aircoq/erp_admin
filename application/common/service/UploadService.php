<?php
namespace app\common\service;
/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2017/5/3
 * Time: 10:30
 */
class UploadService
{
    protected $_setting = [];   //系统配置信息
    protected $_errors = null;  //错误信息
    protected $_tmp_name = '';   //临时文件
    protected $_real_name = '';  //原始文件名
    protected $_file_name = '';  //生产的文件完整路径
    protected $_thumb_name = ''; //生成缩略图的完整路径
    protected $_image_ext = '';  //图片扩展名
    protected $_image_size = '';  //图片大小
    protected $_file_ext = '';    //文件扩展名
    protected $_mime_type = '';   //MIME类型
    protected $_is_image = false; //是不是图片
    protected $_rand_name = true;  //是否生成随机文件名
    protected $_s_thumb_prefix = '';  //缩略图前缀
    protected $_l_thumb_prefix = '';  //大图图前缀
    protected $_image_path = '/images/';  //默认图片上传路径
    protected $_thumb_path = '/thumb/';  //默认缩略图上传路径
    public $_file_path = '/file/';   //默认文件上传路径
    protected $_upload_path = '';  //上传路径
    public $_file_name_all = [];

    /** 初始化
     * Upload constructor.
     */
    public function __construct()
    {
        $this->_setting = [];  //读取配置文件，这里没有，先默认为空
        if(empty($this->_setting)){
            $this->_errors = '请先配置文件上传设置';
            return false;
        }
        $this->_file_ext = explode(',',$this->_setting['file_ext']);
        $this->_image_ext = explode(',',$this->_setting['image_ext']);
        $this->_image_size =$this->_setting['image_size'];
        $this->_s_thumb_prefix = 's';
        $this->_l_thumb_prefix = 'l';
        return true;
    }

    /** 对象形式上传文件
     * @param $file
     * @param bool|false $small_thumb
     * @param int $type
     * @return bool
     */
    public function uploadByObject($file,$small_thumb = false,$type = 1)
    {
        if(!empty($this->_errors)) return false;
        if(!$this->checkUpload($file,$type)){
            return false;
        }
        $tmp_name = $file['tmp_name'];
        $show_path = ($this->_is_image === true) ? $this->_image_path : $this->_file_path;
        $show_path .= date('Ymd',time()) . '/';
        $save_path = ROOT_PATH.$this->_upload_path.$show_path;
        $fileName = $this->_rand_name ? substr(md5(uniqid('file')),0,11).'.'.$this->getExt($file['name']) : $file['name'];
        if(!is_dir($save_path)){
            mkdir($save_path,0777,true);
        }
        $save_path .= $fileName;
        $this->_file_name = $this->_setting['upload_domain'].$show_path.$fileName;
        $mv = move_uploaded_file($tmp_name,$save_path);
        if(!$mv){
            $this->_errors = '移动文件失败';
            return false;
        }
        return true;
    }

    /** 数据流形式上传图片
     * @param $fileData
     * @return bool
     */
    public function uploadByFlow($fileData)
    {
        if(is_array($fileData))
        {
            $show_path =  $this->_image_path.'/';
            $show_path .= date('Ymd', time()) . '/';
            $save_path  = ROOT_PATH.'../upload'.$show_path;
            if(!is_dir($save_path))
            {
                mkdir($save_path, 0777, true);
            }
            $count = 0;
            foreach ($fileData as $k => $v)
            {
                $str = substr($v, 0,strpos($v, ';'));
                $s = base64_decode(str_replace($str.';base64,', '', $v));
                if (substr_count($str,'png')>0){
                    $this->_file_ext = 'png';
                }elseif (substr_count($str,'jpeg')>0){
                    $this->_file_ext = 'jpeg';
                }else {
                    $this->_file_ext = 'jpeg';
                }
                $filename = $this->_rand_name ? substr(md5(uniqid('file')), 0,11).'.'.$this->_file_ext : $this->_real_name;
                if(file_put_contents($save_path.$filename, $s)>0)
                {
                    $this->_file_name_all[$k] = $this->_setting['upload_domain'].$show_path.$filename;
                    $count++;
                }
            }
            if(count($fileData) == $count)
            {
                return true;
            }else
            {
                $this->_file_name_all = [];
                return false;
            }
        }
        return false;
    }

    /** 校验上传文件是否符合要求(包括文件类型、大小)
     * @param $file 【文件名】
     * @param $type  【类型】
     * @return bool
     */
    public function checkUpload($file,$type)
    {
        if(!$file || $file['error'] != UPLOAD_ERR_OK){
            $this->_errors = '文件上传失败（'.$file['error'].'）';
            return false;
        }
        $file_ext = $this->getExt($file['name']);
        if($type == '1'){
            if(in_array($file_ext,$this->_image_ext)){
                $this->_is_image = true;
            }
            if(!in_array($file_ext,$this->_image_ext)){
                $this->_errors = '禁止上传{$file_ext}后缀的文件';
                return false;
            }
            if($file['size'] > $this->_image_size * 1024){
                $this->_errors = '上传图片大小超出限制';
                return false;
            }
        }else{
            if(in_array($file_ext,$this->_file_ext)){
                $this->_is_image = false;
            }
            if(!in_array($file_ext,$this->_file_ext)){
                $this->_errors = '禁止上传{$file_ext}后缀的文件';
                return false;
            }
        }
        if(!is_uploaded_file($file['tmp_name'])){
            $this->_errors = '系统临时文件错误';
            return false;
        }
        return true;
    }

    /** 取得上传文件的后缀名
     * @param $realName  【文件名】
     * @return string
     */
    private function getExt($realName)
    {
        $pathInfo = pathinfo($realName);
        return trim(strtolower($pathInfo['extension']));
    }
}