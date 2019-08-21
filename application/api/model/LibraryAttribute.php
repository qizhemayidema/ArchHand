<?php

namespace app\api\model;

use think\Model;

class LibraryAttribute extends Model
{

    public function attributeValue()
    {
        return $this->hasMany('LibraryAttributeValue', 'attr_id', 'id');
    }
}
