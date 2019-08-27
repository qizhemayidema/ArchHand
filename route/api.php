<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/8/19
 * Time: 10:17
 */

use think\facade\Request;
use think\facade\Route;
use Upyun\Config;
use Upyun\Upyun;


Route::group('api', function () {
    //主页
    Route::get('index', 'api/Index/index');
    //公共配置
    Route::get('config', 'api/Config/index');
    //获取分类和属性
    Route::get('library/category', 'api/LibraryCategory/index');

    //云库首页
    Route::get('library/index', 'api/Library/index');
    //云库添加
    Route::post('library/save', 'api/Library/save');
    //云库显示
    Route::post('library/show', 'api/Library/show');
    //云库修改
    Route::patch('library', 'api/Library/update');
    //云库点赞
    Route::post('library/like', 'api/Library/like');
    //云库收藏
    Route::post('library/collect', 'api/Library/collect');
    //购买文库
    Route::post('library/buy', 'api/Library/buy');
    //云库下载
    Route::post('library/download', 'api/Library/download');

    //云库评论发布
    Route::post('library/comment/save', 'api/LibraryComment/save');
    //云库评论点赞
    Route::post('library/comment/like', 'api/LibraryComment/like');
    //云库删除自己的评论
    Route::delete('library/comment/delete', 'api/LibraryComment/delete');
    //云库评论
    Route::get('library/comment', 'api/LibraryComment/index');

    //云库删除
    Route::delete('library', 'api/Library/delete');

    //获取VIP
    Route::get('vip', 'api/Vip/index');


});
