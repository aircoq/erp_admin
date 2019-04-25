<?php
/**
 * Created by PhpStorm.
 * User: zhangdongdong
 * Date: 2017/12/22
 * Time: 17:02
 */

namespace app\publish\service;

use think\Exception;
use app\common\model\joom\JoomTagSearch as JoomTagSearchModel;
use app\common\service\CommonQueuer;
use app\publish\queue\JoomTagBackSave;


class JoomTagSearchHelp
{
    public $userName = '';

    public $password = '';

    public $cookie = [];

    public $domain = 'www.actneed.com';

    public $ql = null;

    private $error = '';

    public function __construct($account=[])
    {
        $this->userName = isset($account['username'])?$account['username']:'13003915078';
        $this->password = isset($account['password'])?$account['password']:'JOOM123';
    }

    /**
     * @param string $keyword 关键字
     * @param int $site 站点
     * @param int $reSearch 重试次数；
     * @return array
     */
    public function query($keyword = '')
    {
        $time = time();
        $searchUrl = 'https://www.actneed.com/api/tags/search?q='. $keyword;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $searchUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $json = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($json, true);

        if(!isset($data['status']) && $data['status'] != 1) {
            $this->error = '程序出错';
            return false;
        }

        $result = [
            'keyword' => $keyword,
            'tags' => $data['tags']
        ];
//        $JoomTagSearchModel = new JoomTagSearchModel();
//        $JoomTagSearchModel->updateTags($result);
        (new CommonQueuer(JoomTagBackSave::class))->push($result);
        return $result;
    }

    public function getError ()
    {
        return $this->error;
    }

}