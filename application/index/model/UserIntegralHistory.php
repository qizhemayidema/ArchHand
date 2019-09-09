<?php

namespace app\index\model;

use think\Model;

class UserIntegralHistory extends Model
{
    public function getTypeAttr($value)
    {
        $arr = [
            1  => '签到',
            2  => '充值筑手币',
            3  => '购买课程',
            4  => '购买云库素材',
            5  => '云库评论',
            6  => '课程评论',
            7  => '社区评论',
            8  => '社区发帖',
            9  => '云库素材被购买(可提现)',
            10 => '发布云库素材',
        ];
        return $arr[$value];
    }
}
