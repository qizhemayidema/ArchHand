<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
use app\admin\model\LibraryCategory;


/**
 * 文库树形菜单
 * @param int $parentId
 * @param null $allCategories
 * @return array
 * @throws \think\Exception\DbException
 */
function getCategoryTree($parentId = 0, $allCategories = null)
{
    if (is_null($allCategories)) {
        //从数据库中一次性取出所有类目
        $allCategories = LibraryCategory::all();
    }
    $data = array();
    $categories = LibraryCategory::where('p_id',$parentId)->all();
    foreach($categories as $k=>$v){
        $data[$k] = ['id' => $v->id, 'name' => $v->cate_name];
        if($v->p_id!=0){
            return $data;
        }
        $data[$k]['children']=getCategoryTree($v->id,$allCategories);

    }
    return $data;
}