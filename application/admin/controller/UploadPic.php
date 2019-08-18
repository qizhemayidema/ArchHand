<?php

namespace app\admin\controller;

use app\http\middleware\LoginCheck;
use think\Controller;
use think\Request;

class Pic extends Controller {

    protected $middleware = [LoginCheck::class];

    public function uploadOnePic(Request $request,$file_path = null)
    {
        if ($request->isPost()){
            $file_path = $file_path ?? '/static/images/';
            $rules = [
                'ext'   => 'jpeg,jpg,png,gif',
                'size'  => 10 * 1024 * 1024,
            ];
            $file_info = $request->file('file')->validate($rules)->move('.'.$file_path);
            if (!$file_info){
                return json(['code'=>0,'valid'=>1,'msg'=>'格式仅支持jpeg,jpg,png,gif,最大图片为10Mb']);
            }
            $path = $file_info->getSaveName();
            $path = str_replace('\\','/',$path);
            $file_path .= $path;

            return json(['code'=>1,'valid'=>1,'message'=>$file_path]);
        }else{
            return json(['code'=>0,'valid'=>1,'msg'=>'操作失误,请重新操作']);
        }
    }


    public function uploadBase64Pic($base64_image_content,$path)
    {
        //匹配出图片的格式
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)) {
            $type = $result[2];
            $new_file = $path . "/" . date('Ymd', time()) . "/";
            if (!file_exists($new_file)) {
                mkdir($new_file, 0777);
            }
            $file_name = md5(time() . mt_rand(100000000, 999999999));
            $new_file = $new_file . $file_name . ".{$type}";
            if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $base64_image_content)))) {
                return json(['code'=>1,'msg'=>'/' . $new_file]);
            } else {
                return json(['code'=>0,'msg'=>'error']);
            }
        } else {
            return json(['code'=>0,'msg'=>'error1']);
        }
    }
}
