<?php

namespace app\admin\validate;

use think\Db;
use think\Validate;

class libraryCategory extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'    =>    ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'cate_name' => 'require|min:2|max:10|unique:library_category,cate_name',
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名'    =>    '错误信息'
     *
     * @var array
     */
    protected $message = [
        'cate_name.require' => '请填写分类名称',
        'cate_name.min' => '分类名称应不小于两个字符',
        'cate_name.max' => '分类字符应不超过10个字符',
        'cate_name.unique' => '当前分类名称以存在',
    ];
}
