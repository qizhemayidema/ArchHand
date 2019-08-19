<?php

namespace app\common\controller;

use think\Controller;
use think\Request;

class UploadPic extends Controller{

    /**
     * @param null $file_path 保存的目录
     * @param null $form_name 表单中的名称
     * @return \think\response\Json
     */
    public function uploadOnePic($file_path = null,$form_name = 'file')
    {
        if (request()->isPost()){
            $file_path = $file_path ? config('app.upload_root_path') . $file_path : config('app.upload_root_path');
            if (!file_exists('.' . $file_path)) {
                mkdir('.' . $file_path, 0777,true);
            }
            $rules = [
                'ext'   => 'jpeg,jpg,png,gif',
                'size'  => 10 * 1024 * 1024,
            ];
            $file_info = request()->file($form_name)->validate($rules)->move('.'.$file_path);
            if (!$file_info){
                return json(['code'=>0,'msg'=>'格式仅支持jpeg,jpg,png,gif,最大图片为10Mb']);
            }
            $path = $file_info->getSaveName();
            $path = str_replace('\\','/',$path);
            $file_path .= $path;

            return json(['code'=>1,'msg'=>$file_path]);
        }else{
            return json(['code'=>0,'msg'=>'操作失误,请重新操作']);
        }
    }

    /**
     * @param null $file_path
     * @param \think\File $file
     * @return array
     */
    public function uploadOnePicForObject($file_path = null,\think\File $file)
    {
        if (request()->isPost()){
            $file_path = $file_path ? config('app.upload_root_path') . $file_path : config('app.upload_root_path');
            if (!file_exists('.' . $file_path)) {
                mkdir('.' . $file_path, 0777,true);
            }
            $rules = [
                'ext'   => 'jpeg,jpg,png,gif',
                'size'  => 10 * 1024 * 1024,
            ];
            $file_info = $file->validate($rules)->move('.'.$file_path);
            if (!$file_info){
                return ['code'=>0,'msg'=>'格式仅支持jpeg,jpg,png,gif,最大图片为10Mb'];
            }
            $path = $file_info->getSaveName();
            $path = str_replace('\\','/',$path);
            $file_path .= $path;

            return ['code'=>1,'msg'=>$file_path];
        }else{
            return ['code'=>0,'msg'=>'操作失误,请重新操作'];
        }
    }

    /**
     * @param $base64_image_content
     * @param $path
     * @return \think\response\Json
     */
    public function uploadBase64Pic($base64_image_content,$path)
    {
        //匹配出图片的格式
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)) {
            $type = $result[2];
            $new_file = config('app.upload_root_path'). $path . date('Ymd', time()) . "/";
            if (!file_exists('.' . $new_file)) {
                mkdir( '.' . $new_file, 0777,true);
            }
            $file_name = md5(time() . mt_rand(100000000, 999999999));
            $new_file = $new_file . $file_name . ".{$type}";
            if (file_put_contents('.' . $new_file, base64_decode(str_replace($result[1], '', $base64_image_content)))) {
                return ['code'=>1,'msg'=>$new_file];
            } else {
                return ['code'=>0,'msg'=>'error'];
            }
        } else {
            return ['code'=>0,'msg'=>'error1'];
        }
    }
}
