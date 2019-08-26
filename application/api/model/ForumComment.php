<?php

namespace app\api\model;

use app\common\model\Common;
use think\Model;

class ForumComment extends Model
{
    public function getCommentAttr($value)
    {
        return Common::imgSrcToRealUrl($value);
    }
}
