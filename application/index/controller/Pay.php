<?php

namespace app\index\controller;

use think\Db;
use think\Request;
use think\Validate;
use Yansongda\Pay\Pay as PaySdk;
use Yansongda\Pay\Log;
use app\index\model\Order as OrderModel;
use app\index\model\User as UserModel;


class Pay extends Base
{
    public $integralScale = 10;


    public function index()
    {
        return $this->fetch();
    }

    public function pay(Request $request)
    {
        $integralScale = $this->getConfig('integral_scale');
        //获取用户信息
        $user_info = $this->userInfo;

        $rules = ['pay_money' => 'require|number'];
        $messages = ['pay_money.number' => '必须为整数',
            'pay_money.require' => '必须填写付款金额'];
        $data = $request->param();

        $validate = new Validate($rules, $messages);

        if (!$validate->check($data)) {

            return json(['code' => 0, 'msg' => $validate->getError()]);
        }

        if ($data['pay_money'] == 0) return json(['code' => 0, 'msg' => '付款金额不能为0']);

        $order = [
            'out_trade_no' => $this->makeOrder($data['pay_money']),
//            'total_amount' => '0.01',
            'total_amount' => $data['pay_money'], //这个才是真实付款金额
            'subject' => '充值筑手币+' . ($data['pay_money'] * $integralScale),
        ];


        $alipay = PaySdk::alipay($this->getPayConfig())->web($order);

        return $alipay->send();
    }

    public function notify(Request $request)
    {
        $aliPay = PaySdk::alipay($this->getPayConfig());

        $integralScale = $this->getConfig('integral_scale');

        Db::startTrans();
        try {
            $result = $aliPay->verify();
            //查询订单信息
            $order = (new OrderModel())->where(['order_code' => $result->out_trade_no])->find();
            //查询用户信息
            $user_info = (new UserModel())->where(['id' => $order['user_id']])->find();
            //订单改变状态
            $order->save(['status' => 2, 'pay_time' => time()]);
            //计入筑手币变动记录
            $this->updateUserIntegral(2, $order['pay_money'] * $integralScale, $user_info['id']);

            Db::commit();
            return $aliPay->success()->send();
        } catch (\Exception $e) {
            file_put_contents('./1.txt', $e->getMessage());
        }

    }

    /**
     * 获取接口所需配置数组
     * @return array
     */
    private function getPayConfig()
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
     * 创建订单数据 返回订单号
     * @param  $pay_money string 支付金额
     * @return string            订单号
     */
    private function makeOrder($pay_money)
    {
        $user_info = $this->userInfo;
        //生成订单
        $order_code = $this->createOrderCode();
        $insert = [
            'order_code' => $order_code,
            'user_id' => $user_info['id'],
            'pay_money' => $pay_money,
            'order_title' => '充值筑手币-' . $pay_money . '元',
            'create_time' => time(),
        ];
        (new OrderModel())->insert($insert);

        return $order_code;
    }

    /**
     * 生成唯一订单号
     * @return string
     */
    private function createOrderCode()
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
