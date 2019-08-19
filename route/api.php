<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/8/19
 * Time: 10:17
 */


Route::group('api',function(){
    Route::get('library/category','api/LibraryCategory/index');



    Route::get('library/index','api/Library/index');
    Route::get('library/create','api/Library/add');
    Route::post('library/save','api/Library/save');
    Route::get('library/:id','api/Library/show');
    Route::get('library/edit/:id','api/Library/read');
    Route::put('library/update','api/Library/update');
    Route::delete('library/delete','api/Library/delete');
});
