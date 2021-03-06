<?php

namespace app\index\controller;

use function Composer\Autoload\includeFile;
use think\Controller;
use think\exception\HttpException;
use think\Request;
use app\index\model\ForumManagerPermission as PermissionModel;
use app\index\model\ForumManager as ManagerModel;
use app\index\model\ForumManagerRole as RoleModel;
use app\index\model\ForumApplyForManager as ApplyModel;
use app\index\model\ForumPlate as PlateModel;
use app\index\model\Forum as ForumModel;
use app\index\model\ForumComment as ForumCommentModel;
use app\index\model\User as UserModel;
use think\Validate;
use Yansongda\Pay\Exceptions\Exception;

/**
 * 此模块 plate_id 无论哪些路由 必须传
 * Class PlateManager
 * @package app\index\controller
 */
class PlateManager extends Base
{
    public $userPermissionList = [];

    public $role_id = null;

    public $plate_id = null;

    public $managerMaxLength = 6;   //最多只能有六个管理员

    public $roleMaxLength = 6;      //最多只能由六个角色

    public $pageLength = 10;        //分页

    //判断该用户是否为管理员 且是否有权限进入此处路由
    public function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub
        $this->init();
        if (!$this->checkPermission()) {
            if (request()->isAjax()){
                header('Content-type: application/json');
                exit(json_encode(['code' => 0, 'msg' => '操作越权'], 256));
            }else{
                throw new HttpException(404);
            }
        }
    }

    //管理页面
    public function index(Request $request)
    {
        //查找板块
        $plateInfo = (new PlateModel())->where(['is_delete' => 0])->find($this->plate_id);
        if (!$plateInfo) {   //板块不存在或被删除
            throw new HttpException(404);
        }
        //查询角色列表 这个无需分页
        $role = $this->getRoleList();
        //查询成员列表 这个无需分页
        $manager = $this->getManagerList();

        //查询审核处理  优先待审核
        $applyData = $this->getApplyList();
        $apply = $applyData['data'];
        $applyNum = $applyData['number'];

        $permission = (new PermissionModel())->select()->toArray();

//        print_r($apply);die;

        $this->assign('apply', $apply);
        $this->assign('apply_num', $applyNum);
        $this->assign('manager', $manager);
        $this->assign('role', $role);
        $this->assign('role_id', $this->role_id);
        $this->assign('plate_info', $plateInfo);
        $this->assign('permission', $permission);
        $this->assign('page_length', $this->pageLength);

        return $this->fetch();
    }


    /****************************角色相关*********************************************/

    //添加角色页面
    public function addRole()
    {
        //查询所有权限
        $permissionList = (new PermissionModel())->select();

        $this->assign('plate_id', $this->plate_id);
        $this->assign('permission', $permissionList);
        return $this->fetch();
    }

    //添加角色动作
    public function saveRole(Request $request)
    {
        $data = $request->post();
        $rules = [
            'role_name' => 'require|chsAlphaNum|max:6',
            'per_ids' => 'require',
            '__token__' => 'token',
            'role_desc' => 'max:18',
        ];

        $messages = [
            'role_name.require' => '角色名称必须填写',
            'role_name.chsAlphaNum' => '角色名称只能是汉字 字母 数字',
            'role_name.max' => '角色最多6个字符',
            'per_ids.require' => '角色所需权限至少选择一项',
            'role_desc.max' => '备注最多18个字符',
            '__token__.token' => '请刷新后重新提交',
        ];

        $validate = new Validate($rules, $messages);
        if (!$validate->check($data)) {
            return json(['code' => 0, 'msg' => $validate->getError()]);
        }
        $roleModel = new RoleModel();
        //判断库中数量
        $sum = $roleModel->where(['plate_id' => $this->plate_id])->count();
        if ($sum >= $this->roleMaxLength) {
            return json(['code' => 0, 'msg' => '无法继续添加,角色最多只能存在' . $this->roleMaxLength . '个']);
        }

        $insert = [
            'role_name' => $data['role_name'],
            'role_desc' => $data['role_desc'],
            'plate_id' => $this->plate_id,
            'permission_ids' => implode(',', $data['per_ids']),
        ];

        //入库
        $roleModel->insert($insert);

        return json(['code' => 1, 'msg' => 'success']);
    }

    //修改角色页面
    public function readRole(Request $request)
    {
        $role_id = $request->get('role_id');
        if (!$role_id) throw new HttpException(404);
        //查询所有权限
        $permissionList = (new PermissionModel())->select();

        //查询角色信息
        $roleInfo = (new RoleModel())->where(['id' => $role_id])->where(['plate_id' => $this->plate_id])->find()->toArray();
        if (!$roleInfo) throw new HttpException(404);
        $roleInfo['permission'] = (new PermissionModel())->whereIn('id', $roleInfo['permission_ids'])->column('id');


        $this->assign('plate_id', $this->plate_id);
        $this->assign('permission', $permissionList);
        $this->assign('role_info', $roleInfo);
        return $this->fetch();
    }

    //修改角色动作
    public function updateRole(Request $request)
    {
        $data = $request->post();
        $rules = [
            'role_id' => 'require',
            'role_name' => 'require|chsAlphaNum|max:6',
            'per_ids' => 'require',
            '__token__' => 'token',
            'role_desc' => 'max:18',
        ];

        $messages = [
            'role_id.require' => '非法操作',
            'role_name.require' => '角色名称必须填写',
            'role_name.chsAlphaNum' => '角色名称只能是汉字 字母 数字',

            'role_name.max' => '角色最多6个字符',
            'per_ids.require' => '角色所需权限至少选择一项',
            'role_desc.max' => '备注最多18个字符',
            '__token__.token' => '请刷新后重新提交',
        ];

        $validate = new Validate($rules, $messages);
        if (!$validate->check($data)) {
            return json(['code' => 0, 'msg' => $validate->getError()]);
        }
        $roleModel = new RoleModel();

        $update = [
            'role_name' => $data['role_name'],
            'role_desc' => $data['role_desc'],
            'permission_ids' => implode(',', $data['per_ids']),
        ];

        //入库
        $roleModel->where(['id' => $data['role_id'], 'plate_id' => $this->plate_id])->update($update);

        return json(['code' => 1, 'msg' => 'success']);
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
        $res = (new ManagerModel())->where(['plate_id' => $this->plate_id, 'role_id' => $data['role_id']])->find();
        if ($res) {
            return json(['code' => 0, 'msg' => '有管理员正在使用此角色,不能删除']);
        }
        (new RoleModel())->where(['id' => $data['role_id']])->delete();

        return json(['code' => 1, 'msg' => 'success']);

    }

    /**
     * 获取角色列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getRoleList()
    {
        //获取角色名称 角色备注
        $roleList = (new RoleModel())->where(['plate_id' => $this->plate_id])->order('id')->select()->toArray();
        foreach ($roleList as $key => $value) {
            $roleList[$key]['permission'] = (new PermissionModel())->whereIn('id', $value['permission_ids'])->select()->toArray();
        }
        return $roleList;

    }

/**********************************************************************************/
    //审核同意用户进入管理组申请
    public function managerJoin(Request $request)
    {
        $data = $request->post();
        $rules = [
            'apply_id' => 'require',
            'role_id' => 'require',
        ];

        $messages = [
            'apply_id.require' => '缺少参数',
            'role_id.require' => '缺少参数2',
        ];

        $validate = new Validate($rules, $messages);
        if (!$validate->check($data)) {
            return json(['code' => 0, 'msg' => $validate->getError()]);
        }

        $managerModel = new ManagerModel();
        $roleModel = new RoleModel();
        $applyModel = new ApplyModel();

        $res = $applyModel->where(['status' => 0])->find($data['apply_id']);
        if (!$res) return json(['code' => 0, 'msg' => '不存在这条申请']);
        $role_info = $roleModel->where(['plate_id' => $this->plate_id])->find($data['role_id']);
        if (!$role_info) return json(['code' => 0, 'msg' => '不存在此个角色']);
        //检查用户是否已经在管理组了
        $manager = $managerModel->where(['user_id' => $res['user_id'], 'plate_id' => $this->plate_id])->find();
        if ($manager) return json(['code' => 0, 'msg' => '该用户已在管理组中,请不要重复添加']);

        //检查管理组成员数量
        $sum = $managerModel->where(['plate_id' => $this->plate_id])->count();
        if ($sum >= $this->managerMaxLength) {
            return json(['code' => 0, 'msg' => '添加失败,管理组成员最多只能有' . $this->managerMaxLength . '位']);
        }

        $managerModel->startTrans();
        try {
            $managerModel->insert([
                'user_id' => $res['user_id'],
                'plate_id' => $data['plate_id'],
                'role_id' => $data['role_id'],
                'create_time' => time(),
            ]);
            $applyModel->where(['id' => $data['apply_id'], 'plate_id' => $this->plate_id, 'status' => 0])->update(['update_time' => time(), 'status' => 1]);
            $managerModel->commit();
        } catch (\Exception $e) {
            $managerModel->rollback();
            return json(['code' => 0, 'msg' => $e->getMessage()]);
        }
        return json(['code' => 1, 'msg' => 'success']);

    }

    //审核拒绝用户进入管理组
    public function managerNotJoin(Request $request)
    {
        $data = $request->post();
        $rules = [
            'apply_id' => 'require',
        ];

        $messages = [
            'apply_id.require' => '缺少参数',
        ];

        $validate = new Validate($rules, $messages);
        if (!$validate->check($data)) {
            return json(['code' => 0, 'msg' => $validate->getError()]);
        }

        $applyModel = new ApplyModel();

        $res = $applyModel->where(['status' => 0])->find($data['apply_id']);
        if (!$res) return json(['code' => 0, 'msg' => '不存在这条申请']);

        $applyModel->where(['id' => $data['apply_id'], 'plate_id' => $this->plate_id, 'status' => 0])->update(['update_time' => time(), 'status' => 2]);

        return json(['code' => 1, 'msg' => 'success']);

    }


    //关闭 or 开启 申请进入管理组的通道
    public function changeJoinChannel(Request $request)
    {
        $plate_id = $request->post('plate_id');
        $plateInfo = (new PlateModel())->where(['id' => $plate_id])->find();

        $status = $plateInfo['is_allow_join_manager'] ? 0 : 1;

        (new PlateModel())->where(['id' => $plate_id])->update([
            'is_allow_join_manager' => $status,

        ]);
        return json(['code' => 1, 'msg' => 'success']);
    }

    //修改管理组某人的角色
    public function changeManagerRole(Request $request)
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
        $roleInfo = (new RoleModel())->where(['plate_id' => $data['plate_id'], 'id' => $data['role_id']])->find()->toArray();
        if (!$roleInfo) {
            return json(['code' => 0, 'msg' => '没有此角色']);
        }
        //检查用户是否管理组人员
        $manager = (new ManagerModel())->where(['plate_id' => $data['plate_id'], 'user_id' => $data['user_id']])->find();
        if (!$manager) {
            return json(['code' => 0, 'msg' => '该用户不是管理组人员']);
        }
        if ($manager['role_id'] == 0) {
            return json(['code' => 0, 'msg' => '非法操作']);
        }

        (new ManagerModel())->where(['plate_id' => $data['plate_id'], 'user_id' => $data['user_id']])->update(['role_id' => $data['role_id']]);

        return json(['code' => 1, 'msg' => 'success']);
    }

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
        $manager = (new ManagerModel())
            ->where(['plate_id' => $data['plate_id'], 'user_id' => $data['user_id']])
            ->find();
        if (!$manager || $manager['role_id'] == 0) {
            return json(['code'=>0,'msg'=>'不是此管理组成员']);
        }
        $manager->delete();
        return json(['code' => 1, 'msg' => 'success']);
    }

    //获取管理组列表
    public function getManagerList()
    {
        $manager_list = (new ManagerModel())->alias('manager')
            ->join('user user', 'user.id = manager.user_id')
            ->leftJoin('forum_manager_role role', 'role.id = manager.role_id')
            ->where(['manager.plate_id' => $this->plate_id])
            ->field('user.id user_id,manager.create_time,user.nickname,user.avatar_url,role.role_name,manager.role_id')
            ->order('manager.role_id')
            ->order('manager.id', 'desc')
            ->select();

        return $manager_list;
    }

    //获取申请列表
    public function getApplyList($type = null)
    {
        $request = request();
        $type = $request->post('type') ?? 0;   //默认未审核
        $page = $request->post('page') ?? 1;

        $start = $page * $this->pageLength - $this->pageLength;
        $data = (new ApplyModel())->alias('apply')
            ->join('user user', 'apply.user_id = user.id')
            ->where(['apply.plate_id' => $this->plate_id])
            ->where(['apply.status' => $type])
            ->field('apply.create_time,apply.update_time,apply.apply_for_desc,user.nickname,user.avatar_url,apply.id apply_id,apply.status')
            ->limit($start, $this->pageLength)
            ->select();
        if ($request->isGet()) {
            $count = (new ApplyModel())->field('count(*) count')->where(['plate_id' => $this->plate_id])->group('status')->column('count(*) count', 'status');

            return ['data' => $data, 'number' => $count];
        }
        $this->assign('apply', $data);
        return json(['code' => 1, 'data' => $this->fetch('plate_manager/apply_list')]);
    }


