<?php

namespace app\admin\model;

use think\Model;

class LibraryTag extends Model
{
    //

    public function category(){
        return $this->belongsTo('libraryCategory','cate_id');
    }

}
