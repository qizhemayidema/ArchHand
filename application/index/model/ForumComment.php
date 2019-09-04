<?php
namespace app\index\model;

use app\common\model\Common;
use think\Model;

class ForumComment extends Model
{
    public function getCommentAttr($value)
    {
        return Common::imgSrcToRealUrl($value);
    }
}