/***********************管理员一些操作******************************/
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
        $msg = $status ? '已加精' : '已取消加精';
        $forumInfo->save(['is_classics' => $status]);

        return json(['code' => 1, 'msg' => $msg]);
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
        $msg = $status ? '已置顶' : '已取消置顶';
        $forumInfo->save(['is_top' => $status]);

        return json(['code' => 1, 'msg' => $msg]);
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

        $callbackUrl = url("forumPlateIndex",['plate_id'=>$data['plate_id']]);
        return json(['code' => 1, 'msg' => '操作成功','callbackUrl'=>$callbackUrl]);
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
        $plateInfo = (new PlateModel())->where(['id'=>$this->plate_id])->find();
        if (!$forumInfo) {
            return json(['code' => 0, 'msg' => '该帖子不存在']);
        }
        try{
            (new ForumCommentModel())->where(['id' => $data['forum_comment_id']])->update(['status'=>0]);
            if($plateInfo){
                if ($plateInfo['comment_num'] > 0){
                    $plateInfo->setDec('comment_num');
                }else{
                    $plateInfo->save(['comment_num'=>0]);
                }
            }
            if ($forumInfo['comment_num'] > 0){
                $forumInfo->setDec('comment_num');
            }else{
                $forumInfo->save(['comment_num'=>0]);
            }
        }catch(\Exception $e){
            return json(['code'=>0,'msg'=>'操作失败']);
        }


        return json(['code' => 1, 'msg' => '删除成功']);
    }

