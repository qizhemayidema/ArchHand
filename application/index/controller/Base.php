<?php

namespace app\index\controller;

use app\http\middleware\IndexCheckIp;
use think\Controller;
use think\Db;
use think\Exception;
use think\facade\Session;
use think\Request;
use think\Response;
use app\index\model\User as UserModel;
use app\index\model\UserIntegralHistory as IntegralHistoryModel;
use app\index\model\ForumPlate as PlateModel;
use app\index\model\Vip as VipModel;

class Base extends Controller
{
    protected $middleware = [
        IndexCheckIp::class,
    ];

    const WEB_SITE_PATH = CONFIG_PATH . 'web_site.json';        //网站配置路径

    private $configObject = null;

    private $userInfo = [];

    protected $userInfoSessionPath = null;   // 用户session 存储路径

    public function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub

        $this->userInfoSessionPath = config('app.index_user_session_path');

        $this->getUserInfo();

        $this->loadingPublicData(\think\facade\Request::instance());
    }

    protected function getUserInfo()
    {
        $user_info = Session::get($this->userInfoSessionPath);
        if ($user_info) {
            $this->userInfo = (new UserModel())->where(['id' => $user_info['id'], 'is_delete' => 1,'status'=>0])->find();
        }
    }

    /**
     * 加载公共数据
     * @param Request $request
     */
    private function loadingPublicData(Request $request)
    {
        if (!$request->isAjax()){
            //查询社区分类
            $this->assign('forum_plate_list',(new PlateModel())->getList());
            //查询用户信息
            $this->assign('user_info',$this->userInfo);
            //右面浮动栏数据
            $this->assign('qq',$this->getConfig('qq'));             //公告
            $this->assign('phone',$this->getConfig('phone'));             //公告
            $this->assign('qr_code',$this->getConfig('qr_code'));
            //网站标题
            $this->assign('website_title',$this->getConfig('title'));
            //网站meta信息
            $this->assign('website_keywords',$this->getConfig('keyword'));
            $this->assign('website_description',$this->getConfig('description'));
            //版权与备案号
            $this->assign('website_copyright',$this->getConfig('copyright'));
            $this->assign('website_icp',$this->getConfig('icp'));
        }
    }

    public function __get($name)
    {
        // TODO: Implement __get() method.
        if ($name == 'userInfo'){
            if (!$this->$name) {
                if (\request()->isAjax()){
                    header('Content-type: application/json');
                    exit(json_encode(['code' => 0, 'msg' => '请先登陆账号~'], 256));
                }else{
                    return $this->redirect('index');
                }
            }
            return $this->$name;
        }
    }

    /**
     * 更改用户积分 只要用户积分会出现变动 只调用这个方法 只!
     * @param $type integer|string 类型
     * @param $integral integer | string 变动的积分
     * @param null $user_id 如果为null则默认当前登陆用户 否则指定用户
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    protected function updateUserIntegral($type, $integral, $user_id = null , $desc = '')
    {
        $user_id = $user_id ? $user_id : $this->userInfo['id'];

        $history = [
            'type' => $type,
            'integral' => $integral,
            'user_id' => $user_id,
            'desc'  => $desc,
            'create_time' => time(),
        ];
        /**
         * 首先要执行更改账号余额的操作 成功后才可录入历史记录
         * 此处为依靠CAP版本自旋操作 防止并发问题产生
         */
        $userModel = new UserModel();
        $integralHistModel = new IntegralHistoryModel();
        $integralScale = $this->getConfig('integral_scale');
        $upArr = [1, 2, 5, 6, 7, 8, 9, 10];
        $downArr = [3, 4,11];
        while(true){
            $userUpdate = [];   //用户表更改字段
            $user_info = $userModel->field('id,vip_id,pay_money,version,integral,profit_integral')->find($user_id);
            if (in_array($type, $upArr)) {
                //如果是2 -> 充值 则涉及判断用户会员情况
                if ($type == 2){
                    //判断用户会员情况
                    $vipInfo = (new VipModel())->where('price','<=',$user_info['pay_money'] + ($integral) / $integralScale)
                        ->order('discount')->find();
                    if ($vipInfo){
                        $userUpdate['vip_id'] = $vipInfo['id'];
                    }
                    $userUpdate['pay_money'] = $user_info['pay_money'] + ($integral / $integralScale);
                //如果是9 -> 别人下载你的文库所得 计入提现字段
                }elseif ($type == 9){   //如果此状态 integral需要扣除手续费后传进来
                    $userUpdate['profit_integral'] = $user_info['profit_integral'] + $integral;
                }
                $userUpdate['integral'] = $user_info['integral'] + $integral;
            } else if (in_array($type, $downArr)) {
                //判断 提现字段的情况 如果 筑手币扣除后小于 提现字段 则 两个字段数据变成一样的
                $userUpdate['integral'] = $user_info['integral'] - $integral;
                if ($type == 11){
                    $userUpdate['profit_integral'] = $user_info['profit_integral'] - $integral;
                }else{
                    if($userUpdate['integral']  < $user_info['profit_integral']){
                        $userUpdate['profit_integral'] = $userUpdate['integral'];
                    }
                }
            }
            $userUpdate['version'] = $user_info['version'] + 1;
            if (!$userModel->where(['id'=>$user_info['id'],'version'=>$user_info['version']])->update($userUpdate)){
                sleep(0.05);
                continue;
            }
            $integralHistModel->insert($history);
            break;
        }
    }

    /**
     * 获取配置信息
     * @param $name
     * @return mixed|null
     */
    protected function getConfig($name)
    {
        if (!$this->configObject){
            $this->configObject = json_decode(file_get_contents(self::WEB_SITE_PATH));
        }
        $configPath = explode('.', $name);
        $temp = $this->configObject;
        try {
            foreach ($configPath as $key => $value) {
                $temp = $temp->$value;
            }
            if ($temp === null) throw new Exception();
        } catch (Exception $e) {
            header('Content-type: application/json');
            exit(json_encode(['code' => 0, 'msg' => '获取配置失败'], 256));
        }
        $temp = json_decode(json_encode($temp,256),true);
        return $temp;
    }
}
