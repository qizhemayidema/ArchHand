<?php

namespace app\index\controller;

use think\Request;
use think\Validate;
use app\index\model\Forum as ForumModel;
use app\index\model\ForumComment as ForumCommentModel;
use app\index\model\ForumManager as ForumManagerModel;
use app\index\model\ForumPlate as PlateModel;
use app\index\model\UserCollect as UserCollectModel;
use app\index\model\ForumApplyForManager as ApplyModel;

class MyForum extends Base
{
    public $commonPageLength = 10;

    public function index()
    {
        $user_info = $this->userInfo;
        //我的发布
        $publish = (new ForumModel())->where(['user_id' => $user_info['id'], 'is_delete' => 0]);
        $publishCount = $publish->count();
        $publish = $publish->field('id,name,is_classics,is_top,is_original,pic,see_num,like_num,collect_num,comment_num,create_time')
            ->order('id', 'desc')->limit(0, $this->commonPageLength)->select();
        //我的收藏
        $collect = (new UserCollectModel())->alias('collect')->where(['collect.user_id' => $user_info['id']])
            ->where(['type' => '3']);
        $collectCount = $collect->count();
        $collect = $collect->join('forum forum', 'collect.collect_id = forum.id')
            ->field('forum.pic,forum.see_num,forum.like_num,forum.collect_num,forum.comment_num,forum.id,forum.name,forum.id,collect.create_time')
            ->order('collect.create_time', 'desc')
            ->limit(0, $this->commonPageLength)
            ->select();

        //我的评论
        $comment = (new ForumCommentModel())->alias('comment')
            ->join('forum forum','forum.id = comment.forum_id')->where(['comment.user_id' => $user_info['id'], 'status' => 1]);
        $commentCount = $comment->count();

        $comment = $comment->field('forum.id,forum.name,comment.content,comment.like_num,comment.create_time')
            ->order('comment.create_time', 'desc')
            ->limit(0, $this->commonPageLength)
            ->select();
        //我的申请
        $apply = (new ApplyModel())->alias('apply')
            ->join('forum_plate plate', 'plate.id = apply.plate_id')
            ->where(['apply.user_id' => $this->userInfo['id']]);
        $applyCount = $apply->count();
        $apply = $apply->field('plate.id plate_id,plate.plate_img,plate.plate_name,apply.apply_for_desc,apply.create_time,apply.status')
            ->order('apply.id', 'desc')
            ->limit(0,$this->commonPageLength)
            ->select();
        //我的参与
        $manager = (new ForumManagerModel())->alias('manager')
            ->join('forum_plate plate', 'plate.id = manager.plate_id')
            ->leftJoin('forum_manager_role role', 'role.id = manager.role_id')
            ->where(['manager.user_id' => $user_info['id']]);
        $managerCount = $manager->count();
        $manager = $manager->field('plate.id plate_id,plate.plate_img,plate.plate_name,role.role_name,manager.role_id')
            ->order('manager.role_id')
            ->limit(0, $this->commonPageLength)
            ->select()->toArray();
        foreach ($manager as $key => $value) {
            if ($value['role_id'] == 0) {
                $manager[$key]['role_name'] = '版主';
            }
        }

        $this->assign('publish',$publish);
        $this->assign('publish_count',$publishCount);
        $this->assign('collect',$collect);
        $this->assign('collect_count',$collectCount);
        $this->assign('comment',$comment);
        $this->assign('comment_count',$commentCount);
        $this->assign('apply',$apply);
        $this->assign('apply_count',$applyCount);
        $this->assign('manager',$manager);
        $this->assign('manager_count',$managerCount);
        $this->assign('page_length',$this->commonPageLength);

        return $this->fetch();
    }


    //我的发布
    public function getMyPublishList(Request $request)
    {
        $data = $request->post();
        $user_info = $this->userInfo;
        $rules = [
            'page' => 'require',

        ];
        $messages = [
            'page.require' => 'page必须携带',
        ];
        $validate = new Validate($rules, $messages);
        if (!$validate->check($data)) {
            return json(['code' => 0, 'msg' => $validate->getError()]);
        }
        $start = $data['page'] * $this->commonPageLength - $this->commonPageLength;

        $publish = (new ForumModel())->where(['user_id' => $user_info['id'], 'is_delete' => 0])
                ->field('id,name,is_classics,is_top,is_original,pic,see_num,like_num,collect_num,comment_num,create_time')
            ->order('id', 'desc')->limit($start, $this->commonPageLength)->select()->toArray();

        $this->assign('publish',$publish);
//        return json(['code' => 1, 'data' => $res, 'count' => $count, 'is_manager' => $this->is_manager]);
        return json(['code' => 1, 'data' => $this->fetch('my_forum/publish_list')]);

    }

    //我的回复
    public function getMyCommentList(Request $request)
    {
        $data = $request->post();
        $rules = [
            'page' => 'require',

        ];

        $messages = [
            'page.require' => 'page必须携带',
        ];
        $validate = new Validate($rules, $messages);
        if (!$validate->check($data)) {
            return json(['code' => 0, 'msg' => $validate->getError()]);
        }
        $start = $data['page'] * $this->commonPageLength - $this->commonPageLength;
        $comment = (new ForumCommentModel())->alias('comment')
            ->join('forum forum','forum.id = comment.forum_id')->where(['comment.user_id' => $this->userInfo['id'], 'status' => 1])
            ->field('forum.id,forum.name,comment.content,comment.like_num,comment.create_time')
            ->order('comment.create_time', 'desc')
            ->limit($start, $this->commonPageLength)
            ->select();

        $this->assign('comment',$comment);
        return json(['code' => 1, 'data' => $this->fetch('my_forum/comment_list') ]);
    }

