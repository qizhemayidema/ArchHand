<?php

namespace app\common\controller;

use think\Controller;
use think\Model;
use think\Request;
use app\common\model\Library as LibraryModel;
use app\common\model\LibraryAttributeValue as LAVModel;
use app\common\model\LibraryCategory as CateModel;
use app\common\model\LibraryHaveAttributeValue as LHAVModel;

class Library extends Controller
{
    //删除 or 审核通过后又变不通过后减少 审核通过加 | 加减相关统计用的数据
    public function setAboutSum($library_id,$type = 0) // 0 为 减少  1 为 增加
    {
        //首先查出云库所属一级分类  去 加或减少
        $cate_id = (new LibraryModel())->where(['id'=>$library_id])->value('cate_id');
        //查出所使用的所有二级值 id 去批量 增 或减少
        $attr_val_ids = (new LHAVModel())->where(['library_id'=>$library_id])->column('attr_value_id');
        if ($type){
            (new CateModel())->where(['id'=>$cate_id])->setInc('count');
            (new LAVModel())->whereIn('id',$attr_val_ids)->setInc('library_num');
        }else{
            (new CateModel())->where(['id'=>$cate_id])->setDec('count');
            (new LAVModel())->whereIn('id',$attr_val_ids)->setDec('library_num');
            
        }
    }
}
