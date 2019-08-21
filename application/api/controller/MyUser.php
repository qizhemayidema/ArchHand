<?php

namespace app\api\controller;

use think\Controller;
use think\Request;
use think\Validate;
use app\api\model\UserIntegralHistory as UserIntegralHistoryModel;
use app\api\model\Vip as VipModel;

class MyUser extends Base
{
    /**
     * 我的账号
     */
    public function info()
    {
        // 查询会员等级
        if ($this->userInfo['vip_id'] == 0){
            $vip_name = '普通会员';
        }else{
            $vip_name = (new VipModel())->where(['id'=>$this->userInfo['vip_id']])->value('vip_name');
        }
        $result = [
            'vip_name'  => $vip_name,
            'integral'  => $this->userInfo['integral'],
        ];

        return json(['code'=>1,'data'=>$result]);
    }

    /**
     * 我的筑手币
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function integralHistory(Request $request)
    {
        $data = $request->post();
        $rules = [
            'page'  => 'require',
            'page_length'   => 'require'
        ];

        $messages = [
            'page.require'  => '缺少参数 page',
            'page_length.require' => '缺少参数 page_length',
        ];

        $validate = new Validate($rules,$messages);
        if (!$validate->check($data)){
            return json(['code'=>0,'msg'=>$validate->getError()]);
        }
        $start = $data['page'] * $data['page_length'] - $data['page_length'];

        $result = (new UserIntegralHistoryModel())->where(['user_id'=>$this->userInfo['id']])
                ->field('type,integral,create_time')
                ->order('id','desc')->limit($start,$data['page_length'])->select();

        $count = (new UserIntegralHistoryModel())->where(['user_id'=>$this->userInfo['id']])->count();
        return json(['code'=>1,'data'=>$result,'count'=>$count]);

    }


}
