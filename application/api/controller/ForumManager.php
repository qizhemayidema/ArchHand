<?php

namespace app\api\controller;

use think\Request;
use think\Validate;
use app\api\model\Forum as ForumModel;
use app\api\model\ForumComment as ForumCommentModel;
use app\api\model\ForumManager as ForumManagerModel;
use app\api\model\ForumManagerRole as ForumManagerRoleModel;
use app\api\model\ForumManagerPermission as ForumManagerPermissionModel;
use app\api\model\ForumApplyForManager as ForumApplyForManagerModel;
use app\api\model\User as UserModel;
use app\api\model\ForumPlate as PlateModel;


class ForumManager extends ForumManagerBase
{
    //加精 or 去掉加精华
    public function classics(Request $request)
    {
        $data = $request->post();
        $rules = [
            'forum_id' => 'require',
        ];
        $validate = new Validate($rules);
        if (!$validate->check($data)) {
            return json(['code' => 0, 'msg' => $validate->getError()]);
        }
        $forumInfo = (new ForumModel())->where(['id' => $data['forum_id'], 'plate_id' => $data['plate_id'], 'is_delete' => 0])->find();
        if (!$forumInfo) {
            return json(['code' => 0, 'msg' => '该帖子不存在']);
        }
        $status = $forumInfo['is_classics'] == 0 ? 1 : 0;
        $forumInfo->save(['is_classics' => $status]);

        return json(['code' => 1, 'msg' => 'success']);
    }

    //置顶 or 取消置顶
    public function top(Request $request)
    {
        $data = $request->post();
        $rules = [
            'forum_id' => 'require',
        ];

        $validate = new Validate($rules);
        if (!$validate->check($data)) {
            return json(['code' => 0, 'msg' => $validate->getError()]);
        }
        $forumInfo = (new ForumModel())->where(['id' => $data['forum_id'], 'plate_id' => $data['plate_id'], 'is_delete' => 0])->find();

        if (!$forumInfo) {
            return json(['code' => 0, 'msg' => '该帖子不存在']);
        }
        $status = $forumInfo['is_top'] == 0 ? 1 : 0;
        $forumInfo->save(['is_top' => $status]);

        return json(['code' => 1, 'msg' => 'success']);
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

        (new ForumModel())->where(['id' => $data['forum_id'], 'plate_id' => $data['plate_id'], 'is_delete' => 0])
            ->update(['is_delete' => 1]);

        return json(['code' => 1, 'msg' => 'success']);
    }

    //删评论
    public function delComment(Request $request)
    {
        $data = $request->post();
        $rules = [
            'forum_comment_id' => 'require',
        ];

        $validate = new Validate($rules);
        if (!$validate->check($data)) {
            return json(['code' => 0, 'msg' => $validate->getError()]);
        }
        $forum_id = (new ForumCommentModel())->where(['id' => $data['forum_comment_id']])->value('forum_id');
        if (!$forum_id) {
            return json(['code' => 0, 'msg' => '该评论不存在']);

        }
        $forumInfo = (new ForumModel())->where(['id' => $forum_id, 'plate_id' => $data['plate_id'], 'is_delete' => 0])->find();

        if (!$forumInfo) {
            return json(['code' => 0, 'msg' => '该帖子不存在']);
        }
        (new ForumCommentModel())->where(['id' => $data['forum_comment_id']])->update(['status'=>0]);
        return json(['code' => 1, 'msg' => 'success']);
    }

    //创建角色
    public function saveRole(Request $request)
    {
        $data = $request->post();
        $rules = [
            'role_name' => 'require|max:30',
            'role_desc' => 'require|max:100',
            'permission_ids' => 'require',
        ];

        $messages = [
            'role_name.require' => '角色名称必须填写',
            'role_name.max' => '角色名称最多长度为30',
            'role_desc.require' => '角色备注必须填写',
            'role_desc.max' => '角色备注最大长度为100',
            'permission_ids.require' => '必须选择权限',
        ];

        $validate = new Validate($rules, $messages);
        if (!$validate->check($data)) {
            return json(['code' => 0, 'msg' => $validate->getError()]);
        }

        //入库 forum_role表
        $result = [
            'plate_id' => $data['plate_id'],
            'role_name' => $data['role_name'],
            'role_desc' => $data['role_desc'],
            'permission_ids' => implode(',', $data['permission_ids']),
        ];

        (new ForumManagerRoleModel())->insert($result);

        return json(['code' => 0, 'msg' => 'success']);
    }

