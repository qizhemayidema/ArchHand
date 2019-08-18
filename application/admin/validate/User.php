<?php

namespace app\admin\validate;

use think\Validate;

class User extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'    =>    ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'phone' => 'require|unique:user|regex:/^1[34578]\d{9}$/',
        'nickname' => 'min:2|max:30|unique:user',
        'real_name' => 'min:2|max:16',
        'sex' => 'require|number',
        'birthday' => 'date',
        'profession' => 'min:2|max:32',
        'address' => 'min:5|max:128',
        'email' => 'email|unique:user',
        'password' => 'require|min:6|max:10',
        'repassword'=>'confirm:password',
        'vip_id' => 'require',
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名'    =>    '错误信息'
     *
     * @var array
     */
    protected $message = [
        'phone.require' => '请填写手机号',
        'phone.unique'=>'当前手机号已存在',
        'phone.regex'=>'请填写正确手机号',
        'nickname.min' => '昵称不能小于2个字',
        'nickname.max' => '昵称不能大于30个字',
        'nickname.unique'=>'当前昵称以存在',
        'real_name.min' => '真实姓名不能小于2个字',
        'real_name.max' => '真实姓名不能大于16个字',
        'sex.require' => '性别必须填写',
        'sex.number' => '性别填写错误',
        'birthday.number' => '生日填写错误',
        'profession.min' => '专业不能小于2个字',
        'profession.max' => '专业不能大于32个字',
        'address.min' => '地址不能小于5个字',
        'address.max' => '地址不能大于128个字',
        'email.email' => '邮箱填写错误',
        'email.unique'=>'当前邮箱已存在',
        'password.require' => '密码必须填写',
        'password.min' => '密码不能小于6个字符',
        'password.max' => '密码不能大于10个字符',
        'repassword.confirm' => '两次填写的密码不一致',
        'vip_id.require' => 'VIP设置错误',
        'vip_id.number' => 'VIP设置错误'
    ];


}
