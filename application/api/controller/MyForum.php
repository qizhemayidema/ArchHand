<?php
/**
 * Created by PhpStorm.
 * User: 刘彪
 * Date: 2019/8/26
 * Time: 16:28
 */

namespace app\api\controller;


use think\Request;
use think\Validate;
use app\api\model\Forum as ForumModel;
use app\api\model\ForumComment as ForumCommentModel;
use app\api\model\ForumManager as ForumManagerModel;
use app\api\model\UserCollect as UserCollectModel;
use app\api\model\ForumApplyForManager as ApplyModel;


class MyForum extends Base
{
    public $manager_info = [];

    public $is_manager = false;     //是否拥有管理员身份


    public function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub

        //查看用户是否管理组成员
        $user_id = $this->userInfo['id'];

        $managerInfo = (new ForumManagerModel())->where(['id' => $user_id])->select()->toArray();

        if ($managerInfo) {
            $this->manager_info = $managerInfo;
            $this->is_manager = true;
        }
    }

    //我的发布
    public function myPublish(Request $request)
    {
        $data = $request->post();
        $rules = [
            'page' => 'require',
            'page_length' => 'require',

        ];
        $messages = [
            'page.require' => 'page必须携带',
            'page_length.require' => 'page_length必须携带',
        ];
        $validate = new Validate($rules, $messages);
        if (!$validate->check($data)) {
            return json(['code' => 0, 'msg' => $validate->getError()]);
        }
        $user_id = $this->userInfo['id'];
        $start = $data['page'] * $data['page_length'] - $data['page_length'];

        $res = (new ForumModel())->where(['user_id' => $user_id, 'is_delete' => 0]);
        $count = $res->count();
        $res = $res->field('id,name,is_classics,is_top,is_original,pic,see_num,like_num,collect_num,comment_num,create_time')
            ->order('id', 'desc')->limit($start, $data['page_length'])->select();

        return json(['code' => 1, 'data' => $res, 'count' => $count, 'is_manager' => $this->is_manager]);

    }

    //我的回复
    public function myComment(Request $request)
    {
        $data = $request->post();
        $rules = [
            'page' => 'require',
            'page_length' => 'require',
        ];

        $messages = [
            'page.require' => 'page必须携带',
            'page_length.require' => 'page_length必须携带',
        ];
        $validate = new Validate($rules, $messages);
        if (!$validate->check($data)) {
            return json(['code' => 0, 'msg' => $validate->getError()]);
        }
        $start = $data['page'] * $data['page_length'] - $data['page_length'];
        $comment = (new ForumCommentModel())->where(['user_id' => $this->userInfo['id'], 'status' => 1]);
        $count = $comment->count();

        $commentList = $comment->field('content,status,create_time')->order('id', 'desc')
            ->limit($start, $data['page_length'])
            ->select();


        return json(['code' => 1, 'data' => $commentList, 'count' => $count, 'is_manager' => $this->is_manager]);
    }

    //我的收藏
    public function myCollect(Request $request)
    {
        $data = $request->post();
        $rules = [
            'page' => 'require',
            'page_length' => 'require',
        ];

        $messages = [
            'page.require' => 'page必须携带',
            'page_length.require' => 'page_length必须携带',
        ];
        $validate = new Validate($rules, $messages);
        if (!$validate->check($data)) {
            return json(['code' => 0, 'msg' => $validate->getError()]);
        }

        $start = $data['page'] * $data['page_length'] - $data['page_length'];

        $collect = (new UserCollectModel())->alias('collect')->where(['collect.user_id' => $this->userInfo['id']])
            ->where(['type' => '3']);
        $count = $collect->count();
        $collectList = $collect->join('forum forum', 'collect.collect_id = forum.id')
            ->field('forum.name,forum.id,forum.create_time')
            ->order('forum.id', 'desc')
            ->limit($start, $data['page_length'])
            ->select();
        return json(['code' => 1, 'data' => $collectList, 'count' => $count, 'is_manager' => $this->is_manager]);
    }

    //我的申请
    public function myApplyFor(Request $request)
    {
        $data = $request->post();
        $rules = [
            'page' => 'require',
            'page_length' => 'require',
        ];

        $messages = [
            'page.require' => 'page必须携带',
            'page_length.require' => 'page_length必须携带',
        ];
        $validate = new Validate($rules, $messages);
        if (!$validate->check($data)) {
            return json(['code' => 0, 'msg' => $validate->getError()]);
        }

        $start = $data['page'] * $data['page_length'] - $data['page_length'];

        $res = (new ApplyModel())->alias('apply')
            ->join('forum_plate plate', 'plate.id = apply.plate_id')
            ->where(['apply.user_id' => $this->userInfo['id']]);
        $count = $res->count();
        $res = $res->field('plate.id plate_id,plate.plate_img,plate.plate_name,apply.apply_for_desc,apply.create_time')
            ->order('apply.id', 'desc')
            ->limit($start, $data['page_length'])
            ->select();

        return json(['code' => 1, 'data' => $res, 'count' => $count]);

    }

    //我的参与
    public function myJoinIn(Request $request)
    {
        $user_info = $this->userInfo;
        $data = $request->post();
        $rules = [
            'page' => 'require',
            'page_length' => 'require',
        ];

        $messages = [
            'page.require' => 'page必须携带',
            'page_length.require' => 'page_length必须携带',
        ];
        $validate = new Validate($rules, $messages);
        if (!$validate->check($data)) {
            return json(['code' => 0, 'msg' => $validate->getError()]);
        }
        $start = $data['page'] * $data['page_length'] - $data['page_length'];

        $managerList = (new ForumManagerModel())->alias('manager')
            ->join('forum_plate plate', 'plate.id = manager.plate_id')
            ->leftJoin('forum_manager_role role', 'role.id = manager.role_id')
            ->where(['manager.user_id' => $user_info['id']]);
        $count = $managerList->count();
        $managerList = $managerList->field('plate.plate_img,plate.plate_name,role.role_name,manager.role_id')
            ->order('manager.role_id')
            ->limit($start, $data['page_length'])
            ->select()->toArray();
        foreach ($managerList as $key => $value) {
            if ($value['role_id'] == 0) {
                $managerList[$key]['role_name'] = '版主';
            }
        }
        return json(['code' => 1, 'data' => $managerList, 'count' => $count]);
    }

    //审核   状态 0 未审核 1 通过 2 拒绝
    public function applyForStatus(Request $request)
    {
        $data = $request->post();
        $status = [0, 1, 2];
        $rules = [
            'page' => 'require',
            'page_length' => 'require',
            'status' => 'require',
        ];

        $messages = [
            'page.require' => 'page必须携带',
            'page_length.require' => 'page_length必须携带',
        ];
        $validate = new Validate($rules, $messages);
        if (!$validate->check($data)) {
            return json(['code' => 0, 'msg' => $validate->getError()]);
        }
        if (!in_array($data['status'], $status)) {
            return json(['code' => 0, 'msg' => '操作非法']);
        }
        $start = $data['page'] * $data['page_length'] - $data['page_length'];


        $banPlateList = [];
        $applyModel = new ApplyModel();
        foreach ($this->manager_info as $key => $value) {
            if ($value['role_id'] == 0) {
                $banPlateList[] = $value['plate_id'];
            }
        }
        if (!$banPlateList) {
            return json(['code' => 0, 'msg' => '操作越权']);
        }
        $applyInfo = $applyModel->alias('apply')
            ->join('forum_plate plate', 'plate.id = apply.plate_id')
            ->join('user user', 'user.id = apply.user_id')
            ->whereIn('apply.plate_id', $banPlateList)
            ->where(['status' => $data['status']]);
        $count = $applyInfo->count();
        $applyInfo = $applyInfo
            ->field('plate.plate_name,user.avatar_url,user.nickname,apply.apply_for_desc,apply.create_time')
            ->order('apply.id')
            ->limit($start, $data['page_length'])
            ->select()->toArray();

        return json(['code' => 1, 'data' => $applyInfo, 'count' => $count]);
    }


}