    //修改角色
    public function updateRole(Request $request)
    {
        $data = $request->post();
        $rules = [
            'role_id' => 'require',
            'role_name' => 'require|max:30',
            'role_desc' => 'require|max:100',
            'permission_ids' => 'require',
        ];

        $messages = [
            'role_name.require' => '角色名称必须填写',
            'role_name.max' => '角色名称最多长度为30',
            'role_desc.require' => '角色备注必须填写',
            'role_desc.max' => '角色备注最大长度为100',
            'permission_ids.require' => '必须选择权限',
        ];

        $validate = new Validate($rules, $messages);
        if (!$validate->check($data)) {
            return json(['code' => 0, 'msg' => $validate->getError()]);
        }

        //修改 forum_role表
        $result = [
            'role_name' => $data['role_name'],
            'role_desc' => $data['role_desc'],
            'permission_ids' => implode(',', $data['permission_ids']),
        ];

        (new ForumManagerRoleModel())->where(['id' => $data['role_id']])->update($result);

        return json(['code' => 0, 'msg' => 'success']);
    }

    //删除角色
    public function delRole(Request $request)
    {
        $data = $request->post();
        $rules = [
            'role_id' => 'require',
        ];

        $messages = [
            'role_id.require' => '非法操作',
        ];

        $validate = new Validate($rules, $messages);
        if (!$validate->check($data)) {
            return json(['code' => 0, 'msg' => $validate->getError()]);
        }
        //判断是否有用户在使用
        $res = (new ForumManagerModel())->where(['plate_id' => $data['plate_id'], 'role_id' => $data['role_id']])->find();
        if ($res) {
            return json(['code' => 0, 'msg' => '有管理员正在使用此角色,不能删除']);
        }
        (new ForumManagerRoleModel())->where(['id' => $data['id']])->delete();

        return json(['code' => 1, 'msg' => 'success']);

    }

    //获取权限信息
    public function permission(Request $request)
    {
        $list = (new ForumManagerPermissionModel())
            ->field('id,name')->select();
        return json(['code' => 1, 'data' => $list]);
    }

    //获取所有角色信息
    public function roleList(Request $request)
    {
        $data = $request->post();

        //获取角色名称 角色备注
        $roleList = (new ForumManagerRoleModel())->where(['plate_id' => $data['plate_id']])->select()->toArray();
        foreach ($roleList as $key => $value) {
            $roleList['permission'] = (new ForumManagerPermissionModel())->whereIn('id', $value['permission_ids'])->select();
        }

        return json(['code' => 1, 'msg' => $roleList]);
    }

    //获取一个角色信息
    public function role(Request $request)
    {
        $data = $request->post();
        $rules = [
            'role_id' => 'require',
        ];

        $messages = [
            'role_id.require' => '缺少参数',
        ];

        $validate = new Validate($rules, $messages);
        if (!$validate->check($data)) {
            return json(['code' => 0, 'msg' => $validate->getError()]);
        }
        //获取角色名称 角色备注
        $roleList = (new ForumManagerRoleModel())->where(['plate_id' => $data['plate_id'], 'id' => $data['role_id']])->find()->toArray();
        if (!$roleList) {
            return json(['code' => 0, 'msg' => '没有此角色']);
        }
        $roleList['permission'] = (new ForumManagerPermissionModel())->whereIn('id', $roleList['permission_ids'])->select();

        return json(['code' => 1, 'msg' => $roleList]);
    }

