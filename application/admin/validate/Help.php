<?php

namespace app\admin\validate;

use think\Validate;

class Help extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'    =>    ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'cate_name' => 'require|min:3|max:15',
        'desc' => 'require|min:5',
        'question' => 'require|min:5',
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名'    =>    '错误信息'
     *
     * @var array
     */
    protected $message = [
        'cate_name.require' => '板块名称必须填写',
        'cate_name.min' => '板块名称不能低于3个字',
        'cate_name.max' => '板块名称不能大于15个字',
        'desc.require' => '板块内容必须填写',
        'desc.min' => '板块内容不能小于5个字',
        'question.require' => '板块内容必须填写',
        'question.min' => '板块内容不能小于5个字'
    ];
}
