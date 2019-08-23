<?php

namespace app\admin\controller;

use app\admin\model\ForumManager;
use app\common\controller\UploadPic;
use think\Exception;
use think\Request;
use app\admin\model\ForumCategory as CateModel;
use app\admin\model\ForumPlate as PlateModel;
use app\admin\model\User as UserModel;
use app\admin\model\ForumManager as ForumManagerModel;
use think\Validate;

class ForumPlate extends Base
{
    public function index(Request $request)
    {
        $cate_id = $request->param('cate_id') ?? 0;
        $plateInfo = (new CateModel())->alias('cate')
            ->join('forum_plate plate','plate.cate_id = cate.id');
        if ($cate_id){
            $plateInfo = $plateInfo->where(['cate_id'=>$cate_id]);
        }
        $plateInfo = $plateInfo->join('forum_manager manager','manager.plate_id = plate.id')
            ->join('user user','user.id = manager.user_id')
            ->field('plate.plate_img,cate.cate_name,user.id user_id,user.avatar_url,user.nickname,plate.id,plate.plate_name,plate.forum_num,plate.comment_num')
            ->order('id','desc')
            ->paginate(20,false,['query'=>$request->param()]);

        $cateInfo = (new CateModel())->select();

        $this->assign('plate_info',$plateInfo);
        $this->assign('cate_id',$cate_id);
        $this->assign('cate_info',$cateInfo);
        return $this->fetch();
    }

    public function add(Request $request)
    {
        $cateList = (new CateModel())->select();

        $this->assign('cate_list',$cateList);

        return $this->fetch();
    }

    public function save(Request $request)
    {
        $data = $request->post();

        $rules = [
            'plate_img'      => 'require',
            'cate_id'        => 'require',
            'plate_name'       => 'require',
            'plate_man_phone'  => 'require|max:11|regex:/^1[34578]\d{9}$/',
        ];

        $messages = [
            'plate_img.require'     => '板块封面必须上传',
            'cate_id.require'       => 'error',
            'plate_name.require'    => '名称必须填写',
            'plate_man_phone.require'       => '必须选择一个版主',
            'plate_man_phone.max'   => '版主手机号最长位数 11位',
            'plate_man_phone.regex' => '手机号不合法',
        ];

        $validate = new Validate($rules,$messages);

        if (!$validate->check($data)){
            return json(['code'=>0,'msg'=>$validate->getError()]);
        }

        $plateModel = new PlateModel();
        $reData = $plateModel->where(['cate_id'=>$data['cate_id'],'plate_name'=>$data['plate_name']])->find();
        if($reData){
            return json(['code'=>0,'msg'=>'此板块已存在']);
        }
        //查看版主是否存在
        $plateManInfo = (new UserModel())->where(['phone'=>$data['plate_man_phone']])->find();
        if(!$plateManInfo){
            return json(['code'=>0,'msg'=>'被选为版主的用户不存在']);
        }

//        //查看将要指定的版主是否为此板块管理组成员
//        (new ForumManagerModel())->where([''])
        $plateModel->startTrans();
        try{
            //入库
            $plateModel->insert([
                'plate_name'    => $data['plate_name'],
                'cate_id'       => $data['cate_id'],
                'plate_img'     => $data['plate_img'],
            ]);
            $plate_id = $plateModel->getLastInsID();
            (new ForumManager())->insert([
                'plate_id'  =>  $plate_id,
                'user_id'   =>  $plateManInfo['id'],
                'role_id'   =>  0
            ]);
            $plateModel->commit();
        }catch(Exception $e){
            return json(['code'=>0,'msg'=>'操作失败']);
            $plateModel->rollback();
        }

        return json(['code'=>1,'msg'=>'success']);
    }

    public function read(Request $request)
    {
        $plate_id = $request->param('plate_id');

        $cateList = (new CateModel())->select();

        $this->assign('cate_list',$cateList);

        $plateInfo = (new PlateModel())->find($plate_id);

        $user_id = (new ForumManagerModel())->where(['plate_id'=>$plate_id,'role_id'=>0])->value('user_id');

        $plateInfo['phone'] = (new UserModel())->where(['id'=>$user_id])->value('phone');

        $this->assign('plate_info',$plateInfo);

        return $this->fetch();

    }

    public function update(Request $request)
    {
        $data = $request->post();

        $rules = [
            'id'             => 'require',
            'plate_img'      => 'require',
            'cate_id'        => 'require',
            'plate_name'       => 'require',
            'plate_man_phone'  => 'require|max:11|regex:/^1[34578]\d{9}$/',
        ];

        $messages = [
            'id.require'            => 'error',
            'cate_id.require'       => 'error',
            'plate_img.require'     => '必须上传封面图',
            'plate_name.require'    => '名称必须填写',
            'plate_man_phone.require' => '必须选择一个版主',
            'plate_man_phone.max'   => '版主手机号最长位数 11位',
            'plate_man_phone.regex' => '手机号不合法',
        ];

        $validate = new Validate($rules,$messages);

        if (!$validate->check($data)){
            return json(['code'=>0,'msg'=>$validate->getError()]);
        }

        $plateModel = new PlateModel();
        $reData = $plateModel->find($data['id']);
        if(!$reData){
            return json(['code'=>0,'msg'=>'此模块不存在']);
        }
        //查看版主是否存在
        $plateManInfo = (new UserModel())->where(['phone'=>$data['plate_man_phone']])->find();
        if(!$plateManInfo){
            return json(['code'=>0,'msg'=>'被选为版主的用户不存在']);
        }

        //查看将要指定的版主是否为此板块管理组成员
        $flag = (new ForumManagerModel())->where(['plate_id'=>$data['id'],'user_id'=>$plateManInfo['id']])->where('role_id','<>',0)->find();

        $plateModel->startTrans();
        try{
            //入库
            $plateModel->where(['id'=>$data['id']])->update([
                'plate_name'    => $data['plate_name'],
                'cate_id'       => $data['cate_id'],
                'plate_img'    => $data['plate_img'],
            ]);
            $plate_id = $data['id'];
            if($flag){
                $flag->save(['role_id'=>0]);
            }else{
                //修改原先的版主id
                (new ForumManagerModel())->where(['plate_id'=>$data['id'],'user_id'=>$plateManInfo['id'],'role_id'=>0])
                    ->update(['user_id'=>$plateManInfo['id']]);
            }
            $plateModel->commit();
        }catch(Exception $e){
            return json(['code'=>0,'msg'=>'操作失败']);
            $plateModel->rollback();
        }

        return json(['code'=>1,'msg'=>'success']);
    }

    public function delete(Request $request)
    {
        $id = $request->param('tag_id');

        $tagInfo = (new TagModel())->find($id);
        if(file_exists($tagInfo['tag_img'])){
            unlink('.' . $tagInfo['tag_img']);
        }
        //删除tag表
        (new TagModel())->where(['id'=>$id])->delete();
        //删除课程 tag_list
        (new TagListModel())->where(['tag_id'=>$id])->delete();

        return json(['code'=>1,'msg'=>'success']);
    }

    public function uploadPic()
    {
        $path = 'forum_plate/';
        return (new UploadPic())->uploadOnePic($path);
    }
}