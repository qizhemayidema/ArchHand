<?php

namespace app\admin\model;

use think\Model;

class User extends Model
{
    //


    public function getBirthdayAttr($value)
    {
        if ($value == 0) {
            return '';
        }
        return date('Y-m-d', $value);
    }

    public function getSexAttr($value)
    {
        $sex = [2 => '女', 1 => '男'];
        return $sex[$value];
    }

//    public function getVipIdAttr($value)
//    {
//        $vip = [0 => '否', 1 => '是'];
//        return $vip[$value];
//    }

    public function getCreateTimeAttr($value)
    {
        return date('Y-m-d H:i:s', $value);
    }

    public function vip()
    {
        return $this->belongsTo('Vip', 'vip_id', 'id');
    }

}
