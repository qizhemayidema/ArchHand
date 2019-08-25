<?php

namespace app\api\controller;

use think\Controller;
use think\Request;
use app\api\model\User as UserModel;


class SignIn extends Base
{
    /**
     * 签到动作
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function getIntegral(Request $request)
    {
        $integral_list = json_decode(json_encode($this->getConfig('sign_in_integral'),256),true);
        if ($this->userInfo['last_sign_in_num'] == 0){
            //第一次签到
            (new UserModel())->where(['id'=>$this->userInfo['id']])->update([
                'last_sign_in_num'  => 1,
                'last_sign_in_time' => time(),
                'integral'          => $integral_list[1] + $this->userInfo['integral'],
            ]);
            $this->addUserIntegralHistory(1,$integral_list[1]);
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
            $this->addUserIntegralHistory(1,$integral_list[$number]);

            return json(['code'=>1,'msg'=>$integral_list[$number]]);
        }else{
            return json(['code'=>0,'msg'=>'您今天已经签到过了,不能再次签到哦']);
        }
    }

    /**
     * 获取今天的签到状态
     * @param Request $request
     * @return \think\response\Json
     */
    public function getSignInDays(Request $request)
    {
        $user_info = $this->userInfo;
        $result = [
            'is_sign'           => true,
            'last_sign_in_time' => $user_info['last_sign_in_time'],
            'sign_in_integral'  => $this->getConfig('sign_in_integral'),
            'last_sign_in_num'  => $user_info['last_sign_in_num'],
        ];

        $today = date('Y-m-d',time());


        if (date("Y-m-d",$result['last_sign_in_time'] ) != date('Y-m-d',time()) &&
            date("Y-m-d",$result['last_sign_in_time'] - 86400 ) != date('Y-m-d',time())){
            $result['is_sign'] = false;
        }
        return json(['code'=>1,'data'=>$result]);
    }
}

