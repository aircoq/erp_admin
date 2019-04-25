<?php
namespace app\common\model\ebay;

use think\Model;
use think\Loader;
use think\Db;

class EbayCategorySpecific extends Model
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
        $masterTable  = "ebay_category_specific";
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
     * 取分类属性
     * @param unknown $res
     * @param unknown $siteId
     * @param unknown $categoryID
     */
    public function getCategorySpecific($siteId,$categoryID)
    {   
        $attrs = [];
        if (empty($siteId)) {
            return false;
        }
        
        if(!empty($categoryID)){
            $list   = $this->all(['category_id'=> $categoryID ,'site'=>$siteId]);
        } else {
            $list   = $this->all(['site'=>$siteId]);
        }
        foreach($list as $k=>$v){
            if(!empty($v->category_specific_name)) {
                $attrs[$siteId][$v->category_id][$v->category_specific_name] = $v->category_specific_value;
            }
            
        }
        return $attrs;
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

    /**
     * 同步类目属性
     * @return [type] [description]
     */
    public function syncSpecifics(array $data)
    {
        $wh['category_id']=$data['category_id'];
        $wh['category_specific_name']=$data['category_specific_name'];
        $wh['site']=$data['site'];
        $rows=$this->get($wh);
        if($rows){#更新
            $this->save($data,array('id'=>$rows->id));
            return $rows->id;
        }else{#添加
            return $this->insertGetId($data);
        }
    }

    /**
     * 同步类目属性详情表
     * @return [type] [description]
     */
    public function syncSpecificsDetail(array $data){
        $wh['ebay_specific_id'] = $data['ebay_specific_id'];
        $wh['category_specific_name'] = $data['category_specific_name'];
        $wh['category_specific_value'] = $data['category_specific_value'];
        if(isset($data['parent_name'])){
           $wh['parent_name'] = $data['parent_name'];
        }

        if(isset($data['parent_value'])){
           $wh['parent_value'] = $data['parent_value']; 
        }

        #$rows = $this->table("ebay_category_specific_detail")->where($wh)->find();
        $rows = Db::name("ebay_category_specific_detail")->where($wh)->find();
        if($rows){#更新
            $this->table("ebay_category_specific_detail")->where($wh)->update($data);
            return $rows['id'];
        }else{#添加
            return $this->table("ebay_category_specific_detail")->insertGetId($data);
        }
    }


}