    //分配角色
    public function giveRole(Request $request)
    {
        $data = $request->post();
        $rules = [
            'user_id' => 'require',
            'role_id' => 'require',
        ];

        $messages = [
            'role_id.require' => '缺少参数',
            'user_id.require' => '缺少参数2',
        ];

        $validate = new Validate($rules, $messages);
        if (!$validate->check($data)) {
            return json(['code' => 0, 'msg' => $validate->getError()]);
        }
        //检查是否有此角色
        $roleInfo = (new ForumManagerRoleModel())->where(['plate_id' => $data['plate_id'], 'id' => $data['role_id']])->find()->toArray();
        if (!$roleInfo) {
            return json(['code' => 0, 'msg' => '没有此角色']);
        }
        //检查用户是否管理组人员
        $manager = (new ForumManagerModel())->where(['plate_id' => $data['plate_id'], 'user_id' => $data['user_id']])->find();
        if (!$manager) {
            return json(['code' => 0, 'msg' => '该用户不是管理组人员']);
        }
        if ($manager['role_id'] == 0) {
            return json(['code' => 0, 'msg' => '非法操作']);
        }

        (new ForumManagerModel())->where(['plate_id' => $data['plate_id'], 'user_id' => $data['user_id']])->update(['role_id' => $data['role_id']]);

        return json(['code' => 0, 'msg' => 'success']);
    }

    //踢出管理组
    public function shotOffManager(Request $request)
    {
        $data = $request->post();
        $rules = [
            'user_id' => 'require',
        ];

        $messages = [
            'user_id.require' => '缺少参数2',
        ];

        $validate = new Validate($rules, $messages);
        if (!$validate->check($data)) {
            return json(['code' => 0, 'msg' => $validate->getError()]);
        }

        //查看 是否此板块的管理组
        $manager = (new ForumManagerModel())
            ->where(['plate_id' => $data['plate_id'], 'user_id' => $data['user_id']])
            ->find();
        if (!$manager || $manager['role_id'] == 0) {
            return json(['不是此管理组成员']);
        }
        $manager->delete();
        return json(['code' => 1, 'msg' => 'success']);
    }

    //审核同意用户进入管理组申请
    public function checkManagerJoin(Request $request)
    {
        $data = $request->post();
        $rules = [
            'apply_for_manager_id' => 'require',
            'role_id' => 'require',
            'status' => 'require|number',
        ];

        $messages = [
            'apply_for_manager_id.require' => '缺少参数',
            'role_id.require' => '缺少参数2',
            'status.require' => '状态必须选择',
        ];

        $validate = new Validate($rules, $messages);
        if (!$validate->check($data)) {
            return json(['code' => 0, 'msg' => $validate->getError()]);
        }

        $res = (new ForumApplyForManagerModel())->where(['status' => 0])->find($data['apply_for_manager_id']);
        if (!$res) return json(['code' => 0, 'msg' => '不存在这条申请']);
        $role_info = (new ForumManagerRoleModel())->find($data['role_id']);
        if (!$role_info) return json(['code' => 0, 'msg' => '不存在此个角色']);
        $user = (new UserModel())->where(['id' => $data['user_id']])->find();
        if (!$user) return json(['code' => 0, 'msg' => '不存在该用户']);

        //检查用户是否已经在管理组了
        $manager = (new ForumManagerModel())->where(['user_id' => $res['user_id'], 'plate_id' => $data['plate_id']])->find();
        (new ForumManagerModel())->startTrans();
        try {
            switch ($data['status']) {
                case 1 :
                    if (!$manager) {
                        (new ForumManagerModel())->insert([
                            'user_id' => $data['user_id'],
                            'plate_id' => $data['plate_id'],
                            'role_id' => $data['role_id'],
                        ]);
                    }
                    break;
            }
            (new ForumApplyForManagerModel())->where(['id' => $data['apply_for_manager_id']])->delete();
            $manager->commit();
        } catch (\Exception $e) {
            $manager->rollback();
        }
        return json(['code' => 1, 'msg' => 'success']);

    }

    //关闭 or 开启 申请进入管理组的通道
    public function changeJoinChannel(Request $request)
    {
        $plate_id = $request->post('plate_id');
        $plateInfo = (new PlateModel())->where(['id' => $plate_id])->find();

        $status = $plateInfo['is_allow_join_manager'] ? 0 : 1;

        (new PlateModel())->where(['id' => $plate_id])->update([
            'status' => $status,
        ]);
        return json(['code' => 1, 'msg' => 'success']);
    }

}
