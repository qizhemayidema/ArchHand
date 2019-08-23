<?php

namespace app\api\model;


use app\common\model\Common;
use think\facade\Request;
use think\Model;


class LibraryComment extends Model
{

    //

    public function getCreateTimeAttr($value)
    {
        return date('Y-m-d', $value);
    }

    public function user()
    {
        return $this->belongsTo('User', 'user_id', 'id');
    }

    public function getComment($value)
    {
        return Common::imgSrcToRealUrl($value);
    }

}
