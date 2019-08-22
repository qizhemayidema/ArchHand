<?php

namespace app\admin\model;

use think\Model;

class ClassesComment extends Model
{
    protected $table = 'zhu_class_comment';

    public function user()
    {
        return $this->belongsTo('User');
    }

    public function getStatusAttr($value)
    {
        $status = [0 => '封禁', 1 => '正常'];
        return $status[$value];
    }

    public function getCreateTimeAttr($value){
        return date('Y-m-d H:i:s',$value);
    }
}
