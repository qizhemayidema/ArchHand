<?php

namespace app\admin\validate;

use think\Validate;

class Config extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'    =>    ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'title' => 'require|min:2|max:25',
        'keyword' => 'require|keywordCheck',
        'description' => 'min:2|max:80',
        'icp' => 'require',
        'announcement' => 'require',
        'copyright' => 'require',
        'qq' => 'regex:^[1-9][0-9]*$|min:6|max:15',
        'phone' => 'regex:/^1[34578]\d{9}$/',
        'issue_integral' => 'require|regex:^[1-9][0-9]*$',
        'issue_integral_count' => 'require|regex:^[1-9][0-9]*$',
        'comment_integral' => 'require|regex:^[1-9][0-9]*$',
        'comment_integral_count' => 'require|regex:^[1-9][0-9]*$',
//        'ratio_integral' => 'require|integer|intCheck|min:1|max:9',
        'service_charge_integral' => 'require|regex:^[0-9]{1,2}\.{0,1}[0-9]{0,1}$',//百分比小数点前两位 后一位
        'sign_in_integral' => 'require|signCheck',
        'today_title'=>'require|min:3|max:20',
        'today_url'=>'require|url',
        'today_content'=>'require|min:15',
        'today_pic'=>'require'

    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名'    =>    '错误信息'
     *
     * @var array
     */
    protected $message = [
        'title.require' => '网站标题必须填写',
        'title.min' => '网站标题不能小于两个字',
        'title.max' => '网站标题不能大于25个字',
        'keyword.require' => '网站关键字必须填写',
        'keyword.keywordCheck' => '网站关键字填写错误',
        'description.mix' => '网站描述不能小于2个字',
        'description.max' => '网站描述不能大于80个字',
        'icp' => 'icp认证必须填写',
        'announcement' => '网站公告必须填写',
        'copyright' => '网站版权信息必须填写',
        'qq.regex' => 'QQ填写错误',
        'qq.min' => 'QQ不能小于6个数字',
        'qq.max' => 'QQ不能大于15个数字',
        'phone' => '手机格式填写错误',
        'issue_integral.require' => '发布积分必须填写',
        'issue_integral.regex' => '发布积分必须是正整数',
        'issue_integral_count.require' => '发布积分必须填写',
        'issue_integral_count.regex' => '发布积分必须是正整数',
        'comment_integral.require' => '评论积分必须填写',
        'comment_integral.regex' => '评论积分必须是正整数',
        'comment_integral_count.require' => '评论次数必须填写',
        'comment_integral_count.regex' => '评论次数必须是正整数',
        'ratio_integral.require' => '助手币兑换比例必须填写',
        'ratio_integral.integer' => '助手币兑换比例必须是正整数',
        'ratio_integral.intCheck' => '助手币兑换比例必须是正整数',
        'ratio_integral.min' => '助手币兑换比例不能小于1位数',
        'ratio_integral.max' => '助手币兑换比例不能大于9位数',
        'service_charge_integral.require' => '手续费必须填写',
        'service_charge_integral.regex' => '手续费填写错误',
        'sign_in_integral.require' => '签到积分必须填写',
        'sign_in_integral.signCheck' => '签到积分填写错误',
        'today_title.require'=>'今日力推标题必须填写',
        'today_title.min'=>'今日力推标题不能小于3个字',
        'today_title.max'=>'今日力推标题不能大于20个字',
        'today_url.require'=>'今日力推URL必须填写',
        'today_url.url'=>'今日力推URL不是有效的地址',
        'today_content.require'=>'今日力推内容必须填写',
        'today_content.min'=>'今日力推内容不能小于15个字',
        'today_pic.require'=>'今日力推封面必须上传',
    ];

    public function intCheck($value, $rule, $data)
    {
        if (is_numeric($value)) {
            return true;
        }
        return false;
    }

    public function keywordCheck($value, $rule, $data)
    {
        $keywords = explode(',', $value);
        foreach ($keywords as $v) {
            if (strlen($v) > 8) {
                return false;
            }
        }
        return true;
    }

    public function signCheck($value, $rule, $data)
    {

        $signs = explode(',', $value);
        if (count($signs) != 7) {
            return false;
        }
        foreach ($signs as $sign) {
            if (!preg_match('/^[1-9]{1}[0-9]{0,1}$/', $sign)) {
                return false;
            }
        }
        return true;
    }
}
