<?php

namespace app\api\controller;

use think\Controller;
use think\Db;
use think\Request;
use app\api\model\LibraryComment as LibraryCommentModel;
use app\api\validate\LibraryComment as LibraryCommentValidate;
use app\api\model\Library as LibraryModel;
use think\Exception;

class LibraryComment extends Base
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index(Request $request)
    {
        $library_id = $request->get('library_id');
        try {
            $comment = LibraryCommentModel::field('u.nickname,u.avatar_url,l.id,l.comment,l.like_num,l.create_time')->alias('l')
                ->join('user u', 'l.user_id=u.id')
                ->where('status', 1)->where('library_id', $library_id)->paginate(15);

            return json(['code' => 1, 'msg' => '查询成功', 'data' => $comment], 200);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '查询失败'], 400);
        }
    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        $data = $request->post();

        $user = $this->userInfo;

        $data['user_id'] = $user['id'];
        $validate = new LibraryCommentValidate();
        if (!$validate->check($data)) {
            return json(['code' => 0, 'msg' => $validate->getError()]);
        }
        //加载默认配置
        $config =  \HTMLPurifier_Config::createDefault();
//       //设置白名单
//        $config->set('HTML.Allowed','p');
//       //实例化对象
        $purifier = new \HTMLPurifier($config);
//        //过滤
        $data['comment'] = $purifier->purify($data['comment']);

        $data['create_time'] = time();
        Db::startTrans();
        try {
            $comment = LibraryCommentModel::create($data, ['user_id', 'library_id', 'comment', 'create_time']);
            if ($comment) {
                $library = (new LibraryModel())->where('id', $data['library_id'])->find();
                $library->comment_num = $library->comment_num + 1;
                $library->save();

                //TODO:加积分
                Db::commit();
                return json(['code' => 1, 'msg' => '发布成功'], 201);
            } else {
                return json(['code' => 0, 'msg' => '发布失败'], 400);
            }
        } catch (\Exception $e) {
            Db::rollback();
            return json(['code' => 0, 'msg' => '发布失败'], 400);
        }
    }

    /**
     * 评论点赞
     * @param Request $request
     * @return \think\response\Json
     */
    public function like(Request $request)
    {
        $user = $this->userInfo;
        $comment_id = $request->post('comment_id');

        Db::startTrans();
        try {
            if ($comment_id) {
                $comment_like_user = Db::name('library_comment_like_history')
                    ->where('comment_id',$comment_id)->where('user_id',$user['id'])
                    ->count('comment_id');

                if($comment_like_user){
                   return json(['code'=>0,'msg'=>'当前评论以点赞，不能重复点赞']);
                }

                $comment = (new LibraryCommentModel())->where('id', $comment_id)->find();
                if(!$comment){
                    return json(['code'=>0,'msg'=>'数据走丢啦，刷新后试试吧']);
                }
                $comment->like_num = $comment->like_num + 1;
                $comment->save();

                $comment_like_user = Db::name('library_comment_like_history')->insert(['comment_id' => $comment->id, 'user_id' => $user['id']]);

                if ($comment_like_user) {
                    Db::commit();
                    return json(['code' => 1, 'msg' => '点赞成功'], 200);
                } else {
                    return json(['code' => 0, 'msg' => '点赞失败'], 400);
                }
            }else{
                return json(['code'=>0,'msg'=>'缺少必要参数']);
            }
        } catch (\Exception $e) {
            Db::rollback();
            return json(['code' => 0, 'msg' => '点赞失败'], 400);
        }

    }

    public function delete()
    {
        $comment_id = request()->post('comment_id');
        if (!$comment_id){ return json(['code'=>0,'msg'=>'缺少comment_id']);}
        $commentModel = new LibraryCommentModel();
        try{
            $commentInfo = $commentModel->find($comment_id);
            if($commentInfo['user_id'] != $this->userInfo['id']) throw new Exception('只能删除自己的评论');
            $res = $commentModel->where(['id'=>$comment_id,'user_id'=>$this->userInfo['id'],'status'=>1])->delete();
            if(!$res) throw new Exception('删除失败');
            (new LibraryModel())->where(['id'=>$commentInfo['library_id']])->setDec('comment_num');
        }catch (Exception $e){
            return json(['code'=>0,'msg'=>'删除失败']);
        }

        return json(['code'=>1,'msg'=>'success']);
    }
}
