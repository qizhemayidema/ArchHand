<?php

namespace app\admin\validate;

use think\Db;
use think\Validate;

class Vip extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'    =>    ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'vip_name'=>'require|min:2|max:20',
        'price' => 'require|number|integer|unique:vip',
        'discount' => 'require|unique:vip|regex:^[1-9]\.[0-9]{1,1}$',
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名'    =>    '错误信息'
     *
     * @var array
     */
    protected $message = [
        'price.require' => '充值金额必须填写',
        'price.number' => '充值金额填写错误',
        'price.integer' > '充值金额必须是整数',
        'price.unique' => '当前金额以存在',
        'discount.require' => '折扣必须填写',
        'discount.regex' => '折扣填写错误',
        'discount.unique' => '当前折扣以存在',
    ];





}
