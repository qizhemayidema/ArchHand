<?php
/**
 * Created by PhpStorm.
 * User: 刘彪
 * Date: 2019/8/23
 * Time: 17:23
 */

namespace app\api\controller;


use think\Controller;
use app\api\model\User as UserModel;
use app\admin\model\ForumManager as ForumManagerModel;
use app\api\model\ForumManagerRole as RoleModel;
use app\api\model\ForumManagerPermission as PermissionModel;

/**
 * 必传 token 和 plate_id
 * Class ForumManagerBase
 * @package app\api\controller
 */
class ForumManagerBase extends Controller
{

    const WEB_SITE_PATH = CONFIG_PATH . 'web_site.json';

    private $userInfo = [];

    public $role_id;

    private $permission_list = [];

    public function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub

        $this->getManagerInfo();

        if (!$this->checkPermission()){
            header('Content-type: application/json');
            exit(json_encode(['code' => 0, 'msg' => '操作越权'], 256));
        }
    }

    private function getManagerInfo()
    {
        $token = \think\facade\Request::param('token');
        $plate_id = \think\facade\Request::param('plate_id');
        if (!$token || !$plate_id){
            header('Content-type: application/json');
            exit(json_encode(['code' => 0, 'msg' => '缺少参数'], 256));
        }
        $this->userInfo = $this->getUserInfo();

        //查找板块权限
        $this->role_id = (new ForumManagerModel())->where(['user_id'=>$this->userInfo['id'],'plate_id'=>$plate_id])->value('role_id');
        if (!$this->role_id && $this->role_id != 0){
            header('Content-type: application/json');
            exit(json_encode(['code' => 0, 'msg' => '获取角色失败'], 256));
        }
        //查找权限列表
        $permission_ids = (new RoleModel())->where(['id'=>$this->role_id])->value('permission_ids');
        $this->permission_list = (new PermissionModel())->whereIn('id',$permission_ids)->select();

    }
    private function getUserInfo()
    {

        $token = \think\facade\Request::param('token');
        if ($token) {
            $this->userInfo = (new UserModel())->where(['token' => $token, 'is_delete' => 1])->find();
            if (!$this->userInfo) {
                header('Content-type: application/json');
                exit(json_encode(['code' => -1, 'msg' => '获取用户信息失败'], 256));
            }
        }
    }


    private function checkPermission($controller = '',$action = '')
    {
        $controller = $controller != '' ? strtolower($controller) : request()->controller(true);

        $action = $action != '' ? strtolower($action) : request()->action(true);

        if ($this->role_id == 0) return true;

        $except = [
//            'index.index',
        ];
        if (in_array($controller . '.' . $action,$except)){
            return true;
        }
        if (in_array($controller . '.' . $action,$this->loginInfo['permission_list'])){
            return true;
        }
        return false;
    }

    public function __get($name)
    {
        // TODO: Implement __get() method.
        if (!$this->$name) {
            header('Content-type: application/json');
            exit(json_encode(['code' => -1, 'msg' => '获取用户信息失败'], 256));
        }
        return $this->$name;
    }

}