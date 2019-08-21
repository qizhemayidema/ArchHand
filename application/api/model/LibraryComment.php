<?php

namespace app\api\model;


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

    public function getComment($value){
        //替换img src路径
        $http = Request::domain();
        $regex = "/src=[\'|\"](.*?(?:[\.jpg|\.jpeg|\.png|\.gif|\.bmp]))[\'|\"].*?[\/]?/";
        $src = "src=\'".$http.'${1}\'';
        return $content =  preg_replace($regex, $src, $value);
    }

}
