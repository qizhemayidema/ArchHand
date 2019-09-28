<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------


use think\facade\Route;

Route::group('/', function () {
    //首页
    Route::get('/', 'index/Index/index')->name('index');
    //社区板块模块
    Route::group('forumPlate', function () {
        //添加角色页面  弹框中使用
        Route::get('/manager/:plate_id/addRole', 'index/PlateManager/addRole')->name('forumPlateManagerRoleAdd');
        //编辑角色页面  弹框中使用
        Route::get('/manager/:plate_id/editRole', 'index/PlateManager/readRole')->name('forumPlateManagerRoleEdit');
        //社区版主管理页
        Route::get('/manager/:plate_id', 'index/PlateManager/index')->name('forumPlateManagerIndex');

        //社区列表页面
        Route::get('/:plate_id/[:type]', 'index/Plate/index')->name('forumPlateIndex');
    });

    //社区模块
    Route::group('forum', function () {
        //社区发布页面
        Route::get('/publish', 'index/Forum/add')->name('forumAdd');
        Route::post('/publish', 'index/Forum/save')->name('forumAddAction');
        //社区帖子详情页
        Route::get('/:forum_id', 'index/Forum/info')->name('forumInfo');
    });
    //云库首页
    Route::group('library', function () {
        //云库首页
        Route::get('/', 'index/Library/index')->name('libraryIndex');
        //云库发布页
        Route::get('/publish', 'index/Library/add')->name('libraryAdd');
        //云库修改页
        Route::get('/edit/:library_id', 'index/Library/edit')->name('libraryEdit');
        //云库详情页
        Route::get('/:library_id', 'index/Library/info')->name('libraryInfo');
    });
    //云库店铺首页
    Route::group('store', function () {
        Route::get('/:store_id', 'index/Store/index')->name('storeIndex');
    });
    //课程模块
    Route::group('class', function () {
        //首页
        Route::get('/', 'index/Classes/index')->name('classIndex');
        //搜索列表页
        Route::get('/search','index/Classes/search')->name('classSearch');
        //课程列表页
        Route::get('/list/:cate_id', 'index/Classes/list')->name('classList');
        //课程详情页
        Route::get('/:class_id', 'index/Classes/info')->name('classInfo');
    });
    //我的xxx模块
    Route::group('my', function () {
        //我的信息页面
        Route::get('info', 'index/My_info/index')->name('myInfo');
        //我的社区
        Route::get('forum', 'index/My_forum/index')->name('myForum');
        //我的云库
        Route::get('library', 'index/My_library/index')->name('myLibrary');
        //我的课程
        Route::get('class', 'index/My_classes/index')->name('myClass');
        //账户信息
        Route::get('account', 'index/My_account/index')->name('myAccount');

    })->middleware(\app\http\middleware\IndexCheckLoginStatus::class);
    //VIP
    Route::get('vip', 'index/Vip/index')->name('vipIndex');
    //帮助
    Route::get('help', 'index/Help/index')->name('help');
    //页脚
    Route::get('info', 'index/Footer/index')->name('footerIndex');

    Route::group('pay',function(){
        Route::get('/','index/Pay/index')->name('payIndex');
    })->middleware(\app\http\middleware\IndexCheckLoginStatus::class);

    Route::group('transfer',function(){
        Route::get('/','index/Transfer/index')->name('transferIndex');

    })->middleware(\app\http\middleware\IndexCheckLoginStatus::class);

    //签到模块
    Route::group('signIn', function () {
        //签到块
        Route::get('/', 'index/SignIn/index')->name('signIn');
        //签到动作
        Route::post('/', 'index/SignIn/signIn')->name('signInAction');
    });
});