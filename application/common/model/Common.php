<?php

namespace app\common\model;

use think\Model;
use think\facade\Request;

class Common extends Model
{
    public static function imgSrcToRealUrl($value)
    {
        return $value;
//        //替换img src路径前加上 当前域名 /a.jpg -> http://www.xxx.com/a.jpg
//        $http = Request::domain();
//        $regex = "/src=[\'|\"](.*?(?:[\.jpg|\.jpeg|\.png|\.gif|\.bmp]))[\'|\"].*?[\/]?/";
//        $src = "src=".$http.'${1}';
//        return $content =  preg_replace($regex, $src, $value);
    }
}
