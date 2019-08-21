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
        Route::post('/register','api/Login/getCode');
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

    Route::group('/Comment',function(){
        Route::post('/removeLibrary','api/Comment/removeLibrary');
        Route::post('/removeClass','api/Comment/removeClass');
    });

    Route::group('/Class',function(){
        Route::post('/search','api/Classes/search');
        Route::post('/list','api/Classes/list');
        Route::post('/listMore','api/Classes/listMore');
    });

    Route::post('/Sign_in/getIntegral','api/Sign_in/getIntegral');
    Route::post('/Library/uploadVideo','api/Library/uploadVideo');

});