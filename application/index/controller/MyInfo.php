<?php

namespace app\index\controller;

use think\Controller;
use think\Request;
use think\Validate;
use app\common\controller\UploadPic;
use app\api\model\User as UserModel;

class MyInfo extends Base
{
    /**
     * 我的信息页面
     * @return mixed
     */
    public function index(){
        $user_info = $this->userInfo;
        $result = [
            'avatar_url'    => $user_info['avatar_url'],
            'nickname'    => $user_info['nickname'],
            'real_name'    => $user_info['real_name'],
            'sex'            => $user_info['sex'],
            'birthday'            => $user_info['birthday'],
            'profession'            => $user_info['profession'],
            'address'            => $user_info['address'],
        ];
        if (!$result['birthday']){
            $result['birthday'] = date('Y-m-d',time());
        }else{
            $result['birthday'] = date('Y-m-d',$result['birthday']);
        }
        $this->assign('basic_user_info',$result);
        return $this->fetch();
    }

    /**
     * 修改信息
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function update(Request $request)
    {
        $data = $request->post();
        $rules = [
            'nickname'      => 'require|max:40',
            'real_name'     => 'require|max:10',
            'sex'           => 'require|integer',
            'birthday'      => 'require',
            'profession'    => 'require|max:40',
            'address'       => 'require|max:120',
        ];

        $messages = [
            'nickname.require'      => '昵称必须填写',
            'nickname.max'          => '昵称最大长度为 40',
            'real_name.require'     => '真实姓名必须填写',
            'real_name.max'         => '真实姓名最大长度为 10',
            'sex.require'           => '性别必须选择',
            'sex.integer'           => '性别非法',
            'birthday.require'          => '生日必须填写',
            'profession.require'    => '专业必须填写',
            'address.require'    => '专业必须填写',
            'profession.max'       => '专业最大长度为 40',
            'address.max'       => '地址最大长度为 120',
        ];

        $avatar_url = '';

        $validate = new Validate($rules,$messages);
        if(!$validate->check($data)){
            return json(['code'=>0,'msg'=>$validate->getError()]);
        }

        if ($data['avatar_url']){
            $avatar_url = $this->uploadUserAvatarBase64($data['avatar_url']);
            if ($avatar_url['code'] == 0){
                return json(['code'=>0,'msg'=>$avatar_url['msg']]);
            }
            $avatar_url = $avatar_url['msg'];
        }

        $birthday = strtotime($data['birthday']);

        $result = [
            'real_name'     => $data['real_name'],
            'sex'           => $data['sex'],
            'birthday'      => $birthday,
            'profession'    => $data['profession'],
            'address'       => $data['address'],
            'nickname'      => $data['nickname'],
        ];

        if($avatar_url){
            $result['avatar_url'] = $avatar_url;
            if ($this->userInfo['avatar_url'] && file_exists('.' . $this->userInfo['avatar_url'])){
                unlink('.'.$this->userInfo['avatar_url']);
            }
        }

        (new UserModel())->where(['id'=>$this->userInfo['id']])->update($result);

        return json(['code'=>1,'msg'=>'success']);
    }

    /**
     * 修改密码
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function updatePassword(Request $request)
    {

        $data = $request->post();

        $rules  = [
            'old_password'      => 'require|max:30',
            'new_password'      => 'require|max:30',
            're_password'       => 'require|max:30',
        ];

        $messages = [
            'old_password.require'  =>  '原密码必须填写',
            'old_password.max'      =>  '原密码最大长度为 30',
            'new_password.require'  =>  '新密码必须填写',
            'new_password.max'      =>  '新密码最大长度为 30',
            're_password.require'  =>   '确认密码必须填写',
            're_password.max'      =>   '确认密码最大长度为 30',
        ];

        $validate = new Validate($rules,$messages);
        if (!$validate->check($data)){
            return json(['code'=>0,'msg'=>$validate->getError()]);
        }
        if ($this->userInfo['password'] != md5($data['old_password'])){
            return json(['code'=>0,'msg'=>'当前密码不正确']);
        }
        if ($data['new_password'] != $data['re_password']){
            return json(['code'=>0,'msg'=>'两次密码不一致']);
        }
        (new UserModel())->where(['id'=>$this->userInfo['id']])->update([
            'password'      => md5($data['new_password'])
        ]);

        return json(['code'=>1,'msg'=>'success']);
    }

    private function uploadUserAvatarBase64($base64_content)
    {
        $user_id = $this->userInfo['id'];
        $path = 'user/' . $user_id . '/';
        return (new UploadPic())->uploadBase64Pic($base64_content,$path);
    }
}
