<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\ebay\EbaySite as EbaySiteModel;

class EbaySite extends Cache
{
    private $key = 'table:ebay_site'; 
    
    /**
     * 获取ebay站点
     * @return $trees: ebay站点
     */       
    public function getAllSites()
    {
        if ($this->redis->exists($this->key)) {
            $result = $this->redis->hGetAll($this->key);
            return $result ?? [];
        }
        $result = (new EbaySiteModel)->field(true)->select();
        foreach($result as $site) {
            $this->redis->hSet($this->key, $site['siteid'], json_encode($site, JSON_UNESCAPED_UNICODE));
        }
        
        return $result;
    }
    
    public function getSiteInfoByCode($code, $field = 'abbreviation')
    {
        $sites = $this->getAllSites();
        foreach($sites as $site) {
            $site = json_decode($site, true);
            if ($site[$field] != $code) {
                continue;
            }
            return $site;
        }
        
        return [];
    }
    
    public function getSiteInfoBySiteId($siteid)
    {
        if (!$this->redis->exists($this->key)) {
            $this->getAllSites();
        }
        $info = json_decode($this->redis->hGet($this->key, $siteid), true);
        return $info ? $info : [];
    }
}