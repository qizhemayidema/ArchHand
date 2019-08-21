<?php

namespace app\api\controller;

use think\Controller;
use think\Request;

use Upyun\Upyun;
use Upyun\Config;
use Upyun\Signature;
use Upyun\Util;

class Library extends Base
{
    /**
     * 又拍云 文库上传接口
     * @throws \Exception
     */
    public function uploadVideo()
    {
        $config = new Config(env('UPYUN.SERVICE_NAME'), env('UPYUN.USERNAME'), env('UPYUN.PASSWORD'));
        $config->setFormApiKey(env('UPYUN.API_KEY'));

        //创建目录
        $path = 'library/' . $this->userInfo['id'] . '/';
        (new Upyun($config))->createDir($path);

        $real_path = $path . $_POST['save_file_name'];

        $data['save-key'] = $real_path;

        $data['expiration'] = time() + 120;
        $data['bucket'] = env('UPYUN.SERVICE_NAME');
        $policy = Util::base64Json($data);
        $method = 'POST';
        $uri = '/' . $data['bucket'];
        $signature = Signature::getBodySignature($config, $method, $uri, null, $policy);
        echo json_encode(array(
            'policy' => $policy,
            'authorization' => $signature,
            'service_name' => env('UPYUN.SERVICE_NAME'),
            'path'  => $real_path,
        ));
    }
}
