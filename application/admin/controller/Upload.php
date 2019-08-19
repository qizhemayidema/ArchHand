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
//        $file = request()->file('file');
//        $path = request()->post('path');
        $file_path = '/static/images/';
        $file = $request->file('file');
        $file_info = $file->validate(['size' => 2097152, 'ext' => 'jpg,png,git'])->move('.' . $file_path);
        if (!$file_info) {
            return json(['success' => false, 'msg' => $file->getError(), 'file_path' => $file->getError()]);
        }
        $path = $file_info->getSaveName();
        $path = str_replace('\\', '/', $path);
        $file_path .= $path;

        return json(['success' => true, 'msg' => '图片上传成功', 'file_path' => $file_path]);
    }

    //会员头像上传
    public function uploadUser()
    {
        $pic = (new Pic())->uploadOnePic(\request());
        $path = $pic->getData();
        echo $path['message'];
    }

}