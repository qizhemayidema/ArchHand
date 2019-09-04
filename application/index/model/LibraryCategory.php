<?php

namespace app\index\model;

use think\Cache;
use think\Model;

class LibraryCategory extends Model
{

    public function getCate($cate_id = null)    //如果有传参 说明要获取某个分类下的所有属性值
    {
        $cache = new Cache(['type'=>config('cache.type')]);
        $library_cate = $cache->get('library_cate');
        if (!$library_cate){
            $library_cate = $this->with(['attribute.attributeValue'])->all()->toArray();
            $cache->set('library_cate',$library_cate);
        }
        if ($cate_id){
            foreach ($library_cate as $key => $value){
                if ($value['id'] == $cate_id){
                    return $value['attribute'];
                }
            }
            return [];
        }
        return $library_cate;
    }

    public function attribute(){
        return $this->hasMany('LibraryAttribute','cate_id','id');
    }
}
