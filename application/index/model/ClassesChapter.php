<?php

namespace app\index\model;

use think\Model;

class ClassesChapter extends Model
{
    protected $table = 'zhu_class_chapter';

    public function getSourceUrlAttr($value)
    {
        return env('UPYUN.CDN_URL') . $value;
    }
}
