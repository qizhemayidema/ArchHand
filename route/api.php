<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/8/19
 * Time: 10:17
 */


Route::group('api',function(){
    //获取分类和属性
    Route::get('library/category','api/LibraryCategory/index');
    //云库首页
    Route::get('library/index','api/Library/index');
    //云库添加
    Route::post('library/save','api/Library/save');
    //云库显示
    Route::get('library/show','api/Library/show');
    //云库修改
    Route::put('library/update','api/Library/update');
    //云库删除
    Route::delete('library/:id','api/Library/delete');
});
