<?php

namespace app\admin\controller;

use app\http\middleware\LoginCheck;
use think\Controller;
use think\Request;

class Pic extends Controller {

    protected $middleware = [LoginCheck::class];

    public function uploadOnePic(Request $request)
    {
        if ($request->isPost()){
            $file_path = '/static/images/';
            $file_info = $request->file('file')->move('.'.$file_path);
            $path = $file_info->getSaveName();
            $path = str_replace('\\','/',$path);
            $file_path .= $path;

            return json(['valid'=>1,'message'=>$file_path]);
        }
    }
}
