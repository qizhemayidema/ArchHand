<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;
use app\admin\model\User as UserModel;
use app\admin\validate\User as UserValidate;
use app\admin\model\Store as StoreModel;
use app\admin\model\Vip;

class Official extends Base
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $user = UserModel::where('type', 2)->where('is_delete', 1)
            ->order('id desc')->paginate(15);

        $this->assign('users', $user);
        return $this->fetch();

    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function add()
    {
        $vip = Vip::all();
        $this->assign('vip', $vip);
        return $this->fetch('official/add_edit');
    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        $form = $request->only('phone,nickname,real_name,sex,birthday,profession,address,email,password,avatar_url,vip_id,integral,repassword');
        $validate = new UserValidate();
        if (!$validate->check($form)) {
            return json(['code' => 0, 'msg' => $validate->getError()]);
        }
        $form['password'] = md5($form['password']);
        if ($form['avatar_url'] == '') {
            $form['avatar_url'] = '/static/uploads/user/default/20150828225753jJ4Fc.jpeg';
        }
        $form['type'] = 2;
        $form['birthday'] = strTotime($form['birthday']);
        $form['last_login_time'] = time();
        $form['create_time'] = time();
        $form['store_id'] = 1;

        $user = (new UserModel())->save($form);

        if ($user) {
            return jsone(1, '创建成功');
        }
        return jsone(0, '创建失败');
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
            return $this->fetch('official/add_edit');
        } catch (\Exception $e) {
            $this->assign('is_exist', $e->getMessage());
            return $this->fetch('official/add_edit');
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
            unset($form['avatar_url']);
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
