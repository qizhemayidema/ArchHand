<?php

namespace app\index\controller;

use think\Controller;
use think\Db;
use think\Exception;
use think\Request;
use think\Validate;
use app\index\model\Transfer as TransferModel;
use Yansongda\Pay\Pay as PaySdk;
use Yansongda\Pay\Log;


class Transfer extends Base
{
    public function index()
    {
        $this->assign('user_info',$this->userInfo);
        $this->assign('integral_scale',$this->getConfig('integral_scale'));
        return $this->fetch();
    }

    public function transfer(Request $request)
    {
        $user_info = $this->userInfo;
        $data = $request->param();
        $rules = [
            'transfer_money'    => 'require|number',
            'payee_account'     => 'require',
            '__token__'         => 'token',
        ];
        $messages = [
            'transfer_money.require'    => '提现金额必须填写',
            'transfer_money.number'     => '提现金额必须为正整数',
            'payee_account.require'     => '提现账户必须填写',
            '__token__.token'           => '请刷新页面后重新提现~',
        ];
        $validate = new Validate($rules,$messages);
        if (!$validate->check($data)){
            return json(['code'=>0,'msg'=>$validate->getError()]);
        }
        if ($data['transfer_money'] == 0) return json(['code'=>0,'msg'=>'提现金额不能为0']);
        //判断用户余额
        if ($user_info['profit_integral'] < $data['transfer_money'] * 10){
            return json(['code'=>0,'msg'=>'账号余额不足']);
        }
        Db::startTrans();
        try{
            throw new Exception('暂不开通');
            //创建数据 返回 提现单号
            $out_biz_no = $this->makeTransferData($data['transfer_money'],$data['payee_account']);
            $order = [
                'out_biz_no' => $out_biz_no,
                'payee_type' => 'ALIPAY_LOGONID',
                'payee_account' => $data['payee_account'],
                'amount' => $data['transfer_money'],
            ];
            //调起来提现
            $aliPay = PaySdk::alipay($this->getTransferConfig());
            $result = $aliPay->transfer($order);
            if ($result['msg'] == 'Success'){   //直接记录
                $this->updateUserIntegral(11,$data['transfer_money'] * 10);
            }else{
                throw new Exception('提现失败');
            }
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
//            return json(['code'=>0,'msg'=>'提现失败 请稍后重试']);
            return json(['code'=>0,'msg'=>$e->getMessage()]);
        }

        return json(['code'=>1,'msg'=>'success']);
    }

    /**
     * @param $transfer_money int 支付金额
     * @param $payee_account string 提现支付宝账户
     * @return mixed
     */
    public function makeTransferData($transfer_money,$payee_account)
    {
        $insert = [
            'out_biz_no' => $this->createTransferCode(),
            'transfer_money'    => $transfer_money,
            'payee_account'     => $payee_account,
            'create_time'       => time(),
        ];
        (new TransferModel())->insert($insert);

        return $insert['out_biz_no'];

    }

    private function getTransferConfig()
    {
        return [
            'app_id' => env('ALIPAY.APP_ID'),
            'notify_url' => \think\facade\Request::domain() . url('/index/Pay/notify'),
            'return_url' => url('myAccount'),
            'ali_public_key' => env('ALIPAY.PUBLIC_KEY'),
            // 加密方式： **RSA2**
            'private_key' => env('ALIPAY.PRIVATE_KEY'),
            'log' => [ // optional
                'file' => './logs/alipay.log',
                'level' => 'info', // 建议生产环境等级调整为 info，开发环境为 debug
                'type' => 'single', // optional, 可选 daily.
                'max_file' => 30, // optional, 当 type 为 daily 时有效，默认 30 天
            ],
            'http' => [ // optional
                'timeout' => 5.0,
                'connect_timeout' => 5.0,
            ],
        ];
    }

    /**
     * 生成唯一提现单号
     * @return string
     */
    private function createTransferCode()
    {
        $order_date = date('Y-m-d');

        //订单号码主体（YYYYMMDDHHIISSNNNNNNNN）

        $order_id_main = date('YmdHis') . rand(10000000, 99999999);

        //订单号码主体长度

        $order_id_len = strlen($order_id_main);

        $order_id_sum = 0;

        for ($i = 0; $i < $order_id_len; $i++) {

            $order_id_sum += (int)(substr($order_id_main, $i, 1));

        }

        //唯一订单号码（YYYYMMDDHHIISSNNNNNNNNCC）

        return $order_id_main . str_pad((100 - $order_id_sum % 100) % 100, 2, '0', STR_PAD_LEFT);
    }
}
