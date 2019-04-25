<?php
namespace app\publish\service;
class FieldAdjustHelper
{
    static public $UTH='underlineToHump';
    static public $HTU='humpToUnderline';
    static public $role = [
        'publish'=>[
            'imgs'=>'imageurls',
            'title'=>'subject',
        ],
    ];
    
    static public function humpToUnderline($str = '')
    {
        $strTemp = '';
        for($i=0;$i<strlen($str);$i++)
        {
            if($str{$i} != strtolower($str{$i}))
            {
                 $strTemp.='_';
            }
            $strTemp .=strtolower($str{$i});
        }
        return $strTemp;
    }
    
    static public function underlineToHump($str = '')
    {
        for($i=0;$i<strlen($str);$i++)
        {
            if($str{$i} == '_')
            {
                $str{++$i} = strtoupper($str{$i});
            }
        }
        return lcfirst(str_replace('_','',$str));
    }
    
    static public function adjust($res,$strRole,$strCase = '',$intIndex=1)
    {
        if(is_array($res))
        {
            return self::adjustArray($res,$strRole,$strCase,$intIndex);
        }
        
        if(is_object($res))
        {
            //Temprorarily do not write
            return $res;
        }
        
    }
    
    static public function adjustArray($res,$strRole,$strCase = '',$intIndex=1)
    {
        if(!empty($strCase) && isset(self::$$strCase))
        {
            $strCaseRole= self::$$strCase;
        }
        if((int)$intIndex==1)
        {
            foreach($res as $k=>$v)
            {
                if(!empty($strRole) && isset(self::$role[$strRole]) && array_key_exists($k, self::$role[$strRole]))
                {
                    $res[self::$role[$strRole][$k]] = $v;
                    unset($res[$k]);
                    $k=self::$role[$strRole][$k];
                }
                if(isset($strCaseRole))
                {
                    if($k == self::$strCaseRole($k))continue;
                    $res[self::$strCaseRole($k)] = $v;
                    unset($res[$k]);
                    $k=self::$strCaseRole($k);
                }
            }
        }
        else 
        {
            foreach($res as $k=>$v)
            {
                if(isset($strCaseRole))
                {
                    if($k == self::$strCaseRole($k))continue;
                    $res[self::$strCaseRole($k)] = $v;
                    unset($res[$k]);
                    $k=self::$strCaseRole($k);
                }
                if(!empty($strRole) && isset(self::$role[$strRole]) && array_key_exists($k, self::$role[$strRole]))
                {
                    $res[self::$role[$strRole][$k]] = $v;
                    unset($res[$k]);
                    $k=self::$role[$strRole][$k];
                }
            }
        }
        return $res;
    }
}
