<?php

namespace app\common\model;

use think\Model;
use think\facade\Request;

class Common extends Model
{
    public static function imgSrcToRealUrl($value)
    {
        //替换img src路径
        $http = Request::domain();
        $regex = "/src=[\'|\"](.*?(?:[\.jpg|\.jpeg|\.png|\.gif|\.bmp]))[\'|\"].*?[\/]?/";
        $src = "src=\'".$http.'${1}\'';
        return $content =  preg_replace($regex, $src, $value);
    }
}
