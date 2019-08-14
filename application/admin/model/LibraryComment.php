<?php

namespace app\admin\model;

use think\Model;

class LibraryComment extends Model
{
    //
    public function user()
    {
        return $this->belongsTo('User');
    }

    public function getStatusAttr($value)
    {
        $status = [0 => '封禁', 1 => '正常'];
        return $status[$value];
    }
}
