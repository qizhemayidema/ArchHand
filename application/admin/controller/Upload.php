<?php

namespace app\admin\controller;

use think\Controller;
use think\File;
use think\Request;

class Upload extends Base
{
    //图片上传
    public function upload(Request $request)
    {
        $upload = (new UploadPic())->uploadOnePic();

        $upload = $upload->getData();
        if ($upload['code'] == 1) {
            return json(['success' => true, 'msg' => '图片上传成功', 'file_path' => $upload['msg']]);
        } else {
            return json(['success' => false, 'msg' => $upload['msg'], 'file_path' => '']);
        }
    }

    //会员头像上传
    public function uploadUser()
    {
        $pic = (new UploadPic())->uploadOnePic();
        $path = $pic->getData();
        echo $path['msg'];
    }

}