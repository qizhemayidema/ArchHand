<?php

namespace app\admin\model;

use think\Model;

class Library extends Model
{
    //

    public function user()
    {
        return $this->belongsTo('User');
    }

    public function category()
    {
        return $this->belongsTo('LibraryCategory', 'cate_id');
    }

    public function attribute(){
        return $this->hasMany('LibraryHaveAttributeValue','library_id','id');
    }

    public function cate(){
        return $this->belongsTo('LibraryCategory','cate_id','id');
    }

    public function getStatusAttr($value)
    {
        $status = [-1 => '审核未通过', 0 => '未审核', 1 => '审核以通过'];
        return $status[$value];
    }

    public function getIsOfficialAttr($value)
    {
        $is_official = [0 => '否', 1 => '是'];
        return $is_official[$value];
    }

    public function getCreateTimeAttr($value)
    {
        return date('Y-m-d H:i:s', $value);
    }

}
