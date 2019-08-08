<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;
use app\admin\model\Role as RoleModel;
use app\admin\model\Manager as ManagerModel;
use think\Validate;

//权限控制器
class Role extends Base
{
    public function index()
    {
        $roleList = (new RoleModel())->select()->toArray();
        $managerInfo = (new ManagerModel())->column('role_id', 'user_name');


        foreach ($managerInfo as $key => $value) {
            foreach ($roleList as $key1 => $value1) {
                if ($value == $value1['id']){
                    $roleList[$key1]['manager'][] = $key;
                }
            }
        }

        $this->assign('role_list', $roleList);
        return $this->fetch();
    }


    public function add()
    {
        //查询 所有权限
        $permissionList = (new \app\admin\model\Permission())->getPermissionArray();

        $this->assign('per_list',$permissionList);
        return $this->fetch();
    }

    public function save(Request $request)
    {
        $data = $request->post();

        $rules = [
            'role_name'     => 'require',
            'permission'    => 'require',
        ];

        $messages = [
            'role_name.require' => '角色名称必须填写',
            'permission.require'=> '角色的权限必须选择',
        ];

        $validate = new Validate($rules,$messages);

        if (!$validate->check($data)){
            return json(['code'=>0,'msg'=>$validate->getError()]);
        }
        $result  = [
            'role_name' => $data['role_name'],
            'permission_ids' => implode(',',$data['permission']),
            'role_desc' => $data['role_desc'],
        ];

        (new RoleModel())->insert($result);

        return json(['code'=>1,'msg'=>'success']);

    }

    public function read(Request $request)
    {
        $role_id = $request->param('id');
        //查询 所有权限
        $permissionList = (new \app\admin\model\Permission())->getPermissionArray();

        $roleInfo = (new RoleModel())->find($role_id)->toArray();
        $roleInfo['permission_ids'] = explode(',',$roleInfo['permission_ids']);

        $this->assign('per_list',$permissionList);
        $this->assign('role_info',$roleInfo);

        return $this->fetch();
    }

    public function update(Request $request)
    {
        $data = $request->post();

        $rules = [
            'id'            => 'require',
            'role_name'     => 'require',
            'permission'    => 'require',
        ];

        $messages = [
            'id.require'        => 'error',
            'role_name.require' => '角色名称必须填写',
            'permission.require'=> '角色的权限必须选择',
        ];

        $validate = new Validate($rules,$messages);

        if (!$validate->check($data)){
            return json(['code'=>0,'msg'=>$validate->getError()]);
        }
        if ($data['id'] == 1){ return json(['code'=>0,'msg'=>'操作越权']); }

        $result  = [
            'role_name' => $data['role_name'],
            'permission_ids' => implode(',',$data['permission']),
            'role_desc' => $data['role_desc'],
        ];

        (new RoleModel())->where(['id'=>$data['id']])->update($result);

        return json(['code'=>1,'msg'=>'success11']);
    }

    public function delete(Request $request)
    {
        $id = $request->param('id');
        if ($id == 1){ return json(['code'=>0,'msg'=>'操作越权']); }

        if ((new ManagerModel())->where(['role_id'=>$id])->find()){
            return json(['code'=>0,'msg'=>'有人正在使用此角色,无法删除']);
        }

        (new RoleModel())->where(['id'=>$id])->delete();

        return json(['code'=>1,'msg'=>'success']);


    }
}
