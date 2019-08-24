<?php

namespace app\admin\model;

use app\common\model\Common;
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

    public function getCreateTimeAttr($value)
    {
        return date('Y-m-d H:i:s', $value);
    }

    public function getCommentAttr($value)
    {
        return Common::imgSrcToRealUrl($value);
    }

}
