<?php

namespace app\api\model;


use think\facade\Request;

use think\Model;
use app\common\model\Common as CommonModel;

class Library extends Model
{


    public function getCreateTimeAttr($value)
    {
        return date('Y-m-d', $value);
    }

    public function getDescAttr($value)
    {
        return CommonModel::imgSrcToRealUrl($value);
    }

}
