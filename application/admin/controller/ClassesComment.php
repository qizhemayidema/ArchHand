<?php

namespace app\admin\controller;

use think\Controller;
use think\Db;
use think\Request;
use app\admin\model\ClassesComment as ClassCommentModel;

class ClassesComment extends Base
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $comment = (new ClassCommentModel())->order('status desc')->order('id','desc')->paginate(15);
        if (!$comment) {
            $this->assign('is_exist');
            return $this->fetch();
        }
        $this->assign('comments', $comment);
        return $this->fetch();
    }


    public function show($id)
    {
        try {
            $comment = ClassCommentModel::where('id', $id)->find();
            if (!$comment) {
                $this->assign('is_exist', '未找到数据，请刷新页面确认当前数据是否以删除');
            }

            $this->assign('comment', $comment);
            return $this->fetch();
        }catch (\Exception $e){
            $this->assign('is_exist', $e->getMessage());
            return $this->fetch();
        }
    }

    public function userShow($id){
        try {
            $user = \app\admin\model\User::where('id', $id)->find();
            if (!$user) {
                $this->assign('is_exist', '未找到数据，请刷新页面确认当前数据是否以删除');
            }
            $this->assign('user', $user);
            return $this->fetch();
        }catch(\Exception $e){
            $this->assign('is_exist',$e->getMessage());
            return $this->fetch();
        }
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
            //TODO::替换字段
            $comm = ClassCommentModel::where('id', $id['id'])->delete();
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
            $comment = ClassCommentModel::where('id', $id)->find();
            if (!$comment) {
                return json(['code' => 0, 'msg' => '未找到当前数据，请刷新网页查看是否删除']);
            }
            if ($comment->getData('status') == 0) {

                $comment->save(['status' => 1]);
                $check = Db::name('library_comment_check_history')->where('library_comment_id', $id)->delete();
                Db::commit();
                return json(['code' => 1, 'msg' => '正常']);
            } else {
                $comm = ClassCommentModel::update(['id' => $id,'status' => 0]);
                $check = Db::name('library_comment_check_history')->insert([
                    'library_comment_id' => $id,
                    'manager_name' => session('admin')->user_name,
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
