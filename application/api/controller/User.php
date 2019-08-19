<?php

namespace app\api\controller;

use app\common\controller\UploadPic;
use think\Controller;
use think\Request;
use app\api\model\User as UserModel;
use think\Validate;

class User extends Base
{
    /**
     * 资料设置 基本资料
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function basicInfo()
    {
        $userInfo = (new UserModel())->where(['id'=>$this->userInfo['id']])
            ->field('avatar_url,real_name,sex,birthday,profession,address')->find();

        return json(['code'=>'0','data'=>$userInfo]);
    }

    /**
     * 修改基本资料
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function updateBasicInfo(Request $request)
    {
        $data = $request->post();
        $rules = [
            'avatar_url'    => 'require',
            'nickname'      => 'require|max:40',
            'real_name'     => 'require|max:10',
            'sex'           => 'require|integer',
            'year'          => 'require|integer',
            'month'         => 'require|integer',
            'day'           => 'require|integer',
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
            'year.require'          => '年必须选择',
            'year.integer'          => '年非法',
            'month.require'         => '月必须选择',
            'month.integer'         => '月非法',
            'day.require'           => '日必须选择',
            'day.integer'           => '日非法',
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

        $birthday = strtotime($data['year'] . '-' . $data['month'] . '-' . $data['day']);

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
            if (file_exists('.' . $this->userInfo['avatar_url'])){
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

    /**
     * 上传 base 64 头像
     * @param $base64_content
     * @return \think\response\Json
     */
    private function uploadUserAvatarBase64($base64_content)
    {
        $user_id = $this->userInfo['id'];
        $path = 'user/' . $user_id . '/';
        return (new UploadPic())->uploadBase64Pic($base64_content,$path);
    }


}
