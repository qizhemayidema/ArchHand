<?php

namespace app\admin\controller;
use think\Controller;
use think\File;
use think\Request;

class Upload extends Base
{
	//图片上传
    public function upload(){
       $file = request()->file('file');
       $path = request()->post('path');
       $path = 'uploads/'.$path;
       $info = $file->move($path);
       if($info){
           return json(['success'=>true,'msg'=>'图片上传成功','file_path'=>'/'.$path.'/'.$info->getSaveName()]);
        }else{
           return json(['success'=>true,'msg'=>$file->getError(),'file_path'=>$file->getError()]);
        }
    }

    //会员头像上传
    public function uploadUser(){
        $path = request()->param('path');
       $file = request()->file('file');

       $path = 'uploads/'.$path;
       $info = $file->move($path);
       if($info){
            echo $path.'/'.$info->getSaveName();
        }else{
            echo $file->getError();
        }
    }

}