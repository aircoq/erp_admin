<?php
namespace app\index\controller;

use think\Request;
class  Index
{
    private $doc = NULL;
    private $root = NULL;
    
    public function __construct()
    {
        $this->doc= new \DOMDocument('1.0','UTF-8');
        $this->doc->formatOutput = true;
        $this->root = $this->doc->createElement('request');
        $this->root = $this->doc->appendChild($this->root);
    }
    
    public function index()
    {
        $request = Request::instance();
        $params  = $request->param();
        $input   = $request->getInput();
        //file_put_contents("/tmp/ebay.log",print_r($input,true),FILE_APPEND);
        
        //$xml = simplexml_load_file($input);
        //$data = json_decode(json_encode($input),TRUE);
        //file_put_contents("/tmp/ebay.log",print_r($request,true),FILE_APPEND);
        
        $clean_xml = str_ireplace(['soapenv:', 'SOAP:'], '', $input);
        $data      = simplexml_load_string($clean_xml);
         
        
        //file_put_contents("/tmp/ebay.log",print_r($data,true),FILE_APPEND);
        echo '200';
        
    }
    /*
    *@title ceshi
    */
    public function test(Request $request)
    {
        $sf = new SfExpress();
        $result = $sf->commitOrder();
        echo $result;

    }
    
}

?>