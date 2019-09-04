<?php
///**
// * Created by PhpStorm.
// * User: Administrator
// * Date: 2019/8/19
// * Time: 10:17
// */
//
//use think\facade\Request;
//use think\facade\Route;
//use Upyun\Config;
//use Upyun\Upyun;
//
//
//Route::group('api', function () {
//    //主页
//    Route::get('index', 'api/Index/index');
//    //公共配置
//    Route::get('config', 'api/Config/index');
//    //获取分类和属性
//    Route::get('library/category', 'api/LibraryCategory/index');
//
//    //云库首页
//    Route::get('library/index', 'api/Library/index');
//    //云库添加
//    Route::post('library/save', 'api/Library/save');
//    //云库显示
//    Route::post('library/show', 'api/Library/show');
//    //云库修改
//    Route::patch('library', 'api/Library/update');
//    //云库点赞
//    Route::post('library/like', 'api/Library/like');
//    //云库收藏
//    Route::post('library/collect', 'api/Library/collect');
//    //购买文库
//    Route::post('library/buy', 'api/Library/buy');
//    //云库下载
//    Route::post('library/download', 'api/Library/download');
//
//    //云库评论发布
//    Route::post('library/comment/save', 'api/LibraryComment/save');
//    //云库评论点赞
//    Route::post('library/comment/like', 'api/LibraryComment/like');
//    //云库删除自己的评论
//    Route::delete('library/comment/delete', 'api/LibraryComment/delete');
//    //云库评论
//    Route::get('library/comment', 'api/LibraryComment/index');
//
//    //云库删除
//    Route::delete('library', 'api/Library/delete');
//
//    //获取VIP
//    Route::get('vip', 'api/Vip/index');
//
//    Route::group('/Login', function () {
//        Route::post('/getCode', 'api/Login/getCode');
//        Route::post('/register', 'api/Login/register');
//        Route::post('/login', 'api/Login/login');
//        Route::post('/rePwd', 'api/Login/rePwd');
//        Route::post('/getCsrf', 'api/Login/getCsrf');
//    });
//    Route::group('/User', function () {
//        Route::post('/basicInfo', 'api/User/basicInfo');
//        Route::post('/updateBasicInfo', 'api/User/updateBasicInfo');
//        Route::post('/updatePassword', 'api/User/updatePassword');
//    });
//    Route::group('/My_user', function () {
//        Route::post('/integralHistory', 'api/My_user/integralHistory');
//        Route::post('/info', 'api/My_user/info');
//
//    });
//
//    Route::group('/My_library', function () {
//        Route::post('/myPublish', 'api/My_library/myPublish');
//        Route::post('/myComment', 'api/My_library/myComment');
//        Route::post('/myDownload', 'api/My_library/myDownload');
//        Route::post('/myCollect', 'api/My_library/myCollect');
//        Route::post('/myBuy', 'api/My_library/myBuy');
//    });
//    Route::group('/My_class', function () {
//        Route::post('/myCollect', 'api/My_class/myCollect');
//        Route::post('/myBuy', 'api/My_class/myBuy');
//    });
//    Route::group('/Collect', function () {
//        Route::post('/removeClass', 'api/Collect/removeClass');
//        Route::post('/removeLibrary', 'api/Collect/removeLibrary');
//    });
//    Route::group('/Class', function () {
//        Route::post('/search', 'api/Classes/search');
//        Route::post('/list', 'api/Classes/list');
//        Route::post('/listMore', 'api/Classes/listMore');
//        Route::post('/info', 'api/Classes/info');
//        Route::post('/seeVideo', 'api/Classes/seeVideo');
//        Route::post('/buy', 'api/Classes/buy');
//        Route::post('/collect', 'api/Classes/collect');
//    });
//    Route::group('/Class_comment', function () {
//        Route::post('/add', 'api/ClassesComment/add');
//        Route::post('/readList', 'api/ClassesComment/readList');
//        Route::post('/remove', 'api/ClassesComment/remove');
//        Route::post('/like', 'api/ClassesComment/like');
//    });
//    Route::group('/Sign_in', function () {
//        Route::post('/getIntegral', 'api/Sign_in/getIntegral');
//        Route::post('/getSignInDays', 'api/Sign_in/getSignInDays');
//    });
//    Route::group('/Forum', function () {
//        Route::post('/getAllPlate', 'api/Forum/getAllPlate');
//        Route::post('/getPlateForCate', 'api/Forum/getPlateForCate');
//        Route::post('/getCate', 'api/Forum/getCate');
//        Route::post('/save', 'api/Forum/save');
//        Route::post('/plate', 'api/Forum/plate');
//        Route::post('/info', 'api/Forum/info');
//        Route::post('/collect', 'api/Forum/collect');
//        Route::post('/like', 'api/Forum/like');
//        Route::post('/likeComment', 'api/Forum/likeComment');
//        Route::post('/comment', 'api/Forum/comment');
//        Route::post('/joinInManager', 'api/Forum/joinInManager');
//    });
//
//    Route::group('/Forum_manager', function () {
//        Route::post('/classics', 'api/Forum_manager/classics');
//        Route::post('/top', 'api/Forum_manager/top');
//        Route::post('/delForum', 'api/Forum_manager/delForum');
//        Route::post('/delComment', 'api/Forum_manager/delComment');
//        Route::post('/saveRole', 'api/Forum_manager/saveRole');
//        Route::post('/updateRole', 'api/Forum_manager/updateRole');
//        Route::post('/delRole', 'api/Forum_manager/delRole');
//        Route::post('/permission', 'api/Forum_manager/permission');
//        Route::post('/roleList', 'api/Forum_manager/roleList');
//        Route::post('/role', 'api/Forum_manager/role');
//        Route::post('/giveRole', 'api/Forum_manager/giveRole');
//        Route::post('/shotOffManager', 'api/Forum_manager/shotOffManager');
//        Route::post('/checkManagerJoin', 'api/Forum_manager/checkManagerJoin');
//        Route::post('/changeJoinChannel', 'api/Forum_manager/changeJoinChannel');
//    });
//    Route::post('/Library/uploadVideo', 'api/Library/uploadVideo');
//});
