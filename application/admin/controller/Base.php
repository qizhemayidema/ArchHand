<?php
/**
 * Created by PhpStorm.
 * User: 刘彪
 * Date: 2019/7/22
 * Time: 14:29
 */

namespace app\admin\controller;

use app\http\middleware\LoginCheck;
use think\Controller;
use app\admin\model\Role as RoleModel;
use app\admin\model\Permission as PermissionModel;

class Base extends Controller
{
    protected $middleware = [LoginCheck::class];

    public $loginInfo;

    public function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub

        $this->loginInfo = $this->getLoginInfo();

        if (!$this->loginInfo) $this->redirect('/admin/Login/index');

        $this->assign('Base', $this);

        if (!$this->checkPermission()) {
            if (request()->isAjax()) {
                header('Content-type: application/json');
                exit(json_encode(['code' => 0, 'msg' => '操作越权'], 256));

            } else {
                $this->redirect('http://www.baidu.com');
            }
        }
    }

    private function getLoginInfo()
    {
        $loginInfo = \think\facade\Session::get('admin');

        if (!$loginInfo) return [];

        $permissionIds = (new RoleModel())->where(['id' => $loginInfo['role_id']])->value('permission_ids');

        $perInfo = (new PermissionModel())->whereIn('id', $permissionIds)->field(['controller', 'action'])->select()->toArray();

        $perList = [];
        foreach ($perInfo as $key => $value) {
            $perList[] = $value['controller'] . '.' . $value['action'];
        }
        $loginInfo['permission_list'] = $perList;

        return $loginInfo;
    }

    /**
     * @param string $controller
     * @param string $action
     * @return bool
     */
    public function checkPermission($controller = '', $action = '')
    {
        if (!$controller && !$action && request()->isGet()) return true;

        $controller = $controller != '' ? strtolower($controller) : request()->controller(true);

        $action = $action != '' ? strtolower($action) : request()->action(true);

        if ($this->loginInfo['role_id'] == 1) return true;

        $except = [
            'index.index',
            'classestagapi.gettagforcate',
            'classes.uploadVideo',
            'classes.uploadPic',
            'classes.uploadChapterPic',
            'classes.uploadChapterBase64Pic',
            'classestag.uploadPic',
            'forumplate.uploadPic',
            'library.verify'
        ];
        if (in_array($controller . '.' . $action, $except)) {
            return true;
        }
        if (in_array($controller . '.' . $action, $this->loginInfo['permission_list'])) {
            return true;
        }
        return false;
    }
}