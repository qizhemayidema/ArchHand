<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;
use app\admin\model\Manager as ManagerModel;
use app\admin\model\Role as RoleModel;
use think\Validate;

class Manager extends Base
{
    //管理员列表
    public function index()
    {
        $managerList = (new ManagerModel())->alias('manager')
                            ->join('role role','manager.role_id = role.id')
                            ->field('manager.id,manager.user_name,role.role_name')
                            ->select();

        $this->assign('manager_list',$managerList);
        return $this->fetch();
    }

    public function add(Request $request)
    {
        $manager_id = $request->param('manager_id');


        $managerInfo = (new ManagerModel())->find($manager_id);

        $roleList = ($managerInfo['role_id'] == 1 ) ? (new RoleModel())->select()->toArray() : (new RoleModel())->where('id','<>',1)->select()->toArray();

        $this->assign('role_list',$roleList);

        return $this->fetch();
    }

    public function save(Request $request)
    {
        $data = $request->post();

        $rules = [
            'user_name'     => 'require',
            'password'      => 'require',
            're_password'   => 'require',
            'role_id'       => 'require',
        ];

        $messages = [
            'user_name.require'     => '管理员名称必须填写',
            'password.require'      => '密码必须填写',
            're_password.require'   => '确认密码必须填写',
            'role_id.require'       => 'error',
        ];

        $validate = new Validate($rules,$messages);
        if (!$validate->check($data)){
            return json(['code'=>0,'msg'=>$validate->getError()]);
        }

        if ($data['password'] != $data['re_password']){
            return json(['code'=>0,'msg'=>'两次密码不一致']);
        }

        if ((new ManagerModel())->where(['user_name'=>$data['user_name']])->find()){
            return json(['code'=>0,'msg'=>'该账号已存在']);
        }

        (new ManagerModel())->insert([
            'user_name'     => $data['user_name'],
            'password'      => md5($data['password']),
            'role_id'       => $data['role_id'],
        ]);

        return json(['code'=>1,'msg'=>'success']);
    }

    //查看管理员
    public function read(Request $request)
    {

        $manager_id = $request->param('manager_id');


        $managerInfo = (new ManagerModel())->find($manager_id);

        $roleList = ($managerInfo['role_id'] == 1 ) ? (new RoleModel())->select()->toArray() : (new RoleModel())->where('id','<>',1)->select()->toArray();

        $this->assign('manager_info',$managerInfo);

        $this->assign('role_list',$roleList);

        return $this->fetch();
    }

    //编辑管理员
    public function update(Request $request)
    {
        $rules = [
            'manager_id'    =>   'require',
            'user_name'     =>   'require',
            'role_id'       =>   'require',
        ];

        $message = [
            'manager_id.require'        => 'error',
            'user_name.require'         => '管理员名称必须填写',
            'role_id.require'           => '必须选择一个角色',
        ];

        $data =  $request->post();

        $validate = new Validate($rules,$message);
        if (!$validate->check($data)){
            return json(['code'=>0,'msg'=>'error']);
        }

        $result = [];

        //如果写了密码 则 更新密码
        if ($data['password'] || $data['new_password']){
            if ($data['password'] != $data['new_password']){
                return json(['code'=>0,'msg'=>'两次密码不一致']);
            }
            $result['password'] = md5($data['new_password']);
        }

        $result['role_id'] = $this->loginInfo['id'] != 1 ? $this->loginInfo['id'] : $data['role_id'];
        $result['user_name'] = $data['user_name'];

        (new ManagerModel())->where(['id'=>$data['manager_id']])->update($result);

        return json(['code'=>1,'msg'=>'success']);
    }


    public function delete(Request $request)
    {
        $manager_id = $request->param('manager_id');
        if ($manager_id == 1){
            return json(['code'=>0,'msg'=>'error']);
        }

        (new ManagerModel())->where(['id'=>$manager_id])->delete();

        return json(['code'=>1,'msg'=>'success']);
    }

}
