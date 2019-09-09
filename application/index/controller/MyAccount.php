<?php

namespace app\index\controller;

use think\Controller;
use think\Request;
use app\index\model\UserIntegralHistory as UserIntegralHistoryModel;
use app\index\model\UserDownloadLibraryHistory as DownloadModel;
use app\index\model\Vip as VipModel;
use think\Validate;

class MyAccount extends Base
{
    public $integral_page_length = 10;

    public function index()
    {
        // 查询会员等级
        if ($this->userInfo['vip_id'] == 0){
            $vip_name = '普通会员';
        }else{
            $vip_name = (new VipModel())->where(['id'=>$this->userInfo['vip_id']])->value('vip_name');
        }

        $count = (new DownloadModel())->where(['user_id'=>$this->userInfo['id']])->count();
        $account = [
            'vip_name'  => $vip_name,
            'integral'  => $this->userInfo['integral'],
            'download_count'     => $count,
            'profit_integral' => $this->userInfo['profit_integral'],
            'pay_money' => $this->userInfo['pay_money'],
        ];

        $integral = (new UserIntegralHistoryModel())->where(['user_id'=>$this->userInfo['id']])
            ->field('type,integral,create_time')
            ->order('id','desc')->limit(0,$this->integral_page_length)->select();

        $integralCount = (new UserIntegralHistoryModel())->where(['user_id'=>$this->userInfo['id']])->count();

        $this->assign('integral',$integral);
        $this->assign('integralCount',$integralCount);

        $this->assign('account',$account);
        $this->assign('integral_page_length',$this->integral_page_length);

        return $this->fetch();
    }


    /**
     * 我的筑手币
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getIntegralHistory(Request $request)
    {
        $data = $request->post();
        $rules = [
            'page'  => 'require',
        ];

        $messages = [
            'page.require'  => '缺少参数 page',
        ];

        $validate = new Validate($rules,$messages);
        if (!$validate->check($data)){
            return json(['code'=>0,'msg'=>$validate->getError()]);
        }
        $start = $data['page'] * $this->integral_page_length - $this->integral_page_length;

        $result = (new UserIntegralHistoryModel())->where(['user_id'=>$this->userInfo['id']])
            ->field('type,integral,create_time')
            ->order('id','desc')->limit($start,$this->integral_page_length)->select();


        $this->assign('integral',$result);

        return json(['code'=>1,'data'=>$this->fetch('my_account/integral_list')]);

    }

}
