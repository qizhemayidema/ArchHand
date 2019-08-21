<?php

namespace app\api\model;

use think\facade\Request;
use think\Model;

class Library extends Model
{
    //

    public function getCreateTimeAttr($value)
    {
        return date('Y-m-d', $value);
    }

    public function getDescAttr($value){
        //替换img src路径
        $http = Request::domain();
        $regex = "/src=[\'|\"](.*?(?:[\.jpg|\.jpeg|\.png|\.gif|\.bmp]))[\'|\"].*?[\/]?/";
        $src = "src=\'".$http.'${1}\'';
       return $content =  preg_replace($regex, $src, $value);
    }
}
