<?php

namespace app\api\validate;

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
        'user_id' => 'require|userCheck',
        'comment' => 'min:5',
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名'    =>    '错误信息'
     *
     * @var array
     */
    protected $message = [
        'library_id.require' => '云库ID必须填写',
        'library_id.libraryCheck' => '云库ID错误',
        'user_id.require' => '用户ID必须填写',
        'user_id.userCheck' => '用户ID错误',
        'comment.min' => '评论内容不能小于5个字',
    ];

    public function libraryCheck($value,$rule,$data){
        $library_id = Db::name('library')->where('id',$value)->count('id');
        if($library_id){
            return true;
        }
        return false;
    }

    public function userCheck($value,$rule,$data){
        $user_id = Db::name('user')->where('id',$value)->count('id');
        if($user_id){
            return true;
        }
        return false;
    }
}
