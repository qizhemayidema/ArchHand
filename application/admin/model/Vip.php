<?php

namespace app\admin\model;

use app\common\model\Common;
use think\Model;

class Vip extends Model
{
    //
    public function getDescAttr($value)
    {
        return Common::imgSrcToRealUrl($value);
    }
}
