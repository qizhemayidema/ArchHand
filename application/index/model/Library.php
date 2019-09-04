<?php
namespace app\index\model;


use app\common\model\Common;
use think\facade\Request;

use think\Model;
use app\common\model\Common as CommonModel;

class Library extends Model
{

    public function getDescAttr($value)
    {
        return CommonModel::imgSrcToRealUrl($value);
    }
}
