<?php

namespace app\api\controller;

use think\Controller;
use think\Request;
use app\api\model\User as UserModel;
use app\api\model\UserIntegralHistory as IntegralHistoryModel;


class SignIn extends Base
{
    const WEB_SITE_PATH = CONFIG_PATH . 'web_site.json';
    /**
     * 签到动作
     */
    public function getIntegral(Request $request)
    {
        $integral_list = json_decode(file_get_contents(self::WEB_SITE_PATH),true)['sign_in_integral'];
        if ($this->userInfo['last_sign_in_num'] == 0){
            //第一次签到
            (new UserModel())->where(['id'=>$this->userInfo['id']])->update([
                'last_sign_in_num'  => 1,
                'last_sign_in_time' => time(),
                'integral'          => $integral_list[1] + $this->userInfo['integral'],
            ]);
            $this->addUserIntegralHistory($integral_list[1]);
            return json(['code'=>1,'msg'=>$integral_list[1]]);
        }
        $last_sign_in_time_tomorrow = date('Y-m-d',$this->userInfo['last_sign_in_time'] + 86400);
        $today = date('Y-m-d',time());
        if ($last_sign_in_time_tomorrow == $today){
            //签到
            $number = $this->userInfo['last_sign_in_num'];
            if ($number == 7){$number = 0;}

            $number ++;
            //第一次签到
            (new UserModel())->where(['id'=>$this->userInfo['id']])->update([
                'last_sign_in_num'  => $number,
                'last_sign_in_time' => time(),
                'integral'          => $integral_list[$number] + $this->userInfo['integral'],
            ]);
            $this->addUserIntegralHistory($integral_list[$number]);

            return json(['code'=>1,'msg'=>$integral_list[$number]]);
        }else{
            return json(['code'=>0,'msg'=>'您今天已经签到过了,不能再次签到哦']);
        }
    }

    /**
     * 添加 积分变动记录到表中
     * @param $integral
     */
    private function addUserIntegralHistory($integral)
    {
        $result = [
            'type'  => 1,
            'integral' => $integral,
            'user_id'   => $this->userInfo['id'],
            'create_time' => time(),
        ];

        (new IntegralHistoryModel())->insert($result);
    }
}

