<?php

namespace app\admin\controller;

use think\Controller;
use think\Db;
use think\Request;
use app\admin\model\LibraryComment as LibraryCommentModel;

class LibraryComment extends Base
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $comment = (new LibraryCommentModel())->order('status desc')->paginate(15);
        if (!$comment) {
            $this->assign('is_exist');
            return $this->fetch();
        }
        $this->assign('comments', $comment);
        return $this->fetch();
    }


    public function show($id)
    {
        $comment = LibraryCommentModel::where('id', $id)->find();
        if (!$comment) {
            $this->assign('is_exist', '未找到当前评论信息，请返回列表页刷新后确认是否以删除');
        }

        $this->assign('comment', $comment);
        return $this->fetch();
    }

    public function userShow($id){
        $user = \app\admin\model\User::where('id',$id)->find();
        if(!$user){
            $this->assign('is_exist',11);
        }
        $this->assign('user',$user);
        return $this->fetch();
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function add()
    {
        //
    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        //
    }

    /**
     * 显示指定的资源
     *
     * @param  int $id
     * @return \think\Response
     */
    public function read($id)
    {
        //
    }


    /**
     * 保存更新的资源
     *
     * @param  \think\Request $request
     * @param  int $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * 删除指定资源
     *
     * @param  int $id
     * @return \think\Response
     */
    public function delete(Request $request)
    {
        $id = $request->only('id');
        Db::startTrans();
        try {
            $comm = LibraryCommentModel::where('id', $id['id'])->delete();
            $check = Db::name('library_comment_check_history')->where('library_comment_id', $id['id'])->delete();
            Db::commit();
            return json(['code' => 1, 'msg' => '删除成功']);
        } catch (\Exception $e) {
            Db::rollback();
            return json(['code' => 0, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 审核显示
     * @param $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function verify($id)
    {
        Db::startTrans();
        try {
            $comment = LibraryCommentModel::where('id', $id)->find();
            if (!$comment) {
                return json(['code' => 0, 'msg' => '未找到当前数据，请刷新网页查看是否删除']);
            }
            if ($comment->getData('status') == 0) {

                $comment->save(['status' => 1]);
                $check = Db::name('library_comment_check_history')->where('library_comment_id', $id)->delete();
                Db::commit();
                return json(['code' => 1, 'msg' => '正常']);
            } else {
                $comm = LibraryCommentModel::update(['id' => $id, 'status' => 0]);
                $check = Db::name('library_comment_check_history')->insert([
                    'library_comment_id' => $id,
                    'manager_id' => session('admin')->id,
                    'create_time' => time(),
                ]);
                Db::commit();
                return json(['code' => 0, 'msg' => '封禁']);
            }

        } catch (\Exception $e) {
            Db::rollback();
            return json(['code' => 0, 'msg' => $e->getMessage()]);
        }
    }

}
