<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/8/19
 * Time: 10:17
 */




Route::group('api',function(){
    //公共配置
    Route::get('');
    //获取分类和属性
    Route::get('library/category','api/LibraryCategory/index');
    //云库首页
    Route::get('library/index','api/Library/index');
    //云库添加
    Route::post('library/save','api/Library/save');
    //云库显示
    Route::post('library/show','api/Library/show');
    //云库修改
    Route::put('library/update','api/Library/update');
    //云库删除
    Route::delete('library/:id','api/Library/delete');
    //云库点赞
    Route::post('library/like','api/Library/like');

    //云库评论
    Route::get('library/comment/index','api/LibraryComment/index');
    //云库评论发布
    Route::post('library/comment/save','api/LibraryComment/save');
    //云库评论点赞
    Route::post('library/comment/like','api/LibraryComment/like');
});
