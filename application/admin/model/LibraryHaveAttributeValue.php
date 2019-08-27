<?php

namespace app\admin\model;

use think\Model;

class LibraryHaveAttributeValue extends Model
{

    public function attributeValue(){
        return $this->belongsTo('LibraryAttributeValue','attr_value_id','id');
    }
}
