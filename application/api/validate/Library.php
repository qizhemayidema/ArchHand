<?php

namespace app\api\validate;

use app\api\model\LibraryCategory;
use app\api\model\User;
use think\Db;
use think\Validate;

class Library extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'    =>    ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'cate_id' => 'require|number|cateCheck',
        'name' => 'require|min:3|max:64|chsAlpha',//print
        'user_id' => 'require|number|userCheck',
        'name_status' => 'require|number',
        'integral' => 'require|number',
        'source_url' => 'require',
        'desc' => 'require|min:10',
        'suffix' => 'require|alpha',
        'data_size' => 'require|number',
        'is_original' => 'require|number',
        'is_classics' => 'require|number|in:0,2',
//        'attr_value_ids' => 'require|array|length',
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名'    =>    '错误信息'
     *
     * @var array
     */
    protected $message = [
        'cate_id.require' => '请选择分类',
        'cate_id.number' => '分类填写错误',
        'cate_id.cateCheck' => '分类填写错误',
        'name.require' => '请填写标题',
        'name.min:3' => '标题不能小于3个文字',
        'name.max:64' => '标题不能大于64个文字',
        'name.chsAlpha' => '标题填写错误',
        'user_id.require' => '请登录后重试',
        'user_id.number' => '请登录后重试',
        'user_id.userCheck' => '请登陆后重试',
        'name_status.require' => '请选择是否显示名称',
        'name_status.number' => '显示名称出错',
        'integral.require' => '请填写下载所需积分',
        'integral.number' => '下载所需积分填写错误',
        'source_url.require' => '资源路径错误',
        'desc.require' => '请填写正文信息',
        'desc.min' => '正文字数过少',
        'suffix.require' => '文件格式错误',
        'suffix.alpha' => '文件格式错误',
        'data_size.require' => '请填写文件大小',
        'data_size.number' => '文件大小错误',
        'is_original.require' => '请填写原创信息',
        'is_original.number' => '原创信息填写错误',
        'is_classics.require' => '请填写是否加精',
        'is_classics.number' => '加精信息错误',
        'is_classics.in' => '加精信息错误',
        'attr_value_ids.require'=>'文库属性必须填写',
        'attr_value_ids.array' => '文库属性填写错误',
        'attr_value_ids.length' => '文库属性必须填写',
    ];

    public function cateCheck($value, $rule, $data)
    {
        $cate = LibraryCategory::where('id', $value)->count('id');
        if ($cate) {
            return true;
        }
        return false;
    }

    public function userCheck($value, $rule, $data)
    {
        $user = User::where('id', $value)->count('id');
        if ($user) {
            return true;
        }
        return false;
    }

}
