<?php

namespace app\index\controller;

use think\Controller;
use think\Request;
use app\api\model\User as UserModel;

class SignIn extends Base
{
    //签到页面
    public function index()
    {

        $user_info = $this->userInfo;
        $result = [
            'is_sign' => true,
            'last_sign_in_time' => $user_info['last_sign_in_time'],
            'sign_in_integral' => $this->getConfig('sign_in_integral'),
            'last_sign_in_num' => $user_info['last_sign_in_num'],
        ];
        if (date('Y-m-d', $result['last_sign_in_time']) != date('Y-m-d', time())) {
            $result['is_sign'] = false;
        }


        //如果上次签到的时间不是今天也不是昨天,那就断签了
        if (date("Y-m-d", $result['last_sign_in_time']) != date('Y-m-d', time()) &&
            date("Y-m-d", $result['last_sign_in_time']) != date('Y-m-d', time() - 86400)) {
            $result['last_sign_in_num'] = 0;
        }

        $this->assign('sign_in', $result);

        return $this->fetch();
    }

    //签到动作
    public function signIn()
    {
        $integral_list = $this->getConfig('sign_in_integral');
        if ($this->userInfo['last_sign_in_num'] == 0){
            //第一次签到
            (new UserModel())->where(['id'=>$this->userInfo['id']])->update([
                'last_sign_in_num'  => 1,
                'last_sign_in_time' => time(),
            ]);
            $this->updateUserIntegral(1,$integral_list[1]);
            return json(['code'=>1,'msg'=>$integral_list[1]]);
        }
        $last_sign_in_time_tomorrow = date('Y-m-d',$this->userInfo['last_sign_in_time'] + 86400);
        $today = date('Y-m-d',time());
        if ($last_sign_in_time_tomorrow == $today){ //说明是连续签到
            //签到
            $number = $this->userInfo['last_sign_in_num'];
            if ($number == 7){$number = 0;}

            $number ++;
            //第一次签到
            (new UserModel())->where(['id'=>$this->userInfo['id']])->update([
                'last_sign_in_num'  => $number,
                'last_sign_in_time' => time(),
            ]);
            $this->updateUserIntegral(1,$integral_list[$number]);

            return json(['code'=>1,'msg'=>$integral_list[$number]]);


        }else if(date('Y-m-d',$this->userInfo['last_sign_in_time']) != $today) { //最后签到时间){  //不是连续签到
            (new UserModel())->where(['id' => $this->userInfo['id']])->update([
                'last_sign_in_num' => 1,
                'last_sign_in_time' => time(),
            ]);
            $this->updateUserIntegral(1, $integral_list[1]);
            return json(['code' => 1, 'msg' => $integral_list[1]]);
        }else{
            return json(['code' => 0, 'msg' => '您今天已经签到过了,不能再次签到哦']);
        }


    }
}
