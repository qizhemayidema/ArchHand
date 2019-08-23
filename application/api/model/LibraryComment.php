<?php

namespace app\api\model;


use think\facade\Request;
use think\Model;
use app\common\model\Common as CommonModel;


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

    public function getCommentAttr($value){
        return CommonModel::imgSrcToRealUrl($value);
    }

}
