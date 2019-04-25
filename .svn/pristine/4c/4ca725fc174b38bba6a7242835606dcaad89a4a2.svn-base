<?php

namespace app\customerservice\service;


class ContentExtraction{


    public function contentExtraction($country, $box_id, $html){

        $html = $this->url_filter($html);
        $result=$html;
        if ($box_id == 2){

            switch (strtoupper($country)){
                case 'COM':
                case 'UK':
                case 'CA':
                    $result = $this->checkRegular_us($html);
                    break;
                case 'DE':
                    $result = $this->checkRegular_de($html);
                    break;
                case 'IT':
                    $result = $this->checkRegular_it($html);
                    break;
                case 'FR':
                    $result = $this->checkRegular_fr($html);
                    break;
                case 'ES':
                    $result = $this->checkRegular_es($html);
                    break;
                case 'MX':
                    $result = $this->checkRegular_mx($html);
                    break;
                case 'JP':
                    $result = $this->checkRegular_jp($html);
                    break;
                default:
                    break;
            }
        }

        return $result;
    }

    /*
     * 去掉img标签的链接
     */
    public function url_filter($html){
        $str = str_replace('alt=','',$html);
        return str_replace('src=','src="" alt=',$str);
    }

    /*
     * 正则过滤US，UK，CA站点
     * @param $html
     */
    public function checkRegular_us($html){

        $patten1 = '/(?<=(?:<body))([\s\S]*?)(?=(?:<center))/';
        $patten2 = '/<pre(([\s\S])*?)<\/pre>/';
        $patten3 = '/(?<=------------- Begin message -------------)([\s\S]*?)(?=------------- End message -------------)/';
        $patten4 = '/(?<=>)([\s\S]*?)(?=<\/pre>)/';

        if (preg_match_all($patten1, $html,$match_first)){
            if (isset($match_first[0][1]))
            {
                return trim($match_first[0][1]);
                
            }else if (preg_match($patten2, $html,$match_second)){
                return $match_second[0];
            }else if (preg_match($patten3, $html,$match_third)){
                return $match_third[0];
            }
            else{
                return $html;
            }
        }else if (preg_match($patten2, $html,$match_second)){
            return $match_second[0];
//            if ( preg_match($patten2, $match_first[0],$match_second) ) {
//                return $match_second[0];
//            }else{
//                return $html;
//            }
        }else if (preg_match($patten3, $html,$match_third)){
            return $match_third[0];
        }
        else{
            return $html;
        }
    }

    /*
     * 正则过滤DE站点
     * @param $html
     */
    public function checkRegular_de($html){

        $patten1 = '/(?<=------------- Anfang der Nachricht -------------)([\s\S]*?)(?=------------- Ende der Nachricht -------------)/';
        $patten2 = '/(?<=<span style="font-size: 11.0pt;">)([\s\S]*?)(?=<\/span>)/';
        $patten3 = '/(?<=<div dir="auto">)([\s\S]*?)(?=<\/div>)/';

        if ((preg_match($patten1, $html,$match))){
            return $match[0];
        }else if(preg_match($patten2, $html,$match)) {
            return $match[0];
        }else if (preg_match($patten3, $html,$match)) {
            return $match[0];
        }else{
            return $html;
        }
    }

    /*
     * 正则过滤IT站点
     * @param $html
     */
    public function checkRegular_it($html){

        $patten1 = '/(?<=------------- Inizio messaggio -------------)([\s\S]*?)(?=------------- Fine messaggio -------------)/';
        $patten2 = '/(?<=<body)([\s\S]*?)(?=<blockquote)/';

        if (preg_match($patten1, $html,$match)){
            return $match[0];
        }else if(preg_match($patten2, $html,$match_first)){
            if(preg_match('/(?<=>)([\s\S]*)/',$match_first[0],$match)){
                return $match[0];
            }else{
                return $html;
            }

        }else{
            return $html;
        }
    }

    /*
     * 正则过滤FR站点
     * @param $html
     */
    public function checkRegular_fr($html){

        $patten1 = '/(?<=------------- DÃ©but du message -------------)([\s\S]*?)(?=------------- Fin du message -------------)/';
        $patten2 = '/(?<=------------- Début du message -------------)([\s\S]*?)(?=------------- Fin du message -------------)/';

        if (preg_match($patten1, $html,$match)){
            return $match[0];
        }else if (preg_match($patten2, $html,$match)){
            return $match[0];
        }else{
            return $html;
        }
    }

    /*
     * 正则过滤ES站点
     * @param $html
     */
    public function checkRegular_es($html){

        $patten1 = '/(?<=------------- Iniciar mensaje -------------)([\s\S]*?)(?=------------- Finalizar mensaje -------------)/';

        if ((preg_match($patten1, $html,$match))){
            return $match[0];
        }else{
            return $html;
        }
    }

    /*
     * 正则过滤MX站点
     * @param $html
     */
    public function checkRegular_mx($html){

        $patten1 = '/(?<=------------- Iniciar mensaje -------------)([\s\S]*?)(?=------------- Finalizar mensaje -------------)/';

        if ((preg_match($patten1, $html,$match))){
            return $match[0];
        }else{
            return $html;
        }
    }

    /*
     * 正则过滤JP站点
     * @param $html
     */
    public function checkRegular_jp($html){

        $patten1 = '/(?<=------------- メッセージはここから -------------)([\s\S]*?)(?=------------- メッセージはここまで -------------)/';

        if ((preg_match($patten1, $html, $match))){
            return $match[0];
        }else{
            return $html;
        }
    }


}