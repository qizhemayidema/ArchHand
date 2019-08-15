<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;
use Upyun\Upyun;
use Upyun\Config;
use Upyun\Signature;
use Upyun\Util;

use app\admin\model\ClassesCategory as ClassCateModel;
use app\admin\model\ClassesTag as ClassTagModel;

class Classes extends Controller
{

    public function index()
    {
        return $this->fetch();
    }

    public function add()
    {
//        $config = new Config(env('UPYUN.SERVICE_NAME'),env('UPYUN.USERNAME'),env('UPYUN.PASSWORD'));
//        $client = new Upyun($config);
//        $client->write('/test', 'file content');
        //计算签名
//        $service_name = env('UPYUN.SERVICE_NAME');
//        $username = env('UPYUN.USERNAME');
//        $password = env('UPYUN.PASSWORD');
//        $uri = $service_name;

        //获取分类数据
        $cateList = (new ClassCateModel())->select()->toArray();

        //获取默认第一个的标签数据
        $classTagModel = new ClassTagModel();
        $tagList = isset($cateList[0]) ? $classTagModel->where(['cate_id'=>$cateList[0]['id']])->select()->toArray() : [];

        $this->assign('cate_list',$cateList);
        $this->assign('tag_list',$tagList);

        return $this->fetch();
    }
    public function test1()
    {
        return $this->fetch('classes/test');
    }

    public function uploadVideo()
    {
        $config = new Config(env('UPYUN.SERVICE_NAME'), env('UPYUN.USERNAME'), env('UPYUN.PASSWORD'));
        $config->setFormApiKey(env('UPYUN.API_KEY'));

        $data['save-key'] = $_GET['save_path'];
        $data['expiration'] = time() + 120;
        $data['bucket'] = env('UPYUN.SERVICE_NAME');
        $policy = Util::base64Json($data);
        $method = 'POST';
        $uri = '/' . $data['bucket'];
        $signature = Signature::getBodySignature($config, $method, $uri, null, $policy);
        echo json_encode(array(
            'policy' => $policy,
            'authorization' => $signature
        ));
    }
}
