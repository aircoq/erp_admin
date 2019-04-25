<?php



//日志记录
function logger($log_content)
{

    $max_size = 100000;   //声明日志的最大尺寸

    $log_filename = "data.log";  //日志名称

    //如果文件存在并且大于了规定的最大尺寸就删除了
    if(file_exists($log_filename) && (abs(filesize($log_filename)) > $max_size)){
        unlink($log_filename);
    }

    //写入日志，内容前加上时间， 后面加上换行， 以追加的方式写入
    file_put_contents($log_filename, date('H:i:s')." ".$log_content."\n", FILE_APPEND);
}



