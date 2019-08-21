<?php

namespace app\api\controller;


use app\api\model\ClassesComment as ClassCommentModel;
use app\api\model\Library as LibraryModel;
use app\api\model\LibraryComment as LibraryCommentModel;
use app\api\model\Classes as ClassesModel;
use think\Exception;
use think\Request;
use think\Validate;


class ClassesComment extends Base
{
    //add
    public function add(Request $request)
    {
        $data = $request->post();
        $commentModel = new ClassCommentModel();
        $rules = [
            'content'   => 'require',
            'class_id'  => 'require',
        ];

        $messages = [
            'content.require'   => '必须携带 content',
            'class_id.require'  => '必须携带 class_id',
        ];

        $validate = new Validate($rules,$messages);
        if (!$validate->check($data)){
            return json(['code'=>0,'msg'=>$validate->getError()]);
        }
        //加载默认配置
        $config =  \HTMLPurifier_Config::createDefault();
//       //设置白名单
//        $config->set('HTML.Allowed','p');
//       //实例化对象
        $purifier = new \HTMLPurifier($config);
        $data['content'] = $purifier->purify($data['content']);

        $commentModel->startTrans();
        try{
            $commentModel->insert([
                'user_id'   => $this->userInfo['id'],
                'create_time' => time(),
                'class_id'  => $data['class_id'],
                'comment'   => $data['content'],
            ]);
//            $this->addUserIntegralHistory();
            $commentModel->commit();
        }catch (Exception $e){
            $commentModel->rollback();
        }

        return json(['code'=>1,'msg'=>'success']);
    }

    //like
    public function readList(Request $request)
    {
        $data = $request->post();
        $rules = [
            'page'      => 'require',
            'page_length' => 'require',
            'class_id'  => 'require',
        ];

        $messages = [
            'page.require'  => '参数携带 page',
            'page_length.require'   => '参数携带 page_length',
            'class_id.require'  => '参数携带 class_id'
        ];

        $validate = new Validate($rules,$messages);
        if (!$validate->check($data)){
            return json(['code'=>0,'msg'=>$validate->getError()]);
        }
    }

    /**
     * 删除评论
     * @return \think\response\Json
     */
    public function remove()
    {
        $comment_id = request()->post('comment_id');
        if (!$comment_id){ return json(['code'=>0,'msg'=>'缺少comment_id']);}
        $commentModel = new ClassCommentModel();
        try{
            $commentInfo = $commentModel->find($comment_id);
            if($commentInfo['user_id'] != $this->userInfo['id']) throw new Exception('只能删除自己的评论');
            $res = $commentModel->where(['id'=>$comment_id,'user_id'=>$this->userInfo['id'],'status'=>1])->delete();
            if(!$res) throw new Exception('删除失败');
//            (new ClassesModel())->where(['id'=>$commentInfo['class_id']])->setDec('comment_num');
        }catch (Exception $e){
            return json(['code'=>0,'msg'=>'删除失败']);
        }
        return json(['code'=>1,'msg'=>'success']);
    }
}
