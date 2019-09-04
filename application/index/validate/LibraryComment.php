<?php

namespace app\index\validate;

use think\Db;
use think\Validate;

class LibraryComment extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'    =>    ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'library_id' => 'require|libraryCheck',
        'captcha'   => 'captcha',
        'comment' => 'min:5',
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名'    =>    '错误信息'
     *
     * @var array
     */
    protected $message = [
        'captcha.captcha'   => '验证码错误',
        'library_id.require' => '请刷新后重试~',
        'library_id.libraryCheck' => '请刷新后重试~',
        'comment.min' => '评论内容不能小于5个字',
    ];

    public function libraryCheck($value,$rule,$data){
        $library_id = Db::name('library')->where('id',$value)->count('id');
        if($library_id){
            return true;
        }
        return false;
    }
}
