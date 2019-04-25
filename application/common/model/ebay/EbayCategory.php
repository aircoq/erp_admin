<?php
namespace app\common\model\ebay;

use think\Model;
use think\Loader;
use think\Db;
use think\Exception;

class EbayCategory extends Model
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
        $masterTable  = "ebay_category";
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
     * 批量新增
     * @param array $data [description]
     */
    public function addAll(array $data)
    {
        $i = 0; $j=0;
        foreach ($data as $key => $value) {
            $res = $this->add($value);
            if($res==1){
                $i++;
            }elseif($res==2){
                $j++;
            }
        }
        \think\Log::write('insert:'.$i." 条，update：".$j." 条");
    }
    
    
    public function add(array $data)
    {
        if (isset($data['category_id'])) {
            //Db::startTrans();
            try {                
                //检查是否已存在
                $info = $this->where(['category_id'=>$data['category_id']])->find();
               
                if(empty($info)){
                   $this->insert($data);
                   return 1;
                   //\think\Log::write('write:'.$data['category_id']);
                }else{
                    
                     $r = $this->allowField(true)->save($data, ['category_id'=>$data['category_id']]); 
                    return 2;
                }
                 //Db::commit();
             } catch (Exception $ex) {
                   // Db::rollback();
                    print_r($ex->getMessage());exit;
              }
        }
        return false;
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
    * 检查分类是否存在
    * @return [type] [description]
    */
    protected function check(array $data)
    {
        $result = $this->get($data);
        if (!empty($result)) {
            return true;
        }
        return false;
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
    * 同步分类
    * @return [type] [description]
    */
    public function syncCategory(array $data)
    {
        if (isset($data['category_id'])) {
            //出现了一种情况，分类id更新了，但是名称没有更新，仅根据分类id查询会查询不到，造成重复插入
            $map['category_id'] = $data['category_id'];
            $map['site'] = $data['site'];
            $wh['site'] = $data['site'];
            $wh['category_name'] = $data['category_name'];
            if ($this->get($map)) { //先根据category_id查询
                return $this->edit($data, $map);
            } else if ($this->get($wh)) { //根据分类名称查询
                return $this->edit($data, $wh);
            } else {
                return $this->insert($data);
            }
//            //检查分类是否已存在
//            if ($this->check($map)) {
//                return $this->edit($data, array('category_id' => $data['category_id'],'site' => $data['site']));
//            }else{
//                return $this->insert($data);
//            }
        }
    }

    /**
    * 按照时间排序获取一个类目,用于获取ebay类目是否支持多属性
    * @return [type] [description]
    */
    public function getCategoryOne(){
        $rows=$this->order("up_date")->find();
        $this->edit(array('up_date'=>time()),array("id"=>$rows['id']));
        return $rows;
    }

    /**
    * 按照时间排序获取一个类目,用于获取ebay类目属性
    * @return [type] [description]
    */
    public function getCategoryOneSpecifics(){
        $rows=$this->order("spec_date")->find();
        $this->edit(array('spec_date'=>time()),array("id"=>$rows['id']));
        return $rows;
    }


}