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


/**
 * 文库树形菜单
 * @param int $parentId
 * @param null $allCategories
 * @return array
 * @throws \think\Exception\DbException
 */
//{id:1,name:222,pid:0},
//{}
//{}


function getCategoryTree($allCategories,$parentId = 0,$id=0)
{
//    return $allCategories
//        // 从所有类目中挑选出父类目 ID 为 $parentId 的类目
//        ->where('parent_id', $parentId)
//        // 遍历这些类目，并用返回值构建一个新的集合
//        ->map(function (Category $category) use ($allCategories) {
//            $data = ['id' => $category->id, 'name' => $category->name];
//            // 如果当前类目不是父类目，则直接返回
//            if (!$category->is_directory) {
//                return $data;
//            }
//            // 否则递归调用本方法，将返回值放入 children 字段中
//            $data['children'] = $this->getCategoryTree($category->id, $allCategories);
//
//            return $data;
//        });


   static $data = array();
//    $categories = LibraryCategory::where('p_id', $parentId)->all();
    foreach ($allCategories as $k => $v) {
//        if(isset($v['p_id'])){0
            if($v['p_id']==$parentId){
                $data[] =$v;
                $v['children'] = getCategoryTree($allCategories,$v['id']);
//            }
        }

    }
    return $data;
}


/**
 * 分类列表
 * @param $categories
 * @param int $id
 * @return array|bool
 */
function getCategories($categories, $id = 0,$level = 0)
{

    if (!$categories) {
        return false;
    }
    static $data = array();
    foreach ($categories as $k => $v) {
        if ($v['pid'] == $id) {
            if ($v['children']) {
                $data[] = [
                    'id' => $v['id'],
                    'name' => $v['pid'] ? str_repeat('-', $level * 5 + 1) . $v['name'] : $v['name'],
                    'pid' => $v['pid'],
                    'ids'=>$v['ids'],
                ];
                getCategories($v['children'], $v['id'],$level+1);
            } else {
                $data[] = [
                    'id' => $v['id'],
                    'name' => $v['pid'] ? str_repeat('-', $level * 5 + 1) . $v['name'] : $v['name'],
                    'pid' => $v['pid'],
                    'ids'=>$v['ids'],
                ];
            }
        }
    }
    return $data;
}