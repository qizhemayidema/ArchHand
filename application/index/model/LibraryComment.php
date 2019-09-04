<?php

namespace app\index\model;


use app\common\model\Common;
use think\facade\Request;
use think\Model;
use app\common\model\Common as CommonModel;


class LibraryComment extends Model
{


    public function user()
    {
        return $this->belongsTo('User', 'user_id', 'id');
    }

    public function getCommentAttr($value)
    {
        return Common::imgSrcToRealUrl($value);
    }

}
