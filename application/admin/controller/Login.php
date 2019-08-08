<?php
/**
 * Created by PhpStorm.
 * User: 刘彪
 * Date: 2019/7/22
 * Time: 14:29
 */

namespace app\admin\controller;

use app\admin\model\Manager;
use app\admin\model\User;
use think\Controller;
use think\facade\Session;
use think\Request;
use think\Validate;

class Login extends Controller
{
    public function index()
    {
        if (Session::get('admin')){
            return redirect('/admin/index');
        }
        return $this->fetch();
    }

    public function check(Request $request)
    {
        $data =$request->post();

        $rules = [
            'username'  => 'require',
            'password'  => 'require',
            'captcha'   => 'require|captcha',
        ];
        $messages = [
            'username.require'      => '用户名必须填写',
            'password.require'      => '密码必须填写',
            'captcha.require'       => '验证码必须填写',
            'captcha.captcha'       => '验证码不正确',
        ];
        $validate = new Validate($rules,$messages);
        $result = $validate->check($data);
        if (!$result) {
            return json_encode(['code' => 0, 'msg'=>$validate->getError()], 256);
        }

        $res = (new Manager())->where(['username'=>$data['username'],'password'=>md5($data['password'])])->find();
        if (!$res){
            return json_encode(['code' => 0, 'msg'=>'账号或密码不正确'], 256);
        }
        //登陆成功
        \think\facade\Session::set('admin',$data['username']);

        return json_encode(['code' => 1, 'msg'=>'success'], 256);
    }

    public function logout()
    {
        Session::set('admin',null);
        $this->redirect('admin/Index/index');
    }
}