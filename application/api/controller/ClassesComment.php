<?php

namespace app\api\controller;


use app\api\model\ClassesComment as ClassCommentModel;
use app\api\model\Classes as ClassesModel;
use app\api\model\ClassesCommentLikeHistory as CCLHModel;
use app\api\model\UserIntegralHistory;
use think\Exception;
use think\Request;
use think\Validate;


class ClassesComment extends Base
{
    //add
    public function add(Request $request)
    {
        $user_info = $this->userInfo;
        $data = $request->post();
        $commentModel = new ClassCommentModel();
        $rules = [
            'content' => 'require',
            'class_id' => 'require',
        ];

        $messages = [
            'content.require' => '必须携带 content',
            'class_id.require' => '必须携带 class_id',
        ];

        $validate = new Validate($rules, $messages);
        if (!$validate->check($data)) {
            return json(['code' => 0, 'msg' => $validate->getError()]);
        }
        //加载默认配置
        $config = \HTMLPurifier_Config::createDefault();
//       //设置白名单
//        $config->set('HTML.Allowed','p');
//       //实例化对象
        $purifier = new \HTMLPurifier($config);
        $data['content'] = $purifier->purify($data['content']);

        $commentModel->startTrans();
        try {
            $commentModel->insert([
                'user_id' => $user_info['id'],
                'create_time' => time(),
                'class_id' => $data['class_id'],
                'comment' => $data['content'],
            ]);
            //判断他是否加筑手币
            $day_count = $this->getConfig('comment_integral_count');
            $integral = $this->getConfig('comment_integral');
            $today_timestamp = strtotime(date('Y-m-d', time()));
            $count = (new UserIntegralHistory())
                ->where(['user_id' => $user_info['id'], 'type' => 6])
                ->where('create_time', '>', $today_timestamp)
                ->limit($day_count)->count();
            if ($count != $day_count) {
                $this->addUserIntegralHistory(6, $integral);
            }
            (new ClassesModel())->where(['id' => $data['class_id']])->setInc('comment_num');

            $commentModel->commit();
        } catch (\Exception $e) {
            $commentModel->rollback();
            return json(['code'=>0,'msg'=>'操作失败']);
        }

        return json(['code' => 1, 'msg' => 'success']);
    }

    /**
     * 获取评论列表
     * @param Request $request
     * @return \think\response\Json
     */
    public function readList(Request $request)
    {
        $data = $request->post();
        $rules = [
            'page' => 'require',
            'page_length' => 'require',
            'class_id' => 'require',
        ];

        $messages = [
            'page.require' => '参数携带 page',
            'page_length.require' => '参数携带 page_length',
            'class_id.require' => '参数携带 class_id'
        ];

        $validate = new Validate($rules, $messages);
        if (!$validate->check($data)) {
            return json(['code' => 0, 'msg' => $validate->getError()]);
        }
        $start = $data['page'] * $data['page_length'] - $data['page_length'];

        $classCommentModel = new ClassCommentModel();
        $likeModel = new CCLHModel();

        $result = $classCommentModel->alias('comment')
            ->join('user user', 'comment.user_id = user.id')
            ->where(['comment.class_id' => $data['class_id'], 'comment.status' => 1])
            ->field('comment.id comment_id,comment.comment,comment.like_num,comment.create_time,user.nickname,user.avatar_url')
            ->order('comment.id', 'desc')->limit($start, $data['page_length'])
            ->select()->toArray();

        if ($request->param('token')) {
            foreach ($result as $key => $value) {
                $result[$key]['is_like'] = $likeModel->where(['comment_id' => $value['comment_id'], 'user_id' => $this->userInfo['id']])->value('comment_id') ? true : false;
            }
        }
        $count = $classCommentModel
            ->where(['class_id' => $data['class_id'], 'status' => 1])
            ->count();


        return json(['code' => 1, 'msg' => 'success', 'data' => $result, 'count' => $count]);
    }

    /**
     * 删除评论
     * @return \think\response\Json
     */
    public function remove()
    {
        $comment_id = request()->post('comment_id');
        if (!$comment_id) {
            return json(['code' => 0, 'msg' => '缺少comment_id']);
        }
        $commentModel = new ClassCommentModel();
        $commentModel->startTrans();
        try {
            $commentInfo = $commentModel->find($comment_id);
            if ($commentInfo['user_id'] != $this->userInfo['id']) throw new Exception('只能删除自己的评论');
            $res = $commentModel->where(['id' => $comment_id, 'user_id' => $this->userInfo['id'], 'status' => 1])->delete();
            if (!$res) throw new Exception('删除失败');
            if($commentInfo['comment_num'] > 0){
                (new ClassesModel())->where(['id' => $commentInfo['class_id']])->setDec('comment_num');
            }
            $commentModel->where(['id' => $comment_id])->delete();
            $commentModel->commit();
        } catch (\Exception $e) {
            $commentModel->rollback();
            return json(['code' => 0, 'msg' => '删除失败']);
        }
        return json(['code' => 1, 'msg' => 'success']);
    }


    /**
     * 点赞
     */
    public function like(Request $request)
    {
        $data = $request->post();
        $user_info = $this->userInfo;
        $rules = [
            'comment_id' => 'require',
        ];
        $messages = [
            'comment_id.require' => '请携带comment_id',
        ];

        $validate = new Validate($rules, $messages);
        if (!$validate->check($data)) {
            return json(['code' => 0, 'msg' => $validate->getError()]);
        }


        $commentModel = new ClassCommentModel();
        $likeModel = new CCLHModel();
        if ($likeModel->where(['comment_id' => $data['comment_id'], 'user_id' => $user_info['id']])->find()) {
            return json(['code' => 0, 'msg' => '您已经点过赞啦,去看看别的评论吧~']);
        }
        $commentModel->startTrans();
        try {
            $commentModel->where(['id' => $data['comment_id']])->setInc('like_num');
            $likeModel->insert([
                'comment_id' => $data['comment_id'],
                'user_id' => $user_info['id'],
            ]);
            $commentModel->commit();
        } catch (\Exception $e) {
            $commentModel->rollback();
            return json(['code' => 0, 'msg' => '操作失误']);
        }
        return json(['code' => 1, 'msg' => 'success']);
    }
}
