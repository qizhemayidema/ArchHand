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
        'id' => 'require|checkId',
        'name' => 'require|unique:library_category,cate_name',
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名'    =>    '错误信息'
     *
     * @var array
     */
    protected $message = [
        'id.require' => '请选择分类层级',
        'id.checkId' => '上级分类填写错误',
        'name.require' => '请填写分类名称',
        'name.unique' => '当前分类名称以存在',
    ];

    protected function checkId($value, $rule, $data)
    {
        $num = Db::name('library_category')->where('p_id',0)->where('id', $value)->count();
        if ($num||$value==0) {
            return true;
        }
        return false;
    }
}
