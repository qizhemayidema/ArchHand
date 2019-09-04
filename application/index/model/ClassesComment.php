<?php

namespace app\index\model;

use think\Model;
use app\common\model\Common;

class ClassesComment extends Model
{
    protected $table = 'zhu_class_comment';

    public function getCommentAttr($value)
    {
        return Common::imgSrcToRealUrl($value);
    }
}
