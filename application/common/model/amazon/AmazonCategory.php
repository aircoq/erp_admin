<?php
namespace app\common\model\amazon;

use think\Model;
use think\Loader;
use think\Db;

class AmazonCategory extends Model
{
    /**
     * 初始化
     * @return [type] [description]
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }

    /**
     * 关系
     * @return [type] [description]
     */
    public function role()
    {
        //一对一的关系，一个订单对应一个商品
        return $this->belongsTo('WishPlatformOnlineGoods');
    }

    /**
     * 新增分类 
     * @param array $data [description]
     */
    public function addCategory($res,$siteId)
    {   
        if (empty($res)) {
            return false;
        }
        $masterTable  = "amazon_category";
        if(!empty($res['Category'])){
            foreach($res['Category'] as $val){
                $data = array(
                    'category_id'                 => $val['CategoryID'],
                    'category_level'              => $val['CategoryLevel'],
                    'category_name'               => $val['CategoryName'],
                    'category_parent_id'          => $val['CategoryParentID'],
                    'platform'                    => 'ebay',
                    'site'                        => $siteId
                );
                
                if (!empty($data)) {                    
                    try {
                        $res = Db::name($masterTable)->insert($data);
                    } catch (\Exception $e) {
                        echo $e;
                    }
                }
            }
                       
        }
        return true;
    }
    
    /**
     * 取分类
     * @param unknown $res
     * @param unknown $siteId
     * @param unknown $categoryID
     */
    public function getCategory($siteId,$categoryID)
    {
        $category = [];
        if (empty($siteId)) {
            return false;
        }
    
        if(!empty($categoryID)){
            $list   = $this->all(['category_id'=> $categoryID ,'site'=>$siteId]);
        } else {
            $list   = $this->all(['site'=>$siteId]);
        }
        foreach($list as $k=>$v){
            if(!empty($v->category_name)) {
                $category[$siteId][$v->category_id]= $v;
            }
    
        }
        return $category;
    }
    
    /**
     * 新增分类属性
     * @param unknown $res
     * @param unknown $siteId
     * @return boolean
     */
    public function addCategorySpecific($res,$siteId,$categoryID)
    {  
        set_time_limit(0);
        if (empty($res)) {
            return false;
        }
        $masterTable  = "amazon_category_specific";
    	if (!empty($res)) {     		    
    			foreach ($res['NameRecommendation'] as $val) {		
    				$category_specific_name = $val['Name'] ;
    				$category_specific_value = "";
    				if (isset($val['ValueRecommendation']) && !empty($val['ValueRecommendation'])) {
    					unset($category_specific_values);
	    				foreach($val['ValueRecommendation'] as $v){
	    					$category_specific_values[] = $v['Value'];
	    					unset($v);
	    				}
	    				$category_specific_value = json_encode($category_specific_values);
    				}

    				$data = array(
        						'category_id'               => $categoryID,
        						'category_specific_name'    => !empty($category_specific_name)?$category_specific_name:'',
        						'category_specific_value'   => $category_specific_value,
        						'platform'                  => 'ebay',
        						'site'                      => $siteId    						
    				        );

    				if (!empty($data)) {
    				    try {
    					   $res = Db::name($masterTable)->insert($data);
    				    } catch (\Exception $e) {                        
                           echo $e;
                        }
    				}
    				unset($val);
    			}
    	}
        return true;
    }

    /**
     * 批量新增
     * @param array $data [description]
     */
    public function addAll(array $data)
    {
        foreach ($data as $key => $value) {
            $this->add($value);
        }
    }
    
    
    /**
     * 修改订单
     * @param  array $data [description]
     * @return [type]       [description]
     */
    public function edit(array $data, array $where)
    {
        return $this->allowField(true)->save($data, $where);
    }

    /**
     * 批量修改
     * @param  array $data [description]
     * @return [type]       [description]
     */
    public function editAll(array $data)
    {
        return $this->save($data);
    }

    /**
     * 检查订单是否存在
     * @return [type] [description]
     */
    protected function checkorder(array $data)
    {
        $result = $this->get($data);
        if (!empty($result)) {
            return true;
        }
        return false;
    }
}