<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 2017/9/5
 * Time: 15:57
 */

namespace app\common\cache\driver;
use think\Db;
use app\common\cache\Cache;
use think\Exception;

class AliexpressCategoryCache extends Cache{
	/** 获取分类树
	 * @return array|mixed
	 */
	public function getCategoryTree()
	{
		$result = [];
		if ($this->redis->exists('cache:aliexpress_category_tree')) {
			$result = json_decode($this->redis->get('cache:aliexpress_category_tree'), true);
			return $result;
		} else {
			$result= $this->updateCategoryTree();
		}

		return $result;
	}

	/**
	 * 更新分类树
	 */
	public function updateCategoryTree()
	{
		$result=[];
		try {
			$category_list = Db::table('aliexpress_category')->field('category_id id,category_name_zh name,category_pid pid')->order('id ASC')->select();

			if ($category_list)
			{
				$child = '_child';
				$child_ids = [];
				$icon='';
				$temp = [
					'depr' => '-',
					'parents' => [],
					'child_ids' => [],
					'dir' => [],
					'_child' => [],
				];

				$_list = [];
				foreach ($category_list as $k => $v)
				{
					$v['title']=$v['name'];
					$_list['model'][$v['id']] = $v;
				}

				$func = function ($tree) use (&$func, &$result, &$temp, &$child, &$icon, &$child_ids)
				{
					foreach ($tree as $k => $v)
					{

						$v['parents'] = $temp['parents']; //所有父节点
						$v['depth'] = count($temp['parents']); //深度
						$v['name_path'] = empty($temp['name']) ? $v['name'] : implode($temp['depr'],
								$temp['name']) . $temp['depr'] . $v['name']; //英文名路径
						if (isset($v[$child]))
						{
							$_tree = $v[$child];
							unset($v[$child]);
							$temp['parents'][] = $v['id'];
							$temp['name'][] = $v['name'];
							$result[$k] = $v;
							if ($v['pid'] == 0)
							{
								if (empty($child_ids)) {
									$child_ids = [$k];
								} else {
									array_push($child_ids, $k);
								}
							}

							$func($_tree);
							foreach ($result as $value)
							{
								if ($value['pid'] == $k)
								{
									$temp['child_ids'] = array_merge($temp['child_ids'], [$value['id']]);
								}
							}
							$result[$k]['child_ids'] = $temp['child_ids']; //所有子节点
							$temp['child_ids'] = [];
							array_pop($temp['parents']);
							array_pop($temp['name']);
						} else {
							$v['child_ids'] = [];
							$result[$k] = $v;
							if ($v['pid'] == 0) {
								if (empty($child_ids)) {
									$child_ids = [$k];
								} else {
									array_push($child_ids, $k);
								}
							}
						}
					}
				};
				foreach ($_list as $k => $v)
				{
					$func(list_to_tree($v,'id','pid'));
				}


			}
			//$result = list_to_tree($result);
			$result['child_ids'] = $child_ids;
			//加入redis中
			$this->redis->set('cache:aliexpress_category_tree', json_encode($result));
		} catch (Exception $e) {
			throw new Exception($e->getFile().$e->getLine().$e->getMessage());
		}
		return $result;
	}
}