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

Route::group('/api',function(){

    Route::group('/Login',function(){
        Route::post('/getCode','api/Login/getCode');
        Route::post('/register','api/Login/register');
        Route::post('/login','api/Login/login');
        Route::post('/rePwd','api/Login/rePwd');
        Route::post('/getCsrf','api/Login/getCsrf');
    });
    Route::group('/User',function(){
        Route::post('/basicInfo','api/User/basicInfo');
        Route::post('/updateBasicInfo','api/User/updateBasicInfo');
        Route::post('/updatePassword','api/User/updatePassword');
    });
    Route::group('/My_user',function (){
        Route::post('/integralHistory','api/My_user/integralHistory');
        Route::post('/info','api/My_user/info');

    });
    Route::group('/My_library',function (){
        Route::post('/myPublish','api/My_library/myPublish');
        Route::post('/myComment','api/My_library/myComment');
        Route::post('/myDownload','api/My_library/myDownload');
        Route::post('/myCollect','api/My_library/myCollect');
        Route::post('/myBuy','api/My_library/myBuy');
    });
    Route::group('/My_class',function(){
        Route::post('/myCollect','api/My_class/myCollect');
        Route::post('/myBuy','api/My_class/myBuy');
    });
    Route::group('/Collect',function(){
        Route::post('/removeClass','api/Collect/removeClass');
        Route::post('/removeLibrary','api/Collect/removeLibrary');
    });
    Route::group('/Class',function(){
        Route::post('/search','api/Classes/search');
        Route::post('/list','api/Classes/list');
        Route::post('/listMore','api/Classes/listMore');
        Route::post('/info','api/Classes/info');
        Route::post('/seeVideo','api/Classes/seeVideo');
        Route::post('/buy','api/Classes/buy');
        Route::post('/collect','api/Classes/collect');
    });
    Route::group('/Class_comment',function(){
        Route::post('/add','api/ClassesComment/add');
        Route::post('/readList','api/ClassesComment/readList');
        Route::post('/remove','api/ClassesComment/remove');
        Route::post('/like','api/ClassesComment/like');
    });
    Route::group('/Sign_in',function(){
        Route::post('/getIntegral','api/Sign_in/getIntegral');
        Route::post('/getSignInDays','api/Sign_in/getSignInDays');
    });
    Route::group('/Forum',function(){
        Route::post('/getAllPlate','api/Forum/getAllPlate');
        Route::post('/getPlateForCate','api/Forum/getPlateForCate');
        Route::post('/getCate','api/Forum/getCate');
        Route::post('/save','api/Forum/save');
        Route::post('/plate','api/Forum/plate');
        Route::post('/info','api/Forum/info');
        Route::post('/collect','api/Forum/collect');
        Route::post('/like','api/Forum/like');
        Route::post('/likeComment','api/Forum/likeComment');
        Route::post('/comment','api/Forum/comment');
    });
    Route::post('/Library/uploadVideo','api/Library/uploadVideo');
});