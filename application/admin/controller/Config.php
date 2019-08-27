<?php

namespace app\admin\controller;

use think\Controller;
use think\Env;
use think\facade\Cache;
use think\Request;

class Config extends Base
{
    const WEB_SITE_PATH = CONFIG_PATH . 'web_site.json';

    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $data = json_decode(file_get_contents(self::WEB_SITE_PATH));

        $sign = [];

        if(!$data){
            return $this->fetch('config/index');
        }

        foreach ($data->sign_in_integral as $k => $v) {
            $sign[$k] = $v;
        }

        $sign = implode(',', $sign);

        $data->sign_in_integral = $sign;

        $this->assign('site', $data);

        return $this->fetch('config/index');
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function add()
    {
        //
    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request $request
     * @return \think\Response
     */
    public function save(Request $request)
    {

        $form = $request->post();

        $validate = new \app\admin\validate\Config();
        if (!$validate->check($form)) {
            return jsone(0, $validate->getError());
        }


        $sign_in_integral = explode(',', trim($form['sign_in_integral']));
        $sign = [];
        foreach ($sign_in_integral as $k => $v) {
            $sign[$k + 1] = $v;

        }

        //今日力推

        $today_recommend = [
            'today_title'=>$form['today_title'],
            'today_url'=>$form['today_url'],
            'today_content'=>$form['today_content'],
            'today_pic'=>$form['today_pic'],
        ];


        $data = [
            'title' => trim($form['title']),
            'keyword' => trim($form['keyword']),
            'description' => trim($form['description']),
            'icp' => trim($form['icp']),
            'announcement' => trim($form['announcement']),
            'copyright' => trim($form['copyright']),
            'qq' => trim($form['qq']),
            'phone' => trim($form['phone']),
            'qr_code' => trim($form['qr_code']),
            'issue_integral' => trim($form['issue_integral']),
            'issue_integral_count'=>trim($form['issue_integral_count']),
            'comment_integral' => trim($form['comment_integral']),
            'comment_integral_count'=>trim($form['comment_integral_count']),
//            'ratio_integral' => trim($form['ratio_integral']),
            'service_charge_integral' => trim($form['service_charge_integral']),
            'sign_in_integral' => $sign,
            'today_recommend'=>$today_recommend,
        ];

        $data = json_encode($data);

//        dump($data);
//        die;
        $status = file_put_contents(self::WEB_SITE_PATH, $data);
        if ($status) {
            return jsone(1, '设置成功');
        } else {
            return jsone(0, '设置失败');
        }

    }


    /**
     * 显示指定的资源
     *
     * @param  int $id
     * @return \think\Response
     */
    public function read($id)
    {
        //
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param  int $id
     * @return \think\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * 保存更新的资源
     *
     * @param  \think\Request $request
     * @param  int $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * 删除指定资源
     *
     * @param  int $id
     * @return \think\Response
     */
    public function delete($id)
    {
        //
    }
}
