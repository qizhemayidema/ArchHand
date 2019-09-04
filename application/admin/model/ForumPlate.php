<?php

namespace app\admin\model;

use think\Cache;
use think\Model;

class ForumPlate extends Model
{
    public function clearCache()
    {
        (new Cache(['type'=>config('cache.type')]))->set('forum_plate_list',null);

    }
}
