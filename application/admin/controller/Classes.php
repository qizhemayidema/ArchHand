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

use app\common\controller\UploadPic;


class Classes extends Controller
{

    public function index()
    {
        $classInfo = (new ClassModel)->alias('class')
                        ->join('class_category cate','class.cate_id = cate.id')
                        ->where(['class.is_delete'=>0])
                        ->field('class.id,cate.cate_name,class.name,class.see_num,class.learn_num,class.integral,class.create_time')
                        ->order('class.id','desc')
                        ->paginate(15);
        $this->assign('cate_info',$classInfo);
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
            'class_pic'     => 'require|max:120',
            'desc'          => 'require',
            'free_chapter'  => 'require|number',
            'integral'      => 'require|number|max:9',
            'name'          => 'require|max:18',
        ];
        $messages = [
            'cate_id.require'       => '必须选择分类',
            'cate_id.number'        => '分类传参错误',
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
        $chapter_rules = [
            'chapter_num'   => 'require|number',
            'chapter_title' => 'require|max:20',
            'chapter_video' => 'require',
        ];
        $chapter_messages = [
            'chapter_num.require'   => '课程章节必须填写',
            'chapter_num.number'    => '章节必须为数字',
            'chapter_title.require' => '课程名称必须填写',
            'chapter_title.max'     => '课程名称最大长度为20',
            'chapter_video.require' => '课程视频必须上传',
        ];
        //验证课程相关数据
        $chapterValidate = new Validate($chapter_rules,$chapter_messages);
        foreach ($data['chapter_video'] as $key => $value){
            $temp = [
                'chapter_num'   => $data['chapter_num'][$key],
                'chapter_title' => $data['chapter_title'][$key],
                'chapter_video' => $data['chapter_video'][$key]
            ];
            if (!$chapterValidate->check($temp)){
                return json(['code'=>0,'msg'=>$chapterValidate->getError()]);
            }
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
            'chapter_sum'   => count($data['chapter_video']),
        ];
        $classModel->startTrans();
        try{
            $classModel->insert($classSet);
            $classId = $classModel->getLastInsID();
            //入库 使用标签表
            if (isset($data['tag_id']) && $data['tag_id']){
                $tagSet = [];
                foreach ($data['tag_id'] as $key => $value){
                    $tagSet[] = [
                        'tag_id'    => $value,
                        'class_id'  => $classId,
                    ];
                }
                $classTagListModel = new ClassTagListModel();
                $classTagListModel->insertAll($tagSet);
            }
            //入库 课程视频表
            //上传课程封面图片
            $file_info = $request->file('chapter_pic');
            $chapter_set = [];  //课程视频表数据集合

            foreach ($file_info as $key => $value){

                $file_path = $this->uploadChapterPic($value);
                if ($file_path['code'] == 1){
                    $chapter_pic = $file_path['msg'];
                }else{
                    throw new Exception($file_path['msg']);
                }
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
            $messages = $e->getMessage();
            if ($messages == 'no file to uploaded'){
                $messages = '有课程视频的封面图没有上传';
            }
            return json(['code'=>0,'msg'=>$messages.'--'.$e->getLine()]);
        }


        return json(['code'=>1,'msg'=>'success']);
    }

    public function read(Request $request)
    {
        $id = $request->param('id');

        //查询课程主表信息
        $classInfo = (new ClassModel())->find($id);

        //查询课程详细信息 按照章节排列
        $chapterInfo = (new ClassChapterModel())->where(['class_id'=>$id])->order('chapter_num')->select()->toArray();

        //课程选中的标签
        $classTagIds = (new ClassTagListModel())->where(['class_id'=>$id])->column('tag_id');


        //获取分类数据
        $cateList = (new ClassCateModel())->select()->toArray();

        //获取选取分类的标签数据
        $classTagModel = new ClassTagModel();
        $tagList = $classTagModel->where(['cate_id'=>$classInfo['cate_id']])->select();


        $this->assign('cate_list',$cateList);
        $this->assign('tag_list',$tagList);
        $this->assign('class_tag_ids',$classTagIds);
        $this->assign('class_info',$classInfo);
        $this->assign('chapter_info',$chapterInfo);
        $this->assign('chapter_count',count($chapterInfo));

        return $this->fetch();
    }

    public function update(Request $request)
    {
        $data = $request->post();
        $classModel = new ClassModel();
        $classTagModel = new ClassTagListModel();
        $chapterModel = new ClassChapterModel();
        $rules = [
            'class_id'      => 'require',
            'cate_id'       => 'require|number',
            'class_pic'     => 'require|max:120',
            'desc'          => 'require',
            'free_chapter'  => 'require|number',
            'integral'      => 'require|number|max:9',
            'name'          => 'require|max:18',
        ];
        $messages = [
            'class_id.require'      => '操作失误,请稍后再试',
            'cate_id.require'       => '必须选择分类',
            'cate_id.number'        => '分类传参错误',
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
        $chapter_rules = [
            'chapter_num'   => 'require|number',
            'chapter_title' => 'require|max:20',
            'chapter_video' => 'require',
        ];
        $chapter_messages = [
            'chapter_num.require'   => '课程章节必须填写',
            'chapter_num.number'    => '章节必须为数字',
            'chapter_title.require' => '课程名称必须填写',
            'chapter_title.max'     => '课程名称最大长度为20',
            'chapter_video.require' => '课程视频必须上传',
        ];
        //验证课程相关数据
        $chapterValidate = new Validate($chapter_rules,$chapter_messages);
        if (isset($data['chapter_video'])){
            foreach ($data['chapter_video'] as $key => $value){
                $temp = [
                    'chapter_num'   => $data['chapter_num'][$key],
                    'chapter_title' => $data['chapter_title'][$key],
                    'chapter_video' => $data['chapter_video'][$key]
                ];
                if (!$chapterValidate->check($temp)){
                    return json(['code'=>0,'msg'=>$chapterValidate->getError()]);
                }
            }
        }
        $oldChapterSet = [];
        if (isset($data['old_chapter_id'])){
            foreach ($data['old_chapter_id'] as $key => $value){
                $temp = [
                    'chapter_num'   => $data['old_chapter_num'][$key],
                    'chapter_title' => $data['old_chapter_title'][$key],
                    'chapter_video' => $data['old_chapter_video_url'][$key]
                ];
                if (!$chapterValidate->check($temp)){
                    return json(['code'=>0,'msg'=>$chapterValidate->getError()]);
                }
                $temp['id'] = $value;
                $temp['chapter_pic'] = $data['old_chapter_pic'][$key];
                $oldChapterSet[$value] = $temp;
            }
        }

        //组装 标签ids
        $tagSet = [];
        if (isset($data['tag_id'])){
            foreach ($data['tag_id'] as $key => $value){
                $tagSet[] = [
                    'class_id'  => $data['class_id'],
                    'tag_id'    => $value,
                ];
            }
        }

        //组装课程主表数据
        $classSet = [
            'name'      => $data['name'],
            'class_pic' => $data['class_pic'],
            'cate_id'   => $data['cate_id'],
            'desc'      => $data['desc'],
            'free_chapter' => $data['free_chapter'],
            'integral'  => $data['integral'],
            //差一个总节数  chapter_sum
        ];

        //组装课程视频表数据
        $chapter_set = [];
        //课程总数量
        $chapter_num = 0;

        $classModel->startTrans();
        try{
            $upyun_config = new Config(env('UPYUN.SERVICE_NAME'), env('UPYUN.USERNAME'), env('UPYUN.PASSWORD'));
            $upyun = new Upyun($upyun_config);

            //删除所有标签
            $classTagModel->where(['class_id'=>$data['class_id']])->delete();
            //录入新的标签
            $classTagModel->insertAll($tagSet);
            //取出所有的视频
            $oldChapterInfo = $chapterModel->where(['class_id'=>$data['class_id']])->select();

            //更新 or 删除 原有的课程视频
            foreach ($oldChapterInfo as $key => $value){
                //判断是否被删除了 如果删除过 则 也删除掉这个
                if (!in_array($value['id'],$data['old_chapter_id'])){
                    //删除视频
                    $upyun->delete($value['source_url'],true);
                    //删除图片
                    if (file_exists($value['pic'])){
                        unlink('.' . $value['pic']);
                    }
                    //从数据库删除
                    $chapterModel->where(['id'=>$value['id']])->delete();
                }else{
                    //如果没有被删除  则修改
                    $updateChapterSet = [
                        'title'         => $oldChapterSet[$value['id']]['chapter_title'],
                        'pic'           => $oldChapterSet[$value['id']]['chapter_pic'],
                        'chapter_num'   => $oldChapterSet[$value['id']]['chapter_num'],
                        'source_url'    => $oldChapterSet[$value['id']]['chapter_video'],
                    ];
                    //判断图片是否更新了
                    if ($data['old_chapter_new_pic_base64'][$value['id']]){
                       $oldChapterPicRes = $this->uploadChapterBase64Pic($data['old_chapter_new_pic_base64'][$value['id']]);
                       if ($oldChapterPicRes['code'] == 0){
                           throw new Exception($oldChapterPicRes['msg']);
                       }
                        $updateChapterSet['pic'] = $oldChapterPicRes['msg'];
                    }
                    $chapterModel->where(['id'=>$value['id']])->update($updateChapterSet);
                }
                $chapter_num ++;
            }
            //添加新的视频
            $file_info = $request->file('chapter_pic');
            if ($file_info){
                foreach ($file_info as $key => $value){

                    $file_path = $this->uploadChapterPic($value);
                    if ($file_path['code'] == 1){
                        $chapter_pic = $file_path['msg'];
                    }else{
                        throw new Exception($file_path['msg']);
                    }

                    $temp = [
                        'class_id'      => $data['class_id'],
                        'title'         => $data['chapter_title'][$key],
                        'pic'           => $chapter_pic,
                        'chapter_num'   => $data['chapter_num'][$key],
                        'source_url'    => $data['chapter_video'][$key],
                    ];
                    $chapter_set[] = $temp;
                    $chapter_num ++;
                }
                $chapterModel->insertAll($chapter_set);
            }

            //更新 课程主表
            $classSet['chapter_sum'] = $chapter_num;
            $classModel->where(['id'=>$data['class_id']])->update($classSet);
            $classModel->commit();
        }catch (Exception $e){
            $classModel->rollback();
            $messages = $e->getMessage();
            if ($messages == 'no file to uploaded'){
                $messages = '有课程视频的封面图没有上传';
            }
            return json(['code'=>0,'msg'=>$messages.'--'.$e->getLine()]);
        }

        return json(['code'=>1,'msg'=>'success']);
    }

    public function show(Request $request)
    {
        $id = $request->param('id');        //课程id

        //查询课程主表信息
        $classInfo = (new ClassModel)->alias('class')
            ->join('class_category cate','class.cate_id = cate.id')
            ->field('cate.cate_name,class.*')
            ->where(['class.id'=>$id])
            ->find();

        //查询课程详细信息 按照章节排列
        $chapterInfo = (new ClassChapterModel())->where(['class_id'=>$id])->order('chapter_num')->select()->toArray();


        $this->assign('class_info',$classInfo);
        $this->assign('chapter_info',$chapterInfo);

        return $this->fetch();
        //预览视频功能
    }

    public function delete(Request $request)
    {
        $id = $request->param('id');
        (new ClassModel())->where(['id'=>$id])->update(['is_delete'=>1]);
        (new ClassTagListModel())->where(['class_id'=>$id])->delete();
        return json(['code'=>1,'msg'=>'success']);
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

    //上传课程封面
    public function uploadPic()
    {
        $path = 'class/';
        return (new UploadPic())->uploadOnePic($path);
    }

    //上传视频封面
    public function uploadChapterPic($file)
    {
        //上传课程封面图片
        $path = 'class_chapter/';
        return ((new UploadPic())->uploadOnePicForObject($path,$file));
    }

    //上传视频封面 base64
    public function uploadChapterBase64Pic($base64_content)
    {
        $path = 'class_chapter/';
        return ((new UploadPic())->uploadBase64Pic($base64_content,$path));
    }
}