/********************************************************************/

    //初始化数据
    protected function init()
    {

        $user_info = $this->userInfo;


        $this->plate_id = request()->param('plate_id');
        if (!$this->plate_id) {
            throw new HttpException(404);
        }
        //获取role_id
        $this->role_id = (new ManagerModel())->where(['user_id' => $user_info['id'], 'plate_id' => $this->plate_id])->value('role_id');
        if ($this->role_id != 0) {
            //获取用户的权限
            $per_ids = (new RoleModel())->where(['id' => $this->role_id])->value('permission_ids');
            $perTemp = (new PermissionModel())->whereIn('id', $per_ids)->select();
            foreach ($perTemp as $key => $value) {
                $this->userPermissionList[] = strtolower($value['controller'] . '.' . $value['action']);
            }
        }
    }

    //检查权限
    protected function checkPermission($controller = '', $action = '')
    {
        if ($this->role_id === null) {
            return false;
        }
        $controller = $controller != '' ? strtolower($controller) : request()->controller(true);
        $action = $action != '' ? strtolower($action) : request()->action(true);
        if ($this->role_id == 0) return true;

        $except = [
//            'index.index',
        ];
        if (in_array($controller . '.' . $action, $except)) {
            return true;
        }
        if (in_array($controller . '.' . $action, $this->userPermissionList)) {
            return true;
        }
        return false;
    }

}
