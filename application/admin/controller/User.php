<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;
use app\admin\model\User as UserModel;
use app\admin\validate\User as UserValidate;
use app\admin\model\Vip;

class User extends Base
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $users = UserModel::where('type', 1)->where('is_delete', 1)->paginate(15);
        $this->assign('users', $users);
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
        try {
            $user = UserModel::where('id', $id)->find();

            if (!$user) {
                $this->assign('is_exist', '未找到数据，请刷新页面确认当前数据是否以删除');

            } else {
                $user['password'] = '******';

                $vip = Vip::all();
                $this->assign('vip', $vip);
                $this->assign('user', $user);

            }
            return $this->fetch('user/add_edit');
        } catch (\Exception $e) {
            $this->assign('ix_exist', $e->getMessage());
            return $this->fetch('user/add_edit');
        }
    }

    /**
     * 保存更新的资源
     *
     * @param  \think\Request $request
     * @param  int $id
     * @return \think\Response
     */
    public function update(Request $request)
    {
        $form = $request->post();
        $validate = new UserValidate();
        if (!$validate->check($form)) {
            return jsone(0, $validate->getError());
        }
        if ($form['password'] == '******') {
            unset($form['password']);
        } else {
            $form['password'] = md5($form['password']);
        }

        $form['birthday'] = strtotime($form['birthday']);


        if ($form['avatar_url'] == '') {
            $form['avatar_url'] = 'static/uploads/user/default/20150828225753jJ4Fc.jpeg';
        }
        $user = UserModel::update($form);
        if ($user) {
            return jsone(1, '编辑成功');
        }
        return jsone(0, '编辑失败');

    }

    /**
     * 删除指定资源
     *
     * @param  int $id
     * @return \think\Response
     */
    public function delete($id)
    {
        try {
            $user = UserModel::where('id', $id)->where('is_delete', 1)->find();
            if ($user) {
                $user = UserModel::update(['id' => $id, 'is_delete' => 0]);
            }
            return jsone(1, '删除成功');
        } catch (\Exception $e) {
            return jsone(0, $e->getMessage());
        }
    }
}