    //我的收藏
    public function getMyCollectList(Request $request)
    {
        $data = $request->post();
        $rules = [
            'page' => 'require',
        ];

        $messages = [
            'page.require' => 'page必须携带',
        ];
        $validate = new Validate($rules, $messages);
        if (!$validate->check($data)) {
            return json(['code' => 0, 'msg' => $validate->getError()]);
        }

        $start = $data['page'] * $this->commonPageLength - $this->commonPageLength;
        $collect = (new UserCollectModel())->alias('collect')->where(['collect.user_id' => $this->userInfo['id']])
            ->where(['collect.type' => '3'])->join('forum forum', 'collect.collect_id = forum.id')
            ->field('forum.pic,forum.see_num,forum.like_num,forum.collect_num,forum.comment_num,forum.id,forum.name,forum.id,collect.create_time')
            ->order('collect.create_time', 'desc')
            ->limit($start, $this->commonPageLength)
            ->select();

        $this->assign('collect',$collect);
        return json(['code' => 1, 'data' => $this->fetch('my_forum/collect_list')]);
    }

    //我的申请
    public function getMyApplyList(Request $request)
    {
        $data = $request->post();
        $rules = [
            'page' => 'require',
        ];

        $messages = [
            'page.require' => 'page必须携带',
        ];
        $validate = new Validate($rules, $messages);
        if (!$validate->check($data)) {
            return json(['code' => 0, 'msg' => $validate->getError()]);
        }

        $start = $data['page'] * $this->commonPageLength - $this->commonPageLength;

        $apply = (new ApplyModel())->alias('apply')
            ->join('forum_plate plate', 'plate.id = apply.plate_id')
            ->where(['apply.user_id' => $this->userInfo['id']])
            ->field('plate.id plate_id,plate.plate_img,plate.plate_name,apply.apply_for_desc,apply.create_time,apply.status')
            ->order('apply.id', 'desc')
            ->limit($start,$this->commonPageLength)
            ->select();

        $this->assign('apply',$apply);
        return json(['code' => 1, 'data' => $this->fetch('my_forum/apply_list')]);

    }

    //我的参与
    public function getMyJoinInList(Request $request)
    {
        $user_info = $this->userInfo;
        $data = $request->post();

        $rules = [
            'page' => 'require',
        ];

        $messages = [
            'page.require' => 'page必须携带',
        ];
        $validate = new Validate($rules, $messages);
        if (!$validate->check($data)) {
            return json(['code' => 0, 'msg' => $validate->getError()]);
        }
        $start = $data['page'] * $this->commonPageLength - $this->commonPageLength;

        $manager = (new ForumManagerModel())->alias('manager')
            ->join('forum_plate plate', 'plate.id = manager.plate_id')
            ->leftJoin('forum_manager_role role', 'role.id = manager.role_id')
            ->where(['manager.user_id' => $user_info['id']])
            ->field('plate.id plate_id,plate.plate_img,plate.plate_name,role.role_name,manager.role_id,manager.create_time')
            ->order('manager.role_id')
            ->limit($start, $this->commonPageLength)
            ->select()->toArray();
        foreach ($manager as $key => $value) {
            if ($value['role_id'] == 0) {
                $manager[$key]['role_name'] = '版主';
            }
        }

        $this->assign('manager',$manager);
        return json(['code' => 1, 'data' => $this->fetch('my_forum/manager_list')]);
    }

    //删帖子
    public function delForum(Request $request)
    {
        $data = $request->post();
        $rules = [
            'forum_id' => 'require',
        ];

        $validate = new Validate($rules);
        if (!$validate->check($data)) {
            return json(['code' => 0, 'msg' => $validate->getError()]);
        }

        $forumInfo = (new ForumModel())->where(['id' => $data['forum_id'], 'is_delete' => 0])->find();
        if($forumInfo['user_id'] != $this->userInfo['id']){
            return json(['code'=>0,'msg'=>'操作非法']);
        }
        if(!$forumInfo){
            return json(['code'=>0,'msg'=>'该帖子不存在']);
        }
        (new ForumModel())->where(['id' => $data['forum_id'], 'plate_id' => $forumInfo['plate_id'], 'is_delete' => 0])
                ->update(['is_delete' => 1]);
        $plateInfo = (new PlateModel())->where(['id'=>$forumInfo['plate_id']])->find();
        $comment_num = $forumInfo['comment_num'];
        if($comment_num){
            if ($plateInfo['comment_num'] < $comment_num){
                $plateInfo->save(['comment_num'=>0]);
            }else{
                $plateInfo->setDec('comment_num',$comment_num);
            }
        }
        if ($plateInfo['forum_num'] > 0){
            $plateInfo->setDec('forum_num');
        }

        return json(['code' => 1, 'msg' => 'success']);
    }

    /**
     * 取消申请
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function delApply(Request $request)
    {
        $data = $request->post();
        $user_info = $this->userInfo;
        $rules = [
            'plate_id' => 'require',
        ];

        $messages = [
            'plate_id.require' => 'page必须携带',
        ];
        $validate = new Validate($rules, $messages);
        if (!$validate->check($data)) {
            return json(['code' => 0, 'msg' => $validate->getError()]);
        }

        $apply = (new ApplyModel())->where(['user_id'=>$user_info['id'],'plate_id'=>$data['plate_id']])->find();
        if (!$apply){
            return json(['code'=>0,'msg'=>'不存在这条申请哦~']);
        }
        $apply->delete();
        return json(['code' => 1, 'msg'=>'success']);

    }
}
