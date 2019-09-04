<?php

namespace app\api\model;

use think\Cache;
use think\Model;

class LibraryCategory extends Model
{

    public function attribute(){
        return $this->hasMany('LibraryAttribute','cate_id','id');
    }
}
