<?php
namespace app\publish\controller;

use app\common\model\amazon\AmazonPublishProductDraft;
use app\publish\service\AmazonPublishDraftService;
use think\Request;
use think\Exception;
use app\common\controller\Base;

/**
 * @module 刊登系统
 * @title Amazon刊登草稿箱
 * @url /publish/amazon/draft
 * Class AmazonPublishDraft
 * @package app\publish\controller
 */
class AmazonPublishDraft extends Base
{
    protected $lang = 'zh';

    public $service = null;

    public function __construct(Request $request)
    {
        parent::__construct($request);

        if (empty($this->service)) {
            //erp的语言设置，默认是中文，目前可能的值是en:英文；
            $this->lang = $request->header('Lang', 'zh');

            $this->service = new AmazonPublishDraftService();
            $this->service->setLang($this->lang);
        }
    }


    /**
     * @title amazon刊登草稿箱列表；
     * @access public
     * @method GET
     * @url /publish/amazon/draft
     * @apiRelate app\publish\controller\AmazonPublishDraft::edit
     * @apiRelate app\publish\controller\AmazonPublishDraft::save
     * @apiRelate app\publish\controller\AmazonPublishDraft::update
     * @apiRelate app\publish\controller\AmazonPublishDraft::delete
     * @param Request $request
     * @return \think\response\Json
     */
    public function index(Request $request)
    {
        try {
            $params = $request->get();
            $lists = $this->service->lists($params);
            return json($lists);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()]);
        }

    }


    /**
     * @title amazon刊登草稿去编辑；
     * @access public
     * @method GET
     * @url /publish/amazon/:id/draft
     * @param Request $request
     * @return \think\response\Json
     */
    public function edit($id)
    {
        try {
            $result = $this->service->get($id);
            return json($result);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title amazon刊登草稿保存；
     * @access public
     * @method POST
     * @url /publish/amazon/draft
     * @param Request $request
     * @return \think\response\Json
     */
    public function save(Request $request)
    {
        try {
            $data = $request->param();
            $result = $this->service->save($data);
            if ($result > 0) {
                if ($this->lang == 'zh') {
                    return json(['message' => '草稿保存成功', 'data' => ['draft_id' => $result], 'status' => 1]);
                } else {
                    return json(['message' => 'Draft saved successfully', 'data' => ['draft_id' => $result], 'status' => 1]);
                }
            }
            if ($this->lang == 'zh') {
                return json(['message' => '草稿保存失败', 'status' => 0], 400);
            } else {
                return json(['message' => 'Draft saved failed', 'status' => 0], 400);
            }
        } catch (Exception $e) {
            return json(['message' => $e->getMessage(), 'status' => 0], 400);
        }
    }


    /**
     * @title amazon刊登草稿更新；
     * @access public
     * @method PUT
     * @url /publish/amazon/draft
     * @param Request $request
     * @return \think\response\Json
     */
    public function update(Request $request)
    {
        try {
            $data = $request->put();
            $result = $this->service->update($data);
            if ($result > 0) {
                if ($this->lang == 'zh') {
                    return json(['message' => '草稿更新成功', 'data' => ['draft_id' => $result], 'status' => 1]);
                } else {
                    return json(['message' => 'Draft updated successfully', 'data' => ['draft_id' => $result], 'status' => 1]);
                }
            }
            if ($this->lang == 'zh') {
                return json(['message' => '草稿更新失败', 'status' => 0], 400);
            } else {
                return json(['message' => 'Draft updated failed', 'status' => 0], 400);
            }
        } catch (Exception $e) {
            return json(['message' => $e->getMessage(), 'status' => 0], 400);
        }
    }


    /**
     * @title amazon刊登草稿删除；
     * @access public
     * @method DELETE
     * @url /publish/amazon/draft
     * @param Request $request
     * @return \think\response\Json
     */
    public function delete(Request $request)
    {
        try {
            $ids = $request->delete('draft_ids');
            if (empty($ids)) {
                if ($this->lang == 'zh') {
                    throw new Exception('删除参数draft_ids 为空');
                } else {
                    throw new Exception('Param is null');
                }
            }
            $ids = explode(',', $ids);
            if (empty($ids)) {
                if ($this->lang == 'zh') {
                    throw new Exception('删除参数draft_ids 为空');
                } else {
                    throw new Exception('Param is null');
                }
            }
            AmazonPublishProductDraft::where(['id' => ['in', $ids]])->delete();
            if ($this->lang == 'zh') {
                return json(['message' => '草稿删除成功'], 200);
            } else {
                return json(['message' => 'Draft deleted successfully'], 200);
            }
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }

}