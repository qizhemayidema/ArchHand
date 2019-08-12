<?php

namespace app\admin\validate;

use think\Validate;

class LibraryTag extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
        'cate_id'=>'require|number|checkCate',
        'name'=>'require|min:2|max:10|checkName',

    ];
    
    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [
        'name.require'=>'请填写标签名称',
        'name.min'=>'标签名称不能低于两个字符',
        'name.max'=>'标签名称不能大于10个字符',
        'name.checkName'=>'当前分类下标签名称以存在',
        'cate_id.require'=>'所属分类必须填写',
        'cate_id.number'=>'所属分类填写错误',
        'cate_id.checkCate'=>'未找到分类',
    ];

    public function checkName($value,$rule,$data){

            $tags = \app\admin\model\LibraryTag::where('cate_id',$data['cate_id'])->column('name');
            if(!$tags){
                return true;
            }
            foreach($tags as $v){
                if($value==$v){
                    return false;
                }
                return true;
            }
    }

    public function checkCate($value,$rule,$data){
        $cate_id = \app\admin\model\LibraryCategory::where('id',$value)->count('id');
        if($cate_id){
            return true;
        }
        return false;
    }
}
