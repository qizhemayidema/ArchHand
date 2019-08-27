<?php

namespace app\api\controller;

use think\Controller;
use think\Exception;
use think\Request;
use think\Response;
use app\api\model\User as UserModel;
use app\api\model\UserIntegralHistory as IntegralHistoryModel;

class Base extends Controller
{
    const WEB_SITE_PATH = CONFIG_PATH . 'web_site.json';

    private $userInfo = [];

    public function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub

        $this->getUserInfo();

    }

    private function getUserInfo()
    {

        $token = \think\facade\Request::param('token');
        if ($token) {
            $this->userInfo = (new UserModel())->where(['token' => $token, 'is_delete' => 1])->find();
            if (!$this->userInfo) {
                header('Content-type: application/json');
                exit(json_encode(['code' => 0, 'msg' => '获取用户信息失败'], 256));
            }
        }
    }

    public function __get($name)
    {
        // TODO: Implement __get() method.
        if (!$this->$name) {
            header('Content-type: application/json');
            exit(json_encode(['code' => 0, 'msg' => '获取用户信息失败'], 256));
        }
        return $this->$name;
    }

    /**
     * 添加 积分变动记录到表中
     * @param $integral
     */
    protected function addUserIntegralHistory($type, $integral,$user_id = null)
    {
        $user_id = $user_id ? $user_id : $this->userInfo['id'];

        $result = [
            'type' => $type,
            'integral' => $integral,
            'user_id' => $user_id,
            'create_time' => time(),
        ];
        (new IntegralHistoryModel())->insert($result);
        $upArr = [1, 2, 5, 6, 7, 8, 9, 10];
        $downArr = [3, 4];
        $userModel = (new UserModel())->where(['id' => $user_id]);
        if (in_array($type, $upArr)) {
            $userModel->setInc('integral', $integral);
        } else if (in_array($type, $downArr)) {
            $userModel->setDec('integral', $integral);
        }
    }

    /**
     * 获取配置信息
     * @param $name
     * @return mixed
     */
    protected function getConfig($name)
    {
        $configObject = json_decode(file_get_contents(self::WEB_SITE_PATH));
        $configPath = explode('.', $name);
        $temp = $configObject;
        try {
            foreach ($configPath as $key => $value) {
                $temp = $temp->$value;
            }
            if (!$temp) throw new Exception();
        } catch (Exception $e) {
            header('Content-type: application/json');
            exit(json_encode(['code' => 0, 'msg' => '获取配置失败'], 256));
        }

        return $temp;
    }
}
