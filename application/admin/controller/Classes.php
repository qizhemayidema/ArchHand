<?php

namespace app\admin\controller;

use think\Controller;
use think\Exception;
use think\Request;
use think\Validate;
use Upyun\Upyun;
use Upyun\Config;
use Upyun\Signature;
use Upyun\Util;

use app\admin\model\ClassesCategory as ClassCateModel;
use app\admin\model\ClassesTag as ClassTagModel;
use app\admin\model\Classes as ClassModel;
use app\admin\model\ClassesTagList as ClassTagListModel;
use app\admin\model\ClassesChapter as ClassChapterModel;

class Classes extends Controller
{

    public function index()
    {
        return $this->fetch();
    }

    public function add()
    {
        //获取分类数据
        $cateList = (new ClassCateModel())->select()->toArray();

        //获取默认第一个的标签数据
        $classTagModel = new ClassTagModel();
        $tagList = isset($cateList[0]) ? $classTagModel->where(['cate_id'=>$cateList[0]['id']])->select()->toArray() : [];

        $this->assign('cate_list',$cateList);
        $this->assign('tag_list',$tagList);

        return $this->fetch();
    }

    public function save(Request $request)
    {
        $data = $request->post();
        $classModel = new ClassModel();
        $rules = [
            'cate_id'       => 'require|number',
            'chapter_num'   => 'require',
            'chapter_title' => 'require',
            'chapter_video' => 'require',
            'class_pic'     => 'require|max:120',
            'desc'          => 'require',
            'free_chapter'  => 'require|number',
            'integral'      => 'require|number|max:9',
            'name'          => 'require|max:18',
        ];
        $messages = [
            'cate_id.require'       => '必须选择分类',
            'cate_id.number'        => '分类传参错误',
            'chapter_num.require'   => '课程章节必须填写',
            'chapter_title.require' => '课程名称必须填写',
            'chapter_video.require' => '课程视频必须上传',
            'class_pic.require'     => '封面图必须上传',
            'class_pic.max'         => '封面图上传失误',
            'desc.require'          => '介绍必须填写',
            'free_chapter.require'  => '第几节前免费必须填写',
            'free_chapter.number'   => '第几节前免费必须为数字',
            'integral.require'      => '筑手币必须填写',
            'integral.number'       => '筑手币必须为数字',
            'integral.max'          => '筑手币非法',
            'name.require'          => '课程名称必须填写',
            'name.max'              => '课程名称最大长度为18',
        ];
        $validate = new Validate($rules,$messages);
        if (!$validate->check($data)){
            return json(['code'=>0,'msg'=>$validate->getError()]);
        }
        //验证课程相关数据
        foreach ($data['chapter_video'] as $key => $value){
         $chapter_num = $data['chapter_num'][$key];
         $chapter_title = $data['chapter_title'][$key];
         $chapter_video = $data['chapter_video'][$key];
            if (!$chapter_num){return json(['code'=>0,'msg'=>'课程章节必须填写']);}
            if (!$chapter_title){return json(['code'=>0,'msg'=>'课程名称必须填写']);}
            if (!$chapter_video){return json(['code'=>0,'msg'=>'课程视频必须上传']);}
        }
        //拼接课程 数据表数据
        $classSet = [
            'cate_id'       => $data['cate_id'],
            'name'          => $data['name'],
            'desc'          => $data['desc'],
            'class_pic'     => $data['class_pic'],
            'free_chapter'  => $data['free_chapter'],
            'integral'      => $data['integral'],
            'create_time'    => time(),
        ];
        $classModel->startTrans();
        try{
            $classModel->insert($classSet);
            $classId = $classModel->getLastInsID();
            //入库 使用标签表
            $tagSet = [];
            foreach ($data['tag_id'] as $key => $value){
                $tagSet[] = [
                    'tag_id'    => $value,
                    'class_id'  => $classId,
                ];
            }
            $classTagListModel = new ClassTagListModel();
            $classTagListModel->insertAll($tagSet);
            //入库 课程视频表
            //上传课程封面图片
            $file_path = '/static/upload/images/';
            $file_info = $request->file('chapter_pic');
            $rules = [
                'ext'   => 'jpeg,jpg,png,gif',
                'size'  => 10 * 1024 * 1024,
            ];
            $chapter_set = [];  //课程视频表数据集合

            foreach ($file_info as $key => $value){
                $file_temp = $value->validate($rules)->move('.'.$file_path);
                if (!$file_temp){
                    return json(['code'=>0,'msg'=>'error']);
                }
                $path = $file_temp->getSaveName();
                $path = str_replace('\\','/',$path);
                $chapter_pic = $file_path . $path;
                $chapter_num = $data['chapter_num'][$key];
                $chapter_title = $data['chapter_title'][$key];
                $chapter_video = $data['chapter_video'][$key];

                $temp = [
                    'class_id'      => $classId,
                    'title'         => $chapter_title,
                    'pic'           => $chapter_pic,
                    'chapter_num'   => $chapter_num,
                    'source_url'    => $chapter_video,
                ];
                $chapter_set[] = $temp;
            }

            (new ClassChapterModel())->insertAll($chapter_set);

            $classModel->commit();
        }catch (Exception $e){
            $classModel->rollback();
            return json(['code'=>0,'msg'=>$e->getMessage().'--'.$e->getLine()]);
        }


        return json(['code'=>1,'msg'=>'success']);
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
