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
use think\exception\DbException;
use think\Log;



function jsone($code,$msg){
    return json(['code'=>$code,'msg'=>$msg]);
}

/**
 * 文库树形菜单
 * @param int $parentId
 * @param null $allCategories
 * @return array
 * @throws \think\Exception\DbException
 */
//function getCategoryTree($allCategories = null, $parentId = 0, $level = 0)
//{
//    $data = array();
//    if (!$allCategories) {
//        $allCategories = LibraryCategory::all();
//    }
//    foreach ($allCategories as $k => $v) {
//        if ($v['p_id'] == $parentId) {
//            $v['children'] = getCategoryTree($allCategories, $v['id']);
//            $data[] = $v;
//        }
//    }
//    return $data;
//}




/**
 * 分类列表
 * @param $categories
 * @param int $id
 * @return array|bool
 */
//function getCategories($allCategories = null, $parentId = 0, $level = 0)
//{
//    if (!$allCategories) {
//        $allCategories = LibraryCategory::all();
//    }
//    static $data = array();
//
//    foreach ($allCategories as $v) {
//        if ($v['p_id'] == $parentId) {
//            $v['level'] = $level;
//            $data[] = [
//                'id' => $v['id'],
//                'name' => $v['p_id'] ? str_repeat('-', $level * 5 + 1) . $v['cate_name'] : $v['cate_name'],
//                'pid' => $v['p_id'],
//                'ids' => $v['ids_string'],
//            ];
//            getCategories($allCategories, $v['id'], $level + 1);
//        }
//    }
//    return $data;
//}