<?php

namespace app\admin\controller;

use think\Request;
use think\Route;
use think\Validate;
use app\admin\model\Permission as PermissionModel;


/**
 * 权限控制器
 * Class Permission
 * @package app\admin\controllers
 */
class Permission extends Base
{
    public function index()
    {
        $res = (new PermissionModel())->getPermissionList();

        $this->assign('permission_list',$res);
        return $this->fetch();
    }

    public function add()
    {
        //查询
        $permissionListInfo = (new PermissionModel())->getPermissionList();

        $this->assign('permission_info',$permissionListInfo);
        return $this->fetch();
    }

    public function save(Request $request)
    {
        $data = $request->post();

        $rules = [
            'name'               => 'require',
            'controller'        => 'require',
            'action'            => 'require',
            'p_id'              => 'require',
        ];

        $messages = [
            'name.require'   => '权限名称必须添加',
            'controller.require'        => '控制器名称必须填写',
            'action.require'            => '方法名称必须填写',
            'p_id.require'              => '父节点必须选择',
        ];

        $validate = new Validate($rules,$messages);
        if (!$validate->check($data)){
            return json(['code'=>0,'msg'=>$validate->getError()]);
        }

        $data['controller'] = mb_strtolower($data['controller']);
        $data['action'] = mb_strtolower($data['action']);

        $permissionModel = new PermissionModel();
        $permissionInfo = $permissionModel->where(['controller'=>$data['controller'],'action'=>$data['action']])->find();
        if ($permissionInfo){
            return json(['code'=>0,'msg'=>'该方法已经存在权限']);
        }

        $permissionModel->insert([
            'p_id'      => $data['p_id'],
            'name'      => $data['name'],
            'controller'=> $data['controller'],
            'action'    => $data['action']
        ]);

        return json(['code'=>1,'msg'=>'success']);
    }

    public function read(Request $request)
    {
        $perId = $request->param('id');

        $permissionListInfo = (new PermissionModel())->where(['p_id'=>0])->select();

        $info = (new PermissionModel())->find($perId);

        $this->assign('permission_info',$permissionListInfo);

        $this->assign('info',$info);

        return $this->fetch();
    }

    public function update(Request $request)
    {
        $data = $request->post();

        $rules = [
            'id'        => 'require',
            'name'      => 'require',
            'controller'=> 'require',
            'action'    => 'require',
        ];

        $message = [
            'id.require'    => 'error',
            'name.require'  => '权限名称必须填写',
            'controller.require'    =>  '控制器必须填写',
            'action.require'    => '方法必须填写',
        ];

        $validate = new Validate($rules,$message);
        if (!$validate->check($data)){
            return json(['code'=>0,'msg'=>$validate->getError()]);
        }

        $data['controller'] = mb_strtolower($data['controller']);
        $data['action'] = mb_strtolower($data['action']);

        $permissionModel = new PermissionModel();
        $permissionInfo = $permissionModel->where('id','<>',$data['id'])->where(['controller'=>$data['controller'],'action'=>$data['action']])->find();
        if ($permissionInfo){
            return json(['code'=>0,'msg'=>'该方法已经存在权限']);
        }

        $permissionModel->where(['id'=>$data['id']])->update([
            'name'      => $data['name'],
            'controller'=> $data['controller'],
            'action'    => $data['action']
        ]);

        return json(['code'=>1,'msg'=>'success']);
    }

    public function delete()
    {

    }
}
